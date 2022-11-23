<?php
/**
 * File with class for testing Sensei Messages.
 *
 * @package sensei-tests
 */

/**
 * Class for testing Sensei_Messages class.
 *
 * @group messages
 */
class Sensei_Messages_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;

	/**
	 * Factory object.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Set up the test.
	 */
	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Make sure non-messages are not affected.
	 */
	public function testUserMessagesCapCheckNonMessage() {
		$this->login_as_teacher();

		$instance  = new Sensei_Messages();
		$course_id = $this->factory->course->create();

		$this->assertEquals( [], $instance->user_messages_cap_check( [], [ 'read' ], [ 'read_post', get_current_user_id(), $course_id ] ) );
	}

	/**
	 * Make sure participants in a private message have access to the message.
	 */
	public function testUserMessagesCapCheckAsParticipant() {
		$this->login_as_teacher();
		$course_id  = $this->factory->course->create();
		$teacher_id = get_current_user_id();

		$this->login_as_student();
		$student_id = get_current_user_id();

		$instance   = new Sensei_Messages();
		$message_id = $this->factory->message->create(
			[
				'meta_input' => [
					'_post'     => $course_id,
					'_posttype' => 'course',
					'_receiver' => get_user_by( 'ID', $teacher_id )->user_login,
					'_sender'   => get_user_by( 'ID', $student_id )->user_login,
				],
			]
		);

		$this->assertEquals( [ 'read' => true ], $instance->user_messages_cap_check( [], [ 'read' ], [ 'read_post', $teacher_id, $message_id ] ) );
		$this->assertEquals( [ 'read' => true ], $instance->user_messages_cap_check( [], [ 'read' ], [ 'read_post', $student_id, $message_id ] ) );
	}

	/**
	 * Make sure other students and teachers do not have access to messages but admins do.
	 */
	public function testUserMessagesCapCheckAsNonParticipant() {
		$this->login_as_teacher();
		$course_id  = $this->factory->course->create();
		$teacher_id = get_current_user_id();

		$this->login_as_student();
		$student_id = get_current_user_id();

		$instance   = new Sensei_Messages();
		$message_id = $this->factory->message->create(
			[
				'meta_input' => [
					'_post'     => $course_id,
					'_posttype' => 'course',
					'_receiver' => get_user_by( 'ID', $teacher_id )->user_login,
					'_sender'   => get_user_by( 'ID', $student_id )->user_login,
				],
			]
		);

		$this->login_as_teacher_b();
		$this->assertEquals( [ 'read' => false ], $instance->user_messages_cap_check( [], [ 'read' ], [ 'read_post', get_current_user_id(), $message_id ] ), 'Other teachers should not have access' );

		$this->login_as_student_b();
		$this->assertEquals( [ 'read' => false ], $instance->user_messages_cap_check( [], [ 'read' ], [ 'read_post', get_current_user_id(), $message_id ] ), 'Other students should not have access' );

		$this->login_as_admin();
		$this->assertEquals( [ 'read' => true ], $instance->user_messages_cap_check( [], [ 'read' ], [ 'read_post', get_current_user_id(), $message_id ] ), 'Admins should still have access' );
	}
}
