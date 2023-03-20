<?php
/**
 * File containing the Email_Blocks class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

use \WP_Theme_JSON_Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Email_Blocks
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Email_Blocks {

	/**
	 *  List of allowed blocks.
	 *
	 *  @internal
	 */
	public const ALLOWED_BLOCKS = [
		'core/paragraph',
		'core/image',
		'core/group',
		'core/heading',
		'core/buttons',
		'core/post-title',
		'core/button',
		'core/site-logo',
		'core/site-title',
	];

	public const EMAIL_THEME_SETTINGS = [
		'version'  => 2,
		'settings' => [
			'layout'     => [
				'contentSize' => '800px',
			],
			'color'      => [ 'palette' => null ],
			'typography' => [
				'fluid'     => false,
				'fontSizes' => [
					'theme' => null,
				],
			],
			'spacing'    =>
				[
					'units'        => 'px',
					'spacingScale' => [
						'steps' => 0,
					],
				],
		],
	];

	/**
	 * Initialize the class and add hooks.
	 *
	 * @internal
	 */
	public function init(): void {
		add_filter( 'allowed_block_types_all', [ $this, 'set_allowed_blocks' ], 25, 2 );
		add_filter( 'wp_theme_json_data_theme', [ $this, 'set_email_css_units' ], 25, 2 );

		add_action( 'current_screen', [ $this, 'load_admin_assets' ] );
	}

	/**
	 * Set the allowed blocks.
	 *
	 * @internal
	 * @access private
	 *
	 * @param bool|string[]            $default_allowed_blocks List of default allowed blocks.
	 * @param \WP_Block_Editor_Context $context     Block Editor Context.
	 *
	 * @return bool|string[]
	 */
	public function set_allowed_blocks( $default_allowed_blocks, $context ) {
		if ( Email_Post_Type::POST_TYPE === ( $context->post->post_type ?? '' ) ) {
			return self::ALLOWED_BLOCKS;
		}

		return $default_allowed_blocks;
	}

	/**
	 * Load admin assets.
	 *
	 * @internal
	 * @access private
	 */
	public function load_admin_assets() {
		$screen = get_current_screen();

		if ( ! is_admin() || ! $screen || 'sensei_email' !== $screen->post_type || ! $screen->is_block_editor ) {
			return;
		}

		Sensei()->assets->enqueue( 'sensei-email-editor-setup', 'blocks/email-editor.js', [], true );
		Sensei()->assets->enqueue( 'sensei-email-editor-style', 'blocks/email-editor-style.css' );
	}

	/**
	 * Set the allowed blocks.
	 *
	 * @internal
	 * @access private
	 *
	 * @param WP_Theme_JSON_Data $theme       Original theme settings.
	 *
	 * @return WP_Theme_JSON_Data Updated theme settings.
	 */
	public function set_email_css_units( $theme ):\WP_Theme_JSON_Data {

		if ( ! is_admin() ) {
			return $theme;
		}

		if ( ! function_exists( 'get_current_screen' ) ) {
			return $theme;
		}

		$screen = get_current_screen();

		if ( Email_Post_Type::POST_TYPE !== ( $screen->post_type ?? '' ) || ! $screen->is_block_editor() ) {
			return $theme;
		}

		$updated = $theme->update_with( self::EMAIL_THEME_SETTINGS );

		return $updated;
	}
}

