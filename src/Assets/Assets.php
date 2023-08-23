<?php

namespace StellarWP\Assets;

class Assets {
	/**
	 * @var ?Assets
	 */
	protected static $instance;

	/**
	 * @var array Array of memoized key value pairs.
	 */
	protected array $memoized = [];

	/**
	 * @var string
	 */
	private string $base_path;

	/**
	 * @var string
	 */
	private string $assets_url;

	/**
	 * @var string
	 */
	private string $version;

	/**
	 * Stores all the Assets and it's configurations.
	 *
	 * @var array
	 */
	protected $assets = [];

	/**
	 * Stores the localized scripts for reference.
	 *
	 * @var array
	 */
	private $localized = [];

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Assets
	 */
	public static function instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $base_path  Base path to the directory.
	 * @param string|null $assets_url Directory to the assets.
	 */
	public function __construct( $base_path = null, $assets_url = null ) {
		$this->base_path  = $base_path ?? Config::get_path();
		$this->assets_url = $assets_url ?? trailingslashit( plugins_url( $this->base_path ) );
		$this->version    = Config::get_version();
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Add actions for the Assets.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_actions() {
		add_action( 'init', [ $this, 'register_in_wp' ], 1, 0 );
	}

	/**
	 * Add filters for the Assets.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_filters() {
		add_filter( 'script_loader_tag', [ $this, 'filter_tag_async_defer' ], 50, 2 );
		add_filter( 'script_loader_tag', [ $this, 'filter_modify_to_module' ], 50, 2 );
		add_filter( 'script_loader_tag', [ $this, 'filter_print_before_after_script' ], 100, 2 );

		// Enqueue late.
		add_filter( 'script_loader_tag', [ $this, 'filter_add_localization_data' ], 500, 2 );
	}

	/**
	 * Registers a script with WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $handle
	 * @param string      $relative_path
	 * @param array       $deps
	 * @param string|null $version
	 * @param bool        $in_footer
	 *
	 * @return void
	 */
	public function register_script( string $handle, string $relative_path, array $deps = [], string $version = null, bool $in_footer = true ) {
		$hook_prefix = Config::get_hook_prefix();
		$file_name   = str_replace( '.js', '.asset.php', $relative_path );
		$file        = $this->base_path . $file_name;
		$version     = is_null( $version ) ? $this->version : $version;
		$asset_url   = $this->get_asset_url( $relative_path );

		if ( file_exists( $file ) ) {
			$assets  = include $file;
			$version = $assets['version'] ?? $version;
			if ( isset( $assets['dependencies'] ) ) {
				$deps = array_merge( $assets['dependencies'], $deps );
			}
		}

		/**
		 * Filter the asset URL of the script.
		 *
		 * @since 1.0.0
		 *
		 * @var string $asset_url URL of the script.
		 * @var string $handle Handle of the script.
		 */
		$asset_url = (string) apply_filters( "stellarwp/assets/{$hook_prefix}/script_url", $asset_url, $handle );

		/**
		 * Filter the dependencies of a script.
		 *
		 * @since 1.0.0
		 *
		 * @var array $deps Dependencies of the script.
		 * @var string $handle Handle of the script.
		 */
		$deps = (array) apply_filters( "stellarwp/assets/{$hook_prefix}/script_dependencies", $deps, $handle );

		/**
		 * Filter the version of a script.
		 *
		 * @since 1.0.0
		 *
		 * @var string $version Version of the script.
		 * @var string $handle Handle of the script.
		 */
		$version = (string) apply_filters( "stellarwp/assets/{$hook_prefix}/script_version", $version, $handle );

		/**
		 * Filter whether or not the script should be in the footer.
		 *
		 * @since 1.0.0
		 *
		 * @var bool $in_footer Whether or not the script should be in the footer.
		 * @var string $handle Handle of the script.
		 */
		$in_footer = (bool) apply_filters( "stellarwp/assets/{$hook_prefix}/script_in_footer", $in_footer, $handle );

		wp_register_script( $handle, $asset_url, $deps, $version, $in_footer );
	}

	/**
	 * Registers a style with WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $handle
	 * @param string      $relative_path
	 * @param array       $deps
	 * @param string|null $version
	 *
	 * @return void
	 */
	public function register_style( string $handle, string $relative_path, array $deps = [], string $version = null ) {
		$hook_prefix = Config::get_hook_prefix();
		$version     = is_null( $version ) ? $this->version : $version;
		$asset_url   = $this->get_asset_url( $relative_path );

		/**
		 * Filter the asset URL of the style.
		 *
		 * @since 1.0.0
		 *
		 * @var string $asset_url URL of the style.
		 * @var string $handle Handle of the style.
		 */
		$asset_url = apply_filters( "stellarwp/assets/{$hook_prefix}/style_url", $asset_url, $handle );

		/**
		 * Filter the dependencies of the style.
		 *
		 * @since 1.0.0
		 *
		 * @var array $deps Dependencies of the style.
		 * @var string $handle Handle of the style.
		 */
		$deps = apply_filters( "stellarwp/assets/{$hook_prefix}/style_dependencies", $deps, $handle );

		/**
		 * Filter the version of the style.
		 *
		 * @since 1.0.0
		 *
		 * @var string $version Version of the style.
		 * @var string $handle Handle of the style.
		 */
		$version = (string) apply_filters( "stellarwp/assets/{$hook_prefix}/script_version", $version, $handle );

		wp_register_style( $handle, $asset_url, $deps, $version );
	}

	/**
	 * Get the asset URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $relative_path The full path to the asset.
	 *
	 * @return string
	 */
	public function get_asset_url( string $relative_path ): string {
		return $this->assets_url . trim( $relative_path, '/' );
	}


	/**
	 * Depending on how certain scripts are loaded and how much cross-compatibility is required we need to be able to
	 * create noConflict backups and restore other scripts, which normally need to be printed directly on the scripts.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tag    Tag we are filtering.
	 * @param string $handle Which is the ID/Handle of the tag we are about to print.
	 *
	 * @return string Script tag with the before and after strings attached to it.
	 */
	public function filter_print_before_after_script( $tag, $handle ): string {
		// Only filter for our own filters.
		if ( ! $asset = $this->get( $handle ) ) {
			return (string) $tag;
		}

		// Bail when not dealing with JS assets.
		if ( 'js' !== $asset->get_type() ) {
			return (string) $tag;
		}

		// Only go forward if there is any print before or after.
		if ( empty( $asset->get_print_before() ) && empty( $asset->get_print_after() ) ) {
			return (string) $tag;
		}

		$before       = '';
		$print_before = $asset->get_print_before();
		if ( ! empty( $print_before ) ) {
			$before = (string) ( is_callable( $print_before ) ? call_user_func( $print_before, $asset ) : $print_before );
		}

		$after       = '';
		$print_after = $asset->get_print_after();
		if ( ! empty( $print_after ) ) {
			$after = (string) ( is_callable( $print_after ) ? call_user_func( $print_after, $asset ) : $print_after );
		}

		$tag = $before . (string) $tag . $after;

		return $tag;
	}

	/**
	 * Handles adding localization data, when attached to `script_loader_tag` which allows dependencies to load in their
	 * localization data as well.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tag    Tag we are filtering.
	 * @param string $handle Which is the ID/Handle of the tag we are about to print.
	 *
	 * @return string Script tag with the localization variable HTML attached to it.
	 */
	public function filter_add_localization_data( $tag, $handle ) {
		// Only filter for own filters.
		if ( ! $asset = $this->get( $handle ) ) {
			return $tag;
		}

		// Bail when not dealing with JS assets.
		if ( 'js' !== $asset->get_type() ) {
			return $tag;
		}

		// Only localize on JS and if we have data.
		if ( empty( $asset->get_localize_scripts() ) ) {
			return $tag;
		}

		global $wp_scripts;

		$localization = $asset->get_localize_scripts();

		/**
		 * Check to ensure we haven't already localized it before.
		 *
		 * @since 1.0.0
		 */
		foreach ( $localization as $key => $localize ) {

			if ( in_array( $key, $this->localized ) ) {
				continue;
			}

			// If we have a Callable as the Localize data we execute it.
			if ( is_callable( $localize ) ) {
				$localize = call_user_func( $localize, $asset );
			}

			wp_localize_script( $asset->get_slug(), $key, $localize );

			$this->localized[] = $key;
		}

		// Fetch the HTML for all the localized data.
		ob_start();
		$wp_scripts->print_extra_script( $asset->get_slug(), true );
		$localization_html = ob_get_clean();

		// After printing it remove data;|
		$wp_scripts->add_data( $asset->get_slug(), 'data', '' );

		return $localization_html . $tag;
	}

	/**
	 * Filters the Script tags to attach Async and/or Defer based on the rules we set in our Asset class.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tag    Tag we are filtering.
	 * @param string $handle Which is the ID/Handle of the tag we are about to print.
	 *
	 * @return string Script tag with the defer and/or async attached.
	 */
	public function filter_tag_async_defer( $tag, $handle ) {
		// Only filter for our own filters.
		if ( ! $asset = $this->get( $handle ) ) {
			return $tag;
		}

		// Bail when not dealing with JS assets.
		if ( 'js' !== $asset->get_type() ) {
			return $tag;
		}

		// When async and defer are false we bail with the tag.
		if ( ! $asset->is_deferred() && ! $asset->is_async() ) {
			return $tag;
		}

		$tag_has_async = false !== strpos( $tag, ' async ' );
		$tag_has_defer = false !== strpos( $tag, ' defer ' );
		$replacement   = '<script ';

		if ( $asset->is_async() && ! $tag_has_async ) {
			$replacement .= 'async ';
		}

		if ( $asset->is_deferred() && ! $tag_has_defer ) {
			$replacement .= 'defer ';
		}


		return str_replace( '<script ', $replacement, $tag );
	}

	/**
	 * Filters the Script tags to attach type=module based on the rules we set in our Asset class.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tag    Tag we are filtering.
	 * @param string $handle Which is the ID/Handle of the tag we are about to print.
	 *
	 * @return string Script tag with the type=module
	 */
	public function filter_modify_to_module( $tag, $handle ) {
		// Only filter for own own filters.
		if ( ! $asset = $this->get( $handle ) ) {
			return $tag;
		}

		// Bail when not dealing with JS assets.
		if ( 'js' !== $asset->get_type() ) {
			return $tag;
		}

		// When not module we bail with the tag.
		if ( ! $asset->is_module() ) {
			return $tag;
		}

		// These themes already have the `type='text/javascript'` added by WordPress core.
		if ( ! current_theme_supports( 'html5', 'script' ) ) {
			$replacement = 'type="module"';

			return str_replace( "type='text/javascript'", $replacement, $tag );
		}

		$replacement = '<script type="module" ';

		return str_replace( '<script ', $replacement, $tag );
	}

	/**
	 * Register the Assets on the correct hooks.
	 *
	 * @since 1.0.0
	 *
	 * @param array|Asset|null $assets Array of asset objects, single asset object, or null.
	 *
	 * @return void
	 */
	public function register_in_wp( $assets = null ) {
		if ( is_null( $assets ) ) {
			$assets = $this->get();
		}

		if ( ! is_array( $assets ) ) {
			$assets = [ $assets ];
		}

		foreach ( $assets as $asset ) {
			// Asset is already registered.
			if ( $asset->is_registered() ) {
				continue;
			}

			if ( 'js' === $asset->get_type() ) {
				// Script is already registered.
				if ( wp_script_is( $asset->get_slug(), 'registered' ) ) {
					continue;
				}

				$dependencies = $asset->get_dependencies();

				// If the asset is a callable, we call the function,
				// passing it the asset and expecting back an array of dependencies.
				if ( is_callable( $asset->get_dependencies() ) ) {
					$dependencies = call_user_func( $asset->get_dependencies(), [ $asset ] );
				}

				wp_register_script( $asset->get_slug(), $asset->get_url(), $dependencies, $asset->get_version(), $asset->is_in_footer() );

				// Register that this asset is actually registered on the WP methods.
				// @phpstan-ignore-next-line
				if ( wp_script_is( $asset->get_slug(), 'registered' ) ) {
					$asset->set_as_registered();
				}
			} else {
				// Style is already registered.
				if ( wp_style_is( $asset->get_slug(), 'registered' ) ) {
					continue;
				}

				wp_register_style( $asset->get_slug(), $asset->get_url(), $asset->get_dependencies(), $asset->get_version(), $asset->get_media() );

				// Register that this asset is actually registered on the WP methods.
				// @phpstan-ignore-next-line
				if ( wp_style_is( $asset->get_slug(), 'registered' ) ) {
					$asset->set_as_registered();
				}
			}

			// If we don't have an action we don't even register the action to enqueue.
			if ( empty( $asset->get_action() ) ) {
				continue;
			}

			// Now add an action to enqueue the registered assets.
			foreach ( (array) $asset->get_action() as $action ) {
				// Enqueue the registered assets at the appropriate time.
				if ( did_action( $action ) > 0 ) {
					$this->enqueue();
				} else {
					add_action( $action, [ $this, 'enqueue' ], $asset->get_priority(), 0 );
				}
			}
		}
	}

	/**
	 * Enqueues registered assets based on their groups.
	 *
	 * @since 1.0.0
	 *
	 * @param string|array $groups                        Which groups will be enqueued.
	 * @param bool         $should_enqueue_no_matter_what Whether to ignore conditional requirements when enqueuing.
	 *
	 * @uses  Assets::enqueue()
	 *
	 */
	public function enqueue_group( $groups, bool $should_enqueue_no_matter_what = false ) {
		$assets  = $this->get( null, false );
		$enqueue = [];

		foreach ( $assets as $asset ) {
			if ( empty( $asset->get_groups() ) ) {
				continue;
			}

			$intersect = array_intersect( (array) $groups, $asset->get_groups() );

			if ( empty( $intersect ) ) {
				continue;
			}
			$enqueue[] = $asset->get_slug();
		}

		$this->enqueue( $enqueue, $should_enqueue_no_matter_what );
	}

	/**
	 * Enqueues registered assets.
	 *
	 * This method is called on whichever action (if any) was declared during registration.
	 *
	 * It can also be called directly with a list of asset slugs to forcibly enqueue, which may be
	 * useful where an asset is required in a situation not anticipated when it was originally
	 * registered.
	 *
	 * @since 1.0.0
	 *
	 * @param string|array $assets_to_enqueue             Which assets will be enqueued.
	 * @param bool         $should_enqueue_no_matter_what Whether to ignore conditional requirements when enqueuing.
	 */
	public function enqueue( $assets_to_enqueue = null, bool $should_enqueue_no_matter_what = false ) {
		$assets_to_enqueue = array_filter( (array) $assets_to_enqueue );
		if ( ! empty( $assets_to_enqueue ) ) {
			$assets = (array) $this->get( $assets_to_enqueue );
		} else {
			$assets = $this->get();
		}

		foreach ( $assets as $asset ) {
			$slug = $asset->get_slug();
			// Should this asset be enqueued regardless of the current filter/any conditional requirements?
			$must_enqueue = in_array( $slug, $assets_to_enqueue );
			$in_filter    = in_array( current_filter(), (array) $asset->get_action() );

			// Skip if we are not on the correct filter (unless we are forcibly enqueuing).
			if ( ! $in_filter && ! $must_enqueue ) {
				continue;
			}

			// If any single conditional returns true, then we need to enqueue the asset.
			if ( empty( $asset->get_action() ) && ! $must_enqueue ) {
				continue;
			}

			// If this asset was late called
			if ( ! $asset->is_registered() ) {
				$this->register_in_wp( $asset );
			}

			if ( $asset->is_enqueued() ) {
				continue;
			}

			// Default to enqueuing the asset if there are no conditionals,
			// and default to not enqueuing it if there *are* conditionals.
			$condition     = $asset->get_condition();
			$has_condition = ! empty( $condition );
			$enqueue       = ! $has_condition;

			if ( $has_condition ) {
				$enqueue = (bool) call_user_func( $condition );
			}

			/**
			 * Allows developers to hook-in and prevent an asset from being loaded.
			 *
			 * @since 1.0.0
			 *
			 * @param bool   $enqueue If we should enqueue or not a given asset.
			 * @param object $asset   Which asset we are dealing with.
			 */
			$enqueue = (bool) apply_filters( 'stellarwp/assets/enqueue', $enqueue, $asset );

			/**
			 * Allows developers to hook-in and prevent an asset from being loaded.
			 *
			 * @since 1.0.0
			 *
			 * @param bool   $enqueue If we should enqueue or not a given asset.
			 * @param object $asset   Which asset we are dealing with.
			 */
			$enqueue = (bool) apply_filters( "stellarwp/assets/enqueue_{$slug}", $enqueue, $asset );

			if ( ! $enqueue && ! $should_enqueue_no_matter_what ) {
				continue;
			}

			if ( 'js' === $asset->get_type() ) {
				if ( $asset->should_print() && ! $asset->is_printed() ) {
					$asset->set_printed();
					wp_print_scripts( [ $slug ] );
				}
				// We print first, and tell the system it was enqueued, WP is smart not to do it twice.
				wp_enqueue_script( $slug );
			} else {
				if ( $asset->should_print() && ! $asset->is_printed() ) {
					$asset->set_printed();
					wp_print_styles( [ $slug ] );
				}
				// We print first, and tell the system it was enqueued, WP is smart not to do it twice.
				wp_enqueue_style( $slug );

				$style_data = $asset->get_style_data();
				foreach ( $style_data as $key => $value ) {
					wp_style_add_data( $slug, $key, $value );
				}
			}

			if ( ! empty( $asset->get_after_enqueue() ) && is_callable( $asset->get_after_enqueue() ) ) {
				call_user_func_array( $asset->get_after_enqueue(), [ $asset ] );
			}

			$asset->set_as_enqueued();
		}
	}

	/**
	 * Register an Asset and attach a callback to the required action to display it correctly.
	 *
	 * @since 1.0.0
	 *
	 * @param Asset $asset Register an asset.
	 *
	 * @return Asset|false The registered object or false on error.
	 */
	public function register( Asset $asset ) {
		// Prevent weird stuff here.
		$slug = $asset->get_slug();

		if ( $this->exists( $slug ) ) {
			return $this->get( $slug );
		}

		// Set the Asset on the array of notices.
		$this->assets[ $slug ] = $asset;

		// Return the Slug because it might be modified.
		return $asset;
	}

	/**
	 * Create an asset.
	 *
	 * @param string      $slug        The asset slug.
	 * @param string      $file        The asset file path.
	 * @param string|null $version     The asset version.
	 * @param string|null $plugin_path The path to the root of the plugin.
	 */
	public static function asset( string $slug, string $file, string $version = null, string $plugin_path = null ) {
		return static::instance()->register( new Asset( $slug, $file, $version, $plugin_path ) );
	}

	/**
	 * Removes an Asset from been registered and enqueue.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Slug of the Asset.
	 *
	 * @return bool
	 */
	public function remove( $slug ) {
		if ( ! $this->exists( $slug ) ) {
			return false;
		}

		unset( $this->assets[ $slug ] );
		return true;
	}

	/**
	 * Get the Asset Object configuration.
	 *
	 * @since 1.0.0
	 *
	 * @param string|array $slug Slug of the Asset.
	 * @param boolean      $sort If we should do any sorting before returning.
	 *
	 * @return array|Asset Array of asset objects, single asset object, or null if looking for a single asset but
	 *                           it was not in the array of objects.
	 */
	public function get( $slug = null, $sort = true ) {
		if ( is_null( $slug ) ) {
			if ( $sort ) {
				$cache_key_count = __METHOD__ . ':count';
				// Sorts by priority.
				$cache_count = $this->get_var( $cache_key_count, 0 );
				$count       = count( $this->assets );

				if ( $count !== $cache_count ) {
					uasort( $this->assets, static function( $a, $b ) {
						return stellarwp_sort_by_priority( $a, $b, 'get_priority' );
					} );
					$this->set_var( $cache_key_count, $count );
				}
			}
			return $this->assets;
		}

		// If slug is an array we return all of those.
		if ( is_array( $slug ) ) {
			$assets = [];
			foreach ( $slug as $asset_slug ) {
				$asset_slug = sanitize_key( $asset_slug );
				// Skip empty assets.
				if ( empty( $this->assets[ $asset_slug ] ) ) {
					continue;
				}

				$assets[ $asset_slug ] = $this->assets[ $asset_slug ];
			}

			if ( empty( $assets ) ) {
				return [];
			}

			if ( $sort ) {
				// Sorts by priority.
				uasort( $assets, static function( $a, $b ) {
					return stellarwp_sort_by_priority( $a, $b, 'get_priority' );
				} );
			}

			return $assets;
		}

		// Prevent weird stuff here.
		$slug = sanitize_key( $slug );

		if ( ! empty( $this->assets[ $slug ] ) ) {
			return $this->assets[ $slug ];
		}

		return [];
	}

	/**
	 * Gets a memoized value.
	 *
	 * @param string     $var     Var name.
	 * @param mixed|null $default Default value.
	 *
	 * @return mixed|null
	 */
	public function get_var( string $var, $default = null ) {
		return $this->memoized[ $var ] ?? $default;
	}

	/**
	 * Checks if an Asset exists.
	 *
	 * @since 1.0.0
	 *
	 * @param string|array $slug Slug of the Asset.
	 *
	 * @return bool
	 */
	public function exists( $slug ) {
		$slug = sanitize_key( $slug );
		return isset( $this->assets[ $slug ] );
	}

	/**
	 * Prints the `script` (JS) and `link` (CSS) HTML tags associated with one or more assets groups.
	 *
	 * The method will force the scripts and styles to print overriding their registration and conditional.
	 *
	 * @since 1.0.0
	 *
	 * @param string|array $group Which group(s) should be enqueued.
	 * @param bool         $echo  Whether to print the group(s) tag(s) to the page or not; default to `true` to
	 *                            print the HTML `script` (JS) and `link` (CSS) tags to the page.
	 *
	 * @return string The `script` and `link` HTML tags produced for the group(s).
	 */
	public function print_group( $group, $echo = true ) {
		$all_assets = $this->get();
		$groups     = (array) $group;
		$to_print   = array_filter( $all_assets, static function( Asset $asset ) use ( $groups ) {
			$asset_groups = $asset->get_groups();

			return ! empty( $asset_groups ) && array_intersect( $asset_groups, $groups );
		} );
		$by_type    = array_reduce( $to_print, static function( array $acc, Asset $asset ) {
			$acc[ $asset->get_type() ][] = $asset->get_slug();

			return $acc;
		}, [ 'css' => [], 'js' => [] ] );


		// Make sure each script is registered.
		foreach ( $to_print as $slug => $asset ) {
			if ( $asset->is_registered() ) {
				continue;
			}
			'js' === $asset->get_type()
				? wp_register_script( $slug, $asset->get_file(), $asset->get_dependencies(), $asset->get_version() )
				: wp_register_style( $slug, $asset->get_file(), $asset->get_dependencies(), $asset->get_version() );
		}

		ob_start();
		wp_scripts()->do_items( $by_type['js'] );
		wp_styles()->do_items( $by_type['css'] );
		$tags = ob_get_clean();

		if ( $echo ) {
			echo $tags;
		}

		return $tags;
	}

	/**
	 * Sets a memoized value.
	 *
	 * @param string     $var   Var name.
	 * @param mixed|null $value The value.
	 */
	public function set_var( string $var, $value = null ) {
		$this->memoized[ $var ] = $value;
	}
}
