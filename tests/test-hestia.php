<?php
/**
 * Class SampleTest
 *
 * @package Hestia
 */

/**
 * Sample test case.
 */
class TestHestia extends WP_UnitTestCase {
	/**
	 * Test Constants.
	 */
	public function testConstants() {
		$this->assertTrue( defined( 'HESTIA_VERSION' ) );
		$this->assertTrue( defined( 'HESTIA_ADDONS_URI' ) );
		$this->assertTrue( defined( 'HESTIA_CORE_DIR' ) );
		$this->assertTrue( defined( 'HESTIA_PHP_INCLUDE' ) );
		$this->assertTrue( defined( 'HESTIA_VENDOR_VERSION' ) );
	}

	/**
	 * Make sure HESTIA_DEBUG is false.
	 */
	public function testDebugOff() {
		$this->assertFalse( HESTIA_DEBUG );
	}
}
