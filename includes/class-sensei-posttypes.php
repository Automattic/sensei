<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Sensei Post Types Class
 *
 * All functionality pertaining to the post types and taxonomies in Sensei.
 *
 * @package Core
 * @author Automattic
 *
 * @since 1.0.0
 */
class Sensei_PostTypes {
	const LEARNER_TAXONOMY_NAME = 'sensei_learner';

	public $token;
	public $slider_labels;
	public $role_caps;

	/**
	 * @var Sensei_Course
	 */
	public $course;

	/**
	 * @var Sensei_Lesson
	 */
	public $lesson;

	/**
	 * @var Sensei_Question
	 */
	public $question;

	/**
	 * @var Sensei_Quiz
	 */
	public $quiz;

	/**
	 * Messages object.
	 *
	 * @var Sensei_Messages
	 */
	public $messages;

	/**
	 * Array of post ID's for which to fire an "initial publish" action.
	 *
	 * @var array
	 */
	private $initial_publish_post_ids = [];

	/**
	 * Constructor
	 *
	 * @since  1.0.0
	 */
	public function __construct() {

		// Setup Post Types
		$this->labels = array();
		$this->token  = 'woothemes-sensei-posttypes';

		$this->setup_post_type_labels_base();
		add_action( 'init', array( $this, 'setup_course_post_type' ), 100 );
		add_action( 'init', array( $this, 'setup_lesson_post_type' ), 100 );
		add_action( 'init', array( $this, 'setup_quiz_post_type' ), 100 );
		add_action( 'init', array( $this, 'setup_question_post_type' ), 100 );
		add_action( 'init', array( $this, 'setup_multiple_question_post_type' ), 100 );
		add_action( 'init', array( $this, 'setup_sensei_message_post_type' ), 100 );

		// Setup Taxonomies
		add_action( 'init', array( $this, 'setup_learner_taxonomy' ), 100 );
		add_action( 'init', array( $this, 'setup_course_category_taxonomy' ), 100 );
		add_action( 'init', array( $this, 'setup_quiz_type_taxonomy' ), 100 );
		add_action( 'init', array( $this, 'setup_question_type_taxonomy' ), 100 );
		add_action( 'init', array( $this, 'setup_question_category_taxonomy' ), 100 );
		add_action( 'init', array( $this, 'setup_lesson_tag_taxonomy' ), 100 );

		// Load Post Type Objects
		$default_post_types = array(
			'course'   => 'Course',
			'lesson'   => 'Lesson',
			'quiz'     => 'Quiz',
			'question' => 'Question',
			'messages' => 'Messages',
		);
		$this->load_posttype_objects( $default_post_types );
		$this->set_role_cap_defaults( $default_post_types );

		// Admin functions
		if ( is_admin() ) {
			global $pagenow;
			if ( ( $pagenow == 'post.php' || $pagenow == 'post-new.php' ) ) {
				add_filter( 'enter_title_here', array( $this, 'enter_title_here' ), 10 );
				add_filter( 'post_updated_messages', array( $this, 'setup_post_type_messages' ) );
			}
		}

		// REST API functionality.
		add_action( 'rest_api_init', [ $this, 'setup_rest_api' ] );

		// Add protections on feeds for certain CPTs.
		add_action( 'wp', [ $this, 'protect_feeds' ] );

		// Add 'Edit Quiz' link to admin bar
		add_action( 'admin_bar_menu', array( $this, 'quiz_admin_bar_menu' ), 81 );

		$this->setup_initial_publish_action();

	}

	/**
	 * load_posttype_objects function.
	 * Dynamically loads post type objects for meta boxes on backend
	 *
	 * @access public
	 * @param array $posttypes (default: array())
	 * @return void
	 */
	public function load_posttype_objects( $posttypes = array() ) {

		foreach ( $posttypes as $posttype_token => $posttype_name ) {

			// Load the files
			$class_name                   = 'Sensei_' . $posttype_name;
			$this->$posttype_token        = new $class_name();
			$this->$posttype_token->token = $posttype_token;

		}

	}

	/**
	 * Set up REST API for post types.
	 *
	 * @access private
	 * @since 2.2.0
	 */
	public function setup_rest_api() {
		// Ensure registered meta will show up in the REST API for courses and lessons.
		add_post_type_support( 'course', 'custom-fields' );
		add_post_type_support( 'lesson', 'custom-fields' );

		// Hide post content for students who aren't enrolled.
		add_filter( 'post_password_required', [ $this, 'lesson_is_protected' ], 10, 2 );
	}

	/**
	 * Add protection to Sensei post type feeds.
	 *
	 * @access private
	 */
	public function protect_feeds() {
		if ( is_feed() && is_post_type_archive( [ 'lesson', 'question', 'quiz', 'sensei_message' ] ) ) {
			wp_die( esc_html__( 'Error: Feed does not exist', 'sensei-lms' ), '', [ 'response' => 404 ] );
		}
	}

	/**
	 * Helper function to hide lesson post content by artificially making this a password protected post in certain contexts.
	 *
	 * @access private
	 *
	 * @param bool    $is_password_protected Filtered value for if this is a password protected post.
	 * @param WP_Post $post                  Post object.
	 *
	 * @return bool
	 */
	public function lesson_is_protected( $is_password_protected, $post ) {
		if (
			$post instanceof WP_Post
			&& 'lesson' === $post->post_type
			&& ! sensei_can_user_view_lesson( $post->ID, get_current_user_id() )
		) {
			return true;
		}

		return $is_password_protected;
	}

