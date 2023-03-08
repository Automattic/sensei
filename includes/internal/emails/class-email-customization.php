<?php
/**
 * File containing the Email_Customization class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

use Sensei_Assets;
use Sensei_Settings;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Interface;

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
	 * Email_Repository instance.
	 *
	 * @var Email_Repository
	 */
	public $repository;

	/**
	 * Email_Customization constructor.
	 *
	 * Prevents other instances from being created outside of `self::instance()`.
	 *
	 * @param Sensei_Settings                      $settings Sensei_Settings instance.
	 * @param Sensei_Assets                        $assets Sensei_Assets instance.
	 * @param Lesson_Progress_Repository_Interface $lesson_progress_repository Lesson_Progress_Repository_Interface instance.
	 */
	private function __construct( Sensei_Settings $settings, Sensei_Assets $assets, Lesson_Progress_Repository_Interface $lesson_progress_repository ) {
		$this->repository         = new Email_Repository();
		$this->patterns           = new Email_Patterns();
		$this->post_type          = new Email_Post_Type();
		$this->settings_menu      = new Settings_Menu();
		$this->settings_tab       = new Email_Settings_Tab( $settings );
		$this->blocks             = new Email_Blocks();
		$this->email_sender       = new Email_Sender( $this->repository, $settings, $this->patterns );
		$this->email_generator    = new Email_Generator( $this->repository, $lesson_progress_repository );
		$this->list_table_actions = new Email_List_Table_Actions();
		$this->preview            = new Email_Preview( $this->email_sender, $assets );

		$seeder                     = new Email_Seeder( new Email_Seeder_Data(), $this->repository );
		$this->recreate_emails_tool = new Recreate_Emails_Tool( $seeder, \Sensei_Tools::instance() );
	}

	/**
	 * Get the singleton instance.
	 *
	 * @internal
	 *
	 * @param Sensei_Settings|null                      $settings Sensei_Settings instance.
	 * @param Sensei_Assets|null                        $assets Sensei_Assets instance.
	 * @param Lesson_Progress_Repository_Interface|null $lesson_progress_repository Lesson_Progress_Repository_Interface instance.
	 *
	 * @return self
	 */
	public static function instance(
		Sensei_Settings $settings = null,
		Sensei_Assets $assets = null,
		Lesson_Progress_Repository_Interface $lesson_progress_repository = null
	): self {
		if ( ! self::$instance ) {
			self::$instance = new self(
				$settings ?? Sensei()->settings,
				$assets ?? Sensei()->assets,
				$lesson_progress_repository ?? Sensei()->lesson_progress_repository
			);
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
		$emails_to_be_disabled = [
			[ 'sensei_course_status_updated', Sensei()->emails, 'teacher_completed_course', 10 ],
			[ 'sensei_user_course_start', Sensei()->emails, 'teacher_started_course', 10 ],
			[ 'sensei_user_quiz_submitted', Sensei()->emails, 'teacher_quiz_submitted', 10 ],
			[ 'sensei_course_status_updated', Sensei()->emails, 'learner_completed_course', 10 ],
			[ 'sensei_course_new_teacher_assigned', Sensei()->teacher, 'teacher_course_assigned_notification', 10 ],
			[ 'sensei_user_lesson_end', Sensei()->emails, 'teacher_completed_lesson', 10 ],
			[ 'sensei_user_quiz_grade', Sensei()->emails, 'learner_graded_quiz', 10 ],
			[ 'sensei_private_message_reply', Sensei()->emails, 'new_message_reply', 10 ],
			[ 'sensei_new_private_message', Sensei()->emails, 'teacher_new_message', 10 ],
		];

		/**
		 * Filter the list of legacy emails to be disabled.
		 *
		 * @hook sensei_disable_legacy_emails
		 *
		 * @since $$next-version$$
		 *
		 * @param {array} $emails_to_be_disabled List of legacy emails to be disabled.
		 *
		 * @return {array} List of legacy emails to be disabled.
		 */
		$emails_to_be_disabled = apply_filters( 'sensei_disable_legacy_emails', $emails_to_be_disabled );

		foreach ( $emails_to_be_disabled as $email ) {
			remove_action( $email[0], [ $email[1], $email[2] ], $email[3] );
		}
	}
}
