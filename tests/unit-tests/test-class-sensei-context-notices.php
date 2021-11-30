<?php

/**
 * Tests for Sensei_Context_Notices class.
 *
 * @group context-notices
 */
class Sensei_Context_Notices_Test extends WP_UnitTestCase {
	const TEMPLATE = 'course-theme/lesson-quiz-notice.php';

	/**
	 * Testing the Course Theme class to make sure it is loaded.
	 */
	public function testClassInstance() {
		$this->assertTrue( class_exists( 'Sensei_Context_Notices' ), 'Sensei Context Notices class should exist' );
	}

	/**
	 * Test that it adds new notice.
	 */
	public function testAddNewNotice() {
		$notices = \Sensei_Context_Notices::instance( 'context_x' );

		$actions = [
			[
				'label' => 'Action label',
				'url'   => 'http://action',
				'style' => 'link',
			],
		];
		$notices->add_notice( 'key', 'Text', 'Title', $actions );

		$html = $notices->get_notices_html( self::TEMPLATE );

		$this->assertContains( 'Text', $html );
		$this->assertContains( 'Title', $html );
		$this->assertContains( 'Action label', $html );
	}

	/**
	 * Test that a notice is removed.
	 */
	public function testRemoveNotice() {
		$notices = \Sensei_Context_Notices::instance( 'context_x' );

		$notices->add_notice( 'key', 'Text' );

		$this->assertTrue( $notices->remove_notice( 'key' ) );
		$this->assertFalse( $notices->remove_notice( 'no-exist' ) );

		$html = $notices->get_notices_html( self::TEMPLATE );

		$this->assertNotContains( 'Text', $html );
	}

	/**
	 * Test that notice is updated when using the same key.
	 */
	public function testUpdateNotice() {
		$notices = \Sensei_Context_Notices::instance( 'context_x' );

		$notices->add_notice( 'key', 'X' );
		$notices->add_notice( 'key', 'Y' );
		$notices->add_notice( 'key2', 'Z' );

		$html = $notices->get_notices_html( self::TEMPLATE );

		$this->assertNotContains( 'X', $html );
		$this->assertContains( 'Y', $html );
		$this->assertContains( 'Z', $html );
	}

	/**
	 * Test that notice context works isolated.
	 */
	public function testNoticeContexts() {
		$notices_x = \Sensei_Context_Notices::instance( 'context_x' );
		$notices_y = \Sensei_Context_Notices::instance( 'context_y' );

		$notices_x->add_notice( 'key', 'X' );
		$notices_y->add_notice( 'key', 'Y' );

		$html_x = $notices_x->get_notices_html( self::TEMPLATE );
		$html_y = $notices_y->get_notices_html( self::TEMPLATE );

		$this->assertContains( 'X', $html_x );
		$this->assertNotContains( 'Y', $html_x );

		$this->assertContains( 'Y', $html_y );
		$this->assertNotContains( 'X', $html_y );
	}
}
