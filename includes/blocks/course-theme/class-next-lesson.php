<?php
/**
 * File containing the Next_Lesson class.
 *
 * @package sensei
 * @since
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks;

/**
 * Class Next_Lesson is responsible for rendering the 'Next Lesson >' block.
 */
class Next_Lesson {

	/**
	 * Next_Lesson constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-theme-next-lesson',
			[
				'render_callback' => [ $this, 'render' ],
			]
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
	public function render( array $attributes = [] ) : string {
		$lesson_id = \Sensei_Utils::get_current_lesson();

		if ( empty( $lesson_id ) ) {
			return '';
		}

		$urls = sensei_get_prev_next_lessons( $lesson_id );

		if ( empty( $urls['next']['url'] ) ) {
			return '';
		}
		$url  = esc_url( $urls['next']['url'] );
		$text = $attributes['text'] ?? __( 'Next', 'sensei-lms' );
		$text = wp_kses_post( $text );
		$icon = \Sensei()->assets->get_icon( 'chevron-right' );

		return ( "
			<a class='sensei-course-theme-prev-next-lesson-a sensei-course-theme-prev-next-lesson-a__next' href='{$url}' aria-label='{$text}'>
				<span class='sensei-course-theme-prev-next-lesson-text sensei-course-theme-prev-next-lesson-text__next'>
					{$text}
				</span>
				{$icon}
			</a>
		" );
	}


}
