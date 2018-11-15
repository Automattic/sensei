<?php

class WP_UnitTest_Factory_For_Multiple_Question extends WP_UnitTest_Factory_For_Post_Sensei {
	function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = array(
			'post_status'  => 'publish',
			'post_title'   => new WP_UnitTest_Generator_Sequence( 'Multiple question title %s' ),
			'post_content' => new WP_UnitTest_Generator_Sequence( 'Multiple question content %s' ),
			'post_excerpt' => new WP_UnitTest_Generator_Sequence( 'Multiple question excerpt %s' ),
			'post_type'    => 'multiple_question',
		);
	}

	/**
	 * @param array $args
	 *
	 * @return int|WP_Error
	 * @throws Exception
	 */
	public function create_object( $args ) {
		if ( ! isset( $args['meta_input'] ) ) {
			$args['meta_input'] = array();
		}
		$args['meta_input']['number'] = isset( $args['question_number'] ) ? $args['question_number'] : 3;
		if ( ! isset( $args['question_category_id'] ) ) {
			$question_category            = $this->factory->question_category->create_and_get();
			$args['question_category_id'] = $question_category->term_id;
			$this->factory->question->create_many(
				$args['meta_input']['number'] + 1,
				array(
					'quiz_id'           => $this->factory->quiz->create(),
					'question_category' => $args['question_category_id'],
				)
			);
		}
		$args['meta_input']['category'] = $args['question_category_id'];

		if ( ! empty( $args['quiz_id'] ) ) {
			$args['meta_input']['_quiz_id']                                  = $args['quiz_id'];
			$args['meta_input'][ '_quiz_question_order' . $args['quiz_id'] ] = $args['quiz_id'] . '000' . $args['meta_input']['number'];
			$lesson_id = get_post_meta( $args['quiz_id'], '_quiz_lesson', true );
			update_post_meta( $lesson_id, '_quiz_has_questions', '1' );
		}

		return parent::create_object( $args );
	}

}
