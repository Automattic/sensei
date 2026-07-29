<?php
/**
 * File containing \Sensei\WPML\Question_Display class.
 *
 * @package sensei
 */

namespace Sensei\WPML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Question_Display
 *
 * Compatibility code with WPML.
 *
 * Renders quiz questions in the viewer's language: submissions store the
 * questions as taken, and this class swaps the display data for the
 * current-language translation at render time.
 *
 * @since $$next-version$$
 *
 * @internal
 */
class Question_Display {
	use WPML_API;

	/**
	 * Init hooks.
	 */
	public function init() {
		// After the question-type loaders (priority 10): they compute selection and
		// order in as-taken space, where stored answers match; this class only
		// swaps what is displayed.
		add_filter( 'sensei_get_question_template_data', array( $this, 'translate_template_data' ), 15, 2 );
		// The question heading is rendered from get_the_title(), outside the
		// template data.
		add_filter( 'the_title', array( $this, 'translate_question_title' ), 10, 2 );
	}

	/**
	 * Show a question's title in the current language on the frontend.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 *
	 * @param string $title   Post title.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	public function translate_question_title( $title, $post_id ) {
		if ( is_admin() || 'question' !== get_post_type( $post_id ) ) {
			return $title;
		}

		$current_language = $this->get_current_language();
		if ( ! $current_language ) {
			return $title;
		}

		$display_question_id = $this->get_object_id( (int) $post_id, 'question', true, $current_language );
		if ( ! $display_question_id || (int) $post_id === $display_question_id ) {
			return $title;
		}

		$display_question = get_post( $display_question_id );
		if ( ! $display_question ) {
			return $title;
		}

		return $display_question->post_title;
	}

	/**
	 * Swap a question's template data for the current-language translation.
	 *
	 * Display only: stored answers and grades are never modified.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 *
	 * @param array $question_data Question template data.
	 * @param int   $question_id   Question ID the data was built from (as taken).
	 * @return array
	 */
	public function translate_template_data( $question_data, $question_id ) {
		$current_language = $this->get_current_language();
		if ( ! $current_language ) {
			return $question_data;
		}

		$display_question_id = $this->get_object_id( (int) $question_id, 'question', true, $current_language );
		if ( ! $display_question_id || (int) $question_id === $display_question_id ) {
			return $question_data;
		}

		$display_question = get_post( $display_question_id );
		if ( ! $display_question ) {
			return $question_data;
		}

		$question_data['title']   = $display_question->post_title;
		$question_data['content'] = $display_question->post_content;

		return $this->translate_answer_options( $question_data, (int) $question_id, $display_question_id );
	}

	/**
	 * Swap the option labels of a choice question for the display question's,
	 * matching right answers with right answers and wrong with wrong, by position.
	 *
	 * Multilingual plugins translate option labels in place, never reordering
	 * them or moving them between the right and wrong lists, so same list + same
	 * position is the same answer. All-or-nothing: on any mismatch (list size,
	 * unknown label) the data is returned untouched and the question renders as
	 * taken.
	 *
	 * The as-taken lists are read from the question's own meta: the type loaders
	 * mutate the ones in the template data (the right answer is merged into the
	 * wrong list to build the options).
	 *
	 * @param array $question_data       Question template data, as left by the type loaders.
	 * @param int   $question_id         As-taken question ID.
	 * @param int   $display_question_id Current-language question ID.
	 * @return array
	 */
	private function translate_answer_options( $question_data, $question_id, $display_question_id ) {
		if ( empty( $question_data['answer_options'] ) ) {
			return $question_data;
		}

		$taken_right   = (array) get_post_meta( $question_id, '_question_right_answer', true );
		$taken_wrong   = (array) get_post_meta( $question_id, '_question_wrong_answers', true );
		$display_right = (array) get_post_meta( $display_question_id, '_question_right_answer', true );
		$display_wrong = (array) get_post_meta( $display_question_id, '_question_wrong_answers', true );

		if ( count( $taken_right ) !== count( $display_right ) || count( $taken_wrong ) !== count( $display_wrong ) ) {
			return $question_data;
		}

		$label_map = array_merge(
			array_combine( array_values( $taken_right ), array_values( $display_right ) ),
			array_combine( array_values( $taken_wrong ), array_values( $display_wrong ) )
		);

		$translated_options = array();
		foreach ( $question_data['answer_options'] as $key => $option ) {
			if ( ! isset( $option['answer'], $label_map[ $option['answer'] ] ) ) {
				return $question_data;
			}

			$option['answer']           = $label_map[ $option['answer'] ];
			$translated_options[ $key ] = $option;
		}

		$question_data['answer_options']         = $translated_options;
		$question_data['question_right_answer']  = get_post_meta( $display_question_id, '_question_right_answer', true );
		$question_data['question_wrong_answers'] = $display_wrong;

		return $question_data;
	}
}
