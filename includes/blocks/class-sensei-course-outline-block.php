<?php
/**
 * File containing the Sensei_Course_Outline_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_Outline_Block
 */
class Sensei_Course_Outline_Block {
	/**
	 * Sensei_Course_Outline_Block constructor.
	 */
	public function __construct() {
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Enqueue frontend and editor assets.
	 *
	 * @access private
	 */
	public function enqueue_assets() {
		Sensei()->assets->enqueue( 'sensei-course-outline-script', 'blocks/course-outline/index.js' );
		Sensei()->assets->enqueue( 'sensei-course-outline-style', 'blocks/course-outline/style.css' );
	}
}
