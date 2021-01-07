<?php
/**
 * File containing Sensei_WCPC_Prompt class.
 *
 * @package Sensei\Admin
 * @since   3.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Exit survey upon plugin deactivation.
 *
 * @since 3.7.0
 */
class Sensei_WCPC_Prompt {
	const DISMISS_PROMPT_OPTION = 'sensei_dismiss_wcpc_prompt';

	/**
	 * Sensei_WCPC_Prompt constructor.
	 */
	public function __construct() {
		add_action( 'admin_notices', [ $this, 'wcpc_prompt' ] );
		add_action( 'admin_init', [ $this, 'dismiss_prompt' ] );
	}

	/**
	 * WooCommerce Paid Courses plugin prompt.
	 *
	 * @access private
	 */
	public function wcpc_prompt() {
		if ( ! $this->should_show_prompt() ) {
			return;
		}

		$dismiss_url = add_query_arg( 'sensei_dismiss_wcpc_prompt', '1' );
		$dismiss_url = wp_nonce_url( $dismiss_url, 'sensei_dismiss_wcpc_prompt' );

		$install_url = $this->get_wcpc_install_url();

		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<?php esc_html_e( 'Monetize and sell your courses by installing the WooCommerce Paid Courses extension.', 'sensei-lms' ); ?>
				<a href="https://woocommerce.com/products/woocommerce-paid-courses/" target="_blank" rel="noopener noreferrer" data-sensei-log-event="wcpc_upgrade_learn_more"><?php esc_html_e( 'Learn more', 'sensei-lms' ); ?></a>
				<?php esc_html_e( 'or', 'sensei-lms' ); ?>
				<a href="<?php echo esc_url( $install_url ); ?>" class="button-primary" data-sensei-log-event="wcpc_upgrade_install"><?php esc_html_e( 'Install now', 'sensei-lms' ); ?></a>
			</p>
			<a href="<?php echo esc_url( $dismiss_url ); ?>" class="notice-dismiss sensei-dismissible-link">
				<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'sensei-lms' ); ?></span>
			</a>
		</div>
		<?php
	}

	/**
	 * Get WCPC install URL.
	 *
	 * @access private
	 *
	 * @return string WCPC install URL.
	 */
	protected function get_wcpc_install_url() {
		$install_url = 'https://woocommerce.com/cart';
		$install_url = add_query_arg( Sensei_Utils::get_woocommerce_connect_data(), $install_url );
		$install_url = add_query_arg(
			[
				'wccom-replace-with' => $this->get_wcpc_wccom_product_id(),
				'wccom-back'         => rawurlencode( 'plugins.php' ),
			],
			$install_url
		);

		return $install_url;
	}

	/**
	 * If should show prompt in the context contitions.
	 *
	 * @return boolean
	 */
	private function should_show_prompt() {
		if (
			// User is not admin.
			! current_user_can( 'manage_sensei' )
			// Not edit course page.
			|| 'edit-course' !== get_current_screen()->id
			// No published course.
			|| 0 === wp_count_posts( 'course' )->publish
			// Sensei_WC_Paid_Courses class exists.
			|| class_exists( 'Sensei_WC_Paid_Courses\Sensei_WC_Paid_Courses' )
			// WooCommerce is not active.
			|| ! Sensei_Utils::is_woocommerce_active()
		) {
			return false;
		}

		return 0 === get_option( self::DISMISS_PROMPT_OPTION, 0 );
	}

	/**
	 * Get WCPC WCCom product ID.
	 *
	 * @return string
	 */
	private function get_wcpc_wccom_product_id() {
		$extensions = Sensei_Extensions::instance()->get_extensions( 'plugin' );

		$wcpc_wccom_product_id = '';
		foreach ( $extensions as $extension ) {
			if ( 'sensei-wc-paid-courses' === $extension->product_slug ) {
				$wcpc_wccom_product_id = $extension->wccom_product_id;
				break;
			}
		}

		return $wcpc_wccom_product_id;
	}

	/**
	 * Dismiss WCPC prompt.
	 *
	 * @access private
	 */
	public function dismiss_prompt() {
		if (
			isset( $_GET['sensei_dismiss_wcpc_prompt'] )
			&& '1' === $_GET['sensei_dismiss_wcpc_prompt']
			&& isset( $_GET['_wpnonce'] )
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Don't touch the nonce.
			&& wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'sensei_dismiss_wcpc_prompt' )
			&& current_user_can( 'manage_sensei' )
		) {
			update_option( self::DISMISS_PROMPT_OPTION, 1 );
		}
	}
}
