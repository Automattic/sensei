<?php
/**
 * File containing extra functionalities of extended Sensei_Course_List_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_List_Block
 */
class Sensei_Course_List_Block {

	/**
	 * Sensei_Course_List_Block constructor.
	 */
	public function __construct() {
		add_filter( 'render_block', [ $this, 'maybe_render_login_form' ], 10, 2 );
	}

	/**
	 * Replaces content of Course List block with the login form when user is logged out and is on My Courses page.
	 *
	 * @param string $block_content The block content to be rendered.
	 * @param array  $block          The block to be rendered.
	 *
	 * @access private
	 * @return string
	 */
	public function maybe_render_login_form( $block_content, $block ) {
		if ( 'core/query' !== $block['blockName'] ) {
			return $block_content;
		}

		$is_course_list_block = 'course' === ( $block['attrs']['query']['postType'] ?? '' ) &&
			false !== strpos( ( $block['attrs']['className'] ?? '' ), 'wp-block-sensei-lms-course-list' );

		$is_my_courses_page = get_the_ID() === (int) Sensei()->settings->get( 'my_course_page' );

		if (
			$is_course_list_block &&
			$is_my_courses_page &&
			! is_user_logged_in()
		) {
			ob_start();
			Sensei()->frontend->sensei_login_form();
			return ob_get_clean();
		}

		return $block_content;
	}
}
