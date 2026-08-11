<?php
/**
 * File containing the class Sensei_Command_Palette.
 *
 * @package sensei-lms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that registers Sensei post types with the block editor Command Palette.
 *
 * @since $$next-version$$
 */
class Sensei_Command_Palette {
	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Sensei_Command_Palette constructor. Prevents other instances from being created outside of `self::instance()`.
	 */
	private function __construct() {}

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

	/**
	 * Initializes the class.
	 */
	public function init() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue command palette assets. Not scoped to a specific screen or post type
	 * since the command palette is available everywhere in wp-admin.
	 *
	 * Loaded in the footer (`in_footer = true`) so the script executes after the
	 * DOM is ready — matches the convention in `class-sensei-home.php`.
	 */
	public function enqueue_assets() {
		Sensei()->assets->enqueue( 'sensei-command-palette', 'admin/command-palette/index.js', array(), true );
	}
}
