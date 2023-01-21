<?php
/**
 * Nextcloud - Tmdb
 *
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2023
 */

namespace OCA\Tmdb\AppInfo;

use Closure;
use OCA\Tmdb\Listener\TmdbReferenceListener;
use OCA\Tmdb\Reference\TmdbReferenceProvider;
use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCP\IConfig;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserSession;

use OCA\Tmdb\Search\TmdbSearchMovieProvider;

class Application extends App implements IBootstrap {

	public const APP_ID = 'integration_tmdb';
	public const TMDB_URL = 'https://www.themoviedb.org';

	public const DEFAULT_API_KEY_V3 = 'ea9463b748a30c8127e58636af4decaf';
	public const DEFAULT_API_KEY_V4 = 'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJlYTk0NjNiNzQ4YTMwYzgxMjdlNTg2MzZhZjRkZWNhZiIsInN1YiI6IjYzY2JlZWZjOWE2NDM1MDBhZTAzMTcwNSIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.v3HqvuSJ2XSvbXbfbo8hQUwgudae29Ay4-qzu9d9OTY';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);

		$container = $this->getContainer();
		$this->container = $container;
		$this->config = $container->query(IConfig::class);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerSearchProvider(TmdbSearchMovieProvider::class);

		$context->registerReferenceProvider(TmdbReferenceProvider::class);
		// $context->registerEventListener(RenderReferenceEvent::class, TmdbReferenceListener::class);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn(Closure::fromCallable([$this, 'registerNavigation']));
	}

	public function registerNavigation(IUserSession $userSession): void {
		$user = $userSession->getUser();
		if ($user !== null) {
			$userId = $user->getUID();
			$container = $this->getContainer();

			if ($this->config->getUserValue($userId, self::APP_ID, 'navigation_enabled', '0') === '1') {
				$l10n = $container->get(IL10N::class);
				$navName = $l10n->t('The Movie Database');
				$container->get(INavigationManager::class)->add(function () use ($container, $navName) {
					$urlGenerator = $container->get(IURLGenerator::class);
					return [
						'id' => self::APP_ID,
						'order' => 10,
						'href' => self::TMDB_URL,
						'icon' => $urlGenerator->imagePath(self::APP_ID, 'app.svg'),
						'name' => $navName,
						'target' => '_blank',
					];
				});
			}
		}
	}
}

