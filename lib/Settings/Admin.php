<?php
namespace OCA\Tmdb\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;

use OCA\Tmdb\AppInfo\Application;

class Admin implements ISettings {

	public function __construct(private IConfig       $config,
								private IInitialState $initialStateService,
								?string       $userId) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$apiKey = $this->config->getAppValue(Application::APP_ID, 'api_key');

		$state = [
			'api_key' => $apiKey,
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
