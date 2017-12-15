<?php
/**
 * Opt-in Usage tracking.
 **/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Usage Tracking
 */
class Sensei_Usage_Tracking {

	/**
	 * @var string
	 **/
	private static $prefix = 'sensei_';

	/**
	 * @var string
	 **/
	private static $usage_tracking_setting_name = 'sensei_usage_tracking_enabled';

	private static $job_name = 'sensei_core_jobs_usage_tracking_send_data';

	/**
	 * Send an event or stat.
	 *
	 * @param string $event The event name.
	 * @param array $properties Event Properties.
	 * @param null|int $event_timestamp When the event occurred.
	 *
	 * @return null|WP_Error
	 **/
	public static function send_event( $event, $properties = array(), $event_timestamp = null ) {
		$pixel = 'http://pixel.wp.com/t.gif';
		$prefix = apply_filters( 'sensei_usage_tracking_prefix', self::$prefix );
		$event_name = $prefix . str_replace( $prefix, '', $event );
		$user = wp_get_current_user();

		if ( null === $event_timestamp ) {
			$event_timestamp = time();
		}

		$properties['admin_email'] = get_option( 'admin_email' );
		$properties['_ut'] = 'sensei:site_url';
		// Use site URL as the userid to enable usage tracking at the site level.
		// Note that we would likely want to use site URL + user ID for userid if we were
		// to ever add event tracking at the user level.
		$properties['_ui'] = site_url();
		$properties['_ul'] = $user->user_login;
		$properties['_en'] = $event_name;
		$properties['_ts'] = $event_timestamp . '000';
		$properties['_rt'] = round( microtime( true ) * 1000 );  // log time
		$p = array();

		foreach( $properties as $key => $value ) {
			$p[]= urlencode( $key ) . '=' . urlencode( $value );
		}

		$pixel .= '?' . implode( '&', $p ) . '&_=_'; // EOF marker
		$response = wp_remote_get( $pixel, array(
			'blocking'    => true,
			'timeout'     => 1,
			'redirection' => 2,
			'httpversion' => '1.1',
			'user-agent'  => 'sensei_plugin_usage_1',
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = isset( $response['response']['code'] ) ? $response['response']['code'] : 0;

		if ( $code !== 200 ) {
			return new WP_Error( 'request_failed', 'HTTP Request failed', $code );
		}

		return true;
	}

	public static function maybe_schedule_tracking_task() {
		if ( ! wp_next_scheduled( self::$job_name ) ) {
			wp_schedule_event( time(), 'sensei_two_weeks', self::$job_name );
		}
	}

	public static function maybe_unschedule_tracking_task() {
		if ( wp_next_scheduled( self::$job_name ) ) {
			wp_clear_scheduled_hook( self::$job_name );
		}
	}

	/**
	 * Check if tracking is enabled.
	 * @return bool
	 **/
	public static function is_tracking_enabled() {
		$setting = Sensei()->settings->get( self::$usage_tracking_setting_name ) || false;
		/**
		 * Filter
		 * @return bool
		 **/
		$enabled = (bool) apply_filters( 'sensei_usage_tracking_enabled', (bool)$setting );

		return $enabled;
	}

	/**
	 * Attach hooks.
	 **/
	function hook() {
		// Setting
		add_filter( 'sensei_settings_fields', array( $this, 'add_setting_field' ) );
		// Admin
		add_action( 'admin_notices', array( $this, 'maybe_display_tracking_opt_in' ) );
		add_action( 'admin_menu', array( $this, 'register_usage_submenu_page' ) );
		add_action( 'admin_init', array( $this, 'handle_request' ) );
		// Cron
		add_filter( 'cron_schedules', array( $this, 'add_two_weeks' ) );
		add_action( self::$job_name, array( $this, 'maybe_send_usage_data' ) );
	}

	function add_two_weeks( $schedules ) {
		$schedules['sensei_two_weeks'] = array(
			'interval' => 15 * DAY_IN_SECONDS,
			'display'  => esc_html__( 'Every Two Weeks', 'woothemes-sensei' ),
		);

		return $schedules;
	}

	function handle_request() {
		if ( ! isset( $_GET['page'] ) ) {
			return;
		}

		if ( $_GET['page'] !== 'sensei_usage_tracking' ) {
			return;
		}

		if ( isset( $_POST['usage_tracking_action'] ) && $_POST['usage_tracking_action'] === 'send_data' ) {
			check_admin_referer( 'send_data_nonce' );
			$result = self::maybe_send_usage_data();

			if ( is_wp_error( $result ) ) {
				wp_redirect( 'admin.php?page=sensei_usage_tracking&msg=data_send_error' );
			}

			wp_redirect( 'admin.php?page=sensei_usage_tracking&msg=data_send_success' );
			exit;
		}
	}

	/**
	 * Send usage data.
	 **/
	public static function maybe_send_usage_data() {
		if ( ! self::is_tracking_enabled() ) {
			return;
		}

		/**
		 * Define data to send. Add or remove array keys and values.
		 *
		 * @param array $usage_data The data to send.
		 *
		 * @return array The array should be key/value. All values will be urlencoded
		 **/
		$usage_data = (array) apply_filters( 'sensei_usage_tracking_usage_data', array(
			'course_count' => wp_count_posts( 'course' )->publish,
			'learner_count' => self::get_learner_count(),
			'lesson_count' => wp_count_posts( 'lesson' )->publish,
			'message_count' => wp_count_posts( 'sensei_message' )->publish,
			'question_count' => wp_count_posts( 'question' )->publish,
			'teacher_count' => self::get_teacher_count(),
		) );

		$resp = self::send_event( 'stats_log', $usage_data );

		// Send a dummy event at the same time to enable using Tracks funnels,
		// which requires a minimum of two event steps.
		$resp = self::send_event( 'sensei_dummy_stats_log' );

		return $resp;
	}

	/**
	 * Get the number of teachers.
	 *
	 * @return int Number of teachers.
	 **/
	public static function get_teacher_count() {
		$teacher_query = new WP_User_Query( array( 'role' => 'teacher' ) );

		return $teacher_query->total_users;
	}

	/**
	 * Get the total number of learners enrolled in at least one course.
	 *
	 * @return int Number of learners.
	 **/
	public static function get_learner_count() {
		$learner_count = 0;
		$args['fields'] = array( 'ID' );
		$user_query = new WP_User_Query( $args );
		$learners = $user_query->get_results();

		foreach( $learners as $learner ) {
			$course_args = array(
				'user_id' => $learner->ID,
				'type' => 'sensei_course_status',
				'status' => 'any',
			);

			$course_count = Sensei_Utils::sensei_check_for_activity( $course_args );

			if ( $course_count > 0 ) {
				$learner_count++;
			}
		}

		return $learner_count;
	}

	/**
	 *
	 * Register usage submenu page.
	 **/
	function register_usage_submenu_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		add_submenu_page(
			'sensei',
			__( 'Usage Tracking', 'woothemes-sensei' ),
			__( 'Usage Tracking', 'woothemes-sensei' ),
			'manage_options',
			'sensei_usage_tracking',
			array( $this, 'render_usage_tracking_page' )
		);
	}

	function render_usage_tracking_page() {
		?>
		<div class="wrap">

			<div id="icon-woothemes-sensei" class="icon32"><br></div>
			<h1><?php _e('Sensei Usage Tracking', 'woothemes-sensei'); ?></h1>
			<form method="post" action="" name="update-sensei" class="upgrade">
				<p>
					<input id="update-sensei" type="hidden" value="send_data" name="usage_tracking_action">
					<input id="send-data" class="button button-primary" type="submit" value="Send Data Now">
					<?php wp_nonce_field( 'send_data_nonce' ); ?>
				</p>
			</form>
		</div>
		<?php
	}

	function add_setting_field( $fields ) {
		// default-settings
		$fields[ self::$usage_tracking_setting_name ] = array(
			'name' => __( 'Enable usage tracking', 'woothemes-sensei' ),
			'description' => __( 'Allow Sensei to anonymously track plugin usage**. No sensitive data is tracked.', 'woothemes-sensei' ),
			'type' => 'checkbox',
			'default' => false,
			'section' => 'default-settings'
		);

		return $fields;
	}

	function maybe_display_tracking_opt_in() {
		$opt_in_displayed = (bool) get_option( 'sensei_usage_tracking_opt_in_display' );
		$user_tracking_enabled = (bool) get_option( self::$usage_tracking_setting_name );

		if ( false && ! $user_tracking_enabled && ! $opt_in_displayed ) { ?>
			<div class="notice">
				<p><?php echo esc_html__( 'Help us make Sensei better! Allow Sensei to anonymously track plugin usage. No sensitive data is tracked.', 'woothemes-sensei' ); ?></p>
				<form>
					<input class="button button-primary" type="submit" value="Yes">
				</form>
				<form>
					<input class="button" type="submit" value="No">
				</form>
			</div>
		<?php
		}
	}
}
