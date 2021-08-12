<?php
/**
 * Setup Wizard.
 *
 * @package Sensei\Setup_Wizard
 * @since   3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Setup Wizard Class
 * All setup wizard functionality.
 *
 * @package Sensei
 * @author  Automattic
 * @since   3.1.0
 */
class Sensei_Setup_Wizard {
	const SUGGEST_SETUP_WIZARD_OPTION = 'sensei_suggest_setup_wizard';
	const WCCOM_INSTALLING_TRANSIENT  = 'sensei_setup_wizard_wccom_installing';
	const USER_DATA_OPTION            = 'sensei_setup_wizard_data';
	const MC_LIST_ID                  = '4fa225a515';
	const MC_USER_ID                  = '7a061a9141b0911d6d9bafe3a';
	const MC_GDPR_FIELD               = '23563';
	const MC_URL                      = 'https://senseilms.us19.list-manage.com/subscribe/post?u=' . self::MC_USER_ID . '&id=' . self::MC_LIST_ID;

	/**
	 * Default value for setup wizard user data.
	 *
	 * @var array
	 */
	private $user_data_defaults = [
		'features'  => [
			'selected' => [],
		],
		'purpose'   => [
			'selected' => [],
			'other'    => '',
		],
		'steps'     => [],
		'__version' => '1',
	];

	/**
	 * URL Slug for Setup Wizard Wizard page
	 *
	 * @var string
	 */
	public $page_slug;

	/**
	 * Creation of Sensei pages.
	 *
	 * @var Sensei_Setup Wizard_Pages
	 */
	public $pages;

	/**
	 * Instance of singleton.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Fetches the instance of the class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Sensei_Setup Wizard constructor.
	 */
	public function __construct() {

		$this->page_slug = 'sensei_setup_wizard';
		$this->pages     = new Sensei_Setup_Wizard_Pages();

		if ( is_admin() ) {

			add_action( 'admin_menu', [ $this, 'register_wizard_page' ], 20 );
			add_action( 'admin_notices', [ $this, 'setup_wizard_notice' ] );
			add_action( 'admin_init', [ $this, 'skip_setup_wizard' ] );
			add_action( 'admin_init', [ $this, 'activation_redirect' ] );
			add_action( 'current_screen', [ $this, 'add_setup_wizard_help_tab' ] );
			add_action( 'admin_init', [ $this, 'close_wccom_install' ] );

			// Maybe prevent WooCommerce help tab.
			add_filter( 'woocommerce_enable_admin_help_tab', [ $this, 'should_enable_woocommerce_help_tab' ] );

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for comparison.
			if ( isset( $_GET['page'] ) && ( $_GET['page'] === $this->page_slug ) ) {
				$this->prepare_wizard_page();
			}
		}
	}

	/**
	 * Register the Setup Wizard admin page via a hidden submenu.
	 *
	 * @link https://developer.wordpress.org/reference/functions/add_submenu_page/#comment-445
	 */
	public function register_wizard_page() {
		if ( current_user_can( 'manage_sensei' ) ) {
			add_submenu_page(
				'options.php',
				__( 'Sensei LMS - Setup Wizard', 'sensei-lms' ),
				__( 'Sensei LMS - Setup Wizard', 'sensei-lms' ),
				'manage_sensei',
				$this->page_slug,
				[ $this, 'render_wizard_page' ]
			);
		}
	}

	/**
	 * Enqueue JS for Setup Wizard page.
	 *
	 * @access private
	 */
	public function enqueue_scripts() {
		$handle = 'sensei-setup-wizard';
		Sensei()->assets->enqueue( $handle, 'setup-wizard/index.js', [ 'sensei-event-logging' ], true );
		Sensei()->assets->preload_data( [ '/sensei-internal/v1/setup-wizard' ] );

		wp_localize_script(
			$handle,
			'sensei_setup_wizard',
			[ 'nonce' => wp_create_nonce( $handle ) ]
		);
	}

	/**
	 * Enqueue CSS for Setup Wizard page.
	 *
	 * @access private
	 */
	public function enqueue_styles() {
		Sensei()->assets->enqueue( 'sensei-setup-wizard', 'setup-wizard/style.css', [ 'sensei-wp-components' ] );
	}

	/**
	 * Set script translations.
	 *
	 * @access private
	 * @deprecated 3.8.0
	 */
	public function setup_wizard_set_script_translations() {
		_deprecated_function( __METHOD__, '3.8.0' );
	}

