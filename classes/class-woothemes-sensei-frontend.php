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
 */
class WooThemes_Sensei_Frontend {
	public $token;
	public $course;
	public $lesson;

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
		if ( !$disable_js ) {
			// My Courses tabs script
			wp_register_script( $this->token . '-user-dashboard', esc_url( $woothemes_sensei->plugin_url . 'assets/js/user-dashboard.js' ), array( 'jquery-ui-tabs' ), '1.0.0', true );
			wp_enqueue_script( $this->token . '-user-dashboard' );
			// Load the general script
			wp_enqueue_script( 'woosensei-general-frontend', $woothemes_sensei->plugin_url . 'assets/js/general-frontend.js', array( 'jquery' ), '1.0.0' );
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
		if ( !$disable_styles ) {
			wp_register_style( $woothemes_sensei->token . '-frontend', $woothemes_sensei->plugin_url . 'assets/css/frontend.css', '', '1.0.0', 'screen' );
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
		if ( !$template && $name && file_exists( $woothemes_sensei->plugin_path() . "/templates/{$slug}-{$name}.php" ) )
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
		return apply_filters('sensei_locate_template', $template, $template_name, $template_path);
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
					$items .= '<li class="courses' . $classes . '"><a href="'. get_permalink( $course_page_id ) .'">'.__('Courses', 'woothemes-sensei').'</a></li>';
				} // End If Statement
				// Lesson Archive Link
				if ( !strstr( $items, get_post_type_archive_link( 'lesson' ) ) ) {
					$classes = '';
					if ( is_post_type_archive( 'lesson' ) ) {
						$classes = ' current-menu-item current_page_item';
					} // End If Statement
					$items .= '<li class="lessons' . $classes . '"><a href="'. get_post_type_archive_link( 'lesson' ) .'">'.__('Lessons', 'woothemes-sensei').'</a></li>';
				} // End If Statement
				// My Courses Page Link
				if ( 0 < $my_account_page_id && is_user_logged_in() && !strstr( $items, get_permalink( $my_account_page_id ) ) ) {
					$classes = '';
					if ( is_page( $my_account_page_id ) ) {
						$classes = ' current-menu-item current_page_item';
					} // End If Statement
					$items .= '<li class="my-account' . $classes . '"><a href="'. get_permalink( $my_account_page_id ) .'">'.__('My Courses', 'woothemes-sensei').'</a></li>';
				} // End If Statement
				// Logout Link
				$items .= '<li class="logout"><a href="'. wp_logout_url( home_url() ) .'">'.__('Logout', 'woothemes-sensei').'</a></li>';
			} else {
				// Course Page Link
				if ( 0 < $course_page_id && !strstr( $items, get_permalink( $course_page_id ) ) ) {
					$classes = '';
					if ( is_page( $course_page_id ) ) {
						$classes = ' current-menu-item current_page_item';
					} // End If Statement
					$items .= '<li class="courses' . $classes . '"><a href="'. get_permalink( $course_page_id ) .'">'.__('Courses', 'woothemes-sensei').'</a></li>';
				} // End If Statement
				// Lesson Archive Link
				if ( !strstr( $items, get_post_type_archive_link( 'lesson' ) ) ) {
					$classes = '';
					if ( is_post_type_archive( 'lesson' ) ) {
						$classes = ' current-menu-item current_page_item';
					} // End If Statement
					$items .= '<li class="lessons' . $classes . '"><a href="'. get_post_type_archive_link( 'lesson' ) .'">'.__('Lessons', 'woothemes-sensei').'</a></li>';
				} // End If Statement
				// My Courses Page Link
				if ( 0 < $my_account_page_id && is_user_logged_in() && !strstr( $items, get_permalink( $my_account_page_id ) ) ) {
					$classes = '';
					if ( is_page( $my_account_page_id ) ) {
						$classes = ' current-menu-item current_page_item';
					} // End If Statement
					$items .= '<li class="my-account' . $classes . '"><a href="'. get_permalink( $my_account_page_id ) .'">'.__('My Courses', 'woothemes-sensei').'</a></li>';
				} // End If Statement
				// Logout Link
				if ( is_user_logged_in() && !strstr( $items, wp_logout_url( home_url() ) ) ) {
					$items .= '<li class="logout"><a href="'. wp_logout_url( home_url() ) .'">'.__('Logout', 'woothemes-sensei').'</a></li>';
				} else {
					// Login Link
					$items .= '<li class="login"><a href="'. wp_login_url( home_url() ) .'">'.__('Login', 'woothemes-sensei').'</a></li>';
				} // End If Statement
			} // End If Statement
		
		} // End If Statement
		
	    return $items;
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
	
} // End Class
?>