	/**
	 * Setup the "course" post type, it's admin menu item and the appropriate labels and permissions.
	 *
	 * @since  1.0.0
	 * @uses  Sensei()
	 * @return void
	 */
	public function setup_course_post_type() {
		// If Sensei LMS was first activated pre-3.7.0 and permalinks had a front value, `with_front` will be enabled.
		$with_front = Sensei()->get_legacy_flag( Sensei_Main::LEGACY_FLAG_WITH_FRONT ) ? true : false;

		$args = array(
			'labels'                => $this->create_post_type_labels( $this->labels['course']['singular'], $this->labels['course']['plural'], $this->labels['course']['menu'] ),
			'public'                => true,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_in_admin_bar'     => true,
			'query_var'             => true,
			'rewrite'               => array(
				'slug'       => esc_attr( apply_filters( 'sensei_course_slug', _x( 'course', 'post type single url base', 'sensei-lms' ) ) ),
				'with_front' => $with_front,
				'feeds'      => true,
				'pages'      => true,
			),
			'map_meta_cap'          => true,
			'capability_type'       => 'course',
			'has_archive'           => $this->get_course_post_type_archive_slug(),
			'hierarchical'          => false,
			'menu_position'         => 51,
			'supports'              => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields' ),
			'show_in_rest'          => true,
			'rest_base'             => 'courses',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
		);

		/**
		 * Filter the arguments passed in when registering the Sensei Course post type.
		 *
		 * @since 1.9.0
		 * @param array $args
		 */
		register_post_type( 'course', apply_filters( 'sensei_register_post_type_course', $args ) );

	}

	/**
	 * Figure out of the course post type has an archive and what it should be.
	 *
	 * This function should return 'courses' or the page_uri for the course page setting.
	 *
	 * For backward compatibility  sake ( pre 1.9 )If the course page set in settings
	 * still has any of the old shortcodes: [newcourses][featuredcourses][freecourses][paidcourses] the
	 * page slug will not be returned. For any other pages without it the page URI will be returned.
	 *
	 * @sine 1.9.0
	 *
	 * @return false|string
	 */
	public function get_course_post_type_archive_slug() {

		$settings_course_page = get_post( Sensei()->settings->get( 'course_page' ) );

		// for a valid post that doesn't have any of the old short codes set the archive the same
		// as the page URI
		if ( is_a( $settings_course_page, 'WP_Post' ) && ! $this->has_old_shortcodes( $settings_course_page->post_content ) ) {

			 return get_page_uri( $settings_course_page->ID );

		} else {

			return 'courses';

		}

	}

	/**
	 * Check if given content has any of these old shortcodes:
	 * [newcourses][featuredcourses][freecourses][paidcourses]
	 *
	 * @since 1.9.0
	 *
	 * @param string $content
	 *
	 * @return bool
	 */
	public function has_old_shortcodes( $content ) {

		return ( has_shortcode( $content, 'newcourses' )
		|| has_shortcode( $content, 'featuredcourses' )
		|| has_shortcode( $content, 'freecourses' )
		|| has_shortcode( $content, 'paidcourses' ) );

	}


	/**
	 * Setup the "lesson" post type, it's admin menu item and the appropriate labels and permissions.
	 *
	 * @since  1.0.0
	 * @uses  Sensei()
	 * @return void
	 */
	public function setup_lesson_post_type() {

		$supports_array = array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' );
		$allow_comments = false;
		if ( isset( Sensei()->settings->settings['lesson_comments'] ) ) {
			$allow_comments = Sensei()->settings->settings['lesson_comments'];
		}
		if ( $allow_comments ) {
			array_push( $supports_array, 'comments' );
		}

		// If Sensei LMS was first activated pre-3.7.0 and permalinks had a front value, `with_front` will be enabled.
		$with_front = Sensei()->get_legacy_flag( Sensei_Main::LEGACY_FLAG_WITH_FRONT ) ? true : false;

		$args = array(
			'labels'                => $this->create_post_type_labels( $this->labels['lesson']['singular'], $this->labels['lesson']['plural'], $this->labels['lesson']['menu'] ),
			'public'                => true,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'query_var'             => true,
			'rewrite'               => array(
				'slug'       => esc_attr( apply_filters( 'sensei_lesson_slug', _x( 'lesson', 'post type single slug', 'sensei-lms' ) ) ),
				'with_front' => $with_front,
				'feeds'      => true,
				'pages'      => true,
			),
			'map_meta_cap'          => true,
			'capability_type'       => 'lesson',
			'has_archive'           => true,
			'hierarchical'          => false,
			'menu_position'         => 52,
			'supports'              => $supports_array,
			'show_in_rest'          => true,
			'rest_base'             => 'lessons',
			'rest_controller_class' => 'Sensei_REST_API_Lessons_Controller',
		);

		/**
		 * Filter the arguments passed in when registering the Sensei Lesson post type.
		 *
		 * @since 1.9.0
		 * @param array $args
		 */
		register_post_type( 'lesson', apply_filters( 'sensei_register_post_type_lesson', $args ) );

	}

