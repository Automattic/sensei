<?php
/**
 * File containing the \Sensei\WPML\Lesson_Translation_Helper trait.
 *
 * @package sensei
 */

namespace Sensei\WPML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait Lesson_Translation_Helper
 *
 * @since 4.22.0
 *
 * @internal
 */
trait Lesson_Translation_Helper {
	use WPML_API;

	/**
	 * Update lesson course meta field.
	 *
	 * @param int $new_lesson_id New lesson ID.
	 * @param int $new_course_id New course ID.
	 */
	private function update_lesson_course( $new_lesson_id, $new_course_id ) {
		update_post_meta( $new_lesson_id, '_lesson_course', $new_course_id );
	}

	/**
	 * Update lesson taxonomies for a translated lesson.
	 *
	 * @param int      $new_lesson_id    New lesson ID.
	 * @param int|null $master_lesson_id Original lesson ID.
	 */
	private function update_translated_lesson_properties( $new_lesson_id, $master_lesson_id = null ) {
		$details = $this->get_element_language_details( $new_lesson_id, 'lesson' );
		if ( empty( $details ) ) {
			return;
		}

		if ( empty( $master_lesson_id ) ) {
			if ( empty( $details['source_language_code'] ) ) {
				return;
			}

			$master_lesson_id = $this->get_object_id( $new_lesson_id, 'lesson', false, $details['source_language_code'] );
			if ( empty( $master_lesson_id ) || $master_lesson_id === $new_lesson_id ) {
				return;
			}

			// Sync lesson course field across translations if possible.
			// Does not work for lessons created with `wpml_post_duplicates` filter.
			$this->sync_custom_field( $master_lesson_id, '_lesson_course' );
		}

		$this->set_lesson_order( $master_lesson_id, $details );
		$this->set_module_taxonomies( $new_lesson_id, $master_lesson_id, $details );
	}

	/**
	 * Update lesson module taxonomies for a translated lesson.
	 *
	 * @param int   $new_lesson_id New lesson ID.
	 * @param int   $master_lesson_id Original lesson ID.
	 * @param array $details Language details.
	 */
	private function set_module_taxonomies( $new_lesson_id, $master_lesson_id, $details ) {
		$terms = wp_get_object_terms( $master_lesson_id, 'module', array( 'fields' => 'ids' ) );
		if ( empty( $terms ) || is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return;
		}

		$new_terms = array();
		foreach ( $terms as $term_id ) {
			$term_id = (int) $term_id;

			$new_term = $this->get_object_id( $term_id, 'module', false, $details['language_code'] );
			delete_post_meta( $new_lesson_id, '_order_module_' . intval( $term_id ) );

			$order = get_post_meta( $master_lesson_id, '_order_module_' . intval( $term_id ), true );
			update_post_meta( $new_lesson_id, '_order_module_' . intval( $new_term ), $order );
			$new_terms[] = $new_term;
		}

		wp_set_object_terms( $new_lesson_id, $new_terms, 'module' );
	}

	/**
	 * Set the lesson order for the master lesson's course translation.
	 *
	 * @param int   $master_lesson_id Original lesson ID.
	 * @param array $details Language details.
	 */
	private function set_lesson_order( $master_lesson_id, $details ) {
		$master_course_id = (int) get_post_meta( $master_lesson_id, '_lesson_course', true );
		if ( ! $master_course_id ) {
			return;
		}

		$new_course_id = $this->get_object_id( $master_course_id, 'course', false, $details['language_code'] );
		if ( ! $new_course_id ) {
			return;
		}

		$this->sync_course_lesson_order( $master_course_id, $new_course_id, $details['language_code'] );
	}

	/**
	 * Mirror the master course's lesson order onto the translated course.
	 *
	 * Copying the order meta only to the lesson being translated is not enough:
	 * lessons translated before this sync existed (or through flows that never
	 * ran it, like the WordPress editor or WPML duplicates) have no
	 * `_order_{course_id}` meta and fall back to the end of the course, so a
	 * newly translated lesson would jump in front of all of them. Rewriting the
	 * order of every lesson keeps the translated course consistent with the
	 * master regardless of how each translation was created.
	 *
	 * @param int    $master_course_id Original course ID.
	 * @param int    $new_course_id    Translated course ID.
	 * @param string $language_code    Language of the translated course.
	 */
	private function sync_course_lesson_order( $master_course_id, $new_course_id, $language_code ) {
		// Translations of the master course's lessons, in the master's order.
		$lesson_ids = array();
		foreach ( Sensei()->course->course_lessons( $master_course_id, 'any', 'ids' ) as $master_lesson_id ) {
			$translated_lesson_id = $this->get_object_id( (int) $master_lesson_id, 'lesson', false, $language_code );
			if ( $translated_lesson_id && $translated_lesson_id !== (int) $master_lesson_id ) {
				$lesson_ids[] = (int) $translated_lesson_id;
			}
		}

		// Lessons of the translated course with no counterpart in the master
		// course keep their current relative order, after the mirrored ones.
		foreach ( Sensei()->course->course_lessons( $new_course_id, 'any', 'ids' ) as $translated_lesson_id ) {
			$lesson_ids[] = (int) $translated_lesson_id;
		}

		foreach ( array_values( array_unique( $lesson_ids ) ) as $index => $lesson_id ) {
			update_post_meta( $lesson_id, '_order_' . $new_course_id, $index + 1 );
		}
	}
}
