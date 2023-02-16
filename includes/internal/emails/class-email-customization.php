<?php
/**
 * File containing the Email_Customization class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

use Sensei_Settings;

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
	 * Email blocks configurations.
	 *
	 * @var Email_Blocks
	 */
	private $blocks;

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
	 * Email_Sender instance.
	 *
	 * @var Email_Sender
	 */
	private $email_sender;

	/**
	 * Email_Generator instance.
	 *
	 * @var Email_Generator
	 */
	private $email_generator;

	/**
	 * Email_Customization constructor.
	 *
	 * Prevents other instances from being created outside of `self::instance()`.
	 *
	 * @param \Sensei_Settings $settings Sensei_Settings instance.
	 */
	private function __construct( \Sensei_Settings $settings ) {
		$this->post_type     = new Email_Post_Type();
		$this->settings_menu = new Settings_Menu();
		$this->settings_tab  = new Email_Settings_Tab( $settings );
		$this->blocks        = new Email_Blocks();
		$this->email_sender    = new Email_Sender();
		$this->email_generator = new Email_Generator();
	}

	/**
	 * Get the singleton instance.
	 *
	 * @internal
	 *
	 * @param \Sensei_Settings $settings Sensei_Settings instance.
	 * @return self
	 */
	public static function instance( Sensei_Settings $settings ): self {
		if ( ! self::$instance ) {
			self::$instance = new self( $settings );
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
		$this->email_sender->init();
		$this->email_generator->init();
	}
}
