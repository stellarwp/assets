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

	public function test_can_filter_asset_version(): void {
		$prefix = 'bork';

		Config::reset();
		Config::set_hook_prefix( $prefix );
		Config::set_version( '1.1.0' );
		Config::set_path( constant( 'WP_PLUGIN_DIR' ) . '/assets' );

		$asset = new Asset( 'test-script', 'fake.js', '1.0.0', codecept_data_dir() );

		// The asset version should be the same as the one passed to the constructor.
		$this->assertEquals( '1.0.0', $asset->get_version() );

		// Filter the asset version.
		$filter = function () {
			return '2.0.0';
		};

		add_filter( "stellarwp/assets/{$prefix}/version", $filter );

		// After filtering, the asset version should be updated.
		$this->assertEquals( '2.0.0', $asset->get_version() );

		// Cleanup the filter.
		remove_filter( "stellarwp/assets/{$prefix}/version", $filter );
	}
}
