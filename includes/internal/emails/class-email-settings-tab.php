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
 * @since 4.12.0
 */
class Email_Settings_Tab {

	/**
	 * Sensei_Settings instance.
	 *
	 * @var \Sensei_Settings
	 */
	private $settings;

	/**
	 * Email_Settings_Tab constructor.
	 *
	 * @param \Sensei_Settings $settings Sensei_Settings instance.
	 */
	public function __construct( \Sensei_Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Initialize the class and add hooks.
	 *
	 * @internal
	 */
	public function init() {
		add_action( 'sensei_settings_after_links', [ $this, 'render_tabs' ] );
		add_filter( 'sensei_settings_content', [ $this, 'get_content' ], 10, 2 );
		add_filter( 'sensei_settings_fields', [ $this, 'add_reply_to_setting' ] );
	}

	/**
	 * Render tabs on the Emails settings page.
	 *
	 * @internal
	 * @access private
	 *
	 * @param string $tab_name The current tab name.
	 */
	public function render_tabs( string $tab_name ) {
		if ( 'email-notification-settings' !== $tab_name ) {
			return;
		}

		$current_subtab = $this->get_current_subtab();

		ob_start();
		$this->render_submenu( $current_subtab );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in render_submenu function.
		echo ob_get_clean();
	}

	/**
	 * Get the content on the Emails settings page.
	 *
	 * @internal
	 * @access private
	 *
	 * @param string $tab_name The current tab name.
	 * @param string $content  Tab content.
	 * @return string
	 */
	public function get_content( string $tab_name, string $content = '' ): string {
		if ( 'email-notification-settings' !== $tab_name ) {
			return $content;
		}

		$current_subtab = $this->get_current_subtab();

		ob_start();
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
			<div class="sensei-custom-navigation__tabbar">
				<?php foreach ( $this->get_subtabs() as $subtab ) : ?>
					<a class="sensei-custom-navigation__tab <?php echo $subtab['key'] === $current_subtab['key'] ? 'active' : ''; ?>"
						href="<?php echo esc_url( $subtab['href'] ); ?>">
						<?php echo esc_html( $subtab['name'] ); ?>
					</a>
				<?php endforeach; ?>
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
		$this->render_list_table_for_type( 'student' );
	}

	/**
	 * Render the teacher emails subtab.
	 */
	private function render_teacher_subtab(): void {
		$this->render_list_table_for_type( 'teacher' );
	}

	/**
	 * Reder list table for given type.
	 *
	 * @param string $type Type of emails to render.
	 */
	private function render_list_table_for_type( string $type ): void {
		$list_table = new Email_List_Table( new Email_Repository() );
		$list_table->prepare_items( $type );
		$list_table->display();
	}

	/**
	 * Render the settings subtab.
	 */
	private function render_settings_subtab(): void {
		global $wp_settings_fields;

		$fields_to_display = array_filter(
			$wp_settings_fields['sensei-settings']['email-notification-settings'] ?? [],
			function( $field_key ) {
				return in_array( $field_key, [ 'email_from_name', 'email_from_address', 'email_reply_to_name', 'email_reply_to_address' ], true );
			},
			ARRAY_FILTER_USE_KEY
		);

		$fields_to_display = array_map(
			function( $field ) {
				unset( $field['args']['data']['description'] );
				$class = $field['args']['class'] ?? '';
				if ( $class ) {
					$class = ' class="' . esc_attr( $field['args']['class'] ) . '"';
				}

				$field['args']['class'] = $class;
				return $field;
			},
			$fields_to_display
		);

		$options = $this->settings->get_settings() ?? [];
		unset( $options['email_from_name'], $options['email_from_address'], $options['email_reply_to_address'], $options['email_reply_to_name'] );

		include dirname( __FILE__ ) . '/views/html-settings.php';
	}

	/**
	 * Display hidden field.
	 *
	 * @param array $key   Field key.
	 * @param mixed $value Field value.
	 */
	private function form_field_hidden( $key, $value ) {
		if ( ! is_array( $value ) ) {
			echo '<input name="sensei-settings[' . esc_attr( $key ) . ']" type="hidden" value="' . esc_attr( $value ) . '" />' . "\n";
		} else {
			foreach ( $value as $v ) {
				echo '<input name="sensei-settings[' . esc_attr( $key ) . '][]" type="hidden" value="' . esc_attr( $v ) . '" />' . "\n";
			}
		}
	}

	/**
	 * Add the Reply To email address setting field.
	 *
	 * @since 4.12.0
	 * @access private
	 *
	 * @param array $fields The fields to add to.
	 *
	 * @return array The fields with the Reply To email address field added.
	 */
	public function add_reply_to_setting( $fields ) {

		$fields['email_reply_to_name'] = [
			'name'     => __( '"Reply To" Name', 'sensei-lms' ),
			'type'     => 'input',
			'default'  => '',
			'section'  => 'email-notification-settings',
			'required' => 0,
		];

		$fields['email_reply_to_address'] = [
			'name'     => __( '"Reply To" Address', 'sensei-lms' ),
			'type'     => 'email',
			'default'  => get_bloginfo( 'admin_email' ),
			'section'  => 'email-notification-settings',
			'required' => 0,
		];

		return $fields;
	}
}
