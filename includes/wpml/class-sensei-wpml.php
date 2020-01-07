<?php
class Sensei_WPML {
	public function __construct() {
		add_action( 'sensei_before_mail', array( $this, 'sensei_before_mail' ) );
		add_action( 'sensei_after_sending_email', array( $this, 'sensei_after_sending_email' ) );

		add_action( 'init', array( $this, 'init_hook' ), 13 );
	}

	/**
	 * Method to run on the init action.
	 *
	 * @return void
	 */
	public function init_hook() {
		/**
		 * Replicate user progress for all languages hook.
		 *
		 * This can affect other hooks behavior. The following actions will run for
		 * all translation content throught WPML:
		 * sensei_course_status_updated, sensei_lesson_status_updated,
		 * sensei_user_lesson_end and sensei_started_lesson_completed.
		 *
		 * @since 2.3.0
		 *
		 * @param bool $replicate_progress If progress should be replicated for all languages.
		 */
		if ( apply_filters( 'sensei_wpml_replicate_user_progress_for_all_languages', false ) ) {
			add_action( 'sensei_course_status_updated', array( $this, 'sensei_course_status_updated' ), 10, 6 );
			add_action( 'sensei_lesson_status_updated', array( $this, 'sensei_lesson_status_updated' ), 10, 6 );
			add_action( 'sensei_started_lesson_completed', array( $this, 'sensei_started_lesson_completed' ), 10, 5 );
		}
	}

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
		do_action( 'wpml_switch_language_for_email', $email_address );
	}

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
		do_action( 'wpml_restore_language_from_email' );
	}

	/**
	 * Replicate course status for all languages.
	 *
	 * @param string $status
	 * @param int    $user_id
	 * @param int    $course_id
	 * @param array  $comment_id
	 * @param array  $metadata
	 * @param bool   $replicating_lang Flag if the status is being replicated for another language.
	 * @return void
	 */
	public function sensei_course_status_updated( $status, $user_id, $course_id, $comment_id, $metadata, $replicating_lang ) {
		// Prevent to replicate the replication.
		if ( $replicating_lang ) {
			return;
		}

		// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$trid                 = apply_filters( 'wpml_element_trid', null, $course_id );
		$element_translations = apply_filters( 'wpml_get_element_translations', [], $trid );
		// phpcs:enable

		foreach ( $element_translations as $item ) {
			// Skip the original update.
			if ( (int) $item->element_id === (int) $course_id ) {
				continue;
			}

			Sensei_Utils::update_course_status( $user_id, $item->element_id, $status, $metadata, true );
		}
	}

	/**
	 * Start the lesson for the user on all languages.
	 *
	 * @param string $status
	 * @param int    $user_id
	 * @param int    $lesson_id
	 * @param array  $comment_id
	 * @param array  $metadata
	 * @param bool   $replicating_lang Flag if the content is being replicated for another language.
	 * @return void
	 */
	public function sensei_lesson_status_updated( $status, $user_id, $lesson_id, $comment_id, $metadata, $replicating_lang ) {
		if ( $replicating_lang ) {
			return;
		}

		// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$trid                 = apply_filters( 'wpml_element_trid', null, $lesson_id );
		$element_translations = apply_filters( 'wpml_get_element_translations', [], $trid );
		// phpcs:enable

		foreach ( $element_translations as $item ) {
			// Skip the original update.
			if ( (int) $item->element_id === (int) $lesson_id ) {
				continue;
			}

			Sensei_Utils::update_lesson_status( $user_id, $item->element_id, $status, $metadata, true );
		}
	}

	/**
	 * Replicate lesson status for all languages.
	 *
	 * @param int    $lesson_id
	 * @param int    $user_id
	 * @param string $status
	 * @param bool   $complete
	 * @param bool   $replicating_lang Flag if the status is being replicated for another language.
	 * @return void
	 */
	public function sensei_started_lesson_completed( $lesson_id, $user_id, $status, $complete, $replicating_lang ) {
		// Prevent to replicate the replication.
		if ( $replicating_lang ) {
			return;
		}

		// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$trid                 = apply_filters( 'wpml_element_trid', null, $lesson_id );
		$element_translations = apply_filters( 'wpml_get_element_translations', [], $trid );
		// phpcs:enable

		global $sitepress;
		// Fix for the Sensei_Utils::sensei_check_for_activity receive the comments from all languages.
		remove_filter( 'comments_clauses', array( $sitepress, 'comments_clauses' ) );
		foreach ( $element_translations as $item ) {
			// Skip the original update.
			if ( (int) $item->element_id === (int) $lesson_id ) {
				continue;
			}

			Sensei_Utils::complete_lesson( $item->element_id, $user_id, $status, $complete, true );
		}
		// Re-enable the filter.
		add_filter( 'comments_clauses', array( $sitepress, 'comments_clauses' ), 10, 2 );
	}
}
