<?php
/**
 * Sensei Onboarding tests
 *
 * @package sensei-lms
 * @since   3.1.0
 */

/**
 * Class for Sensei Onboarding API tests.
 */
class Sensei_Onboarding_Test extends WP_UnitTestCase {

	/**
	 * Testing the quiz class to make sure it is loaded
	 */
	public function testClassInstance() {
		$this->assertTrue( class_exists( 'Sensei_Onboarding' ), 'the Sensei Onboarding class is not loaded' );
	}


}
