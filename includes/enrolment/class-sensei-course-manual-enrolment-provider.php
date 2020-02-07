<?php
/**
 * File containing the class Sensei_Course_Manual_Enrolment_Provider.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Course enrolment provider for manually enrolling students.
 *
 * @since 3.0.0
 */
class Sensei_Course_Manual_Enrolment_Provider
	extends Sensei_Course_Enrolment_Stored_Status_Provider
	implements Sensei_Course_Enrolment_Provider_Interface {
	const META_PREFIX_LEGACY_MIGRATION = 'course-enrolment-manual-legacy-';

	/**
	 * Singleton instance.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Provides singleton instance of manual provider.
	 *
	 * @return Sensei_Course_Manual_Enrolment_Provider
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Sensei_Course_Manual_Enrolment_Provider constructor. Private so it can only be initialized internally.
	 */
	private function __construct() {}

	/**
	 * Gets the unique identifier of this enrolment provider.
	 *
	 * @return int
	 */
	public function get_id() {
		return 'manual';
	}

	/**
	 * Gets the descriptive name of the provider.
	 *
	 * @return string
	 */
	public function get_name() {
		return esc_html__( 'Manual', 'sensei-lms' );
	}

	/**
	 * Check if this course enrolment provider manages enrolment for a particular course.
	 *
	 * @param int $course_id Course post ID.
	 *
	 * @return bool
	 */
	public function handles_enrolment( $course_id ) {
		return true;
	}

	/**
	 * Check if this course enrolment provider is enroling a user to a course.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 *
	 * @return bool  `true` if this provider enrols the student and `false` if not.
	 */
	protected function get_initial_enrolment_status( $user_id, $course_id ) {
		if ( $this->needs_legacy_migration( $user_id, $course_id ) ) {
			return $this->get_legacy_enrolment( $user_id, $course_id );
		}

		return false;
	}

	/**
	 * Enrols a student manually in a course.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 *
	 * @return bool
	 */
	public function enrol_student( $user_id, $course_id ) {
		// Check if they are already manually enrolled.
		if ( $this->is_enrolled( $user_id, $course_id ) ) {
			return true;
		}

		$this->set_enrolment_status( $user_id, $course_id, true );
		Sensei_Course_Enrolment_Manager::trigger_course_enrolment_check( $user_id, $course_id );

		return $this->is_enrolled( $user_id, $course_id );
	}

	/**
	 * Withdraw manual enrolment for a student in a course.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 *
	 * @return bool
	 */
	public function withdraw_student( $user_id, $course_id ) {
		// Check if they aren't manually enrolled.
		if ( ! $this->is_enrolled( $user_id, $course_id ) ) {
			return true;
		}

		$this->set_enrolment_status( $user_id, $course_id, false );
		Sensei_Course_Enrolment_Manager::trigger_course_enrolment_check( $user_id, $course_id );

		return ! $this->is_enrolled( $user_id, $course_id );
	}

	/**
	 * Checks the legacy enrolment status during initial migration.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 *
	 * @return bool
	 */
	private function get_legacy_enrolment( $user_id, $course_id ) {
		$course_progress_comment_id = Sensei_Utils::get_course_progress_comment_id( $course_id, $user_id );

		$migration_log      = [];
		$is_legacy_enrolled = ! empty( $course_progress_comment_id );

		$migration_log['had_progress'] = $is_legacy_enrolled;

		/**
		 * Allows other providers to have an opinion about whether or not a user was enrolled pre-3.0.0.
		 * This should only be called once per user/course just after upgrading from a pre-3.0.0 version of Sensei.
		 *
		 * @since 3.0.0
		 *
		 * @param bool      $is_legacy_enrolled          If the user was actually enrolled before 3.0.0 migration.
		 * @param int       $user_id                     User ID.
		 * @param int       $course_id                   Course post ID.
		 * @param int|false $course_progress_comment_id  Comment ID for the course progress record (if it exists).
		 */
		$is_legacy_enrolled = apply_filters( 'sensei_is_legacy_enrolled', $is_legacy_enrolled, $user_id, $course_id, $course_progress_comment_id );

		$migration_log['is_enrolled'] = $is_legacy_enrolled;

		$this->set_migrated_legacy_enrolment_status( $user_id, $course_id, $migration_log );

		return $is_legacy_enrolled;
	}

	/**
	 * Check if a user's enrolment status needs to be migrated from pre-3.0.0 enrolment.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 *
	 * @return bool
	 */
	private function needs_legacy_migration( $user_id, $course_id ) {
		if ( ! get_option( 'sensei_enrolment_legacy' ) ) {
			return false;
		}

		return ! $this->has_migrated_legacy_enrolment( $user_id, $course_id );
	}

	/**
	 * Checks if we've migrated legacy enrolment status.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 *
	 * @return bool
	 */
	private function has_migrated_legacy_enrolment( $user_id, $course_id ) {
		$term          = Sensei_Learner::get_learner_term( $user_id );
		$migration_log = get_term_meta( $term->term_id, $this->get_legacy_migration_status_meta_key( $course_id ), true );

		return ! empty( $migration_log );
	}

	/**
	 * Update legacy migration status.
	 *
	 * @param int   $user_id       User ID.
	 * @param int   $course_id     Course post ID.
	 * @param array $migration_log Log of the migration.
	 */
	private function set_migrated_legacy_enrolment_status( $user_id, $course_id, $migration_log ) {
		$term = Sensei_Learner::get_learner_term( $user_id );
		update_term_meta( $term->term_id, $this->get_legacy_migration_status_meta_key( $course_id ), wp_json_encode( $migration_log ) );
	}

	/**
	 * Gets the meta key used to track legacy enrolment status migration.
	 *
	 * @param int $course_id Course post ID.
	 *
	 * @return string
	 */
	private function get_legacy_migration_status_meta_key( $course_id ) {
		return self::META_PREFIX_LEGACY_MIGRATION . $course_id;
	}

	/**
	 * Gets the version of the enrolment provider logic. If this changes, enrolment will be recalculated.
	 *
	 * @return int|string
	 */
	public function get_version() {
		// @todo change this to just increment an integer.
		return filemtime( __FILE__ );
	}
}
