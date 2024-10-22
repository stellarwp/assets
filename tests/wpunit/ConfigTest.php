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

		$this->assertEquals( WP_PLUGIN_DIR . '/assets/', Config::get_path() );
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
	public function should_reset() {
		Config::set_hook_prefix( 'bork' );
		Config::set_version( '1.1.0' );
		Config::set_path( dirname( dirname( __DIR__ ) ) );
		Config::set_relative_asset_path( 'src/resources/' );
		Config::reset();

		$this->assertEquals( 'src/assets/', Config::get_relative_asset_path() );
		$this->assertEquals( '', Config::get_version() );

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

	/**
	 * @test
	 */
	public function should_add_group_paths() {
		Config::add_group_path( 'my-group-path-1', [ 'root' => dirname( dirname( __DIR__ ) ) . '/src/feature-1', 'relative' => 'app-1' ] );
		Config::add_group_path( 'my-group-path-2', [ 'root' => dirname( dirname( __DIR__ ) ) . '/src/feature-2', 'relative' => 'app-2' ] );

		$this->assertEquals( WP_PLUGIN_DIR . '/assets/src/feature-1/', Config::get_path_of_group_path( 'my-group-path-1' ) );
		$this->assertEquals( 'app-1/', Config::get_relative_path_of_group_path( 'my-group-path-1' ) );
		$this->assertEquals( WP_PLUGIN_DIR . '/assets/src/feature-2/', Config::get_path_of_group_path( 'my-group-path-2' ) );
		$this->assertEquals( 'app-2/', Config::get_relative_path_of_group_path( 'my-group-path-2' ) );
	}

	/**
	 * @test
	 */
	public function should_throw_exception_when_root_is_not_provided() {
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'You must specify a root path for the group path.' );
		Config::add_group_path( 'my-group-path-1', [ 'relative' => 'app-1' ] );
	}

	/**
	 * @test
	 */
	public function should_throw_exception_when_relative_is_not_provided() {
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'You must specify a relative path for the group path.' );
		Config::add_group_path( 'my-group-path-1', [ 'root' => 'app-1' ] );
	}

	/**
	 * @test
	 */
	public function should_return_empty_string_if_no_group() {
		$this->assertEquals( '', Config::get_path_of_group_path( 'test1-' . wp_rand( 1, 9999 ) ) );
		$this->assertEquals( '', Config::get_relative_path_of_group_path( 'test2-' . wp_rand( 1, 9999 ) ) );
	}
}
