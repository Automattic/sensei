<?php
/**
 * File containing the Sensei_Blocks_Initializer class.
 *
 * @package sensei
 * @since 3.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Blocks_Initializer initializes blocks for pages with a specific post type.
 */
abstract class Sensei_Blocks_Initializer {
	/**
	 * The post type to initialize blocks for.
	 *
	 * @var array
	 */
	private $post_types;

	/**
	 * Sensei_Blocks_Initializer constructor.
	 *
	 * @param array|null $post_types The post types to initialize blocks for, or null for any post type.
	 */
	public function __construct( $post_types = null ) {
		add_action( 'init', [ $this, 'maybe_initialize_blocks' ], 200 ); // Should be after all post types are registered.

		$this->post_types = $post_types;
	}

	/**
	 * Check if blocks should be initialized and do initialization.
	 *
	 * @access private
	 */
	public function maybe_initialize_blocks() {
		if ( ! $this->should_initialize_blocks() ) {
			return;
		}

		$this->initialize_blocks();
		$this->initialize_assets();
	}

	/**
	 * Initializes the block assets.
	 */
	private function initialize_assets(): void {
		if ( is_admin() ) {
			add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_assets' ] );
			add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
		} else {
			add_action( 'template_redirect', [ $this, 'initialize_frontend_assets' ], 9 );
		}
	}

	/**
	 * Initializes the block assets for the frontend.
	 *
	 * @access private
	 */
	public function initialize_frontend_assets(): void {
		if ( ! $this->is_post_type_included( get_post_type() ) ) {
			return;
		}

		$is_archive_with_query_block = ( is_post_type_archive( 'course' ) || is_tax( 'course-category' ) ) && Sensei()->course->course_archive_page_has_query_block();
		if (
			$is_archive_with_query_block ||
			Sensei()->blocks->has_sensei_blocks()
		) {
			add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_assets' ] );
		}
	}

	/**
	 * Check if it should initialize the blocks.
	 *
	 * @since 3.13.4
	 *
	 * @return bool
	 */
	protected function should_initialize_blocks() {
		if ( is_admin() ) {
			$post_type = $this->get_admin_page_post_type();
			if ( ! $this->is_post_type_included( $post_type ) ) {
				return false;
			}

			global $pagenow;
			$is_site_editor_or_widgets = in_array( $pagenow, [ 'site-editor.php', 'widgets.php' ], true );
			$is_gutenberg_edit_site    = isset( $_GET['page'] ) && 'gutenberg-edit-site' === $_GET['page']; // phpcs:ignore WordPress.Security.NonceVerification -- Only reading the page.

			if ( ! function_exists( 'use_block_editor_for_post_type' ) ) {
				require_once ABSPATH . 'wp-admin/includes/post.php';
			}

			$is_editor = use_block_editor_for_post_type( $post_type ) || $is_site_editor_or_widgets || $is_gutenberg_edit_site;
			if ( ! $is_editor ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if post type is included for initialization.
	 *
	 * @param string $post_type Post type to check.
	 *
	 * @return boolean Whether post type is included.
	 */
	private function is_post_type_included( $post_type ) {
		return null === $this->post_types || in_array( $post_type, $this->post_types, true );
	}

	/**
	 * Get the post type of the current admin page.
	 *
	 * @return string|null The post type or null if not found.
	 */
	private function get_admin_page_post_type(): ?string {
		global $pagenow;

		if ( 'post.php' === $pagenow && isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- Only reading the post ID.
			$post_type = get_post_type( (int) $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only reading the post ID.
			return $post_type ? $post_type : null;
		}

		if ( 'post-new.php' === $pagenow && isset( $_GET['post_type'] ) && post_type_exists( $_GET['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- Only reading the post type.
			return $_GET['post_type']; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- Already validated.
		}

		return null;
	}

	/**
	 * Initializes the blocks.
	 */
	abstract public function initialize_blocks();

	/**
	 * Enqueue frontend and editor assets.
	 *
	 * @access private
	 */
	abstract public function enqueue_block_assets();

	/**
	 * Enqueue editor assets.
	 *
	 * @access private
	 */
	abstract public function enqueue_block_editor_assets();
}
