<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace OCA\CMSPico\Service;

use OC\Security\CSP\ContentSecurityPolicyNonceManager;
use OCA\CMSPico\Model\WebsiteCore;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IRequest;

/**
 * Caches rendered pages of public websites for anonymous visitors.
 *
 * The cache key contains the website folder's etag plus every app setting
 * that influences the rendered HTML, so entries invalidate themselves when
 * content, themes, plugins or relevant configuration change - there is no
 * explicit invalidation.
 *
 * Rendered pages embed the per-request CSP nonce; the nonce used at render
 * time is stored with the entry and replaced with the current request's
 * nonce on every hit, so cached responses keep a valid, request-specific
 * CSP.
 */
class PageCacheService
{
	/** @var int Upper bound for entry lifetime; freshness comes from the etag-based key */
	private const CACHE_TTL = 86400;

	private ICache $cache;
	private IRequest $request;
	private ConfigService $configService;
	private WebsitesService $websitesService;
	private ContentSecurityPolicyNonceManager $nonceManager;

	public function __construct(
		ICacheFactory $cacheFactory,
		IRequest $request,
		ConfigService $configService,
		WebsitesService $websitesService,
		ContentSecurityPolicyNonceManager $nonceManager
	) {
		$this->cache = $cacheFactory->createDistributed('cms_pico.pages');
		$this->request = $request;
		$this->configService = $configService;
		$this->websitesService = $websitesService;
		$this->nonceManager = $nonceManager;
	}

	/**
	 * Returns the cached page, or null on a miss or non-cacheable request.
	 *
	 * @param string      $site
	 * @param string      $page
	 * @param string|null $viewer
	 * @param bool        $proxyRequest
	 *
	 * @return array{html: string, notFound: bool}|null
	 */
	public function get(string $site, string $page, ?string $viewer, bool $proxyRequest): ?array
	{
		$key = $this->getKey($site, $page, $viewer, $proxyRequest);
		if ($key === null) {
			return null;
		}

		$data = $this->cache->get($key);
		if (!is_array($data) || !isset($data['html'], $data['nonce'], $data['notFound'])) {
			return null;
		}

		return [
			'html' => str_replace($data['nonce'], $this->nonceManager->getNonce(), $data['html']),
			'notFound' => $data['notFound'],
		];
	}

	/**
	 * Stores a rendered page; no-op for non-cacheable requests.
	 *
	 * @param string      $site
	 * @param string      $page
	 * @param string|null $viewer
	 * @param bool        $proxyRequest
	 * @param string      $html
	 * @param bool        $notFound
	 */
	public function set(
		string $site,
		string $page,
		?string $viewer,
		bool $proxyRequest,
		string $html,
		bool $notFound
	): void {
		$key = $this->getKey($site, $page, $viewer, $proxyRequest);
		if ($key === null) {
			return;
		}

		$this->cache->set($key, [
			'html' => $html,
			'nonce' => $this->nonceManager->getNonce(),
			'notFound' => $notFound,
		], self::CACHE_TTL);
	}

	/**
	 * Returns the cache key, or null if the request must not be cached.
	 *
	 * Only anonymous GET requests without a query string for public
	 * websites are cacheable; everything else takes the regular render
	 * path (which enforces access control).
	 *
	 * @param string      $site
	 * @param string      $page
	 * @param string|null $viewer
	 * @param bool        $proxyRequest
	 *
	 * @return string|null
	 */
	private function getKey(string $site, string $page, ?string $viewer, bool $proxyRequest): ?string
	{
		if ($this->configService->getAppValue(ConfigService::PAGE_CACHE) !== '1') {
			return null;
		}
		if ($viewer !== null) {
			return null;
		}
		if ($this->request->getMethod() !== 'GET') {
			return null;
		}
		if (strpos($this->request->getRequestUri(), '?') !== false) {
			return null;
		}

		try {
			$website = $this->websitesService->getWebsiteFromSite($site);
			if ($website->getType() !== WebsiteCore::TYPE_PUBLIC) {
				return null;
			}

			$websiteEtag = $website->getWebsiteFolder()->getOCNode()->getEtag();
		} catch (\Exception $e) {
			return null;
		}

		return 'page.' . md5(json_encode([
			$site,
			$page,
			$proxyRequest,
			$websiteEtag,
			$website->getName(),
			$website->getTheme(),
			$this->configService->getAppValue(ConfigService::THEMES_ETAG),
			$this->configService->getAppValue(ConfigService::PLUGINS_ETAG),
			$this->configService->getAppValue(ConfigService::LINK_MODE),
			$this->configService->getAppValue(ConfigService::COMMENTS_URL),
			$this->configService->getAppValue(ConfigService::CUSTOM_DOMAINS),
		]));
	}
}
