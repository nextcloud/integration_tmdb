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

use OCA\Tmdb\Service\TmdbAPIService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\OCSController;

use OCP\IRequest;
use OCP\IURLGenerator;

class TmdbAPIController extends OCSController {

	public function __construct(string                 $appName,
		IRequest               $request,
		private TmdbAPIService $tmdbAPIService,
		private IURLGenerator  $urlGenerator,
		?string                $userId) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $size
	 * @param string $imagePath
	 * @param string $fallbackName
	 * @return Response
	 */
	public function getImage(string $size, string $imagePath, string $fallbackName): Response {
		$result = $this->tmdbAPIService->getImage($size, $imagePath);
		if (isset($result['error'])) {
			$fallbackAvatarUrl = $this->urlGenerator->linkToRouteAbsolute('core.GuestAvatar.getAvatar', ['guestName' => $fallbackName, 'size' => 44]);
			return new RedirectResponse($fallbackAvatarUrl);
		} else {
			$contentType = $result['headers']['Content-Type'][0] ?? 'image/jpeg';
			$response = new DataDisplayResponse(
				$result['body'],
				Http::STATUS_OK,
				['Content-Type' => $contentType]
			);
			$response->cacheFor(60 * 60 * 24);
			return $response;
		}
	}
}
