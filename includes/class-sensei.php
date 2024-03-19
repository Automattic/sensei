<?php
/**
 * File containing the Sensei_Main class.
 *
 * @package sensei
 */

use Sensei\Admin\Tour\Sensei_Tour;
use Sensei\Clock\Clock;
use Sensei\Clock\Clock_Interface;
use Sensei\Internal\Action_Scheduler\Action_Scheduler;
use Sensei\Internal\Emails\Email_Customization;
use Sensei\Internal\Installer\Updates_Factory;
use Sensei\Internal\Migration\Migration_Job;
use Sensei\Internal\Migration\Migration_Job_Scheduler;
use Sensei\Internal\Migration\Migrations\Quiz_Migration;
use Sensei\Internal\Migration\Migrations\Student_Progress_Migration;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Answer_Repository_Factory;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Answer_Repository_Interface;
use Sensei\Internal\Quiz_Submission\Grade\Repositories\Grade_Repository_Factory;
use Sensei\Internal\Quiz_Submission\Grade\Repositories\Grade_Repository_Interface;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Submission_Repository_Factory;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Submission_Repository_Interface;
use Sensei\Internal\Services\Progress_Storage_Settings;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Factory;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Interface;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Factory;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Interface;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Quiz_Progress_Repository_Factory;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Quiz_Progress_Repository_Interface;
use Sensei\Internal\Student_Progress\Services\Course_Deleted_Handler;
use Sensei\Internal\Student_Progress\Services\Lesson_Deleted_Handler;
use Sensei\Internal\Student_Progress\Services\Quiz_Deleted_Handler;
use Sensei\Internal\Student_Progress\Services\User_Deleted_Handler;
use Sensei\Internal\Tools\Progress_Tables_Eraser;
use Sensei\WPML\WPML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Responsible for loading Sensei and setting up the Main WordPress hooks.
 *
 * @package Core
 * @author Automattic
 * @since 1.0.0
 */
class Sensei_Main {
	const COMMENT_COUNT_TRANSIENT_PREFIX = 'sensei_comment_counts_';
	const LEGACY_FLAG_OPTION             = 'sensei-legacy-flags';
	const LEGACY_FLAG_WITH_FRONT         = 'with_front';

	/**
	 * Reference to the main plugin file name
	 *
	 * @var string
	 */
	private $main_plugin_file_name;

	/**
	 * The main Sensei Instance.
	 * $_instance to the the main and only instance of the Sensei class.
	 *
	 * @var Sensei_Main
	 * @since 1.8.0
	 */
	protected static $_instance = null;

	/**
	 * Main reference to the plugins current version
	 *
	 * @var string
	 */
	public $version;

	/**
	 * Main reference to the plugin's version when it was installed.
	 * Or false if the install version is not available.
	 *
	 * @since 4.7.0
	 * @var string|false
	 */
	public $install_version;

	/**
	 * Public token, referencing for the text domain.
	 *
	 * @var string
	 */
	public $token = 'sensei';

	/**
	 * Plugin url and path for use when access resources.
	 *
	 * @var string
	 */
	public $plugin_url;

	/**
	 * Plugin path for use when accessing resources.
	 *
	 * @var string
	 */
	public $plugin_path;

	/**
	 * Template url for use when accessing resources.
	 *
	 * @var string
	 */
	public $template_url;

	/**
	 * All Sensei sub classes. Currently used to access functionality contained within
	 * within Sensei sub classes e.g. Sensei()->course->all_courses()
	 *
	 * @var Sensei_PostTypes
	 */
	public $post_types;

	/**
	 * Sensei Settings instance.
	 *
	 * @var Sensei_Settings
	 */
	public $settings;

	/**
	 * Script and stylesheet loading.
	 *
	 * @var Sensei_Assets
	 */
	public $assets;

	/**
	 * Sensei_Course_Results instance.
	 *
	 * @var Sensei_Course_Results
	 */
	public $course_results;

	/**
	 * Sensei_Updates instance.
	 *
	 * @var Sensei_Updates
	 */
	public $updates;

	/**
	 * Sensei_Course instance.
	 *
	 * @var Sensei_Course
	 */
	public $course;

	/**
	 * Sensei_Lesson instance.
	 *
	 * @var Sensei_Lesson
	 */
	public $lesson;

	/**
	 * Sensei_Quiz instance.
	 *
	 * @var Sensei_Quiz
	 */
	public $quiz;

	/**
	 * Sensei_Question instance.
	 *
	 * @var Sensei_Question
	 */
	public $question;

	/**
	 * Sensei_Messages instance.
	 *
	 * @var Sensei_Messages
	 */
	public $messages;

	/**
	 * Sensei_Admin instance.
	 *
	 * @var Sensei_Admin
	 */
	public $admin;

	/**
	 * Sensei_Frontend instance.
	 *
	 * @var Sensei_Frontend
	 */
	public $frontend;

	/**
	 * Sensei_Notices instance.
	 *
	 * @var Sensei_Notices
	 */
	public $notices;

	/**
	 * Sensei_Theme_Integration_Loader instance.
	 *
	 * @var Sensei_Theme_Integration_Loader
	 */
	public $theme_integration_loader;

	/**
	 * Sensei_Grading instance.
	 *
	 * @var Sensei_Grading
	 */
	public $grading;

	/**
	 * Sensei_Emails instance.
	 *
	 * @var Sensei_Emails
	 */
	public $emails;

	/**
	 * Sensei_Learner_Profiles instance.
	 *
	 * @var Sensei_Learner_Profiles
	 */
	public $learner_profiles;

	/**
	 * Sensei_Teacher instance.
	 *
	 * @var Sensei_Teacher
	 */
	public $teacher;

	/**
	 * Sensei_Learner_Management instance.
	 *
	 * @var Sensei_Learner_Management
	 */
	public $learners;

	/**
	 * Global instance for access to the permissions message shown
	 * when users do not have the right privileges to access resources.
	 *
	 * @var array
	 */
	public $permissions_message;

	/**
	 * Sensei_Core_Modules instance.
	 *
	 * @var Sensei_Core_Modules Sensei Modules functionality
	 */
	public $modules;

	/**
	 * Sensei_Analysis instance.
	 *
	 * @var Sensei_Analysis
	 */
	public $analysis;

	/**
	 * Internal REST API.
	 *
	 * @var Sensei_REST_API_Internal
	 */
	public $rest_api_internal;

	/**
	 * Global Usage Tracking object.
	 *
	 * @var Sensei_Usage_Tracking
	 */
	public $usage_tracking;

	/**
	 * Sensei plugin id.
	 *
	 * @var string
	 */
	private $id = 'sensei-lms';

