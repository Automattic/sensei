<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Generator;
use Sensei_Factory;

/**
 * Tests for Sensei\Internal\Emails\Email_Generator class.
 *
 * @covers \Sensei\Internal\Emails\Email_Generator
 */
class Email_Generator_Test extends \WP_UnitTestCase {
	use \Sensei_Course_Enrolment_Test_Helpers;
	use \Sensei_Course_Enrolment_Manual_Test_Helpers;

	/**
	 * Factory for creating test data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	public function setUp(): void {
		parent::setUp();
		$this->prepareEnrolmentManager();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Clean up after all tests.
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		self::resetEnrolmentProviders();
	}

	public function testGenerateEmail_WhenCalledByStudentStartCourseEvent_CallsEmailSendingActionWithRightData() {
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
				'post_title'  => 'Test Course',
				'post_author' => $teacher_id,
			]
		);
		$manage_url = esc_url(
			add_query_arg(
				array(
					'page'      => 'sensei_learners',
					'course_id' => $course->ID,
					'view'      => 'learners',
				),
				admin_url( 'admin.php' )
			)
		);

		( new Email_Generator() )->init();

		$email_data = [
			'name' => '',
			'data' => null,
		];

		add_action(
			'sensei_send_html_email',
			function ( $email_name, $replacements ) use ( &$email_data ) {
				$email_data['name'] = $email_name;
				$email_data['data'] = $replacements;
			},
			10,
			2
		);

		/* Act. */
		do_action( 'sensei_user_course_start', $student_id, $course->ID );

		/* Assert. */
		self::assertEquals( 'student_starts_course', $email_data['name'] );
		self::assertArrayHasKey( 'test@a.com', $email_data['data'] );
		self::assertEquals( 'Test Student', $email_data['data']['test@a.com']['student:displayname'] );
		self::assertEquals( 'Test Course', $email_data['data']['test@a.com']['course:name'] );
		self::assertEquals( $manage_url, $email_data['data']['test@a.com']['manage:students'] );
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
				'post_title'  => 'Test Course',
				'post_author' => $teacher_id,
			]
		);

		$this->manuallyEnrolStudentInCourse( $student_id, $course->ID );

		( new Email_Generator() )->init();

		$email_data = [
			'name' => '',
			'data' => null,
		];

		add_action(
			'sensei_send_html_email',
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
		self::assertEquals( 'Test Course', $email_data['data']['test@a.com']['course:name'] );
		self::assertArrayHasKey( 'manage:students', $email_data['data']['test@a.com'] );
		self::assertNotEmpty( $email_data['data']['test@a.com']['manage:students'] );
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

		( new Email_Generator() )->init();

		$email_data = [
			'name' => '',
			'data' => null,
		];

		add_action(
			'sensei_send_html_email',
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

		( new Email_Generator() )->init();

		$email_data = [
			'name' => '',
			'data' => null,
		];

		add_action(
			'sensei_send_html_email',
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
