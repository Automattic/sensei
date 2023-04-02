<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Patterns;

/**
 * Tests for the Email_Blocks class.
 *
 * @covers \Sensei\Internal\Emails\Email_Blocks
 */
class Email_Patterns_Test extends \WP_UnitTestCase {

	public function tearDown(): void {
		parent::tearDown();

		$registry = \WP_Block_Patterns_Registry::get_instance();
		foreach ( $this->get_pattern_items() as $pattern_item ) {
			if ( $registry->is_registered( $pattern_item ) ) {
				$registry->unregister( $pattern_item );
			}
		}
	}

	public function testInit_WhenCalled_AddsFilter() {
		/* Arrange. */
		$patterns = new Email_Patterns();

		/* Act. */
		$patterns->init();

		/* Assert. */
		$patterns_editor_priority  = has_filter( 'current_screen', [ $patterns, 'register_email_editor_block_patterns' ] );
		$patterns_preview_priority = has_filter( 'init', [ $patterns, 'register_email_preview_block_patterns' ] );
		$category_priority         = has_filter( 'init', [ $patterns, 'register_block_patterns_category' ] );
		self::assertSame( 10, $patterns_editor_priority );
		self::assertSame( 10, $patterns_preview_priority );
		self::assertSame( 10, $category_priority );
	}

	public function testPatternRegistration_WhenCalledWithANonEmailPostType_DoesNotRegisterPattern() {
		/* Arrange. */
		$patterns = new Email_Patterns();
		$patterns->init();

		$registry = \WP_Block_Patterns_Registry::get_instance();

		$pattern_items = [
			'sensei-lms/student-completes-course',
			'sensei-lms/student-starts-course',
			'sensei-lms/student-submits-quiz',
			'sensei-lms/student-completes-lesson',
			'sensei-lms/course-completed',
			'sensei-lms/course-created',
			'sensei-lms/course-welcome',
			'sensei-lms/new-course-assigned',
			'sensei-lms/quiz-graded',
			'sensei-lms/teacher-message-reply',
			'sensei-lms/student-message-reply',
			'sensei-lms/student-sends-message',
			'sensei-lms/student-no-progress-3-days',
			'sensei-lms/student-no-progress-7-days',
			'sensei-lms/student-no-progress-28-days',
			'sensei-lms/course-expiration-today',
			'sensei-lms/course-expiration-x-days',
			'sensei-lms/content-drip',
		];

		/* Act. */
		set_current_screen( 'edit-post' );

		/* Assert. */
		foreach ( $pattern_items as $pattern_item ) {
			self::assertFalse( $registry->is_registered( $pattern_item ) );
		}
	}

	public function testPatternRegistration_WhenCalledWithEmailPostType_RegistersPatternAsExpected() {
		/* Arrange. */
		$patterns = new Email_Patterns();
		$patterns->init();
		$screen            = \WP_Screen::get( 'edit-sensei_email' );
		$screen->base      = 'edit-sensei_email';
		$screen->post_type = 'sensei_email';
		$registry          = \WP_Block_Patterns_Registry::get_instance();

		/* Act. */
		$screen->set_current_screen();

		/* Assert. */
		foreach ( $this->get_pattern_items() as $pattern_item ) {
			self::assertTrue( $registry->is_registered( $pattern_item ) );
		}
	}

	public function testRegisterEmailPreviewBlockPatterns_WhenCalledNotInPreview_DoesNotRegisterPattern() {
		/* Arrange. */
		$patterns = new Email_Patterns();
		$registry = \WP_Block_Patterns_Registry::get_instance();

		/* Act. */
		$patterns->register_email_preview_block_patterns();

		/* Assert. */
		foreach ( $this->get_pattern_items() as $pattern_item ) {
			self::assertFalse( $registry->is_registered( $pattern_item ) );
		}
	}

	public function testRegisterEmailPreviewBlockPatterns_WhenCalledInPreview_RegistersPatternAsExpected() {
		/* Arrange. */
		$patterns = new Email_Patterns();
		$registry = \WP_Block_Patterns_Registry::get_instance();

		$_GET['sensei_email_preview_id'] = 1;

		/* Act. */
		$patterns->register_email_preview_block_patterns();

		/* Assert. */
		foreach ( $this->get_pattern_items() as $pattern_item ) {
			self::assertTrue( $registry->is_registered( $pattern_item ) );
		}
	}

	public function testRegisteredCourseCompletedEmail_WhenRetrieved_HasViewResultsButton() {
		/* Arrange. */
		$patterns = new Email_Patterns();
		$registry = \WP_Block_Patterns_Registry::get_instance();

		/* Act. */
		$patterns->register_email_block_patterns();

		/* Assert. */
		$this->assertStringContainsString( 'View Results', $registry->get_registered( 'sensei-lms/course-completed' )['content'] );
		$this->assertStringNotContainsString( 'Certificate', $registry->get_registered( 'sensei-lms/course-completed' )['content'] );
	}

	private function get_pattern_items() {
		return [
			'sensei-lms/student-completes-course',
			'sensei-lms/student-starts-course',
			'sensei-lms/student-completes-lesson',
			'sensei-lms/student-submits-quiz',
			'sensei-lms/course-completed',
			'sensei-lms/course-created',
			'sensei-lms/course-welcome',
			'sensei-lms/new-course-assigned',
			'sensei-lms/quiz-graded',
			'sensei-lms/teacher-message-reply',
			'sensei-lms/student-message-reply',
			'sensei-lms/student-sends-message',
			'sensei-lms/student-no-progress-3-days',
			'sensei-lms/student-no-progress-7-days',
			'sensei-lms/student-no-progress-28-days',
			'sensei-lms/course-expiration-today',
			'sensei-lms/course-expiration-x-days',
			'sensei-lms/content-drip',
		];
	}
}
