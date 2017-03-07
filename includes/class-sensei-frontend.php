<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Frontend Class
 *
 * All functionality pertaining to the frontend of Sensei.
 *
 * @package Core
 * @author Automattic
 *
 * @since 1.0.0
 */
class Sensei_Frontend {

	public $messages;
	public $data;
	public $allowed_html;

	/**
	 * Constructor.
	 * @since  1.0.0
	 */
	public function __construct () {

		$this->allowed_html = array(
			'embed'  => array(),
			'iframe' => array(
				'width'           => array(),
				'height'          => array(),
				'src'             => array(),
				'frameborder'     => array(),
				'allowfullscreen' => array(),
			),
			'video'  => Sensei_Wp_Kses::get_video_html_tag_allowed_attributes()
		);

		// Template output actions
		add_action( 'sensei_before_main_content', array( $this, 'sensei_output_content_wrapper' ), 10 );
		add_action( 'sensei_after_main_content', array( $this, 'sensei_output_content_wrapper_end' ), 10 );
		add_action( 'sensei_lesson_archive_lesson_title', array( $this, 'sensei_lesson_archive_lesson_title' ), 10 );

		// 1.2.1
		add_action( 'wp_head', array( $this, 'sensei_complete_lesson' ), 10 );
		add_action( 'wp_head', array( $this, 'sensei_complete_course' ), 10 );
		add_action( 'sensei_frontend_messages', array( $this, 'sensei_frontend_messages' ) );
		add_action( 'sensei_lesson_video', array( $this, 'sensei_lesson_video' ), 10, 1 );
		add_action( 'sensei_complete_lesson_button', array( $this, 'sensei_complete_lesson_button' ) );
		add_action( 'sensei_reset_lesson_button', array( $this, 'sensei_reset_lesson_button' ) );

		add_action( 'sensei_course_archive_meta', array( $this, 'sensei_course_archive_meta' ) );

		add_action( 'sensei_lesson_tag_main_content', array( $this, 'sensei_lesson_archive_main_content' ), 10 );
		add_action( 'sensei_no_permissions_main_content', array( $this, 'sensei_no_permissions_main_content' ), 10 );

		add_action( 'sensei_lesson_meta', array( $this, 'sensei_lesson_meta' ), 10 );
		add_action( 'sensei_single_course_content_inside_before', array( $this, 'sensei_course_start' ), 10 );

		add_filter( 'the_title', array( $this, 'sensei_lesson_preview_title' ), 10, 2 );

		//1.6.2
		add_filter( 'wp_login_failed', array( $this, 'sensei_login_fail_redirect' ), 10 );
		add_filter( 'init', array( $this, 'sensei_handle_login_request' ), 10 );
		//1.6.3
		add_action( 'init', array( $this, 'sensei_process_registration' ), 2 );
		//1.7.0
		add_action( 'sensei_pagination', array( $this, 'sensei_breadcrumb' ), 80, 1 );

		// Fix pagination for course archive pages when filtering by course type
		add_filter( 'pre_get_posts', array( $this, 'sensei_course_archive_pagination' ) );

		// Scripts and Styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Custom Menu Item filters
		add_filter( 'wp_setup_nav_menu_item', array( $this, 'sensei_setup_nav_menu_item' ) );
		add_filter( 'wp_nav_menu_objects', array( $this, 'sensei_wp_nav_menu_objects' ) );
		// Search Results filters
		add_filter( 'post_class', array( $this, 'sensei_search_results_classes' ), 10 );
		// Only show course & lesson excerpts in search results
		add_filter( 'the_content', array( $this, 'sensei_search_results_excerpt' ) );

        //Use WooCommerce filter to show admin bar to Teachers.
        add_action( 'init', array( $this, 'sensei_show_admin_bar') );

        // Remove course from active courses if an order is cancelled or refunded
		add_action( 'woocommerce_order_status_processing_to_cancelled', array( $this, 'remove_active_course' ), 10, 1 );
		add_action( 'woocommerce_order_status_completed_to_cancelled', array( $this, 'remove_active_course' ), 10, 1 );
		add_action( 'woocommerce_order_status_on-hold_to_cancelled', array( $this, 'remove_active_course' ), 10, 1 );
		add_action( 'woocommerce_order_status_processing_to_refunded', array( $this, 'remove_active_course' ), 10, 1 );
		add_action( 'woocommerce_order_status_completed_to_refunded', array( $this, 'remove_active_course' ), 10, 1 );
		add_action( 'woocommerce_order_status_on-hold_to_refunded', array( $this, 'remove_active_course' ), 10, 1 );

		// Make sure correct courses are marked as active for users
		add_action( 'sensei_before_my_courses', array( $this, 'activate_purchased_courses' ), 10, 1 );
		add_action( 'sensei_single_course_content_inside_before', array( $this, 'activate_purchased_single_course' ), 10 );

		// Lesson tags
		add_action( 'sensei_lesson_meta_extra', array( $this, 'lesson_tags_display' ), 10, 1 );
		add_action( 'pre_get_posts', array( $this, 'lesson_tag_archive_filter' ), 10, 1 );
		add_filter( 'sensei_lessons_archive_text', array( $this, 'lesson_tag_archive_header' ) );
		add_action( 'sensei_loop_lesson_inside_before', array( $this, 'lesson_tag_archive_description' ), 11 );

		// Hide Sensei activity comments from lesson and course pages
		add_filter( 'wp_list_comments_args', array( $this, 'hide_sensei_activity' ) );
	} // End __construct()

	/**
	 * Graceful fallback for course and lesson variables on Frontend object
	 *
	 * @param string $key Key to get.
	 * @since  1.7.3
	 * @return array|mixed
	 */
	public function __get( $key ) {

		if ( 'lesson' == $key || 'course' == $key ) {
			if ( WP_DEBUG ) {
				trigger_error( sprintf( 'Sensei()->frontend->%1$s has been <strong>deprecated</strong> since version %2$s! Please use Sensei()->%1$s to access the instance.', $key, '1.7.3' ) );
			}
			return Sensei()->$key;
		}

		return null;
	}

	/**
	 * Enqueue frontend JavaScripts.
	 * @since  1.0.0
	 * @return void
	 */
	public function enqueue_scripts () {
		$disable_js = Sensei_Utils::get_setting_as_flag( 'js_disable', 'sensei_settings_js_disable' );

		if ( ! $disable_js ) {

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			// My Courses tabs script
			wp_register_script( Sensei()->token . '-user-dashboard', esc_url( Sensei()->plugin_url . 'assets/js/user-dashboard' . $suffix . '.js' ), array( 'jquery-ui-tabs' ), Sensei()->version, true );
			wp_enqueue_script( Sensei()->token . '-user-dashboard' );


            // Course Archive javascript
            if( is_post_type_archive( 'course' ) ){

                wp_register_script( 'sensei-course-archive-js', esc_url( Sensei()->plugin_url . 'assets/js/frontend/course-archive' . $suffix . '.js' ), array( 'jquery' ), '1', true );
                wp_enqueue_script( 'sensei-course-archive-js' );

            }


			// Allow additional scripts to be loaded
			do_action( 'sensei_additional_scripts' );

		} // End If Statement

	} // End enqueue_scripts()

	/**
	 * Enqueue frontend CSS files.
	 * @since  1.0.0
	 * @return void
	 */
	public function enqueue_styles () {

		$disable_styles = Sensei_Utils::get_setting_as_flag( 'styles_disable', 'sensei_disable_styles' );

		if ( ! $disable_styles ) {

			wp_register_style( Sensei()->token . '-frontend', Sensei()->plugin_url . 'assets/css/frontend/sensei.css', '', Sensei()->version, 'screen' );
			wp_enqueue_style( Sensei()->token . '-frontend' );

			// Allow additional stylesheets to be loaded
			do_action( 'sensei_additional_styles' );

		} // End If Statement

	} // End enqueue_styles()


