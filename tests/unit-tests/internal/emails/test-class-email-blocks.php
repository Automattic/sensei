<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Blocks;
use Sensei\Internal\Emails\Email_Post_Type;
use WP_Post;

/**
 * Tests for the Email_Blocks class.
 *
 * @covers \Sensei\Internal\Emails\Email_Blocks
 */
class Email_Blocks_Test extends \WP_UnitTestCase {

	public function testInit_WhenCalled_AddsFilter() {
		/* Arrange. */
		$blocks = new Email_Blocks();

		/* Act. */
		$blocks->init();

		/* Assert. */
		$priority = has_filter( 'allowed_block_types_all', [ $blocks, 'set_allowed_blocks' ] );
		self::assertSame( 25, $priority );
	}


	public function testSetAllowedBlocks_WhenCalledWithANonEmailPostType_ReturnDefaultBlocks() {
		/* Arrange. */
		$blocks               = new Email_Blocks();
		$default_block        = [ 'core/a', 'core/b', 'core/c' ];
		$block_editor_context = new \WP_Block_Editor_Context( [ 'post' => new WP_Post( (object) array( 'post_type' => 'any_post_type' ) ) ] );

		/* Act. */
		$allowed_blocks = $blocks->set_allowed_blocks( $default_block, $block_editor_context );

		/* Assert. */
		self::assertSame( $allowed_blocks, $default_block );
	}

	public function testSetAllowedBlocks_WhenCalledWithEmailPostType_ReturnAllowedBlocks() {
		/* Arrange. */
		$blocks               = new Email_Blocks();
		$default_block        = [ 'core/block-a', 'core/block-b', 'core/block-c' ];
		$block_editor_context = new \WP_Block_Editor_Context( [ 'post' => new WP_Post( (object) array( 'post_type' => Email_Post_Type::POST_TYPE ) ) ] );

		/* Act. */
		$allowed_blocks = $blocks->set_allowed_blocks( $default_block, $block_editor_context );

		/* Assert. */
		self::assertSame( $allowed_blocks, Email_Blocks::ALLOWED_BLOCKS );
	}

	public function testLoadAdminAssets_WhenCalledWithNonEmailPostType_DoesNotEnqueueScripts() {
		/* Arrange. */
		$blocks = new Email_Blocks();
		$blocks->init();
		set_current_screen( 'any_post_type' );

		/* Act. */
		$blocks->load_admin_assets();

		/* Assert. */
		self::assertFalse( wp_script_is( 'sensei-email-editor-setup' ) );
	}

	public function testLoadAdminAssets_WhenCalledWithEmailPostType_EnqueuesScripts() {
		/* Arrange. */
		$blocks = new Email_Blocks();
		$blocks->init();

		$screen                  = \WP_Screen::get( 'edit-sensei_email' );
		$screen->base            = 'edit-sensei_email';
		$screen->post_type       = 'sensei_email';
		$screen->is_block_editor = true;
		$screen->set_current_screen();

		/* Act. */
		$blocks->load_admin_assets();

		/* Assert. */
		self::assertTrue( wp_script_is( 'sensei-email-editor-setup' ) );
		self::assertTrue( wp_style_is( 'sensei-email-editor-style' ) );
	}
}
