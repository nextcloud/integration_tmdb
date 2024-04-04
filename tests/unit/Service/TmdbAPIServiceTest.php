<?php

namespace OCA\Tmdb\Tests;

use OCA\Tmdb\AppInfo\Application;

class TmdbAPIServiceTest extends \PHPUnit\Framework\TestCase {

	public function testDummy() {
		$app = new Application();
		$this->assertEquals('integration_tmdb', $app::APP_ID);
	}
}
