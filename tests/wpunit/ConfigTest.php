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

	public function paths_provider() {
		yield 'plugin' => [ WP_PLUGIN_DIR . '/assets/', '/var/www/html/wp-content/plugins/assets/' ];
		yield 'theme' => [ get_theme_file_path() . '/assets/', get_theme_file_path() . '/var/www/html/wp-content/themes/assets/' ];
		yield 'mu-plugin' => [ WPMU_PLUGIN_DIR . '/assets/', '/var/www/html/wp-content/mu-plugins/assets/' ];
		yield 'content' => [ WP_CONTENT_DIR . '/assets/', '/var/www/html/wp-content/assets/' ];
		yield 'root' => [ ABSPATH . 'assets/', '/var/www/html/assets/' ];
		yield 'relative' => [ 'src/resources/', 'src/resources/' ];
	}

	/**
	 * @test
	 * @dataProvider paths_provider
	 */
	public function should_set_root_path_correctly( $path, $expected ) {
		Config::set_path( $path );
		$this->assertEquals( $expected, Config::get_path( $path ), Config::get_path( $path ) );
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
	public function should_set_path_outside_of_themes_and_plugins() {
		Config::set_path( ABSPATH . 'foo/' );

		$this->assertEquals( '/var/www/html/foo/', Config::get_path() );
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
		Config::add_group_path( 'my-group-path-1', dirname( __DIR__, 2) . '/src/feature-1', 'app-1');
		Config::add_group_path( 'my-group-path-2', dirname( __DIR__, 2) . '/src/feature-2', 'app-2');

		$this->assertEquals( WP_PLUGIN_DIR . '/assets/src/feature-1/', Config::get_path_of_group_path( 'my-group-path-1' ) );
		$this->assertEquals( 'app-1/', Config::get_relative_path_of_group_path( 'my-group-path-1' ) );
		$this->assertEquals( WP_PLUGIN_DIR . '/assets/src/feature-2/', Config::get_path_of_group_path( 'my-group-path-2' ) );
		$this->assertEquals( 'app-2/', Config::get_relative_path_of_group_path( 'my-group-path-2' ) );
	}

	/**
	 * @test
	 */
	public function should_return_empty_string_if_no_group() {
		$this->assertEquals( '', Config::get_path_of_group_path( 'test1-' . wp_rand( 1, 9999 ) ) );
		$this->assertEquals( '', Config::get_relative_path_of_group_path( 'test2-' . wp_rand( 1, 9999 ) ) );
	}
}
