<?php
/**
 * File with trait Sensei_Course_Enrolment_Manual_Test_Helpers.
 *
 * @package sensei-tests
 */

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Using PHPUnit conventions.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helpers for course enrolment related tests.
 *
 * @since 3.0.0
 */
trait Sensei_Course_Enrolment_Manual_Test_Helpers {

	/**
	 * Checks to see if user's legacy enrolment was checked.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 *
	 * @return bool
	 */
	private function wasLegacyEnrolmentChecked( $user_id, $course_id ) {
		$learner_term            = Sensei_Learner::get_learner_term( $user_id );
		$enrolment_migration_log = get_term_meta( $learner_term->term_id, Sensei_Course_Manual_Enrolment_Provider::META_PREFIX_LEGACY_MIGRATION . $course_id, true );

		return ! empty( $enrolment_migration_log );
	}

	/**
	 * Adds enrolment for student but does not trigger recaculation.
	 * Simulates `Sensei_Course_Manual_Enrolment_Provider::add_student_enrolment`.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 */
	private function directlyEnrolStudent( $user_id, $course_id ) {
		$term = Sensei_Learner::get_learner_term( $user_id );
		update_term_meta( $term->term_id, Sensei_Course_Manual_Enrolment_Provider::META_PREFIX_MANUAL_STATUS . $course_id, time() );

		Sensei_Utils::user_start_course( $user_id, $course_id );
	}

	/**
	 * Manually withdraw a student in a course.
	 * Simulates `Sensei_Course_Manual_Enrolment_Provider::remove_student_enrolment`.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 */
	private function directlyWithdrawStudent( $user_id, $course_id ) {
		$term = Sensei_Learner::get_learner_term( $user_id );
		delete_term_meta( $term->term_id, Sensei_Course_Manual_Enrolment_Provider::META_PREFIX_MANUAL_STATUS . $course_id );
	}

	/**
	 * Manually enrol student in course.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 *
	 * @return bool
	 */
	private function manuallyEnrolStudentInCourse( $user_id, $course_id ) {
		$manual_provider = $this->getManualEnrolmentProvider();
		if ( ! $manual_provider ) {
			return false;
		}

		return $manual_provider->enrol_student( $user_id, $course_id );
	}

	/**
	 * Start a student on a course and simulate a legacy enrolment.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 *
	 * @return bool
	 */
	private function legacyEnrolStudentStartCourseProgress( $user_id, $course_id ) {
		return false !== Sensei_Utils::user_start_course( $user_id, $course_id );
	}

	/**
	 * Gets the manual enrolment manager.
	 *
	 * @return false|Sensei_Course_Manual_Enrolment_Provider
	 */
	private function getManualEnrolmentProvider() {
		return Sensei_Course_Enrolment_Manager::instance()->get_enrolment_provider_by_id( Sensei_Course_Manual_Enrolment_Provider::get_id() );
	}

	/**
	 * Simulates upgrading from Sensei 2.x to Sensei 3.x by setting the site-wide enrolment flag (`sensei_enrolment_legacy`).
	 */
	private static function simulateUpgradingFromSensei2ToSensei3() {
		update_option( 'sensei_enrolment_legacy', time() );
	}

	/**
	 * Deletes the side-wide flag for legacy enrolment migration.
	 */
	private static function resetSideWideLegacyEnrolmentFlag() {
		delete_option( 'sensei_enrolment_legacy' );
	}

	/**
	 * Resets all legacy filters.
	 */
	private static function resetLegacyFilters() {
		remove_all_filters( 'sensei_is_legacy_enrolled' );
	}

	/**
	 * Resets the course enrolment providers.
	 */
	private static function resetCourseEnrolmentProviders() {
		$course_enrolment_instances = new ReflectionProperty( Sensei_Course_Enrolment::class, 'instances' );
		$course_enrolment_instances->setAccessible( true );
		$course_enrolment_instances->setValue( [] );
	}

	/**
	 * Resets the course enrolment manager. Do not do this in production. This is just to reset state in tests.
	 */
	private static function resetCourseEnrolmentManager() {
		$enrolment_providers = new ReflectionProperty( Sensei_Course_Enrolment_Manager::class, 'enrolment_providers' );
		$enrolment_providers->setAccessible( true );
		$enrolment_providers->setValue( Sensei_Course_Enrolment_Manager::instance(), null );

		Sensei_Course_Enrolment_Manager::instance()->collect_enrolment_providers();
	}
}
