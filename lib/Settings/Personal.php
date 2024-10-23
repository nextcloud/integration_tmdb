<?php

namespace OCA\Tmdb\Settings;

use OCA\Tmdb\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;

use OCP\Security\ICrypto;
use OCP\Settings\ISettings;

class Personal implements ISettings {

	public function __construct(
		private IConfig $config,
		private IInitialState $initialStateService,
		private ICrypto $crypto,
		private ?string $userId
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$searchEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'search_enabled', '1') === '1';
		$navigationEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'navigation_enabled', '0') === '1';
		$linkPreviewEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'link_preview_enabled', '1') === '1';
		$adminApiKeyV3 = $this->config->getAppValue(Application::APP_ID, 'api_key_v3');
		if ($adminApiKeyV3 !== '') {
			$adminApiKeyV3 = $this->crypto->decrypt($adminApiKeyV3);
		}
		$apiKeyV3 = $this->config->getUserValue($this->userId, Application::APP_ID, 'api_key_v3');
		if ($apiKeyV3 !== '') {
			$apiKeyV3 = $this->crypto->decrypt($apiKeyV3);
		}
		$adminApiKeyV4 = $this->config->getAppValue(Application::APP_ID, 'api_key_v4');
		if ($adminApiKeyV4 !== '') {
			$adminApiKeyV4 = $this->crypto->decrypt($adminApiKeyV4);
		}
		$apiKeyV4 = $this->config->getUserValue($this->userId, Application::APP_ID, 'api_key_v4');
		if ($apiKeyV4 !== '') {
			$apiKeyV4 = $this->crypto->decrypt($apiKeyV4);
		}

		$userConfig = [
			'search_enabled' => $searchEnabled,
			'navigation_enabled' => $navigationEnabled ,
			'link_preview_enabled' => $linkPreviewEnabled,
			'api_key_v3' => $apiKeyV3,
			'has_admin_api_key_v3' => $adminApiKeyV3 !== '',
			'api_key_v4' => $apiKeyV4,
			'has_admin_api_key_v4' => $adminApiKeyV4 !== '',
		];
		$this->initialStateService->provideInitialState('user-config', $userConfig);
		return new TemplateResponse(Application::APP_ID, 'personalSettings');
	}

	public function getSection(): string {
		return 'connected-accounts';
	}

	public function getPriority(): int {
		return 10;
	}
}
