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
 * - sensei_admin_menu_items()
 * - load_posttype_objects()
 * - setup_course_post_type()
 * - setup_lesson_post_type()
 * - setup_quiz_post_type()
 * - setup_question_post_type()
 * - setup_course_category_taxonomy()
 * - setup_quiz_type_taxonomy()
 * - setup_question_type_taxonomy()
 * - setup_post_type_labels_base()
 * - create_post_type_labels()
 * - setup_post_type_messages()
 * - create_post_type_messages()
 * - enter_title_here()
 * - load_class()
 */
class WooThemes_Sensei_PostTypes {
	public $token;
	public $slider_labels;
	public $role_caps;

	/**
	 * Constructor
	 * @since  1.0.0
	 * @return  void
	 */
	public function __construct () {
		global $woothemes_sensei;

		// Setup Post Types
		$this->labels = array();
		$this->setup_post_type_labels_base();
		add_action( 'init', array( $this, 'setup_course_post_type' ), 100 );
		add_action( 'init', array( $this, 'setup_lesson_post_type' ), 100 );
		add_action( 'init', array( $this, 'setup_quiz_post_type' ), 100 );
		add_action( 'init', array( $this, 'setup_question_post_type' ), 100 );
		add_action( 'init', array( $this, 'setup_multiple_question_post_type' ), 100 );
		add_action( 'init', array( $this, 'setup_sensei_message_post_type' ), 100 );

		// Setup Taxonomies
		add_action( 'init', array( $this, 'setup_course_category_taxonomy' ), 100 );
		add_action( 'init', array( $this, 'setup_quiz_type_taxonomy' ), 100 );
		add_action( 'init', array( $this, 'setup_question_type_taxonomy' ), 100 );
		add_action( 'init', array( $this, 'setup_question_category_taxonomy' ), 100 );
		add_action( 'init', array( $this, 'setup_lesson_tag_taxonomy' ), 100 );

		// Load Post Type Objects
		$default_post_types = array( 'course' => 'Course', 'lesson' => 'Lesson', 'quiz' => 'Quiz', 'question' => 'Question', 'messages' => 'Messages' ) ;
		$this->load_posttype_objects( $default_post_types );

		// Admin functions
		if ( is_admin() ) {
			$this->set_role_cap_defaults( $default_post_types );
			global $pagenow;
			if ( ( $pagenow == 'post.php' || $pagenow == 'post-new.php' ) ) {
				add_filter( 'enter_title_here', array( $this, 'enter_title_here' ), 10 );
				add_filter( 'post_updated_messages', array( $this, 'setup_post_type_messages' ) );
			} // End If Statement
		} // End If Statement

		// Add 'Edit Quiz' link to admin bar
		add_action( 'admin_bar_menu', array( $this, 'quiz_admin_bar_menu' ), 81 );

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
			$this->load_class( $posttype_token );
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
		    'show_in_menu' => true,
		    'show_in_admin_bar' => true,
		    'query_var' => true,
		    /**
		     * "with_front" property of rewrite behavior for courses.
		     *
		     * Allows for the "with_front" property of the rewrite rules for the course post type to be modified.
		     *
		     * @since 1.9.0
		     *
		     * @param bool $sensei_rewrite_course_with_front
		     */
		    'rewrite' => array( 'slug' => esc_attr( apply_filters( 'sensei_course_slug', _x( 'course', 'post type single url base', 'woothemes-sensei' ) ) ) , 'with_front' => apply_filters( 'sensei_rewrite_course_with_front', true ), 'feeds' => true, 'pages' => true ),
		    'map_meta_cap' => true,
		    'capability_type' => 'course',
		    'has_archive' => true,
		    'hierarchical' => false,
		    'menu_position' => 51,
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
		$allow_comments = false;
		if ( isset( $woothemes_sensei->settings->settings[ 'lesson_comments' ] ) ) {
			$allow_comments = $woothemes_sensei->settings->settings[ 'lesson_comments' ];
		} // End If Statement
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
		    /**
		     * "with_front" property of rewrite behavior for lessons.
		     *
		     * Allows for the "with_front" property of the rewrite rules for the lesson post type to be modified.
		     *
		     * @since 1.9.0
		     *
		     * @param bool $sensei_rewrite_lesson_with_front
		     */
		    'rewrite' => array( 'slug' => esc_attr( apply_filters( 'sensei_lesson_slug', _x( 'lesson', 'post type single slug', 'woothemes-sensei' ) ) ) , 'with_front' => apply_filters( 'sensei_rewrite_lesson_with_front', true ), 'feeds' => true, 'pages' => true ),
		    'map_meta_cap' => true,
		    'capability_type' => 'lesson',
		    'has_archive' => true,
		    'hierarchical' => false,
		    'menu_position' => 52,
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
		    /**
		     * "with_front" property of rewrite behavior for quizzes.
		     *
		     * Allows for the "with_front" property of the rewrite rules for the quiz post type to be modified.
		     *
		     * @since 1.9.0
		     *
		     * @param bool $sensei_rewrite_quiz_with_front
		     */
		    'rewrite' => array( 'slug' => esc_attr( apply_filters( 'sensei_quiz_slug', _x( 'quiz', 'post type single slug', 'woothemes-sensei' ) ) ) , 'with_front' => apply_filters( 'sensei_rewrite_quiz_with_front', true ), 'feeds' => true, 'pages' => true ),
		    'map_meta_cap' => true,
		    'capability_type' => 'quiz',
		    'has_archive' => false,
		    'hierarchical' => false,
		    'menu_position' => 20, // Below "Pages"
		    'supports' => array( '' )
		);

