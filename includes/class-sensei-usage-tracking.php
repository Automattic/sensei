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

	/****** Plugin-specific section ******/

	/*
	 * This section needs to modified in order to use this within another
	 * plugin.
	 */

	/**
	 * Prefix for actions and strings. Please set this to something unique to
	 * your plugin.
	 **/
	const PREFIX = 'sensei';

	/**
	 * Determine whether usage tracking is enabled. Please override this
	 * function based on how your plugin stores this flag.
	 *
	 * @return bool true if usage tracking is enabled, false otherwise.
	 **/
	private function get_tracking_enabled() {
		return Sensei()->settings->get( self::SENSEI_SETTING_NAME ) || false;
	}

	/**
	 * Set whether usage tracking is enabled. Please override this function
	 * based on how your plugin stores this flag.
	 *
	 * @param bool $enable true if usage tracking should be enabled, false if
	 * it should be disabled.
	 **/
	private function set_tracking_enabled( $enable ) {
		Sensei()->settings->set( self::SENSEI_SETTING_NAME, $enable );
	}

	/**
	 * Determine whether current user can manage the tracking options.
	 *
	 * @return bool true if the current user is allowed to manage the tracking
	 * options, false otherwise.
	 **/
	private function current_user_can_manage_tracking() {
		return current_user_can( 'manage_sensei' );
	}

	/**
	 * Get the text to display in the opt-in dialog for users to enable
	 * tracking. This text should include a link to a page indicating what data
	 * is being tracked.
	 *
	 * @return string the text to display in the opt-in dialog.
	 **/
	private function opt_in_dialog_text() {
		return _e( "We'd love if you helped us make Sensei better by allowing us to collect
			<a href=\"https://docs.woocommerce.com/document/what-data-does-sensei-track\" target=\"_blank\">usage tracking data</a>.
			No sensitive information is collected, and you can opt out at any time.",
			'woothemes-sensei' );
	}

	/**
	 * Add plugin-specific initialization code to this method. It will be
	 * called when the singleton instance is constructed.
	 **/
	private function custom_init() {
		// Add filter for settings
		add_filter( 'sensei_settings_fields', array( $this, 'add_setting_field' ) );
	}

	/*
	 * Any other plugin-specific constants, variables, and functions can go
	 * here.
	 */

	const SENSEI_SETTING_NAME = 'sensei_usage_tracking_enabled';

	function add_setting_field( $fields ) {
		// default-settings
		$fields[ self::SENSEI_SETTING_NAME ] = array(
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

	/****** END Plugin-specific section ******/


	/**
	 * @var string $hide_tracking_opt_in the name of the Option for
	 * hiding the Usage Tracking opt-in dialog.
	 **/
	private $hide_tracking_opt_in_option_name;

	/**
	 * @var string $job_name the name of the cron job action for regularly
	 * logging usage data.
	 **/
	private $job_name;

	/**
	 * @var {Prefix}_Usage_Tracking $instance singleton instance
	 **/
	private static $instance;

	/**
	 * @var array $callback Callback function for the usage tracking job.
	 **/
	private $callback;


	private function __construct() {
		// Init instance vars
		$this->hide_tracking_opt_in_option_name = self::PREFIX . '_usage_tracking_opt_in_hide';
		$this->job_name = self::PREFIX . '_usage_tracking_send_usage_data';

		// Set up the opt-in dialog
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_script_deps' ) );
		add_action( 'admin_footer', array( $this, 'output_opt_in_js' ) );
		add_action( 'admin_notices', array( $this, 'maybe_display_tracking_opt_in' ) );
		add_action( 'wp_ajax_' . self::PREFIX . '_handle_tracking_opt_in', array( $this, 'handle_tracking_opt_in' ) );

		// Set up schedule and action needed for cron job
		add_filter( 'cron_schedules', array( $this, 'add_usage_tracking_two_week_schedule' ) );
		add_action( $this->job_name, array( $this, 'send_usage_data' ) );

		// Call plugin-specific initialization method
		$this->custom_init();
	}

	/**
	 * Get the singleton instance.
	 **/
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Configure the Usage Tracking instance. Acceptable keys are:
	 *
	 * - usage_data_callback: the callback returning the usage data to be logged.
	 *
	 * @param array $config the configuration array.
	 **/
	public function configure( $config ) {
		if ( isset( $config['usage_data_callback'] ) ) {
			$this->callback = $config['usage_data_callback'];
		}
	}

	/**
	 * Send an event to Tracks if tracking is enabled.
	 *
	 * @param string $event The event name.
	 * @param array $properties Event Properties.
	 * @param null|int $event_timestamp When the event occurred.
	 * @param bool $force if true, send the event even if tracking is disabled.
	 *
	 * @return null|WP_Error
	 **/
	public function send_event( $event, $properties = array(), $event_timestamp = null, $force = false ) {

		// Only continue if tracking is enabled, or send_event is forced
		if ( ! $this->is_tracking_enabled() && ! $force ) {
			return false;
		}

		$pixel = 'http://pixel.wp.com/t.gif';
		$event_prefix = self::PREFIX . '_';
		$event_name = $event_prefix . str_replace( $event_prefix, '', $event );
		$user = wp_get_current_user();

		if ( null === $event_timestamp ) {
			$event_timestamp = time();
		}

		$properties['admin_email'] = get_option( 'admin_email' );
		$properties['_ut'] = self::PREFIX . ':site_url';
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
			'user-agent'  => self::PREFIX . '_usage_tracking',
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

	/**
	 * Set up a regular cron job to send usage data. The job will only send
	 * the data if tracking is enabled, so it is safe to call this function,
	 * and schedule the job, before the user opts into tracking.
	 **/
	public function schedule_tracking_task() {
		if ( ! wp_next_scheduled( $this->job_name ) ) {
			wp_schedule_event( time(), self::PREFIX . '_usage_tracking_two_weeks', $this->job_name );
		}
	}

	/**
	 * Unschedule the job scheduled by schedule_tracking_task if any is
	 * scheduled. This should be called on plugin deactivation.
	 **/
	public function unschedule_tracking_task() {
		if ( wp_next_scheduled( $this->job_name ) ) {
			wp_clear_scheduled_hook( $this->job_name );
		}
	}

	/**
	 * Check if tracking is enabled.
	 *
	 * @return bool true if tracking is enabled, false otherwise
	 **/
	public function is_tracking_enabled() {
		// Defer to the plugin-specific function
		return $this->get_tracking_enabled();
	}

	/**
	 * Call the usage data callback and send the usage data to Tracks. Only
	 * sends data if tracking is enabled.
	 **/
	public function send_usage_data() {
		if ( ! self::is_tracking_enabled() || ! is_callable( $this->callback ) ) {
			return;
		}

		$usage_data = call_user_func( $this->callback );

		if ( ! is_array( $usage_data ) ) {
			return;
		}

		return self::send_event( 'stats_log', $usage_data );
	}

	//
	// Private functions
	//

	/**
	 * Add two week schedule to use for cron job.
	 *
	 * @access private
	 **/
	function add_usage_tracking_two_week_schedule( $schedules ) {
		$schedules[ self::PREFIX . '_usage_tracking_two_weeks' ] = array(
			'interval' => 15 * DAY_IN_SECONDS,
			'display'  => esc_html__( 'Every Two Weeks', 'wp-plugin-usage-tracking' ),
		);

		return $schedules;
	}

	/**
	 * Hide the opt-in for enabling usage tracking.
	 *
	 * @access private
	 **/
	private function hide_tracking_opt_in() {
		update_option( $this->hide_tracking_opt_in_option_name, true );
	}

	/**
	 * Determine whether the opt-in for enabling usage tracking is hidden.
	 *
	 * @access private
	 *
	 * @return bool true if the opt-in is hidden, false otherwise.
	 **/
	private function is_opt_in_hidden() {
		return (bool) get_option( $this->hide_tracking_opt_in_option_name );
	}

	/**
	 * If needed, display opt-in dialog to enable tracking.
	 *
	 * @access private
	 **/
	function maybe_display_tracking_opt_in() {
		$opt_in_hidden = $this->is_opt_in_hidden();
		$user_tracking_enabled = $this->is_tracking_enabled();
		$can_manage_tracking = $this->current_user_can_manage_tracking();

		if ( ! $user_tracking_enabled && ! $opt_in_hidden && $can_manage_tracking ) { ?>
			<div id="<?php echo self::PREFIX; ?>-usage-tracking-notice" class="notice notice-info"
				data-nonce="<?php echo wp_create_nonce( 'tracking-opt-in' ) ?>">
				<p>
					<?php echo $this->opt_in_dialog_text(); ?>
				</p>
				<p>
					<button class="button button-primary" data-enable-tracking="yes">
						<?php _e( 'Enable Usage Tracking', 'wp-plugin-usage-tracking' ) ?>
					</button>
					<button class="button" data-enable-tracking="no">
						<?php _e( 'Disable Usage Tracking', 'wp-plugin-usage-tracking' ) ?>
					</button>
					<span id="progress" class="spinner alignleft"></span>
				</p>
			</div>
			<div id="<?php echo self::PREFIX; ?>-usage-tracking-enable-success" class="notice notice-success hidden">
				<p><?php _e( 'Usage data enabled. Thank you!', 'wp-plugin-usage-tracking' ) ?></p>
			</div>
			<div id="<?php echo self::PREFIX; ?>-usage-tracking-disable-success" class="notice notice-success hidden">
				<p><?php _e( 'Disabled usage tracking.', 'wp-plugin-usage-tracking' ) ?></p>
			</div>
			<div id="<?php echo self::PREFIX; ?>-usage-tracking-failure" class="notice notice-error hidden">
				<p><?php _e( 'Something went wrong. Please try again later.', 'wp-plugin-usage-tracking' ) ?></p>
			</div>
		<?php
		}
	}

	/**
	 * Handle ajax request from the opt-in dialog.
	 *
	 * @access private
	 **/
	function handle_tracking_opt_in() {
		check_ajax_referer( 'tracking-opt-in', 'nonce' );

		if ( ! $this->current_user_can_manage_tracking() ) {
			wp_die( '', '', 403 );
		}

		$enable_tracking = isset( $_POST['enable_tracking'] ) && $_POST['enable_tracking'] === '1';
		$this->set_tracking_enabled( $enable_tracking );
		$this->hide_tracking_opt_in();
		wp_die();
	}

	/**
	 * Ensure that jQuery has been enqueued since the opt-in dialog JS depends
	 * on it.
	 *
	 * @access private
	 **/
	function enqueue_script_deps() {
		// Ensure jQuery is loaded
		wp_enqueue_script( self::PREFIX . '_usage-tracking-notice', '',
			array( 'jquery' ), null, true );
	}

	/**
	 * Output the JS code to handle the opt-in dialog.
	 *
	 * @access private
	 **/
	function output_opt_in_js() {
?>
<script type="text/javascript">
	(function( prefix ) {
		jQuery( document ).ready( function() {
			function displayProgressIndicator() {
				jQuery( '#' + prefix + '-usage-tracking-notice #progress' ).addClass( 'is-active' );
			}

			function displaySuccess( enabledTracking ) {
				if ( enabledTracking ) {
					jQuery( '#' + prefix + '-usage-tracking-enable-success' ).show();
				} else {
					jQuery( '#' + prefix + '-usage-tracking-disable-success' ).show();
				}
				jQuery( '#' + prefix + '-usage-tracking-notice' ).hide();
			}

			function displayError() {
				jQuery( '#' + prefix + '-usage-tracking-failure' ).show();
				jQuery( '#' + prefix + '-usage-tracking-notice' ).hide();
			}

			// Handle button clicks
			jQuery( '#' + prefix + '-usage-tracking-notice button' ).click( function( event ) {
				event.preventDefault();

				const button         = jQuery( this );
				const enableTracking = jQuery( this ).data( 'enable-tracking' ) == 'yes';
				const nonce          = jQuery( '#' + prefix + '-usage-tracking-notice' ).data( 'nonce' );

				displayProgressIndicator();

				jQuery.ajax( {
					type: 'POST',
					url: ajaxurl,
					data: {
						action: prefix + '_handle_tracking_opt_in',
						enable_tracking: enableTracking ? 1 : 0,
						nonce: nonce,
					},
					success: () => {
						displaySuccess( enableTracking );
					},
					error: displayError,
				} );
			});
		});
	})( "<?php echo self::PREFIX; ?>" );
</script>
<?php
	}
}
