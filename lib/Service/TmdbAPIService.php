<?php
/**
 * Nextcloud - Tmdb
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier
 * @copyright Julien Veyssier 2023
 */

namespace OCA\Tmdb\Service;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use OCA\Tmdb\AppInfo\Application;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Service to make requests to Tmdb REST API
 */
class TmdbAPIService {

	private IClient $client;

	public function __construct(
		string $appName,
		private LoggerInterface $logger,
		private IL10N $l10n,
		private IConfig $config,
		private ICrypto $crypto,
		private IFactory $l10nFactory,
		IClientService $clientService
	) {
		$this->client = $clientService->newClient();
	}

	/**
	 * Search items
	 *
	 * @param string|null $userId
	 * @param string $query
	 * @param int $offset
	 * @param int $limit
	 * @return array request result
	 */
	public function searchMulti(?string $userId, string $query, int $offset = 0, int $limit = 5): array {
		$language = $this->getLanguage();
		$params = [
			'query' => $query,
			'language' => $language,
		];
		$result = $this->request($userId, 'search/multi', $params);
		if (!isset($result['error']) && isset($result['results']) && is_array($result['results'])) {
			return array_slice($result['results'], $offset, $limit);
		}
		return $result;
	}

	private function getLanguage(): string {
		$language = $this->l10nFactory->findLanguage();
		if (strlen($language) === 2) {
			return $language . '-' . strtoupper($language);
		}
		return $language;
	}

	/**
	 * @param string|null $userId
	 * @param int $movieId
	 * @return array
	 */
	public function getMovieInfo(?string $userId, int $movieId): array {
		$language = $this->getLanguage();
		$params = [
			'language' => $language,
		];
		return $this->request($userId, 'movie/' . $movieId, $params);
	}

	/**
	 * @param string|null $userId
	 * @param int $personId
	 * @return array
	 */
	public function getPersonInfo(?string $userId, int $personId): array {
		$language = $this->getLanguage();
		$params = [
			'language' => $language,
		];
		return $this->request($userId, 'person/' . $personId, $params);
	}

	/**
	 * @param string|null $userId
	 * @param int $tvInfo
	 * @return array
	 */
	public function getTvInfo(?string $userId, int $tvInfo): array {
		$language = $this->getLanguage();
		$params = [
			'language' => $language,
		];
		return $this->request($userId, 'tv/' . $tvInfo, $params);
	}

	/**
	 * @param string|null $userId
	 * @param string $movieId
	 * @return array
	 */
	public function getMovieInfoFromImdbId(?string $userId, string $movieId): array {
		$language = $this->getLanguage();
		$params = [
			'language' => $language,
			'external_source' => 'imdb_id',
		];
		$result = $this->request($userId, 'find/' . $movieId, $params);
		if (isset($result['error'])) {
			return $result;
		}
		if (isset($result['movie_results']) && is_array($result['movie_results']) && count($result['movie_results']) > 0) {
			if (isset($result['movie_results'][0])) {
				$shortMovie = $result['movie_results'][0];
				if (isset($shortMovie['id'])) {
					return $this->getMovieInfo($userId, $shortMovie['id']);
				}
			}
		}
		if (isset($result['tv_results']) && is_array($result['tv_results']) && count($result['tv_results']) > 0) {
			if (isset($result['tv_results'][0])) {
				$shortTv = $result['tv_results'][0];
				if (isset($shortTv['id'])) {
					return $this->getTvInfo($userId, $shortTv['id']);
				}
			}
		}
		return [
			'error' => 'no result',
		];
	}

	/**
	 * @param string|null $userId
	 * @param string $personId
	 * @return array
	 */
	public function getPersonInfoFromImdbId(?string $userId, string $personId): array {
		$language = $this->getLanguage();
		$params = [
			'language' => $language,
			'external_source' => 'imdb_id',
		];
		$result = $this->request($userId, 'find/' . $personId, $params);
		if (isset($result['error'])) {
			return $result;
		}
		if (isset($result['person_results']) && is_array($result['person_results']) && count($result['person_results']) > 0) {
			if (isset($result['person_results'][0])) {
				$shortPerson = $result['person_results'][0];
				if (isset($shortPerson['id'])) {
					return $this->getPersonInfo($userId, $shortPerson['id']);
				}
			}
		}
		return [
			'error' => 'no result',
		];
	}

	/**
	 * @param int $movieId
	 * @return string
	 */
	public function getMovieLinkFromTmdbId(int $movieId): string {
		return Application::TMDB_URL . '/movie/' . $movieId;
	}

	/**
	 * @param int $personId
	 * @return string
	 */
	public function getPersonLinkFromTmdbId(int $personId): string {
		return Application::TMDB_URL . '/person/' . $personId;
	}

