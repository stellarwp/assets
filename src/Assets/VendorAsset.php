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
	 * @param bool $use_min_if_available (Unused) Use the minified version of the asset if available.
	 *
	 * @return string
	 * @throws LogicException If the URL has a placeholder but no version is provided.
	 */
	public function get_url( bool $use_min_if_available = true ): string {
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
}
