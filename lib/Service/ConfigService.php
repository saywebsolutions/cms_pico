<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
 * @copyright Copyright (c) 2017, Maxence Lange (<maxence@artificial-owl.com>)
 * @copyright Copyright (c) 2019, Daniel Rudolf (<picocms.org@daniel-rudolf.de>)
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

use OCA\CMSPico\AppInfo\Application;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class ConfigService
{
	/** @var string */
	public const SYSTEM_TEMPLATES = 'system_templates';

	/** @var string */
	public const CUSTOM_TEMPLATES = 'custom_templates';

	/** @var string */
	public const SYSTEM_THEMES = 'system_themes';

	/** @var string */
	public const CUSTOM_THEMES = 'custom_themes';

	/** @var string */
	public const THEMES_ETAG = 'themes_etag';

	/** @var string */
	public const SYSTEM_PLUGINS = 'system_plugins';

	/** @var string */
	public const CUSTOM_PLUGINS = 'custom_plugins';

	/** @var string */
	public const PLUGINS_ETAG = 'plugins_etag';

	/** @var string */
	public const LIMIT_GROUPS = 'limit_groups';

	/** @var string */
	public const LINK_MODE = 'link_mode';

	/** @var string */
	public const COMMENTS_URL = 'comments_url';

	/** @var string JSON object mapping domains to website names, e.g. {"example.com": "my_site"} */
	public const CUSTOM_DOMAINS = 'custom_domains';

	/** @var string Whether rendered pages of public websites are cached ('1' or '0') */
	public const PAGE_CACHE = 'page_cache';

	/** @var IConfig */
	protected $config;

	/** @var LoggerInterface */
	protected $logger;

	/** @var array<string,string> */
	protected $defaultValues;

	/**
	 * ConfigService constructor.
	 *
	 * @param IConfig         $config
	 * @param LoggerInterface $logger
	 */
	public function __construct(IConfig $config, LoggerInterface $logger)
	{
		$this->config = $config;
		$this->logger = $logger;

		$this->defaultValues = [
			self::SYSTEM_TEMPLATES => '',
			self::CUSTOM_TEMPLATES => '',
			self::SYSTEM_THEMES => '',
			self::CUSTOM_THEMES => '',
			self::THEMES_ETAG => '',
			self::SYSTEM_PLUGINS => '',
			self::CUSTOM_PLUGINS => '',
			self::PLUGINS_ETAG => '',
			self::LIMIT_GROUPS => '',
			self::LINK_MODE => (string) WebsitesService::LINK_MODE_LONG,
			self::COMMENTS_URL => '',
			self::CUSTOM_DOMAINS => '',
			self::PAGE_CACHE => '1',
		];
	}

	/**
	 * Returns the configured custom domains as a domain => site map.
	 *
	 * Domains are normalized to lowercase; invalid entries are dropped
	 * with a warning.
	 *
	 * @return array<string,string>
	 */
	public function getCustomDomains(): array
	{
		$json = $this->getAppValue(self::CUSTOM_DOMAINS);
		if (!$json) {
			return [];
		}

		$domains = json_decode($json, true);
		if (!is_array($domains)) {
			$this->logger->warning('Ignoring invalid cms_pico custom_domains value: not a JSON object');
			return [];
		}

		$result = [];
		foreach ($domains as $domain => $site) {
			$domain = is_string($domain) ? strtolower(trim($domain)) : '';
			if (!$domain || !is_string($site) || !preg_match('/^[a-z0-9][a-z0-9.-]*$/', $domain)) {
				$this->logger->warning('Ignoring invalid cms_pico custom_domains entry', [
					'domain' => $domain,
				]);
				continue;
			}

			$result[$domain] = $site;
		}

		return $result;
	}

	/**
	 * Checks whether a website is served from a custom domain.
	 *
	 * Multiple domains may map to the same website (e.g. an apex domain
	 * and its www subdomain), so there is no single "the" domain of a
	 * website - just whether it has one.
	 *
	 * @param string $site
	 *
	 * @return bool
	 */
	public function hasCustomDomain(string $site): bool
	{
		return in_array($site, $this->getCustomDomains(), true);
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	public function getAppValue(string $key): string
	{
		return $this->config->getAppValue(Application::APP_NAME, $key, $this->defaultValues[$key] ?? '');
	}

	/**
	 * @param string $key
	 * @param string $value
	 */
	public function setAppValue(string $key, string $value): void
	{
		$this->config->setAppValue(Application::APP_NAME, $key, $value);
	}

	/**
	 * @param string $key
	 */
	public function deleteAppValue(string $key): void
	{
		$this->config->deleteAppValue(Application::APP_NAME, $key);
	}

	/**
	 * @param string $key
	 * @param mixed  $defaultValue
	 *
	 * @return mixed
	 */
	public function getSystemValue(string $key, $defaultValue = '')
	{
		return $this->config->getSystemValue($key, $defaultValue);
	}
}
