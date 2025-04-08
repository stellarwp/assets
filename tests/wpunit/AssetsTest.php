<?php

namespace StellarWP\Assets;

use StellarWP\Assets\Tests\AssetTestCase;
use PHPUnit\Framework\Assert;
use stdClass;
use Closure;
use Generator;
use InvalidArgumentException;

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
			// Restore in reverse order.
			self::$uopz_redefines = array_reverse( self::$uopz_redefines );

			foreach ( self::$uopz_redefines as $restore_callback ) {
				$restore_callback();
			}
		}

		self::$uopz_redefines = [];
	}

	public function it_should_accept_instance_of_asset_or_array_of_assets_in_register_in_wp() {
		$asset_1 = Asset::add( 'fake1-script', 'fake1.js' );
		$asset_2 = Asset::add( 'fake1-style', 'fake1.css' );
		$asset_3 = Asset::add( 'fake2-script', 'fake2.js' );
		$asset_4 = Asset::add( 'fake2-style', 'fake2.css' );
		$asset_5 = Asset::add( 'fake3-script', 'fake3.js' );

		$assets = Assets::init();

		$assets->register_in_wp( null ); // No problemo... nothing happens though.
		$assets->register_in_wp( [] ); // No problemo... nothing happens though.

		$this->assertFalse( $asset_1->is_registered() );
		$assets->register_in_wp( $asset_1 );
		$this->assertTrue( $asset_1->is_registered() );

		$this->assertFalse( $asset_2->is_registered() );
		$this->assertFalse( $asset_3->is_registered() );
		$this->assertFalse( $asset_4->is_registered() );
		$this->assertFalse( $asset_5->is_registered() );
		$assets->register_in_wp( [ $asset_2, $asset_3, $asset_4, $asset_5 ] );
		$this->assertTrue( $asset_2->is_registered() );
		$this->assertTrue( $asset_3->is_registered() );
		$this->assertTrue( $asset_4->is_registered() );
		$this->assertTrue( $asset_5->is_registered() );
	}

	public function invalid_params_for_register_in_wp_provider(): Generator {
		yield 'string'         => [ fn() => 'string' ];
		yield 'int'            => [ fn() => 1 ];
		yield 'float'          => [ fn() => 1.1 ];
		yield 'bool - true'    => [ fn() => true ];
		yield 'bool - false'   => [ fn() => false ];
		yield 'object'         => [ fn() => new stdClass() ];
		yield 'array - mixed'  => [ fn () => [ Asset::add( 'fake1-script', 'fake1.js' ), 'string' ] ];
	}

	/**
	 * @test
	 * @dataProvider invalid_params_for_register_in_wp_provider
	 */
	public function it_should_throw_exception_when_invalid_params_are_passed_to_register_in_wp( Closure $fixture ) {
		$assets = Assets::init();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Assets in register_in_wp() must be of type Asset' );

		$assets->register_in_wp( $fixture() );
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
	 *
	 * @dataProvider constantProvider
	 */
	public function it_should_get_the_correct_url_when_wp_content_dir_and_wp_content_url_are_diff( $id, $constants ) {
		$slugs = [
			'fake1' => [ 'has_min' => true, 'has_only_min' => false ],
			'fake2' => [ 'has_min' => false, 'has_only_min' => false ],
			'fake3' => [ 'has_min' => true, 'has_only_min' => true ]
		];

		foreach ( array_keys( $slugs ) as $slug ) {
			Assets::init()->remove( $slug . '-script' );
			Assets::init()->remove( $slug . '-style' );
		}

		foreach ( $constants as $constant => $value ) {
			$this->set_const_value( $constant, $value );
			$this->assertEquals( $value, constant( $constant ) );
		}

		Config::reset();

		Config::set_hook_prefix( 'bork' );
		Config::set_version( '1.1.0' );
		Config::set_path( constant( 'WP_PLUGIN_DIR' ) . '/assets' );
		Config::set_relative_asset_path( 'tests/_data/' );

		foreach ( array_keys( $slugs ) as $slug ) {
			Asset::add( $slug . '-script', $slug . '.js' );
			Asset::add( $slug . '-style', $slug . '.css' );
		}

		foreach ( $slugs as $slug => $data ) {
			$this->assert_minified_found( $slug, true, $data['has_min'], $data['has_only_min'], $id );
			$this->assert_minified_found( $slug, false, $data['has_min'], $data['has_only_min'], $id );
		}
	}

	/**
	 * @test
	 *
	 * @dataProvider constantProvider
	 */
	public function it_should_get_the_correct_url_when_wp_content_dir_and_wp_content_url_are_diff_and_assets_are_in_asset_group( $id, $constants ) {
		$slugs = [
			'fake1' => [ true, false ],
			'fake2' => [ false, false ],
			'fake3' => [ true, true ]
		];

		foreach ( array_keys( $slugs ) as $slug ) {
			Assets::init()->remove( $slug . '-script' );
			Assets::init()->remove( $slug . '-style' );
		}

		foreach ( $constants as $constant => $value ) {
			$this->set_const_value( $constant, $value );
			$this->assertEquals( $value, constant( $constant ) );
		}

		Config::reset();

		Config::set_hook_prefix( 'bork' );
		Config::set_version( '1.1.0' );
		Config::set_path( constant( 'WP_PLUGIN_DIR' ) . '/assets' );
		Config::set_relative_asset_path( 'tests/_data/' );
		Config::add_group_path( 'fake-group-path', constant( 'WP_PLUGIN_DIR' ) . '/assets/tests', '_data/fake-feature', true );

		foreach ( array_keys( $slugs ) as $slug ) {
			Asset::add( $slug . '-script', $slug . '.js' )->add_to_group_path( 'fake-group-path' );
			Asset::add( $slug . '-style', $slug . '.css' )->add_to_group_path( 'fake-group-path' );
		}

		foreach ( $slugs as $slug => $data ) {
			$this->assert_minified_found( $slug, true, $data['0'], $data['1'], $id, 'fake-group-path', '/assets/tests/_data/fake-feature/' );
			$this->assert_minified_found( $slug, false, $data['0'], $data['1'], $id, 'fake-group-path', '/assets/tests/_data/fake-feature/' );
		}
	}

	/**
	 * @test
	 *
	 * @dataProvider constantProvider
	 */
	public function it_should_get_the_correct_url_when_wp_content_dir_and_wp_content_url_are_diff_and_assets_are_in_asset_group_without_prefixing( $id, $constants ) {
		$slugs = [
			'fake1' => [ true, false ],
			'fake2' => [ false, false ],
			'fake3' => [ true, true ]
		];

		foreach ( array_keys( $slugs ) as $slug ) {
			Assets::init()->remove( $slug . '-script' );
			Assets::init()->remove( $slug . '-style' );
		}

		foreach ( $constants as $constant => $value ) {
			$this->set_const_value( $constant, $value );
			$this->assertEquals( $value, constant( $constant ) );
		}

		Config::reset();

		Config::set_hook_prefix( 'bork' );
		Config::set_version( '1.1.0' );
		Config::set_path( constant( 'WP_PLUGIN_DIR' ) . '/assets' );
		Config::set_relative_asset_path( 'tests/_data/' );
		Config::add_group_path( 'fake-group-path', constant( 'WP_PLUGIN_DIR' ) . '/assets/tests', '_data/fake-feature' );

		foreach ( array_keys( $slugs ) as $slug ) {
			Asset::add( $slug . '-script', $slug . '.js' )->add_to_group_path( 'fake-group-path' );
			Asset::add( $slug . '-style', $slug . '.css' )->add_to_group_path( 'fake-group-path' );
		}

		foreach ( $slugs as $slug => $data ) {
			$this->assert_minified_found( $slug, true, $data['0'], $data['1'], $id, 'fake-group-path', '/assets/tests/_data/fake-feature/', false, false );
			$this->assert_minified_found( $slug, false, $data['0'], $data['1'], $id, 'fake-group-path', '/assets/tests/_data/fake-feature/', false, false );
		}
	}

	/**
	 * @test
	 *
	 * @dataProvider constantProvider
	 */
	public function it_should_get_the_correct_url_when_wp_content_dir_and_wp_content_url_are_diff_and_assets_are_in_asset_group_while_outside_of_root_path( $id, $constants ) {
		$slugs = [
			'fake1' => [ 'has_min' => true, 'has_only_min' => false ],
			'fake2' => [ 'has_min' => false, 'has_only_min' => false ],
			'fake3' => [ 'has_min' => true, 'has_only_min' => true ]
		];

		foreach ( array_keys( $slugs ) as $slug ) {
			Assets::init()->remove( $slug . '-script' );
			Assets::init()->remove( $slug . '-style' );
		}

		foreach ( $constants as $constant => $value ) {
			$this->set_const_value( $constant, $value );
			$this->assertEquals( $value, constant( $constant ) );
		}

		Config::reset();

		Config::set_hook_prefix( 'bork' );
		Config::set_version( '1.1.0' );
		Config::set_path( constant( 'WP_PLUGIN_DIR' ) . '/assets' );
		Config::set_relative_asset_path( 'tests/_data/' );
		// Now our scripts are using a path that does not actually exist in the filesystem. So we can't expect it to figure out minified vs un-minified. So we are adding a new param.
		Config::add_group_path( 'fake-group-path', constant( 'WP_PLUGIN_DIR' ) . '/another-plugin/ecp', 'random/feature', true );

		foreach ( array_keys( $slugs ) as $slug ) {
			Asset::add( $slug . '-script', $slug . '.js' )->add_to_group_path( 'fake-group-path' );
			Asset::add( $slug . '-style', $slug . '.css' )->add_to_group_path( 'fake-group-path' );
		}

		foreach ( $slugs as $slug => $data ) {
			$this->assert_minified_found( $slug, true, $data['has_min'], $data['has_only_min'], $id, 'fake-group-path', '/another-plugin/ecp/random/feature/', true );
			$this->assert_minified_found( $slug, false, $data['has_min'], $data['has_only_min'], $id, 'fake-group-path', '/another-plugin/ecp/random/feature/', true );
		}
	}

	public function constantProvider() {
		$data = [
			[
				// Normal.
				'**normal**',
				[
					'WP_CONTENT_DIR' => '/var/www/html/wp-content',
					'WP_CONTENT_URL' => 'http://wordpress.test/wp-content',
					'WP_PLUGIN_DIR'  => '/var/www/html/wp-content/plugins',
					'WP_PLUGIN_URL'  => 'http://wordpress.test/wp-content/plugins',
				],
			],
			[
				// Small complexity.
				'**small-complex**',
				[
					'WP_CONTENT_DIR' => '/var/www/html/wp-content',
					'WP_CONTENT_URL' => 'http://wordpress.test/foo',
					'WP_PLUGIN_DIR'  => '/var/www/html/wp-content/plugins',
					'WP_PLUGIN_URL'  => 'http://wordpress.test/foo/plugins',
				],
			],
			[
				// Complex.
				'**complex**',
				[
					'WP_CONTENT_DIR' => '/var/www/html/content',
					'WP_CONTENT_URL' => 'http://wordpress.test/content',
					'WP_PLUGIN_DIR'  => '/var/www/html/plugins',
					'WP_PLUGIN_URL'  => 'http://wordpress.test/plugins',
				],
			],
			[
				// More Complex.
				'**more-complex**',
				[
					'WP_CONTENT_DIR' => '/var/www/html/wp-content',
					'WP_CONTENT_URL' => 'http://wordpress.test/wp-content',
					'WP_PLUGIN_DIR'  => '/var/www/html/addons',
					'WP_PLUGIN_URL'  => null,
				],
			],
			[
				// More Complex.
				'**more-complex-2**',
				[
					'WP_CONTENT_DIR' => '/var/www/html/content',
					'WP_CONTENT_URL' => null,
					'WP_PLUGIN_DIR'  => null,
					'WP_PLUGIN_URL'  => null,
				],
			],
			[
				// More More Complex.
				'**more-more-complex**',
				[
					'WP_CONTENT_DIR' => '/var/www/html/content',
					'WP_CONTENT_URL' => null,
					'WP_PLUGIN_DIR'  => '/var/www/html/addons',
					'WP_PLUGIN_URL'  => null,
				],
			],
			[
				// Windows
				'**windows-simple**',
				[
					'ABSPATH'        => 'C:\\xampp\\htdocs\\wordpress',
					'WP_CONTENT_DIR' => 'C:\\xampp\\htdocs\\wordpress\\wp-content',
					'WP_CONTENT_URL' => 'http://wordpress.test/wp-content',
					'WP_PLUGIN_DIR'  => 'C:\\xampp\\htdocs\\wordpress\\wp-content\\plugins',
					'WP_PLUGIN_URL'  => 'http://wordpress.test/wp-content/plugins',
				],
			],
			[
				// Windows
				'**windows-complex**',
				[
					'ABSPATH'        => 'C:\\xampp\\htdocs\\wordpress',
					'WP_CONTENT_DIR' => 'C:\\xampp\\htdocs\\content',
					'WP_CONTENT_URL' => 'http://wordpress.test/content',
					'WP_PLUGIN_DIR'  => 'C:\\xampp\\htdocs\\addons',
					'WP_PLUGIN_URL'  => 'http://wordpress.test/wp-content/addons',
				],
			],
		];

		foreach ( $data as $d ) {
			yield $d;
		}
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
	 * @test
	 */
	public function it_should_not_use_include_css_asset_file_dependencies_when_no_dependencies_are_set(): void {
		$asset = Asset::add( 'my-style' . uniqid(), 'fake4.css' );

		$this->assertEmpty( $asset->get_dependencies() );
	}

	/**
	 * @test
	 */
	public function it_should_use_include_css_asset_file_dependencies_when_no_dependencies_are_set(): void {
		$asset = Asset::add( 'my-style' . uniqid(), 'fake4.css' )->use_asset_file( true );

		$this->assertEquals( ['some-dependency'], $asset->get_dependencies() );
	}

	/**
	 * @test
	 */
	public function it_should_use_include_css_asset_file_dependencies_when_dependencies_are_set(): void {
		$asset = Asset::add( 'my-style' . uniqid(), 'fake4.css' )->use_asset_file( true );
		$asset->set_dependencies( 'fake1' );

		$this->assertEquals( [ 'some-dependency', 'fake1' ], $asset->get_dependencies() );
	}

	/**
	 * @test
	 */
	public function it_should_use_include_css_asset_file_dependencies_when_dependencies_are_set_as_callable(): void {
		$asset = Asset::add( 'my-style' . uniqid(), 'fake4.css' )->use_asset_file( true );
		$asset->set_dependencies( static function() {
			return [ 'fake1' ];
		} );

		$this->assertContains( 'fake1', $asset->get_dependencies() );
		$this->assertContains( 'some-dependency', $asset->get_dependencies() );
	}

	/**
	 * @test
	 */
	public function it_should_not_use_css_asset_file_version_when_no_version_is_set(): void {
		$asset = Asset::add( 'my-style' . uniqid(), 'fake4.css' );

		$this->assertEquals( '1.0.0', $asset->get_version() );
	}

	/**
	 * @test
	 */
	public function it_should_use_css_asset_file_version_when_no_version_is_set(): void {
		$asset = Asset::add( 'my-style' . uniqid(), 'fake4.css' )->use_asset_file( true );

		$this->assertEquals( '12345', $asset->get_version() );
	}

	/**
	 * @test
	 */
	public function it_should_use_css_asset_file_version_when_version_is_set(): void {
		$asset = Asset::add( 'my-style' . uniqid(), 'fake4.css', '1.0' )->use_asset_file( true );

		$this->assertEquals( '12345', $asset->get_version() );
	}

	/**
	 * @test
	 */
	public function it_should_use_include_js_asset_file_dependencies_when_no_dependencies_are_set(): void {
		$asset = Asset::add( 'my-script' . uniqid(), 'fake4.js' );

		$this->assertContains( 'jquery', $asset->get_dependencies() );
	}

	/**
	 * @test
	 */
	public function it_should_use_include_js_asset_file_dependencies_when_dependencies_are_set(): void {
		$asset = Asset::add( 'my-script' . uniqid(), 'fake4.js' );
		$asset->set_dependencies( 'fake1' );

		$this->assertContains( 'fake1', $asset->get_dependencies() );
		$this->assertContains( 'jquery', $asset->get_dependencies() );
	}

	/**
	 * @test
	 */
	public function it_should_use_include_js_asset_file_dependencies_when_dependencies_are_set_as_callable(): void {
		$asset = Asset::add( 'my-script' . uniqid(), 'fake4.js' );
		$asset->set_dependencies( static function() {
			return [ 'fake1' ];
		} );

		$this->assertContains( 'fake1', $asset->get_dependencies() );
		$this->assertContains( 'jquery', $asset->get_dependencies() );
	}

	/**
	 * @test
	 */
	public function it_should_use_js_asset_file_version_when_no_version_is_set(): void {
		$asset = Asset::add( 'my-script' . uniqid(), 'fake4.js' );

		$this->assertEquals( '12345', $asset->get_version() );
	}

	/**
	 * @test
	 */
	public function it_should_use_js_asset_file_version_when_version_is_set(): void {
		$asset = Asset::add( 'my-script' . uniqid(), 'fake4.js', '1.0' );

		$this->assertEquals( '12345', $asset->get_version() );
	}

	/**
	 * @test
	 */
	public function it_should_allow_for_asset_file_path_overrides(): void {
		$asset = Asset::add( 'my-script' . uniqid(), 'fake4.js', '1.0.0' );
		$asset->set_asset_file( 'other-asset-root/fake4' );

		$this->assertContains( 'some-dependency', $asset->get_dependencies() );
		$this->assertNotContains( 'jquery', $asset->get_dependencies() );
		$this->assertEquals( '67890', $asset->get_version() );
	}

	/**
	 * @test
	 */
	public function it_should_allow_for_asset_file_path_overrides_when_providing_full_asset_file(): void {
		$asset = Asset::add( 'my-script' . uniqid(), 'fake4.js', '1.0.0' );
		$asset->set_asset_file( 'other-asset-root/fake4.asset.php' );

		$this->assertContains( 'some-dependency', $asset->get_dependencies() );
		$this->assertNotContains( 'jquery', $asset->get_dependencies() );
		$this->assertEquals( '67890', $asset->get_version() );
	}

	/**
	 * @test
	 */
	public function it_should_allow_for_asset_file_path_overrides_when_providing_full_js_file(): void {
		$asset = Asset::add( 'my-script' . uniqid(), 'fake4.js', '1.0.0' );
		$asset->set_asset_file( 'other-asset-root/fake4.js' );

		$this->assertContains( 'some-dependency', $asset->get_dependencies() );
		$this->assertNotContains( 'jquery', $asset->get_dependencies() );
		$this->assertEquals( '67890', $asset->get_version() );
	}

	/**
	 * @test
	 */
	public function it_should_reset_asset_file_contents_when_setting_asset_file_later(): void {
		$asset = Asset::add( 'my-script' . uniqid(), 'fake4.js' );

		$this->assertNotContains( 'some-dependency', $asset->get_dependencies() );
		$this->assertContains( 'jquery', $asset->get_dependencies() );
		$this->assertEquals( '12345', $asset->get_version() );

		$asset->set_asset_file( 'other-asset-root/fake4' );

		$this->assertContains( 'some-dependency', $asset->get_dependencies() );
		$this->assertNotContains( 'jquery', $asset->get_dependencies() );
		$this->assertEquals( '67890', $asset->get_version() );
	}

	/**
	 * @test
	 */
	public function it_should_set_properties_when_using_register_with_css() {
		$slug = 'something-' . uniqid() . '-css';

		Asset::add( $slug, 'something.css' )
			->set_path( 'tests/_data/build' )
			->set_min_path( 'tests/_data/build' )
			->add_to_group( 'bork' )
			->enqueue_on( 'wp_enqueue_scripts', 100 )
			->set_condition( 'is_admin' )
			->register_with_js();

		$css = Assets::init()->get( $slug );
		$js  = Assets::init()->get( str_replace( '-css', '-js', $slug ) );

		$this->assertEquals( $css->get_path(), $js->get_path() );
		$this->assertEquals( $css->get_min_path(), $js->get_min_path() );
		$this->assertEquals( $css->get_enqueue_on(), $js->get_enqueue_on() );
		$this->assertEquals( $css->get_condition(), $js->get_condition() );
		$this->assertEquals( $css->get_groups(), $js->get_groups() );
		$this->assertEquals( $css->get_priority(), $js->get_priority() );
	}

	/**
	 * @test
	 */
	public function it_should_set_properties_when_using_register_with_js() {
		$slug = 'something-' . uniqid() . '-js';

		Asset::add( $slug, 'something.js' )
			->set_path( 'tests/_data/build' )
			->set_min_path( 'tests/_data/build' )
			->add_to_group( 'bork' )
			->enqueue_on( 'wp_enqueue_scripts', 100 )
			->set_condition( 'is_admin' )
			->register_with_css();

		$js  = Assets::init()->get( $slug );
		$css = Assets::init()->get( str_replace( '-js', '-css', $slug ) );

		$this->assertEquals( $css->get_path(), $js->get_path() );
		$this->assertEquals( $css->get_min_path(), $js->get_min_path() );
		$this->assertEquals( $css->get_enqueue_on(), $js->get_enqueue_on() );
		$this->assertEquals( $css->get_condition(), $js->get_condition() );
		$this->assertEquals( $css->get_groups(), $js->get_groups() );
		$this->assertEquals( $css->get_priority(), $js->get_priority() );
	}

	/**
	 * @test
	 */
	public function it_should_set_properties_with_clone_to() {
		$slug = 'something-' . uniqid() . '-js';

		$css = Asset::add( $slug, 'something.js' )
			->set_path( 'tests/_data/build' )
			->set_min_path( 'tests/_data/build' )
			->add_to_group( 'bork' )
			->enqueue_on( 'wp_enqueue_scripts', 100 )
			->set_condition( 'is_admin' )
			->clone_to( 'css' );

		$js = Assets::init()->get( $slug );

		$this->assertEquals( $css->get_path(), $js->get_path() );
		$this->assertEquals( $css->get_min_path(), $js->get_min_path() );
		$this->assertEquals( $css->get_enqueue_on(), $js->get_enqueue_on() );
		$this->assertEquals( $css->get_condition(), $js->get_condition() );
		$this->assertEquals( $css->get_groups(), $js->get_groups() );
		$this->assertEquals( $css->get_priority(), $js->get_priority() );
	}

	/**
	 * @test
	 */
	public function it_should_throw_errors_when_cloning_js_to_js() {
		$this->expectException( \InvalidArgumentException::class );
		$slug = 'something-' . uniqid() . '-js';

		Asset::add( $slug, 'something.js' )
			->set_path( 'tests/_data/build' )
			->set_min_path( 'tests/_data/build' )
			->add_to_group( 'bork' )
			->enqueue_on( 'wp_enqueue_scripts', 100 )
			->set_condition( 'is_admin' )
			->clone_to( 'js' );
	}

	/**
	 * @test
	 */
	public function it_should_throw_errors_when_cloning_css_to_css() {
		$this->expectException( \InvalidArgumentException::class );
		$slug = 'something-' . uniqid() . '-css';

		Asset::add( $slug, 'something.css' )
			->set_path( 'tests/_data/build' )
			->set_min_path( 'tests/_data/build' )
			->add_to_group( 'bork' )
			->enqueue_on( 'wp_enqueue_scripts', 100 )
			->set_condition( 'is_admin' )
			->clone_to( 'css' );
	}

	/**
	 * @test
	 */
	public function it_should_register_translations() {
		$slug = 'something-' . uniqid() . '-js';

		$asset = Asset::add( $slug, 'something.js' )
			->set_translations( 'fake1', 'tests/_data/lang' )
			->register();

		$this->assertEquals( 'fake1', $asset->get_textdomain() );
		$this->assertEquals( dirname( dirname( __DIR__ ) ) . '/tests/_data/lang', $asset->get_translation_path() );
	}

	/**
	 * @test
	 */
	public function it_should_throw_exception_when_registering_translations_for_css() {
		$this->expectException( \InvalidArgumentException::class );
		$slug = 'something-' . uniqid() . '-css';

		Asset::add( $slug, 'something.css' )
			->set_translations( 'fake1', 'tests/_data/lang' )
			->register();
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
	 * @param string $id
	 * @param string $add_to_path_group
	 * @param string $group_path_path
	 * @param bool   $wont_figure_out_min_vs_unmin
	 * @param bool   $should_prefix
	 */
	protected function assert_minified_found( $slug_prefix, $is_js = true, $has_min = true, $has_only_min = false, $id = '', $add_to_path_group = '', $group_path_path = '', $wont_figure_out_min_vs_unmin = false, $should_prefix = true ) {
		$asset = Assets::init()->get( $slug_prefix . '-' . ( $is_js ? 'script' : 'style' ) );

		$url = plugins_url( ( $group_path_path ? $group_path_path : '/assets/tests/_data/' ) . ( $should_prefix ? ( $is_js ? 'js/' : 'css/' ) : '' ) . $slug_prefix );

		$path = wp_normalize_path( WP_PLUGIN_DIR . ( $group_path_path ? $group_path_path : '/assets/tests/_data/' ) . ( $should_prefix ? ( $is_js ? 'js/' : 'css/' ) : '' ) . $slug_prefix );
		$urls = [];

		$this->set_const_value( 'SCRIPT_DEBUG', false );

		$this->assertFalse( SCRIPT_DEBUG );

		if ( $has_only_min ) {
			$urls[] = $url . '.min' . ( $is_js ? '.js' : '.css' );
			$urls[] = $url . '.min' . ( $is_js ? '.js' : '.css' );
			$temp_path = $path . '.min' . ( $is_js ? '.js' : '.css' );
			$path = file_exists( $temp_path ) ? $temp_path : $path . ( $is_js ? '.js' : '.css' );
		} elseif ( $has_min ) {
			$urls[] = $url . ( $is_js ? '.min.js' : '.min.css' );
			$urls[] = $url . ( $is_js ? '.js' : '.css' );
			$temp_path = $path . '.min' . ( $is_js ? '.js' : '.css' );
			$path = file_exists( $temp_path ) ? $temp_path : $path . ( $is_js ? '.js' : '.css' );
		} else {
			$urls[] = $url . ( $is_js ? '.js' : '.css' );
			$urls[] = $url . ( $is_js ? '.js' : '.css' );
			$path .= $is_js ? '.js' : '.css';
		}

		$plugins_path = str_replace( constant( 'WP_CONTENT_DIR' ), '', constant( 'WP_PLUGIN_DIR' ) );

		if ( constant( 'WP_PLUGIN_DIR' ) !== constant( 'WP_CONTENT_DIR' ) . $plugins_path || strpos( constant( 'ABSPATH' ), 'C:') === 0 || $wont_figure_out_min_vs_unmin ) {
			// If we are testing outside of the actual plugin directory, the `is_file` check will always fail.
			// In installations where this set up is the actual, the file should exist.
			// In this case it will always fail to locate mins.
			$urls = array_map(
				static function ( $url ) {
					return str_replace( '.min', '', $url );
				},
				$urls
			);
		}

		$this->assertEquals(
			$urls['0'],
			$asset->get_url(),
			$id
		);

		$this->set_const_value( 'SCRIPT_DEBUG', true );

		$this->assertTrue( SCRIPT_DEBUG );

		// Remove and re add to clear cache.
		Assets::init()->remove( $slug_prefix . '-' . ( $is_js ? 'script' : 'style' ) );
		$asset = Asset::add( $slug_prefix . '-' . ( $is_js ? 'script' : 'style' ), $slug_prefix . '.' . ( $is_js ? 'js' : 'css' ) );

		if ( $add_to_path_group ) {
			$asset->add_to_group_path( $add_to_path_group );
		}

		$this->assertEquals(
			$urls['1'],
			$asset->get_url(),
			$id
		);

		$this->assertEquals( $path, $asset->get_full_resource_path() );
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
