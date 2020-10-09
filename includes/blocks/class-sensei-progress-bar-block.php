<?php
/**
 * File containing the Sensei_Progress_Bar_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Progress_Bar_Block
 */
class Sensei_Progress_Bar_Block {

	/**
	 * Sensei_Progress_Bar_Block constructor.
	 */
	public function __construct() {
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_assets' ] );
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Enqueue frontend and admin assets.
	 *
	 * @access private
	 */
	public function enqueue_block_assets() {
		if ( 'course' !== get_post_type() ) {
			return;
		}

		if ( is_admin() ) {
			Sensei()->assets->enqueue( 'sensei-progress-bar', 'blocks/progress-bar/index.js' );
		}

		Sensei()->assets->enqueue( 'sensei-progress-bar', 'blocks/progress-bar/style.css' );
	}


	/**
	 * Register progress bar block.
	 *
	 * @access private
	 */
	public function register_block() {
		register_block_type_from_metadata(
			Sensei()->assets->src_path( 'blocks/progress-bar' ),
			[
				'render_callback' => [ $this, 'render_progress_bar' ],
			]
		);
	}

	/**
	 * Renders the course progress bar block in the frontend.
	 *
	 * @param array $attributes The block attributes.
	 *
	 * @return string The HTML of the block.
	 */
	public function render_progress_bar( $attributes ) : string {
		return '
			<div>
				<section class="wp-block-sensei-lms-progress-heading">
					<div class="wp-block-sensei-lms-progress-heading__lessons">5 Lessons</div>
					<div class="wp-block-sensei-lms-progress-heading__completed">3 completed (60%)</div>
				</section>
				<progress
					class="wp-block-sensei-lms-progress-bar"
					max="100"
					value="50"
				/>
			</div>
		';
	}
}
