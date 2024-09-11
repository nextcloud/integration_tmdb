<?php
/**
 * @copyright Copyright (c) 2023 Julien Veyssier <eneiluj@posteo.net>
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Tmdb\Reference;

use OC\Collaboration\Reference\LinkReferenceProvider;
use OCA\Tmdb\AppInfo\Application;
use OCA\Tmdb\Service\TmdbAPIService;
use OCA\Tmdb\Service\UtilsService;
use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;
use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\IReferenceManager;
use OCP\Collaboration\Reference\ISearchableReferenceProvider;
use OCP\Collaboration\Reference\Reference;
use OCP\IConfig;
use OCP\IL10N;

use OCP\IURLGenerator;

class TmdbReferenceProvider extends ADiscoverableReferenceProvider implements ISearchableReferenceProvider {

	private const RICH_OBJECT_TYPE_MOVIE = Application::APP_ID . '_movie';
	private const RICH_OBJECT_TYPE_PERSON = Application::APP_ID . '_person';
	private const RICH_OBJECT_TYPE_TV = Application::APP_ID . '_tv';

	public function __construct(private TmdbAPIService $tmdbAPIService,
		private IConfig $config,
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private IReferenceManager $referenceManager,
		private LinkReferenceProvider $linkReferenceProvider,
		private UtilsService $utilsService,
		private ?string $userId) {
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'tmdb-items';
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): string {
		return $this->l10n->t('Movies, series and people (by The Movie Database)');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(): int {
		return 10;
	}

	/**
	 * @inheritDoc
	 */
	public function getIconUrl(): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg')
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getSupportedSearchProviderIds(): array {
		return ['tmdb-search-multi'];
	}

	/**
	 * @inheritDoc
	 */
	public function matchReference(string $referenceText): bool {
		$adminLinkPreviewEnabled = $this->config->getAppValue(Application::APP_ID, 'link_preview_enabled', '1') === '1';
		$userLinkPreviewEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'link_preview_enabled', '1') === '1';
		if (!$adminLinkPreviewEnabled || !$userLinkPreviewEnabled) {
			return false;
		}

		// link examples:
		// https://www.themoviedb.org/movie/293-blabla
		// https://www.themoviedb.org/person/3636-blabla
		// https://www.themoviedb.org/tv/42009-blabla
		// https://www.imdb.com/name/nm0000602/blabla
		// https://www.imdb.com/title/tt0216787/blabla
		return preg_match('/^(?:https?:\/\/)?(?:www\.)?themoviedb\.org\/movie\/\d+/i', $referenceText) === 1
			|| preg_match('/^(?:https?:\/\/)?(?:www\.)?themoviedb\.org\/person\/\d+/i', $referenceText) === 1
			|| preg_match('/^(?:https?:\/\/)?(?:www\.)?themoviedb\.org\/tv\/\d+/i', $referenceText) === 1
			|| preg_match('/^(?:https?:\/\/)?(?:www\.)?imdb\.com\/name\/[^\/]+/i', $referenceText) === 1
			|| preg_match('/^(?:https?:\/\/)?(?:www\.)?imdb\.com\/title\/[^\/]+/i', $referenceText) === 1;
	}

	/**
	 * @inheritDoc
	 */
	public function resolveReference(string $referenceText): ?IReference {
		if ($this->matchReference($referenceText)) {
			$urlInfo = $this->getUrlInfo($referenceText);
			if ($urlInfo !== null) {
				if ($urlInfo['type'] === 'tmdb-movie' || $urlInfo['type'] === 'imdb-movie') {
					if ($urlInfo['type'] === 'tmdb-movie') {
						$movieInfo = $this->tmdbAPIService->getMovieInfo($this->userId, $urlInfo['id']);
					} else {
						$movieInfo = $this->tmdbAPIService->getMovieInfoFromImdbId($this->userId, $urlInfo['id']);
					}
					if (!isset($movieInfo['error'])) {
						// this is one ugly way to find out if we got a tv imdb link
						if (isset($movieInfo['title'])) {
							return $this->buildMovieReference($referenceText, $movieInfo);
						} elseif ($movieInfo['name']) {
							return $this->buildTvReference($referenceText, $movieInfo);
						}
					}
				} elseif ($urlInfo['type'] === 'tmdb-tv') {
					$tvInfo = $this->tmdbAPIService->getTvInfo($this->userId, $urlInfo['id']);
					if (!isset($tvInfo['error'])) {
						return $this->buildTvReference($referenceText, $tvInfo);
					}
				} elseif ($urlInfo['type'] === 'tmdb-person' || $urlInfo['type'] === 'imdb-person') {
					if ($urlInfo['type'] === 'tmdb-person') {
						$personInfo = $this->tmdbAPIService->getPersonInfo($this->userId, $urlInfo['id']);
					} else {
						$personInfo = $this->tmdbAPIService->getPersonInfoFromImdbId($this->userId, $urlInfo['id']);
					}
					if (!isset($personInfo['error'])) {
						return $this->buildPersonReference($referenceText, $personInfo);
					}
				}
			}
			// fallback to opengraph
			return $this->linkReferenceProvider->resolveReference($referenceText);
		}

		return null;
	}

	/**
	 * @param string $referenceText
	 * @param array $personInfo
	 * @return Reference
	 */
	private function buildPersonReference(string $referenceText, array $personInfo): Reference {
		$reference = new Reference($referenceText);
		$reference->setTitle($personInfo['name']);
		$reference->setDescription($personInfo['biography'] ?? '???');
		$fallbackName = $personInfo['name'] ?? '???';
		if (isset($personInfo['birthday']) && is_string($personInfo['birthday'])) {
			$formattedBirthday = $this->utilsService->formatDate($personInfo['birthday']);
			$personInfo['formatted_birthday'] = $formattedBirthday;
		}
		if (isset($personInfo['deathday']) && is_string($personInfo['deathday'])) {
			$formattedDeathday = $this->utilsService->formatDate($personInfo['deathday']);
			$personInfo['formatted_deathday'] = $formattedDeathday;
		}
		if (isset($personInfo['profile_path']) && $personInfo['profile_path']) {
			$imagePath = preg_replace('/^\/+/', '', $personInfo['profile_path']);
			$fallbackName = preg_replace('/\//', '', $fallbackName);
			$imageUrl = $this->urlGenerator->linkToRouteAbsolute(
				Application::APP_ID . '.tmdbAPI.getImage',
				['size' => 'w500', 'imagePath' => $imagePath, 'fallbackName' => $fallbackName]
			);
		} else {
			$imageUrl = $this->urlGenerator->linkToRouteAbsolute('core.GuestAvatar.getAvatar', ['guestName' => $fallbackName, 'size' => 44]);
		}
		$reference->setImageUrl($imageUrl);
		$personInfo['image_url'] = $imageUrl;
		$personInfo['tmdb_url'] = $referenceText;
		$reference->setRichObject(
			self::RICH_OBJECT_TYPE_PERSON,
			$personInfo,
		);
		return $reference;
	}

	/**
	 * @param string $referenceText
	 * @param array $tvInfo
	 * @return Reference
	 */
	private function buildTvReference(string $referenceText, array $tvInfo): Reference {
		$reference = new Reference($referenceText);
		if (isset($tvInfo['name'], $tvInfo['original_name']) && $tvInfo['name'] !== $tvInfo['original_name']) {
			$formattedName = $tvInfo['name'] . ' (' . $tvInfo['original_name'] . ')';
		} else {
			$formattedName = $tvInfo['name'] ?? $tvInfo['original_name'] ?? '???';
		}
		$tvInfo['formatted_name'] = $formattedName;
		$reference->setTitle($formattedName);
		if (isset($tvInfo['first_air_date']) && is_string($tvInfo['first_air_date'])) {
			$date = $this->utilsService->formatDate($tvInfo['first_air_date']);
			$tvInfo['formatted_first_air_date'] = $date;
			$reference->setDescription($date . ' - ' . $tvInfo['overview']);
		} else {
			$reference->setDescription($tvInfo['overview']);
		}
		if (isset($tvInfo['last_air_date']) && is_string($tvInfo['last_air_date'])) {
			$date = $this->utilsService->formatDate($tvInfo['last_air_date']);
			$tvInfo['formatted_last_air_date'] = $date;
		}
		$fallbackName = $tvInfo['name'] ?? $tvInfo['original_name'] ?? '???';
		if (isset($tvInfo['poster_path']) && $tvInfo['poster_path']) {
			$imagePath = preg_replace('/^\/+/', '', $tvInfo['poster_path']);
			$fallbackName = preg_replace('/\//', '', $fallbackName);
			$imageUrl = $this->urlGenerator->linkToRouteAbsolute(
				Application::APP_ID . '.tmdbAPI.getImage',
				['size' => 'w500', 'imagePath' => $imagePath, 'fallbackName' => $fallbackName]
			);
		} else {
			$imageUrl = $this->urlGenerator->linkToRouteAbsolute('core.GuestAvatar.getAvatar', ['guestName' => $fallbackName, 'size' => 44]);
		}
		$reference->setImageUrl($imageUrl);
		$tvInfo['image_url'] = $imageUrl;
		$tvInfo['tmdb_url'] = $referenceText;
		$reference->setRichObject(
			self::RICH_OBJECT_TYPE_TV,
			$tvInfo,
		);
		return $reference;
	}

	/**
	 * @param string $referenceText
	 * @param array $movieInfo
	 * @return Reference
	 */
	private function buildMovieReference(string $referenceText, array $movieInfo): Reference {
		$reference = new Reference($referenceText);
		if (isset($movieInfo['title'], $movieInfo['original_title']) && $movieInfo['title'] !== $movieInfo['original_title']) {
			$formattedTitle = $movieInfo['title'] . ' (' . $movieInfo['original_title'] . ')';
		} else {
			$formattedTitle = $movieInfo['title'] ?? $movieInfo['original_title'] ?? '???';
		}
		$reference->setTitle($formattedTitle);
		$movieInfo['formatted_title'] = $formattedTitle;
		if (isset($movieInfo['release_date']) && is_string($movieInfo['release_date'])) {
			$date = $this->utilsService->formatDate($movieInfo['release_date']);
			$movieInfo['formatted_release_date'] = $date;
			$reference->setDescription($date . ' - ' . $movieInfo['overview']);
		} else {
			$reference->setDescription($movieInfo['overview']);
		}
		$fallbackName = $movieInfo['name'] ?? $movieInfo['original_name'] ?? '???';
		if (isset($movieInfo['poster_path']) && $movieInfo['poster_path']) {
			$imagePath = preg_replace('/^\/+/', '', $movieInfo['poster_path']);
			$fallbackName = preg_replace('/\//', '', $fallbackName);
			$imageUrl = $this->urlGenerator->linkToRouteAbsolute(
				Application::APP_ID . '.tmdbAPI.getImage',
				['size' => 'w500', 'imagePath' => $imagePath, 'fallbackName' => $fallbackName]
			);
		} else {
			$imageUrl = $this->urlGenerator->linkToRouteAbsolute('core.GuestAvatar.getAvatar', ['guestName' => $fallbackName, 'size' => 44]);
		}
		$movieInfo['image_url'] = $imageUrl;
		$movieInfo['tmdb_url'] = $referenceText;
		$reference->setImageUrl($imageUrl);
		$reference->setRichObject(
			self::RICH_OBJECT_TYPE_MOVIE,
			$movieInfo,
		);
		return $reference;
	}

	/**
	 * @param string $url
	 * @return array|null
	 */
	private function getUrlInfo(string $url): ?array {
		preg_match('/^(?:https?:\/\/)?(?:www\.)?themoviedb\.org\/movie\/(\d+)/i', $url, $matches);
		if (count($matches) > 1) {
			return [
				'type' => 'tmdb-movie',
				'id' => (int)$matches[1],
			];
		}

		preg_match('/^(?:https?:\/\/)?(?:www\.)?themoviedb\.org\/tv\/(\d+)/i', $url, $matches);
		if (count($matches) > 1) {
			return [
				'type' => 'tmdb-tv',
				'id' => (int)$matches[1],
			];
		}

		preg_match('/^(?:https?:\/\/)?(?:www\.)?themoviedb\.org\/person\/(\d+)/i', $url, $matches);
		if (count($matches) > 1) {
			return [
				'type' => 'tmdb-person',
				'id' => (int)$matches[1],
			];
		}

		preg_match('/^(?:https?:\/\/)?(?:www\.)?imdb\.com\/name\/([^\/]+)/i', $url, $matches);
		if (count($matches) > 1) {
			return [
				'type' => 'imdb-person',
				'id' => $matches[1],
			];
		}

		preg_match('/^(?:https?:\/\/)?(?:www\.)?imdb\.com\/title\/([^\/]+)/i', $url, $matches);
		if (count($matches) > 1) {
			return [
				'type' => 'imdb-movie',
				'id' => $matches[1],
			];
		}

		return null;
	}

	/**
	 * We use the userId here because when connecting/disconnecting from the GitHub account,
	 * we want to invalidate all the user cache and this is only possible with the cache prefix
	 * @inheritDoc
	 */
	public function getCachePrefix(string $referenceId): string {
		return $this->userId ?? '';
	}

	/**
	 * We don't use the userId here but rather a reference unique id
	 * @inheritDoc
	 */
	public function getCacheKey(string $referenceId): ?string {
		return $referenceId;
	}

	/**
	 * @param string $userId
	 * @return void
	 */
	public function invalidateUserCache(string $userId): void {
		$this->referenceManager->invalidateCache($userId);
	}
}
