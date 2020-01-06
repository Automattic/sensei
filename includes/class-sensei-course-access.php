<?php
/**
 * File containing the class Sensei_Course_Access.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles course access logic.
 */
class Sensei_Course_Access {
	const META_PREFIX_COURSE_LOG = 'course-access-check-';

	/**
	 * Courses instances.
	 *
	 * @var static[]
	 */
	private static $instances = [];

	/**
	 * Course access providers.
	 *
	 * @var Sensei_Course_Access_Provider_Interface[]
	 */
	private static $access_providers;

	/**
	 * Course access providers version hash.
	 *
	 * @var string
	 */
	private static $access_providers_version;

	/**
	 * Course ID to handle access checks for.
	 *
	 * @var int
	 */
	private $course_id;

	/**
	 * Get singleton instance.
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
	 * Sensei_Course_Access constructor.
	 *
	 * @param int $course_id Course ID to handle checks for.
	 */
	private function __construct( $course_id ) {
		$this->course_id = $course_id;
	}

	/**
	 * Return if a user has access.
	 *
	 * @param int  $user_id       User ID.
	 * @param bool $force_recheck Force a recalculation of access.
	 * @return bool
	 */
	public function has_access( $user_id, $force_recheck = false ) {
		try {
			if ( ! $force_recheck ) {
				$access_check_log = $this->get_access_check_log( $user_id );
				if (
					$access_check_log
					&& $access_check_log->get_version() === $this->get_course_access_providers_version()
				) {
					return $this->get_stored_access( $user_id );
				}
			}

			$access_check_log = $this->build_access_log( $user_id );
			$has_access       = $access_check_log->has_access();
			if ( ! is_bool( $has_access ) ) {
				$has_access = $this->get_default_access( $user_id );
			}

			$this->save_access( $user_id, $has_access );
		} catch ( Exception $e ) {
			$has_access = false;
		}

		return $has_access;
	}

	/**
	 * Trigger course access check when access might have changed.
	 *
	 * @param int $user_id User ID.
	 */
	public function trigger_course_access_check( $user_id ) {
		$this->has_access( $user_id, true );
	}

	/**
	 * Get access from taxonomy record.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 * @throws Exception When learner term could not be created.
	 */
	private function get_stored_access( $user_id ) {
		$term = Sensei_Learner::get_learner_term( $user_id );

		return has_term( $term->term_id, Sensei_PostTypes::LEARNER_TAXONOMY_NAME, $this->course_id );
	}

	/**
	 * Save access in taxonomy.
	 *
	 * @param int  $user_id    User ID.
	 * @param bool $has_access If the user has access to the course.
	 * @return bool
	 * @throws Exception When learner term could not be created.
	 */
	private function save_access( $user_id, $has_access ) {
		$term = Sensei_Learner::get_learner_term( $user_id );
		if ( ! $has_access ) {
			$result = wp_remove_object_terms( $this->course_id, [ intval( $term->term_id ) ], Sensei_PostTypes::LEARNER_TAXONOMY_NAME );

			return true === $result;
		}

		// If they have access, make sure they have started the course.
		Sensei_Utils::start_user_on_course( $user_id, $this->course_id );

		$result = wp_set_post_terms( $this->course_id, [ intval( $term->term_id ) ], Sensei_PostTypes::LEARNER_TAXONOMY_NAME, true );

		return is_array( $result ) && ! empty( $result );
	}

	/**
	 * Get the access check log for a user.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return bool|Sensei_Course_Access_Log
	 * @throws Exception When learner term could not be created.
	 */
	private function get_access_check_log( $user_id ) {
		$term             = Sensei_Learner::get_learner_term( $user_id );
		$access_check_log = get_term_meta( $term->term_id, $this->get_course_log_meta_key(), true );

		if ( empty( $access_check_log ) ) {
			return false;
		}

		return Sensei_Course_Access_Log::from_json( $access_check_log );
	}

