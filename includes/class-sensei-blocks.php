<?php
/**
 * File containing the class \Sensei_Blocks
 *
 * @package sensei
 * @since   2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles Sensei's blocks.
 */
final class Sensei_Blocks {
	/**
	 * Stores singleton of self.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Fetch singleton of class.
	 *
	 * @return Sensei_Blocks
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initializes blocks.
	 */
	public function init() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}
		add_action( 'init', [ $this, 'register_blocks' ], 11 );
		add_filter( 'block_categories', [ $this, 'add_sensei_block_category' ] );
	}

	/**
	 * Registers all the blocks.
	 */
	public function register_blocks() {
		$this->register_course_shortcode();
	}

	/**
	 * Add Sensei's block category.
	 *
	 * @param array $categories Block categories.
	 *
	 * @return array
	 */
	public function add_sensei_block_category( $categories ) {
		$categories[] = array(
			'slug'  => 'sensei-lms',
			'title' => __( 'Sensei LMS', 'sensei-lms' ),
			'icon'  => null,
		);

		return $categories;
	}

	/**
	 * Registers the course shortcode block.
	 */
	private function register_course_shortcode() {
		Sensei_Admin::register_styles();
		Sensei_Frontend::register_styles();

		$asset_info = include Sensei()->plugin_path . 'assets/block-editor/build/course-shortcode-block.asset.php';
		wp_register_script(
			'sensei-course-shortcode-block',
			Sensei()->plugin_url . 'assets/block-editor/build/course-shortcode-block.js',
			$asset_info['dependencies'],
			$asset_info['version'],
			true
		);

		$css_extension = is_rtl() ? '.rtl.css' : '.css';
		wp_register_style(
			'sensei-course-shortcode-block-css',
			Sensei()->plugin_url . 'assets/block-editor/build/course-shortcode-block' . $css_extension,
			[ 'sensei-global', Sensei()->token . '-frontend' ],
			$asset_info['version']
		);

		register_block_type(
			'sensei-lms/course-shortcode-block',
			[
				'attributes'      => [
					'exclude' => [
						'type' => 'string',
					],
					'ids'     => [
						'type' => 'string',
					],
					'number'  => [
						'type' => 'number',
					],
					'order'   => [
						'type' => 'string',
					],
					'orderby' => [
						'type' => 'string',
					],
					'teacher' => [
						'type' => 'string',
					],
				],
				'editor_script'   => 'sensei-course-shortcode-block',
				'editor_style'    => 'sensei-course-shortcode-block-css',
				'render_callback' => function( $attributes, $content ) {
					return $this->do_shortcode( 'sensei_courses', $attributes );
				},
			]
		);
	}

	/**
	 * Render the shortcode.
	 *
	 * @param string $shortcode  Name of shortcode.
	 * @param array  $attributes Attributes passed to the block.
	 * @return string
	 */
	private function do_shortcode( $shortcode, $attributes ) {
		$shortcode_str = '[' . $shortcode;
		foreach ( $attributes as $key => $value ) {
			$shortcode_str .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
		}
		$shortcode_str .= ']';
		return do_shortcode( $shortcode_str );
	}
}
