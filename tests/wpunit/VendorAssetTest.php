<?php

declare( strict_types=1 );

namespace wpunit;

use InvalidArgumentException;
use LogicException;
use StellarWP\Assets\Config;
use StellarWP\Assets\Tests\AssetTestCase;
use StellarWP\Assets\VendorAsset;

class VendorAssetTest extends AssetTestCase {

	/**
	 * @before
	 */
	public function before_tests(): void {
		Config::reset();
		Config::set_hook_prefix( 'jpry' );
	}

	public function test_can_create_vendor_asset(): void {
		$asset = new VendorAsset( 'test-script', 'https://example.com/fake.js' );

		$this->assertEquals( 'test-script', $asset->get_slug() );
		$this->assertEquals( 'https://example.com/fake.js', $asset->get_url() );
		$this->assertEquals( 'js', $asset->get_type() );
	}

	public function test_invalid_url_throws_exception(): void {
		$this->expectException( InvalidArgumentException::class );

		new VendorAsset( 'test-script', 'invalid-url' );
	}

	public function test_can_set_version(): void {
		$asset = new VendorAsset( 'test-script', 'https://example.com/fake.js' );
		$asset->set_version( '1.0.0' );

		$this->assertEquals( '1.0.0', $asset->get_version() );
		$this->assertEquals( 'https://example.com/fake.js?ver=1.0.0', $asset->get_url() );
	}

	public function test_can_set_version_with_url_placeholder(): void {
		$asset = new VendorAsset( 'test-script', 'https://example.com/path/to/version/%s/fake.js' );
		$asset->set_version( '1.2.3' );
		$this->assertEquals( '1.2.3', $asset->get_version() );
		$this->assertEquals( 'https://example.com/path/to/version/1.2.3/fake.js', $asset->get_url() );
	}

	public function test_placeholder_without_version_throws_error(): void {
		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'A URL with a placeholder must have a version provided.' );

		$asset = new VendorAsset( 'test-script', 'https://example.com/path/to/version/%s/fake.js' );
		$asset->get_url();
	}

	public function test_version_with_query_string_has_ver_appended_correctly(): void {
		$asset = new VendorAsset( 'test-script', 'https://example.com/path/to/version?query=string' );
		$asset->set_version( '1.2.3' );

		$this->assertEquals( '1.2.3', $asset->get_version() );
		$this->assertEquals( 'https://example.com/path/to/version?query=string&ver=1.2.3', $asset->get_url() );
	}
}
