<?php
/**
 * File containing the Sensei_Home_Task_Pro_Upsell class.
 *
 * @package sensei-lms
 * @since $$next-version$$
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Home_Task_Pro_Upsell.
 *
 * @since $$next-version$$
 */
class Sensei_Home_Task_Pro_Upsell implements Sensei_Home_Task {

	/**
	 * The ID for the task.
	 *
	 * @return string
	 */
	public static function get_id(): string {
		return 'sensei-home-task-pro-upsell';
	}

	/**
	 * Number used to sort in frontend.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 250;
	}

	/**
	 * Task title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Sell your course with Sensei Pro', 'sensei-lms' );
	}

	/**
	 * Task url.
	 *
	 * @return string
	 */
	public function get_url(): ?string {
		// Here external=true is used to show the external link icon in the frontend component.
		return rest_url( 'sensei-internal/v1/home/sensei-pro-upsell-redirect?_wpnonce=' . wp_create_nonce( 'wp_rest' ) . '&external=true' );
	}

	/**
	 * Whether the task is completed or not.
	 *
	 * @return bool
	 */
	public function is_completed(): bool {
		return get_option( self::get_id(), false );
	}

	/**
	 * Mark the task as completed.
	 *
	 * @internal
	 */
	public static function mark_completed_and_redirect() {
		sensei_log_event( 'home_task_complete', [ 'type' => self::get_id() ] );

		update_option( self::get_id(), true );

		// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- We're redirecting to an external URL.
		wp_redirect( 'https://senseilms.com/sensei-pro/?utm_source=plugin_sensei&utm_medium=upsell&utm_campaign=sensei-home' );
	}

	/**
	 * Whether the task is active or not.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			return false;
		}

		// If the user does not intend to sell courses, we do not need to add this task.
		$features = Sensei()->setup_wizard->get_wizard_user_data( 'features' );

		if ( ! in_array( 'woocommerce', $features['selected'], true ) ) {
			return false;
		}

		return ! Sensei_Plugins_Installation::instance()->get_installed_plugin_path( 'sensei-pro.php' ) &&
			! Sensei_Plugins_Installation::instance()->get_installed_plugin_path( 'woothemes-sensei.php' );
	}
}
