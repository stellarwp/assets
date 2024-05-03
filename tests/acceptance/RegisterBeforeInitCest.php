<?php

namespace StellarWP\Assets\Tests;

use AcceptanceTester;

class RegisterBeforeInitCest {
	public function it_should_register_and_enqueue_js_before_init( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'immediate-enqueue-template.php' ) );
		$code .= <<<PHP
		Asset::add( 'fake-js', 'fake.js' )
			->enqueue_on( 'wp_enqueue_scripts' )
			->register();
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );

		$I->amOnPage( '/' );
		$I->seeElement( 'script', [ 'src' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/js/fake.js?ver=1.0.0' ] );
	}

	public function it_should_register_and_enqueue_css_before_init( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'immediate-enqueue-template.php' ) );
		$code .= <<<PHP
		Asset::add( 'fake-css', 'fake.css' )
			->enqueue_on( 'wp_enqueue_scripts' )
			->register();
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );

		$I->amOnPage( '/' );
		$I->seeElement( 'link', [ 'href' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/css/fake.css?ver=1.0.0' ] );
	}

	public function is_should_register_and_enqueue_js_with_dependencies_before_init( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'immediate-enqueue-template.php' ) );
		$code .= <<<PHP
		Asset::add( 'module-one', 'module-one.js' )->register();
		Asset::add( 'module-two', 'module-two.js' )->register();
		Asset::add( 'fake-js', 'fake.js' )
			->set_dependencies( 'module-one', 'module-two' )
			->enqueue_on( 'wp_enqueue_scripts' )
			->register();
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );

		$I->amOnPage( '/' );
		$I->seeElement( 'script', [ 'src' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/js/module-one.js?ver=1.0.0' ] );
		$I->seeElement( 'script', [ 'src' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/js/module-two.js?ver=1.0.0' ] );
		$I->seeElement( 'script', [ 'src' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/js/fake.js?ver=1.0.0' ] );
	}

	public function it_should_register_and_enqueue_css_with_dependencies_before_init( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'immediate-enqueue-template.php' ) );
		$code .= <<<PHP
		Asset::add( 'module-one', 'module-one.css' )->register();
		Asset::add( 'module-two', 'module-two.css' )->register();
		Asset::add( 'fake-css', 'fake.css' )
			->set_dependencies( 'module-one', 'module-two' )
			->enqueue_on( 'wp_enqueue_scripts' )
			->register();
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );

		$I->amOnPage( '/' );
		$I->seeElement( 'link', [ 'href' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/css/module-one.css?ver=1.0.0' ] );
		$I->seeElement( 'link', [ 'href' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/css/module-two.css?ver=1.0.0' ] );
		$I->seeElement( 'link', [ 'href' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/css/fake.css?ver=1.0.0' ] );
	}
}
