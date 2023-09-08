<?php
/**
 * Enables the Sensei WP-CLI commands.
 *
 * @package sensei
 */

defined( 'ABSPATH' ) || exit;

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
	}
}
