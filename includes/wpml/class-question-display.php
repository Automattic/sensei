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
		// Run after the question-type loaders (priority 10), so the student's
		// selection is already resolved when the labels get translated.
		add_filter( 'sensei_get_question_template_data', array( $this, 'translate_template_data' ), 15, 2 );
		// The question heading is rendered from get_the_title(), outside the template data.
		add_filter( 'the_title', array( $this, 'translate_question_title' ), 10, 2 );
		// The description renderer asks through this filter which question to
		// read from.
		add_filter( 'sensei_question_description_post_id', array( $this, 'translate_question_description_id' ) );
		// The "Right Answer:" reveal shown on failed questions.
		add_filter( 'sensei_question_answer_message_correct_answer', array( $this, 'translate_correct_answer_message' ), 10, 3 );
		// The feedback text shown under Correct/Incorrect.
		add_filter( 'sensei_question_answer_notes', array( $this, 'translate_answer_notes' ), 10, 2 );
	}

	/**
	 * Show the question-authored answer feedback in the current language.
	 *
	 * Runs when the feedback box of a graded question is rendered on the quiz
	 * page. If the notes are the question's own feedback (the correct/incorrect
	 * feedback blocks or the answer feedback field), it swaps them for the
	 * translation's version. Notes written by the teacher for this student
	 * while grading have no translation and are shown as written; they are
	 * told apart because they match none of the question's feedback sources.
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
		if ( ! is_string( $answer_notes ) || '' === $answer_notes ) {
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
	 * Runs when the feedback box of a failed question reveals the right answer
	 * on the quiz page. It rebuilds the message from the question's
	 * translation. Gap fill stays as taken on purpose: the right answer only
	 * makes sense against the gap in its own language.
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
	 * Render the question description from the current-language question.
	 *
	 * Runs whenever a question description is rendered on the frontend. In
	 * wp-admin (the grading screen renders descriptions too), or without a
	 * translation, the question renders its own description, exactly like core.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 *
	 * @param int $question_id Question ID the description would render from (as taken).
	 * @return int
	 */
	public function translate_question_description_id( $question_id ) {
		if ( is_admin() ) {
			return $question_id;
		}

		$display_question_id = $this->get_display_question_id( (int) $question_id );

		return $display_question_id ? $display_question_id : (int) $question_id;
	}

	/**
	 * Show a question's title in the current language on the frontend.
	 *
	 * Runs whenever a question title is fetched on the frontend (the quiz page
	 * heading among others). In wp-admin, or without a translation, the title
	 * is returned unchanged.
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

		$display_question = get_post( $display_question_id );

		return $display_question ? $display_question->post_title : $title;
	}

	/**
	 * Get the ID of the question's translation in the current language.
	 *
	 * @param int $question_id Question ID.
	 * @return int Translated question ID, or 0 when the question should render
	 *             as is: no multilingual plugin is active, the question has no
	 *             translation in the current language, or it already is the
	 *             current-language version.
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
	 * Show a question of a completed quiz in the current language.
	 *
	 * Runs every time a question is rendered on the quiz page, after the type
	 * loaders have built its template data. On a completed quiz it replaces the
	 * title, content, and option labels with the ones from the question's
	 * translation. While the quiz is open, or when the options cannot be
	 * mapped, the question renders as taken. Display only: stored answers and
	 * grades are never modified.
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
		// Only translate once the quiz is completed (review mode). While it is
		// open, the option labels are live form values: translating them would
		// make the form submit display-language labels against the as-taken
		// questions and corrupt the stored answers.
		if ( empty( $question_data['quiz_is_completed'] ) ) {
			return $question_data;
		}

		$display_question_id = $this->get_display_question_id( (int) $question_id );
		if ( ! $display_question_id ) {
			return $question_data;
		}

		// All-or-nothing per question: if this is a choice question and its
		// options cannot be mapped, render the whole question as taken.
		$translated = $this->translate_answer_options( $question_data, (int) $question_id, $display_question_id );
		if ( null === $translated ) {
			return $question_data;
		}

		$display_question = get_post( $display_question_id );

		$translated['title']   = $display_question->post_title;
		$translated['content'] = $display_question->post_content;

		return $translated;
	}

	/**
	 * Swap the option labels of a choice question for the display question's,
	 * matching right answers with right answers and wrong with wrong, by position.
	 *
	 * Multilingual plugins translate option labels in place, never reordering
	 * them or moving them between the right and wrong lists, so same list + same
	 * position is the same answer.
	 *
	 * @param array $question_data       Question template data, as left by the type loaders.
	 * @param int   $question_id         As-taken question ID.
	 * @param int   $display_question_id Current-language question ID.
	 * @return array|null Translated data; the data untouched for questions
	 *                    without options; null when options exist but cannot be
	 *                    mapped (list size or label mismatch).
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
			return null;
		}

		$label_map = array_merge(
			array_combine( array_values( $taken_right ), array_values( $display_right ) ),
			array_combine( array_values( $taken_wrong ), array_values( $display_wrong ) )
		);

		$translated_options = array();
		foreach ( $question_data['answer_options'] as $key => $option ) {
			if ( ! isset( $option['answer'], $label_map[ $option['answer'] ] ) ) {
				return null;
			}

			$option['answer']           = $label_map[ $option['answer'] ];
			$translated_options[ $key ] = $option;
		}

		$question_data['answer_options']         = $translated_options;
		$question_data['question_right_answer']  = $this->translate_answer_labels( $question_data['question_right_answer'] ?? '', $label_map );
		$question_data['question_wrong_answers'] = $this->translate_answer_labels( $question_data['question_wrong_answers'] ?? array(), $label_map );

		return $question_data;
	}

	/**
	 * Translate answer labels through the label map, keeping the value's shape.
	 *
	 * The type loaders reshape these fields (the right answer gets merged into
	 * the wrong list to build the options), so the existing values are mapped
	 * in place rather than replaced with the translation's raw meta. Labels
	 * missing from the map are kept as they are.
	 *
	 * @param string|array $labels    Label or list of labels, as the loaders left them.
	 * @param array        $label_map As-taken label => display-language label.
	 * @return string|array
	 */
	private function translate_answer_labels( $labels, $label_map ) {
		if ( is_array( $labels ) ) {
			$translated_labels = array();
			foreach ( $labels as $key => $label ) {
				$translated_labels[ $key ] = $label_map[ $label ] ?? $label;
			}

			return $translated_labels;
		}

		return $label_map[ $labels ] ?? $labels;
	}
}
