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

namespace OCA\CMSPico\Http;

use OCA\CMSPico\AppInfo\Application;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Server;

class PicoErrorResponse extends TemplateResponse
{
	private ?\Exception $exception;

	public function __construct(?string $message = null, ?\Exception $exception = null)
	{
		$this->exception = $exception;

		$request = Server::get(IRequest::class);
		$params = [
			'message' => $message,
			'debugMode' => Server::get(IConfig::class)->getSystemValue('debug', false),
			'remoteAddr' => $request->getRemoteAddress(),
			'requestID' => $request->getId(),
		];

		if ($this->exception !== null) {
			$params['errorClass'] = get_class($this->exception);
			$params['errorMsg'] = $this->exception->getMessage();
			$params['errorCode'] = $this->exception->getCode();
			$params['errorFile'] = $this->exception->getFile();
			$params['errorLine'] = $this->exception->getLine();
			$params['errorTrace'] = $this->exception->getTraceAsString();
		}

		parent::__construct(Application::APP_NAME, 'error', $params, 'guest');
		$this->setStatus(Http::STATUS_INTERNAL_SERVER_ERROR);
	}
}
