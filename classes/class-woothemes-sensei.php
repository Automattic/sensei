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
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 * - run_upgrades()
 * - set_woocommerce_functionality()
 * - virtual_order_payment_complete
 * - register_widgets()
 * - load_localisation()
 * - load_plugin_textdomain()
 * - activation()
 * - install()
 * - activate_sensei()
 * - register_plugin_version()
 * - ensure_post_thumbnails_support()
 * - template_loader()
 * - plugin_path()
 * - get_page_id()
 * - woocommerce_course_update()
 * - check_user_permissions()
 * - access_settings()
 * - sensei_woocommerce_complete_order()
 * - sensei_woocommerce_cancel_order()
 * - load_class()
 * - sensei_activate_subscription()
 */
class WooThemes_Sensei {
	public $admin;
	public $frontend;
	public $post_types;
	public $token = 'woothemes-sensei';
	public $plugin_url;
	public $plugin_path;
	public $slider_count = 1;
	public $version;
	public $permissions_message;
	private $file;

	/**
	 * Constructor method.
	 * @param  string $file The base file of the plugin.
	 * @since  1.0.0
	 * @return void
	 */
	public function __construct ( $file ) {
		// REFACTOR
		// Setup object data
		$this->file = $file;
		$this->plugin_url = trailingslashit( plugins_url( '', $plugin = $file ) );
		$this->plugin_path = trailingslashit( dirname( $file ) );
		$this->template_url	= apply_filters( 'sensei_template_url', 'sensei/' );
		$this->permissions_message = array( 'title' => __( 'Permission Denied', 'woothemes-sensei' ), 'message' => __( 'Unfortunately you do not have permissions to access this page.', 'woothemes-sensei' ) );
		// Localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );
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
		// Differentiate between administration and frontend logic.
		if ( is_admin() ) {
			// Load Admin Class
			$this->load_class( 'admin' );
			$this->admin = new WooThemes_Sensei_Admin( $file );
			$this->admin->token = $this->token;
			// Load Analysis Reports
			$this->load_class( 'analysis' );
			$this->analysis = new WooThemes_Sensei_Analysis( $file );
			$this->analysis->token = $this->token;
			// Load Grading Functionality
			$this->load_class( 'grading' );
			$this->grading = new WooThemes_Sensei_Grading( $file );
			$this->grading->token = $this->token;
		} else {
			// Load Frontend Class
			$this->load_class( 'frontend' );
			$this->frontend = new WooThemes_Sensei_Frontend();
			$this->frontend->token = $this->token;
			$this->frontend->init();
			// Frontend Hooks
			add_filter( 'template_include', array( $this, 'template_loader' ) );
		}
		// Force WooCommerce Required Settings
		$this->set_woocommerce_functionality();
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );
		add_action( 'after_setup_theme', array( $this, 'ensure_post_thumbnails_support' ) );
		// WooCommerce Payment Actions
		add_action( 'woocommerce_payment_complete' , array( $this, 'sensei_woocommerce_complete_order' ) );
		add_action( 'woocommerce_thankyou' , array( $this, 'sensei_woocommerce_complete_order' ) );
		add_action( 'woocommerce_order_status_completed' , array( $this, 'sensei_woocommerce_complete_order' ) );
		add_action( 'woocommerce_order_status_cancelled' , array( $this, 'sensei_woocommerce_cancel_order' ) );
		add_action( 'subscriptions_activated_for_order', array( $this, 'sensei_activate_subscription' ) );
		// WooCommerce Subscriptions Actions
		add_action( 'reactivated_subscription', array( $this, 'sensei_woocommerce_reactivate_subscription' ), 10, 2 );
		add_action( 'subscription_expired' , array( $this, 'sensei_woocommerce_subscription_ended' ), 10, 2 );
		add_action( 'subscription_end_of_prepaid_term' , array( $this, 'sensei_woocommerce_subscription_ended' ), 10, 2 );
		add_action( 'cancelled_subscription' , array( $this, 'sensei_woocommerce_subscription_ended' ), 10, 2 );
		add_action( 'subscription_put_on-hold' , array( $this, 'sensei_woocommerce_subscription_ended' ), 10, 2 );
		// Run Upgrades once the WP functions have loaded
		if ( is_admin() ) {
			add_action( 'wp_loaded', array( $this, 'run_upgrades' ), 10 );
		} // End If Statement
	} // End __construct()

	/**
	 * Run Sensei updates.
	 * @access  public
	 * @since   1.1.0
	 * @return  void
	 */
	public function run_upgrades() {
		// Run updates if administrator
		if ( current_user_can( 'manage_options' ) ) {
			$this->load_class( 'updates' );
			$this->updates = new WooThemes_Sensei_Updates( $this );
			$this->updates->update();
		} // End If Statement
	} // End run_upgrades()

	/**
	 * Setup required WooCommerce settings.
	 * @access  public
	 * @since   1.1.0
	 * @return  void
	 */
	public function set_woocommerce_functionality() {
		// Disable guest checkout as we need a valid user to store data for
		update_option( 'woocommerce_enable_guest_checkout', false );

		// Make orders with virtual products to complete rather then stay processing
		add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'virtual_order_payment_complete' ), 10, 2 );

	} // End set_woocommerce_functionality()

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
		if ( $order_status == 'processing' && ( $order->status == 'on-hold' || $order->status == 'pending' || $order->status == 'failed' ) ) {
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
			update_option( 'woothemes-sensei' . '-version', $this->version );
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
	public function template_loader ( $template ) {
		// REFACTOR
		global $post;

		$find = array( 'woothemes-sensei.php' );
		$file = '';

		if ( is_single() && get_post_type() == 'course' ) {

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

		} elseif ( is_tax( 'product_cat' ) || is_tax( 'product_tag' ) ) {

		    $term = get_queried_object();

		    $file 		= 'taxonomy-' . $term->taxonomy . '.php';
		    $find[] 	= 'taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
		    $find[] 	= $this->template_url . 'taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
		    $find[] 	= $file;
		    $find[] 	= $this->template_url . $file;

		} elseif ( is_post_type_archive( 'course' ) || is_page( $this->get_page_id( 'courses' ) ) ) {

		    $file 	= 'archive-course.php';
		    $find[] = $file;
		    $find[] = $this->template_url . $file;

		} elseif( is_tax( 'course-category' ) ) {

			$file 	= 'taxonomy-course-category.php';
		    $find[] = $file;
		    $find[] = $this->template_url . $file;

		} elseif ( is_post_type_archive( 'lesson' ) ) {

		    $file 	= 'archive-lesson.php';
		    $find[] = $file;
		    $find[] = $this->template_url . $file;

		} // End If Statement

		// Load the template file
		if ( $file ) {
			$template = locate_template( $find );
			if ( ! $template ) $template = $this->plugin_path() . '/templates/' . $file;
		} // End If Statement

		return $template;
	} // End template_loader()


	/**
	 * Determine the relative path to the plugin's directory.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function plugin_path () {
		if ( $this->plugin_path ) return $this->plugin_path;
		return $this->plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );
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

	 	$is_user_taking_course = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => intval( $course_id ), 'user_id' => intval( $user_id ), 'type' => 'sensei_course_start' ) );

		if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() && WooThemes_Sensei_Utils::sensei_customer_bought_product( $user_email, $user_id, $wc_post_id ) && ( 0 < $wc_post_id ) && ! $is_user_taking_course ) {

			$args = array(
							    'post_id' => intval( $course_id ),
							    'username' => sanitize_user( $user_login ),
							    'user_email' => sanitize_email( $user_email ),
							    'user_url' => esc_url( $user_url ),
							    'data' => __('Course started by the user', 'woothemes-sensei' ),
							    'type' => 'sensei_course_start', /* FIELD SIZE 20 */
							    'parent' => 0,
							    'user_id' => intval( $user_id )
							);

			$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );

			$is_user_taking_course = false;
			if ( true == $activity_logged ) {
				$is_user_taking_course = true;
			} // End If Statement
		} // End If Statement

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

      	if ( is_preview() ) {
      		return true;
      	} // End If Statement

      	$user_allowed = false;

		switch ( $page ) {
			case 'course-single':

					// check for prerequisite course or lesson,
					$course_prerequisite_id = get_post_meta( $post->ID, '_course_prerequisite', true);
					$update_course = $this->woocommerce_course_update( $post->ID  );
					// Count completed lessons
					$lessons_completed = 0;
					$course_lessons = $this->frontend->course->course_lessons( $course_prerequisite_id );
		    		$lessons_completed = 0;
		    		foreach ($course_lessons as $lesson_item){
		    			// Check if Lesson is complete
    					$user_lesson_end =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_item->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_end', 'field' => 'comment_content' ) );
						if ( '' != $user_lesson_end ) {
							//Check for Passed or Completed Setting
		                    $course_completion = $this->settings->settings[ 'course_completion' ];
		                    if ( 'passed' == $course_completion ) {
		                        // If Setting is Passed -> Check for Quiz Grades
		                        $lesson_quizzes = $this->post_types->lesson->lesson_quizzes( $lesson_item->ID );
		                        // Get Quiz ID
		                        if ( is_array( $lesson_quizzes ) || is_object( $lesson_quizzes ) ) {
		                            foreach ($lesson_quizzes as $quiz_item) {
		                                $lesson_quiz_id = $quiz_item->ID;
		                            } // End For Loop
		                            // Quiz Grade
		                            $lesson_grade =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_quiz_id, 'user_id' => $current_user->ID, 'type' => 'sensei_quiz_grade', 'field' => 'comment_content' ) ); // Check for wrapper
		                            // Check if Grade is bigger than pass percentage
		                            $lesson_prerequisite = abs( round( doubleval( get_post_meta( $lesson_quiz_id, '_quiz_passmark', true ) ), 2 ) );
		                            if ( $lesson_prerequisite <= intval( $lesson_grade ) ) {
		                                $lessons_completed++;
		                                $single_lesson_complete = true;
		                            } // End If Statement
		                        } // End If Statement
		                    } else {
		                        $lessons_completed++;
		                        $single_lesson_complete = true;
		                    } // End If Statement;
						} // End If Statement
		    		} // End For Loop
		    		// Check prerequisites
		    		$prerequisite_complete = false;
					if ( absint( $lessons_completed ) == absint( count( $course_lessons ) ) && ( 0 < absint( count( $course_lessons ) ) ) && ( 0 < absint( $lessons_completed ) ) ) {
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
				$update_course = $this->woocommerce_course_update( $lesson_course_id  );
				if ( $this->access_settings() && WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $lesson_course_id, 'user_id' => $current_user->ID, 'type' => 'sensei_course_start' ) ) ) {
					$user_allowed = true;
				} elseif( $this->access_settings() ) {
					$user_allowed = true;
				} else {
					$this->permissions_message['title'] = get_the_title( $post->ID ) . ': ' . __('Restricted Access', 'woothemes-sensei' );
					$course_link = '<a href="' . esc_url( get_permalink( $lesson_course_id ) ) . '">' . __( 'course', 'woothemes-sensei' ) . '</a>';
					$wc_post_id = get_post_meta( $lesson_course_id, '_course_woocommerce_product',true );
					if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() && ( 0 < $wc_post_id ) ) {
						$this->permissions_message['message'] = sprintf( __('Please purchase the %1$s before starting this Lesson.', 'woothemes-sensei' ), $course_link );
					} else {
						$this->permissions_message['message'] = sprintf( __('Please sign up for the %1$s before starting this Lesson.', 'woothemes-sensei' ), $course_link );
					} // End If Statement
				} // End If Statement
				break;
			case 'quiz-single':
				$lesson_id = get_post_meta( $post->ID, '_quiz_lesson',true );
				$lesson_course_id = get_post_meta( $lesson_id, '_lesson_course',true );
				$update_course = $this->woocommerce_course_update( $lesson_course_id  );
				if ( $this->access_settings() && WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $lesson_course_id, 'user_id' => $current_user->ID, 'type' => 'sensei_course_start' ) ) ) {
					// Check for prerequisite lesson for this quiz
					$lesson_prerequisite_id = get_post_meta( $lesson_id, '_lesson_prerequisite', true);
					$user_lesson_prerequisite_end =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_prerequisite_id, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_end', 'field' => 'comment_content' ) );
					$user_lesson_prerequisite_complete = false;
					if ( '' != $user_lesson_prerequisite_end ) {
						$user_lesson_prerequisite_complete = true;
					} // End If Statement
					// Handle restrictions
					if ( 0 < absint( $lesson_prerequisite_id ) && ( !$user_lesson_prerequisite_complete ) ) {
						$this->permissions_message['title'] = get_the_title( $post->ID ) . ': ' . __('Restricted Access', 'woothemes-sensei' );
						$lesson_link = '<a href="' . esc_url( get_permalink( $lesson_prerequisite_id ) ) . '">' . __( 'lesson', 'woothemes-sensei' ) . '</a>';
						$this->permissions_message['message'] = sprintf( __('Please complete the previous %1$s before taking this Quiz.', 'woothemes-sensei' ), $lesson_link );
					} else {
						$user_allowed = true;
					} // End If Statement
				} elseif( $this->access_settings() ) {
					// Check if the user has started the course
					if ( is_user_logged_in() && !sensei_has_user_started_course( $lesson_course_id, $current_user->ID ) && ( isset( $this->settings->settings['access_permission'] ) && ( true == $this->settings->settings['access_permission'] ) ) ) {
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
		return apply_filters( 'sensei_access_permissions', $user_allowed );
	} // End get_placeholder_image()


	/**
	 * Check if visitors have access permission. If the "access_permission" setting is active, do a log in check.
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function access_settings () {
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
		// REFACTOR
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
				$courses = array();
				$courses = $this->post_types->course->get_product_courses( $item_id );
				// Loop and update those courses
				foreach ($courses as $course_item){
					// Check and Remove course from courses user meta
					$dataset_changes = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => intval( $course_item->ID ), 'user_id' => intval( $order_user['ID'] ), 'type' => 'sensei_course_start' ) );
		    		$dataset_changes = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => intval( $course_item->ID ), 'user_id' => intval( $order_user['ID'] ), 'type' => 'sensei_course_end' ) );
					// Get all course lessons
	    			$course_lessons = $this->post_types->course->course_lessons( $course_item->ID );
	    			foreach ($course_lessons as $lesson_item){
	    				// Check for lesson complete
	    				$dataset_changes = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => intval( $lesson_item->ID ), 'user_id' => intval( $order_user['ID'] ), 'type' => 'sensei_lesson_start' ) );
	    				$dataset_changes = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => intval( $lesson_item->ID ), 'user_id' => intval( $order_user['ID'] ), 'type' => 'sensei_lesson_end' ) );
	    				// Lesson Quiz Meta
	        			$lesson_quizzes = $this->post_types->lesson->lesson_quizzes( $lesson_item->ID );
	        			if ( 0 < count($lesson_quizzes) )  {
	        				foreach ($lesson_quizzes as $quiz_item){
	        					// Check for quiz grade
	    						$dataset_changes = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => intval( $quiz_item->ID ), 'user_id' => intval( $order_user['ID'] ), 'type' => 'sensei_quiz_grade' ) );
	    						$delete_answers = WooThemes_Sensei_Utils::sensei_delete_quiz_answers( intval( $quiz_item->ID ), intval( $order_user['ID'] ) );
	    					} // End For Loop
	    				} // End If Statement
	    			} // End For Loop
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
	 * Returns the WooCommerce Product Object for pre and post WooCommerce 2.0 installations.
	 * @since   1.1.1
	 * @access  public
	 * @param   integer $wc_product_id Product ID or Variation ID
	 * @param   string  $product_type  '' or 'variation'
	 * @return  woocommerce product object $wc_product_object
	 */
	public function sensei_get_woocommerce_product_object ( $wc_product_id = 0, $product_type = '' ) {
		$wc_product_object = false;
		if ( 0 < intval( $wc_product_id ) ) {
			// Get the product
			if ( function_exists( 'get_product' ) ) {
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

} // End Class
?>