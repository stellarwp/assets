<?php
namespace StellarWP\Assets;

use StellarWP\Assets\Tests\AssetTestCase;

class ConfigTest extends AssetTestCase {
	public function setUp() {
		// before
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
		Config::reset();
	}

	/**
	 * @test
	 */
	public function should_set_hook_prefix() {
		Config::set_hook_prefix( 'bork' );

		$this->assertEquals( 'bork', Config::get_hook_prefix() );
	}
}
