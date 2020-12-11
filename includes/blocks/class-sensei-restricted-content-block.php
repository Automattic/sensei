<?php
/**
 * File containing the Sensei_Restricted_Content_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Restricted_Content_Block
 */
class Sensei_Restricted_Content_Block {

	/**
	 * Sensei_Restricted_Content_Block constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/unenrolled-content',
			[
				'render_callback' => [ $this, 'render' ],
			],
			Sensei()->assets->src_path( 'blocks/restricted-content/unenrolled' )
		);

		Sensei_Blocks::register_sensei_block(
			'sensei-lms/enrolled-content',
			[
				'render_callback' => [ $this, 'render' ],
			],
			Sensei()->assets->src_path( 'blocks/restricted-content/enrolled' )
		);
	}

	/**
	 * Renders restricted content blocks in the frontend.
	 *
	 * @param array    $attributes The block attributes.
	 * @param string   $content    The inner block content.
	 * @param WP_Block $block      The block object.
	 *
	 * @return string The HTML of the block.
	 */
	public function render( $attributes, $content, $block ) : string {
		$course_id = null;

		if ( 'course' === get_post_type() ) {
			$course_id = get_the_ID();
		} elseif ( 'lesson' === get_post_type() ) {
			$course_id = Sensei()->lesson->get_course_id( get_the_ID() );
		}

		$should_hide = ( 'sensei-lms/enrolled-content' === $block->name && ! Sensei()->course::is_user_enrolled( $course_id ) ) ||
						( 'sensei-lms/unenrolled-content' === $block->name && Sensei()->course::is_user_enrolled( $course_id ) );

		if ( $should_hide ) {
			return '';
		}

		return $content;
	}
}
