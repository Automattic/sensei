<?php
/**
 * File containing the \Sensei\WPML\Language_Details class.
 *
 * @package sensei
 */

namespace Sensei\WPML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Language_Details
 *
 * Compatibility code with WPML.
 *
 * @since 4.22.0
 *
 * @internal
 */
class Language_Details {
	/**
	 * Init hooks.
	 */
	public function init() {
		add_action( 'sensei_course_structure_lesson_created', array( $this, 'set_language_details_when_lesson_created' ), 10, 2 );
		add_action( 'sensei_quiz_create', array( $this, 'set_language_details_when_quiz_created' ), 10, 2 );
		add_action( 'sensei_quiz_create', array( $this, 'set_language_details_when_quiz_created' ), 10, 2 );
		add_action( 'sensei_quiz_create', array( $this, 'set_language_details_when_quiz_created' ), 10, 2 );
		add_action( 'sensei_rest_api_question_saved', array( $this, 'set_language_details_when_question_created' ), 10, 1 );
	}

	/**
	 * Set language details for the lesson when it is created.
	 *
	 * @since 4.20.1
	 *
	 * @internal
	 *
	 * @param int $lesson_id Lesson ID.
	 * @param int $course_id Course ID.
	 */
	public function set_language_details_when_lesson_created( $lesson_id, $course_id ) {
		// Get course language_code.
		$language_code = apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			'wpml_element_language_code',
			null,
			array(
				'element_id'   => $course_id,
				'element_type' => 'course',
			)
		);

		if ( ! $language_code ) {
			// Use current language if course language is not set.
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$language_code = apply_filters( 'wpml_current_language', null );
		}

		$args = array(
			'element_id'    => $lesson_id,
			'element_type'  => 'post_lesson',
			'trid'          => false,
			'language_code' => $language_code,
		);

		// Set language details for the lesson.
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		do_action( 'wpml_set_element_language_details', $args );
	}

	/**
	 * Set language details for the quiz when it is created.
	 *
	 * @since 4.20.1
	 *
	 * @internal
	 *
	 * @param int $quiz_id   Quiz ID.
	 * @param int $lesson_id Lesson ID.
	 */
	public function set_language_details_when_quiz_created( $quiz_id, $lesson_id ) {
		// Get lesson language_code.
		$language_code = apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			'wpml_element_language_code',
			null,
			array(
				'element_id'   => $lesson_id,
				'element_type' => 'lesson',
			)
		);
		if ( ! $language_code ) {
			// Use current language if lesson language is not set.
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$language_code = apply_filters( 'wpml_current_language', null );
		}

		$args = array(
			'element_id'    => $quiz_id,
			'element_type'  => 'post_quiz',
			'trid'          => false,
			'language_code' => $language_code,
		);

		// Set language details for the quiz.
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		do_action( 'wpml_set_element_language_details', $args );
	}

	/**
	 * Set language details for the question when it is created.
	 *
	 * @since 4.22.0
	 *
	 * @internal
	 *
	 * @param int $question_id Question ID.
	 */
	public function set_language_details_when_question_created( $question_id ) {
		if ( is_wp_error( $question_id ) ) {
			return;
		}

		$question_id = (int) $question_id;

		// Get lesson language_code.
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$language_code = apply_filters( 'wpml_current_language', null );

		$args = array(
			'element_id'    => $question_id,
			'element_type'  => 'post_question',
			'trid'          => false,
			'language_code' => $language_code,
		);

		// Set language details for the question.
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		do_action( 'wpml_set_element_language_details', $args );
	}
}
