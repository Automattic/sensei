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
		$user_id = Sensei_Data_Port_Utilities::create_or_update_user( 'testuser', 'testemail@test.com' );

		$this->assertEquals( $user_id, get_user_by( 'login', 'testuser' )->ID );
	}

	public function testUserIsUpdatedIfExists() {
		$user_id = $this->factory->user->create(
			[
				'user_login' => 'testuser',
				'user_email' => 'testemail@test.com',
			]
		);

		Sensei_Data_Port_Utilities::create_or_update_user( 'testuser', 'updated@test.com' );

		$updated_user = get_user_by( 'ID', $user_id );
		$this->assertEquals( 'testuser', $updated_user->user_login );
		$this->assertEquals( 'updated@test.com', $updated_user->user_email );
	}
}
