# StellarWP Assets

[![Tests](https://github.com/stellarwp/assets/workflows/Tests/badge.svg)](https://github.com/stellarwp/assets/actions?query=branch%3Amain) [![Static Analysis](https://github.com/stellarwp/assets/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/stellarwp/assets/actions/workflows/static-analysis.yml)

A library for managing asset registration and enqueuing in WordPress.

## Table of contents

* [Installation](#installation)
* [Notes on examples](#notes-on-examples)
* [Configuration](#configuration)

## Installation

It's recommended that you install Assets as a project dependency via [Composer](https://getcomposer.org/):

```bash
composer require stellarwp/assets
```

> We _actually_ recommend that this library gets included in your project using [Strauss](https://github.com/BrianHenryIE/strauss).
>
> Luckily, adding Strauss to your `composer.json` is only slightly more complicated than adding a typical dependency, so checkout our [strauss docs](https://github.com/stellarwp/global-docs/blob/main/docs/strauss-setup.md).

## Notes on examples

Since the recommendation is to use Strauss to prefix this library's namespaces, all examples will be using the `Boomshakalaka` namespace prefix.

## Configuration

This library requires some configuration before its features can be used. The configuration is done via the `Config` class.

```php
use Boomshakalaka\StellarWP\Assets\Config;

add_action( 'plugins_loaded', function() {
	Config::set_hook_prefix( 'boom-shakalaka' );
	Config::set_path( PATH_TO_YOUR_PROJECT_ROOT );
	Config::set_version( YOU_PROJECT::VERSION );

	// Optionally, set a relative asset path. It defaults to `src/assets/`.
	// This path is where your JS and CSS directories are stored.
	Config::set_relative_asset_path( 'src/assets/' );
} );
```

## Comprehensive JS example

```php
use Boomshakalaka\StellarWP\Assets\Asset;

Asset::register( 'my-asset', 'js/some-asset.js', $an_optional_version, $an_optional_path_to_project_root )
	->add_localize_script( // You can have more than one of these.
		'some_js_variable',
		[
			'color' => 'blue',
		]
	)
	->add_to_group( 'my-assets' ) // You can have more than one of these.
	->call_after_enqueue( // This can be any callable.
		static function() {
			// Do something after the asset is enqueued.
		}
	)
	->set_action( 'wp_enqueue_scripts' )
	->set_as_async( true )
	->set_as_deferred( true )
	->set_as_module( true )
	->set_condition( // This can be any callable.
		static function() {
			return is_front_page() || is_single();
		}
	)
	->set_dependencies( [ 'jquery' ] )
	->set_priority( 50 )
	->set_type( 'js' ); // Technically unneeded due to the .js extension.
```

## Comprehensive CSS example

```php
use Boomshakalaka\StellarWP\Assets\Asset;

Asset::register( 'my-asset', 'css/some-asset.css', $an_optional_version, $an_optional_path_to_project_root )
	->add_to_group( 'my-assets' ) // You can have more than one of these.
	->call_after_enqueue( // This can be any callable.
		static function() {
			// Do something after the asset is enqueued.
		}
	)
	->set_action( 'wp_enqueue_scripts' )
	->set_condition( // This can be any callable.
		static function() {
			return is_front_page() || is_single();
		}
	)
	->set_dependencies( [ 'some-css' ] )
	->set_media( 'screen' )
	->set_priority( 50 )
	->set_type( 'css' ); // Technically unneeded due to the .js extension.
```

## Registering an asset

There are a lot of options that are available for handling assets. Here is a comprehensive example. You can dig into the
details of each chainable method in the documentation below.

### Registering a JS file with a `.js` extension

The following example registers a JS file with the `.js` extension. Due to the presence of that extension, the file
will be identified and handled like a JS file.

```php
use Boomshakalaka\StellarWP\Assets\Asset;

Asset::register( 'my-script', 'js/my-script.js' );
```

### Registering a CSS file with a `.css` extension

The following example registers a CSS file with the `.css` extension. Due to the presence of that extension, the file
will be identified and handled like a CSS file.

```php
use Boomshakalaka\StellarWP\Assets\Asset;

Asset::register( 'my-style', 'css/my-style.css' );
```

### Registering a file or URL without an extension

Sometimes you need to register an asset that doesn't have an extension. In these cases, you need to let the library know
what type of asset it is. You can do this by adding the `set_type()` method to the chain.

```php
use Boomshakalaka\StellarWP\Assets\Asset;

// Register it as a JS file.
Asset::register( 'something', 'https://somewhere.com/a/resource/' )
	->set_type( 'js' );

// Register it as a CSS file.
Asset::register( 'something-else', 'https://somewhere.com/another/resource/' )
	->set_type( 'css' );
```

## Enqueuing an asset

To set an asset to enqueue during a specific action, you add `set_action()` to the chain, like so:

```php
use Boomshakalaka\StellarWP\Assets\Asset;

Asset::register( 'my-asset', 'css/my-style.css' )
	->set_action( 'wp_enqueue_scripts' );
```

## Setting dependencies

You can set dependencies for an asset by adding `set_dependencies()` to the chain. This method accepts an array of asset
handles.

```php
use Boomshakalaka\StellarWP\Assets\Asset;

Asset::register( 'my-asset', 'js/my-script.js' )
	->set_dependencies( [ 'jquery' ] );
```
