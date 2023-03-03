<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Patterns;

/**
 * Tests for the Email_Blocks class.
 *
 * @covers \Sensei\Internal\Emails\Email_Blocks
 */
class Email_Patterns_Test extends \WP_UnitTestCase {

	public function testInit_WhenCalled_AddsFilter() {
		/* Arrange. */
		$patterns = new Email_Patterns();

		/* Act. */
		$patterns->init();

		/* Assert. */
		$category_priority = has_filter( 'init', [ $patterns, 'register_block_patterns_category' ] );
		$patterns_priority = has_filter( 'current_screen', [ $patterns, 'register_email_editor_block_patterns' ] );
		self::assertSame( 10, $category_priority );
		self::assertSame( 10, $patterns_priority );
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
			'sensei-lms/course-completed',
			'sensei-lms/new-course-assigned',
			'sensei-lms/teacher-message-reply',
			'sensei-lms/student-message-reply',
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
		$pattern_items     = [
			'sensei-lms/student-completes-course',
			'sensei-lms/student-starts-course',
			'sensei-lms/student-submits-quiz',
			'sensei-lms/course-completed',
			'sensei-lms/new-course-assigned',
			'sensei-lms/teacher-message-reply',
			'sensei-lms/student-message-reply',
		];

		/* Act. */
		$screen->set_current_screen();

		/* Assert. */
		foreach ( $pattern_items as $pattern_item ) {
			self::assertTrue( $registry->is_registered( $pattern_item ) );
		}
	}
}
