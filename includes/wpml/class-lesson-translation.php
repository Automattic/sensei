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
		add_action( 'wpml_pro_translation_completed', array( $this, 'update_lesson_translations_on_lesson_translation_created' ), 10, 1 );
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
		$this->update_quiz_translations( $master_lesson_id );
		$this->update_question_translations_from_lesson( $new_lesson_id );
	}
}
