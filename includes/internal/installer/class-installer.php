<?php
/**
 * File containing the class \Sensei\Internal\Installer\Installer.
 *
 * @package sensei
 * @since 4.16.1
 */

namespace Sensei\Internal\Installer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Installer class.
 *
 * Responsible for running DB updates that need to be run per version.
 *
 * @internal
 *
 * @since 4.16.1
 */
class Installer {

	private const SENSEI_VERSION_OPTION_NAME = 'sensei-version';
	const SENSEI_INSTALL_VERSION_OPTION_NAME = 'sensei-install-version';

	/**
	 * Instance of the class.
	 *
	 * @since 4.16.1
	 * @var self
	 */
	private static $instance;

	/**
	 * The database schema class.
	 *
	 * @since 4.16.1
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
	 * @internal
	 *
	 * @since 4.16.1
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
	 * @internal
	 *
	 * @since 4.16.1
	 *
	 * @param string|null $version Current Sensei version.
	 * @return self
	 */
	public static function instance( ?string $version = null ): self {
		if ( ! self::$instance ) {
			$schema         = new Schema( Sensei()->feature_flags );
			self::$instance = new self( $schema, new Updates_Factory(), $version );
		}

		return self::$instance;
	}

	/**
	 * Initialize necessary hooks.
	 *
	 * @internal
	 *
	 * @since 4.16.1
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
	 * @internal
	 *
	 * @since 4.16.1
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
		 * @since 4.16.1
		 */
		do_action( 'sensei_lms_installed' );
	}

	/**
	 * Checks for plugin update tasks and ensures the current version is set.
	 *
	 * @internal
	 *
	 * @since 4.16.1
	 */
	public function update(): void {
		$current_version = get_option( self::SENSEI_VERSION_OPTION_NAME );
		$is_new_install  = $this->is_new_install( $current_version );
		$updates         = $this->updates_factory->create( $current_version, $this->version, $is_new_install );

		$updates->run_updates();
		$this->update_version( $is_new_install );
	}

	/**
	 * Get the Schema instance.
	 *
	 * @internal
	 *
	 * @since 4.16.1
	 *
	 * @return Schema
	 */
	public function get_schema(): Schema {
		return $this->schema;
	}

	/**
	 * Check if the installer is running.
	 *
	 * @since 4.16.1
	 *
	 * @return bool
	 */
	private function is_installing(): bool {
		return 'yes' === get_transient( 'sensei_lms_installing' );
	}

	/**
	 * Determine if the installer needs to be run by checking the plugin's version.
	 *
	 * @since 4.16.1
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
	 * @since 4.16.1
	 *
	 * @param bool $is_new_install Whether this is a new install.
	 */
	private function update_version( bool $is_new_install ): void {
		if ( ! $this->version ) {
			return;
		}
		update_option( self::SENSEI_VERSION_OPTION_NAME, $this->version );

		if ( $is_new_install ) {
			update_option( self::SENSEI_INSTALL_VERSION_OPTION_NAME, $this->version );
		}
	}

	/**
	 * Checks if the plugin is being installed for the first time.
	 *
	 * @param mixed $current_version The current version of the plugin, based on settings.
	 *
	 * @return bool
	 */
	private function is_new_install( $current_version ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Lightweight query run only once before post type is registered.
		$course_sample_id = (int) $wpdb->get_var( "SELECT `ID` FROM {$wpdb->posts} WHERE `post_type`='course' LIMIT 1" );

		return ! $current_version && empty( $course_sample_id );
	}
}

