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

namespace OCA\CMSPico\Db;

use OCA\CMSPico\Exceptions\WebsiteNotFoundException;
use OCA\CMSPico\Model\Website;
use OCA\CMSPico\Model\WebsiteFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class WebsitesRequest
{
	public const TABLE_NAME = 'cms_pico_websites';

	protected IDBConnection $dbConnection;
	protected WebsiteFactory $websiteFactory;

	public function __construct(IDBConnection $connection, WebsiteFactory $websiteFactory)
	{
		$this->dbConnection = $connection;
		$this->websiteFactory = $websiteFactory;
	}

	public function create(Website $website): void
	{
		$qb = $this->dbConnection->getQueryBuilder()
			->insert(WebsitesRequest::TABLE_NAME);

		$qb
			->setValue('name', $qb->createNamedParameter($website->getName()))
			->setValue('user_id', $qb->createNamedParameter($website->getUserId()))
			->setValue('site', $qb->createNamedParameter($website->getSite()))
			->setValue('theme', $qb->createNamedParameter($website->getTheme()))
			->setValue('type', $qb->createNamedParameter($website->getType()))
			->setValue('options', $qb->createNamedParameter($website->getOptionsJSON()))
			->setValue('path', $qb->createNamedParameter($website->getPath()))
			->setValue('creation', $qb->createFunction('NOW()'));

		$qb->executeStatement();

		$website->setId($qb->getLastInsertId());
	}

	public function update(Website $website): void
	{
		$qb = $this->dbConnection->getQueryBuilder()
			->update(WebsitesRequest::TABLE_NAME);

		$qb
			->set('name', $qb->createNamedParameter($website->getName()))
			->set('user_id', $qb->createNamedParameter($website->getUserId()))
			->set('site', $qb->createNamedParameter($website->getSite()))
			->set('theme', $qb->createNamedParameter($website->getTheme()))
			->set('type', $qb->createNamedParameter($website->getType()))
			->set('options', $qb->createNamedParameter($website->getOptionsJSON()))
			->set('path', $qb->createNamedParameter($website->getPath()));

		$this->limitToField($qb, 'id', $website->getId());

		$qb->executeStatement();
	}

	public function delete(Website $website): void
	{
		$qb = $this->dbConnection->getQueryBuilder()
			->delete(WebsitesRequest::TABLE_NAME);

		$this->limitToField($qb, 'id', $website->getId());

		$qb->executeStatement();
	}

	public function deleteAllFromUserId(string $userId): void
	{
		$qb = $this->dbConnection->getQueryBuilder()
			->delete(WebsitesRequest::TABLE_NAME);

		$this->limitToField($qb, 'user_id', $userId);

		$qb->executeStatement();
	}

	/**
	 * @return Website[]
	 */
	public function getWebsites(): array
	{
		$qb = $this->dbConnection->getQueryBuilder()
			->select('*')
			->from(WebsitesRequest::TABLE_NAME);

		$websites = [];
		$result = $qb->executeQuery();
		while ($data = $result->fetch()) {
			$websites[] = $this->createInstance($data);
		}
		$result->closeCursor();

		return $websites;
	}

	/**
	 * @return Website[]
	 */
	public function getWebsitesFromUserId(string $userId): array
	{
		$qb = $this->dbConnection->getQueryBuilder()
			->select('*')
			->from(WebsitesRequest::TABLE_NAME);

		$this->limitToField($qb, 'user_id', $userId);

		$websites = [];
		$result = $qb->executeQuery();
		while ($data = $result->fetch()) {
			$websites[] = $this->createInstance($data);
		}
		$result->closeCursor();

		return $websites;
	}

	/**
	 * @throws WebsiteNotFoundException
	 */
	public function getWebsiteFromId(int $id): Website
	{
		$qb = $this->dbConnection->getQueryBuilder()
			->select('*')
			->from(WebsitesRequest::TABLE_NAME);

		$this->limitToField($qb, 'id', $id);

		$result = $qb->executeQuery();
		$data = $result->fetch();
		$result->closeCursor();

		if ($data === false) {
			throw new WebsiteNotFoundException('#' . $id);
		}

		return $this->createInstance($data);
	}

	/**
	 * @throws WebsiteNotFoundException
	 */
	public function getWebsiteFromSite(string $site): Website
	{
		$qb = $this->dbConnection->getQueryBuilder()
			->select('*')
			->from(WebsitesRequest::TABLE_NAME);

		$this->limitToField($qb, 'site', $site);

		$result = $qb->executeQuery();
		$data = $result->fetch();
		$result->closeCursor();

		if ($data === false) {
			throw new WebsiteNotFoundException($site);
		}

		return $this->createInstance($data);
	}

	private function createInstance(array $data): Website
	{
		return $this->websiteFactory->create($data);
	}

	private function limitToField(IQueryBuilder $qb, string $field, mixed $value): void
	{
		$qb->andWhere($qb->expr()->eq($field, $qb->createNamedParameter($value)));
	}
}
