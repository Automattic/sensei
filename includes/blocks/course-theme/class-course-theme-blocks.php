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

use \Sensei_Blocks_Initializer;
use \Sensei\Blocks\Course_Theme as Blocks;

/**
 * Class Course_Theme
 */
class Course_Theme_Blocks extends Sensei_Blocks_Initializer {
	/**
	 * Course_Theme constructor.
	 */
	public function __construct() {
		parent::__construct( null );
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
		new Blocks\Ui();
		new Blocks\Course_Title();
		new Blocks\Course_Navigation();
		new Blocks\Site_Logo();
		new Blocks\Notices();
		new Blocks\Focus_Mode();
		new Blocks\Post_Title();
		new Blocks\Lesson_Module();
		new Blocks\Course_Content();
		new Blocks\Prev_Next_Lesson();
		new Blocks\Exit_Course();
		new Blocks\Course_Progress_Counter();
		new Blocks\Course_Progress_Bar();
		new Blocks\Lesson_Actions();
		new Blocks\Quiz_Back_To_Lesson();
		new Blocks\Sidebar_Toggle_Button();
		new Blocks\Quiz_Actions();
		new Blocks\Page_Actions();
		new Blocks\Template_Style();
		new \Sensei_Block_Quiz_Progress();
		new Blocks\Lesson_Properties();
		new Blocks\Lesson_Video();
	}
}
