<?php
/**
 * This file contains the Sensei_Command_Palette_Test class.
 *
 * @package sensei
 */

/**
 * Tests for Sensei_Command_Palette class.
 */
class Sensei_Command_Palette_Test extends WP_UnitTestCase {
	/**
	 * The command palette script is enqueued on the block editor.
	 *
	 * @covers Sensei_Command_Palette::enqueue_assets
	 */
	public function testEnqueueAssets_BlockEditorHookFired_EnqueuesScript() {
		Sensei_Command_Palette::instance()->init();

		do_action( 'enqueue_block_editor_assets' );

		self::assertTrue( wp_script_is( 'sensei-command-palette' ) );
	}

	/**
	 * The command palette script is enqueued on other wp-admin screens too,
	 * since the palette isn't scoped to a single post type or screen.
	 *
	 * @covers Sensei_Command_Palette::enqueue_assets
	 */
	public function testEnqueueAssets_AdminEnqueueScriptsHookFired_EnqueuesScript() {
		set_current_screen( 'dashboard' );

		Sensei_Command_Palette::instance()->init();

		do_action( 'admin_enqueue_scripts' );

		self::assertTrue( wp_script_is( 'sensei-command-palette' ) );
	}
}
