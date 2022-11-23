<?php
/**
 * File containing the Exit_Course class.
 *
 * @package sensei
 * @since   4.0.0
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks;

/**
 * Exit course link, to leave Learning Mode and open the course page.
 */
class Exit_Course {
	/**
	 * Block JSON file.
	 */
	const BLOCK_JSON_FILE = '/lesson-blocks/exit-course.block.json';

	/**
	 * Exit_Course constructor.
	 */
	public function __construct() {
		$block_json_path = Sensei()->assets->src_path( 'course-theme/blocks' ) . self::BLOCK_JSON_FILE;
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/exit-course',
			[
				'render_callback' => [ $this, 'render' ],
				'style'           => 'sensei-theme-blocks',
			],
			$block_json_path
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array $attributes The block attributes.
	 *
	 * @access private
	 *
	 * @return string The block HTML.
	 */
	public function render( array $attributes = [] ): string {

		$course_id = \Sensei_Utils::get_current_course();

		$wrapper_attributes = '';
		if ( function_exists( 'get_block_wrapper_attributes' ) ) {
			$wrapper_attributes = get_block_wrapper_attributes( $attributes );
		}

		$label = $attributes['label'] ?? __( 'Exit Course', 'sensei-lms' );

		return sprintf( '<a href="%1$s" %2$s>%3$s</a>', get_the_permalink( $course_id ), $wrapper_attributes, $label );
	}
}
