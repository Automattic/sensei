<?php
/**
 * This file contains the Sensei_Import_Courses class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Import_Courses class.
 *
 * @group data-port
 */
class Sensei_Import_Courses_Tests extends WP_UnitTestCase {
	/**
	 * Set up before class.
	 */
	public static function setUpBeforeClass() {
		new Sensei_Import_Job( '' );
	}

	/**
	 * Placeholder test.
	 */
	public function testClassExists() {
		$this->assertTrue( class_exists( 'Sensei_Import_Courses' ) );
	}
}
