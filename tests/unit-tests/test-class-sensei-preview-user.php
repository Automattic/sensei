<?php
/**
 * File with class for testing Sensei Preview User.
 *
 * @package sensei-tests
 */

/**
 * Class for testing Sensei_Preview_User class.
 *
 * @group Preview User
 */
class Sensei_Preview_User_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;

	/**
	 * Factory object.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory      = new Sensei_Factory();
		$this->preview_user = new Sensei_Preview_User();
	}

	/**
	 * Switch to preview user, then switch back.
	 */
	public function testSwitchToPreviewUser() {
		add_filter( 'wp_redirect', '__return_false' );

		$course_data = $this->factory->get_course_with_lessons();
		$course_link = get_permalink( $course_data['course_id'] );
		$admin_id    = $this->get_user_by_role( 'administrator' );
		$this->login_as( $admin_id );

		// Switch to preview user.

		$this->go_to( add_query_arg( [ 'sensei-preview-as-student' => wp_create_nonce( 'sensei-preview-as-student' ) ], $course_link ) );

		$this->go_to( $course_link );

		$this->assertNotEquals( $admin_id, get_current_user_id(), 'Current user not changed.' );
		$preview_user = wp_get_current_user();
		$this->assertRegexp( '/^Preview Student.*$/', $preview_user->display_name, 'Current user is not a preview student.' );

		// Switch off preview user.

		$this->go_to( add_query_arg( [ 'sensei-exit-student-preview' => wp_create_nonce( 'sensei-exit-student-preview' ) ], $course_link ) );

		wp_set_current_user( $admin_id );
		$this->go_to( $course_link );

		$this->assertEquals( $admin_id, get_current_user_id(), 'Current user is not changed back.' );
		$this->assertEmpty( get_user_by( 'ID', $preview_user->ID ), 'Preview user is not removed.' );

	}
}
