<?php
namespace StellarWP\Assets\Tests;

use AcceptanceTester;

class EnqueueCSSCest {
	public function it_should_register_and_enqueue_css( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::register( 'fake-css', 'fake.css' )
				->set_action( 'wp_enqueue_scripts' )
				->enqueue();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->seeElement( 'link', [ 'href' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/css/fake.css?ver=1.0.0' ] );
	}

	public function it_should_enqueue_with_custom_version( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::register( 'fake-css', 'fake.css', '2.0.0' )
				->set_action( 'wp_enqueue_scripts' )
				->enqueue();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->seeElement( 'link', [ 'href' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/css/fake.css?ver=2.0.0' ] );
	}

	public function it_should_enqueue_when_missing_extension( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::register( 'fake-css', 'fake-css-with-no-extension' )
				->set_type( 'css' )
				->set_action( 'wp_enqueue_scripts' )
				->enqueue();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->seeElement( 'link', [ 'href' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/css/fake-css-with-no-extension?ver=1.0.0' ] );
	}

	public function it_should_not_enqueue_if_action_is_not_fired( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::register( 'fake-css', 'fake.css' )
				->set_action( 'boom' )
				->enqueue();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->dontSeeElement( 'link', [ 'href' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/css/fake.css?ver=1.0.0' ] );
	}
}
