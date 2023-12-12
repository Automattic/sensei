<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Repository;
use Sensei\Internal\Emails\Generators\Quiz_Graded;
use Sensei_Factory;

/**
 * Tests for Sensei\Internal\Emails\Quiz_Graded class.
 *
 * @covers \Sensei\Internal\Emails\Generators\Quiz_Graded
 */
class Quiz_Graded_Test extends \WP_UnitTestCase {
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

		$this->factory = new Sensei_Factory();

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

	public function testGradeQuizzes_WhenQuizGradedEventIsCalled_CallsEmailSendingActionWithRightData() {
		/* Arrange. */
		$student_id = $this->factory->user->create(
			[
				'user_email' => 'test@a.com',
			]
		);

		$data = $this->factory->get_course_with_lessons(
			[
				'course_args' => [
					'post_title' => 'Course Test',
				],
			]
		);

		$course_id = $data['course_id'];
		$course    = get_post( $course_id );
		$lesson_id = current( $data['lesson_ids'] );
		$lesson    = get_post( $lesson_id );
		$quiz_id   = $data['quiz_ids'][0];
		$quiz_url  = esc_url( get_permalink( $quiz_id ) );

		$this->manuallyEnrolStudentInCourse( $student_id, $course_id );
		\Sensei_Lesson::maybe_start_lesson( $lesson_id, $student_id );

		( new Quiz_Graded( $this->email_repository ) )->init();

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
		do_action( 'sensei_user_quiz_grade', $student_id, $quiz_id, 65.68, 0, 'manual' );

		/* Assert. */
		self::assertEquals( 'quiz_graded', $email_data['name'] );
		self::assertArrayHasKey( 'test@a.com', $email_data['data'] );
		self::assertEquals( __( 'You Passed!', 'sensei-lms' ), $email_data['data']['test@a.com']['grade:validation'] );
		self::assertEquals( $course->post_title, $email_data['data']['test@a.com']['course:name'] );
		self::assertArrayHasKey( 'lesson:name', $email_data['data']['test@a.com'] );
		self::assertEquals( $lesson->post_title, $email_data['data']['test@a.com']['lesson:name'] );
		self::assertArrayHasKey( 'grade:percentage', $email_data['data']['test@a.com'] );
		self::assertEquals( '65.68%', $email_data['data']['test@a.com']['grade:percentage'] );
		self::assertArrayHasKey( 'quiz:url', $email_data['data']['test@a.com'] );
		self::assertEquals( $quiz_url, $email_data['data']['test@a.com']['quiz:url'] );
	}

	public function testGradeQuizzes_WhenQuizGradedEventIsCalled_DoesNotCallIfStudentHasNotStartedCourse() {
		/* Arrange. */
		$student_id = $this->factory->user->create(
			[
				'user_email' => 'test@a.com',
			]
		);

		$data = $this->factory->get_course_with_lessons(
			[
				'course_args' => [
					'post_title' => 'Course Test',
				],
			]
		);

		$course_id = $data['course_id'];
		$lesson_id = current( $data['lesson_ids'] );
		$quiz_id   = $data['quiz_ids'][0];

		$this->manuallyEnrolStudentInCourse( $student_id, $course_id );

		( new Quiz_Graded( $this->email_repository ) )->init();

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
		do_action( 'sensei_user_quiz_grade', $student_id, $quiz_id, 65.68, 0, 'manual' );

		/* Assert. */
		self::assertEquals( '', $email_data['name'] );
	}

	public function testGradeQuizzes_WhenQuizGradedEventIsCalled_CallsEmailSendingWithFailedText() {
		/* Arrange. */
		$student_id = $this->factory->user->create(
			[
				'user_email' => 'test@a.com',
			]
		);

		$data = $this->factory->get_course_with_lessons(
			[
				'course_args' => [
					'post_title' => 'Course Test',
				],
			]
		);

		$course_id = $data['course_id'];
		$lesson_id = current( $data['lesson_ids'] );
		$quiz_id   = $data['quiz_ids'][0];

		$this->manuallyEnrolStudentInCourse( $student_id, $course_id );
		\Sensei_Lesson::maybe_start_lesson( $lesson_id, $student_id );

		( new Quiz_Graded( $this->email_repository ) )->init();

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
		do_action( 'sensei_user_quiz_grade', $student_id, $quiz_id, 65.68, 66, 'manual' );

		/* Assert. */
		self::assertEquals( __( 'You Did Not Pass', 'sensei-lms' ), $email_data['data']['test@a.com']['grade:validation'] );
	}
}
