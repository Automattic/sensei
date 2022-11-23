<?php
/**
 * File containing the Sensei_Conditional_Content_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Conditional_Content_Block
 */
class Sensei_Conditional_Content_Block {

	/**
	 * Sensei_Conditional_Content_Block constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/conditional-content',
			[
				'render_callback' => [ $this, 'render' ],
			],
			Sensei()->assets->src_path( 'blocks/conditional-content-block' )
		);
	}

	/**
	 * Renders conditional content blocks in the frontend.
	 *
	 * @param array  $attributes The block attributes.
	 * @param string $content    The inner block content.
	 *
	 * @return string The HTML of the block.
	 */
	public function render( $attributes, $content ) : string {
		$course_id = null;

		if ( 'course' === get_post_type() ) {
			$course_id = get_the_ID();
		} elseif ( 'lesson' === get_post_type() ) {
			$course_id = Sensei()->lesson->get_course_id( get_the_ID() );
		}

		$should_hide = false;

		switch ( $attributes['condition'] ) {
			case 'enrolled':
				$should_hide = ! Sensei()->course::is_user_enrolled( $course_id );
				break;
			case 'unenrolled':
				$should_hide = Sensei()->course::is_user_enrolled( $course_id );
				break;
			case 'course-completed':
				$should_hide = ! Sensei_Utils::user_completed_course( $course_id );
				break;
			default:
				break;
		}

		if ( $should_hide ) {
			return '';
		}

		return $content;
	}
}
