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
 *
 * Each course/user combination has its own results record (`Sensei_Course_Enrolment_Provider_Results`)
 * stored in user meta. There are several ways in which result records can be invalidated so that
 * enrolment providers are asked to recalculate.
 *
 * - The user meta record can be set to an empty string. This marks it as invalid and needing recalculation. One
 *   common way this will happen is by a provider calling `\Sensei_Course_Enrolment_Manager::trigger_course_enrolment_check`
 *   to trigger the recalculation.
 * - The version hash for the record could be changed. The version hash is made up of three components. If
 *   any single component changes, the record will be recalculated. The three components are:
 *   - Site wide hash: If this changes, every enrolment result record is invalidated.
 *   - Course hash: If this changes for a course, every enrolment result related to that specific course is invalidated.
 *   - Hash of provider versions: If an update occurs that includes changed logic for one of the enrolment providers,
 *     all enrolment results are invalidated.
 */
class Sensei_Course_Enrolment {
	const META_PREFIX_ENROLMENT_RESULTS = 'sensei_course_enrolment_';
	const META_COURSE_ENROLMENT_VERSION = '_course_enrolment_version';
	const META_REMOVED_LEARNERS         = 'sensei_removed_learners';

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
	 * Course ID for this enrolment object.
	 *
	 * @var int
	 */
	private $course_id;

