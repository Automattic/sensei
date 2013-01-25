<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Post Types Class
 *
 * All functionality pertaining to the post types and taxonomies in Sensei.
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
 * - load_posttype_objects()
 * - setup_course_post_type()
 * - setup_lesson_post_type()
 * - setup_quiz_post_type()
 * - setup_question_post_type()
 * - setup_quiz_type_taxonomy()
 * - setup_post_type_labels_base()
 * - create_post_type_labels()
 * - setup_post_type_messages()
 * - create_post_type_messages()
 * - enter_title_here()
 */
class WooThemes_Sensei_PostTypes {
	public $token;
	public $slider_labels;
	
	/**
	 * Constructor
	 * @since  1.0.0
	 * @return  void
	 */
	public function __construct () {
	
		// Setup Post Types
		$this->labels = array();
		$this->setup_post_type_labels_base();
		add_action( 'init', array( &$this, 'setup_course_post_type' ), 100 );
		add_action( 'init', array( &$this, 'setup_lesson_post_type' ), 100 );
		add_action( 'init', array( &$this, 'setup_quiz_post_type' ), 100 );
		add_action( 'init', array( &$this, 'setup_question_post_type' ), 100 );
		// Setup Taxonomies
		add_action( 'init', array( &$this, 'setup_quiz_type_taxonomy' ), 100 );
		// Load Post Type Objects
		$this->load_posttype_objects( array( 'course' => 'Course', 'lesson' => 'Lesson', 'quiz' => 'Quiz', 'question' => 'Question' ) );
		// Admin functions
		if ( is_admin() ) {
			global $pagenow;
			if ( ( $pagenow == 'post.php' || $pagenow == 'post-new.php' ) ) {
				add_filter( 'enter_title_here', array( &$this, 'enter_title_here' ), 10 );
				add_filter( 'post_updated_messages', array( &$this, 'setup_post_type_messages' ) );
			} // End If Statement
		} // End If Statement
	} // End __construct()

	
	/**
	 * load_posttype_objects function.
	 * Dynamically loads post type objects for meta boxes on backend
	 * @access public
	 * @param array $posttypes (default: array())
	 * @return void
	 */
	public function load_posttype_objects( $posttypes = array() ) {
	
		foreach ( $posttypes as $posttype_token => $posttype_name ) {
			
			// Load the files
			require_once( 'class-woothemes-sensei-'.$posttype_token.'.php' );
			$class_name = 'WooThemes_Sensei_' . $posttype_name;
			$this->$posttype_token = new $class_name();
			$this->$posttype_token->token = $posttype_token;
			
		} // End For Loop
	
	} // End load_posttype_objects
	
	/**
	 * Setup the "course" post type, it's admin menu item and the appropriate labels and permissions.
	 * @since  1.0.0
	 * @uses  global $woothemes_sensei
	 * @return void
	 */
	public function setup_course_post_type () {
		global $woothemes_sensei;

		$args = array(
		    'labels' => $this->create_post_type_labels( 'course', $this->labels['course']['singular'], $this->labels['course']['plural'], $this->labels['course']['menu'] ),
		    'public' => true,
		    'publicly_queryable' => true,
		    'show_ui' => true, 
		    'show_in_menu' => 'edit.php?post_type=lesson', 
		    'query_var' => true,
		    'rewrite' => array( 'slug' => esc_attr( apply_filters( 'sensei_course_slug', 'course' ) ) , 'with_front' => true, 'feeds' => true, 'pages' => true ),
		    'capability_type' => 'post',
		    'has_archive' => true, 
		    'hierarchical' => false,
		    'menu_position' => 20, // Below "Pages"
		    'menu_icon' => esc_url( $woothemes_sensei->plugin_url . 'assets/images/icon_course_16.png' ), 
		    'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail' )
		);

