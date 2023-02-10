<?php
/**
 * File containing the Email_Customization class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Email_Customization
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Email_Customization {

	/**
	 * Class instance.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Email post type.
	 *
	 * @var Email_Post_Type
	 */
	private $post_type;

	/**
	 * Settings_Menu instance.
	 *
	 * @var Settings_Menu
	 */
	private $settings_menu;

	/**
	 * Email_Settings_Tab instance.
	 *
	 * @var Email_Settings_Tab
	 */
	private $settings_tab;

	/**
	 * Email_List_Table_Actions instance.
	 *
	 * @var Email_List_Table_Actions
	 */
	private $list_table_actions;

	/**
	 * Email_Customization constructor.
	 *
	 * Prevents other instances from being created outside of `self::instance()`.
	 */
	private function __construct() {
		$this->post_type          = new Email_Post_Type();
		$this->settings_menu      = new Settings_Menu();
		$this->settings_tab       = new Email_Settings_Tab();
		$this->list_table_actions = new Email_List_Table_Actions();
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
		$this->list_table_actions->init();
	}
}
