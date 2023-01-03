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
	 * Initialize blocks that are available globally.
	 */
	public function initialize_blocks() {
		new Sensei_Block_Take_Course();
		new Sensei_Block_View_Results();
		new Sensei_Continue_Course_Block();
		new Sensei_Course_Categories_Block();
		new Sensei_Course_List_Filter_Block();
		new Sensei_Course_Progress_Block();
		new Sensei_Course_Overview_Block();
		new Sensei_Course_List_Block();
	}

	/**
	 * Enqueue frontend and editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_assets() {
		Sensei()->assets->enqueue(
			'sensei-global-blocks-style',
			'blocks/global-blocks-style.css'
		);
		if ( ! is_admin() ) {
			Sensei()->assets->enqueue(
				'sensei-course-list-filter',
				'blocks/course-list-filter-block/course-list-filter.js',
				[],
				true
			);
		}
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_editor_assets() {
		Sensei()->assets->enqueue( 'sensei-global-blocks', 'blocks/global-blocks.js', [], true );
		Sensei()->assets->enqueue(
			'sensei-global-blocks-editor-style',
			'blocks/global-blocks-style-editor.css'
		);
	}
}
