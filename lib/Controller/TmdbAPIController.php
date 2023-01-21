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

use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

use OCA\Tmdb\Service\TmdbAPIService;
use OCP\IURLGenerator;

class TmdbAPIController extends OCSController {

	private TmdbAPIService $tmdbAPIService;
	private IURLGenerator $urlGenerator;
	private ?string $userId;

	public function __construct(string          $appName,
								IRequest        $request,
								TmdbAPIService   $tmdbAPIService,
								IURLGenerator   $urlGenerator,
								?string         $userId) {
		parent::__construct($appName, $request);
		$this->tmdbAPIService = $tmdbAPIService;
		$this->urlGenerator = $urlGenerator;
		$this->userId = $userId;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $size
	 * @param string $imagePath
	 * @param string $fallbackName
	 * @return DataDownloadResponse|RedirectResponse
	 */
	public function getImage(string $size, string $imagePath, string $fallbackName) {
		$result = $this->tmdbAPIService->getImage($this->userId, $size, $imagePath);
		if (isset($result['error'])) {
			$fallbackAvatarUrl = $this->urlGenerator->linkToRouteAbsolute('core.GuestAvatar.getAvatar', ['guestName' => $fallbackName, 'size' => 44]);
			return new RedirectResponse($fallbackAvatarUrl);
		} else {
			$response = new DataDownloadResponse(
				$result['body'],
				'tmdb-image',
				$result['headers']['Content-Type'][0] ?? 'image/jpeg'
			);
			$response->cacheFor(60 * 60 * 24);
			return $response;
		}
	}
}
