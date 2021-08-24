<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Sensei Course Class
 *
 * All functionality pertaining to the Courses Post Type in Sensei.
 *
 * @package Content
 * @author Automattic
 * @since 1.0.0
 */
class Sensei_Course {

	/**
	 * @var $token
	 */
	public $token;

	/**
	 * @var array $meta_fields
	 */
	public $meta_fields;

	/**
	 * @var string|bool $my_courses_page reference to the sites
	 * my courses page, false if none was set
	 */
	public $my_courses_page;

	/**
	 * Course ID being saved, if no resave is needed.
	 * The resave will be needed when updating a course with
	 * outline block which needs id sync.
	 *
	 * @since 3.6.0
	 *
	 * @var int
	 */
	private $course_id_updating;

	/**
	 * @var array The HTML allowed for message boxes.
	 */
	public static $allowed_html;

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {

		$this->token = 'course';

		add_action( 'init', array( $this, 'set_up_meta_fields' ) );

		// Admin actions
		if ( is_admin() ) {
			// Metabox functions
			add_action( 'add_meta_boxes', array( $this, 'meta_box_setup' ), 20 );
			add_action( 'save_post', array( $this, 'meta_box_save' ) );

			// Custom Write Panel Columns
			add_filter( 'manage_course_posts_columns', array( $this, 'add_column_headings' ), 20, 1 );
			add_action( 'manage_course_posts_custom_column', array( $this, 'add_column_data' ), 10, 2 );

			// Enqueue scripts.
			add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ) );
		} else {
			$this->my_courses_page = false;
		}

		self::$allowed_html = array(
			'embed'  => array(),
			'iframe' => array(
				'width'           => array(),
				'height'          => array(),
				'src'             => array(),
				'frameborder'     => array(),
				'allowfullscreen' => array(),
			),
			'video'  => Sensei_Wp_Kses::get_video_html_tag_allowed_attributes(),
			'source' => Sensei_Wp_Kses::get_source_html_tag_allowed_attributes(),
		);

		// Update course completion upon completion of a lesson
		add_action( 'sensei_user_lesson_end', array( $this, 'update_status_after_lesson_change' ), 10, 2 );
		// Update course completion upon reset of a lesson
		add_action( 'sensei_user_lesson_reset', array( $this, 'update_status_after_lesson_change' ), 10, 2 );
		// Update course completion upon grading of a quiz
		add_action( 'sensei_user_quiz_grade', array( $this, 'update_status_after_quiz_submission' ), 10, 2 );

		// provide an option to block all emails related to a selected course
		add_filter( 'sensei_send_emails', array( $this, 'block_notification_emails' ) );
		add_action( 'save_post', array( $this, 'save_course_notification_meta_box' ) );

		// Log course content counter.
		add_action( 'save_post_course', [ $this, 'mark_updating_course_id' ], 10, 2 );
		add_action( 'shutdown', [ $this, 'log_course_update' ] );
		add_action( 'rest_api_init', [ $this, 'disable_log_course_update' ] );

		// preview lessons on the course content
		add_action( 'sensei_course_content_inside_after', array( $this, 'the_course_free_lesson_preview' ) );

		// the course meta
		add_action( 'sensei_course_content_inside_before', array( $this, 'the_course_meta' ) );

		// The course enrolment actions.
		add_action( 'sensei_output_course_enrolment_actions', array( __CLASS__, 'output_course_enrolment_actions' ) );

		// add the user status on the course to the markup as a class
		add_filter( 'post_class', array( __CLASS__, 'add_course_user_status_class' ), 20, 3 );

		// filter the course query in Sensei specific instances
		add_filter( 'pre_get_posts', array( __CLASS__, 'course_query_filter' ) );

		// attache the sorting to the course archive
		add_action( 'sensei_archive_before_course_loop', array( 'Sensei_Course', 'course_archive_sorting' ) );

		// attach the filter links to the course archive
		add_action( 'sensei_archive_before_course_loop', array( 'Sensei_Course', 'course_archive_filters' ) );

		// filter the course query when featured filter is applied
		add_filter( 'pre_get_posts', array( __CLASS__, 'course_archive_featured_filter' ), 10, 1 );

		// handle the order by title post submission
		add_filter( 'pre_get_posts', array( __CLASS__, 'course_archive_order_by_title' ), 10, 1 );

		// ensure the course category page respects the manual order set for courses
		add_filter( 'pre_get_posts', array( __CLASS__, 'alter_course_category_order' ), 10, 1 );

		// Allow course archive to be setup as the home page
		if ( (int) get_option( 'page_on_front' ) > 0 ) {
			add_action( 'pre_get_posts', array( $this, 'allow_course_archive_on_front_page' ), 9, 1 );
		}

		// Log event on the initial publish for a course.
		add_action( 'sensei_course_initial_publish', [ $this, 'log_initial_publish_event' ] );

		add_action( 'template_redirect', [ $this, 'setup_single_course_page' ] );
		add_action( 'sensei_loaded', [ $this, 'add_legacy_course_hooks' ] );
	}

	/**
	 * Register and enqueue scripts that are needed in the backend.
	 *
	 * @access private
	 * @since 2.1.0
	 */
	public function register_admin_scripts() {
		$screen = get_current_screen();

		if ( 'course' === $screen->id ) {
			Sensei()->assets->enqueue( 'sensei-admin-course-edit', 'js/admin/course-edit.js', [ 'jquery', 'sensei-core-select2' ], true );
		}
	}

	/**
	 * Check if a user is enrolled in a course.
	 *
	 * @since 3.0.0
	 *
	 * @param int      $course_id Course post ID.
	 * @param int|null $user_id   User ID.
	 * @return bool
	 */
	public static function is_user_enrolled( $course_id, $user_id = null ) {
		if ( empty( $course_id ) ) {
			return false;
		}

		if ( 'course' !== get_post_type( $course_id ) ) {
			return false;
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		return $course_enrolment->is_enrolled( $user_id );
	}

	/**
	 * Check if a visitor can access course content.
	 *
	 * This is just part of the check for lessons and quizzes. To include checks for prerequisites and preview lessons,
	 * use the global template function `sensei_can_user_view_lesson()`.
	 *
	 * @param int    $course_id Course post ID.
	 * @param int    $user_id   User ID.
	 * @param string $context   Context that we're checking for course content access (`lesson`, `quiz`, or `module`).
	 */
	public function can_access_course_content( $course_id, $user_id = null, $context = 'lesson' ) {
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		$can_view_course_content = false;
		$is_user_enrolled        = false;
		if ( ! empty( $user_id ) ) {
			$is_user_enrolled = self::is_user_enrolled( $course_id, $user_id );
		}

		if (
			! sensei_is_login_required()
			|| sensei_all_access( $user_id )
			|| $is_user_enrolled
		) {
			$can_view_course_content = true;
		}

		/**
		 * Filters if a visitor can view course content.
		 *
		 * @since 3.0.0
		 * @hook sensei_can_access_course_content
		 *
		 * @param {bool}   $can_view_course_content True if they can view the course content.
		 * @param {int}    $course_id               Course post ID.
		 * @param {int}    $user_id                 User ID if user is logged in.
		 * @param {string} $context                 Context that we're checking for course content
		 *                                        access (`lesson`, `quiz`, or `module`).
		 * @return {bool} Whether the visitor can view course content.
		 */
		return apply_filters( 'sensei_can_access_course_content', $can_view_course_content, $course_id, $user_id, $context );
	}

	/**
	 * @param $message
	 */
	private static function add_course_access_permission_message( $message ) {
		global $post;
		if ( Sensei()->settings->get( 'access_permission' ) ) {
			$message = apply_filters_deprecated(
				'sensei_couse_access_permission_message',
				[ $message, $post->ID ],
				'3.0.0',
				null
			);

			if ( ! empty( $message ) ) {
				Sensei()->notices->add_notice( $message, 'info' );
			}
		}
	}

	/**
	 * Fires when a quiz has been graded to check if the Course status needs changing
	 *
	 * @param type $user_id
	 * @param type $quiz_id
	 */
	public function update_status_after_quiz_submission( $user_id, $quiz_id ) {
		if ( intval( $user_id ) > 0 && intval( $quiz_id ) > 0 ) {
			$lesson_id = get_post_meta( $quiz_id, '_quiz_lesson', true );
			$this->update_status_after_lesson_change( $user_id, $lesson_id );
		}
	}

	/**
	 * Fires when a lesson has changed to check if the Course status needs changing
	 *
	 * @param int $user_id
	 * @param int $lesson_id
	 */
	public function update_status_after_lesson_change( $user_id, $lesson_id ) {
		if ( intval( $user_id ) > 0 && intval( $lesson_id ) > 0 ) {
			$course_id = get_post_meta( $lesson_id, '_lesson_course', true );
			if ( intval( $course_id ) > 0 ) {
				// Updates the Course status and it's meta data
				Sensei_Utils::user_complete_course( $course_id, $user_id );
			}
		}
	}

	/**
	 * Sets up the meta fields used for courses.
	 */
	public function set_up_meta_fields() {
		/**
		 * Sets up the meta fields saved on course save in WP admin.
		 *
		 * @since 2.0.0
		 *
		 * @param string[] $course_meta_fields Array of meta field key names to save on course save.
		 */
		$this->meta_fields = apply_filters( 'sensei_course_meta_fields', array( 'course_prerequisite', 'course_featured', 'course_video_embed' ) );
	}

	/**
	 * meta_box_setup function.
	 *
	 * @access public
	 * @return void
	 */
	public function meta_box_setup() {

		// Add Meta Box for Prerequisite Course
		add_meta_box( 'course-prerequisite', __( 'Course Prerequisite', 'sensei-lms' ), array( $this, 'course_prerequisite_meta_box_content' ), $this->token, 'side', 'default' );
		// Add Meta Box for Featured Course
		add_meta_box( 'course-featured', __( 'Featured Course', 'sensei-lms' ), array( $this, 'course_featured_meta_box_content' ), $this->token, 'side', 'default' );
		// Add Meta Box for Course Meta
		add_meta_box( 'course-video', __( 'Course Video', 'sensei-lms' ), array( $this, 'course_video_meta_box_content' ), $this->token, 'normal', 'default' );
		// Add Meta Box for Course Lessons
		add_meta_box( 'course-lessons', __( 'Course Lessons', 'sensei-lms' ), array( $this, 'course_lessons_meta_box_content' ), $this->token, 'normal', 'default' );
		// Add Meta Box to link to Manage Learners
		add_meta_box( 'course-manage', __( 'Course Management', 'sensei-lms' ), array( $this, 'course_manage_meta_box_content' ), $this->token, 'side', 'default' );
		// Remove "Custom Settings" meta box.
		remove_meta_box( 'woothemes-settings', $this->token, 'normal' );

		// add Disable email notification box
		add_meta_box( 'course-notifications', __( 'Course Notifications', 'sensei-lms' ), array( $this, 'course_notification_meta_box_content' ), 'course', 'normal', 'default' );

	}

	/**
	 * course_prerequisite_meta_box_content function.
	 *
	 * @access public
	 * @return void
	 */
	public function course_prerequisite_meta_box_content() {
		global $post;

		$select_course_prerequisite = get_post_meta( $post->ID, '_course_prerequisite', true );

		$post_args   = array(
			'post_type'        => 'course',
			'posts_per_page'   => -1,
			'orderby'          => 'title',
			'order'            => 'DESC',
			'exclude'          => $post->ID,
			'suppress_filters' => 0,
			'post_status'      => 'any',
		);
		$posts_array = get_posts( $post_args );

		$html = '';

		$html .= '<input type="hidden" name="' . esc_attr( 'woo_' . $this->token . '_noonce' ) . '" id="' . esc_attr( 'woo_' . $this->token . '_noonce' ) . '" value="' . esc_attr( wp_create_nonce( plugin_basename( __FILE__ ) ) ) . '" />';

		if ( count( $posts_array ) > 0 ) {
			$html .= '<select id="course-prerequisite-options" name="course_prerequisite" class="chosen_select widefat">' . "\n";
			$html .= '<option value="">' . esc_html__( 'None', 'sensei-lms' ) . '</option>';
			foreach ( $posts_array as $post_item ) {
				$html .= '<option value="' . esc_attr( absint( $post_item->ID ) ) . '"' . selected( $post_item->ID, $select_course_prerequisite, false ) . '>' . esc_html( $post_item->post_title ) . '</option>' . "\n";
			}
			$html .= '</select>' . "\n";
		} else {
			$html .= '<p>' . esc_html__( 'No courses exist yet. Please add some first.', 'sensei-lms' ) . '</p>';
		}

		echo wp_kses(
			$html,
			array_merge(
				wp_kses_allowed_html( 'post' ),
				array(
					'input'  => array(
						'id'    => array(),
						'name'  => array(),
						'type'  => array(),
						'value' => array(),
					),
					'option' => array(
						'selected' => array(),
						'value'    => array(),
					),
					'select' => array(
						'class' => array(),
						'id'    => array(),
						'name'  => array(),
					),
				)
			)
		);
	}

	/**
	 * course_featured_meta_box_content function.
	 *
	 * @access public
	 * @return void
	 */
	public function course_featured_meta_box_content() {
		global $post;

		$course_featured = get_post_meta( $post->ID, '_course_featured', true );

		$html = '';

		$html .= '<input type="hidden" name="' . esc_attr( 'woo_' . $this->token . '_noonce' ) . '" id="' . esc_attr( 'woo_' . $this->token . '_noonce' ) . '" value="' . esc_attr( wp_create_nonce( plugin_basename( __FILE__ ) ) ) . '" />';

		$checked = '';
		if ( isset( $course_featured ) && ( '' != $course_featured ) ) {
			$checked = checked( 'featured', $course_featured, false );
		}

		$html .= '<input type="checkbox" name="course_featured" value="featured" ' . $checked . '>&nbsp;' . esc_html__( 'Feature this course', 'sensei-lms' ) . '<br>';

		echo wp_kses(
			$html,
			array_merge(
				wp_kses_allowed_html( 'post' ),
				array(
					'input' => array(
						'checked' => array(),
						'id'      => array(),
						'name'    => array(),
						'type'    => array(),
						'value'   => array(),
					),
				)
			)
		);
	}

	/**
	 * course_video_meta_box_content function.
	 *
	 * @access public
	 * @return void
	 */
	public function course_video_meta_box_content() {
		global $post;

		$course_video_embed = get_post_meta( $post->ID, '_course_video_embed', true );
		$course_video_embed = Sensei_Wp_Kses::maybe_sanitize( $course_video_embed, self::$allowed_html );

		$html = '';

		$html .= '<label class="screen-reader-text" for="course_video_embed">' . esc_html__( 'Video Embed Code', 'sensei-lms' ) . '</label>';
		$html .= '<textarea rows="5" cols="50" name="course_video_embed" tabindex="6" id="course-video-embed">';

		$html .= $course_video_embed . '</textarea><p>';

		$html .= esc_html__( 'Paste the embed code for your video (e.g. YouTube, Vimeo etc.) in the box above.', 'sensei-lms' ) . '</p>';

		echo wp_kses(
			$html,
			array_merge(
				wp_kses_allowed_html( 'post' ),
				self::$allowed_html,
				array(
					// Explicitly allow label tag for WP.com.
					'label'    => array(
						'class' => array(),
						'for'   => array(),
					),
					'textarea' => array(
						'cols'     => array(),
						'id'       => array(),
						'name'     => array(),
						'rows'     => array(),
						'tabindex' => array(),
					),
				)
			)
		);
	}

	/**
	 * meta_box_save function.
	 *
	 * Handles saving the meta data
	 *
	 * @access public
	 * @param int $post_id
	 * @return int
	 */
	public function meta_box_save( $post_id ) {
		global $post;

		/* Verify the nonce before proceeding. */
		if ( ( get_post_type() != $this->token ) || ! isset( $_POST[ 'woo_' . $this->token . '_noonce' ] ) || ! wp_verify_nonce( $_POST[ 'woo_' . $this->token . '_noonce' ], plugin_basename( __FILE__ ) ) ) {
			return;
		}

		/* Get the post type object. */
		$post_type = get_post_type_object( $post->post_type );

		/* Check if the current user has permission to edit the post. */
		if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
			return $post_id;
		}

		if ( 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			}
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}
		}

		// Save the post meta data fields
		if ( isset( $this->meta_fields ) && is_array( $this->meta_fields ) ) {
			foreach ( $this->meta_fields as $meta_key ) {
				$this->save_post_meta( $meta_key, $post_id );
			}
		}

	}


	/**
	 * save_post_meta function.
	 *
	 * Does the save
	 *
	 * @access private
	 * @param string $post_key (default: '')
	 * @param int    $post_id (default: 0)
	 * @return int new meta id | bool meta value saved status
	 */
	private function save_post_meta( $post_key = '', $post_id = 0 ) {
		/*
		 * This function is called from `meta_box_save` where the nonce is
		 * verified. We can ignore nonce verification here.
		 */

		// Get the meta key.
		$meta_key = '_' . $post_key;
		// Get the posted data and sanitize it for use as an HTML class.
		if ( 'course_video_embed' == $post_key ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$new_meta_value = ( isset( $_POST[ $post_key ] ) ) ? $_POST[ $post_key ] : '';
			$new_meta_value = Sensei_Wp_Kses::maybe_sanitize( $new_meta_value, self::$allowed_html );
		} else {
			// phpcs:ignore WordPress.Security.NonceVerification
			$new_meta_value = ( isset( $_POST[ $post_key ] ) ? sanitize_html_class( $_POST[ $post_key ] ) : '' );
		}

		/**
		 * Action before saving the meta value.
		 *
		 * @since 2.2.0
		 *
		 * @param int    $post_id        The course ID.
		 * @param string $meta_key       The meta to be saved.
		 * @param mixed  $new_meta_value The meta value to be saved.
		 */
		do_action( 'sensei_course_meta_before_save', $post_id, $meta_key, $new_meta_value );

		/**
		 * Filter whether or not to run the default save functionality for the
		 * meta. This may be used with the
		 * "sensei_course_meta_before_save" action to create custom
		 * save functionality for specific meta.
		 *
		 * @since 2.2.0
		 *
		 * @param bool   $do_save        Whether or not to do the default save.
		 * @param int    $post_id        The course ID.
		 * @param string $meta_key       The meta to be saved.
		 * @param mixed  $new_meta_value The meta value to be saved.
		 */
		if ( apply_filters( 'sensei_course_meta_default_save', true, $post_id, $meta_key, $new_meta_value ) ) {
			// Update meta field with the new value
			return update_post_meta( $post_id, $meta_key, $new_meta_value );
		}

	}

	/**
	 * course_lessons_meta_box_content function.
	 *
	 * @access public
	 * @return void
	 */
	public function course_lessons_meta_box_content() {

		global $post;

		// Setup Lesson Query
		$posts_array = array();
		if ( 0 < $post->ID ) {

			$posts_array = $this->course_lessons( $post->ID, 'any' );

		}

		$html  = '';
		$html .= '<input type="hidden" name="' . esc_attr( 'woo_' . $this->token . '_noonce' ) . '" id="'
				 . esc_attr( 'woo_' . $this->token . '_noonce' )
				 . '" value="' . esc_attr( wp_create_nonce( plugin_basename( __FILE__ ) ) ) . '" />';

		$course_id            = ( 0 < $post->ID ) ? '&course_id=' . $post->ID : '';
		$add_lesson_admin_url = admin_url( 'post-new.php?post_type=lesson' . $course_id );

		if ( count( $posts_array ) > 0 ) {

			foreach ( $posts_array as $post_item ) {

				$html .= '<p>' . "\n";

					$html .= esc_html( $post_item->post_title ) . "\n";
				$html     .= '<a href="'
					. esc_url( get_edit_post_link( $post_item->ID ) )
					. '" title="'
					// translators: Placeholder is the Lesson title.
					. esc_attr( sprintf( __( 'Edit %s', 'sensei-lms' ), $post_item->post_title ) )
					. '" data-course-status="' . esc_attr( $post->post_status )
					. '" class="edit-lesson-action">'
					. esc_html__( 'Edit this lesson', 'sensei-lms' )
					. '</a>';

				$html .= '</p>' . "\n";

			}
		}
		$html .= '<p>';
		if ( 0 === count( $posts_array ) ) {
			$html .= esc_html__( 'No lessons exist yet for this course.', 'sensei-lms' ) . "\n";
		} else {
			$html .= '<hr />';
		}
		$html .= '<a class="add-course-lesson" href="' . esc_url( $add_lesson_admin_url )
			. '" data-course-status="' . esc_attr( $post->post_status )
			. '" title="' . esc_attr__( 'Add a Lesson', 'sensei-lms' ) . '">';
		if ( count( $posts_array ) < 1 ) {
			$html .= esc_html__( 'Please add some.', 'sensei-lms' );
		} else {

			$html .= esc_html__( '+ Add Another Lesson', 'sensei-lms' );
		}

		$html .= '</a></p>';

		echo wp_kses(
			$html,
			array_merge(
				wp_kses_allowed_html( 'post' ),
				array(
					'input' => array(
						'id'    => array(),
						'name'  => array(),
						'type'  => array(),
						'value' => array(),
					),
				)
			)
		);
	}

	/**
	 * course_manage_meta_box_content function.
	 *
	 * @since 1.9.0
	 * @access public
	 * @return void
	 */

	public function course_manage_meta_box_content() {
		global $post;

		$manage_url  = add_query_arg(
			array(
				'page'      => 'sensei_learners',
				'course_id' => $post->ID,
				'view'      => 'learners',
			),
			admin_url( 'admin.php' )
		);
		$grading_url = add_query_arg(
			array(
				'page'      => 'sensei_grading',
				'course_id' => $post->ID,
				'view'      => 'learners',
			),
			admin_url( 'admin.php' )
		);

		echo '<ul><li><a href=' . esc_url( $manage_url ) . '>' . esc_html__( 'Manage Learners', 'sensei-lms' ) . '</a></li>';
		echo '<li><a href=' . esc_url( $grading_url ) . '>' . esc_html__( 'Manage Grading', 'sensei-lms' ) . '</a></li></ul>';
	}

	/**
	 * Add column headings to the "course" post list screen,
	 * while moving the existing ones to the end.
	 *
	 * @access private
	 * @since  1.0.0
	 * @param  array $defaults  Array of column header labels keyed by column ID.
	 * @return array            Updated array of column header labels keyed by column ID.
	 */
	public function add_column_headings( $defaults ) {
		$new_columns                        = [];
		$new_columns['cb']                  = '<input type="checkbox" />';
		$new_columns['title']               = _x( 'Course Title', 'column name', 'sensei-lms' );
		$new_columns['course-prerequisite'] = _x( 'Pre-requisite Course', 'column name', 'sensei-lms' );
		$new_columns['course-category']     = _x( 'Category', 'column name', 'sensei-lms' );
		if ( isset( $defaults['date'] ) ) {
			$new_columns['date'] = $defaults['date'];
		}

		// Make sure other sensei columns stay directly behind the new columns.
		$other_sensei_columns = [
			'taxonomy-module',
			'teacher',
			'module_order',
		];
		foreach ( $other_sensei_columns as $column_key ) {
			if ( isset( $defaults[ $column_key ] ) ) {
				$new_columns[ $column_key ] = $defaults[ $column_key ];
			}
		}

		// Add all remaining columns at the end.
		foreach ( $defaults as $column_key => $column_value ) {
			if ( ! isset( $new_columns[ $column_key ] ) ) {
				$new_columns[ $column_key ] = $column_value;
			}
		}

		return $new_columns;
	}

	/**
	 * Add data for our newly-added custom columns.
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  string $column_name
	 * @param  int    $id
	 * @return void
	 */
	public function add_column_data( $column_name, $id ) {
		switch ( $column_name ) {
			case 'id':
				echo esc_html( $id );
				break;

			case 'course-prerequisite':
				$course_prerequisite_id = get_post_meta( $id, '_course_prerequisite', true );
				if ( 0 < absint( $course_prerequisite_id ) ) {
					echo '<a href="'
						. esc_url( get_edit_post_link( absint( $course_prerequisite_id ) ) )
						. '" title="'
						// translators: Placeholder is the title of the course prerequisite.
						. esc_attr( sprintf( __( 'Edit %s', 'sensei-lms' ), get_the_title( absint( $course_prerequisite_id ) ) ) )
						. '">'
						. esc_html( get_the_title( absint( $course_prerequisite_id ) ) )
						. '</a>';
				}

				break;

			case 'course-category':
				$output = get_the_term_list( $id, 'course-category', '', ', ', '' );

				if ( '' == $output ) {
					echo esc_html__( 'None', 'sensei-lms' );
				} else {
					echo wp_kses_post( $output );
				}
				break;

			default:
				break;
		}
	}


	/**
	 * Query courses.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 For `$type` argument, `paidcourses` is no longer supported.
	 * @since 2.0.0 For `$type` argument, `freecourses` is no longer supported.
	 *
	 * @deprecated 3.0.0
	 *
	 * @param int    $amount (default: 0)
	 * @param string $type (default: 'default')
	 * @param array  $includes (default: array())
	 * @return array
	 */
	public function course_query( $amount = 0, $type = 'default', $includes = array(), $excludes = array() ) {
		_deprecated_function( __METHOD__, '3.0.0' );

		if ( 'usercourses' === $type ) {
			$base_query = [
				'posts_per_page' => $amount,
			];
			if ( ! empty( $includes ) ) {
				$base_query['post__in'] = $includes;
			}
			if ( ! empty( $excludes ) ) {
				$base_query['post__not_in'] = $excludes;
			}

			$learner_manager = Sensei_Learner::instance();

			return $learner_manager->get_enrolled_courses_query( get_current_user_id(), $base_query )->posts;
		}

		$results_array = array();

		$post_args = $this->get_archive_query_args( $type, $amount, $includes, $excludes );

		// get the posts
		if ( empty( $post_args ) ) {

			return $results_array;

		} else {

			// reset the pagination as this widgets do not need it
			$post_args['paged'] = 1;
			$results_array      = get_posts( $post_args );

		}

		return $results_array;

	}


	/**
	 * Get the query arguments for fetching courses in different contexts.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 For `$type` argument, `paidcourses` is no longer supported.
	 * @since 2.0.0 For `$type` argument, `freecourses` is no longer supported.
	 *
	 * @deprecated 3.0.0
	 *
	 * @param string $type (default: '')
	 * @param int    $amount (default: 0)
	 * @param array  $includes (default: array())
	 * @return array
	 */
	public function get_archive_query_args( $type = '', $amount = 0, $includes = array(), $excludes = array() ) {
		_deprecated_function( __METHOD__, '3.0.0' );

		global $wp_query;

		if ( 0 == $amount && ( isset( Sensei()->settings->settings['course_archive_amount'] ) && 'usercourses' != $type && ( 0 < absint( Sensei()->settings->settings['course_archive_amount'] ) ) ) ) {
			$amount = absint( Sensei()->settings->settings['course_archive_amount'] );
		} else {
			if ( 0 == $amount ) {
				$amount = $wp_query->get( 'posts_per_page' );
			}
		}

		$stored_order = get_option( 'sensei_course_order', '' );
		$order        = 'ASC';
		$orderby      = 'menu_order';
		if ( empty( $stored_order ) ) {

			$order   = 'DESC';
			$orderby = 'date';

		}

		switch ( $type ) {

			case 'usercourses':
				$learner_manager = Sensei_Learner::instance();
				$post_args       = array(
					'orderby'          => $orderby,
					'order'            => $order,
					'post__in'         => $includes,
					'post__not_in'     => $excludes,
					'suppress_filters' => 0,
				);
				$post_args       = $learner_manager->get_enrolled_courses_query_args( get_current_user_id(), $post_args );

				break;

			case 'freecourses':
				_doing_it_wrong(
					__METHOD__,
					esc_html__( 'Querying with argument `$type` having a value of `freecourses` is deprecated.', 'sensei-lms' ),
					'2.0.0'
				);

				$post_args = array(
					'post_type'        => 'course',
					'orderby'          => $orderby,
					'order'            => $order,
					'post_status'      => 'publish',
					'exclude'          => $excludes,
					'suppress_filters' => 0,
				);

				// If WooCommerce Paid Courses is not active, we will display all courses.
				if ( class_exists( 'Sensei_WC_Paid_Courses\Sensei_WC_Paid_Courses' ) ) {
					// Sub Query to get all WooCommerce Products that have Zero price
					$post_args['meta_query'] = Sensei_WC::get_free_courses_meta_query_args();
				}

				break;

			case 'paidcourses':
				_doing_it_wrong(
					__METHOD__,
					esc_html__( 'Querying with argument `$type` having a value of `paidcourses` is deprecated.', 'sensei-lms' ),
					'2.0.0'
				);

				$post_args = array(
					'post_type'        => 'course',
					'orderby'          => $orderby,
					'order'            => $order,
					'post_status'      => 'publish',
					'exclude'          => $excludes,
					'suppress_filters' => 0,
				);

				// If WooCommerce Paid Courses is not active, we will display no courses.
				if ( class_exists( 'Sensei_WC_Paid_Courses\Sensei_WC_Paid_Courses' ) ) {
					// Sub Query to get all WooCommerce Products that have price greater than zero
					$post_args['meta_query'] = Sensei_WC::get_paid_courses_meta_query_args();
				} else {
					$post_args['post__in'] = array( -1 );
				}

				break;

			case 'featuredcourses':
				$post_args = array(
					'post_type'        => 'course',
					'orderby'          => $orderby,
					'order'            => $order,
					'post_status'      => 'publish',
					'meta_value'       => 'featured',
					'meta_key'         => '_course_featured',
					'meta_compare'     => '=',
					'exclude'          => $excludes,
					'suppress_filters' => 0,
				);
				break;
			default:
				$post_args = array(
					'post_type'        => 'course',
					'orderby'          => $orderby,
					'order'            => $order,
					'post_status'      => 'publish',
					'exclude'          => $excludes,
					'suppress_filters' => 0,
				);
				break;

		}

		$post_args['posts_per_page'] = $amount;
		$paged                       = $wp_query->get( 'paged' );
		$post_args['paged']          = empty( $paged ) ? 1 : $paged;

		if ( 'newcourses' == $type ) {

			$post_args['orderby'] = 'date';
			$post_args['order']   = 'DESC';
		}

		return $post_args;
	}


	/**
	 * course_image function.
	 *
	 * Outputs the courses image, or first image from a lesson within a course
	 *
	 * Will echo the image unless return true is specified.
	 *
	 * @access public
	 * @param int | WP_Post $course_id (default: 0)
	 * @param string        $width (default: '100')
	 * @param string        $height (default: '100')
	 * @param bool          $return default false
	 *
	 * @return string | void
	 */
	public function course_image( $course_id = 0, $width = '100', $height = '100', $return = false ) {
		global $sensei_is_block;

		if ( is_a( $course_id, 'WP_Post' ) ) {

			$course_id = $course_id->ID;

		}

		if ( 'course' !== get_post_type( $course_id ) ) {

			return;

		}

		$html = '';

		// Get Width and Height settings
		if ( ( $width == '100' ) && ( $height == '100' ) ) {

			if ( is_singular( 'course' ) ) {

				if ( ! Sensei()->settings->settings['course_single_image_enable'] ) {
					return '';
				}
				$image_thumb_size = 'course_single_image';
				$dimensions       = Sensei()->get_image_size( $image_thumb_size );
				$width            = $dimensions['width'];
				$height           = $dimensions['height'];

			} else {

				if ( ! Sensei()->settings->settings['course_archive_image_enable'] && ! $sensei_is_block ) {
					return '';
				}

				$image_thumb_size = 'course_archive_image';
				$dimensions       = Sensei()->get_image_size( $image_thumb_size );
				$width            = $dimensions['width'];
				$height           = $dimensions['height'];

			}
		}

		$img_html         = '';
		$used_placeholder = false;
		$classes          = '';

		if ( ! $sensei_is_block ) {
			$classes = 'woo-image thumbnail alignleft';
		}

		if ( has_post_thumbnail( $course_id ) ) {
			// Get Featured Image
			if ( $sensei_is_block ) {
				$img_html = get_the_post_thumbnail( $course_id, 'medium', array( 'class' => $classes ) );
			} else {
				$img_html = get_the_post_thumbnail( $course_id, array( $width, $height ), array( 'class' => $classes ) );
			}
		} else {

			// Check for a Lesson Image
			$course_lessons = $this->course_lessons( $course_id );

			foreach ( $course_lessons as $lesson_item ) {
				if ( has_post_thumbnail( $lesson_item->ID ) ) {
					// Get Featured Image
					if ( $sensei_is_block ) {
						$img_html = get_the_post_thumbnail( $lesson_item->ID, 'medium', array( 'class' => $classes ) );
					} else {
						$img_html = get_the_post_thumbnail( $lesson_item->ID, array( $width, $height ), array( 'class' => $classes ) );
					}

					if ( '' !== $img_html ) {
						break;
					}
				}
			}

			if ( '' === $img_html ) {

				// Display Image Placeholder if none
				if ( Sensei()->settings->get( 'placeholder_images_enable' ) ) {

					/**
					 * Filter the image HTML when no course image exists.
					 *
					 * @since 1.1.0
					 * @since 1.12.0 Added $course_id, $width, and $height.
					 *
					 * @param string $img_html  Course image HTML.
					 * @param int    $course_id Course ID.
					 * @param int    $width     Requested image width.
					 * @param int    $height    Requested image height.
					 */
					$img_html         = apply_filters( 'sensei_course_placeholder_image_url', '<img src="http://placehold.it/' . $width . 'x' . $height . '" class="' . esc_attr( $classes ) . '" />', $course_id, $width, $height );
					$used_placeholder = true;

				}
			}
		}

		/**
		 * Filter the HTML for the course image. If not blank, this will be surrounded with an anchor tag linking to the course.
		 *
		 * @since 1.12.0
		 *
		 * @param string $img_html         Course image HTML.
		 * @param int    $course_id        Course ID.
		 * @param int    $width            Requested image width.
		 * @param int    $height           Requested image height.
		 * @param bool   $used_placeholder True if placeholder was used in the generation of the image HTML.
		 */
		$img_html = apply_filters( 'sensei_course_image_html', $img_html, $course_id, $width, $height, $used_placeholder );

		if ( '' != $img_html ) {

			$html .= '<a href="' . esc_url( get_permalink( $course_id ) ) . '" title="' . esc_attr( get_post_field( 'post_title', $course_id ) ) . '">' . wp_kses_post( $img_html ) . '</a>';

		}

		if ( $return ) {

			return $html;

		} else {

			echo wp_kses_post( $html );

		}

	}


	/**
	 * course_count function.
	 *
	 * @access public
	 * @param array  $exclude (default: array())
	 * @param string $post_status (default: 'publish')
	 * @return int
	 */
	public function course_count( $post_status = 'publish' ) {

		$post_args = array(
			'post_type'        => 'course',
			'posts_per_page'   => -1,
			'post_status'      => $post_status,
			'suppress_filters' => 0,
			'fields'           => 'ids',
		);

		// Allow WP to generate the complex final query, just shortcut to only do an overall count
		$courses_query = new WP_Query( apply_filters( 'sensei_course_count', $post_args ) );

		return count( $courses_query->posts );
	}


	/**
	 * course_lessons function.
	 *
	 * @access public
	 *
	 * @param int    $course_id   (default: 0)
	 * @param string $post_status (default: 'publish')
	 * @param string $fields      (default: 'all'). WP only allows 3 types, but we will limit it to only 'ids' or 'all'
	 * @param array  $query_args  Base arguments for the WP query.
	 *
	 * @return array{ type WP_Post }  $posts_array
	 */
	public function course_lessons( $course_id = 0, $post_status = 'publish', $fields = 'all', $query_args = [] ) {

		if ( is_a( $course_id, 'WP_Post' ) ) {
			$course_id = $course_id->ID;
		}

		$query_args = array_merge(
			$query_args,
			[
				'post_type'        => 'lesson',
				'posts_per_page'   => -1,
				'orderby'          => 'date',
				'order'            => 'ASC',
				'post_status'      => $post_status,
				'suppress_filters' => 0,
			]
		);

		if ( ! isset( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = [];
		}

		$query_args['meta_query'][] = [
			'key'   => '_lesson_course',
			'value' => intval( $course_id ),
		];

		$query_results = new WP_Query( $query_args );
		$lessons       = $query_results->posts;

		// re order the lessons. This could not be done via the OR meta query as there may be lessons
		// with the course order for a different course and this should not be included. It could also not
		// be done via the AND meta query as it excludes lesson that does not have the _order_$course_id but
		// that have been added to the course.
		if ( count( $lessons ) > 1 ) {

			foreach ( $lessons as $lesson ) {

				$order = intval( get_post_meta( $lesson->ID, '_order_' . $course_id, true ) );
				// for lessons with no order set it to be 10000 so that it show up at the end
				$lesson->course_order = $order ? $order : 100000;
			}

			uasort( $lessons, array( $this, '_short_course_lessons_callback' ) );
		}

		/**
		 * Filter runs inside Sensei_Course::course_lessons function
		 *
		 * Returns all lessons for a given course
		 *
		 * @param array $lessons
		 * @param int $course_id
		 */
		$lessons = apply_filters( 'sensei_course_get_lessons', $lessons, $course_id );

		// return the requested fields
		// runs after the sensei_course_get_lessons filter so the filter always give an array of lesson
		// objects
		if ( 'ids' === $fields ) {
			$lesson_objects = $lessons;
			$lessons        = array();

			foreach ( $lesson_objects as $lesson ) {
				$lessons[] = $lesson->ID;
			}
		}

		return $lessons;

	}

	/**
	 * Used for the uasort in $this->course_lessons()
	 *
	 * @since 1.8.0
	 * @access protected
	 *
	 * @param array $lesson_1
	 * @param array $lesson_2
	 * @return int
	 */
	protected function _short_course_lessons_callback( $lesson_1, $lesson_2 ) {

		if ( $lesson_1->course_order == $lesson_2->course_order ) {
			return 0;
		}

		return ( $lesson_1->course_order < $lesson_2->course_order ) ? -1 : 1;
	}

	/**
	 * Fetch all quiz ids in a course
	 *
	 * @since  1.5.0
	 * @param  integer $course_id ID of course
	 * @param  boolean $boolean_check True if a simple yes/no is required
	 * @return array              Array of quiz post objects
	 */
	public function course_quizzes( $course_id = 0, $boolean_check = false ) {

		$course_quizzes = array();

		if ( $course_id ) {
			$lesson_ids = Sensei()->course->course_lessons( $course_id, 'any', 'ids' );

			foreach ( $lesson_ids as $lesson_id ) {
				$has_questions = Sensei_Lesson::lesson_quiz_has_questions( $lesson_id );
				if ( $has_questions && $boolean_check ) {
					return true;
				} elseif ( $has_questions ) {
					$quiz_id          = Sensei()->lesson->lesson_quizzes( $lesson_id );
					$course_quizzes[] = $quiz_id;
				}
			}
		}
		if ( $boolean_check && empty( $course_quizzes ) ) {
			$course_quizzes = false;
		}
		return $course_quizzes;
	}


	/**
	 * course_lessons_completed function. Appears to be completely unused and a duplicate of course_lessons()!
	 *
	 * @access public
	 * @param  int    $course_id (default: 0)
	 * @param  string $post_status (default: 'publish')
	 * @return array
	 */
	public function course_lessons_completed( $course_id = 0, $post_status = 'publish' ) {

		return $this->course_lessons( $course_id, $post_status );

	}


	/**
	 * course_author_lesson_count function.
	 *
	 * @access public
	 * @param  int $author_id (default: 0)
	 * @param  int $course_id (default: 0)
	 * @return int
	 */
	public function course_author_lesson_count( $author_id = 0, $course_id = 0 ) {

		$lesson_args   = array(
			'post_type'        => 'lesson',
			'posts_per_page'   => -1,
			'author'           => $author_id,
			'meta_key'         => '_lesson_course',
			'meta_value'       => $course_id,
			'post_status'      => 'publish',
			'suppress_filters' => 0,
			'fields'           => 'ids', // less data to retrieve
		);
		$lessons_array = get_posts( $lesson_args );
		$count         = count( $lessons_array );
		return $count;

	}

	/**
	 * course_lesson_count function.
	 *
	 * @access public
	 * @param  int $course_id (default: 0)
	 * @return int
	 */
	public function course_lesson_count( $course_id = 0 ) {

		$lesson_args   = array(
			'post_type'        => 'lesson',
			'posts_per_page'   => -1,
			'meta_key'         => '_lesson_course',
			'meta_value'       => $course_id,
			'post_status'      => 'publish',
			'suppress_filters' => 0,
			'fields'           => 'ids', // less data to retrieve
		);
		$lessons_array = get_posts( $lesson_args );

		$count = count( $lessons_array );

		return $count;

	}

	/**
	 * course_lesson_preview_count function.
	 *
	 * @access public
	 * @param  int $course_id (default: 0)
	 * @return int
	 */
	public function course_lesson_preview_count( $course_id = 0 ) {

		$lesson_args   = array(
			'post_type'        => 'lesson',
			'posts_per_page'   => -1,
			'post_status'      => 'publish',
			'suppress_filters' => 0,
			'meta_query'       => array(
				array(
					'key'   => '_lesson_course',
					'value' => $course_id,
				),
				array(
					'key'   => '_lesson_preview',
					'value' => 'preview',
				),
			),
			'fields'           => 'ids', // less data to retrieve
		);
		$lessons_array = get_posts( $lesson_args );

		$count = count( $lessons_array );

		return $count;

	}

	/**
	 * get_product_courses function.
	 *
	 * @access public
	 * @deprecated 2.0.0 Use `Sensei_WC_Paid_Courses\Courses::get_product_courses()` instead.
	 *
	 * @param  int $product_id (default: 0)
	 * @return array
	 */
	public function get_product_courses( $product_id = 0 ) {

		_deprecated_function( __METHOD__, '2.0.0', 'Sensei_WC_Paid_Courses\Courses::get_product_courses' );

		if ( method_exists( 'Sensei_WC_Paid_Courses\Courses', 'get_product_courses' ) ) {
			return \Sensei_WC_Paid_Courses\Courses::get_product_courses( $product_id );
		}

		return array();

	}

	/**
	 * @deprecated 2.0.0 Use `Sensei_WC_Paid_Courses\Courses::get_product_courses_query_args()` instead.
	 *
	 * @param $product_id
	 *
	 * @return array
	 */
	public static function get_product_courses_query_args( $product_id ) {

		_deprecated_function( __METHOD__, '2.0.0', 'Sensei_WC_Paid_Courses\Courses::get_product_courses_query_args' );

		if ( method_exists( 'Sensei_WC_Paid_Courses\Courses', 'get_product_courses_query_args' ) ) {
			return \Sensei_WC_Paid_Courses\Courses::get_product_courses_query_args( $product_id );
		}

		return array();

	}

	/**
	 * Fix posts_per_page for My Courses page
	 *
	 * @deprecated 3.0.0
	 *
	 * @param  WP_Query $query
	 * @return void
	 */
	public function filter_my_courses( $query ) {
		_deprecated_function( __METHOD__, '3.0.0' );
	}

	/**
	 * load_user_courses_content generates HTML for user's active & completed courses
	 *
	 * This function also ouputs the html so no need to echo the content.
	 *
	 * @since  1.4.0
	 * @param  object  $user   Queried user object
	 * @param  boolean $manage Whether the user has permission to manage the courses
	 * @return string          HTML displayng course data
	 */
	public function load_user_courses_content( $user = false ) {
		global $course;

		if ( ! isset( Sensei()->settings->settings['learner_profile_show_courses'] )
			|| ! Sensei()->settings->settings['learner_profile_show_courses'] ) {

			// do not show the content if the settings doesn't allow for it
			return;

		}

		$manage = ( $user->ID == get_current_user_id() ) ? true : false;

		do_action( 'sensei_before_learner_course_content', $user );

		// Build Output HTML
		$complete_html = $active_html = '';

		if ( is_a( $user, 'WP_User' ) ) {

			// Allow action to be run before My Courses content has loaded
			do_action( 'sensei_before_my_courses', $user->ID );

			// Logic for Active and Completed Courses
			$per_page = 20;
			if ( isset( Sensei()->settings->settings['my_course_amount'] )
				&& ( 0 < absint( Sensei()->settings->settings['my_course_amount'] ) ) ) {

				$per_page = absint( Sensei()->settings->settings['my_course_amount'] );

			}

			$learner_manager = Sensei_Learner::instance();

			$active_query_args    = [
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe use of pagination var.
				'paged'          => isset( $_GET['active_page'] ) ? absint( $_GET['active_page'] ) : 1,
				'posts_per_page' => $per_page,
			];
			$active_courses_query = $learner_manager->get_enrolled_active_courses_query( $user->ID, $active_query_args );
			$active_courses       = $active_courses_query->posts;
			$active_count         = $active_courses_query->found_posts;

			$completed_query_args    = [
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe use of pagination var.
				'paged'          => isset( $_GET['completed_page'] ) ? absint( $_GET['completed_page'] ) : 1,
				'posts_per_page' => $per_page,
			];
			$completed_courses_query = $learner_manager->get_enrolled_completed_courses_query( $user->ID, $completed_query_args );
			$completed_courses       = $completed_courses_query->posts;
			$completed_count         = $completed_courses_query->found_posts;

			foreach ( $active_courses as $course_item ) {

				$course_lessons    = Sensei()->course->course_lessons( $course_item->ID );
				$lessons_completed = 0;
				foreach ( $course_lessons as $lesson ) {
					if ( Sensei_Utils::user_completed_lesson( $lesson->ID, $user->ID ) ) {
						++$lessons_completed;
					}
				}

				// Get Course Categories
				$category_output = get_the_term_list( $course_item->ID, 'course-category', '', ', ', '' );

				$active_html .= '<article class="' . esc_attr( join( ' ', get_post_class( array( 'course', 'post' ), $course_item->ID ) ) ) . '">';

				// Image
				$active_html .= Sensei()->course->course_image( absint( $course_item->ID ), '100', '100', true );

				// Title
				$active_html .= '<header>';
				$active_html .= '<h2 class="course-title"><a href="' . esc_url( get_permalink( absint( $course_item->ID ) ) ) . '" title="' . esc_attr( $course_item->post_title ) . '">' . esc_html( $course_item->post_title ) . '</a></h2>';

				// Author
				$user_info = get_userdata( absint( $course_item->post_author ) );

				if ( isset( Sensei()->settings->settings['course_author'] )
					&& ( Sensei()->settings->settings['course_author'] ) ) {
					$active_html .= '<span class="course-author">'
						. esc_html__( 'by ', 'sensei-lms' )
						. '<a href="' . esc_url( get_author_posts_url( absint( $course_item->post_author ) ) )
						. '" title="' . esc_attr( $user_info->display_name ) . '">'
						. esc_html( $user_info->display_name )
						. '</a></span>';
				}

				$active_html .= '</header>';
				$active_html .= '<section class="entry">';
				$active_html .= '<div class="sensei-course-meta">';

				// Lesson count for this author
				$lesson_count = Sensei()->course->course_lesson_count( absint( $course_item->ID ) );
				// Handle Division by Zero
				if ( 0 == $lesson_count ) {

					$lesson_count = 1;

				}
				$active_html .= '<span class="course-lesson-count">' .
					// translators: Placeholder %d is the lesson count.
					esc_html( sprintf( _n( '%d Lesson', '%d Lessons', $lesson_count, 'sensei-lms' ), $lesson_count ) ) .
				'</span>';
				// Course Categories
				if ( '' != $category_output ) {

					$active_html .= '<span class="course-category">'
						// translators: Placeholder is a comma-separated list of the Course categories.
						. sprintf( __( 'in %s', 'sensei-lms' ), $category_output )
						. '</span>';

				}

				// translators: Placeholders are the counts for lessons completed and total lessons, respectively.
				$active_html .= '<span class="course-lesson-progress">' . esc_html( sprintf( __( '%1$d of %2$d lessons completed', 'sensei-lms' ), $lessons_completed, $lesson_count ) ) . '</span>';

				$active_html .= '</div>';

				$active_html .= '<p class="course-excerpt">' . esc_html( $course_item->post_excerpt ) . '</p>';

				$progress_percentage = Sensei_Utils::quotient_as_absolute_rounded_percentage( $lessons_completed, $lesson_count, 0 );

				$active_html .= $this->get_progress_meter( $progress_percentage );

				$active_html .= '</section>';

				if ( is_user_logged_in() ) {

					$active_html .= '<section class="entry-actions">';

					$active_html .= '<form method="POST" action="' . esc_url( remove_query_arg( array( 'active_page', 'completed_page' ) ) ) . '">';

					$active_html .= '<input type="hidden" name="' . esc_attr( 'woothemes_sensei_complete_course_noonce' ) . '" id="' . esc_attr( 'woothemes_sensei_complete_course_noonce' ) . '" value="' . esc_attr( wp_create_nonce( 'woothemes_sensei_complete_course_noonce' ) ) . '" />';

					$active_html .= '<input type="hidden" name="course_complete_id" id="course-complete-id" value="' . esc_attr( absint( $course_item->ID ) ) . '" />';

					if ( 0 < absint( count( $course_lessons ) )
						&& Sensei()->settings->settings['course_completion'] == 'complete' ) {
						wp_enqueue_script( 'sensei-stop-double-submission' );

						$active_html .= '<span><input name="course_complete" type="submit" class="course-complete sensei-stop-double-submission" value="'
							. esc_attr__( 'Mark as Complete', 'sensei-lms' ) . '"/> </span>';

					}

					$course_purchased = false;
					if ( class_exists( 'Sensei_WC' ) && Sensei_WC::is_woocommerce_active() ) {

						// Get the product ID
						$wc_post_id = get_post_meta( absint( $course_item->ID ), '_course_woocommerce_product', true );
						if ( 0 < $wc_post_id ) {

							$course_purchased = Sensei_WC::has_customer_bought_product( $user->ID, $wc_post_id );

						}
					}

					/**
					 * documented in class-sensei-course.php the_course_action_buttons function
					 *
					 * @deprecated 2.0.0
					 */
					$show_delete_course_button = apply_filters_deprecated(
						'sensei_show_delete_course_button',
						[ false ],
						'2.0.0',
						null,
						'Sensei LMS "Delete Course" button will be removed in version 4.0.'
					);

					if ( false == $course_purchased && $show_delete_course_button ) {

						$active_html .= '<span><input name="course_complete" type="submit" class="course-delete" value="'
							. esc_attr__( 'Delete Course', 'sensei-lms' ) . '"/></span>';

					}

					$active_html .= '</form>';

					$active_html .= '</section>';
				}

				$active_html .= '</article>';
			}

			// Active pagination
			if ( $active_count > $per_page ) {

				$current_page = 1;
				if ( isset( $_GET['active_page'] ) && 0 < intval( $_GET['active_page'] ) ) {
					$current_page = $_GET['active_page'];
				}

				$active_html .= '<nav class="pagination woo-pagination">';
				$total_pages  = ceil( $active_count / $per_page );

				if ( $current_page > 1 ) {
					$prev_link    = add_query_arg( 'active_page', $current_page - 1 );
					$active_html .= '<a class="prev page-numbers" href="' . esc_url( $prev_link ) . '">' . esc_html__( 'Previous', 'sensei-lms' ) . '</a> ';
				}

				for ( $i = 1; $i <= $total_pages; $i++ ) {
					$link = add_query_arg( 'active_page', $i );

					if ( $i == $current_page ) {
						$active_html .= '<span class="page-numbers current">' . esc_html( $i ) . '</span> ';
					} else {
						$active_html .= '<a class="page-numbers" href="' . esc_url( $link ) . '">' . esc_html( $i ) . '</a> ';
					}
				}

				if ( $current_page < $total_pages ) {
					$next_link    = add_query_arg( 'active_page', $current_page + 1 );
					$active_html .= '<a class="next page-numbers" href="' . esc_url( $next_link ) . '">' . esc_html__( 'Next', 'sensei-lms' ) . '</a> ';
				}

				$active_html .= '</nav>';
			}

			foreach ( $completed_courses as $course_item ) {
				$course       = $course_item;
				$lesson_count = Sensei()->course->course_lesson_count( absint( $course_item->ID ) );

				// Get Course Categories
				$category_output = get_the_term_list( $course_item->ID, 'course-category', '', ', ', '' );

				$complete_html .= '<article class="' . esc_attr( join( ' ', get_post_class( array( 'course', 'post' ), $course_item->ID ) ) ) . '">';

				// Image
				$complete_html .= Sensei()->course->course_image( absint( $course_item->ID ), 100, 100, true );

				// Title
				$complete_html .= '<header>';
				$complete_html .= '<h2 class="course-title"><a href="' . esc_url( get_permalink( absint( $course_item->ID ) ) ) . '" title="' . esc_attr( $course_item->post_title ) . '">' . esc_html( $course_item->post_title ) . '</a></h2>';

				// Author
				$user_info = get_userdata( absint( $course_item->post_author ) );

				if ( isset( Sensei()->settings->settings['course_author'] ) && ( Sensei()->settings->settings['course_author'] ) ) {
					$complete_html .= '<span class="course-author">' . esc_html__( 'by ', 'sensei-lms' ) . '<a href="' . esc_url( get_author_posts_url( absint( $course_item->post_author ) ) ) . '" title="' . esc_attr( $user_info->display_name ) . '">' . esc_html( $user_info->display_name ) . '</a></span>';
				}

					$complete_html .= '</header>';

					$complete_html .= '<section class="entry">';

						$complete_html .= '<p class="sensei-course-meta">';

							// Lesson count for this author
							$complete_html .= '<span class="course-lesson-count">' .
								// translators: Placeholder %d is the lesson count.
								esc_html( sprintf( _n( '%d Lesson', '%d Lessons', $lesson_count, 'sensei-lms' ), $lesson_count ) ) .
							'</span>';

							// Course Categories
				if ( '' != $category_output ) {

					// translators: Placeholder is a comma-separated list of the Course categories.
					$complete_html .= '<span class="course-category">' . sprintf( __( 'in %s', 'sensei-lms' ), $category_output ) . '</span>';

				}

						$complete_html .= '</p>';

						$complete_html .= '<p class="course-excerpt">' . esc_html( $course_item->post_excerpt ) . '</p>';

						$complete_html .= $this->get_progress_meter( 100 );

				if ( $manage ) {
					$has_quizzes = Sensei()->course->course_quizzes( $course_item->ID, true );

					// Output only if there is content to display
					if ( has_filter( 'sensei_results_links' ) || $has_quizzes ) {
						$complete_html .= '<p class="sensei-results-links">';
						$results_link   = '';

						if ( $has_quizzes ) {
							$results_link = '<a class="button view-results" href="'
								. esc_url( self::get_view_results_link( $course_item->ID ) )
								. '">' . esc_html__( 'View Results', 'sensei-lms' )
								. '</a>';
						}

						/**
						 * Filter documented in Sensei_Course::the_course_action_buttons
						 */
						$complete_html .= apply_filters( 'sensei_results_links', $results_link, $course_item->ID );
						$complete_html .= '</p>';

					}
				}

					$complete_html .= '</section>';

				$complete_html .= '</article>';
			}

			// Active pagination
			if ( $completed_count > $per_page ) {

				$current_page = 1;
				if ( isset( $_GET['completed_page'] ) && 0 < intval( $_GET['completed_page'] ) ) {
					$current_page = $_GET['completed_page'];
				}

				$complete_html .= '<nav class="pagination woo-pagination">';
				$total_pages    = ceil( $completed_count / $per_page );

				if ( $current_page > 1 ) {
					$prev_link      = add_query_arg( 'completed_page', $current_page - 1 );
					$complete_html .= '<a class="prev page-numbers" href="' . esc_url( $prev_link ) . '">' . esc_html__( 'Previous', 'sensei-lms' ) . '</a> ';
				}

				for ( $i = 1; $i <= $total_pages; $i++ ) {
					$link = add_query_arg( 'completed_page', $i );

					if ( $i == $current_page ) {
						$complete_html .= '<span class="page-numbers current">' . esc_html( $i ) . '</span> ';
					} else {
						$complete_html .= '<a class="page-numbers" href="' . esc_url( $link ) . '">' . esc_html( $i ) . '</a> ';
					}
				}

				if ( $current_page < $total_pages ) {
					$next_link      = add_query_arg( 'completed_page', $current_page + 1 );
					$complete_html .= '<a class="next page-numbers" href="' . esc_url( $next_link ) . '">' . esc_html__( 'Next', 'sensei-lms' ) . '</a> ';
				}

				$complete_html .= '</nav>';
			}
		}

		if ( $manage ) {
			$no_active_message   = __( 'You have no active courses.', 'sensei-lms' );
			$no_complete_message = __( 'You have not completed any courses yet.', 'sensei-lms' );
		} else {
			$no_active_message   = __( 'This learner has no active courses.', 'sensei-lms' );
			$no_complete_message = __( 'This learner has not completed any courses yet.', 'sensei-lms' );
		}

		ob_start();
		?>

		<?php do_action( 'sensei_before_user_courses' ); ?>

		<?php
		if ( $manage && ( ! isset( Sensei()->settings->settings['messages_disable'] ) || ! Sensei()->settings->settings['messages_disable'] ) ) {
			?>
			<p class="my-messages-link-container">
				<a class="my-messages-link" href="<?php echo esc_url( get_post_type_archive_link( 'sensei_message' ) ); ?>"
				   title="<?php esc_attr_e( 'View & reply to private messages sent to your course & lesson teachers.', 'sensei-lms' ); ?>">
					<?php esc_html_e( 'My Messages', 'sensei-lms' ); ?>
				</a>
			</p>
			<?php
		}
		?>
		<div id="my-courses">

			<ul>
				<li><a href="#active-courses"><?php esc_html_e( 'Active Courses', 'sensei-lms' ); ?></a></li>
				<li><a href="#completed-courses"><?php esc_html_e( 'Completed Courses', 'sensei-lms' ); ?></a></li>
			</ul>

			<?php do_action( 'sensei_before_active_user_courses' ); ?>

			<?php
			$course_page_url = self::get_courses_page_url();
			?>

			<div id="active-courses">

				<?php
				if ( '' != $active_html ) {
					echo wp_kses(
						$active_html,
						array_merge(
							wp_kses_allowed_html( 'post' ),
							array(
								// Explicitly allow form tag for WP.com.
								'form'  => array(
									'action' => array(),
									'method' => array(),
								),
								'input' => array(
									'class' => array(),
									'id'    => array(),
									'name'  => array(),
									'type'  => array(),
									'value' => array(),
								),
								// Explicitly allow nav tag for WP.com.
								'nav'   => array(
									'class' => array(),
								),
							)
						)
					);

				} else {
					?>

					<div class="sensei-message info">

						<?php echo esc_html( $no_active_message ); ?>

						<a href="<?php echo esc_url( $course_page_url ); ?>">

							<?php esc_html_e( 'Start a Course!', 'sensei-lms' ); ?>

						</a>

					</div>

				<?php } ?>

			</div>

			<?php do_action( 'sensei_after_active_user_courses' ); ?>

			<?php do_action( 'sensei_before_completed_user_courses' ); ?>

			<div id="completed-courses">

				<?php
				if ( '' != $complete_html ) {
					echo wp_kses(
						$complete_html,
						array_merge(
							wp_kses_allowed_html( 'post' ),
							array(
								// Explicitly allow nav tag for WP.com.
								'nav' => array(
									'class' => array(),
								),
							)
						)
					);
				} else {
					?>

					<div class="sensei-message info">

						<?php echo esc_html( $no_complete_message ); ?>

					</div>

				<?php } ?>

			</div>

			<?php do_action( 'sensei_after_completed_user_courses' ); ?>

		</div>

		<?php do_action( 'sensei_after_user_courses' ); ?>

		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped above and should be escaped in hooked methods.
		echo ob_get_clean();

		do_action( 'sensei_after_learner_course_content', $user );

	}

	/**
	 * Returns a list of all courses
	 *
	 * @since 1.8.0
	 * @return array $courses{
	 *  @type $course WP_Post
	 * }
	 */
	public static function get_all_courses() {

		$args = array(
			'post_type'        => 'course',
			'posts_per_page'   => -1,
			'orderby'          => 'title',
			'order'            => 'ASC',
			'post_status'      => 'any',
			'suppress_filters' => 0,
		);

		$wp_query_obj = new WP_Query( $args );

		/**
		 * sensei_get_all_courses filter
		 *
		 * This filter runs inside Sensei_Course::get_all_courses.
		 *
		 * @param array $courses{
		 *  @type WP_Post
		 * }
		 * @param array $attributes
		 */
		return apply_filters( 'sensei_get_all_courses', $wp_query_obj->posts );

	}

	/**
	 * Generate the course meter component
	 *
	 * @since 1.8.0
	 * @param int $progress_percentage 0 - 100
	 * @return string $progress_bar_html
	 */
	public function get_progress_meter( $progress_percentage ) {

		if ( 50 < $progress_percentage ) {
			$class = ' green';
		} elseif ( 25 <= $progress_percentage && 50 >= $progress_percentage ) {
			$class = ' orange';
		} else {
			$class = ' red';
		}
		$progress_bar_html = '<div class="meter' . esc_attr( $class ) . '"><span class="value" style="width: ' .
			esc_attr( $progress_percentage ) . '%">' . esc_html( round( $progress_percentage ) ) . '%</span></div>';

		return $progress_bar_html;

	}

	/**
	 * Generate a statement that tells users
	 * how far they are in the course.
	 *
	 * @param int $course_id
	 * @param int $user_id
	 *
	 * @return string $statement_html
	 */
	public function get_progress_statement( $course_id, $user_id ) {

		if (
			empty( $course_id )
			|| empty( $user_id )
			|| ! self::is_user_enrolled( $course_id, $user_id )
		) {
			return '';
		}

		$completed     = count( $this->get_completed_lesson_ids( $course_id, $user_id ) );
		$total_lessons = count( $this->course_lessons( $course_id ) );

		// translators: Placeholders are the counts for lessons completed and total lessons, respectively.
		$statement = sprintf( _n( 'Currently completed %1$s lesson of %2$s in total', 'Currently completed %1$s lessons of %2$s in total', $completed, 'sensei-lms' ), $completed, $total_lessons );

		/**
		 * Filter the course completion statement.
		 * Default Currently completed $var lesson($plural) of $var in total
		 *
		 * @param string $statement
		 */
		return apply_filters( 'sensei_course_completion_statement', $statement );

	}

	/**
	 * Output the course progress statement
	 *
	 * @param $course_id
	 * @return void
	 */
	public function the_progress_statement( $course_id = 0, $user_id = 0 ) {
		if ( empty( $course_id ) ) {
			global $post;
			$course_id = $post->ID;
		}

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$progress_statement = $this->get_progress_statement( $course_id, $user_id );
		if ( ! empty( $progress_statement ) ) {

			echo '<div class="progress statement course-completion-rate">' . esc_html( $progress_statement ) . '</div>';

		}

	}

	/**
	 * Output the course progress bar
	 *
	 * @param $course_id
	 * @return void
	 */
	public function the_progress_meter( $course_id = 0, $user_id = 0 ) {

		if ( empty( $course_id ) ) {
			global $post;
			$course_id = $post->ID;
		}

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if (
			'course' !== get_post_type( $course_id )
			|| ! get_userdata( $user_id )
			|| ! self::is_user_enrolled( $course_id, $user_id )
		) {
			return;
		}
		$percentage_completed = $this->get_completion_percentage( $course_id, $user_id );

		echo wp_kses_post( $this->get_progress_meter( $percentage_completed ) );

	}

	/**
	 * Checks how many lessons are completed
	 *
	 * @since 1.8.0
	 *
	 * @param int $course_id
	 * @param int $user_id
	 * @return array $completed_lesson_ids
	 */
	public function get_completed_lesson_ids( $course_id, $user_id = 0 ) {

		if ( ! ( intval( $user_id ) ) > 0 ) {
			$user_id = get_current_user_id();
		}

		$completed_lesson_ids = array();

		$course_lessons = $this->course_lessons( $course_id );

		foreach ( $course_lessons as $lesson ) {

			$is_lesson_completed = Sensei_Utils::user_completed_lesson( $lesson->ID, $user_id );
			if ( $is_lesson_completed ) {
				$completed_lesson_ids[] = $lesson->ID;
			}
		}

		return $completed_lesson_ids;

	}

	/**
	 * Calculate the perceantage completed in the course
	 *
	 * @since 1.8.0
	 *
	 * @param int $course_id
	 * @param int $user_id
	 * @return int $percentage
	 */
	public function get_completion_percentage( $course_id, $user_id = 0 ) {

		if ( ! ( intval( $user_id ) ) > 0 ) {
			$user_id = get_current_user_id();
		}

		$completed = count( $this->get_completed_lesson_ids( $course_id, $user_id ) );

		if ( ! ( $completed > 0 ) ) {
			return 0;
		}

		$total_lessons = count( $this->course_lessons( $course_id ) );
		$percentage    = Sensei_Utils::quotient_as_absolute_rounded_percentage( $completed, $total_lessons, 2 );

		/**
		 *
		 * Filter the percentage returned for a users course.
		 *
		 * @param $percentage
		 * @param $course_id
		 * @param $user_id
		 * @since 1.8.0
		 */
		return apply_filters( 'sensei_course_completion_percentage', $percentage, $course_id, $user_id );

	}

	/**
	 * Block email notifications for the specific courses
	 * that the user disabled the notifications.
	 *
	 * @since 1.8.0
	 * @param $should_send
	 * @return bool
	 */
	public function block_notification_emails( $should_send ) {
		global $sensei_email_data;
		$email = $sensei_email_data;

		$course_id = '';

		if ( isset( $email['course_id'] ) ) {

			$course_id = $email['course_id'];

		} elseif ( isset( $email['lesson_id'] ) ) {

			$course_id = Sensei()->lesson->get_course_id( $email['lesson_id'] );

		} elseif ( isset( $email['quiz_id'] ) ) {

			$lesson_id = Sensei()->quiz->get_lesson_id( $email['quiz_id'] );
			$course_id = Sensei()->lesson->get_course_id( $lesson_id );

		}

		if ( ! empty( $course_id ) && 'course' == get_post_type( $course_id ) ) {

			$course_emails_disabled = get_post_meta( $course_id, 'disable_notification', true );

			if ( $course_emails_disabled ) {

				return false;

			}
		}

		return $should_send;
	}

	/**
	 * Render the course notification setting meta box
	 *
	 * @since 1.8.0
	 * @param $course
	 */
	public function course_notification_meta_box_content( $course ) {

		$checked = get_post_meta( $course->ID, 'disable_notification', true );

		wp_nonce_field( 'update-course-notification-setting', '_sensei_course_notification' );

		echo '<input id="disable_sensei_course_notification" ' . checked( $checked, true, false ) . ' type="checkbox" name="disable_sensei_course_notification" >';
		echo '<label for="disable_sensei_course_notification">' . esc_html__( 'Disable notifications on this course?', 'sensei-lms' ) . '</label>';

	}

	/**
	 * Store the setting for the course notification setting.
	 *
	 * @hooked int save_post
	 * @since 1.8.0
	 *
	 * @param $course_id
	 */
	public function save_course_notification_meta_box( $course_id ) {

		if ( ! isset( $_POST['_sensei_course_notification'] )
			|| ! wp_verify_nonce( $_POST['_sensei_course_notification'], 'update-course-notification-setting' ) ) {
			return;
		}

		if ( isset( $_POST['disable_sensei_course_notification'] ) && 'on' == $_POST['disable_sensei_course_notification'] ) {
			$new_val = true;
		} else {
			$new_val = false;
		}

		update_post_meta( $course_id, 'disable_notification', $new_val );

	}

	/**
	 * Output a link to view course. The button text is different depending on the amount of preview lesson available.
	 *
	 * hooked into 'sensei_course_content_inside_after'
	 *
	 * @since 1.9.0
	 *
	 * @param integer $course_id
	 */
	public function the_course_free_lesson_preview( $course_id ) {
		// Meta data
		$course                = get_post( $course_id );
		$preview_lesson_count  = intval( Sensei()->course->course_lesson_preview_count( $course->ID ) );
		$is_user_taking_course = self::is_user_enrolled( $course->ID, get_current_user_id() );

		if ( 0 < $preview_lesson_count && ! $is_user_taking_course ) {
			?>
			<p class="sensei-free-lessons">
				<a href="<?php echo esc_url( get_permalink() ); ?>">
					<?php esc_html_e( 'Preview this course', 'sensei-lms' ); ?>
				</a>
				-
				<?php
					// translators: Placeholder is the number of preview lessons.
					echo esc_html( sprintf( __( '(%d preview lessons)', 'sensei-lms' ), $preview_lesson_count ) );
				?>
			</p>

			<?php
		}
	}

	/**
	 * Add course mata to the course meta hook
	 *
	 * @since 1.9.0
	 * @param integer $course_id
	 */
	public function the_course_meta( $course_id ) {
		$course              = get_post( $course_id );
		$category_output     = get_the_term_list( $course->ID, 'course-category', '', ', ', '' );
		$author_display_name = get_the_author_meta( 'display_name', $course->post_author );
		$lesson_count        = Sensei()->course->course_lesson_count( $course_id );

		if ( isset( Sensei()->settings->settings['course_author'] ) && ( Sensei()->settings->settings['course_author'] ) ) {
			echo '<span class="course-author">' .
				wp_kses(
					sprintf(
						// translators: %1$s is the author posts URL, %2$s and %3$s are the author name.
						__( 'by <a href="%1$s" title="%2$s">%3$s</a>', 'sensei-lms' ),
						esc_url( get_author_posts_url( $course->post_author ) ),
						esc_attr( $author_display_name ),
						esc_html( $author_display_name )
					),
					array(
						'a' => array(
							'href'  => array(),
							'title' => array(),
						),
					)
				) .
			'</span>';
		}

		echo '<div class="sensei-course-meta">';

		/** This action is documented in includes/class-sensei-frontend.php */
		do_action( 'sensei_course_meta_inside_before', $course->ID );

		echo '<span class="course-lesson-count">' .
			// translators: Placeholder %d is the lesson count.
			esc_html( sprintf( _n( '%d Lesson', '%d Lessons', $lesson_count, 'sensei-lms' ), $lesson_count ) ) .
		'</span>';

		if ( ! empty( $category_output ) ) {
			echo '<span class="course-category">' .
				// translators: Placeholder is a comma-separated list of the Course categories.
				wp_kses_post( sprintf( __( 'in %s', 'sensei-lms' ), $category_output ) ) .
			'</span>';
		}

		// number of completed lessons
		if ( Sensei_Utils::has_started_course( $course->ID, get_current_user_id() )
			|| Sensei_Utils::user_completed_course( $course->ID, get_current_user_id() ) ) {

			$completed    = count( $this->get_completed_lesson_ids( $course->ID, get_current_user_id() ) );
			$lesson_count = count( $this->course_lessons( $course->ID ) );
			// translators: Placeholders are the counts for lessons completed and total lessons, respectively.
			echo '<span class="course-lesson-progress">' . esc_html( sprintf( __( '%1$d of %2$d lessons completed', 'sensei-lms' ), $completed, $lesson_count ) ) . '</span>';
		}

		/** This action is documented in includes/class-sensei-frontend.php */
		do_action( 'sensei_course_meta_inside_after', $course->ID );

		echo '</div>';
	}

	/**
	 * Filter the classes attached to a post types for courses
	 * and add a status class for when the user is logged in.
	 *
	 * @param $classes
	 * @param $class
	 * @param $post_id
	 *
	 * @return array $classes
	 */
	public static function add_course_user_status_class( $classes, $class, $course_id ) {

		if ( 'course' == get_post_type( $course_id ) && is_user_logged_in() ) {

			if ( Sensei_Utils::user_completed_course( $course_id, get_current_user_id() ) ) {

				$classes[] = 'user-status-completed';

			} else {

				$classes[] = 'user-status-active';

			}
		}

		return $classes;

	}

	/**
	 * Prints out the course action buttons links
	 *
	 * - complete course
	 * - delete course
	 *
	 * @param WP_Post $course
	 */
	public static function the_course_action_buttons( $course ) {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$has_course_complete_button = false;
		$has_delete_course_button   = false;
		$has_results_button         = false;

		if ( 0 < absint( count( Sensei()->course->course_lessons( $course->ID ) ) )
			&& Sensei()->settings->settings['course_completion'] == 'complete'
			&& ! Sensei_Utils::user_completed_course( $course, get_current_user_id() ) ) {
			$has_course_complete_button = true;
		}

		$course_purchased = false;

		if ( class_exists( 'Sensei_WC' ) && Sensei_WC::is_woocommerce_active() ) {
			// Get the product ID.
			$wc_post_id = get_post_meta( intval( $course->ID ), '_course_woocommerce_product', true );

			if ( 0 < $wc_post_id ) {
				$user             = wp_get_current_user();
				$course_purchased = Sensei_WC::has_customer_bought_product( $user->ID, $wc_post_id );
			}
		}

		/**
		 * Hide or show the delete course button.
		 *
		 * This button on shows in certain instances, but this filter will hide it in those
		 * cases. For other instances the button will be hidden.
		 *
		 * @since 1.9.0
		 *
		 * @deprecated 2.0.0
		 *
		 * @param bool $show_delete_course_button defaults to false
		 */
		$show_delete_course_button = apply_filters_deprecated(
			'sensei_show_delete_course_button',
			[ false ],
			'2.0.0',
			null,
			'Sensei LMS "Delete Course" button will be removed in version 4.0.'
		);

		if ( ! $course_purchased
				&& ! Sensei_Utils::user_completed_course( $course->ID, get_current_user_id() )
				&& $show_delete_course_button ) {
			$has_delete_course_button = true;
		}

		$has_quizzes = Sensei()->course->course_quizzes( $course->ID, true );

		if ( has_filter( 'sensei_results_links' ) || $has_quizzes ) {
			$has_results_button = true;
		}

		if ( ! $has_course_complete_button && ! $has_delete_course_button && ! $has_results_button ) {
			return;
		}

		?>
			<section class="entry-actions">
				<form method="POST" action="<?php echo esc_url( remove_query_arg( array( 'active_page', 'completed_page' ) ) ); ?>">

					<input type="hidden"
						name="<?php echo esc_attr( 'woothemes_sensei_complete_course_noonce' ); ?>"
						id="<?php echo esc_attr( 'woothemes_sensei_complete_course_noonce' ); ?>"
						value="<?php echo esc_attr( wp_create_nonce( 'woothemes_sensei_complete_course_noonce' ) ); ?>"
					/>

					<input type="hidden" name="course_complete_id" id="course-complete-id" value="<?php echo esc_attr( intval( $course->ID ) ); ?>" />

			<?php
			if ( $has_course_complete_button ) {
				wp_enqueue_script( 'sensei-stop-double-submission' );

				?>
					<span>
						<input name="course_complete" type="submit" class="course-complete sensei-stop-double-submission"
							value="<?php esc_attr_e( 'Mark as Complete', 'sensei-lms' ); ?>" />
					</span>

				<?php
			}

			if ( $has_delete_course_button ) {
				?>
					<span>
						<input name="course_complete" type="submit" class="course-delete"
							value="<?php echo esc_attr__( 'Delete Course', 'sensei-lms' ); ?>"/>
					</span>
				<?php
			}

			$results_link = '';

			if ( $has_quizzes ) {
				$results_link = '<a class="button view-results" href="' . esc_url( self::get_view_results_link( $course->ID ) ) . '">' .
					esc_html__( 'View Results', 'sensei-lms' ) . '</a>';
			}

			// Output only if there is content to display.
			if ( $has_results_button ) {
				?>
					<p class="sensei-results-links">
				<?php
				/**
				 * Filter the results links
				 *
				 * @param string $results_links_html
				 * @param integer $course_id
				 */
				echo wp_kses_post( apply_filters( 'sensei_results_links', $results_link, $course->ID ) );
				?>
					</p>

				<?php
			}
			?>
				</form>
			</section>

			<?php
	}

	/**
	 * This function alter the main query on the course archive page.
	 * This also gives Sensei specific filters that allows variables to be altered specifically on the course archive.
	 *
	 * This function targets only the course archives and the my courses page. Shortcodes can set their own
	 * query parameters via the arguments.
	 *
	 * This function is hooked into pre_get_posts filter
	 *
	 * @since 1.9.0
	 *
	 * @param WP_Query $query
	 * @return WP_Query $query
	 */
	public static function course_query_filter( $query ) {

		// exit early for no course queries and admin queries
		if ( is_admin() || 'course' != $query->get( 'post_type' ) ) {
			return $query;
		}

		global $post; // used to get the current page id for my courses

		// for the course archive page
		if ( $query->is_main_query() && is_post_type_archive( 'course' ) ) {
			/**
			 * sensei_archive_courses_per_page
			 *
			 * Sensei courses per page on the course
			 * archive
			 *
			 * @since 1.9.0
			 * @param integer $posts_per_page defaults to the value of get_option( 'posts_per_page' )
			 */
			$query->set( 'posts_per_page', apply_filters( 'sensei_archive_courses_per_page', get_option( 'posts_per_page' ) ) );
			if ( isset( $query->query ) && isset( $query->query['paged'] ) && false === $query->get( 'paged', false ) ) {
				$query->set( 'paged', $query->query['paged'] );
			}
		}
		// for the my courses page
		elseif ( isset( $post ) && is_page() && Sensei()->settings->get( 'my_course_page' ) == $post->ID ) {
			/**
			 * sensei_my_courses_per_page
			 *
			 * Sensei courses per page on the my courses page
			 * as set in the settings
			 *
			 * @since 1.9.0
			 * @param integer $posts_per_page default 10
			 */
			$query->set( 'posts_per_page', apply_filters( 'sensei_my_courses_per_page', $query->get( 'posts_per_page', 10 ) ) );

		}

		return $query;

	}

	/**
	 * Determine the class of the course loop
	 *
	 * This will output .first or .last and .course-item-number-x
	 *
	 * @return array $extra_classes
	 * @since 1.9.0
	 */
	public static function get_course_loop_content_class() {
		global $sensei_course_loop;

		if ( ! isset( $sensei_course_loop ) ) {
			$sensei_course_loop = array();
		}

		if ( ! isset( $sensei_course_loop['counter'] ) ) {
			$sensei_course_loop['counter'] = 0;
		}

		if ( ! isset( $sensei_course_loop['columns'] ) ) {
			$sensei_course_loop['columns'] = self::get_loop_number_of_columns();
		}

		// increment the counter
		$sensei_course_loop['counter']++;

		$extra_classes = array();

		// Apply "first" and "last" CSS classes for grid-based layouts.
		if ( 1 !== $sensei_course_loop['columns'] ) {
			if ( 0 === ( $sensei_course_loop['counter'] - 1 ) % $sensei_course_loop['columns'] ) {
				$extra_classes[] = 'first';
			}

			if ( 0 === $sensei_course_loop['counter'] % $sensei_course_loop['columns'] ) {
				$extra_classes[] = 'last';
			}
		}

		// add the item number to the classes as well.
		$extra_classes[] = 'loop-item-number-' . $sensei_course_loop['counter'];

		/**
		 * Filter the course loop class the fires in the in get_course_loop_content_class function
		 * which is called from the course loop content-course.php.
		 *
		 * @since 1.9.0
		 * @hook sensei_course_loop_content_class
		 *
		 * @param {array} $extra_classes
		 * @param {WP_Post} $loop_current_course
		 *
		 * @return {array} Additional CSS classes.
		 */
		return apply_filters( 'sensei_course_loop_content_class', $extra_classes, get_post() );

	}

	/**
	 * Get the number of columns set for Sensei courses
	 *
	 * @since 1.9.0
	 * @return mixed|void
	 */
	public static function get_loop_number_of_columns() {

		/**
		 * Filter the number of columns on the course archive page.
		 *
		 * @since 1.9.0
		 * @param int $number_of_columns default 1
		 */
		return apply_filters( 'sensei_course_loop_number_of_columns', 1 );

	}

	/**
	 * Output the course archive filter markup
	 *
	 * hooked into sensei_loop_course_before
	 *
	 * @since 1.9.0
	 * @param
	 */
	public static function course_archive_sorting( $query ) {

		// don't show on category pages and other pages
		if ( ! is_archive( 'course ' ) || is_tax( 'course-category' ) ) {
			return;
		}

		/**
		 * Filter the sensei archive course order by values
		 *
		 * @since 1.9.0
		 * @param array $options {
		 *  @type string $option_value
		 *  @type string $option_string
		 * }
		 */
		$course_order_by_options = apply_filters(
			'sensei_archive_course_order_by_options',
			array(
				'newness' => __( 'Sort by newest first', 'sensei-lms' ),
				'title'   => __( 'Sort by title A-Z', 'sensei-lms' ),
			)
		);

		// setup the currently selected item
		$selected = 'newness';
		if ( isset( $_REQUEST['course-orderby'] ) && in_array( $selected, array_keys( $course_order_by_options ), true ) ) {

			$selected = sanitize_text_field( $_REQUEST['course-orderby'] );

		}

		?>

		<form class="sensei-ordering" name="sensei-course-order" action="<?php echo esc_attr( Sensei_Utils::get_current_url() ); ?>" method="get">
			<select name="course-orderby" class="orderby">
				<?php
				foreach ( $course_order_by_options as $value => $text ) {

					echo '<option value="' . esc_attr( $value ) . '"' . selected( $selected, $value, false ) . '>' . esc_html( $text ) . '</option>';

				}
				?>
			</select>
		</form>

		<?php
	}

	/**
	 * Output the course archive filter markup
	 *
	 * hooked into sensei_loop_course_before
	 *
	 * @since 1.9.0
	 * @param
	 */
	public static function course_archive_filters( $query ) {

		// don't show on category pages
		if ( is_tax( 'course-category' ) ) {
			return;
		}

		/**
		 * filter the course archive filter buttons
		 *
		 * @since 1.9.0
		 * @param array $filters{
		 *   @type array ( $id, $url , $title )
		 * }
		 */
		$filters = apply_filters(
			'sensei_archive_course_filter_by_options',
			array(
				array(
					'id'    => 'all',
					'url'   => self::get_courses_page_url(),
					'title' => __( 'All', 'sensei-lms' ),
				),
				array(
					'id'    => 'featured',
					'url'   => add_query_arg( array( 'course_filter' => 'featured' ), self::get_courses_page_url() ),
					'title' => __( 'Featured', 'sensei-lms' ),
				),
			)
		);

		?>
		<ul class="sensei-course-filters clearfix" >
			<?php

			$active_course_filter = isset( $_GET['course_filter'] ) ? sanitize_text_field( $_GET['course_filter'] ) : 'all';

			foreach ( $filters as $filter ) {

				$active_class = $active_course_filter == $filter['id'] ? 'active' : '';

				echo '<li><a class="' . esc_attr( $active_class ) . '" id="' . esc_attr( $filter['id'] ) . '" href="' . esc_url( $filter['url'] ) . '" >' . esc_html( $filter['title'] ) . '</a></li>';

			}
			?>

		</ul>

		<?php

	}

	/**
	 * if the featured link is clicked on the course archive page
	 * filter the courses returned to only show those featured
	 *
	 * Hooked into pre_get_posts
	 *
	 * @since 1.9.0
	 * @param WP_Query $query
	 * @return WP_Query $query
	 */
	public static function course_archive_featured_filter( $query ) {

		if ( isset( $_GET['course_filter'] ) && 'featured' == $_GET['course_filter'] && $query->is_main_query() ) {
			// setup meta query for featured courses
			$query->set( 'meta_value', 'featured' );
			$query->set( 'meta_key', '_course_featured' );
			$query->set( 'meta_compare', '=' );
		}

		return $query;
	}

	/**
	 * if the course order drop down is changed
	 *
	 * Hooked into pre_get_posts
	 *
	 * @since 1.9.0
	 * @param WP_Query $query
	 * @return WP_Query $query
	 */
	public static function course_archive_order_by_title( $query ) {

		if ( isset( $_REQUEST['course-orderby'] ) && 'title' == $_REQUEST['course-orderby']
			&& 'course' === $query->get( 'post_type' ) && $query->is_main_query() ) {
			// setup the order by title for this query
			$query->set( 'orderby', 'title' );
			$query->set( 'order', 'ASC' );
		}

		return $query;
	}


	/**
	 * Get the link to the courses page. This will be the course post type archive
	 * page link or the page the user set in their settings
	 *
	 * @since 1.9.0
	 * @return string $course_page_url
	 */
	public static function get_courses_page_url() {
		$course_page_id  = intval( Sensei()->settings->settings['course_page'] );
		$course_page_url = empty( $course_page_id ) ? get_post_type_archive_link( 'course' ) : get_permalink( $course_page_id );

		/**
		 * Filter the course archive page URL.
		 *
		 * @since 3.0.0
		 *
		 * @param string $course_page_url Course archive page URL.
		 */
		return apply_filters( 'sensei_course_archive_page_url', $course_page_url );
	}

	/**
	 * Get the link to the course completed page.
	 *
	 * @since 3.13.0
	 * @param int $course_id Course ID to append to URL.
	 *
	 * @return string The URL or empty string if page is not set or does not exist.
	 */
	public static function get_course_completed_page_url( $course_id ) {
		$page_id = isset( Sensei()->settings->settings['course_completed_page'] ) ? intval( Sensei()->settings->settings['course_completed_page'] ) : 0;
		$url     = $page_id ? get_permalink( $page_id ) : '';
		$url     = $url && 0 < $course_id ? add_query_arg( 'course_id', $course_id, $url ) : '';

		/**
		 * Filter the course completed page URL.
		 *
		 * @since 3.13.0
		 * @hook sensei_course_completed_page_url
		 *
		 * @param {string} $url       Course completed page URL.
		 * @param {int}    $course_id ID of the course that was completed.
		 *
		 * @return {string} Course completed page URL.
		 */
		return apply_filters( 'sensei_course_completed_page_url', $url, $course_id );
	}

	/**
	 * Get the link for the View Results button.
	 *
	 * @since 3.13.0
	 * @param int $course_id Course ID.
	 *
	 * @return string The URL for the View Results button.
	 */
	public static function get_view_results_link( $course_id ) {
		$url = self::get_course_completed_page_url( $course_id );

		if ( ! $url ) {
			$url = Sensei()->course_results->get_permalink( $course_id );
		}

		return $url;
	}

	/**
	 * Output the headers on the course archive page
	 *
	 * @since 1.9.0
	 * @param string $query_type
	 * @param string $before_html
	 * @param string $after_html
	 * @return void
	 */
	public static function archive_header( $query_type = '', $before_html = '', $after_html = '' ) {

		if ( ! is_post_type_archive( 'course' ) ) {
			return;
		}

		$html = '';

		if ( empty( $before_html ) ) {

			$before_html = '<header class="archive-header"><h1>';

		}

		if ( empty( $after_html ) ) {

			$after_html = '</h1></header>';

		}

		if ( is_tax( 'course-category' ) ) {

			global $wp_query;

			$taxonomy_obj        = $wp_query->get_queried_object();
			$taxonomy_short_name = $taxonomy_obj->taxonomy;
			$taxonomy_raw_obj    = get_taxonomy( $taxonomy_short_name );
			// translators: Placeholders are the taxonomy name and the term name, respectively.
			$title = sprintf( __( '%1$s Archives: %2$s', 'sensei-lms' ), $taxonomy_raw_obj->labels->name, $taxonomy_obj->name );
			echo wp_kses_post( apply_filters( 'course_category_archive_title', $before_html . $title . $after_html ) );
			return;

		}

		switch ( $query_type ) {
			case 'newcourses':
				$html .= $before_html . __( 'New Courses', 'sensei-lms' ) . $after_html;
				break;
			case 'featuredcourses':
				$html .= $before_html . __( 'Featured Courses', 'sensei-lms' ) . $after_html;
				break;
			case 'freecourses':
				$html .= $before_html . __( 'Free Courses', 'sensei-lms' ) . $after_html;
				break;
			case 'paidcourses':
				$html .= $before_html . __( 'Paid Courses', 'sensei-lms' ) . $after_html;
				break;
			default:
				$html .= $before_html . __( 'Courses', 'sensei-lms' ) . $after_html;
				break;
		}

		echo wp_kses_post( apply_filters( 'course_archive_title', $html ) );

	}


	/**
	 * Filter the single course content taking into account if the user has access.
	 *
	 * @since 1.9.0
	 *
	 * @param string $content
	 * @return string $content or $excerpt
	 */
	public static function single_course_content( $content ) {

		if ( ! is_singular( 'course' ) ) {
			return $content;
		}

		/**
		 * Access check for the course content.
		 *
		 * @since 2.0.0
		 *
		 * @param bool $has_access_to_content Filtered variable for if the visitor has access to view the content.
		 * @param int  $course_id             Post ID for the course.
		 */
		if ( apply_filters( 'sensei_course_content_has_access', true, get_the_ID() ) ) {
			if ( empty( $content ) ) {
				remove_filter( 'the_content', array( 'Sensei_Course', 'single_course_content' ) );
				$course = get_post( get_the_ID() );

				$content = apply_filters( 'the_content', $course->post_content );
			}
			return $content;
		} else {
			return '<p class="course-excerpt">' . get_post( get_the_ID() )->post_excerpt . '</p>';
		}

	}

	/**
	 * Output the the single course lessons title with markup.
	 *
	 * @since 1.9.0
	 */
	public static function the_course_lessons_title() {
		if ( ! is_singular( 'course' ) || ! Sensei_Utils::show_course_lessons( get_the_ID() ) ) {
			return;
		}

		global $post;

		$none_module_lessons = Sensei()->modules->get_none_module_lessons( $post->ID );
		$course_lessons      = Sensei()->course->course_lessons( $post->ID );

		// title should be Other Lessons if there are lessons belonging to models.
		$title = __( 'Other Lessons', 'sensei-lms' );

		// show header if there are lessons the number of lesson in the course is the same as those that isn't assigned to a module
		if ( ! empty( $course_lessons ) && count( $course_lessons ) == count( $none_module_lessons ) ) {

			$title = ( 1 === count( $course_lessons ) ) ? __( 'Lesson', 'sensei-lms' ) : __( 'Lessons', 'sensei-lms' );

		} elseif ( empty( $none_module_lessons ) ) { // if the none module lessons are simply empty the title should not be shown

			$title = '';
		}

		/**
		 * hook document in class-woothemes-sensei-message.php
		 */
		$title = apply_filters( 'sensei_single_title', $title, $post->post_type );

		ob_start(); // start capturing the following output.

		?>

			<header>
				<h2>
					<?php echo esc_html( $title ); ?>
				</h2>
			</header>

		<?php

		/**
		 * Filter the title and markup that appears above the lessons on a single course
		 * page.
		 *
		 * @since 1.9.0
		 * @param string $lessons_title_html
		 */
		echo wp_kses_post( apply_filters( 'the_course_lessons_title', ob_get_clean() ) ); // output and filter the captured output and stop capturing.

	}

	/**
	 * This function loads the global wp_query object with with lessons
	 * of the current course. It is designed to be used on the single-course template
	 * and expects the global post to be a singular course.
	 *
	 * This function excludes lessons belonging to modules as they are
	 * queried separately.
	 *
	 * @since 1.9.0
	 * @global $wp_query
	 */
	public static function load_single_course_lessons_query() {

		global $post, $wp_query;

		$course_id = $post->ID;

		if ( 'course' != get_post_type( $course_id ) ) {
			return;
		}

		$course_lessons_post_status = isset( $wp_query ) && $wp_query->is_preview() ? 'all' : 'publish';

		$course_lesson_query_args = array(
			'post_status'      => $course_lessons_post_status,
			'post_type'        => 'lesson',
			'posts_per_page'   => 500,
			'orderby'          => 'date',
			'order'            => 'ASC',
			'meta_query'       => array(
				array(
					'key'   => '_lesson_course',
					'value' => intval( $course_id ),
				),
			),
			'suppress_filters' => 0,
		);

		// Exclude lessons belonging to modules as they are queried along with the modules.
		$modules = Sensei()->modules->get_course_modules( $course_id );
		if ( ! is_wp_error( $modules ) && ! empty( $modules ) && is_array( $modules ) ) {

			$terms_ids = array();
			foreach ( $modules as $term ) {

				$terms_ids[] = $term->term_id;

			}

			$course_lesson_query_args['tax_query'] = array(
				array(
					'taxonomy' => 'module',
					'field'    => 'id',
					'terms'    => $terms_ids,
					'operator' => 'NOT IN',
				),
			);
		}

		// setting lesson order
		$course_lesson_order = get_post_meta( $course_id, '_lesson_order', true );
		$all_ids             = get_posts(
			array(
				'post_type'      => 'lesson',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_lesson_course',
				'meta_value'     => intval( $course_id ),
			)
		);
		if ( ! empty( $course_lesson_order ) ) {

			$course_lesson_query_args['post__in'] = array_merge( explode( ',', $course_lesson_order ), $all_ids );
			$course_lesson_query_args['orderby']  = 'post__in';
			unset( $course_lesson_query_args['order'] );

		}

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Used for lesson loop on single course page. Reset in hook to `sensei_single_course_lessons_after`.
		$wp_query = new WP_Query( $course_lesson_query_args );

	}

	/**
	 * Flush the rewrite rules.
	 *
	 * @since 1.9.0
	 * @deprecated 2.2.1
	 *
	 * @param int $post_id Post ID.
	 */
	public static function flush_rewrite_rules( $post_id ) {
		_deprecated_function( __METHOD__, '2.2.1' );
	}

	/**
	 * If the user is already taking the course, show a progress indicator.
	 * Otherwise, output the course actions like start taking course, register,
	 * etc.
	 *
	 * @since 1.9.0
	 */
	public static function the_course_enrolment_actions() {
		global $post, $current_user;

		if ( 'course' != $post->post_type ) {
			return;
		}

		?>
		<section class="course-meta course-enrolment">
		<?php
		$is_user_taking_course = self::is_user_enrolled( $post->ID, $current_user->ID );

		// If user is taking course, display progress.
		if ( $is_user_taking_course ) {
			// Check if course is completed
			$user_course_status = Sensei_Utils::user_course_status( $post->ID, $current_user->ID );
			$completed_course   = Sensei_Utils::user_completed_course( $user_course_status );
			// Success message
			if ( $completed_course ) {
				?>
				<div class="status completed"><?php esc_html_e( 'Completed', 'sensei-lms' ); ?></div>
				<?php
				$has_quizzes = Sensei()->course->course_quizzes( $post->ID, true );
				if ( has_filter( 'sensei_results_links' ) || $has_quizzes ) {
					?>
					<p class="sensei-results-links">
						<?php
						$results_link = '';

						if ( $has_quizzes ) {
							$results_link = '<a class="view-results" href="' . esc_url( self::get_view_results_link( $post->ID ) ) . '">' . esc_html__( 'View Results', 'sensei-lms' ) . '</a>';
						}

						/**
						 * Filter documented in Sensei_Course::the_course_action_buttons
						 */
						$results_link = apply_filters( 'sensei_results_links', $results_link, $post->ID );
						echo wp_kses_post( $results_link );
						?>
						</p>
					<?php
				}
			} else {
				?>
				<div class="status in-progress"><?php echo esc_html__( 'In Progress', 'sensei-lms' ); ?></div>
				<?php
			}
		} else {
			/**
			 * Action to output the course enrolment buttons. When this is
			 * called, we know that the user is not taking the course, but do
			 * not know whether the user is logged in.
			 *
			 * @since 2.0.0
			 */
			do_action( 'sensei_output_course_enrolment_actions' );
		}
		?>

		</section>
		<?php
	}

	/**
	 * Check if a user can manually enrol themselves.
	 *
	 * @param int $course_id Course post ID.
	 *
	 * @return bool
	 */
	public static function can_current_user_manually_enrol( $course_id ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		// Check if the user is already enrolled through any provider.
		$is_user_enrolled = self::is_user_enrolled( $course_id, get_current_user_id() );

		$default_can_user_manually_enrol = ! $is_user_enrolled;

		$can_user_manually_enrol = apply_filters_deprecated(
			'sensei_display_start_course_form',
			[ $default_can_user_manually_enrol, $course_id ],
			'3.0.0',
			'sensei_can_user_manually_enrol'
		);

		/**
		 * Check if currently logged in user can manually enrol themselves. Defaults to `true` when not already enrolled.
		 *
		 * @since 3.0.0
		 *
		 * @param bool $can_user_manually_enrol True if they can manually enrol themselves, false if not.
		 * @param int  $course_id               Course post ID.
		 */
		return (bool) apply_filters( 'sensei_can_user_manually_enrol', $can_user_manually_enrol, $course_id );
	}

	/**
	 * Output the course actions like start taking course, register, etc. Note
	 * that this expects that the user is not already taking the course; that
	 * check is done in `the_course_enrolment_actions`.
	 *
	 * @access private
	 *
	 * @since 2.0.0
	 */
	public static function output_course_enrolment_actions() {
		global $post;

		$is_course_content_restricted = (bool) apply_filters_deprecated(
			'sensei_is_course_content_restricted',
			[ false, $post->ID ],
			'3.0.0',
			null
		);

		if ( is_user_logged_in() ) {
			$should_display_start_course_form = self::can_current_user_manually_enrol( $post->ID );
			if ( $is_course_content_restricted && false == $should_display_start_course_form ) {
				self::add_course_access_permission_message( '' );
			}
			if ( $should_display_start_course_form ) {
				sensei_start_course_form( $post->ID );
			}
		} else {
			if ( get_option( 'users_can_register' ) ) {

				// set the permissions message
				$anchor_before = '<a href="' . esc_url( sensei_user_login_url() ) . '" >';
				$anchor_after  = '</a>';
				$notice        = sprintf(
					// translators: Placeholders are an opening and closing <a> tag linking to the login URL.
					__( 'or %1$slog in%2$s to view this course.', 'sensei-lms' ),
					$anchor_before,
					$anchor_after
				);

				self::add_course_access_permission_message( $notice );

				$my_courses_page_id = '';

				/**
				 * Filter to force Sensei to output the default WordPress user
				 * registration link.
				 *
				 * @since 1.9.0
				 * @param bool $wp_register_link default false
				 */
				$wp_register_link = apply_filters( 'sensei_use_wp_register_link', false );

				$settings = Sensei()->settings->get_settings();
				if ( isset( $settings['my_course_page'] )
					&& 0 < intval( $settings['my_course_page'] ) ) {

					$my_courses_page_id = $settings['my_course_page'];

				}

				if (
					! (bool) apply_filters_deprecated(
						'sensei_user_can_register_for_course',
						[ true, $post->ID ],
						'3.0.0',
						null
					)
				) {
					return;
				}
				// If a My Courses page was set in Settings, and 'sensei_use_wp_register_link'
				// is false, link to My Courses. If not, link to default WordPress registration page.
				if ( ! empty( $my_courses_page_id ) && $my_courses_page_id && ! $wp_register_link ) {
						$my_courses_url = get_permalink( $my_courses_page_id );
						echo '<div class="status register"><a href="' . esc_url( $my_courses_url ) . '">' .
							esc_html__( 'Register', 'sensei-lms' ) . '</a></div>';
				} else {
						wp_register( '<div class="status register">', '</div>' );
				}
			}
		}
	}

	/**
	 * Output the course video inside the loop.
	 *
	 * @since 1.9.0
	 */
	public static function the_course_video() {

		global $post;

		if ( ! ( $post instanceof WP_Post ) || 'course' !== $post->post_type ) {
			return;
		}

		// Get the meta info
		$course_video_embed = get_post_meta( $post->ID, '_course_video_embed', true );

		if ( 'http' == substr( $course_video_embed, 0, 4 ) ) {

			$course_video_embed = wp_oembed_get( esc_url( $course_video_embed ) );

		}

		$course_video_embed = do_shortcode( $course_video_embed );

		$course_video_embed = Sensei_Wp_Kses::maybe_sanitize( $course_video_embed, self::$allowed_html );

		if ( '' != $course_video_embed ) {
			?>

			<div class="course-video">
				<?php echo wp_kses( $course_video_embed, self::$allowed_html ); ?>
			</div>

			<?php
		}
	}

	/**
	 * Output the title for the single lesson page
	 *
	 * @global $post
	 * @since 1.9.0
	 */
	public static function the_title() {

		if ( ! is_singular( 'course' ) ) {
			return;
		}
		global $post;

		?>
		<header>

			<h1>

				<?php
				/**
				 * Filter documented in class-sensei-messages.php the_title
				 */
				echo wp_kses_post( apply_filters( 'sensei_single_title', get_the_title( $post ), $post->post_type ) );
				?>

			</h1>

		</header>

		<?php

	}

	/**
	 * Show the title on the course category pages
	 *
	 * @since 1.9.0
	 */
	public static function course_category_title() {

		if ( ! is_tax( 'course-category' ) ) {
			return;
		}

		$term = get_queried_object();

		if ( ! empty( $term ) ) {

			$title = __( 'Course Category:', 'sensei-lms' ) . ' ' . $term->name;

		} else {

			$title = __( 'Course Category', 'sensei-lms' );

		}

		$html  = '<h2 class="sensei-category-title">';
		$html .= $title;
		$html .= '</h2>';

		echo wp_kses_post( apply_filters( 'course_category_title', $html, $term->term_id ) );

	}

	/**
	 * Alter the course query to respect the order set for courses and apply
	 * this on the course-category pages.
	 *
	 * @since 1.9.0
	 *
	 * @param WP_Query $query
	 * @return WP_Query
	 */
	public static function alter_course_category_order( $query ) {

		if ( ! $query->is_main_query() || ! is_tax( 'course-category' ) ) {
			return $query;
		}

		$order = get_option( 'sensei_course_order', '' );
		if ( ! empty( $order ) ) {
			$query->set( 'orderby', 'menu_order' );
			$query->set( 'order', 'ASC' );
		}

		return $query;

	}

	/**
	 * The very basic course query arguments
	 * so we don't have to repeat this througout
	 * the code base.
	 *
	 * Usage:
	 * $args = Sensei_Course::get_default_query_args();
	 * $args['custom_arg'] ='custom value';
	 * $courses = get_posts( $args )
	 *
	 * @since 1.9.0
	 *
	 * @return array
	 */
	public static function get_default_query_args() {
		return array(
			'post_type'        => 'course',
			'posts_per_page'   => 1000,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => 0,
		);
	}

	/**
	 * Check if the prerequisite course is completed
	 * Courses with no pre-requisite should always return true
	 *
	 * @since 1.9.0
	 * @param $course_id
	 * @return bool
	 */
	public static function is_prerequisite_complete( $course_id ) {

		$course_prerequisite_id = get_post_meta( $course_id, '_course_prerequisite', true );

		// if it has a pre requisite course check it
		$prerequisite_complete = true;

		if ( ! empty( $course_prerequisite_id ) ) {

			$prerequisite_complete = Sensei_Utils::user_completed_course( $course_prerequisite_id, get_current_user_id() );

		}

		/**
		 * Filter course prerequisite complete
		 *
		 * @since 1.9.10
		 * @param bool $prerequisite_complete
		 * @param int $course_id
		 */
		return apply_filters( 'sensei_course_is_prerequisite_complete', $prerequisite_complete, $course_id );

	}

	/**
	 * Allowing user to set course archive page as front page.
	 *
	 * Expects to be called during pre_get_posts, but only if page_on_front is set
	 * to a non-empty value.
	 *
	 * @since 1.9.5
	 * @param WP_Query $query hooked in from pre_get_posts
	 */
	function allow_course_archive_on_front_page( $query ) {
		// Bail if it's clear we're not looking at a static front page or if the $running flag is
		// set @see https://github.com/Automattic/sensei/issues/1438 and https://github.com/Automattic/sensei/issues/1491
		if ( is_admin() || false === $query->is_main_query() || false === $this->is_front_page( $query ) ) {
			return;
		}

		// We don't need this callback to run for subsequent queries (nothing after the main query interests us
		// besides the need to avoid an infinite loop of doom when we call get_posts() on our cloned query
		remove_action( 'pre_get_posts', array( $this, 'allow_course_archive_on_front_page' ) );

		// Set the flag indicating our test query is (about to be) running
		$query_check = clone $query;
		$posts       = $query_check->get_posts();

		if ( ! $query_check->have_posts() ) {
			return;
		}

		// Check if the first returned post matches the currently set static frontpage
		$post = array_shift( $posts );

		if ( 'page' !== $post->post_type || $post->ID != get_option( 'page_on_front' ) ) {
			return;
		}

		// for a valid post that doesn't have any of the old short codes set the archive the same
		// as the page URI
		$settings_course_page = get_post( Sensei()->settings->get( 'course_page' ) );
		if ( ! is_a( $settings_course_page, 'WP_Post' )
			|| Sensei()->post_types->has_old_shortcodes( $settings_course_page->post_content )
			|| $settings_course_page->ID != get_option( 'page_on_front' ) ) {

			return;
		}

		$query->set( 'post_type', 'course' );
		$query->set( 'page_id', '' );

		// Set properties to match an archive
		$query->is_page              = 0;
		$query->is_singular          = 0;
		$query->is_post_type_archive = 1;
		$query->is_archive           = 1;
	}

	/**
	 * Workaround for determining if this is the front page.
	 * We cannot use is_front_page() on pre_get_posts, or it will throw notices.
	 * See https://core.trac.wordpress.org/ticket/21790
	 *
	 * @param WP_Query $query
	 * @return bool
	 */
	private function is_front_page( $query ) {
		if ( 'page' != get_option( 'show_on_front' ) ) {
			return false;
		}

		$page_on_front = get_option( 'page_on_front', '' );
		if ( empty( $page_on_front ) ) {
			return false;
		}

		$page_id = $query->get( 'page_id', '' );
		if ( empty( $page_id ) ) {
			return false;
		}

		return $page_on_front == $page_id;
	}

	/**
	 * Show a message telling the user to complete the previous course if they haven't done so yet
	 *
	 * @since 1.9.10
	 */
	public static function prerequisite_complete_message() {
		if ( ! self::is_prerequisite_complete( get_the_ID() ) ) {
			$message = self::get_course_prerequisite_message( get_the_ID() );
			Sensei()->notices->add_notice( $message, 'info' );
		}
	}

	/**
	 * Generate the HTML of the course prerequisite notice.
	 *
	 * @param int $course_id The course id.
	 *
	 * @return string The HTML.
	 */
	public static function get_course_prerequisite_message( int $course_id ) : string {
		$course_prerequisite_id   = absint( get_post_meta( $course_id, '_course_prerequisite', true ) );
		$course_title             = get_the_title( $course_prerequisite_id );
		$prerequisite_course_link = '<a href="' . esc_url( get_permalink( $course_prerequisite_id ) )
			. '" title="'
			. sprintf(
				// translators: Placeholder $1$s is the course title.
				esc_attr__( 'You must first complete: %1$s', 'sensei-lms' ),
				$course_title
			)
			. '">' . $course_title . '</a>';

		$complete_prerequisite_message = sprintf(
			// translators: Placeholder $1$s is the course title.
			esc_html__( 'You must first complete %1$s before taking this course.', 'sensei-lms' ),
			$prerequisite_course_link
		);

		/**
		 * Filter sensei_course_complete_prerequisite_message.
		 *
		 * @param string $complete_prerequisite_message the message to filter
		 *
		 * @since 1.9.10
		 */
		return apply_filters( 'sensei_course_complete_prerequisite_message', $complete_prerequisite_message );
	}

	/**
	 * Log an event when a course is initially published.
	 *
	 * @since 2.1.0
	 * @access private
	 *
	 * @param WP_Post $course The Course.
	 */
	public function log_initial_publish_event( $course ) {
		$product_ids   = get_post_meta( $course->ID, '_course_woocommerce_product', false );
		$product_count = empty( $product_ids ) ? 0 : count( array_filter( $product_ids, 'is_numeric' ) );

		$event_properties = [
			'module_count'  => count( wp_get_post_terms( $course->ID, 'module' ) ),
			'lesson_count'  => $this->course_lesson_count( $course->ID ),
			'product_count' => $product_count,
			'sample_course' => Sensei_Data_Port_Manager::SAMPLE_COURSE_SLUG === $course->post_name ? 1 : 0,
		];
		sensei_log_event( 'course_publish', $event_properties );
	}

	/**
	 * Mark updating course id when no resave is needed for id sync.
	 *
	 * Hooked into `save_post_course`.
	 *
	 * @since 3.6.0
	 * @access private
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function mark_updating_course_id( $post_id, $post ) {
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		$content = $post->post_content;

		if ( has_block( 'sensei-lms/course-outline', $content ) ) {
			$blocks = parse_blocks( $content );

			if ( ! $this->has_pending_id_sync( $blocks ) ) {
				$this->course_id_updating = $post_id;
			}
		} else {
			$this->course_id_updating = $post_id;
		}
	}

	/**
	 * Check if blocks has pending id sync.
	 *
	 * @since 3.6.0
	 *
	 * @param array[] $blocks Blocks array.
	 *
	 * @return boolean Whether has block with pending id sync.
	 */
	private function has_pending_id_sync( $blocks ) {
		foreach ( $blocks as $block ) {
			$is_checkable_block = 'sensei-lms/course-outline-module' === $block['blockName']
				|| 'sensei-lms/course-outline-lesson' === $block['blockName'];

			if (
				// Check pending id sync.
				( $is_checkable_block && empty( $block['attrs']['id'] ) )
				// Check inner blocks pending id sync.
				|| (
					! empty( $block['innerBlocks'] )
					&& $this->has_pending_id_sync( $block['innerBlocks'] )
				)
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Log the course update.
	 *
	 * Hooked into `shutdown`.
	 *
	 * @since 3.6.0
	 * @access private
	 */
	public function log_course_update() {
		if ( empty( $this->course_id_updating ) ) {
			return;
		}

		$course_id = $this->course_id_updating;
		$post      = get_post( $course_id );

		if ( empty( $post ) ) {
			return;
		}

		$content       = $post->post_content;
		$product_ids   = get_post_meta( $course_id, '_course_woocommerce_product', false );
		$product_count = empty( $product_ids ) ? 0 : count( array_filter( $product_ids, 'is_numeric' ) );

		$event_properties = [
			'course_id'                     => $course_id,
			'has_outline_block'             => has_block( 'sensei-lms/course-outline', $content ) ? 1 : 0,
			'has_progress_block'            => has_block( 'sensei-lms/course-progress', $content ) ? 1 : 0,
			'has_take_course_block'         => has_block( 'sensei-lms/button-take-course', $content ) ? 1 : 0,
			'has_contact_teacher_block'     => has_block( 'sensei-lms/button-contact-teacher', $content ) ? 1 : 0,
			'has_conditional_content_block' => has_block( 'sensei-lms/conditional-content', $content ) ? 1 : 0,
			'module_count'                  => count( wp_get_post_terms( $course_id, 'module' ) ),
			'lesson_count'                  => $this->course_lesson_count( $course_id ),
			'product_count'                 => $product_count,
			'sample_course'                 => Sensei_Data_Port_Manager::SAMPLE_COURSE_SLUG === $post->post_name ? 1 : 0,
		];

		sensei_log_event( 'course_update', $event_properties );
	}

	/**
	 * Disable log course update when it's a REST request.
	 *
	 * Hooked into `rest_api_init`.
	 *
	 * @since 3.6.0
	 * @access private
	 */
	public function disable_log_course_update() {
		remove_action( 'shutdown', [ $this, 'log_course_update' ] );
	}

	/**
	 * Setup the single course page.
	 *
	 * @access private
	 */
	public function setup_single_course_page() {
		global $post;

		// Remove legacy actions on courses with new blocks.
		if (
			$post
			&& is_singular( 'course' )
			&& $this->has_sensei_blocks( $post )
		) {
			$this->remove_legacy_course_actions();
		}
	}

	/**
	 * Adds legacy course actions.
	 *
	 * @param Sensei_Main $sensei Sensei object.
	 */
	public function add_legacy_course_hooks( $sensei ) {
		// Legacy progress bar on the single course page.
		add_action( 'sensei_single_course_content_inside_before', [ $this, 'the_progress_statement' ], 15 );
		add_action( 'sensei_single_course_content_inside_before', [ $this, 'the_progress_meter' ], 16 );
		// Legacy lesson listing.
		add_action( 'sensei_single_course_content_inside_after', [ __CLASS__, 'the_course_lessons_title' ], 9 );
		add_action( 'sensei_single_course_content_inside_after', 'course_single_lessons', 10 );

		// Take this course.
		add_action( 'sensei_single_course_content_inside_before', [ __CLASS__, 'the_course_enrolment_actions' ], 30 );

		// Module listing.
		add_action( 'sensei_single_course_content_inside_after', [ $sensei->modules, 'load_course_module_content_template' ], 8 );

		// Add message links to courses.
		add_action( 'sensei_single_course_content_inside_before', [ $sensei->post_types->messages, 'send_message_link' ], 35 );

		// Course prerequisite completion message.
		add_action( 'sensei_single_course_content_inside_before', array( 'Sensei_Course', 'prerequisite_complete_message' ), 20 );

	}

	/**
	 * Remove legacy course actions.
	 */
	public function remove_legacy_course_actions() {
		// Legacy lesson listing.
		remove_action( 'sensei_single_course_content_inside_after', [ __CLASS__, 'the_course_lessons_title' ], 9 );
		remove_action( 'sensei_single_course_content_inside_after', 'course_single_lessons', 10 );

		// Module listing.
		remove_action( 'sensei_single_course_content_inside_after', [ Sensei()->modules, 'load_course_module_content_template' ], 8 );

		// Legacy progress bar on the single course page.
		remove_action( 'sensei_single_course_content_inside_before', [ $this, 'the_progress_statement' ], 15 );
		remove_action( 'sensei_single_course_content_inside_before', [ $this, 'the_progress_meter' ], 16 );

		// Take this course.
		remove_action( 'sensei_single_course_content_inside_before', [ __CLASS__, 'the_course_enrolment_actions' ], 30 );

		// Course prerequisite completion message.
		remove_action( 'sensei_single_course_content_inside_before', array( 'Sensei_Course', 'prerequisite_complete_message' ), 20 );

		// Add message links to courses.
		remove_action( 'sensei_single_course_content_inside_before', [ Sensei()->post_types->messages, 'send_message_link' ], 35 );

	}

	/**
	 * Check if a course is a legacy course.
	 *
	 * @param int|WP_Post $course Course ID or course object.
	 *
	 * @return bool
	 */
	public function is_legacy_course( $course = null ) {
		return ! $this->has_sensei_blocks( $course );
	}

	/**
	 * Check if a course contains Sensei blocks.
	 *
	 * @param int|WP_Post $course Course ID or course object.
	 *
	 * @return bool
	 */
	public function has_sensei_blocks( $course = null ) {
		$course = get_post( $course );

		$course_blocks = [
			'sensei-lms/course-outline',
			'sensei-lms/course-progress',
			'sensei-lms/button-take-course',
			'sensei-lms/button-contact-teacher',
			'sensei-lms/button-view-results',
		];

		foreach ( $course_blocks as $block ) {
			if ( has_block( $block, $course ) ) {
				return true;
			}
		}

		return false;
	}
}

/**
 * Class WooThemes_Sensei_Course
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Course extends Sensei_Course{}
