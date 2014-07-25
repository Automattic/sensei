<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Frontend Class
 *
 * All functionality pertaining to the frontend of Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Frontend
 * @author WooThemes
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 * - init()
 * - enqueue_scripts()
 * - enqueue_styles()
 * - sensei_get_template_part()
 * - sensei_get_template()
 * - sensei_locate_template()
 * - sensei_output_content_wrapper()
 * - sensei_output_content_wrapper_end()
 * - sensei_output_content_pagination()
 * - sensei_output_comments()
 * - sensei_nav_menu_items()
 * - sensei_nav_menu_item_classes()
 * - sensei_search_results_classes()
 * - sensei_single_title()
 * - sensei_course_image()
 * - sensei_lesson_image()
 * - sensei_course_archive_header()
 * - sensei_course_archive_course_title()
 */
class WooThemes_Sensei_Frontend {
	public $token;
	public $course;
	public $lesson;
	public $messages;
	public $data;

	/**
	 * Constructor.
	 * @since  1.0.0
	 * @return  void
	 */
	public function __construct () {

		// Template output actions
		add_action( 'sensei_before_main_content', array( $this, 'sensei_output_content_wrapper' ), 10 );
		add_action( 'sensei_after_main_content', array( $this, 'sensei_output_content_wrapper_end' ), 10 );
		add_action( 'sensei_pagination', array( $this, 'sensei_output_content_pagination' ), 10 );
		add_action( 'sensei_comments', array( $this, 'sensei_output_comments' ), 10 );
		add_action( 'sensei_course_single_meta', 'course_single_meta', 10 );
		add_action( 'sensei_course_single_lessons', 'course_single_lessons', 10 );
		add_action( 'sensei_lesson_single_meta', 'lesson_single_meta', 10 );
		add_action( 'sensei_quiz_questions', 'quiz_questions', 10 );
		add_action( 'sensei_course_single_title', array( $this, 'sensei_single_title' ), 10 );
		add_action( 'sensei_lesson_single_title', array( $this, 'sensei_single_title' ), 10 );
		add_action( 'sensei_quiz_single_title', array( $this, 'sensei_single_title' ), 10 );
		add_action( 'sensei_message_single_title', array( $this, 'sensei_single_title' ), 10 );
		add_action( 'sensei_course_image', array( $this, 'sensei_course_image' ), 10, 4 );
		add_action( 'sensei_lesson_image', array( $this, 'sensei_lesson_image' ), 10, 5 );
		add_action( 'sensei_course_archive_header', array( $this, 'sensei_course_archive_header' ), 10, 3 );
		add_action( 'sensei_lesson_archive_header', array( $this, 'sensei_lesson_archive_header' ), 10, 3 );
		add_action( 'sensei_message_archive_header', array( $this, 'sensei_message_archive_header' ), 10, 3 );
		add_action( 'sensei_course_archive_course_title', array( $this, 'sensei_course_archive_course_title' ), 10, 1 );
		add_action( 'sensei_lesson_archive_lesson_title', array( $this, 'sensei_lesson_archive_lesson_title' ), 10 );
		// 1.2.1
		add_action( 'sensei_lesson_back_link', array( $this, 'sensei_lesson_back_to_course_link' ), 10, 1 );
		add_action( 'sensei_quiz_back_link', array( $this, 'sensei_quiz_back_to_lesson_link' ), 10, 1 );
		add_action( 'sensei_lesson_course_signup', array( $this, 'sensei_lesson_course_signup_link' ), 10, 1 );
		add_action( 'sensei_complete_lesson', array( $this, 'sensei_complete_lesson' ) );
		add_action( 'sensei_complete_course', array( $this, 'sensei_complete_course' ) );
		add_action( 'sensei_complete_quiz', array( $this, 'sensei_complete_quiz' ) );
		add_action( 'sensei_frontend_messages', array( $this, 'sensei_frontend_messages' ) );
		add_action( 'sensei_lesson_video', array( $this, 'sensei_lesson_video' ), 10, 1 );
		add_action( 'sensei_complete_lesson_button', array( $this, 'sensei_complete_lesson_button' ) );
		add_action( 'sensei_lesson_quiz_meta', array( $this, 'sensei_lesson_quiz_meta' ), 10, 2 );
		add_action( 'sensei_course_archive_meta', array( $this, 'sensei_course_archive_meta' ) );
		add_action( 'sensei_single_main_content', array( $this, 'sensei_single_main_content' ), 10 );
		add_action( 'sensei_course_archive_main_content', array( $this, 'sensei_course_archive_main_content' ), 10 );
		add_action( 'sensei_lesson_archive_main_content', array( $this, 'sensei_lesson_archive_main_content' ), 10 );
		add_action( 'sensei_message_archive_main_content', array( $this, 'sensei_message_archive_main_content' ), 10 );
		add_action( 'sensei_course_category_main_content', array( $this, 'sensei_course_category_main_content' ), 10 );
		add_action( 'sensei_no_permissions_main_content', array( $this, 'sensei_no_permissions_main_content' ), 10 );
		add_action( 'sensei_login_form', array( $this, 'sensei_login_form' ), 10 );
		add_action( 'sensei_quiz_action_buttons', array( $this, 'sensei_quiz_action_buttons' ), 10 );
		add_action( 'sensei_lesson_meta', array( $this, 'sensei_lesson_meta' ), 10 );
		add_action( 'sensei_course_meta', array( $this, 'sensei_course_meta' ), 10 );
		add_action( 'sensei_course_meta_video', array( $this, 'sensei_course_meta_video' ), 10 );
		add_action( 'sensei_woocommerce_in_cart_message', array( $this, 'sensei_woocommerce_in_cart_message' ), 10 );
		add_action( 'sensei_course_start', array( $this, 'sensei_course_start' ), 10 );
		add_filter( 'get_comments_number', array( $this, 'sensei_lesson_comment_count' ), 1 );
		add_filter( 'the_title', array( $this, 'sensei_lesson_preview_title' ), 10, 2 ); 
		// 1.3.0
		add_action( 'sensei_quiz_question_type', 'quiz_question_type', 10 , 1);
		// Load post type classes
		$this->course = new WooThemes_Sensei_Course();
		$this->lesson = new WooThemes_Sensei_Lesson();
		// Scripts and Styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_head', array( $this, 'enqueue_scripts' ) );
		// Custom Menu Item filters
		add_filter( 'wp_setup_nav_menu_item', array( $this, 'sensei_setup_nav_menu_item' ) );
		add_filter( 'wp_nav_menu_objects', array( $this, 'sensei_wp_nav_menu_objects' ) );
		// Search Results filters
		add_filter( 'post_class', array( $this, 'sensei_search_results_classes' ), 10 );
		// Comments Feed Actions
		add_filter( 'comment_feed_where', array( $this, 'comments_rss_item_filter' ), 10, 1 );
		// Checks if Course is complete when completing a Lesson or Quiz
		add_action( 'sensei_user_lesson_end', array( $this, 'sensei_completed_course' ), 10, 2 );
		// Only show course & lesson excerpts in search results
		add_filter( 'the_content', array( $this, 'sensei_search_results_excerpt' ) );

		// Remove course from active courses if an order is cancelled or refunded
		add_action( 'woocommerce_order_status_processing_to_cancelled', array( $this, 'remove_active_course' ), 10, 1 );
        add_action( 'woocommerce_order_status_completed_to_cancelled', array( $this, 'remove_active_course' ), 10, 1 );
        add_action( 'woocommerce_order_status_on-hold_to_cancelled', array( $this, 'remove_active_course' ), 10, 1 );
        add_action( 'woocommerce_order_status_processing_to_refunded', array( $this, 'remove_active_course' ), 10, 1 );
        add_action( 'woocommerce_order_status_completed_to_refunded', array( $this, 'remove_active_course' ), 10, 1 );
        add_action( 'woocommerce_order_status_on-hold_to_refunded', array( $this, 'remove_active_course' ), 10, 1 );

        // Add course link to order page
        add_action( 'woocommerce_thankyou', array( $this, 'course_link_from_order' ), 10, 1 );
        add_action( 'woocommerce_view_order', array( $this, 'course_link_from_order' ), 10, 1 );

        // Make sure correct courses are marked as active for users
        add_action( 'sensei_before_my_courses', array( $this, 'activate_purchased_courses' ), 10, 1 );
        add_action( 'sensei_course_start', array( $this, 'activate_purchased_single_course' ), 10 );

        // Lesson tags
        add_action( 'sensei_lesson_meta_extra', array( $this, 'lesson_tags_display' ), 10, 1 );
        add_action( 'pre_get_posts', array( $this, 'lesson_tag_archive_filter' ), 10, 1 );
        add_filter( 'sensei_lessons_archive_text', array( $this, 'lesson_tag_archive_header' ) );
        add_action( 'sensei_lesson_archive_header', array( $this, 'lesson_tag_archive_description' ), 11 );

        // Hide Sensie activity comments from lesson and course pages
        add_filter( 'wp_list_comments_args', array( $this, 'hide_sensei_activity' ) );

	} // End __construct()

	/**
	 * Initialise the code.
	 * @since  1.0.0
	 * @return void
	 */
	public function init () {

	} // End init()