	/**
	 * An array of removed learners from the course.
	 *
	 * @var array {
	 *     @type string $date  Timestamp of when the the learner was removed.
	 * }
	 */
	private $removed_learners;

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
	 * Check if a user is enrolled in a course.
	 *
	 * @param int  $user_id     User ID.
	 * @param bool $check_cache Check and use cached result.
	 *
	 * @return bool
	 */
	public function is_enrolled( $user_id, $check_cache = true ) {
		if ( ! $user_id ) {
			return false;
		}

		/**
		 * Allow complete side-stepping of enrolment handling in Sensei.
		 *
		 * This will have some other side-effects. For example, if using learner queries (My Courses,
		 * Learner Profiles, etc), you will have to save the learner term and association by using the
		 * `\Sensei_Course_Enrolment::save_enrolment` method. Additionally, manual enrolment handling
		 * in Learner Management will not have any effect.
		 *
		 * @since 3.0.0
		 *
		 * @param bool|null $is_enrolled If a boolean, that value will be used. Null values will keep default behavior.
		 * @param int       $user_id     User ID.
		 * @param int       $course_id   Course post ID.
		 * @param bool      $check_cache Advise hooked method if cached values should be trusted.
		 */
		$is_enrolled = apply_filters( 'sensei_is_enrolled', null, $user_id, $this->course_id, $check_cache );
		if ( null !== $is_enrolled ) {
			return $is_enrolled;
		}

		// User is not enrolled if the course is not published or he is removed.
		if ( 'publish' !== get_post_status( $this->course_id ) || $this->is_learner_removed( $user_id ) ) {
			return false;
		}

		try {
			if ( $check_cache ) {
				$enrolment_check_results = $this->get_enrolment_check_results( $user_id );
				if (
					$enrolment_check_results
					&& $enrolment_check_results->get_version_hash() === $this->get_current_enrolment_result_version()
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
	 * Marks all enrolment results as invalid for a course and enqueues an async job to recalculate.
	 *
	 * This will still cause a delay when users visit My Courses or another page that relies on the term.
	 * We aren't invalidating the entire user for this.
	 *
	 * @return Sensei_Enrolment_Course_Calculation_Job|null
	 */
	public function recalculate_enrolment() {
		$this->reset_course_enrolment_salt();
		$job_scheduler = Sensei_Enrolment_Job_Scheduler::instance();

		return $job_scheduler->start_course_calculation_job( $this->course_id );
	}

	/**
	 * Invalidate a single learner/course enrolment result.
	 *
	 * Note: this could still cause a delay when users visit My Courses or another page that relies on the term.
	 * We aren't invalidating the entire user for this.
	 *
	 * @param int $user_id User ID.
	 */
	public function invalidate_learner_result( $user_id ) {
		update_user_meta( $user_id, $this->get_enrolment_results_meta_key(), '' );
	}

	/**
	 * Get the IDs for the enrolled users.
	 *
	 * @param array $args Additional arguments to pass to `WP_Term_Query`. Useful for pagination.
	 *
	 * @return int[]
	 */
	public function get_enrolled_user_ids( $args = [] ) {
		$args['fields'] = 'names';

		$learner_terms = wp_get_object_terms( $this->course_id, Sensei_PostTypes::LEARNER_TAXONOMY_NAME, $args );

		// This only happens if we asked for terms too early (before init).
		if ( is_wp_error( $learner_terms ) ) {
			return [];
		}

		return array_map( [ 'Sensei_learner', 'get_learner_id' ], $learner_terms );
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
	 * @param int  $user_id     User ID.
	 * @param bool $is_enrolled If the user is enrolled in the course.
	 *
	 * @return bool
	 * @throws Exception When learner term could not be created.
	 */
	public function save_enrolment( $user_id, $is_enrolled ) {
		$term = Sensei_Learner::get_learner_term( $user_id );

		$is_enrolled_current = has_term( $term->term_id, Sensei_PostTypes::LEARNER_TAXONOMY_NAME, $this->course_id );

		// Nothing has changed.
		if ( $is_enrolled_current === $is_enrolled ) {
			return true;
		}

		if ( ! $is_enrolled ) {
			$result = true === wp_remove_object_terms( $this->course_id, [ intval( $term->term_id ) ], Sensei_PostTypes::LEARNER_TAXONOMY_NAME );
		} else {
			// If they are enrolled, make sure they have started the course.
			Sensei_Utils::user_start_course( $user_id, $this->course_id );

			$save_result = wp_set_post_terms( $this->course_id, [ intval( $term->term_id ) ], Sensei_PostTypes::LEARNER_TAXONOMY_NAME, true );
			$result      = is_array( $save_result ) && ! empty( $save_result );
		}

		if ( ! $result ) {
			return false;
		}

		/**
		 * Fire action when course enrolment status changes.
		 *
		 * @since 3.0.0
		 *
		 * @param int  $user_id     User ID.
		 * @param int  $course_id   Course post ID.
		 * @param bool $is_enrolled New enrolment status.
		 */
		do_action( 'sensei_course_enrolment_status_changed', $user_id, $this->course_id, $is_enrolled );

		return true;
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

		$enrolment_results = new Sensei_Course_Enrolment_Provider_Results( $provider_results, $this->get_current_enrolment_result_version() );

		$this->store_enrolment_results( $user_id, $enrolment_results );

		Sensei_Enrolment_Provider_Journal_Store::register_possible_enrolment_change(
			$enrolment_results,
			$user_id,
			$this->course_id
		);

		/**
		 * Notify upon calculation of enrolment results.
		 *
		 * @since 3.0.0
		 *
		 * @param Sensei_Course_Enrolment_Provider_Results $enrolment_results Enrolment results object.
		 * @param int                                      $course_id         Course post ID.
		 * @param int                                      $user_id           User ID.
		 */
		do_action( 'sensei_enrolment_results_calculated', $enrolment_results, $this->course_id, $user_id );

		return $enrolment_results;
	}

	/**
	 * Store the enrolment results in user meta.
	 *
	 * @param int                                      $user_id           User ID.
	 * @param Sensei_Course_Enrolment_Provider_Results $enrolment_results Enrolment results object.
	 */
	private function store_enrolment_results( $user_id, Sensei_Course_Enrolment_Provider_Results $enrolment_results ) {
		$results_meta_key   = $this->get_enrolment_results_meta_key();
		$had_existing_value = ! empty( get_user_meta( $user_id, $results_meta_key, true ) );

		/**
		 * Filter on whether course enrolment results should be stored.
		 *
		 * @since 3.0.0
		 *
		 * @param bool                                     $store_results      Whether to store the results.
		 * @param int                                      $user_id            User ID.
		 * @param int                                      $course_id          Course post ID.
		 * @param bool                                     $had_existing_value True if a stale enrolment result is already stored.
		 * @param Sensei_Course_Enrolment_Provider_Results $enrolment_results  Enrolment results object.
		 */
		$store_results = apply_filters( 'sensei_course_enrolment_store_results', true, $user_id, $this->course_id, $had_existing_value, $enrolment_results );

		if ( $store_results ) {
			update_user_meta( $user_id, $results_meta_key, wp_slash( wp_json_encode( $enrolment_results ) ) );
		} elseif ( $had_existing_value ) {
			// This will only occur if the filter returns something other than the default.
			delete_user_meta( $user_id, $results_meta_key );
		}
	}

	/**
	 * Helper to disable storing enrolment results when it isn't already stored and enrolment isn't provided.
	 *
	 * @param bool                                     $store_results      Whether to store the results.
	 * @param int                                      $user_id            User ID.
	 * @param int                                      $course_id          Course post ID.
	 * @param bool                                     $had_existing_value True if a stale enrolment result is already stored.
	 * @param Sensei_Course_Enrolment_Provider_Results $enrolment_results  Enrolment results object.
	 *
	 * @return bool
	 */
	public static function do_not_store_negative_enrolment_results( $store_results, $user_id, $course_id, $had_existing_value, $enrolment_results ) {
		return $had_existing_value || $enrolment_results->is_enrolment_provided();
	}

	/**
	 * Get the enrolment results meta key.
	 *
	 * @return string
	 */
	public function get_enrolment_results_meta_key() {
		global $wpdb;

		return $wpdb->get_blog_prefix() . self::META_PREFIX_ENROLMENT_RESULTS . $this->course_id;
	}

	/**
	 * Get a enrolment provider's state for a user.
	 *
	 * @param Sensei_Course_Enrolment_Provider_Interface $provider Provider object.
	 * @param int                                        $user_id User ID.
	 *
	 * @return Sensei_Enrolment_Provider_State
	 * @throws Exception When learner term could not be created.
	 */
	public function get_provider_state( Sensei_Course_Enrolment_Provider_Interface $provider, $user_id ) {
		return Sensei_Enrolment_Provider_State_Store::get( $user_id )->get_provider_state( $provider, $this->course_id );
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
	 * Get the version hash that current enrolment results should be at.
	 *
	 * @return string
	 */
	public function get_current_enrolment_result_version() {
		$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();

		$hash_components   = [];
		$hash_components[] = $enrolment_manager->get_site_salt();
		$hash_components[] = $enrolment_manager->get_enrolment_provider_versions_hash();
		$hash_components[] = $this->get_course_enrolment_salt();

		return md5( implode( '-', $hash_components ) );
	}

	/**
	 * Gets the course salt that can be used to invalidate all course enrolments.
	 *
	 * @return string
	 */
	public function get_course_enrolment_salt() {
		$course_salt = get_post_meta( $this->course_id, self::META_COURSE_ENROLMENT_VERSION, true );

		if ( ! $course_salt ) {
			return $this->reset_course_enrolment_salt();
		}

		return $course_salt;
	}

	/**
	 * Resets the course salt. If already set, this will invalidate all enrolment results for the current course.
	 *
	 * @return string
	 */
	public function reset_course_enrolment_salt() {
		$new_salt = md5( uniqid() );

		update_post_meta( $this->course_id, self::META_COURSE_ENROLMENT_VERSION, $new_salt );

		return $new_salt;
	}

	/**
	 * Remove learner from the course, overriding the providers rule.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return boolean Success flag.
	 */
	public function remove_learner( $user_id ) {
		$removed_learners = $this->get_removed_learners();

		if ( isset( $removed_learners[ $user_id ] ) ) {
			return false;
		}

		$removed_learners[ $user_id ] = [ 'date' => time() ];

		$this->save_enrolment( $user_id, false );

		return $this->update_removed_learners( $removed_learners );
	}

	/**
	 * Restore removed learner enrolment, giving the control back to the providers.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return boolean Success flag.
	 */
	public function restore_learner( $user_id ) {
		$removed_learners = $this->get_removed_learners();

		unset( $removed_learners[ $user_id ] );

		return $this->update_removed_learners( $removed_learners );
	}

	/**
	 * Check if the user is removed.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return boolean Whether the learner is removed.
	 */
	public function is_learner_removed( $user_id ) {
		$removed_learners = $this->get_removed_learners();

		return array_key_exists( $user_id, $removed_learners );
	}

	/**
	 * Get removed learners meta.
	 *
	 * @return array Removed learners array.
	 */
	private function get_removed_learners() {
		if ( isset( $this->removed_learners ) ) {
			return $this->removed_learners;
		}

		$removed_learners_json = get_post_meta( $this->course_id, self::META_REMOVED_LEARNERS, true );

		if ( empty( $removed_learners_json ) ) {
			$this->removed_learners = [];
		} else {
			$removed_learners = json_decode( $removed_learners_json, true );

			if ( ! $removed_learners ) {
				$this->removed_learners = [];
			} else {
				$this->removed_learners = $removed_learners;
			}
		}

		return $this->removed_learners;
	}

	/**
	 * Update removed learners meta.
	 *
	 * @param array $removed_learners Removed learners array.
	 *
	 * @return bool Whether it was updated.
	 */
	private function update_removed_learners( $removed_learners ) {
		$result = update_post_meta( $this->course_id, self::META_REMOVED_LEARNERS, wp_json_encode( $removed_learners ) );

		if ( $result ) {
			$this->removed_learners = $removed_learners;
			return true;
		}

		return false;
	}

	/**
	 * Withdraw learner. It removes the manual enrolment and/or remove the learner,
	 * depending on the user enrollment situation.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return boolean If user is withdrawn.
	 */
	public function withdraw( $user_id ) {
		$enrolment_manager         = Sensei_Course_Enrolment_Manager::instance();
		$manual_enrolment_provider = $enrolment_manager->get_manual_enrolment_provider();

		if ( $manual_enrolment_provider instanceof Sensei_Course_Manual_Enrolment_Provider ) {
			$manual_enrolment_provider->withdraw_learner( $user_id, $this->course_id );
		}

		if ( ! $this->is_enrolled( $user_id, false ) ) {
			return true;
		}

		// If user is still enrolled for some reason, remove them.
		$this->remove_learner( $user_id );

		return ! $this->is_enrolled( $user_id, false );
	}

	/**
	 * Enroll learner. It restore a learner, if they are enrolled through a provider,
	 * otherwise, give them a manually enrollment.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return boolean If user is enrolled.
	 */
	public function enrol( $user_id ) {
		// If user is removed, just restore.
		if ( $this->is_learner_removed( $user_id ) ) {
			$this->restore_learner( $user_id );
		}

		if ( $this->is_enrolled( $user_id, false ) ) {
			return true;
		}

		// If user isn't still enrolled, enroll manually.
		$enrolment_manager         = Sensei_Course_Enrolment_Manager::instance();
		$manual_enrolment_provider = $enrolment_manager->get_manual_enrolment_provider();

		if ( ! ( $manual_enrolment_provider instanceof Sensei_Course_Manual_Enrolment_Provider ) ) {
			return false;
		}

		return $manual_enrolment_provider->enrol_learner( $user_id, $this->course_id );
	}
}
