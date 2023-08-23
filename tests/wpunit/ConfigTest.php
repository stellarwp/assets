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

	/**
	 * @test
	 */
	public function should_set_path() {
		Config::set_path( dirname( dirname( __DIR__ ) ) );

		$this->assertEquals( dirname( dirname( __DIR__ ) ), Config::get_path() );
	}

	/**
	 * @test
	 */
	public function should_set_version() {
		Config::set_version( '1.1.0' );

		$this->assertEquals( '1.1.0', Config::get_version() );
	}

	/**
	 * @test
	 */
	public function should_reset() {
		Config::set_hook_prefix( 'bork' );
		Config::set_version( '1.1.0' );
		Config::set_path( dirname( dirname( __DIR__ ) ) );
		Config::reset();

		$this->assertEquals( null, Config::get_hook_prefix() );
		$this->assertEquals( null, Config::get_path() );
		$this->assertEquals( null, Config::get_version() );
	}
}
