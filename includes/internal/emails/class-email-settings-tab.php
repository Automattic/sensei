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

		$current_subtab = $this->get_current_subtab();

		ob_start();

		$this->render_submenu( $current_subtab );
		$this->render_subtab( $current_subtab );

		return ob_get_clean();
	}

	/**
	 * Get the currently selected subtab.
	 * Defaults to the first defined if none is selected.
	 *
	 * @return array
	 */
	private function get_current_subtab(): array {
		$subtabs        = $this->get_subtabs();
		$default_subtab = $subtabs[0];

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_subtab_key = sanitize_key( wp_unslash( $_GET['subtab'] ?? $default_subtab['key'] ) );

		foreach ( $subtabs as $subtab ) {
			if ( $subtab['key'] === $current_subtab_key ) {
				return $subtab;
			}
		}

		return $default_subtab;
	}

	/**
	 * Get all available subtabs.
	 *
	 * @return array[]
	 */
	private function get_subtabs(): array {
		return [
			[
				'name'            => __( 'Student Emails', 'sensei-lms' ),
				'href'            => admin_url( 'admin.php?page=sensei-settings&tab=email-notification-settings' ),
				'key'             => 'student',
				'render_callback' => [ $this, 'render_student_subtab' ],
			],
			[
				'name'            => __( 'Teacher Emails', 'sensei-lms' ),
				'href'            => admin_url( 'admin.php?page=sensei-settings&tab=email-notification-settings&subtab=teacher' ),
				'key'             => 'teacher',
				'render_callback' => [ $this, 'render_teacher_subtab' ],
			],
			[
				'name'            => __( 'Settings', 'sensei-lms' ),
				'href'            => admin_url( 'admin.php?page=sensei-settings&tab=email-notification-settings&subtab=settings' ),
				'key'             => 'settings',
				'render_callback' => [ $this, 'render_settings_subtab' ],
			],
		];
	}

	/**
	 * Render the submenu.
	 *
	 * @param array $current_subtab The selected subtab.
	 */
	private function render_submenu( array $current_subtab ): void {
		?>
		<div class="sensei-custom-navigation">
			<div class="sensei-custom-navigation__tabbar">
				<?php foreach ( $this->get_subtabs() as $subtab ) : ?>
					<a class="sensei-custom-navigation__tab <?php echo $subtab['key'] === $current_subtab['key'] ? 'active' : ''; ?>"
						href="<?php echo esc_url( $subtab['href'] ); ?>">
						<?php echo esc_html( $subtab['name'] ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the subtab by calling the render callback.
	 *
	 * @param array $subtab The subtab to render.
	 */
	private function render_subtab( array $subtab ): void {
		call_user_func( $subtab['render_callback'] );
	}

	/**
	 * Render the student emails subtab.
	 */
	private function render_student_subtab(): void {
		$list_table = new Email_List_Table();
		$list_table->prepare_items( 'student' );
		$list_table->display();
	}

	/**
	 * Render the teacher emails subtab.
	 */
	private function render_teacher_subtab(): void {
		$list_table = new Email_List_Table();
		$list_table->prepare_items( 'teacher' );
		$list_table->display();
	}

	/**
	 * Render the settings subtab.
	 */
	private function render_settings_subtab(): void {
		echo 'TODO';
	}
}
