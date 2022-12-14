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
		add_filter( 'wp_redirect', [ $this, 'go_to' ] );
	}

	/**
	 * Clean up after the test.
	 */
	public function tearDown() {
		parent::tearDown();
		remove_filter( 'wp_redirect', [ $this, 'go_to' ] );
	}

	/**
	 * Switch to preview user, then switch back.
	 */
	public function testSwitchToPreviewUser() {

		$course_id   = $this->factory->course->create();
		$course_link = get_permalink( $course_id );
		$admin_id    = $this->get_user_by_role( 'administrator' );
		$this->login_as( $admin_id );

		// Switch to preview user.

		$this->go_to( add_query_arg( [ 'sensei-preview-as-student' => wp_create_nonce( 'sensei-preview-as-student' ) ], $course_link ) );

		$this->assertNotEquals( $admin_id, get_current_user_id(), 'Current user not changed.' );
		$preview_user = wp_get_current_user();
		$this->assertRegexp( '/^Preview Student.*$/', $preview_user->display_name, 'Current user is not a preview student.' );

		// Switch off preview user.

		$this->go_to( add_query_arg( [ 'sensei-exit-student-preview' => wp_create_nonce( 'sensei-exit-student-preview' ) ], $course_link ) );

		$this->assertEquals( $admin_id, get_current_user_id(), 'Current user is not changed back.' );
		$this->assertEmpty( get_user_by( 'ID', $preview_user->ID ), 'Preview user is not removed.' );

	}

	/**
	 * Courses should have independent preview users.
	 */
	public function testPreviewUserPerCourse() {

		$course_1 = $this->factory->course->create();
		$course_2 = $this->factory->course->create();
		$admin_id = $this->get_user_by_role( 'administrator' );
		$this->login_as( $admin_id );

		// Switch to preview user for course 1.

		$this->go_to( add_query_arg( [ 'sensei-preview-as-student' => wp_create_nonce( 'sensei-preview-as-student' ) ], get_permalink( $course_1 ) ) );

		$preview_user_1 = get_current_user_id();
		wp_set_current_user( $admin_id );
		$this->go_to( get_permalink( $course_2 ) );

		$this->assertNotEquals( $preview_user_1, get_current_user_id(), 'Course 2 should not use course 1\'s preview user.' );

		// Switch to preview user for course 2.

		$this->go_to( add_query_arg( [ 'sensei-preview-as-student' => wp_create_nonce( 'sensei-preview-as-student' ) ], get_permalink( $course_2 ) ) );

		$preview_user_2 = get_current_user_id();
		wp_set_current_user( $admin_id );
		$this->go_to( get_permalink( $course_2 ) );

		$this->assertEquals( $preview_user_2, get_current_user_id(), 'Course 2 should use its own preview user.' );

		wp_set_current_user( $admin_id );
		$this->go_to( get_permalink( $course_1 ) );

		$this->assertNotEquals( $preview_user_2, get_current_user_id(), 'Course 1 should not use course 2\'s preview user.' );

	}


	/**
	 * Switch to preview user, then switch back.
	 */
	public function testPreviewUserForLessons() {

		list( 'course_id' => $course_id, 'lesson_ids' => list( $lesson_id ) ) = $this->factory->get_course_with_lessons();
		$admin_id = $this->get_user_by_role( 'administrator' );
		$this->login_as( $admin_id );

		// Switch to preview user.

		$this->go_to( add_query_arg( [ 'sensei-preview-as-student' => wp_create_nonce( 'sensei-preview-as-student' ) ], get_permalink( $course_id ) ) );

		$this->go_to( get_permalink( $lesson_id ) );

		$this->assertNotEquals( $admin_id, get_current_user_id(), 'Preview user is not used for the lesson.' );

	}

	/**
	 * Reset user ID when navigating to a new page.
	 *
	 * Normally when the tested class changes the user, it should be for the current request only. It is however persisted in the test environment, so we need to set it back to the original user (admin) when using go_to() or on redirects.
	 *
	 * @param string $url URL.
	 */
	public function go_to( $url ) {
		wp_set_current_user( $this->get_user_by_role( 'administrator' ) );
		parent::go_to( $url );
	}

}
