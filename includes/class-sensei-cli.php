<?php
/**
 * Enables the Sensei WP-CLI commands.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CLI class.
 *
 * @since 4.3.0
 */
class Sensei_CLI {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->register();
	}

	/**
	 * Register the CLI commands.
	 */
	private function register() {
		WP_CLI::add_command( 'sensei db seed', Sensei_DB_Seed_Command::class );
		WP_CLI::add_command( 'sensei validate progress', Sensei_Validate_Progress_Command::class );
		WP_CLI::add_command( 'sensei validate quiz-submission', Sensei_Validate_Quiz_Submission_Command::class );
	}
}
