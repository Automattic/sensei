<?php
/**
 * Sensei REST API: Sensei_REST_API_Lessons_Controller class.
 *
 * @package sensei-lms
 * @since 3.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

/**
 * A REST controller for Sensei LMS lessons CPT.
 *
 * @since 3.9.0
 *
 * @see WP_REST_Posts_Controller
 */
class Sensei_REST_API_Lessons_Controller extends WP_REST_Posts_Controller {
	/**
	 * Adds the quiz block if missing when a quiz is active on the lesson.
	 *
	 * @param array           $prepared Prepared response array.
	 * @param WP_REST_Request $request  Full details about the request.
	 * @return array Modified data object with additional fields.
	 */
	protected function add_additional_fields_to_object( $prepared, $request ) {
		if ( ! Sensei()->quiz->is_block_based_editor_enabled() ) {
			return $prepared;
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		if ( 'edit' === $context && isset( $prepared['content']['raw'] ) ) {
			$post = get_post();
			if ( Sensei()->lesson::lesson_quiz_has_questions( $post->ID ) && ! has_block( 'sensei-lms/quiz' ) ) {
				$prepared['content']['raw'] .= serialize_block(
					[
						'blockName'    => 'sensei-lms/quiz',
						'innerContent' => [],
						'attrs'        => [],
					]
				);
			}
		}

		return $prepared;
	}
}
