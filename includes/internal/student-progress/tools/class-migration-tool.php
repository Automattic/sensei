<?php
/**
 * File containing Migration_Tool class.
 *
 * @package sensei
 * @since 4.16.1
 */

namespace Sensei\Internal\Student_Progress\Tools;

use Sensei\Internal\Installer\Migrations\Student_Progress_Migration;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Migration_Tool class.
 *
 * @since 4.16.1
 */
class Migration_Tool implements \Sensei_Tool_Interface {

	/**
	 * Sensei_Tools instance.
	 *
	 * @var \Sensei_Tools
	 */
	private $tools;

	/**
	 * Migration_Tool constructor.
	 *
	 * @param \Sensei_Tools $tools Sensei_Tools instance.
	 */
	public function __construct( \Sensei_Tools $tools ) {
		$this->tools = $tools;
	}

	/**
	 * Initialize the tool.
	 */
	public function init() {
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
		return 'student-progress-migration';
	}

	/**
	 * Get the name of the tool.
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Migrate comment-based student progress', 'sensei-lms' );
	}

	/**
	 * Get the description of the tool.
	 *
	 * @return string
	 */
	public function get_description() {
		return __(
			'Migrate comment-based progress to the new table-based progress system.',
			'sensei-lms'
		);
	}

	/**
	 * Run the tool.
	 */
	public function process() {
		$migration     = new Student_Progress_Migration();
		$rows_migrated = $migration->run( false );
		$errors        = $migration->get_errors();
		$result        = empty( $errors );

		// translators: %d: number of statuses migrated.
		$message = sprintf( __( 'Progress entries created based on comments: %d.', 'sensei-lms' ), $rows_migrated );
		if ( ! $result ) {
			$message .= ' ' . __( 'Errors:', 'sensei-lms' );
			foreach ( $errors as $error ) {
				$message .= ' ' . $error;
			}
		}

		$this->tools->add_user_message( $message, ! $result );
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
