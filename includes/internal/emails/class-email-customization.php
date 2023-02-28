<?php
/**
 * File containing the Email_Customization class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

use Sensei_Assets;
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
	 * Email_List_Table_Actions instance.
	 *
	 * @var Email_List_Table_Actions
	 */
	private $list_table_actions;

	/**
	 * Recreate_Emails_Tool instance.
	 *
	 * @var Recreate_Emails_Tool
	 */
	private $recreate_emails_tool;

	/**
	 * Email_Patterns instance.
	 *
	 * @var Email_Patterns
	 */
	public $patterns;

	/**
	 * Email_Preview instance.
	 *
	 * @var Email_Preview
	 */
	private $preview;

	/**
	 * Email_Customization constructor.
	 *
	 * Prevents other instances from being created outside of `self::instance()`.
	 *
	 * @param Sensei_Settings $settings Sensei_Settings instance.
	 * @param Sensei_Assets   $assets Sensei_Assets instance.
	 */
	private function __construct( Sensei_Settings $settings, Sensei_Assets $assets ) {
		$repository = new Email_Repository();
		$seeder     = new Email_Seeder( new Email_Seeder_Data(), $repository );

		$this->post_type            = new Email_Post_Type();
		$this->settings_menu        = new Settings_Menu();
		$this->settings_tab         = new Email_Settings_Tab( $settings );
		$this->blocks               = new Email_Blocks();
		$this->email_sender         = new Email_Sender( $repository );
		$this->email_generator      = new Email_Generator();
		$this->list_table_actions   = new Email_List_Table_Actions();
		$this->recreate_emails_tool = new Recreate_Emails_Tool( $seeder, \Sensei_Tools::instance() );
		$this->patterns             = new Email_Patterns();
		$this->preview              = new Email_Preview( $this->email_sender, $assets );
	}

	/**
	 * Get the singleton instance.
	 *
	 * @internal
	 *
	 * @param Sensei_Settings $settings Sensei_Settings instance.
	 * @param Sensei_Assets   $assets Sensei_Assets instance.
	 *
	 * @return self
	 */
	public static function instance( Sensei_Settings $settings, Sensei_Assets $assets ): self {
		if ( ! self::$instance ) {
			self::$instance = new self( $settings, $assets );
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
		$this->list_table_actions->init();
		$this->recreate_emails_tool->init();
		$this->patterns->init();
		$this->preview->init();

		add_action( 'init', [ $this, 'disable_legacy_emails' ] );
	}

	/**
	 * Disable legacy emails.
	 *
	 * @access private
	 */
	public function disable_legacy_emails() {
		remove_action( 'sensei_course_status_updated', [ \Sensei()->emails, 'teacher_completed_course' ] );
		remove_action( 'sensei_user_course_start', [ \Sensei()->emails, 'teacher_started_course' ] );
	}
}
