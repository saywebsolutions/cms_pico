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

namespace OCA\CMSPico\Tests\Unit\Service;

use OC\Security\CSP\ContentSecurityPolicyNonceManager;
use OCA\CMSPico\Files\StorageFolder;
use OCA\CMSPico\Model\Website;
use OCA\CMSPico\Model\WebsiteCore;
use OCA\CMSPico\Service\ConfigService;
use OCA\CMSPico\Service\PageCacheService;
use OCA\CMSPico\Service\WebsitesService;
use OCP\Files\Folder;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PageCacheServiceTest extends TestCase
{
	/** @var array<string,mixed> */
	private array $cacheData = [];

	/** @var IRequest|MockObject */
	private $request;

	/** @var ConfigService|MockObject */
	private $configService;

	/** @var WebsitesService|MockObject */
	private $websitesService;

	/** @var ContentSecurityPolicyNonceManager|MockObject */
	private $nonceManager;

	/** @var string */
	private $websiteEtag = 'etag-1';

	/** @var int */
	private $websiteType = WebsiteCore::TYPE_PUBLIC;

	protected function setUp(): void
	{
		$this->cacheData = [];

		$this->request = $this->createMock(IRequest::class);
		$this->request->method('getMethod')->willReturn('GET');
		$this->request->method('getRequestUri')->willReturn('/sites/test_site/some/page');

		$this->configService = $this->createMock(ConfigService::class);
		$this->configService->method('getAppValue')->willReturnCallback(function (string $key) {
			return ($key === ConfigService::PAGE_CACHE) ? '1' : '';
		});

		$ocFolder = $this->createMock(Folder::class);
		$ocFolder->method('getEtag')->willReturnCallback(function () {
			return $this->websiteEtag;
		});

		$folder = $this->createMock(StorageFolder::class);
		$folder->method('getOCNode')->willReturn($ocFolder);

		$website = $this->createMock(Website::class);
		$website->method('getType')->willReturnCallback(function () {
			return $this->websiteType;
		});
		$website->method('getWebsiteFolder')->willReturn($folder);

		$this->websitesService = $this->createMock(WebsitesService::class);
		$this->websitesService->method('getWebsiteFromSite')->willReturn($website);

		$this->nonceManager = $this->createMock(ContentSecurityPolicyNonceManager::class);
		$this->nonceManager->method('getNonce')->willReturn('test-nonce');
	}

	private function createPageCacheService(): PageCacheService
	{
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cache = $this->createMock(ICache::class);
		$cache->method('get')->willReturnCallback(function (string $key) {
			return $this->cacheData[$key] ?? null;
		});
		$cache->method('set')->willReturnCallback(function (string $key, $value) {
			$this->cacheData[$key] = $value;
			return true;
		});
		$cacheFactory->method('createDistributed')->willReturn($cache);

		return new PageCacheService(
			$cacheFactory,
			$this->request,
			$this->configService,
			$this->websitesService,
			$this->nonceManager
		);
	}

	public function testRoundtrip(): void
	{
		$service = $this->createPageCacheService();

		$this->assertNull($service->get('test_site', 'some/page', null, false));

		$service->set('test_site', 'some/page', null, false, '<html>content</html>', false);

		$this->assertSame(
			['html' => '<html>content</html>', 'notFound' => false],
			$service->get('test_site', 'some/page', null, false)
		);
	}

	public function testNonceIsSubstitutedOnHit(): void
	{
		$nonceManager = $this->createMock(ContentSecurityPolicyNonceManager::class);
		$nonceManager->method('getNonce')->willReturnOnConsecutiveCalls('nonce-render', 'nonce-hit');
		$this->nonceManager = $nonceManager;

		$service = $this->createPageCacheService();

		$service->set('test_site', '', null, false, '<script nonce="nonce-render">x</script>', false);
		$cached = $service->get('test_site', '', null, false);

		$this->assertSame('<script nonce="nonce-hit">x</script>', $cached['html']);
	}

	public function testNotFoundPagesAreCached(): void
	{
		$service = $this->createPageCacheService();

		$service->set('test_site', 'missing', null, false, '<html>404</html>', true);

		$this->assertSame(
			['html' => '<html>404</html>', 'notFound' => true],
			$service->get('test_site', 'missing', null, false)
		);
	}

	public function testLoggedInViewerIsNotCached(): void
	{
		$service = $this->createPageCacheService();

		$service->set('test_site', '', 'admin', false, '<html>x</html>', false);

		$this->assertNull($service->get('test_site', '', 'admin', false));
		$this->assertSame([], $this->cacheData);
	}

	public function testDisabledCacheIsBypassed(): void
	{
		$this->configService = $this->createMock(ConfigService::class);
		$this->configService->method('getAppValue')->willReturn('0');

		$service = $this->createPageCacheService();

		$service->set('test_site', '', null, false, '<html>x</html>', false);

		$this->assertNull($service->get('test_site', '', null, false));
		$this->assertSame([], $this->cacheData);
	}

	public function testNonGetRequestIsNotCached(): void
	{
		$this->request = $this->createMock(IRequest::class);
		$this->request->method('getMethod')->willReturn('POST');
		$this->request->method('getRequestUri')->willReturn('/sites/test_site/');

		$service = $this->createPageCacheService();

		$service->set('test_site', '', null, false, '<html>x</html>', false);

		$this->assertNull($service->get('test_site', '', null, false));
	}

	public function testQueryStringIsNotCached(): void
	{
		$this->request = $this->createMock(IRequest::class);
		$this->request->method('getMethod')->willReturn('GET');
		$this->request->method('getRequestUri')->willReturn('/sites/test_site/?q=x');

		$service = $this->createPageCacheService();

		$service->set('test_site', '', null, false, '<html>x</html>', false);

		$this->assertNull($service->get('test_site', '', null, false));
	}

	public function testPrivateWebsiteIsNotCached(): void
	{
		$this->websiteType = WebsiteCore::TYPE_PRIVATE;

		$service = $this->createPageCacheService();

		$service->set('test_site', '', null, false, '<html>x</html>', false);

		$this->assertNull($service->get('test_site', '', null, false));
		$this->assertSame([], $this->cacheData);
	}

	public function testContentChangeInvalidates(): void
	{
		$service = $this->createPageCacheService();

		$service->set('test_site', '', null, false, '<html>old</html>', false);
		$this->assertNotNull($service->get('test_site', '', null, false));

		$this->websiteEtag = 'etag-2';

		$this->assertNull($service->get('test_site', '', null, false));
	}

	public function testPagesAreCachedIndependently(): void
	{
		$service = $this->createPageCacheService();

		$service->set('test_site', 'a', null, false, '<html>a</html>', false);
		$service->set('test_site', 'b', null, false, '<html>b</html>', false);

		$this->assertSame('<html>a</html>', $service->get('test_site', 'a', null, false)['html']);
		$this->assertSame('<html>b</html>', $service->get('test_site', 'b', null, false)['html']);
		$this->assertNull($service->get('test_site', 'c', null, false));
	}
}
