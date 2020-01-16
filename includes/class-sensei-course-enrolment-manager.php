<?php
/**
 * File containing the class Sensei_Course_Enrolment_Manager.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the management of course enrolment.
 */
class Sensei_Course_Enrolment_Manager {
	/**
	 * All course enrolment providers.
	 *
	 * @var Sensei_Course_Enrolment_Provider_Interface[]
	 */
	private static $enrolment_providers;

	/**
	 * Gets the descriptive name of the provider by ID.
	 *
	 * @param string $provider_id Unique identifier of the enrolment provider.
	 *
	 * @return string|false
	 */
	public static function get_enrolment_provider_name_by_id( $provider_id ) {
		$provider = self::get_enrolment_provider_by_id( $provider_id );
		if ( ! $provider ) {
			return false;
		}

		$provider_class = get_class( $provider );

		return $provider_class::get_name();
	}

	/**
	 * Gets the enrolment provider object by its ID.
	 *
	 * @param string $provider_id Unique identifier of the enrolment provider.
	 *
	 * @return Sensei_Course_Enrolment_Provider_Interface|false
	 */
	public static function get_enrolment_provider_by_id( $provider_id ) {
		$all_providers = self::get_all_enrolment_providers();
		if ( ! isset( $all_providers[ $provider_id ] ) ) {
			return false;
		}

		return $all_providers[ $provider_id ];
	}

	/**
	 * Check if we should use the legacy enrolment check. Legacy check uses course
	 * progress to determine enrolment.
	 *
	 * @return bool
	 */
	public static function use_legacy_enrolment_check() {
		$use_legacy_enrolment_check = false;

		// Check if WCPC is around but not offering enrolment providers (an old version).
		if (
			class_exists( '\Sensei_WC_Paid_Courses\Sensei_WC_Paid_Courses' ) &&
			! class_exists( '\Sensei_WC_Paid_Courses\Course_Enrolment_Providers' )
		) {
			$use_legacy_enrolment_check = true;
		}

		return apply_filters( 'sensei_legacy_enrolment_check', $use_legacy_enrolment_check );
	}

	/**
	 * Get an array of all the instantiated course enrolment providers.
	 *
	 * @return Sensei_Course_Enrolment_Provider_Interface[]
	 */
	public static function get_all_enrolment_providers() {
		if ( ! isset( self::$enrolment_providers ) ) {
			self::$enrolment_providers = [];

			/**
			 * Fetch all registered course enrolment providers.
			 *
			 * @param string[] $provider_classes List of enrolment providers classes.
			 *
			 * @since 3.0.0
			 *
			 */
			$provider_classes = apply_filters( 'sensei_course_enrolment_providers', [] );
			foreach ( $provider_classes as $provider_class ) {
				if ( ! class_exists( $provider_class ) || ! is_a( $provider_class, 'Sensei_Course_Enrolment_Provider_Interface', true ) ) {
					continue;
				}

				self::$enrolment_providers[ $provider_class::get_id() ] = new $provider_class();
			}
		}

		return self::$enrolment_providers;
	}

	/**
	 * Trigger course enrolment check when enrolment might have changed.
	 *
	 * @param int $course_id Course post ID.
	 * @param int $user_id   User ID.
	 */
	public static function trigger_course_enrolment_check( $course_id, $user_id ) {
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		if ( $course_enrolment ) {
			$course_enrolment->is_enrolled( $user_id, false );
		}
	}
}
