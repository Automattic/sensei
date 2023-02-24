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
}
