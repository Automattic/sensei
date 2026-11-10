<?php

/**
 * Tests for Sensei_Course_Results.
 *
 * @covers Sensei_Course_Results
 */
class Sensei_Course_Results_Test extends WP_UnitTestCase {

	/**
	 * Sensei factory.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	public function set_up(): void {
		parent::set_up();

		$this->factory = new Sensei_Factory();

		// go_to() creates a new WP instance, which drops the query var added by the rewrite tag.
		add_filter(
			'query_vars',
			function ( $query_vars ) {
				$query_vars[] = 'course_results';

				return $query_vars;
			}
		);
	}

	public function testRestrictToReadableCourses_DraftCourseRequestedByVisitor_Returns404() {
		$this->factory->course->create(
			array(
				'post_name'   => 'secret-course',
				'post_status' => 'draft',
			)
		);
		wp_set_current_user( 0 );

		$this->go_to( add_query_arg( 'course_results', 'secret-course', home_url( '/' ) ) );

		$this->assertTrue( is_404(), 'The request should be a 404.' );
		$this->assertFalse( Sensei_Utils::is_course_results_page(), 'The course results page should not load.' );
	}

	public function testRestrictToReadableCourses_PublishedCourseRequestedByVisitor_LoadsCourseResultsPage() {
		$this->factory->course->create(
			array(
				'post_name'   => 'open-course',
				'post_status' => 'publish',
			)
		);
		wp_set_current_user( 0 );

		$this->go_to( add_query_arg( 'course_results', 'open-course', home_url( '/' ) ) );

		$this->assertFalse( is_404(), 'The request should not be a 404.' );
		$this->assertTrue( Sensei_Utils::is_course_results_page(), 'The course results page should load.' );
	}

	public function testRestrictToReadableCourses_DraftCourseRequestedByItsTeacher_LoadsCourseResultsPage() {
		$teacher_id = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$this->factory->course->create(
			array(
				'post_author' => $teacher_id,
				'post_name'   => 'secret-course',
				'post_status' => 'draft',
			)
		);
		wp_set_current_user( $teacher_id );

		$this->go_to( add_query_arg( 'course_results', 'secret-course', home_url( '/' ) ) );

		$this->assertFalse( is_404(), 'The request should not be a 404.' );
		$this->assertTrue( Sensei_Utils::is_course_results_page(), 'The course results page should load.' );
	}
}
