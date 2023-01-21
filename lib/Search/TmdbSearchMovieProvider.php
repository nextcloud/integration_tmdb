<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023, Julien Veyssier
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Tmdb\Search;

use OCA\Tmdb\Service\TmdbAPIService;
use OCA\Tmdb\AppInfo\Application;
use OCP\App\IAppManager;
use OCP\IL10N;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;

class TmdbSearchMovieProvider implements IProvider {

	private IAppManager $appManager;
	private IL10N $l10n;
	private IConfig $config;
	private TmdbAPIService $tmdbAPIService;
	private IURLGenerator $urlGenerator;

	public function __construct(IAppManager        $appManager,
								IL10N              $l10n,
								IConfig            $config,
								IURLGenerator      $urlGenerator,
								TmdbAPIService     $tmdbAPIService) {
		$this->appManager = $appManager;
		$this->l10n = $l10n;
		$this->config = $config;
		$this->tmdbAPIService = $tmdbAPIService;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'tmdb-search-movie';
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l10n->t('TMDB movies');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(string $route, array $routeParameters): int {
		if (strpos($route, Application::APP_ID . '.') === 0) {
			// Active app, prefer Tmdb results
			return -1;
		}

		return 20;
	}

	/**
	 * @inheritDoc
	 */
	public function search(IUser $user, ISearchQuery $query): SearchResult {
		if (!$this->appManager->isEnabledForUser(Application::APP_ID, $user)) {
			return SearchResult::complete($this->getName(), []);
		}

		$limit = $query->getLimit();
		$term = $query->getTerm();
		$offset = $query->getCursor();
		$offset = $offset ? intval($offset) : 0;

		$searchEnabled = $this->config->getAppValue(Application::APP_ID, 'search_enabled', '1') === '1';
		if (!$searchEnabled) {
			return SearchResult::paginated($this->getName(), [], 0);
		}

		$searchResult = $this->tmdbAPIService->searchMovie($user->getUID(), $term, $offset, $limit);
		if (isset($searchResult['error'])) {
			$items = [];
		} else {
			$items = $searchResult;
		}

		$formattedResults = array_map(function (array $entry): TmdbSearchResultEntry {
			return new TmdbSearchResultEntry(
				$this->getThumbnailUrl($entry),
				$this->getMainText($entry),
				$this->getSubline($entry),
				$this->getLink($entry),
				$this->getIconUrl($entry),
				false
			);
		}, $items);

		return SearchResult::paginated(
			$this->getName(),
			$formattedResults,
			$offset + $limit
		);
	}

	protected function getMainText(array $entry): string {
		if (isset($entry['title'], $entry['original_title']) && $entry['title'] !== $entry['original_title']) {
			return $entry['title'] . ' (' . $entry['original_title'] . ')';
		} else {
			return $entry['title'] ?? $entry['original_title'] ?? '???';
		}
	}

	protected function getSubline(array $entry): string {
		return $entry['overview'];
	}

	protected function getLink(array $entry): string {
		// return $this->tmdbAPIService->getMovieLinkFromTmdbId($entry['id']);
		return '';
	}

	protected function getIconUrl(array $entry): string {
		return $this->urlGenerator->linkToRouteAbsolute(
			Application::APP_ID . '.tmdbAPI.getImage',
			['size' => 'w500', 'imagePath' => $entry['poster_path'], 'fallbackName' => $entry['title']]
		);
	}

	protected function getThumbnailUrl(array $entry): string {
		return $this->urlGenerator->imagePath(Application::APP_ID, 'tmdb.logo.svg');
	}
}