	/**
	 * Setup the "quiz" post type, it's admin menu item and the appropriate labels and permissions.
	 *
	 * @since  1.0.0
	 * @uses  Sensei()
	 * @return void
	 */
	public function setup_quiz_post_type() {
		// If Sensei LMS was first activated pre-3.7.0 and permalinks had a front value, `with_front` will be enabled.
		$with_front = Sensei()->get_legacy_flag( Sensei_Main::LEGACY_FLAG_WITH_FRONT ) ? true : false;

		$args = array(
			'labels'              => $this->create_post_type_labels(
				$this->labels['quiz']['singular'],
				$this->labels['quiz']['plural'],
				$this->labels['quiz']['menu']
			),
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'query_var'           => true,
			'exclude_from_search' => true,
			'rewrite'             => array(
				'slug'       => esc_attr( apply_filters( 'sensei_quiz_slug', _x( 'quiz', 'post type single slug', 'sensei-lms' ) ) ),
				'with_front' => $with_front,
				'feeds'      => true,
				'pages'      => true,
			),
			'map_meta_cap'        => true,
			'capability_type'     => 'quiz',
			'capabilities'        => array(
				'edit_published_posts' => 'do_not_allow',
			),
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => 20, // Below "Pages"
			'supports'            => array( '' ),
		);

		/**
		 * Filter the arguments passed in when registering the Sensei Quiz post type.
		 *
		 * @since 1.9.0
		 * @param array $args
		 */
		register_post_type( 'quiz', apply_filters( 'sensei_register_post_type_quiz', $args ) );

	}


