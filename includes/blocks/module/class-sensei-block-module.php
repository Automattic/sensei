<?php
/**
 * Sensei Block Module Class
 *
 * Gutenblock for rendering a module.
 *
 * @package Content
 * @author Automattic
 *
 * @since 1.10.0
 */

class Sensei_Block_Module {
	public function __construct() {
		if ( is_admin() ) {
			add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		}

		register_block_type( 'sensei/module', array(
			'render_callback' => array( $this, 'render_module' ),
		) );
	}

	public function enqueue_block_editor_assets() {
		wp_enqueue_script(
			'sensei-block-module',
			Sensei()->plugin_url . 'includes/blocks/module/build/index.js',
			array( 'wp-blocks', 'wp-i18n', 'wp-element' ),
			Sensei()->version
		);

		wp_enqueue_style(
			'sensei-block-module-editor',
			Sensei()->plugin_url . 'includes/blocks/module/build/style.css',
			array( 'wp-blocks' ),
			Sensei()->version
		);

		wp_enqueue_style(
			'sensei-block-module-frontend',
			Sensei()->plugin_url . 'assets/css/frontend/sensei.css',
			array( 'wp-blocks' ),
			Sensei()->version
		);

		wp_enqueue_style(
			'sensei-block-module',
			Sensei()->plugin_url . 'assets/css/modules-frontend.css',
			array( 'wp-blocks' ),
			Sensei()->version
		);
	}

	public function render_module( $attributes ) {
		return Sensei_Templates::get_template( 'single-course/modules.php' );
	}
}
