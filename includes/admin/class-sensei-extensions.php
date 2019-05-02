<?php
/**
 * File containing Sensei_Extensions class.
 *
 * @package Sensei\Admin
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Extensions class.
 *
 * All functionality pertaining to the admin area's extension directory.
 *
 * @since 2.0.0
 */
final class Sensei_Extensions {
	const SENSEILMS_PRODUCTS_API_BASE_URL = 'https://senseilms.com/wp-json/senseilms-products/1.0';

	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Courses constructor. Prevents other instances from being created outside of `Sensei_Extensions::instance()`.
	 */
	private function __construct() {}

	/**
	 * Initializes the class and adds all filters and actions related to the extension directory.
	 *
	 * @since 2.0.0
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu_item' ), 60 );
	}

	/**
	 * Enqueues admin scripts when needed on different screens.
	 *
	 * @since  2.0.0
	 * @access private
	 */
	public function enqueue_admin_assets() {
		$screen = get_current_screen();
		if ( in_array( $screen->id, array( 'sensei-lms_page_sensei-extensions' ), true ) ) {
			wp_enqueue_style( 'sensei-admin-extensions', Sensei()->plugin_url . 'assets/css/extensions.css', '', Sensei()->version, 'screen' );
		}
	}

	/**
	 * Call API to get Sensei extensions.
	 *
	 * @since  2.0.0
	 *
	 * @param  string $type      Product type ('plugin' or 'theme').
	 * @param  string $category  Category to fetch (null = all).
	 * @return array
	 */
	private function get_extensions( $type = null, $category = null ) {
		$extension_request_key = md5( $type . '|' . $category );
		$extensions            = get_transient( 'sensei_extensions_' . $extension_request_key );

		if ( false === $extensions ) {
			$raw_extensions = wp_safe_remote_get(
				add_query_arg(
					array(
						array(
							'category' => $category,
							'type'     => $type,
						),
					),
					self::SENSEILMS_PRODUCTS_API_BASE_URL . '/search'
				)
			);
			if ( ! is_wp_error( $raw_extensions ) ) {
				$extensions = json_decode( wp_remote_retrieve_body( $raw_extensions ) )->products;
				set_transient( 'sensei_extensions_' . $extension_request_key, $extensions, DAY_IN_SECONDS );
			}
		}

		return $extensions;
	}

	/**
	 * Get resources (such as categories and product types) for the extensions screen.
	 *
	 * @since  2.0.0
	 *
	 * @return array of objects.
	 */
	private function get_resources() {
		$extension_resources = get_transient( 'sensei_extensions_resources' );
		if ( false === $extension_resources ) {
			$raw_resources = wp_safe_remote_get(
				add_query_arg(
					array(
						'version' => Sensei()->version,
						'lang'    => get_locale(),
					),
					self::SENSEILMS_PRODUCTS_API_BASE_URL . '/resources'
				)
			);
			if ( ! is_wp_error( $raw_resources ) ) {
				$extension_resources = json_decode( wp_remote_retrieve_body( $raw_resources ) );
				if ( $extension_resources ) {
					set_transient( 'sensei_extensions_resources', $extension_resources, DAY_IN_SECONDS );
				}
			}
		}

		return $extension_resources;
	}

	/**
	 * Get messages for the extensions page.
	 *
	 * @since  2.0.0
	 *
	 * @return array
	 */
	private function get_messages() {
		$extension_messages = get_transient( 'sensei_extensions_messages' );
		if ( false === $extension_messages ) {
			$raw_messages = wp_safe_remote_get(
				add_query_arg(
					array(
						'version' => Sensei()->version,
						'lang'    => get_locale(),
					),
					self::SENSEILMS_PRODUCTS_API_BASE_URL . '/messages'
				)
			);
			if ( ! is_wp_error( $raw_messages ) ) {
				$extension_messages = json_decode( wp_remote_retrieve_body( $raw_messages ) );
				if ( $extension_messages ) {
					set_transient( 'sensei_extensions_messages', $extension_messages, DAY_IN_SECONDS );
				}
			}
		}

		return $extension_messages;
	}

	/**
	 * Adds the menu item for the Extensions page.
	 *
	 * @since  2.0.0
	 * @access private
	 */
	public function add_admin_menu_item() {
		add_submenu_page( 'sensei', __( 'Sensei LMS Extensions', 'sensei-lms' ), __( 'Extensions', 'sensei-lms' ), 'install_plugins', 'sensei-extensions', array( $this, 'render' ) );
	}

	/**
	 * Renders the extensions page.
	 *
	 * @since  2.0.0
	 * @access private
	 */
	public function render() {
		// phpcs:ignore WordPress.Security.NonceVerification
		$category = isset( $_GET['category'] ) ? sanitize_text_field( $_GET['category'] ) : null;

		// phpcs:ignore WordPress.Security.NonceVerification
		$type = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : null;

		// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- used in view
		$messages   = $this->get_messages();
		$resources  = $this->get_resources();
		$extensions = $this->get_extensions( $type, $category );
		// phpcs:enable
		include_once dirname( __FILE__ ) . '/views/html-admin-page-extensions.php';
	}

	/**
	 * Fetches an instance of the class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

}