		register_post_type( 'quiz', $args );
	} // End setup_quiz_post_type()


	/**
	 * Setup the "question" post type, it's admin menu item and the appropriate labels and permissions.
	 * @since  1.0.0
	 * @return void
	 */
	public function setup_question_post_type () {

		$args = array(
		    'labels' => $this->create_post_type_labels( 'question', $this->labels['question']['singular'], $this->labels['question']['plural'], $this->labels['question']['menu'] ),
		    'public' => false,
		    'publicly_queryable' => true,
		    'show_ui' => true,
		    'show_in_menu' => true,
		    'show_in_nav_menus' => false,
		    'query_var' => true,
		    'exclude_from_search' => true,
		    /**
		     * "with_front" property of rewrite behavior for questions.
		     *
		     * Allows for the "with_front" property of the rewrite rules for the question post type to be modified.
		     *
		     * @since 1.9.0
		     *
		     * @param bool $sensei_rewrite_question_with_front
		     */
		    'rewrite' => array( 'slug' => esc_attr( apply_filters( 'sensei_question_slug', _x( 'question', 'post type single slug', 'woothemes-sensei' ) ) ) , 'with_front' => apply_filters( 'sensei_rewrite_question_with_front', true ), 'feeds' => true, 'pages' => true ),
		    'map_meta_cap' => true,
		    'capability_type' => 'question',
		    'has_archive' => true,
		    'hierarchical' => false,
		    'menu_position' => 51,
		    'supports' => array( 'title' )
		);

		register_post_type( 'question', $args );
	} // End setup_question_post_type()

	/**
	 * Setup the "multiple_question" post type, it's admin menu item and the appropriate labels and permissions.
	 * @since  1.6.0
	 * @return void
	 */
	public function setup_multiple_question_post_type () {

		$args = array(
		    'labels' => $this->create_post_type_labels( 'multiple_question', $this->labels['multiple_question']['singular'], $this->labels['multiple_question']['plural'], $this->labels['multiple_question']['menu'] ),
		    'public' => false,
		    'publicly_queryable' => false,
		    'show_ui' => false,
		    'show_in_menu' => false,
		    'show_in_nav_menus' => false,
		    'query_var' => false,
		    'exclude_from_search' => true,
		    /**
		     * "with_front" property of rewrite behavior for multiple questions.
		     *
		     * Allows for the "with_front" property of the rewrite rules for the multiple question post type to be modified.
		     *
		     * @since 1.9.0
		     *
		     * @param bool $sensei_rewrite_multiple_question_with_front
		     */
		    'rewrite' => array( 'slug' => esc_attr( apply_filters( 'sensei_multiple_question_slug', _x( 'multiple_question', 'post type single slug', 'woothemes-sensei' ) ) ) , 'with_front' => apply_filters( 'sensei_rewrite_multiple_question_with_front', false ), 'feeds' => false, 'pages' => false ),
		    'map_meta_cap' => true,
		    'capability_type' => 'question',
		    'has_archive' => false,
		    'hierarchical' => false,
		    'menu_position' => 51,
		    'supports' => array( 'title', 'custom-fields' )
		);

		register_post_type( 'multiple_question', $args );
	} // End setup_multiple_question_post_type()

	/**
	 * Setup the "sensei_message" post type, it's admin menu item and the appropriate labels and permissions.
	 * @since  1.6.0
	 * @return void
	 */
	public function setup_sensei_message_post_type () {
		global $woothemes_sensei;

		if( ! isset( $woothemes_sensei->settings->settings['messages_disable'] ) || ! $woothemes_sensei->settings->settings['messages_disable'] ) {

			$args = array(
			    'labels' => $this->create_post_type_labels( 'sensei_message', $this->labels['sensei_message']['singular'], $this->labels['sensei_message']['plural'], $this->labels['sensei_message']['menu'] ),
			    'public' => true,
			    'publicly_queryable' => true,
			    'show_ui' => true,
			    'show_in_menu' => 'sensei',
			    'show_in_nav_menus' => true,
			    'query_var' => true,
			    'exclude_from_search' => true,
				/**
				 * "with_front" property of rewrite behavior for messages.
				 *
				 * Allows for the "with_front" property of the rewrite rules for the message post type to be modified.
				 *
				 * @since 1.9.0
				 *
				 * @param bool $sensei_rewrite_message_with_front
				 */
			    'rewrite' => array( 'slug' => esc_attr( apply_filters( 'sensei_messages_slug', _x( 'messages', 'post type single slug', 'woothemes-sensei' ) ) ) , 'with_front' => apply_filters( 'sensei_rewrite_message_with_front', false ), 'feeds' => false, 'pages' => true ),
			    'map_meta_cap' => true,
			    'capability_type' => 'question',
			    'has_archive' => true,
			    'hierarchical' => false,
			    'menu_position' => 50,
			    'supports' => array( 'title', 'editor', 'comments' ),
			);

			register_post_type( 'sensei_message', $args );
		}
	} // End setup_sensei_message_post_type()

	/**
	 * Setup the "course category" taxonomy, linked to the "course" post type.
	 * @since  1.1.0
	 * @return void
	 */
	public function setup_course_category_taxonomy () {
		// "Course Categories" Custom Taxonomy
		$labels = array(
			'name' => _x( 'Course Categories', 'taxonomy general name', 'woothemes-sensei' ),
			'singular_name' => _x( 'Course Category', 'taxonomy singular name', 'woothemes-sensei' ),
			'search_items' =>  __( 'Search Course Categories', 'woothemes-sensei' ),
			'all_items' => __( 'All Course Categories', 'woothemes-sensei' ),
			'parent_item' => __( 'Parent Course Category', 'woothemes-sensei' ),
			'parent_item_colon' => __( 'Parent Course Category:', 'woothemes-sensei' ),
			'edit_item' => __( 'Edit Course Category', 'woothemes-sensei' ),
			'update_item' => __( 'Update Course Category', 'woothemes-sensei' ),
			'add_new_item' => __( 'Add New Course Category', 'woothemes-sensei' ),
			'new_item_name' => __( 'New Course Category Name', 'woothemes-sensei' ),
			'menu_name' => __( 'Course Categories', 'woothemes-sensei' ),
			'popular_items' => null // Hides the "Popular" section above the "add" form in the admin.
		);

		$args = array(
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => true,
			'query_var' => true,
			'show_in_nav_menus' => true,
            'capabilities' => array(
                'manage_terms' => 'manage_categories',
                'edit_terms'   => 'edit_courses',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_courses',),
			'rewrite' => array( 'slug' => esc_attr( apply_filters( 'sensei_course_category_slug', _x( 'course-category', 'taxonomy archive slug', 'woothemes-sensei' ) ) ) )
		);

		register_taxonomy( 'course-category', array( 'course' ), $args );
	} // End setup_course_category_taxonomy()

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
			'show_ui' => true, /* TO DO - future releases */
			'query_var' => true,
			'show_in_nav_menus' => false,
            'public' => false,
			'rewrite' => array( 'slug' => esc_attr( apply_filters( 'sensei_quiz_type_slug', _x( 'quiz-type', 'taxonomy archive slug', 'woothemes-sensei' ) ) ) )
		);

		register_taxonomy( 'quiz-type', array( 'quiz' ), $args );
	} // End setup_quiz_type_taxonomy()

	/**
	 * Setup the "question type" taxonomy, linked to the "question" post type.
	 * @since  1.3.0
	 * @return void
	 */
	public function setup_question_type_taxonomy () {
		// "Question Types" Custom Taxonomy
		$labels = array(
			'name' => _x( 'Question Types', 'taxonomy general name', 'woothemes-sensei' ),
			'singular_name' => _x( 'Question Type', 'taxonomy singular name', 'woothemes-sensei' ),
			'search_items' =>  __( 'Search Question Types', 'woothemes-sensei' ),
			'all_items' => __( 'All Question Types', 'woothemes-sensei' ),
			'parent_item' => __( 'Parent Question Type', 'woothemes-sensei' ),
			'parent_item_colon' => __( 'Parent Question Type:', 'woothemes-sensei' ),
			'edit_item' => __( 'Edit Question Type', 'woothemes-sensei' ),
			'update_item' => __( 'Update Question Type', 'woothemes-sensei' ),
			'add_new_item' => __( 'Add New Question Type', 'woothemes-sensei' ),
			'new_item_name' => __( 'New Question Type Name', 'woothemes-sensei' ),
			'menu_name' => __( 'Question Types', 'woothemes-sensei' ),
			'popular_items' => null // Hides the "Popular" section above the "add" form in the admin.
		);

		$args = array(
			'hierarchical' => false,
			'labels' => $labels,
			'show_ui' => false,
			'public' => false,
			'query_var' => false,
			'show_in_nav_menus' => false,
			'show_admin_column' => true,
			'rewrite' => array( 'slug' => esc_attr( apply_filters( 'sensei_question_type_slug', _x( 'question-type', 'taxonomy archive slug', 'woothemes-sensei' ) ) ) )
		);

		register_taxonomy( 'question-type', array( 'question' ), $args );
	} // End setup_question_type_taxonomy()

	/**
	 * Setup the "question category" taxonomy, linked to the "question" post type.
	 * @since  1.3.0
	 * @return void
	 */
	public function setup_question_category_taxonomy () {
		// "Question Categories" Custom Taxonomy
		$labels = array(
			'name' => _x( 'Question Categories', 'taxonomy general name', 'woothemes-sensei' ),
			'singular_name' => _x( 'Question Category', 'taxonomy singular name', 'woothemes-sensei' ),
			'search_items' =>  __( 'Search Question Categories', 'woothemes-sensei' ),
			'all_items' => __( 'All Question Categories', 'woothemes-sensei' ),
			'parent_item' => __( 'Parent Question Category', 'woothemes-sensei' ),
			'parent_item_colon' => __( 'Parent Question Category:', 'woothemes-sensei' ),
			'edit_item' => __( 'Edit Question Category', 'woothemes-sensei' ),
			'update_item' => __( 'Update Question Category', 'woothemes-sensei' ),
			'add_new_item' => __( 'Add New Question Category', 'woothemes-sensei' ),
			'new_item_name' => __( 'New Question Category Name', 'woothemes-sensei' ),
			'menu_name' => __( 'Categories', 'woothemes-sensei' ),
		);

		$args = array(
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => true,
			'public' => false,
			'query_var' => false,
			'show_in_nav_menus' => false,
			'show_admin_column' => true,
            'capabilities' => array(
                'manage_terms' => 'manage_categories',
                'edit_terms'   => 'edit_questions',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_questions',),
			'rewrite' => array( 'slug' => esc_attr( apply_filters( 'sensei_question_category_slug', _x( 'question-category', 'taxonomy archive slug', 'woothemes-sensei' ) ) ) )
		);

		register_taxonomy( 'question-category', array( 'question' ), $args );
	} // End setup_question_type_taxonomy()

	/**
	 * Setup the "lesson tags" taxonomy, linked to the "lesson" post type.
	 * @since  1.5.0
	 * @return void
	 */
	public function setup_lesson_tag_taxonomy () {
		// "Lesson Tags" Custom Taxonomy
		$labels = array(
			'name' => _x( 'Lesson Tags', 'taxonomy general name', 'woothemes-sensei' ),
			'singular_name' => _x( 'Lesson Tag', 'taxonomy singular name', 'woothemes-sensei' ),
			'search_items' =>  __( 'Search Lesson Tags', 'woothemes-sensei' ),
			'all_items' => __( 'All Lesson Tags', 'woothemes-sensei' ),
			'parent_item' => __( 'Parent Tag', 'woothemes-sensei' ),
			'parent_item_colon' => __( 'Parent Tag:', 'woothemes-sensei' ),
			'edit_item' => __( 'Edit Lesson Tag', 'woothemes-sensei' ),
			'update_item' => __( 'Update Lesson Tag', 'woothemes-sensei' ),
			'add_new_item' => __( 'Add New Lesson Tag', 'woothemes-sensei' ),
			'new_item_name' => __( 'New Tag Name', 'woothemes-sensei' ),
			'menu_name' => __( 'Lesson Tags', 'woothemes-sensei' )
		);

		$args = array(
			'hierarchical' => false,
			'labels' => $labels,
			'show_ui' => true,
			'query_var' => true,
			'show_in_nav_menus' => true,
            'capabilities' => array(
                'manage_terms' => 'manage_categories',
                'edit_terms'   => 'edit_lessons',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_lessons',),
			'rewrite' => array( 'slug' => esc_attr( apply_filters( 'sensei_lesson_tag_slug', _x( 'lesson-tag', 'taxonomy archive slug', 'woothemes-sensei' ) ) ) )
		);

		register_taxonomy( 'lesson-tag', array( 'lesson' ), $args );
	} // End setup_lesson_tag_taxonomy()

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
		$this->labels['multiple_question'] = array( 'singular' => __( 'Multiple Question', 'woothemes-sensei' ), 'plural' => __( 'Multiple Questions', 'woothemes-sensei' ), 'menu' => __( 'Multiple Questions', 'woothemes-sensei' ) );
		$this->labels['sensei_message'] = array( 'singular' => __( 'Message', 'woothemes-sensei' ), 'plural' => __( 'Messages', 'woothemes-sensei' ), 'menu' => __( 'Messages', 'woothemes-sensei' ) );

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
		    'add_new' => __( 'Add New', 'woothemes-sensei' ),
		    'add_new_item' => sprintf( __( 'Add New %s', 'woothemes-sensei' ), $singular ),
		    'edit_item' => sprintf( __( 'Edit %s', 'woothemes-sensei' ), $singular ),
		    'new_item' => sprintf( __( 'New %s', 'woothemes-sensei' ), $singular ),
		    'all_items' => sprintf( __( 'All %s', 'woothemes-sensei' ), $plural ),
		    'view_item' => sprintf( __( 'View %s', 'woothemes-sensei' ), $singular ),
		    'search_items' => sprintf( __( 'Search %s', 'woothemes-sensei' ), $plural ),
		    'not_found' =>  sprintf( __( 'No %s found', 'woothemes-sensei' ), mb_strtolower( $plural, 'UTF-8') ),
		    'not_found_in_trash' => sprintf( __( 'No %s found in Trash', 'woothemes-sensei' ), mb_strtolower( $plural, 'UTF-8') ),
		    'parent_item_colon' => '',
		    'menu_name' => sprintf( __( '%s', 'woothemes-sensei' ), $menu )
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
		$messages['multiple_question'] = $this->create_post_type_messages( 'multiple_question' );

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
			0 => '',
			1 => sprintf( __( '%1$s updated. %2$sView %1$s%3$s.' , 'woothemes-sensei' ), $this->labels[$post_type]['singular'], '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
			2 => __( 'Custom field updated.' , 'woothemes-sensei' ),
			3 => __( 'Custom field deleted.' , 'woothemes-sensei' ),
			4 => sprintf( __( '%1$s updated.' , 'woothemes-sensei' ), $this->labels[$post_type]['singular'] ),
			5 => isset( $_GET['revision'] ) ? sprintf( __( '%1$s restored to revision from %2$s.' , 'woothemes-sensei' ), $this->labels[$post_type]['singular'], wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __( '%1$s published. %2$sView %1$s%3$s.' , 'woothemes-sensei' ), $this->labels[$post_type]['singular'], '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
			7 => sprintf( __( '%1$s saved.' , 'woothemes-sensei' ), $this->labels[$post_type]['singular'] ),
			8 => sprintf( __( '%1$s submitted. %2$sPreview %1$s%3$s.' , 'woothemes-sensei' ), $this->labels[$post_type]['singular'], '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', '</a>' ),
			9 => sprintf( __( '%1$s scheduled for: %2$s. %3$sPreview %4$s%5$s.' , 'woothemes-sensei' ), $this->labels[$post_type]['singular'], '<strong>' . date_i18n( __( 'M j, Y @ G:i' , 'woothemes-sensei' ), strtotime( $post->post_date ) ) . '</strong>', '<a target="_blank" href="' . esc_url( get_permalink( $post_ID ) ) . '">', $this->labels[$post_type]['singular'], '</a>' ),
			10 => sprintf( __( '%1$s draft updated. %2$sPreview %3$s%4$s.' , 'woothemes-sensei' ), $this->labels[$post_type]['singular'], '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', $this->labels[$post_type]['singular'], '</a>' ),
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

	/**
	 * Assigns the defaults for each user role capabilities.
	 *
	 * @since  1.1.0
	 * @access public
	 * @return void
	 */
	public function set_role_cap_defaults( $post_types = array() ) {

		foreach ( $post_types as $post_type_item => $post_type_name ) {
			// Super Admin
			$this->role_caps[] =  array(	'administrator' 	=> array( 	'edit_' . $post_type_item,
																			'read_' . $post_type_item,
																			'delete_' . $post_type_item,
																			'create_' . $post_type_item . 's',
																			'edit_' . $post_type_item . 's',
																			'edit_others_' . $post_type_item . 's',
																			'publish_' . $post_type_item . 's',
																			'read_private_' . $post_type_item . 's',
																			'read',
																			'delete_' . $post_type_item . 's',
																			'delete_private_' . $post_type_item . 's',
																			'delete_published_' . $post_type_item . 's',
																			'delete_others_' . $post_type_item . 's',
																			'edit_private_' . $post_type_item . 's',
																			'edit_published_' . $post_type_item . 's',
																			'manage_sensei',
																			'manage_sensei_grades' ),
											'editor' 			=> array(	'edit_' . $post_type_item,
																			'read_' . $post_type_item,
																			'delete_' . $post_type_item,
																			'create_' . $post_type_item . 's',
																		 	'edit_' . $post_type_item . 's',
																			'edit_others_' . $post_type_item . 's',
																			'publish_' . $post_type_item . 's',
																			'read_private_' . $post_type_item . 's',
																			'read',
																			'delete_' . $post_type_item . 's',
																			'delete_private_' . $post_type_item . 's',
																			'delete_published_' . $post_type_item . 's',
																			'delete_others_' . $post_type_item . 's',
																			'edit_private_' . $post_type_item . 's',
																			'edit_published_' . $post_type_item . 's' ),
											'author' 			=> array( 	'edit_' . $post_type_item,
																			'read_' . $post_type_item,
																			'delete_' . $post_type_item,
																			'create_' . $post_type_item . 's',
																			'edit_' . $post_type_item . 's',
																			'publish_' . $post_type_item . 's',
																			'read',
																			'delete_' . $post_type_item . 's',
																			'delete_published_' . $post_type_item . 's',
																			'edit_published_' . $post_type_item . 's' ),
											'contributor' 		=> array( 	'edit_' . $post_type_item,
																			'read_' . $post_type_item,
																			'delete_' . $post_type_item,
																			'create_' . $post_type_item . 's',
																			'edit_' . $post_type_item . 's',
																			'read',
																			'delete_' . $post_type_item . 's' ),
											'subscriber' 		=> array( 	'read' )

										);
		} // End For Loop

	} // End set_role_cap_defaults()

	/**
	 * Adds a 'Edit Quiz' link to the admin bar when viewing a Quiz linked to a corresponding Lesson
	 * 
	 * @since  1.7.0
	 * @return void
	 */
	public function quiz_admin_bar_menu( $bar ) {
		if ( is_single() && 'quiz' == get_queried_object()->post_type ) {
			$lesson_id = get_post_meta( get_queried_object()->ID, '_quiz_lesson', true );
			if ( $lesson_id ) {
				$object_type = get_post_type_object('quiz');
				$bar->add_menu( array(
					'id' => 'edit',
					'title' => $object_type->labels->edit_item,
					'href' => get_edit_post_link( $lesson_id ),
				) );
			}
		}
	}

	/**
	 * load_class loads in class files
	 * @since  1.2.0
	 * @return void
	 */
	public function load_class( $class_name = '' ) {
		if ( '' != $class_name ) {
			require_once( 'class-woothemes-sensei-' . $class_name . '.php' );
		} // End If Statement
	} // End load_class()

} // End Class