	/**
	 * Shortcode loader.
	 *
	 * @var Sensei_Shortcode_Loader
	 */
	private $shortcode_loader;

	/**
	 * View Helper.
	 *
	 * @var Sensei_View_Helper
	 */
	public $view_helper;

	/**
	 * Experimental features.
	 *
	 * @var Sensei_Feature_Flags
	 */
	public $feature_flags;

	/**
	 * The scheduler which is responsible to recalculate user enrolments.
	 *
	 * @var Sensei_Enrolment_Job_Scheduler
	 */
	private $enrolment_scheduler;

	/**
	 * Setup wizard.
	 *
	 * @var Sensei_Setup_Wizard
	 */
	public $setup_wizard;

	/**
	 * Blocks.
	 *
	 * @var Sensei_Blocks
	 */
	public $blocks;

	/**
	 * Admin notices.
	 *
	 * @var Sensei_Admin_Notices
	 */
	public $admin_notices;

	/**
	 * WPML compatibility class.
	 *
	 * @var WPML
	 *
	 * @psalm-suppress PropertyNotSetInConstructor
	 */
	public $sensei_wpml;

	/**
	 * Course progress repository factory.
	 *
	 * @var Course_Progress_Repository_Factory
	 */
	public $course_progress_repository_factory;

	/**
	 * Course progress repository.
	 *
	 * @var Course_Progress_Repository_Interface
	 */
	public $course_progress_repository;

	/**
	 * Lesson progress repository factory.
	 *
	 * @var Lesson_Progress_Repository_Factory
	 */
	public $lesson_progress_repository_factory;

	/**
	 * Lesson progress repository.
	 *
	 * @var Lesson_Progress_Repository_Interface
	 */
	public $lesson_progress_repository;

	/**
	 * Quiz progress repository factory.
	 *
	 * @var Quiz_Progress_Repository_Factory
	 */
	public $quiz_progress_repository_factory;

	/**
	 * Quiz progress repository.
	 *
	 * @var Quiz_Progress_Repository_Interface
	 */
	public $quiz_progress_repository;

	/**
	 * Quiz submission repository.
	 *
	 * @var Submission_Repository_Interface
	 */
	public $quiz_submission_repository;

	/**
	 * Quiz answer repository.
	 *
	 * @var Answer_Repository_Interface
	 */
	public $quiz_answer_repository;

	/**
	 * Quiz grade repository.
	 *
	 * @var Grade_Repository_Interface
	 */
	public $quiz_grade_repository;

	/**
	 * Migration job scheduler.
	 *
	 * @var Migration_Job_Scheduler|null
	 */
	public $migration_scheduler;

	/**
	 * Action scheduler.
	 *
	 * @var Action_Scheduler|null
	 */
	public $action_scheduler;

	/**
	 * Clock.
	 *
	 * @var Clock_Interface
	 */
	public $clock;

	/**
	 * Sensei Tour.
	 *
	 * @var Sensei_Tour|null
	 */
	public $tour;

	/**
	 * Constructor method.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $main_plugin_file_name The base file of the plugin.
	 * @param  array  $args                  The arguments to pass to the plugin.
	 */
	private function __construct( $main_plugin_file_name, $args ) {

		// Setup object data.
		$this->main_plugin_file_name = $main_plugin_file_name;
		$this->plugin_url            = trailingslashit( plugins_url( '', $this->main_plugin_file_name ) );
		$this->plugin_path           = trailingslashit( dirname( $this->main_plugin_file_name ) );
		/**
		 * Filter the template url.
		 *
		 * @hook sensei_template_url
		 *
		 * @param {string} $template_url The template url.
		 * @return {string} Filtered template url.
		 */
		$this->template_url = apply_filters( 'sensei_template_url', 'sensei/' );
		$this->version      = isset( $args['version'] ) ? $args['version'] : null;

		// Only set the install version if it is included in alloptions. This prevents a query on every page load.
		$alloptions            = wp_load_alloptions();
		$this->install_version = $alloptions['sensei-install-version'] ?? null;

		/**
		 * Filter the clock.
		 *
		 * @hook sensei_clock_init
		 *
		 * @since 4.20.1
		 *
		 * @param {Clock_Interface} $clock The clock.
		 * @return {Clock_Interface} Filtered clock.
		 */
		$this->clock = apply_filters( 'sensei_clock_init', new Clock( wp_timezone() ) );

		// Initialize the core Sensei functionality.
		$this->init();

		// Installation.
		$this->install();

		// Run this on deactivation.
		register_deactivation_hook( $this->main_plugin_file_name, array( $this, 'deactivation' ) );

		// Image Sizes.
		$this->init_image_sizes();

		// Load all hooks.
		$this->load_hooks();

		/**
		 * Fires once all global objects have been set in Sensei.
		 *
		 * @hook sensei_loaded
		 * @since 3.6.0
		 *
		 * @param {Sensei_Main} $sensei Sensei object.
		 */
		do_action( 'sensei_loaded', $this );
	}

	/**
	 * Load the foundations of Sensei.
	 *
	 * @since 1.9.0
	 */
	protected function init() {

		// Localisation.
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );
		add_action( 'update_option_WPLANG', array( $this, 'maybe_initiate_rewrite_rules_flush_after_language_change' ), 10, 2 );
		add_action( 'upgrader_process_complete', array( $this, 'maybe_initiate_rewrite_rules_flush_on_translation_update' ), 10, 2 );