	/**
	 * sensei_get_template_part function.
	 *
     * @deprecated sine 1.9.0
	 * @access public
	 * @param mixed $slug
	 * @param string $name (default: '')
	 * @return void
	 */
	function sensei_get_template_part( $slug, $name = '' ) {

        Sensei_Templates::get_part( $slug, $name );

	} // End sensei_get_template_part()

	/**
	 * sensei_get_template function.
	 *
     * @deprecated since 1.9.0
	 * @access public
	 * @param mixed $template_name
	 * @param array $args (default: array())
	 * @param string $template_path (default: '')
	 * @param string $default_path (default: '')
	 * @return void
	 */
	function sensei_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {

        _deprecated_function( 'sensei_get_template', '1.9.0', 'Sensei_Templates::get_template' );
        Sensei_Templates::get_template($template_name, $args, $template_path, $default_path  );

	} // End sensei_get_template()


	/**
	 * sensei_locate_template function.
	 *
	 * @access public
	 * @param mixed $template_name
	 * @param string $template_path (default: '')
	 * @param string $default_path (default: '')
	 * @return void
	 */
	function sensei_locate_template( $template_name, $template_path = '', $default_path = '' ) {

        _deprecated_function( 'sensei_locate_template', '1.9.0', 'Sensei_Templates::locate_template' );
        Sensei_Templates::locate_template( $template_name, $template_path, $default_path );

	} // End sensei_locate_template()


	/**
	 * sensei_output_content_wrapper function.
     *
	 * @access public
	 * @return void
	 */
	function sensei_output_content_wrapper() {

	    // backwards compatibility check for old location under the wrappers directory of the active theme
        $backwards_compatible_wrapper_location =   array(
            Sensei()->template_url . 'wrappers/wrapper-start.php',
            'wrappers/wrapper-start.php'
        );

        $template = locate_template( $backwards_compatible_wrapper_location );
        if( !empty( $template ) ){

            Sensei_Templates::get_template( 'wrappers/wrapper-start.php' );
            return;

        }

		Sensei_Templates::get_template( 'globals/wrapper-start.php' );

	} // End sensei_output_content_wrapper()


	/**
	 * sensei_output_content_wrapper_end function.
     *
	 * @access public
	 * @return void
	 */
	function sensei_output_content_wrapper_end() {

	    // backwards compatibility check for old location under the wrappers directory of the active theme
        $backwards_compatible_wrapper_location =   array(
            Sensei()->template_url . 'wrappers/wrapper-end.php',
            'wrappers/wrapper-end.php'
        );

        $backwards_compatible_template = locate_template( $backwards_compatible_wrapper_location );
        if( !empty( $backwards_compatible_template ) ){

            Sensei_Templates::get_template( 'wrappers/wrapper-end.php' );
            return;

        }


		Sensei_Templates::get_template( 'globals/wrapper-end.php' );

	} // End sensei_output_content_wrapper_end()


	/**
	 * sensei_output_content_pagination function.
	 *
	 * @access public
	 * @return void
	 */
	public static function load_content_pagination() {

        if( is_singular('course') ) {

            // backwards compatibility check for old location under the wrappers directory of the active theme
            $template = locate_template( array( Sensei()->template_url . 'wrappers/pagination-posts.php' ) );
            if( !empty( $template ) ){

                Sensei_Templates::get_template( 'wrappers/pagination-posts.php' );
                return;

            }

			Sensei_Templates::get_template( 'globals/pagination-posts.php' );

		} elseif( is_singular('lesson') ) {

		    // backwards compatibility check for old location under the wrappers directory of the active theme
		    $template = locate_template( array( Sensei()->template_url . 'wrappers/pagination-lesson.php' ) );
            if( !empty( $template ) ){

                Sensei_Templates::get_template( 'wrappers/pagination-lesson.php' );
                return;

            }

			Sensei_Templates::get_template( 'globals/pagination-lesson.php' );

		} elseif( is_singular('quiz') ) {

		    // backwards compatibility check for old location under the wrappers directory of the active theme
		    $template = locate_template( array( Sensei()->template_url . 'wrappers/pagination-quiz.php' ) );
            if( !empty( $template ) ){

                Sensei_Templates::get_template( 'wrappers/pagination-quiz.php' );
                return;

            }

			Sensei_Templates::get_template( 'globals/pagination-quiz.php' );

		} else {

            // backwards compatibility check for old location under the wrappers directory of the active theme
            $template = locate_template( array( Sensei()->template_url . 'wrappers/pagination.php' ) );
            if( !empty( $template ) ){

                Sensei_Templates::get_template( 'wrappers/pagination.php' );
                return;

            }

			Sensei_Templates::get_template( 'globals/pagination.php' );

		} // End If Statement

	} // End sensei_output_content_pagination()

	/**
	 * outputs comments for the specified pages
	 * @deprecated
	 * @return void
	 */
	function sensei_output_comments() {

		Sensei_Lesson::output_comments();

	} // End sensei_output_comments()

	/**
	 * sensei_setup_nav_menu_item function.
	 *
	 * Generate the urls for Sensei custom menu items.
	 *
	 * @access public
	 * @param object $item
	 * @return object $item
	 */
	public function sensei_setup_nav_menu_item( $item ) {
		global $pagenow, $wp_rewrite;

		if( 'nav-menus.php' != $pagenow && !defined('DOING_AJAX') && isset( $item->url ) && 'custom' == $item->type ) {

			// Set up Sensei menu links
			$course_page_id = intval( Sensei()->settings->settings[ 'course_page' ] );
			$my_account_page_id = intval( Sensei()->settings->settings[ 'my_course_page' ] );

			$course_page_url = Sensei_Course::get_courses_page_url();
			$lesson_archive_url = get_post_type_archive_link( 'lesson' );
			$my_courses_url = get_permalink( $my_account_page_id );
			$my_messages_url = get_post_type_archive_link( 'sensei_message' );

			switch ( $item->url ) {
				case '#senseicourses':
					$item->url = $course_page_url;
					break;

				case '#senseilessons':
					$item->url = $lesson_archive_url;
					break;

				case '#senseimycourses':
					$item->url = $my_courses_url;
					break;

				case '#senseimymessages':
					$item->url = $my_messages_url;
                    // if no archive link exist for sensei_message
                    // set it back to the place holder
                    if( ! $item->url ){

                        $item->url = '#senseimymessages';

                    }
					break;

				case '#senseilearnerprofile':
					$item->url = esc_url( Sensei()->learner_profiles->get_permalink() );
					break;

				case '#senseiloginlogout':
						$logout_url = wp_logout_url( home_url() );
						// Login link links to the My Courses page, to avoid the WP dashboard.
						$login_url = $my_courses_url;

						$item->url = ( is_user_logged_in() ? $logout_url : $login_url );

						// determine the menu title login or logout
						if ( is_user_logged_in() ) {
							$menu_title =  __( 'Logout'  ,'woothemes-sensei');
						} else {
							$menu_title =  __( 'Login'  ,'woothemes-sensei');
						}

						/**
						 * Action Filter: login/logout menu title
						 *
						 * With this filter you can alter the login / login menu item title string
						 *
						 * @param $menu_title
						 */
						$item->title = apply_filters( 'sensei_login_logout_menu_title', $menu_title );

					break;

				default:
					break;
			}

			$_root_relative_current = untrailingslashit( $_SERVER['REQUEST_URI'] );
			$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_root_relative_current );
			$item_url = untrailingslashit( $item->url );
			$_indexless_current = untrailingslashit( preg_replace( '/' . preg_quote( $wp_rewrite->index, '/' ) . '$/', '', $current_url ) );
			// Highlight current menu item
			if ( $item_url && in_array( $item_url, array( $current_url, $_indexless_current, $_root_relative_current ) ) ) {
				$item->classes[] = 'current-menu-item current_page_item';
			}

		} // endif nav

