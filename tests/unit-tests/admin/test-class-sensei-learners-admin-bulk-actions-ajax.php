<?php
/**
 * AJAX tests for Sensei.
 *
 * @group ajax-calls
 */
class Sensei_Learners_Admin_Bulk_Actions_View_AJAX_Test extends WP_Ajax_UnitTestCase {
	use Sensei_Course_Enrolment_Test_Helpers;

	/**
	 * Gets the manual enrolment manager.
	 *
	 * @return false|Sensei_Course_Manual_Enrolment_Provider
	 * @throws Exception
	 */
	private function getManualEnrolmentProvider() {
		return Sensei_Course_Enrolment_Manager::instance()->get_manual_enrolment_provider();
	}

	/**
	 * Test the functionality of displaying additional courses from the Students page "More" button using the get_course_list action.
	 */
	public function testSingleRow_ItemGiven_ReturnsMatchingCourses() {
		$this->factory = new Sensei_Factory();

		// Generate 12 courses
		$this->factory->generate_many_courses( 12 );
		$courses = $this->factory->get_courses();

		// Generate 2 Students
		$users    = $this->factory->user->create_many( 2, array( 'role' => 'administrator' ) );
		$provider = $this->getManualEnrolmentProvider();

		// Enroll users into courses
		foreach ( $users as $user ) {
			foreach ( $courses as $course ) {
				$provider->enrol_learner( $user, $course );
			}
		}

		$this->_setRole( 'administrator' );
		$_POST['nonce']   = wp_create_nonce( 'get_course_list' );
		$_POST['user_id'] = $users[0];

		try {
			$this->_handleAjax( 'get_course_list' );
		} catch ( \WPAjaxDieContinueException $e ) {
			unset( $e );
		}
		$response = json_decode( $this->_last_response );

		$this->assertIsObject( $response );
		$this->assertObjectHasProperty( 'success', $response );
		$this->assertTrue( $response->success );
		$this->assertCount( 9, $response->data );

		foreach ( $response->data as $item ) {
			$this->assertStringContainsString( 'Course title', $item );
		}
	}

