<?php

namespace StellarWP\Assets;

use StellarWP\Assets\Tests\AssetTestCase;
use PHPUnit\Framework\Assert;

class AssetsTest extends AssetTestCase {
	/**
	 * Store const modifications.
	 *
	 * @var mixed
	 */
	protected static $uopz_redefines = [];

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
	 * @after
	 */
	public function unset_uopz_redefines() {
		if ( function_exists( 'uopz_redefine' ) ) {
			foreach ( self::$uopz_redefines as $restore_callback ) {
				$restore_callback();
			}
		}

		self::$uopz_redefines = [];
	}

	/**
	 * @test
	 */
	public function it_should_register_multiple_assets() {
		Asset::add( 'my-script', 'fake.js' )->register();
		Asset::add( 'my-style', 'fake.css' )->register();

		$this->existence_assertions( 'my' );
	}

	/**
	 * @test
	 */
	public function it_should_locate_minified_versions_of_external_assets() {
		Asset::add( 'fake1-script', 'fake1.js' )->register();
		Asset::add( 'fake1-style', 'fake1.css' )->register();
		Asset::add( 'fake2-script', 'fake2.js' )->register();
		Asset::add( 'fake2-style', 'fake2.css' )->register();
		Asset::add( 'fake3-script', 'fake3.js' )->register();
		Asset::add( 'fake3-style', 'fake3.css' )->register();

		$slugs = [
			'fake1' => [ true, false ],
			'fake2' => [ false, false ],
			'fake3' => [ true, true ]
		];

		foreach ( array_keys( $slugs ) as $slug ) {
			$this->existence_assertions( $slug );
		}

		foreach ( $slugs as $slug => $data ) {
			$this->assert_minified_found( $slug, true, ...$data );
			$this->assert_minified_found( $slug, false, ...$data );
		}
	}

	/**
	 * @test
	 */
	public function it_should_locate_minified_versions_of_external_assets() {

		Asset::add( 'fake3-script', 'fake3.js' )->register();

		$this->assertTrue( Assets::init()->exists( 'fake3-script' ) );
		$this->assertTrue( wp_script_is( 'fake3-script', 'registered' ) );
		$this->assertEquals( 'fake3-script', Assets::init()->get( 'fake3-script' )->get_slug() );

		$this->assert_minified_found( 'fake3', true, true, true );
	}

	/**
	 * @test
	 */
	public function it_should_remove_assets() {
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

	/**
	 * Evaluates if a script and style have been registered.
	 */
	protected function existence_assertions( $test_slug_prefix ) {
		$this->assertTrue( Assets::init()->exists( $test_slug_prefix . '-script' ) );
		$this->assertTrue( Assets::init()->exists( $test_slug_prefix . '-style' ) );
		$this->assertTrue( wp_script_is( $test_slug_prefix . '-script', 'registered' ) );
		$this->assertTrue( wp_style_is( $test_slug_prefix . '-style', 'registered' ) );
		$this->assertEquals( $test_slug_prefix . '-script', Assets::init()->get( $test_slug_prefix . '-script' )->get_slug() );
		$this->assertEquals( $test_slug_prefix . '-style', Assets::init()->get( $test_slug_prefix. '-style' )->get_slug() );
	}

	/**
	 * Asserts that the minified version of a script or style is found.
	 *
	 * @param string $slug_prefix
	 * @param bool   $is_js
	 * @param bool   $has_min
	 * @param bool   $has_only_min
	 */
	protected function assert_minified_found( $slug_prefix, $is_js = true, $has_min = true, $has_only_min = false ) {
		$asset = Assets::init()->get( $slug_prefix . '-' . ( $is_js ? 'script' : 'style' ) );

		$url = get_site_url() . '/wp-content/plugins/assets/tests/_data/' . ( $is_js ? 'js' : 'css' ) . '/' . $slug_prefix;

		$urls = [];

		$this->set_const_value( 'SCRIPT_DEBUG', false );

		$this->assertFalse( SCRIPT_DEBUG );

		if ( $has_only_min ) {
			$urls[] = $url . '.min' . ( $is_js ? '.js' : '.css' );
			$urls[] = $url . '.min' . ( $is_js ? '.js' : '.css' );
		} elseif ( $has_min ) {
			$urls[] = $url . ( $is_js ? '.min.js' : '.min.css' );
			$urls[] = $url . ( $is_js ? '.js' : '.css' );
		} else {
			$urls[] = $url . ( $is_js ? '.js' : '.css' );
			$urls[] = $url . ( $is_js ? '.js' : '.css' );
		}

		$this->assertEquals(
			$urls['0'],
			$asset->get_url()
		);

		$this->set_const_value( 'SCRIPT_DEBUG', true );

		$this->assertTrue( SCRIPT_DEBUG );

		// Remove and re add to clear cache.
		Assets::init()->remove( $slug_prefix . '-' . ( $is_js ? 'script' : 'style' ) );
		Asset::add( $slug_prefix . '-' . ( $is_js ? 'script' : 'style' ), $slug_prefix . '.' . ( $is_js ? 'js' : 'css' ) )->register();

		$asset = Assets::init()->get( $slug_prefix . '-' . ( $is_js ? 'script' : 'style' ) );

		$this->assertEquals(
			$urls['1'],
			$asset->get_url()
		);
	}

	/**
	 * Set a constant value using uopz.
	 *
	 * @param string $const
	 * @param mixed  $value
	 */
	private function set_const_value( $const, $value ) {
		if ( ! function_exists( 'uopz_redefine' ) ) {
			$this->markTestSkipped( 'uopz extension is not installed' );
		}

		// Normal const redefinition.
		$previous_value = defined( $const ) ? constant( $const ) : null;
		if ( null === $previous_value ) {
			$restore_callback = static function () use ( $const ) {
				uopz_undefine( $const );
				Assert::assertFalse( defined( $const ) );
			};
		} else {
			$restore_callback = static function () use ( $previous_value, $const ) {
				uopz_redefine( $const, $previous_value );
				Assert::assertEquals( $previous_value, constant( $const ) );
			};
		}
		uopz_redefine( $const, $value );
		self::$uopz_redefines[] = $restore_callback;
	}
}
