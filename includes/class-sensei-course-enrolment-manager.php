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
		$provider_classes = [
			Sensei_Course_Manual_Enrolment_Provider::class,
		];

		/**
		 * Fetch all registered course enrolment providers.
		 *
		 * @param string[] $provider_classes List of enrolment providers classes.
		 *
		 * @since 3.0.0
		 */
		$provider_classes = apply_filters( 'sensei_course_enrolment_providers', $provider_classes );
		foreach ( $provider_classes as $provider_class ) {
			if ( ! class_exists( $provider_class ) || ! is_a( $provider_class, 'Sensei_Course_Enrolment_Provider_Interface', true ) ) {
				continue;
			}

			$this->enrolment_providers[ $provider_class::get_id() ] = new $provider_class();
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

		$provider_class = get_class( $provider );

		return $provider_class::get_name();
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
		return $this->get_enrolment_provider_by_id( Sensei_Course_Manual_Enrolment_Provider::get_id() );
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
}
