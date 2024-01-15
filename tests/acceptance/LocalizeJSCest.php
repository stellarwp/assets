<?php
namespace StellarWP\Assets\Tests;

use AcceptanceTester;
use PHPUnit\Framework\Assert;

class LocalizeJSCest {

	public function it_should_localize( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-js', 'fake.js' )
				->enqueue_on( 'wp_enqueue_scripts' )
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

	public function immediate_localize_should_append( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-js', 'fake.js' )
				->enqueue_on( 'wp_enqueue_scripts' )
				->add_localize_script(
					'animal',
					[
						'cow' => 'black',
					]
				)
				->add_localize_script(
					'color',
					[ 'blue' ]
				)
				->add_localize_script(
					'animal',
					[
						'cow' => 'brown',
					]
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
		Assert::assertContains( 'brown', $contents );
		Assert::assertContains( 'black', $contents );
	}

	public function it_should_overwrite_localize_with_force( AcceptanceTester $I ) {
		$code = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code .= <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-js', 'fake.js' )
				->enqueue_on( 'wp_enqueue_scripts' )
				->add_localize_script(
					'animal',
					[
						'cow' => 'black',
					]
				)
				->add_localize_script(
					'color',
					[ 'blue' ]
				)
				->add_localize_script(
					'animal',
					[
						'cow' => 'brown',
					],
					true
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
		Assert::assertContains( 'brown', $contents );
		Assert::assertNotContains( 'black', $contents );
	}

	public function it_should_append_to_localize_with_same_handle( AcceptanceTester $I ) {
		$code_base = file_get_contents( codecept_data_dir( 'enqueue-template.php' ) );
		$code = $code_base . <<<PHP
		add_action( 'wp_enqueue_scripts', function() {
			Asset::add( 'fake-js', 'fake.js' )
				->enqueue_on( 'wp_enqueue_scripts' )
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

		$code_2 = $code_base . <<<PHP
		Assets::get( 'fake-js' )
			->add_localize_script(
				'animal',
					[
						'sheep' => 'true',
					]
			);

		PHP;

		$I->haveMuPlugin( 'enqueue_2.php', $code_2 );

		$I->amOnPage( '/' );
		$I->seeElement( '#fake-js-js-extra' );
		$contents = $I->grabTextFrom( '#fake-js-js-extra' );
		Assert::assertContains( 'var animal', $contents );
		Assert::assertContains( 'var color', $contents );
		Assert::assertContains( 'cow', $contents );
		Assert::assertContains( 'sheep', $contents );
	}
}
