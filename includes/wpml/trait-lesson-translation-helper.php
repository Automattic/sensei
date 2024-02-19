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
 * @since $$next-version$$
 *
 * @internal
 */
trait Lesson_Translation_Helper {
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
	private function update_translated_lesson_taxonomies( $new_lesson_id, $master_lesson_id = null ) {
		$details = (array) apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			'wpml_element_language_details',
			null,
			array(
				'element_id'   => $new_lesson_id,
				'element_type' => 'lesson',
			)
		);

		if ( empty( $details ) ) {
			return;
		}

		if ( empty( $master_lesson_id ) ) {
			if ( empty( $details['source_language_code'] ) ) {
				return;
			}

			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$master_lesson_id = apply_filters( 'wpml_object_id', $new_lesson_id, 'lesson', false, $details['source_language_code'] );
			if ( empty( $master_lesson_id ) || $master_lesson_id === $new_lesson_id ) {
				return;
			}

			// Sync lesson course field across translations if possible.
			// Does not work for lessons created with `wpml_post_duplicates` filter.
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			do_action( 'wpml_sync_custom_field', $master_lesson_id, '_lesson_course' );
		}

		// Update lesson taxonomies.
		$terms = wp_get_object_terms( $master_lesson_id, 'module', array( 'fields' => 'ids' ) );
		if ( empty( $terms ) || is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return;
		}

		$new_terms = array();
		foreach ( $terms as $term_id ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$new_term = apply_filters( 'wpml_object_id', $term_id, 'module', false, $details['language_code'] );
			delete_post_meta( $new_lesson_id, '_order_module_' . intval( $term_id ) );

			$order = get_post_meta( $master_lesson_id, '_order_module_' . intval( $term_id ), true );
			update_post_meta( $new_lesson_id, '_order_module_' . intval( $new_term ), $order );
			$new_terms[] = $new_term;
		}

		wp_set_object_terms( $new_lesson_id, $new_terms, 'module' );
	}
}
