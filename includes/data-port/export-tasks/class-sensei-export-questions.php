<?php
/**
 * File containing the Sensei_Import_File_Process_Task class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Export content to a CSV file for the given type.
 */
class Sensei_Export_Questions
	extends Sensei_Export_Task {

	/**
	 * Sensei_Export_Questions constructor.
	 *
	 * Export questions.
	 *
	 * @param Sensei_Data_Port_Job $job
	 */
	public function __construct( Sensei_Data_Port_Job $job ) {
		parent::__construct( $job );
		class_alias( 'Sensei_Data_Port_Question_Schema', 'Schema' );
	}

	/**
	 * Content type of the task.
	 *
	 * @return string
	 */
	public function get_content_type() {
		return 'question';
	}

	/**
	 * Collect exported fields of the course.
	 *
	 * @param WP_Post $post The course.
	 *
	 * @return string[]
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

		$columns = array_merge(
			[
				Schema::COLUMN_ID              => $post->ID,
				Schema::COLUMN_TITLE           => $post->post_title,
				Schema::COLUMN_SLUG            => $post->post_name,
				Schema::COLUMN_DESCRIPTION     => $post->post_content,
				Schema::COLUMN_STATUS          => $post->post_status,
				Schema::COLUMN_TYPE            => $question_type,
				Schema::COLUMN_GRADE           => $grade,
				Schema::COLUMN_RANDOM_ORDER    => 'multiple-choice' === $question_type && $meta['_random_order'] ? 1 : '',
				Schema::COLUMN_MEDIA           => $meta['_question_media'],
				Schema::COLUMN_CATEGORIES      => $categories ? Sensei_Data_Port_Utilities::serialize_term_list( $categories ) : '',
				Schema::COLUMN_FEEDBACK        => $meta['_answer_feedback'],
				Schema::COLUMN_TEXT_BEFORE_GAP => '',
				Schema::COLUMN_GAP             => '',
				Schema::COLUMN_TEXT_AFTER_GAP  => '',
				Schema::COLUMN_UPLOAD_NOTES    => '',
				Schema::COLUMN_TEACHER_NOTES   => '',
			],
			$this->get_answer_fields( $question_type, $meta )
		);

		$schema = array_keys( $this->get_type_schema()->get_schema() );
		return array_map(
			function( $column ) use ( $columns ) {
				return $columns[ $column ];
			},
			$schema
		);
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
				$answers_right = array_map(
					function( $value ) {
						return 'Right:' . $value;
					},
					$meta['_question_right_answer']
				);
				$answers_wrong = array_map(
					function( $value ) {
						return 'Wrong:' . $value;
					},
					$meta['_question_wrong_answers']
				);

				$columns[ Schema::COLUMN_ANSWER ] = Sensei_Data_Port_Utilities::serialize_list( array_merge( $answers_right, $answers_wrong ) );

				break;
			case 'boolean':
				$columns[ Schema::COLUMN_ANSWER ] = $meta['_question_right_answer'] ? 'true' : 'false';

				break;
			case 'file-upload':
				$columns[ Schema::COLUMN_TEACHER_NOTES ] = $meta['_question_right_answer'] ?? '';
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
