<?php
/**
 * This file contains the Sensei_Data_Port_Utilities_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Data_Port_Utilities class.
 *
 * @group data-port
 */
class Sensei_Data_Port_Utilities_Test extends WP_UnitTestCase {


	public function testUserIsCreatedIfDoesNotExist() {
		$user_id = Sensei_Data_Port_Utilities::create_user( 'testuser', 'testemail@test.com' );

		$this->assertEquals( $user_id, get_user_by( 'login', 'testuser' )->ID );
	}
}
