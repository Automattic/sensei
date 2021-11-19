<?php
/**
 * File containing the class Sensei_CT_Blocks.
 *
 * @package sensei
 */

namespace Sensei\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks_Initializer;
use \Sensei\Blocks\Course_Theme\Prev_Lesson;
use \Sensei\Blocks\Course_Theme\Next_Lesson;
use \Sensei\Blocks\Course_Theme\Prev_Next_Lesson;
use \Sensei\Blocks\Course_Theme\Quiz_Back_To_Lesson;

/**
 * Class Sensei_Course_Theme_Blocks
 */
class Course_Theme extends Sensei_Blocks_Initializer {
	/**
	 * Sensei_Blocks constructor.
	 */
	public function __construct() {
		parent::__construct( [ 'lesson', 'quiz' ] );
	}

	/**
	 * Enqueue frontend and editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_assets() {
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_editor_assets() {
	}

	/**
	 * Initializes the blocks.
	 */
	public function initialize_blocks() {
		$prev = new Prev_Lesson();
		$next = new Next_Lesson();
		new Prev_Next_Lesson( $prev, $next );
		new Quiz_Back_To_Lesson();
	}
}
