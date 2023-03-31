<?php
namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Customization;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Interface;
use Sensei_Assets;
use Sensei_Settings;

/**
 * Tests for the Email_Customization class.
 *
 * @covers \Sensei\Internal\Emails\Email_Customization
 */
class Email_Customization_Test extends \WP_UnitTestCase {
	protected function setUp(): void {
		add_action( 'sensei_course_status_updated', [ Sensei()->emails, 'teacher_completed_course' ] );
		add_action( 'sensei_user_course_start', [ Sensei()->emails, 'teacher_started_course' ] );
		add_action( 'sensei_user_lesson_end', [ Sensei()->emails, 'teacher_completed_lesson' ] );
		add_action( 'sensei_user_quiz_submitted', [ Sensei()->emails, 'teacher_quiz_submitted' ] );
		add_action( 'sensei_course_status_updated', [ Sensei()->emails, 'learner_completed_course' ] );
		add_action( 'sensei_course_new_teacher_assigned', [ Sensei()->teacher, 'teacher_course_assigned_notification' ] );
		add_action( 'sensei_user_quiz_grade', [ Sensei()->emails, 'learner_graded_quiz' ] );
		add_action( 'sensei_private_message_reply', [ Sensei()->emails, 'new_message_reply' ] );
		add_action( 'sensei_new_private_message', [ Sensei()->emails, 'teacher_new_message' ] );
		add_action( 'transition_post_status', [ Sensei()->teacher, 'notify_admin_teacher_course_creation' ] );
	}

	public function testInstance_WhenCalled_ReturnsInstance() {
		/* Arrange. */
		$settings                   = $this->createMock( Sensei_Settings::class );
		$assets                     = $this->createMock( Sensei_Assets::class );
		$lesson_progress_repository = $this->createMock( Lesson_Progress_Repository_Interface::class );

		/* Act. */
		$result = Email_Customization::instance( $settings, $assets, $lesson_progress_repository );

		/* Assert. */
		$this->assertInstanceOf( Email_Customization::class, $result );
	}

	public function testInstance_WhenInitiated_AddsHookForRemovingLegacyEmail() {
		/* Arrange. */
		$settings                   = $this->createMock( Sensei_Settings::class );
		$assets                     = $this->createMock( Sensei_Assets::class );
		$lesson_progress_repository = $this->createMock( Lesson_Progress_Repository_Interface::class );
		$instance                   = Email_Customization::instance( $settings, $assets, $lesson_progress_repository );

		/* Act. */
		$instance->init();

		/* Assert. */
		$this->assertEquals( 10, has_action( 'init', [ $instance, 'disable_legacy_emails' ] ) );
	}

	/**
	 * Tests that the legacy email hooks are removed when the disable_legacy_emails function is called.
	 *
	 * @param string $action_name The name of the action.
	 * @param string $function_name The name of the function.
	 * @param object $hook_instance The instance of the hook's class.
	 *
	 * @dataProvider legacyHooksDataProvider
	 */
	public function testDisableLegacy_WhenCalled_RemovesLegacyEmailHooks( $action_name, $function_name, $hook_instance ) {
		/* Arrange. */
		$settings                   = $this->createMock( Sensei_Settings::class );
		$assets                     = $this->createMock( Sensei_Assets::class );
		$lesson_progress_repository = $this->createMock( Lesson_Progress_Repository_Interface::class );
		$instance                   = Email_Customization::instance( $settings, $assets, $lesson_progress_repository );
		$priority_before            = has_action( $action_name, [ $hook_instance, $function_name ] );

		/* Act. */
		$instance->disable_legacy_emails();

		/* Assert. */
		$priority_after = has_action( $action_name, [ $hook_instance, $function_name ] );

		$this->assertNotEquals( $priority_before, $priority_after );
	}

	public function testDisableLegacyHook_WhenCalled_CallsTheDisableLegacyActionHook() {
		/* Arrange. */
		$settings                   = $this->createMock( Sensei_Settings::class );
		$assets                     = $this->createMock( Sensei_Assets::class );
		$lesson_progress_repository = $this->createMock( Lesson_Progress_Repository_Interface::class );
		$instance                   = Email_Customization::instance( $settings, $assets, $lesson_progress_repository );
		$count_before               = did_action( 'sensei_disable_legacy_emails' );

		/* Act. */
		$instance->disable_legacy_emails();

		/* Assert. */
		$this->assertEquals( $count_before + 1, did_action( 'sensei_disable_legacy_emails' ) );
	}

	public function legacyHooksDataProvider() {
		return [
			'student_completes_course' => [ 'sensei_course_status_updated', 'teacher_completed_course', Sensei()->emails ],
			'student_starts_course'    => [ 'sensei_user_course_start', 'teacher_started_course', Sensei()->emails ],
			'student_completes_lesson' => [ 'sensei_user_lesson_end', 'teacher_completed_lesson', Sensei()->emails ],
			'student_quiz_submitted'   => [ 'sensei_user_quiz_submitted', 'teacher_quiz_submitted', Sensei()->emails ],
			'course_completed'         => [ 'sensei_course_status_updated', 'learner_completed_course', Sensei()->emails ],
			'teacher_course_assigned'  => [ 'sensei_course_new_teacher_assigned', 'teacher_course_assigned_notification', Sensei()->teacher ],
			'quiz_graded'              => [ 'sensei_user_quiz_grade', 'learner_graded_quiz', Sensei()->emails ],
			'teacher_message_reply'    => [ 'sensei_private_message_reply', 'new_message_reply', Sensei()->emails ],
			'teacher_new_message'      => [ 'sensei_new_private_message', 'teacher_new_message', Sensei()->emails ],
			'course_created'           => [ 'transition_post_status', 'notify_admin_teacher_course_creation', Sensei()->teacher ],
		];
	}
}
