<?php

/**
 * Tests for Sensei_Email_Teacher_Quiz_Submitted class.
 *
 * @covers Sensei_Email_Teacher_Quiz_Submitted
 */
class Sensei_Email_Teacher_Quiz_Submitted_Test extends WP_UnitTestCase {

	/**
	 * Sensei factory.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
		reset_phpmailer_instance();
	}

	/**
	 * The quiz submitted email goes to the author of the lesson's course.
	 *
	 * @covers Sensei_Email_Teacher_Quiz_Submitted::trigger
	 */
	public function testTrigger_WhenStudentHadStartedLesson_SendsEmailToCourseAuthor() {
		// Arrange.
		$teacher_id  = $this->factory->user->create( array( 'user_email' => 'teacher@example.com' ) );
		$student_id  = $this->factory->user->create();
		$course_info = $this->factory->get_course_with_lessons(
			array(
				'lesson_count'   => 1,
				'question_count' => 0,
				'course_args'    => array( 'post_author' => $teacher_id ),
			)
		);
		$lesson_id   = $course_info['lesson_ids'][0];
		$quiz_id     = $course_info['quiz_ids'][0];
		update_post_meta( $quiz_id, '_quiz_lesson', $lesson_id );
		Sensei()->lesson_progress_repository->create( $lesson_id, $student_id );

		// Act.
		( new Sensei_Email_Teacher_Quiz_Submitted() )->trigger( $student_id, $quiz_id );

		// Assert.
		self::assertSame( 'teacher@example.com', tests_retrieve_phpmailer_instance()->get_recipient( 'to' )->address );
	}
}
