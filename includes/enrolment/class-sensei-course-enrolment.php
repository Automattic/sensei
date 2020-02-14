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
 * Handles course enrolment logic for a particular course.
 */
class Sensei_Course_Enrolment {
	const META_PREFIX_ENROLMENT_RESULTS         = 'course-enrolment-';
	const META_PREFIX_ENROLMENT_PROVIDERS_STATE = 'sensei_enrolment_providers_state_';

	/**
	 * Courses instances.
	 *
	 * @var static[]
	 */
	private static $instances = [];

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
	 * Sets of enrolment provider states for different users.
	 *
	 * @var Sensei_Course_Enrolment_Provider_State_Set[]
	 */
	private $provider_state_sets = [];

	/**
	 * Sensei_Course_Enrolment constructor.
	 *
	 * @param int $course_id Course ID to handle checks for.
	 */
	private function __construct( $course_id ) {
		$this->course_id = $course_id;

		add_action( 'shutdown', [ $this, 'persist_state_sets' ] );
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
	 * Check if a user is enrolled in a course.
	 *
	 * @param int  $user_id     User ID.
	 * @param bool $check_cache Check and use cached result.
	 *
	 * @return bool
	 */
	public function is_enrolled( $user_id, $check_cache = true ) {
		// Users can only be enrolled in a published course.
		if ( 'publish' !== get_post_status( $this->course_id ) ) {
			return false;
		}

		try {
			if ( $check_cache ) {
				$enrolment_check_results = $this->get_enrolment_check_results( $user_id );
				if (
					$enrolment_check_results
					&& $enrolment_check_results->get_version_hash() === $this->get_course_enrolment_providers_version()
				) {
					return $this->has_stored_enrolment( $user_id );
				}
			}

			$enrolment_check_results = $this->query_enrolment_providers( $user_id );
			$is_enrolled             = $enrolment_check_results->is_enrolment_provided();

			$this->save_enrolment( $user_id, $is_enrolled );
		} catch ( Exception $e ) {
			$is_enrolled = false;
		}

		return $is_enrolled;
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
	 * @param bool $is_enrolled If the user is enrolled in the course.
	 *
	 * @return bool
	 * @throws Exception When learner term could not be created.
	 */
	private function save_enrolment( $user_id, $is_enrolled ) {
		$term = Sensei_Learner::get_learner_term( $user_id );
		if ( ! $is_enrolled ) {
			$result = wp_remove_object_terms( $this->course_id, [ intval( $term->term_id ) ], Sensei_PostTypes::LEARNER_TAXONOMY_NAME );

			return true === $result;
		}

		// If they are enrolled, make sure they have started the course.
		Sensei_Utils::user_start_course( $user_id, $this->course_id );

		$result = wp_set_post_terms( $this->course_id, [ intval( $term->term_id ) ], Sensei_PostTypes::LEARNER_TAXONOMY_NAME, true );

		return is_array( $result ) && ! empty( $result );
	}

	/**
	 * Get the enrolment check results for a user.
	 *
	 * @access private Used internally only.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return bool|Sensei_Course_Enrolment_Provider_Results
	 * @throws Exception When learner term could not be created.
	 */
	public function get_enrolment_check_results( $user_id ) {
		$enrolment_check_results = get_user_meta( $user_id, $this->get_enrolment_results_meta_key(), true );

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
		$provider_results = [];
		foreach ( $this->get_course_enrolment_providers() as $enrolment_provider_id => $enrolment_provider ) {
			$provider_results[ $enrolment_provider_id ] = $enrolment_provider->is_enrolled( $user_id, $this->course_id );
		}

		$enrolment_results = new Sensei_Course_Enrolment_Provider_Results( $provider_results, $this->get_course_enrolment_providers_version() );
		update_user_meta( $user_id, $this->get_enrolment_results_meta_key(), wp_slash( wp_json_encode( $enrolment_results ) ) );

		return $enrolment_results;
	}

	/**
	 * Get the enrolment results meta key.
	 *
	 * @return string
	 */
	private function get_enrolment_results_meta_key() {
		return self::META_PREFIX_ENROLMENT_RESULTS . $this->course_id;
	}

	/**
	 * Get a enrolment provider's state for a user.
	 *
	 * @access private Used internally only.
	 *
	 * @param Sensei_Course_Enrolment_Provider_Interface $provider Provider object.
	 * @param int                                        $user_id User ID.
	 *
	 * @return Sensei_Course_Enrolment_Provider_State
	 * @throws Exception When learner term could not be created.
	 */
	public function get_provider_state( Sensei_Course_Enrolment_Provider_Interface $provider, $user_id ) {
		if ( ! isset( $this->provider_state_sets[ $user_id ] ) ) {
			$provider_state_sets = get_user_meta( $user_id, $this->get_providers_state_meta_key(), true );

			if ( ! empty( $provider_state_sets ) ) {
				$provider_state_sets = Sensei_Course_Enrolment_Provider_State_Set::from_json( $provider_state_sets );
			}

			if ( empty( $provider_state_sets ) ) {
				$provider_state_sets = Sensei_Course_Enrolment_Provider_State_Set::create();
			}

			$this->provider_state_sets[ $user_id ] = $provider_state_sets;
		}

		return $this->provider_state_sets[ $user_id ]->get_provider_state( $provider );
	}

	/**
	 * Persist the state sets that have changed.
	 *
	 * @access private Used internally only.
	 *
	 * @return bool
	 * @throws Exception When learner term could not be created.
	 */
	public function persist_state_sets() {
		$success = true;

		foreach ( $this->provider_state_sets as $user_id => $state_set ) {
			if ( ! $state_set->get_has_changed() ) {
				continue;
			}

			$result = update_user_meta( $user_id, $this->get_providers_state_meta_key(), wp_slash( wp_json_encode( $state_set ) ) );

			if ( $result && ! is_wp_error( $result ) ) {
				$state_set->set_has_changed( false );
			} else {
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * Get the enrolment provider state meta key.
	 *
	 * @return string
	 */
	private function get_providers_state_meta_key() {
		return self::META_PREFIX_ENROLMENT_PROVIDERS_STATE . $this->course_id;
	}

	/**
	 * Get an array of all the enrolment providers that are handling this course's enrolment.
	 *
	 * @return Sensei_Course_Enrolment_Provider_Interface[]
	 */
	private function get_course_enrolment_providers() {
		if ( ! isset( $this->course_enrolment_providers ) ) {
			$this->course_enrolment_providers = [];

			$enrolment_manager   = Sensei_Course_Enrolment_Manager::instance();
			$enrolment_providers = $enrolment_manager->get_all_enrolment_providers();

			foreach ( $enrolment_providers as $id => $enrolment_provider ) {
				if ( $enrolment_provider->handles_enrolment( $this->course_id ) ) {
					$this->course_enrolment_providers[ $id ] = $enrolment_provider;
				}
			}
		}

		return $this->course_enrolment_providers;
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
			$versions[ $enrolment_provider_class ] = $enrolment_provider->get_version();
		}

		ksort( $versions );

		return md5( Sensei_Course_Enrolment_Manager::get_site_salt() . wp_json_encode( $versions ) );
	}
}
