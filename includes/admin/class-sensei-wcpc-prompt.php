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
 * WCPC prompt to install the Paid Courses plugin.
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
		add_action( 'admin_init', [ $this, 'redirect_to_install' ] );

		$this->wcpc_extension = $this->get_wcpc_extension();
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

		$install_url = add_query_arg( 'sensei_wcpc_prompt_install', '1' );
		$install_url = wp_nonce_url( $install_url, 'sensei_wcpc_prompt_install' );

		$link = '<a href="https://woocommerce.com/products/woocommerce-paid-courses/" target="_blank" rel="noopener noreferrer" data-sensei-log-event="wcpc_upgrade_learn_more">' . __( 'WooCommerce Paid Courses extension', 'sensei-lms' ) . '</a>';

		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<?php
					echo sprintf(
						// translators: Placeholder is the learn more link.
						esc_html__( 'Monetize and sell your courses by installing the %s.', 'sensei-lms' ),
						wp_kses_post( $link )
					);
				?>
			</p>
			<p>
				<a href="<?php echo esc_url( $install_url ); ?>" class="button-primary" data-sensei-log-event="wcpc_upgrade_install">
					<?php esc_html_e( 'Install extension', 'sensei-lms' ); ?>
				</a>
			</p>
			<a href="<?php echo esc_url( $dismiss_url ); ?>" class="notice-dismiss sensei-dismissible-link">
				<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'sensei-lms' ); ?></span>
			</a>
		</div>
		<?php
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
			|| ! $this->has_published_courses()
			// WCPC is installed.
			|| $this->is_wcpc_installed()
			// WooCommerce is not active.
			|| ! Sensei_Utils::is_woocommerce_active()
		) {
			return false;
		}

		return '0' === get_option( self::DISMISS_PROMPT_OPTION, '0' );
	}

	/**
	 * Redirect to WCPC installation URL.
	 *
	 * @access private
	 */
	public function redirect_to_install() {
		if ( ! isset( $this->wcpc_extension->wccom_product_id ) ) {
			return;
		}

		if (
			isset( $_GET['sensei_wcpc_prompt_install'] )
			&& '1' === $_GET['sensei_wcpc_prompt_install']
			&& isset( $_GET['_wpnonce'] )
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Don't touch the nonce.
			&& wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'sensei_wcpc_prompt_install' )
		) {
			$install_url = 'https://woocommerce.com/cart';
			$install_url = add_query_arg( Sensei_Utils::get_woocommerce_connect_data(), $install_url );
			$install_url = add_query_arg(
				[
					'wccom-replace-with' => $this->wcpc_extension->wccom_product_id,
					'wccom-back'         => rawurlencode( 'plugins.php' ),
				],
				$install_url
			);

			// Log Jetpack event.
			sensei_log_jetpack_event( 'wcpc_upgrade_wccom_install' );

			// Allow safe redirect to woocommerce.com.
			add_filter(
				'allowed_redirect_hosts',
				function( $hosts ) {
					$hosts[] = 'woocommerce.com';
					return $hosts;
				}
			);

			wp_safe_redirect( $install_url );
			exit;
		}
	}

	/**
	 * Get WCPC extension.
	 *
	 * @return object WCPC extension object.
	 */
	private function get_wcpc_extension() {
		$extensions = Sensei_Extensions::instance()->get_extensions( 'plugin' );

		if ( ! $extensions ) {
			return null;
		}

		$wcpc_extension = null;
		foreach ( $extensions as $extension ) {
			if ( 'sensei-wc-paid-courses' === $extension->product_slug ) {
				$wcpc_extension = $extension;
				break;
			}
		}

		return $wcpc_extension;
	}

	/**
	 * Whether WCPC is installed.
	 *
	 * @access private
	 *
	 * @return boolean
	 */
	protected function is_wcpc_installed() {
		if ( ! isset( $this->wcpc_extension->plugin_file ) ) {
			return false;
		}

		return null !== Sensei_Plugins_Installation::instance()->get_installed_plugin_path( $this->wcpc_extension->plugin_file );
	}

	/**
	 * Check if there are published courses.
	 *
	 * @return boolean
	 */
	private function has_published_courses() {
		$course_args = [
			'post_type'        => 'course',
			'posts_per_page'   => 1,
			'post_status'      => 'publish',
			'suppress_filters' => 0,
			'fields'           => 'ids',
		];

		// Ignores the sample course in the query.
		$sample_course = get_page_by_path( Sensei_Data_Port_Manager::SAMPLE_COURSE_SLUG, OBJECT, 'course' );
		if ( $sample_course ) {
			$course_args['post__not_in'] = [ $sample_course->ID ];
		}

		$courses_query = new WP_Query( $course_args );

		return 0 !== $courses_query->found_posts;
	}
}
