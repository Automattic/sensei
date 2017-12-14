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
	private $module;

	public function __construct() {
		if ( is_admin() ) {
			add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		} else {
			if ( function_exists( 'register_block_type' ) ) {
				register_block_type( 'sensei/module', array(
					'render_callback' => array( $this, 'render_module' ),
				) );
			}
		}
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
		$this->module = get_term_by( 'id', $attributes['moduleId'], 'module' );

		include( 'templates/block-module.php' );
	}

	public function get_module_id() {
		/**
		 * Filter the module ID.
		 *
		 * This fires within the get_module_id function.
		 *
		 * @since 1.9.7
		 *
		 * @param int $this->module->term_id Module ID.
		 */
		return apply_filters( 'sensei_the_module_id', $this->module->term_id );
	}

	public function get_module_title() {
		global $post;

		/**
		 * Filter the module title.
		 *
		 * This fires within the get_module_title function.
		 *
		 * @since 1.9.0
		 *
		 * @param string $this->module->name Module title.
		 * @param int $this->module->term_id Module ID.
		 * @param string $course_id Course ID.
		 */
		return apply_filters( 'sensei_the_module_title', $this->module->name, $this->module->term_id, $post->ID );
	}

	public function get_module_description() {
		/**
		 * Filter the module description.
		 *
		 * This fires within the get_module_description function.
		 *
		 * @param string $this->module->description Module Description.
		 */
		return apply_filters( 'sensei_the_module_description', $this->module->description );
	}

	public function get_module_url() {
		global $post;

		$module_url = get_term_link( $this->module, 'module' );

		/**
		 * Filter the module permalink url.
		 *
		 * This fires within the get_module_url function.
		 *
		 * @since 1.9.0
		 *
		 * @param string $module_url Module URL.
		 * @param int $this->module->term_id Module ID.
		 * @param string $post->ID Course ID.
		 */
		return apply_filters( 'sensei_the_module_permalink', $module_url, $this->module->term_id, $post->ID );
	}
}
