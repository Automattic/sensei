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
		$lesson_id = \Sensei_Utils::get_current_lesson();

		if ( empty( $lesson_id ) ) {
			return '';
		}

		$urls           = sensei_get_prev_next_lessons( $lesson_id );
		$disabled_attrs = '';

		if ( empty( $urls['previous']['url'] ) ) {
			$url            = '#';
			$tag            = 'span';
			$disabled_attrs = 'data-disabled="disabled"';
		} else {
			$url = esc_url( $urls['previous']['url'] );
			$tag = 'a';
		}

		$text = $attributes['text'] ?? __( 'Previous', 'sensei-lms' );
		$text = wp_kses_post( $text );
		$icon = \Sensei()->assets->get_icon( 'chevron-left' );

		return ( "
			<{$tag} class='sensei-course-theme-prev-next-lesson-a sensei-course-theme-prev-next-lesson-a__prev' href='{$url}' aria-label='{$text}' {$disabled_attrs}>
				{$icon}
				<span class='sensei-course-theme-prev-next-lesson-text sensei-course-theme-prev-next-lesson-text__prev'>
					{$text}
				</span>
			</{$tag}>
		" );
	}
}
