<?php
/**
 * File containing \Sensei\WPML\Course_Progress class.
 *
 * @package sensei
 */

namespace Sensei\WPML;

use Sensei_Course_Enrolment_Manager;
use Sensei_PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Course_Progress
 *
 * Compatibility code with WPML.
 *
 * @since 4.23.1
 *
 * @internal
 */
class Course_Progress {
	use Progress_Query_Helper;
	use WPML_API;

	/**
	 * Init hooks.
	 */
	public function init() {
		add_filter( 'sensei_course_is_user_enrolled_course_id', array( $this, 'translate_course_id' ) );
		add_filter( 'sensei_block_take_course_course_id', array( $this, 'translate_course_id' ) );
		add_filter( 'sensei_course_progress_create_course_id', array( $this, 'translate_course_id' ) );
		add_filter( 'sensei_course_progress_get_course_id', array( $this, 'translate_course_id' ) );
		add_filter( 'sensei_course_progress_has_course_id', array( $this, 'translate_course_id' ) );
		add_filter( 'sensei_course_progress_delete_for_course_course_id', array( $this, 'translate_course_id' ) );
		add_filter( 'sensei_course_progress_find_course_id', array( $this, 'translate_course_id' ) );
		add_filter( 'sensei_lesson_progress_count_course_id', array( $this, 'translate_course_id' ) );
		add_filter( 'sensei_course_start_course_id', array( $this, 'translate_course_id' ) );
		add_filter( 'sensei_learner_get_course_ids_by_progress_status_course_ids', array( $this, 'translate_course_ids' ) );
		add_filter( 'sensei_learner_get_enrolled_courses_query_args_term_id', array( $this, 'translate_term_id' ) );
		// The student management screens query course progress with the ID of the course they show.
		add_filter( 'sensei_check_for_activity_args', array( $this, 'translate_course_query_args' ) );

		add_action( 'sensei_manual_enrolment_learner_enrolled', array( $this, 'enrol_learner' ), 10, 2 );
		add_action( 'sensei_manual_enrolment_learner_withdrawn', array( $this, 'withdraw_learner' ), 10, 2 );
	}

	/**
	 * Enroll learner.
	 *
	 * @since 4.23.1
	 *
	 * @internal
	 *
	 * @param int $user_id User ID.
	 * @param int $course_id Course ID.
	 */
	public function enrol_learner( $user_id, $course_id ) {
		remove_action( 'sensei_manual_enrolment_learner_enrolled', array( $this, 'enrol_learner' ) );
		remove_filter( 'sensei_course_is_user_enrolled_course_id', array( $this, 'translate_course_id' ) );

		$enrolment_manager         = Sensei_Course_Enrolment_Manager::instance();
		$manual_enrolment_provider = $enrolment_manager->get_manual_enrolment_provider();
		if ( $manual_enrolment_provider ) {
			$course_translations = $this->get_element_translations( $course_id, 'post_course' );
			foreach ( $course_translations as $course_translation ) {
				$course_id = $course_translation->element_id;
				$manual_enrolment_provider->enrol_learner( $user_id, $course_id );
			}
		}

		add_action( 'sensei_manual_enrolment_learner_enrolled', array( $this, 'enrol_learner' ), 10, 2 );
		add_filter( 'sensei_course_is_user_enrolled_course_id', array( $this, 'translate_course_id' ) );
	}

	/**
	 * Withdraw learner.
	 *
	 * @since 4.23.1
	 *
	 * @internal
	 *
	 * @param int $user_id User ID.
	 * @param int $course_id Course ID.
	 */
	public function withdraw_learner( $user_id, $course_id ) {
		remove_action( 'sensei_manual_enrolment_learner_withdrawn', array( $this, 'withdraw_learner' ) );
		remove_filter( 'sensei_course_is_user_enrolled_course_id', array( $this, 'translate_course_id' ) );

		$enrolment_manager         = Sensei_Course_Enrolment_Manager::instance();
		$manual_enrolment_provider = $enrolment_manager->get_manual_enrolment_provider();
		if ( $manual_enrolment_provider ) {
			$course_translations = $this->get_element_translations( $course_id, 'post_course' );
			foreach ( $course_translations as $course_translation ) {
				$course_id = $course_translation->element_id;
				$manual_enrolment_provider->withdraw_learner( $user_id, $course_id );
			}
		}

		add_action( 'sensei_manual_enrolment_learner_withdrawn', array( $this, 'withdraw_learner' ), 10, 2 );
		add_filter( 'sensei_course_is_user_enrolled_course_id', array( $this, 'translate_course_id' ) );
	}

	/**
	 * Point the course IDs of a progress query at the original language.
	 *
	 * Progress is stored against the original language, but the admin screens
	 * query it with the ID of the course they are showing, which in a secondary
	 * language is the translation, so the query finds nothing.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 *
	 * @param mixed $args Query arguments.
	 * @return mixed
	 */
	public function translate_course_query_args( $args ) {
		return $this->translate_query_post_ids( $args, 'course', array( $this, 'translate_course_id' ) );
	}

	/**
	 * Translate course ID.
	 *
	 * @since 4.23.1
	 *
	 * @internal
	 *
	 * @param int $course_id Course ID.
	 * @return int
	 */
	public function translate_course_id( $course_id ): int {
		$course_id = (int) $course_id;
		$details   = $this->get_element_language_details( $course_id, 'course' );

		$original_language_code = $details['source_language_code'] ?? $details['language_code'] ?? null;

		return $this->get_object_id( $course_id, 'course', true, $original_language_code );
	}

	/**
	 * Translate course IDs.
	 *
	 * @since 4.23.1
	 *
	 * @internal
	 *
	 * @param array $course_ids Course IDs.
	 * @return array
	 */
	public function translate_course_ids( array $course_ids ): array {
		foreach ( $course_ids as $key => $course_id ) {
			$course_ids[ $key ] = $this->get_object_id( $course_id, 'course', true );
		}

		return $course_ids;
	}

	/**
	 * Translate term ID.
	 *
	 * @since 4.23.1
	 *
	 * @internal
	 *
	 * @param int $term_id Term ID.
	 * @return int
	 */
	public function translate_term_id( $term_id ): int {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		return (int) apply_filters( 'wpml_object_id', $term_id, Sensei_PostTypes::LEARNER_TAXONOMY_NAME, true );
	}
}
