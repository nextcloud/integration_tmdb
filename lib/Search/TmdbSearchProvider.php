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

use OCA\Tmdb\AppInfo\Application;
use OCA\Tmdb\Service\TmdbAPIService;
use OCA\Tmdb\Service\UtilsService;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;

class TmdbSearchProvider implements IProvider {

	public function __construct(private IAppManager        $appManager,
		private IL10N              $l10n,
		private IConfig            $config,
		private IURLGenerator      $urlGenerator,
		private UtilsService       $utilsService,
		private TmdbAPIService     $tmdbAPIService) {
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'tmdb-search-multi';
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l10n->t('The Movie Database items');
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

		$routeFrom = $query->getRoute();
		$requestedFromSmartPicker = $routeFrom === '' || $routeFrom === 'smart-picker';

		if (!$requestedFromSmartPicker) {
			$searchEnabled = $this->config->getUserValue($user->getUID(), Application::APP_ID, 'search_enabled', '1') === '1';
			if (!$searchEnabled) {
				return SearchResult::paginated($this->getName(), [], 0);
			}
		}

		$searchResult = $this->tmdbAPIService->searchMulti($user->getUID(), $term, $offset, $limit);
		if (isset($searchResult['error'])) {
			$items = [];
		} else {
			$items = $searchResult;
		}

		$formattedResults = array_map(function (array $entry): SearchResultEntry {
			[$rounded, $thumbnailUrl] = $this->getThumbnailUrl($entry);
			return new SearchResultEntry(
				$thumbnailUrl,
				$this->getMainText($entry),
				$this->getSubline($entry),
				$this->getLink($entry),
				$this->getIconUrl($entry),
				$rounded
			);
		}, $items);

		return SearchResult::paginated(
			$this->getName(),
			$formattedResults,
			$offset + $limit
		);
	}

	protected function getMainText(array $entry): string {
		if ($entry['media_type'] === 'movie') {
			if (isset($entry['title'], $entry['original_title']) && $entry['title'] !== $entry['original_title']) {
				return '🎥 ' . $entry['title'] . ' (' . $entry['original_title'] . ')';
			} else {
				return '🎥 ' . ($entry['title'] ?? $entry['original_title'] ?? '???');
			}
		} elseif ($entry['media_type'] === 'tv') {
			if (isset($entry['name'], $entry['original_name']) && $entry['name'] !== $entry['original_name']) {
				return '📺 ' . $entry['name'] . ' (' . $entry['original_name'] . ')';
			} else {
				return '📺 ' . ($entry['name'] ?? $entry['original_name'] ?? '???');
			}
		} elseif ($entry['media_type'] === 'person') {
			return '👤 ' . $entry['name'];
		}
		return '';
	}

	protected function getSubline(array $entry): string {
		if ($entry['media_type'] === 'movie') {
			if (isset($entry['release_date']) && is_string($entry['release_date'])) {
				$date = $this->utilsService->formatDate($entry['release_date']);
				return $date . ' - ' . $entry['overview'];
			}
		} elseif ($entry['media_type'] === 'tv') {
			if (isset($entry['first_air_date']) && is_string($entry['first_air_date'])) {
				$date = $this->utilsService->formatDate($entry['first_air_date']);
				return $date . ' - ' . $entry['overview'];
			}
		}
		return $entry['overview'] ?? '';
	}

	protected function getLink(array $entry): string {
		if ($entry['media_type'] === 'movie') {
			return $this->tmdbAPIService->getMovieLinkFromTmdbId($entry['id']);
		} elseif ($entry['media_type'] === 'tv') {
			return $this->tmdbAPIService->getTvLinkFromTmdbId($entry['id']);
		} elseif ($entry['media_type'] === 'person') {
			return $this->tmdbAPIService->getPersonLinkFromTmdbId($entry['id']);
		}
		return '';
	}

	protected function getIconUrl(array $entry): string {
		return '';
		// return $this->urlGenerator->imagePath(Application::APP_ID, 'tmdb.logo.svg');
	}

	protected function getThumbnailUrl(array $entry): array {
		if ($entry['media_type'] === 'movie') {
			$imagePath = $entry['poster_path'] ?? null;
			$fallbackName = $entry['original_title'] ?? $entry['title'] ?? '???';
		} elseif ($entry['media_type'] === 'tv') {
			$imagePath = $entry['poster_path'] ?? null;
			$fallbackName = $entry['original_name'] ?? $entry['name'] ?? '???';
		} elseif ($entry['media_type'] === 'person') {
			$imagePath = $entry['profile_path'] ?? null;
			$fallbackName = $entry['name'] ?? '???';
		} else {
			return [false, ''];
		}
		$fallbackName = preg_replace('/\//', '', $fallbackName);
		if ($imagePath === null) {
			$url = $this->urlGenerator->linkToRouteAbsolute('core.GuestAvatar.getAvatar', ['guestName' => $fallbackName, 'size' => 44]);
			return [true, $url];
		}
		$imagePath = preg_replace('/^\/+/', '', $imagePath);
		$url = $this->urlGenerator->linkToRouteAbsolute(
			Application::APP_ID . '.tmdbAPI.getImage',
			['size' => 'w500', 'imagePath' => $imagePath, 'fallbackName' => $fallbackName]
		);
		return [false, $url];
	}
}
