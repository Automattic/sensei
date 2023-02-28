<?php
namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Customization;
use Sensei\Internal\Emails\Email_Post_Type;
use Sensei_Settings;

/**
 * Tests for the Email_Customization class.
 *
 * @covers \Sensei\Internal\Emails\Email_Customization
 */
class Email_Customization_Test extends \WP_UnitTestCase {
	public function testInstance_WhenCalled_ReturnsInstance() {
		/* Arrange. */
		$settings = $this->createMock( Sensei_Settings::class );

		/* Act. */
		$result = Email_Customization::instance( $settings );

		/* Assert. */
		$this->assertInstanceOf( Email_Customization::class, $result );
	}

	public function testInstace_WhenInitiated_AddsHookForRemovingLegacyEmail() {
		/* Arrange. */
		$settings = $this->createMock( Sensei_Settings::class );
		$instance = Email_Customization::instance( $settings );

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
	 *
	 * @dataProvider legacyHooksDataProvider
	 */
	public function testDisableLegacy_WhenCalled_RemovesLegacyEmailHooks( $action_name, $function_name ) {
		/* Arrange. */
		$settings        = $this->createMock( Sensei_Settings::class );
		$instance        = Email_Customization::instance( $settings );
		$priority_before = has_action( $action_name, [ \Sensei()->emails, $function_name ] );

		/* Act. */
		$instance->disable_legacy_emails();

		/* Assert. */
		$priority_after = has_action( $action_name, [ \Sensei()->emails, $function_name ] );
		$this->assertNotEquals( $priority_before, $priority_after );
	}

	public function legacyHooksDataProvider() {
		return [
			'student_completes_course' => [ 'sensei_course_status_updated', 'teacher_completed_course' ],
			'student_starts_course'    => [ 'sensei_user_course_start', 'teacher_started_course' ],
			'student_quiz_submitted'   => [ 'sensei_user_quiz_submitted', 'teacher_quiz_submitted' ],
		];
	}
}
