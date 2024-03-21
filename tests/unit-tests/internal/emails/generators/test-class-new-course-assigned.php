<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Repository;
use Sensei\Internal\Emails\Generators\New_Course_Assigned;
use Sensei_Factory;

/**
 * Tests for Sensei\Internal\Emails\New_Course_Assigned class.
 *
 * @covers \Sensei\Internal\Emails\Generators\New_Course_Assigned
 */
class New_Course_Assigned_Test extends \WP_UnitTestCase {
	use \Sensei_Test_Login_Helpers;

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

	public function testSendNewCourseEmail_WhenTeacherAssignedToNewCourseEventFires_CallsEmailSendingActionWithRightData() {
		/* Arrange. */
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

		$edit_link = esc_url(
			add_query_arg(
				array(
					'post'   => $course->ID,
					'action' => 'edit',
				),
				admin_url( 'post.php' )
			)
		);
		( new New_Course_Assigned( $this->email_repository ) )->init();

		$email_data = [
			'name' => '',
			'data' => null,
		];
		$user_name  = get_user_by( 'id', $teacher_id )->display_name;
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
		do_action( 'sensei_course_new_teacher_assigned', $teacher_id, $course->ID );

		/* Assert. */
		self::assertEquals( 'new_course_assigned', $email_data['name'] );
		self::assertArrayHasKey( 'test@a.com', $email_data['data'] );
		self::assertEquals( $user_name, $email_data['data']['test@a.com']['teacher:displayname'] );
		self::assertEquals( '“Course with Special Characters…?”', $email_data['data']['test@a.com']['course:name'] );
		self::assertArrayHasKey( 'editcourse:url', $email_data['data']['test@a.com'] );
		self::assertEquals( $edit_link, $email_data['data']['test@a.com']['editcourse:url'] );
	}

	public function testSendNewCourseEmail_WhenTeacherAssignedToNewCourseEventFires_DoesNotSendEmailIfSameLoggedInUser() {
		/* Arrange. */
		$this->login_as_teacher();

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

		( new New_Course_Assigned( $this->email_repository ) )->init();

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
		do_action( 'sensei_course_new_teacher_assigned', get_current_user_id(), $course->ID );

		/* Assert. */
		self::assertEquals( '', $email_data['name'] );
	}
}
