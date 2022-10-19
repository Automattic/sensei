<?php
/**
 * File containing the class \Sensei\Installer\Installer.
 *
 * @package sensei
 * @since $$next-version$$
 */

namespace Sensei\Installer;

use Sensei_Updates;

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

	private const SENSEI_VERSION_OPTION_NAME = 'sensei-version';

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
	 * Sensei Updates factory.
	 *
	 * @var Updates_Factory
	 */
	private $updates_factory;
	/**
	 * Current Sensei version.
	 *
	 * @var string|null
	 */
	private $version;

	/**
	 * Constructor.
	 *
	 * @since $$next-version$$
	 *
	 * @param Schema          $schema Schema migration object.
	 * @param Updates_Factory $updates_factory Updates factory object.
	 * @param string|null     $version Current Sensei version.
	 */
	public function __construct( Schema $schema, Updates_Factory $updates_factory, ?string $version ) {
		$this->schema          = $schema;
		$this->updates_factory = $updates_factory;
		$this->version         = $version;
	}

	/**
	 * Fetches an instance of the class.
	 *
	 * @since $$next-version$$
	 *
	 * @param string|null $version Current Sensei version.
	 * @return self
	 */
	public static function instance( ?string $version = null ): self {
		if ( ! self::$instance ) {
			self::$instance = new self( new Schema(), new Updates_Factory(), $version );
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
		add_action( 'init', array( $this, 'update' ) );
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

		delete_transient( 'sensei_lms_installing' );

		/**
		 * Fires after the installation completes.
		 *
		 * @since $$next-version$$
		 */
		do_action( 'sensei_lms_installed' );
	}

	/**
	 * Checks for plugin update tasks and ensures the current version is set.
	 *
	 * @since $$next-version$$
	 */
	public function update(): void {
		$current_version = get_option( self::SENSEI_VERSION_OPTION_NAME );
		$updates         = $this->updates_factory->create( $current_version, $this->version );

		$updates->run_updates();
		$this->update_version();
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
		$version = get_option( self::SENSEI_VERSION_OPTION_NAME );

		return version_compare( $version, SENSEI_LMS_VERSION, '<' );
	}

	/**
	 * Update the plugin's version to the current one.
	 *
	 * @since $$next-version$$
	 */
	private function update_version(): void {
		if ( ! $this->version ) {
			return;
		}
		update_option( self::SENSEI_VERSION_OPTION_NAME, $this->version );
	}
}

