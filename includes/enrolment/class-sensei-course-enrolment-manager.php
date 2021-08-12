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
	const LEARNER_CALCULATION_META_NAME     = 'sensei_learner_calculated_version';

	/**
	 * Update this when releasing a version that requires user enrolment to get checked.
	 * Calculated and stored enrolments will only get updated if enrolment provider
	 * versions have changed.
	 */
	const ENROLMENT_VERSION = '3.8.1';

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
	 * Hash of all the enrolment provider versions.
	 *
	 * @var string
	 */
	private $enrolment_providers_versions_hash;

	/**
	 * Deferred enrolment checks.
	 *
	 * @var array
	 */
	private $deferred_enrolment_checks = [];

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
		add_action( 'plugins_loaded', [ $this, 'detect_wcpc_1' ], 100 );
		add_action( 'init', [ $this, 'collect_enrolment_providers' ], 100 );
		add_action( 'shutdown', [ $this, 'run_deferred_course_enrolment_checks' ] );
		add_action( 'sensei_enrolment_results_calculated', [ $this, 'remove_deferred_enrolment_check' ], 10, 3 );
		add_filter( 'sensei_can_user_manually_enrol', [ $this, 'maybe_prevent_frontend_manual_enrol' ], 10, 2 );
		add_action( 'sensei_before_learners_enrolled_courses_query', [ $this, 'recalculate_enrolments' ] );
		add_action( 'transition_post_status', [ $this, 'recalculate_on_course_post_status_change' ], 10, 3 );

		add_action( 'shutdown', [ Sensei_Enrolment_Provider_State_Store::class, 'persist_all' ] );
		add_action( 'shutdown', [ Sensei_Enrolment_Provider_Journal_Store::class, 'persist_all' ] );
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
	 * Generates a hash of all the enrolment provider versions.
	 *
	 * @return string
	 */
	public function get_enrolment_provider_versions_hash() {
		if ( ! isset( $this->enrolment_providers_versions_hash ) ) {
			$versions = [];
			foreach ( $this->get_all_enrolment_providers() as $enrolment_provider ) {
				if ( ! ( $enrolment_provider instanceof Sensei_Course_Enrolment_Provider_Interface ) ) {
					continue;
				}

				$enrolment_provider_class              = get_class( $enrolment_provider );
				$versions[ $enrolment_provider_class ] = $enrolment_provider->get_version();
			}

			ksort( $versions );

			$this->enrolment_providers_versions_hash = md5( wp_json_encode( $versions ) );
		}

		return $this->enrolment_providers_versions_hash;
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
		if ( ! isset( $all_providers[ Sensei_Course_Manual_Enrolment_Provider::instance()->get_id() ] ) ) {
			return false;
		}

		unset( $all_providers[ Sensei_Course_Manual_Enrolment_Provider::instance()->get_id() ] );

		foreach ( $all_providers as $provider ) {
			if ( $provider->handles_enrolment( $course_id ) ) {
				// One of the other providers handles enrolment. Prevent enrolment on the frontend form.
				return false;
			}
		}

		return $can_user_manually_enrol;
	}

	/**
	 * Run the deferred enrolment checks.
	 *
	 * @access private
	 */
	public function run_deferred_course_enrolment_checks() {
		foreach ( $this->deferred_enrolment_checks as $user_id => $course_ids ) {
			foreach ( array_keys( $course_ids ) as $course_id ) {
				$this->do_course_enrolment_check( $user_id, $course_id );
			}
		}
	}

	/**
	 * When enrolment calculation happens, remove it from deferred calculation.
	 *
	 * @param Sensei_Course_Enrolment_Provider_Results $enrolment_results Enrolment results object.
	 * @param int                                      $course_id         Course post ID.
	 * @param int                                      $user_id           User ID.
	 */
	public function remove_deferred_enrolment_check( Sensei_Course_Enrolment_Provider_Results $enrolment_results, $course_id, $user_id ) {
		if ( isset( $this->deferred_enrolment_checks[ $user_id ] ) ) {
			unset( $this->deferred_enrolment_checks[ $user_id ][ $course_id ] );
		}
	}

	/**
	 * Defer course enrolment check to the end of request.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 */
	private function defer_course_enrolment_check( $user_id, $course_id ) {
		if ( ! isset( $this->deferred_enrolment_checks[ $user_id ] ) ) {
			$this->deferred_enrolment_checks[ $user_id ] = [];
		}

		// Check if the enrolment check is already deferred.
		if ( isset( $this->deferred_enrolment_checks[ $user_id ][ $course_id ] ) ) {
			return;
		}

		// Usually the user will be back calculated by the end of the request, but mark them
		// as needing a recalculation just in case the request fails early.
		$this->mark_user_as_needing_recalculation( $user_id );
		$this->invalidate_learner_result( $user_id, $course_id );

		$this->deferred_enrolment_checks[ $user_id ][ $course_id ] = true;
	}

	/**
	 * Trigger course enrolment check when enrolment might have changed.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 */
	private function do_course_enrolment_check( $user_id, $course_id ) {
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		if ( $course_enrolment ) {
			$course_enrolment->is_enrolled( $user_id, false );
		}
	}

	/**
	 * Invalidate an enrolment result so that it gets recalculated next time it is requested.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 */
	private function invalidate_learner_result( $user_id, $course_id ) {
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		if ( $course_enrolment ) {
			$course_enrolment->invalidate_learner_result( $user_id );
		}
	}

	/**
	 * Gets the site course enrolment salt that can be used to invalidate all enrolments.
	 *
	 * @return string
	 */
	public function get_site_salt() {
		$enrolment_salt = get_option( self::COURSE_ENROLMENT_SITE_SALT_OPTION );

		if ( ! $enrolment_salt ) {
			return $this->reset_site_salt();
		}

		return $enrolment_salt;
	}

	/**
	 * Resets the site course enrolment salt. If already set, this will invalidate all current course enrolment results.
	 *
	 * @return string
	 */
	public function reset_site_salt() {
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
		$instance = self::instance();

		if ( self::should_defer_enrolment_check() ) {
			$instance->defer_course_enrolment_check( $user_id, $course_id );

			return;
		}

		$instance->do_course_enrolment_check( $user_id, $course_id );
	}

	/**
	 * Check if we should defer enrolment checks.
	 *
	 * @return bool
	 */
	private static function should_defer_enrolment_check() {
		$should_defer_enrolment_check = true;

		// If this is called during a cron job, do not defer the enrolment check.
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			$should_defer_enrolment_check = false;
		}

		// If the `shutdown` action has already been fired, do not defer the enrolment check.
		if ( did_action( 'shutdown' ) ) {
			$should_defer_enrolment_check = false;
		}

		/**
		 * Override deferment handling for enrolment checking.
		 *
		 * @since 3.0.0
		 *
		 * @param bool $should_defer_enrolment_check True if enrolment checks should be deferred to the end of the request.
		 */
		return apply_filters( 'sensei_should_defer_enrolment_check', $should_defer_enrolment_check );
	}

	/**
	 * Recalculate all enrolments for a specific user. This method will return the cached enrolments if they already
	 * exist. To enforce a calculation after a possible change, use
	 * Sensei_Course_Enrolment_Manager::trigger_course_enrolment_check instead.
	 *
	 * @param int $user_id User ID.
	 *
	 * @see Sensei_Course_Enrolment_Manager::trigger_course_enrolment_check
	 */
	public function recalculate_enrolments( $user_id ) {
		$learner_calculated_version = get_user_meta( $user_id, self::get_learner_calculated_version_meta_key(), true );
		if ( $this->get_enrolment_calculation_version() === $learner_calculated_version ) {
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
			self::get_learner_calculated_version_meta_key(),
			$this->get_enrolment_calculation_version()
		);
	}

	/**
	 * Trigger course enrolment recalculation when post status changes.
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       Post object.
	 */
	public function recalculate_on_course_post_status_change( $new_status, $old_status, $post ) {
		if (
			$new_status === $old_status       // No change in status.
			|| 'course' !== $post->post_type  // Not a course.
			|| 'new' === $old_status          // This is initial creation.
			|| (
				'publish' !== $new_status     // Not changing between published and not published.
				&& 'publish' !== $old_status
			)
		) {
			return;
		}

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $post->ID );
		$course_enrolment->recalculate_enrolment();
	}

	/**
	 * Mark a user as needing recalculation.
	 *
	 * @param int $user_id User ID.
	 */
	public function mark_user_as_needing_recalculation( $user_id ) {
		delete_user_meta( $user_id, self::get_learner_calculated_version_meta_key() );
	}

	/**
	 * Returns the enrolment calculation version string.
	 *
	 * @return string The calculation version.
	 */
	public function get_enrolment_calculation_version() {
		$hash_components   = [];
		$hash_components[] = $this->get_site_salt();
		$hash_components[] = $this->get_enrolment_provider_versions_hash();

		return md5( implode( '-', $hash_components ) ) . '-' . self::ENROLMENT_VERSION;
	}

	/**
	 * Get the learner calculated meta key.
	 *
	 * @return string
	 */
	public static function get_learner_calculated_version_meta_key() {
		global $wpdb;

		return $wpdb->get_blog_prefix() . self::LEARNER_CALCULATION_META_NAME;
	}

	/**
	 * Detect WooCommerce Paid Courses v1 and disable the enrolment functionality while it is installed.
	 *
	 * @access private
	 */
	public function detect_wcpc_1() {
		if (
			! defined( 'SENSEI_WC_PAID_COURSES_VERSION' )
			|| '1.' !== substr( SENSEI_WC_PAID_COURSES_VERSION, 0, 2 )
		) {
			return;
		}

		// Disable enrolment system until WCPC is deactivated or upgraded.
		add_filter( 'sensei_is_enrolled', '__return_false' );
		add_filter( 'sensei_course_enrolment_providers', '__return_empty_array', 100 );

		// Show admin notice.
		add_action( 'admin_notices', [ $this, 'add_wcpc_1_notice' ] );
	}

	/**
	 * Adds notice in WP Admin when we detect WooCommerce Paid Courses v1 is installed.
	 *
	 * @access private
	 */
	public function add_wcpc_1_notice() {
		$screen        = get_current_screen();
		$valid_screens = [ 'dashboard', 'plugins', 'plugins-network', 'sensei-lms_page_sensei_learners' ];

		if ( ! current_user_can( 'activate_plugins' ) || ! in_array( $screen->id, $valid_screens, true ) ) {
			return;
		}

		$message = __( '<strong>Sensei LMS</strong> has detected an incompatible version of WooCommerce Paid Courses. Learners will not be able to access their courses until it is upgraded to version 2.0.0 or greater.', 'sensei-lms' );

		echo '<div class="error"><p>';
		echo wp_kses( $message, [ 'strong' => [] ] );
		echo '</p></div>';
	}
}