	/**
	 * Enqueue frontend JavaScripts.
	 * @since  1.0.0
	 * @return void
	 */
	public function enqueue_scripts () {
		global $woothemes_sensei;
		$disable_js = false;
		if ( isset( $woothemes_sensei->settings->settings[ 'js_disable' ] ) ) {
			$disable_js = $woothemes_sensei->settings->settings[ 'js_disable' ];
		} // End If Statement
		if ( ! $disable_js ) {

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			// My Courses tabs script
			wp_register_script( $this->token . '-user-dashboard', esc_url( $woothemes_sensei->plugin_url . 'assets/js/user-dashboard' . $suffix . '.js' ), array( 'jquery-ui-tabs' ), '1.5.2', true );
			wp_enqueue_script( $this->token . '-user-dashboard' );

			// Load the general script
			wp_enqueue_script( 'sensei-general-frontend', $woothemes_sensei->plugin_url . 'assets/js/general-frontend' . $suffix . '.js', array( 'jquery' ), '1.6.0' );

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
		global $woothemes_sensei;

		$disable_styles = false;
		if ( isset( $woothemes_sensei->settings->settings[ 'styles_disable' ] ) ) {
			$disable_styles = $woothemes_sensei->settings->settings[ 'styles_disable' ];
		} // End If Statement

		// Add filter for theme overrides
		$disable_styles = apply_filters( 'sensei_disable_styles', $disable_styles );

		if ( ! $disable_styles ) {
			wp_register_style( $woothemes_sensei->token . '-frontend', $woothemes_sensei->plugin_url . 'assets/css/frontend.css', '', '1.6.0', 'screen' );
			wp_enqueue_style( $woothemes_sensei->token . '-frontend' );

			// Allow additional stylesheets to be loaded
			do_action( 'sensei_additional_styles' );

		} // End If Statement

	} // End enqueue_styles()


	/**
	 * sensei_get_template_part function.
	 *
	 * @access public
	 * @param mixed $slug
	 * @param string $name (default: '')
	 * @return void
	 */
	function sensei_get_template_part( $slug, $name = '' ) {
		global $woothemes_sensei;
		$template = '';

		// Look in yourtheme/slug-name.php and yourtheme/sensei/slug-name.php
		if ( $name )
			$template = locate_template( array ( "{$slug}-{$name}.php", "{$woothemes_sensei->template_url}{$slug}-{$name}.php" ) );

		// Get default slug-name.php
		if ( ! $template && $name && file_exists( $woothemes_sensei->plugin_path() . "/templates/{$slug}-{$name}.php" ) )
			$template = $woothemes_sensei->plugin_path() . "/templates/{$slug}-{$name}.php";

		// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/sensei/slug.php
		if ( !$template )
			$template = locate_template( array ( "{$slug}.php", "{$woothemes_sensei->template_url}{$slug}.php" ) );

		if ( $template )
			load_template( $template, false );
	} // End sensei_get_template_part()


	/**
	 * sensei_get_template function.
	 *
	 * @access public
	 * @param mixed $template_name
	 * @param array $args (default: array())
	 * @param string $template_path (default: '')
	 * @param string $default_path (default: '')
	 * @return void
	 */
	function sensei_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
		global $woothemes_sensei;

		if ( $args && is_array($args) )
			extract( $args );

		$located = $this->sensei_locate_template( $template_name, $template_path, $default_path );

		do_action( 'sensei_before_template_part', $template_name, $template_path, $located );

		include( $located );

		do_action( 'sensei_after_template_part', $template_name, $template_path, $located );
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
		global $woothemes_sensei;

		if ( ! $template_path ) $template_path = $woothemes_sensei->template_url;
		if ( ! $default_path ) $default_path = $woothemes_sensei->plugin_path() . '/templates/';

		// Look within passed path within the theme - this is priority
		$template = locate_template(
			array(
				$template_path . $template_name,
				$template_name
			)
		);

		// Get default template
		if ( ! $template )
			$template = $default_path . $template_name;

		// Return what we found
		return apply_filters( 'sensei_locate_template', $template, $template_name, $template_path );
	} // End sensei_locate_template()


	/**
	 * sensei_output_content_wrapper function.
	 *
	 * @access public
	 * @return void
	 */
	function sensei_output_content_wrapper() {
		$this->sensei_get_template( 'wrappers/wrapper-start.php' );
	} // End sensei_output_content_wrapper()


	/**
	 * sensei_output_content_wrapper_end function.
	 *
	 * @access public
	 * @return void
	 */
	function sensei_output_content_wrapper_end() {
		$this->sensei_get_template( 'wrappers/wrapper-end.php' );
	} // End sensei_output_content_wrapper_end()


	/**
	 * sensei_output_content_pagination function.
	 *
	 * @access public
	 * @return void
	 */
	function sensei_output_content_pagination() {
		global $wp_query, $woothemes_sensei;
		// Handle Pagination on course archive pages
		$paged = $wp_query->get( 'paged' );
		$course_page_id = intval( $woothemes_sensei->settings->settings[ 'course_page' ] );
		if ( ( is_post_type_archive( 'course' ) || ( is_page( $course_page_id ) ) ) && ( isset( $paged ) && 0 == $paged ) ) {
			// Do NOT show the pagination
		} elseif( is_singular('course') ) {
			$this->sensei_get_template( 'wrappers/pagination-posts.php' );
		} elseif( is_singular('lesson') ) {
			$this->sensei_get_template( 'wrappers/pagination-lesson.php' );
		} elseif( is_singular('quiz') ) {
			$this->sensei_get_template( 'wrappers/pagination-quiz.php' );
		} else {
			$this->sensei_get_template( 'wrappers/pagination.php' );
		} // End If Statement
	} // End sensei_output_content_pagination()

	/**
	 * outputs comments for the specified pages
	 * @access  public
	 * @return void
	 */
	function sensei_output_comments() {
		global $woothemes_sensei, $view_lesson, $user_taking_course;
		$allow_comments = $woothemes_sensei->settings->settings[ 'lesson_comments' ];
		if ( is_user_logged_in() && $allow_comments && ( isset( $view_lesson ) && $view_lesson ) && ( isset( $user_taking_course ) && $user_taking_course ) ) {
			comments_template();
		} elseif( is_singular( 'sensei_message' ) ) {
			comments_template();
		} // End If Statement
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
		global $pagenow, $wp_rewrite, $woothemes_sensei;
		
		if( 'nav-menus.php' != $pagenow && !defined('DOING_AJAX') && isset( $item->url ) && 'custom' == $item->type ) {

			// Set up Sensei menu links
			$course_page_id = intval( $woothemes_sensei->settings->settings[ 'course_page' ] );
			$my_account_page_id = intval( $woothemes_sensei->settings->settings[ 'my_course_page' ] );

			$course_page_url = ( 0 < $course_page_id ? get_permalink( $course_page_id ) : get_post_type_archive_link( 'course' ) );
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
					break;

				case '#senseilearnerprofile':
					$item->url = esc_url( $woothemes_sensei->learner_profiles->get_permalink() );
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

			$logout_url = wp_logout_url( home_url() );
			// Login link links to the My Courses page, to avoid the WP dashboard.
			$login_url = $my_courses_url;

			// Set the correct title and URL for the login/logout link
			if ( '#senseiloginlogout' == $item->url ) {
				$item->url = ( is_user_logged_in() ? $logout_url : $login_url );
				$item->title = $this->sensei_login_logout_title( $item->title );
			}
		}
		return $item;
	} // End sensei_setup_nav_menu_item()

	/**
	 * sensei_wp_nav_menu_objects function.
	 *
	 * Remove Sensei custom menu items depending on settings and logged in status.
	 *
	 * @access public
	 * @param object $item
	 * @return object $item
	 */
	public function sensei_wp_nav_menu_objects( $sorted_menu_items ) {
		global $woothemes_sensei;

		foreach( $sorted_menu_items as $k=>$item ) {
			// Remove the My Messages link for logged out users or if Private Messages are disabled.	
			if( get_post_type_archive_link( 'sensei_message' ) == $item->url ) {
				if ( !is_user_logged_in() || ( isset( $woothemes_sensei->settings->settings['messages_disable'] ) && $woothemes_sensei->settings->settings['messages_disable'] ) ) {
					unset( $sorted_menu_items[$k] );
				}
			}
			// Remove the My Profile link for logged out users.
			if( $woothemes_sensei->learner_profiles->get_permalink() == $item->url ) {
				if ( !is_user_logged_in() || ! ( isset( $woothemes_sensei->settings->settings[ 'learner_profile_enable' ] ) && $woothemes_sensei->settings->settings[ 'learner_profile_enable' ] ) ) {
					unset( $sorted_menu_items[$k] );
				}
			}
		}
		return $sorted_menu_items;
	} // End sensei_wp_nav_menu_objects

	public function sensei_login_logout_title( $title ) {
		// Get the title of the login/logout link, from the pipe-separated values given e.g. "Sign In|Sign Out"
		$titles = explode( '|', $title );
		if ( is_user_logged_in() ) {
			return esc_html( isset( $titles[1] ) ? $titles[1] : $title );
		} else {
			return esc_html( isset( $titles[0] ) ? $titles[0] : $title );
		}
	} // End sensei_login_logout_title()

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
	 */
	function sensei_single_title() {
		global $post;

		if( is_singular( 'sensei_message' ) ) {
			$content_post_id = get_post_meta( $post->ID, '_post', true );
			if( $content_post_id ) {
				$title = sprintf( __( 'Re: %1$s', 'woothemes-sensei' ), '<a href="' . get_permalink( $content_post_id ) . '">' . get_the_title( $content_post_id ) . '</a>' );
			} else {
				$title = get_the_title( $post->ID );
			}
		} else {
			$title = get_the_title();
		}
		?><header><h1><?php echo $title; ?></h1></header><?php
	} // End sensei_single_title()

	/**
	 * sensei_course_image output for course image
	 * @since  1.2.0
	 * @return void
	 */
	function sensei_course_image( $course_id, $width = '100', $height = '100', $return = false ) {
    	if ( $return ) {
			return $this->course->course_image( $course_id, $width, $height );
		} else {
			echo $this->course->course_image( $course_id, $width, $height );
		} // End If Statement
	} // End sensei_course_image()

	/**
	 * sensei_lesson_image output for lesson image
	 * @since  1.2.0
	 * @return void
	 */
	function sensei_lesson_image( $lesson_id, $width = '100', $height = '100', $return = false, $widget = false ) {
		if ( $return ) {
			return $this->lesson->lesson_image( $lesson_id, $width, $height, $widget );
		} else {
			echo $this->lesson->lesson_image( $lesson_id, $width, $height, $widget );
		} // End If Statement
	} // End sensei_lesson_image()

	/**
	 * sensei_course_archive_header function.
	 *
	 * @access public
	 * @since  1.2.0
	 * @param string $query_type (default: '')
	 * @return void
	 */
	function sensei_course_archive_header( $query_type = '', $before_html = '<header class="archive-header"><h1>', $after_html = '</h1></header>' ) {

		$html = '';

		if ( is_tax( 'course-category' ) ) {
			global $wp_query;
			$taxonomy_obj = $wp_query->get_queried_object();
			$term_id = intval( $taxonomy_obj->term_id );
			$taxonomy_short_name = $taxonomy_obj->taxonomy;
			$taxonomy_raw_obj = get_taxonomy( $taxonomy_short_name );
			$title = sprintf( __( '%1$s Archives: %2$s', 'woothemes-sensei' ), $taxonomy_raw_obj->labels->name, $taxonomy_obj->name );
			echo apply_filters( 'course_category_archive_title', $before_html . $title . $after_html );
			return;
		} // End If Statement

		switch ( $query_type ) {
			case 'newcourses':
				$html .= $before_html . apply_filters( 'sensei_new_courses_text', __( 'New Courses', 'woothemes-sensei' ) ) . $after_html;
				break;
			case 'featuredcourses':
				$html .= $before_html . apply_filters( 'sensei_featured_courses_text', __( 'Featured Courses', 'woothemes-sensei' ) ) . $after_html;
				break;
			case 'freecourses':
				$html .= $before_html . apply_filters( 'sensei_free_courses_text', __( 'Free Courses', 'woothemes-sensei' ) ) . $after_html;
				break;
			case 'paidcourses':
				$html .= $before_html . apply_filters( 'sensei_paid_courses_text', __( 'Paid Courses', 'woothemes-sensei' ) ) . $after_html;
				break;
			default:
				$html .= $before_html . apply_filters( 'sensei_courses_text', __( 'Courses', 'woothemes-sensei' ) ) . $after_html;
				break;
		} // End Switch Statement

		echo apply_filters( 'course_archive_title', $html );
	} // sensei_course_archive_header()

