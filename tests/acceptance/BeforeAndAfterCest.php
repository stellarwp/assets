<?php
namespace StellarWP\Assets\Tests;

use AcceptanceTester;

class BeforeAndAfterCest {
	public function it_should_call_callback_after_enqueue( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-js', 'fake.js' )
				->enqueue_on( 'wp_enqueue_scripts' )
				->call_after_enqueue( function() {
					\$_SERVER['fake-js-after-enqueue'] = true;
				} )
				->register();
		}, 100 );

		add_action( 'wp_footer', function() {
			if ( ! empty( \$_SERVER['fake-js-after-enqueue'] ) ) {
				echo '<strong data-fired="yes">FIRED</strong>';
			}
		} );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->seeElement( 'strong', [ 'data-fired' => 'yes' ] );
	}

	public function it_should_print_before_and_after( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-js', 'fake.js' )
				->enqueue_on( 'wp_enqueue_scripts' )
				->print_before( '<strong data-before="yes"></strong>' )
				->print_after( '<strong data-after="yes"></strong>' )
				->register();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->seeElement( 'strong', [ 'data-before' => 'yes' ] );
		$I->seeElement( 'strong', [ 'data-after' => 'yes' ] );
	}
}
