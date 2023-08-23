<?php

namespace StellarWP\Assets\Tests;

use StellarWP\Assets\Assets;

class AssetTestCase extends \Codeception\Test\Unit {
	protected $backupGlobals = false;

	public function setUp() {
		// before
		parent::setUp();

		Assets::init();
	}
}

