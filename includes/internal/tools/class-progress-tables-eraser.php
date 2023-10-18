<?php
/**
 * File containing the class Progress_Tables_Eraser.
 *
 * @package sensie
 */

namespace Sensei\Internal\Tools;

use Sensei\Internal\Installer\Schema;
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
 * @since $$next-version$$
 */
class Progress_Tables_Eraser implements Sensei_Tool_Interface {

	/**
	 * Sensei schema.
	 *
	 * @var Schema
	 */
	private $schema;

	/**
	 * Progress_Tables_Eraser constructor.
	 *
	 * @param Schema $schema Sensei schema.
	 */
	public function __construct( Schema $schema ) {
		$this->schema = $schema;
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
		return 'student-progress-eraser';
	}

	/**
	 * Get the name of the tool.
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Erase content of student progress tables', 'sensei-lms' );
	}

	/**
	 * Get the description of the tool.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Erase the content of the student progress and quiz submission tables. This will delete all data in those tables, but won\'t affect comment-based data.', 'sensei-lms' );
	}

	/**
	 * Run the tool.
	 */
	public function process() {
		global $wpdb;

		foreach ( $this->schema->get_tables() as $table ) {
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) === $table ) {
				$wpdb->query( "TRUNCATE TABLE $table" );
			}
		}
	}

	/**
	 * Is the tool currently available?
	 *
	 * @return bool True if tool is available.
	 */
	public function is_available() {
		return true;
	}
}