	/**
	 * Setup the "question" post type, it's admin menu item and the appropriate labels and permissions.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function setup_question_post_type() {
		// If Sensei LMS was first activated pre-3.7.0 and permalinks had a front value, `with_front` will be enabled.
		$with_front = Sensei()->get_legacy_flag( Sensei_Main::LEGACY_FLAG_WITH_FRONT ) ? true : false;

		$args = array(
			'labels'                => $this->create_post_type_labels( $this->labels['question']['singular'], $this->labels['question']['plural'], $this->labels['question']['menu'] ),
			'public'                => false,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_in_nav_menus'     => false,
			'query_var'             => true,
			'exclude_from_search'   => true,
			'rewrite'               => array(
				'slug'       => esc_attr( apply_filters( 'sensei_question_slug', _x( 'question', 'post type single slug', 'sensei-lms' ) ) ),
				'with_front' => $with_front,
				'feeds'      => true,
				'pages'      => true,
			),
			'map_meta_cap'          => true,
			'capability_type'       => 'question',
			'has_archive'           => true,
			'hierarchical'          => false,
			'menu_position'         => 51,
			'supports'              => array( 'title', 'revisions' ),
			'show_in_rest'          => true,
			'rest_base'             => 'questions',
			'rest_controller_class' => 'Sensei_REST_API_Questions_Controller',
		);

		if ( Sensei()->quiz->is_block_based_editor_enabled() ) {
			$args['supports'][] = 'editor';
		}

		/**
		 * Filter the arguments passed in when registering the Sensei Question post type.
		 *
		 * @since 1.9.0
		 * @param array $args
		 */
		register_post_type( 'question', apply_filters( 'sensei_register_post_type_question', $args ) );

	}

	/**
	 * Setup the "multiple_question" post type, it's admin menu item and the appropriate labels and permissions.
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function setup_multiple_question_post_type() {

		$args = array(
			'labels'              => $this->create_post_type_labels( $this->labels['multiple_question']['singular'], $this->labels['multiple_question']['plural'], $this->labels['multiple_question']['menu'] ),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'query_var'           => false,
			'exclude_from_search' => true,
			'rewrite'             => array(
				'slug'       => esc_attr( apply_filters( 'sensei_multiple_question_slug', _x( 'multiple_question', 'post type single slug', 'sensei-lms' ) ) ),
				'with_front' => false,
				'feeds'      => false,
				'pages'      => false,
			),
			'map_meta_cap'        => true,
			'capability_type'     => 'question',
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => 51,
			'supports'            => array( 'title', 'custom-fields' ),
		);

		register_post_type( 'multiple_question', $args );
	}

	/**
	 * Setup the "sensei_message" post type, it's admin menu item and the appropriate labels and permissions.
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function setup_sensei_message_post_type() {

		if ( ! isset( Sensei()->settings->settings['messages_disable'] ) || ! Sensei()->settings->settings['messages_disable'] ) {

			$args = array(
				'labels'                => $this->create_post_type_labels( $this->labels['sensei_message']['singular'], $this->labels['sensei_message']['plural'], $this->labels['sensei_message']['menu'] ),
				'public'                => true,
				'publicly_queryable'    => true,
				'show_ui'               => true,
				'show_in_menu'          => 'admin.php?page=sensei',
				'show_in_nav_menus'     => true,
				'query_var'             => true,
				'exclude_from_search'   => true,
				'rewrite'               => array(
					'slug'       => esc_attr( apply_filters( 'sensei_messages_slug', _x( 'messages', 'post type single slug', 'sensei-lms' ) ) ),
					'with_front' => false,
					'feeds'      => false,
					'pages'      => true,
				),
				'map_meta_cap'          => true,
				'capability_type'       => 'question',
				'has_archive'           => true,
				'hierarchical'          => false,
				'menu_position'         => 50,
				'show_in_rest'          => true,
				'rest_base'             => 'sensei-messages',
				'rest_controller_class' => 'Sensei_REST_API_Messages_Controller',
				'supports'              => array( 'title', 'editor', 'comments' ),
				'delete_with_user'      => true,
			);

			/**
			 * Filter the arguments passed in when registering the Sensei sensei_message post type.
			 *
			 * @since 1.9.0
			 * @param array $args
			 */
			register_post_type( 'sensei_message', apply_filters( 'sensei_register_post_type_sensei_message', $args ) );
		}
	}

	/**
	 * Registers the learner taxonomy.
	 *
	 * @access private
	 */
	public function setup_learner_taxonomy() {
		register_taxonomy(
			self::LEARNER_TAXONOMY_NAME,
			'course',
			[
				'public'  => false,
				'show_ui' => false,
			]
		);
	}

	/**
	 * Setup the "course category" taxonomy, linked to the "course" post type.
	 *
	 * @since  1.1.0
	 * @return void
	 */
	public function setup_course_category_taxonomy() {
		// "Course Categories" Custom Taxonomy
		$labels = array(
			'name'              => _x( 'Course Categories', 'taxonomy general name', 'sensei-lms' ),
			'singular_name'     => _x( 'Course Category', 'taxonomy singular name', 'sensei-lms' ),
			'search_items'      => __( 'Search Course Categories', 'sensei-lms' ),
			'all_items'         => __( 'All Course Categories', 'sensei-lms' ),
			'parent_item'       => __( 'Parent Course Category', 'sensei-lms' ),
			'parent_item_colon' => __( 'Parent Course Category:', 'sensei-lms' ),
			'view_item'         => __( 'View Course Category', 'sensei-lms' ),
			'edit_item'         => __( 'Edit Course Category', 'sensei-lms' ),
			'update_item'       => __( 'Update Course Category', 'sensei-lms' ),
			'add_new_item'      => __( 'Add New Course Category', 'sensei-lms' ),
			'new_item_name'     => __( 'New Course Category Name', 'sensei-lms' ),
			'menu_name'         => __( 'Course Categories', 'sensei-lms' ),
			'popular_items'     => null, // Hides the "Popular" section above the "add" form in the admin.
			'back_to_items'     => __( '&larr; Back to Course Categories', 'sensei-lms' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_in_rest'      => true,
			'show_ui'           => true,
			'query_var'         => true,
			'show_in_nav_menus' => true,
			'capabilities'      => array(
				'manage_terms' => 'manage_categories',
				'edit_terms'   => 'edit_courses',
				'delete_terms' => 'manage_categories',
				'assign_terms' => 'edit_courses',
			),
			'rewrite'           => array( 'slug' => esc_attr( apply_filters( 'sensei_course_category_slug', _x( 'course-category', 'taxonomy archive slug', 'sensei-lms' ) ) ) ),
		);

		register_taxonomy( 'course-category', array( 'course' ), $args );

	}

	/**
	 * Setup the "quiz type" taxonomy, linked to the "quiz" post type.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function setup_quiz_type_taxonomy() {

		// "Quiz Types" Custom Taxonomy
		$labels = array(
			'name'              => _x( 'Quiz Types', 'taxonomy general name', 'sensei-lms' ),
			'singular_name'     => _x( 'Quiz Type', 'taxonomy singular name', 'sensei-lms' ),
			'search_items'      => __( 'Search Quiz Types', 'sensei-lms' ),
			'all_items'         => __( 'All Quiz Types', 'sensei-lms' ),
			'parent_item'       => __( 'Parent Quiz Type', 'sensei-lms' ),
			'parent_item_colon' => __( 'Parent Quiz Type:', 'sensei-lms' ),
			'edit_item'         => __( 'Edit Quiz Type', 'sensei-lms' ),
			'update_item'       => __( 'Update Quiz Type', 'sensei-lms' ),
			'add_new_item'      => __( 'Add New Quiz Type', 'sensei-lms' ),
			'new_item_name'     => __( 'New Quiz Type Name', 'sensei-lms' ),
			'menu_name'         => __( 'Quiz Types', 'sensei-lms' ),
			'popular_items'     => null, // Hides the "Popular" section above the "add" form in the admin.
		);

		$args = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true, /* TO DO - future releases */
			'query_var'         => true,
			'show_in_nav_menus' => false,
			'public'            => false,
			'rewrite'           => array( 'slug' => esc_attr( apply_filters( 'sensei_quiz_type_slug', _x( 'quiz-type', 'taxonomy archive slug', 'sensei-lms' ) ) ) ),
		);

		register_taxonomy( 'quiz-type', array( 'quiz' ), $args );
	}

	/**
	 * Setup the "question type" taxonomy, linked to the "question" post type.
	 *
	 * @since  1.3.0
	 * @return void
	 */
	public function setup_question_type_taxonomy() {

		// "Question Types" Custom Taxonomy
		$labels = array(
			'name'              => _x( 'Question Types', 'taxonomy general name', 'sensei-lms' ),
			'singular_name'     => _x( 'Question Type', 'taxonomy singular name', 'sensei-lms' ),
			'search_items'      => __( 'Search Question Types', 'sensei-lms' ),
			'all_items'         => __( 'All Question Types', 'sensei-lms' ),
			'parent_item'       => __( 'Parent Question Type', 'sensei-lms' ),
			'parent_item_colon' => __( 'Parent Question Type:', 'sensei-lms' ),
			'edit_item'         => __( 'Edit Question Type', 'sensei-lms' ),
			'update_item'       => __( 'Update Question Type', 'sensei-lms' ),
			'add_new_item'      => __( 'Add New Question Type', 'sensei-lms' ),
			'new_item_name'     => __( 'New Question Type Name', 'sensei-lms' ),
			'menu_name'         => __( 'Question Types', 'sensei-lms' ),
			'popular_items'     => null, // Hides the "Popular" section above the "add" form in the admin.
		);

		$args = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => false,
			'public'            => false,
			'query_var'         => false,
			'show_in_nav_menus' => false,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => esc_attr( apply_filters( 'sensei_question_type_slug', _x( 'question-type', 'taxonomy archive slug', 'sensei-lms' ) ) ) ),
		);

		register_taxonomy( 'question-type', array( 'question' ), $args );
	}

	/**
	 * Setup the "question category" taxonomy, linked to the "question" post type.
	 *
	 * @since  1.3.0
	 * @return void
	 */
	public function setup_question_category_taxonomy() {
		// "Question Categories" Custom Taxonomy
		$labels = array(
			'name'              => _x( 'Question Categories', 'taxonomy general name', 'sensei-lms' ),
			'singular_name'     => _x( 'Question Category', 'taxonomy singular name', 'sensei-lms' ),
			'search_items'      => __( 'Search Question Categories', 'sensei-lms' ),
			'all_items'         => __( 'All Question Categories', 'sensei-lms' ),
			'parent_item'       => __( 'Parent Question Category', 'sensei-lms' ),
			'parent_item_colon' => __( 'Parent Question Category:', 'sensei-lms' ),
			'view_item'         => __( 'View Question Category', 'sensei-lms' ),
			'edit_item'         => __( 'Edit Question Category', 'sensei-lms' ),
			'update_item'       => __( 'Update Question Category', 'sensei-lms' ),
			'add_new_item'      => __( 'Add New Question Category', 'sensei-lms' ),
			'new_item_name'     => __( 'New Question Category Name', 'sensei-lms' ),
			'menu_name'         => __( 'Categories', 'sensei-lms' ),
			'back_to_items'     => __( '&larr; Back to Question Categories', 'sensei-lms' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'public'            => false,
			'query_var'         => false,
			'show_in_nav_menus' => false,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'capabilities'      => array(
				'manage_terms' => 'manage_categories',
				'edit_terms'   => 'edit_questions',
				'delete_terms' => 'manage_categories',
				'assign_terms' => 'edit_questions',
			),
			'rewrite'           => array( 'slug' => esc_attr( apply_filters( 'sensei_question_category_slug', _x( 'question-category', 'taxonomy archive slug', 'sensei-lms' ) ) ) ),
		);

		register_taxonomy( 'question-category', array( 'question' ), $args );
	}

	/**
	 * Setup the "lesson tags" taxonomy, linked to the "lesson" post type.
	 *
	 * @since  1.5.0
	 * @return void
	 */
	public function setup_lesson_tag_taxonomy() {
		// "Lesson Tags" Custom Taxonomy
		$labels = array(
			'name'              => _x( 'Lesson Tags', 'taxonomy general name', 'sensei-lms' ),
			'singular_name'     => _x( 'Lesson Tag', 'taxonomy singular name', 'sensei-lms' ),
			'search_items'      => __( 'Search Lesson Tags', 'sensei-lms' ),
			'all_items'         => __( 'All Lesson Tags', 'sensei-lms' ),
			'parent_item'       => __( 'Parent Tag', 'sensei-lms' ),
			'parent_item_colon' => __( 'Parent Tag:', 'sensei-lms' ),
			'view_item'         => __( 'View Lesson Tag', 'sensei-lms' ),
			'edit_item'         => __( 'Edit Lesson Tag', 'sensei-lms' ),
			'update_item'       => __( 'Update Lesson Tag', 'sensei-lms' ),
			'add_new_item'      => __( 'Add New Lesson Tag', 'sensei-lms' ),
			'new_item_name'     => __( 'New Tag Name', 'sensei-lms' ),
			'menu_name'         => __( 'Lesson Tags', 'sensei-lms' ),
			'back_to_items'     => __( '&larr; Back to Lesson Tags', 'sensei-lms' ),
		);

		$args = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_in_rest'      => true,
			'show_ui'           => true,
			'query_var'         => true,
			'show_in_nav_menus' => true,
			'capabilities'      => array(
				'manage_terms' => 'manage_categories',
				'edit_terms'   => 'edit_lessons',
				'delete_terms' => 'manage_categories',
				'assign_terms' => 'edit_lessons',
			),
			'rewrite'           => array( 'slug' => esc_attr( apply_filters( 'sensei_lesson_tag_slug', _x( 'lesson-tag', 'taxonomy archive slug', 'sensei-lms' ) ) ) ),
		);

		register_taxonomy( 'lesson-tag', array( 'lesson' ), $args );
	}

	/**
	 * Setup the singular, plural and menu label names for the post types.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private function setup_post_type_labels_base() {
		$this->labels = array(
			'course'   => array(),
			'lesson'   => array(),
			'quiz'     => array(),
			'question' => array(),
		);

		$this->labels['course']            = array(
			'singular' => __( 'Course', 'sensei-lms' ),
			'plural'   => __( 'Courses', 'sensei-lms' ),
			'menu'     => __( 'Courses', 'sensei-lms' ),
		);
		$this->labels['lesson']            = array(
			'singular' => __( 'Lesson', 'sensei-lms' ),
			'plural'   => __( 'Lessons', 'sensei-lms' ),
			'menu'     => __( 'Lessons', 'sensei-lms' ),
		);
		$this->labels['quiz']              = array(
			'singular' => __( 'Quiz', 'sensei-lms' ),
			'plural'   => __( 'Quizzes', 'sensei-lms' ),
			'menu'     => __( 'Quizzes', 'sensei-lms' ),
		);
		$this->labels['question']          = array(
			'singular' => __( 'Question', 'sensei-lms' ),
			'plural'   => __( 'Questions', 'sensei-lms' ),
			'menu'     => __( 'Questions', 'sensei-lms' ),
		);
		$this->labels['multiple_question'] = array(
			'singular' => __( 'Multiple Question', 'sensei-lms' ),
			'plural'   => __( 'Multiple Questions', 'sensei-lms' ),
			'menu'     => __( 'Multiple Questions', 'sensei-lms' ),
		);
		$this->labels['sensei_message']    = array(
			'singular' => __( 'Message', 'sensei-lms' ),
			'plural'   => __( 'Messages', 'sensei-lms' ),
			'menu'     => __( 'Messages', 'sensei-lms' ),
		);

	}

	/**
	 * Create the labels for a specified post type.
	 *
	 * @since  1.0.0
	 * @param  string $singular The label for a singular instance of the post type
	 * @param  string $plural   The label for a plural instance of the post type
	 * @param  string $menu     The menu item label
	 * @return array            An array of the labels to be used
	 */
	private function create_post_type_labels( $singular, $plural, $menu ) {

		$lower_case_plural = function_exists( 'mb_strtolower' ) ? mb_strtolower( $plural, 'UTF-8' ) : strtolower( $plural );

		$labels = array(
			'name'               => $plural,
			'singular_name'      => $singular,
			'add_new'            => __( 'Add New', 'sensei-lms' ),
			// translators: Placeholder is the singular post type label.
			'add_new_item'       => sprintf( __( 'Add New %s', 'sensei-lms' ), $singular ),
			// translators: Placeholder is the singular post type label.
			'edit_item'          => sprintf( __( 'Edit %s', 'sensei-lms' ), $singular ),
			// translators: Placeholder is the singular post type label.
			'new_item'           => sprintf( __( 'New %s', 'sensei-lms' ), $singular ),
			// translators: Placeholder is the plural post type label.
			'all_items'          => sprintf( __( 'All %s', 'sensei-lms' ), $plural ),
			// translators: Placeholder is the singular post type label.
			'view_item'          => sprintf( __( 'View %s', 'sensei-lms' ), $singular ),
			// translators: Placeholder is the plural post type label.
			'search_items'       => sprintf( __( 'Search %s', 'sensei-lms' ), $plural ),
			// translators: Placeholder is the lower-case plural post type label.
			'not_found'          => sprintf( __( 'No %s found', 'sensei-lms' ), $lower_case_plural ),
			// translators: Placeholder is the lower-case plural post type label.
			'not_found_in_trash' => sprintf( __( 'No %s found in Trash', 'sensei-lms' ), $lower_case_plural ),
			'parent_item_colon'  => '',
			'menu_name'          => $menu,
		);

		return $labels;
	}

	/**
	 * Setup update messages for the post types.
	 *
	 * @since  1.0.0
	 * @param  array $messages The existing array of messages for post types.
	 * @return array           The modified array of messages for post types.
	 */
	public function setup_post_type_messages( $messages ) {
		$messages['course']            = $this->create_post_type_messages( 'course' );
		$messages['lesson']            = $this->create_post_type_messages( 'lesson' );
		$messages['quiz']              = $this->create_post_type_messages( 'quiz' );
		$messages['question']          = $this->create_post_type_messages( 'question' );
		$messages['multiple_question'] = $this->create_post_type_messages( 'multiple_question' );

		return $messages;
	}

	/**
	 * Create an array of messages for a specified post type.
	 *
	 * @since  1.0.0
	 * @param  string $post_type The post type for which to create messages.
	 * @return array            An array of messages (empty array if the post type isn't one we're looking to work with).
	 */
	private function create_post_type_messages( $post_type ) {
		global $post, $post_ID;

		if ( ! isset( $this->labels[ $post_type ] ) ) {
			return array(); }

		$messages = array(
			0  => '',
			// translators: Placeholders are the singular label for the post type and the post's permalink, respectively.
			1  => sprintf( __( '%1$s updated. %2$sView %1$s%3$s.', 'sensei-lms' ), $this->labels[ $post_type ]['singular'], '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
			2  => __( 'Custom field updated.', 'sensei-lms' ),
			3  => __( 'Custom field deleted.', 'sensei-lms' ),
			// translators: Placeholder is the singular label for the post type.
			4  => sprintf( __( '%1$s updated.', 'sensei-lms' ), $this->labels[ $post_type ]['singular'] ),
			// translators: Placeholders are the singular label for the post type and the post's revision, respectively.
			5  => isset( $_GET['revision'] ) ? sprintf( __( '%1$s restored to revision from %2$s.', 'sensei-lms' ), $this->labels[ $post_type ]['singular'], wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			// translators: Placeholders are the singular label for the post type and the post's permalink, respectively.
			6  => sprintf( __( '%1$s published. %2$sView %1$s%3$s.', 'sensei-lms' ), $this->labels[ $post_type ]['singular'], '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
			// translators: Placeholder is the singular label for the post type.
			7  => sprintf( __( '%1$s saved.', 'sensei-lms' ), $this->labels[ $post_type ]['singular'] ),
			// translators: Placeholders are the singular label for the post type and the post's preview link, respectively.
			8  => sprintf( __( '%1$s submitted. %2$sPreview %1$s%3$s.', 'sensei-lms' ), $this->labels[ $post_type ]['singular'], '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', '</a>' ),
			/*
			  * translators: Placeholders are as follows (in order):
			  *
			  * - The singular label for the post type.
			  * - The formatted post date.
			  * - The opening tag for the post's permalink.
			  * - The closing tag for the post's permalink.
			  */
			9  => sprintf( __( '%1$s scheduled for: %2$s. %3$sPreview %4$s%5$s.', 'sensei-lms' ), $this->labels[ $post_type ]['singular'], '<strong>' . date_i18n( __( 'M j, Y @ G:i', 'sensei-lms' ), strtotime( $post->post_date ) ) . '</strong>', '<a target="_blank" href="' . esc_url( get_permalink( $post_ID ) ) . '">', $this->labels[ $post_type ]['singular'], '</a>' ),
			// translators: Placeholders are the singular label for the post type and the post's preview link, respectively.
			10 => sprintf( __( '%1$s draft updated. %2$sPreview %3$s%4$s.', 'sensei-lms' ), $this->labels[ $post_type ]['singular'], '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', $this->labels[ $post_type ]['singular'], '</a>' ),
		);

		return $messages;
	}

	/**
	 * Change the "Enter Title Here" text for the "slide" post type.
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  string $title
	 * @return string $title
	 */
	public function enter_title_here( $title ) {
		if ( get_post_type() == 'course' ) {
			$title = __( 'Course name', 'sensei-lms' );
		} elseif ( get_post_type() == 'lesson' ) {
			$title = __( 'Lesson name', 'sensei-lms' );
		}

		return $title;
	}

	/**
	 * Assigns the defaults for each user role capabilities.
	 *
	 * @since  1.1.0
	 *
	 * @param array $post_types
	 * @return void
	 */
	public function set_role_cap_defaults( $post_types = array() ) {

		foreach ( $post_types as $post_type_item => $post_type_name ) {
			// Super Admin
			$this->role_caps[] = array(
				'administrator' => array(
					'edit_' . $post_type_item,
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
					'manage_sensei_grades',
				),
				'editor'        => array(
					'edit_' . $post_type_item,
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
				),
				'author'        => array(
					'edit_' . $post_type_item,
					'read_' . $post_type_item,
					'delete_' . $post_type_item,
					'create_' . $post_type_item . 's',
					'edit_' . $post_type_item . 's',
					'publish_' . $post_type_item . 's',
					'read',
					'delete_' . $post_type_item . 's',
					'delete_published_' . $post_type_item . 's',
					'edit_published_' . $post_type_item . 's',
				),
				'contributor'   => array(
					'edit_' . $post_type_item,
					'read_' . $post_type_item,
					'delete_' . $post_type_item,
					'create_' . $post_type_item . 's',
					'edit_' . $post_type_item . 's',
					'read',
					'delete_' . $post_type_item . 's',
				),
				'subscriber'    => array( 'read' ),

			);
		}

	}

	/**
	 * Adds a 'Edit Quiz' link to the admin bar when viewing a Quiz linked to a corresponding Lesson
	 *
	 * @since  1.7.0
	 * @param WP_Admin_Bar $bar
	 * @return void
	 */
	public function quiz_admin_bar_menu( $bar ) {
		if ( is_single() && 'quiz' == get_queried_object()->post_type ) {
			$lesson_id = get_post_meta( get_queried_object()->ID, '_quiz_lesson', true );
			if ( $lesson_id ) {
				$object_type = get_post_type_object( 'quiz' );
				$bar->add_menu(
					array(
						'id'    => 'edit',
						'title' => $object_type->labels->edit_item,
						'href'  => get_edit_post_link( $lesson_id ),
					)
				);
			}
		}
	}

	/**
	 * Setup firing of the "initial publish" action for Sensei CPT's. This will
	 * set up hooks to track when posts are published, and to fire the "initial
	 * publish" action at the correct time.
	 *
	 * However, this action will not be fired for posts that are created through
	 * the REST API. This is because of an edge case with the block editor. When
	 * a post is published through the block editor, the "initial publish"
	 * action will be fired when the metabox save request is posted, rather than
	 * when the initial API request is posted.
	 *
	 * Note that the REST API restriction can be removed when we migrate all
	 * meta information for the block editor away from metaboxes and into
	 * blocks.
	 *
	 * @since 2.1.0
	 * @access private
	 */
	public function setup_initial_publish_action() {
		$this->reset_scheduled_initial_publish_actions();

		// Schedule an action for initial publish of Sensei CPT's.
		add_action( 'transition_post_status', [ $this, 'maybe_schedule_initial_publish_action' ], 10, 3 );

		// Fire all scheduled actions on shutdown.
		add_action( 'shutdown', [ $this, 'fire_scheduled_initial_publish_actions' ] );

		// Never fire actions on REST API request.
		add_action( 'rest_api_init', [ $this, 'disable_fire_scheduled_initial_publish_actions' ] );
	}

	/**
	 * Disable the scheduled "initial publish" actions from being fired. This is
	 * called on `rest_api_init`.
	 *
	 * @since 2.1.0
	 * @access private
	 */
	public function disable_fire_scheduled_initial_publish_actions() {
		remove_action( 'shutdown', [ $this, 'fire_scheduled_initial_publish_actions' ] );
	}

	/**
	 * This hook is run on `post_status_transition` to schedule the "initial
	 * publish" action if needed.
	 *
	 * Posts will be marked as already published if the old status is `publish`,
	 * so that we do not fire the "initial publish" action for existing publish
	 * posts when they are re-published.
	 *
	 * For newly published posts, we schedule the "initial publish" action to be
	 * fired at the end of the request.
	 *
	 * Note that we do not mark as published if this is a metabox update
	 * request. In this case, the REST API request has already handled this, so
	 * we just need to schedule the action if needed.
	 *
	 * @since 2.1.0
	 * @access private
	 *
	 * @param string  $new_status The new post status.
	 * @param string  $old_status The old post status.
	 * @param WP_Post $post       The post.
	 */
	public function maybe_schedule_initial_publish_action( $new_status, $old_status, $post ) {
		// Only handle Sensei post types.
		if ( ! $this->is_sensei_post_type_for_initial_publish_action( $post->post_type ) ) {
			return;
		}

		// If the old status is `publish`, mark as already published.
		if ( 'publish' === $old_status && ! $this->is_meta_box_save_request() ) {
			$this->mark_post_already_published( $post->ID );
		}

		// If transitioning to `publish` for the first time, schedule the action.
		if ( 'publish' === $new_status && ! $this->check_post_already_published( $post->ID ) ) {
			$this->schedule_initial_publish_action( $post->ID );
		}
	}

	/**
	 * Fire the scheduled "initial publish" actions. This is run on `shutdown`.
	 *
	 * @since 2.1.0
	 * @access private
	 */
	public function fire_scheduled_initial_publish_actions() {
		foreach ( array_unique( $this->initial_publish_post_ids ) as $post_id ) {
			$post = get_post( $post_id );
			if ( $post ) {
				do_action( "sensei_{$post->post_type}_initial_publish", $post );
				$this->mark_post_already_published( $post->ID );
			}
		}

		// Clear the finished post ID's.
		$this->reset_scheduled_initial_publish_actions();
	}

	/**
	 * Determine whether the current request is a "meta box save" request
	 * (typically run by the block editor).
	 *
	 * @since 2.1.0
	 * @access private
	 */
	private function is_meta_box_save_request() {
		// phpcs:ignore WordPress.Security.NonceVerification
		return isset( $_REQUEST['meta-box-loader'] ) && '1' === $_REQUEST['meta-box-loader'];
	}

	/**
	 * Schedule an "initial publish" action for the given post ID.
	 *
	 * @since 2.1.0
	 * @access private
	 *
	 * @param int $post_id The post ID.
	 */
	private function schedule_initial_publish_action( $post_id ) {
		$this->initial_publish_post_ids[] = $post_id;
	}

	/**
	 * Reset the array of post ID's for which to fire "initial publish" actions.
	 *
	 * @since 2.1.0
	 * @access private
	 */
	private function reset_scheduled_initial_publish_actions() {
		$this->initial_publish_post_ids = [];
	}

	/**
	 * Check if post type is one for which we should fire the "initial publish"
	 * action.
	 *
	 * @since 2.1.0
	 *
	 * @param string $post_type The post type.
	 * @return bool
	 */
	private function is_sensei_post_type_for_initial_publish_action( $post_type ) {
		/**
		 * Filter the post types for which to fire an action on initial publish.
		 *
		 * @since 2.1.0
		 *
		 * @param array $post_types The post types.
		 */
		$post_types = apply_filters(
			'sensei_post_types_for_initial_publish_action',
			[
				'course',
				'lesson',
				'quiz',
				'question',
				'sensei_message',
			]
		);
		return in_array( $post_type, $post_types, true );
	}

	/**
	 * Mark the given post as "already published".
	 *
	 * @since 2.1.0
	 *
	 * @param string $post_id The post ID.
	 */
	private function mark_post_already_published( $post_id ) {
		add_post_meta( $post_id, '_sensei_already_published', true, true );
	}

	/**
	 * Check whether the post is marked as "already published".
	 *
	 * @since 2.1.0
	 *
	 * @param string $post_id The post ID.
	 * @return bool
	 */
	private function check_post_already_published( $post_id ) {
		return get_post_meta( $post_id, '_sensei_already_published', true );
	}

}

/**
 * Class WooThemes_Sensei_PostTypes
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_PostTypes extends Sensei_PostTypes{}
