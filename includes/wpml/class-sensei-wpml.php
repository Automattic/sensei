<?php
/**
 * File containing the Sensei_WPML class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_WPML
 */
class Sensei_WPML {
	/**
	 * Sensei_WPML constructor.
	 */
	public function __construct() {
		add_action( 'sensei_before_mail', array( $this, 'sensei_before_mail' ) );
		add_action( 'sensei_after_sending_email', array( $this, 'sensei_after_sending_email' ) );
		add_action( 'sensei_course_structure_lesson_created', array( $this, 'set_language_details_when_lesson_created' ), 10, 2 );
		add_action( 'sensei_course_structure_quiz_created', array( $this, 'set_language_details_when_quiz_created' ), 10, 2 );

		add_filter( 'wpml_sync_custom_field_copied_value', array( $this, 'update_lesson_course_before_copied' ), 10, 4 );
		add_filter( 'wpml_sync_custom_field_copied_value', array( $this, 'update_course_prerequisite_before_copied' ), 10, 4 );

		// Create translations for lessons and update lesson properties on course translation created.
		add_action( 'wpml_pro_translation_completed', array( $this, 'update_lesson_properties_on_course_translation_created' ), 10, 1 );
		// Update lesson properties on lesson translation created in UI.
		add_action( 'wpml_pro_translation_completed', array( $this, 'update_lesson_properties_on_lesson_translation_created' ), 10, 1 );
	}

	/**
	 * Switch language for email.
	 *
	 * @param string $email_address Recipient's email address.
	 */
	public function sensei_before_mail( $email_address ) {
		/**
		* Switch language for email
		*
		* Allows WPML to switch current language to one preferred by email recipient.
		* WPML checks language set in user preferences and applies it for email
		* string localisation
		* It runs before any email string is obtained and localised
		*
		* @since 1.9.7
		*
		* @param string  $email_address Recipient's email address
		*/
		do_action( 'wpml_switch_language_for_email', $email_address ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Restore language after sending email.
	 */
	public function sensei_after_sending_email() {
		/**
		* Restore language after sending email
		*
		* Allows WPML to switch language to the last one before switching with
		* action 'wpml_switch_language_for_email'
		* It runs just after wp_mail() call
		* No params
		*
		* @since 1.9.7
		*/
		do_action( 'wpml_restore_language_from_email' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
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

		// Set language details for the lesson.
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		do_action( 'wpml_set_element_language_details', $args );
	}

	/**
	 * Update course prerequisite before copied.
	 *
	 * @since $$next-version$$
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
	 * @since $$next-version$$
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
	 * Save lessons fields on course translation created.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 *
	 * @param int $new_course_id New course ID.
	 */
	public function update_lesson_properties_on_course_translation_created( $new_course_id ) {
		if ( 'course' !== get_post_type( $new_course_id ) ) {
			return;
		}

		$details = apply_filters(
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

		$details = (array) $details;
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

				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				$translations = apply_filters( 'wpml_post_duplicates', $lesson_id );
				foreach ( $translations as $translated_lesson_id ) {
					$this->update_lesson_course( (int) $translated_lesson_id, $new_course_id );
					$this->update_translated_lesson_taxonomies( (int) $translated_lesson_id, $lesson_id );
				}
			}

			// Sync lesson course field across translations.
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			do_action( 'wpml_sync_custom_field', $lesson_id, '_lesson_course' );
		}
	}

	/**
	 * Update lesson properties on lesson translation created.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 *
	 * @param int $new_lesson_id New lesson ID.
	 */
	public function update_lesson_properties_on_lesson_translation_created( $new_lesson_id ) {
		if ( 'lesson' !== get_post_type( $new_lesson_id ) ) {
			return;
		}

		$this->update_translated_lesson_taxonomies( $new_lesson_id );
	}

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
		$details = apply_filters(
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

		$details = (array) $details;
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
