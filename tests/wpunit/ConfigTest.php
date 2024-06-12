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

		$this->assertEquals( '/wp-content/plugins/assets/', Config::get_path() );
	}

	/**
	 * @test
	 */
	public function should_set_relative_asset_path() {
		Config::set_relative_asset_path( 'src/resources' );

		$this->assertEquals( 'src/resources/', Config::get_relative_asset_path() );
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
	public function should_set_ignore_script_debug() {
		Config::set_ignore_script_debug( true );

		$this->assertTrue( Config::should_ignore_script_debug() );
	}

	/**
	 * @test
	 */
	public function should_reset() {
		Config::set_hook_prefix( 'bork' );
		Config::set_version( '1.1.0' );
		Config::set_path( dirname( dirname( __DIR__ ) ) );
		Config::set_relative_asset_path( 'src/resources/' );
		Config::set_ignore_script_debug( true );
		Config::reset();

		$this->assertEquals( 'src/assets/', Config::get_relative_asset_path() );
		$this->assertEquals( '', Config::get_version() );
		$this->assertFalse( Config::should_ignore_script_debug() );

		try {
			Config::get_hook_prefix();
		} catch ( \Exception $e ) {
			$this->assertInstanceOf( \RuntimeException::class, $e );
		}

		try {
			Config::get_path();
		} catch ( \Exception $e ) {
			$this->assertInstanceOf( \RuntimeException::class, $e );
		}
	}
}
