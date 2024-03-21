<?php
/**
 * File containing the \Sensei\WPML\Course_Translation class.
 *
 * @package sensei
 */

namespace Sensei\WPML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Course_Translation
 *
 * Compatibility code with WPML.
 *
 * @since 4.22.0
 *
 * @internal
 */
class Course_Translation {

	use Lesson_Translation_Helper;
	use Quiz_Translation_Helper;

	/**
	 * Init hooks.
	 */
	public function init() {
		// Create translations for lessons and update lesson properties on course translation created.
		add_action( 'wpml_pro_translation_completed', array( $this, 'update_lesson_properties_on_course_translation_created' ), 10, 1 );
	}

	/**
	 * Save lessons fields on course translation created.
	 *
	 * @since 4.22.0
	 *
	 * @internal
	 *
	 * @param int $new_course_id New course ID.
	 */
	public function update_lesson_properties_on_course_translation_created( $new_course_id ) {
		if ( 'course' !== get_post_type( $new_course_id ) ) {
			return;
		}

		$details = (array) apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			'wpml_element_language_details',
			null,
			array(
				'element_id'   => $new_course_id,
				'element_type' => 'course',
			)
		);
		if ( empty( $details ) ) {
			return;
		}

		if ( empty( $details['source_language_code'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$master_id = apply_filters( 'wpml_object_id', $new_course_id, 'course', false, $details['source_language_code'] );
		if ( empty( $master_id ) || $master_id === $new_course_id ) {
			return;
		}

		$lesson_ids = Sensei()->course->course_lessons( $master_id, 'any', 'ids' );
		foreach ( $lesson_ids as $lesson_id ) {
			if ( ! is_int( $lesson_id ) ) {
				$lesson_id = (int) $lesson_id;
			}

			// Create translatons if they don't exist.
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$is_translated = apply_filters( 'wpml_element_has_translations', '', $lesson_id, 'lesson' );
			if ( ! $is_translated ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				do_action( 'wpml_admin_make_post_duplicates', $lesson_id );

			}

			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$translations = apply_filters( 'wpml_post_duplicates', $lesson_id );
			foreach ( $translations as $translated_lesson_id ) {
				$this->update_lesson_course( (int) $translated_lesson_id, $new_course_id );
				$this->update_translated_lesson_properties( (int) $translated_lesson_id, $lesson_id );
			}

			$this->update_quiz_translations( $lesson_id );

			// Sync lesson course field across translations.
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			do_action( 'wpml_sync_custom_field', $lesson_id, '_lesson_course' );
		}
	}
}
