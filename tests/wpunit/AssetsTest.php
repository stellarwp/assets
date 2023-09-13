<?php
namespace StellarWP\Assets;

use StellarWP\Assets\Tests\AssetTestCase;

class AssetsTest extends AssetTestCase {
	public function setUp() {
		// before
		parent::setUp();
		Config::set_hook_prefix( 'bork' );
		Config::set_version( '1.0.0' );
		Config::set_path( dirname( dirname( __DIR__ ) ) );
		Config::set_relative_asset_path( 'tests/_data/' );
	}

	public function tearDown() {
		parent::tearDown();
		Config::reset();
	}

	/**
	 * @test
	 */
	public function it_should_should_register_multiple_assets() {
		Asset::add( 'my-script', 'fake.js' )->register();
		Asset::add( 'my-style', 'fake.css' )->register();

		$this->assertTrue( Assets::init()->exists( 'my-script' ) );
		$this->assertTrue( Assets::init()->exists( 'my-style' ) );
		$this->assertTrue( wp_script_is( 'my-script', 'registered' ) );
		$this->assertTrue( wp_style_is( 'my-style', 'registered' ) );
		$this->assertEquals( 'my-script', Assets::init()->get( 'my-script' )->get_slug() );
		$this->assertEquals( 'my-style', Assets::init()->get( 'my-style' )->get_slug() );
	}

	/**
	 * @test
	 */
	public function it_should_should_remove_assets() {
		Asset::add( 'my-script', 'fake.js' )->register();
		Asset::add( 'my-style', 'fake.css' )->register();

		$this->assertTrue( Assets::init()->exists( 'my-script' ) );
		$this->assertTrue( wp_script_is( 'my-script', 'registered' ) );

		Assets::init()->remove( 'my-script' );

		$this->assertFalse( Assets::init()->exists( 'my-script' ) );
		$this->assertFalse( wp_script_is( 'my-script', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'my-script', 'registered' ) );
	}

	/**
	 * @test
	 */
	public function it_should_enqueue() {
		Asset::add( 'my-script', 'fake.js' )->register();
		Asset::add( 'my-style', 'fake.css' )->register();

		Assets::init()->enqueue( [ 'my-script', 'my-style' ] );

		$x = Assets::init()->get( 'my-style' );

		$this->assertTrue( wp_script_is( 'my-script', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'my-style', 'enqueued' ) );
	}

	/**
	 * @test
	 */
	public function it_should_dequeue_after_removing() {
		Asset::add( 'my-script', 'fake.js' )->register();

		Assets::init()->enqueue( [ 'my-script' ] );
		Assets::init()->remove( 'my-script' );

		$this->assertFalse( Assets::init()->exists( 'my-script' ) );
		$this->assertFalse( wp_script_is( 'my-script', 'enqueued' ) );
	}
}
