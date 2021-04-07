<?php
/**
 * File containing the class Sensei_WCCOM_Connect_Notice
 *
 * @package sensei-lms
 * @since   3.10.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles showing the WooCommerce.com connect notice.
 *
 * @access private
 *
 * @class Sensei_WCCOM_Connect_Notice
 */
class Sensei_WCCOM_Connect_Notice {
	const DISMISS_NOTICE_NONCE_ACTION   = 'sensei-lms-cancelled-wccom-connect-dismiss';
	const DISMISSED_NOTIFICATION_OPTION = 'sensei-cancelled-wccom-connect-dismissed';
	const SENSEI_WCCOM_EXTENSIONS       = [
		'152116:bad2a02a063555b7e2bee59924690763', // WC Paid Courses.
		'543363:8ee2cdf89f55727f57733133ccbbfbb0', // Content Drip.
		'435830:700f6f6786c764debcd5dfb789f5f506', // Share your Grade.
		'435834:f6479a8a3a01ac11794f32be22b0682f', // Course Participants.
	];

	/**
	 * Class constructor.
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		add_filter( 'admin_notices', [ $this, 'add_admin_notice' ] );
		add_action( 'wp_ajax_sensei_dismiss_wccom_connect_notice', [ $this, 'handle_notice_dismiss' ] );
	}

	/**
	 * Check if WooCommerce.com connection has been made.
	 *
	 * @return bool
	 */
	private function is_wccom_connected() {
		if ( ! class_exists( 'WC_Helper_Options' ) ) {
			// We can't do anything until WooCommerce is activated. There is already a notice for that.
			return true;
		}

		$auth = WC_Helper_Options::get( 'auth' );

		return ! empty( $auth['access_token'] );
	}

	/**
	 * Get the connect URL.
	 */
	private function get_wccom_connect_url() {
		return add_query_arg(
			[
				'page'              => 'wc-addons',
				'section'           => 'helper',
				'wc-helper-connect' => 1,
				'wc-helper-nonce'   => wp_create_nonce( 'connect' ),
			],
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Output the admin notice.
	 *
	 * @access private
	 */
	public function add_admin_notice() {
		if (
			$this->is_wccom_connected()
			|| $this->is_notice_dismissed()
			|| ! $this->has_wccom_sensei_extension()
			|| ! $this->can_see_notice_on_screen()
			|| ! $this->can_update_plugins()
		) {
			return;
		}

		add_action( 'admin_footer', [ $this, 'output_dismiss_js' ] );

		$wccom_connect_url = $this->get_wccom_connect_url();
		?>
		<div id="sensei-lms-wccom-connect-notice" class="notice notice-info is-dismissible"
				data-nonce="<?php echo esc_attr( wp_create_nonce( self::DISMISS_NOTICE_NONCE_ACTION ) ); ?>">
			<p>
				<?php
				esc_html_e(
					'Get notified about new features and updates by connecting your WooCommerce account.',
					'sensei-lms'
				);
				?>
			</p>
			<p>
				<a href="<?php echo esc_url( $wccom_connect_url ); ?>" class="button button-primary">
					<?php esc_html_e( 'Connect account', 'sensei-lms' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Output the JS for dismissing the notice.
	 *
	 * @access private
	 **/
	public function output_dismiss_js() {
		?>
		<script type="text/javascript">
			( function() {
				var noticeSelector = '#sensei-lms-wccom-connect-notice';
				var $notice = jQuery( noticeSelector );
				if ( $notice.length === 0 ) {
					return;
				}

				var nonce = $notice.data( 'nonce' );

				// Handle button clicks
				jQuery( noticeSelector ).on( 'click', 'button.notice-dismiss', function() {
					jQuery.ajax( {
						type: 'POST',
						url: ajaxurl,
						data: {
							action: 'sensei_dismiss_wccom_connect_notice',
							nonce: nonce,
						}
					} );
				} );
			} )();
		</script>
		<?php
	}

	/**
	 * Check to see if user is on a screen that should see the notice.
	 *
	 * @return bool
	 */
	private function can_see_notice_on_screen() {
		$screen        = \get_current_screen();
		$valid_screens = [
			'course',
			'plugins',
			'plugins-network',
			'sensei-lms_page_sensei_learners',
			'sensei-lms_page_sensei-extensions',
		];

		if (
			! $screen
			|| ! in_array( $screen->id, $valid_screens, true )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Check to see if this notice is dismissed.
	 *
	 * @return bool
	 */
	private function is_notice_dismissed() {
		return (bool) get_option( self::DISMISSED_NOTIFICATION_OPTION, false );
	}

	/**
	 * Check if the user can update plugins.
	 *
	 * @return bool
	 */
	private function can_update_plugins() {
		return current_user_can( 'activate_plugins' );
	}

	/**
	 * Check if a Sensei WooCommerce.com extension is installed.
	 */
	private function has_wccom_sensei_extension() {
		if ( ! function_exists( 'get_plugins' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$has_sensei_wccom_extension = false;
		$plugins                    = get_plugins();
		foreach ( $plugins as $plugin ) {
			if ( isset( $plugin['Woo'] ) && in_array( $plugin['Woo'], self::SENSEI_WCCOM_EXTENSIONS, true ) ) {
				$has_sensei_wccom_extension = true;
				break;
			}
		}

		return $has_sensei_wccom_extension;
	}

	/**
	 * Handle the dismissal of the notice.
	 *
	 * @access private
	 */
	public function handle_notice_dismiss() {
		check_ajax_referer( self::DISMISS_NOTICE_NONCE_ACTION, 'nonce' );

		if ( ! $this->can_update_plugins() ) {
			wp_die( '', '', 403 );
		}

		if ( $this->is_notice_dismissed() ) {
			return;
		}

		update_option( self::DISMISSED_NOTIFICATION_OPTION, true );
	}
}
