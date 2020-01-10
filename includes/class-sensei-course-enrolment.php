<?php
/**
 * File containing the class Sensei_Course_Enrolment.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles course enrolment logic.
 */
class Sensei_Course_Enrolment {
	const META_PREFIX_ENROLMENT_RESULTS = 'course-enrolment-';

	/**
	 * Courses instances.
	 *
	 * @var static[]
	 */
	private static $instances = [];

	/**
	 * All course enrolment providers.
	 *
	 * @var Sensei_Course_Enrolment_Provider_Interface[]
	 */
	private static $enrolment_providers;

	/**
	 * Enrolment providers handling this particular course.
	 *
	 * @var Sensei_Course_Enrolment_Provider_Interface[]
	 */
	private $course_enrolment_providers;

	/**
	 * Course enrolment providers version hash.
	 *
	 * @var string
	 */
	private $course_enrolment_providers_version;

	/**
	 * Course ID for this enrolment object.
	 *
	 * @var int
	 */
	private $course_id;

	/**
	 * Sensei_Course_Enrolment constructor.
	 *
	 * @param int $course_id Course ID to handle checks for.
	 */
	private function __construct( $course_id ) {
		$this->course_id = $course_id;
	}

	/**
	 * Get instance for a particular course.
	 *
	 * @param int $course_id Course ID to handle checks for.
	 *
	 * @return self
	 */
	public static function get_course_instance( $course_id ) {
		if ( ! isset( self::$instances[ $course_id ] ) ) {
			self::$instances[ $course_id ] = new static( $course_id );
		}

		return self::$instances[ $course_id ];
	}

	/**
	 * Gets the course ID for this enrolment object.
	 *
	 * @return int
	 */
	public function get_course_id() {
		return $this->course_id;
	}

	/**
	 * Check if a user is enroled in a course.
	 *
	 * @param int  $user_id       User ID.
	 * @param bool $force_recheck Force a recalculation with all providers.
	 * @return bool
	 */
	public function is_enroled( $user_id, $force_recheck = false ) {
		try {
			if ( ! $force_recheck ) {
				$enrolment_check_results = $this->get_enrolment_check_results( $user_id );
				if (
					$enrolment_check_results
					&& $enrolment_check_results->get_version() === $this->get_course_enrolment_providers_version()
				) {
					return $this->has_stored_enrolment( $user_id );
				}
			}

			$enrolment_check_results = $this->query_enrolment_providers( $user_id );
			$is_enroled              = $enrolment_check_results->is_enrolment_provided();

			// @todo Method will always be bool when we add manual enrolment provider. Remove this and the `get_legacy_enrolment_status` temporary method.
			if ( ! is_bool( $is_enroled ) ) {
				$is_enroled = $this->get_legacy_enrolment_status( $user_id );
			}

			$this->save_enrolment( $user_id, $is_enroled );
		} catch ( Exception $e ) {
			$is_enroled = false;
		}

		return $is_enroled;
	}

	/**
	 * Trigger course enrolment check when enrolment might have changed.
	 *
	 * @param int $user_id User ID.
	 */
	public function trigger_course_enrolment_check( $user_id ) {
		$this->is_enroled( $user_id, true );
	}

	/**
	 * Get enrolment from taxonomy record.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 * @throws Exception When learner term could not be created.
	 */
	private function has_stored_enrolment( $user_id ) {
		$term = Sensei_Learner::get_learner_term( $user_id );

		return has_term( $term->term_id, Sensei_PostTypes::LEARNER_TAXONOMY_NAME, $this->course_id );
	}

	/**
	 * Save enrolment in taxonomy.
	 *
	 * @param int  $user_id    User ID.
	 * @param bool $is_enroled If the user is enroled in the course.
	 *
	 * @return bool
	 * @throws Exception When learner term could not be created.
	 */
	private function save_enrolment( $user_id, $is_enroled ) {
		$term = Sensei_Learner::get_learner_term( $user_id );
		if ( ! $is_enroled ) {
			$result = wp_remove_object_terms( $this->course_id, [ intval( $term->term_id ) ], Sensei_PostTypes::LEARNER_TAXONOMY_NAME );

			return true === $result;
		}

		// If they are enroled, make sure they have started the course.
		Sensei_Utils::start_user_on_course( $user_id, $this->course_id );

		$result = wp_set_post_terms( $this->course_id, [ intval( $term->term_id ) ], Sensei_PostTypes::LEARNER_TAXONOMY_NAME, true );

		return is_array( $result ) && ! empty( $result );
	}

