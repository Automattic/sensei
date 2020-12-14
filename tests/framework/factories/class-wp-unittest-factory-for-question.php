<?php

class WP_UnitTest_Factory_For_Question extends WP_UnitTest_Factory_For_Post_Sensei {
	private $generated_types = array();
	private $question_count  = 0;

	function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = array();
	}

	public function create_object( $args = array() ) {
		$question_types      = Sensei()->question->question_types();
		$question_type_slugs = array_keys( $question_types );

		if ( isset( $args['question_type'] ) ) {
			$type = $args['question_type'];
			unset( $args['question_type'] );
		} else {
			// If we have created a question for every type, then shuffle.
			if ( count( $this->generated_types ) === count( $question_type_slugs ) ) {
				shuffle( $question_type_slugs );
				$type = array_pop( $question_type_slugs );
			} else {
				$type                    = $question_type_slugs[ count( $this->generated_types ) ];
				$this->generated_types[] = $type;
			}
		}
		$this->question_count++;
		if ( isset( $args['quiz_id'] ) && ! isset( $args['post_author'] ) ) {
			$args['post_author'] = get_post( $args['quiz_id'] )->post_author;
		}
		$args = array_merge( $this->get_sample_question_data( $type ), $args );
		return Sensei()->lesson->lesson_save_question( $args );
	}

	public function get_sample_question_data( $type ) {
		$test_question_data = array(
			'question_type'        => $type,
			'question_category'    => 'undefined',
			'action'               => 'add',
			'question'             => 'Is this a sample' . $type . ' question ? _ ' . rand(),
			'question_grade'       => '1',
			'answer_feedback'      => 'Answer Feedback sample ' . rand(),
			'question_description' => ' Basic description for the question',
			'question_media'       => '',
			'answer_order'         => '',
			'random_order'         => 'yes',
			'question_count'       => $this->question_count,
		);

		// setup the right / wrong answers base on the question type
		if ( 'multiple-choice' === $type ) {

			$test_question_data['question_right_answers'] = array( 'right' );
			$test_question_data['question_wrong_answers'] = array( 'wrong1', 'wrong2', 'wrong3' );

		} elseif ( 'boolean' === $type ) {

			$test_question_data['question_right_answer_boolean'] = 'true';

		} elseif ( 'single-line' === $type ) {

			$test_question_data['add_question_right_answer_singleline'] = '';

		} elseif ( 'gap-fill' === $type ) {

			$test_question_data['add_question_right_answer_gapfill_pre']  = '';
			$test_question_data['add_question_right_answer_gapfill_gap']  = '';
			$test_question_data['add_question_right_answer_gapfill_post'] = '';

		} elseif ( 'multi-line' === $type ) {

			$test_question_data['add_question_right_answer_multiline'] = '';

		} elseif ( 'file-upload' === $type ) {

			$test_question_data['add_question_right_answer_fileupload'] = '';
			$test_question_data['add_question_wrong_answer_fileupload'] = '';

		}

		return $test_question_data;
	}
}