		register_post_type( 'course', $args );
	} // End setup_course_post_type()
	
	/**
	 * Setup the "lesson" post type, it's admin menu item and the appropriate labels and permissions.
	 * @since  1.0.0
	 * @uses  global $woothemes_sensei
	 * @return void
	 */
	public function setup_lesson_post_type () {
		global $woothemes_sensei;
		
		$supports_array = array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' );
		$allow_comments = $woothemes_sensei->settings->settings[ 'lesson_comments' ];
		if ( $allow_comments ) {
			array_push( $supports_array, 'comments' );
		} // End If Statement
		
		$args = array(
		    'labels' => $this->create_post_type_labels( 'lesson', $this->labels['lesson']['singular'], $this->labels['lesson']['plural'], $this->labels['lesson']['menu'] ),
		    'public' => true,
		    'publicly_queryable' => true,
		    'show_ui' => true, 
		    'show_in_menu' => true, 
		    'query_var' => true,
		    'rewrite' => array( 'slug' => esc_attr( apply_filters( 'sensei_lesson_slug', 'lesson' ) ) , 'with_front' => true, 'feeds' => true, 'pages' => true ),
		    'capability_type' => 'post',
		    'has_archive' => true, 
		    'hierarchical' => false,
		    'menu_position' => 20, // Below "Pages"
		    'menu_icon' => esc_url( $woothemes_sensei->plugin_url . 'assets/images/icon_course_16.png' ), 
		    'supports' => $supports_array
		);

		register_post_type( 'lesson', $args );
	} // End setup_lesson_post_type()
	
	/**
	 * Setup the "quiz" post type, it's admin menu item and the appropriate labels and permissions.
	 * @since  1.0.0
	 * @uses  global $woothemes_sensei
	 * @return void
	 */
	public function setup_quiz_post_type () {
		global $woothemes_sensei;

		$args = array(
		    'labels' => $this->create_post_type_labels( 'quiz', $this->labels['quiz']['singular'], $this->labels['quiz']['plural'], $this->labels['quiz']['menu'] ),
		    'public' => true,
		    'publicly_queryable' => true,
		    'show_ui' => true, 
		    'show_in_menu' => false, 
		    'show_in_nav_menus' => false,
		    'query_var' => true,
		    'exclude_from_search' => true,
		    'rewrite' => array( 'slug' => esc_attr( apply_filters( 'sensei_quiz_slug', 'quiz' ) ) , 'with_front' => true, 'feeds' => true, 'pages' => true ),
		    'capability_type' => 'post',
		    'has_archive' => false, 
		    'hierarchical' => false,
		    'menu_position' => 20, // Below "Pages"
		    'menu_icon' => esc_url( $woothemes_sensei->plugin_url . 'assets/images/icon_course_16.png' ), 
		    'supports' => array( 'title' )
		);

		register_post_type( 'quiz', $args );
	} // End setup_quiz_post_type()
	
	
	/**
	 * Setup the "question" post type, it's admin menu item and the appropriate labels and permissions.
	 * @since  1.0.0
	 * @uses  global $woothemes_sensei
	 * @return void
	 */
	public function setup_question_post_type () {
		global $woothemes_sensei;

		$args = array(
		    'labels' => $this->create_post_type_labels( 'question', $this->labels['question']['singular'], $this->labels['question']['plural'], $this->labels['question']['menu'] ),
		    'public' => false,
		    'publicly_queryable' => true,
		    'show_ui' => true, 
		    'show_in_menu' => false, 
		    'show_in_nav_menus' => false,
		    'query_var' => true,
		    'exclude_from_search' => true,
		    'rewrite' => array( 'slug' => esc_attr( apply_filters( 'sensei_question_slug', 'question' ) ) , 'with_front' => true, 'feeds' => true, 'pages' => true ),
		    'capability_type' => 'post',
		    'has_archive' => true, 
		    'hierarchical' => false,
		    'menu_position' => 10, // Below "Pages"
		    'menu_icon' => esc_url( $woothemes_sensei->plugin_url . 'assets/images/icon_course_16.png' ), 
		    'supports' => array( 'title' )
		);

		register_post_type( 'question', $args );
	} // End setup_question_post_type()
	
	/**
	 * Setup the "quiz type" taxonomy, linked to the "quiz" post type.
	 * @since  1.0.0
	 * @return void
	 */
	public function setup_quiz_type_taxonomy () {
		// "Quiz Types" Custom Taxonomy
		$labels = array(
			'name' => _x( 'Quiz Types', 'taxonomy general name', 'woothemes-sensei' ),
			'singular_name' => _x( 'Quiz Type', 'taxonomy singular name', 'woothemes-sensei' ),
			'search_items' =>  __( 'Search Quiz Types', 'woothemes-sensei' ),
			'all_items' => __( 'All Quiz Types', 'woothemes-sensei' ),
			'parent_item' => __( 'Parent Quiz Type', 'woothemes-sensei' ),
			'parent_item_colon' => __( 'Parent Quiz Type:', 'woothemes-sensei' ),
			'edit_item' => __( 'Edit Quiz Type', 'woothemes-sensei' ), 
			'update_item' => __( 'Update Quiz Type', 'woothemes-sensei' ),
			'add_new_item' => __( 'Add New Quiz Type', 'woothemes-sensei' ),
			'new_item_name' => __( 'New Quiz Type Name', 'woothemes-sensei' ),
			'menu_name' => __( 'Quiz Types', 'woothemes-sensei' ), 
			'popular_items' => null // Hides the "Popular" section above the "add" form in the admin.
		); 	
		
		$args = array(
			'hierarchical' => false,
			'labels' => $labels,
			'show_ui' => true, /* TO DO - CHANGE TO FALSE WHEN GOING LIVE */
			'query_var' => true,
			'show_in_nav_menus' => false,
			'rewrite' => array( 'slug' => esc_attr( apply_filters( 'sensei_quiz_type_slug', 'quiz-type' ) ) )
		);
		
		register_taxonomy( 'quiz-type', array( 'quiz' ), $args );
	} // End setup_quiz_type_taxonomy()

	/**
	 * Setup the singular, plural and menu label names for the post types.
	 * @since  1.0.0
	 * @return void
	 */
	private function setup_post_type_labels_base () {
		$this->labels = array( 'course' => array(), 'lesson' => array(), 'quiz' => array(), 'question' => array() );
		
		$this->labels['course'] = array( 'singular' => __( 'Course', 'woothemes-sensei' ), 'plural' => __( 'Courses', 'woothemes-sensei' ), 'menu' => __( 'Courses', 'woothemes-sensei' ) );
		$this->labels['lesson'] = array( 'singular' => __( 'Lesson', 'woothemes-sensei' ), 'plural' => __( 'Lessons', 'woothemes-sensei' ), 'menu' => __( 'Lessons', 'woothemes-sensei' ) );
		$this->labels['quiz'] = array( 'singular' => __( 'Quiz', 'woothemes-sensei' ), 'plural' => __( 'Quizzes', 'woothemes-sensei' ), 'menu' => __( 'Quizzes', 'woothemes-sensei' ) );
		$this->labels['question'] = array( 'singular' => __( 'Question', 'woothemes-sensei' ), 'plural' => __( 'Questions', 'woothemes-sensei' ), 'menu' => __( 'Questions', 'woothemes-sensei' ) );
		
	} // End setup_post_type_labels_base()

	/**
	 * Create the labels for a specified post type.
	 * @since  1.0.0
	 * @param  string $token    The post type for which to setup labels (used to provide context)
	 * @param  string $singular The label for a singular instance of the post type
	 * @param  string $plural   The label for a plural instance of the post type
	 * @param  string $menu     The menu item label
	 * @return array            An array of the labels to be used
	 */
	private function create_post_type_labels ( $token, $singular, $plural, $menu ) {
		$labels = array(
		    'name' => sprintf( _x( '%s', 'post type general name', 'woothemes-sensei' ), $plural ),
		    'singular_name' => sprintf( _x( '%s', 'post type singular name', 'woothemes-sensei' ), $singular ),
		    'add_new' => sprintf( _x( 'Add New %s', $token, 'woothemes-sensei' ), $singular ),
		    'add_new_item' => sprintf( __( 'Add New %s', 'woothemes-sensei' ), $singular ),
		    'edit_item' => sprintf( __( 'Edit %s', 'woothemes-sensei' ), $singular ),
		    'new_item' => sprintf( __( 'New %s', 'woothemes-sensei' ), $singular ),
		    'all_items' => sprintf( __( 'All %s', 'woothemes-sensei' ), $plural ),
		    'view_item' => sprintf( __( 'View %s', 'woothemes-sensei' ), $singular ),
		    'search_items' => sprintf( __( 'Search %s', 'woothemes-sensei' ), $plural ),
		    'not_found' =>  sprintf( __( 'No %s found', 'woothemes-sensei' ), strtolower( $plural ) ),
		    'not_found_in_trash' => sprintf( __( 'No %s found in Trash', 'woothemes-sensei' ), strtolower( $plural ) ), 
		    'parent_item_colon' => '',
		    'menu_name' => $menu
		  );

		return $labels;
	} // End create_post_type_labels()

	/**
	 * Setup update messages for the post types.
	 * @since  1.0.0
	 * @param  array $messages The existing array of messages for post types.
	 * @return array           The modified array of messages for post types.
	 */
	public function setup_post_type_messages ( $messages ) {
		global $post, $post_ID;

		$messages['course'] = $this->create_post_type_messages( 'course' );
		$messages['lesson'] = $this->create_post_type_messages( 'lesson' );
		$messages['quiz'] = $this->create_post_type_messages( 'quiz' );
		$messages['question'] = $this->create_post_type_messages( 'question' );
		
		return $messages;
	} // End setup_post_type_messages()

	/**
	 * Create an array of messages for a specified post type.
	 * @since  1.0.0
	 * @param  string $post_type The post type for which to create messages.
	 * @return array            An array of messages (empty array if the post type isn't one we're looking to work with).
	 */
	private function create_post_type_messages ( $post_type ) {
		global $post, $post_ID;

		if ( ! isset( $this->labels[$post_type] ) ) { return array(); }

		$messages = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __( '%s updated.' ), esc_attr( $this->labels[$post_type]['singular'] ) ),
			2 => __( 'Custom field updated.', 'woothemes-sensei' ),
			3 => __( 'Custom field deleted.', 'woothemes-sensei' ),
			4 => sprintf( __( '%s updated.', 'woothemes-sensei' ), esc_attr( $this->labels[$post_type]['singular'] ) ),
			/* translators: %s: date and time of the revision */
			5 => isset( $_GET['revision']) ? sprintf( __('%2$s restored to revision from %1$s', 'woothemes-sensei' ), wp_post_revision_title( (int) $_GET['revision'], false ), esc_attr( $this->labels[$post_type]['singular'] ) ) : false,
			6 => sprintf( __('%2$s published.' ), esc_url( get_permalink($post_ID) ), esc_attr( $this->labels[$post_type]['singular'] ) ),
			7 => sprintf( __( '%s saved.', 'woothemes-sensei' ),  esc_attr( $this->labels[$post_type]['singular'] ) ),
			8 => sprintf( __( '%2$s submitted.', 'woothemes-sensei' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ), esc_attr( $this->labels[$post_type]['singular'] ) ),
			9 => sprintf( __( '%s scheduled for: <strong>%1$s</strong>.', 'woothemes-sensei' ),
			// translators: Publish box date format, see http://php.net/date
			date_i18n( __( ' M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ), strtolower( esc_attr( $this->labels[$post_type]['singular'] ) ) ),
			10 => sprintf( __( '%s draft updated.', 'woothemes-sensei' ), esc_attr( $this->labels[$post_type]['singular'] ) ),
		);

		return $messages;
	} // End create_post_type_messages()

	/**
	 * Change the "Enter Title Here" text for the "slide" post type.
	 * @access public
	 * @since  1.0.0
	 * @param  string $title
	 * @return string $title
	 */
	public function enter_title_here ( $title ) {
		if ( get_post_type() == 'course' ) {
			$title = __( 'Enter a title for this course here', 'woothemes-sensei' );
		} elseif ( get_post_type() == 'lesson' ) {
			$title = __( 'Enter a title for this lesson here', 'woothemes-sensei' );
		}
		
		return $title;
	} // End enter_title_here()
	
	
} // End Class
?>