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

		$this->assertIsObject( $response, 'The AJAX response should be a JSON object.' );
		$this->assertTrue( $response->success, 'The request should succeed.' );
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

		$this->assertIsObject( $response, 'The AJAX response should be a JSON object.' );
		$this->assertTrue( $response->success, 'The request should succeed.' );
		// Five courses enrolled, first three are shown in the table, so two remain for the "More" call.
		$this->assertCount( 2, $response->data, 'Two courses should remain for the "More" call.' );

		foreach ( $response->data as $item ) {
			$this->assertStringContainsString( 'Teacher Own Course', $item, 'Each returned course should belong to the teacher.' );
		}
	}

	/**
	 * A user without the manage_sensei_grades capability is denied.
	 */
	public function testGetCourseList_WhenUserLacksManageGradesCap_ReturnsError() {
		$this->factory = new Sensei_Factory();

		$subscriber = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$student    = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		$_POST['nonce']   = wp_create_nonce( 'get_course_list' );
		$_POST['user_id'] = $student;

		try {
			$this->_handleAjax( 'get_course_list' );
		} catch ( \WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		$response = json_decode( $this->_last_response );

		$this->assertIsObject( $response, 'The AJAX response should be a JSON object.' );
		$this->assertFalse( $response->success, 'A user without manage_sensei_grades must be denied.' );
	}

	/**
	 * With a mixed enrolment, unmanageable courses are dropped before the first three are sliced off.
	 *
	 * Other teachers' courses are interleaved (including the newest), so a slice-before-filter approach
	 * would skip the wrong courses or duplicate visible ones. The "More" call must return the current
	 * teacher's courses that come after their own first three manageable courses.
	 */
	public function testGetCourseList_WithMixedEnrolment_ReturnsManageableAfterTheirOwnFirstThree() {
		$this->factory = new Sensei_Factory();
		Sensei()->teacher->create_role();

		$teacher_a = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$teacher_b = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$student   = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$provider  = $this->getManualEnrolmentProvider();

		// Each row is [ author, title, date ]. Newest first by date; Teacher B courses are interleaved.
		// After filtering to Teacher A's courses, the first three are "Visible" and the rest are "More".
		$courses = array(
			array( $teacher_b, 'Teacher B Hidden 1', '2026-01-07 00:00:00' ),
			array( $teacher_a, 'Teacher A Visible 1', '2026-01-06 00:00:00' ),
			array( $teacher_a, 'Teacher A Visible 2', '2026-01-05 00:00:00' ),
			array( $teacher_b, 'Teacher B Hidden 2', '2026-01-04 00:00:00' ),
			array( $teacher_a, 'Teacher A Visible 3', '2026-01-03 00:00:00' ),
			array( $teacher_a, 'Teacher A More 1', '2026-01-02 00:00:00' ),
			array( $teacher_a, 'Teacher A More 2', '2026-01-01 00:00:00' ),
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

		$this->assertIsObject( $response, 'The AJAX response should be a JSON object.' );
		$this->assertTrue( $response->success, 'The request should succeed.' );

		$body = implode( "\n", $response->data );

		// Teacher A's two courses after their own first three; the visible three and all Teacher B courses are excluded.
		$this->assertCount( 2, $response->data, 'Only the manageable courses after the first three should be returned.' );
		$this->assertStringContainsString( 'Teacher A More 1', $body, 'Manageable course after the first three should be present.' );
		$this->assertStringContainsString( 'Teacher A More 2', $body, 'Manageable course after the first three should be present.' );
		$this->assertStringNotContainsString( 'Teacher A Visible', $body, 'The first three manageable courses are shown in the table, not the "More" call.' );
		$this->assertStringNotContainsString( 'Teacher B', $body, 'No Teacher B course should leak.' );
	}
}
