<?php
namespace StellarWP\Assets\Tests;

use AcceptanceTester;

class GroupCest {

	public function it_should_enqueue_everything_in_group( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-js', 'fake.js' )
				->add_to_group( 'fake-group' )
				->register();

			Asset::add( 'fake-css', 'fake.css' )
				->add_to_group( 'fake-group' )
				->register();

			Assets::init()->enqueue_group( 'fake-group' );
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->seeElement( 'script', [ 'src' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/js/fake.js?ver=1.0.0' ] );
		$I->seeElement( 'link', [ 'href' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/css/fake.css?ver=1.0.0' ] );
	}

	public function it_should_enqueue_multiple_groups( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-js', 'fake.js' )
				->add_to_group( 'fake-group' )
				->register();

			Asset::add( 'fake-css', 'fake.css' )
				->add_to_group( 'fake-group-two' )
				->register();

			Assets::init()->enqueue_group( [ 'fake-group', 'fake-group-two' ] );
		}, 100 );
		PHP;

		$I->haveMuPlugin( 'enqueue.php', $code );


		$I->amOnPage( '/' );
		$I->seeElement( 'script', [ 'src' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/js/fake.js?ver=1.0.0' ] );
		$I->seeElement( 'link', [ 'href' => 'http://wordpress.test/wp-content/plugins/assets/tests/_data/css/fake.css?ver=1.0.0' ] );
	}
}
