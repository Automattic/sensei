<?php
/**
 * This file contains the Sensei_Course_Navigation_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Course_Navigation_Test class.
 *
 * @group course-navigation
 */
class Sensei_Course_Navigation_Test extends WP_UnitTestCase {
	/**
	 * Testing the Course Navigation class to make sure it is loaded.
	 */
	public function testClassInstance() {
		$this->assertTrue( class_exists( 'Sensei_Course_Navigation' ), 'Sensei Course Navigation class should exist' );
	}
}
