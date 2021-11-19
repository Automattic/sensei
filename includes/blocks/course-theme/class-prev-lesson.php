<?php
/**
 * File containing the Prev_Lesson class.
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
 * Class Prev_Lesson is responsible for rendering the '< Prev Lesson' block.
 */
class Prev_Lesson {

	/**
	 * Prev_Lesson constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-theme-prev-lesson',
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
		$lesson = get_post();

		if ( empty( $lesson ) ) {
			return '';
		}

		$urls = sensei_get_prev_next_lessons( $lesson->ID );

		if ( empty( $urls['previous']['url'] ) ) {
			return '';
		}
		$url  = esc_url( $urls['previous']['url'] );
		$text = $attributes['text'] ?? __( 'Previous Lesson', 'sensei-lms' );
		$text = wp_kses_post( $text );
		$icon = \Sensei_Utils::icon( 'chevron-left' );

		return ( "
			<a class='sensei-course-theme-prev-next-lesson-a sensei-course-theme-prev-next-lesson-a__prev' href='{$url}'>
				{$icon}
				<span class='sensei-course-theme-prev-next-lesson-text sensei-course-theme-prev-next-lesson-text__prev'>
					{$text}
				</span>
			</a>
		" );
	}
}
