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
		add_filter( 'render_block_data', [ $this, 'maybe_change_inherited_to_true' ], 1 );
		add_filter( 'render_block_data', [ $this, 'maybe_add_attributes_to_inner_blocks' ], 10, 3 );
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

	/**
	 * If course list block is being rendered in Archive page, set inherited to true.
	 *
	 * @param array $parsed_block The block to be rendered.
	 *
	 * @return array
	 */
	public function maybe_change_inherited_to_true( $parsed_block ) {
		if (
			'core/query' === $parsed_block['blockName'] &&
			'course' === ( $parsed_block['attrs']['query']['postType'] ?? '' ) &&
			false !== strpos( ( $parsed_block['attrs']['className'] ?? '' ), 'wp-block-sensei-lms-course-list' ) &&
			Sensei()->course->course_archive_page_has_query_block() &&
			( is_post_type_archive( 'course' ) || is_tax( 'course-category' ) )
		) {
			$parsed_block['attrs']['query']['inherit'] = true;
		}
		return $parsed_block;
	}

	/**
	 * Add an attribute in inner blocks inside post-template block to determine if they're in Query Loop block.
	 *
	 * @param array  $parsed_block The block to be rendered.
	 * @param array  $source_block Unchanged source copy of the block to be rendered.
	 * @param object $parent_block The parent block.
	 *
	 * @access private
	 * @return array
	 */
	public function maybe_add_attributes_to_inner_blocks( $parsed_block, $source_block, $parent_block ) {
		if (
			'core/post-template' === $parsed_block['blockName'] &&
			$parent_block &&
			'core/query' === ( $parent_block->parsed_block['blockName'] ?? '' ) &&
			'course' === ( $parent_block->parsed_block['attrs']['query']['postType'] ?? '' )
		) {
			$parsed_block['innerBlocks'] = $this->recursively_find_and_set_attribute_to_the_take_course_block( $parsed_block['innerBlocks'] );
		}

		return $parsed_block;
	}

	/**
	 * Find and set isCourseListChild attribute to Take Course Button from inner blocks
	 * to indicate if it's in Course List block.
	 *
	 * @param mixed $inner_blocks The inner blocks of the parsed block.
	 *
	 * @return array The inner blocks.
	 */
	private function recursively_find_and_set_attribute_to_the_take_course_block( $inner_blocks ) {
		foreach ( $inner_blocks as $key => $inner_block ) {
			if ( 'sensei-lms/button-take-course' === $inner_block['blockName'] ) {
				if ( ! $inner_block['attrs'] ) {
					$inner_blocks[ $key ]['attrs'] = [];
				}
				$inner_blocks[ $key ]['attrs']['isCourseListChild'] = true;
				break;
			} elseif ( ! empty( $inner_block['innerBlocks'] ) ) {
				$inner_blocks[ $key ]['innerBlocks'] = $this->recursively_find_and_set_attribute_to_the_take_course_block( $inner_block['innerBlocks'] );
			}
		}

		return $inner_blocks;
	}
}
