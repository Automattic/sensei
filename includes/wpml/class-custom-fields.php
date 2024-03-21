<?php
/**
 * File containing the \Sensei\WPML\Custom_Fields class.
 *
 * @package sensei
 */

namespace Sensei\WPML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Custom_Fields
 *
 * Compatibility code with WPML.
 *
 * @since 4.22.0
 *
 * @internal
 */
class Custom_Fields {

	/**
	 * Init hooks.
	 */
	public function init() {
		add_filter( 'wpml_sync_custom_field_copied_value', array( $this, 'update_lesson_course_before_copied' ), 10, 4 );
		add_filter( 'wpml_sync_custom_field_copied_value', array( $this, 'update_course_prerequisite_before_copied' ), 10, 4 );
		add_filter( 'wpml_sync_custom_field_copied_value', array( $this, 'update_quiz_id_before_copied' ), 10, 4 );
	}

	/**
	 * Update course prerequisite before copied.
	 *
	 * @since 4.22.0
	 *
	 * @internal
	 *
	 * @param mixed  $copied_value Copied value.
	 * @param int    $post_id_from Post ID from.
	 * @param int    $post_id_to   Post ID to.
	 * @param string $meta_key     Meta key.
	 * @return mixed
	 */
	public function update_course_prerequisite_before_copied( $copied_value, $post_id_from, $post_id_to, $meta_key ) {
		if ( '_course_prerequisite' !== $meta_key ) {
			return $copied_value;
		}

		if ( empty( $copied_value ) ) {
			return $copied_value;
		}

		$course_id = (int) $copied_value;

		$target_language_code = apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			'wpml_element_language_code',
			null,
			array(
				'element_id'   => $post_id_to,
				'element_type' => 'course',
			)
		);

		if ( ! $target_language_code ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$target_language_code = apply_filters( 'wpml_current_language', null );
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		return apply_filters( 'wpml_object_id', $course_id, 'course', false, $target_language_code );
	}

	/**
	 * Update lesson course before copied.
	 *
	 * @since 4.22.0
	 *
	 * @internal
	 *
	 * @param mixed  $copied_value Copied value.
	 * @param int    $post_id_from Post ID from.
	 * @param int    $post_id_to   Post ID to.
	 * @param string $meta_key     Meta key.
	 * @return mixed
	 */
	public function update_lesson_course_before_copied( $copied_value, $post_id_from, $post_id_to, $meta_key ) {
		if ( '_lesson_course' !== $meta_key ) {
			return $copied_value;
		}

		if ( empty( $copied_value ) ) {
			return $copied_value;
		}

		$course_id = (int) $copied_value;

		$target_language_code = apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			'wpml_element_language_code',
			null,
			array(
				'element_id'   => $post_id_to,
				'element_type' => 'lesson',
			)
		);

		if ( ! $target_language_code ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$target_language_code = apply_filters( 'wpml_current_language', null );
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		return apply_filters( 'wpml_object_id', $course_id, 'course', false, $target_language_code );
	}

	/**
	 * Update quiz id for a question before copied.
	 *
	 * @since 4.22.0
	 *
	 * @internal
	 *
	 * @param mixed  $copied_value Copied value.
	 * @param int    $post_id_from Post ID from.
	 * @param int    $post_id_to   Post ID to.
	 * @param string $meta_key     Meta key.
	 * @return mixed
	 */
	public function update_quiz_id_before_copied( $copied_value, $post_id_from, $post_id_to, $meta_key ) {
		if ( '_quiz_id' !== $meta_key ) {
			return $copied_value;
		}

		if ( empty( $copied_value ) ) {
			return $copied_value;
		}

		$quiz_id = (int) $copied_value;

		// Get the post type. Might be a question or a multiple choice question.
		$post_type = get_post_type( $post_id_to );
		if ( ! in_array( $post_type, array( 'question', 'multiple_question' ), true ) ) {
			return $copied_value;
		}

		$target_language_code = apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			'wpml_element_language_code',
			null,
			array(
				'element_id'   => $post_id_to,
				'element_type' => $post_type,
			)
		);

		if ( ! $target_language_code ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$target_language_code = apply_filters( 'wpml_current_language', null );
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		return apply_filters( 'wpml_object_id', $quiz_id, 'quiz', false, $target_language_code );
	}
}
