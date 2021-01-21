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
	 * @var $post_type
	 */
	private $post_type;

	/**
	 * Sensei_Blocks_Initializer constructor.
	 *
	 * @param string $post_type The post type to initialize blocks for.
	 */
	public function __construct( $post_type ) {
		add_action( 'template_redirect', [ $this, 'maybe_initialize_blocks' ] );
		add_action( 'current_screen', [ $this, 'maybe_initialize_blocks' ] );
		$this->post_type = $post_type;
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
			if ( null === $screen || ! $screen->is_block_editor || $this->post_type !== $screen->post_type ) {
				return;
			}
		} elseif ( get_post_type() !== $this->post_type ) {
			return;
		}

		$this->initialize_blocks();
	}

	/**
	 * Initializes the blocks.
	 */
	abstract public function initialize_blocks();
}
