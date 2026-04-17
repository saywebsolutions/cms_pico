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

use OCA\CMSPico\Exceptions\PluginNotCompatibleException;
use OCA\CMSPico\Files\FolderInterface;
use OCA\CMSPico\Pico;
use OCA\CMSPico\Service\MiscService;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use ParseError;
use function OCA\CMSPico\t;

class Plugin implements \JsonSerializable
{
	public const TYPE_SYSTEM = 1;
	public const TYPE_CUSTOM = 2;

	public const PLUGIN_API_VERSIONS = [
		Pico::API_VERSION_1,
		Pico::API_VERSION_2,
		Pico::API_VERSION_3,
	];

	private MiscService $miscService;
	private FolderInterface $folder;
	private int $type;
	private ?bool $compat = null;
	private ?PluginNotCompatibleException $compatException = null;

	/**
	 * Plugin constructor.
	 *
	 * @param FolderInterface $folder
	 * @param int $type
	 * @param MiscService $miscService
	 */
	public function __construct(
		FolderInterface $folder,
		int $type,
		MiscService $miscService
	) {
		$this->folder = $folder;
		$this->type = $type;
		$this->miscService = $miscService;
	}

	public function getName(): string
	{
		return $this->folder->getName();
	}

	public function getFolder(): FolderInterface
	{
		return $this->folder;
	}

	public function getType(): int
	{
		return $this->type;
	}

	public function isCompatible(): bool
	{
		if ($this->compat !== null) {
			return $this->compat;
		}

		try {
			$this->checkCompatibility();
			return true;
		} catch (PluginNotCompatibleException $e) {
			return false;
		}
	}

	/**
	 * @throws PluginNotCompatibleException
	 */
	public function checkCompatibility(): void
	{
		if ($this->compat === false) {
			throw $this->compatException;
		} elseif ($this->compat) {
			return;
		}

		$includeClosure = static function (string $pluginFile): void {
			require_once($pluginFile);
		};

		try {
			try {
				$pluginFile = $this->getFolder()->getFile($this->getName() . '.php');
				$includeClosure($pluginFile->getLocalPath());
			} catch (InvalidPathException|NotFoundException $e) {
				throw new PluginNotCompatibleException(
					$this->getName(),
					t('Incompatible plugin: Plugin file "{file}" not found.'),
					['file' => $this->getName() . '/' . $this->getName() . '.php']
				);
			} catch (ParseError $e) {
				throw new PluginNotCompatibleException(
					$this->getName(),
					t('Incompatible plugin: PHP parse error in file "{file}".'),
					['file' => $this->getName() . '/' . $this->getName() . '.php']
				);
			}

			$className = $this->getName();
			if (!class_exists($className, false)) {
				throw new PluginNotCompatibleException(
					$this->getName(),
					t('Incompatible plugin: Plugin class "{class}" not found.'),
					['class' => $className]
				);
			}

			$apiVersion = Pico::API_VERSION_0;
			if (is_a($className, \PicoPluginInterface::class, true)) {
				$apiVersion = Pico::API_VERSION_1;
				if (defined($className . '::API_VERSION')) {
					$apiVersion = (int) $className::API_VERSION;
				}
			}

			if (!in_array($apiVersion, static::PLUGIN_API_VERSIONS, true)) {
				throw new PluginNotCompatibleException(
					$this->getName(),
					t('Incompatible plugin: Plugins for Pico CMS for Nextcloud must use one of the API versions '
						. '{compatApiVersions}, but this plugin uses API version {apiVersion}.'),
					['compatApiVersions' => implode(', ', static::PLUGIN_API_VERSIONS), 'apiVersion' => $apiVersion]
				);
			}

			$this->compat = true;
			$this->compatException = null;
		} catch (PluginNotCompatibleException $e) {
			$this->compat = false;
			$this->compatException = $e;

			throw $e;
		}
	}

	public function toArray(): array
	{
		$data = [
			'name' => $this->getName(),
			'type' => $this->getType(),
			'compat' => $this->isCompatible(),
		];

		if (!$this->isCompatible() && ($this->compatException !== null)) {
			$data['compatReason'] = $this->compatException->getRawReason();
			$data['compatReasonData'] = $this->compatException->getRawReasonData();
		}

		return $data;
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
