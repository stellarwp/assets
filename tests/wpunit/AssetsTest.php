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

	/**
	 * It should localize data correctly
	 *
	 * @test
	 */
	public function should_localize_data_correctly(): void {
		Asset::add( 'my-first-script', 'first-script.js' )
		     ->add_localize_script( 'boomshakalakaProjectFirstScriptData', [
			     'animal' => 'cat',
			     'color'  => 'orange',
		     ] )
		     ->register();
		Asset::add( 'my-second-script', 'second-script.js' )
		     ->add_localize_script( 'boomshakalakaProjectSecondScriptData', [
			     'animal' => 'dog',
			     'color'  => 'green',
		     ] )
		     ->register();
		Asset::add( 'my-second-script-mod', 'second-script-mod.js' )
		     ->add_localize_script( 'boomshakalakaProjectSecondScriptModData', [
			     'animal' => 'horse'
		     ] )
		     ->register();

		$this->assertEquals( <<< SCRIPT
<script id="my-first-script-js-extra">
var boomshakalakaProjectFirstScriptData = {"animal":"cat","color":"orange"};
</script>

SCRIPT,
			apply_filters( 'script_loader_tag', '', 'my-first-script' )
		);
		$this->assertEquals( <<< SCRIPT
<script id="my-second-script-js-extra">
var boomshakalakaProjectSecondScriptData = {"animal":"dog","color":"green"};
</script>

SCRIPT,
			apply_filters( 'script_loader_tag', '', 'my-second-script' )
		);

		$this->assertEquals( <<< SCRIPT
<script id="my-second-script-mod-js-extra">
var boomshakalakaProjectSecondScriptModData = {"animal":"horse"};
</script>

SCRIPT,
			apply_filters( 'script_loader_tag', '', 'my-second-script-mod' )
		);
	}

	/**
	 * It should localize dot notation data correctly
	 *
	 * @test
	 */
	public function should_localize_dot_notation_data_correctly(): void {
		Asset::add( 'my-first-ns-script', 'first-script.js' )
		     ->add_localize_script( 'boomshakalaka.project.firstScriptData', [
			     'animal' => 'cat',
			     'color'  => 'orange',
		     ] )
		     ->register();
		Asset::add( 'my-second-ns-script', 'second-script.js' )
		     ->add_localize_script( 'boomshakalaka.project.secondScriptData', [
			     'animal' => 'dog',
			     'color'  => 'green',
		     ] )
		     ->register();
		Asset::add( 'my-second-ns-script-mod', 'second-script-mod.js' )
		     ->add_localize_script( 'boomshakalaka.project.secondScriptData', [
			     'animal' => 'horse'
		     ] )
		     ->register();

		$this->assertEquals( <<< SCRIPT
<script id="my-first-ns-script-ns-extra">
window.boomshakalaka = window.boomshakalaka || {};
window.boomshakalaka.project = window.boomshakalaka.project || {};
window.boomshakalaka.project.firstScriptData = Object.assign(window.boomshakalaka.project.firstScriptData || {}, {"animal":"cat","color":"orange"});
</script>
SCRIPT,
			apply_filters( 'script_loader_tag', '', 'my-first-ns-script' )
		);
		$this->assertEquals( <<< SCRIPT
<script id="my-second-ns-script-ns-extra">
window.boomshakalaka = window.boomshakalaka || {};
window.boomshakalaka.project = window.boomshakalaka.project || {};
window.boomshakalaka.project.secondScriptData = Object.assign(window.boomshakalaka.project.secondScriptData || {}, {"animal":"dog","color":"green"});
</script>
SCRIPT,
			apply_filters( 'script_loader_tag', '', 'my-second-ns-script' )
		);

		$this->assertEquals( <<< SCRIPT
<script id="my-second-ns-script-mod-ns-extra">
window.boomshakalaka = window.boomshakalaka || {};
window.boomshakalaka.project = window.boomshakalaka.project || {};
window.boomshakalaka.project.secondScriptData = Object.assign(window.boomshakalaka.project.secondScriptData || {}, {"animal":"horse"});
</script>
SCRIPT,
			apply_filters( 'script_loader_tag', '', 'my-second-ns-script-mod' )
		);
	}

	/**
	 * It should allow localizing data in normal and namespaced form for same script
	 *
	 * @test
	 */
	public function should_allow_localizing_data_in_normal_and_namespaced_form_for_same_script(): void {
		Asset::add( 'my-test-script', 'test-script.js' )
		     ->add_localize_script( 'boomshakalakaProjectTestScriptData', [
			     'animal' => 'cat',
			     'color'  => 'orange',
		     ] )
		     ->add_localize_script( 'boomshakalaka.project.testScriptData', [
			     'animal' => 'dog',
			     'color'  => 'green',
		     ] )
		     ->register();

		$apply_filters = apply_filters( 'script_loader_tag', '', 'my-test-script' );
		$this->assertEquals( <<< SCRIPT
<script id="my-test-script-js-extra">
var boomshakalakaProjectTestScriptData = {"animal":"cat","color":"orange"};
</script>
<script id="my-test-script-ns-extra">
window.boomshakalaka = window.boomshakalaka || {};
window.boomshakalaka.project = window.boomshakalaka.project || {};
window.boomshakalaka.project.testScriptData = Object.assign(window.boomshakalaka.project.testScriptData || {}, {"animal":"dog","color":"green"});
</script>
SCRIPT,
			$apply_filters
		);
	}

	/**
	 * It should allow localizing data using a Closure
	 *
	 * @test
	 */
	public function should_allow_localizing_data_using_a_closure(): void {
		$resolved_one      = false;
		$resolved_two      = false;
		$data_callback_one = function () use ( &$resolved_one ) {
			$resolved_one = true;

			return [
				'animal' => 'cat',
				'color'  => 'orange',
			];
		};
		$data_callback_two = function () use ( &$resolved_two ) {
			$resolved_two = true;

			return [
				'animal' => 'dog',
				'color'  => 'green',
			];
		};

		Asset::add( 'my-script-with-closure-data', 'test-script.js' )
		     ->add_localize_script( 'scriptWithClosureData', $data_callback_one )
		     ->add_localize_script( 'acme.project.closureData', $data_callback_two )
		     ->register();

		$this->assertFalse( $resolved_one, 'The first callback should not have been resolved yet.' );
		$this->assertFalse( $resolved_two, 'The second callback should not have been resolved yet.' );

		$apply_filters = apply_filters( 'script_loader_tag', '', 'my-script-with-closure-data' );
		$this->assertEquals( <<< SCRIPT
<script id="my-script-with-closure-data-js-extra">
var scriptWithClosureData = {"animal":"cat","color":"orange"};
</script>
<script id="my-script-with-closure-data-ns-extra">
window.acme = window.acme || {};
window.acme.project = window.acme.project || {};
window.acme.project.closureData = Object.assign(window.acme.project.closureData || {}, {"animal":"dog","color":"green"});
</script>
SCRIPT,
			$apply_filters
		);

		$this->assertTrue( $resolved_one );
		$this->assertTrue( $resolved_two );
	}

	/**
	 * It should allow setting dependencies with an array
	 *
	 * @test
	 */
	public function should_allow_setting_dependencies_with_an_array(): void {
		Asset::add( 'my-deps-base-script', 'base-script.js' )
		     ->register();
		Asset::add( 'my-deps-vendor-script', 'vendor-script.js' )
		     ->register();
		Asset::add( 'my-deps-dependent-script', 'dependent-script.js' )
		     ->set_dependencies( 'my-deps-base-script', 'my-deps-vendor-script' )
		     ->enqueue_on( 'test_action' )
		     ->print()
		     ->register();

		ob_start();
		do_action( 'test_action' );
		$this->assertEquals( <<< SCRIPT
<script src="http://wordpress.test/wp-content/plugins/assets/tests/_data/js/base-script.js?ver=1.0.0" id="my-deps-base-script-js"></script>
<script src="http://wordpress.test/wp-content/plugins/assets/tests/_data/js/vendor-script.js?ver=1.0.0" id="my-deps-vendor-script-js"></script>
<script src="http://wordpress.test/wp-content/plugins/assets/tests/_data/js/dependent-script.js?ver=1.0.0" id="my-deps-dependent-script-js"></script>

SCRIPT,
			ob_get_clean()
		);
	}

	/**
	 * It should allow setting dependencies with a callable
	 *
	 * @test
	 */
	public function should_allow_setting_dependencies_with_a_callable(): void {
		Asset::add( 'my-base-script-2', 'base-script-2.js' )
		     ->register();
		Asset::add( 'my-vendor-script-2', 'vendor-script-2.js' )
		     ->register();
		$resolved = false;
		$asset = Asset::add( 'my-dependent-script-2', 'dependent-script-2.js' )
		              ->set_dependencies( function () use ( &$resolved ) {
			              $resolved = true;

			              return [ 'my-base-script-2', 'my-vendor-script-2' ];
		              } )
		              ->enqueue_on( 'test_action_2' )
		              ->print();

		$this->assertFalse( $resolved, 'The dependencies should not have been resolved yet.' );

		$asset->register();

		$this->assertTrue( $resolved );

		ob_start();
		do_action( 'test_action_2' );
		$this->assertEquals( <<< SCRIPT
<script src="http://wordpress.test/wp-content/plugins/assets/tests/_data/js/base-script-2.js?ver=1.0.0" id="my-base-script-2-js"></script>
<script src="http://wordpress.test/wp-content/plugins/assets/tests/_data/js/vendor-script-2.js?ver=1.0.0" id="my-vendor-script-2-js"></script>
<script src="http://wordpress.test/wp-content/plugins/assets/tests/_data/js/dependent-script-2.js?ver=1.0.0" id="my-dependent-script-2-js"></script>

SCRIPT,
			ob_get_clean()
		);
	}
}
