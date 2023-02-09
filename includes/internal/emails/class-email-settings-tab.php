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
	 * @return string
	 */
	public function tab_content( string $content, string $tab_name ): string {
		if ( 'email-notification-settings' !== $tab_name ) {
			return $content;
		}

		ob_start();

		$this->render_submenu();

		$list_table = new Email_List_Table();
		$list_table->prepare_items();
		$list_table->display();

		return ob_get_clean();
	}

	/**
	 * Render the submenu.
	 */
	private function render_submenu() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_subtab = sanitize_key( wp_unslash( $_GET['subtab'] ?? 'student' ) );

		$tabs = [
			[
				'name' => __( 'Student Emails', 'sensei-lms' ),
				'href' => admin_url( 'admin.php?page=sensei-settings&tab=email-notification-settings' ),
				'key'  => 'student',
			],
			[
				'name' => __( 'Teacher Emails', 'sensei-lms' ),
				'href' => admin_url( 'admin.php?page=sensei-settings&tab=email-notification-settings&subtab=teacher' ),
				'key'  => 'teacher',
			],
			[
				'name' => __( 'Settings', 'sensei-lms' ),
				'href' => admin_url( 'admin.php?page=sensei-settings&tab=email-notification-settings&subtab=settings' ),
				'key'  => 'settings',
			],
		];

		?>
		<div class="sensei-custom-navigation">
			<div class="sensei-custom-navigation__tabbar">
				<?php foreach ( $tabs as $tab ) : ?>
					<a class="sensei-custom-navigation__tab <?php echo $tab['key'] === $current_subtab ? 'active' : ''; ?>"
						href="<?php echo esc_url( $tab['href'] ); ?>">
						<?php echo esc_html( $tab['name'] ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}
