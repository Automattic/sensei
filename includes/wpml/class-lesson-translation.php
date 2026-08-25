<?php
/**
 * File containing the \Sensei\WPML\Lesson_Translation class.
 *
 * @package sensei
 */

namespace Sensei\WPML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Lesson_Translation
 *
 * Compatibility code with WPML.
 *
 * @since 4.22.0
 *
 * @internal
 */
class Lesson_Translation {
	use Lesson_Translation_Helper;
	use Quiz_Translation_Helper;
	use Question_Translation_Helper;
	use WPML_API;

	/**
	 * Init hooks.
	 */
	public function init() {
		// Update lesson properties on lesson translation created in UI.
		add_action( 'wpml_pro_translation_completed', array( $this, 'update_lesson_translations_on_lesson_translation_created' ) );
		// Run the deferred question sync when WPML writes the translated lesson content.
		add_action( 'wp_after_insert_post', array( $this, 'update_question_translations_on_lesson_content_written' ), 10, 2 );
		// Attach lesson duplicates to the translated course.
		// "icl_make_duplicate" is WPML-internal but the WPML team confirmed it's safe to rely on.
		// It also fires on duplicate refresh, so the handler must stay idempotent.
		add_action( 'icl_make_duplicate', array( $this, 'update_lesson_properties_on_lesson_duplicated' ), 10, 4 );
	}

	/**
	 * Update lesson properties on lesson translation created.
	 *
	 * @since 4.22.0
	 *
	 * @internal
	 *
	 * @param int $new_lesson_id New lesson ID.
	 */
	public function update_lesson_translations_on_lesson_translation_created( $new_lesson_id ) {
		if ( 'lesson' !== get_post_type( $new_lesson_id ) ) {
			return;
		}

		$details = $this->get_element_language_details( $new_lesson_id, 'lesson' );
		if ( empty( $details ) ) {
			return;
		}

		if ( empty( $details['source_language_code'] ) ) {
			return;
		}

		$master_lesson_id = $this->get_object_id( $new_lesson_id, 'lesson', false, $details['source_language_code'] );
		if ( empty( $master_lesson_id ) || $master_lesson_id === $new_lesson_id ) {
			return;
		}

		$this->update_translated_lesson_properties( $new_lesson_id, $master_lesson_id );
		$this->update_quiz_translations( $master_lesson_id, $details['language_code'] );
		$this->defer_question_translations_update( $new_lesson_id );
	}

	/**
	 * Attach a duplicated lesson to the translated course.
	 *
	 * WPML's duplicate flow copies custom fields verbatim, which leaves the
	 * duplicate attached to the master course and carrying the master course's
	 * order meta key, so it never shows up in the translated course.
	 *
	 * @since 4.26.3
	 *
	 * @internal
	 *
	 * @param int    $master_post_id Master lesson ID.
	 * @param string $lang           Language code of the duplicate.
	 * @param array  $post_array     Data of the duplicated post (unused).
	 * @param int    $new_lesson_id  Duplicated lesson ID.
	 */
	public function update_lesson_properties_on_lesson_duplicated( $master_post_id, $lang, $post_array, $new_lesson_id ) {
		if ( 'lesson' !== get_post_type( $new_lesson_id ) ) {
			return;
		}

		$master_course_id = (int) get_post_meta( $master_post_id, '_lesson_course', true );
		if ( ! $master_course_id ) {
			return;
		}

		$new_course_id = $this->get_object_id( $master_course_id, 'course', false, $lang );
		if ( ! $new_course_id || $new_course_id === $master_course_id ) {
			return;
		}

		// The order meta copied from the master belongs to the master course.
		delete_post_meta( $new_lesson_id, '_order_' . $master_course_id );

		$this->update_lesson_course( $new_lesson_id, $new_course_id );
		$this->update_translated_lesson_properties( $new_lesson_id, $master_post_id );
	}
}
