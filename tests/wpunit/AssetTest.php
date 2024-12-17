<?php

namespace wpunit;

use StellarWP\Assets\Asset;
use StellarWP\Assets\Config;
use StellarWP\Assets\Tests\AssetTestCase;

class AssetTest extends AssetTestCase {
	public function test_add_to_group_path_changes_resolution_path_to_group(): void {
		Config::reset();
		Config::set_hook_prefix( 'bork' );
		Config::set_version( '1.1.0' );
		Config::set_path( constant( 'WP_PLUGIN_DIR' ) . '/assets' );
		Config::set_relative_asset_path( 'tests/_data/' );
		Config::add_group_path( 'fake-group', constant( 'WP_PLUGIN_DIR' ) . '/some-plugin/build', '/' );

		$asset = new Asset( 'test-script', 'fake.js', '1.0.0', codecept_data_dir() );

		$this->assertEquals( WP_PLUGIN_DIR . '/assets/tests/_data/', $asset->get_root_path() );

		// Now add the asset to a group path.
		$asset->add_to_group_path( 'fake-group' );

		// The asset root path will change to the group path.
		$this->assertEquals( WP_PLUGIN_DIR . '/some-plugin/build/', $asset->get_root_path() );

		$asset->remove_from_group_path( 'fake-group' );

		$this->assertEquals( WP_PLUGIN_DIR . '/assets/tests/_data/', $asset->get_root_path() );
	}

	public function test_it_can_enqueue_multiple_times_overwritting_previous(): void {
		Config::reset();
		Config::set_hook_prefix( 'bork' );
		Config::set_version( '1.1.0' );
		Config::set_path( constant( 'WP_PLUGIN_DIR' ) . '/assets' );
		Config::set_relative_asset_path( 'tests/_data/' );

		$checker = static fn( string $handle ): array => wp_scripts()->registered[ $handle ]->deps;

		$asset = Asset::add( 'test-script-unique-654321', 'fake.js', '1.0.0', codecept_data_dir() );
		$asset->enqueue_on( 'a_random_action_1234' );
		$asset->set_dependencies( 'jquery', 'jquery-ui-core' );

		do_action( 'a_random_action_1234' );

		$asset->enqueue();

		$this->assertTrue( wp_script_is( $asset->get_slug(), 'enqueued' ) );
		$this->assertTrue( $asset->asset_is( 'enqueued' ) );
		$this->assertEquals( [ 'jquery', 'jquery-ui-core' ], $checker( $asset->get_slug() ) );
		$this->assertEquals( [ 'jquery', 'jquery-ui-core' ], $asset->get_dependencies() );

		$asset->set_dependencies( 'jquery' );
		// It's not going to register again! We need to set it as unregistered first.
		$asset->enqueue();

		$this->assertTrue( wp_script_is( $asset->get_slug(), 'enqueued' ) );
		$this->assertTrue( $asset->asset_is( 'enqueued' ) );
		$this->assertEquals( [ 'jquery', 'jquery-ui-core' ], $checker( $asset->get_slug() ) );
		$this->assertEquals( [ 'jquery' ], $asset->get_dependencies() );

		$asset->set_as_unregistered();
		$asset->enqueue();

		$this->assertTrue( wp_script_is( $asset->get_slug(), 'enqueued' ) );
		$this->assertTrue( $asset->asset_is( 'enqueued' ) );
		$this->assertEquals( [ 'jquery' ], $checker( $asset->get_slug() ) );
		$this->assertEquals( [ 'jquery' ], $asset->get_dependencies() );
	}

	public function test_it_can_register_multiple_times_overwritting_previous(): void {
		Config::reset();
		Config::set_hook_prefix( 'bork' );
		Config::set_version( '1.1.0' );
		Config::set_path( constant( 'WP_PLUGIN_DIR' ) . '/assets' );
		Config::set_relative_asset_path( 'tests/_data/' );

		$checker = static fn( string $handle ): array => wp_scripts()->registered[ $handle ]->deps;

		$asset = new Asset( 'test-script-unique-123456', 'fake.js', '1.0.0', codecept_data_dir() );

		$asset->set_dependencies( 'jquery', 'jquery-ui-core' );
		$asset->register();

		$this->assertTrue( wp_script_is( $asset->get_slug(), 'registered' ) );
		$this->assertTrue( $asset->asset_is( 'registered' ) );
		$this->assertEquals( [ 'jquery', 'jquery-ui-core' ], $checker( $asset->get_slug() ) );
		$this->assertEquals( [ 'jquery', 'jquery-ui-core' ], $asset->get_dependencies() );

		$asset->set_dependencies( 'jquery' );
		// It's not going to register again! We need to set it as unregistered first.
		$asset->register();

		$this->assertTrue( wp_script_is( $asset->get_slug(), 'registered' ) );
		$this->assertTrue( $asset->asset_is( 'registered' ) );
		$this->assertEquals( [ 'jquery', 'jquery-ui-core' ], $checker( $asset->get_slug() ) );
		$this->assertEquals( [ 'jquery' ], $asset->get_dependencies() );

		$asset->set_as_unregistered();
		$asset->register();

		$this->assertTrue( wp_script_is( $asset->get_slug(), 'registered' ) );
		$this->assertTrue( $asset->asset_is( 'registered' ) );
		$this->assertEquals( [ 'jquery' ], $checker( $asset->get_slug() ) );
		$this->assertEquals( [ 'jquery' ], $asset->get_dependencies() );
	}
}
