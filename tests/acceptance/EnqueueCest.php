<?php
namespace StellarWP\Assets\Tests;

use AcceptanceTester;
use StellarWP\Assets\Asset;
use StellarWP\Assets\Assets;
use StellarWP\Assets\Config;

class EnqueueCest {
	/**
	 * @test
	 */
	public function it_should_register_and_enqueue( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::register( 'fake-js', 'fake.js' )
			->set_action( 'wp_enqueue_scripts' )
			->enqueue();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->seeElement( 'script', [ 'src' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/js/fake.js?ver=1.0.0' ] );
	}
}
