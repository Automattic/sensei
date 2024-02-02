<?php
/**
 * File containing the class Progress_Tables_Eraser.
 *
 * @package sensie
 */

namespace Sensei\Internal\Tools;

use Sensei\Internal\Installer\Eraser;
use Sensei\Internal\Services\Progress_Storage_Settings;
use Sensei_Tool_Interactive_Interface;
use Sensei_Tool_Interface;
use Sensei_Tools;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Progress_Tables_Eraser.
 *
 * @internal
 *
 * @since 4.19.0
 */
class Progress_Tables_Eraser implements Sensei_Tool_Interface, Sensei_Tool_Interactive_Interface {
	/**
	 * Nonce action.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'sensei-tools-progress-tables-eraser';

	/**
	 * Eraser instance.
	 *
	 * @var Eraser
	 */
	private $eraser;

	/**
	 * Progress_Tables_Eraser constructor.
	 */
	public function __construct() {
		$this->eraser = new Eraser();
	}

	/**
	 * Initialize the tool.
	 */
	public function init(): void {
		add_filter( 'sensei_tools', [ $this, 'register_tool' ] );
	}

	/**
	 * Register the tool.
	 *
	 * @param array $tools List of tools.
	 *
	 * @return array
	 */
	public function register_tool( $tools ) {
		$tools[] = $this;
		return $tools;
	}

	/**
	 * Get the ID of the tool.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'progress-tables-eraser';
	}

	/**
	 * Get the name of the tool.
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Delete student progress tables', 'sensei-lms' );
	}

	/**
	 * Get the description of the tool.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Delete student progress and quiz submission tables. This will delete those tables, but won\'t affect comment-based data. The tables can be deleted only if progress sync is disabled (Settings -> Experimental Features).', 'sensei-lms' );
	}

	/**
	 * Run the tool.
	 */
	public function process() {
		if ( empty( $_POST['delete-tables'] ) ) {
			return;
		}

		$wpnonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		if ( empty( $wpnonce ) || ! wp_verify_nonce( wp_unslash( $wpnonce ), self::NONCE_ACTION ) ) {
			Sensei_Tools::instance()->trigger_invalid_request( $this );
			return;
		}

		if ( empty( $_POST['confirm'] ) ) {
			Sensei_Tools::instance()->add_user_message( __( 'You must confirm the action before it can be performed.', 'sensei-lms' ), true );
			wp_safe_redirect( $this->get_tool_url() );
			exit;
		}

		$results = $this->eraser->drop_tables();

		if ( count( $results ) > 0 ) {
			$message = sprintf(
				/* translators: %s: list of tables. */
				__( 'The following tables have been deleted: %s', 'sensei-lms' ),
				implode( ', ', $results )
			);
		} else {
			$message = __( 'No tables were deleted.', 'sensei-lms' );
		}

		Sensei_Tools::instance()->add_user_message( $message );

		// Redirect to the tools page to avoid confusion: the tool is no longer available.
		wp_safe_redirect( Sensei_Tools::instance()->get_tools_url() );
		exit;
	}

	/**
	 * Is the tool currently available?
	 *
	 * @return bool True if tool is available.
	 */
	public function is_available() {
		// Disable the tool if tables are in use.
		if ( Progress_Storage_Settings::is_sync_enabled() || Progress_Storage_Settings::is_tables_repository() ) {
			return false;
		}

		global $wpdb;

		foreach ( $this->eraser->get_tables() as $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Output tool view for interactive action methods.
	 */
	public function output() {
		// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Variable used in view.
		$tool_id = $this->get_id();
		include __DIR__ . '/views/html-progress-tables-eraser-form.php';
	}

	/**
	 * Get the URL for this tool.
	 *
	 * @return string
	 */
	private function get_tool_url(): string {
		return admin_url( 'admin.php?page=sensei-tools&tool=' . $this->get_id() );
	}
}
