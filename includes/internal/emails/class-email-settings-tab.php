<?php
/**
 * File containing the Email_Settings_Tab class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Email_Settings_Tab
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Email_Settings_Tab {

	/**
	 * Initialize the class and add hooks.
	 *
	 * @internal
	 */
	public function init() {
		add_filter( 'sensei_settings_tab_content', [ $this, 'tab_content' ], 10, 2 );
	}

	/**
	 * Render the email settings tab.
	 *
	 * @internal
	 * @access private
	 *
	 * @param string $content  Existing content.
	 * @param string $tab_name The current tab name.
	 */
	public function tab_content(string $contet, string $tab_name ): string {
		if ( 'email-notification-settings' !== $tab_name ) {
			return $contet;
		}

		ob_start();
		?>
			<?php
			// For demo purposes.
			require_once ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php';
			set_current_screen( 'edit-sensei_email' );
			$list_table = new \WP_Posts_List_Table( [ 'screen' => get_current_screen() ] );
			$list_table->prepare_items();
			$list_table->display();
			?>
		<?php

		return ob_get_clean();
	}
}