	/**
	 * The get_course_list action only returns courses the current user can manage.
	 */
	public function testGetCourseList_ExcludesCoursesTheUserCannotManage() {
		$this->factory = new Sensei_Factory();
		Sensei()->teacher->create_role();

		$teacher_a = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$teacher_b = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$student   = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$provider  = $this->getManualEnrolmentProvider();

		// Enroll the student in five courses owned by teacher B.
		for ( $i = 1; $i <= 5; $i++ ) {
			$course_id = $this->factory->course->create(
				array(
					'post_author' => $teacher_b,
					'post_title'  => 'Teacher B Hidden Course ' . $i,
				)
			);
			$provider->enrol_learner( $student, $course_id );
		}

		wp_set_current_user( $teacher_a );

		$_POST['nonce']   = wp_create_nonce( 'get_course_list' );
		$_POST['user_id'] = $student;

		try {
			$this->_handleAjax( 'get_course_list' );
		} catch ( \WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		$response = json_decode( $this->_last_response );

		$this->assertIsObject( $response );
		$this->assertTrue( $response->success );
		$this->assertEmpty( $response->data, 'Teacher A must not receive Teacher B courses.' );
	}

	/**
	 * A teacher should still receive their own enrolled courses beyond the third one.
	 */
	public function testGetCourseList_AsTeacherForOwnCourses_ReturnsThem() {
		$this->factory = new Sensei_Factory();
		Sensei()->teacher->create_role();

		$teacher  = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$student  = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$provider = $this->getManualEnrolmentProvider();

		// Enroll the student in five courses owned by the teacher.
		for ( $i = 1; $i <= 5; $i++ ) {
			$course_id = $this->factory->course->create(
				array(
					'post_author' => $teacher,
					'post_title'  => 'Teacher Own Course ' . $i,
				)
			);
			$provider->enrol_learner( $student, $course_id );
		}

		wp_set_current_user( $teacher );

		$_POST['nonce']   = wp_create_nonce( 'get_course_list' );
		$_POST['user_id'] = $student;

		try {
			$this->_handleAjax( 'get_course_list' );
		} catch ( \WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		$response = json_decode( $this->_last_response );

		$this->assertIsObject( $response );
		$this->assertTrue( $response->success );
		// Five courses enrolled, first three are shown in the table, so two remain for the "More" call.
		$this->assertCount( 2, $response->data );

		foreach ( $response->data as $item ) {
			$this->assertStringContainsString( 'Teacher Own Course', $item );
		}
	}

	/**
	 * A non-numeric user_id is rejected before any learner query runs.
	 */
	public function testGetCourseList_WhenUserIdNotNumeric_ReturnsError() {
		$this->factory = new Sensei_Factory();

		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$_POST['nonce']   = wp_create_nonce( 'get_course_list' );
		$_POST['user_id'] = 'foo';

		try {
			$this->_handleAjax( 'get_course_list' );
		} catch ( \WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		$response = json_decode( $this->_last_response );

		$this->assertIsObject( $response );
		$this->assertFalse( $response->success, 'A non-numeric user_id must not be queried.' );
	}

	/**
	 * With a mixed enrolment, only the manageable courses after the first three visible ones are returned.
	 *
	 * The query orders by date DESC, so the three newest (unmanageable) courses occupy the visible
	 * slots, and the "More" call must return only the current teacher's courses from the remainder.
	 */
	public function testGetCourseList_WithMixedEnrolment_ReturnsOnlyManageableAfterTheVisibleThree() {
		$this->factory = new Sensei_Factory();
		Sensei()->teacher->create_role();

		$teacher_a = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$teacher_b = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$student   = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$provider  = $this->getManualEnrolmentProvider();

		// Each row is [ author, title, date ]. Newest first by date: three Teacher B courses fill the
		// visible three, then the remainder is a mix of Teacher A and Teacher B courses.
		$courses = array(
			array( $teacher_b, 'Teacher B Visible 1', '2026-01-06 00:00:00' ),
			array( $teacher_b, 'Teacher B Visible 2', '2026-01-05 00:00:00' ),
			array( $teacher_b, 'Teacher B Visible 3', '2026-01-04 00:00:00' ),
			array( $teacher_a, 'Teacher A Remaining 1', '2026-01-03 00:00:00' ),
			array( $teacher_b, 'Teacher B Remaining', '2026-01-02 00:00:00' ),
			array( $teacher_a, 'Teacher A Remaining 2', '2026-01-01 00:00:00' ),
		);
		foreach ( $courses as $course ) {
			$course_id = $this->factory->course->create(
				array(
					'post_author' => $course[0],
					'post_title'  => $course[1],
					'post_date'   => $course[2],
				)
			);
			$provider->enrol_learner( $student, $course_id );
		}

		wp_set_current_user( $teacher_a );

		$_POST['nonce']   = wp_create_nonce( 'get_course_list' );
		$_POST['user_id'] = $student;

		try {
			$this->_handleAjax( 'get_course_list' );
		} catch ( \WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		$response = json_decode( $this->_last_response );
		$body     = implode( "\n", $response->data );

		$this->assertIsObject( $response );
		$this->assertTrue( $response->success );
		// Only Teacher A's two courses from the remainder; the visible three and the unmanageable remainder are excluded.
		$this->assertCount( 2, $response->data, 'Only the manageable remaining courses should be returned.' );
		$this->assertStringContainsString( 'Teacher A Remaining 1', $body, 'Manageable remaining course should be present.' );
		$this->assertStringContainsString( 'Teacher A Remaining 2', $body, 'Manageable remaining course should be present.' );
		$this->assertStringNotContainsString( 'Teacher B', $body, 'No Teacher B course should leak.' );
	}
}