	/**
	 * Get the enrolment check results for a user.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return bool|Sensei_Course_Enrolment_Provider_Results
	 * @throws Exception When learner term could not be created.
	 */
	private function get_enrolment_check_results( $user_id ) {
		$term                    = Sensei_Learner::get_learner_term( $user_id );
		$enrolment_check_results = get_term_meta( $term->term_id, $this->get_course_results_meta_key(), true );

		if ( empty( $enrolment_check_results ) ) {
			return false;
		}

		return Sensei_Course_Enrolment_Provider_Results::from_json( $enrolment_check_results );
	}

	/**
	 * Builds a new enrolment results record by checking with enrolment providers.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return Sensei_Course_Enrolment_Provider_Results
	 * @throws Exception When learner term could not be created.
	 */
	private function query_enrolment_providers( $user_id ) {
		$term             = Sensei_Learner::get_learner_term( $user_id );
		$provider_results = [];
		foreach ( $this->get_course_enrolment_providers() as $enrolment_provider_id => $enrolment_provider ) {
			$provider_results[ $enrolment_provider_id ] = $enrolment_provider->is_enroled( $user_id, $this->course_id );
		}

		$enrolment_results = new Sensei_Course_Enrolment_Provider_Results( $provider_results, $this->get_course_enrolment_providers_version() );
		update_term_meta( $term->term_id, $this->get_course_results_meta_key(), wp_slash( wp_json_encode( $enrolment_results ) ) );

		return $enrolment_results;
	}

	/**
	 * Provides the legacy enrolment status.
	 *
	 * @todo This is just a temporary method until we add a manual enrolment provider.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return bool
	 */
	private function get_legacy_enrolment_status( $user_id ) {
		// No enrolment provider has explicitly provided enrolment to the user. Use Sensei's default.
		return Sensei_Utils::has_started_course( $this->course_id, $user_id );
	}

	/**
	 * Get the course log meta key.
	 *
	 * @return string
	 */
	private function get_course_results_meta_key() {
		return self::META_PREFIX_ENROLMENT_RESULTS . $this->course_id;
	}

	/**
	 * Get an array of all the enrolment providers that are handling this course's enrolment.
	 *
	 * @return Sensei_Course_Enrolment_Provider_Interface[]
	 */
	private function get_course_enrolment_providers() {
		if ( ! isset( $this->course_enrolment_providers ) ) {
			$this->course_enrolment_providers = [];

			foreach ( self::get_all_enrolment_providers() as $id => $enrolment_provider ) {
				if ( $enrolment_provider->handles_enrolment( $this->course_id ) ) {
					$this->course_enrolment_providers[ $id ] = $enrolment_provider;
				}
			}
		}

		return $this->course_enrolment_providers;
	}

	/**
	 * Get an array of all the instantiated course enrolment providers.
	 *
	 * @return Sensei_Course_Enrolment_Provider_Interface[]
	 */
	private static function get_all_enrolment_providers() {
		if ( ! isset( self::$enrolment_providers ) ) {
			self::$enrolment_providers = [];

			/**
			 * Fetch all registered course enrolment providers.
			 *
			 * @since 3.0.0
			 *
			 * @param string[] $provider_classes List of enrolment providers classes.
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
	 * Get the hash of the current versions of all course enrolment providers.
	 *
	 * @return string
	 */
	public function get_course_enrolment_providers_version() {
		if ( ! isset( $this->course_enrolment_providers_version ) ) {
			$enrolment_providers                      = $this->get_course_enrolment_providers();
			$this->course_enrolment_providers_version = self::hash_course_enrolment_provider_versions( $enrolment_providers );
		}

		return $this->course_enrolment_providers_version;
	}

	/**
	 * Generates a hash of all the enrolment provider versions.
	 *
	 * @param Sensei_Course_Enrolment_Provider_Interface[] $enrolment_providers Array of enrolment provider objects.
	 *
	 * @return string
	 */
	private static function hash_course_enrolment_provider_versions( $enrolment_providers ) {
		$versions = [];
		foreach ( $enrolment_providers as $enrolment_provider ) {
			if ( ! ( $enrolment_provider instanceof Sensei_Course_Enrolment_Provider_Interface ) ) {
				continue;
			}

			$enrolment_provider_class              = get_class( $enrolment_provider );
			$versions[ $enrolment_provider_class ] = $enrolment_provider_class::get_version();
		}

		ksort( $versions );

		return md5( wp_json_encode( $versions ) );
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
}
