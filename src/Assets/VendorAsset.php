<?php

declare( strict_types=1 );

namespace StellarWP\Assets;

use InvalidArgumentException;
use LogicException;

class VendorAsset extends Asset {

	/**
	 * Whether this is a vendor asset.
	 *
	 * @var bool
	 */
	protected bool $is_vendor = true;

	/**
	 * Whether to attempt to load an .asset.php file.
	 *
	 * @var bool
	 */
	protected bool $use_asset_file = false;

	/**
	 * VendorAsset constructor.
	 *
	 * @param string $slug The asset slug.
	 * @param string $url  The asset URL.
	 * @param string $type The asset type.
	 *
	 * @throws InvalidArgumentException If the URL is not valid.
	 */
	public function __construct( string $slug, string $url, string $type = 'js' ) {
		$filtered = filter_var( $url, FILTER_VALIDATE_URL );
		if ( false === $filtered ) {
			throw new InvalidArgumentException( 'The URL must be a valid URL.' );
		}

		$this->url  = $filtered;
		$this->slug = sanitize_key( $slug );
		$this->type = strtolower( $type );
	}

	/**
	 * Registers a vendor asset.
	 *
	 * @param string  $slug    The asset slug.
	 * @param string  $url     The asset file path.
	 * @param ?string $type    The asset type.
	 * @param ?string $version The asset version.
	 *
	 * @return self
	 */
	public static function add( string $slug, string $url, ?string $type = null, $version = null ) {
		$instance = new self( $slug, $url, $type ?? 'js' );

		if ( null !== $version ) {
			$instance->set_version( (string) $version );
		}

		return Assets::init()->add( $instance );
	}

	/**
	 * Set the asset version.
	 *
	 * @param string $version The asset version.
	 *
	 * @return $this
	 */
	public function set_version( string $version ): self {
		$this->version = $version;
		return $this;
	}

	/**
	 * Get the asset version.
	 *
	 * @since 1.0.0
	 *
	 * @return string The asset version.
	 */
	public function get_version(): string {
		return $this->version ?? '';
	}

	/**
	 * Get the asset url.
	 *
	 * If the version has been provided, then it will be used to format the URL.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $_unused (Unused) Use the minified version of the asset if available.
	 *
	 * @return string
	 * @throws LogicException If the URL has a placeholder but no version is provided.
	 */
	public function get_url( bool $_unused = true ): string {
		$has_version = null !== $this->version;
		if ( ! $has_version && $this->url_has_placeholder( $this->url ) ) {
			throw new LogicException( 'A URL with a placeholder must have a version provided.' );
		}

		$url = $has_version
			? $this->get_formatted_url()
			: $this->url;

		$hook_prefix = Config::get_hook_prefix();

		/**
		 * Filters the asset URL.
		 *
		 * @param string $url   Asset URL.
		 * @param string $slug  Asset slug.
		 * @param Asset  $asset The Asset object.
		 */
		return (string) apply_filters( "stellarwp/assets/{$hook_prefix}/resource_url", $url, $this->slug, $this );
	}

	/**
	 * Get the minified version of the URL.
	 *
	 * @return string
	 */
	public function get_min_url(): string {
		return $this->get_url();
	}

	/**
	 * Get the formatted version of the URL.
	 *
	 * This will replace the version placeholder in the URL with the actual version. If there
	 * is no placeholder, it will append the version as a query string.
	 *
	 * @return string
	 */
	protected function get_formatted_url() {
		return $this->url_has_placeholder( $this->url )
			? sprintf( $this->url, $this->version )
			: add_query_arg( 'ver', $this->version, $this->url );
	}

	/**
	 * Determine if the URL has a placeholder for the version.
	 *
	 * @param string $url The URL to check.
	 *
	 * @return bool True if the URL has a placeholder, false otherwise.
	 */
	protected function url_has_placeholder( string $url ): bool {
		return false !== strpos( $url, '%s' );
	}

	// ---------------------------------------------
	// NO-OP or UNUSED METHODS
	// ---------------------------------------------

	/**
	 * Get the asset asset file path.
	 *
	 * @return string
	 */
	public function get_asset_file_path(): string {
		return '';
	}

	/**
	 * Get the asset file.
	 *
	 * @return string
	 */
	public function get_file(): string {
		return '';
	}

	/**
	 * Get the asset min path.
	 *
	 * @return string
	 */
	public function get_min_path(): string {
		return '';
	}

	/**
	 * Get the asset min path.
	 *
	 * @return string
	 */
	public function get_path(): string {
		return '';
	}

	/**
	 * Gets the root path for the resource.
	 *
	 * @return ?string
	 */
	public function get_root_path(): ?string {
		return '';
	}

	/**
	 * Get the asset translation path.
	 *
	 * @return string
	 */
	public function get_translation_path(): string {
		return '';
	}

	/**
	 * Get the asset's full path - considering if minified exists.
	 *
	 * @param bool $_unused
	 *
	 * @return string
	 */
	public function get_full_resource_path( bool $_unused = true ): string {
		return $this->get_url( $_unused );
	}

	/**
	 * Set the asset file path for the asset.
	 *
	 * @param string $path The partial path to the asset.
	 *
	 * @return static
	 */
	public function set_asset_file( string $path ) {
		return $this;
	}

	/**
	 * Set the directory where asset should be retrieved.
	 *
	 * @param ?string $path   The path to the minified file.
	 * @param ?bool   $prefix Whether to prefix files automatically by type (e.g. js/ for JS). Defaults to true.
	 *
	 * @return $this
	 */
	public function set_path( ?string $path = null, $prefix = null ) {
		return $this;
	}

	/**
	 * Set the directory where min files should be retrieved.
	 *
	 * @param ?string $path The path to the minified file.
	 *
	 * @return $this
	 */
	public function set_min_path( ?string $path = null ) {
		return $this;
	}

	/**
	 * Set whether or not to use an .asset.php file.
	 *
	 * @param boolean $_unused Whether to use an .asset.php file.
	 *
	 * @return self
	 */
	public function use_asset_file( bool $_unused = true ): self {
		return $this;
	}
}
