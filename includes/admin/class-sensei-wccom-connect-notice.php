<?php
/**
 * File containing the class Sensei_WCCOM_Connect_Notice
 *
 * @package sensei-lms
 * @since   3.11.0
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
	const DISMISS_NOTICE_NONCE_ACTION   = 'sensei-lms-wccom-connect-dismiss';
	const DISMISSED_NOTIFICATION_OPTION = 'sensei-wccom-connect-dismissed';

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

		wp_enqueue_script( 'sensei-dismiss-notices' );

		$wccom_connect_url = $this->get_wccom_connect_url();
		?>
		<div id="sensei-lms-wccom-connect-notice" class="notice sensei-notice is-dismissible" data-dismiss-action="sensei_dismiss_wccom_connect_notice"
				data-dismiss-nonce="<?php echo esc_attr( wp_create_nonce( self::DISMISS_NOTICE_NONCE_ACTION ) ); ?>">
			<div class='sensei-notice__wrapper'>
				<div class='sensei-notice__content'>
					<?php
					esc_html_e(
						'Your site needs to be connected to your WooCommerce.com account before Sensei extensions can be updated.',
						'sensei-lms'
					);
					?>
				</div>
			</div>
			<div class='sensei-notice__actions'>
				<a href="<?php echo esc_url( $wccom_connect_url ); ?>" class="button button-primary">
					<?php esc_html_e( 'Connect account', 'sensei-lms' ); ?>
				</a>
			</div>
		</div>
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
			'edit-course',
			'plugins',
			'plugins-network',
			'sensei-lms_page_sensei_learners',
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
		return count( Sensei_Extensions::instance()->get_installed_plugins( true ) ) > 0;
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
