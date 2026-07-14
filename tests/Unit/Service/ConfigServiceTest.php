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

use OCA\CMSPico\AppInfo\Application;
use OCA\CMSPico\Service\ConfigService;
use OCP\IConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ConfigServiceTest extends TestCase
{
	private function createConfigService(string $customDomainsValue): ConfigService
	{
		$config = $this->createMock(IConfig::class);
		$config->method('getAppValue')
			->with(Application::APP_NAME, ConfigService::CUSTOM_DOMAINS, '')
			->willReturn($customDomainsValue);

		return new ConfigService($config, new NullLogger());
	}

	public function testEmptyValueYieldsNoDomains(): void
	{
		$this->assertSame([], $this->createConfigService('')->getCustomDomains());
	}

	public function testInvalidJsonYieldsNoDomains(): void
	{
		$this->assertSame([], $this->createConfigService('{oops')->getCustomDomains());
	}

	public function testNonObjectJsonYieldsNoDomains(): void
	{
		$this->assertSame([], $this->createConfigService('"example.com"')->getCustomDomains());
		$this->assertSame([], $this->createConfigService('["example.com"]')->getCustomDomains());
	}

	public function testValidDomainsAreReturned(): void
	{
		$service = $this->createConfigService('{"example.com": "my_site", "www.example.com": "my_site"}');
		$this->assertSame(
			['example.com' => 'my_site', 'www.example.com' => 'my_site'],
			$service->getCustomDomains()
		);
	}

	public function testDomainsAreNormalizedToLowercase(): void
	{
		$service = $this->createConfigService('{" Example.COM ": "my_site"}');
		$this->assertSame(['example.com' => 'my_site'], $service->getCustomDomains());
	}

	public function testInvalidEntriesAreDropped(): void
	{
		$service = $this->createConfigService(json_encode([
			'valid.example.com' => 'my_site',
			'under_score.com' => 'other_site',
			'-leading-dash.com' => 'other_site',
			'' => 'other_site',
			'nonstring.example.com' => 42,
		]));
		$this->assertSame(['valid.example.com' => 'my_site'], $service->getCustomDomains());
	}

	public function testHasCustomDomain(): void
	{
		$service = $this->createConfigService('{"example.com": "my_site", "www.example.com": "my_site"}');
		$this->assertTrue($service->hasCustomDomain('my_site'));
		$this->assertFalse($service->hasCustomDomain('other_site'));
	}

	public function testHasCustomDomainWithoutConfig(): void
	{
		$this->assertFalse($this->createConfigService('')->hasCustomDomain('my_site'));
	}
}
