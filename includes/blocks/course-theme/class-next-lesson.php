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
 * Class Next_Lesson is responsible for rendering the 'Next Lesson >' blocks.
 */
class Next_Lesson {

	/**
	 * The right chevron icon.
	 *
	 * @var string
	 */
	public static $icon = '<svg viewBox="0 0 8 13" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M0.999999 1.00002L6 6.50002L1 12" stroke="#1E1E1E" stroke-width="1.5"/>
						   </svg>';

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
		$lesson = get_post();

		if ( empty( $lesson ) ) {
			return '';
		}

		$urls = sensei_get_prev_next_lessons( $lesson->ID );

		if ( empty( $urls['next']['url'] ) ) {
			return '';
		}
		$url  = esc_url( $urls['next']['url'] );
		$text = $attributes['text'] ?? __( 'Next Lesson', 'sensei-lms' );
		$text = wp_kses_post( $text );
		$icon = self::$icon;

		return ( "
			<a class='sensei-course-theme-prev-next-lesson-a sensei-course-theme-prev-next-lesson-a__next' href='{$url}'>
				<span class='sensei-course-theme-prev-next-lesson-text sensei-course-theme-prev-next-lesson-text__next'>
					{$text}
				</span>
				{$icon}
			</a>
		" );
	}


}
