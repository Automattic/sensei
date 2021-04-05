<?php
/**
 * This file contains the Sensei_Extensions_Test class.
 *
 * @package sensei
 */

/**
 * Tests for Sensei_Extensions class.
 */
class Sensei_Extensions_Test extends WP_UnitTestCase {

	/**
	 * Testing the Sensei Extensions class to make sure it is loaded.
	 */
	public function testClassInstance() {
		$this->assertTrue( class_exists( 'Sensei_Extensions' ), 'Sensei Extensions class does not exist' );
	}
}
