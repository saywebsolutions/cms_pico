<?php
/**
 * CMS Pico - Create websites using Pico CMS for Nextcloud.
 *
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

namespace OCA\CMSPico\Model;

use OCA\CMSPico\Service\MiscService;
use OCA\CMSPico\Service\ThemesService;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUserManager;

class WebsiteFactory
{
	private IConfig $config;
	private IUserManager $userManager;
	private IGroupManager $groupManager;
	private IURLGenerator $urlGenerator;
	private ThemesService $themesService;
	private MiscService $miscService;

	public function __construct(
		IConfig $config,
		IUserManager $userManager,
		IGroupManager $groupManager,
		IURLGenerator $urlGenerator,
		ThemesService $themesService,
		MiscService $miscService
	) {
		$this->config = $config;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->urlGenerator = $urlGenerator;
		$this->themesService = $themesService;
		$this->miscService = $miscService;
	}

	public function create(?array $data = null): Website
	{
		return new Website(
			$data,
			$this->config,
			$this->userManager,
			$this->groupManager,
			$this->urlGenerator,
			$this->themesService,
			$this->miscService
		);
	}
}
