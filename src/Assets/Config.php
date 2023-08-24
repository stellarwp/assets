<?php

namespace StellarWP\Assets;

use RuntimeException;

class Config {
	/**
	 * @var string
	 */
	protected static string $hook_prefix = '';

	/**
	 * @var string
	 */
	protected static string $relative_asset_path = 'src/assets/';

	/**
	 * @var string
	 */
	protected static string $root_path = '';

	/**
	 * @var string
	 */
	protected static string $version = '';

	/**
	 * Gets the hook prefix.
	 *
	 * @return string
	 */
	public static function get_hook_prefix(): string {
		if ( static::$hook_prefix === '' ) {
			$class = __CLASS__;
			throw new RuntimeException( "You must specify a hook prefix for your project with {$class}::set_hook_prefix()" );
		}
		return static::$hook_prefix;
	}

	/**
	 * Gets the root path of the project.
	 *
	 * @return string
	 */
	public static function get_path(): string {
		if ( static::$root_path === '' ) {
			$class = __CLASS__;
			throw new RuntimeException( "You must specify a path to the root of you project with {$class}::set_path()" );
		}
		return static::$root_path;
	}

	/**
	 * Gets the relative asset path of the project.
	 *
	 * @return string
	 */
	public static function get_relative_asset_path(): string {
		return static::$relative_asset_path;
	}

	/**
	 * Gets the version of the project.
	 *
	 * @return string
	 */
	public static function get_version(): string {
		return static::$version;
	}

	/**
	 * Resets this class back to the defaults.
	 */
	public static function reset() {
		static::$hook_prefix         = '';
		static::$relative_asset_path = 'src/assets/';
		static::$root_path           = '';
		static::$version             = '';
	}

	/**
	 * Sets the hook prefix.
	 *
	 * @param string $prefix The prefix to add to hooks.
	 *
	 * @return void
	 */
	public static function set_hook_prefix( string $prefix ) {
		static::$hook_prefix = $prefix;
	}

	/**
	 * Sets the relative asset path of the project.
	 *
	 * @param string $path The root path of the project.
	 *
	 * @return void
	 */
	public static function set_relative_asset_path( string $path ) {
		static::$relative_asset_path = trailingslashit( $path );
	}

	/**
	 * Sets the root path of the project.
	 *
	 * @param string $path The root path of the project.
	 *
	 * @return void
	 */
	public static function set_path( string $path ) {
		static::$root_path = trailingslashit( $path );
	}

	/**
	 * Sets the version of the project.
	 *
	 * @param string $version The version of the project.
	 *
	 * @return void
	 */
	public static function set_version( string $version ) {
		static::$version = $version;
	}
}
