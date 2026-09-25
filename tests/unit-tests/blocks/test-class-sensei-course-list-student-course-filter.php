<?php

/**
 * Tests for Sensei_Course_List_Student_Course_Filter class.
 *
 * @group course-structure
 */
class Sensei_Course_List_Student_Course_Filter_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Test_Helpers;
	use Sensei_Course_Enrolment_Manual_Test_Helpers;
	use Sensei_Test_Login_Helpers;

	/**
	 * Factory for setting up testing data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	public function set_up(): void {
		parent::set_up();
		$this->prepareEnrolmentManager();
		$this->factory = new Sensei_Factory();
	}

	public function tear_down(): void {
		$this->factory->tearDown();
		parent::tear_down();
	}

	public static function tear_down_after_class(): void {
		self::resetEnrolmentProviders();
		parent::tear_down_after_class();
	}

	public function testGetCourseIdsToBeExcluded_CourseHiddenFromFilteredQueries_LeavesItOut() {
		/* Arrange. */
		$student            = $this->factory->user->create();
		$hidden_course_id   = $this->factory->course->create();
		$active_course_id   = $this->factory->course->create();
		$inactive_course_id = $this->factory->course->create();

		$this->login_as( $student );
		$this->manuallyEnrolStudentInCourse( $student, $active_course_id );
		$this->hide_course_from_filtered_queries( $hidden_course_id );
		$_GET['course-list-student-course-filter-13'] = 'active';

		$filter = new Sensei_Course_List_Student_Course_Filter();

		/* Act. */
		$actual = $filter->get_course_ids_to_be_excluded( 13 );

		/* Assert. */
		$this->assertSame( array( $inactive_course_id ), array_values( $actual ) );
	}

	/**
	 * Hide a course from every post query that runs with filters, the way a
	 * multilingual plugin hides the courses of other languages.
	 *
	 * @param int $course_id Course ID.
	 */
	private function hide_course_from_filtered_queries( int $course_id ): void {
		add_filter(
			'posts_where',
			function ( $where ) use ( $course_id ) {
				global $wpdb;

				return $where . $wpdb->prepare( " AND {$wpdb->posts}.ID != %d", $course_id );
			}
		);
	}
}
