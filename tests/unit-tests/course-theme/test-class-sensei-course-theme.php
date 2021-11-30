<?php
/**
 * This file contains the Sensei_Course_Theme_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Course_Theme_Test class.
 *
 * @group course-theme
 */
class Sensei_Course_Theme_Test extends WP_UnitTestCase {
	/**
	 * Testing the Course Theme class to make sure it is loaded.
	 */
	public function testClassInstance() {
		$this->assertTrue( class_exists( 'Sensei_Course_Theme' ), 'Sensei Course Theme class should exist' );
	}
}
