<?php

namespace StellarWP\Assets;

class Config {
	/**
	 * @var string
	 */
	protected static $hook_prefix = '';

	/**
	 * @var string
	 */
	protected static $root_path = '';

	/**
	 * @var string
	 */
	protected static $version = '';

	/**
	 * Gets the hook prefix.
	 *
	 * @return string
	 */
	public static function get_hook_prefix(): string {
		return static::$hook_prefix;
	}

	/**
	 * Gets the root path of the project.
	 *
	 * @return string
	 */
	public static function get_path(): string {
		return static::$root_path;
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
		static::$hook_prefix = '';
		static::$root_path   = '';
		static::$version     = '';
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
	 * Sets the root path of the project.
	 *
	 * @param string $path The root path of the project.
	 *
	 * @return void
	 */
	public static function set_path( string $path ) {
		static::$root_path = $path;
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
