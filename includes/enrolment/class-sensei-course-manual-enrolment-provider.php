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
 * Course enrolment provider for manually enrolling learners.
 *
 * @since 3.0.0
 */
class Sensei_Course_Manual_Enrolment_Provider
	extends Sensei_Course_Enrolment_Stored_Status_Provider
	implements Sensei_Course_Enrolment_Provider_Interface, Sensei_Course_Enrolment_Provider_Debug_Interface {
	const DATA_KEY_LEGACY_MIGRATION = 'legacy_manual';

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
	 * Check if this course enrolment provider is enrolling a user to a course.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 *
	 * @return bool  `true` if this provider enrols the learner and `false` if not.
	 */
	protected function get_initial_enrolment_status( $user_id, $course_id ) {
		if ( $this->needs_legacy_migration( $user_id, $course_id ) ) {
			return $this->get_legacy_enrolment( $user_id, $course_id );
		}

		return false;
	}

	/**
	 * Enrols a learner manually in a course.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 *
	 * @return bool
	 */
	public function enrol_learner( $user_id, $course_id ) {
		// Check if they are already manually enrolled.
		if ( $this->is_enrolled( $user_id, $course_id ) ) {
			return true;
		}

		$this->set_enrolment_status( $user_id, $course_id, true );
		Sensei_Course_Enrolment_Manager::trigger_course_enrolment_check( $user_id, $course_id );

		if ( ! $this->is_enrolled( $user_id, $course_id ) ) {
			return false;
		}

		/**
		 * Fire action when a learner is provided with manual enrolment.
		 *
		 * @since 3.0.0
		 *
		 * @param int $user_id   User ID.
		 * @param int $course_id Course post ID.
		 */
		do_action( 'sensei_manual_enrolment_learner_enrolled', $user_id, $course_id );

		return true;
	}

	/**
	 * Withdraw manual enrolment for a learner in a course.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 *
	 * @return bool
	 */
	public function withdraw_learner( $user_id, $course_id ) {
		// Check if they aren't manually enrolled.
		if ( ! $this->is_enrolled( $user_id, $course_id ) ) {
			return true;
		}

		$this->set_enrolment_status( $user_id, $course_id, false );
		Sensei_Course_Enrolment_Manager::trigger_course_enrolment_check( $user_id, $course_id );

		if ( $this->is_enrolled( $user_id, $course_id ) ) {
			return false;
		}

		/**
		 * Fire action when a learner's manual enrolment is withdrawn.
		 *
		 * @since 3.0.0
		 *
		 * @param int $user_id   User ID.
		 * @param int $course_id Course post ID.
		 */
		do_action( 'sensei_manual_enrolment_learner_withdrawn', $user_id, $course_id );

		return true;
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

		/**
		 * Allows other providers to have an opinion about whether or not a user was enrolled pre-3.0.0.
		 * This should only be called once per user/course just after upgrading from a pre-3.0.0 version of Sensei.
		 *
		 * Note: This will only allow manual enrolment to not be given. It won't be called for learners without course
		 * progress.
		 *
		 * @since 3.0.0
		 *
		 * @param bool      $is_legacy_enrolled          If the user was actually enrolled before 3.0.0 migration.
		 * @param int       $user_id                     User ID.
		 * @param int       $course_id                   Course post ID.
		 * @param int|false $course_progress_comment_id  Comment ID for the course progress record (if it exists).
		 */
		$is_legacy_enrolled = apply_filters( 'sensei_is_legacy_enrolled', true, $user_id, $course_id, $course_progress_comment_id );
		$this->set_migrated_legacy_enrolment_status( $user_id, $course_id, $is_legacy_enrolled );

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

		// We only migrate people who had course progress.
		$course_progress_comment_id = Sensei_Utils::get_course_progress_comment_id( $course_id, $user_id );
		if ( ! $course_progress_comment_id ) {
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
		$course_enrolment    = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$provider_state      = $course_enrolment->get_provider_state( $this, $user_id );
		$legacy_manual_value = $provider_state->get_stored_value( self::DATA_KEY_LEGACY_MIGRATION );

		return null !== $legacy_manual_value;
	}

	/**
	 * Update legacy migration status.
	 *
	 * @param int  $user_id                 User ID.
	 * @param int  $course_id               Course post ID.
	 * @param bool $legacy_enrolment_status Value of legacy enrolment status.
	 */
	private function set_migrated_legacy_enrolment_status( $user_id, $course_id, $legacy_enrolment_status ) {
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$provider_state   = $course_enrolment->get_provider_state( $this, $user_id );

		$provider_state->set_stored_value( self::DATA_KEY_LEGACY_MIGRATION, $legacy_enrolment_status );
		$provider_state->save();
	}

	/**
	 * Provide debugging information about a user's enrolment in a course.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 *
	 * @return string[] Array of human readable debug messages. Allowed HTML tags: a[href]; strong; em; span[style,class]
	 */
	public function debug( $user_id, $course_id ) {
		$messages = [];

		$course_enrolment        = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$provider_state          = $course_enrolment->get_provider_state( $this, $user_id );
		$legacy_migration_status = $provider_state->get_stored_value( self::DATA_KEY_LEGACY_MIGRATION );

		if ( null === $legacy_migration_status ) {
			$messages[] = __( 'Learner manual enrollment <strong>was not migrated</strong> from a legacy version of Sensei LMS.', 'sensei-lms' );

			if ( false !== get_option( 'sensei_enrolment_legacy' ) ) {
				$messages[] = __( 'Learner <strong>did not have</strong> course progress at the time of manual enrollment migration.', 'sensei-lms' );
			}
		} else {
			$messages[] = __( 'Learner <strong>did have</strong> course progress at the time of manual enrollment migration.', 'sensei-lms' );

			if ( false === $legacy_migration_status ) {
				$messages[] = __( 'Manual enrollment <strong>was not provided</strong> to the learner on legacy migration.', 'sensei-lms' );
			} else {
				$messages[] = __( 'Manual enrollment <strong>was provided</strong> to the learner on legacy migration.', 'sensei-lms' );
			}
		}

		return $messages;
	}

	/**
	 * Gets the version of the enrolment provider logic. If this changes, enrolment will be recalculated.
	 *
	 * This version should be bumped to the next stable Sensei LMS version whenever this provider is modified.
	 *
	 * @return int|string
	 */
	public function get_version() {
		return '3.0.0';
	}
}
