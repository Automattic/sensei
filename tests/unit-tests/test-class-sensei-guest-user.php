<?php
/**
 * File with class for testing Sensei Guest User.
 *
 * @package sensei-tests
 */

/**
 * Class for testing Sensei_Guest_Users class.
 *
 * @group Guest User
 */
class Sensei_Guest_User_Test extends WP_UnitTestCase {
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

		$this->factory    = new Sensei_Factory();
		$this->guest_user = new Sensei_Guest_User();
	}

	private function setup_course( $open_access ) {
		$course_data = $this->factory->get_course_with_lessons();
		$course_id   = $course_data['course_id'];
		update_post_meta( $course_id, 'open_access', $open_access );
		global $post;
		$post = get_post( $course_id );

		return $course_data;
	}

	/**
	 * Test if guest is created for take course requests when course is open access.
	 *
	 * @testWith [ true ]
	 *           [ false ]
	 *
	 * @param bool $open_access
	 */
	public function testGuestUserCreatedOnTakeCourseRequest( $open_access ) {

		$this->setup_course( $open_access );
		$this->logout();

		$_POST['course_start']                         = 1;
		$_POST['woothemes_sensei_start_course_noonce'] = wp_create_nonce( 'woothemes_sensei_start_course_noonce' );

		do_action( 'wp' );

		$this->assertEquals( is_user_logged_in(), $open_access );
		if ( $open_access ) {
			$this->assertRegexp( '/^guest_user_.*$/', wp_get_current_user()->user_login );
			$this->assertRegexp( '/^Guest Student 00.*$/', wp_get_current_user()->display_name );
		}

	}

	/**
	 * Test that a guest user is created, enrolled and completes lesson when clicking complete lesson.
	 */
	public function testGuestUserEnrolledOnCompleteLessonRequest() {

		[ 'course_id' => $course_id, 'lesson_ids' => [ $lesson_id ] ] = $this->setup_course( true );
		$this->logout();

		global $post;
		$post = get_post( $lesson_id );

		$_POST['quiz_action']                             = 'lesson-complete';
		$_POST['woothemes_sensei_complete_lesson_noonce'] = wp_create_nonce( 'woothemes_sensei_complete_lesson_noonce' );

		do_action( 'wp' );

		// Guest user is created and enrolled.
		$this->assertTrue( is_user_logged_in(), 'Guest user was not created' );
		$this->assertTrue( Sensei_Course::is_user_enrolled( $course_id, get_current_user_id() ), 'Guest user was not enrolled' );

		// The 'complete lesson' action is also executed.
		$this->assertTrue( Sensei_Utils::user_completed_lesson( $lesson_id, get_current_user_id() ), 'Lesson was not completed' );

	}

	public function testRolesList_WhenFetched_DoesNotContainGuestStudentRole() {
		/* Arrange */
		$this->factory->user->create( [ 'role' => 'guest_student' ] );

		$all_roles = get_editable_roles();

		Sensei_Guest_User::init_guest_user_admin();

		/* Act */
		$all_roles_except_guest = get_editable_roles();

		/* Assert */
		$this->assertArrayHasKey( 'guest_student', $all_roles );
		$this->assertArrayNotHasKey( 'guest_student', $all_roles_except_guest );
	}

	public function testUserList_WhenFetched_DoesNotContainGuestUsers() {
		/* Arrange */
		$user_args = [
			'fields' => 'ID',
		];
		$result1   = ( new WP_User_Query( $user_args ) )->get_results();

		$this->factory->user->create_many( 2, [ 'role' => 'guest_student' ] );
		Sensei_Guest_User::init_guest_user_admin();

		/* Act */
		$result2 = ( new WP_User_Query( $user_args ) )->get_results();

		/* Assert */
		$this->assertEquals( count( $result1 ), count( $result2 ) );
	}

}
