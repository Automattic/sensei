<?php
/**
 * File containing the Prev_Next_Lesson class.
 *
 * @package sensei
 * @since   3.13.4
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks;

/**
 * Class Prev_Next_Lesson is responsible for rendering the '< Prev Lesson | Next Lesson >' blocks.
 */
class Prev_Next_Lesson {
	/**
	 * Block JSON file.
	 */
	const BLOCK_JSON_FILE = '/lesson-blocks/course-theme-prev-next-lesson.block.json';

	/**
	 * Prev_Next_Lesson constructor.
	 */
	public function __construct() {
		$block_json_path = Sensei()->assets->src_path( 'course-theme/blocks' ) . self::BLOCK_JSON_FILE;
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-theme-prev-next-lesson',
			[
				'render_callback' => [ $this, 'render' ],
				'style'           => 'sensei-theme-blocks',
			],
			$block_json_path
		);
	}

	/**
	 * Get the previous or next link link.
	 *
	 * @param array  $urls       Previous and next lesson URLs.
	 * @param string $type       Link type.
	 * @param string $label      Link label.
	 * @param string $icon_name  Icon name for the link.
	 * @param string $aria_label Link ARIA label.
	 *
	 * @return string The link,
	 */
	private function get_link( $urls, $type, $label, $icon_name, $aria_label ) {
		$disabled_attrs = '';

		if ( empty( $urls[ $type ]['url'] ) ) {
			$url            = '#';
			$tag            = 'span';
			$disabled_attrs = 'data-disabled="disabled" aria-disabled="true"';
		} else {
			$url = esc_url( $urls[ $type ]['url'] );
			$tag = 'a';
		}

		$icon        = \Sensei()->assets->get_icon( $icon_name );
		$before_icon = 'previous' === $type ? $icon : '';
		$after_icon  = 'next' === $type ? $icon : '';

		$aria_label = $aria_label ?? $label;

		return ( "
			<{$tag} class='sensei-course-theme-prev-next-lesson-a sensei-course-theme-prev-next-lesson-a__next' href='{$url}' aria-label='{$aria_label}' {$disabled_attrs}>
				{$before_icon}
				<span class='sensei-course-theme-prev-next-lesson-text sensei-course-theme-prev-next-lesson-text__next'>
					{$label}
				</span>
				{$after_icon}
			</{$tag}>
		" );
	}

	/**
	 * Renders the block.
	 *
	 * @param array  $attributes The attributes that were saved for this block.
	 * @param string $content    The content that is rendered by the inner blocks.
	 *
	 * @return string The block HTML.
	 */
	public function render( array $attributes, string $content ): string {
		$lesson_id = \Sensei_Utils::get_current_lesson();

		if ( empty( $lesson_id ) ) {
			return '';
		}

		$urls = sensei_get_prev_next_lessons( $lesson_id );
		$prev = $this->get_link( $urls, 'previous', __( 'Previous', 'sensei-lms' ), 'chevron-left', __( 'Previous Lesson', 'sensei-lms' ) );
		$next = $this->get_link( $urls, 'next', __( 'Next', 'sensei-lms' ), 'chevron-right', __( 'Next Lesson', 'sensei-lms' ) );

		$wrapper_attr = get_block_wrapper_attributes(
			[
				'class' => 'sensei-course-theme-prev-next-lesson-container',
			]
		);

		return sprintf( '<nav %s>%s %s</nav>', $wrapper_attr, $prev, $next );
	}
}