	/**
	 * sensei_lesson_archive_header function.
	 *
	 * @access public
	 * @since  1.2.1
	 * @param string $before_html
	 * @param  string $after_html
	 * @return void
	 */
	public function sensei_lesson_archive_header( $query_type = '', $before_html = '<header class="archive-header"><h1>', $after_html = '</h1></header>' ) {
		$html = '';
		error_log( $before_html, 0 );
		error_log( $after_html, 0 );
		$html .= $before_html . apply_filters( 'sensei_lessons_archive_text', __( 'Lessons Archive', 'woothemes-sensei' ) ) . $after_html;
		echo apply_filters( 'sensei_lesson_archive_title', $html );
	} // sensei_course_archive_header()

	public function sensei_message_archive_header( $query_type = '', $before_html = '<header class="archive-header"><h1>', $after_html = '</h1></header>' ) {
		$html = '';
		error_log( $before_html, 0 );
		error_log( $after_html, 0 );
		$html .= $before_html . apply_filters( 'sensei_my_messages_text', __( 'My Messages', 'woothemes-sensei' ) ) . $after_html;
		echo apply_filters( 'sensei_message_archive_title', $html );
	} // sensei_message_archive_header()

	/**
	 * sensei_course_archive_course_title output for course archive page individual course title
	 * @since  1.2.0
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
	 * sensei_lesson_back_to_course_link back link to the lessons course
	 * @since  1.2.1
	 * @param  integer $course_id id of the lessons course
	 * @return void
	 */
	public function sensei_lesson_back_to_course_link( $course_id = 0 ) {
		if ( 0 < intval( $course_id ) ) {
		?><section class="lesson-course">
    		<?php _e( 'Back to ', 'woothemes-sensei' ); ?><a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>" title="<?php echo esc_attr( apply_filters( 'sensei_back_to_course_text', __( 'Back to the course', 'woothemes-sensei' ) ) ); ?>"><?php echo get_the_title( $course_id ); ?></a>
    	</section><?php
    	} // End If Statement
	} // End sensei_lesson_back_to_course_link()

	public function sensei_quiz_back_to_lesson_link( $quiz_id = 0 ) {
		if ( 0 < intval( $quiz_id ) ) {
		?><section class="lesson-course">
    		<?php _e( 'Back to ', 'woothemes-sensei' ); ?><a href="<?php echo esc_url( get_permalink( $quiz_id ) ); ?>" title="<?php echo esc_attr( apply_filters( 'sensei_back_to_lesson_text', __( 'Back to the lesson', 'woothemes-sensei' ) ) ); ?>"><?php echo get_the_title( $quiz_id ); ?></a>
		</section><?php
		} // End If Statement
	} // End sensei_quiz_back_to_lesson_link()

	public function sensei_lesson_course_signup_link( $course_id = 0 ) {
		if ( 0 < intval( $course_id ) ) {
		?><section class="lesson-meta"><?php
			$course_link = '<a href="' . esc_url( get_permalink( $course_id ) ) . '">' . __( 'course', 'woothemes-sensei' ) . '</a>';
			$wc_post_id = get_post_meta( $course_id, '_course_woocommerce_product', true );
			if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() && ( 0 < $wc_post_id ) ) {
				global $current_user;
				if( is_user_logged_in() ) {
					wp_get_current_user();
					$course_purchased = WooThemes_Sensei_Utils::sensei_customer_bought_product( $current_user->user_email, $current_user->ID, $wc_post_id );
					if( $course_purchased ) {
						$prereq_course_id = get_post_meta( $course_id, '_course_prerequisite',true );
						?>
						<div class="sensei-message info"><?php echo apply_filters( 'sensei_complete_prerequisite_course_text', sprintf( __( 'Please complete %1$s before starting the lesson.', 'woothemes-sensei' ), '<a href="' . esc_url( get_permalink( $prereq_course_id ) ) . '" title="' . esc_attr( get_the_title( $prereq_course_id ) ) . '">' . apply_filters( 'sensei_previous_course_text', __( 'the previous course', 'woothemes-sensei' ) ) . '</a>' ) ); ?></div>
					<?php } else { ?>
						<div class="sensei-message info"><?php echo apply_filters( 'sensei_please_purchase_course_text', sprintf( __( 'Please purchase the %1$s before starting the lesson.', 'woothemes-sensei' ), '<a href="' . esc_url( get_permalink( $course_id ) ) . '" title="' . esc_attr( apply_filters( 'sensei_sign_up_text', __( 'Sign Up', 'woothemes-sensei' ) ) ) . '">' . __( 'course', 'woothemes-sensei' ) . '</a>' ) ); ?></div>
					<?php }
				} else { ?>
					<div class="sensei-message info"><?php echo apply_filters( 'sensei_please_purchase_course_text', sprintf( __( 'Please purchase the %1$s before starting the lesson.', 'woothemes-sensei' ), '<a href="' . esc_url( get_permalink( $course_id ) ) . '" title="' . esc_attr( apply_filters( 'sensei_sign_up_text', __( 'Sign Up', 'woothemes-sensei' ) ) ) . '">' . __( 'course', 'woothemes-sensei' ) . '</a>' ) ); ?></div>
				<?php } ?>
			<?php } else { ?>
				<div class="sensei-message info"><?php echo apply_filters( 'sensei_please_sign_up_text', sprintf( __( 'Please sign up for the %1$s before starting the lesson.', 'woothemes-sensei' ), '<a href="' . esc_url( get_permalink( $course_id ) ) . '" title="' . esc_attr( apply_filters( 'sensei_sign_up_text', __( 'Sign Up', 'woothemes-sensei' ) ) ) . '">' . __( 'course', 'woothemes-sensei' ) . '</a>' ) ); ?></div>
			<?php } // End If Statement ?>
    	</section><?php
    	} // End If Statement
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

