<?php
/**
 * File containing the Sensei_Course_Completed_Actions_Block class.
 *
 * @package sensei
 * @since 3.13.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_Completed_Actions_Block
 */
class Sensei_Course_Completed_Actions_Block {

	/**
	 * Sensei_Course_Completed_Actions_Block constructor.
	 */
	public function __construct() {
		add_filter( 'render_block', [ $this, 'update_more_courses_button_url' ], 10, 2 );
	}

	/**
	 * Update the URL of the "Find More Courses" button.
	 *
	 * @access private
	 *
	 * @param string $block_content The block content about to be appended.
	 * @param array  $block         The full block, including name and attributes.
	 *
	 * @return string Block HTML.
	 */
	public function update_more_courses_button_url( $block_content, $block ): string {
		return Sensei_Blocks::update_button_block_url(
			$block_content,
			$block,
			'more-courses',
			Sensei_Course::get_courses_page_url()
		);
	}
}
