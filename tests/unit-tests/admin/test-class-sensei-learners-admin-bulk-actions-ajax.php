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
}
