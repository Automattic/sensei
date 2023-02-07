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
 * @since $$next-version$$
 */
class Settings_Menu {
	

	public function init() {
		add_action( 'sensei_settings_tabs', [ $this, 'replace_email_tab' ] );
	}

	public function replace_email_tab( array $sections ) {
		$sections['email-notification-settings'] = array(
			'name'        => __( 'Emails', 'sensei-lms' ),
			'description' => __( 'Settings for email notifications sent from your site.', 'sensei-lms' ),
			'href'        => admin_url( 'edit.php?post_type=' . Email_Post_Type::POST_TYPE ),
		);
		return $sections;
	}

}
