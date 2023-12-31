<?php
namespace StellarWP\Assets\Tests;

use AcceptanceTester;

class ConditionCest {

	public function it_should_enqueue_if_condition_is_set_to_home_and_on_home( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-js', 'fake.js' )
				->enqueue_on( 'wp_enqueue_scripts' )
				->set_condition( 'is_home' )
				->register();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->seeElement( 'script', [ 'src' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/js/fake.js?ver=1.0.0' ] );
	}

	public function it_should_not_enqueue_if_condition_is_set_to_not_home_and_on_home( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-js', 'fake.js' )
				->enqueue_on( 'wp_enqueue_scripts' )
				->set_condition( static function() {
					return ! is_home();
				} )
				->register();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->dontSeeElement( 'script', [ 'src' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/js/fake.js?ver=1.0.0' ] );
	}

	public function it_should_not_enqueue_if_condition_is_set_to_home_and_not_on_home( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-js', 'fake.js' )
				->enqueue_on( 'wp_enqueue_scripts' )
				->set_condition( 'is_home' )
				->register();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/wp-admin' );
		$I->dontSeeElement( 'script', [ 'src' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/js/fake.js?ver=1.0.0' ] );
	}

	public function it_should_ignore_conditions_if_forced( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-js', 'fake.js' )
				->enqueue_on( 'wp_enqueue_scripts' )
				->set_condition( static function() {
					return ! is_home();
				} )
				->register();

			Assets::init()->enqueue( 'fake-js', true );
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->seeElement( 'script', [ 'src' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/js/fake.js?ver=1.0.0' ] );
	}
}
