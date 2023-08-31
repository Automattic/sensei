<?php

namespace SenseiTest\Internal\Emails\Generators;

use Sensei\Internal\Emails\Email_Repository;
use Sensei\Internal\Emails\Generators\Student_Submits_Quiz;
use Sensei_Factory;

/**
 * Tests for Sensei\Internal\Emails\Student_Submits_Quiz class.
 *
 * @covers \Sensei\Internal\Emails\Generators\Student_Submits_Quiz
 */
class Student_Submits_Quiz_Test extends \WP_UnitTestCase {
	use \Sensei_Course_Enrolment_Test_Helpers;
	use \Sensei_Course_Enrolment_Manual_Test_Helpers;

	/**
	 * Factory for creating test data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Email repository instance.
	 *
	 * @var Email_Repository
	 */
	protected $email_repository;

	public function setUp(): void {
		parent::setUp();
		$this->prepareEnrolmentManager();

		$this->factory          = new Sensei_Factory();
		$this->email_repository = $this->createMock( Email_Repository::class );
		$this->email_repository->method( 'get' )
			->willReturn( new \WP_Post( (object) [ 'post_status' => 'publish' ] ) );
	}

	/**
	 * Clean up after all tests.
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		self::resetEnrolmentProviders();
	}

	public function testStudentSubmitsQuizMailToTeacher_WhenCalled_CallsEmailSendingActionWithRightData() {
		/* Arrange. */
		$student_id = $this->factory->user->create(
			[
				'display_name' => 'Test Student',
			]
		);
		$teacher_id = $this->factory->user->create(
			[
				'user_email' => 'test@a.com',
			]
		);

		$data = $this->factory->get_course_with_lessons(
			[
				'course_args' => [
					'post_author' => $teacher_id,
				],
				'lesson_args' => [
					'post_author' => $teacher_id,
				],
			]
		);

		$course_id = $data['course_id'];
		$course    = get_post( $course_id );

		// Add a question to the course's quiz.
		$quiz_id     = $data['quiz_ids'][0];
		$question_id = wp_insert_post(
			[
				'post_type'   => 'question',
				'post_title'  => 'Question',
				'post_status' => 'publish',
				'post_author' => $teacher_id,
			]
		);
		Sensei()->quiz->set_questions( $quiz_id, [ $question_id ] );

		$this->manuallyEnrolStudentInCourse( $student_id, $course_id );

		$grade_url = esc_url(
			add_query_arg(
				array(
					'page'    => 'sensei_grading',
					'quiz_id' => $quiz_id,
					'user'    => $student_id,
				),
				admin_url( 'admin.php' )
			)
		);

		( new Student_Submits_Quiz( $this->email_repository ) )->init();

		$email_data = [
			'name' => '',
			'data' => null,
		];

		add_action(
			'sensei_email_send',
			function ( $email_name, $replacements ) use ( &$email_data ) {
				$email_data['name'] = $email_name;
				$email_data['data'] = $replacements;
			},
			10,
			2
		);

		/* Act. */
		do_action( 'sensei_user_quiz_submitted', $student_id, $quiz_id, 0, 0, 'manual' );

		/* Assert. */
		self::assertEquals( 'student_submits_quiz', $email_data['name'] );
		self::assertArrayHasKey( 'test@a.com', $email_data['data'] );
		self::assertEquals( 'Test Student', $email_data['data']['test@a.com']['student:displayname'] );
		self::assertEquals( $course->post_title, $email_data['data']['test@a.com']['course:name'] );
		self::assertArrayHasKey( 'lesson:name', $email_data['data']['test@a.com'] );
		self::assertArrayHasKey( 'grade:quiz', $email_data['data']['test@a.com'] );
		self::assertNotEmpty( $email_data['data']['test@a.com']['grade:quiz'] );
		self::assertEquals( $grade_url, $email_data['data']['test@a.com']['grade:quiz'] );
	}

	public function testGenerateEmail_WhenCalledByStudentSubmittedQuizEvent_DoesNotCallEmailSendingIfGradeNotManual() {
		/* Arrange. */
		$student_id = $this->factory->user->create(
			[
				'display_name' => 'Test Student',
			]
		);

		( new Student_Submits_Quiz( $this->email_repository ) )->init();

		$email_data = [
			'name' => '',
			'data' => null,
		];

		add_action(
			'sensei_email_send',
			function ( $email_name, $replacements ) use ( &$email_data ) {
				$email_data['name'] = $email_name;
				$email_data['data'] = $replacements;
			},
			10,
			2
		);

		/* Act. */
		do_action( 'sensei_user_quiz_submitted', $student_id, 0, 0, 0, 'something-else' );

		/* Assert. */
		self::assertEquals( '', $email_data['name'] );
	}
}
