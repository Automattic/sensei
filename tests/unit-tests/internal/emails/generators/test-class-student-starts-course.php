<?php

namespace SenseiTest\Internal\Emails\Generators;

use Sensei\Internal\Emails\Email_Repository;
use Sensei\Internal\Emails\Generators\Student_Starts_Course;
use Sensei_Factory;

/**
 * Tests for Sensei\Internal\Emails\Student_Starts_Course class.
 *
 * @covers \Sensei\Internal\Emails\Generators\Student_Starts_Course
 */
class Student_Starts_Course_Test extends \WP_UnitTestCase {

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

		$this->factory          = new Sensei_Factory();
		$this->email_repository = $this->createMock( Email_Repository::class );
		$this->email_repository->method( 'get' )
			->willReturn( new \WP_Post( (object) [ 'post_status' => 'publish' ] ) );
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
				'post_title'  => '“Course with Special Characters…?”',
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

		( new Student_Starts_Course( $this->email_repository ) )->init();

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
		do_action( 'sensei_user_course_start', $student_id, $course->ID );

		/* Assert. */
		self::assertEquals( 'student_starts_course', $email_data['name'] );
		self::assertArrayHasKey( 'test@a.com', $email_data['data'] );
		self::assertEquals( 'Test Student', $email_data['data']['test@a.com']['student:displayname'] );
		self::assertEquals( '“Course with Special Characters…?”', $email_data['data']['test@a.com']['course:name'] );
		self::assertEquals( $manage_url, $email_data['data']['test@a.com']['manage:students'] );
	}
}
