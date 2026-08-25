<?php
/**
 * File containing \Sensei\WPML\Grading class.
 *
 * @package sensei
 */

namespace Sensei\WPML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Grading
 *
 * Compatibility code with WPML.
 *
 * @since 4.26.3
 *
 * @internal
 */
class Grading {
	use WPML_API;

	/**
	 * Init hooks.
	 */
	public function init() {
		add_filter( 'sensei_grading_main_column_data', array( $this, 'translate_row_titles' ), 10, 3 );
	}

	/**
	 * Show the lesson and course titles of a Grading row in the current admin language.
	 *
	 * Display only: the rebuilt links keep the original-language IDs, which is
	 * what the listing filters expect.
	 *
	 * @since 4.26.3
	 *
	 * @internal
	 *
	 * @param array  $column_data Column data for the row.
	 * @param object $item        Grading item for the row.
	 * @param int    $course_id   Course ID.
	 * @return array
	 */
	public function translate_row_titles( $column_data, $item, $course_id ) {
		$current_language = $this->get_current_language();
		if ( ! $current_language ) {
			return $column_data;
		}

		$lesson_id = (int) $item->lesson_id;
		if ( ! empty( $column_data['lesson'] ) && $lesson_id ) {
			$display_lesson_id = $this->get_object_id( $lesson_id, 'lesson', true, $current_language );
			if ( $display_lesson_id !== $lesson_id ) {
				$column_data['lesson'] = $this->build_title_link( 'lesson_id', $lesson_id, $display_lesson_id );
			}
		}

		$course_id = (int) $course_id;
		if ( ! empty( $column_data['course'] ) && $course_id ) {
			$display_course_id = $this->get_object_id( $course_id, 'course', true, $current_language );
			if ( $display_course_id !== $course_id ) {
				$column_data['course'] = $this->build_title_link( 'course_id', $course_id, $display_course_id );
			}
		}

		return $column_data;
	}

	/**
	 * Build a Grading filter link with the original ID and the display post's title.
	 *
	 * @param string $query_key   Query argument name.
	 * @param int    $original_id Original-language post ID for the link.
	 * @param int    $display_id  Post ID to take the title from.
	 * @return string
	 */
	private function build_title_link( $query_key, $original_id, $display_id ) {
		return '<a href="' . esc_url(
			add_query_arg(
				array(
					'page'     => 'sensei_grading',
					$query_key => $original_id,
				),
				admin_url( 'admin.php' )
			)
		) . '">' . esc_html( get_the_title( $display_id ) ) . '</a>';
	}
}