	/**
	 * Add global classes for Setup Wizard page.
	 *
	 * @param string $classes Current class list.
	 *
	 * @access private
	 * @return string Extended class list.
	 */
	public function filter_body_class( $classes ) {
		$classes .= ' sensei-wp-admin-fullscreen ';
		return $classes;
	}

	/**
	 * Set up hooks for loading Setup Wizard page assets.
	 */
	public function prepare_wizard_page() {
		add_action( 'admin_print_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_print_styles', [ $this, 'enqueue_styles' ] );
		add_action( 'admin_body_class', [ $this, 'filter_body_class' ] );

		add_filter( 'show_admin_bar', '__return_false' );
	}

	/**
	 * Redirect after first activation.
	 *
	 * @access private
	 */
	public function activation_redirect() {
		if (
			// Check if activation redirect is needed.
			! get_transient( 'sensei_activation_redirect' )
			// Test whether the context of execution comes from async action scheduler.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for comparison.
			|| ( isset( $_REQUEST['action'] ) && 'as_async_request_queue_runner' === $_REQUEST['action'] )
			// On these pages, or during these events, postpone the redirect.
			|| wp_doing_ajax() || wp_doing_cron() || is_network_admin() || ! current_user_can( 'manage_sensei' )
		) {
			return;
		}

		delete_transient( 'sensei_activation_redirect' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for comparison.
		if ( isset( $_GET['activate-multi'] ) ) {
			return;
		}

		$this->redirect_to_setup_wizard();
	}

	/**
	 * Redirect to setup wizard.
	 */
	protected function redirect_to_setup_wizard() {
		wp_safe_redirect( admin_url( 'admin.php?page=' . $this->page_slug ) );
		exit;
	}

	/**
	 * Render app container for setup wizard.
	 */
	public function render_wizard_page() {

		?>
		<div id="sensei-setup-wizard-page" class="sensei-setup-wizard">

		</div>
		<?php
	}

	/**
	 * Check if current screen is selected to display the wizard notice.
	 *
	 * @return boolean
	 */
	private function should_current_page_display_wizard() {
		$screen = get_current_screen();

		if ( false !== strpos( $screen->id, 'sensei-lms_page_sensei' ) ) {
			return true;
		}

		$screens_without_sensei_prefix = [
			'dashboard',
			'plugins',
			'edit-sensei_message',
			'edit-course',
			'edit-course-category',
			'course_page_course-order',
			'edit-module',
			'course_page_module-order',
			'edit-lesson',
			'edit-lesson-tag',
			'lesson_page_lesson-order',
			'edit-question',
			'question',
			'edit-question-category',
		];

		return in_array( $screen->id, $screens_without_sensei_prefix, true );
	}

	/**
	 * Setup wizard notice.
	 *
	 * @access private
	 */
	public function setup_wizard_notice() {
		if (
			! $this->should_current_page_display_wizard()
			|| ! get_option( self::SUGGEST_SETUP_WIZARD_OPTION, 0 )
			|| ! current_user_can( 'manage_sensei' )
		) {
			return;
		}

		$setup_wizard_user_data   = get_option( self::USER_DATA_OPTION, false );
		$setup_wizard_in_progress = $setup_wizard_user_data && ! empty( $setup_wizard_user_data['steps'] );

		$setup_url = admin_url( 'admin.php?page=' . $this->page_slug );

		$skip_url = add_query_arg( 'sensei_skip_setup_wizard', '1' );
		$skip_url = wp_nonce_url( $skip_url, 'sensei_skip_setup_wizard' );
		?>
		<div id="message" class="updated sensei-message sensei-connect">
			<p><?php echo wp_kses_post( __( '<strong>Welcome to Sensei LMS</strong> &#8211; You\'re almost ready to start creating online courses!', 'sensei-lms' ) ); ?></p>

			<p class="submit">
				<a href="<?php echo esc_url( $setup_url ); ?>" class="button-primary">
					<?php
					if ( $setup_wizard_in_progress ) {
						esc_html_e( 'Complete Setup', 'sensei-lms' );
					} else {
						esc_html_e( 'Run the Setup Wizard', 'sensei-lms' );
					}
					?>
				</a>

				<a class="button" href="<?php echo esc_url( $skip_url ); ?>">
					<?php esc_html_e( 'Skip setup', 'sensei-lms' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Skip setup wizard.
	 *
	 * @access private
	 */
	public function skip_setup_wizard() {
		if (
			isset( $_GET['sensei_skip_setup_wizard'] )
			&& '1' === $_GET['sensei_skip_setup_wizard']
			&& isset( $_GET['_wpnonce'] )
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Don't touch the nonce.
			&& wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'sensei_skip_setup_wizard' )
			&& current_user_can( 'manage_sensei' )
		) {
			$this->finish_setup_wizard();
		}
	}

	/**
	 * Mark the setup wizard as finished.
	 */
	public function finish_setup_wizard() {
		update_option( self::SUGGEST_SETUP_WIZARD_OPTION, 0 );
	}

	/**
	 * Prevent displaying WooCommerce help tab in Sensei admin pages.
	 *
	 * @access private
	 *
	 * @param boolean $allow Allow showing the WooCommerce help tab.
	 *
	 * @return boolean
	 */
	public function should_enable_woocommerce_help_tab( $allow ) {
		$post_types_to_prevent = [ 'course', 'lesson', 'sensei_message' ];

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for comparison.
		if ( isset( $_GET['post_type'] ) && in_array( $_GET['post_type'], $post_types_to_prevent, true ) ) {
			return false;
		}

		return $allow;
	}

	/**
	 * Check if should show help tab or not.
	 *
	 * @param string $screen_id Screen ID to check if should show the help tab.
	 *
	 * @return boolean
	 */
	private function should_show_help_screen( $screen_id ) {
		return 'edit-course' === $screen_id;
	}

	/**
	 * Add setup wizard help tab.
	 *
	 * @param WP_Screen $screen Current screen.
	 *
	 * @access private
	 */
	public function add_setup_wizard_help_tab( $screen ) {
		$link_track_event = 'setup_wizard_click';

		if ( ! $screen || ! $this->should_show_help_screen( $screen->id ) || ! current_user_can( 'manage_sensei' ) ) {
			return;
		}

		$screen->add_help_tab(
			[
				'id'      => 'sensei_lms_setup_wizard_tab',
				'title'   => __( 'Setup wizard', 'sensei-lms' ),
				'content' =>
					'<h2>' . __( 'Sensei LMS Setup Wizard', 'sensei-lms' ) . '</h2>' .
					'<h3>' . __( 'Setup Wizard', 'sensei-lms' ) . '</h3>' .
					'<p>' . __( 'If you need to access the setup wizard again, please click on the button below.', 'sensei-lms' ) . '</p>' .
					'<p><a href="' . admin_url( 'admin.php?page=' . $this->page_slug ) . '" class="button button-primary" data-sensei-log-event="' . $link_track_event . '">' . __( 'Setup wizard', 'sensei-lms' ) . '</a></p>',
			]
		);
	}

	/**
	 * Get saved Setup Wizard user data.
	 *
	 * @param string $key Limit data returned to selected key.
	 *
	 * @return mixed
	 */
	public function get_wizard_user_data( $key = null ) {
		$data = get_option( self::USER_DATA_OPTION, [] );

		// Reset data if the schema changed.
		if ( empty( $data['__version'] ) || $data['__version'] !== $this->user_data_defaults['__version'] ) {
			$data = $this->user_data_defaults;
			update_option( self::USER_DATA_OPTION, $data );
		}

		return empty( $key ) ? $data : $data[ $key ];
	}

	/**
	 * Save Setup Wizard user data.
	 *
	 * @param array $changes Key-value pair of updates to save.
	 *
	 * @return bool Whether value was updated.
	 */
	public function update_wizard_user_data( $changes ) {
		$option = array_merge( $this->get_wizard_user_data(), $changes );
		return update_option( self::USER_DATA_OPTION, $option );
	}

	/**
	 * Get data used for Mailing list sign-up form.
	 *
	 * @return array The data.
	 */
	public function get_mailing_list_form_data() {

		return [
			'admin_email' => get_option( 'admin_email', '' ),
			'mc_url'      => self::MC_URL,
			'gdpr_field'  => self::MC_GDPR_FIELD,
		];
	}

	/**
	 * Get data used for WooCommerce.com purchase redirect for feature installation.
	 *
	 * @deprecated 3.7.0
	 * @return array The data.
	 */
	public function get_woocommerce_connect_data() {
		_deprecated_function( __METHOD__, '3.7.0', 'Sensei_Utils::get_woocommerce_connect_data' );

		return Sensei_Utils::get_woocommerce_connect_data();
	}

	/**
	 * Get feature with status.
	 *
	 * @param stdClass   $extension          Extension object.
	 * @param stdClass[] $installing_plugins Plugins which are installing.
	 * @param stdClass[] $selected_plugins   Plugins selected for installation.
	 *
	 * @return stdClass Extension with status.
	 */
	private function get_feature_with_status( $extension, $installing_plugins, $selected_plugins ) {
		$installing_key = array_search( $extension->product_slug, wp_list_pluck( $installing_plugins, 'product_slug' ), true );

		if ( in_array( $extension->product_slug, $selected_plugins, true ) && isset( $extension->wccom_product_id ) ) {
			$extension->status = 'external';
		}
		if ( false !== $installing_key ) {
			if ( isset( $installing_plugins[ $installing_key ]->error ) ) {
				$extension->error  = $installing_plugins[ $installing_key ]->error;
				$extension->status = 'error';
			} else {
				$extension->status = 'installing';
			}
		}

		if ( Sensei_Plugins_Installation::instance()->is_plugin_active( $extension->plugin_file ) ) {
			$extension->status = 'installed';
		}

		return $extension;
	}

	/**
	 * Get Sensei extensions for setup wizard.
	 *
	 * @param boolean $clear_active_plugins_cache Clear cache for `is_plugin_active`.
	 *
	 * @return array Sensei extensions.
	 */
	public function get_sensei_extensions( $clear_active_plugins_cache = false ) {
		if ( $clear_active_plugins_cache ) {
			wp_cache_delete( 'alloptions', 'options' );
		}

		$extensions = Sensei_Extensions::instance()->get_extensions( 'plugin', 'setup-wizard-extensions' );

		// Add WooCommerce.
		array_push( $extensions, Sensei_Utils::get_woocommerce_plugin_information() );

		$installing_plugins = Sensei_Plugins_Installation::instance()->get_installing_plugins();
		$selected_plugins   = $this->get_wizard_user_data( 'features' )['selected'];

		if ( ! $extensions ) {
			$extensions = [];
		}

		$extensions = array_map(
			function( $extension ) use ( $installing_plugins, $selected_plugins ) {
				// Decode price.
				if ( isset( $extension->price ) && 0 !== $extension->price ) {
					$extension->price = html_entity_decode( $extension->price );
				}

				return $this->get_feature_with_status( $extension, $installing_plugins, $selected_plugins );
			},
			$extensions
		);

		return $extensions;
	}

	/**
	 * Filter extensions to install and call installation.
	 *
	 * @param string[] $extension_slugs Extension slugs to install.
	 */
	public function install_extensions( $extension_slugs ) {

		$extensions_to_install = [];
		$wccom_extensions      = [];

		foreach ( $this->get_sensei_extensions() as $extension ) {
			if ( ! in_array( $extension->product_slug, $extension_slugs, true ) ) {
				continue;
			}

			if ( isset( $extension->wccom_product_id ) ) {
				$wccom_extensions[] = $extension->product_slug;
			} else {
				$extensions_to_install[] = $extension;
			}
		}

		if ( ! empty( $wccom_extensions ) ) {
			$event_name       = 'setup_wizard_features_install_paid';
			$event_properties = [ 'slug' => join( ',', $wccom_extensions ) ];

			sensei_log_jetpack_event( $event_name, $event_properties );
		}

		set_transient( self::WCCOM_INSTALLING_TRANSIENT, $wccom_extensions, 10 * 60 );

		Sensei_Plugins_Installation::instance()->install_plugins( $extensions_to_install );
	}

	/**
	 * Close the browser tab if it's a redirect from WooCommerce.com after a successful extension install.
	 */
	public static function close_wccom_install() {
		if (
			isset( $_SERVER['HTTP_REFERER'] ) &&
			0 === strpos( $_SERVER['HTTP_REFERER'], 'https://woocommerce.com/checkout' ) && // phpcs:ignore sanitization ok.
			false !== get_transient( self::WCCOM_INSTALLING_TRANSIENT )
		) {
			delete_transient( self::WCCOM_INSTALLING_TRANSIENT );

			// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NoExplicitVersion -- Inline script.
			wp_register_script( 'sensei-close-window', '', [], false, false );
			wp_enqueue_script( 'sensei-close-window' );
			wp_add_inline_script( 'sensei-close-window', ' window.close() ' );
		}
	}
}
