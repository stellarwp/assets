<?php
namespace StellarWP\Assets\Tests;

use AcceptanceTester;
use PHPUnit\Framework\Assert;

class EnqueueJSCest {
	public function it_should_register_and_enqueue_js( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-js', 'fake.js' )
				->set_action( 'wp_enqueue_scripts' )
				->register();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->seeElement( 'script', [ 'src' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/js/fake.js?ver=1.0.0' ] );
	}

	public function it_should_enqueue_min( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-js', 'fake-with-min.js' )
				->set_action( 'wp_enqueue_scripts' )
				->register();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->seeElement( 'script', [ 'src' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/js/fake-with-min.min.js?ver=1.0.0' ] );
	}

	public function it_should_not_enqueue_if_dependencies_missing( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-js', 'fake.js' )
				->set_dependencies( [ 'something' ] )
				->set_action( 'wp_enqueue_scripts' )
				->register();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->dontSeeElement( 'script', [ 'src' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/js/fake.js?ver=1.0.0' ] );
	}

	public function it_should_enqueue_with_custom_version( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-js', 'fake.js', '2.0.0' )
				->set_action( 'wp_enqueue_scripts' )
				->register();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->seeElement( 'script', [ 'src' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/js/fake.js?ver=2.0.0' ] );
	}

	public function it_should_enqueue_when_missing_extension( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-js', 'fake-js-with-no-extension' )
				->set_type( 'js' )
				->set_action( 'wp_enqueue_scripts' )
				->register();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->seeElement( 'script', [ 'src' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/js/fake-js-with-no-extension?ver=1.0.0' ] );
	}

	public function it_should_not_enqueue_if_action_is_not_fired( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-js', 'fake.js' )
				->set_action( 'boom' )
				->register();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->dontSeeElement( 'script', [ 'src' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/js/fake.js?ver=1.0.0' ] );
	}

	public function it_should_be_deferred( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-js', 'fake.js' )
				->set_as_deferred( true )
				->set_action( 'wp_enqueue_scripts' )
				->register();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->seeElement( '#fake-js-js[defer]' );
	}

	public function it_should_be_async( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-js', 'fake.js' )
				->set_as_async( true )
				->set_action( 'wp_enqueue_scripts' )
				->register();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->seeElement( '#fake-js-js[async]' );
	}

	public function it_should_be_a_module( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-js', 'fake.js' )
				->set_as_module( true )
				->set_action( 'wp_enqueue_scripts' )
				->register();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->seeElement( '#fake-js-js[type=module]' );
	}

	public function it_should_localize( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-js', 'fake.js' )
				->set_action( 'wp_enqueue_scripts' )
				->add_localize_script(
					'animal',
					[
						'cow' => 'true',
					]
				)
				->add_localize_script(
					'color',
					[ 'blue' ]
				)
				->register();
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->seeElement( '#fake-js-js-extra' );
		$contents = $I->grabTextFrom( '#fake-js-js-extra' );
		Assert::assertContains( 'var animal', $contents );
		Assert::assertContains( 'var color', $contents );
	}
}
