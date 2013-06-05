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
		add_action( 'sensei_before_main_content', array( &$this, 'sensei_output_content_wrapper' ), 10 );
		add_action( 'sensei_after_main_content', array( &$this, 'sensei_output_content_wrapper_end' ), 10 );
		add_action( 'sensei_pagination', array( &$this, 'sensei_output_content_pagination' ), 10 );
		add_action( 'sensei_comments', array( &$this, 'sensei_output_comments' ), 10 );
		add_action( 'sensei_course_single_meta', 'course_single_meta', 10 );
		add_action( 'sensei_course_single_lessons', 'course_single_lessons', 10 );
		add_action( 'sensei_lesson_single_meta', 'lesson_single_meta', 10 );
		add_action( 'sensei_quiz_questions', 'quiz_questions', 10 );
		add_action( 'sensei_course_single_title', array( &$this, 'sensei_single_title' ), 10 );
		add_action( 'sensei_lesson_single_title', array( &$this, 'sensei_single_title' ), 10 );
		add_action( 'sensei_quiz_single_title', array( &$this, 'sensei_single_title' ), 10 );
		add_action( 'sensei_course_image', array( &$this, 'sensei_course_image' ), 10, 4 );
		add_action( 'sensei_lesson_image', array( &$this, 'sensei_lesson_image' ), 10, 4 );
		add_action( 'sensei_course_archive_header', array( &$this, 'sensei_course_archive_header' ), 10, 3 );
		add_action( 'sensei_lesson_archive_header', array( &$this, 'sensei_lesson_archive_header' ), 10, 3 );
		add_action( 'sensei_course_archive_course_title', array( &$this, 'sensei_course_archive_course_title' ), 10, 1 );
		add_action( 'sensei_lesson_archive_lesson_title', array( &$this, 'sensei_lesson_archive_lesson_title' ), 10 );
		// 1.2.1
		add_action( 'sensei_lesson_back_link', array( &$this, 'sensei_lesson_back_to_course_link' ), 10, 1 );
		add_action( 'sensei_quiz_back_link', array( &$this, 'sensei_quiz_back_to_lesson_link' ), 10, 1 );
		add_action( 'sensei_lesson_course_signup', array( &$this, 'sensei_lesson_course_signup_link' ), 10, 1 );
		add_action( 'sensei_complete_lesson', array( &$this, 'sensei_complete_lesson' ) );
		add_action( 'sensei_complete_course', array( &$this, 'sensei_complete_course' ) );
		add_action( 'sensei_complete_quiz', array( &$this, 'sensei_complete_quiz' ) );
		add_action( 'sensei_frontend_messages', array( &$this, 'sensei_frontend_messages' ) );
		add_action( 'sensei_lesson_video', array( &$this, 'sensei_lesson_video' ), 10, 1 );
		add_action( 'sensei_complete_lesson_button', array( &$this, 'sensei_complete_lesson_button' ) );
		add_action( 'sensei_lesson_quiz_meta', array( &$this, 'sensei_lesson_quiz_meta' ), 10, 2 );
		add_action( 'sensei_course_archive_meta', array( &$this, 'sensei_course_archive_meta' ) );
		add_action( 'sensei_single_main_content', array( &$this, 'sensei_single_main_content' ), 10 );
		add_action( 'sensei_course_archive_main_content', array( &$this, 'sensei_course_archive_main_content' ), 10 );
		add_action( 'sensei_lesson_archive_main_content', array( &$this, 'sensei_lesson_archive_main_content' ), 10 );
		add_action( 'sensei_course_category_main_content', array( &$this, 'sensei_course_category_main_content' ), 10 );
		add_action( 'sensei_no_permissions_main_content', array( &$this, 'sensei_no_permissions_main_content' ), 10 );
		add_action( 'sensei_login_form', array( &$this, 'sensei_login_form' ), 10 );
		add_action( 'sensei_quiz_action_buttons', array( &$this, 'sensei_quiz_action_buttons' ), 10 );
		add_action( 'sensei_lesson_meta', array( &$this, 'sensei_lesson_meta' ), 10 );
		add_action( 'sensei_course_meta', array( &$this, 'sensei_course_meta' ), 10 );
		add_action( 'sensei_course_meta_video', array( &$this, 'sensei_course_meta_video' ), 10 );
		add_action( 'sensei_woocommerce_in_cart_message', array( &$this, 'sensei_woocommerce_in_cart_message' ), 10 );
		add_action( 'sensei_course_start', array( &$this, 'sensei_course_start' ), 10 );
		add_filter( 'get_comments_number', array( &$this, 'sensei_lesson_comment_count' ), 1 );
		// 1.3.0
		add_action( 'sensei_quiz_question_type', 'quiz_question_type', 10 , 1);
		// Load post type classes
		$this->course = new WooThemes_Sensei_Course();
		$this->lesson = new WooThemes_Sensei_Lesson();
		// Scripts and Styles
		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_styles' ) );
		add_action( 'wp_head', array( &$this, 'enqueue_scripts' ) );
		// Menu Item filters
		add_filter( 'wp_nav_menu_items', array( &$this, 'sensei_nav_menu_items' ), 10, 2 );
		add_filter( 'wp_nav_menu_objects',  array( &$this, 'sensei_nav_menu_item_classes' ), 2, 20 );
		// Search Results filters
		add_filter( 'post_class', array( &$this, 'sensei_search_results_classes' ), 10 );
		// Comments Feed Actions
		add_filter( 'comment_feed_where', array( &$this, 'comments_rss_item_filter' ), 10, 1 );
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
			// My Courses tabs script
			wp_register_script( $this->token . '-user-dashboard', esc_url( $woothemes_sensei->plugin_url . 'assets/js/user-dashboard.js' ), array( 'jquery-ui-tabs' ), '1.3.0', true );
			wp_enqueue_script( $this->token . '-user-dashboard' );
			// Load the general script
			wp_enqueue_script( 'woosensei-general-frontend', $woothemes_sensei->plugin_url . 'assets/js/general-frontend.js', array( 'jquery' ), '1.3.0' );
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
		if ( ! $disable_styles ) {
			wp_register_style( $woothemes_sensei->token . '-frontend', $woothemes_sensei->plugin_url . 'assets/css/frontend.css', '', '1.3.3', 'screen' );
			wp_enqueue_style( $woothemes_sensei->token . '-frontend' );
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
		global $woothemes_sensei;
		$allow_comments = $woothemes_sensei->settings->settings[ 'lesson_comments' ];
		if ( is_user_logged_in() && $allow_comments ) {
			comments_template();
		} // End If Statement
	} // End sensei_output_comments()

	/**
	 * sensei_nav_menu_items function.
	 *
	 * Adds Courses, My Courses, and Login Logout links to navigation menu.
	 *
	 * @access public
	 * @param mixed $items
	 * @param mixed $args
	 * @return void
	 */
	function sensei_nav_menu_items( $items, $args ) {
		global $woothemes_sensei;
		$add_menu_items = $woothemes_sensei->settings->settings[ 'menu_items' ];
		// If setting is enabled
		if ( isset($add_menu_items) && $add_menu_items) {

			$course_page_id = intval( $woothemes_sensei->settings->settings[ 'course_page' ] );
			$my_account_page_id = intval( $woothemes_sensei->settings->settings[ 'my_course_page' ] );
			// Check for WooCommerce and Logged in User
			if ( is_user_logged_in() && !WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() ) {
				// Course Page Link
				if ( 0 < $course_page_id && !strstr( $items, get_permalink( $course_page_id ) ) ) {
					$classes = '';
					if ( is_page( $course_page_id ) ) {
						$classes = ' current-menu-item current_page_item';
					} // End If Statement
					$items .= apply_filters( 'sensei_course_page_menu_link', '<li class="courses' . $classes . '"><a href="'. get_permalink( $course_page_id ) .'">'.__('Courses', 'woothemes-sensei').'</a></li>' );
				} // End If Statement
				// Lesson Archive Link
				if ( !strstr( $items, get_post_type_archive_link( 'lesson' ) ) ) {
					$classes = '';
					if ( is_post_type_archive( 'lesson' ) ) {
						$classes = ' current-menu-item current_page_item';
					} // End If Statement
					$items .= apply_filters( 'sensei_lesson_archive_page_menu_link', '<li class="lessons' . $classes . '"><a href="'. get_post_type_archive_link( 'lesson' ) .'">'.__('Lessons', 'woothemes-sensei').'</a></li>' );
				} // End If Statement
				// My Courses Page Link
				if ( 0 < $my_account_page_id && is_user_logged_in() && !strstr( $items, get_permalink( $my_account_page_id ) ) ) {
					$classes = '';
					if ( is_page( $my_account_page_id ) ) {
						$classes = ' current-menu-item current_page_item';
					} // End If Statement
					$items .= apply_filters( 'sensei_my_account_page_menu_link', '<li class="my-account' . $classes . '"><a href="'. get_permalink( $my_account_page_id ) .'">'.__('My Courses', 'woothemes-sensei').'</a></li>' );
				} // End If Statement
				// Logout Link
				$items .= apply_filters( 'sensei_logout_menu_link', '<li class="logout"><a href="'. wp_logout_url( home_url() ) .'">'.__('Logout', 'woothemes-sensei').'</a></li>' );
			} else {
				// Course Page Link
				if ( 0 < $course_page_id && !strstr( $items, get_permalink( $course_page_id ) ) ) {
					$classes = '';
					if ( is_page( $course_page_id ) ) {
						$classes = ' current-menu-item current_page_item';
					} // End If Statement
					$items .= apply_filters( 'sensei_course_page_menu_link', '<li class="courses' . $classes . '"><a href="'. get_permalink( $course_page_id ) .'">'.__('Courses', 'woothemes-sensei').'</a></li>' );
				} // End If Statement
				// Lesson Archive Link
				if ( !strstr( $items, get_post_type_archive_link( 'lesson' ) ) ) {
					$classes = '';
					if ( is_post_type_archive( 'lesson' ) ) {
						$classes = ' current-menu-item current_page_item';
					} // End If Statement
					$items .= apply_filters( 'sensei_lesson_archive_page_menu_link', '<li class="lessons' . $classes . '"><a href="'. get_post_type_archive_link( 'lesson' ) .'">'.__('Lessons', 'woothemes-sensei').'</a></li>' );
				} // End If Statement
				// My Courses Page Link
				if ( 0 < $my_account_page_id && is_user_logged_in() && !strstr( $items, get_permalink( $my_account_page_id ) ) ) {
					$classes = '';
					if ( is_page( $my_account_page_id ) ) {
						$classes = ' current-menu-item current_page_item';
					} // End If Statement
					$items .= apply_filters( 'sensei_my_account_page_menu_link', '<li class="my-account' . $classes . '"><a href="'. get_permalink( $my_account_page_id ) .'">'.__('My Courses', 'woothemes-sensei').'</a></li>' );
				} // End If Statement
				// Logout Link
				if ( is_user_logged_in() && !strstr( $items, wp_logout_url( home_url() ) ) ) {
					$items .= apply_filters( 'sensei_logout_menu_link', '<li class="logout"><a href="'. wp_logout_url( home_url() ) .'">'.__('Logout', 'woothemes-sensei').'</a></li>' );
				} else {
					// Login Link
					$items .= apply_filters( 'sensei_login_menu_link', '<li class="login"><a href="'. wp_login_url( home_url() ) .'">'.__('Login', 'woothemes-sensei').'</a></li>' );
				} // End If Statement
			} // End If Statement

		} // End If Statement

	    return apply_filters( 'sensei_custom_menu_links', $items );
	} // End sensei_nav_menu_items()


	/**
	 * sensei_nav_menu_item_classes function.
	 *
	 * Fix active class in nav for shop page.
	 *
	 * @access public
	 * @param mixed $menu_items
	 * @param mixed $args
	 * @return void
	 */
	function sensei_nav_menu_item_classes( $menu_items, $args ) {

		global $woothemes_sensei;
		$course_page_id = intval( $woothemes_sensei->settings->settings[ 'course_page' ] );
		$my_account_page_id = intval( $woothemes_sensei->settings->settings[ 'my_course_page' ] );

		foreach ( (array) $menu_items as $key => $menu_item ) {

			$classes = (array) $menu_item->classes;

			// Handle Singular Pages
			$post_types = array( 'course', 'lesson', 'quiz', 'question' );
			if ( 0 < $course_page_id && ( is_page( $course_page_id ) || is_singular( $post_types ) ) && $course_page_id == $menu_item->object_id ) {
				$menu_items[$key]->current = true;
				$classes[] = 'current-menu-item';
				$classes[] = 'current_page_item';
			// Set active state if this is the my courses page link
			} elseif ( 0 < $my_account_page_id && is_page( $my_account_page_id ) && $my_account_page_id == $menu_item->object_id ) {
				$menu_items[$key]->current = true;
				$classes[] = 'current-menu-item';
				$classes[] = 'current_page_item';

			} // End If Statement

			$menu_items[$key]->classes = array_unique( $classes );

		} // End For Loop

		return $menu_items;
	} // End sensei_nav_menu_item_classes()

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
		?><header><h1><?php the_title(); ?></h1></header><?php
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
	function sensei_lesson_image( $lesson_id, $width = '100', $height = '100', $return = false ) {
		if ( $return ) {
			return $this->lesson->lesson_image( $lesson_id, $width, $height );
		} else {
			echo $this->lesson->lesson_image( $lesson_id, $width, $height );
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
				$html .= $before_html . __( 'New Courses', 'woothemes-sensei' ) . $after_html;
				break;
			case 'featuredcourses':
				$html .= $before_html . __( 'Featured Courses', 'woothemes-sensei' ) . $after_html;
				break;
			case 'freecourses':
				$html .= $before_html . __( 'Free Courses', 'woothemes-sensei' ) . $after_html;
				break;
			case 'paidcourses':
				$html .= $before_html . __( 'Paid Courses', 'woothemes-sensei' ) . $after_html;
				break;
			default:
				$html .= $before_html . __( 'Courses', 'woothemes-sensei' ) . $after_html;
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
		$html .= $before_html . __( 'Lessons Archive', 'woothemes-sensei' ) . $after_html;
		echo apply_filters( 'lesson_archive_title', $html );
	} // sensei_course_archive_header()

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
    		<?php _e( 'Back to ', 'woothemes-sensei' ); ?><a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>" title="<?php echo esc_attr( __( 'Back to the course', 'woothemes-sensei' ) ); ?>"><?php echo get_the_title( $course_id ); ?></a>
    	</section><?php
    	} // End If Statement
	} // End sensei_lesson_back_to_course_link()

	public function sensei_quiz_back_to_lesson_link( $quiz_id = 0 ) {
		if ( 0 < intval( $quiz_id ) ) {
		?><section class="lesson-course">
    		<?php _e( 'Back to ', 'woothemes-sensei' ); ?><a href="<?php echo esc_url( get_permalink( $quiz_id ) ); ?>" title="<?php echo esc_attr( __( 'Back to the lesson', 'woothemes-sensei' ) ); ?>"><?php echo get_the_title( $quiz_id ); ?></a>
		</section><?php
		} // End If Statement
	} // End sensei_quiz_back_to_lesson_link()

	public function sensei_lesson_course_signup_link( $course_id = 0 ) {
		if ( 0 < intval( $course_id ) ) {
		?><section class="lesson-meta"><?php
			$course_link = '<a href="' . esc_url( get_permalink( $course_id ) ) . '">' . __( 'course', 'woothemes-sensei' ) . '</a>';
			$wc_post_id = get_post_meta( $course_id, '_course_woocommerce_product',true );
			if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() && ( 0 < $wc_post_id ) ) { ?>
				<div class="woo-sc-box info"><?php echo sprintf( __( 'Please purchase the %1$s before starting the Lesson.', 'woothemes-sensei' ), '<a href="' . esc_url( get_permalink( $course_id ) ) . '" title="' . esc_attr( __( 'Sign Up', 'woothemes-sensei' ) ) . '">' . __( 'course', 'woothemes-sensei' ) . '</a>' ); ?></div>
			<?php } else { ?>
				<div class="woo-sc-box info"><?php echo sprintf( __( 'Please Sign Up for the %1$s before starting the Lesson.', 'woothemes-sensei' ), '<a href="' . esc_url( get_permalink( $course_id ) ) . '" title="' . esc_attr( __( 'Sign Up', 'woothemes-sensei' ) ) . '">' . __( 'course', 'woothemes-sensei' ) . '</a>' ); ?></div>
			<?php } // End If Statement ?>
    	</section><?php
    	} // End If Statement
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
		        case __( 'Complete Lesson', 'woothemes-sensei' ):

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
	                // Get Lesson Grading Setting
	                if ( $activity_logged && 'passed' == $woothemes_sensei->settings->settings[ 'lesson_completion' ] ) {
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
	                } // End If Statement
		            break;
		        case __( 'Reset Lesson', 'woothemes-sensei' ):
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
		            $this->messages = '<div class="woo-sc-box note">' . __( 'Lesson Reset Successfully.', 'woothemes-sensei' ) . '</div>';
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
		    	case __( 'Mark as Complete', 'woothemes-sensei' ):

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
									} // End For Loop
								} // End If Statement
							} // End If Statement
						} // End For Loop
		    		} // End If Statement

					// Success message
		    		if ( $dataset_changes ) {
		    			$this->messages = '<header class="archive-header"><div class="woo-sc-box tick">' . sprintf( __( '%1$s marked as complete.', 'woothemes-sensei' ), get_the_title( $sanitized_course_id ) ) . '</div></header><div class="fix"></div>';
		    		} // End If Statement

		    		break;
		    	case __( 'Delete Course', 'woothemes-sensei' ):

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
		    		if ( isset( $lesson_quizzes ) && 0 < count($lesson_quizzes) )  {
		    			foreach ($course_lessons as $lesson_item){
		    				// Check for lesson complete
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
		    		} // End If Statement
		    		// Success message
		    		if ( $dataset_changes ) {
		    			$this->messages = '<header class="archive-header"><div class="woo-sc-box tick">' . sprintf( __( '%1$s deleted.', 'woothemes-sensei' ), get_the_title( $sanitized_course_id ) ) . '</div></header><div class="fix"></div>';
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

		// See if we must randomize questions
		$random_quiz_questions = $woothemes_sensei->settings->settings[ 'quiz_randomize_questions' ];
		if ( isset( $random_quiz_questions ) && ( $random_quiz_questions ) ) {
		    // Get Quiz Questions
		    $lesson_quiz_questions = $woothemes_sensei->frontend->lesson->lesson_quiz_questions( $post->ID, 'publish', 'rand', '' );
		} else {
		    // Get Quiz Questions
		    $lesson_quiz_questions = $woothemes_sensei->frontend->lesson->lesson_quiz_questions( $post->ID );
		}

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

		// Handle Quiz Completion
		if ( isset( $_POST['quiz_complete'] ) && wp_verify_nonce( $_POST[ 'woothemes_sensei_complete_quiz_noonce' ], 'woothemes_sensei_complete_quiz_noonce' ) ) {

		    $sanitized_submit = esc_html( $_POST['quiz_complete'] );

		    $answers_array = array();
		    $activity_logged = false;

		    switch ($sanitized_submit) {
		    	case __( 'Complete Quiz', 'woothemes-sensei' ):

		    		$activity_logged = WooThemes_Sensei_Utils::sensei_start_lesson( $quiz_lesson );

		    		// Save Quiz Answers
		    		if( isset( $_POST['sensei_question'] ) ) {
			    		$activity_logged = WooThemes_Sensei_Utils::sensei_save_quiz_answers( $_POST['sensei_question'] );
			    	}

					if ( $activity_logged ) {

						// Grade quiz
		    			$grade = WooThemes_Sensei_Utils::sensei_grade_quiz_auto( $post->ID, $_POST['sensei_question'], count( $lesson_quiz_questions ), $quiz_grade_type );

						// Get Lesson Grading Setting
						if ( 'auto' == $quiz_grade_type && 'passed' == $woothemes_sensei->settings->settings[ 'lesson_completion' ] ) {
							$lesson_prerequisite = abs( round( doubleval( get_post_meta( $post->ID, '_quiz_passmark', true ) ), 2 ) );
							if ( $lesson_prerequisite <= $grade ) {
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
						} // End If Statement
					} else {
						// Something broke
					} // End If Statement

					break;
		    	case __( 'Save Quiz', 'woothemes-sensei' ):

		    		$activity_logged = WooThemes_Sensei_Utils::sensei_start_lesson( $quiz_lesson );

			    	if( isset( $_POST['sensei_question'] ) ) {
			    		$activity_logged = WooThemes_Sensei_Utils::sensei_save_quiz_answers( $_POST['sensei_question'] );
			    	}

					$this->messages = '<div class="woo-sc-box note">' . __( 'Quiz Saved Successfully.', 'woothemes-sensei' ) . '</div>';

					break;
		    	case __( 'Reset Quiz', 'woothemes-sensei' ):
		    		// Remove existing user quiz meta
		    		$grade = '';
		    		$answers_array = array();
		    		// Check for quiz grade
		    		$delete_grades = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $post->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_quiz_grade' ) );
		    		// Check for quiz answers
		    		$delete_answers = WooThemes_Sensei_Utils::sensei_delete_quiz_answers( $post->ID, $current_user->ID );
		    		// Check for lesson complete
		    		$delete_lesson_completion = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $quiz_lesson, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_end' ) );
		    		// Check for course complete
		    		$course_id = absint( get_post_meta( $quiz_lesson, '_lesson_course' ,true ) );
		    		$delete_course_completion = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $course_id, 'user_id' => $current_user->ID, 'type' => 'sensei_course_end' ) );
		    		$this->messages = '<div class="woo-sc-box note">' . __( 'Quiz Reset Successfully.', 'woothemes-sensei' ) . '</div>';
		    		break;
		    	default:
		    		// Nothing
		    		break;

		    } // End Switch Statement

			// Get latest quiz answers and grades
			$user_quizzes = $this->sensei_get_user_quiz_answers( $post->ID );
		    $user_quiz_grade =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $post->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_quiz_grade', 'field' => 'comment_content' ) );
			if ( '' == $user_quiz_grade ) {
				$user_quiz_grade = '';
			} // End If Statement

			if ( ! is_array($user_quizzes) ) { $user_quizzes = array(); }

			// Check again that the lesson is complete
			$quiz_lesson = absint( get_post_meta( $post->ID, '_quiz_lesson', true ) );
			$user_lesson_end = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $quiz_lesson, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_end', 'field' => 'comment_content' ) );
			$user_lesson_complete = false;
			if ( '' != $user_lesson_end ) {
				$user_lesson_complete = true;
			} // End If Statement

		} // End If Statement

		// Build frontend data object
		$this->data->user_quizzes = $user_quizzes;
		$this->data->user_quiz_grade = $user_quiz_grade;
		$this->data->quiz_lesson = $quiz_lesson;
		$this->data->quiz_grade_type = $quiz_grade_type;
		$this->data->user_lesson_end = $user_lesson_end;
		$this->data->user_lesson_complete = $user_lesson_complete;
		$this->data->lesson_quiz_questions = $lesson_quiz_questions;

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
		global $woothemes_sensei;
		if ( isset( $woothemes_sensei->settings->settings[ 'lesson_complete_button' ] ) && $woothemes_sensei->settings->settings[ 'lesson_complete_button' ] ) {
		?>
		<form method="POST" action="<?php echo esc_url( get_permalink() ); ?>">
            <input type="hidden" name="<?php echo esc_attr( 'woothemes_sensei_complete_lesson_noonce' ); ?>" id="<?php echo esc_attr( 'woothemes_sensei_complete_lesson_noonce' ); ?>" value="<?php echo esc_attr( wp_create_nonce( 'woothemes_sensei_complete_lesson_noonce' ) ); ?>" />
            <span><input type="submit" name="quiz_complete" class="quiz-submit complete" value="<?php _e( 'Complete Lesson', 'woothemes-sensei' ); ?>"/></span>
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
		if ( 0 < count($lesson_quizzes) && is_user_logged_in() && sensei_has_user_started_course( $lesson_course_id, $user_id ) ) { ?>
        	<header>
            <?php $no_quiz_count = 0; ?>
        	<?php foreach ($lesson_quizzes as $quiz_item){
        		// Get quiz grade type
        		$quiz_grade_type = get_post_meta( $quiz_item->ID, '_quiz_grade_type', true );
                // Check quiz grade
        		$user_quiz_grade =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $quiz_item->ID, 'user_id' => $user_id, 'type' => 'sensei_quiz_grade', 'field' => 'comment_content' ) );
				if ( '' == $user_quiz_grade ) {
					$user_quiz_grade = '';
				} // End If Statement
        		$question_count = 0;
                if ( 0 < $quiz_item->ID ) {
                	$questions_array = WooThemes_Sensei_Utils::lesson_quiz_questions( $quiz_item->ID );
                    $question_count = count( $questions_array );
                } // End If Statement
        		// Check if Lesson is complete
        	    if ( sensei_has_user_completed_lesson( $post_id, $user_id ) ) { ?>
        	    	<?php
        	    	if ( 0 < $question_count ) { ?>
		    	    	<?php if( $quiz_grade_type == 'auto' ) { ?>
		    	    		<div class="woo-sc-box tick"><?php echo sprintf( __( 'You have completed this lesson quiz with a grade of %d%%', 'woothemes-sensei' ), round( $user_quiz_grade ) ); ?> <a href="<?php echo esc_url( get_permalink( $quiz_item->ID ) ); ?>" title="<?php echo esc_attr( __( 'View the Lesson Quiz', 'woothemes-sensei' ) ); ?>" class="view-quiz"><?php _e( 'View the Lesson Quiz', 'woothemes-sensei' ); ?></a></div>
		    	    	<?php } else { ?>
		    	    		<div class="woo-sc-box info"><?php echo sprintf( __( 'You have completed this lesson quiz and it will be graded soon.', 'woothemes-sensei' ), round( $user_quiz_grade ) ); ?> <a href="<?php echo esc_url( get_permalink( $quiz_item->ID ) ); ?>" title="<?php echo esc_attr( __( 'View the Lesson Quiz', 'woothemes-sensei' ) ); ?>" class="view-quiz"><?php _e( 'View the Lesson Quiz', 'woothemes-sensei' ); ?></a></div>
		    	    	<?php } // End If Statement ?>
                	<?php } else { ?>
                		<div class="woo-sc-box tick"><?php echo __( 'You have completed this lesson.', 'woothemes-sensei' ); ?></div>
                	<?php } // End If Statement ?>
                    <?php sensei_reset_lesson_button(); ?>
        	    <?php } else {
                    if ( 0 < $question_count ) {
                        if ( $lesson_prerequisite > 0) {
                            if ( sensei_has_user_completed_prerequisite_lesson( $lesson_prerequisite, $user_id ) ) { ?>
                                <a class="button" href="<?php echo esc_url( get_permalink( $quiz_item->ID ) ); ?>" title="<?php echo esc_attr( __( 'Take the Lesson Quiz', 'woothemes-sensei' ) ); ?>"><?php _e( 'Take the Lesson Quiz',    'woothemes-sensei' ); ?></a>
                                <?php do_action( 'sensei_complete_lesson_button' ); ?>
                            <?php } else {
                                echo sprintf( __( 'You must first complete %1$s before taking this Lesson\'s Quiz', 'woothemes-sensei' ), '<a href="' . esc_url( get_permalink( $lesson_prerequisite ) ) . '" title="' . esc_attr(  sprintf( __( 'You must first complete: %1$s', 'woothemes-sensei' ), get_the_title( $lesson_prerequisite ) ) ) . '">' . get_the_title( $lesson_prerequisite ). '</a>' );
                            } // End If Statement
                        } else { ?>
                            <a class="button" href="<?php echo esc_url( get_permalink( $quiz_item->ID ) ); ?>" title="<?php echo esc_attr( __( 'Take the Lesson Quiz', 'woothemes-sensei' ) ); ?>"><?php _e( 'Take the Lesson Quiz', 'woothemes-sensei'    ); ?></a>
                           <?php do_action( 'sensei_complete_lesson_button' ); ?>
                        <?php } // End If Statement
                    } else {
                        $no_quiz_count = sensei_no_quiz_message( $no_quiz_count );
                        do_action( 'sensei_complete_lesson_button' );
                    } // End If Statement
        	    } // End If Statement
        	} // End For Loop ?>
        	</header>
        <?php } elseif( 0 < count($lesson_quizzes) && $woothemes_sensei->access_settings() ) { ?>
        	<header>
        		<?php foreach ($lesson_quizzes as $quiz_item){ ?>
        		<a class="button" href="<?php echo esc_url( get_permalink( $quiz_item->ID ) ); ?>" title="<?php echo esc_attr( __( 'View the Lesson Quiz', 'woothemes-sensei' ) ); ?>"><?php _e( 'View the Lesson Quiz',    'woothemes-sensei' ); ?></a>
        		<?php } // End For Loop ?>
        	</header>
        <?php } // End If Statement
	} // End sensei_lesson_quiz_meta()

	public function sensei_course_archive_meta() {
		global $woothemes_sensei, $post;
		// Meta data
		$post_id = get_the_ID();
		$post_title = get_the_title();
		$author_display_name = get_the_author();
		$author_id = get_the_author_meta('ID');
		$category_output = get_the_term_list( $post_id, 'course-category', '', ', ', '' );
		?><section class="entry">
        	<p class="sensei-course-meta">
           	<?php if ( isset( $woothemes_sensei->settings->settings[ 'course_author' ] ) && ( $woothemes_sensei->settings->settings[ 'course_author' ] ) ) { ?>
		   	<span class="course-author"><?php _e( 'by ', 'woothemes-sensei' ); ?><?php the_author_link(); ?></span>
		   	<?php } // End If Statement ?>
		   	<span class="course-lesson-count"><?php echo $woothemes_sensei->post_types->course->course_author_lesson_count( $author_id, $post_id ) . '&nbsp;' . __( 'Lectures', 'woothemes-sensei' ); ?></span>
		   	<?php if ( '' != $category_output ) { ?>
		   	<span class="course-category"><?php echo sprintf( __( 'in %s', 'woothemes-sensei' ), $category_output ); ?></span>
		   	<?php } // End If Statement ?>
		   	<?php sensei_simple_course_price( $post_id ); ?>
        	</p>
        	<p><?php echo apply_filters( 'get_the_excerpt', $post->post_excerpt ); ?></p>
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
			?><p><?php _e( 'No courses found which match your selection.', 'woothemes-sensei' ); ?></p><?php
		} // End If Statement
	} // End sensei_course_archive_main_content()

	public function sensei_lesson_archive_main_content() {
		if ( have_posts() ) {
			$this->sensei_get_template( 'loop-lesson.php' );
		} else {
			?><p><?php _e( 'No lessons found which match your selection.', 'woothemes-sensei' ); ?></p><?php
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
	    	    <div class="fix"></div>
	    	    <?php while ( have_posts() ) { the_post(); ?>
				<article class="<?php echo join( ' ', get_post_class( array( 'course', 'post' ), get_the_ID() ) ); ?>">
	    			<?php do_action( 'sensei_course_image', get_the_ID() ); ?>
	    			<?php do_action( 'sensei_course_archive_course_title', $post ); ?>
	    			<?php do_action( 'sensei_course_archive_meta' ); ?>
	    		</article>
	    		<div class="fix"></div>
	    		<?php } // End While Loop ?>
	    	</section>
		<?php } else { ?>
			<p><?php _e( 'No courses found which match your selection.', 'woothemes-sensei' ); ?></p>
		<?php } // End If Statement
	} // End sensei_course_category_main_content()

	public function sensei_login_form() {
		?><div id="my-courses">
			<?php
			// Display Login Form and Registration Link
			wp_login_form( array( 'redirect' => get_permalink() ) );
			wp_register();
			?>
		</div><?php
	} // End sensei_login_form()

	public function sensei_quiz_action_buttons() {
		global $post, $current_user;
		$lesson_id = get_post_meta( $post->ID, '_quiz_lesson', true );
		$lesson_course_id = get_post_meta( $lesson_id, '_lesson_course', true );
		if ( is_user_logged_in() && sensei_has_user_started_course( $lesson_course_id, $current_user->ID ) ) {
			global $woothemes_sensei;
			// Get Reset Settings
			$reset_quiz_allowed = $woothemes_sensei->settings->settings[ 'quiz_reset_allowed' ]; ?>
			<input type="hidden" name="<?php echo esc_attr( 'woothemes_sensei_complete_quiz_noonce' ); ?>" id="<?php echo esc_attr( 'woothemes_sensei_complete_quiz_noonce' ); ?>" value="<?php echo esc_attr(  wp_create_nonce( 'woothemes_sensei_complete_quiz_noonce' ) ); ?>" />
		    <?php if ( ( isset( $this->data->user_lesson_complete ) && !$this->data->user_lesson_complete ) ) { ?>
		 	<span><input type="submit" name="quiz_complete" class="quiz-submit complete" value="<?php _e( 'Complete Quiz', 'woothemes-sensei' ); ?>"/></span>
		 	<span><input type="submit" name="quiz_complete" class="quiz-submit save" value="<?php _e( 'Save Quiz', 'woothemes-sensei' ); ?>"/></span>
		     <?php } // End If Statement ?>
	          <?php if ( isset( $reset_quiz_allowed ) && $reset_quiz_allowed ) { ?>
		 	   <span><input type="submit" name="quiz_complete" class="quiz-submit reset" value="<?php _e( 'Reset Quiz', 'woothemes-sensei' ); ?>"/></span>
		     <?php } ?>
        <?php }
	} // End sensei_quiz_action_buttons()

	public function sensei_lesson_meta( $post_id = 0 ) {
		global $woothemes_sensei;
		if ( 0 < intval( $post_id ) ) {
		$lesson_course_id = absint( get_post_meta( $post_id, '_lesson_course', true ) );
		?><section class="entry">
            <p class="sensei-course-meta">
			    <?php if ( isset( $woothemes_sensei->settings->settings[ 'lesson_author' ] ) && ( $woothemes_sensei->settings->settings[ 'lesson_author' ] ) ) { ?>
			    <span class="course-author"><?php _e( 'by ', 'woothemes-sensei' ); ?><?php the_author_link(); ?></span>
			    <?php } ?>
                <?php if ( 0 < intval( $lesson_course_id ) ) { ?>
                <span class="lesson-course"><?php echo '&nbsp;' . sprintf( __( 'Part of: %s', 'woothemes-sensei' ), '<a href="' . esc_url( get_permalink( $lesson_course_id ) ) . '" title="' . esc_attr( __( 'View course', 'woothemes-sensei' ) ) . '"><em>' . get_the_title( $lesson_course_id ) . '</em></a>' ); ?></span>
                <?php } ?>
            </p>
            <p><?php the_excerpt(); ?></p>
		</section><?php
		} // End If Statement
	} // sensei_lesson_meta()

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
			$this->data->is_user_taking_course = false;
			if ( $activity_logged ) {
				$this->data->is_user_taking_course = true;
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
						} // End If Statement
		    		} // End If Statement
				} // End If Statement
				// Success message
		   		if ( $completed_course ) { ?>
		   			<div class="status completed"><?php _e( 'Completed', 'woothemes-sensei' ); ?></div>
		   		<?php } else { ?>
		    		<div class="status in-progress"><?php _e( 'In Progress', 'woothemes-sensei' ); ?></div>
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
				echo '<div class="woo-sc-box info">' . sprintf(  __('You have already added this Course to your cart. Please %1$s to access the course.', 'woothemes-sensei') . '</div>', '<a class="cart-complete" href="' . $woocommerce->cart->get_checkout_url() . '" title="' . __('complete the purchase', 'woothemes-sensei') . '">' . __('complete the purchase', 'woothemes-sensei') . '</a>' );
			} // End If Statement
		} // End If Statement

	} // End sensei_woocommerce_in_cart_message()

	public function sensei_lesson_comment_count( $count ) {
		global $post, $current_user;
		if ( is_singular( 'lesson' ) ) {
			$lesson_comments_start = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $post->ID, 'type' => 'sensei_lesson_start'/*, 'user_id' => $current_user->ID*/ ), true );
			$lesson_comments_end = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $post->ID, 'type' => 'sensei_lesson_end'/*, 'user_id' => $current_user->ID*/ ), true );
			return $count - intval( count( $lesson_comments_start ) ) - intval( count( $lesson_comments_end ) );
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
		if ( is_comment_feed() ) {
			$pieces .= " AND comment_type NOT LIKE 'sensei_%' ";
		} // End If Statement
		return $pieces;
	} // End comments_rss_item_filter()

} // End Class
?>