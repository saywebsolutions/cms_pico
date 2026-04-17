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

use OCA\CMSPico\Files\FolderInterface;
use OCA\CMSPico\Service\MiscService;

class TemplateFactory
{
	private MiscService $miscService;

	public function __construct(MiscService $miscService)
	{
		$this->miscService = $miscService;
	}

	/**
	 * Create a new Template instance.
	 *
	 * @param FolderInterface $folder
	 * @param int $type
	 * @return Template
	 */
	public function create(FolderInterface $folder, int $type = Template::TYPE_SYSTEM): Template
	{
		return new Template($folder, $type, $this->miscService);
	}
}
