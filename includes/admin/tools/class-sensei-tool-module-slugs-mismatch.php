<?php
/**
 * File containing Sensei_Tool_Module_Slugs_Mismatch class.
 *
 * @package sensei-lms
 * @since 3.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Tool_Module_Slugs_Mismatch class.
 *
 * @since 3.7.0
 */
class Sensei_Tool_Module_Slugs_Mismatch implements Sensei_Tool_Interface {
	/**
	 * Get the ID of the tool.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'module-slugs-mismatch';
	}

	/**
	 * Get the name of the tool.
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Module slugs mismatch', 'sensei-lms' );
	}

	/**
	 * Get the description of the tool.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Fix module slugs that do not match the module name.', 'sensei-lms' );
	}

	/**
	 * Run the tool.
	 */
	public function process() {
		$terms   = $this->get_module_terms();
		$updated = [];
		$errors  = [];

		foreach ( $terms as $key => $term ) {
			$correct_slug = $this->get_correct_slug( $term );

			if ( $correct_slug !== $term->slug ) {
				$change_result = wp_update_term(
					$term->term_id,
					'module',
					[
						'slug' => $correct_slug,
					]
				);

				if ( is_wp_error( $change_result ) ) {
					$errors[] = $term->term_id;
				} else {
					$updated[] = $term->term_id;
				}
			}
		}

		$message = $this->get_feedback_message( $updated, $errors );

		Sensei_Tools::instance()->add_user_message( $message, ! empty( $errors ) );
	}

	/**
	 * Get module terms.
	 *
	 * @return \WP_Term[] Module terms.
	 */
	private function get_module_terms() {
		remove_filter( 'get_terms', array( Sensei()->modules, 'append_teacher_name_to_module' ), 70 );
		$terms = get_terms(
			[
				'hide_empty' => false,
				'taxonomy'   => 'module',
			]
		);
		add_filter( 'get_terms', array( Sensei()->modules, 'append_teacher_name_to_module' ), 70, 3 );

		return $terms;
	}

	/**
	 * Get correct slug.
	 *
	 * @param \WP_Term $term Module term.
	 *
	 * @return string Correct slug.
	 */
	private function get_correct_slug( $term ) {
		$sanitized_title = sanitize_title( $term->name );

		return preg_replace( '/(\d+-)?(.*)/', '$1' . $sanitized_title, $term->slug, 1 );
	}

	/**
	 * Get feedback message.
	 *
	 * @param int[] $updated ID of the terms that were updated.
	 * @param int[] $errors  ID of the terms that had error.
	 *
	 * @return string Feedback message.
	 */
	private function get_feedback_message( $updated, $errors ) {
		$message = '';

		if ( ! empty( $updated ) ) {
			$message .= sprintf(
				// translators: %1$s is the IDs of the updated terms.
				__( 'Module slugs were updated in the terms with IDs: %1$s.', 'sensei-lms' ),
				implode( ', ', $updated )
			);
		}

		if ( ! empty( $errors ) ) {
			$message .= ' ' . sprintf(
				// translators: %1$s is terms with error on update.
				__( 'Errors happened while updating slugs for the terms with IDs: %1$s.', 'sensei-lms' ),
				implode( ', ', $errors )
			);
		}

		if ( empty( $message ) ) {
			$message .= ' ' . __( 'There were no slugs mismatch.', 'sensei-lms' );
		}

		return $message;
	}

	/**
	 * Is the tool currently available?
	 *
	 * @return bool True if tool is available.
	 */
	public function is_available() {
		return true;
	}
}
