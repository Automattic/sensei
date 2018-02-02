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
	 * @var array $callback Callback function for the usage tracking job.
	 **/
	private $callback;

	/**
	 * @var string
	 **/
	private static $prefix = 'sensei_';

	/**
	 * @var string
	 **/
	private static $usage_tracking_setting_name = 'sensei_usage_tracking_enabled';

	private static $hide_tracking_opt_in_option_name = 'sensei_usage_tracking_opt_in_hide';

	private static $job_name = 'sensei_usage_tracking_send_usage_data';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.9.20
	 * @param array $callback  Callable usage tracking function
	 **/
	function __construct( $callback ) {
		$this->callback = $callback;
	}

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
		$event_name = self::$prefix . str_replace( self::$prefix, '', $event );
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
			'user-agent'  => 'sensei_usage_tracking',
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
			wp_schedule_event( time(), 'sensei_usage_tracking_two_weeks', self::$job_name );
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
		return Sensei()->settings->get( self::$usage_tracking_setting_name ) || false;
	}

	/**
	 * Attach hooks.
	 **/
	function hook() {
		// Setting
		add_filter( 'sensei_settings_fields', array( $this, 'add_setting_field' ) );
		// Admin
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_notices', array( $this, 'maybe_display_tracking_opt_in' ) );
		// Ajax
		add_action( 'wp_ajax_handle_tracking_opt_in', array( $this, 'handle_tracking_opt_in' ) );
		// Cron
		add_filter( 'cron_schedules', array( $this, 'add_two_weeks' ) );
		add_action( self::$job_name, array( $this, 'maybe_send_usage_data' ) );
	}

	function admin_enqueue_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'sensei-usage-tracking-notice',
			Sensei()->plugin_url . 'assets/js/admin/usage-tracking-notice' . $suffix . '.js',
			array( 'jquery' ), Sensei()->version, true );
	}

	function add_two_weeks( $schedules ) {
		$schedules['sensei_usage_tracking_two_weeks'] = array(
			'interval' => 15 * DAY_IN_SECONDS,
			'display'  => esc_html__( 'Every Two Weeks', 'woothemes-sensei' ),
		);

		return $schedules;
	}

	/**
	 * Send usage data.
	 **/
	public function maybe_send_usage_data() {
		if ( ! self::is_tracking_enabled() || ! is_callable( $this->callback ) ) {
			return;
		}

		$usage_data = call_user_func( $this->callback );

		if ( ! is_array( $usage_data ) ) {
			return;
		}

		return self::send_event( 'stats_log', $usage_data );
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
			'description' => __(
				'Help us make Sensei better by allowing us to collect
				<a href="https://docs.woocommerce.com/document/what-data-does-sensei-track" target="_blank">usage tracking data</a>.
				No sensitive information is collected.', 'woothemes-sensei' ),
			'type' => 'checkbox',
			'default' => false,
			'section' => 'default-settings'
		);

		return $fields;
	}

	function maybe_display_tracking_opt_in() {
		$opt_in_hidden = (bool) get_option( self::$hide_tracking_opt_in_option_name );
		$user_tracking_enabled = Sensei()->settings->get( self::$usage_tracking_setting_name );

		if ( ! $user_tracking_enabled && ! $opt_in_hidden && current_user_can( 'manage_sensei' ) ) { ?>
			<div id="sensei-usage-tracking-notice" class="notice notice-info"
				data-nonce="<?php echo wp_create_nonce( 'tracking-opt-in' ) ?>">
				<p>
					<?php _e( "We'd love if you helped us make Sensei better by allowing us to collect
						<a href=\"https://docs.woocommerce.com/document/what-data-does-sensei-track\" target=\"_blank\">usage tracking data</a>.
						No sensitive information is collected, and you can opt out at any time.",
						'woothemes-sensei' ) ?>
				</p>
				<p>
					<button class="button button-primary" data-enable-tracking="yes">
						<?php _e( 'Enable Usage Tracking', 'woothemes-sensei' ) ?>
					</button>
					<button class="button" data-enable-tracking="no">
						<?php _e( 'Disable Usage Tracking', 'woothemes-sensei' ) ?>
					</button>
					<span id="progress" class="spinner alignleft"></span>
				</p>
				<p>
					<noscript>
						<?php _e( "Looks like you don't have Javascript enabled! Please go to the
							<a href=\"/wp-admin/admin.php?page=woothemes-sensei-settings\">Settings page</a>
							to enable usage tracking.", 'woothemes-sensei' ); ?>
					</noscript>
				</p>
			</div>
			<div id="sensei-usage-tracking-enable-success" class="notice notice-success hidden">
				<p><?php _e( 'Usage data enabled. Thank you!', 'woothemes-sensei' ) ?></p>
			</div>
			<div id="sensei-usage-tracking-disable-success" class="notice notice-success hidden">
				<p><?php _e( 'Disabled usage tracking.', 'woothemes-sensei' ) ?></p>
			</div>
			<div id="sensei-usage-tracking-failure" class="notice notice-error hidden">
				<p><?php _e( 'Something went wrong. Please try again later.', 'woothemes-sensei' ) ?></p>
			</div>
		<?php
		}
	}

	function handle_tracking_opt_in() {
		check_ajax_referer( 'tracking-opt-in', 'nonce' );

		if ( ! current_user_can( 'manage_sensei' ) ) {
			wp_die( '', '', 403 );
		}

		$enable_tracking = isset( $_POST['enable_tracking'] ) && $_POST['enable_tracking'] === '1';
		Sensei()->settings->set( self::$usage_tracking_setting_name, $enable_tracking );
		$this->hide_tracking_opt_in();
		wp_die();
	}

	function hide_tracking_opt_in() {
		update_option( self::$hide_tracking_opt_in_option_name, true );
	}
}
