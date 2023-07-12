<?php

namespace SenseiTest\Internal\Emails\Generators;

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

		$filters = array(
			'allowed_block_types_all'  => 'set_allowed_blocks',
			'wp_theme_json_data_theme' => 'set_email_css_units',
		);

		/* Assert. */
		foreach ( $filters as $name => $callback ) {
			$priority = has_filter( $name, [ $blocks, $callback ] );
			self::assertTrue( $priority > 0, 'has the filter' . $name );
		}
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

	public function testSetAllowedBlocks_WhenCalledWithoutAnyPostInContext_ThrowsNoException() {
		/* Arrange. */
		$blocks               = new Email_Blocks();
		$default_block        = [ 'core/block-a', 'core/block-b', 'core/block-c' ];
		$block_editor_context = new \WP_Block_Editor_Context( [] );

		/* Assert. */
		$this->expectNotToPerformAssertions();

		/* Act. */
		$blocks->set_allowed_blocks( $default_block, $block_editor_context );
	}


	public function testSetEmailCssUnits_WhenCalledWithTheEmailPostType_ReturnsTheUpdatedTheme() {
		if ( ! version_compare( get_bloginfo( 'version' ), '6.1.0', '>=' ) ) {
			$this->markTestSkipped( 'Requires `WP_Theme_JSON_Data` which was introduced in WordPress 6.1.0.' );
		}

		/* Arrange. */
		$blocks             = new Email_Blocks();
		$theme_json         = $this->createMock( 'WP_Theme_JSON_Data' );
		$updated_theme_json = $this->createMock( 'WP_Theme_JSON_Data' );

		$screen                  = \WP_Screen::get( 'edit-sensei_email' );
		$screen->base            = 'edit-sensei_email';
		$screen->post_type       = Email_Post_Type::POST_TYPE;
		$screen->is_block_editor = true;
		$screen->set_current_screen();

		$theme_json->expects( $this->once() )
			->method( 'update_with' )
			->with( Email_Blocks::EMAIL_THEME_SETTINGS )
			->willReturn( $updated_theme_json );

		/* Act. */
		$this->assertSame( $updated_theme_json, $blocks->set_email_css_units( $theme_json ) );
	}

	public function testSetEmailCssUnits_WhenCalledWithTheOtherPostType_ReturnsTheOriginalTheme() {
		if ( ! version_compare( get_bloginfo( 'version' ), '6.1.0', '>=' ) ) {
			$this->markTestSkipped( 'Requires `WP_Theme_JSON_Data` which was introduced in WordPress 6.1.0.' );
		}

		/* Arrange. */
		$blocks             = new Email_Blocks();
		$theme_json         = $this->createMock( 'WP_Theme_JSON_Data' );
		$updated_theme_json = $this->createMock( 'WP_Theme_JSON_Data' );

		$theme_json
			->method( 'update_with' )
			->willReturn( $updated_theme_json );

		/* Act. */
		$this->assertSame( $theme_json, $blocks->set_email_css_units( $theme_json ) );
	}

	/**
	 * Ran in separate process to not set WP_ADMIN for all tests.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testSetEmailCssUnits_WhenCalledWithoutACurrentScreen_DoesNotRaiseWarnings() {
		if ( ! version_compare( get_bloginfo( 'version' ), '6.1.0', '>=' ) ) {
			$this->markTestSkipped( 'Requires `WP_Theme_JSON_Data` which was introduced in WordPress 6.1.0.' );
		}

		/* Arrange. */
		$blocks     = new Email_Blocks();
		$theme_json = $this->createMock( 'WP_Theme_JSON_Data' );

		define( 'WP_ADMIN', true );

		/* Assert. */
		$this->expectNotToPerformAssertions();

		/* Act. */
		$blocks->set_email_css_units( $theme_json );
	}
}