		return $item;

	} // End sensei_setup_nav_menu_item()

	/**
	 * sensei_wp_nav_menu_objects function.
	 *
	 * Remove Sensei custom menu items depending on settings and logged in status.
	 *
	 * @access public
	 * @param object $sorted_menu_items
	 * @return object $sorted_menu_items
	 */
	public function sensei_wp_nav_menu_objects( $sorted_menu_items ) {

		foreach( $sorted_menu_items as $k=>$item ) {

			// Remove the My Messages link for logged out users or if Private Messages are disabled
			if( ! get_post_type_archive_link( 'sensei_message' )
                && '#senseimymessages' == $item->url ) {

				if ( !is_user_logged_in() || ( isset( Sensei()->settings->settings['messages_disable'] ) && Sensei()->settings->settings['messages_disable'] ) ) {

					unset( $sorted_menu_items[$k] );

				}
			}
			// Remove the My Profile link for logged out users.
			if( Sensei()->learner_profiles->get_permalink() == $item->url ) {

				if ( !is_user_logged_in() || ! ( isset( Sensei()->settings->settings[ 'learner_profile_enable' ] ) && Sensei()->settings->settings[ 'learner_profile_enable' ] ) ) {

					unset( $sorted_menu_items[$k] );

				}
			}
		}
		return $sorted_menu_items;
	} // End sensei_wp_nav_menu_objects

	// add category nicenames in body and post class
	function sensei_search_results_classes($classes) {
	    global $post;
	    // Handle Search Classes for Courses, Lessons, and WC Products
	    if ( isset( $post->post_type ) && ( ( 'course' == $post->post_type ) || ( 'lesson' == $post->post_type ) || ( 'product' == $post->post_type ) ) ) {
	    	$classes[] = 'post';
		} // End If Statement
	    return $classes;
	} // End sensei_search_results_classes()

    /**
     * sensei_single_title output for single page title
     * @since  1.1.0
     * @return void
     * @deprecated
     */
    function the_single_title() {

        _deprecated_function(' WooThemes_Sensei_Frontend::the_single_title', '1.9.0');

    } // End sensei_single_title()

	/**
	 * sensei_course_image output for course image Please use Sensei()->course->course_image instead.
     *
     * @deprecated since 1.9.0
     * @param $course_id
     * @param string $width
     * @param string $height
     * @param bool|false $return
     * @return string|void
	 */
	function sensei_course_image( $course_id, $width = '100', $height = '100', $return = false ) {

    	if ( ! $return ) {

			echo Sensei()->course->course_image( $course_id, $width, $height );
            return '';

		} // End If Statement

		return Sensei()->course->course_image( $course_id, $width, $height );

	} // End sensei_course_image()

	/**
	 * sensei_lesson_image output for lesson image
	 * @since  1.2.0
     * @deprecated since 1.9.0
     * @param $lesson_id
     * @param string $width
     * @param string $height
     * @param bool|false $return
     * @param bool|false $widget
     * @return string
	 */
	function sensei_lesson_image( $lesson_id, $width = '100', $height = '100', $return = false, $widget = false ) {

        if( ! $return ){

            echo Sensei()->lesson->lesson_image( $lesson_id, $width, $height, $widget );
            return '';
        }

        return Sensei()->lesson->lesson_image( $lesson_id, $width, $height, $widget );

	} // End sensei_lesson_image()

    /**
     * @since 1.0.0
     * @param WP_Query $query
     */
    function sensei_course_archive_pagination( $query ) {

		if( ! is_admin() && $query->is_main_query() && isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'newcourses', 'featuredcourses', 'freecourses', 'paidcourses' ) ) ) {

			$amount = 0;
			if ( isset( Sensei()->settings->settings[ 'course_archive_amount' ] ) && ( 0 < absint( Sensei()->settings->settings[ 'course_archive_amount' ] ) ) ) {
				$amount = absint( Sensei()->settings->settings[ 'course_archive_amount' ] );
			}

			if( $amount ) {
				$query->set( 'posts_per_page', $amount );
			}

			$query->set( 'orderby', 'menu_order date' );

		}
	}

	/**
	 * sensei_course_archive_header function.
	 *
	 * @access public
	 * @since  1.2.0
     * @deprecated since 1.9.0 use WooThemes_Sensei_Course::archive_header
	 * @return void
	 */
	function sensei_course_archive_header(  ) {

        trigger_error('This function sensei_course_archive_header has been depricated. Please use: WooThemes_Sensei_Course::course_archive_header ');
        WooThemes_Sensei_Course::archive_header( '', '<header class="archive-header"><h1>', '</h1></header>' );

	} // sensei_course_archive_header()

	/**
	 * sensei_lesson_archive_header function.
	 *
     * @deprecated since 1.9.0
	 * @access public
	 * @since  1.2.1
	 * @return void
	 */
	public function sensei_lesson_archive_header( ) {
        _deprecated_function( 'WooThemes_Sensei_Frontend::sensei_lesson_archive_header', '1.9.0', 'WooThemes_Sensei_Lesson::the_archive_header' );
        Sensei()->lesson->the_archive_header();
	} // sensei_course_archive_header()

    /**
     * @deprecated since 1.9.0
     */
	public function sensei_message_archive_header( ){
        _deprecated_function('Sensei_Frontend::sensei_message_archive_header','Please use: Sense');
        Sensei_Messages::the_archive_header();
	} // sensei_message_archive_header()

	/**
	 * sensei_course_archive_course_title output for course archive page individual course title
	 * @since  1.2.0
     * @param WP_Post $post_item
	 * @return void
	 */
	function sensei_course_archive_course_title( $post_item ) {
		if ( isset( $post_item->ID ) && ( 0 < $post_item->ID ) ) {
			$post_id = absint( $post_item->ID );
    		$post_title = $post_item->post_title;
		} else {
			$post_id = get_the_ID();
    		$post_title = get_the_title();
		} // End If Statement
		?><header><h2><a href="<?php echo get_permalink( $post_id ); ?>" title="<?php echo esc_attr( $post_title ); ?>"><?php echo $post_title; ?></a></h2></header><?php
	} // End sensei_course_archive_course_title()

	/**
	 * sensei_lesson_archive_lesson_title output for course archive page individual course title
	 * @since  1.2.1
	 * @return void
	 */
	public function sensei_lesson_archive_lesson_title() {
		$post_id = get_the_ID();
    	$post_title = get_the_title();
		?><header><h2><a href="<?php echo get_permalink( $post_id ); ?>" title="<?php echo esc_attr( $post_title ); ?>"><?php echo $post_title; ?></a></h2></header><?php
	} // End sensei_lesson_archive_lesson_title()

	/**
	 * sensei_breadcrumb outputs Sensei breadcrumb trail on lesson & quiz pages
	 * @since  1.7.0
	 * @param  integer $id course, lesson or quiz id
	 * @return void
	 */
	public function sensei_breadcrumb( $id = 0 ) {

		// Only output on lesson, quiz and taxonomy (module) pages
		if( ! ( is_tax( 'module' ) || is_singular( 'lesson' ) || is_singular( 'quiz' ) ) ) return;

		if( empty( $id )  ){

            $id = get_the_ID();

        }

		$sensei_breadcrumb_prefix = __( 'Back to: ', 'woothemes-sensei' );
		$separator = apply_filters( 'sensei_breadcrumb_separator', '&gt;' );

		$html = '<section class="sensei-breadcrumb">' . $sensei_breadcrumb_prefix;
		// Lesson
		if ( is_singular( 'lesson' ) && 0 < intval( $id ) ) {
			$course_id = intval( get_post_meta( $id, '_lesson_course', true ) );
			if( ! $course_id ) {
				return;
			}
			$html .= '<a href="' . esc_url( get_permalink( $course_id ) ) . '" title="' . __( 'Back to the course', 'woothemes-sensei' ) . '">' . get_the_title( $course_id ) . '</a>';
    	} // End If Statement
    	// Quiz
		if ( is_singular( 'quiz' ) && 0 < intval( $id ) ) {
			$lesson_id = intval( get_post_meta( $id, '_quiz_lesson', true ) );
			if( ! $lesson_id ) {
				return;
			}
			 $html .= '<a href="' . esc_url( get_permalink( $lesson_id ) ) . '" title="' .  __( 'Back to the lesson', 'woothemes-sensei' ) . '">' . get_the_title( $lesson_id ) . '</a>';
    	} // End If Statement

    	// Allow other plugins to filter html
    	$html = apply_filters ( 'sensei_breadcrumb_output', $html, $separator );
    	$html .= '</section>';

    	echo $html;
	} // End sensei_breadcrumb()


    /**
     * @deprecated since 1.9.0 use WooThemes_Sensei_Lesson::course_signup_link instead
     */
	public function sensei_lesson_course_signup_link( ) {

        _deprecated_function('sensei_lesson_course_signup_link', '1.9.0', 'WooThemes_Sensei_Lesson::course_signup_link' );
        WooThemes_Sensei_Lesson::course_signup_link();
	}

	public function lesson_tags_display( $lesson_id = 0 ) {
		if( $lesson_id ) {
			$tags = wp_get_post_terms( $lesson_id, 'lesson-tag' );
			if( $tags && count( $tags ) > 0 ) {
				$tag_list = '';
				foreach( $tags as $tag ) {
					$tag_link = get_term_link( $tag, 'lesson-tag' );
					if( ! is_wp_error( $tag_link ) ) {
						if( $tag_list ) { $tag_list .= ', '; }
						$tag_list .= '<a href="' . $tag_link . '">' . $tag->name . '</a>';
					}
				}
				if( $tag_list ) {
					?><section class="lesson-tags">
		    			<?php printf( __( 'Lesson tags: %1$s', 'woothemes-sensei' ), $tag_list ); ?>
		    		</section><?php
		    	}
	    	}
		}
	}

    /**
     * @param WP_Query $query
     */
	public function lesson_tag_archive_filter( $query ) {
    	if( $query->is_main_query() && is_tax( 'lesson-tag' ) ) {
    		// Limit to lessons only
    		$query->set( 'post_type', 'lesson' );

    		// Set order of lessons
    		$query->set( 'orderby', 'menu_order' );
    		$query->set( 'order', 'ASC' );

    	}
    }

    public function lesson_tag_archive_header( $title ) {
		if( is_tax( 'lesson-tag' ) ) {
			$title = sprintf( __( 'Lesson tag: %1$s', 'woothemes-sensei' ), apply_filters( 'sensei_lesson_tag_archive_title', get_queried_object()->name ) );
		}
		return $title;
	}

	public function lesson_tag_archive_description() {
		if( is_tax( 'lesson-tag' ) ) {
			$tag = get_queried_object();
			echo '<p class="archive-description lesson-description">' . apply_filters( 'sensei_lesson_tag_archive_description', nl2br( $tag->description ), $tag->term_id ) . '</p>';
		}
	}

	public function sensei_complete_lesson() {
		global $post,  $current_user;
		// Handle Quiz Completion
		if ( isset( $_POST['quiz_action'] ) && wp_verify_nonce( $_POST[ 'woothemes_sensei_complete_lesson_noonce' ], 'woothemes_sensei_complete_lesson_noonce' ) ) {

			$sanitized_submit = esc_html( $_POST['quiz_action'] );

			switch ($sanitized_submit) {
                case 'lesson-complete':

					Sensei_Utils::sensei_start_lesson( $post->ID, $current_user->ID, $complete = true );

					break;

                case 'lesson-reset':

					Sensei_Utils::sensei_remove_user_from_lesson( $post->ID, $current_user->ID );

					$this->messages = '<div class="sensei-message note">' .  __( 'Lesson Reset Successfully.', 'woothemes-sensei' ) . '</div>';
					break;

				default:
					// Nothing
					break;

			} // End Switch Statement

		} // End If Statement

	} // End sensei_complete_lesson()

	public function sensei_complete_course() {
		global $post,  $current_user, $wp_query;
		if ( isset( $_POST['course_complete'] ) && wp_verify_nonce( $_POST[ 'woothemes_sensei_complete_course_noonce' ], 'woothemes_sensei_complete_course_noonce' ) ) {

			$sanitized_submit = esc_html( $_POST['course_complete'] );
			$sanitized_course_id = absint( esc_html( $_POST['course_complete_id'] ) );
			// Handle submit data
			switch ($sanitized_submit) {
				case __( 'Mark as Complete', 'woothemes-sensei' ):

					// Add user to course
					$course_metadata = array(
						'start' => current_time('mysql'),
						'percent' => 0, // No completed lessons yet
						'complete' => 0,
					);
					$activity_logged = Sensei_Utils::update_course_status( $current_user->ID, $sanitized_course_id, 'in-progress', $course_metadata );

					if ( $activity_logged ) {
						// Get all course lessons
						$course_lesson_ids = Sensei()->course->course_lessons( $sanitized_course_id, 'any', 'ids' );
						// Mark all quiz user meta lessons as complete
						foreach ( $course_lesson_ids as $lesson_item_id ){
							// Mark lesson as complete
							$activity_logged = Sensei_Utils::sensei_start_lesson( $lesson_item_id, $current_user->ID, $complete = true );
						} // End For Loop

						// Update with final stats
						$course_metadata = array(
							'percent' => 100,
							'complete' => count($course_lesson_ids),
						);
						$activity_logged = Sensei_Utils::update_course_status( $current_user->ID, $sanitized_course_id, 'complete', $course_metadata );

						do_action( 'sensei_user_course_end', $current_user->ID, $sanitized_course_id );

						// Success message
						$this->messages = '<header class="archive-header"><div class="sensei-message tick">' . sprintf( __( '%1$s marked as complete.', 'woothemes-sensei' ), get_the_title( $sanitized_course_id ) ) . '</div></header>';
					} // End If Statement

					break;

				case __( 'Delete Course', 'woothemes-sensei' ):

					Sensei_Utils::sensei_remove_user_from_course( $sanitized_course_id, $current_user->ID );

					// Success message
					$this->messages = '<header class="archive-header"><div class="sensei-message tick">' . sprintf( __( '%1$s deleted.', 'woothemes-sensei' ), get_the_title( $sanitized_course_id ) ) . '</div></header>';
					break;

				default:
					// Nothing
					break;
			} // End Switch Statement

		} // End If Statement
	} // End sensei_complete_course()

	/**
	 * @deprecated use WooThemes_Sensei_Quiz::get_user_answers
	 * @param int $lesson_id
	 * @return array
	 */
	public function sensei_get_user_quiz_answers( $lesson_id = 0 ) {
		global $current_user;

		$user_answers = array();

		if ( 0 < intval( $lesson_id ) ) {
			$lesson_quiz_questions = Sensei()->lesson->lesson_quiz_questions( $lesson_id );
			foreach( $lesson_quiz_questions as $question ) {
				$answer = maybe_unserialize( base64_decode( Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $question->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_user_answer', 'field' => 'comment_content' ) ) ) );
				$user_answers[ $question->ID ] = $answer;
			}
		}

		return $user_answers;
	} // End sensei_get_user_quiz_answers()

	public function sensei_has_user_completed_lesson( $post_id = 0, $user_id = 0 ) {
		_deprecated_function( __FUNCTION__, '1.7', "WooThemes_Sensei_Utils::user_completed_lesson()" );
		return Sensei_Utils::user_completed_lesson( $post_id, $user_id );
	} // End sensei_has_user_completed_lesson()

/**
*
 */
	public function sensei_frontend_messages() {
		Sensei()->notices->maybe_print_notices();
	} // End sensei_frontend_messages()

	public function sensei_lesson_video( $post_id = 0 ) {
		if ( 0 < intval( $post_id ) && sensei_can_user_view_lesson( $post_id ) ) {
			$lesson_video_embed = get_post_meta( $post_id, '_lesson_video_embed', true );
			if ( 'http' == substr( $lesson_video_embed, 0, 4) ) {
        		// V2 - make width and height a setting for video embed
        		$lesson_video_embed = wp_oembed_get( esc_url( $lesson_video_embed ) );
        	} // End If Statement

		$lesson_video_embed = do_shortcode( html_entity_decode( $lesson_video_embed ) );
		$lesson_video_embed = Sensei_Wp_Kses::maybe_sanitize( $lesson_video_embed, $this->allowed_html );

        	if ( '' != $lesson_video_embed ) {
				?><div class="video"><?php echo $lesson_video_embed; ?></div><?php
        	} // End If Statement
        } // End If Statement
	} // End sensei_lesson_video()

	public function sensei_complete_lesson_button() {
		global  $post;

		$quiz_id = 0;

		//make sure user is taking course
		$course_id = Sensei()->lesson->get_course_id( $post->ID );
		if( ! Sensei_Utils::user_started_course( $course_id, get_current_user_id() ) ){
			return;
		}

		// Lesson quizzes
		$quiz_id = Sensei()->lesson->lesson_quizzes( $post->ID );
		$pass_required = true;
		if( $quiz_id ) {
			// Get quiz pass setting
	    	$pass_required = get_post_meta( $quiz_id, '_pass_required', true );
	    }
		if( ! $quiz_id || ( $quiz_id && ! $pass_required ) ) {
			?>
			<form class="lesson_button_form" method="POST" action="<?php echo esc_url( get_permalink() ); ?>">
	            <input type="hidden"
                       name="woothemes_sensei_complete_lesson_noonce"
                       id="woothemes_sensei_complete_lesson_noonce"
                       value="<?php echo esc_attr( wp_create_nonce( 'woothemes_sensei_complete_lesson_noonce' ) ); ?>"
                />

	            <input type="hidden" name="quiz_action" value="lesson-complete" />

                <input type="submit"
                       name="quiz_complete"
                       class="quiz-submit complete"
                       value="<?php _e( 'Complete Lesson', 'woothemes-sensei' ); ?>"/>

	        </form>
			<?php
		} // End If Statement
	} // End sensei_complete_lesson_button()

	public function sensei_reset_lesson_button() {
		global  $post;

		$quiz_id = 0;

		// Lesson quizzes
		$quiz_id = Sensei()->lesson->lesson_quizzes( $post->ID );
		$reset_allowed = true;
		if( $quiz_id ) {
			// Get quiz pass setting
			$reset_allowed = get_post_meta( $quiz_id, '_enable_quiz_reset', true );
		}
		if ( ! $quiz_id || !empty($reset_allowed) ) {
		?>
		<form method="POST" action="<?php echo esc_url( get_permalink() ); ?>">

            <input
            type="hidden"
            name="<?php echo esc_attr( 'woothemes_sensei_complete_lesson_noonce' ); ?>"
            id="<?php echo esc_attr( 'woothemes_sensei_complete_lesson_noonce' ); ?>"
            value="<?php echo esc_attr( wp_create_nonce( 'woothemes_sensei_complete_lesson_noonce' ) ); ?>" />

            <input type="hidden" name="quiz_action" value="lesson-reset" />

            <input type="submit" name="quiz_complete" class="quiz-submit reset" value="<?php _e( 'Reset Lesson', 'woothemes-sensei' ); ?>"/>

        </form>
		<?php
		} // End If Statement
	} // End sensei_reset_lesson_button()

    /**
     * @deprecated since 1.9.0
     */
    public function sensei_lesson_quiz_meta( ) {

        Sensei_Lesson::footer_quiz_call_to_action();

	} // End sensei_lesson_quiz_meta()

	public function sensei_course_archive_meta() {
		global  $post;
		// Meta data
		$post_id = get_the_ID();
		$post_title = get_the_title();
		$author_display_name = get_the_author();
		$author_id = get_the_author_meta('ID');
		$category_output = get_the_term_list( $post_id, 'course-category', '', ', ', '' );
		$free_lesson_count = intval( Sensei()->course->course_lesson_preview_count( $post_id ) );
		?><section class="entry">
        	<p class="sensei-course-meta">
           	<?php if ( isset( Sensei()->settings->settings[ 'course_author' ] ) && ( Sensei()->settings->settings[ 'course_author' ] ) ) { ?>
		   	<span class="course-author"><?php _e( 'by ', 'woothemes-sensei' ); ?><?php the_author_link(); ?></span>
		   	<?php } // End If Statement ?>
		   	<span class="course-lesson-count"><?php echo Sensei()->course->course_lesson_count( $post_id ) . '&nbsp;' . __( 'Lessons', 'woothemes-sensei' ); ?></span>
		   	<?php if ( '' != $category_output ) { ?>
		   	<span class="course-category"><?php echo sprintf( __( 'in %s', 'woothemes-sensei' ), $category_output ); ?></span>
		   	<?php } // End If Statement ?>
		   	<?php sensei_simple_course_price( $post_id ); ?>
        	</p>
        	<p class="course-excerpt"><?php the_excerpt(); ?></p>
        	<?php if ( 0 < $free_lesson_count ) {
                $free_lessons = sprintf( __( 'You can access %d of this course\'s lessons for free', 'woothemes-sensei' ), $free_lesson_count ); ?>
                <p class="sensei-free-lessons"><a href="<?php echo get_permalink( $post_id ); ?>"><?php _e( 'Preview this course', 'woothemes-sensei' ) ?></a> - <?php echo $free_lessons; ?></p>
            <?php } ?>
		</section><?php
	} // End sensei_course_archive_meta()

    /**
     * @deprecated since 1.9.0
     */
	public function sensei_single_main_content() {
	    _deprecated_function('Woothemes_Sensei_Frontend::sensei_single_main_content', '1.9.0');
	} // End sensei_single_main_content()

    /**
    * @deprecated since 1.9.0
    */
	public function sensei_lesson_archive_main_content() {
        _deprecated_function('Sensei_Frontend::sensei_lesson_archive_main_content', '1.9.0', 'Please include loop-lesson.php directly');
	} // End sensei_lesson_archive_main_content()

    /**
    * @deprecated since 1.9.0
    */
	public function sensei_message_archive_main_content() {
		_deprecated_function( 'Sensei_Frontend::sensei_message_archive_main_content', 'This method is no longer needed' );
	} // End sensei_lesson_archive_main_content()

    /**
    * @deprecated since 1.9.0
    */
	public function sensei_no_permissions_main_content() {
        _deprecated_function( 'Sensei_Frontend::sensei_no_permissions_main_content', 'This method is no longer needed' );
	} // End sensei_no_permissions_main_content()

	public function sensei_course_category_main_content() {
		global $post;
		if ( have_posts() ) { ?>

			<section id="main-course" class="course-container">

                <?php do_action( 'sensei_course_archive_header' ); ?>

                <?php while ( have_posts() ) { the_post(); ?>

                    <article class="<?php echo join( ' ', get_post_class( array( 'course', 'post' ), get_the_ID() ) ); ?>">

	    			    <?php sensei_do_deprecated_action('sensei_course_image','1.9.0', 'sensei_single_course_content_inside_before', get_the_ID() ); ?>

	    			    <?php sensei_do_deprecated_action( 'sensei_course_archive_course_title','1.9.0','sensei_course_content_inside_before', $post ); ?>

	    			    <?php do_action( 'sensei_course_archive_meta' ); ?>

	    		    </article>

                <?php } // End While Loop ?>

	    	</section>

		<?php } else { ?>

			<p>

                <?php _e( 'No courses found that match your selection.', 'woothemes-sensei' ); ?>

            </p>

		<?php } // End If Statement

	} // End sensei_course_category_main_content()

	public function sensei_login_form() {
		?>
		<div id="my-courses">
			<?php Sensei()->notices->maybe_print_notices(); ?>
			<div class="col2-set" id="customer_login">

				<div class="col-1">
					<?php
					// output the actual form markup
                    Sensei_Templates::get_template( 'user/login-form.php');
					?>
				</div>

			<?php
			if ( get_option('users_can_register') ) {

				// get current url
				$action_url = get_permalink();

				?>

				<div class="col-2">
					<h2><?php _e( 'Register', 'woothemes-sensei' ); ?></h2>

					<form method="post" class="register"  action="<?php echo esc_url( $action_url ); ?>" >

						<?php do_action( 'sensei_register_form_start' ); ?>

						<p class="form-row form-row-wide">
							<label for="sensei_reg_username"><?php _e( 'Username', 'woothemes-sensei' ); ?> <span class="required">*</span></label>
							<input type="text" class="input-text" name="sensei_reg_username" id="sensei_reg_username" value="<?php if ( ! empty( $_POST['sensei_reg_username'] ) ) echo esc_attr( $_POST['sensei_reg_username'] ); ?>" />
						</p>

						<p class="form-row form-row-wide">
							<label for="sensei_reg_email"><?php _e( 'Email address', 'woothemes-sensei' ); ?> <span class="required">*</span></label>
							<input type="email" class="input-text" name="sensei_reg_email" id="sensei_reg_email" value="<?php if ( ! empty( $_POST['sensei_reg_email'] ) ) echo esc_attr( $_POST['sensei_reg_email'] ); ?>" />
						</p>

						<p class="form-row form-row-wide">
							<label for="sensei_reg_password"><?php _e( 'Password', 'woothemes-sensei' ); ?> <span class="required">*</span></label>
							<input type="password" class="input-text" name="sensei_reg_password" id="sensei_reg_password" value="<?php if ( ! empty( $_POST['sensei_reg_password'] ) ) echo esc_attr( $_POST['sensei_reg_password'] ); ?>" />
						</p>

						<!-- Spam Trap -->
						<div style="left:-999em; position:absolute;"><label for="trap"><?php _e( 'Anti-spam', 'woothemes-sensei' ); ?></label><input type="text" name="email_2" id="trap" tabindex="-1" /></div>

						<?php do_action( 'sensei_register_form_fields' ); ?>
						<?php do_action( 'register_form' ); ?>

						<p class="form-row">
							<input type="submit" class="button" name="register" value="<?php _e( 'Register', 'woothemes-sensei' ); ?>" />
						</p>

						<?php do_action( 'sensei_register_form_end' ); ?>

					</form>
				</div>
				<?php
			}
			?>
			</div>
		</div>

		<?php
	} // End sensei_login_form()

	public function sensei_lesson_meta( $post_id = 0 ) {
		global $post;
		if ( 0 < intval( $post_id ) ) {
		$lesson_course_id = absint( get_post_meta( $post_id, '_lesson_course', true ) );
		?><section class="entry">
            <p class="sensei-course-meta">
			    <?php if ( isset( Sensei()->settings->settings[ 'lesson_author' ] ) && ( Sensei()->settings->settings[ 'lesson_author' ] ) ) { ?>
			    <span class="course-author"><?php _e( 'by ', 'woothemes-sensei' ); ?><?php the_author_link(); ?></span>
			    <?php } ?>
                <?php if ( 0 < intval( $lesson_course_id ) ) { ?>
                <span class="lesson-course"><?php echo '&nbsp;' . sprintf( __( 'Part of: %s', 'woothemes-sensei' ), '<a href="' . esc_url( get_permalink( $lesson_course_id ) ) . '" title="' . __( 'View course', 'woothemes-sensei' ) . '"><em>' . get_the_title( $lesson_course_id ) . '</em></a>' ); ?></span>
                <?php } ?>
            </p>
            <p class="lesson-excerpt"><?php the_excerpt( ); ?></p>
		</section><?php
		} // End If Statement
	} // sensei_lesson_meta()

	public function sensei_lesson_preview_title_text( $course_id ) {

		$preview_text = __( ' (Preview)', 'woothemes-sensei' );

		//if this is a paid course
		if ( Sensei_WC::is_woocommerce_active() ) {
    	    $wc_post_id = get_post_meta( $course_id, '_course_woocommerce_product', true );
    	    if ( 0 < $wc_post_id ) {
    	    	$preview_text = __( ' (Free Preview)', 'woothemes-sensei' );
    	    } // End If Statement
    	}
    	return $preview_text;
	}

	public function sensei_lesson_preview_title( $title = '', $id = 0 ) {
		global $post, $current_user;

		// Limit to lessons and check if lesson ID matches filtered post ID
		// @see https://github.com/woothemes/sensei/issues/574
		if( isset( $post->ID ) && $id == $post->ID && 'lesson' == get_post_type( $post ) ) {

			// Limit to main query only
			if( is_main_query() ) {

				// Get the course ID
				$course_id = get_post_meta( $post->ID, '_lesson_course', true );

				// Check if the user is taking the course
				if( is_singular( 'lesson' ) && Sensei_Utils::is_preview_lesson( $post->ID ) && ! Sensei_Utils::user_started_course( $course_id, $current_user->ID ) && $post->ID == $id ) {
					$title .= ' ' . $this->sensei_lesson_preview_title_text( $course_id );
				}
			}
		}
		return $title;
	} // sensei_lesson_preview_title

	public function sensei_course_start() {
		global $post, $current_user;

		// Check if the user is taking the course
		$is_user_taking_course = Sensei_Utils::user_started_course( $post->ID, $current_user->ID );
		// Handle user starting the course
		if ( isset( $_POST['course_start'] )
		    && wp_verify_nonce( $_POST[ 'woothemes_sensei_start_course_noonce' ], 'woothemes_sensei_start_course_noonce' )
		    && !$is_user_taking_course ) {

			// Start the course
			$activity_logged = Sensei_Utils::user_start_course( $current_user->ID, $post->ID );
			$this->data = new stdClass();
			$this->data->is_user_taking_course = false;
			if ( $activity_logged ) {
				$this->data->is_user_taking_course = true;

				// Refresh page to avoid re-posting
				?>

			    <script type="text/javascript"> window.location = '<?php echo get_permalink( $post->ID ); ?>'; </script>

			    <?php
			} // End If Statement
		} // End If Statement
	} // End sensei_course_start()

    /**
     * @deprecated since 1.9.0
     */
	public function sensei_course_meta() {
        _deprecated_function( 'Sensei_Frontend::sensei_course_meta', '1.9.0' , 'Sensei_Course::the_course_meta()' );
        Sensei()->course->the_course_meta( get_post() );
	} // End sensei_course_meta()

    /**
     * @deprecated since 1.9.0
     */
	public function sensei_course_meta_video() {
        _deprecated_function( 'Sensei_Frontend::sensei_course_meta_video', '1.9.0' , 'Sensei_Course::the_course_video()' );
        Sensei_Course::the_course_video();
	} // End sensei_course_meta_video()

    /**
     * This function shows the WooCommerce cart notice if the user has
     * added the current course to cart. It does not show if the user is already taking
     * the course.
     *
     * @since 1.0.2
     * @return void;
     */
    public function sensei_woocommerce_in_cart_message() {
		global $post, $woocommerce;

		$wc_post_id = absint( get_post_meta( $post->ID, '_course_woocommerce_product', true ) );
        $user_course_status_id = Sensei_Utils::user_started_course($post->ID , get_current_user_id() );
		if ( 0 < intval( $wc_post_id ) && ! $user_course_status_id ) {

			if ( Sensei_WC::is_product_in_cart( $wc_post_id ) ) {
				echo '<div class="sensei-message info">' . sprintf(  __('You have already added this Course to your cart. Please %1$s to access the course.', 'woothemes-sensei') . '</div>', '<a class="cart-complete" href="' . $woocommerce->cart->get_checkout_url() . '" title="' . __('complete the purchase', 'woothemes-sensei') . '">' . __('complete the purchase', 'woothemes-sensei') . '</a>' );
			} // End If Statement

		} // End If Statement

	} // End sensei_woocommerce_in_cart_message()

	// Deprecated
	public function sensei_lesson_comment_count( $count ) {
		return $count;
	} // End sensei_lesson_comment_count()

	/**
	 * Only show excerpts for lessons and courses in search results
	 * @param  string $content Original content
	 * @return string          Modified content
	 */
	public function sensei_search_results_excerpt( $content ) {
		global $post;

		if( is_search() && in_array( $post->post_type, array( 'course', 'lesson' ) ) ) {

			// Prevent infinite loop, because wp_trim_excerpt calls the_content filter again
			remove_filter( 'the_content', array( $this, 'sensei_search_results_excerpt' ) );

			// Don't echo the excerpt
			$content = '<p class="course-excerpt">' . get_the_excerpt( ) . '</p>';
		}

		return $content;
	} // End sensei_search_results_excerpt()

	/**
	 * Remove active course when an order is refunded or cancelled
	 * @param  integer $order_id ID of order
	 * @return void
	 */
	public function remove_active_course( $order_id ) {
		$order = new WC_Order( $order_id );

		foreach ( $order->get_items() as $item ) {
			if ( isset( $item['variation_id'] ) && ( 0 < $item['variation_id'] ) ) {
				// If item has variation_id then its a variation of the product
				$item_id = $item['variation_id'];
			} else {
				// Than its real product set it's id to item_id
				$item_id = $item['product_id'];
			}

            if ( $item_id > 0 ) {

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
						'fields' => 'ids',
					);
					$course_ids = get_posts( $args );

					if( $course_ids && count( $course_ids ) > 0 ) {
						foreach( $course_ids as $course_id ) {

							// Remove all course user meta
							Sensei_Utils::sensei_remove_user_from_course( $course_id, $user_id );

						} // End For Loop
					} // End If Statement
				} // End If Statement
			} // End If Statement
		} // End For Loop
	} // End remove_active_course()


	/**
	 * Activate all purchased courses for user
	 * @since  1.4.8
	 * @param  integer $user_id User ID
	 * @return void
	 */
	public function activate_purchased_courses( $user_id = 0 ) {

		if( $user_id ) {

			if( Sensei_WC::is_woocommerce_active() ) {

				// Get all user's orders
				$order_args = array(
					'post_type' => 'shop_order',
					'post_status' =>  array( 'wc-processing', 'wc-completed' ),
					'posts_per_page' => -1,
					'meta_query' => array(
						array(
							'key' => '_customer_user',
							'value' => $user_id
						)
					),
				);

				$orders = get_posts( $order_args );

				$product_ids = array();
				$order_ids = array();

				foreach( $orders as $post_id ) {

					// Only process each order once
					$processed = get_post_meta( $post_id, 'sensei_products_processed', true );

					if( $processed && $processed == 'processed' ) {
						continue;
					}

					// Get course product IDs from order
					$order = new WC_Order( $post_id );

					$items = $order->get_items();
					foreach( $items as $item ) {
                                            if (isset($item['variation_id']) && $item['variation_id'] > 0) {
                                                $item_id = $item['variation_id'];
                                                $product_type = 'variation';
                                            } else {
                                                $item_id = $item['product_id'];
                                            }

                                            $product_ids[] = $item_id;
                                            }

					$order_ids[] = $post_id;
				}

				if( count( $product_ids ) > 0 ) {

					// Get all courses from user's orders
					$course_args = array(
						'post_type' => 'course',
						'posts_per_page' => -1,
						'meta_query' => array(
							array(
								'key' => '_course_woocommerce_product',
								'value' => $product_ids,
								'compare' => 'IN'
							)
						),
						'orderby' => 'menu_order date',
						'order' => 'ASC',
						'fields' => 'ids',
					);
					$course_ids = get_posts( $course_args );

					foreach( $course_ids as $course_id ) {

						$user_course_status = Sensei_Utils::user_course_status( intval($course_id), $user_id );

						// Ignore course if already completed
						if( Sensei_Utils::user_completed_course( $user_course_status ) ) {
							continue;
						}

						// Ignore course if already started
						if( $user_course_status ) {
							continue;
						}

						// Mark course as started by user
						Sensei_Utils::user_start_course( $user_id, $course_id );
					}
				}

				if( count( $order_ids ) > 0 ) {
					foreach( $order_ids as $order_id ) {
						// Mark order as processed
						update_post_meta( $order_id, 'sensei_products_processed', 'processed' );
					}
				}
			}
		}
	} // End activate_purchased_courses()

	/**
	 * Activate single course if already purchases
	 * @return void
	 */
	public function activate_purchased_single_course() {
		global $post, $current_user;

		if( Sensei_WC::is_woocommerce_active() ) {

			if( ! is_user_logged_in() ) return;
			if( ! isset( $post->ID ) ) return;

			$user_id = $current_user->ID;
			$course_id = $post->ID;
			$course_product_id = (int) get_post_meta( $course_id, '_course_woocommerce_product', true );
			if( ! $course_product_id ) {
				return;
			}

			$user_course_status = Sensei_Utils::user_course_status( intval($course_id), $user_id );

			// Ignore course if already completed
			if( Sensei_Utils::user_completed_course( $user_course_status ) ) {

				return;
			}

			// Ignore course if already started
			if( $user_course_status ) {
				return;
			}

			// Get all user's orders
			$order_args = array(
				'post_type' => 'shop_order',
				'posts_per_page' => -1,
				'post_status' => array( 'wc-processing', 'wc-completed' ),
				'meta_query' => array(
					array(
						'key' => '_customer_user',
						'value' => $user_id
					)
				),
				'fields' => 'ids',
			);
			$orders = get_posts( $order_args );

			foreach( $orders as $order_post_id ) {

				// Get course product IDs from order
				$order = new WC_Order( $order_post_id );

				$items = $order->get_items();
				foreach( $items as $item ) {
					$product_id = Sensei_WC_Utils::get_item_id_from_item( $item );
                    $product = wc_get_product( $product_id );

                    // handle product bundles
                    if( is_object( $product ) &&  $product->is_type('bundle') ) {

                        $bundled_product = new WC_Product_Bundle( Sensei_WC_Utils::get_product_id( $product ) );
                        $bundled_items = $bundled_product->get_bundled_items();

                        foreach ( $bundled_items as $bundled_item ) {
                            if( $bundled_item->product_id == $course_product_id ) {
                                Sensei_Utils::user_start_course( $user_id, $course_id );
                                return;
                            }
                        }

                    } else {

                    // handle regular products
                        if( $item['product_id'] == $course_product_id ) {
                            Sensei_Utils::user_start_course( $user_id, $course_id );
                            return;
                        }

                    }
				}
			}

		}
	} // End activate_purchased_single_course()

	/**
	 * Hide Sensei activity comments from frontend (requires WordPress 4.0+)
	 * @param  array  $args Default arguments
	 * @return array        Modified arguments
	 */
	public function hide_sensei_activity( $args = array() ) {

		if( is_singular( 'lesson' ) || is_singular( 'course' ) ) {
			$args['type'] = 'comment';
		}

		return $args;
	} // End hide_sensei_activity()

	/**
	 * Redirect failed login attempts to the front end login page
	 * in the case where the login fields are not left empty
	 *
	 * @return void redirect
	 */
	function sensei_login_fail_redirect( ) {

		//if not posted from the sensei login form let
		// WordPress or any other party handle the failed request
	    if( ! isset( $_REQUEST['form'] ) || 'sensei-login' != $_REQUEST['form']  ){

	    	return ;

	    }

    	// Get the reffering page, where did the post submission come from?
    	$referrer = add_query_arg('login', false, $_SERVER['HTTP_REFERER']);

   		 // if there's a valid referrer, and it's not the default log-in screen
	    if(!empty($referrer) && !strstr($referrer,'wp-login') && !strstr($referrer,'wp-admin')){
	        // let's append some information (login=failed) to the URL for the theme to use
	        wp_redirect( esc_url_raw( add_query_arg('login', 'failed',  $referrer) ) );
	    	exit;
    	}
	}// End sensei_login_fail_redirect_to_front_end_login

	/**
	 * Handle the login reques from all sensei intiated login forms.
	 *
	 * @return void redirect
	 */
	function sensei_handle_login_request( ) {

		// Check that it is a sensei login request and if it has a valid nonce
	    if(  isset( $_REQUEST['form'] ) && 'sensei-login' == $_REQUEST['form'] ) {

	    	// Validate the login request nonce
		    if( !wp_verify_nonce( $_REQUEST['_wpnonce'], 'sensei-login' ) ){
		    	return;
		    }

		    //get the page where the sensei log form is located
		    $referrer = $_REQUEST['_wp_http_referer'];

		    if ( ( isset( $_REQUEST['log'] ) && !empty( $_REQUEST['log'] ) )
		    	 && ( isset( $_REQUEST['pwd'] ) && !empty( $_REQUEST['pwd'] ) ) ){

		    	// when the user has entered a password or username do the sensei login
		    	$creds = array();

		    	// check if the requests login is an email address
		    	if( is_email(  trim( $_REQUEST['log'] ) )  ){
		    		$login = sanitize_email( $_REQUEST['log'] );

	    			// Occasionally a user's username IS an email,
	    			// but they have changed their actual email, so check for this case.
	    			$user = get_user_by( 'login', $login );

		    		if( ! $user ) {
		    			// Ok, fallback to checking by email.
		    			$user = get_user_by( 'email', $login );
		    		}

		    		// validate the user object
		    		if( !$user ){

		    			// the email doesnt exist
                        wp_redirect( esc_url_raw( add_query_arg('login', 'failed', $referrer) ) );
		        		exit;

		    		}

		    		//assigne the username to the creds array for further processing
		    		$creds['user_login'] =  $user->user_login ;

		    	}else{

		    		// process this as a default username login
		    		$creds['user_login'] = sanitize_text_field( $_REQUEST['log'] ) ;

		    	}

				// get setup the rest of the creds array
				$creds['user_password'] = $_REQUEST['pwd'];
				$creds['remember'] = isset( $_REQUEST['rememberme'] ) ? true : false ;

				//attempt logging in with the given details
			    $secure_cookie = is_ssl() ? true : false;
			    $user = wp_signon( $creds, $secure_cookie );

				if ( is_wp_error($user) ){ // on login failure
                    wp_redirect( esc_url_raw( add_query_arg('login', 'failed', $referrer) ) );
                    exit;
				}else{ // on login success

					/**
					* change the redirect url programatically
					*
					* @since 1.6.1
					*
					* @param string $referrer the page where the current url wheresensei login form was posted from
					*/

					$success_redirect_url = apply_filters('sesei_login_success_redirect_url', remove_query_arg( 'login', $referrer ) );

					wp_redirect( esc_url_raw( $success_redirect_url ) );
		        	exit;

				}	// end is_wp_error($user)

		    }else{ // if username or password is empty

                wp_redirect( esc_url_raw( add_query_arg('login', 'emptyfields', $referrer) ) );
		        exit;

		    } // end if username $_REQUEST['log']  and password $_REQUEST['pwd'] is empty

	    }elseif( ( isset( $_GET['login'] ) ) ) {
	    	// else if this request is a redircect from a previously faile login request
	    	$this->login_message_process();

			//exit the handle login request function
			return;
	    }

	    // if none of the above
	    return;

	} // End  sensei_login_fail_redirect_to_front_end_login

	/**
	 * handle sensei specific registration requests
	 *
	 * @return void redirect
	 *
	 */
	public function sensei_process_registration(){
		global 	 $current_user;
		// check the for the sensei specific registration requests
		if( !isset( $_POST['sensei_reg_username'] ) && ! isset( $_POST['sensei_reg_email'] ) && !isset( $_POST['sensei_reg_password'] )){
			// exit if this is not a sensei registration request
			return ;
		}
		// check for spam throw cheating huh
		if( isset( $_POST['email_2'] ) &&  '' !== $_POST['email_2']   ){
			$message = 'Error:  The spam field should be empty';
			Sensei()->notices->add_notice( $message, 'alert');
			return;
		}

		// retreive form variables
		$new_user_name		= sanitize_user( $_POST['sensei_reg_username'] );
		$new_user_email		= $_POST['sensei_reg_email'];
		$new_user_password	= $_POST['sensei_reg_password'];

		// Check the username
		$username_error_notice = '';
		if ( $new_user_name == '' ) {
			$username_error_notice =  __( '<strong>ERROR</strong>: Please enter a username.' );
		} elseif ( ! validate_username( $new_user_name ) ) {
			$username_error_notice =  __( '<strong>ERROR</strong>: This username is invalid because it uses illegal characters. Please enter a valid username.' );
		} elseif ( username_exists( $new_user_name ) ) {
			$username_error_notice =  __( '<strong>ERROR</strong>: This username is already registered. Please choose another one.' );
		}

		// exit on username error
		if( '' !== $username_error_notice ){
			Sensei()->notices->add_notice( $username_error_notice , 'alert');
			return;
		}

		// Check the e-mail address
		$email_error_notice = '';
		if ( $new_user_email == '' ) {
			$email_error_notice = __( '<strong>ERROR</strong>: Please enter an email address.' );
		} elseif ( ! is_email( $new_user_email ) ) {
			$email_error_notice = __( '<strong>ERROR</strong>: The email address isn&#8217;t correct.' );
		} elseif ( email_exists( $new_user_email ) ) {
			$email_error_notice = __( '<strong>ERROR</strong>: This email is already registered, please choose another one.' );
		}

		// exit on email address error
		if( '' !== $email_error_notice ){
			Sensei()->notices->add_notice( $email_error_notice , 'alert');
			return;
		}

		//check user password

		// exit on email address error
		if( empty( $new_user_password ) ){
			Sensei()->notices->add_notice(  __( '<strong>ERROR</strong>: The password field is empty.' )  , 'alert');
			return;
		}

		// register user
		$user_id = wp_create_user( $new_user_name, $new_user_password, $new_user_email );
		if ( ! $user_id || is_wp_error( $user_id ) ) {
			Sensei()->notices->add_notice( sprintf( __( '<strong>ERROR</strong>: Couldn&#8217;t register you&hellip; please contact the <a href="mailto:%s">webmaster</a> !' ), get_option( 'admin_email' ) ), 'alert');
		}

		// notify the user
		wp_new_user_notification( $user_id, $new_user_password );

		// set global current user aka log the user in
		$current_user = get_user_by( 'id', $user_id );
		wp_set_auth_cookie( $user_id, true );

		// Redirect
		global $wp;
		if ( wp_get_referer() ) {
			$redirect = esc_url( wp_get_referer() );
		} else {
			$redirect = esc_url( home_url( $wp->request ) );
		}

		wp_redirect( apply_filters( 'sensei_registration_redirect', $redirect ) );
		exit;

	} // end  sensei_process_registration)()

	/**
	 * login_message_process(). handle the login message displayed on faile login
	 *
	 * @return void redirect
	 * @since 1.7.0
	 */
	public function login_message_process(){

            // setup the message variables
			$message = '';

			//only output message if the url contains login=failed and login=emptyfields

			if( $_GET['login'] == 'failed' ){

				$message = __('Incorrect login details', 'woothemes-sensei' );

			}elseif( $_GET['login'] == 'emptyfields'  ){

				$message= __('Please enter your username and password', 'woothemes-sensei' );
			}

			Sensei()->notices->add_notice( $message, 'alert');

	}// end login_message_process


    /**
     * sensei_show_admin_bar(). Use WooCommerce filter
     * to show admin bar to Teachers as well.
     *
     * @return void redirect
     *
     */
    public function sensei_show_admin_bar () {

        if (current_user_can('edit_courses')) {

            add_filter( 'woocommerce_disable_admin_bar', '__return_false', 10, 1);

        }

    }

} // End Class

/**
 * Class WooThemes_Sensei_Frontend
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Frontend extends Sensei_Frontend{}
