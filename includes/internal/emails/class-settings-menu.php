<?php
/**
 * File containing the Settings_Menu class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings_Menu
 *
 * @internal
 *
 * @since 4.12.0
 */
class Settings_Menu {

	/**
	 * Initialize the class and add hooks.
	 *
	 * @internal
	 */
	public function init() {
		add_filter( 'sensei_settings_tabs', [ $this, 'replace_email_tab' ] );
	}


	/**
	 * Replace the email tab with a link to new settings.
	 *
	 * @internal
	 * @access private
	 *
	 * @param array $sections The existing sections.
	 * @return array
	 */
	public function replace_email_tab( array $sections ) {
		$sections['email-notification-settings'] = array(
			'name'        => __( 'Emails', 'sensei-lms' ),
			'description' => __( 'Settings for email notifications sent from your site.', 'sensei-lms' ),
			'href'        => admin_url( 'admin.php?page=sensei-settings&tab=email-notification-settings' ),
		);
		return $sections;
	}
}

