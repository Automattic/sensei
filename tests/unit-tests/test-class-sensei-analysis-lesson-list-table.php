<?php

/**
 * Sensei Analysis Lesson List Table Unit Test.
 *
 * @covers Sensei_Analysis_Lesson_List_Table
 */
class Sensei_Analysis_Lesson_List_Table_Test extends WP_UnitTestCase {
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

		$this->factory = new Sensei_Factory();
	}

	/**
	 * The table keeps the course of its lesson for the student links.
	 *
	 * @covers Sensei_Analysis_Lesson_List_Table::__construct
	 */
	public function testConstruct_WhenLessonHadCourse_SetsCourseId() {
		/* Arrange. */
		$course = $this->factory->get_course_with_lessons( array( 'question_count' => 0 ) );

		/* Act. */
		$table = new Sensei_Analysis_Lesson_List_Table( $course['lesson_ids'][0] );

		/* Assert. */
		self::assertSame( $course['course_id'], $table->course_id );
	}

	/**
	 * The table keeps a zero course ID for a lesson without a course, so the student links still carry it.
	 *
	 * @covers Sensei_Analysis_Lesson_List_Table::__construct
	 */
	public function testConstruct_WhenLessonHadNoCourse_SetsZeroCourseId() {
		/* Arrange. */
		$lesson_id = $this->factory->lesson->create();

		/* Act. */
		$table = new Sensei_Analysis_Lesson_List_Table( $lesson_id );

		/* Assert. */
		self::assertSame( 0, $table->course_id );
	}

	public function testGenerateReport_StudentsByLesson_ReturnsCorrectNumberOfRows() {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course' => $course_id,
				],
			]
		);

		$user1_id = $this->factory->user->create();
		$user2_id = $this->factory->user->create();
		$user3_id = $this->factory->user->create();

		Sensei_Utils::start_user_on_course( $user1_id, $course_id );
		Sensei_Utils::start_user_on_course( $user2_id, $course_id );
		Sensei_Utils::start_user_on_course( $user3_id, $course_id );

		Sensei_Utils::user_start_lesson( $user1_id, $lesson_id );
		Sensei_Utils::user_start_lesson( $user2_id, $lesson_id );
		Sensei_Utils::user_start_lesson( $user3_id, $lesson_id );

		/* Act. */
		$table       = new Sensei_Analysis_Lesson_List_Table( $lesson_id );
		$export_data = $table->generate_report( 'lesson-name-learners-overview' );

		/* Assert. */
		self::assertSame( 4, count( $export_data ) ); // Header row + 3 students.
	}
}
