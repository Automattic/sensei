<?php
/**
 * File containing the class Sensei_Editor_Wizard.
 *
 * @package sensei-lms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles editor wizards.
 *
 * @since $$next-version$$
 */
class Sensei_Editor_Wizard {
	const PATTERNS_CATEGORY = 'sensei-lms';

	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Sensei_Editor_Wizard constructor. Prevents other instances from being created outside of `self::instance()`.
	 */
	private function __construct() {
	}

	/**
	 * Fetches an instance of the class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initializes the class.
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_post_metas' ] );
		add_action( 'init', [ $this, 'register_block_patterns_category' ] );
		add_action( 'current_screen', [ $this, 'register_block_patterns' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
	}

	/**
	 * Register post metas.
	 *
	 * @access private
	 */
	public function register_post_metas() {
		$meta_key = '_new_post';
		$args     = [
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'boolean',
			'auth_callback' => function( $allowed, $meta_key, $post_id ) {
				return current_user_can( 'edit_post', $post_id );
			},
		];

		register_post_meta( 'lesson', $meta_key, $args );
		register_post_meta( 'course', $meta_key, $args );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @param string $hook_suffix The current admin page.
	 *
	 * @access private
	 */
	public function enqueue_admin_scripts( $hook_suffix ) {
		$post_type   = get_post_type();
		$post_id     = get_the_ID();
		$new_post    = get_post_meta( $post_id, '_new_post', true );
		$post_types  = [ 'course', 'lesson' ];
		$is_new_post = 'post-new.php' === $hook_suffix || $new_post;

		if ( $is_new_post && in_array( $post_type, $post_types, true ) ) {
			Sensei()->assets->enqueue( 'sensei-editor-wizard-script', 'admin/editor-wizard/index.js' );
			Sensei()->assets->enqueue( 'sensei-editor-wizard-style', 'admin/editor-wizard/style.css' );

			// Preload extensions (needed to identify if Sensei Pro is installed, and extension details).
			Sensei()->assets->preload_data( [ '/sensei-internal/v1/sensei-extensions?type=plugin' ] );
		}
	}

	/**
	 * Register Sensei block patterns category.
	 *
	 * @access private
	 */
	public function register_block_patterns_category() {
		register_block_pattern_category(
			self::PATTERNS_CATEGORY,
			[ 'label' => __( 'Sensei LMS', 'sensei-lms' ) ]
		);
	}

	/**
	 * Register block patterns.
	 *
	 * @param WP_Screen $current_screen Current WP_Screen object.
	 *
	 * @access private
	 */
	public function register_block_patterns( $current_screen ) {
		$post_type      = $current_screen->post_type;
		$block_patterns = [];

		if ( 'course' === $post_type ) {
			$block_patterns = [
				'video-hero',
				'long-sales-page',
			];
		} elseif ( 'lesson' === $post_type ) {
			$block_patterns = [
				'artists',
				'testimonials',
				'featured',
			];
		}

		foreach ( $block_patterns as $block_pattern ) {
			register_block_pattern(
				'sensei-lms/' . $block_pattern,
				require __DIR__ . '/../block-patterns/' . $post_type . '/' . $block_pattern . '.php'
			);
		}
	}
}
