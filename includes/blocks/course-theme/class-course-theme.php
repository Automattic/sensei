<?php
/**
 * File containing the class Course_Theme.
 *
 * @package sensei
 */

namespace Sensei\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Sensei\Blocks\Course_Theme\Course_Title;
use Sensei\Blocks\Course_Theme\Focus_Mode;
use Sensei\Blocks\Course_Theme\Site_Logo;
use \Sensei_Blocks_Initializer;
use \Sensei_Course_Theme;
use \Sensei\Blocks\Course_Theme\Prev_Lesson;
use \Sensei\Blocks\Course_Theme\Next_Lesson;
use \Sensei\Blocks\Course_Theme\Prev_Next_Lesson;
use \Sensei\Blocks\Course_Theme\Quiz_Back_To_Lesson;
use \Sensei\Blocks\Course_Theme\Course_Progress_Counter;
use \Sensei\Blocks\Course_Theme\Course_Progress_Bar;
use \Sensei\Blocks\Course_Theme\Quiz_Button;

/**
 * Class Course_Theme
 */
class Course_Theme extends Sensei_Blocks_Initializer {
	/**
	 * Course_Theme constructor.
	 */
	public function __construct() {
		parent::__construct( [ 'lesson', 'course', 'quiz' ] );
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
	 * Check if it should initialize the blocks.
	 */
	protected function should_initialize_blocks() {
		return Sensei_Course_Theme::instance()->should_use_sensei_theme_template();
	}

	/**
	 * Initializes the blocks.
	 */
	public function initialize_blocks() {
		new Course_Title();
		new Site_Logo();
		new Focus_Mode();
		if ( 'lesson' === get_post_type() ) {
			new Prev_Lesson();
			new Next_Lesson();
			new Prev_Next_Lesson();
			new Course_Progress_Counter();
			new Course_Progress_Bar();
			new Quiz_Button();
		} elseif ( 'quiz' === get_post_type() ) {
			new Quiz_Back_To_Lesson();
		}

	}
}
