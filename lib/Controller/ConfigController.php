<?php
/**
 * Nextcloud - Tmdb
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2023
 */

namespace OCA\Tmdb\Controller;

use OCA\Tmdb\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;

use OCP\IRequest;
use OCP\PreConditionNotMetException;
use OCP\Security\ICrypto;

class ConfigController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		private IConfig  $config,
		private ICrypto $crypto,
		private ?string  $userId
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @param array $values
	 * @return DataResponse
	 * @throws PreConditionNotMetException
	 */
	#[NoAdminRequired]
	public function setConfig(array $values): DataResponse {
		if (isset($values['api_key_v3']) || isset($values['api_key_v4'])) {
			return new DataResponse('', Http::STATUS_BAD_REQUEST);
		}
		foreach ($values as $key => $value) {
			$this->config->setUserValue($this->userId, Application::APP_ID, $key, $value);
		}
		return new DataResponse('');
	}

	/**
	 * @param array $values
	 * @return DataResponse
	 * @throws PreConditionNotMetException
	 */
	#[NoAdminRequired]
	#[PasswordConfirmationRequired]
	public function setSensitiveConfig(array $values): DataResponse {
		foreach ($values as $key => $value) {
			if (in_array($key, ['api_key_v3', 'api_key_v4']) && $value !== '') {
				$value = $this->crypto->encrypt($value);
			}
			$this->config->setUserValue($this->userId, Application::APP_ID, $key, $value);
		}
		return new DataResponse('');
	}

	/**
	 * @param array $values
	 * @return DataResponse
	 */
	#[PasswordConfirmationRequired]
	public function setSensitiveAdminConfig(array $values): DataResponse {
		foreach ($values as $key => $value) {
			if (in_array($key, ['api_key_v3', 'api_key_v4']) && $value !== '') {
				$value = $this->crypto->encrypt($value);
			}
			$this->config->setAppValue(Application::APP_ID, $key, $value);
		}
		return new DataResponse('');
	}
}
