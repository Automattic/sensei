<?php
/**
 * This file contains the Sensei_Tool_Remove_Deleted_User_Data_Tests class.
 *
 * @package sensei
 */

/**
 * Tests for Sensei_Tool_Remove_Deleted_User_Data class.
 *
 * @group tools
 */
class Sensei_Tool_Remove_Deleted_User_Data_Tests extends WP_UnitTestCase {
	/**
	 * Factory object.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Tests to make sure deleted users progress is removed and current users progress is preserved.
	 */
	public function testRunKeepsData() {
		global $wpdb;

		$user_id_a = $this->factory->user->create();
		$user_id_b = $this->factory->user->create();
		$course_id = $this->factory->course->create();

		$user_a_status_comment_id = Sensei_Utils::update_course_status( $user_id_a, $course_id );
		$user_b_status_comment_id = Sensei_Utils::update_course_status( $user_id_b, $course_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $wpdb->users, [ 'ID' => $user_id_a ] );

		$tool = new Sensei_Tool_Remove_Deleted_User_Data();
		$tool->process();

		$this->assertNull( get_comment( $user_a_status_comment_id ) );
		$this->assertTrue( get_comment( $user_b_status_comment_id ) instanceof WP_Comment );
	}
}
