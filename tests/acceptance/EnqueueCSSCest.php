<?php
namespace StellarWP\Assets\Tests;

use AcceptanceTester;

class EnqueueCSSCest {
	public function it_should_register_and_enqueue_css( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-css', 'fake.css' )
				->enqueue_on( 'wp_enqueue_scripts' )
				->register();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->seeElement( 'link', [ 'href' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/css/fake.css?ver=1.0.0' ] );
	}

	public function it_should_enqueue_min( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-css', 'fake-with-min.css' )
				->enqueue_on( 'wp_enqueue_scripts' )
				->register();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->seeElement( 'link', [ 'href' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/css/fake-with-min.min.css?ver=1.0.0' ] );
	}

	public function it_should_enqueue_min_from_different_dir( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-css', 'fake.css' )
				->set_min_path( 'tests/_data/minified' )
				->enqueue_on( 'wp_enqueue_scripts' )
				->register();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->seeElement( 'link', [ 'href' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/minified/css/fake.min.css?ver=1.0.0' ] );
	}

	public function it_should_not_enqueue_if_dependencies_missing( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-css', 'fake.css' )
				->set_dependencies( [ 'something' ] )
				->enqueue_on( 'wp_enqueue_scripts' )
				->register();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->dontSeeElement( 'link', [ 'href' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/css/fake.css?ver=1.0.0' ] );
	}

	public function it_should_enqueue_with_custom_version( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-css', 'fake.css', '2.0.0' )
				->enqueue_on( 'wp_enqueue_scripts' )
				->register();
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
			Asset::add( 'fake-css', 'fake-css-with-no-extension' )
				->set_type( 'css' )
				->enqueue_on( 'wp_enqueue_scripts' )
				->register();
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
			Asset::add( 'fake-css', 'fake.css' )
				->enqueue_on( 'boom' )
				->register();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->dontSeeElement( 'link', [ 'href' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/css/fake.css?ver=1.0.0' ] );
	}

	public function it_should_replace_with_rtl( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		\$GLOBALS['text_direction'] = 'rtl';
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-css', 'fake.css' )
				->add_style_data( 'rtl', 'replace' )
				->enqueue_on( 'wp_enqueue_scripts' )
				->register();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->dontSeeElement( 'link', [ 'href' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/css/fake.css?ver=1.0.0' ] );
		$I->seeElement( 'link', [ 'href' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/css/fake-rtl.css?ver=1.0.0' ] );
	}

	public function it_should_add_rtl( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		\$GLOBALS['text_direction'] = 'rtl';
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-css', 'fake.css' )
				->add_style_data( 'rtl', true )
				->enqueue_on( 'wp_enqueue_scripts' )
				->register();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->seeElement( 'link', [ 'href' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/css/fake.css?ver=1.0.0' ] );
		$I->seeElement( 'link', [ 'href' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/css/fake-rtl.css?ver=1.0.0' ] );
	}

	public function it_should_set_media( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-css', 'fake.css' )
				->set_media( 'print' )
				->enqueue_on( 'wp_enqueue_scripts' )
				->register();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->seeElement( 'link', [ 'href' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/css/fake.css?ver=1.0.0', 'media' => 'print' ] );
		$I->dontSeeElement( 'link', [ 'href' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/css/fake.css?ver=1.0.0', 'media' => 'screen' ] );
	}
}
