<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Repository;
use Sensei\Internal\Emails\Course_Completed;
use Sensei_Factory;

/**
 * Tests for Sensei\Internal\Emails\Course_Completed class.
 *
 * @covers \Sensei\Internal\Emails\Course_Completed
 */
class Course_Completed_Test extends \WP_UnitTestCase {
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
		$this->email_repository = new Email_Repository();
	}

	/**
	 * Clean up after all tests.
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		self::resetEnrolmentProviders();
	}

	public function testGenerateEmail_WhenCalledByStudentCompletedCourseEvent_CallsStudentEmailSendingActionWithRightData() {
		/* Arrange. */
		$student_id = $this->factory->user->create(
			[
				'display_name' => 'Test Student',
				'user_email'   => 'test@a.com',
			]
		);
		$course     = $this->factory->course->create_and_get(
			[
				'post_title' => 'Test Course',
			]
		);

		\Sensei_Setup_Wizard::instance()->pages->create_pages();
		remove_action( 'sensei_course_status_updated', [ \Sensei()->frontend, 'redirect_to_course_completed_page' ], 1000 );

		$this->manuallyEnrolStudentInCourse( $student_id, $course->ID );

		( new Course_Completed( $this->email_repository ) )->init();

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
		self::assertEquals( 'course_completed', $email_data['name'] );
		self::assertArrayHasKey( 'test@a.com', $email_data['data'] );
		self::assertEquals( 'Test Student', $email_data['data']['test@a.com']['student:displayname'] );
		self::assertEquals( 'Test Course', $email_data['data']['test@a.com']['course:name'] );
		self::assertNotEmpty( $email_data['data']['test@a.com']['certificate:url'] );
		self::assertArrayHasKey( 'results:url', $email_data['data']['test@a.com'] );
	}

	public function testGenerateEmail_WhenCalledByStudentUpdatedCourseEvent_DoesNotCallStudentEmailIfCourseNotCompleted() {
		/* Arrange. */
		$student_id = $this->factory->user->create(
			[
				'user_email' => 'test@a.com',
			]
		);
		$course     = $this->factory->course->create_and_get();

		( new Course_Completed( $this->email_repository ) )->init();

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

	public function testGenerateEmail_WhenCalledByStudentUpdatedCourseEvent_DoesNotCallStudentEmailIfStudentNotEnrolled() {
		/* Arrange. */
		$student_id = $this->factory->user->create(
			[
				'user_email' => 'test@a.com',
			]
		);
		$course     = $this->factory->course->create_and_get();

		( new Course_Completed( $this->email_repository ) )->init();

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
