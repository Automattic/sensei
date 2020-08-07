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
	 * @return string[]
	 */
	protected function get_post_fields( $post ) {
		$meta           = [];
		$meta_keys      = [
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

		$tags = get_the_terms( $post->ID, 'lesson-tag' );

		$columns = [
			Schema::COLUMN_ID             => $post->ID,
			Schema::COLUMN_TITLE          => $post->post_title,
			Schema::COLUMN_SLUG           => $post->post_name,
			Schema::COLUMN_DESCRIPTION    => $post->post_content,
			Schema::COLUMN_EXCERPT        => $post->post_excerpt,
			Schema::COLUMN_STATUS         => $post->post_status,
			Schema::COLUMN_COURSE         => '',
			Schema::COLUMN_MODULE         => '',
			Schema::COLUMN_PREREQUISITE   => '',
			Schema::COLUMN_PREVIEW        => 'preview' === $meta['_lesson_preview'] ? 1 : 0,
			Schema::COLUMN_TAGS           => ! empty( $tags ) ? Sensei_Data_Port_Utilities::serialize_term_list( $tags ) : '',
			Schema::COLUMN_IMAGE          => get_the_post_thumbnail_url( $post, 'full' ),
			Schema::COLUMN_LENGTH         => $meta['_lesson_length'],
			Schema::COLUMN_COMPLEXITY     => $meta['_lesson_complexity'],
			Schema::COLUMN_VIDEO          => $meta['_lesson_video_embed'],
			Schema::COLUMN_PASS_REQUIRED  => $meta['_pass_required'],
			Schema::COLUMN_PASSMARK       => $meta['_quiz_passmark'],
			Schema::COLUMN_NUM_QUESTIONS  => $meta['_show_questions'],
			Schema::COLUMN_RANDOMIZE      => $meta['_random_question_order'],
			Schema::COLUMN_AUTO_GRADE     => 'manual' === $meta['_quiz_grade_type'] ? 0 : 1,
			Schema::COLUMN_QUIZ_RESET     => 'on' === $meta['_enable_quiz_reset'] ? 1 : 0,
			Schema::COLUMN_ALLOW_COMMENTS => 'closed' === $post->comment_status ? 0 : 1,
			Schema::COLUMN_QUESTIONS      => '',
		];

		$schema = array_keys( $this->get_type_schema()->get_schema() );
		return array_map(
			function( $column ) use ( $columns ) {
				return $columns[ $column ];
			},
			$schema
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
