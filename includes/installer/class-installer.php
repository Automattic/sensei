<?php
/**
 * File containing the class \Sensei\Installer\Installer.
 *
 * @package sensei
 * @since $$next-version$$
 */

namespace Sensei\Installer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Installer class.
 *
 * Responsible for running DB updates that need to be run per version.
 *
 * @since $$next-version$$
 */
class Installer {
	/**
	 * Instance of the class.
	 *
	 * @since $$next-version$$
	 * @var self
	 */
	private static $instance;

	/**
	 * The database schema class.
	 *
	 * @since $$next-version$$
	 * @var Schema
	 */
	private $schema;

	/**
	 * Constructor.
	 *
	 * @since $$next-version$$
	 *
	 * @param Schema $schema Schema migration object.
	 */
	public function __construct( Schema $schema ) {
		$this->schema = $schema;
	}

	/**
	 * Fetches an instance of the class.
	 *
	 * @since $$next-version$$
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( ! self::$instance ) {
			self::$instance = new self( new Schema() );
		}

		return self::$instance;
	}

	/**
	 * Initialize necessary hooks.
	 *
	 * @since $$next-version$$
	 */
	public function init() {
		register_activation_hook( SENSEI_LMS_PLUGIN_FILE, [ $this, 'install' ] );
		add_action( 'plugins_loaded', [ $this, 'install' ] );
		add_action( 'init', [ $this, 'migrate' ], 5 );
	}

	/**
	 * Run the installer.
	 *
	 * This method is executed when the plugin is installed or updated.
	 *
	 * @since $$next-version$$
	 */
	public function install() {
		if (
			! is_blog_installed()
			|| $this->is_installing()
			|| ! $this->requires_install()
		) {
			return;
		}

		set_transient( 'sensei_lms_installing', 'yes', MINUTE_IN_SECONDS * 10 );

		$this->schema->create_tables();
		$this->update_version();

		delete_transient( 'sensei_lms_installing' );

		/**
		 * Fires after the installation completes.
		 *
		 * @since $$next-version$$
		 */
		do_action( 'sensei_lms_installed' );
	}

	/**
	 * Get the Schema instance.
	 *
	 * @since $$next-version$$
	 *
	 * @return Schema
	 */
	public function get_schema(): Schema {
		return $this->schema;
	}

	/**
	 * Check if the installer is running.
	 *
	 * @since $$next-version$$
	 *
	 * @return bool
	 */
	private function is_installing(): bool {
		return 'yes' === get_transient( 'sensei_lms_installing' );
	}

	/**
	 * Determine if the installer needs to be run by checking the plugin's version.
	 *
	 * @since $$next-version$$
	 *
	 * @return bool
	 */
	private function requires_install(): bool {
		$version = get_option( 'sensei_lms_version' );

		return version_compare( $version, SENSEI_LMS_VERSION, '<' );
	}

	/**
	 * Update the plugin's version to the current one.
	 *
	 * @since $$next-version$$
	 */
	private function update_version() {
		update_option( 'sensei_lms_version', SENSEI_LMS_VERSION );
	}
}