	public function lesson_tag_archive_filter( $query ) {
    	if( is_tax( 'lesson-tag' ) && $query->is_main_query() ) {
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
		global $post, $woothemes_sensei, $current_user;
		// Handle Quiz Completion
		if ( isset( $_POST['quiz_complete'] ) && wp_verify_nonce( $_POST[ 'woothemes_sensei_complete_lesson_noonce' ], 'woothemes_sensei_complete_lesson_noonce' ) ) {
			// Lesson Quiz Meta
			$lesson_quizzes = $woothemes_sensei->frontend->lesson->lesson_quizzes( $post->ID );

		    $lesson_quiz_id = 0;

		    if ( 0 < count($lesson_quizzes) )  {
		        foreach ($lesson_quizzes as $quiz_item){
		            $lesson_quiz_id = $quiz_item->ID;
		        } // End For Loop
		    } // End If Statement

		    $sanitized_submit = esc_html( $_POST['quiz_complete'] );

		    $answers_array = array();

		    switch ($sanitized_submit) {
		        case apply_filters( 'sensei_complete_lesson_text', __( 'Complete Lesson', 'woothemes-sensei' ) ):

		            // Manual Grade
		            $grade = 100;

		            // Force Start the Lesson
                    $args = array(
                                        'post_id' => $post->ID,
                                        'username' => $current_user->user_login,
                                        'user_email' => $current_user->user_email,
                                        'user_url' => $current_user->user_url,
                                        'data' => __( 'Lesson started by the user', 'woothemes-sensei' ),
                                        'type' => 'sensei_lesson_start', /* FIELD SIZE 20 */
                                        'parent' => 0,
                                        'user_id' => $current_user->ID
                                    );
                    $activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );

                    do_action( 'sensei_user_lesson_start', $current_user->ID, $post->ID );

		            // Save Quiz Grade
	                $args = array(
	                                    'post_id' => $lesson_quiz_id,
	                                    'username' => $current_user->user_login,
	                                    'user_email' => $current_user->user_email,
	                                    'user_url' => $current_user->user_url,
	                                    'data' => $grade,
	                                    'type' => 'sensei_quiz_grade', /* FIELD SIZE 20 */
	                                    'parent' => 0,
	                                    'user_id' => $current_user->ID,
	                                    'action' => 'update'
	                                );
	                $activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );

	                $quiz_passmark = absint( get_post_meta( $lesson_quiz_id, '_quiz_passmark', true ) );
	                do_action( 'sensei_user_quiz_grade', $current_user->ID, $lesson_quiz_id, $grade, $quiz_passmark );

	                // Get quiz pass setting
	                $pass_required = get_post_meta( $lesson_quiz_id, '_pass_required', true );

	                // Get Lesson Grading Setting
	                if ( $activity_logged && $pass_required ) {
	                    $lesson_prerequisite = abs( round( doubleval( get_post_meta( $lesson_quiz_id, '_quiz_passmark', true ) ), 2 ) );
	                    if ( $lesson_prerequisite <= $grade ) {
	                        // Student has reached the pass mark and lesson is complete
	                        $args = array(
	                                            'post_id' => $post->ID,
	                                            'username' => $current_user->user_login,
	                                            'user_email' => $current_user->user_email,
	                                            'user_url' => $current_user->user_url,
	                                            'data' => __( 'Lesson completed and passed by the user', 'woothemes-sensei' ),
	                                            'type' => 'sensei_lesson_end', /* FIELD SIZE 20 */
	                                            'parent' => 0,
	                                            'user_id' => $current_user->ID
	                                        );
	                        $activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );

	                        do_action( 'sensei_user_lesson_end', $current_user->ID, $post->ID );

	                    } // End If Statement
	                } elseif ($activity_logged) {
	                    // Mark lesson as complete
	                    $args = array(
	                                        'post_id' => $post->ID,
	                                        'username' => $current_user->user_login,
	                                        'user_email' => $current_user->user_email,
	                                        'user_url' => $current_user->user_url,
	                                        'data' => __( 'Lesson completed by the user', 'woothemes-sensei' ),
	                                        'type' => 'sensei_lesson_end', /* FIELD SIZE 20 */
	                                        'parent' => 0,
	                                        'user_id' => $current_user->ID
	                                    );
	                    $activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );

	                    do_action( 'sensei_user_lesson_end', $current_user->ID, $post->ID );

	                } // End If Statement
		            break;
		        case apply_filters( 'sensei_reset_lesson_text', __( 'Reset Lesson', 'woothemes-sensei' ) ):
		            // Remove existing user quiz meta
		            // Check for quiz grade
		            $delete_grades = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $lesson_quiz_id, 'user_id' => $current_user->ID, 'type' => 'sensei_quiz_grade' ) );
		            // Check for quiz answers
		            $delete_answers = WooThemes_Sensei_Utils::sensei_delete_quiz_answers( $lesson_quiz_id, $current_user->ID );
		            // Check for lesson complete
		            $delete_lesson_completion = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $post->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_end' ) );
		            // Check for course complete
		            $course_id = get_post_meta( $post->ID, '_lesson_course' ,true );
		            $delete_course_completion = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $course_id, 'user_id' => $current_user->ID, 'type' => 'sensei_course_end' ) );
		            // Run any action on course reset
		            do_action( 'sensei_user_course_reset', $current_user->ID, $course_id );
		            $this->messages = '<div class="sensei-message note">' . apply_filters( 'sensei_lesson_reset_text', __( 'Lesson Reset Successfully.', 'woothemes-sensei' ) ) . '</div>';
		            break;
		        default:
		            // Nothing
		            break;

		    } // End Switch Statement

		} // End If Statement

	} // End sensei_complete_lesson()

	public function sensei_complete_course() {
		global $post, $current_user, $wp_query;
		if ( isset( $_POST['course_complete'] ) && wp_verify_nonce( $_POST[ 'woothemes_sensei_complete_course_noonce' ], 'woothemes_sensei_complete_course_noonce' ) ) {
		    $sanitized_submit = esc_html( $_POST['course_complete'] );
		    $sanitized_course_id = absint( esc_html( $_POST['course_complete_id'] ) );
			// Handle submit data
		    switch ($sanitized_submit) {
		    	case apply_filters( 'sensei_mark_as_complete_text', __( 'Mark as Complete', 'woothemes-sensei' ) ):

		    		$dataset_changes = false;
		    		// Save Course Data Answers
		    		$args = array(
									    'post_id' => $sanitized_course_id,
									    'username' => $current_user->user_login,
									    'user_email' => $current_user->user_email,
									    'user_url' => $current_user->user_url,
									    'data' => __( 'Course completed by the user', 'woothemes-sensei' ),
									    'type' => 'sensei_course_end', /* FIELD SIZE 20 */
									    'parent' => 0,
									    'user_id' => $current_user->ID,
									    'action' => 'update'
									);
					$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );
					$dataset_changes = true;

					if ( $activity_logged ) {
						// Get all course lessons
		    			$course_lessons = $this->course->course_lessons( $sanitized_course_id );
		    			// Mark all quiz user meta lessons as complete
		    			foreach ($course_lessons as $lesson_item){
		    				// Mark lesson as started
							$args = array(
							    		    'post_id' => $lesson_item->ID,
							    		    'username' => $current_user->user_login,
							    		    'user_email' => $current_user->user_email,
							    		    'user_url' => $current_user->user_url,
							    		    'data' => __( 'Lesson started by the user', 'woothemes-sensei' ),
							    		    'type' => 'sensei_lesson_start', /* FIELD SIZE 20 */
							    		    'parent' => 0,
							    		    'user_id' => $current_user->ID
							    		);
							$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );

							do_action( 'sensei_user_lesson_start', $current_user->ID, $lesson_item->ID );

		    				// Mark lesson as complete
							$args = array(
							    		    'post_id' => $lesson_item->ID,
							    		    'username' => $current_user->user_login,
							    		    'user_email' => $current_user->user_email,
							    		    'user_url' => $current_user->user_url,
							    		    'data' => __( 'Lesson completed by the user', 'woothemes-sensei' ),
							    		    'type' => 'sensei_lesson_end', /* FIELD SIZE 20 */
							    		    'parent' => 0,
							    		    'user_id' => $current_user->ID
							    		);
							$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );

							do_action( 'sensei_user_lesson_end', $current_user->ID, $lesson_item->ID );

							if ( $activity_logged ) {
								// Lesson Quiz Meta
		        				$lesson_quizzes = $this->lesson->lesson_quizzes( $lesson_item->ID );
		        				if ( 0 < count($lesson_quizzes) )  {
		        					foreach ($lesson_quizzes as $quiz_item){
										// Mark quiz grade as passed
										$args = array(
										    		    'post_id' => $quiz_item->ID,
										    		    'username' => $current_user->user_login,
										    		    'user_email' => $current_user->user_email,
										    		    'user_url' => $current_user->user_url,
										    		    'data' => '100',
										    		    'type' => 'sensei_quiz_grade', /* FIELD SIZE 20 */
										    		    'parent' => 0,
										    		    'user_id' => $current_user->ID
										    		);
										$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );

										$quiz_passmark = absint( get_post_meta( $quiz_item->ID, '_quiz_passmark', true ) );
										do_action( 'sensei_user_quiz_grade', $current_user->ID, $quiz_item->ID, 100, $quiz_passmark );

									} // End For Loop
								} // End If Statement
							} // End If Statement
						} // End For Loop

						do_action( 'sensei_user_course_end', $current_user->ID, $sanitized_course_id );
		    		} // End If Statement

					// Success message
		    		if ( $dataset_changes ) {
		    			$this->messages = '<header class="archive-header"><div class="sensei-message tick">' . sprintf( __( '%1$s marked as complete.', 'woothemes-sensei' ), get_the_title( $sanitized_course_id ) ) . '</div></header>';
		    		} // End If Statement

		    		break;
		    	case apply_filters( 'sensei_delete_course_text', __( 'Delete Course', 'woothemes-sensei' ) ):

		    		$dataset_changes = false;
		    		// Check for quiz grade
		    		$dataset_changes = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $post->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_quiz_grade' ) );
		    		// Check and Remove course from courses user meta
		    		$dataset_changes = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $sanitized_course_id, 'user_id' => $current_user->ID, 'type' => 'sensei_course_start' ) );
		    		$dataset_changes = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $sanitized_course_id, 'user_id' => $current_user->ID, 'type' => 'sensei_course_end' ) );
		    		// Get all course lessons
		    		$course_lessons = $this->course->course_lessons( $sanitized_course_id );
		    		// Remove all quiz user meta lessons
		    		// Mark all quiz user meta lessons as complete
		    		$dataset_changes = false;
	    			foreach ($course_lessons as $lesson_item){
	    				// Check for lesson complete
	    				$dataset_changes = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $lesson_item->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_start' ) );
	    				$dataset_changes = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $lesson_item->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_end' ) );
	    				// Lesson Quiz Meta
	        			$lesson_quizzes = $this->lesson->lesson_quizzes( $lesson_item->ID );
	        			if ( 0 < count($lesson_quizzes) )  {
	        				foreach ($lesson_quizzes as $quiz_item){
	        					// Check for quiz answers
	        					$delete_answers = WooThemes_Sensei_Utils::sensei_delete_quiz_answers( $quiz_item->ID, $current_user->ID );
	    						// Check for quiz grade
	    						$dataset_changes = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $quiz_item->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_quiz_grade' ) );
	    					} // End For Loop
	    				} // End If Statement
	    			} // End For Loop
	    			// Run any action on course reset
	    			do_action( 'sensei_user_course_reset', $current_user->ID, $sanitized_course_id );
		    		// Success message
		    		if ( $dataset_changes ) {
		    			$this->messages = '<header class="archive-header"><div class="sensei-message tick">' . sprintf( __( '%1$s deleted.', 'woothemes-sensei' ), get_the_title( $sanitized_course_id ) ) . '</div></header>';
		    		} // End If Statement
		    		break;
		    	default:
		    		// Nothing
		    		break;
		    } // End Switch Statement
		} // End If Statement
	} // End sensei_complete_course()

	public function sensei_complete_quiz() {
		global $post, $woothemes_sensei, $current_user;

		// Default grade
		$grade = 0;

		// Get Quiz Questions
	    $lesson_quiz_questions = $woothemes_sensei->frontend->lesson->lesson_quiz_questions( $post->ID );

		// Get Answers and Grade
		$user_quizzes = $this->sensei_get_user_quiz_answers( $post->ID );
		$user_quiz_grade =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $post->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_quiz_grade', 'field' => 'comment_content' ) );
		if ( '' == $user_quiz_grade ) {
			$user_quiz_grade = '';
		} // End If Statement

		if ( ! is_array($user_quizzes) ) { $user_quizzes = array(); }

		// Check if the lesson is complete
		$quiz_lesson = absint( get_post_meta( $post->ID, '_quiz_lesson', true ) );
		$user_lesson_end =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $quiz_lesson, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_end', 'field' => 'comment_content' ) );
		$user_lesson_complete = false;
		if ( '' != $user_lesson_end ) {
			$user_lesson_complete = true;
		} // End If Statement

		// Get quiz grade type
		$quiz_grade_type = get_post_meta( $post->ID, '_quiz_grade_type', true );

		// Get quiz pass mark
		$quiz_passmark = abs( round( doubleval( get_post_meta( $post->ID, '_quiz_passmark', true ) ), 2 ) );

		// Handle Quiz Completion
		if ( isset( $_POST['quiz_complete'] ) && wp_verify_nonce( $_POST[ 'woothemes_sensei_complete_quiz_noonce' ], 'woothemes_sensei_complete_quiz_noonce' ) ) {

		    $sanitized_submit = esc_html( $_POST['quiz_complete'] );

		    $answers_array = array();
		    $activity_logged = false;

		    $questions_asked = $_POST['questions_asked'];
		    $questions_asked_string = implode( ',', $questions_asked );
		    $questions_asked_args = false;
		    if( $questions_asked_string ) {
			    $questions_asked_args = array(
				    'post_id' => $post->ID,
				    'username' => $current_user->user_login,
				    'user_email' => $current_user->user_email,
				    'user_url' => $current_user->user_url,
				    'data' => $questions_asked_string,
				    'type' => 'sensei_quiz_asked', /* FIELD SIZE 20 */
				    'parent' => 0,
				    'user_id' => $current_user->ID
				);
			}

		    switch ($sanitized_submit) {
		    	case apply_filters( 'sensei_complete_quiz_text', __( 'Complete Quiz', 'woothemes-sensei' ) ):

		    		$activity_logged = WooThemes_Sensei_Utils::sensei_start_lesson( $quiz_lesson );

		    		// Save Quiz Answers
		    		if( isset( $_POST['sensei_question'] ) ) {
						$activity_logged = WooThemes_Sensei_Utils::sensei_save_quiz_answers( $_POST['sensei_question'] );
			    	}

			    	// Save questions that were asked in this quiz
					if( $questions_asked_args ) {
						$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $questions_asked_args );
					}

					if ( $activity_logged ) {

						// Grade quiz
		    			$grade = WooThemes_Sensei_Utils::sensei_grade_quiz_auto( $post->ID, $_POST['sensei_question'], count( $lesson_quiz_questions ), $quiz_grade_type );

		    			// Get quiz pass setting
	                	$pass_required = get_post_meta( $post->ID, '_pass_required', true );

						// Get Lesson Grading Setting
						if ( 'auto' == $quiz_grade_type && $pass_required ) {
							if ( $quiz_passmark <= $grade ) {
								// Student has reached the pass mark and lesson is complete
								$args = array(
												    'post_id' => $quiz_lesson,
												    'username' => $current_user->user_login,
												    'user_email' => $current_user->user_email,
												    'user_url' => $current_user->user_url,
												    'data' => __( 'Lesson completed and passed by the user', 'woothemes-sensei' ),
												    'type' => 'sensei_lesson_end', /* FIELD SIZE 20 */
												    'parent' => 0,
												    'user_id' => $current_user->ID
												);
								$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );

								do_action( 'sensei_user_lesson_end', $current_user->ID, $quiz_lesson );

							} // End If Statement
						} else {
							// Mark lesson as complete
							$args = array(
							    			    'post_id' => $quiz_lesson,
							    			    'username' => $current_user->user_login,
							    			    'user_email' => $current_user->user_email,
							    			    'user_url' => $current_user->user_url,
							    			    'data' => __( 'Lesson completed by the user', 'woothemes-sensei' ),
							    			    'type' => 'sensei_lesson_end', /* FIELD SIZE 20 */
							    			    'parent' => 0,
							    			    'user_id' => $current_user->ID
							    			);
							$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );

							do_action( 'sensei_user_lesson_end', $current_user->ID, $quiz_lesson );

						} // End If Statement

						if( 'manual' == $quiz_grade_type ) {
							do_action( 'sensei_user_quiz_submitted', $current_user->ID, $post->ID );
						}

					} // End If Statement

					break;
		    	case apply_filters( 'sensei_save_quiz_text', __( 'Save Quiz', 'woothemes-sensei' ) ):

		    		$activity_logged = WooThemes_Sensei_Utils::sensei_start_lesson( $quiz_lesson );

			    	if( isset( $_POST['sensei_question'] ) ) {
			    		$activity_logged = WooThemes_Sensei_Utils::sensei_save_quiz_answers( $_POST['sensei_question'] );
			    	}

			    	// Save questions that were asked in this quiz
			    	if( $questions_asked_args ) {
						$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $questions_asked_args );
					}

					$this->messages = '<div class="sensei-message note">' . apply_filters( 'sensei_quiz_saved_text', __( 'Quiz Saved Successfully.', 'woothemes-sensei' ) ) . '</div>';

					break;
		    	case apply_filters( 'sensei_reset_quiz_text', __( 'Reset Quiz', 'woothemes-sensei' ) ):
		    		// Remove existing user quiz meta
		    		$grade = '';
		    		$answers_array = array();

		    		// Delete quiz grade
		    		$delete_grades = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $post->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_quiz_grade' ) );

		    		// Delete quiz answers
		    		$delete_answers = WooThemes_Sensei_Utils::sensei_delete_quiz_answers( $post->ID, $current_user->ID );

		    		// Delete lesson completion flag
		    		$delete_lesson_completion = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $quiz_lesson, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_end' ) );

		    		// Delete course completion flag
		    		$course_id = absint( get_post_meta( $quiz_lesson, '_lesson_course' ,true ) );
		    		$delete_course_completion = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $course_id, 'user_id' => $current_user->ID, 'type' => 'sensei_course_end' ) );

		    		// Delete questions asked in this quiz
		    		$delete_questions_asked = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $post->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_quiz_asked' ) );

		    		$this->messages = '<div class="sensei-message note">' . apply_filters( 'sensei_quiz_reset_text', __( 'Quiz Reset Successfully.', 'woothemes-sensei' ) ) . '</div>';
		    		break;
		    	default:
		    		// Nothing
		    		break;

		    } // End Switch Statement

		    // Refresh page to avoid re-posting
			?>
		    <script type="text/javascript"> window.location = '<?php echo get_permalink( $post->ID ); ?>'; </script>
		    <?php

		    // Get latest quiz answers and grades
			$user_quizzes = $this->sensei_get_user_quiz_answers( $post->ID );
		    $user_quiz_grade =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $post->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_quiz_grade', 'field' => 'comment_content' ) );
			if ( '' == $user_quiz_grade ) {
				$user_quiz_grade = '';
			} // End If Statement

			if ( ! is_array($user_quizzes) ) { $user_quizzes = array(); }

			$quiz_lesson = absint( get_post_meta( $post->ID, '_quiz_lesson', true ) );

			// Check again that the lesson is complete
			$user_lesson_end = WooThemes_Sensei_Utils::user_completed_lesson( $quiz_lesson, $current_user->ID );
			$user_lesson_complete = false;
			if ( '' != $user_lesson_end ) {
				$user_lesson_complete = true;
			} // End If Statement

		} // End If Statement

		$this->data = new stdClass();

		$reset_allowed = get_post_meta( $post->ID, '_enable_quiz_reset', true );

		// Build frontend data object
		$this->data->user_quizzes = $user_quizzes;
		$this->data->user_quiz_grade = $user_quiz_grade;
		$this->data->quiz_passmark = $quiz_passmark;
		$this->data->quiz_lesson = $quiz_lesson;
		$this->data->quiz_grade_type = $quiz_grade_type;
		$this->data->user_lesson_end = $user_lesson_end;
		$this->data->user_lesson_complete = $user_lesson_complete;
		$this->data->lesson_quiz_questions = $lesson_quiz_questions;
		$this->data->reset_quiz_allowed = $reset_allowed;

	} // End sensei_complete_quiz()

	public function sensei_get_user_quiz_answers( $lesson_id = 0 ) {
		global $current_user, $woothemes_sensei;

		$user_answers = array();

		if ( 0 < intval( $lesson_id ) ) {
			$lesson_quiz_questions = $woothemes_sensei->frontend->lesson->lesson_quiz_questions( $lesson_id );
			foreach( $lesson_quiz_questions as $question ) {
				$answer = maybe_unserialize( base64_decode( WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $question->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_user_answer', 'field' => 'comment_content' ) ) ) );
				$user_answers[ $question->ID ] = $answer;
			}
		}

		return $user_answers;
	} // End sensei_get_user_quiz_answers()

	public function sensei_has_user_completed_lesson( $post_id = 0, $user_id = 0 ) {
		$user_lesson_complete = false;
		if ( 0 < intval( $post_id ) && 0 < intval( $user_id ) ) {
			$user_lesson_end = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $post_id, 'user_id' => $user_id, 'type' => 'sensei_lesson_end', 'field' => 'comment_content' ) );
			if ( '' != $user_lesson_end ) {
			    $user_lesson_complete = true;
			} // End If Statement
		} // End If Statement
		return $user_lesson_complete;
	} // End sensei_has_user_completed_lesson()

	public function sensei_frontend_messages() {
		if ( isset( $this->messages ) && '' != $this->messages ) {
			echo $this->messages;
		} // End If Statement
	} // End sensei_frontend_messages()

	public function sensei_lesson_video( $post_id = 0 ) {
		if ( 0 < intval( $post_id ) ) {
			$lesson_video_embed = get_post_meta( $post_id, '_lesson_video_embed', true );
			if ( 'http' == substr( $lesson_video_embed, 0, 4) ) {
        		// V2 - make width and height a setting for video embed
        		$lesson_video_embed = wp_oembed_get( esc_url( $lesson_video_embed )/*, array( 'width' => 100 , 'height' => 100)*/ );
        	} // End If Statement
        	?><div class="video"><?php echo html_entity_decode($lesson_video_embed); ?></div><?php
        } // End If Statement
	} // End sensei_lesson_video()

	public function sensei_complete_lesson_button() {
		global $woothemes_sensei, $post;

		$quiz_id = 0;

		// Lesson quizzes
		$lesson_quizzes = $woothemes_sensei->frontend->lesson->lesson_quizzes( $post->ID );
		$pass_required = true;
		if( is_array( $lesson_quizzes ) && 0 < count( $lesson_quizzes ) ) {
			foreach ($lesson_quizzes as $quiz_item) {
				$quiz_id = $quiz_item->ID;
				break;
			}

			// Get quiz pass setting
	    	$pass_required = get_post_meta( $quiz_id, '_pass_required', true );
	    }
		if( ! $quiz_id || ( $quiz_id && ! $pass_required ) ) {
			?>
			<form class="lesson_button_form" method="POST" action="<?php echo esc_url( get_permalink() ); ?>#lesson_complete">
	            <input type="hidden" name="<?php echo esc_attr( 'woothemes_sensei_complete_lesson_noonce' ); ?>" id="<?php echo esc_attr( 'woothemes_sensei_complete_lesson_noonce' ); ?>" value="<?php echo esc_attr( wp_create_nonce( 'woothemes_sensei_complete_lesson_noonce' ) ); ?>" />
	            <span><input type="submit" name="quiz_complete" class="quiz-submit complete" value="<?php echo apply_filters( 'sensei_complete_lesson_text', __( 'Complete Lesson', 'woothemes-sensei' ) ); ?>"/></span>
	        </form>
			<?php
		} // End If Statement
	} // End sensei_complete_lesson_button()

	public function sensei_lesson_quiz_meta( $post_id = 0, $user_id = 0 ) {
		global $woothemes_sensei;
		// Get the prerequisite lesson
		$lesson_prerequisite = get_post_meta( $post_id, '_lesson_prerequisite', true );
		$lesson_course_id = get_post_meta( $post_id, '_lesson_course', true );

		// Lesson Quiz Meta
		$lesson_quizzes = $woothemes_sensei->frontend->lesson->lesson_quizzes( $post_id );
		foreach ($lesson_quizzes as $quiz_item) {
			$quiz_id = $quiz_item->ID ;
		}
		$has_user_completed_lesson = WooThemes_Sensei_Utils::user_completed_lesson( $post_id, $user_id );
		$show_actions = true;

		if( intval( $lesson_prerequisite ) > 0 ) {

			// Lesson Quiz Meta
			$prereq_lesson_quizzes = $woothemes_sensei->frontend->lesson->lesson_quizzes( $lesson_prerequisite );
			foreach ($prereq_lesson_quizzes as $quiz_item) {
				$prerequisite_quiz_id = $quiz_item->ID ;
			}

			// Get quiz pass setting
        	$prereq_pass_required = get_post_meta( $prerequisite_quiz_id, '_pass_required', true );

			if( $prereq_pass_required ) {
				$quiz_grade = intval( WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $prerequisite_quiz_id, 'user_id' => $user_id, 'type' => 'sensei_quiz_grade', 'field' => 'comment_content' ) ) );
				$quiz_passmark = intval( get_post_meta( $prerequisite_quiz_id, '_quiz_passmark', true ) );
				if( $quiz_grade < $quiz_passmark ) {
					$show_actions = false;
				}
			} else {
				$user_lesson_end = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_prerequisite, 'user_id' => $user_id, 'type' => 'sensei_lesson_start', 'field' => 'comment_content' ) );
				if ( ! $user_lesson_end || $user_lesson_end == '' || strlen( $user_lesson_end ) == 0 ) {
					$show_actions = false;
				}
			}
		}
		?><header><?php
		if ( 0 < count($lesson_quizzes) && is_user_logged_in() && sensei_has_user_started_course( $lesson_course_id, $user_id ) ) { ?>
            <?php $no_quiz_count = 0; ?>
        	<?php
        		$quiz_questions = $woothemes_sensei->frontend->lesson->lesson_quiz_questions( $quiz_id );
	        	// Display lesson quiz status message
	        	if ( $has_user_completed_lesson || 0 < count( $quiz_questions ) ) {
	        		$status = WooThemes_Sensei_Utils::sensei_user_quiz_status_message( $post_id, $user_id, true );
	        		echo '<div class="sensei-message ' . $status['box_class'] . '">' . $status['message'] . '</div>';
	    			if( 0 < count( $quiz_questions ) ) {
	        			echo $status['extra'];
    				} // End If Statement
    			} // End If Statement
        	?>
        <?php } elseif( $show_actions && 0 < count($lesson_quizzes) && $woothemes_sensei->access_settings() ) { ?>
    		<?php
        		$quiz_questions = $woothemes_sensei->frontend->lesson->lesson_quiz_questions( $quiz_id );
        		if( 0 < count( $quiz_questions ) ) { ?>
        			<p><a class="button" href="<?php echo esc_url( get_permalink( $quiz_id ) ); ?>" title="<?php echo esc_attr( apply_filters( 'sensei_view_lesson_quiz_text', __( 'View the Lesson Quiz', 'woothemes-sensei' ) ) ); ?>"><?php echo apply_filters( 'sensei_view_lesson_quiz_text', __( 'View the Lesson Quiz', 'woothemes-sensei' ) ); ?></a></p>
        		<?php } ?>
        <?php } // End If Statement
        if ( $show_actions && ! $has_user_completed_lesson ) {
        	sensei_complete_lesson_button();
        } elseif( $show_actions ) {
        	sensei_reset_lesson_button();
        } // End If Statement
        ?></header><?php
	} // End sensei_lesson_quiz_meta()

	public function sensei_course_archive_meta() {
		global $woothemes_sensei, $post;
		// Meta data
		$post_id = get_the_ID();
		$post_title = get_the_title();
		$author_display_name = get_the_author();
		$author_id = get_the_author_meta('ID');
		$category_output = get_the_term_list( $post_id, 'course-category', '', ', ', '' );
		$free_lesson_count = intval( $woothemes_sensei->post_types->course->course_lesson_preview_count( $post_id ) );
		?><section class="entry">
        	<p class="sensei-course-meta">
           	<?php if ( isset( $woothemes_sensei->settings->settings[ 'course_author' ] ) && ( $woothemes_sensei->settings->settings[ 'course_author' ] ) ) { ?>
		   	<span class="course-author"><?php _e( 'by ', 'woothemes-sensei' ); ?><?php the_author_link(); ?></span>
		   	<?php } // End If Statement ?>
		   	<span class="course-lesson-count"><?php echo $woothemes_sensei->post_types->course->course_lesson_count( $post_id ) . '&nbsp;' . apply_filters( 'sensei_lessons_text', __( 'Lessons', 'woothemes-sensei' ) ); ?></span>
		   	<?php if ( '' != $category_output ) { ?>
		   	<span class="course-category"><?php echo sprintf( __( 'in %s', 'woothemes-sensei' ), $category_output ); ?></span>
		   	<?php } // End If Statement ?>
		   	<?php sensei_simple_course_price( $post_id ); ?>
        	</p>
        	<p class="course-excerpt"><?php echo apply_filters( 'get_the_excerpt', $post->post_excerpt ); ?></p>
        	<?php if ( 0 < $free_lesson_count ) {
                    $free_lessons = sprintf( __( 'You can access %d of this course\'s lessons for free', 'woothemes_sensei' ), $free_lesson_count ); ?>
                    <p class="sensei-free-lessons"><a href="<?php echo get_permalink( $post_id ); ?>"><?php _e( 'Preview this course', 'woothemes_sensei' ) ?></a> - <?php echo $free_lessons; ?></p>
            <?php } ?>
		</section><?php
	} // End sensei_course_archive_meta()

	public function sensei_single_main_content() {
		while ( have_posts() ) {
			the_post();
			if ( is_singular( 'course' ) ) {
				$this->sensei_get_template_part( 'content', 'single-course' );
			} elseif( is_singular( 'lesson' ) ) {
				$this->sensei_get_template_part( 'content', 'single-lesson' );
				do_action( 'sensei_comments' );
			} elseif( is_singular( 'quiz' ) ) {
				$this->sensei_get_template_part( 'content', 'single-quiz' );
			} elseif( is_singular( 'sensei_message' ) ) {
				$this->sensei_get_template_part( 'content', 'single-message' );
				do_action( 'sensei_comments' );
			} // End If Statement
		} // End While Loop
	} // End sensei_single_main_content()

	public function sensei_course_archive_main_content() {
		global $woothemes_sensei, $wp_query;
		if ( have_posts() && ( is_post_type_archive( 'course' ) || is_page( $woothemes_sensei->get_page_id( 'courses' ) ) ) ) {
			// Handle pagiation
			$paged = $wp_query->get( 'paged' );
			if ( ! $paged || $paged < 2 ) {
				// This is not a paginated page (or it's simply the first page of a paginated page/post)
				echo do_shortcode( '[newcourses]' );
				echo do_shortcode( '[featuredcourses]' );
				echo do_shortcode( '[freecourses]' );
				echo do_shortcode( '[paidcourses]' );
			} else {
				$this->sensei_get_template( 'loop-course.php' );
			} // End If Statement
		} else {
			?><p><?php _e( 'No courses found that match your selection.', 'woothemes-sensei' ); ?></p><?php
		} // End If Statement
	} // End sensei_course_archive_main_content()

	public function sensei_lesson_archive_main_content() {
		if ( have_posts() ) {
			$this->sensei_get_template( 'loop-lesson.php' );
		} else {
			?><p><?php _e( 'No lessons found that match your selection.', 'woothemes-sensei' ); ?></p><?php
		} // End If Statement
	} // End sensei_lesson_archive_main_content()

	public function sensei_message_archive_main_content() {
		if ( have_posts() ) {
			$this->sensei_get_template( 'loop-message.php' );
		} else {
			?><p><?php _e( 'You do not have any messages.', 'woothemes-sensei' ); ?></p><?php
		} // End If Statement
	} // End sensei_lesson_archive_main_content()

	public function sensei_no_permissions_main_content() {
		while ( have_posts() ) {
			the_post();
			$this->sensei_get_template_part( 'content', 'no-permissions' );
		} // End While Loop
	} // End sensei_no_permissions_main_content()

	public function sensei_course_category_main_content() {
		global $post;
		if ( have_posts() ) { ?>
			<section id="main-course" class="course-container">
	    	    <?php do_action( 'sensei_course_archive_header' ); ?>
	    	    <?php while ( have_posts() ) { the_post(); ?>
				<article class="<?php echo join( ' ', get_post_class( array( 'course', 'post' ), get_the_ID() ) ); ?>">
	    			<?php do_action( 'sensei_course_image', get_the_ID() ); ?>
	    			<?php do_action( 'sensei_course_archive_course_title', $post ); ?>
	    			<?php do_action( 'sensei_course_archive_meta' ); ?>
	    		</article>
	    		<?php } // End While Loop ?>
	    	</section>
		<?php } else { ?>
			<p><?php _e( 'No courses found that match your selection.', 'woothemes-sensei' ); ?></p>
		<?php } // End If Statement
	} // End sensei_course_category_main_content()


	public function sensei_login_form() {
		?><div id="my-courses">
			<div class="col2-set" id="customer_login">

				<div class="col-1">

					<h2><?php _e( 'Login', 'woothemes-sensei' ); ?></h2>

					<?php wp_login_form( array( 'redirect' => get_permalink() ) ); ?>

			<?php
			if ( get_option('users_can_register') ) {
				?>

				</div>

				<div class="col-2">
					<h2><?php _e( 'Register', 'woothemes-sensei' ); ?></h2>

					<form method="post" class="register">

						<?php do_action( 'sensei_register_form_start' ); ?>

						<p class="form-row form-row-wide">
							<label for="reg_username"><?php _e( 'Username', 'woothemes-sensei' ); ?> <span class="required">*</span></label>
							<input type="text" class="input-text" name="username" id="reg_username" value="<?php if ( ! empty( $_POST['username'] ) ) esc_attr_e( $_POST['username'] ); ?>" />
						</p>

						<p class="form-row form-row-wide">
							<label for="reg_email"><?php _e( 'Email address', 'woothemes-sensei' ); ?> <span class="required">*</span></label>
							<input type="email" class="input-text" name="email" id="reg_email" value="<?php if ( ! empty( $_POST['email'] ) ) esc_attr_e( $_POST['email'] ); ?>" />
						</p>

						<p class="form-row form-row-wide">
							<label for="reg_password"><?php _e( 'Password', 'woothemes-sensei' ); ?> <span class="required">*</span></label>
							<input type="password" class="input-text" name="password" id="reg_password" value="<?php if ( ! empty( $_POST['password'] ) ) esc_attr_e( $_POST['password'] ); ?>" />
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
				<?php
			}
			?>

				</div>
			</div>
		</div>

		<?php
	} // End sensei_login_form()

	public function sensei_quiz_action_buttons() {
		global $post, $current_user, $woothemes_sensei;
		$lesson_id = get_post_meta( $post->ID, '_quiz_lesson', true );
		$lesson_course_id = get_post_meta( $lesson_id, '_lesson_course', true );
		$lesson_prerequisite = get_post_meta( $lesson_id, '_lesson_prerequisite', true );
		$show_actions = true;
		if( intval( $lesson_prerequisite ) > 0 ) {

			$quizzes = $woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson_prerequisite );
			foreach ( $quizzes as $quiz ) {
                $prerequisite_quiz_id = $quiz->ID;
                break;
            }

			// Get quiz pass setting
    		$pass_required = get_post_meta( $prerequisite_quiz_id, '_pass_required', true );

			if( $pass_required ) {

				$quiz_grade = intval( WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $prerequisite_quiz_id, 'user_id' => $current_user->ID, 'type' => 'sensei_quiz_grade', 'field' => 'comment_content' ) ) );
				$quiz_passmark = intval( get_post_meta( $prerequisite_quiz_id, '_quiz_passmark', true ) );
				if( $quiz_grade < $quiz_passmark ) {
					$show_actions = false;
				}

			} else {
				$user_lesson_end = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_prerequisite, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_start', 'field' => 'comment_content' ) );
				if ( ! $user_lesson_end || $user_lesson_end == '' || strlen( $user_lesson_end ) == 0 ) {
					$show_actions = false;
				}
			}
		}
		if ( $show_actions && is_user_logged_in() && sensei_has_user_started_course( $lesson_course_id, $current_user->ID ) ) {

			// Get Reset Settings
			$reset_quiz_allowed = get_post_meta( $post->ID, '_enable_quiz_reset', true ); ?>
			<input type="hidden" name="<?php echo esc_attr( 'woothemes_sensei_complete_quiz_noonce' ); ?>" id="<?php echo esc_attr( 'woothemes_sensei_complete_quiz_noonce' ); ?>" value="<?php echo esc_attr(  wp_create_nonce( 'woothemes_sensei_complete_quiz_noonce' ) ); ?>" />
		    <?php if ( '' == $this->data->user_quiz_grade ) { ?>
		 	<span><input type="submit" name="quiz_complete" class="quiz-submit complete" value="<?php echo apply_filters( 'sensei_complete_quiz_text', __( 'Complete Quiz', 'woothemes-sensei' ) ); ?>"/></span>
		 	<span><input type="submit" name="quiz_complete" class="quiz-submit save" value="<?php echo apply_filters( 'sensei_save_quiz_text', __( 'Save Quiz', 'woothemes-sensei' ) ); ?>"/></span>
		     <?php } // End If Statement ?>
	          <?php if ( isset( $reset_quiz_allowed ) && $reset_quiz_allowed ) { ?>
		 	   <span><input type="submit" name="quiz_complete" class="quiz-submit reset" value="<?php echo apply_filters( 'sensei_reset_quiz_text', __( 'Reset Quiz', 'woothemes-sensei' ) ); ?>"/></span>
		     <?php } ?>
        <?php }
	} // End sensei_quiz_action_buttons()

	public function sensei_lesson_meta( $post_id = 0 ) {
		global $post, $woothemes_sensei;
		if ( 0 < intval( $post_id ) ) {
		$lesson_course_id = absint( get_post_meta( $post_id, '_lesson_course', true ) );
		?><section class="entry">
            <p class="sensei-course-meta">
			    <?php if ( isset( $woothemes_sensei->settings->settings[ 'lesson_author' ] ) && ( $woothemes_sensei->settings->settings[ 'lesson_author' ] ) ) { ?>
			    <span class="course-author"><?php _e( 'by ', 'woothemes-sensei' ); ?><?php the_author_link(); ?></span>
			    <?php } ?>
                <?php if ( 0 < intval( $lesson_course_id ) ) { ?>
                <span class="lesson-course"><?php echo '&nbsp;' . sprintf( __( 'Part of: %s', 'woothemes-sensei' ), '<a href="' . esc_url( get_permalink( $lesson_course_id ) ) . '" title="' . esc_attr( apply_filters( 'sensei_view_course_text', __( 'View course', 'woothemes-sensei' ) ) ) . '"><em>' . get_the_title( $lesson_course_id ) . '</em></a>' ); ?></span>
                <?php } ?>
            </p>
            <p class="lesson-excerpt"><?php echo apply_filters( 'get_the_excerpt', $post->post_excerpt ); ?></p>
		</section><?php
		} // End If Statement
	} // sensei_lesson_meta()

	public function sensei_lesson_preview_title_text( $course_id ) {
		$preview_text = __( ' (Preview)', 'woothemes_sensei' );
		//if this is a paid course
		if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() ) {
    	    $wc_post_id = get_post_meta( $course_id, '_course_woocommerce_product', true );
    	    if ( 0 < $wc_post_id ) {
    	    	$preview_text = __( ' (Free Preview)', 'woothemes_sensei' );
    	    } // End If Statement
    	}
    	return $preview_text;
	}

	public function sensei_lesson_preview_title( $title = '', $id = 0 ) {
		global $post, $current_user;

		if( isset( $post->ID ) ) {
			// Get the course ID
			$course_id = get_post_meta( $post->ID, '_lesson_course', true );
			// Check if the user is taking the course
			$is_user_taking_course = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $course_id, 'user_id' => $current_user->ID, 'type' => 'sensei_course_start' ) );
			if( is_singular( 'lesson' ) && WooThemes_Sensei_Utils::is_preview_lesson( $post->ID ) && !$is_user_taking_course && $post->ID == $id ) {
				$title .= ' ' . $this->sensei_lesson_preview_title_text( $course_id );
			}
		}
		return $title;
	} // sensei_lesson_preview_title

	public function sensei_course_start() {
		global $post, $current_user;
		// Check if the user is taking the course
		$is_user_taking_course = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $post->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_course_start' ) );
		// Handle user starting the course
		if ( isset( $_POST['course_start'] ) && wp_verify_nonce( $_POST[ 'woothemes_sensei_start_course_noonce' ], 'woothemes_sensei_start_course_noonce' ) && !$is_user_taking_course ) {
		    // Start the course
			$args = array(
							    'post_id' => $post->ID,
							    'username' => $current_user->user_login,
							    'user_email' => $current_user->user_email,
							    'user_url' => $current_user->user_url,
							    'data' => __( 'Course started by the user', 'woothemes-sensei' ),
							    'type' => 'sensei_course_start', /* FIELD SIZE 20 */
							    'parent' => 0,
							    'user_id' => $current_user->ID
							);
			$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );
			$this->data = new stdClass();
			$this->data->is_user_taking_course = false;
			if ( $activity_logged ) {
				$this->data->is_user_taking_course = true;

				do_action( 'sensei_user_course_start', $current_user->ID, $post->ID );

				// Refresh page to avoid re-posting
				?>
			    <script type="text/javascript"> window.location = '<?php echo get_permalink( $post->ID ); ?>'; </script>
			    <?php
			} // End If Statement
		} // End If Statement
	} // End sensei_course_start()

	public function sensei_course_meta() {
		global $woothemes_sensei, $post, $current_user;
		?><section class="course-meta">
			<?php $is_user_taking_course = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $post->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_course_start' ) ); ?>
			<?php if ( is_user_logged_in() && ! $is_user_taking_course ) {
		    	// Get the product ID
		    	$wc_post_id = absint( get_post_meta( $post->ID, '_course_woocommerce_product', true ) );
		    	// Check for woocommerce
		    	if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() && ( 0 < intval( $wc_post_id ) ) ) {
		    		sensei_wc_add_to_cart($post->ID);
		    	} else {
		    		sensei_start_course_form($post->ID);
		    	} // End If Statement
		    } elseif ( is_user_logged_in() ) {
		    	// Check if course is completed
		    	$user_course_end =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $post->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_course_end', 'field' => 'comment_content' ) );
				$completed_course = false;
				if ( '' != $user_course_end ) {
					$completed_course = true;
				} else {
					// Do the check if all lessons complete
					$course_lessons = $woothemes_sensei->frontend->course->course_lessons( $post->ID );
		    		$lessons_completed = 0;
		    		foreach ($course_lessons as $lesson_item){
		    			// Check if Lesson is complete
		    			$user_lesson_end =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_item->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_end', 'field' => 'comment_content' ) );
						if ( '' != $user_lesson_end ) {
							//Check for Passed or Completed Setting
							$course_completion = $woothemes_sensei->settings->settings[ 'course_completion' ];
							if ( 'passed' == $course_completion ) {
								// If Setting is Passed -> Check for Quiz Grades
								$lesson_quizzes = $woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson_item->ID );
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
									} // End If Statement
								} // End If Statement
							} else {
								$lessons_completed++;
							} // End If Statement
						} // End If Statement
					} // End For Loop
					if ( absint( $lessons_completed ) == absint( count( $course_lessons ) ) && ( 0 < absint( count( $course_lessons ) ) ) && ( 0 < absint( $lessons_completed ) ) ) {
		    			// Mark course as complete
		    			$args = array(
										    'post_id' => $post->ID,
										    'username' => $current_user->user_login,
										    'user_email' => $current_user->user_email,
										    'user_url' => $current_user->user_url,
										    'data' => __( 'Course completed by the user', 'woothemes-sensei' ),
										    'type' => 'sensei_course_end', /* FIELD SIZE 20 */
										    'parent' => 0,
										    'user_id' => $current_user->ID,
										    'action' => 'update'
										);
		    			$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );
						$dataset_changes = true;
						if ( $activity_logged ) {
							// Course is complete
							$completed_course = true;
							do_action( 'sensei_user_course_end', $current_user->ID, $post->ID );
						} // End If Statement
		    		} // End If Statement
				} // End If Statement
				// Success message
		   		if ( $completed_course ) { ?>
		   			<div class="status completed"><?php echo apply_filters( 'sensei_complete_text', __( 'Completed', 'woothemes-sensei' ) ); ?></div>
		   			<?php if( count( $woothemes_sensei->frontend->course->course_quizzes( $post->ID ) ) > 0 ) { ?>
		   				<p class="sensei-results-links"><a class="view-results" href="<?php echo $woothemes_sensei->course_results->get_permalink( $post->ID ); ?>"><?php echo apply_filters( 'sensei_view_results_text', __( 'View results', 'woothemes-sensei' ) ); ?></a></p>
		   			<?php } ?>
		   		<?php } else { ?>
		    		<div class="status in-progress"><?php echo apply_filters( 'sensei_in_progress_text', __( 'In Progress', 'woothemes-sensei' ) ); ?></div>
		    	<?php } ?>
		    <?php } else {
		    	// Get the product ID
		    	$wc_post_id = absint( get_post_meta( $post->ID, '_course_woocommerce_product', true ) );
		    	// Check for woocommerce
		    	if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() && ( 0 < intval( $wc_post_id ) ) ) {
		    		sensei_wc_add_to_cart($post->ID);
		    	} else {
		    		// User needs to register
		    		wp_register( '<div class="status register">', '</div>' );
		    	} // End If Statement
		    } // End If Statement ?>

		</section><?php

	} // End sensei_course_meta()


	public function sensei_course_meta_video() {
		global $post;
		// Get the meta info
		$course_video_embed = get_post_meta( $post->ID, '_course_video_embed', true );
		if ( 'http' == substr( $course_video_embed, 0, 4) ) {
		    // V2 - make width and height a setting for video embed
		    $course_video_embed = wp_oembed_get( esc_url( $course_video_embed )/*, array( 'width' => 100 , 'height' => 100)*/ );
		} // End If Statement
		?><div class="course-video"><?php echo html_entity_decode($course_video_embed); ?></div><?php
	} // End sensei_course_meta_video()

	public function sensei_woocommerce_in_cart_message() {
		global $post, $woocommerce;
		$wc_post_id = absint( get_post_meta( $post->ID, '_course_woocommerce_product', true ) );

		if ( 0 < intval( $wc_post_id ) ) {
			if ( sensei_check_if_product_is_in_cart( $wc_post_id ) ) {
				echo '<div class="sensei-message info">' . sprintf(  __('You have already added this Course to your cart. Please %1$s to access the course.', 'woothemes-sensei') . '</div>', '<a class="cart-complete" href="' . $woocommerce->cart->get_checkout_url() . '" title="' . __('complete the purchase', 'woothemes-sensei') . '">' . __('complete the purchase', 'woothemes-sensei') . '</a>' );
			} // End If Statement
		} // End If Statement

	} // End sensei_woocommerce_in_cart_message()

	public function sensei_lesson_comment_count( $count ) {
		global $post, $current_user;
		if ( is_singular( 'lesson' ) || is_singular( 'course' ) ) {
			$lesson_comments_start = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $post->ID, 'type' => 'sensei_lesson_start' ), true );
			$lesson_comments_end = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $post->ID, 'type' => 'sensei_lesson_end' ), true );
			$lesson_quiz_grade = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $post->ID, 'type' => 'sensei_quiz_grade' ), true );
			$lesson_quiz_asked = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $post->ID, 'type' => 'sensei_quiz_asked' ), true );
			$course_comments_start = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $post->ID, 'type' => 'sensei_course_start' ), true );
			$course_comments_end = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $post->ID, 'type' => 'sensei_course_end' ), true );
			return $count - intval( count( $lesson_comments_start ) ) - intval( count( $lesson_comments_end ) ) - intval( count( $lesson_quiz_grade ) ) - intval( count( $lesson_quiz_asked ) ) - intval( count( $course_comments_start ) ) - intval( count( $course_comments_end ) );
		} else {
			return $count;
		} // End If Statement
	} // End sensei_lesson_comment_count()

	/**
	 * comments_rss_item_filter function.
	 *
	 * Filters the frontend comments feed to not include the sensei prefixed comments
	 *
	 * @access public
	 * @param mixed $pieces
	 * @return void
	 */
	function comments_rss_item_filter( $pieces ) {
		// if ( is_comment_feed() ) {
			$pieces .= " AND comment_type NOT LIKE 'sensei_%' ";
		// } // End If Statement
		return $pieces;
	} // End comments_rss_item_filter()

	/**
	 * sensei_completed_course hooks onto everywhere a lesson ends to check if course is complete
	 * @param  integer $user_id   User ID overload - todo in future version
	 * @param  integer $lesson_id lesson ID
	 * @return boolen			  returns true if successful
	 */
	public function sensei_completed_course( $user_id = 0, $lesson_id = 0 ) {
		global $woothemes_sensei, $current_user;

		$completed_course = false;

		// Get Course ID
		$course_id = get_post_meta( $lesson_id, '_lesson_course', true );

		if ( 0 < intval( $course_id ) ) {

			$is_user_taking_course = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $course_id, 'user_id' => $current_user->ID, 'type' => 'sensei_course_start' ) );

			if ( is_user_logged_in() && $is_user_taking_course ) {

		    	// Check if course is completed
		    	$user_course_end =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $course_id, 'user_id' => $current_user->ID, 'type' => 'sensei_course_end', 'field' => 'comment_content' ) );

				if ( '' != $user_course_end ) {

					$completed_course = true;

				} else {

					// Do the check if all lessons complete
					$course_lessons = $woothemes_sensei->frontend->course->course_lessons( $course_id );

		    		$lessons_completed = 0;
		    		foreach ($course_lessons as $lesson_item){

		    			// Check if Lesson is complete
		    			$user_lesson_end =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_item->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_end', 'field' => 'comment_content' ) );

						if ( '' != $user_lesson_end ) {

							//Check for Passed or Completed Setting
							$course_completion = $woothemes_sensei->settings->settings[ 'course_completion' ];

							if ( 'passed' == $course_completion ) {

								// If Setting is Passed -> Check for Quiz Grades
								$lesson_quizzes = $woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson_item->ID );

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
									} // End If Statement

								} // End If Statement

							} else {
								$lessons_completed++;
							} // End If Statement

						} // End If Statement

					} // End For Loop

					if ( absint( $lessons_completed ) == absint( count( $course_lessons ) ) && ( 0 < absint( count( $course_lessons ) ) ) && ( 0 < absint( $lessons_completed ) ) ) {

		    			// Mark course as complete
		    			$args = array(
										    'post_id' => $course_id,
										    'username' => $current_user->user_login,
										    'user_email' => $current_user->user_email,
										    'user_url' => $current_user->user_url,
										    'data' => __( 'Course completed by the user', 'woothemes-sensei' ),
										    'type' => 'sensei_course_end', /* FIELD SIZE 20 */
										    'parent' => 0,
										    'user_id' => $current_user->ID,
										    'action' => 'update'
										);
		    			$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );

						$dataset_changes = true;
						if ( $activity_logged ) {

							// Course is complete
							$completed_course = true;
							do_action( 'sensei_user_course_end', $current_user->ID, $course_id );

						} // End If Statement

		    		} // End If Statement

				} // End If Statement

		    } else {

		    	$completed_course = false;

		    } // End If Statement

		} // End If Statement

		return $completed_course;

	} // End sensei_completed_course()

	/**
	 * Only show excerpts for lessons and courses in search results
	 * @param  string $content Original content
	 * @return string          Modified content
	 */
	public function sensei_search_results_excerpt( $content ) {
		global $post;

		if( is_search() && in_array( $post->post_type, array( 'course', 'lesson' ) ) ) {
			$content = '<p class="course-excerpt">' . apply_filters( 'get_the_excerpt', $post->post_excerpt ) . '</p>';
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

            if ( $item['product_id'] > 0 ) {

				$user_id = get_post_meta( $order_id, '_customer_user', true );

				if( $user_id ) {

					// Get all courses for product
					$args = array(
						'posts_per_page' => -1,
						'post_type' => 'course',
						'meta_query' => array(
							array(
								'key' => '_course_woocommerce_product',
								'value' => $item['product_id']
							)
						),
						'orderby' => 'menu_order date',
						'order' => 'ASC',
					);
					$courses = get_posts( $args );

					if( $courses && count( $courses ) > 0 ) {
						foreach( $courses as $course ) {

				    		// Remove all course user meta
				    		$dataset_changes = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $course->ID, 'user_id' => $user_id, 'type' => 'sensei_course_start' ) );
				    		$dataset_changes = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $course->ID, 'user_id' => $user_id, 'type' => 'sensei_course_end' ) );

				    		// Get all course lessons
				    		$course_lessons = $this->course->course_lessons( $course->ID );

				    		// Remove all lesson user meta in course
			    			foreach ($course_lessons as $lesson_item){

			    				$dataset_changes = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $lesson_item->ID, 'user_id' => $user_id, 'type' => 'sensei_lesson_start' ) );
			    				$dataset_changes = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $lesson_item->ID, 'user_id' => $user_id, 'type' => 'sensei_lesson_end' ) );

			    				// Remove all quiz user meta in lesson
			        			$lesson_quizzes = $this->lesson->lesson_quizzes( $lesson_item->ID );
			        			if ( 0 < count($lesson_quizzes) )  {
			        				foreach ($lesson_quizzes as $quiz_item){
			        					$delete_answers = WooThemes_Sensei_Utils::sensei_delete_quiz_answers( $quiz_item->ID, $user_id );
			    						$dataset_changes = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $quiz_item->ID, 'user_id' => $user_id, 'type' => 'sensei_quiz_grade' ) );
			    					} // End For Loop
			    				} // End If Statement
			    			} // End For Loop
						} // End For Loop
					} // End If Statement
				} // End If Statement
            } // End If Statement
        } // End For Loop
	} // End remove_active_course()

	/**
	 * Add course link to order thank you and details pages
	 * @since  1.4.5
	 * @param  integer $order_id ID of order
	 * @return void
	 */
	public function course_link_from_order( $order_id ) {
		global $woocommerce, $woothemes_sensei;

		$order = new WC_Order( $order_id );

		if( 'completed' != $order->status ) return;

		$order_items = $order->get_items();

		$messages = array();

		foreach ( $order_items as $item ) {

            if ( $item['product_id'] > 0 ) {

				$user_id = get_post_meta( $order_id, '_customer_user', true );

				if( $user_id ) {

					// Get all courses for product
					$args = array(
						'posts_per_page' => -1,
						'post_type' => 'course',
						'meta_query' => array(
							array(
								'key' => '_course_woocommerce_product',
								'value' => $item['product_id']
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

							$messages[] = sprintf( __( 'View course: %1$s', 'woothemes-sensei' ), '<a href="' . esc_url( $permalink ) . '">' . $title . '</a>' );

							$update_course = $woothemes_sensei->woocommerce_course_update( $course->ID  );
						}
					}
				}
			}
		}

		foreach( $messages as $message ) {
			$woocommerce->add_message( $message, 'woocommerce' );
		}

		$woocommerce->show_messages();
	}

	/**
	 * Activate all purchased courses for user
	 * @since  1.4.8
	 * @param  integer $user_id User ID
	 * @return void
	 */
	public function activate_purchased_courses( $user_id = 0 ) {

		if( $user_id ) {

			if( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() ) {

				// Get all user's orders
				$order_args = array(
					'post_type' => 'shop_order',
					'posts_per_page' => -1,
					'meta_query' => array(
						array(
							'key' => '_customer_user',
							'value' => $user_id
						)
					),
					'tax_query' => array(
						array(
							'taxonomy' => 'shop_order_status',
							'field' => 'slug',
							'terms' => array( 'completed', 'processing' )
						)
					)
				);
				$orders = get_posts( $order_args );

				$product_ids = array();
				$order_ids = array();
				foreach( $orders as $post ) {

					// Only process each order once
					$processed = get_post_meta( $post->ID, 'sensei_products_processed', true );
					if( $processed && $processed == 'processed' ) {
						continue;
					}

					// Get course product IDs from order
					$order = new WC_Order( $post->ID );
					$items = $order->get_items();
					foreach( $items as $item ) {
						$product_id = $item['product_id'];
						$product_ids[] = $product_id;
					}

					$order_ids[] = $post->ID;
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
					);
					$courses = get_posts( $course_args );

					foreach( $courses as $course ) {

						// Ignore course if already completed
						$course_completed =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $course->ID, 'user_id' => $user_id, 'type' => 'sensei_course_end', 'field' => 'comment_content' ) );
						if( '' != $course_completed ) {
							continue;
						}

						// Ignore course if already started
						$course_started =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $course->ID, 'user_id' => $user_id, 'type' => 'sensei_course_start', 'field' => 'comment_content' ) );
						if( '' != $course_started ) {
							continue;
						}

						// Mark course as started by user
						WooThemes_Sensei_Utils::user_start_course( $user_id, $course->ID );
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

		if( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() ) {

			if( ! is_user_logged_in() ) return;
			if( ! isset( $post->ID ) ) return;

			$user_id = $current_user->ID;
			$course_id = $post->ID;
			$course_product_id = get_post_meta( $course_id, '_course_woocommerce_product', true );
			if( ! $course_product_id ) return;

			// Ignore course if already completed
			$course_completed =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $course_id, 'user_id' => $user_id, 'type' => 'sensei_course_end', 'field' => 'comment_content' ) );
			if( '' != $course_completed ) {
				return;
			}

			// Ignore course if already started
			$course_started =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $course_id, 'user_id' => $user_id, 'type' => 'sensei_course_start', 'field' => 'comment_content' ) );
			if( '' != $course_started ) {
				return;
			}

			// Get all user's orders
			$order_args = array(
				'post_type' => 'shop_order',
				'posts_per_page' => -1,
				'meta_query' => array(
					array(
						'key' => '_customer_user',
						'value' => $user_id
					)
				),
				'tax_query' => array(
					array(
						'taxonomy' => 'shop_order_status',
						'field' => 'slug',
						'terms' => array( 'completed', 'processing' )
					)
				)
			);
			$orders = get_posts( $order_args );

			foreach( $orders as $order_post ) {

				// Get course product IDs from order
				$order = new WC_Order( $order_post->ID );
				$items = $order->get_items();
				foreach( $items as $item ) {
					if( $item['product_id'] == $course_product_id ) {
						WooThemes_Sensei_Utils::user_start_course( $user_id, $course_id );
						return;
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

} // End Class
?>