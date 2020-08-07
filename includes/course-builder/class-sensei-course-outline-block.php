<?php
/**
 * File containing the class Sensei_Course_Outline_Block.
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
	 * Register course outline block.
	 */
	public function register_block_type() {
		register_block_type(
			'sensei-lms/course-outline',
			[
				'render_callback' => [ __CLASS__, 'render' ],
				'editor_script'   => 'sensei-course-builder',
				'attributes'      => [
					'course_id' => [
						'type' => 'int',
					],
				],
				'supports'        => [],
			]
		);
	}

	/**
	 * Render course outline block.
	 *
	 * @return string
	 */
	public function render() {

		global $post;

		$lessons = Sensei()->course_outline->course_lessons( $post->ID );

		$r = '<div style="border-left: 2px solid #32af7d; padding: 1rem; "><h1>Course outline</h1>';

		foreach ( $lessons->posts as $lesson ) {
			$r .= '
		<div style="border-bottom: 1px solid #eee; padding: 0.5rem; ">
			<h2>
				<a href="' . get_permalink( $lesson ) . '">' . $lesson->post_title . '</a>
			</h2>
			</div>';
		}

		$r .= '</div>';
		return $r;
	}
}
