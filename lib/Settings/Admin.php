<?php

namespace OCA\Tmdb\Settings;

use OCA\Tmdb\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;

use OCP\Security\ICrypto;
use OCP\Settings\ISettings;

class Admin implements ISettings {

	public function __construct(
		private IConfig $config,
		private IInitialState $initialStateService,
		private ICrypto $crypto,
		?string $userId
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$apiKeyV3 = $this->config->getAppValue(Application::APP_ID, 'api_key_v3');
		if ($apiKeyV3 !== '') {
			$apiKeyV3 = $this->crypto->decrypt($apiKeyV3);
		}
		$apiKeyV4 = $this->config->getAppValue(Application::APP_ID, 'api_key_v4');
		if ($apiKeyV4 !== '') {
			$apiKeyV4 = $this->crypto->decrypt($apiKeyV4);
		}

		$state = [
			'api_key_v3' => $apiKeyV3,
			'api_key_v4' => $apiKeyV4,
		];
		$this->initialStateService->provideInitialState('admin-config', $state);
		return new TemplateResponse(Application::APP_ID, 'adminSettings');
	}

	public function getSection(): string {
		return 'connected-accounts';
	}

	public function getPriority(): int {
		return 10;
	}
}
