<?php
/**
 * This file contains the Sensei_Import_Questions class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Import_Questions class.
 *
 * @group data-port
 */
class Sensei_Import_Questions_Tests extends WP_UnitTestCase {
	/**
	 * Placeholder test.
	 */
	public function testClassExists() {
		$this->assertTrue( class_exists( 'Sensei_Import_Questions' ) );
	}
}