	/**
	 * Builds a new access log record by checking with access providers.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return Sensei_Course_Access_Log
	 * @throws Exception When learner term could not be created.
	 */
	private function build_access_log( $user_id ) {
		$term       = Sensei_Learner::get_learner_term( $user_id );
		$access_log = Sensei_Course_Access_Log::create();

		foreach ( self::get_course_access_providers() as $access_provider_id => $access_provider ) {
			$access_log->record_access_check( $access_provider_id, $access_provider->has_access( $user_id, $this->course_id ) );
		}

		$access_log->finalize_log();

		update_term_meta( $term->term_id, $this->get_course_log_meta_key(), wp_slash( wp_json_encode( $access_log ) ) );

		return $access_log;
	}

	/**
	 * Get the default access level for course if no other access provider returns a result.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private function get_default_access( $user_id ) {
		// No access provider has explicitly granted or restricted access to the user. Use Sensei's default.
		if ( Sensei_Utils::has_started_course( $this->course_id, $user_id ) ) {
			return true;
		}

		return ! Sensei()->settings->get( 'access_permission' ) || sensei_all_access();
	}

	/**
	 * Get the course log meta key.
	 *
	 * @return string
	 */
	private function get_course_log_meta_key() {
		return self::META_PREFIX_COURSE_LOG . $this->course_id;
	}

	/**
	 * Get an array of all the instantiated course access providers.
	 *
	 * @return Sensei_Course_Access_Provider_Interface[]
	 */
	private static function get_course_access_providers() {
		if ( ! isset( self::$access_providers ) ) {
			self::$access_providers = [];

			/**
			 * Fetch all registered course access providers.
			 *
			 * @since 2.x.x
			 *
			 * @param string[] $provider_classes List of access providers classes.
			 */
			$provider_classes = apply_filters( 'sensei_course_access_providers', [] );
			foreach ( $provider_classes as $provider_class ) {
				if ( ! class_exists( $provider_class ) || ! is_a( $provider_class, 'Sensei_Course_Access_Provider_Interface', true ) ) {
					continue;
				}

				self::$access_providers[ $provider_class::get_id() ] = new $provider_class();
			}
		}

		return self::$access_providers;
	}

	/**
	 * Get the hash of the current versions of all course access providers.
	 *
	 * @return string
	 */
	public function get_course_access_providers_version() {
		if ( ! isset( self::$access_providers_version ) ) {
			$access_providers               = self::get_course_access_providers();
			self::$access_providers_version = self::hash_course_access_provider_versions( array_keys( $access_providers ) );
		}

		return self::$access_providers_version;
	}

	/**
	 * Generates a hash of all the access provider versions.
	 *
	 * @param string[] $access_providers Array of access provider class names.
	 * @return string
	 */
	public static function hash_course_access_provider_versions( $access_providers ) {
		$versions = [];
		foreach ( $access_providers as $access_provider_class ) {
			if ( ! is_a( $access_provider_class, 'Sensei_Course_Access_Provider_Interface', true ) ) {
				continue;
			}

			$versions[ $access_provider_class ] = $access_provider_class::get_version();
		}

		ksort( $versions );

		return md5( wp_json_encode( $versions ) );
	}

	/**
	 * Check if we should use the legacy access check. Legacy access check
	 * uses course enrollment to determine access.
	 *
	 * return bool
	 */
	public static function use_legacy_access_check() {
		$use_legacy_access_check = false;

		// Check if WCPC is around but not offering access providers (an old version).
		if (
			class_exists( '\Sensei_WC_Paid_Courses\Sensei_WC_Paid_Courses' ) &&
			! class_exists( '\Sensei_WC_Paid_Courses\Course_Access_Providers' )
		) {
			$use_legacy_access_check = true;
		}

		return apply_filters( 'sensei_legacy_access_check', $use_legacy_access_check );
	}
}
