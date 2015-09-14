<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Class
 *
 * Base class for Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Core
 * @author WooThemes
 * @since 1.0.0
 */
class WooThemes_Sensei {

    /**
     * @var file
     * Reference to the main plugin file
     */
    private $file;

    /**
     * @var $_instance reference to the the main and only instance of the Sensei class.
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
     * @var WooThemes_Sensei_PostTypes
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
     * @var String
     */
    public $notice;

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
        $this->permissions_message = array( 'title' => __( 'Permission Denied', 'woothemes-sensei' ), 'message' => __( 'Unfortunately you do not have permissions to access this page.', 'woothemes-sensei' ) );


        // Initialize the core Sensei functionality
        $this->init();

        // Installation
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) $this->install();

        // Run this on activation.
        register_activation_hook( $this->file, array( $this, 'activation' ) );

        // Load the Utils class.
        $this->load_class( 'utils' );

        // Setup post types.
        $this->load_class( 'posttypes' );
        $this->post_types = new WooThemes_Sensei_PostTypes();
        $this->post_types->token = 'woothemes-sensei-posttypes';

        // Lad the updates class
        $this->updates = new WooThemes_Sensei_Updates( $this );

        // Setup settings screen.
        $this->load_class( 'settings-api' );
        $this->load_class( 'settings' );
        $this->settings = new WooThemes_Sensei_Settings();
        $this->settings->token = 'woothemes-sensei-settings';

        // Setup Admin Settings data
        if ( is_admin() ) {

            $this->settings->has_tabs 	= true;
            $this->settings->name 		= __( 'Sensei Settings', 'woothemes-sensei' );
            $this->settings->menu_label	= __( 'Settings', 'woothemes-sensei' );
            $this->settings->page_slug	= 'woothemes-sensei-settings';

        } // End If Statement

        $this->settings->setup_settings();
        $this->settings->get_settings();

        // Load Course Results Class
        $this->load_class( 'course-results' );
        $this->course_results = new WooThemes_Sensei_Course_Results();
        $this->course_results->token = $this->token;

        // Load the teacher role
        require_once( 'class-sensei-teacher.php' );
        $this->teacher = new Sensei_Teacher();

        // Add the Course class
        $this->course = $this->post_types->course;

        // Add the lesson class
        $this->lesson = $this->post_types->lesson;

        // Add the question class
        $this->question = $this->post_types->question;

        //Add the quiz class
        $this->quiz = $this->post_types->quiz;

        // load the modules class
        add_action( 'plugins_loaded', array( $this, 'load_modules_class' ) );

        // Load Learner Management Functionality
        $this->load_class( 'learners' );
        $this->learners = new WooThemes_Sensei_Learners( $file );
        $this->learners->token = $this->token;

        // Differentiate between administration and frontend logic.
        if ( is_admin() ) {

            // Load Admin Welcome class
            require_once( 'admin/class-sensei-welcome.php' );
            new Sensei_Welcome();

            // Load Admin Class
            $this->load_class( 'admin' );
            $this->admin = new WooThemes_Sensei_Admin( $file );
            $this->admin->token = $this->token;

            // Load Analysis Reports
            $this->load_class( 'analysis' );
            $this->analysis = new WooThemes_Sensei_Analysis( $file );
            $this->analysis->token = $this->token;


        } else {

            // Load Frontend Class
            $this->load_class( 'frontend' );
            $this->frontend = new WooThemes_Sensei_Frontend();
            $this->frontend->token = $this->token;
            $this->frontend->init();

            // Load notice Class
            $this->load_class( 'notices' );
            $this->notices = new WooThemes_Sensei_Notices();

            // Frontend Hooks
            add_filter( 'template_include', array( $this, 'template_loader' ), 10, 1 );

        }

        // Load Grading Functionality
        $this->load_class( 'grading' );
        $this->grading = new WooThemes_Sensei_Grading( $file );
        $this->grading->token = $this->token;

        // Load Email Class
        $this->load_class( 'emails' );
        $this->emails = new WooThemes_Sensei_Emails( $file );
        $this->emails->token = $this->token;

        // Load Learner Profiles Class
        $this->load_class( 'learner-profiles' );
        $this->learner_profiles = new WooThemes_Sensei_Learner_Profiles();
        $this->learner_profiles->token = $this->token;

        // Image Sizes
        $this->init_image_sizes();

        // Force WooCommerce Required Settings
        $this->set_woocommerce_functionality();
        add_action( 'widgets_init', array( $this, 'register_widgets' ) );
        add_action( 'after_setup_theme', array( $this, 'ensure_post_thumbnails_support' ) );

        // WooCommerce Payment Actions
        add_action( 'woocommerce_payment_complete' , array( $this, 'sensei_woocommerce_complete_order' ) );
        add_action( 'woocommerce_thankyou' , array( $this, 'sensei_woocommerce_complete_order' ) );
        add_action( 'woocommerce_order_status_completed' , array( $this, 'sensei_woocommerce_complete_order' ) );
        add_action( 'woocommerce_order_status_processing' , array( $this, 'sensei_woocommerce_complete_order' ) );
        add_action( 'woocommerce_order_status_cancelled' , array( $this, 'sensei_woocommerce_cancel_order' ) );
        add_action( 'woocommerce_order_status_refunded' , array( $this, 'sensei_woocommerce_cancel_order' ) );
        add_action( 'subscriptions_activated_for_order', array( $this, 'sensei_activate_subscription' ) );

        // WooCommerce Subscriptions Actions
        add_action( 'reactivated_subscription', array( $this, 'sensei_woocommerce_reactivate_subscription' ), 10, 2 );
        add_action( 'subscription_expired' , array( $this, 'sensei_woocommerce_subscription_ended' ), 10, 2 );
        add_action( 'subscription_end_of_prepaid_term' , array( $this, 'sensei_woocommerce_subscription_ended' ), 10, 2 );
        add_action( 'cancelled_subscription' , array( $this, 'sensei_woocommerce_subscription_ended' ), 10, 2 );
        add_action( 'subscription_put_on-hold' , array( $this, 'sensei_woocommerce_subscription_ended' ), 10, 2 );

        // Add Email link to course orders
        add_action( 'woocommerce_email_after_order_table', array( $this, 'sensei_woocommerce_email_course_details' ), 10, 1 );

        // Filter comment counts
        add_filter( 'wp_count_comments', array( $this, 'sensei_count_comments' ), 10, 2 );

        add_action( 'body_class', array( $this, 'body_class' ) );

        // Check for and activate JetPack LaTeX support
        add_action( 'plugins_loaded', array( $this, 'jetpack_latex_support'), 200 ); // Runs after Jetpack has loaded it's modules

    } // End __construct()

    /**
     * Load the foundations of Sensei.
     * @since 1.9.0
     */
    protected function init(){

        // Localisation
        $this->load_plugin_textdomain();
        add_action( 'init', array( $this, 'load_localisation' ), 0 );

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

        }

        return self::$_instance;

    } // end instance()

    /**
     * Cloning is forbidden.
     * @since 1.8.0
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woothemes-sensei' ), '2.1' );
    }

    /**
     * Unserializing instances of this class is forbidden.
     * @since 1.8.0
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woothemes-sensei' ), '2.1' );
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
     * Setup required WooCommerce settings.
     * @access  public
     * @since   1.1.0
     * @return  void
     */
    public function set_woocommerce_functionality() {
        // Disable guest checkout if a course is in the cart as we need a valid user to store data for
        add_filter( 'pre_option_woocommerce_enable_guest_checkout', array( $this, 'disable_guest_checkout' ) );

        // Mark orders with virtual products as complete rather then stay processing
        add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'virtual_order_payment_complete' ), 10, 2 );

    } // End set_woocommerce_functionality()

    /**
     * Disable guest checkout if a course product is in the cart
     * @param  boolean $guest_checkout Current guest checkout setting
     * @return boolean                 Modified guest checkout setting
     */
    public function disable_guest_checkout( $guest_checkout ) {
        global $woocommerce;

        if( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {

            if( isset( $woocommerce->cart->cart_contents ) && count( $woocommerce->cart->cart_contents ) > 0 ) {
                foreach( $woocommerce->cart->cart_contents as $cart_key => $product ) {
                    if( isset( $product['product_id'] ) ) {
                        $args = array(
                            'posts_per_page' => -1,
                            'post_type' => 'course',
                            'meta_query' => array(
                                array(
                                    'key' => '_course_woocommerce_product',
                                    'value' => $product['product_id']
                                )
                            )
                        );
                        $posts = get_posts( $args );
                        if( $posts && count( $posts ) > 0 ) {
                            foreach( $posts as $course ) {
                                $guest_checkout = '';
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $guest_checkout;
    }

    /**
     * Change order status with virtual products to completed
     * @since  1.1.0
     * @param string $order_status
     * @param int $order_id
     * @return string
     **/
    public function virtual_order_payment_complete( $order_status, $order_id ) {
        $order = new WC_Order( $order_id );
        if ( ! isset ( $order ) ) return;
        if ( $order_status == 'wc-processing' && ( $order->post_status == 'wc-on-hold' || $order->post_status == 'wc-pending' || $order->post_status == 'wc-failed' ) ) {
            $virtual_order = true;

            if ( count( $order->get_items() ) > 0 ) {
                foreach( $order->get_items() as $item ) {
                    if ( $item['product_id'] > 0 ) {
                        $_product = $order->get_product_from_item( $item );
                        if ( ! $_product->is_virtual() ) {
                            $virtual_order = false;
                            break;
                        } // End If Statement
                    } // End If Statement
                } // End For Loop
            } // End If Statement

            // virtual order, mark as completed
            if ( $virtual_order ) {
                return 'completed';
            } // End If Statement
        } // End If Statement
        return $order_status;
    }

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
            if ( file_exists( $this->plugin_path . 'widgets/widget-woothemes-sensei-' . esc_attr( $key ) . '.php' ) ) {
                require_once( $this->plugin_path . 'widgets/widget-woothemes-sensei-' . esc_attr( $key ) . '.php' );
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

            // Check previous version to see if forced updates must run
            // $old_version = get_option( 'woothemes-sensei-version', false );
            // if( $old_version && version_compare( $old_version, '1.7.0', '<' )  ) {
            // 	update_option( 'woothemes-sensei-force-updates', $this->version );
            // } else {
            // 	delete_option( 'woothemes-sensei-force-updates' );
            // }

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
     */
    public function template_loader ( $template = '' ) {
        // REFACTOR
        global $post, $wp_query, $email_template;

        $find = array( 'woothemes-sensei.php' );
        $file = '';

        if ( isset( $email_template ) && $email_template ) {

            $file 	= 'emails/' . $email_template;
            $find[] = $file;
            $find[] = $this->template_url . $file;

        } elseif ( is_single() && get_post_type() == 'course' ) {

            if ( $this->check_user_permissions( 'course-single' ) ) {
                $file 	= 'single-course.php';
                $find[] = $file;
                $find[] = $this->template_url . $file;
            } else {
                // No Permissions Page
                $file 	= 'no-permissions.php';
                $find[] = $file;
                $find[] = $this->template_url . $file;
            } // End If Statement

        } elseif ( is_single() && get_post_type() == 'lesson' ) {

            if ( $this->check_user_permissions( 'lesson-single' ) ) {
                $file 	= 'single-lesson.php';
                $find[] = $file;
                $find[] = $this->template_url . $file;
            } else {
                // No Permissions Page
                $file 	= 'no-permissions.php';
                $find[] = $file;
                $find[] = $this->template_url . $file;
            } // End If Statement

        } elseif ( is_single() && get_post_type() == 'quiz' ) {

            if ( $this->check_user_permissions( 'quiz-single' ) ) {
                $file 	= 'single-quiz.php';
                $find[] = $file;
                $find[] = $this->template_url . $file;
            } else {
                // No Permissions Page
                $file 	= 'no-permissions.php';
                $find[] = $file;
                $find[] = $this->template_url . $file;
            } // End If Statement

        } elseif ( is_single() && get_post_type() == 'sensei_message' ) {

            $file 	= 'single-message.php';
            $find[] = $file;
            $find[] = $this->template_url . $file;

        } elseif ( is_post_type_archive( 'course' ) || is_page( $this->get_page_id( 'courses' ) ) ) {

            $file 	= 'archive-course.php';
            $find[] = $file;
            $find[] = $this->template_url . $file;

        } elseif ( is_post_type_archive( 'sensei_message' ) ) {

            $file 	= 'archive-message.php';
            $find[] = $file;
            $find[] = $this->template_url . $file;

        } elseif( is_tax( 'course-category' ) ) {

            $file 	= 'taxonomy-course-category.php';
            $find[] = $file;
            $find[] = $this->template_url . $file;

        } elseif( is_tax( 'lesson-tag' ) ) {

            $file 	= 'taxonomy-lesson-tag.php';
            $find[] = $file;
            $find[] = $this->template_url . $file;

        } elseif ( is_post_type_archive( 'lesson' ) ) {

            $file 	= 'archive-lesson.php';
            $find[] = $file;
            $find[] = $this->template_url . $file;

        } elseif ( isset( $wp_query->query_vars['learner_profile'] ) ) {

            // Override for sites with static home page
            $wp_query->is_home = false;

            $file 	= 'learner-profile.php';
            $find[] = $file;
            $find[] = $this->template_url . $file;

        } elseif ( isset( $wp_query->query_vars['course_results'] ) ) {

            // Override for sites with static home page
            $wp_query->is_home = false;

            $file 	= 'course-results.php';
            $find[] = $file;
            $find[] = $this->template_url . $file;

        } // Load the template file

        if ( $file ) {
            
            $template = locate_template( $find );

            if ( ! $template ) $template = $this->plugin_path() . 'templates/' . $file;

        } // End If Statement

        return $template;
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
     * @return void
     */
    public function get_page_id ( $page ) {
        $page = apply_filters( 'sensei_get_' . esc_attr( $page ) . '_page_id', get_option( 'sensei_' . esc_attr( $page ) . '_page_id' ) );
        return ( $page ) ? $page : -1;
    } // End get_page_id()


    /**
     * If WooCommerce is activated and the customer has purchased the course, update Sensei to indicate that they are taking the course.
     * @access public
     * @since  1.0.0
     * @param  int 			$course_id  (default: 0)
     * @param  array/Object $order_user (default: array()) Specific user's data.
     * @return void
     */
    public function woocommerce_course_update ( $course_id = 0, $order_user = array()  ) {
        global $current_user;

        if ( ! isset( $current_user ) ) return;

        $data_update = false;

        // Get the product ID
        $wc_post_id = get_post_meta( intval( $course_id ), '_course_woocommerce_product', true );

        // Check if in the admin
        if ( is_admin() ) {
            $user_login = $order_user['user_login'];
            $user_email = $order_user['user_email'];
            $user_url = $order_user['user_url'];
            $user_id = $order_user['ID'];
        } else {
            $user_login = $current_user->user_login;
            $user_email = $current_user->user_email;
            $user_url = $current_user->user_url;
            $user_id = $current_user->ID;
        } // End If Statement

        // This doesn't appear to be purely WooCommerce related. Should it be in a separate function?
        $course_prerequisite_id = (int) get_post_meta( $course_id, '_course_prerequisite', true );
        if( 0 < absint( $course_prerequisite_id ) ) {
            $prereq_course_complete = WooThemes_Sensei_Utils::user_completed_course( $course_prerequisite_id, intval( $user_id ) );
            if ( ! $prereq_course_complete ) {
                // Remove all course user meta
                return WooThemes_Sensei_Utils::sensei_remove_user_from_course( $course_id, $user_id );
            }
        }

        $is_user_taking_course = WooThemes_Sensei_Utils::user_started_course( intval( $course_id ), intval( $user_id ) );

        if( ! $is_user_taking_course ) {

            if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() && WooThemes_Sensei_Utils::sensei_customer_bought_product( $user_email, $user_id, $wc_post_id ) && ( 0 < $wc_post_id ) ) {

                $activity_logged = WooThemes_Sensei_Utils::user_start_course( intval( $user_id), intval( $course_id ) );

                $is_user_taking_course = false;
                if ( true == $activity_logged ) {
                    $is_user_taking_course = true;
                } // End If Statement
            } // End If Statement
        }

        return $is_user_taking_course;
    } // End woocommerce_course_update()


    /**
     * check_user_permissions function.
     *
     * @access public
     * @param string $page (default: '')
     * @param array $data (default: array())
     * @return void
     */
    public function check_user_permissions ( $page = '', $data = array() ) {
        // REFACTOR
        global $current_user, $post;

        if ( ! isset( $current_user ) ) return;

        // Get User Meta
        get_currentuserinfo();

        $user_allowed = false;

        switch ( $page ) {
            case 'course-single':
                // check for prerequisite course or lesson,
                $course_prerequisite_id = (int) get_post_meta( $post->ID, '_course_prerequisite', true);
                $update_course = $this->woocommerce_course_update( $post->ID );
                // Count completed lessons
                if ( 0 < absint( $course_prerequisite_id ) ) {
                    $prerequisite_complete = WooThemes_Sensei_Utils::user_completed_course( $course_prerequisite_id, $current_user->ID );
                }
                else {
                    $prerequisite_complete = true;
                } // End If Statement
                // Handles restrictions
                if ( !$prerequisite_complete && 0 < absint( $course_prerequisite_id ) ) {
                    $this->permissions_message['title'] = get_the_title( $post->ID ) . ': ' . __('Restricted Access', 'woothemes-sensei' );
                    $course_link = '<a href="' . esc_url( get_permalink( $course_prerequisite_id ) ) . '">' . __( 'course', 'woothemes-sensei' ) . '</a>';
                    $this->permissions_message['message'] = sprintf( __('Please complete the previous %1$s before taking this course.', 'woothemes-sensei' ), $course_link );
                } else {
                    $user_allowed = true;
                } // End If Statement
                break;
            case 'lesson-single':
                // Check for WC purchase
                $lesson_course_id = get_post_meta( $post->ID, '_lesson_course',true );

                $update_course = $this->woocommerce_course_update( $lesson_course_id );
                $is_preview = WooThemes_Sensei_Utils::is_preview_lesson( $post->ID );
                if ( $this->access_settings() && WooThemes_Sensei_Utils::user_started_course( $lesson_course_id, $current_user->ID ) ) {
                    $user_allowed = true;
                } elseif( $this->access_settings() && false == $is_preview ) {

                    $user_allowed = true;
                } else {
                    $this->permissions_message['title'] = get_the_title( $post->ID ) . ': ' . __('Restricted Access', 'woothemes-sensei' );
                    $course_link = '<a href="' . esc_url( get_permalink( $lesson_course_id ) ) . '">' . __( 'course', 'woothemes-sensei' ) . '</a>';
                    $wc_post_id = get_post_meta( $lesson_course_id, '_course_woocommerce_product',true );
                    if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() && ( 0 < $wc_post_id ) ) {
                        if ( $is_preview ) {
                            $this->permissions_message['message'] = sprintf( __('This is a preview lesson. Please purchase the %1$s to access all lessons.', 'woothemes-sensei' ), $course_link );
                        } else {
                            $this->permissions_message['message'] = apply_filters( 'sensei_please_purchase_course_text', sprintf( __('Please purchase the %1$s before starting this Lesson.', 'woothemes-sensei' ), $course_link ) );
                        }
                    } else {
                        if ( $is_preview ) {
                            $this->permissions_message['message'] = sprintf( __('This is a preview lesson. Please sign up for the %1$s to access all lessons.', 'woothemes-sensei' ), $course_link );
                        } else {
                            /** This filter is documented in class-woothemes-sensei-frontend.php */
                            $this->permissions_message['message'] =  apply_filters( 'sensei_please_sign_up_text', sprintf( __( 'Please sign up for the %1$s before starting the lesson.', 'woothemes-sensei' ), $course_link ) );
                        }
                    } // End If Statement
                } // End If Statement
                break;
            case 'quiz-single':
                $lesson_id = get_post_meta( $post->ID, '_quiz_lesson',true );
                $lesson_course_id = get_post_meta( $lesson_id, '_lesson_course',true );

                $update_course = $this->woocommerce_course_update( $lesson_course_id );
                if ( ( $this->access_settings() && WooThemes_Sensei_Utils::user_started_course( $lesson_course_id, $current_user->ID ) ) || sensei_all_access() ) {

                    // Check for prerequisite lesson for this quiz
                    $lesson_prerequisite_id = (int) get_post_meta( $lesson_id, '_lesson_prerequisite', true);
                    $user_lesson_prerequisite_complete = WooThemes_Sensei_Utils::user_completed_lesson( $lesson_prerequisite_id, $current_user->ID);

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

                    if ( is_user_logged_in() && ! WooThemes_Sensei_Utils::user_started_course( $lesson_course_id, $current_user->ID ) && ( isset( $this->settings->settings['access_permission'] ) && ( true == $this->settings->settings['access_permission'] ) ) ) {

                        $user_allowed = false;
                        $this->permissions_message['title'] = get_the_title( $post->ID ) . ': ' . __('Restricted Access', 'woothemes-sensei' );
                        $course_link = '<a href="' . esc_url( get_permalink( $lesson_course_id ) ) . '">' . __( 'course', 'woothemes-sensei' ) . '</a>';
                        $wc_post_id = get_post_meta( $lesson_course_id, '_course_woocommerce_product',true );
                        if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() && ( 0 < $wc_post_id ) ) {
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


        if( sensei_all_access() || WooThemes_Sensei_Utils::is_preview_lesson( $post->ID ) ) {
            $user_allowed = true;
        }

        return apply_filters( 'sensei_access_permissions', $user_allowed );
    } // End get_placeholder_image()


    /**
     * Check if visitors have access permission. If the "access_permission" setting is active, do a log in check.
     * @since  1.0.0
     * @access public
     * @return void
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
     * sensei_woocommerce_complete_order description
     * @since   1.0.3
     * @access  public
     * @param   int $order_id WC order ID
     * @return  void
     */
    public function sensei_woocommerce_complete_order ( $order_id = 0 ) {
        $order_user = array();
        // Check for WooCommerce
        if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() && ( 0 < $order_id ) ) {
            // Get order object
            $order = new WC_Order( $order_id );
            $user = get_user_by( 'id', $order->user_id );
            $order_user['ID'] = $user->ID;
            $order_user['user_login'] = $user->user_login;
            $order_user['user_email'] = $user->user_email;
            $order_user['user_url'] = $user->user_url;
            // Run through each product ordered
            if ( 0 < sizeof( $order->get_items() ) ) {
                foreach( $order->get_items() as $item ) {
                    $product_type = '';
                    if ( isset( $item['variation_id'] ) && ( 0 < $item['variation_id'] ) ) {
                        $item_id = $item['variation_id'];
                        $product_type = 'variation';
                    } else {
                        $item_id = $item['product_id'];
                    } // End If Statement
                    $_product = $this->sensei_get_woocommerce_product_object( $item_id, $product_type );
                    // Get courses that use the WC product
                    $courses = $this->post_types->course->get_product_courses( $_product->id );
                    // Loop and update those courses
                    foreach ( $courses as $course_item ) {
                        $update_course = $this->woocommerce_course_update( $course_item->ID, $order_user );
                    } // End For Loop
                } // End For Loop
            } // End If Statement
            // Add meta to indicate that payment has been completed successfully
            update_post_meta( $order_id, 'sensei_payment_complete', '1' );
        } // End If Statement
    } // End sensei_woocommerce_complete_order()

    /**
     * Runs when an order is cancelled.
     * @since   1.2.0
     * @access  public
     * @param   integer $order_id order ID
     * @return  void
     */
    public function sensei_woocommerce_cancel_order ( $order_id ) {

        // Get order object
        $order = new WC_Order( $order_id );

        // Run through each product ordered
        if ( 0 < sizeof( $order->get_items() ) ) {

            // Get order user
            $user_id = $order->__get( 'user_id' );

            foreach( $order->get_items() as $item ) {

                $product_type = '';
                if ( isset( $item['variation_id'] ) && ( 0 < $item['variation_id'] ) ) {
                    $item_id = $item['variation_id'];
                    $product_type = 'variation';
                } else {
                    $item_id = $item['product_id'];
                } // End If Statement
                $_product = $this->sensei_get_woocommerce_product_object( $item_id, $product_type );

                // Get courses that use the WC product
                $courses = array();
                $courses = $this->post_types->course->get_product_courses( $item_id );

                // Loop and update those courses
                foreach ($courses as $course_item){
                    // Check and Remove course from courses user meta
                    $dataset_changes = WooThemes_Sensei_Utils::sensei_remove_user_from_course( $course_item->ID, $user_id );
                } // End For Loop
            } // End For Loop
        } // End If Statement
    } // End sensei_woocommerce_cancel_order()

    /**
     * Runs when an subscription is cancelled or expires.
     * @since   1.3.3
     * @access  public
     * @param   integer $user_id User ID
     * @param   integer $subscription_key Subscription Unique Key
     * @return  void
     */
    public function sensei_woocommerce_subscription_ended( $user_id, $subscription_key ) {
        $subscription = WC_Subscriptions_Manager::get_users_subscription( $user_id, $subscription_key );
        self::sensei_woocommerce_cancel_order( $subscription['order_id'] );
    }

    /**
     * Runs when an subscription is re-activated after suspension.
     * @since   1.3.3
     * @access  public
     * @param   integer $user_id User ID
     * @param   integer $subscription_key Subscription Unique Key
     * @return  void
     */
    public function sensei_woocommerce_reactivate_subscription( $user_id, $subscription_key ) {
        $subscription = WC_Subscriptions_Manager::get_users_subscription( $user_id, $subscription_key );
        $order = new WC_Order( $subscription['order_id'] );
        $user = get_user_by( 'id', $order->user_id );
        $order_user = array();
        $order_user['ID'] = $user->ID;
        $order_user['user_login'] = $user->user_login;
        $order_user['user_email'] = $user->user_email;
        $order_user['user_url'] = $user->user_url;
        $courses = $this->post_types->course->get_product_courses( $subscription['product_id'] );
        foreach ( $courses as $course_item ){
            $update_course = $this->woocommerce_course_update( $course_item->ID, $order_user );
        } // End For Loop
    } // End sensei_woocommerce_reactivate_subscription

    /**
     * Returns the WooCommerce Product Object
     *
     * The code caters for pre and post WooCommerce 2.2 installations.
     *
     * @since   1.1.1
     * @access  public
     * @param   integer $wc_product_id Product ID or Variation ID
     * @param   string  $product_type  '' or 'variation'
     * @return   WC_Product $wc_product_object
     */
    public function sensei_get_woocommerce_product_object ( $wc_product_id = 0, $product_type = '' ) {

        $wc_product_object = false;
        if ( 0 < intval( $wc_product_id ) ) {

            // Get the product
            if ( function_exists( 'wc_get_product' ) ) {

                $wc_product_object = wc_get_product( $wc_product_id ); // Post WC 2.3

            } elseif ( function_exists( 'get_product' ) ) {

                $wc_product_object = get_product( $wc_product_id ); // Post WC 2.0

            } else {

                // Pre WC 2.0
                if ( 'variation' == $product_type || 'subscription_variation' == $product_type ) {

                    $wc_product_object = new WC_Product_Variation( $wc_product_id );

                } else {

                    $wc_product_object = new WC_Product( $wc_product_id );

                } // End If Statement

            } // End If Statement

        } // End If Statement

        return $wc_product_object;

    } // End sensei_get_woocommerce_product_object()

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
     * sensei_activate_subscription runs when a subscription product is purchased
     * @since   1.2.0
     * @access  public
     * @param   integer $order_id order ID
     * @return  void
     */
    public function sensei_activate_subscription(  $order_id = 0 ) {
        if ( 0 < intval( $order_id ) ) {
            $order = new WC_Order( $order_id );
            $user = get_user_by('id', $order->user_id);
            $order_user['ID'] = $user->ID;
            $order_user['user_login'] = $user->user_login;
            $order_user['user_email'] = $user->user_email;
            $order_user['user_url'] = $user->user_url;
            // Run through each product ordered
            if (sizeof($order->get_items())>0) {
                foreach($order->get_items() as $item) {
                    $product_type = '';
                    if (isset($item['variation_id']) && $item['variation_id'] > 0) {
                        $item_id = $item['variation_id'];
                        $product_type = 'subscription_variation';
                    } else {
                        $item_id = $item['product_id'];
                    } // End If Statement
                    $_product = $this->sensei_get_woocommerce_product_object( $item_id, $product_type );
                    // Get courses that use the WC product
                    $courses = array();
                    if ( $product_type == 'subscription_variation' ) {
                        $courses = $this->post_types->course->get_product_courses( $item_id );
                    } // End If Statement
                    // Loop and update those courses
                    foreach ($courses as $course_item){
                        $update_course = $this->woocommerce_course_update( $course_item->ID, $order_user );
                    } // End For Loop
                } // End For Loop
            } // End If Statement
        } // End If Statement
    } // End sensei_activate_subscription()

    /**
     * sensei_woocommerce_email_course_details adds detail to email
     * @since   1.4.5
     * @access  public
     * @param   WC_Order $order
     * @return  void
     */
    public function sensei_woocommerce_email_course_details( $order ) {
        global $woocommerce, $woothemes_sensei;

        // exit early if not wc-completed or wc-processing
        if( 'wc-completed' != $order->post_status
            && 'wc-processing' != $order->post_status  ) {
            return;
        }

        $order_items = $order->get_items();
        $order_id = $order->id;

        //If object have items go through them all to find course
        if ( 0 < sizeof( $order_items ) ) {

        echo '<h2>' . __( 'Course details', 'woothemes-sensei' ) . '</h2>';

        foreach ( $order_items as $item ) {

                $product_type = '';
                if ( isset( $item['variation_id'] ) && ( 0 < $item['variation_id'] ) ) {
                    // If item has variation_id then its from variation
                    $item_id = $item['variation_id'];
                    $product_type = 'variation';
                } else {
                    // If not its real product set its id to item_id
                    $item_id = $item['product_id'];
                } // End If Statement

                $user_id = get_post_meta( $order_id, '_customer_user', true );

                if( $user_id ) {

                    // Get all courses for product
                    $args = array(
                        'posts_per_page' => -1,
                        'post_type' => 'course',
                        'meta_query' => array(
                            array(
                                'key' => '_course_woocommerce_product',
                                'value' => $item_id
                            )
                        ),
                        'orderby' => 'menu_order date',
                        'order' => 'ASC',
                    );
                    $courses = get_posts( $args );

                    if( $courses && count( $courses ) > 0 ) {

                        foreach( $courses as $course ) {

                            $title = $course->post_title;
                            $permalink = get_permalink( $course->ID );

                            echo '<p><strong>' . sprintf( __( 'View course: %1$s', 'woothemes-sensei' ), '</strong><a href="' . esc_url( $permalink ) . '">' . $title . '</a>' ) . '</p>';
                        }
                    }
                }
            }
        }
    }

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
        global $sensei_modules, $woothemes_sensei;

        if( !class_exists( 'Sensei_Modules' )
            &&  'Sensei_Modules' != get_class( $sensei_modules ) ) {

            //Load the modules class
            require_once( 'class-sensei-modules.php');
            $woothemes_sensei->modules = new Sensei_Core_Modules( $this->file );

        }else{
            // fallback for people still using the modules extension.
            global $sensei_modules;
            $woothemes_sensei->modules = $sensei_modules;
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

} // End Class
