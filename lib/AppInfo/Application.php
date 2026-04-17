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

namespace OCA\CMSPico\AppInfo;

use OCA\CMSPico\Listener\ExternalStorageBackendEventListener;
use OCA\CMSPico\Listener\GroupDeletedEventListener;
use OCA\CMSPico\Listener\UserDeletedEventListener;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\User\Events\UserDeletedEvent;

class Application extends App implements IBootstrap
{
	public const APP_NAME = 'cms_pico';

	public function __construct(array $urlParams = [])
	{
		parent::__construct(self::APP_NAME, $urlParams);
	}

	/**
	 * Register application services and event listeners.
	 */
	public function register(IRegistrationContext $context): void
	{
		// Register event listeners
		$context->registerEventListener(UserDeletedEvent::class, UserDeletedEventListener::class);
		$context->registerEventListener(GroupDeletedEvent::class, GroupDeletedEventListener::class);
		$context->registerEventListener(
			'OCA\\Files_External::loadAdditionalBackends',
			ExternalStorageBackendEventListener::class
		);
	}

	/**
	 * Boot the application.
	 */
	public function boot(IBootContext $context): void
	{
		// Load vendor autoload FIRST (required for Twig, Parsedown, etc.)
		require_once __DIR__ . '/../../vendor/autoload.php';

		// Load functions and Pico bootstrap
		require_once __DIR__ . '/../functions.php';
	}

	/**
	 * Returns the absolute path to this app.
	 *
	 * @return string
	 */
	public static function getAppPath(): string
	{
		try {
			/** @var IAppManager $appManager */
			$appManager = \OCP\Server::get(IAppManager::class);
			return $appManager->getAppPath(self::APP_NAME);
		} catch (AppPathNotFoundException $e) {
			return '';
		}
	}

	/**
	 * Returns the absolute web path to this app.
	 *
	 * @return string
	 */
	public static function getAppWebPath(): string
	{
		try {
			/** @var IAppManager $appManager */
			$appManager = \OCP\Server::get(IAppManager::class);
			return $appManager->getAppWebPath(self::APP_NAME);
		} catch (AppPathNotFoundException $e) {
			return '';
		}
	}

	/**
	 * Returns the installed version of this app.
	 *
	 * @return string
	 */
	public static function getAppVersion(): string
	{
		try {
			/** @var IAppManager $appManager */
			$appManager = \OCP\Server::get(IAppManager::class);
			return $appManager->getAppVersion(self::APP_NAME);
		} catch (AppPathNotFoundException $e) {
			return '';
		}
	}
}
