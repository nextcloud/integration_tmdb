<?php
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Tmdb\Migration;

use Closure;
use OCA\Tmdb\AppInfo\Application;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use OCP\Security\ICrypto;

class Version2100Date202410231627 extends SimpleMigrationStep {

	public function __construct(
		private IDBConnection $connection,
		private ICrypto $crypto,
		private IConfig $config,
	) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @return null|ISchemaWrapper
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
		// encrypt appconfig client_id and client_secret
		$clientId = $this->config->getAppValue(Application::APP_ID, 'api_key_v3');
		if ($clientId !== '') {
			$clientId = $this->crypto->encrypt($clientId);
			$this->config->setAppValue(Application::APP_ID, 'api_key_v3', $clientId);
		}
		$clientSecret = $this->config->getAppValue(Application::APP_ID, 'api_key_v4');
		if ($clientSecret !== '') {
			$clientSecret = $this->crypto->encrypt($clientSecret);
			$this->config->setAppValue(Application::APP_ID, 'api_key_v4', $clientSecret);
		}

		// encrypt user tokens
		$qbUpdate = $this->connection->getQueryBuilder();
		$qbUpdate->update('preferences')
			->set('configvalue', $qbUpdate->createParameter('updateValue'))
			->where(
				$qbUpdate->expr()->eq('appid', $qbUpdate->createNamedParameter(Application::APP_ID, IQueryBuilder::PARAM_STR))
			)
			->andWhere(
				$qbUpdate->expr()->eq('configkey', $qbUpdate->createParameter('updateConfigKey'))
			);
		$qbUpdate->andWhere(
			$qbUpdate->expr()->eq('userid', $qbUpdate->createParameter('updateUserId'))
		);

		$qbSelect = $this->connection->getQueryBuilder();
		$qbSelect->select(['userid', 'configvalue', 'configkey'])
			->from('preferences')
			->where(
				$qbSelect->expr()->eq('appid', $qbSelect->createNamedParameter(Application::APP_ID, IQueryBuilder::PARAM_STR))
			);

		$or = $qbSelect->expr()->orx();
		$or->add($qbSelect->expr()->eq('configkey', $qbSelect->createNamedParameter('api_key_v3', IQueryBuilder::PARAM_STR)));
		$or->add($qbSelect->expr()->eq('configkey', $qbSelect->createNamedParameter('api_key_v4', IQueryBuilder::PARAM_STR)));
		$qbSelect->andWhere($or);

		$qbSelect->andWhere(
			$qbSelect->expr()->nonEmptyString('configvalue')
		)
			->andWhere(
				$qbSelect->expr()->isNotNull('configvalue')
			);
		$req = $qbSelect->executeQuery();
		while ($row = $req->fetch()) {
			$userId = $row['userid'];
			$configKey = $row['configkey'];
			$storedClearValue = $row['configvalue'];
			$encryptedValue = $this->crypto->encrypt($storedClearValue);
			$qbUpdate->setParameter('updateUserId', $userId, IQueryBuilder::PARAM_STR);
			$qbUpdate->setParameter('updateConfigKey', $configKey, IQueryBuilder::PARAM_STR);
			$qbUpdate->setParameter('updateValue', $encryptedValue, IQueryBuilder::PARAM_STR);
			$qbUpdate->executeStatement();
		}
		$req->closeCursor();
		return null;
	}
}
