<?php

/**
 * Sensei Reports Overview List Table Lessons Test Class
 *
 * @covers Sensei_Reports_Overview_List_Table_Lessons
 */
class Sensei_Reports_Overview_List_Table_Lessons_Test extends WP_UnitTestCase {
	/**
	 * Factory for setting up testing data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		$this->factory->tearDown();
	}

	/**
	 * Tests that we are getting right data for Completion Rate column.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Lessons::generate_report
	 * @dataProvider lessonCompletionRateData
	 * @test
	 */
	public function testGenerateReport_StudentEnrollmentsFound_ReturnsRowsWithMatchingCompletionRate( $enrolled_student_count, $completed_student_count, $expected_output ) {
		/* Arrange */
		$data_provider_instance = new Sensei_Reports_Overview_Data_Provider_Lessons( Sensei()->course );
		$lesson_table_instance  = new Sensei_Reports_Overview_List_Table_Lessons( Sensei()->course, $data_provider_instance );
		$user_ids               = $this->factory->user->create_many( $enrolled_student_count );
		$course_lessons         = $this->factory->get_course_with_lessons(
			array(
				'lesson_count' => 1,
			)
		);
		$lesson_id              = array_pop( $course_lessons['lesson_ids'] );
		foreach ( $user_ids as $key => $user_id ) {
			Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id, $key < $completed_student_count );
		}
		$_GET['course_filter'] = $course_lessons['course_id'];

		/* Act */
		$row_data = $lesson_table_instance->generate_report();

		/* Assert */
		$this->assertEquals( $row_data[1]['completion_rate'], $expected_output, "The calculated 'Completion Rate' is not correct" );
	}
	/**
	 * Returns an associative array with parameters needed to run lesson completion test.
	 *
	 * @return array
	 */
	public function lessonCompletionRateData(): array {
		return [
			'100%' => [ 5, 5, '100%' ],
			'80%'  => [ 5, 4, '80%' ],
			'67%'  => [ 3, 2, '67%' ],
			'N/A'  => [ 0, 0, 'N/A' ],
			'0%'   => [ 1, 0, '0%' ],
		];
	}
	/**
	 * Tests that we are getting the correct value for totals in column headers for lesson table.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Lessons::get_columns
	 */
	public function testGetColumns_WhenCalled_ReturnsColumnsWithMatchingTotals() {
		/* Arrange */
		$user_ids   = $this->factory->user->create_many( 3 );
		$course_id  = $this->factory->course->create();
		$lesson_ids = $this->factory->lesson->create_many(
			3,
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$days_count = 7;

		$data_provider_instance = new Sensei_Reports_Overview_Data_Provider_Lessons( Sensei()->course );
		$lesson_table_instance  = new Sensei_Reports_Overview_List_Table_Lessons( Sensei()->course, $data_provider_instance );

		$_GET['course_filter'] = $course_id;
		// Complete a lesson for each student on a different date.
		foreach ( $user_ids as $user_id ) {
			$lesson_activity_comment_id = Sensei_Utils::sensei_start_lesson( $lesson_ids[0], $user_id, true );
			wp_update_comment(
				[
					'comment_ID'   => $lesson_activity_comment_id,
					'comment_date' => gmdate( 'Y-m-d H:i:s', strtotime( ( $days_count * 24 ) . ' hours' ) ),
				]
			);
			$days_count++;
		}

		/* ACT */
		$actual = $lesson_table_instance->get_columns();

		/* Assert */
		$expected = [
			'title'              => 'Lesson (3)',
			'students'           => 'Students (3)',
			'last_activity'      => 'Last Activity',
			'completions'        => 'Completed (1)',
			'completion_rate'    => 'Completion Rate (100%)',
			'days_to_completion' => 'Days to Completion (9)',
		];
		self::assertSame( $expected, $actual, 'The expected column headers for lessons table in overview report does not match the actual output' );
	}
}
