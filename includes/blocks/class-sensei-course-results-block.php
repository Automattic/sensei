<?php
/**
 * File containing the Sensei_Course_Results_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_Results_Block
 */
class Sensei_Course_Results_Block {

	/**
	 * Rendered HTML output for the block.
	 *
	 * @var string
	 */
	private $block_content;

	/**
	 * Sensei_Course_Results_Block constructor.
	 */
	public function __construct() {
		$this->register_blocks();
	}

	/**
	 * Resets block content.
	 *
	 * @access private
	 */
	public function clear_block_content() {
		$this->block_content = null;
	}

	/**
	 * Register course results block.
	 *
	 * @access private
	 */
	public function register_blocks() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-results',
			[
				'render_callback' => [ $this, 'render_course_results_block' ],
			],
			Sensei()->assets->src_path( 'blocks/course-results' )
		);
	}

	/**
	 * Check user permission for editing a course.
	 *
	 * @param int $course_id Course post ID.
	 *
	 * @return bool Whether the user can edit the course.
	 */
	private function can_current_user_edit_course( $course_id ) {
		return current_user_can( get_post_type_object( 'course' )->cap->edit_post, $course_id );
	}

	/**
	 * Render Course Results block.
	 *
	 * @access private
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string Block HTML.
	 */
	public function render_course_results_block( $attributes ) {
		if ( $this->block_content ) {
			return $this->block_content;
		}
		$this->block_attributes['course'] = $attributes;

		$this->block_content = 'Hey!';
		return $this->block_content;

	}

}
