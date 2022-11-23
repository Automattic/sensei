<?php
/**
 * File containing the Sensei_Export_Lessons class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Sensei_Data_Port_Lesson_Schema as Schema;

/**
 * Export content to a CSV file for the given type.
 */
class Sensei_Export_Lessons
	extends Sensei_Export_Task {

	/**
	 * Content type of the task.
	 *
	 * @return string
	 */
	public function get_content_type() {
		return 'lesson';
	}

	/**
	 * Collect exported fields of the lesson.
	 *
	 * @param WP_Post $post The lesson.
	 *
	 * @return array The columns data per key.
	 */
	protected function get_post_fields( $post ) {
		$meta           = [];
		$meta_keys      = [
			'_lesson_course',
			'_lesson_prerequisite',
			'_lesson_preview',
			'_lesson_length',
			'_lesson_complexity',
			'_lesson_video_embed',
		];
		$quiz_meta_keys = [
			'_pass_required',
			'_quiz_passmark',
			'_show_questions',
			'_random_question_order',
			'_quiz_grade_type',
			'_enable_quiz_reset',
		];

		foreach ( $meta_keys as $meta_key ) {
			$meta[ $meta_key ] = get_post_meta( $post->ID, $meta_key, true );
		}

		$quiz_id = Sensei()->lesson->lesson_quizzes( $post->ID );

		foreach ( $quiz_meta_keys as $meta_key ) {
			$meta[ $meta_key ] = get_post_meta( $quiz_id, $meta_key, true );
		}

		$tags           = get_the_terms( $post->ID, 'lesson-tag' );
		$module_term_id = Sensei()->modules->get_lesson_module_if_exists( $post );
		$questions_ids  = $this->get_quiz_question_ids( $quiz_id );

		return [
			Schema::COLUMN_ID             => $post->ID,
			Schema::COLUMN_TITLE          => $post->post_title,
			Schema::COLUMN_SLUG           => $post->post_name,
			Schema::COLUMN_DESCRIPTION    => $post->post_content,
			Schema::COLUMN_EXCERPT        => $post->post_excerpt,
			Schema::COLUMN_STATUS         => $post->post_status,
			Schema::COLUMN_MODULE         => 0 !== $module_term_id ? get_term( $module_term_id )->name : '',
			Schema::COLUMN_PREREQUISITE   => Sensei_Data_Port_Utilities::serialize_id_field( $meta['_lesson_prerequisite'] ),
			Schema::COLUMN_PREVIEW        => 'preview' === $meta['_lesson_preview'] ? 1 : 0,
			Schema::COLUMN_TAGS           => ! empty( $tags ) ? Sensei_Data_Port_Utilities::serialize_term_list( $tags ) : '',
			Schema::COLUMN_IMAGE          => get_the_post_thumbnail_url( $post, 'full' ),
			Schema::COLUMN_LENGTH         => $meta['_lesson_length'],
			Schema::COLUMN_COMPLEXITY     => $meta['_lesson_complexity'],
			Schema::COLUMN_VIDEO          => $meta['_lesson_video_embed'],
			Schema::COLUMN_PASS_REQUIRED  => 'on' === $meta['_pass_required'] ? 1 : 0,
			Schema::COLUMN_PASSMARK       => $meta['_quiz_passmark'],
			Schema::COLUMN_NUM_QUESTIONS  => $meta['_show_questions'],
			Schema::COLUMN_RANDOMIZE      => 'yes' === $meta['_random_question_order'] ? 1 : 0,
			Schema::COLUMN_AUTO_GRADE     => 'manual' === $meta['_quiz_grade_type'] ? 0 : 1,
			Schema::COLUMN_QUIZ_RESET     => 'on' === $meta['_enable_quiz_reset'] ? 1 : 0,
			Schema::COLUMN_ALLOW_COMMENTS => 'closed' === $post->comment_status ? 0 : 1,
			Schema::COLUMN_QUESTIONS      => Sensei_Data_Port_Utilities::serialize_id_field( $questions_ids ),
		];
	}

	/**
	 * Get question IDs for a Quiz.
	 *
	 * @param int $quiz_id Quiz ID.
	 *
	 * @return int[] Question IDs.
	 */
	private function get_quiz_question_ids( $quiz_id ) {
		return Sensei_Utils::lesson_quiz_questions(
			$quiz_id,
			[
				'fields'   => 'ids',
				'meta_key' => '_quiz_question_order' . $quiz_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Using the lessons sorter.
				'orderby'  => 'meta_value_num title',
			]
		);
	}

	/**
	 * Schema for the content type.
	 *
	 * @return Sensei_Data_Port_Schema
	 */
	protected function get_type_schema() {
		return new Sensei_Data_Port_Lesson_Schema();
	}

}
