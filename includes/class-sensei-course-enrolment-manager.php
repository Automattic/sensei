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
 * Singleton handling the management of enrolment for all courses.
 */
class Sensei_Course_Enrolment_Manager {
	const COURSE_ENROLMENT_SITE_SALT_OPTION = 'sensei_course_enrolment_site_salt';
	const LEARNER_CALCULATION_META_NAME     = 'learner_calculated_version';

	/**
	 * Instance of singleton.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * All course enrolment providers.
	 *
	 * @var Sensei_Course_Enrolment_Provider_Interface[]
	 */
	private $enrolment_providers;

	/**
	 * Fetches an instance of the class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Sensei_Course_Enrolment_Manager constructor. Private so it can only be initialized internally.
	 */
	private function __construct() {}

	/**
	 * Sets the actions.
	 */
	public function init() {
		add_action( 'init', [ $this, 'collect_enrolment_providers' ], 100 );
		add_filter( 'sensei_can_user_manually_enrol', [ $this, 'maybe_prevent_frontend_manual_enrol' ], 10, 2 );
	}

	/**
	 * Collects and initializes enrolment providers. Hooked late into `init`.
	 *
	 * Do not call outside of this class.
	 *
	 * @access private
	 */
	public function collect_enrolment_providers() {
		if ( isset( $this->enrolment_providers ) ) {
			return;
		}

		$this->enrolment_providers = [];

		// Manual enrolment is Sensei's core enrolment provider.
		$providers = [
			Sensei_Course_Manual_Enrolment_Provider::instance(),
		];

		/**
		 * Fetch all registered course enrolment providers.
		 *
		 * @param Sensei_Course_Enrolment_Provider_Interface[] $providers List of enrolment providers instances.
		 *
		 * @since 3.0.0
		 */
		$providers = apply_filters( 'sensei_course_enrolment_providers', $providers );
		foreach ( $providers as $provider ) {
			if ( ! ( $provider instanceof Sensei_Course_Enrolment_Provider_Interface ) ) {
				continue;
			}

			$this->enrolment_providers[ $provider->get_id() ] = $provider;
		}
	}

	/**
	 * Gets the descriptive name of the provider by ID.
	 *
	 * @param string $provider_id Unique identifier of the enrolment provider.
	 *
	 * @return string|false
	 * @throws Exception When there was an attempt to access enrolment providers before they are collected in init:100.
	 */
	public function get_enrolment_provider_name_by_id( $provider_id ) {
		$provider = $this->get_enrolment_provider_by_id( $provider_id );
		if ( ! $provider ) {
			return false;
		}

		return $provider->get_name();
	}

	/**
	 * Gets the enrolment provider object by its ID.
	 *
	 * @param string $provider_id Unique identifier of the enrolment provider.
	 *
	 * @return Sensei_Course_Enrolment_Provider_Interface|false
	 * @throws Exception When there was an attempt to access enrolment providers before they are collected in init:100.
	 */
	public function get_enrolment_provider_by_id( $provider_id ) {
		$all_providers = $this->get_all_enrolment_providers();
		if ( ! isset( $all_providers[ $provider_id ] ) ) {
			return false;
		}

		return $all_providers[ $provider_id ];
	}

	/**
	 * Get an array of all the instantiated course enrolment providers.
	 *
	 * @return Sensei_Course_Enrolment_Provider_Interface[]
	 * @throws Exception When there was an attempt to access enrolment providers before they are collected in init:100.
	 */
	public function get_all_enrolment_providers() {
		if ( ! isset( $this->enrolment_providers ) ) {
			throw new Exception( 'Enrolment providers were asked for before they were collected late in `init`' );
		}

		return $this->enrolment_providers;
	}

	/**
	 * Get the manual enrolment provider.
	 *
	 * @return false|Sensei_Course_Manual_Enrolment_Provider
	 * @throws Exception When there was an attempt to access the manual enrolment providers before providers are collected in init:100.
	 */
	public function get_manual_enrolment_provider() {
		return $this->get_enrolment_provider_by_id( Sensei_Course_Manual_Enrolment_Provider::instance()->get_id() );
	}

	/**
	 * Check if currently logged in user can manually enrol themselves. Block enrolment when a non-manual
	 * provider handles enrolment.
	 *
	 * @param bool $can_user_manually_enrol True if they can manually enrol themselves, false if not.
	 * @param int  $course_id               Course post ID.
	 *
	 * @return bool
	 */
	public function maybe_prevent_frontend_manual_enrol( $can_user_manually_enrol, $course_id ) {
		$all_providers = $this->get_all_enrolment_providers();

		// If the manual provider has been filtered out, do not allow frontend enrolment.
		if ( ! isset( $all_providers[ Sensei_Course_Manual_Enrolment_Provider::get_id() ] ) ) {
			return false;
		}

		unset( $all_providers[ Sensei_Course_Manual_Enrolment_Provider::get_id() ] );

		foreach ( $all_providers as $provider ) {
			if ( $provider->handles_enrolment( $course_id ) ) {
				// One of the other providers handles enrolment. Prevent enrolment on the frontend form.
				return false;
			}
		}

		return $can_user_manually_enrol;
	}

	/**
	 * Gets the site course enrolment salt that can be used to invalidate all enrolments.
	 *
	 * @return string
	 */
	public static function get_site_salt() {
		$enrolment_salt = get_option( self::COURSE_ENROLMENT_SITE_SALT_OPTION );

		if ( ! $enrolment_salt ) {
			return self::reset_site_salt();
		}

		return $enrolment_salt;
	}

	/**
	 * Resets the site course enrolment salt. If already set, this will invalidate all current course enrolment results.
	 *
	 * @return string
	 */
	public static function reset_site_salt() {
		$new_salt = md5( uniqid() );

		update_option( self::COURSE_ENROLMENT_SITE_SALT_OPTION, $new_salt, true );

		return $new_salt;
	}

	/**
	 * Trigger course enrolment check when enrolment might have changed.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 */
	public static function trigger_course_enrolment_check( $user_id, $course_id ) {
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		if ( $course_enrolment ) {
			$course_enrolment->is_enrolled( $user_id, false );
		}
	}

	/**
	 * Recalculate all enrolments for a specific user. This method will return the cached enrolments if they already
	 * exist. To enforce a calculation after a possible change, use
	 * Sensei_Course_Enrolment_Manager::trigger_course_enrolment_check instead.
	 *
	 * @param int $user_id   User ID.
	 *
	 * @see Sensei_Course_Enrolment_Manager::trigger_course_enrolment_check
	 */
	public function recalculate_enrolments( $user_id ) {

		$learner_calculated_version = get_user_meta( $user_id, self::LEARNER_CALCULATION_META_NAME, true );
		if ( self::get_enrolment_calculation_version() === $learner_calculated_version ) {
			return;
		}

		$course_args = [
			'post_type'      => 'course',
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
			'fields'         => 'ids',
		];

		$courses = get_posts( $course_args );

		if ( empty( $courses ) ) {
			return;
		}

		foreach ( $courses as $course ) {
			Sensei_Course_Enrolment::get_course_instance( $course )->is_enrolled( $user_id );
		}

		update_user_meta(
			$user_id,
			self::LEARNER_CALCULATION_META_NAME,
			self::get_enrolment_calculation_version()
		);
	}

	/**
	 * Returns the enrolment calculation version string.
	 *
	 * @return string The calculation version.
	 */
	public static function get_enrolment_calculation_version() {
		return self::get_site_salt() . '-' . Sensei()->version;
	}
}
