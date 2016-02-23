<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Responsible for loading Sensei and setting up the Main WordPress hooks.
 *
 * @package Core
 * @author Automattic
 * @since 1.0.0
 */
class Sensei_Main {

    /**
     * @var string
     * Reference to the main plugin file
     */
    private $file;

    /**
     * @var Sensei_Main $_instance to the the main and only instance of the Sensei class.
     * @since 1.8.0
     */
    protected static $_instance = null;

    /**
     * Main reference to the plugins current version
     */
    public $version;

    /**
     * Public token, referencing for the text domain.
     */
    public $token = 'woothemes-sensei';

    /**
     * Plugin url and path for use when access resources.
     */
    public $plugin_url;
    public $plugin_path;
    public $template_url;

    /**
     * @var Sensei_PostTypes
     * All Sensei sub classes. Currently used to access functionality contained within
     * within Sensei sub classes e.g. Sensei()->course->all_courses()
     */
    public $post_types;

    /**
     * @var WooThemes_Sensei_Settings
     */
    public $settings;

    /**
     * @var WooThemes_Sensei_Course_Results
     */
    public $course_results;

    /**
     * @var Sensei_Updates
     */
    public $updates;
    /**
     * @var WooThemes_Sensei_Course
     */
    public $course;

    /**
     * @var WooThemes_Sensei_Lesson
     */
    public $lesson;

    /**
     * @var WooThemes_Sensei_Quiz
     */
    public $quiz;

    /**
     * @var WooThemes_Sensei_Question
     */
    public $question;

    /**
     * @var WooThemes_Sensei_Admin
     */
    public $admin;

    /**
     * @var WooThemes_Sensei_Frontend
     */
    public $frontend;

    /**
     * @var Sensei_Notices
     */
    public $notices;

    /**
     * @var WooThemes_Sensei_Grading
     */
    public $grading;

    /**
     * @var WooThemes_Sensei_Emails
     */
    public $emails;

    /**
     * @var WooThemes_Sensei_Learner_Profiles
     */
    public $learner_profiles;

    /**
     * @var Sensei_Teacher
     */
    public $teacher;

    /**
     * @var WooThemes_Sensei_Learners
     */
    public $learners;

    /**
     * @var array
     * Global instance for access to the permissions message shown
     * when users do not have the right privileges to access resources.
     */
    public $permissions_message;

    /**
     * @var Sensei_Core_Modules Sensei Modules functionality
     */
    public $modules;

    /**
     * @var Sensei_Analysis
     */
    public $analysis;

    /**
     * Constructor method.
     * @param  string $file The base file of the plugin.
     * @since  1.0.0
     */
    public function __construct ( $file ) {

        // Setup object data
        $this->file = $file;
        $this->plugin_url = trailingslashit( plugins_url( '', $plugin = $file ) );
        $this->plugin_path = trailingslashit( dirname( $file ) );
        $this->template_url	= apply_filters( 'sensei_template_url', 'sensei/' );

        // Initialize the core Sensei functionality
        $this->init();

        // Installation
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) $this->install();

        // Run this on activation.
        register_activation_hook( $this->file, array( $this, 'activation' ) );

        // Image Sizes
        $this->init_image_sizes();

