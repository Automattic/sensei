<?php
/**
 * Report student listing integration coverage.
 *
 * @package sensei-tests
 */

/**
 * Verify student population in Reports exports.
 *
 * @covers Sensei_Analysis_Course_List_Table
 * @covers Sensei_Analysis_Lesson_List_Table
 * @covers Sensei_Analysis_User_Profile_List_Table
 */
class Sensei_Analysis_Student_Listings_Test extends WP_UnitTestCase {
	use Sensei_HPPS_Helpers;

	protected $factory;

	private $initial_hook_suffix;

	public function setUp(): void {
		parent::setUp();
		$this->factory             = new Sensei_Factory();
		$this->initial_hook_suffix = $GLOBALS['hook_suffix'] ?? null;
		$GLOBALS['hook_suffix']    = null;
	}

	public function tearDown(): void {
		$GLOBALS['hook_suffix'] = $this->initial_hook_suffix;
		$this->factory->tearDown();
		parent::tearDown();
	}

	/**
	 * Exports and pagination must use the same eligible students.
	 *
	 * @dataProvider student_report_provider
	 */
	public function testGenerateReport_StudentListingRequested_ExportsOnlyRegisteredStudents( string $view, string $search ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preserve the test request state.
		$original_get = $_GET;

		$this->maybe_enable_hpps_tables_repository();
		try {
			$created = $this->factory->get_course_with_lessons( array( 'lesson_count' => 1 ) );

			foreach ( array( 'registered_match', 'registered_other', 'sensei_guest_match', 'sensei_preview_match' ) as $login ) {
				$user = $this->factory->user->create(
					array(
						'user_login'   => $login,
						'display_name' => $login,
					)
				);
				Sensei()->course_progress_repository->create( $created['course_id'], $user );
				Sensei()->lesson_progress_repository->create( $created['lesson_ids'][0], $user );
			}

			$_GET['view'] = 'user';
			$_GET['s']    = $search;

			$table = 'course' === $view ? new Sensei_Analysis_Course_List_Table( $created['course_id'] ) : new Sensei_Analysis_Lesson_List_Table( $created['lesson_ids'][0] );

			$actual = $table->generate_report( 'students-overview' );

			$expected = '' === $search ? array( 'registered_match', 'registered_other' ) : array( 'registered_match' );
			$titles   = wp_list_pluck( array_slice( $actual, 1 ), 'title' );
			sort( $titles );

			$this->assertSame( $expected, $titles, 'CSV contains only matching registered students.' );
			$this->assertSame( count( $expected ), $table->total_items, 'Report total matches the eligible CSV rows.' );
		} finally {
			$_GET = $original_get;

			$this->maybe_reset_hpps_repository();
		}
	}

	/**
	 * Only registered students have courses in their Reports export.
	 *
	 * @dataProvider student_course_report_provider
	 *
	 * @param string $login           Student login.
	 * @param array  $expected_titles Expected course titles.
	 */
	public function testGenerateReport_StudentCoursesRequested_ExportsOnlyRegisteredStudentCourses( string $login, array $expected_titles ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preserve the test request state.
		$original_get = $_GET;

		$this->maybe_enable_hpps_tables_repository();
		try {
			$course_id = $this->factory->course->create( array( 'post_title' => 'Report course' ) );
			$user_id   = $this->factory->user->create( array( 'user_login' => $login ) );
			Sensei()->course_progress_repository->create( $course_id, $user_id );

			$_GET = array();

			$table = new Sensei_Analysis_User_Profile_List_Table( $user_id );

			$actual = $table->generate_report( 'student-courses' );

			$this->assertSame(
				array( 'Course', 'Date Started', 'Date Completed', 'Status', 'Percent Complete' ),
				$actual[0],
				'CSV should contain its column header.'
			);
			$this->assertSame( $expected_titles, wp_list_pluck( array_slice( $actual, 1 ), 'title' ), 'CSV should contain only eligible courses.' );
			$this->assertSame( count( $expected_titles ), $table->total_items, 'Pagination should match the eligible CSV rows.' );
		} finally {
			$_GET = $original_get;

			$this->maybe_reset_hpps_repository();
		}
	}

	public function student_report_provider(): array {
		return array(
			'course students' => array( 'course', '' ),
			'lesson students' => array( 'lesson', '' ),
			'course search'   => array( 'course', 'match' ),
			'lesson search'   => array( 'lesson', 'match' ),
		);
	}

	public function student_course_report_provider(): array {
		return array(
			'registered student' => array( 'registered_student', array( 'Report course' ) ),
			'guest student'      => array( 'sensei_guest_student', array() ),
			'preview student'    => array( 'sensei_preview_student', array() ),
		);
	}
}