	/**
	 * @param int $tvId
	 * @return string
	 */
	public function getTvLinkFromTmdbId(int $tvId): string {
		return Application::TMDB_URL . '/tv/' . $tvId;
	}

	/**
	 * @param string $size
	 * @param string $imagePath
	 * @return array
	 */
	public function getImage(string $size, string $imagePath): array {
		$url = 'https://image.tmdb.org/t/p/' . $size . '/' . $imagePath;
		$options = [
			'headers' => [
				'User-Agent' => 'Nextcloud TMDB integration',
			],
		];
		try {
			$response = $this->client->get($url, $options);
			$body = $response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('Bad credentials')];
			} else {
				return [
					'body' => $body,
					'headers' => $response->getHeaders(),
				];
			}
		} catch (Exception|Throwable $e) {
			$this->logger->warning('Tmdb get image error : ' . $e->getMessage(), ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}

	/**
	 * @param string|null $userId
	 * @return string
	 */
	private function getApiKeyV3(?string $userId): string {
		$adminApiKey = $this->config->getAppValue(Application::APP_ID, 'api_key_v3');
		if ($userId === null) {
			return $adminApiKey !== '' ? $this->crypto->decrypt($adminApiKey) : '';
		}
		$userApiKey = $this->config->getUserValue($userId, Application::APP_ID, 'api_key_v3', $adminApiKey) ?: $adminApiKey;
		return $userApiKey !== '' ? $this->crypto->decrypt($userApiKey): '';
	}

	/**
	 * @param string|null $userId
	 * @return string
	 */
	private function getApiKeyV4(?string $userId): string {
		$adminApiKey = $this->config->getAppValue(Application::APP_ID, 'api_key_v4');
		if ($userId === null) {
			return $adminApiKey !== '' ? $this->crypto->decrypt($adminApiKey) : '';
		}
		$userApiKey = $this->config->getUserValue($userId, Application::APP_ID, 'api_key_v4', $adminApiKey) ?: $adminApiKey;
		return $userApiKey !== '' ? $this->crypto->decrypt($userApiKey) : '';
	}

	/**
	 * Make an HTTP request to the Tmdb API
	 *
	 * @param string|null $userId
	 * @param string $endPoint The path to reach in https://api.themoviedb.org/3/
	 * @param array $params Query parameters (key/val pairs)
	 * @param string $method HTTP query method
	 * @param bool $rawResponse
	 * @return array decoded request result or error
	 */
	public function request(?string $userId, string $endPoint, array $params = [], string $method = 'GET', bool $rawResponse = false): array {
		$apiKeyV3 = $this->getApiKeyV3($userId);
		$apiKeyV4 = $this->getApiKeyV4($userId);
		if ($apiKeyV3) {
			$params['api_key'] = $apiKeyV3;
		}
		try {
			$url = 'https://api.themoviedb.org/3/' . $endPoint;
			$options = [
				'headers' => [
					'User-Agent' => 'Nextcloud TMDB integration',
				],
			];
			if ($apiKeyV4 && $apiKeyV3 === '') {
				$options['headers']['Authorization'] = 'Bearer ' . $apiKeyV4;
			}

			if (count($params) > 0) {
				if ($method === 'GET') {
					$paramsContent = http_build_query($params);
					$url .= '?' . $paramsContent;
				} else {
					$options['body'] = json_encode($params);
				}
			}

			if ($method === 'GET') {
				$response = $this->client->get($url, $options);
			} elseif ($method === 'POST') {
				$response = $this->client->post($url, $options);
			} elseif ($method === 'PUT') {
				$response = $this->client->put($url, $options);
			} elseif ($method === 'DELETE') {
				$response = $this->client->delete($url, $options);
			} else {
				return ['error' => $this->l10n->t('Bad HTTP method')];
			}
			$body = $response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('Bad credentials')];
			} else {
				if ($rawResponse) {
					return [
						'body' => $body,
						'headers' => $response->getHeaders(),
					];
				} else {
					return json_decode($body, true) ?: [];
				}
			}
		} catch (ClientException|ServerException $e) {
			$responseBody = $e->getResponse()->getBody();
			$parsedResponseBody = json_decode($responseBody, true);
			if ($e->getResponse()->getStatusCode() === 404) {
				// Only log inaccessible github links as debug
				$this->logger->debug('Tmdb API error : ' . $e->getMessage(), ['response_body' => $parsedResponseBody, 'app' => Application::APP_ID]);
			} else {
				$this->logger->warning('Tmdb API error : ' . $e->getMessage(), ['response_body' => $parsedResponseBody, 'app' => Application::APP_ID]);
			}
			return [
				'error' => $e->getMessage(),
				'body' => $parsedResponseBody,
			];
		} catch (Exception|Throwable $e) {
			$this->logger->warning('Tmdb API error : ' . $e->getMessage(), ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}
}
