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
	public function __construct( $post_types ) {
		add_action( 'template_redirect', [ $this, 'maybe_initialize_blocks' ] );
		add_action( 'current_screen', [ $this, 'maybe_initialize_blocks' ] );

		$this->post_types = $post_types;
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
	 * Check if blocks should be initialized and do initialization.
	 *
	 * @access private
	 */
	public function maybe_initialize_blocks() {
		if ( is_admin() ) {
			$screen = get_current_screen();

			// Init blocks.
			if ( null === $screen || ! $screen->is_block_editor || ! $this->is_post_type_included( $screen->post_type ) ) {
				return;
			}
		} elseif ( ! $this->is_post_type_included( get_post_type() ) ) {
			return;
		}

		$this->initialize_blocks();

		if ( is_admin() || Sensei()->blocks->has_sensei_blocks() ) {
			add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_assets' ] );
		}

		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
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
