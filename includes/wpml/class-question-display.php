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
		// The question heading is rendered from get_the_title(), outside the template data.
		add_filter( 'the_title', array( $this, 'translate_question_title' ), 10, 2 );
		// The description renderer has no filter carrying the question ID; swap
		// the renderer itself right before a quiz renders its questions.
		add_action( 'sensei_single_quiz_questions_before', array( $this, 'replace_question_description_renderer' ) );
		// The "Right Answer:" reveal shown on failed questions.
		add_filter( 'sensei_question_answer_message_correct_answer', array( $this, 'translate_correct_answer_message' ), 10, 3 );
		// The feedback text shown under Correct/Incorrect.
		add_filter( 'sensei_question_answer_notes', array( $this, 'translate_answer_notes' ), 10, 2 );
	}

	/**
	 * Show the question-authored answer feedback in the current language.
	 *
	 * The notes can also be per-student feedback written by the teacher while
	 * grading; that has no translation and is shown as written. The two are
	 * told apart by matching the notes against the taken question's own
	 * feedback sources.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 *
	 * @param string|false $answer_notes Answer notes.
	 * @param int          $question_id  Question ID the row was built from (as taken).
	 * @return string|false
	 */
	public function translate_answer_notes( $answer_notes, $question_id ) {
		if ( ! $answer_notes || ! is_string( $answer_notes ) ) {
			return $answer_notes;
		}

		$display_question_id = $this->get_display_question_id( (int) $question_id );
		if ( ! $display_question_id ) {
			return $answer_notes;
		}

		$sources = array(
			array(
				\Sensei_Quiz::get_correct_answer_feedback( $question_id ),
				\Sensei_Quiz::get_correct_answer_feedback( $display_question_id ),
			),
			array(
				\Sensei_Quiz::get_incorrect_answer_feedback( $question_id ),
				\Sensei_Quiz::get_incorrect_answer_feedback( $display_question_id ),
			),
			array(
				get_post_meta( $question_id, '_answer_feedback', true ),
				get_post_meta( $display_question_id, '_answer_feedback', true ),
			),
		);

		foreach ( $sources as $source ) {
			list( $taken_feedback, $display_feedback ) = $source;
			if ( $taken_feedback && $answer_notes === $taken_feedback && $display_feedback ) {
				return $display_feedback;
			}
		}

		return $answer_notes;
	}

	/**
	 * Show the "Right Answer:" reveal in the current language.
	 *
	 * Gap fill stays as taken on purpose: the right answer only makes sense
	 * against the gap in its own language.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 *
	 * @param string|false $correct_answer Correct answer message, or false when hidden.
	 * @param int          $lesson_id      Lesson ID.
	 * @param int          $question_id    Question ID the row was built from (as taken).
	 * @return string|false
	 */
	public function translate_correct_answer_message( $correct_answer, $lesson_id, $question_id ) {
		if ( ! $correct_answer ) {
			return $correct_answer;
		}

		if ( 'gap-fill' === Sensei()->question->get_question_type( $question_id ) ) {
			return $correct_answer;
		}

		$display_question_id = $this->get_display_question_id( (int) $question_id );
		if ( ! $display_question_id ) {
			return $correct_answer;
		}

		return \Sensei_Question::get_correct_answer( $display_question_id );
	}

	/**
	 * Take over the question-description renderer on the frontend.
	 *
	 * Only when the core renderer is found where expected: otherwise leave it
	 * alone rather than risk rendering the description twice.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 */
	public function replace_question_description_renderer() {
		if ( ! remove_action( 'sensei_quiz_question_inside_before', array( 'Sensei_Question', 'the_question_description' ), 20 ) ) {
			return;
		}

		add_action( 'sensei_quiz_question_inside_before', array( $this, 'render_question_description' ), 20 );
	}

	/**
	 * Render the question description from the current-language question.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 *
	 * @param int $question_id Question ID the row was built from (as taken).
	 */
	public function render_question_description( $question_id ) {
		$display_question_id = $this->get_display_question_id( (int) $question_id );

		\Sensei_Question::the_question_description( $display_question_id ? $display_question_id : $question_id );
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

		$display_question_id = $this->get_display_question_id( (int) $post_id );
		if ( ! $display_question_id ) {
			return $title;
		}

		return get_post( $display_question_id )->post_title;
	}

	/**
	 * Resolve the current-language twin of a question.
	 *
	 * @param int $question_id Question ID.
	 * @return int Twin question ID, or 0 when there is nothing to translate to
	 *             (no multilingual plugin, no translation, or same question).
	 */
	private function get_display_question_id( $question_id ) {
		$current_language = $this->get_current_language();
		if ( ! $current_language ) {
			return 0;
		}

		$display_question_id = $this->get_object_id( $question_id, 'question', true, $current_language );
		if ( ! $display_question_id || $question_id === $display_question_id || ! get_post( $display_question_id ) ) {
			return 0;
		}

		return $display_question_id;
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
		$display_question_id = $this->get_display_question_id( (int) $question_id );
		if ( ! $display_question_id ) {
			return $question_data;
		}

		$display_question = get_post( $display_question_id );

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
	 * position is the same answer. On any mismatch (list size, unknown label)
	 * the data is returned untouched and the question renders as taken.
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
