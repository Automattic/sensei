<?php
/**
 * File containing the Sensei_Email_Customization class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Email_Customization
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Sensei_Email_Customization {

	/**
	 * Class instance.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Email post type.
	 *
	 * @var Sensei_Email_Post_Type
	 */
	private $post_type;

	/**
	 * Email blocks configurations.
	 *
	 * @var Sensei_Email_Blocks
	 */
	private $blocks;

	/**
	 * Sensei_Settings_Menu instance.
	 *
	 * @var Sensei_Settings_Menu
	 */
	private $settings_menu;

	/**
	 * Sensei_Email_Settings_Tab instance.
	 *
	 * @var Sensei_Email_Settings_Tab
	 */
	private $settings_tab;

	/**
	 * Sensei_Email_Customization constructor.
	 *
	 * Prevents other instances from being created outside of `self::instance()`.
	 */
	private function __construct() {
		$this->post_type     = new Sensei_Email_Post_Type();
		$this->settings_menu = new Sensei_Settings_Menu();
		$this->settings_tab  = new Sensei_Email_Settings_Tab();
		$this->blocks        = new Sensei_Email_Blocks();
	}

	/**
	 * Get the singleton instance.
	 *
	 * @internal
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the class and add hooks.
	 *
	 * @internal
	 */
	public function init(): void {
		$this->post_type->init();
		$this->settings_menu->init();
		$this->settings_tab->init();
		$this->blocks->init();
	}
}