        // load all hooks
        $this->load_hooks();

    } // End __construct()

    /**
     * Load the foundations of Sensei.
     * @since 1.9.0
     */
    protected function init(){

        // Localisation
        $this->load_plugin_textdomain();
        add_action( 'init', array( $this, 'load_localisation' ), 0 );

        // Setup settings
        $this->settings = new Sensei_Settings();

        // load the shortcode loader into memory, so as to listen to all for
        // all shortcodes on the front end
        new Sensei_Shortcode_Loader();

    }

    /**
     * Global Sensei Instance
     *
     * Ensure that only one instance of the main Sensei class can be loaded.
     *
     * @since 1.8.0
     * @static
     * @see WC()
     * @return WooThemes_Sensei Instance.
     */
    public static function instance() {

        if ( is_null( self::$_instance ) ) {

            //Sensei requires a reference to the main Sensei plugin file
            $sensei_main_plugin_file = dirname ( dirname( __FILE__ ) ) . '/woothemes-sensei.php';

            self::$_instance = new self( $sensei_main_plugin_file  );

            // load the global class objects needed throughout Sensei
            self::$_instance->initialize_global_objects();

        }

        return self::$_instance;

    } // end instance()

    /**
     * This function is linked into the activation
     * hook to reset flush the urls to ensure Sensei post types show up.
     *
     * @since 1.9.0
     *
     * @param $plugin
     */
    public static function activation_flush_rules( $plugin ){

        if( strpos( $plugin, '/woothemes-sensei.php' ) > 0  ){

            flush_rewrite_rules(true);

        }

    }

    /**
     * Cloning is forbidden.
     * @since 1.8.0
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woothemes-sensei' ), '1.8' );
    }

    /**
     * Unserializing instances of this class is forbidden.
     * @since 1.8.0
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woothemes-sensei' ), '1.8' );
    }

    /**
     * Load the properties for the main Sensei object
     *
     * @since 1.9.0
     */
    public function initialize_global_objects(){

        // Setup post types.
        $this->post_types = new Sensei_PostTypes();

        // Lad the updates class
        $this->updates = new Sensei_Updates( $this );

        // Load Course Results Class
        $this->course_results = new Sensei_Course_Results();

        // Load the teacher role
        $this->teacher = new Sensei_Teacher();

        // Add the Course class
        $this->course = $this->post_types->course;

        // Add the lesson class
        $this->lesson = $this->post_types->lesson;

        // Add the question class
        $this->question = $this->post_types->question;

        //Add the quiz class
        $this->quiz = $this->post_types->quiz;

        // load the modules class after all plugsin are loaded
	    $this->load_modules_class();

        // Load Learner Management Functionality
        $this->learners = new Sensei_Learner_Management( $this->file );

        // Differentiate between administration and frontend logic.
        if ( is_admin() ) {

            // Load Admin Welcome class
            new Sensei_Welcome();

            // Load Admin Class
            $this->admin = new Sensei_Admin( $this->file );

            // Load Analysis Reports
            $this->analysis = new Sensei_Analysis( $this->file );

        } else {

            // Load Frontend Class
            $this->frontend = new Sensei_Frontend();

            // Load notice Class
            $this->notices = new Sensei_Notices();

            // Load built in themes support integration
            new Sensei_Theme_Integration_Loader();


        }

        // Load Grading Functionality
        $this->grading = new Sensei_Grading( $this->file );

        // Load Email Class
        $this->emails = new Sensei_Emails( $this->file );

        // Load Learner Profiles Class
        $this->learner_profiles = new Sensei_Learner_Profiles();

    }

    /**
     * Initialize all Sensei hooks
     *
     * @since 1.9.0
     */
    public function load_hooks(){

        add_action( 'widgets_init', array( $this, 'register_widgets' ) );
        add_action( 'after_setup_theme', array( $this, 'ensure_post_thumbnails_support' ) );

        // Filter comment counts
        add_filter( 'wp_count_comments', array( $this, 'sensei_count_comments' ), 10, 2 );

        add_action( 'body_class', array( $this, 'body_class' ) );

        // Check for and activate JetPack LaTeX support
        add_action( 'plugins_loaded', array( $this, 'jetpack_latex_support'), 200 ); // Runs after Jetpack has loaded it's modules

        // check flush the rewrite rules if the option sensei_flush_rewrite_rules option is 1
        add_action( 'init', array( $this, 'flush_rewrite_rules'), 101 );

    }

    /**
     * Run Sensei updates.
     * @access  public
     * @since   1.1.0
     * @return  void
     */
    public function run_updates() {
        // Run updates if administrator
        if ( current_user_can( 'manage_options' ) || current_user_can( 'manage_sensei' ) ) {

            $this->updates->update();

        } // End If Statement
    } // End run_updates()

    /**
     * Register the widgets.
     * @access public
     * @since  1.0.0
     * @return void
     */
    public function register_widgets () {
        // Widget List (key => value is filename => widget class).
        $widget_list = apply_filters( 'sensei_registered_widgets_list', array( 	'course-component' 	=> 'Course_Component',
                'lesson-component' 	=> 'Lesson_Component',
                'course-categories' => 'Course_Categories',
                'category-courses' 	=> 'Category_Courses' )
        );
        foreach ( $widget_list as $key => $value ) {
            if ( file_exists( $this->plugin_path . 'widgets/widget-woothemes-sensei-' . $key  . '.php' ) ) {
                require_once( $this->plugin_path . 'widgets/widget-woothemes-sensei-' . $key  . '.php' );
                register_widget( 'WooThemes_Sensei_' . $value . '_Widget' );
            }
        } // End For Loop

        do_action( 'sensei_register_widgets' );

    } // End register_widgets()

    /**
     * Load the plugin's localisation file.
     * @access public
     * @since  1.0.0
     * @return void
     */
    public function load_localisation () {

        load_plugin_textdomain( 'woothemes-sensei', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );

    } // End load_localisation()

    /**
     * Load the plugin textdomain from the main WordPress "languages" folder.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function load_plugin_textdomain () {

        $domain = 'woothemes-sensei';
        // The "plugin_locale" filter is also used in load_plugin_textdomain()
        $locale = apply_filters( 'plugin_locale', get_locale(), $domain );
        load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
        load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( $this->file ) ) . '/lang/' );

    } // End load_plugin_textdomain()

    /**
     * Run on activation.
     * @access public
     * @since  1.0.0
     * @return void
     */
    public function activation () {

        $this->register_plugin_version();

    } // End activation()


    /**
     * Register activation hooks.
     * @access public
     * @since  1.0.0
     * @return void
     */
    public function install () {

        register_activation_hook( $this->file, array( $this, 'activate_sensei' ) );
        register_activation_hook( $this->file, 'flush_rewrite_rules' );

    } // End install()


    /**
     * Run on activation of the plugin.
     * @access public
     * @since  1.0.0
     * @return void
     */
    public function activate_sensei () {

        update_option( 'skip_install_sensei_pages', 0 );
        update_option( 'sensei_installed', 1 );

    } // End activate_sensei()

    /**
     * Register the plugin's version.
     * @access public
     * @since  1.0.0
     * @return void
     */
    private function register_plugin_version () {
        if ( $this->version != '' ) {

            update_option( 'woothemes-sensei-version', $this->version );

        }
    } // End register_plugin_version()

    /**
     * Ensure that "post-thumbnails" support is available for those themes that don't register it.
     * @access  public
     * @since   1.0.1
     * @return  void
     */
    public function ensure_post_thumbnails_support () {

        if ( ! current_theme_supports( 'post-thumbnails' ) ) { add_theme_support( 'post-thumbnails' ); }

    } // End ensure_post_thumbnails_support()

    /**
     * template_loader function.
     *
     * @access public
     * @param mixed $template
     * @return void
     * @deprecated
     */
    public function template_loader ( $template = '' ) {

        _deprecated_function( 'Sensei()->template_loader', '1.9.0', 'Use Sensei_Templates::template_loader( $template ) instead' );
        Sensei_Templates::template_loader( $template );

    } // End template_loader()

    /**
     * Determine the relative path to the plugin's directory.
     * @access public
     * @since  1.0.0
     * @return string $sensei_plugin_path
     */
    public function plugin_path () {

        if ( $this->plugin_path ) {

            $sensei_plugin_path =  $this->plugin_path;

        }else{

            $sensei_plugin_path = plugin_dir_path( __FILE__ );

        }

        return $sensei_plugin_path;

    } // End plugin_path()

    /**
     * Retrieve the ID of a specified page setting.
     * @access public
     * @since  1.0.0
     * @param  string $page
     * @return int
     */
    public function get_page_id ( $page ) {
        $page = apply_filters( 'sensei_get_' . esc_attr( $page ) . '_page_id', get_option( 'sensei_' . esc_attr( $page ) . '_page_id' ) );
        return ( $page ) ? $page : -1;
    } // End get_page_id()

    /**
     * check_user_permissions function.
     *
     * @access public
     * @param string $page (default: '')
     *
     * @return bool
     */
    public function check_user_permissions ( $page = '' ) {

        global $current_user, $post;

        $user_allowed = false;

        switch ( $page ) {
	        case 'course-single':
		        // check for prerequisite course or lesson,
		        $course_prerequisite_id = (int) get_post_meta( $post->ID, '_course_prerequisite', true );
		        $update_course          = Sensei_WC::course_update( $post->ID );

		        // Count completed lessons
		        if ( 0 < absint( $course_prerequisite_id ) ) {

			        $prerequisite_complete = Sensei_Utils::user_completed_course( $course_prerequisite_id, $current_user->ID );

		        } else {
			        $prerequisite_complete = true;
		        } // End If Statement

		        // Handles restrictions on the course
		        if ( ( ! $prerequisite_complete && 0 < absint( $course_prerequisite_id ) ) ) {

			        $user_allowed = false;
			        $course_link  = '<a href="' . esc_url( get_permalink( $course_prerequisite_id ) ) . '">' . __( 'course', 'woothemes-sensei' ) . '</a>';
			        $this->notices->add_notice( sprintf( __( 'Please complete the previous %1$s before taking this course.', 'woothemes-sensei' ), $course_link ), 'info' );

		        } elseif( Sensei_WC::is_woocommerce_active() && Sensei_WC::is_course_purchasable( $post->ID ) && ! Sensei_Utils::user_started_course( $post->ID, $current_user->ID )  ) {

			        $message = sprintf( __( 'Or %1$s login %2$s to access your purchased courses', 'woothemes-sensei' ), '<a href="'.sensei_user_login_url().'">', '</a>' );
			        $this->notices->add_notice( $message, 'info' );


		        } elseif ( ! Sensei_Utils::user_started_course( $post->ID, $current_user->ID )  ) {

					// users who haven't started the course are allowed to view it
			        $user_allowed                         = true;



		        } else  {

                    $user_allowed = true;

                } // End If Statement
                break;
            case 'lesson-single':
                // Check for WC purchase
                $lesson_course_id = get_post_meta( $post->ID, '_lesson_course',true );

                $update_course = Sensei_WC::course_update( $lesson_course_id );
                $is_preview = Sensei_Utils::is_preview_lesson( $post->ID );

                if ( $this->access_settings() && Sensei_Utils::user_started_course( $lesson_course_id, $current_user->ID ) ) {
                    $user_allowed = true;
                } elseif( $this->access_settings() && false == $is_preview ) {

                    $user_allowed = true;

                } else {
                    $this->permissions_message['title'] = get_the_title( $post->ID ) . ': ' . __('Restricted Access', 'woothemes-sensei' );
                    $course_link = '<a href="' . esc_url( get_permalink( $lesson_course_id ) ) . '">' . __( 'course', 'woothemes-sensei' ) . '</a>';
                    $wc_post_id = get_post_meta( $lesson_course_id, '_course_woocommerce_product',true );
                    if ( Sensei_WC::is_woocommerce_active() && ( 0 < $wc_post_id ) ) {
                        if ( $is_preview ) {
                            $this->permissions_message['message'] = sprintf( __('This is a preview lesson. Please purchase the %1$s to access all lessons.', 'woothemes-sensei' ), $course_link );
                        } else {
                            $this->permissions_message['message'] =  sprintf( __('Please purchase the %1$s before starting this Lesson.', 'woothemes-sensei' ), $course_link );
                        }
                    } else {
                        if ( $is_preview ) {
                            $this->permissions_message['message'] = sprintf( __('This is a preview lesson. Please sign up for the %1$s to access all lessons.', 'woothemes-sensei' ), $course_link );
                        } else {
                            /** This filter is documented in class-woothemes-sensei-frontend.php */
                            $this->permissions_message['message'] =  sprintf( __( 'Please sign up for the %1$s before starting the lesson.', 'woothemes-sensei' ), $course_link );
                        }
                    } // End If Statement
                } // End If Statement
                break;
            case 'quiz-single':
                $lesson_id = get_post_meta( $post->ID, '_quiz_lesson',true );
                $lesson_course_id = get_post_meta( $lesson_id, '_lesson_course',true );

                $update_course = Sensei_WC::course_update( $lesson_course_id );
                if ( ( $this->access_settings() && Sensei_Utils::user_started_course( $lesson_course_id, $current_user->ID ) ) || sensei_all_access() ) {

                    // Check for prerequisite lesson for this quiz
                    $lesson_prerequisite_id = (int) get_post_meta( $lesson_id, '_lesson_prerequisite', true);
                    $user_lesson_prerequisite_complete = Sensei_Utils::user_completed_lesson( $lesson_prerequisite_id, $current_user->ID);

                    // Handle restrictions
                    if( sensei_all_access() ) {

                        $user_allowed = true;

                    } else {

                        if ( 0 < absint( $lesson_prerequisite_id ) && ( !$user_lesson_prerequisite_complete ) ) {

                            $this->permissions_message['title'] = get_the_title( $post->ID ) . ': ' . __('Restricted Access', 'woothemes-sensei' );
                            $lesson_link = '<a href="' . esc_url( get_permalink( $lesson_prerequisite_id ) ) . '">' . __( 'lesson', 'woothemes-sensei' ) . '</a>';
                            $this->permissions_message['message'] = sprintf( __('Please complete the previous %1$s before taking this Quiz.', 'woothemes-sensei' ), $lesson_link );

                        } else {

                            $user_allowed = true;

                        } // End If Statement
                    } // End If Statement
                } elseif( $this->access_settings() ) {
                    // Check if the user has started the course

                    if ( is_user_logged_in() && ! Sensei_Utils::user_started_course( $lesson_course_id, $current_user->ID ) && ( isset( $this->settings->settings['access_permission'] ) && ( true == $this->settings->settings['access_permission'] ) ) ) {

                        $user_allowed = false;
                        $this->permissions_message['title'] = get_the_title( $post->ID ) . ': ' . __('Restricted Access', 'woothemes-sensei' );
                        $course_link = '<a href="' . esc_url( get_permalink( $lesson_course_id ) ) . '">' . __( 'course', 'woothemes-sensei' ) . '</a>';
                        $wc_post_id = get_post_meta( $lesson_course_id, '_course_woocommerce_product',true );
                        if ( Sensei_WC::is_woocommerce_active() && ( 0 < $wc_post_id ) ) {
                            $this->permissions_message['message'] = sprintf( __('Please purchase the %1$s before starting this Quiz.', 'woothemes-sensei' ), $course_link );
                        } else {
                            $this->permissions_message['message'] = sprintf( __('Please sign up for the %1$s before starting this Quiz.', 'woothemes-sensei' ), $course_link );
                        } // End If Statement
                    } else {
                        $user_allowed = true;
                    } // End If Statement
                } else {
                    $this->permissions_message['title'] = get_the_title( $post->ID ) . ': ' . __('Restricted Access', 'woothemes-sensei' );
                    $course_link = '<a href="' . esc_url( get_permalink( get_post_meta( get_post_meta( $post->ID, '_quiz_lesson', true ), '_lesson_course', true ) ) ) . '">' . __( 'course', 'woothemes-sensei' ) . '</a>';
                    $this->permissions_message['message'] = sprintf( __('Please sign up for the %1$s before taking this Quiz.', 'woothemes-sensei' ), $course_link );
                } // End If Statement
                break;
            default:
                $user_allowed = true;
                break;

        } // End Switch Statement

        /**
         * filter the permissions message shown on sensei post types.
         *
         * @since 1.8.7
         *
         * @param array $permissions_message{
         *
         *   @type string $title
         *   @type string $message
         *
         * }
         * @param string $post_id
         */
        $this->permissions_message = apply_filters( 'sensei_permissions_message', $this->permissions_message, $post->ID );

		// add the permissions message to the stack

        if( sensei_all_access() || Sensei_Utils::is_preview_lesson( $post->ID ) ) {
            $user_allowed = true;
        }

        /**
         * Filter the permissions check final result. Which determines if the user has
         * access to the given page.
         *
         * @since 1.0
         *
         * @param boolean $user_allowed
         * @param integer $user_id
         *
         */
        return apply_filters( 'sensei_access_permissions', $user_allowed, $current_user->ID );

    } // End get_placeholder_image()


    /**
     * Check if visitors have access permission. If the "access_permission" setting is active, do a log in check.
     * @since  1.0.0
     * @access public
     * @return bool
     */
    public function access_settings () {

        if( sensei_all_access() ) return true;

        if ( isset( $this->settings->settings['access_permission'] ) && ( true == $this->settings->settings['access_permission'] ) ) {
            if ( is_user_logged_in() ) {
                return true;
            } else {
                return false;
            } // End If Statement
        } else {
            return true;
        } // End If Statement
    } // End access_settings()

    /**
     * load_class loads in class files
     * @since   1.2.0
     * @access  public
     * @return  void
     */
    public function load_class ( $class_name = '' ) {
        if ( '' != $class_name && '' != $this->token ) {
            require_once( 'class-' . esc_attr( $this->token ) . '-' . esc_attr( $class_name ) . '.php' );
        } // End If Statement
    } // End load_class()

    /**
     * Filtering wp_count_comments to ensure that Sensei comments are ignored
     * @since   1.4.0
     * @access  public
     * @param  array   $comments
     * @param  integer $post_id
     * @return array
     */
    public function sensei_count_comments( $comments, $post_id ) {
        global $wpdb;

        $post_id = (int) $post_id;

        $count = wp_cache_get("comments-{$post_id}", 'counts');

        if ( false !== $count ) {
            return $count;
        }

        $statuses = array( '' ); // Default to the WP normal comments
        $stati = $wpdb->get_results( "SELECT comment_type FROM {$wpdb->comments} GROUP BY comment_type", ARRAY_A );
        foreach ( (array) $stati AS $status ) {
            if ( 'sensei_' != substr($status['comment_type'], 0, 7 ) ) {
                $statuses[] = $status['comment_type'];
            }
        }
        $where = "WHERE comment_type IN ('" . join("', '", array_unique( $statuses ) ) . "')";

        if ( $post_id > 0 )
            $where .= $wpdb->prepare( " AND comment_post_ID = %d", $post_id );

        $count = $wpdb->get_results( "SELECT comment_approved, COUNT( * ) AS num_comments FROM {$wpdb->comments} {$where} GROUP BY comment_approved", ARRAY_A );

        $total = 0;
        $approved = array('0' => 'moderated', '1' => 'approved', 'spam' => 'spam', 'trash' => 'trash', 'post-trashed' => 'post-trashed');
        foreach ( (array) $count as $row ) {
            // Don't count post-trashed toward totals
            if ( 'post-trashed' != $row['comment_approved'] && 'trash' != $row['comment_approved'] )
                $total += $row['num_comments'];
            if ( isset( $approved[$row['comment_approved']] ) )
                $stats[$approved[$row['comment_approved']]] = $row['num_comments'];
        }

        $stats['total_comments'] = $total;
        foreach ( $approved as $key ) {
            if ( empty($stats[$key]) )
                $stats[$key] = 0;
        }

        $stats = (object) $stats;
        wp_cache_set("comments-{$post_id}", $stats, 'counts');

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
        $course_archive_thumbnail 	= $this->get_image_size( 'course_archive_image' );
        $course_single_thumbnail	= $this->get_image_size( 'course_single_image' );
        $lesson_archive_thumbnail 	= $this->get_image_size( 'lesson_archive_image' );
        $lesson_single_thumbnail	= $this->get_image_size( 'lesson_single_image' );

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
     * @param mixed $image_size
     * @return string
     */
    public function get_image_size( $image_size ) {

        // Only return sizes we define in settings
        if ( ! in_array( $image_size, array( 'course_archive_image', 'course_single_image', 'lesson_archive_image', 'lesson_single_image' ) ) )
            return apply_filters( 'sensei_get_image_size_' . $image_size, '' );

        if( ! isset( $this->settings->settings[ $image_size . '_width' ] ) ) {
            $this->settings->settings[ $image_size . '_width' ] = false;
        }
        if( ! isset( $this->settings->settings[ $image_size . '_height' ] ) ) {
            $this->settings->settings[ $image_size . '_height' ] = false;
        }
        if( ! isset( $this->settings->settings[ $image_size . '_hard_crop' ] ) ) {
            $this->settings->settings[ $image_size . '_hard_crop' ] = false;
        }

        $size = array_filter( array(
            'width' => $this->settings->settings[ $image_size . '_width' ],
            'height' => $this->settings->settings[ $image_size . '_height' ],
            'crop' => $this->settings->settings[ $image_size . '_hard_crop' ]
        ) );

        $size['width'] 	= isset( $size['width'] ) ? $size['width'] : '100';
        $size['height'] = isset( $size['height'] ) ? $size['height'] : '100';
        $size['crop'] 	= isset( $size['crop'] ) ? $size['crop'] : 0;

        return apply_filters( 'sensei_get_image_size_' . $image_size, $size );
    }

    public function body_class( $classes ) {
        if( is_sensei() ) {
            $classes[] = 'sensei';
        }
        return $classes;
    }

    /**
     * Checks that the Jetpack Beautiful Maths module has been activated to support LaTeX within question titles and answers
     *
     * @return null
     * @since 1.7.0
     */
    public function jetpack_latex_support() {
        if ( function_exists( 'latex_markup') ) {
            add_filter( 'sensei_question_title', 'latex_markup' );
            add_filter( 'sensei_answer_text', 'latex_markup' );
        }
    }

    /**
     * Load the module functionality.
     *
     * This function is hooked into plugins_loaded to avoid conflicts with
     * the retired modules extension.
     *
     * @since 1.8.0
     */
    public function load_modules_class(){
        global $sensei_modules;

        if( !class_exists( 'Sensei_Modules' )
            &&  'Sensei_Modules' != get_class( $sensei_modules ) ) {

            //Load the modules class
            require_once( 'class-sensei-modules.php');
            Sensei()->modules = new Sensei_Core_Modules( $this->file );

        }else{
            // fallback for people still using the modules extension.
            global $sensei_modules;
            Sensei()->modules = $sensei_modules;
            add_action( 'admin_notices', array( $this, 'disable_sensei_modules_extension'), 30 );
        }
    }

    /**
     * Tell the user to that the modules extension is no longer needed.
     *
     * @since 1.8.0
     */
    public function disable_sensei_modules_extension(){ ?>
        <div class="notice updated fade">
            <p>
                <?php
                $plugin_manage_url = admin_url().'plugins.php#sensei-modules';
                $plugin_link_element = '<a href="' . $plugin_manage_url . '" >plugins page</a> ';
                ?>
                <strong> Modules are now included in Sensei,</strong> so you no longer need the Sensei Modules extension.
                Please deactivate and delete it from your <?php echo $plugin_link_element; ?>. (This will not affect your existing modules).
            </p>
        </div>

    <?php }// end function

    /**
     * Sensei wide rewrite flush call.
     *
     * To use this simply update the option 'sensei_flush_rewrite_rules' to 1
     *
     * After the option is one the Rules will be flushed.
     *
     * @since 1.9.0
     */
    public function flush_rewrite_rules(){

        // ensures that the rewrite rules are flushed on the second
        // attempt. This ensure that the settings for any other process
        // have been completed and saved to the database before we refresh the
        // rewrite rules.
        $option =  get_option('sensei_flush_rewrite_rules');
        if( '1' == $option ) {

            update_option('sensei_flush_rewrite_rules', '2');

        }elseif( '2' == $option ) {

            flush_rewrite_rules();
            update_option('sensei_flush_rewrite_rules', '0');

        }

    } // end flush_rewrite_rules

    /**
     * Calling this function will tell Sensei to flush rewrite
     * rules on the next load.
     *
     * @since 1.9.0
     */
    public function initiate_rewrite_rules_flush(){

        update_option('sensei_flush_rewrite_rules', '1');

    }

    /**
     * sensei_woocommerce_email_course_details adds detail to email
     *
     * @deprecated since 1.9.0 use Sensei_WC::email_course_details
     *
     * @since   1.4.5
     * @access  public
     * @param   WC_Order $order
     *
     * @return  void
     */
    public function sensei_woocommerce_email_course_details( $order ) {

        Sensei_WC::email_course_details( $order );

    } // end func email course details

    /**
     * @deprecated since 1.9.0, movde to the Sensei_WC class
     * @param $user_id
     * @param $subscription_key
     */
    public function sensei_woocommerce_reactivate_subscription( $user_id, $subscription_key ){

        Sensei_WC::reactivate_subscription( $user_id, $subscription_key );
    }

    /**
     * @deprecated since 1.9.0, movde to the Sensei_WC class
     * @param $user_id
     * @param $subscription_key
     */
    public function sensei_woocommerce_subscription_ended( $user_id, $subscription_key ){

        Sensei_WC::end_subscription( $user_id, $subscription_key );
    }

    /**
     * sensei_woocommerce_complete_order description
     *
     * @deprecated since 1.9.0 use Sensei_WC::complete_order( $order_id );
     * @since   1.0.3
     * @access  public
     * @param   int $order_id WC order ID
     *
     * @return  void
     */
    public function sensei_woocommerce_complete_order ( $order_id = 0 ) {

        Sensei_WC::complete_order( $order_id );

    } // End sensei_woocommerce_complete_order()

    /**
     * Runs when an order is cancelled.
     *
     * @deprecated since 1.9.0
     *
     * @since   1.2.0
     * @param   integer $order_id order ID
     * @return  void
     */
    public function sensei_woocommerce_cancel_order ( $order_id ) {

        Sensei_WC::cancel_order( $order_id );

    } // End sensei_woocommerce_cancel_order()

    /**
     * sensei_activate_subscription runs when a subscription product is purchased
     * @deprecated since 1.9.0
     * @since   1.2.0
     * @access  public
     * @param   integer $order_id order ID
     * @return  void
     */
    public function sensei_activate_subscription(  $order_id = 0 ) {

        Sensei_WC::activate_subscription( $order_id );

    } // End sensei_activate_subscription()

    /**
     * If WooCommerce is activated and the customer has purchased the course, update Sensei to indicate that they are taking the course.
     * @deprecated since 1.9.0
     * @since  1.0.0
     * @param  int 			$course_id  (default: 0)
     * @param  array/Object $order_user (default: array()) Specific user's data.
     * @return bool|int
     */
    public function woocommerce_course_update ( $course_id = 0, $order_user = array()  ) {

        return Sensei_WC::course_update( $course_id, $order_user );

    } // End woocommerce_course_update()

    /**
     * Returns the WooCommerce Product Object
     *
     * The code caters for pre and post WooCommerce 2.2 installations.
     *
     * @deprecated since 1.9.0
     * @since   1.1.1
     *
     * @param   integer $wc_product_id Product ID or Variation ID
     * @param   string  $product_type  '' or 'variation'
     *
     * @return   WC_Product $wc_product_object
     */
    public function sensei_get_woocommerce_product_object ( $wc_product_id = 0, $product_type = '' ) {

        return Sensei_WC::get_product_object( $wc_product_id, $product_type );

    } // End sensei_get_woocommerce_product_object()

    /**
     * Setup required WooCommerce settings.
     * @access  public
     * @since   1.1.0
     * @return  void
     */
    public function set_woocommerce_functionality() {

        _deprecated_function('Sensei()->set_woocommerce_functionality', 'Sensei 1.9.0');

    } // End set_woocommerce_functionality()

    /**
     * Disable guest checkout if a course product is in the cart
     * @deprecated since 1.9.0
     * @param  boolean $guest_checkout Current guest checkout setting
     * @return boolean                 Modified guest checkout setting
     */
    public function disable_guest_checkout( $guest_checkout ) {

        return Sensei_WC::disable_guest_checkout( $guest_checkout );

    }// end disable_guest_checkout

    /**
     * Change order status with virtual products to completed
     *
     * @deprecated since 1.9.0 use Sensei_WC::virtual_order_payment_complete( $order_status, $order_id )
     *
     * @since  1.1.0
     * @param string $order_status
     * @param int $order_id
     * @return string
     **/
    public function virtual_order_payment_complete( $order_status, $order_id ) {

        return  Sensei_WC::virtual_order_payment_complete( $order_status, $order_id );
    }

} // End Class

/**
 * Class Woothemes_Sensei
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class Woothemes_Sensei extends Sensei_Main{ }
