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
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;
use OCP\Http\Client\IClientService;
use Throwable;

class TmdbAPIService {
	private LoggerInterface $logger;
	private IL10N $l10n;
	private IConfig $config;
	private IURLGenerator $urlGenerator;
	private IClient $client;

	/**
	 * Service to make requests to Tmdb REST API
	 */
	public function __construct (string $appName,
								LoggerInterface $logger,
								IL10N $l10n,
								IConfig $config,
								IURLGenerator $urlGenerator,
								IClientService $clientService) {
		$this->client = $clientService->newClient();
		$this->logger = $logger;
		$this->l10n = $l10n;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * Search items
	 *
	 * @param string $userId
	 * @param string $query
	 * @param int $offset
	 * @param int $limit
	 * @return array request result
	 */
	public function searchMovie(string $userId, string $query, int $offset = 0, int $limit = 5): array {
		// no pagination...
		$params = [
			'query' => $query,
			//'language' => $language,
			'language' => 'en-US',
		];
		$result = $this->request($userId, 'search/multi', $params);
		if (!isset($result['error']) && isset($result['results']) && is_array($result['results'])) {
			return array_slice($result['results'], $offset, $limit);
		}
		return $result;
	}

	/**
	 * @param string $userId
	 * @return string
	 */
	private function getApiKey(string $userId): string {
		$adminApiKey = $this->config->getAppValue(Application::APP_ID, 'api_key', Application::DEFAULT_API_KEY_V3) ?: Application::DEFAULT_API_KEY_V3;
		return $this->config->getUserValue($userId, Application::APP_ID, 'api_key', $adminApiKey) ?: $adminApiKey;
	}

	/**
	 * Make an HTTP request to the Tmdb API
	 * @param string|null $userId
	 * @param string $endPoint The path to reach in api.github.com
	 * @param array $params Query parameters (key/val pairs)
	 * @param string $method HTTP query method
	 * @param bool $rawResponse
	 * @return array decoded request result or error
	 */
	public function request(?string $userId, string $endPoint, array $params = [], string $method = 'GET', bool $rawResponse = false): array {
		$apiKey = $this->getApiKey($userId);
		$params['api_key'] = $apiKey;
		try {
			$url = 'https://api.themoviedb.org/3/' . $endPoint;
			$options = [
				'headers' => [
					'User-Agent' => 'Nextcloud TMDB integration',
				],
			];

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
			} else if ($method === 'POST') {
				$response = $this->client->post($url, $options);
			} else if ($method === 'PUT') {
				$response = $this->client->put($url, $options);
			} else if ($method === 'DELETE') {
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
		} catch (ClientException | ServerException $e) {
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
		} catch (Exception | Throwable $e) {
			$this->logger->warning('Tmdb API error : ' . $e->getMessage(), ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}
}
