<?php
/**
 * File containing the Sensei_Export_Questions class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Sensei_Data_Port_Question_Schema as Schema;

/**
 * Export content to a CSV file for the given type.
 */
class Sensei_Export_Questions
	extends Sensei_Export_Task {

	/**
	 * Content type of the task.
	 *
	 * @return string
	 */
	public function get_content_type() {
		return 'question';
	}

	/**
	 * Collect exported fields of the question.
	 *
	 * @param WP_Post $post The question.
	 *
	 * @return array The columns data per key.
	 */
	protected function get_post_fields( $post ) {

		$meta_keys     = [
			'_question_grade',
			'_random_order',
			'_answer_feedback',
			'_question_media',
			'_question_right_answer',
			'_question_wrong_answers',
			'_answer_order',
		];
		$meta          = [];
		$question_type = Sensei()->question->get_question_type( $post->ID );
		$grade         = Sensei()->question->get_question_grade( $post->ID );
		$categories    = get_the_terms( $post->ID, 'question-category' );

		foreach ( $meta_keys as $meta_key ) {
			$meta[ $meta_key ] = get_post_meta( $post->ID, $meta_key, true );
		}

		return array_merge(
			[
				Schema::COLUMN_ID              => $post->ID,
				Schema::COLUMN_TITLE           => $post->post_title,
				Schema::COLUMN_SLUG            => $post->post_name,
				Schema::COLUMN_DESCRIPTION     => $post->post_content,
				Schema::COLUMN_STATUS          => $post->post_status,
				Schema::COLUMN_TYPE            => $question_type,
				Schema::COLUMN_GRADE           => $grade,
				Schema::COLUMN_RANDOM_ORDER    => 'multiple-choice' === $question_type && 'yes' === $meta['_random_order'] ? 1 : 0,
				Schema::COLUMN_MEDIA           => $this->get_media( $meta['_question_media'] ),
				Schema::COLUMN_CATEGORIES      => $categories ? Sensei_Data_Port_Utilities::serialize_term_list( $categories ) : '',
				Schema::COLUMN_ANSWER          => '',
				Schema::COLUMN_FEEDBACK        => $meta['_answer_feedback'],
				Schema::COLUMN_TEXT_BEFORE_GAP => '',
				Schema::COLUMN_GAP             => '',
				Schema::COLUMN_TEXT_AFTER_GAP  => '',
				Schema::COLUMN_UPLOAD_NOTES    => '',
				Schema::COLUMN_TEACHER_NOTES   => '',
			],
			$this->get_answer_fields( $question_type, $meta )
		);
	}

	/**
	 * Get media.
	 *
	 * @param int $attachment Attachment ID.
	 *
	 * @return string Media path.
	 */
	private function get_media( $attachment ) {
		if ( empty( $attachment ) ) {
			return '';
		}

		if ( wp_attachment_is_image( $attachment ) ) {
			return wp_get_attachment_image_url( $attachment, 'full' );
		}

		return wp_get_attachment_url( $attachment );
	}

	/**
	 * Collect answer fields based on question type.
	 *
	 * @param string $question_type
	 * @param array  $meta
	 *
	 * @return array
	 */
	protected function get_answer_fields( $question_type, $meta ) {

		$columns = [];

		switch ( $question_type ) {
			case 'multiple-choice':
				$answers_right = Sensei()->question->get_answers_by_id( (array) $meta['_question_right_answer'] );
				$answers_wrong = Sensei()->question->get_answers_by_id( $meta['_question_wrong_answers'] );

				$answers_right = array_map(
					function( $value ) {
						return 'Right:' . Sensei_Data_Port_Utilities::escape_list_item( $value );
					},
					$answers_right
				);
				$answers_wrong = array_map(
					function( $value ) {
						return 'Wrong:' . Sensei_Data_Port_Utilities::escape_list_item( $value );
					},
					$answers_wrong
				);

				$answers = array_merge( $answers_right, $answers_wrong );
				$answers = Sensei()->question->get_answers_sorted( $answers, $meta['_answer_order'] );

				$columns[ Schema::COLUMN_ANSWER ] = implode( ',', $answers );

				break;
			case 'boolean':
				$columns[ Schema::COLUMN_ANSWER ] = $meta['_question_right_answer'];

				break;
			case 'file-upload':
				$columns[ Schema::COLUMN_TEACHER_NOTES ] = $meta['_question_right_answer'];
				$columns[ Schema::COLUMN_UPLOAD_NOTES ]  = ! empty( $meta['_question_wrong_answers'] ) ? $meta['_question_wrong_answers'][0] : '';

				break;
			case 'gap-fill':
				$answer = explode( '||', $meta['_question_right_answer'] );

				$columns[ Schema::COLUMN_TEXT_BEFORE_GAP ] = $answer[0];
				$columns[ Schema::COLUMN_GAP ]             = $answer[1];
				$columns[ Schema::COLUMN_TEXT_AFTER_GAP ]  = $answer[2];
				break;
			case 'single-line':
				$columns[ Schema::COLUMN_ANSWER ] = $meta['_question_right_answer'];
				break;
			case 'multi-line':
				$columns[ Schema::COLUMN_TEACHER_NOTES ] = $meta['_question_right_answer'];
				break;
		}

		return $columns;
	}

	/**
	 * Schema for the content type.
	 *
	 * @return Sensei_Data_Port_Schema
	 */
	protected function get_type_schema() {
		return new Sensei_Data_Port_Question_Schema();
	}

}
