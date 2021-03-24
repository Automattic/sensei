<?php
/**
 * File containing the class Sensei_Global_Blocks.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Global_Blocks
 */
class Sensei_Global_Blocks extends Sensei_Blocks_Initializer {
	/**
	 * Sensei_Global_Blocks constructor.
	 */
	public function __construct() {
		parent::__construct( null );
	}

	/**
	 * Initialize blocks that are used in course pages.
	 */
	public function initialize_blocks() {
		new Sensei_Learner_Courses_Block();
	}

	/**
	 * Enqueue frontend and editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_assets() {
		Sensei()->assets->enqueue_style( 'sensei-shared-blocks-style' );
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_editor_assets() {
		Sensei()->assets->enqueue_script( 'sensei-shared-blocks' );
	}
}