		$this->initialize_cache_groups();
		$this->initialize_global_objects();
		$this->initialize_cli();
		$this->initialize_3rd_party_compatibility();
	}

	/**
	 * Maybe initiate rewrite rules flush when WordPress language has been changed.
	 *
	 * @internal
	 *
	 * @since 4.20.2
	 *
	 * @param mixed $old_value Old value.
	 * @param mixed $new_value New value.
	 */
	public function maybe_initiate_rewrite_rules_flush_after_language_change( $old_value, $new_value ) {
		if ( $old_value !== $new_value ) {
			$this->initiate_rewrite_rules_flush();
		}
	}

	/**
	 * Maybe initiate rewrite rules flush when WordPress translation has been updated.
	 *
	 * @internal
	 *
	 * @since 4.20.2
	 *
	 * @param WP_Upgrader $upgrader_object Upgrader object.
	 * @param array       $options Options.
	 */
	public function maybe_initiate_rewrite_rules_flush_on_translation_update( $upgrader_object, $options ) {
		if ( 'translation' === $options['type'] ) {
			$this->initiate_rewrite_rules_flush();
		}
	}

	/**
	 * Global Sensei Instance
	 *
	 * Ensure that only one instance of the main Sensei class can be loaded.
	 *
	 * @since 1.8.0
	 *
	 * @static
	 *
	 * @see WC()
	 *
	 * @param array $args Arguments to pass to the plugin.
	 * @return self
	 */
	public static function instance( $args ) {

		if ( is_null( self::$_instance ) ) {

			// Sensei requires a reference to the main Sensei plugin file.
			$sensei_main_plugin_file = SENSEI_LMS_PLUGIN_FILE;

			self::$_instance = new self( $sensei_main_plugin_file, $args );

		}

		return self::$_instance;
	}

	/**
	 * This function is linked into the activation
	 * hook to reset flush the urls to ensure Sensei post types show up.
	 *
	 * @since 1.9.0
	 *
	 * @param string $plugin Plugin path.
	 */
	public static function activation_flush_rules( $plugin ) {

		if ( strpos( $plugin, '/sensei-lms.php' ) > 0 ) {

			flush_rewrite_rules( true );

		}
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.8.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'sensei-lms' ), '1.8' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.8.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'sensei-lms' ), '1.8' );
	}

	/**
	 * Initialize the cache groups used in Sensei
	 *
	 * @since 4.6.0
	 */
	protected function initialize_cache_groups() {
		wp_cache_add_non_persistent_groups( 'sensei/temporary' );
	}

	/**
	 * Load the properties for the main Sensei object
	 *
	 * @since 1.9.0
	 */
	public function initialize_global_objects() {
		// Setup settings.
		$this->settings = new Sensei_Settings();

		// Asset loading.
		$this->assets = new Sensei_Assets( $this->plugin_url, $this->plugin_path, $this->version );

		// Feature flags.
		$this->feature_flags = new Sensei_Feature_Flags();

		// Load the shortcode loader into memory, so as to listen to all for
		// all shortcodes on the front end.
		$this->shortcode_loader = new Sensei_Shortcode_Loader();

		// Setup post types.
		$this->post_types = new Sensei_PostTypes();

		// Load Course Results Class.
		$this->course_results = new Sensei_Course_Results();

		// Load the teacher role.
		$this->teacher = new Sensei_Teacher();

		// Add the Course class.
		$this->course = $this->post_types->course;

		// Add the lesson class.
		$this->lesson = $this->post_types->lesson;

		// Add the question class.
		$this->question = $this->post_types->question;

		// Add the question class.
		$this->messages = $this->post_types->messages;

		// Add the quiz class.
		$this->quiz = $this->post_types->quiz;

		// Load the modules class after all plugins are loaded.
		$this->load_modules_class();

		// Load Learner Management Functionality.
		$this->learners = new Sensei_Learner_Management( $this->main_plugin_file_name );

		$this->view_helper = new Sensei_View_Helper();

		$this->usage_tracking = Sensei_Usage_Tracking::get_instance();
		$this->usage_tracking->set_callback(
			array( 'Sensei_Usage_Tracking_Data', 'get_usage_data' )
		);

		// Ensure tracking job is scheduled. If the user does not opt in, no
		// data will be sent.
		$this->usage_tracking->schedule_tracking_task();

		$this->blocks = new Sensei_Blocks();

		Sensei_Learner::instance()->init();
		Sensei_Course_Enrolment_Manager::instance()->init();
		$this->enrolment_scheduler = Sensei_Enrolment_Job_Scheduler::instance();
		$this->enrolment_scheduler->init();
		Sensei_Data_Port_Manager::instance()->init();
		Sensei_Course_Theme_Option::instance()->init();
		Sensei_Course_Theme::instance()->init();
		Sensei_Course_Theme_Editor::instance()->init();
		new Sensei_Customizer();

		// Setup Wizard.
		$this->setup_wizard = Sensei_Setup_Wizard::instance();

		// Sensei Home.
		Sensei_Home::instance()->init();

		Sensei_Scheduler::init();

		// Block patterns.
		Sensei_Block_Patterns::instance()->init();

		// Editor Wizard.
		Sensei_Editor_Wizard::instance()->init();

		// Load Analysis Reports.
		$this->analysis = new Sensei_Analysis( $this->main_plugin_file_name );

		// Admin notices.
		$this->admin_notices = Sensei_Admin_Notices::instance()->init();

		Sensei_Temporary_User::init();

		// Differentiate between administration and frontend logic.
		if ( is_admin() ) {
			// Load Admin Class.
			$this->admin = new Sensei_Admin();

			new Sensei_Import();
			new Sensei_Export();
			new Sensei_Exit_Survey();

			Sensei_No_Users_Table_Relationship::instance()->init();
			SenseiLMS_Plugin_Updater::init();
			Sensei_Course_Pre_Publish_Panel::instance()->init();
		} else {

			// Load Frontend Class.
			$this->frontend = new Sensei_Frontend();

			// Load built in themes support integration.
			$this->theme_integration_loader = new Sensei_Theme_Integration_Loader();

		}
		Sensei_Course_Video_Settings::instance()->init( $this );

		// Load notice Class.
		$this->notices = new Sensei_Notices();

		// Load Grading Functionality.
		$this->grading = new Sensei_Grading( $this->main_plugin_file_name );

		// Load Email Class.
		$this->emails = new Sensei_Emails( $this->main_plugin_file_name );

		// Load Learner Profiles Class.
		$this->learner_profiles = new Sensei_Learner_Profiles();

		// Load WPML compatibility class.
		$this->sensei_wpml = new WPML();
		$this->sensei_wpml->init();

		$this->rest_api_internal = new Sensei_REST_API_Internal();

		// Student progress repositories.
		$tables_feature_enabled = isset( $this->settings->settings['experimental_progress_storage'] )
			&& ( true === $this->settings->settings['experimental_progress_storage'] );

		if ( $tables_feature_enabled ) {
			// Enable tables based progress feature flag.
			add_filter( 'sensei_feature_flag_tables_based_progress', '__return_true' );
		}

		$tables_sync_enabled = $tables_feature_enabled
			&& ( true === $this->settings->settings['experimental_progress_storage_synchronization'] );

		$read_from_tables_setting = isset( $this->settings->settings['experimental_progress_storage_repository'] )
			&& ( Progress_Storage_Settings::TABLES_STORAGE === $this->settings->settings['experimental_progress_storage_repository'] );

		/**
		 * Filter whether to read student progress from tables.
		 *
		 * @since $$next_version$$
		 *
		 * @hook  sensei_student_progress_read_from_tables
		 *
		 * @param {bool} $read_from_tables Whether to read student progress from tables.
		 * @return {bool} Whether to read student progress from tables.
		 */
		$read_from_tables                         = apply_filters( 'sensei_student_progress_read_from_tables', $read_from_tables_setting );
		$this->course_progress_repository_factory = new Course_Progress_Repository_Factory( $tables_sync_enabled, $read_from_tables );
		$this->course_progress_repository         = $this->course_progress_repository_factory->create();
		$this->lesson_progress_repository_factory = new Lesson_Progress_Repository_Factory( $tables_sync_enabled, $read_from_tables );
		$this->lesson_progress_repository         = $this->lesson_progress_repository_factory->create();
		$this->quiz_progress_repository_factory   = new Quiz_Progress_Repository_Factory( $tables_sync_enabled, $read_from_tables );
		$this->quiz_progress_repository           = $this->quiz_progress_repository_factory->create();

		// Quiz submission repositories.
		$this->quiz_submission_repository = ( new Submission_Repository_Factory( $tables_sync_enabled, $read_from_tables ) )->create();
		$this->quiz_answer_repository     = ( new Answer_Repository_Factory( $tables_sync_enabled, $read_from_tables ) )->create();
		$this->quiz_grade_repository      = ( new Grade_Repository_Factory( $tables_sync_enabled, $read_from_tables ) )->create();

		// Progress tables eraser.
		if ( $tables_feature_enabled ) {
			( new Progress_Tables_Eraser() )->init();
		}

		if ( class_exists( 'ActionScheduler_Versions' ) ) {
			$this->action_scheduler = new Action_Scheduler();
		}

		// Student progress migration.
		if ( $tables_feature_enabled && $this->action_scheduler ) {
			$this->init_migration_scheduler();
		}

		// Init student progress handlers.
		( new Course_Deleted_Handler( $this->course_progress_repository ) )->init();
		( new Lesson_Deleted_Handler( $this->lesson_progress_repository ) )->init();
		( new Quiz_Deleted_Handler( $this->quiz_progress_repository ) )->init();
		( new User_Deleted_Handler( $this->course_progress_repository, $this->lesson_progress_repository, $this->quiz_progress_repository ) )->init();

		// Cron for periodically cleaning guest user related data.
		Sensei_Temporary_User_Cleaner::instance()->init();

		$email_customization_enabled = $this->feature_flags->is_enabled( 'email_customization' );
		if ( $email_customization_enabled ) {
			Email_Customization::instance( $this->settings, $this->assets, $this->lesson_progress_repository )->init();
		}

		$this->tour   = null;
		$tour_enabled = $this->feature_flags->is_enabled( 'onboarding_tour' );
		if ( $tour_enabled ) {
			$this->tour = Sensei_Tour::instance();
			$this->tour->init();
		}

		// MailPoet integration.
		/**
		 * Integrate MailPoet by adding lists for courses and groups.
		 *
		 * @since 4.13.0
		 *
		 * @hook  sensei_email_mailpoet_feature
		 *
		 * @param {bool} $enable Enable feature. Default true.
		 * @return {bool} Whether to enable feature.
		 */
		if ( apply_filters( 'sensei_email_mailpoet_feature', true ) ) {
			if ( class_exists( \MailPoet\API\API::class ) ) {
				$mailpoet_api = \MailPoet\API\API::MP( 'v1' );
				new Sensei\Emails\MailPoet\Main( $mailpoet_api );
			}
		}
	}

	/**
	 * Try to initialize Migration_Job_Scheduler.
	 */
	public function init_migration_scheduler(): void {
		if ( ! $this->action_scheduler ) {
			return;
		}

		$this->migration_scheduler = new Migration_Job_Scheduler( $this->action_scheduler );
		$this->migration_scheduler->init();
		$this->migration_scheduler->register_job(
			new Migration_Job( 'student_progress_migration', new Student_Progress_Migration() )
		);
		$this->migration_scheduler->register_job(
			new Migration_Job( 'quiz_migration', new Quiz_Migration() )
		);
	}

	/**
	 * Load the WP-CLI commands.
	 *
	 * @since 4.3.0
	 */
	private function initialize_cli() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once $this->resolve_path( 'includes/class-sensei-cli.php' );

			new Sensei_CLI();
		}
	}

	/**
	 * Load the 3rd party compatibility tweaks.
	 *
	 * @since 4.13.1
	 */
	private function initialize_3rd_party_compatibility(): void {
		require_once $this->resolve_path( 'includes/3rd-party/3rd-party.php' );
	}

	/**
	 * Initialize all Sensei hooks
	 *
	 * @since 1.9.0
	 */
	public function load_hooks() {

		add_action( 'widgets_init', array( $this, 'register_widgets' ) );
		add_action( 'after_setup_theme', array( $this, 'ensure_post_thumbnails_support' ) );
		add_action( 'after_setup_theme', array( $this, 'sensei_load_template_functions' ) );

		// Filter comment counts.
		add_filter( 'wp_count_comments', array( $this, 'sensei_count_comments' ), 999, 2 );

		add_action( 'body_class', array( $this, 'body_class' ) );

		// Check for and activate JetPack LaTeX support.
		add_action( 'plugins_loaded', array( $this, 'jetpack_latex_support' ), 200 ); // Runs after Jetpack has loaded it's modules.

		// Check for and activate WP QuickLaTeX support.
		add_action( 'plugins_loaded', array( $this, 'wp_quicklatex_support' ), 200 ); // Runs after Plugins have loaded.

		// check flush the rewrite rules if the option sensei_flush_rewrite_rules option is 1.
		add_action( 'admin_init', array( $this, 'flush_rewrite_rules' ), 101 );

		// Add plugin action links filter.
		add_filter( 'plugin_action_links_' . plugin_basename( $this->main_plugin_file_name ), array( $this, 'plugin_action_links' ) );

		/**
		 * Load all Template hooks
		 */
		if ( ! is_admin() ) {
			require_once $this->resolve_path( 'includes/hooks/template.php' );
		}
	}

	/**
	 * Run Sensei automatic data updates. This has been unused for many versions and should be considered destructive.
	 *
	 * @deprecated 3.0.0
	 *
	 * @since 1.1.0
	 */
	public function run_updates() {
		_deprecated_function( __METHOD__, '3.0.0' );

		// Run updates if administrator.
		if ( current_user_can( 'manage_options' ) || current_user_can( 'manage_sensei' ) ) {

			$this->updates->update();

		}
	}

	/**
	 * Register the widgets.
	 *
	 * @since  1.0.0
	 */
	public function register_widgets() {
		// Widget List (key => value is filename => widget class).
		$widget_list = apply_filters(
			'sensei_registered_widgets_list',
			array(
				'course-component'  => 'Course_Component',
				'lesson-component'  => 'Lesson_Component',
				'course-categories' => 'Course_Categories',
				'category-courses'  => 'Category_Courses',
			)
		);
		foreach ( $widget_list as $key => $value ) {
			if ( file_exists( $this->plugin_path . 'widgets/class-sensei-' . $key . '-widget.php' ) ) {
				require_once $this->plugin_path . 'widgets/class-sensei-' . $key . '-widget.php';
				register_widget( 'Sensei_' . $value . '_Widget' );
			}
		}

		do_action( 'sensei_register_widgets' );
	}

	/**
	 * Load the plugin's localisation file.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function load_localisation() {

		load_plugin_textdomain( 'sensei-lms', false, dirname( plugin_basename( $this->main_plugin_file_name ) ) . '/lang/' );
	}

	/**
	 * Load the plugin textdomain from the main WordPress "languages" folder.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain() {
		$domain = 'sensei-lms';

		if ( is_admin() ) {
			$wp_user_locale = get_user_locale();
		} else {
			$wp_user_locale = get_locale();
		}

		// The "plugin_locale" filter is also used in load_plugin_textdomain().
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$locale = (string) apply_filters( 'plugin_locale', $wp_user_locale, $domain );
		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->main_plugin_file_name ) ) . '/lang/' );
	}

	/**
	 * Run on activation.
	 *
	 * @since  1.0.0
	 * @deprecated 3.0.0
	 */
	public function activation() {
		_deprecated_function( __METHOD__, '3.0.0' );
	}

	/**
	 * Run on activation.
	 *
	 * @access public
	 * @since  1.9.21
	 * @return void
	 */
	public function deactivation() {
		$this->usage_tracking->unschedule_tracking_task();
		Sensei_Scheduler::instance()->cancel_all_jobs();
		Sensei_Data_Port_Manager::instance()->cancel_all_jobs();
	}

	/**
	 * Register activation hooks.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function install() {

		register_activation_hook( $this->main_plugin_file_name, array( $this, 'activate_sensei' ) );
		register_activation_hook( $this->main_plugin_file_name, array( $this, 'initiate_rewrite_rules_flush' ) );
	}

	/**
	 * Checks for plugin update tasks and ensures the current version is set.
	 *
	 * @since 2.0.0
	 *
	 * @deprecated 4.16.1
	 */
	public function update() {
		_deprecated_function( __METHOD__, '4.16.1' );

		$current_version = get_option( 'sensei-version' );
		$is_new_install  = ! $current_version && ! $this->course_exists();
		$is_upgrade      = $current_version && version_compare( $this->version, $current_version, '>' );

		// Make sure the current version is up-to-date.
		if ( ! $current_version || $is_upgrade ) {
			$this->register_plugin_version( $is_new_install );
		}

		$updates_factory = new Updates_Factory();
		$this->updates   = $updates_factory->create( $current_version, $is_upgrade, $is_new_install );
		$this->updates->run_updates();
	}

	/**
	 * Helper function to check to see if any courses exist in the database.
	 *
	 * @deprecated 4.16.1
	 *
	 * @return bool
	 */
	private function course_exists(): bool {
		_deprecated_function( __METHOD__, '4.16.1' );

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Lightweight query run only once before post type is registered.
		$course_sample_id = (int) $wpdb->get_var( "SELECT `ID` FROM {$wpdb->posts} WHERE `post_type`='course' LIMIT 1" );

		return ! empty( $course_sample_id );
	}

	/**
	 * Register the plugin's version.
	 *
	 * @access public
	 * @since  1.0.0
	 * @param boolean $is_new_install Is this a new install.
	 * @return void
	 *
	 * @deprecated 4.16.1
	 */
	private function register_plugin_version( $is_new_install ) {
		_deprecated_function( __METHOD__, '4.16.1' );

		if ( isset( $this->version ) ) {

			update_option( 'sensei-version', $this->version );

			if ( $is_new_install ) {
				update_option( 'sensei-install-version', $this->version );
			}
		}
	}

	/**
	 * Sets a legacy flag to a boolean value.
	 *
	 * @since 3.7.0
	 *
	 * @param string $flag  Short name for the flag to set.
	 * @param bool   $value Boolean value to set.
	 */
	public function set_legacy_flag( $flag, $value ) {
		$legacy_flags          = $this->get_legacy_flags();
		$legacy_flags[ $flag ] = (bool) $value;

		update_option( self::LEGACY_FLAG_OPTION, wp_json_encode( $legacy_flags ) );
	}

	/**
	 * Get a legacy flag value.
	 *
	 * @param string $flag    Short name for the flag to set.
	 * @param bool   $default Boolean value to set. Defaults to false.
	 *
	 * @return bool
	 */
	public function get_legacy_flag( $flag, $default = false ) {
		$legacy_flags = $this->get_legacy_flags();

		if ( isset( $legacy_flags[ $flag ] ) ) {
			return (bool) $legacy_flags[ $flag ];
		}

		return (bool) $default;
	}

	/**
	 * Get the legacy flags that have been set.
	 *
	 * @return array
	 */
	public function get_legacy_flags() {
		return json_decode( get_option( self::LEGACY_FLAG_OPTION, '{}' ), true );
	}

	/**
	 * Run on activation of the plugin.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function activate_sensei() {

		if ( false === get_option( 'sensei_installed', false ) ) {

			// Do not enable the wizard for sites that are created with the onboarding flow.
			if ( 'sensei' !== get_option( 'site_intent' ) ) {

				set_transient( 'sensei_activation_redirect', 1, 30 );
				update_option( Sensei_Setup_Wizard::SUGGEST_SETUP_WIZARD_OPTION, 1 );

			} else {
				Sensei_Setup_Wizard::instance()->finish_setup_wizard();
			}
		} else {
			return;
		}

		update_option( 'sensei_installed', 1 );
	}

	/**
	 * Ensure that "post-thumbnails" support is available for those themes that don't register it.
	 *
	 * @access  public
	 * @since   1.0.1
	 * @return  void
	 */
	public function ensure_post_thumbnails_support() {

		if ( ! current_theme_supports( 'post-thumbnails' ) ) {
			add_theme_support( 'post-thumbnails' ); }
	}

	/**
	 * Determine the relative path to the plugin's directory.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return string $sensei_plugin_path
	 */
	public function plugin_path() {

		if ( $this->plugin_path ) {

			$sensei_plugin_path = $this->plugin_path;

		} else {

			$sensei_plugin_path = plugin_dir_path( __FILE__ );

		}

		return $sensei_plugin_path;
	}

	/**
	 * Retrieve the ID of a specified page setting.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $page Page.
	 * @return int
	 */
	public function get_page_id( $page ) {

		/**
		 * Filter the page ID.
		 *
		 * {page} is the page name.
		 *
		 * @hook sensei_get_{page}_page_id
		 *
		 * @param {int} $page The page ID.
		 * @return {int} Filtered page ID.
		 */
		$page = apply_filters( 'sensei_get_' . esc_attr( $page ) . '_page_id', get_option( 'sensei_' . esc_attr( $page ) . '_page_id' ) );
		return ( $page ) ? $page : -1;
	}

	/**
	 * Check if visitors have access permission. If the "access_permission" setting is active, do a log in check.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return bool
	 */
	public function access_settings() {

		if ( sensei_all_access() ) {
			return true;
		}

		if ( sensei_is_login_required() ) {
			if ( is_user_logged_in() ) {
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	}

	/**
	 * Load in class files.
	 *
	 * @since 1.2.0
	 *
	 * @param string $class_name Class name.
	 */
	public function load_class( $class_name = '' ) {
		if ( ! empty( $class_name ) && ! empty( $this->token ) ) {
			require_once __DIR__ . '/class-' . esc_attr( (string) $this->token ) . '-' . esc_attr( $class_name ) . '.php';
		}
	}

	/**
	 * Filtering wp_count_comments to ensure that Sensei comments are ignored.
	 *
	 * @since   1.4.0
	 * @access  public
	 *
	 * @param stdClass $comment_counts Counts by comment status.
	 * @param int      $post_id        Post ID.
	 *
	 * @return stdClass
	 */
	public function sensei_count_comments( $comment_counts, $post_id ) {
		if (
			// If comment counts are empty, so far nothing has touched core's counts and we can return early.
			empty( $comment_counts )

			// If we are getting counts for a specific, non-Sensei post, return early.
			|| (
				! empty( $post_id )
				&& ! in_array( get_post_type( $post_id ), array( 'course', 'lesson', 'quiz' ), true )
			)

			// If Sensei's comment counts aren't included, we don't need to adjust.
			|| ! $this->comment_counts_include_sensei_comments( $post_id )
		) {
			return $comment_counts;
		}

		// If there are no Sensei comments to deduct, return early.
		$sensei_counts = $this->get_sensei_comment_counts( $post_id );
		if ( empty( $sensei_counts ) ) {
			return $comment_counts;
		}

		// Subtract Sensei's comment counts from all the comment counts.
		foreach ( array_keys( (array) $comment_counts ) as $count_type ) {
			if ( isset( $sensei_counts[ $count_type ] ) ) {
				$comment_counts->{$count_type} = max( 0, (int) $comment_counts->{$count_type} - $sensei_counts[ $count_type ] );
			}
		}

		return $comment_counts;
	}

	/**
	 * Get if the comment counts include Sensei comments.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool
	 */
	private function comment_counts_include_sensei_comments( $post_id ) {
		// On a clean install, WordPress does not include Sensei's comments in its count.
		$includes_sensei_comments = false;

		// WooCommerce includes Sensei's comments in its counts.
		if (
			empty( $post_id )
			&& has_filter( 'wp_count_comments', array( 'WC_Comments', 'wp_count_comments' ) )
		) {
			$includes_sensei_comments = true;
		}

		/**
		 * Available to override if `wp_count_comments()` includes Sensei's comments in the count.
		 *
		 * @since 3.0.0
		 *
		 * @hook sensei_comment_counts_include_sensei_comments
		 *
		 * @param {bool} $includes_sensei_comments Whether the count already includes Sensei's comments.
		 * @param {int}  $post_id                  Post ID.
		 * @return {bool} Whether the count already includes Sensei's comments.
		 */
		return apply_filters( 'sensei_comment_counts_include_sensei_comments', $includes_sensei_comments, $post_id );
	}

	/**
	 * Clear the comment count cache.
	 *
	 * @param int $post_id Post ID.
	 */
	public function flush_comment_counts_cache( $post_id ) {
		$post_id = (int) $post_id;

		$post_transient_id = self::COMMENT_COUNT_TRANSIENT_PREFIX . $post_id;
		delete_transient( $post_transient_id );

		$all_transient_id = self::COMMENT_COUNT_TRANSIENT_PREFIX . '0';
		delete_transient( $all_transient_id );
	}

	/**
	 * Get the Sensei related comment counts by comment_approved but use caching.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array
	 */
	private function get_sensei_comment_counts( $post_id ) {
		$post_id = (int) $post_id;

		$transient_id = self::COMMENT_COUNT_TRANSIENT_PREFIX . $post_id;
		$stats        = get_transient( $transient_id );

		if ( ! $stats || ! is_array( $stats ) ) {
			$stats = $this->get_sensei_comment_counts_direct( $post_id );

			set_transient( $transient_id, $stats );
		}

		return $stats;
	}

	/**
	 * Get the Sensei related comment counts by comment_approved.
	 *
	 * @param  int $post_id Post ID.
	 *
	 * @return array
	 */
	private function get_sensei_comment_counts_direct( $post_id ) {
		global $wpdb;

		$post_where = '';

		if ( ! empty( $post_id ) ) {
			$post_where = $wpdb->prepare( 'AND comment_post_ID=%d', $post_id );
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- `$post_where` prepared above.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching -- Cached in calling method.
		$counts = $wpdb->get_results(
			"
					SELECT comment_approved, COUNT(*) AS num_comments
					FROM {$wpdb->comments}
					WHERE
						comment_type LIKE 'sensei_%'
						{$post_where}
					GROUP BY comment_approved
			",
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$stats = array(
			'all'            => 0,
			'total_comments' => 0,
		);

		foreach ( (array) $counts as $row ) {
			$row['num_comments'] = (int) $row['num_comments'];

			// Don't count post-trashed toward totals.
			if ( ! in_array( $row['comment_approved'], array( 'post-trashed', 'trash' ), true ) ) {
				$stats['total_comments'] += $row['num_comments'];

				if ( 'spam' !== $row['comment_approved'] ) {
					$stats['all'] += $row['num_comments'];
				}
			}

			if ( ! isset( $stats[ $row['comment_approved'] ] ) ) {
				$stats[ $row['comment_approved'] ] = 0;
			}

			$stats[ $row['comment_approved'] ] += $row['num_comments'];
		}

		if (
			empty( $stats['total_comments'] )
			&& 2 === count( $stats )
		) {
			return array();
		}

		return $stats;
	}

	/**
	 * Init images.
	 *
	 * @since 1.4.5
	 * @access public
	 * @return void
	 */
	public function init_image_sizes() {
		$course_archive_thumbnail = $this->get_image_size( 'course_archive_image' );
		$course_single_thumbnail  = $this->get_image_size( 'course_single_image' );
		$lesson_archive_thumbnail = $this->get_image_size( 'lesson_archive_image' );
		$lesson_single_thumbnail  = $this->get_image_size( 'lesson_single_image' );

		add_image_size( 'course_archive_thumbnail', $course_archive_thumbnail['width'], $course_archive_thumbnail['height'], $course_archive_thumbnail['crop'] );
		add_image_size( 'course_single_thumbnail', $course_single_thumbnail['width'], $course_single_thumbnail['height'], $course_single_thumbnail['crop'] );
		add_image_size( 'lesson_archive_thumbnail', $lesson_archive_thumbnail['width'], $lesson_archive_thumbnail['height'], $lesson_archive_thumbnail['crop'] );
		add_image_size( 'lesson_single_thumbnail', $lesson_single_thumbnail['width'], $lesson_single_thumbnail['height'], $lesson_single_thumbnail['crop'] );
	}

	/**
	 * Get an image size.
	 *
	 * Variable is filtered by sensei_get_image_size_{image_size}
	 *
	 * @since 1.4.5
	 * @access public
	 * @param mixed $image_size Image Size.
	 * @return string
	 */
	public function get_image_size( $image_size ) {

		// Only return sizes we define in settings.
		if ( ! in_array( $image_size, array( 'course_archive_image', 'course_single_image', 'lesson_archive_image', 'lesson_single_image' ), true ) ) {
			/**
			 * Filter the image size.
			 *
			 * {image_size} is NOT one of: course_archive_image, course_single_image, lesson_archive_image, lesson_single_image.
			 *
			 * @hook sensei_get_image_size_{image_size}
			 *
			 * @param {string} $image_size Image Size.
			 * @return {string} Filtered image size.
			 */
			return apply_filters( 'sensei_get_image_size_' . $image_size, '' );
		}

		if ( ! isset( $this->settings->settings[ $image_size . '_width' ] ) ) {
			$this->settings->settings[ $image_size . '_width' ] = false;
		}
		if ( ! isset( $this->settings->settings[ $image_size . '_height' ] ) ) {
			$this->settings->settings[ $image_size . '_height' ] = false;
		}
		if ( ! isset( $this->settings->settings[ $image_size . '_hard_crop' ] ) ) {
			$this->settings->settings[ $image_size . '_hard_crop' ] = false;
		}

		$size = array_filter(
			array(
				'width'  => $this->settings->settings[ $image_size . '_width' ],
				'height' => $this->settings->settings[ $image_size . '_height' ],
				'crop'   => $this->settings->settings[ $image_size . '_hard_crop' ],
			)
		);

		$size['width']  = isset( $size['width'] ) ? $size['width'] : '100';
		$size['height'] = isset( $size['height'] ) ? $size['height'] : '100';
		$size['crop']   = isset( $size['crop'] ) ? $size['crop'] : 0;

		/**
		 * Filter the image size.
		 *
		 * {image_size} is one of: course_archive_image, course_single_image, lesson_archive_image, lesson_single_image.
		 *
		 * @hook sensei_get_image_size_{image_size}
		 *
		 * @param {string} $image_size Image Size.
		 * @return {string} Filtered image size.
		*/
		return apply_filters( 'sensei_get_image_size_' . $image_size, $size );
	}

	/**
	 * Body Class.
	 *
	 * @param array $classes Classes.
	 * @return array
	 */
	public function body_class( $classes ) {
		if ( is_sensei() ) {
			$classes[] = 'sensei';
			$post_type = get_post_type();

			if ( ! empty( $post_type ) ) {
				$classes[] = $post_type;

				if ( 'lesson' === $post_type ) {
					$course_id = Sensei()->lesson->get_course_id( get_the_ID() );
					$classes[] = 'course-id-' . $course_id;
				}

				if ( 'quiz' === $post_type ) {
					$lesson_id = Sensei()->quiz->get_lesson_id( get_the_ID() );
					$classes[] = 'lesson-id-' . $lesson_id;

					$course_id = Sensei()->lesson->get_course_id( $lesson_id );
					$classes[] = 'course-id-' . $course_id;
				}
			}

			// Add class to Course Completed page.
			if ( get_the_ID() === intval( Sensei()->settings->settings['course_completed_page'] ) ) {
				$classes[] = 'course-completed';
			}
		}

		return $classes;
	}

	/**
	 * Checks that the Jetpack Beautiful Maths module has been activated
	 * to support LaTeX within question titles and answers
	 *
	 * @since 1.7.0
	 */
	public function jetpack_latex_support() {
		$this->maybe_add_latex_support_via( 'latex_markup' );
	}

	/**
	 * Possibly Adds LaTex support
	 *
	 * @param string $func_name A Function.
	 */
	private function maybe_add_latex_support_via( $func_name ) {
		if ( function_exists( $func_name ) ) {
			add_filter( 'sensei_question_title', $func_name );
			add_filter( 'sensei_answer_text', $func_name );
			add_filter( 'sensei_question_answer_notes', $func_name );
			add_filter( 'sensei_questions_get_correct_answer', $func_name );
		}
	}

	/**
	 * Checks that the WP QuickLaTeX plugin has been activated
	 * to support LaTeX within question titles and answers
	 */
	public function wp_quicklatex_support() {
		$this->maybe_add_latex_support_via( 'quicklatex_parser' );
	}

	/**
	 * Load the module functionality.
	 *
	 * This function is hooked into plugins_loaded to avoid conflicts with
	 * the retired modules extension.
	 *
	 * @since 1.8.0
	 */
	public function load_modules_class() {
		global $sensei_modules;

		$class = is_null( $sensei_modules ) ? get_class() : get_class( $sensei_modules );

		if ( ! class_exists( 'Sensei_Modules' ) && 'Sensei_Modules' !== $class ) {
			// Load the modules class.
			require_once __DIR__ . '/class-sensei-modules.php';
			$this->modules = new Sensei_Core_Modules( $this->main_plugin_file_name );

		} else {
			// fallback for people still using the modules extension.
			global $sensei_modules;
			$this->modules = $sensei_modules;
			add_action( 'admin_notices', array( $this, 'disable_sensei_modules_extension' ), 30 );
		}
	}

	/**
	 * Tell the user to that the modules extension is no longer needed.
	 *
	 * @since 1.8.0
	 */
	public function disable_sensei_modules_extension() {
		?>
		<div class="notice updated fade">
			<p>
				<?php
				$plugin_manage_url   = admin_url() . 'plugins.php#sensei-modules';
				$plugin_link_element = '<a href="' . esc_url( $plugin_manage_url ) . '" >plugins page</a> ';
				?>
				<strong> Modules are now included in Sensei,</strong> so you no longer need the Sensei Modules extension.
				Please deactivate and delete it from your <?php echo esc_html( $plugin_link_element ); ?>. (This will not affect your existing modules).
			</p>
		</div>

		<?php
	}

	/**
	 * Sensei wide rewrite flush call.
	 *
	 * To use this simply update the option 'sensei_flush_rewrite_rules' to 1
	 *
	 * After the option is one the Rules will be flushed.
	 *
	 * @since 1.9.0
	 */
	public function flush_rewrite_rules() {

		// ensures that the rewrite rules are flushed on the second
		// attempt. This ensure that the settings for any other process
		// have been completed and saved to the database before we refresh the
		// rewrite rules.
		$option = absint( get_option( 'sensei_flush_rewrite_rules' ) );
		if ( 1 === $option ) {

			update_option( 'sensei_flush_rewrite_rules', '2' );

		} elseif ( 2 === $option ) {

			flush_rewrite_rules();
			update_option( 'sensei_flush_rewrite_rules', '0' );

		}
	}

	/**
	 * Calling this function will tell Sensei to flush rewrite
	 * rules on the next load.
	 *
	 * @since 1.9.0
	 */
	public function initiate_rewrite_rules_flush() {

		update_option( 'sensei_flush_rewrite_rules', '1' );
	}

	/**
	 * Add custom action links on the plugin screen.
	 *
	 * @param   mixed $actions Plugin Actions Links.
	 * @return  array
	 */
	public function plugin_action_links( $actions ) {

		$custom_actions = array();

		// settings url(s).
		if ( $this->get_settings_link( $this->get_id() ) ) {
			$custom_actions['configure'] = $this->get_settings_link( $this->get_id() );
		}

		// documentation url if any.
		if ( $this->get_documentation_url() ) {
			/* translators: Docs as in Documentation */
			$custom_actions['docs'] = sprintf( '<a href="%s" target="_blank">%s</a>', $this->get_documentation_url(), esc_html__( 'Docs', 'sensei-lms' ) );
		}

		// add the links to the front of the actions list.
		return array_merge( $custom_actions, $actions );
	}

	/**
	 * Returns the "Configure" plugin action link to go directly to the plugin
	 * settings page (if any)
	 *
	 * @param null $plugin_id Plugin ID.
	 *
	 * @return string plugin configure link
	 */
	public function get_settings_link( $plugin_id = null ) {
		$settings_url = $this->get_settings_url( $plugin_id );
		if ( $settings_url ) {
			return sprintf( '<a href="%s">%s</a>', $settings_url, esc_html_x( 'Configure', 'plugin action link', 'sensei-lms' ) );
		}

		// no settings.
		return '';
	}

	/**
	 * Gets the plugin configuration URL
	 *
	 * @param null $plugin_id Plugin ID.
	 *
	 * @return string plugin settings URL
	 */
	public function get_settings_url( $plugin_id = null ) {
		return admin_url( 'admin.php?page=sensei-settings&tab=general' );
	}

	/**
	 * Gets the plugin documentation url, used for the 'Docs' plugin action
	 *
	 * @return string documentation URL
	 */
	public function get_documentation_url() {
		return sprintf( 'https://senseilms.com/documentation/' );
	}

	/**
	 * Gets the support URL, used for the 'Support' plugin action link
	 *
	 * @deprecated 3.1.1
	 *
	 * @return string support url
	 */
	public function get_support_url() {
		_deprecated_function( __METHOD__, '3.1.1' );

		return 'https://www.woothemes.com/my-account/create-a-ticket/?utm_source=SenseiPlugin&utm_medium=PluginPage&utm_content=Support&utm_campaign=SenseiPlugin';
	}

	/**
	 * Returns the plugin id
	 *
	 * @return string plugin id
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Returns true if the current page is the admin general configuration page
	 *
	 * @return boolean true if the current page is the admin general configuration page
	 */
	public function is_general_configuration_page() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,
		return isset( $_GET['page'] ) && 'sensei-settings' === trim( $_GET['page'] ) && ( ! isset( $_GET['tab'] ) || 'general' === trim( $_GET['tab'] ) );
	}

		/**
		 * Returns the admin configuration url for the admin general configuration page
		 *
		 * @return string admin configuration url for the admin general configuration page
		 */
	public function get_general_configuration_url() {
		return admin_url( 'admin.php?page=sensei-settings&tab=general' );
	}

	/**
	 * Activate sensei. Should only be called from Sensei activation hook
	 *
	 * @since 1.9.13
	 */
	public function activate() {
		// Create the teacher role on activation and ensure that it has all the needed capabilities.
		$this->teacher->create_role();

		// Setup all the role capabilities needed.
		$this->add_sensei_admin_caps();
		$this->add_editor_caps();
		$this->assign_role_caps();
		Sensei()->setup_wizard->pages->create_pages();

		// Flush rules.
		add_action( 'activated_plugin', array( __CLASS__, 'activation_flush_rules' ), 10 );
	}

	/**
	 * Assign role caps for the various post types.
	 */
	public function assign_role_caps() {
		foreach ( $this->post_types->role_caps as $role_cap_set ) {
			foreach ( $role_cap_set as $role_key => $capabilities_array ) {
				// Get the role.
				$role = get_role( $role_key );
				foreach ( $capabilities_array as $cap_name ) {
					// If the role exists, add required capabilities for the plugin.
					if ( ! empty( $role ) ) {
						if ( ! $role->has_cap( $cap_name ) ) {
							$role->add_cap( $cap_name );
						}
					}
				}
			}
		}
	}

	/**
	 * Adds Sensei capabilities to the editor role.
	 *
	 * @return bool
	 */
	public function add_editor_caps() {
		$role = get_role( 'editor' );

		if ( ! is_null( $role ) ) {
			$role->add_cap( 'manage_sensei_grades' );
		}

		return true;
	}

	/**
	 * Adds Sensei capabilities to admin.
	 *
	 * @return bool
	 */
	public function add_sensei_admin_caps() {
		$role = get_role( 'administrator' );

		if ( ! is_null( $role ) ) {
			$role->add_cap( 'manage_sensei' );
			$role->add_cap( 'manage_sensei_grades' );
		}

		return true;
	}

	/**
	 * Load Sensei Template Functions
	 *
	 * @since 1.9.12
	 */
	public function sensei_load_template_functions() {
		require_once $this->resolve_path( 'includes/template-functions.php' );
	}

	/**
	 * Get full path for a path relative to plugin basedir
	 *
	 * @param string $path The path.
	 * @return string
	 * @since 1.9.13
	 */
	private function resolve_path( $path ) {
		return trailingslashit( $this->plugin_path ) . $path;
	}
}

/**
 * Class Woothemes_Sensei
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class Woothemes_Sensei extends Sensei_Main {
}
