<?php

require_once WP_CONTENT_DIR . '/plugins/assets/vendor/autoload.php';

use StellarWP\Assets\Asset;
use StellarWP\Assets\Assets;
use StellarWP\Assets\Config;

add_action( 'init', function() {
	Config::set_path( WP_CONTENT_DIR . '/plugins/assets/' );
	Config::set_relative_asset_path( 'tests/_data/' );
	Config::set_hook_prefix( 'bork' );
	Config::set_version( '1.0.0' );
	Assets::init();
} );
