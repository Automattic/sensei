<?php

namespace SenseiTest\Internal\Emails\Generators;

use Sensei\Internal\Emails\Email_Repository;
use Sensei\Internal\Emails\Generators\Student_Completes_Course;
use Sensei_Factory;

/**
 * Tests for Sensei\Internal\Emails\Student_Completes_Course class.
 *
 * @covers \Sensei\Internal\Emails\Generators\Student_Completes_Course
 */
class Student_Completes_Course_Test extends \WP_UnitTestCase {
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

	public function testGenerateEmail_WhenCalledByStudentCompletedCourseEvent_CallsEmailSendingActionWithRightData() {
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
		$course     = $this->factory->course->create_and_get(
			[
				'post_title'  => '“Course with Special Characters…?”',
				'post_author' => $teacher_id,
			]
		);

		$this->manuallyEnrolStudentInCourse( $student_id, $course->ID );

		( new Student_Completes_Course( $this->email_repository ) )->init();

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
		do_action( 'sensei_course_status_updated', 'complete', $student_id, $course->ID );

		/* Assert. */
		self::assertEquals( 'student_completes_course', $email_data['name'] );
		self::assertArrayHasKey( 'test@a.com', $email_data['data'] );
		self::assertEquals( 'Test Student', $email_data['data']['test@a.com']['student:displayname'] );
		self::assertEquals( '“Course with Special Characters…?”', $email_data['data']['test@a.com']['course:name'] );
		self::assertArrayHasKey( 'manage:students', $email_data['data']['test@a.com'] );
		self::assertNotEmpty( $email_data['data']['test@a.com']['manage:students'] );
	}

	public function testGenerateEmail_WhenCalledByStudentCompletedCourseEventAndTeachersFilterHooked_CallsEmailSendingActionWithRightData() {
		/* Arrange. */
		$student_id  = $this->factory->user->create(
			[
				'display_name' => 'Test Student',
			]
		);
		$teacher1_id = $this->factory->user->create(
			[
				'user_email' => 'test@a.com',
			]
		);
		$teacher2_id = $this->factory->user->create(
			[
				'user_email' => 'test@b.com',
			]
		);
		$course      = $this->factory->course->create_and_get(
			[
				'post_title'  => '“Course with Special Characters…?”',
				'post_author' => $teacher1_id,
			]
		);

		$this->manuallyEnrolStudentInCourse( $student_id, $course->ID );

		( new Student_Completes_Course( $this->email_repository ) )->init();

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

		$teachers_filter = function ( $teachers, $course_id ) use ( $teacher2_id ) {
			$teachers[] = $teacher2_id;
			return $teachers;
		};
		add_filter( 'sensei_email_course_teachers', $teachers_filter, 10, 2 );

		/* Act. */
		do_action( 'sensei_course_status_updated', 'complete', $student_id, $course->ID );

		/* Assert. */
		self::assertArrayHasKey( 'test@a.com', $email_data['data'] );
		self::assertArrayHasKey( 'test@b.com', $email_data['data'] );

		/* Clean up. */
		remove_filter( 'sensei_email_course_teachers', $teachers_filter, 10, 2 );
	}

	public function testGenerateEmail_WhenCalledByStudentUpdatedCourseEvent_DoesNotCallEmailIfCourseNotCompleted() {
		/* Arrange. */
		$student_id = $this->factory->user->create();
		$teacher_id = $this->factory->user->create(
			[
				'user_email' => 'test@a.com',
			]
		);
		$course     = $this->factory->course->create_and_get(
			[
				'post_author' => $teacher_id,
			]
		);

		( new Student_Completes_Course( $this->email_repository ) )->init();

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
		do_action( 'sensei_course_status_updated', 'in-progress', $student_id, $course->ID );

		/* Assert. */
		self::assertEmpty( $email_data['name'] );
	}

	public function testGenerateEmail_WhenCalledByStudentUpdatedCourseEvent_DoesNotCallEmailIfStudentNotEnrolled() {
		/* Arrange. */
		$student_id = $this->factory->user->create();
		$teacher_id = $this->factory->user->create(
			[
				'user_email' => 'test@a.com',
			]
		);
		$course     = $this->factory->course->create_and_get(
			[
				'post_author' => $teacher_id,
			]
		);

		( new Student_Completes_Course( $this->email_repository ) )->init();

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
		do_action( 'sensei_course_status_updated', 'complete', $student_id, $course->ID );

		/* Assert. */
		self::assertEmpty( $email_data['name'] );
	}
}
