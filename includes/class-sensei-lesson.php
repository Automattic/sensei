<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Sensei Lessons Class
 *
 * All functionality pertaining to the lessons post type in Sensei.
 *
 * @package Content
 * @author Automattic
 *
 * @since 1.0.0
 */
class Sensei_Lesson {
	public $token;
	public $meta_fields;
	public $allowed_html;

	/**
	 * Lesson ID being saved.
	 *
	 * @since 3.8.0
	 *
	 * @var int
	 */
	private $lesson_id_updating;

	/**
	 * Message to display on the legacy quiz meta boxes.
	 *
	 * @since 3.9.1
	 *
	 * @var string
	 */
	private $legacy_quiz_message;

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {

		$this->token = 'lesson';

		// Setup meta fields for this post type
		$this->meta_fields = array( 'lesson_prerequisite', 'lesson_course', 'lesson_preview', 'lesson_length', 'lesson_complexity', 'lesson_video_embed' );

		$this->question_order = '';

		$this->allowed_html = Sensei_Wp_Kses::get_default_wp_kses_allowed_html();

		$this->legacy_quiz_message = '<p><em>' .
			sprintf(
				// translators: %1$s is a link to the quiz documentation, %2$s is a link to a support article about the WordPress editor.
				__(
					'*Note that this functionality has been moved to the <a href="%1$s">quiz block</a> and will not be supported going forward. Please consider switching to the <a href="%2$s">block editor</a>.</em>',
					'sensei-lms'
				),
				'https://senseilms.com/lesson/quizzes/',
				'https://wordpress.org/support/article/wordpress-editor/'
			) .
		'</em></p>';

		// Admin actions
		if ( is_admin() ) {

			// Metabox functions
			add_action( 'admin_menu', array( $this, 'meta_box_setup' ), 20 );
			add_action( 'save_post', array( $this, 'meta_box_save' ) );
			add_action( 'save_post', array( $this, 'quiz_update' ) );
			add_action( 'save_post', array( $this, 'add_lesson_to_course_order' ) );

			// Custom Write Panel Columns
			add_filter( 'manage_edit-lesson_columns', array( $this, 'add_column_headings' ), 20, 1 );
			add_action( 'manage_posts_custom_column', array( $this, 'add_column_data' ), 10, 2 );

			// Add/Update question
			add_action( 'wp_ajax_lesson_update_question', array( $this, 'lesson_update_question' ) );
			add_action( 'wp_ajax_nopriv_lesson_update_question', array( $this, 'lesson_update_question' ) );

			// Update grade type
			add_action( 'wp_ajax_lesson_update_grade_type', array( $this, 'lesson_update_grade_type' ) );
			add_action( 'wp_ajax_nopriv_lesson_update_grade_type', array( $this, 'lesson_update_grade_type' ) );

			// Update question order
			add_action( 'wp_ajax_lesson_update_question_order', array( $this, 'lesson_update_question_order' ) );
			add_action( 'wp_ajax_nopriv_lesson_update_question_order', array( $this, 'lesson_update_question_order' ) );

			// Update question order
			add_action( 'wp_ajax_lesson_update_question_order_random', array( $this, 'lesson_update_question_order_random' ) );
			add_action( 'wp_ajax_nopriv_lesson_update_question_order_random', array( $this, 'lesson_update_question_order_random' ) );

			// Get answer ID
			add_action( 'wp_ajax_question_get_answer_id', array( $this, 'question_get_answer_id' ) );
			add_action( 'wp_ajax_nopriv_question_get_answer_id', array( $this, 'question_get_answer_id' ) );

			// Add multiple questions
			add_action( 'wp_ajax_lesson_add_multiple_questions', array( $this, 'lesson_add_multiple_questions' ) );
			add_action( 'wp_ajax_nopriv_lesson_add_multiple_questions', array( $this, 'lesson_add_multiple_questions' ) );

			// Remove multiple questions
			add_action( 'wp_ajax_lesson_remove_multiple_questions', array( $this, 'lesson_remove_multiple_questions' ) );
			add_action( 'wp_ajax_nopriv_lesson_remove_multiple_questions', array( $this, 'lesson_remove_multiple_questions' ) );

			// Get question category limit
			add_action( 'wp_ajax_get_question_category_limit', array( $this, 'get_question_category_limit' ) );
			add_action( 'wp_ajax_nopriv_get_question_category_limit', array( $this, 'get_question_category_limit' ) );

			// Add existing questions
			add_action( 'wp_ajax_lesson_add_existing_questions', array( $this, 'lesson_add_existing_questions' ) );
			add_action( 'wp_ajax_nopriv_lesson_add_existing_questions', array( $this, 'lesson_add_existing_questions' ) );

			// Filter existing questions
			add_action( 'wp_ajax_filter_existing_questions', array( $this, 'quiz_panel_filter_existing_questions' ) );
			add_action( 'wp_ajax_nopriv_filter_existing_questions', array( $this, 'quiz_panel_filter_existing_questions' ) );

			// output bulk edit fields
			add_action( 'bulk_edit_custom_box', array( $this, 'all_lessons_edit_fields' ), 10, 2 );
			add_action( 'quick_edit_custom_box', array( $this, 'all_lessons_edit_fields' ), 10, 2 );

			// load quick edit default values
			add_action( 'manage_lesson_posts_custom_column', array( $this, 'set_quick_edit_admin_defaults' ), 11, 2 );

			// save bulk edit fields
			add_action( 'wp_ajax_save_bulk_edit_book', array( $this, 'save_all_lessons_edit_fields' ) );

			add_action( 'admin_head', array( $this, 'add_custom_link_to_course' ) );

			// Log lesson update.
			add_action( 'save_post_lesson', [ $this, 'mark_updating_lesson_id' ], 10, 2 );
			add_action( 'shutdown', [ $this, 'log_lesson_update' ] );
			add_action( 'rest_api_init', [ $this, 'disable_log_lesson_update' ] );
		} else {
			// Frontend actions
			// Starts lesson when the student visits for the first time and prerequisite courses have been met.
			add_action( 'sensei_single_lesson_content_inside_before', array( __CLASS__, 'maybe_start_lesson' ) );
		}

		// Log event on the initial publish for a lesson.
		add_action( 'sensei_lesson_initial_publish', [ $this, 'log_initial_publish_event' ] );
	}

	/**
	 * Adds a link for editing the lesson's course if it belongs to a course.
	 */
	public function add_custom_link_to_course() {
		global $post;

		if ( ! isset( $post ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( 'post' !== $screen->base ) {
			return;
		}

		$course_id = intval( get_post_meta( $post->ID, '_lesson_course', true ) );

		if ( ! $course_id ) {
			return;
		}

		$course_status = get_post_status( $course_id );

		if ( 'trash' === $course_status ) {
			return;
		}

		$type = get_post_type( $post );

		if ( 'lesson' !== $type ) {
			return;
		}

		$url = admin_url( "post.php?post=$course_id&action=edit" ); ?>

		<script>
			jQuery(function () {
				jQuery("body.post-type-lesson .wrap a.page-title-action")
					.last()
					.after('<a href="<?php echo esc_attr( $url ); ?>" class="page-title-action" data-sensei-log-event="lesson_edit_course_click"><?php echo esc_html__( 'Edit Course', 'sensei-lms' ); ?></a>');
			});
		</script>
		<?php
	}

	/**
	 * meta_box_setup function.
	 *
	 * @access public
	 * @return void
	 */
	public function meta_box_setup() {

		// Add Meta Box for Lesson Course
		add_meta_box( 'lesson-course', esc_html__( 'Course', 'sensei-lms' ), array( $this, 'lesson_course_meta_box_content' ), $this->token, 'side', 'default' );

		// Add Meta Box for Prerequisite Lesson
		add_meta_box( 'lesson-prerequisite', esc_html__( 'Prerequisite', 'sensei-lms' ), array( $this, 'lesson_prerequisite_meta_box_content' ), $this->token, 'side', 'low' );

		// Add Meta Box for Lesson Preview
		add_meta_box( 'lesson-preview', esc_html__( 'Preview', 'sensei-lms' ), array( $this, 'lesson_preview_meta_box_content' ), $this->token, 'side', 'low' );

		// Add Meta Box for Lesson Information
		add_meta_box( 'lesson-info', esc_html__( 'Lesson Information', 'sensei-lms' ), array( $this, 'lesson_info_meta_box_content' ), $this->token, 'normal', 'default' );

		if ( ! Sensei()->quiz->is_block_based_editor_enabled() ) {
			// Add Meta Box for Quiz Settings
			add_meta_box( 'lesson-quiz-settings', esc_html__( 'Quiz Settings*', 'sensei-lms' ), array( $this, 'lesson_quiz_settings_meta_box_content' ), $this->token, 'normal', 'default' );

			// Add Meta Box for Lesson Quiz Questions
			add_meta_box( 'lesson-quiz', esc_html__( 'Quiz Questions*', 'sensei-lms' ), array( $this, 'lesson_quiz_meta_box_content' ), $this->token, 'normal', 'default' );
		}

		// Remove "Custom Settings" meta box.
		remove_meta_box( 'woothemes-settings', $this->token, 'normal' );

		// Add JS scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Add CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );

	}

	/**
	 * lesson_info_meta_box_content function.
	 *
	 * @access public
	 * @return void
	 */
	public function lesson_info_meta_box_content() {
		global $post;

		$lesson_length      = get_post_meta( $post->ID, '_lesson_length', true );
		$lesson_complexity  = get_post_meta( $post->ID, '_lesson_complexity', true );
		$complexity_array   = $this->lesson_complexities();
		$lesson_video_embed = get_post_meta( $post->ID, '_lesson_video_embed', true );

		$lesson_video_embed = Sensei_Wp_Kses::maybe_sanitize( $lesson_video_embed, $this->allowed_html );

		$html = '';
		// Lesson Length
		$html .= '<p><label for="lesson_length">' . esc_html__( 'Lesson Length in minutes', 'sensei-lms' ) . ': </label>';
		$html .= '<input type="number" id="lesson-length" name="lesson_length" class="small-text" value="' . esc_attr( $lesson_length ) . '" /></p>' . "\n";
		// Lesson Complexity
		$html     .= '<p><label for="lesson_complexity">' . esc_html__( 'Lesson Complexity', 'sensei-lms' ) . ': </label>';
		$html     .= '<select id="lesson-complexity-options" name="lesson_complexity" class="chosen_select lesson-complexity-select">';
			$html .= '<option value="">' . esc_html__( 'None', 'sensei-lms' ) . '</option>';
		foreach ( $complexity_array as $key => $value ) {
			$html .= '<option value="' . esc_attr( $key ) . '"' . selected( $key, $lesson_complexity, false ) . '>' . esc_html( $value ) . '</option>' . "\n";
		}
		$html .= '</select></p>' . "\n";

		$html .= '<p><label for="lesson_video_embed">' . esc_html__( 'Video Embed Code', 'sensei-lms' ) . ':</label><br/>' . "\n";
		$html .= '<textarea rows="5" cols="50" name="lesson_video_embed" tabindex="6" id="course-video-embed">';

		$html .= $lesson_video_embed . '</textarea></p>' . "\n";

		$html .= '<p>' . esc_html__( 'Paste the embed code for your video (e.g. YouTube, Vimeo etc.) in the box above.', 'sensei-lms' ) . '</p>';

		echo wp_kses(
			$html,
			array_merge(
				wp_kses_allowed_html( 'post' ),
				$this->allowed_html,
				array(
					'input'    => array(
						'class' => array(),
						'id'    => array(),
						'name'  => array(),
						'type'  => array(),
						'value' => array(),
					),
					// Explicitly allow label tag for WP.com.
					'label'    => array(
						'for' => array(),
					),
					'option'   => array(
						'selected' => array(),
						'value'    => array(),
					),
					'select'   => array(
						'class' => array(),
						'id'    => array(),
						'name'  => array(),
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
	 * lesson_prerequisite_meta_box_content function.
	 *
	 * @access public
	 * @return void
	 */
	public function lesson_prerequisite_meta_box_content() {
		global $post;
		// Get existing post meta
		$select_lesson_prerequisite = get_post_meta( $post->ID, '_lesson_prerequisite', true );
		// Get the Lesson Posts
		$post_args   = array(
			'post_type'        => 'lesson',
			'posts_per_page'   => -1,
			'orderby'          => 'title',
			'order'            => 'ASC',
			'exclude'          => $post->ID,
			'suppress_filters' => 0,
			'post_status'      => [ 'publish', 'draft', 'future' ],
		);
		$posts_array = get_posts( $post_args );
		// Build the HTML to Output
		$html  = '';
		$html .= wp_nonce_field( 'sensei-save-post-meta', 'woo_' . $this->token . '_nonce', true, false );
		if ( count( $posts_array ) > 0 ) {
			$html .= '<select id="lesson-prerequisite-options" name="lesson_prerequisite" class="chosen_select widefat" style="width: 100%">' . "\n";
			$html .= '<option value="">' . esc_html__( 'None', 'sensei-lms' ) . '</option>';
			foreach ( $posts_array as $post_item ) {
				$html .= '<option value="' . esc_attr( absint( $post_item->ID ) ) . '"' . selected( $post_item->ID, $select_lesson_prerequisite, false ) . '>' . esc_html( $post_item->post_title ) . '</option>' . "\n";
			}
			$html .= '</select>' . "\n";
		} else {
			$html .= '<p>' . esc_html__( 'No lessons exist yet. Please add some first.', 'sensei-lms' ) . '</p>';
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
						'style' => array(),
					),
				)
			)
		);
	}

	/**
	 * lesson_preview_meta_box_content function.
	 *
	 * @access public
	 * @return void
	 */
	public function lesson_preview_meta_box_content() {
		global $post;
		// Get existing post meta
		$lesson_preview = get_post_meta( $post->ID, '_lesson_preview', true );
		$html           = '';
		$html          .= wp_nonce_field( 'sensei-save-post-meta', 'woo_' . $this->token . '_nonce', true, false );

		$checked = '';
		if ( isset( $lesson_preview ) && ( '' != $lesson_preview ) ) {
			$checked = checked( 'preview', $lesson_preview, false );
		}

		$html .= '<label for="lesson_preview">';
		$html .= '<input type="checkbox" id="lesson_preview" name="lesson_preview" value="preview" ' . $checked . '>&nbsp;' . esc_html__( 'Allow this lesson to be viewed without login', 'sensei-lms' ) . '<br>';

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
					// Explicitly allow label tag for WP.com.
					'label' => array(
						'for' => array(),
					),
				)
			)
		);
	}

	/**
	 * meta_box_save function.
	 *
	 * @access public
	 * @param int $post_id
	 * @return integer $post_id
	 */
	public function meta_box_save( $post_id ) {

		// Verify the nonce before proceeding.
		if ( ( get_post_type( $post_id ) != $this->token ) || ! isset( $_POST[ 'woo_' . $this->token . '_nonce' ] ) || ! wp_verify_nonce( $_POST[ 'woo_' . $this->token . '_nonce' ], 'sensei-save-post-meta' ) ) {
			return $post_id;
		}
		// Get the post type object.
		$post_type = get_post_type_object( get_post_type( $post_id ) );
		// Check if the current user has permission to edit the post.
		if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
			return $post_id;
		}

		// Check if the current post type is a page
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

				remove_action( 'save_post', array( $this, 'meta_box_save' ) );
				$this->save_post_meta( $meta_key, $post_id );

			}
		}
	}

	/**
	 * When course lessons are being ordered by the user,
	 * and a new published lesson has not been added to
	 * course lesson order meta, add it last.
	 *
	 * Hooked into `post_save`
	 *
	 * @since 3.6.0 It order all lessons that is part of a course, regardless their status.
	 *
	 * @access public
	 * @param int $lesson_id
	 * @return void
	 */
	public function add_lesson_to_course_order( $lesson_id = 0 ) {
		$lesson_id = intval( $lesson_id );

		if ( empty( $lesson_id ) ) {
			return;
		}

		if ( 'lesson' != get_post_type( $lesson_id ) ) {
			return;
		}

		$course_id = intval( get_post_meta( $lesson_id, '_lesson_course', true ) );

		if ( empty( $course_id ) ) {
			return;
		}

		// Assumes Sensei admin is loaded.
		Sensei()->admin->save_lesson_order( '', $course_id );
	}

	/**
	 * to actions when the status of the lesson changes to publish
	 *
	 * @deprecated 3.6.0
	 *
	 * @param string  $new_status
	 * @param string  $old_status
	 * @param WP_Post $post
	 */
	public function on_lesson_published( $new_status, $old_status, $post ) {
		_deprecated_function( __METHOD__, '3.6.0' );

		if ( 'lesson' != get_post_type( $post ) ) {
			return;
		}

		$lesson_id = absint( $post->ID );

		if ( $new_status !== 'publish' ) {
			return;
		}

		$this->add_lesson_to_course_order( $lesson_id );
	}


	/**
	 * Update the lesson quiz and all the post meta
	 *
	 * @access public
	 * @return integer|boolean $post_id or false
	 */
	public function quiz_update( $post_id ) {
		global $post;

		if ( Sensei()->quiz->is_block_based_editor_enabled() ) {
			return false;
		}

		// Verify the nonce before proceeding.
		if ( ( 'lesson' != get_post_type( $post_id ) ) || ! isset( $_POST[ 'woo_' . $this->token . '_nonce' ] ) || ! wp_verify_nonce( $_POST[ 'woo_' . $this->token . '_nonce' ], 'sensei-save-post-meta' ) ) {
			if ( isset( $post->ID ) ) {
				return $post->ID;
			} else {
				return false;
			}
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return false;
		}

		// Temporarily disable the filter
		remove_action( 'save_post', array( $this, 'quiz_update' ) );
		// Save the Quiz
		$quiz_id = $this->lesson_quizzes( $post_id, 'any' );

		 // Sanitize and setup the post data
		$_POST = stripslashes_deep( $_POST );

		// Retrieve the update lesson.
		$lesson = get_post( $post_id );

		if ( isset( $_POST['quiz_id'] ) && ( 0 < absint( $_POST['quiz_id'] ) ) ) {
			$quiz_id = absint( $_POST['quiz_id'] );
		}
		$post_title   = esc_html( $lesson->post_title );
		$post_status  = esc_html( $lesson->post_status );
		$post_content = '';

		// Setup Query Arguments
		$post_type_args = array(
			'post_content' => $post_content,
			'post_status'  => $post_status,
			'post_title'   => $post_title,
			'post_type'    => 'quiz',
			'post_parent'  => $post_id,
		);

		$settings = $this->get_quiz_settings();

		// Update or Insert the Lesson Quiz
		if ( 0 < $quiz_id ) {
			// Update the Quiz
			$post_type_args['ID'] = $quiz_id;
			wp_update_post( $post_type_args );

			// Update the post meta data
			update_post_meta( $quiz_id, '_quiz_lesson', $post_id );

			foreach ( $settings as $field ) {
				if ( 'random_question_order' != $field['id'] ) {
					$value = $this->get_submitted_setting_value( $field );
					if ( isset( $value ) && '-1' !== $value ) {
						update_post_meta( $quiz_id, '_' . $field['id'], $value );
					}
				}
			}

			// Set the post terms for quiz-type
			wp_set_post_terms( $quiz_id, array( 'multiple-choice' ), 'quiz-type' );
		} else {
			// Create the Quiz
			$quiz_id = wp_insert_post( $post_type_args );

			// Add the post meta data WP will add it if it doesn't exist
			update_post_meta( $quiz_id, '_quiz_lesson', $post_id );

			foreach ( $settings as $field ) {
				if ( 'random_question_order' != $field['id'] ) {

					// ignore values not posted to avoid
					// overwriting with empty or default values
					// when the values are posted from bulk edit or quick edit
					if ( ! isset( $_POST[ $field['id'] ] ) ) {
						continue;
					}

					$value = $this->get_submitted_setting_value( $field );
					if ( null === $value ) {
						$value = $field['default'];
					}

					if ( isset( $value ) ) {
						add_post_meta( $quiz_id, '_' . $field['id'], $value );
					}
				}
			}

			// Set the post terms for quiz-type
			wp_set_post_terms( $quiz_id, array( 'multiple-choice' ), 'quiz-type' );
		}

		// Add default lesson order meta value
		$course_id = get_post_meta( $post_id, '_lesson_course', true );
		if ( $course_id ) {
			if ( ! get_post_meta( $post_id, '_order_' . $course_id, true ) ) {
				update_post_meta( $post_id, '_order_' . $course_id, 0 );
			}
		}
		// Add reference back to the Quiz
		update_post_meta( $post_id, '_lesson_quiz', $quiz_id );
		// Mark if the Lesson Quiz has questions
		$quiz_questions = Sensei()->lesson->lesson_quiz_questions( $quiz_id );
		if ( 0 < count( $quiz_questions ) ) {
			update_post_meta( $post_id, '_quiz_has_questions', '1' );
		} else {
			delete_post_meta( $post_id, '_quiz_has_questions' );
		}

		// Restore the previously disabled filter
		add_action( 'save_post', array( $this, 'quiz_update' ) );

	}

	/**
	 * Get setting value from POST data.
	 *
	 * @access private
	 *
	 * @param  {string} $field Field name.
	 *
	 * @return string|null
	 */
	public function get_submitted_setting_value( $field ) {

		if ( ! $field ) {
			return null;
		}

		$value = null;

		// phpcs:ignore WordPress.Security.NonceVerification -- Only checking the field existence.
		if ( isset( $_POST[ 'contains_' . $field['id'] ] ) ) {
			$value = '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification -- Only checking the origin page.
		if ( 'quiz_grade_type' === $field['id'] && isset( $_POST['action'] ) && 'editpost' === $_POST['action'] ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$grade_type_checked = isset( $_POST[ $field['id'] ] ) && 'on' === $_POST[ $field['id'] ];
			return $grade_type_checked ? 'auto' : 'manual';
		}

		// phpcs:ignore WordPress.Security.NonceVerification -- Nonce verified in caller
		if ( isset( $_POST[ $field['id'] ] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification -- Nonce verified in caller
			$value = sanitize_text_field( wp_unslash( $_POST[ $field['id'] ] ) );
		}

		return $value;
	}

	/**
	 * save_post_meta function.
	 * Saves lesson meta data
	 *
	 * @access private
	 * @param string $post_key (default: '')
	 * @param int    $post_id (default: 0)
	 * @return int|bool meta id or saved status
	 */
	private function save_post_meta( $post_key = '', $post_id = 0 ) {
		/*
		 * This function is only called from `meta_box_save`, which performs
		 * nonce verification, so we do not need to do so here.
		 */

		// Get the meta key.
		$meta_key = '_' . $post_key;

		// ignore fields are not posted
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! isset( $_POST[ $post_key ] ) ) {

			// except for lesson preview checkbox field
			if ( 'lesson_preview' == $post_key ) {

				$_POST[ $post_key ] = '';

			} else {

				return false;

			}
		}

		// Get the posted data and sanitize it for use as an HTML class.
		if ( 'lesson_video_embed' == $post_key ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$new_meta_value = isset( $_POST[ $post_key ] ) ? $_POST[ $post_key ] : '';
			$new_meta_value = Sensei_Wp_Kses::maybe_sanitize( $new_meta_value, $this->allowed_html );
		} else {
			// phpcs:ignore WordPress.Security.NonceVerification
			$new_meta_value = ( isset( $_POST[ $post_key ] ) ? sanitize_html_class( $_POST[ $post_key ] ) : '' );
		}

		// quick edit work around
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( 'lesson_preview' == $post_key && isset( $_POST['action'] ) && $_POST['action'] == 'inline-save' ) {
			$new_meta_value = '-1';
		}

		// Check if the user has permission to edit the target course.
		if ( 'lesson_course' === $post_key && ! current_user_can( get_post_type_object( 'course' )->cap->edit_post, $new_meta_value ) ) {
			return;
		}

		// update field with the new value
		if ( -1 != $new_meta_value ) {
			return update_post_meta( $post_id, $meta_key, $new_meta_value );
		}

	}

	/**
	 * lesson_course_meta_box_content function.
	 *
	 * @access public
	 * @return void
	 */
	public function lesson_course_meta_box_content() {
		global $post;
		// Setup Lesson Meta Data
		$selected_lesson_course = 0;
		if ( 0 < $post->ID ) {
			$selected_lesson_course = get_post_meta( $post->ID, '_lesson_course', true );
		}
		// Handle preselected course
		if ( isset( $_GET['course_id'] ) && ( 0 < absint( $_GET['course_id'] ) ) ) {
			$selected_lesson_course = absint( $_GET['course_id'] );
		}
		// Get the Lesson Posts
		$post_args   = array(
			'post_type'        => 'course',
			'posts_per_page'   => -1,
			'orderby'          => 'title',
			'order'            => 'ASC',
			'post_status'      => 'any',
			'suppress_filters' => 0,
		);
		$posts_array = get_posts( $post_args );
		// Buid the HTML to Output
		$html = '';
		// Nonce
		$html .= wp_nonce_field( 'sensei-save-post-meta', 'woo_' . $this->token . '_nonce', true, false );

		// Select the course for the lesson
		$drop_down_args = array(
			'name'  => 'lesson_course',
			'id'    => 'lesson-course-options',
			'style' => 'width: 100%',
		);

		$courses         = Sensei_Course::get_all_courses();
		$courses_options = array();
		foreach ( $courses as $course ) {
			$courses_options[ $course->ID ] = get_the_title( $course );
		}
		$html .= Sensei_Utils::generate_drop_down( $selected_lesson_course, $courses_options, $drop_down_args );

		echo wp_kses(
			$html,
			array_merge(
				wp_kses_allowed_html( 'post' ),
				array(
					'input'    => array(
						'class' => array(),
						'id'    => array(),
						'name'  => array(),
						'size'  => array(),
						'type'  => array(),
						'value' => array(),
					),
					// Explicitly allow label tag for WP.com.
					'label'    => array(
						'for' => array(),
					),
					'optgroup' => array(
						'label' => array(),
					),
					'option'   => array(
						'class'    => array(),
						'selected' => array(),
						'value'    => array(),
					),
					'select'   => array(
						'class' => array(),
						'id'    => array(),
						'name'  => array(),
						'style' => array(),
					),
					'textarea' => array(
						'class' => array(),
						'cols'  => array(),
						'id'    => array(),
						'name'  => array(),
						'rows'  => array(),
						'size'  => array(),
						'value' => array(),
					),
				)
			)
		);
	}

	public function quiz_panel( $quiz_id = 0 ) {
		$html  = wp_nonce_field( 'sensei-save-post-meta', 'woo_' . $this->token . '_nonce', true, false );
		$html .= $this->legacy_quiz_message;
		$html .= '<div id="add-quiz-main">';
		if ( 0 == $quiz_id ) {
			$html .= '<p>';
				// Default message and Add a Quiz button
				$html .= esc_html__( 'Once you have saved your lesson you will be able to add questions.', 'sensei-lms' );
			$html     .= '</p>';
		}

			// Quiz Panel CSS Class
			$quiz_class = '';
		if ( 0 == $quiz_id ) {
			$quiz_class = 'hidden';
		}
			// Build the HTML to Output
			$message_class = '';

			// Setup Questions Query
			$questions = array();
		if ( 0 < $quiz_id ) {
			$questions = $this->lesson_quiz_questions( $quiz_id );
		}

			$question_count = 0;
		foreach ( $questions as $question ) {

			if ( $question->post_type == 'multiple_question' ) {
				$question_number = get_post_meta( $question->ID, 'number', true );
				$question_count += $question_number;
			} else {
				++$question_count;
			}
		}

			// Inner DIV
			$html .= '<div id="add-quiz-metadata" class="' . esc_attr( $quiz_class ) . '">';

				// Quiz ID
				$html .= '<input type="hidden" name="quiz_id" id="quiz_id" value="' . esc_attr( $quiz_id ) . '" />';

				// Default Message
		if ( 0 == $quiz_id ) {
			$html     .= '<p class="save-note">';
				$html .= esc_html__( 'Please save your lesson in order to add questions to your quiz.', 'sensei-lms' );
			$html     .= '</p>';
		}

			$html .= '</div>';

			// Question Container DIV
			$html .= '<div id="add-question-main" class="' . esc_attr( $quiz_class ) . '">';
				// Inner DIV
				$html .= '<div id="add-question-metadata">';

					// Count of questions
					$html .= '<input type="hidden" name="question_counter" id="question_counter" value="' . esc_attr( $question_count ) . '" />';
					// Table headers
					$html .= '<table class="widefat" id="sortable-questions">
								<thead>
									<tr>
										<th class="question-count-column">#</th>
										<th>' . esc_html__( 'Question', 'sensei-lms' ) . '</th>
										<th style="width:45px;">' . esc_html__( 'Grade', 'sensei-lms' ) . '</th>
										<th style="width:125px;">' . esc_html__( 'Type', 'sensei-lms' ) . '</th>
										<th style="width:125px;">' . esc_html__( 'Action', 'sensei-lms' ) . '</th>
									</tr>
								</thead>
								<tfoot>
									<tr>
										<th class="question-count-column">#</th>
										<th>' . esc_html__( 'Question', 'sensei-lms' ) . '</th>
										<th>' . esc_html__( 'Grade', 'sensei-lms' ) . '</th>
										<th>' . esc_html__( 'Type', 'sensei-lms' ) . '</th>
										<th>' . esc_html__( 'Action', 'sensei-lms' ) . '</th>
									</tr>
								</tfoot>';

					$message_class = '';
		if ( 0 < $question_count ) {
			$message_class = 'hidden'; }

					$html         .= '<tbody id="no-questions-message" class="' . esc_attr( $message_class ) . '">';
						$html     .= '<tr>';
							$html .= '<td colspan="5">' . esc_html__( 'There are no Questions for this Quiz yet. Please add some below.', 'sensei-lms' ) . '</td>';
						$html     .= '</tr>';
					$html         .= '</tbody>';

		if ( 0 < $question_count ) {
			$html .= $this->quiz_panel_questions( $questions );
		}

					$html .= '</table>';

		if ( ! isset( $this->question_order ) ) {
			$this->question_order = '';
		}

					$html .= '<input type="hidden" id="question-order" name="question-order" value="' . esc_attr( $this->question_order ) . '" />';

				$html .= '</div>';

				// Question Action Container DIV
				$html .= '<div id="add-question-actions">';

					$html .= $this->quiz_panel_add();

				$html .= '</div>';

			$html .= '</div>';

		$html .= '</div>';

		return wp_kses(
			$html,
			array_merge(
				wp_kses_allowed_html( 'post' ),
				array(
					'button'   => array(
						'class'                     => array(),
						'data-uploader-button-text' => array(),
						'data-uploader-title'       => array(),
						'id'                        => array(),
					),
					'input'    => array(
						'checked'     => array(),
						'class'       => array(),
						'id'          => array(),
						'max'         => array(),
						'min'         => array(),
						'name'        => array(),
						'placeholder' => array(),
						'rel'         => array(),
						'size'        => array(),
						'type'        => array(),
						'value'       => array(),
					),
					// Explicitly allow label tag for WP.com.
					'label'    => array(
						'class' => array(),
						'for'   => array(),
					),
					'option'   => array(
						'value' => array(),
					),
					'select'   => array(
						'class' => array(),
						'id'    => array(),
						'name'  => array(),
					),
					// Explicitly allow textarea tag for WP.com.
					'textarea' => array(
						'class' => array(),
						'id'    => array(),
						'name'  => array(),
						'rows'  => array(),
					),
				)
			)
		);
	}

	public function quiz_panel_questions( $questions = array() ) {
		global $quiz_questions;

		$quiz_questions = $questions;

		$html = '';

		if ( count( $questions ) > 0 ) {
			$question_counter = 1;

			foreach ( $questions as $question ) {

				$question_id = $question->ID;

				$question_type = Sensei()->question->get_question_type( $question_id );

				$multiple_data      = array();
				$question_increment = 1;
				if ( 'multiple_question' == $question->post_type ) {
					$question_type = 'category';

					$question_category = get_post_meta( $question->ID, 'category', true );
					$question_cat      = get_term( $question_category, 'question-category' );

					$question_number    = get_post_meta( $question->ID, 'number', true );
					$question_increment = $question_number;

					$multiple_data = array( $question_cat->name, $question_number );
				}

				if ( ! $question_type ) {
					$question_type = 'multiple-choice';
				}

				// Row with question and actions
				$html             .= $this->quiz_panel_question( $question_type, $question_counter, $question_id, 'quiz', $multiple_data );
				$question_counter += $question_increment;

				if ( isset( $this->question_order ) && strlen( $this->question_order ) > 0 ) {
					$this->question_order .= ','; }
				$this->question_order .= $question_id;
			}
		}

		return $html;

	}

	public function quiz_panel_question( $question_type = '', $question_counter = 0, $question_id = 0, $context = 'quiz', $multiple_data = array() ) {
		global $row_counter;

		$html = '';

		$question_class = '';
		if ( 'quiz' == $context ) {
			if ( ! $row_counter || ! isset( $row_counter ) ) {
				$row_counter = 1;
			}
			if ( $row_counter % 2 ) {
				$question_class = 'alternate'; }
			++$row_counter;
		}

		if ( $question_id ) {

			if ( $question_type != 'category' ) {

				$question_grade = Sensei()->question->get_question_grade( $question_id );

				$question_media             = get_post_meta( $question_id, '_question_media', true );
				$question_media_type        = $question_media_thumb = $question_media_link = $question_media_title = '';
				$question_media_thumb_class = $question_media_link_class = $question_media_delete_class = 'hidden';
				$question_media_add_button  = esc_html__( 'Add file', 'sensei-lms' );
				if ( 0 < intval( $question_media ) ) {
					$mimetype = get_post_mime_type( $question_media );
					if ( $mimetype ) {
						$mimetype_array = explode( '/', $mimetype );
						if ( isset( $mimetype_array[0] ) && $mimetype_array[0] ) {
							$question_media_delete_class = '';
							$question_media_type         = $mimetype_array[0];
							if ( 'image' == $question_media_type ) {
								$question_media_thumb = wp_get_attachment_thumb_url( $question_media );
								if ( $question_media_thumb ) {
									$question_media_thumb_class = '';
								}
							}
							$question_media_url = wp_get_attachment_url( $question_media );
							if ( $question_media_url ) {
								$attachment           = get_post( $question_media );
								$question_media_title = $attachment->post_title;

								if ( ! $question_media_title ) {
									$question_media_filename = basename( $question_media_url );
									$question_media_title    = $question_media_filename;
								}
								$question_media_link       = '<a class="' . esc_attr( $question_media_type ) . '" href="' . esc_url( $question_media_url ) . '" target="_blank">' . esc_html( $question_media_title ) . '</a>';
								$question_media_link_class = '';
							}

							$question_media_add_button = esc_html__( 'Change file', 'sensei-lms' );
						}
					}
				}

				$random_order = get_post_meta( $question_id, '_random_order', true );
				if ( ! $random_order ) {
					$random_order = 'yes';
				}

				if ( ! $question_type ) {
					$question_type = 'multiple-choice'; }
			}

			$html .= '<tbody class="' . esc_attr( $question_class ) . '">';

			if ( 'quiz' == $context ) {
				$html .= '<tr>';
				if ( $question_type != 'category' ) {
					$question                = get_post( $question_id );
					$html                   .= '<td class="table-count question-number question-count-column"><span class="number">' . esc_html( $question_counter ) . '</span></td>';
					$html                   .= '<td>' . esc_html( $question->post_title ) . '</td>';
					$html                   .= '<td class="question-grade-column">' . esc_html( $question_grade ) . '</td>';
					$question_types_filtered = ucwords( str_replace( array( 'boolean', 'multiple-choice', 'gap-fill', 'single-line', 'multi-line', 'file-upload' ), array( __( 'True/False', 'sensei-lms' ), __( 'Multiple Choice', 'sensei-lms' ), __( 'Gap Fill', 'sensei-lms' ), __( 'Single Line', 'sensei-lms' ), __( 'Multi Line', 'sensei-lms' ), __( 'File Upload', 'sensei-lms' ) ), $question_type ) );
					$html                   .= '<td>' . esc_html( $question_types_filtered ) . '</td>';

					if ( current_user_can( get_post_type_object( 'question' )->cap->edit_post, $question_id ) ) {
						$html .= '<td><a title="' . esc_attr__( 'Edit Question', 'sensei-lms' ) . '" href="#question_' . esc_attr( $question_counter ) . '" class="question_table_edit">' . esc_html__( 'Edit', 'sensei-lms' ) . '</a> <a title="' . esc_attr__( 'Remove Question', 'sensei-lms' ) . '" href="#add-question-metadata" class="question_table_delete">' . esc_html__( 'Remove', 'sensei-lms' ) . '</a></td>';
					} else {
						$html .= '<td><a title="' . esc_attr__( 'Remove Question', 'sensei-lms' ) . '" href="#add-question-metadata" class="question_table_delete question_delete--without-edit">' . esc_html__( 'Remove', 'sensei-lms' ) . '</a><br />' . esc_html__( 'You are not the question owner, so you cannot edit it.', 'sensei-lms' ) . '</td>';
					}
				} else {

					$end_number = intval( $question_counter ) + intval( $multiple_data[1] ) - 1;
					if ( $question_counter == $end_number ) {
						$row_numbers = $question_counter;
					} else {
						$row_numbers = $question_counter . ' - ' . $end_number;
					}
					// translators: Placeholder is the question category name.
					$row_title = sprintf( esc_html__( 'Selected from \'%1$s\' ', 'sensei-lms' ), $multiple_data[0] );

					$html .= '<td class="table-count question-number question-count-column"><span class="number hidden">' . esc_html( $question_counter ) . '</span><span class="hidden total-number">' . esc_html( $multiple_data[1] ) . '</span><span class="row-numbers">' . esc_html( $row_numbers ) . '</span></td>';
					$html .= '<td>' . esc_html( $row_title ) . '</td>';
					$html .= '<td class="question-grade-column"></td>';
					$html .= '<td><input type="hidden" name="question_id" class="row_question_id" id="question_' . esc_attr( $question_counter ) . '_id" value="' . esc_attr( $question_id ) . '" /></td>';
					$html .= '<td><a title="' . esc_attr__( 'Remove Question(s)', 'sensei-lms' ) . '" href="#add-question-metadata" class="question_multiple_delete question_delete--without-edit" rel="' . esc_attr( $question_id ) . '">' . esc_html__( 'Remove', 'sensei-lms' ) . '</a></td>';

				}
					$html .= '</tr>';
			}

			if ( $question_type != 'category' ) {

				$edit_class = '';
				if ( 'quiz' == $context ) {
					$edit_class = 'hidden';
				}

				$question      = get_post( $question_id );
				$html         .= '<tr class="question-quick-edit ' . esc_attr( $edit_class ) . '">';
					$html     .= '<td colspan="5">';
						$html .= '<span class="hidden question_original_counter">' . esc_html( $question_counter ) . '</span>';
						$html .= '<div class="question_required_fields">';

							// Question title
							$html     .= '<div>';
								$html .= '<label for="question_' . esc_attr( $question_counter ) . '">' . esc_html__( 'Question:', 'sensei-lms' ) . '</label> ';
								$html .= '<input type="text" id="question_' . esc_attr( $question_counter ) . '" name="question" value="' . esc_attr( htmlspecialchars( $question->post_title ) ) . '" size="25" class="widefat" />';
							$html     .= '</div>';

							// Question description
							$html     .= '<div>';
								$html .= '<label for="question_' . esc_attr( $question_counter ) . '_desc">' . esc_html__( 'Description:', 'sensei-lms' ) . '</label> ';
							$html     .= '</div>';
								$html .= '<textarea id="question_' . esc_attr( $question_counter ) . '_desc" name="question_description" class="widefat" rows="4">' . esc_textarea( $question->post_content ) . '</textarea>';

							// Question grade
							$html     .= '<div>';
								$html .= '<label for="question_' . esc_attr( $question_counter ) . '_grade">' . esc_html__( 'Grade:', 'sensei-lms' ) . '</label> ';
								$html .= '<input type="number" id="question_' . esc_attr( $question_counter ) . '_grade" class="question_grade small-text" name="question_grade" min="0" value="' . esc_attr( $question_grade ) . '" />';
							$html     .= '</div>';

							// Random order
				if ( $question_type == 'multiple-choice' ) {
					$html     .= '<div>';
						$html .= '<label for="' . esc_attr( $question_counter ) . '_random_order"><input type="checkbox" name="random_order" class="random_order" id="' . esc_attr( $question_counter ) . '_random_order" value="yes" ' . checked( $random_order, 'yes', false ) . ' /> ' . esc_html__( 'Random Order', 'sensei-lms' ) . '</label>';
					$html     .= '</div>';
				}

							// Question media
							$html     .= '<div>';
								$html .= '<label for="question_' . esc_attr( $question_counter ) . '_media_button">' . esc_html__( 'Media:', 'sensei-lms' ) . '</label><br/>';
								$html .= '<button id="question_' . esc_attr( $question_counter ) . '_media_button" class="upload_media_file_button button-secondary" data-uploader-title="' . esc_attr__( 'Add file to question', 'sensei-lms' ) . '" data-uploader-button-text="' . esc_attr__( 'Add to question', 'sensei-lms' ) . '">' . esc_html( $question_media_add_button ) . '</button>';
								$html .= '<button id="question_' . esc_attr( $question_counter ) . '_media_button_delete" class="delete_media_file_button button-secondary ' . esc_attr( $question_media_delete_class ) . '">' . esc_html__( 'Delete file', 'sensei-lms' ) . '</button><br/>';
								$html .= '<span id="question_' . esc_attr( $question_counter ) . '_media_link" class="question_media_link ' . esc_attr( $question_media_link_class ) . '">' . wp_kses_post( $question_media_link ) . '</span>';
								$html .= '<br/><img id="question_' . esc_attr( $question_counter ) . '_media_preview" class="question_media_preview ' . esc_attr( $question_media_thumb_class ) . '" src="' . esc_url( $question_media_thumb ) . '" /><br/>';
								$html .= '<input type="hidden" id="question_' . esc_attr( $question_counter ) . '_media" class="question_media" name="question_media" value="' . esc_attr( $question_media ) . '" />';
							$html     .= '</div>';

							$html .= '</div>';

							$html .= $this->quiz_panel_question_field( $question_type, $question_id, $question_counter );

							$html .= '<input type="hidden" id="question_' . esc_attr( $question_counter ) . '_question_type" class="question_type" name="question_type" value="' . esc_attr( $question_type ) . '" />';
							$html .= '<input type="hidden" name="question_id" class="row_question_id" id="question_' . esc_attr( $question_counter ) . '_id" value="' . esc_attr( $question_id ) . '" />';

				if ( 'quiz' == $context ) {
					$html     .= '<div class="update-question">';
						$html .= '<a href="#question-edit-cancel" class="lesson_question_cancel" title="' . esc_attr__( 'Cancel', 'sensei-lms' ) . '">' . esc_html__( 'Cancel', 'sensei-lms' ) . '</a> ';
						$html .= '<a title="' . esc_attr__( 'Update Question', 'sensei-lms' ) . '" href="#add-question-metadata" class="question_table_save button button-highlighted">' . esc_html__( 'Update', 'sensei-lms' ) . '</a>';
					$html     .= '</div>';
				}

							$html .= '</td>';
							$html .= '</tr>';
			}

			$html .= '</tbody>';

		}

		return wp_kses(
			$html,
			array_merge(
				wp_kses_allowed_html( 'post' ),
				array(
					'button'   => array(
						'class'                     => array(),
						'data-uploader-button-text' => array(),
						'data-uploader-title'       => array(),
						'id'                        => array(),
					),
					'input'    => array(
						'checked' => array(),
						'class'   => array(),
						'id'      => array(),
						'min'     => array(),
						'name'    => array(),
						'rel'     => array(),
						'size'    => array(),
						'type'    => array(),
						'value'   => array(),
					),
					// Explicitly allow label tag for WP.com.
					'label'    => array(
						'class' => array(),
						'for'   => array(),
					),
					// Explicitly allow textarea tag for WP.com.
					'textarea' => array(
						'class' => array(),
						'id'    => array(),
						'name'  => array(),
						'rows'  => array(),
					),
				)
			)
		);
	}

	public function quiz_panel_add( $context = 'quiz' ) {

		$html = '<div id="add-new-question">';

			$question_types = Sensei()->question->question_types();

			$question_cats = get_terms( 'question-category', array( 'hide_empty' => false ) );

		if ( 'quiz' == $context ) {
			$html     .= '<h2 class="nav-tab-wrapper add-question-tabs">';
				$html .= '<a id="tab-new" class="nav-tab nav-tab-active">' . esc_html__( 'New Question', 'sensei-lms' ) . '</a>';
				$html .= '<a id="tab-existing" class="nav-tab">' . esc_html__( 'Existing Questions', 'sensei-lms' ) . '</a>';
			if ( ! empty( $question_cats ) && ! is_wp_error( $question_cats ) && ! Sensei()->teacher->is_admin_teacher() ) {
				$html .= '<a id="tab-multiple" class="nav-tab">' . esc_html__( 'Category Questions', 'sensei-lms' ) . '</a>';
			}
				$html .= '</h2>';
		}

			$html .= '<div class="tab-content" id="tab-new-content">';

		if ( 'quiz' == $context ) {
			// translators: Placeholders are an opening and closing <a> tag linking to the question bank.
			$html .= '<p><em>' . sprintf( __( 'Add a new question to this quiz - your question will also be added to the %1$squestion bank%2$s.', 'sensei-lms' ), '<a href="' . esc_url( admin_url( 'edit.php?post_type=question' ) ) . '">', '</a>' ) . '</em></p>';
		}

				$html     .= '<div class="question">';
					$html .= '<div class="question_required_fields">';

						// Question title
						$html .= '<p><label>' . esc_html__( 'Question:', 'sensei-lms' ) . '</label> ';
						$html .= '<input type="text" id="add_question" name="question" value="" size="25" class="widefat" /></p>';

						// Question description
						$html     .= '<p>';
							$html .= '<label for="question_desc">' . esc_html__( 'Description:', 'sensei-lms' ) . '</label> ';
						$html     .= '</p>';
						$html     .= '<textarea id="question_desc" name="question_description" class="widefat" rows="4"></textarea>';

						// Question type
						$html .= '<p><label>' . esc_html__( 'Question Type:', 'sensei-lms' ) . '</label> ';
						$html .= '<select id="add-question-type-options" name="question_type" class="chosen_select widefat question-type-select">' . "\n";
		foreach ( $question_types as $type => $label ) {
			$html .= '<option value="' . esc_attr( $type ) . '">' . esc_html( $label ) . '</option>' . "\n";
		}
						$html .= '</select></p>' . "\n";

						// Question category
		if ( 'quiz' == $context ) {
			if ( ! empty( $question_cats ) && ! is_wp_error( $question_cats ) ) {
				$html .= '<p><label>' . esc_html__( 'Question Category:', 'sensei-lms' ) . '</label> ';
				$html .= '<select id="add-question-category-options" name="question_category" class="chosen_select widefat question-category-select">' . "\n";
				$html .= '<option value="">' . esc_html__( 'None', 'sensei-lms' ) . '</option>' . "\n";
				foreach ( $question_cats as $cat ) {
					$html .= '<option value="' . esc_attr( $cat->term_id ) . '">' . esc_html( $cat->name ) . '</option>';
				}
				$html .= '</select></p>' . "\n";
			}
		}

						// Question grade
						$html .= '<p><label>' . esc_html__( 'Grade:', 'sensei-lms' ) . '</label> ';
						$html .= '<input type="number" id="add-question-grade" name="question_grade" class="small-text" min="0" value="1" /></p>' . "\n";

						// Random order
						$html     .= '<p class="add_question_random_order">';
							$html .= '<label for="add_random_order"><input type="checkbox" name="random_order" class="random_order" id="add_random_order" value="yes" checked="checked" /> ' . esc_html__( 'Random Order', 'sensei-lms' ) . '</label>';
						$html     .= '</p>';

						// Question media
						$html     .= '<p>';
							$html .= '<label for="question_add_new_media_button">' . esc_html__( 'Media:', 'sensei-lms' ) . '</label><br/>';
							$html .= '<button id="question_add_new_media_button" class="upload_media_file_button button-secondary" data-uploader-title="' . esc_attr__( 'Add file to question', 'sensei-lms' ) . '" data-uploader-button-text="' . esc_attr__( 'Add to question', 'sensei-lms' ) . '">' . esc_html__( 'Add file', 'sensei-lms' ) . '</button>';
							$html .= '<button id="question_add_new_media_button_delete" class="delete_media_file_button button-secondary hidden">' . esc_html__( 'Delete file', 'sensei-lms' ) . '</button><br/>';
							$html .= '<span id="question_add_new_media_link" class="question_media_link hidden"></span>';
							$html .= '<br/><img id="question_add_new_media_preview" class="question_media_preview hidden" src="" /><br/>';
							$html .= '<input type="hidden" id="question_add_new_media" class="question_media" name="question_media" value="" />';
						$html     .= '</p>';

					$html .= '</div>';
				$html     .= '</div>';

		foreach ( $question_types as $type => $label ) {
			$html .= $this->quiz_panel_question_field( $type );
		}

		if ( 'quiz' == $context ) {
			$html     .= '<div class="add-question">';
				$html .= '<a title="' . esc_attr__( 'Add Question', 'sensei-lms' ) . '" href="#add-question-metadata" class="add_question_save button button-primary button-highlighted">' . esc_html__( 'Add Question', 'sensei-lms' ) . '</a>';
			$html     .= '</div>';
		}

			$html .= '</div>';

		if ( 'quiz' == $context ) {

			$html .= '<div class="tab-content hidden" id="tab-existing-content">';

				// translators: Placeholders are an opening and closing <a> tag linking to the question bank.
				$html .= '<p><em>' . sprintf( __( 'Add an existing question to this quiz from the %1$squestion bank%2$s.', 'sensei-lms' ), '<a href="' . esc_url( admin_url( 'edit.php?post_type=question' ) ) . '">', '</a>' ) . '</em></p>';

				$html .= '<div id="existing-filters" class="alignleft actions">
								<select id="existing-status">
									<option value="all">' . esc_html__( 'All', 'sensei-lms' ) . '</option>
									<option value="unused">' . esc_html__( 'Unused', 'sensei-lms' ) . '</option>
									<option value="used">' . esc_html__( 'Used', 'sensei-lms' ) . '</option>
								</select>
								<select id="existing-type">
									<option value="">' . esc_html__( 'All Types', 'sensei-lms' ) . '</option>';
			foreach ( $question_types as $type => $label ) {
				$html .= '<option value="' . esc_attr( $type ) . '">' . esc_html( $label ) . '</option>';
			}
								$html .= '</select>
								<select id="existing-category">
									<option value="">' . esc_html__( 'All Categories', 'sensei-lms' ) . '</option>';
			foreach ( $question_cats as $cat ) {
				$html .= '<option value="' . esc_attr( $cat->slug ) . '">' . esc_html( $cat->name ) . '</option>';
			}
								$html .= '</select>
								<input type="text" id="existing-search" placeholder="' . esc_attr__( 'Search', 'sensei-lms' ) . '" />
								<a class="button" id="existing-filter-button">' . esc_html__( 'Filter', 'sensei-lms' ) . '</a>
							</div>';

								$html .= '<table id="existing-table" class="widefat">';

								$html .= '<thead>
										<tr>
											<th scope="col" class="column-cb check-column"><input type="checkbox" /></th>
											<th scope="col">' . esc_html__( 'Question', 'sensei-lms' ) . '</th>
											<th scope="col">' . esc_html__( 'Type', 'sensei-lms' ) . '</th>
											<th scope="col">' . esc_html__( 'Category', 'sensei-lms' ) . '</th>
										</tr>
									</thead>
									<tfoot>
										<tr>
											<th scope="col" class="check-column"><input type="checkbox" /></th>
											<th scope="col">' . esc_html__( 'Question', 'sensei-lms' ) . '</th>
											<th scope="col">' . esc_html__( 'Type', 'sensei-lms' ) . '</th>
											<th scope="col">' . esc_html__( 'Category', 'sensei-lms' ) . '</th>
										</tr>
									</tfoot>';
								$html .= '<tbody id="existing-questions">';

								$questions = $this->quiz_panel_get_existing_questions();

								$row = 1;
			foreach ( $questions['questions'] as $question ) {
				$html .= $this->quiz_panel_add_existing_question( $question->ID, $row );
				++$row;
			}

								$html .= '</tbody>';

								$html .= '</table>';

								$next_class = '';
			if ( $questions['count'] <= 10 ) {
				$next_class = 'hidden';
			}

								$html .= '<div id="existing-pagination">';
								$html .= '<input type="hidden" id="existing-page" value="1" />';
								$html .= '<a class="prev no-paging">&larr; ' . esc_html__( 'Previous', 'sensei-lms' ) . '</a> <a class="next ' . esc_attr( $next_class ) . '">' . esc_html__( 'Next', 'sensei-lms' ) . ' &rarr;</a>';
								$html .= '</div>';

								$html .= '<div class="existing-actions">';
								$html .= '<a title="' . esc_attr__( 'Add Selected Question(s)', 'sensei-lms' ) . '" class="add_existing_save button button-primary button-highlighted">' . esc_html__( 'Add Selected Question(s)', 'sensei-lms' ) . '</a></p>';
								$html .= '</div>';

								$html .= '</div>';

			if ( ! empty( $question_cats ) && ! is_wp_error( $question_cats ) ) {
				$html .= '<div class="tab-content hidden" id="tab-multiple-content">';

					// translators: Placeholders are an opening and closing <a> tag linking to the question categories page.
					$html .= '<p><em>' . sprintf( __( 'Add any number of questions from a specified category. Edit your question categories %1$shere%2$s.', 'sensei-lms' ), '<a href="' . esc_url( admin_url( 'edit-tags.php?taxonomy=question-category&post_type=question' ) ) . '">', '</a>' ) . '</em></p>';

					$html .= '<p><select id="add-multiple-question-category-options" name="multiple_category" class="chosen_select widefat question-category-select">' . "\n";
					$html .= '<option value="">' . esc_html__( 'Select a Question Category', 'sensei-lms' ) . '</option>' . "\n";
				foreach ( $question_cats as $cat ) {
					$html .= '<option value="' . esc_attr( $cat->term_id ) . '">' . esc_html( $cat->name ) . '</option>';
				}
					$html .= '</select></p>' . "\n";

					$html .= '<p>' . esc_html__( 'Number of questions:', 'sensei-lms' ) . ' <input type="number" min="1" value="1" max="1" id="add-multiple-question-count" class="small-text"/>';

					$html .= '<a title="' . esc_attr__( 'Add Question(s)', 'sensei-lms' ) . '" class="add_multiple_save button button-primary button-highlighted">' . esc_html__( 'Add Question(s)', 'sensei-lms' ) . '</a></p>';

				$html .= '</div>';
			}
		}

		$html .= '</div>';

		/**
		 * Filter the quiz panel add html.
		 *
		 * @since 1.9.7
		 * @hook sensei_quiz_panel_add
		 *
		 * @param {string} $html    HTML for adding a question.
		 * @param {string} $context 'quiz' if the question is being added on the lesson screen.
		 *                          Any other value if it's being added on the question screen.
		 * @return {string} HTML for adding a question.
		 */
		$html = apply_filters( 'sensei_quiz_panel_add', $html, $context );

		return $html;
	}

	public function quiz_panel_get_existing_questions( $question_status = 'all', $question_type = '', $question_category = '', $question_search = '', $page = 1 ) {

		$args = array(
			'post_type'        => 'question',
			'posts_per_page'   => 10,
			'post_status'      => 'publish',
			'suppress_filters' => 0,
			'perm'             => 'editable',
		);

		switch ( $question_status ) {
			case 'unused':
				$quiz_status = 'NOT EXISTS';
				break;
			case 'used':
				$quiz_status = 'EXISTS';
				break;
			default:
				$quiz_status = '';
				break;
		}

		if ( $quiz_status ) {
			switch ( $quiz_status ) {
				case 'EXISTS':
					$args['meta_query'][] = array(
						'key'     => '_quiz_id',
						'compare' => $quiz_status,
					);
					break;

				case 'NOT EXISTS':
					$args['meta_query'][] = array(
						'key'     => '_quiz_id',
						'value'   => 'bug #23268',
						'compare' => $quiz_status,
					);
					break;
			}
		}

		if ( $question_type ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'question-type',
				'field'    => 'slug',
				'terms'    => $question_type,
			);
		}

		if ( $question_category ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'question-category',
				'field'    => 'slug',
				'terms'    => $question_category,
			);
		}

		if ( $question_type && $question_category ) {
			$args['tax_query']['relation'] = 'AND';
		}

		if ( $question_search ) {
			$args['s'] = $question_search;
		}

		if ( $page ) {
			$args['paged'] = $page;
		}

		$qry = new WP_Query( $args );

		/**
		 * Filter existing questions query
		 *
		 * @since 1.8.0
		 * @hook sensei_existing_questions_query_results
		 *
		 * @param {object} $qry Query object containing an array of existing questions.
		 * @return {object} Query object containing an array of existing questions.
		 */
		$qry = apply_filters( 'sensei_existing_questions_query_results', $qry );

		$questions              = [];
		$questions['questions'] = $qry->posts;
		$questions['count']     = intval( $qry->found_posts );
		$questions['page']      = $page;

		return $questions;
	}

	public function quiz_panel_add_existing_question( $question_id = 0, $row = 1 ) {

		$html = '';

		if ( ! $question_id ) {

			return;

		}

		$existing_class = '';
		if ( $row % 2 ) {
			$existing_class = 'alternate';
		}

		$question_type = Sensei()->question->get_question_type( $question_id );

		$question_cat_list = strip_tags( get_the_term_list( $question_id, 'question-category', '', ', ', '' ) );

		$html .= '<tr class="' . esc_attr( $existing_class ) . '">
					<td class="cb"><input type="checkbox" value="' . esc_attr( $question_id ) . '" class="existing-item" /></td>
					<td>' . esc_html( get_the_title( $question_id ) ) . '</td>
					<td>' . esc_html( $question_type ) . '</td>
					<td>' . esc_html( $question_cat_list ) . '</td>
				  </tr>';

		return wp_kses(
			$html,
			array_merge(
				wp_kses_allowed_html( 'post' ),
				array(
					'input' => array(
						'class' => array(),
						'type'  => array(),
						'value' => array(),
					),
				)
			)
		);
	}

	public function quiz_panel_filter_existing_questions() {

		$return = array();

		// Add nonce security to the request
		$nonce = '';
		if ( isset( $_POST['filter_existing_questions_nonce'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$nonce = esc_html( $_POST['filter_existing_questions_nonce'] );
		}

		if ( ! wp_verify_nonce( $nonce, 'filter_existing_questions_nonce' ) ) {
			die( '' );
		}

		// Parse POST data
		$data          = $_POST['data'];
		$question_data = array();
		parse_str( $data, $question_data );

		if ( 0 < count( $question_data ) ) {

			$question_status = '';
			if ( isset( $question_data['question_status'] ) ) {
				$question_status = $question_data['question_status'];
			}

			$question_type = '';
			if ( isset( $question_data['question_type'] ) ) {
				$question_type = $question_data['question_type'];
			}

			$question_category = '';
			if ( isset( $question_data['question_category'] ) ) {
				$question_category = $question_data['question_category'];
			}

			$question_search = '';
			if ( isset( $question_data['question_search'] ) ) {
				$question_search = $question_data['question_search'];
			}

			$question_page = 1;
			if ( isset( $question_data['question_page'] ) ) {
				$question_page = intval( $question_data['question_page'] );
			}

			$questions = $this->quiz_panel_get_existing_questions( $question_status, $question_type, $question_category, $question_search, $question_page );

			$row  = 1;
			$html = '';
			foreach ( $questions['questions'] as $question ) {
				$html .= $this->quiz_panel_add_existing_question( $question->ID, $row );
				++$row;
			}

			if ( ! $html ) {
				$html = '<tr class="alternate">
								<td class="no-results" colspan="4"><em>' . esc_html__( 'There are no questions matching your search.', 'sensei-lms' ) . '</em></td>
							  </tr>';
			}

			$return['html']  = $html;
			$return['count'] = $questions['count'];
			$return['page']  = $questions['page'];

			wp_send_json( $return );
		}

		die( '' );
	}

	public function quiz_panel_question_field( $question_type = '', $question_id = 0, $question_counter = 0 ) {

		$html = '';

		if ( $question_type ) {

			$right_answer        = '';
			$wrong_answers       = array();
			$answer_order_string = '';
			$answer_order        = array();
			if ( $question_id ) {
				$right_answer        = get_post_meta( $question_id, '_question_right_answer', true );
				$wrong_answers       = get_post_meta( $question_id, '_question_wrong_answers', true );
				$answer_order_string = get_post_meta( $question_id, '_answer_order', true );
				$answer_order        = array_filter( explode( ',', $answer_order_string ) );
				$question_class      = '';
			} else {
				$question_id    = '';
				$question_class = 'answer-fields question_required_fields hidden';
			}

			switch ( $question_type ) {
				case 'multiple-choice':
					$html .= '<div class="question_default_fields multiple-choice-answers ' . esc_attr( str_replace( ' hidden', '', $question_class ) ) . '">';

					$answers       = [];
					$right_answers = (array) $right_answer;
					// Calculate total right answers available (defaults to 1)
					$total_right = 0;
					if ( $question_id ) {
						$total_right = get_post_meta( $question_id, '_right_answer_count', true );
					}
					if ( 0 == intval( $total_right ) ) {
						$total_right = 1;
					}
					for ( $i = 0; $i < $total_right; $i++ ) {
						if ( ! isset( $right_answers[ $i ] ) ) {
							$right_answers[ $i ] = '';
						}
						$right_answer_id = $this->get_answer_id( $right_answers[ $i ] );
						// Right Answer
						$right_answer = '<label class="answer" for="question_' . esc_attr( $question_counter ) . '_right_answer_' . esc_attr( $i ) . '"><span>' . esc_html__( 'Right:', 'sensei-lms' ) . '</span> <input rel="' . esc_attr( $right_answer_id ) . '" type="text" id="question_' . esc_attr( $question_counter ) . '_right_answer_' . esc_attr( $i ) . '" name="question_right_answers[]" value="' . esc_attr( $right_answers[ $i ] ) . '" size="25" class="question_answer widefat" /> <a class="remove_answer_option"></a></label>';

						if ( $question_id ) {
							$answers[ $right_answer_id ] = $right_answer;
						} else {
							$answers[] = $right_answer;
						}
					}

					// Calculate total wrong answers available (defaults to 4)
					$total_wrong = 0;
					if ( $question_id ) {
						$total_wrong = get_post_meta( $question_id, '_wrong_answer_count', true );
					}
					if ( 0 == intval( $total_wrong ) ) {
						$total_wrong = 1;
					}

					// Setup Wrong Answer HTML
					foreach ( $wrong_answers as $i => $answer ) {

						$answer_id     = $this->get_answer_id( $answer );
						$wrong_answer  = '<label class="answer" for="question_' . esc_attr( $question_counter ) . '_wrong_answer_' . esc_attr( $i ) . '"><span>' . esc_html__( 'Wrong:', 'sensei-lms' );
						$wrong_answer .= '</span> <input rel="' . esc_attr( $answer_id ) . '" type="text" id="question_' . esc_attr( $question_counter ) . '_wrong_answer_' . esc_attr( $i );
						$wrong_answer .= '" name="question_wrong_answers[]" value="' . esc_attr( $answer ) . '" size="25" class="question_answer widefat" /> <a class="remove_answer_option"></a></label>';
						if ( $question_id ) {

							$answers[ $answer_id ] = $wrong_answer;

						} else {

							$answers[] = $wrong_answer;

						}
					}

					$answers_sorted = $answers;
					if ( $question_id && count( $answer_order ) > 0 ) {
						$answers_sorted = Sensei()->question->get_answers_sorted( $answers, $answer_order );
					}

					foreach ( $answers_sorted as $id => $answer ) {
						$html .= $answer;
					}

						$html .= '<input type="hidden" class="answer_order" name="answer_order" value="' . esc_attr( $answer_order_string ) . '" />';
						$html .= '<span class="hidden right_answer_count">' . esc_html( $total_right ) . '</span>';
						$html .= '<span class="hidden wrong_answer_count">' . esc_html( $total_wrong ) . '</span>';

						$html     .= '<div class="add_answer_options">';
							$html .= '<a class="add_right_answer_option add_answer_option button" rel="' . esc_attr( $question_counter ) . '">' . esc_html__( 'Add right answer', 'sensei-lms' ) . '</a>';
							$html .= '<a class="add_wrong_answer_option add_answer_option button" rel="' . esc_attr( $question_counter ) . '">' . esc_html__( 'Add wrong answer', 'sensei-lms' ) . '</a>';
						$html     .= '</div>';

						$html .= $this->quiz_panel_question_feedback( $question_counter, $question_id, 'multiple-choice' );

					$html .= '</div>';
					break;
				case 'boolean':
					$html .= '<div class="question_boolean_fields ' . esc_attr( $question_class ) . '">';
					if ( $question_id ) {
						$field_name = 'question_' . esc_attr( $question_id ) . '_right_answer_boolean';
					} else {
						$field_name   = 'question_right_answer_boolean';
						$right_answer = 'true';
					}
						$html .= '<label for="question_' . esc_attr( $question_id ) . '_boolean_true"><input id="question_' . esc_attr( $question_id ) . '_boolean_true" type="radio" name="' . esc_attr( $field_name ) . '" value="true" ' . checked( $right_answer, 'true', false ) . ' /> ' . esc_html__( 'True', 'sensei-lms' ) . '</label>';
						$html .= '<label for="question_' . esc_attr( $question_id ) . '_boolean_false"><input id="question_' . esc_attr( $question_id ) . '_boolean_false" type="radio" name="' . esc_attr( $field_name ) . '" value="false" ' . checked( $right_answer, 'false', false ) . ' /> ' . esc_html__( 'False', 'sensei-lms' ) . '</label>';

					$html .= $this->quiz_panel_question_feedback( $question_counter, $question_id, 'boolean' );

					$html .= '</div>';
					break;
				case 'gap-fill':
					$gapfill_array = explode( '||', $right_answer );
					if ( isset( $gapfill_array[0] ) ) {
						$gapfill_pre = $gapfill_array[0];
					} else {
						$gapfill_pre = ''; }
					if ( isset( $gapfill_array[1] ) ) {
						$gapfill_gap = $gapfill_array[1];
					} else {
						$gapfill_gap = ''; }
					if ( isset( $gapfill_array[2] ) ) {
						$gapfill_post = $gapfill_array[2];
					} else {
						$gapfill_post = ''; }
					$html .= '<div class="question_gapfill_fields ' . esc_attr( $question_class ) . '">';
						// Fill in the Gaps
						$html .= '<label>' . esc_html__( 'Text before the gap:', 'sensei-lms' ) . '</label> ';
						$html .= '<input type="text" id="question_' . esc_attr( $question_counter ) . '_add_question_right_answer_gapfill_pre" name="add_question_right_answer_gapfill_pre" value="' . esc_attr( $gapfill_pre ) . '" size="25" class="widefat gapfill-field" />';
						$html .= '<label>' . esc_html__( 'Gap:', 'sensei-lms' ) . '</label> ';
						$html .= '<input type="text" id="question_' . esc_attr( $question_counter ) . '_add_question_right_answer_gapfill_gap" name="add_question_right_answer_gapfill_gap" value="' . esc_attr( $gapfill_gap ) . '" size="25" class="widefat gapfill-field" />';
						$html .= '<label>' . esc_html__( 'Text after the gap:', 'sensei-lms' ) . '</label> ';
						$html .= '<input type="text" id="question_' . esc_attr( $question_counter ) . '_add_question_right_answer_gapfill_post" name="add_question_right_answer_gapfill_post" value="' . esc_attr( $gapfill_post ) . '" size="25" class="widefat gapfill-field" />';
						$html .= '<label>' . esc_html__( 'Preview:', 'sensei-lms' ) . '</label> ';
						$html .= '<p class="gapfill-preview">' . esc_html( $gapfill_pre ) . '&nbsp;<u>' . esc_html( $gapfill_gap ) . '</u>&nbsp;' . esc_html( $gapfill_post ) . '</p>';
					$html     .= '</div>';
					break;
				case 'multi-line':
					$html .= '<div class="question_multiline_fields ' . esc_attr( $question_class ) . '">';
						// Guides for grading
					if ( $question_counter ) {
						$field_id = 'question_' . esc_attr( $question_counter ) . '_add_question_right_answer_multiline';
					} else {
						$field_id = 'add_question_right_answer_multiline';
					}
						$html .= '<label>' . esc_html__( 'Grading Notes:', 'sensei-lms' ) . '</label> ';
						$html .= '<textarea id="' . esc_attr( $field_id ) . '" name="add_question_right_answer_multiline" rows="4" cols="40" class="widefat">' . esc_textarea( $right_answer ) . '</textarea>';
						$html .= '<p class="question-field-helper-text">' . esc_html__( 'Displayed to the teacher when grading the question.', 'sensei-lms' ) . '</p>';
					$html     .= '</div>';
					break;
				case 'single-line':
					$html .= '<div class="question_singleline_fields ' . esc_attr( $question_class ) . '">';
						// Grading Notes
					if ( $question_counter ) {
						$field_id = 'question_' . esc_attr( $question_counter ) . '_add_question_right_answer_singleline';
					} else {
						$field_id = 'add_question_right_answer_singleline';
					}
						$html .= '<label>' . esc_html__( 'Grading Notes:', 'sensei-lms' ) . '</label> ';
						$html .= '<input type="text" id="' . esc_attr( $field_id ) . '" name="add_question_right_answer_singleline" value="' . esc_attr( $right_answer ) . '" size="25" class="widefat" />';
						$html .= '<p class="question-field-helper-text">' . esc_html__( 'Displayed to the teacher when grading the question.', 'sensei-lms' ) . '</p>';
					$html     .= '</div>';
					break;
				case 'file-upload':
					$html .= '<div class="question_fileupload_fields ' . esc_attr( $question_class ) . '">';
					if ( $question_counter ) {
						$right_field_id = 'question_' . esc_attr( $question_counter ) . '_add_question_right_answer_fileupload';
						$wrong_field_id = 'question_' . esc_attr( $question_counter ) . '_add_question_wrong_answer_fileupload';
					} else {
						$right_field_id = 'add_question_right_answer_fileupload';
						$wrong_field_id = 'add_question_wrong_answer_fileupload';
					}

						$wrong_answer = '';
					if ( isset( $wrong_answers[0] ) ) {
						$wrong_answer = $wrong_answers[0];
					}
						$html .= '<label>' . esc_html__( 'Upload notes:', 'sensei-lms' ) . '</label> ';
						$html .= '<textarea id="' . esc_attr( $wrong_field_id ) . '" name="add_question_wrong_answer_fileupload" rows="4" cols="40" class="widefat">' . esc_textarea( $wrong_answer ) . '</textarea>';
						$html .= '<p class="question-field-helper-text">' . esc_html__( 'Displayed to the learner to describe what to upload.', 'sensei-lms' ) . '</p>';

						// Guides for grading
						$html .= '<label>' . esc_html__( 'Grading Notes:', 'sensei-lms' ) . '</label> ';
						$html .= '<textarea id="' . esc_attr( $right_field_id ) . '" name="add_question_right_answer_fileupload" rows="4" cols="40" class="widefat">' . esc_textarea( $right_answer ) . '</textarea>';
						$html .= '<p class="question-field-helper-text">' . esc_html__( 'Displayed to the teacher when grading the question.', 'sensei-lms' ) . '</p>';
					$html     .= '</div>';
					break;
			}
		}

		return wp_kses(
			$html,
			array_merge(
				wp_kses_allowed_html( 'post' ),
				array(
					'input'    => array(
						'checked' => array(),
						'class'   => array(),
						'id'      => array(),
						'name'    => array(),
						'rel'     => array(),
						'size'    => array(),
						'type'    => array(),
						'value'   => array(),
					),
					// Explicitly allow label tag for WP.com.
					'label'    => array(
						'class' => array(),
						'for'   => array(),
					),
					// Explicitly allow textarea tag for WP.com.
					'textarea' => array(
						'class' => array(),
						'cols'  => array(),
						'id'    => array(),
						'name'  => array(),
						'rows'  => array(),
					),
				)
			)
		);
	}

	public function quiz_panel_question_feedback( $question_counter = 0, $question_id = 0, $question_type = '' ) {

		// default field name
		$field_name = 'answer_feedback';
		if ( 'boolean' == $question_type ) {

			$field_name = 'answer_feedback_boolean';

		} elseif ( 'multiple-choice' == $question_type ) {

			$field_name = 'answer_feedback_multiple_choice';

		}

		if ( $question_counter ) {
			$field_name = 'answer_' . esc_attr( $question_counter ) . '_feedback';
		}

		$feedback = '';
		if ( $question_id ) {
			$feedback = get_post_meta( $question_id, '_answer_feedback', true );
		}

		$html  = '<p title="' . esc_attr__( 'This feedback will be automatically displayed to the student once they have completed the quiz.', 'sensei-lms' ) . '">';
		$html .= '<label for="' . esc_attr( $field_name ) . '">' . esc_html__( 'Answer Feedback', 'sensei-lms' ) . ':</label>';
		$html .= '<textarea id="' . esc_attr( $field_name ) . '" name="' . esc_attr( $field_name ) . '" rows="4" cols="40" class="answer_feedback widefat">' . esc_textarea( $feedback ) . '</textarea>';
		$html .= '</p>';

		return wp_kses(
			$html,
			array_merge(
				wp_kses_allowed_html( 'post' ),
				array(
					// Explicitly allow label tag for WP.com.
					'label'    => array(
						'for' => array(),
					),
					// Explicitly allow textarea tag for WP.com.
					'textarea' => array(
						'class' => array(),
						'cols'  => array(),
						'id'    => array(),
						'name'  => array(),
						'rows'  => array(),
					),
				)
			)
		);
	}

	public function question_get_answer_id() {
		if ( ! isset( $_GET['answer_value'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification -- No modifications are made here.
			if ( isset( $_POST['data'] ) ) {
				_doing_it_wrong(
					'question_get_answer_id',
					'The question_get_answer_id AJAX call should be a GET request with parameter "answer_value".',
					'1.12.2'
				);
				$this->deprecated_question_get_answer_id();
			}
			wp_die();
		}
		$answer    = $_GET['answer_value'];
		$answer_id = $this->get_answer_id( $answer );
		echo esc_html( $answer_id );
		wp_die();
	}

	/**
	 * Deprecated version of question_get_answer_id() to use as a fallback.
	 */
	private function deprecated_question_get_answer_id() {
		// phpcs:ignore WordPress.Security.NonceVerification -- No modifications are made here.
		$data        = $_POST['data'];
		$answer_data = array();
		parse_str( $data, $answer_data );
		$answer    = $answer_data['answer_value'];
		$answer_id = $this->get_answer_id( $answer );
		echo esc_html( $answer_id );
		die();
	}

	/**
	 * Get answers ID (text md5).
	 *
	 * @param string $answer Answer text.
	 *
	 * @return string Answer ID.
	 */
	public function get_answer_id( $answer = '' ) {
		$answer_id = '';

		if ( $answer ) {
			$answer_id = md5( $answer );
		}

		return $answer_id;
	}

	/**
	 * lesson_quiz_meta_box_content function.
	 *
	 * @access public
	 * @return void
	 */
	public function lesson_quiz_meta_box_content() {
		global $post;

		$quiz_id = 0;

		if ( 0 < $post->ID ) {
			$quiz_id = $this->lesson_quizzes( $post->ID, 'any' );
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in the method.
		echo $this->quiz_panel( $quiz_id );

	}

	/**
	 * Quiz settings metabox
	 *
	 * @return void
	 */
	public function lesson_quiz_settings_meta_box_content() {
		global $post;

		$html = $this->legacy_quiz_message;

		// Get quiz panel
		$quiz_id   = 0;
		$lesson_id = $post->ID;

		if ( 0 < $lesson_id ) {
			$quiz_id = $this->lesson_quizzes( $lesson_id, 'any' );
		}

		if ( $quiz_id ) {
			$html .= $this->quiz_settings_panel( $lesson_id, $quiz_id );
		} else {
			$html .= '<p><em>' . esc_html__( 'There is no quiz for this lesson yet - please add one in the \'Quiz Questions\' box.', 'sensei-lms' ) . '</em></p>';
		}

		echo wp_kses(
			$html,
			array_merge(
				wp_kses_allowed_html( 'post' ),
				array(
					'input'    => array(
						'checked'     => array(),
						'class'       => array(),
						'disabled'    => array(),
						'id'          => array(),
						'max'         => array(),
						'min'         => array(),
						'name'        => array(),
						'placeholder' => array(),
						'type'        => array(),
						'value'       => array(),
					),
					// Explicitly allow label tag for WP.com.
					'label'    => array(
						'for' => array(),
					),
					'option'   => array(
						'selected' => array(),
						'value'    => array(),
					),
					'select'   => array(
						'disabled' => array(),
						'id'       => array(),
						'multiple' => array(),
						'name'     => array(),
					),
					'textarea' => array(
						'cols'        => array(),
						'disabled'    => array(),
						'id'          => array(),
						'name'        => array(),
						'placeholder' => array(),
						'rows'        => array(),
					),
				)
			)
		);
	}

	public function quiz_settings_panel( $lesson_id = 0, $quiz_id = 0 ) {

		$html = '';

		if ( ! $lesson_id && ! $quiz_id ) {
			return $html;
		}

		$settings = $this->get_quiz_settings( $quiz_id );

		$html = Sensei()->admin->render_settings( $settings, $quiz_id, 'quiz-settings' );

		return $html;

	}

	public function get_quiz_settings( $quiz_id = 0 ) {

		$disable_passmark = '';
		$pass_required    = get_post_meta( $quiz_id, '_pass_required', true );
		if ( ! $pass_required ) {
			$disable_passmark = 'hidden';
		}

		// Setup Questions Query
		$questions = array();
		if ( 0 < $quiz_id ) {
			$questions = $this->lesson_quiz_questions( $quiz_id );
		}

		// Count questions
		$question_count = 0;
		foreach ( $questions as $question ) {
			if ( $question->post_type == 'multiple_question' ) {
				$question_number = get_post_meta( $question->ID, 'number', true );
				$question_count += $question_number;
			} else {
				++$question_count;
			}
		}

		$settings = array(
			array(
				'id'          => 'pass_required',
				'label'       => esc_html__( 'Pass required to complete lesson', 'sensei-lms' ),
				'description' => esc_html__( 'The passmark must be achieved before the lesson is complete.', 'sensei-lms' ),
				'type'        => 'checkbox',
				'default'     => '',
				'checked'     => 'on',
			),
			array(
				'id'          => 'quiz_passmark',
				'label'       => esc_html__( 'Quiz passmark percentage', 'sensei-lms' ),
				'description' => '',
				'type'        => 'number',
				'default'     => 0,
				'placeholder' => 0,
				'min'         => 0,
				'max'         => 100,
				'class'       => $disable_passmark,
			),
			array(
				'id'          => 'show_questions',
				'label'       => esc_html__( 'Number of questions to show', 'sensei-lms' ),
				'description' => esc_html__( 'Show a random selection of questions from this quiz each time a student views it.', 'sensei-lms' ),
				'type'        => 'number',
				'default'     => '',
				'placeholder' => esc_html__( 'All', 'sensei-lms' ),
				'min'         => 1,
				'max'         => $question_count,
			),
			array(
				'id'          => 'random_question_order',
				'label'       => esc_html__( 'Randomise question order', 'sensei-lms' ),
				'description' => '',
				'type'        => 'checkbox',
				'default'     => 'no',
				'checked'     => 'yes',
			),
			array(
				'id'          => 'quiz_grade_type',
				'label'       => esc_html__( 'Grade quiz automatically', 'sensei-lms' ),
				'description' => esc_html__( 'Grades quiz and displays answer explanation immediately after completion. Only applicable if quiz is limited to Multiple Choice, True/False and Gap Fill questions. Questions that have a grade of zero are skipped during autograding.', 'sensei-lms' ),
				'type'        => 'checkbox',
				'default'     => 'auto',
				'checked'     => 'auto',
			),
			array(
				'id'          => 'enable_quiz_reset',
				'label'       => esc_html__( 'Allow user to retake the quiz', 'sensei-lms' ),
				'description' => esc_html__( 'Enables the quiz reset button.', 'sensei-lms' ),
				'type'        => 'checkbox',
				'default'     => '',
				'checked'     => 'on',
			),
		);

		/**
		 * Filter the quiz setting fields.
		 *
		 * @hook sensei_quiz_settings
		 *
		 * @param {array} $settings Nested array containing the quiz setting fields.
		 * @return {array} Nested array containing the quiz setting fields.
		 */
		return apply_filters( 'sensei_quiz_settings', $settings );
	}

	/**
	 * enqueue_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
		global $post_type;

		/**
		 * Only load lesson scripts for particular post types.
		 *
		 * @hook sensei_scripts_allowed_post_types
		 *
		 * @param {array} $allowed_post_types Allowed post types.
		 * @return {array} Allowed post types.
		 */
		$allowed_post_types = apply_filters( 'sensei_scripts_allowed_post_types', array( 'lesson', 'question' ) );

		/**
		 * Only load lesson scripts for particular post type pages.
		 *
		 * @hook sensei_scripts_allowed_post_type_pages
		 *
		 * @param {array} $allowed_post_type_pages Allowed post type pages.
		 * @return {array} Allowed post type pages.
		 */
		$allowed_post_type_pages = apply_filters( 'sensei_scripts_allowed_post_type_pages', array( 'post-new.php', 'post.php' ) );

		if ( 'edit.php' === $hook && 'lesson' === $post_type ) {
			$this->enqueue_lesson_edit_scripts();
		}

		if ( ! isset( $post_type )
			|| ! isset( $hook )
			|| ! in_array( $post_type, $allowed_post_types )
			|| ! in_array( $hook, $allowed_post_type_pages )
		) {
			return;
		}

		// Load the lessons script.
		Sensei()->assets->enqueue( 'sensei-lesson-metadata', 'js/admin/lesson-edit.js', [ 'jquery', 'sensei-core-select2' ], true );

		if ( ! Sensei()->quiz->is_block_based_editor_enabled() ) {
			$this->enqueue_scripts_meta_box_quiz_editor();
		}
	}

	/**
	 * Enqueue legacy meta box quiz editor assets.
	 */
	private function enqueue_scripts_meta_box_quiz_editor() {
		wp_enqueue_media();

		// Load the lessons script.
		Sensei()->assets->enqueue( 'sensei-meta-box-quiz-editor', 'js/admin/meta-box-quiz-editor.js', [ 'jquery', 'sensei-core-select2', 'jquery-ui-sortable', 'sensei-chosen-ajax' ], true );

		// Localise script.
		$translation_strings = array(
			'right_colon'             => esc_html__( 'Right:', 'sensei-lms' ),
			'wrong_colon'             => esc_html__( 'Wrong:', 'sensei-lms' ),
			'add_file'                => esc_html__( 'Add file', 'sensei-lms' ),
			'change_file'             => esc_html__( 'Change file', 'sensei-lms' ),
			'confirm_remove'          => esc_html__( 'Are you sure you want to remove this question?', 'sensei-lms' ),
			'confirm_remove_multiple' => esc_html__( 'Are you sure you want to remove these questions?', 'sensei-lms' ),
			'too_many_for_cat'        => esc_html__( 'You have selected more questions than this category contains - please reduce the number of questions that you are adding.', 'sensei-lms' ),
		);

		$ajax_vars = array(
			'lesson_update_question_nonce'              => wp_create_nonce( 'lesson_update_question_nonce' ),
			'lesson_update_grade_type_nonce'            => wp_create_nonce( 'lesson_update_grade_type_nonce' ),
			'lesson_update_question_order_nonce'        => wp_create_nonce( 'lesson_update_question_order_nonce' ),
			'lesson_update_question_order_random_nonce' => wp_create_nonce( 'lesson_update_question_order_random_nonce' ),
			'lesson_add_multiple_questions_nonce'       => wp_create_nonce( 'lesson_add_multiple_questions_nonce' ),
			'lesson_remove_multiple_questions_nonce'    => wp_create_nonce( 'lesson_remove_multiple_questions_nonce' ),
			'lesson_add_existing_questions_nonce'       => wp_create_nonce( 'lesson_add_existing_questions_nonce' ),
			'filter_existing_questions_nonce'           => wp_create_nonce( 'filter_existing_questions_nonce' ),
		);

		$data = array_merge( $translation_strings, $ajax_vars );
		wp_localize_script( 'sensei-meta-box-quiz-editor', 'woo_localized_data', $data );

		// Chosen RTL
		if ( is_rtl() ) {
			Sensei()->assets->enqueue( 'sensei-chosen-rtl', '../vendor/chosen/chosen-rtl.js', [ 'jquery' ], true );
		}
	}

	/**
	 * Load scripts for the Lessons admin page.
	 *
	 * @access private
	 * @since  3.0.0
	 * @return void
	 */
	private function enqueue_lesson_edit_scripts() {
		// Load the quick edit screen script.
		Sensei()->assets->enqueue( 'sensei-lesson-quick-edit', 'js/admin/lesson-quick-edit.js', [ 'jquery' ], true );
		Sensei()->assets->enqueue( 'sensei-lesson-bulk-edit', 'js/admin/lesson-bulk-edit.js', [ 'jquery' ], true );

	}

	/**
	 * Load in CSS styles where necessary.
	 *
	 * @access public
	 * @since  1.4.0
	 * @return void
	 */
	public function enqueue_styles( $hook ) {
		global  $post_type;

		/**
		 * Only load lesson styles for particular post types.
		 *
		 * @hook sensei_scripts_allowed_post_types
		 *
		 * @param {array} $allowed_post_types Allowed post types.
		 * @return {array} Allowed post types.
		 */
		$allowed_post_types = apply_filters( 'sensei_scripts_allowed_post_types', array( 'lesson', 'course', 'question', 'sensei_message' ) );

		/**
		 * Only load lesson styles for particular post type pages.
		 *
		 * @hook sensei_scripts_allowed_post_type_pages
		 *
		 * @param {array} $allowed_post_type_pages Allowed post type pages.
		 * @return {array} Allowed post type pages.
		 */
		$allowed_post_type_pages = apply_filters( 'sensei_scripts_allowed_post_type_pages', array( 'edit.php', 'post-new.php', 'post.php', 'edit-tags.php' ) );

		/**
		 * Only load lesson styles for particular pages.
		 *
		 * @hook sensei_scripts_allowed_pages
		 *
		 * @param {array} $allowed_pages Allowed pages.
		 * @return {array} Allowed pages.
		 */
		$allowed_pages = apply_filters( 'sensei_scripts_allowed_pages', array( 'sensei_grading', 'sensei_analysis', 'sensei_learners', 'sensei_updates', 'sensei-settings' ) );

		// Test for Write Panel Pages
		if ( ( ( isset( $post_type ) && in_array( $post_type, $allowed_post_types ) ) && ( isset( $hook ) && in_array( $hook, $allowed_post_type_pages ) ) ) || ( isset( $_GET['page'] ) && in_array( $_GET['page'], $allowed_pages ) ) ) {
			Sensei()->assets->enqueue( 'sensei-settings-api', 'css/settings.css' );

			if ( ! Sensei()->quiz->is_block_based_editor_enabled() && in_array( $post_type, [ 'question', 'lesson' ], true ) ) {
				Sensei()->assets->enqueue( 'sensei-meta-box-quiz-editor-css', 'css/meta-box-quiz-editor.css', [ 'sensei-settings-api' ] );
			}
		}

	}

	/**
	 * Add column headings to the "lesson" post list screen,
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
		$new_columns['title']               = _x( 'Lesson Title', 'column name', 'sensei-lms' );
		$new_columns['lesson-course']       = _x( 'Course', 'column name', 'sensei-lms' );
		$new_columns['lesson-prerequisite'] = _x( 'Pre-requisite Lesson', 'column name', 'sensei-lms' );
		if ( isset( $defaults['date'] ) ) {
			$new_columns['date'] = $defaults['date'];
		}

		// Make sure other sensei columns stay directly behind the new columns.
		$other_sensei_columns = [
			'taxonomy-module',
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
			case 'lesson-course':
				$lesson_course_id = get_post_meta( $id, '_lesson_course', true );
				if ( 0 < absint( $lesson_course_id ) ) {
					// translators: Placeholder is the course title.
					echo '<a href="' . esc_url( get_edit_post_link( absint( $lesson_course_id ) ) ) . '" title="' . esc_attr( sprintf( __( 'Edit %s', 'sensei-lms' ), get_the_title( absint( $lesson_course_id ) ) ) ) . '">' . esc_html( get_the_title( absint( $lesson_course_id ) ) ) . '</a>';
				}
				break;
			case 'lesson-prerequisite':
				$lesson_prerequisite_id = get_post_meta( $id, '_lesson_prerequisite', true );
				if ( 0 < absint( $lesson_prerequisite_id ) ) {
					$lesson_prerequisite_post = get_post( $lesson_prerequisite_id );
					// translators: Placeholder is the title of the prerequisite lesson.
					echo '<a href="' . esc_url( get_edit_post_link( absint( $lesson_prerequisite_id ) ) ) . '" title="' . esc_attr( sprintf( __( 'Edit %s', 'sensei-lms' ), get_the_title( absint( $lesson_prerequisite_id ) ) ) ) . '">' . esc_html( get_the_title( absint( $lesson_prerequisite_id ) ) ) . '</a>';
					_post_states( $lesson_prerequisite_post );
				}
				break;
			default:
				break;
		}
	}

	/**
	 * Add a course from the lesson page.
	 *
	 * @access public
	 * @deprecated 2.2.0
	 * @return void
	 */
	public function lesson_add_course() {
		_deprecated_function( __METHOD__, '2.2.0' );

		// Add nonce security to the request
		if ( isset( $_POST['lesson_add_course_nonce'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$nonce = esc_html( $_POST['lesson_add_course_nonce'] );
		}
		if ( ! wp_verify_nonce( $nonce, 'lesson_add_course_nonce' )
			|| ! current_user_can( 'edit_lessons' ) ) {
			die( '' );
		}
		// Parse POST data
		$data        = $_POST['data'];
		$course_data = array();
		parse_str( $data, $course_data );
		// Save the Course
		$updated                      = false;
		$current_user                 = wp_get_current_user();
		$question_data                = [];
		$question_data['post_author'] = $current_user->ID;
		$updated                      = $this->lesson_save_course( $course_data );

		// Compute properties and log an event.
		$event_properties = [];
		foreach ( [ 'course_prerequisite', 'course_category', 'course_woocommerce_product' ] as $field ) {
			$value_to_log = -1;
			if ( isset( $course_data[ $field ] ) ) {
				$val = intval( $course_data[ $field ] );
				if ( $val ) {
					$value_to_log = $val;
				}
			}

			// Get property name.
			$property_name = $field . '_id';
			if ( 'course_woocommerce_product' === $field ) {
				$property_name = 'product_id';
			}

			$event_properties[ $property_name ] = $value_to_log;
		}
		sensei_log_event( 'lesson_course_add', $event_properties );

		echo esc_html( $updated );
		die(); // WordPress may print out a spurious zero without this can be particularly bad if using JSON
	}

	/**
	 * Whether user can edit quiz.
	 *
	 * @param int $quiz_id
	 *
	 * @return boolean
	 */
	private function user_can_edit_quiz( $quiz_id ) {
		$lesson_id = get_post_meta( $quiz_id, '_quiz_lesson', true );
		return current_user_can( get_post_type_object( 'lesson' )->cap->edit_post, $lesson_id );
	}

	/**
	 * Updates a question.
	 *
	 * @access public
	 * @return void
	 */
	public function lesson_update_question() {
		// Add nonce security to the request.
		if ( isset( $_POST['lesson_update_question_nonce'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$nonce = esc_html( $_POST['lesson_update_question_nonce'] );
		}
		if ( ! wp_verify_nonce( $nonce, 'lesson_update_question_nonce' )
			|| ! current_user_can( 'edit_questions' ) ) {

			die( '' );

		}

		// Parse POST data
		// WP slashes all incoming data regardless of Magic Quotes setting (see wp_magic_quotes()), which means that
		// even the $_POST['data'] encoded with encodeURIComponent has it's apostrophes slashed.
		// So first restore the original unslashed apostrophes by removing those slashes.
		$data = wp_unslash( $_POST['data'] );
		// Then parse the string to an array (note that parse_str automatically urldecodes all the variables).
		$question_data = array();
		parse_str( $data, $question_data );
		// Finally re-slash all elements to ensure consistancy for lesson_save_question().
		$question_data = wp_slash( $question_data );

		if ( ! $this->user_can_edit_quiz( $question_data['quiz_id'] ) ) {
			die( '' );
		}

		// Save the question
		$return = false;
		// Question Save and Delete logic
		if ( isset( $question_data['action'] ) && ( $question_data['action'] == 'delete' ) ) {
			// Delete the Question
			$return = $this->lesson_remove_question( $question_data );
		} else {
			if ( ! empty( $question_data['question_id'] ) && ! current_user_can( get_post_type_object( 'question' )->cap->edit_post, $question_data['question_id'] ) ) {
				die( '' );
			}

			// Save the Question
			if ( isset( $question_data['quiz_id'] ) && ( 0 < absint( $question_data['quiz_id'] ) ) ) {
				$current_user                 = wp_get_current_user();
				$question_data['post_author'] = $current_user->ID;
				$question_id                  = $this->lesson_save_question( $question_data );
				$question_type                = Sensei()->question->get_question_type( $question_id );

				$question_count = intval( $question_data['question_count'] );
				++$question_count;

				$return = $this->quiz_panel_question( $question_type, $question_count, $question_id );
			}
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in methods that generate `$return`.
		echo $return;

		die();
	}

	public function lesson_add_multiple_questions() {

		$return = '';

		// Add nonce security to the request
		$nonce = '';
		if ( isset( $_POST['lesson_add_multiple_questions_nonce'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$nonce = esc_html( $_POST['lesson_add_multiple_questions_nonce'] );
		}

		if ( ! wp_verify_nonce( $nonce, 'lesson_add_multiple_questions_nonce' )
			|| ! current_user_can( 'edit_lessons' ) ) {
			die( esc_html( $return ) );
		}

		// Parse POST data
		$data          = $_POST['data'];
		$question_data = array();
		parse_str( $data, $question_data );

		if ( is_array( $question_data ) ) {
			if ( isset( $question_data['quiz_id'] ) && ( 0 < absint( $question_data['quiz_id'] ) ) ) {

				$quiz_id           = intval( $question_data['quiz_id'] );
				$question_number   = intval( $question_data['question_number'] );
				$question_category = intval( $question_data['question_category'] );

				$question_counter = intval( $question_data['question_count'] );
				++$question_counter;

				$cat = get_term( $question_category, 'question-category' );

				$post_data = array(
					'post_content' => '',
					'post_status'  => 'publish',
					// translators: Placeholders are the question number and the question category name.
					'post_title'   => sprintf( esc_html__( '%1$s Question(s) from %2$s', 'sensei-lms' ), $question_number, $cat->name ),
					'post_type'    => 'multiple_question',
				);

				$multiple_id = wp_insert_post( $post_data );

				if ( $multiple_id && ! is_wp_error( $multiple_id ) ) {
					add_post_meta( $multiple_id, 'category', $question_category );
					add_post_meta( $multiple_id, 'number', $question_number );
					add_post_meta( $multiple_id, '_quiz_id', $quiz_id, false );
					add_post_meta( $multiple_id, '_quiz_question_order' . $quiz_id, $quiz_id . '000' . $question_counter );
					$lesson_id = get_post_meta( $quiz_id, '_quiz_lesson', true );
					update_post_meta( $lesson_id, '_quiz_has_questions', '1' );
					$return = $this->quiz_panel_question( 'category', $question_counter, $multiple_id, 'quiz', array( $cat->name, $question_number ) );
				}
			}
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in methods that generate `$return`.
		echo $return;

		die();
	}

	public function lesson_remove_multiple_questions() {

		// Add nonce security to the request
		$nonce = '';
		if ( isset( $_POST['lesson_remove_multiple_questions_nonce'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$nonce = esc_html( $_POST['lesson_remove_multiple_questions_nonce'] );
		}

		if ( ! wp_verify_nonce( $nonce, 'lesson_remove_multiple_questions_nonce' )
		|| ! current_user_can( 'edit_lessons' ) || ! isset( $_POST['data'] ) ) {
			die( '' );
		}

		// Parse POST data
		$question_data = array();
		parse_str( $_POST['data'], $question_data );

		$question_id_to_remove      = $question_data['question_id'];
		$quiz_id_to_be_removed_from = $question_data['quiz_id'];

		if ( 'multiple_question' !== get_post_type( $question_id_to_remove ) ) {
			die( '' );
		}

		$found_quiz = false;
		$quizzes    = get_post_meta( $question_id_to_remove, '_quiz_id', false );
		foreach ( $quizzes as $index => $quiz_id ) {
			$same_quiz = (int) $quiz_id === (int) $quiz_id_to_be_removed_from;
			if ( $same_quiz || empty( $quiz_id ) ) {
				delete_post_meta( $question_id_to_remove, '_quiz_id', $quiz_id );

				$found_quiz = $found_quiz || $same_quiz;
				unset( $quizzes[ $index ] );
			}
		}

		if ( empty( $quizzes ) ) {
			wp_delete_post( $question_id_to_remove, true );
		}

		die( $found_quiz ? 'Deleted' : '' );
	}

	public function get_question_category_limit() {
		// Set default
		$return = 1;

		if ( isset( $_GET['cat'] ) && '' != $_GET['cat'] ) {
			$cat = get_term( $_GET['cat'], 'question-category' );
			if ( isset( $cat->count ) ) {
				$return = $cat->count;
			}
		} else {
			// Fallback to old behaviour if $_POST['data'] exists.
			// phpcs:ignore WordPress.Security.NonceVerification -- No modifications are made here.
			if ( isset( $_POST['data'] ) ) {
				_doing_it_wrong(
					'get_question_category_limit',
					'The get_question_category_limit AJAX call should be a GET request with parameter "cat".',
					'1.12.2'
				);
				$this->deprecated_get_question_category_limit();

				wp_die();
			}
		}

		echo esc_html( $return );

		die( '' );
	}

	/**
	 * Deprecated version of get_question_category_limit() to use as a fallback.
	 */
	public function deprecated_get_question_category_limit() {

		// Set default
		$return = 1;

		// Parse POST data
		// phpcs:ignore WordPress.Security.NonceVerification -- No modifications are made here.
		$data     = $_POST['data'];
		$cat_data = array();
		parse_str( $data, $cat_data );

		if ( isset( $cat_data['cat'] ) && '' != $cat_data['cat'] ) {
			$cat = get_term( $cat_data['cat'], 'question-category' );
			if ( isset( $cat->count ) ) {
				$return = $cat->count;
			}
		}

		echo esc_html( $return );

		die( '' );
	}

	public function lesson_add_existing_questions() {

		// Add nonce security to the request
		$nonce = '';
		if ( isset( $_POST['lesson_add_existing_questions_nonce'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$nonce = esc_html( $_POST['lesson_add_existing_questions_nonce'] );
		}

		if ( ! wp_verify_nonce( $nonce, 'lesson_add_existing_questions_nonce' )
		|| ! current_user_can( 'edit_lessons' ) ) {
			die( '' );
		}

		// Parse POST data
		$data          = $_POST['data'];
		$question_data = array();
		parse_str( $data, $question_data );

		if ( ! $this->user_can_edit_quiz( $question_data['quiz_id'] ) ) {
			die( '' );
		}

		$return = '';

		if ( is_array( $question_data ) ) {

			if ( isset( $question_data['questions'] ) && '' != $question_data['questions'] ) {

				$questions = explode( ',', trim( $question_data['questions'], ',' ) );
				$questions = array_filter(
					$questions,
					function( $question_id ) {
						return current_user_can( get_post_type_object( 'question' )->cap->edit_post, $question_id );
					}
				);

				$quiz_id        = $question_data['quiz_id'];
				$question_count = intval( $question_data['question_count'] );

				foreach ( $questions as $question_id ) {

					++$question_count;

					$quizzes = get_post_meta( $question_id, '_quiz_id', false );
					if ( ! in_array( $quiz_id, $quizzes ) ) {
						add_post_meta( $question_id, '_quiz_id', $quiz_id, false );
						$lesson_id = get_post_meta( $quiz_id, '_quiz_lesson', true );
						update_post_meta( $lesson_id, '_quiz_has_questions', '1' );
					}

					add_post_meta( $question_id, '_quiz_question_order' . $quiz_id, $quiz_id . '000' . $question_count );
					$question_type = Sensei()->question->get_question_type( $question_id );

					$return .= $this->quiz_panel_question( $question_type, $question_count, $question_id );
				}
			}
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in methods that generate `$return`.
		echo $return;

		die( '' );
	}

	public function lesson_update_grade_type() {
		// Add nonce security to the request
		if ( isset( $_POST['lesson_update_grade_type_nonce'] ) ) {

			// phpcs:ignore WordPress.Security.NonceVerification
			$nonce = esc_html( $_POST['lesson_update_grade_type_nonce'] );

		}

		if ( ! wp_verify_nonce( $nonce, 'lesson_update_grade_type_nonce' )
		|| ! current_user_can( 'edit_lessons' ) ) {

			die( '' );

		}

		// Parse POST data
		$data      = $_POST['data'];
		$quiz_data = array();
		parse_str( $data, $quiz_data );
		update_post_meta( $quiz_data['quiz_id'], '_quiz_grade_type', $quiz_data['quiz_grade_type'] );
		die();
	}

	public function lesson_update_question_order() {
		// Add nonce security to the request
		if ( isset( $_POST['lesson_update_question_order_nonce'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$nonce = esc_html( $_POST['lesson_update_question_order_nonce'] );
		}

		if ( ! wp_verify_nonce( $nonce, 'lesson_update_question_order_nonce' )
			|| ! current_user_can( 'edit_lessons' ) ) {
			die( '' );
		}

		// Parse POST data
		$data      = $_POST['data'];
		$quiz_data = array();
		parse_str( $data, $quiz_data );

		if ( ! $this->user_can_edit_quiz( $quiz_data['quiz_id'] ) ) {
			die( '' );
		}

		if ( strlen( $quiz_data['question_order'] ) > 0 ) {
			$questions = explode( ',', $quiz_data['question_order'] );
			$o         = 1;
			foreach ( $questions as $question_id ) {
				update_post_meta( $question_id, '_quiz_question_order' . $quiz_data['quiz_id'], $quiz_data['quiz_id'] . '000' . $o );
				++$o;
			}
			update_post_meta( $quiz_data['quiz_id'], '_question_order', $questions );
		}
		die();
	}

	public function lesson_update_question_order_random() {
		// Add nonce security to the request
		if ( isset( $_POST['lesson_update_question_order_random_nonce'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$nonce = esc_html( $_POST['lesson_update_question_order_random_nonce'] );
		}
		if ( ! wp_verify_nonce( $nonce, 'lesson_update_question_order_random_nonce' )
			|| ! current_user_can( 'edit_lessons' ) ) {

			die( '' );

		}
		// Parse POST data
		$data      = $_POST['data'];
		$quiz_data = array();
		parse_str( $data, $quiz_data );
		update_post_meta( $quiz_data['quiz_id'], '_random_question_order', $quiz_data['random_question_order'] );
		die();
	}

	/**
	 * Save lesson course.
	 *
	 * @access private
	 * @deprecated 2.2.0
	 * @param array $data (default: array()).
	 * @return integer|boolean $course_id or false
	 */
	private function lesson_save_course( $data = array() ) {
		_deprecated_function( __METHOD__, '2.2.0' );

		$return = false;
		// Setup the course data.
		$course_id      = 0;
		$course_content = '';
		$course_title   = '';
		if ( isset( $data['course_id'] ) && ( 0 < absint( $data['course_id'] ) ) ) {
			$course_id = absint( $data['course_id'] );
		}
		if ( isset( $data['course_title'] ) && ( '' !== $data['course_title'] ) ) {
			$course_title = $data['course_title'];
		}
		$post_title  = $course_title;
		$post_status = 'publish';
		$post_type   = 'course';
		if ( isset( $data['course_content'] ) && ( '' !== $data['course_content'] ) ) {
			$course_content = $data['course_content'];
		}
		$post_content = $course_content;
		// Course Query Arguments.
		$post_type_args = array(
			'post_content' => $post_content,
			'post_status'  => $post_status,
			'post_title'   => $post_title,
			'post_type'    => $post_type,
		);
		// Only save if there is a valid title.
		if ( '' !== $post_title ) {
			// Check for prerequisite courses.
			$course_prerequisite_id = absint( $data['course_prerequisite'] );
			$course_category_id     = absint( $data['course_category'] );
			// Create the new course.
			$course_id = wp_insert_post( $post_type_args );
			add_post_meta( $course_id, '_course_prerequisite', $course_prerequisite_id );

			/**
			 * Fires after a course was created from the lesson page meta box.
			 *
			 * @since 2.0.0
			 * @hook sensei_lesson_course_created
			 *
			 * @param {int}   $course_id Course ID.
			 * @param {array} $data      Data that was sent when creating the course.
			 */
			do_action( 'sensei_lesson_course_created', $course_id, $data );

			if ( 0 < $course_category_id ) {
				wp_set_object_terms( $course_id, $course_category_id, 'course-category' );
			}
		}
		// Check that the insert or update saved by testing the post id.
		if ( 0 < $course_id ) {
			$return = $course_id;
		}
		return $return;
	}

	/**
	 * lesson_save_question function.
	 *
	 * @access private
	 * @param array $data (default: array())
	 * @return integer|boolean $question_id or false
	 */
	public function lesson_save_question( $data = array(), $context = 'quiz' ) {
		$return = false;
		// Save the Questions
		// Setup the Question data
		$question_id            = 0;
		$question_text          = '';
		$question_right_answer  = '';
		$question_wrong_answers = $question_right_answers = array();
		$question_type          = 'multiple-choice';
		$question_category      = '';

		// Handle Question Type
		if ( isset( $data['question_type'] ) && ( '' != $data['question_type'] ) ) {
			$question_type = $data['question_type'];
		}

		if ( isset( $data['question_category'] ) && ( '' != $data['question_category'] ) ) {
			$question_category = $data['question_category'];
		}

		if ( isset( $data['question_id'] ) && ( 0 < absint( $data['question_id'] ) ) ) {
			$question_id = absint( $data['question_id'] );
		}
		if ( isset( $data['question'] ) && ( '' != $data['question'] ) ) {
			$question_text = $data['question'];
		}
		$post_title = $question_text;
		// Handle Default Fields (multiple choice)
		if ( 'multiple-choice' == $question_type && isset( $data['question_right_answers'] ) && ( '' != $data['question_right_answers'] ) ) {
			$question_right_answers = $data['question_right_answers'];
		} elseif ( 'multiple-choice' == $question_type && isset( $data['question_right_answer'] ) && ( '' != $data['question_right_answer'] ) ) {
			$question_right_answer = $data['question_right_answer'];
		}
		if ( 'multiple-choice' == $question_type && isset( $data['question_wrong_answers'] ) && ( '' != $data['question_wrong_answers'] ) ) {
			$question_wrong_answers = $data['question_wrong_answers'];
		}
		// Handle Boolean Fields - Edit
		if ( 'boolean' == $question_type && isset( $data[ 'question_' . $question_id . '_right_answer_boolean' ] ) && ( '' != $data[ 'question_' . $question_id . '_right_answer_boolean' ] ) ) {
			$question_right_answer = $data[ 'question_' . $question_id . '_right_answer_boolean' ];
		}
		// Handle Boolean Fields - Add
		if ( 'boolean' == $question_type && isset( $data['question_right_answer_boolean'] ) && ( '' != $data['question_right_answer_boolean'] ) ) {
			$question_right_answer = $data['question_right_answer_boolean'];
		}
		// Handle Gap Fill Fields
		if ( 'gap-fill' == $question_type && isset( $data['add_question_right_answer_gapfill_gap'] ) && '' != $data['add_question_right_answer_gapfill_gap'] ) {
			$question_right_answer = $data['add_question_right_answer_gapfill_pre'] . '||' . $data['add_question_right_answer_gapfill_gap'] . '||' . $data['add_question_right_answer_gapfill_post'];
		}
		// Handle Multi Line Fields
		if ( 'multi-line' == $question_type && isset( $data['add_question_right_answer_multiline'] ) && ( '' != $data['add_question_right_answer_multiline'] ) ) {
			$question_right_answer = $data['add_question_right_answer_multiline'];
		}
		// Handle Single Line Fields
		if ( 'single-line' == $question_type && isset( $data['add_question_right_answer_singleline'] ) && ( '' != $data['add_question_right_answer_singleline'] ) ) {
			$question_right_answer = $data['add_question_right_answer_singleline'];
		}
		// Handle File Upload Fields
		if ( 'file-upload' == $question_type && isset( $data['add_question_right_answer_fileupload'] ) && ( '' != $data['add_question_right_answer_fileupload'] ) ) {
			$question_right_answer = $data['add_question_right_answer_fileupload'];
		}
		if ( 'file-upload' == $question_type && isset( $data['add_question_wrong_answer_fileupload'] ) && ( '' != $data['add_question_wrong_answer_fileupload'] ) ) {
			$question_wrong_answers = array( $data['add_question_wrong_answer_fileupload'] );
		}

		// Handle Question Grade
		if ( isset( $data['question_grade'] ) && ( '' != $data['question_grade'] ) ) {
			$question_grade = $data['question_grade'];
		}

		// Handle Answer Feedback
		$answer_feedback = '';
		if ( isset( $data['answer_feedback_boolean'] ) && ! empty( $data['answer_feedback_boolean'] ) ) {

			$answer_feedback = $data['answer_feedback_boolean'];

		} elseif ( isset( $data['answer_feedback_multiple_choice'] ) && ! empty( $data['answer_feedback_multiple_choice'] ) ) {

			$answer_feedback = $data['answer_feedback_multiple_choice'];

		} elseif ( isset( $data['answer_feedback'] ) ) {

			$answer_feedback = $data['answer_feedback'];

		}

		$post_title  = $question_text;
		$post_status = 'publish';
		$post_type   = 'question';
		// Handle the extended question text
		if ( isset( $data['question_description'] ) && ( '' != $data['question_description'] ) ) {
			$post_content = $data['question_description'];
		} else {
			$post_content = '';
		}
		// Question Query Arguments
		$post_type_args = array(
			'post_content' => $post_content,
			'post_status'  => $post_status,
			'post_title'   => $post_title,
			'post_type'    => $post_type,
		);

		// Remove empty values and reindex the array
		if ( is_array( $question_right_answers ) && 0 < count( $question_right_answers ) ) {
			$question_right_answers_array = array_values( array_filter( $question_right_answers, 'strlen' ) );
			$question_right_answers       = array();

			foreach ( $question_right_answers_array as $answer ) {
				if ( ! in_array( $answer, $question_right_answers ) ) {
					$question_right_answers[] = $answer;
				}
			}
			if ( 0 < count( $question_right_answers ) ) {
				$question_right_answer = $question_right_answers;
			}
		}

		$right_answer_count = is_array( $question_right_answer ) ? count( $question_right_answer ) : 1;

		// Remove empty values and reindex the array
		if ( is_array( $question_wrong_answers ) ) {
			$question_wrong_answers_array = array_values( array_filter( $question_wrong_answers, 'strlen' ) );
			$question_wrong_answers       = array();
		}

		foreach ( $question_wrong_answers_array as $answer ) {
			if ( ! in_array( $answer, $question_wrong_answers ) ) {
				$question_wrong_answers[] = $answer;
			}
		}

		$wrong_answer_count = count( $question_wrong_answers );

		// Only save if there is a valid title
		if ( $post_title != '' ) {

			// Get Quiz ID for the question
			$quiz_id = $data['quiz_id'];

			// Get question media
			$question_media = $data['question_media'];

			// Get answer order
			$answer_order = '';
			if ( isset( $data['answer_order'] ) ) {
				$answer_order = $data['answer_order'];
			}

			// Get random order selection
			$random_order = 'no';
			if ( isset( $data['random_order'] ) ) {
				$random_order = $data['random_order'];
			}

			// Insert or Update the question
			if ( 0 < $question_id ) {

				$post_type_args['ID'] = $question_id;
				$question_id          = wp_update_post( $post_type_args );

				// Update poast meta
				if ( 'quiz' == $context ) {
					$quizzes = get_post_meta( $question_id, '_quiz_id', false );
					if ( ! in_array( $quiz_id, $quizzes ) ) {
						add_post_meta( $question_id, '_quiz_id', $quiz_id, false );
					}
				}

				update_post_meta( $question_id, '_question_grade', $question_grade );
				update_post_meta( $question_id, '_question_right_answer', $question_right_answer );
				update_post_meta( $question_id, '_right_answer_count', $right_answer_count );
				update_post_meta( $question_id, '_question_wrong_answers', $question_wrong_answers );
				update_post_meta( $question_id, '_wrong_answer_count', $wrong_answer_count );
				update_post_meta( $question_id, '_question_media', $question_media );
				update_post_meta( $question_id, '_answer_order', $answer_order );
				update_post_meta( $question_id, '_random_order', $random_order );
				update_post_meta( $question_id, '_answer_feedback', $answer_feedback );

				if ( 'quiz' != $context ) {
					wp_set_post_terms( $question_id, array( $question_type ), 'question-type', false );
				}
			} else {
				$question_id    = wp_insert_post( $post_type_args );
				$question_count = intval( $data['question_count'] );
				++$question_count;

				// Set post meta
				if ( 'quiz' == $context ) {
					add_post_meta( $question_id, '_quiz_id', $quiz_id, false );
					$lesson_id = get_post_meta( $quiz_id, '_quiz_lesson', true );
					update_post_meta( $lesson_id, '_quiz_has_questions', '1' );
				}

				if ( isset( $question_grade ) ) {
					add_post_meta( $question_id, '_question_grade', $question_grade );
				}
				add_post_meta( $question_id, '_question_right_answer', $question_right_answer );
				add_post_meta( $question_id, '_right_answer_count', $right_answer_count );
				add_post_meta( $question_id, '_question_wrong_answers', $question_wrong_answers );
				add_post_meta( $question_id, '_wrong_answer_count', $wrong_answer_count );
				add_post_meta( $question_id, '_quiz_question_order' . $quiz_id, $quiz_id . '000' . $question_count );
				add_post_meta( $question_id, '_question_media', $question_media );
				add_post_meta( $question_id, '_answer_order', $answer_order );
				add_post_meta( $question_id, '_random_order', $random_order );
				// Don't store empty value, no point
				if ( ! empty( $answer_feedback ) ) {
					add_post_meta( $question_id, '_answer_feedback', $answer_feedback );
				}

				// Set the post terms for question-type
				wp_set_post_terms( $question_id, array( $question_type ), 'question-type' );

				if ( $question_category ) {
					wp_set_post_terms( $question_id, array( $question_category ), 'question-category' );
				}
			}
		}
		// Check that the insert or update saved by testing the post id
		if ( 0 < $question_id ) {
			$return = $question_id;
		}
		return $return;
	}


	/**
	 * Remove question from lesson
	 *
	 * @access private
	 * @param array $data (default: array())
	 * @return boolean
	 */
	private function lesson_remove_question( $data = array() ) {

		// Get which question to delete
		$question_id = 0;
		if ( isset( $data['question_id'] ) && ( 0 < absint( $data['question_id'] ) ) ) {
			$question_id = absint( $data['question_id'] );
		}

		if ( empty( $question_id ) ) {
			return false;
		}

		// remove the question from the lesson quiz
		$quizzes = get_post_meta( $question_id, '_quiz_id', false );

		foreach ( $quizzes as $quiz_id ) {
			if ( $quiz_id == $data['quiz_id'] ) {
				delete_post_meta( $question_id, '_quiz_id', $quiz_id );
			}
		}

		return true;

	}


	/**
	 * lesson_complexities function.
	 *
	 * @access public
	 * @return array $lesson_complexities
	 */
	public function lesson_complexities() {

		// V2 - make filter for this array
		$lesson_complexities = array(
			'easy' => esc_html__( 'Easy', 'sensei-lms' ),
			'std'  => esc_html__( 'Standard', 'sensei-lms' ),
			'hard' => esc_html__( 'Hard', 'sensei-lms' ),
		);

		return $lesson_complexities;

	}


	/**
	 * lesson_count function.
	 *
	 * @access public
	 * @param string $post_status (default: 'publish')
	 * @return int
	 */
	public function lesson_count( $post_status = 'publish', $course_id = false ) {

		$post_args = array(
			'post_type'        => 'lesson',
			'posts_per_page'   => -1,
			'post_status'      => $post_status,
			'suppress_filters' => 0,
			'fields'           => 'ids',
		);
		if ( $course_id ) {
			$post_args['meta_query'][] = array(
				'key'   => '_lesson_course',
				'value' => $course_id,
			);
		} else {
			// Simple check for connection to a Course
			$post_args['meta_query'][] = array(
				'key'     => '_lesson_course',
				'value'   => 0,
				'compare' => '>=',
			);
		}

		/**
		 * Filter the query arguments for getting the lesson count.
		 *
		 * @hook sensei_lesson_count
		 *
		 * @param {array} $post_args Post arguments.
		 * @return {array} Post arguments.
		 */
		$lessons_query = new WP_Query( apply_filters( 'sensei_lesson_count', $post_args ) );

		return count( $lessons_query->posts );
	}


	/**
	 * Get the quizzes of a lesson
	 *
	 * @access public
	 *
	 * @param int    $lesson_id   The lesson id (default: 0).
	 * @param string $post_status The post status (default: 'any').
	 * @param string $fields      The fields to return (default: 'ids').
	 *
	 * @return int|null $quiz_id
	 */
	public function lesson_quizzes( $lesson_id = 0, $post_status = 'any', $fields = 'ids' ) {

		$posts_array = array();

		$post_args   = array(
			'post_type'        => 'quiz',
			'posts_per_page'   => 1,
			'orderby'          => 'title',
			'order'            => 'DESC',
			'post_parent'      => $lesson_id,
			'post_status'      => $post_status,
			'suppress_filters' => 0,
			'fields'           => $fields,
		);
		$posts_array = get_posts( $post_args );
		$quiz_id     = array_shift( $posts_array );

		return $quiz_id;
	}


	/**
	 * Fetches all the questions for a quiz depending on certain conditions.
	 *
	 * Determine which questions should be shown depending on:
	 * - admin/teacher selected questions to be shown
	 * - questions shown to a user previously (saved as asked questions)
	 * - limit number of questions lesson setting
	 *
	 * @since 1.0
	 * @param int    $quiz_id     The quiz id (default: 0).
	 * @param string $post_status Post status (default: 'publish').
	 * @param string $orderby     Order by (default: 'meta_value_num title').
	 * @param string $order       Order (default: 'ASC').
	 *
	 * @return array $questions { $question type WP_Post }
	 */
	public function lesson_quiz_questions( $quiz_id = 0, $post_status = 'any', $orderby = 'meta_value_num title', $order = 'ASC' ) {

		$quiz_id        = (string) $quiz_id;
		$quiz_lesson_id = Sensei()->quiz->get_lesson_id( $quiz_id );

		// Setup the user id.
		if ( is_admin() ) {
			$user_id = isset( $_GET['user'] ) ? $_GET['user'] : '';
		} else {
			$user_id = get_current_user_id();
		}

		// Get the users current status on the lesson.
		$user_lesson_status = Sensei_Utils::user_lesson_status( $quiz_lesson_id, $user_id );

		// If viewing quiz on the frontend then show questions in random order if set.
		if ( ! is_admin() ) {
			$random_order = get_post_meta( $quiz_id, '_random_question_order', true );
			if ( $random_order && 'yes' === $random_order ) {
				$orderby = 'rand';
			}
		}

		$questions = Sensei()->quiz->get_questions( $quiz_id, $post_status, $orderby, $order );

		// Set the questions array that will be manipulated within this function.
		$questions_array = $questions;

		// If viewing quiz on frontend or in grading then only single questions must be shown.
		$selected_questions = false;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Input used for comparisons.
		if ( ! is_admin() || ( is_admin() && isset( $_GET['page'] ) && 'sensei_grading' === $_GET['page'] && isset( $_GET['user'] ) && isset( $_GET['quiz_id'] ) ) ) {

			// Fetch the questions that the user was asked in their quiz if they have already completed it.
			$questions_asked_string = ! empty( $user_lesson_status->comment_ID ) ? get_comment_meta( $user_lesson_status->comment_ID, 'questions_asked', true ) : false;
			if ( ! empty( $questions_asked_string ) ) {

				$selected_questions = explode( ',', $questions_asked_string );

				// Fetch each question in the order in which they were asked.
				$questions = [];
				foreach ( $selected_questions as $question_id ) {
					if ( ! $question_id ) {
						continue;
					}
					$question = get_post( $question_id );
					if ( ! isset( $question ) || ! isset( $question->ID ) ) {
						continue;
					}
					$questions[] = $question;
				}
			} else {
				// Otherwise, make sure that we convert all multiple questions into single questions.
				$existing_questions = [];

				// Set array of questions that already exist so we can prevent duplicates from appearing.
				foreach ( $questions_array as $question ) {
					if ( 'question' !== $question->post_type ) {
						continue;
					}
					$existing_questions[] = $question->ID;
				}

				// Include only single questions in the return array.
				$questions_loop  = $questions_array;
				$questions_array = [];
				foreach ( $questions_loop as $k => $question ) {

					// If this is a single question then include it.
					if ( 'question' === $question->post_type ) {
						$questions_array[] = $question;
					} else {

						// If this is a multiple question then get the specified amount of questions from the specified category.
						$question_cat    = (int) get_post_meta( $question->ID, 'category', true );
						$question_number = (int) get_post_meta( $question->ID, 'number', true );
						$quiz_author     = get_post( $quiz_id )->post_author;

						$qargs = [
							'post_type'        => 'question',
							'posts_per_page'   => $question_number,
							'orderby'          => $orderby,
							'tax_query'        => [
								[
									'taxonomy' => 'question-category',
									'field'    => 'term_id',
									'terms'    => $question_cat,
								],
							],
							'post_status'      => $post_status,
							'suppress_filters' => 0,
							'post__not_in'     => $existing_questions,
						];

						/**
						 * When a question category is expanded to its questions, if the quiz owner is not an admin,
						 * only the questions owned by the teacher are included. This behaviour can be disabled with
						 * this filter.
						 *
						 * @since 3.10.0
						 * @hook sensei_filter_category_questions_by_author
						 *
						 * @param {array}  $quiz_id The quiz id.
						 *
						 * @return {array} Whether questions should be filtered by author.
						 */
						$should_filter = apply_filters( 'sensei_filter_category_questions_by_author', true, $quiz_id );

						if ( $should_filter && ! user_can( $quiz_author, 'manage_options' ) ) {
							$qargs['author'] = $quiz_author;
						}

						$cat_questions = get_posts( $qargs );

						// Merge results into return array.
						$questions_array = array_merge( $questions_array, $cat_questions );

						// Add selected questions to existing questions array to prevent duplicates from being added.
						foreach ( $questions_array as $cat_question ) {
							if ( in_array( $cat_question->ID, $existing_questions ) ) {
								continue;
							}
							$existing_questions[] = $cat_question->ID;
						}
					}
				}

				// Set return data.
				$questions = $questions_array;
			}
		}

		// If user has not already taken the quiz and a limited number of questions are to be shown, then show a random selection of the specified amount of questions.
		if ( ! $selected_questions ) {

			// Only limit questions like this on the frontend.
			if ( ! is_admin() ) {

				// Get number of questions to show.
				$show_questions = (int) get_post_meta( $quiz_id, '_show_questions', true );

				if ( $show_questions ) {
					// Get random set of array keys from selected questions array.
					$selected_questions = array_rand(
						$questions_array,
						$show_questions > count( $questions_array ) ? count( $questions_array ) : $show_questions
					);

					// Loop through all questions and pick the the ones to be shown based on the random key selection.
					$questions = [];
					foreach ( $questions_array as $k => $question ) {

						// Random keys will always be an array, unless only one question is to be shown.
						if ( is_array( $selected_questions ) ) {
							if ( in_array( $k, $selected_questions ) ) {
								$questions[] = $question;
							}
						} elseif ( 1 == $show_questions ) {
							if ( $selected_questions == $k ) {
								$questions[] = $question;
							}
						}
					}
				}
			}
		}

		/**
		 * Filter the questions returned by Sensei_Lesson::lessons_quiz_questions.
		 *
		 * @since 1.8.0
		 * @hook sensei_lesson_quiz_questions
		 *
		 * @param {array}  $questions Questions.
		 * @param {string} $quiz_id   Quiz ID.
		 * @return {array} Questions.
		 */
		return apply_filters( 'sensei_lesson_quiz_questions', $questions, $quiz_id );
	}

	/**
	 * Set the default quiz order
	 *
	 * @param integer $quiz_id ID of quiz
	 */
	public function set_default_question_order( $quiz_id = 0 ) {

		if ( $quiz_id ) {

			$question_order = get_post_meta( $quiz_id, '_question_order', true );

			if ( ! $question_order ) {

				$args      = array(
					'post_type'        => 'question',
					'posts_per_page'   => -1,
					'orderby'          => 'ID',
					'order'            => 'ASC',
					'meta_query'       => array(
						array(
							'key'   => '_quiz_id',
							'value' => $quiz_id,
						),
					),
					'post_status'      => 'any',
					'suppress_filters' => 0,
				);
				$questions = get_posts( $args );

				$o = 1;
				foreach ( $questions as $question ) {
					add_post_meta( $question->ID, '_quiz_question_order' . $quiz_id, $quiz_id . '000' . $o, true );
					$o++;
				}
			}
		}

	}

	/**
	 * lesson_image function.
	 *
	 * Handles output of the lesson image
	 *
	 * @access public
	 * @param int    $lesson_id (default: 0)
	 * @param string $width (default: '100')
	 * @param string $height (default: '100')
	 * @return string
	 */
	public function lesson_image( $lesson_id = 0, $width = '100', $height = '100', $widget = false ) {

		$html = '';

		// Get Width and Height settings
		if ( ( $width == '100' ) && ( $height == '100' ) ) {

			if ( is_singular( 'lesson' ) ) {

				if ( ! $widget && ! Sensei()->settings->settings['lesson_single_image_enable'] ) {

					return '';

				}

				$image_thumb_size = 'lesson_single_image';
				$dimensions       = Sensei()->get_image_size( $image_thumb_size );
				$width            = $dimensions['width'];
				$height           = $dimensions['height'];
			} else {

				if ( ! $widget && ! Sensei()->settings->settings['course_lesson_image_enable'] ) {

					return '';
				}

				$image_thumb_size = 'lesson_archive_image';
				$dimensions       = Sensei()->get_image_size( $image_thumb_size );
				$width            = $dimensions['width'];
				$height           = $dimensions['height'];
			}
		}

		$img_element = '';

		if ( has_post_thumbnail( $lesson_id ) ) {

			// Get Featured Image
			$img_element = get_the_post_thumbnail( $lesson_id, array( $width, $height ), array( 'class' => 'woo-image thumbnail alignleft' ) );

		} else {

			// Display Image Placeholder if none
			if ( Sensei()->settings->settings['placeholder_images_enable'] ) {
				/**
				 * Filter the lesson placeholder image.
				 *
				 * @hook sensei_lesson_placeholder_image_url
				 *
				 * @param {string} $html HTML for the lesson placeholder image.
				 * @return {string} HTML for the lesson placeholder image.
				 */
				$img_element = apply_filters( 'sensei_lesson_placeholder_image_url', '<img src="http://placehold.it/' . esc_url( $width ) . 'x' . esc_url( $height ) . '" class="woo-image thumbnail alignleft" />' );

			}
		}

		if ( is_singular( 'lesson' ) ) {

			$html .= $img_element;

		} else {

			$html .= '<a href="' . esc_url( get_permalink( $lesson_id ) ) . '" title="' . esc_attr( get_post_field( 'post_title', $lesson_id ) ) . '">' . wp_kses_post( $img_element ) . '</a>';

		}

		return $html;
	}

	/**
	 * Ooutpu the lesson image
	 *
	 * @since 1.9.0
	 * @param integer $lesson_id
	 */
	public static function the_lesson_image( $lesson_id = 0 ) {

		echo wp_kses_post( Sensei()->lesson->lesson_image( $lesson_id ) );

	}

	/**
	 * Returns the the lesson excerpt.
	 *
	 * @param WP_Post $lesson
	 * @param bool    $add_p_tags should the excerpt be wrapped by calling wpautop()
	 * @return string
	 */
	public static function lesson_excerpt( $lesson = null, $add_p_tags = true ) {
		$html = '';
		if ( is_a( $lesson, 'WP_Post' ) && 'lesson' == $lesson->post_type ) {

			$excerpt = $lesson->post_excerpt;

			// if $add_p_tags true wrap with <p> else return the excerpt as is
			$html = $add_p_tags ? wp_kses_post( wpautop( $excerpt ) ) : esc_html( $excerpt );

		}

		/**
		 * Filter the lesson excerpt.
		 *
		 * @hook sensei_lesson_excerpt
		 *
		 * @param {string} $html HTML for the lesson excerpt.
		 * @return {string} HTML for the lesson excerpt.
		 */
		return apply_filters( 'sensei_lesson_excerpt', $html );
	}

	/**
	 * Returns the course for a given lesson
	 *
	 * @since 1.7.4
	 * @access public
	 *
	 * @param int $lesson_id
	 * @return string|bool $course_id Course ID or false if nothing is found.
	 */
	public function get_course_id( $lesson_id ) {

		if ( ! isset( $lesson_id ) || empty( $lesson_id )
		|| 'lesson' != get_post_type( $lesson_id ) ) {
			return false;
		}

		$lesson_course_id = get_post_meta( $lesson_id, '_lesson_course', true );

		// make sure the course id is valid
		if ( empty( $lesson_course_id )
			|| is_array( $lesson_course_id )
			|| intval( $lesson_course_id ) < 1
			|| 'course' != get_post_type( $lesson_course_id ) ) {

			return false;

		}

		return $lesson_course_id;

	}

	/**
	 * Add the admin all lessons screen edit options.
	 *
	 * The fields in this function work for both quick and bulk edit. The ID attributes is used
	 * by bulk edit javascript in the front end to retrieve the new values set byt the user. Then
	 * name attribute is will be used by the quick edit and submitted via standard POST. This
	 * will use this classes save_post_meta function to save the new field data.
	 *
	 * @hooked quick_edit_custom_box
	 * @hooked bulk_edit_custom_box
	 *
	 * @since 1.8.0
	 *
	 * @param string $column_name
	 * @param string $post_type
	 * @return void
	 */
	public function all_lessons_edit_fields( $column_name, $post_type ) {

		// only show these options ont he lesson post type edit screen
		if ( 'lesson' != $post_type || 'lesson-course' != $column_name
			|| ! current_user_can( 'edit_lessons' ) ) {
			return;
		}

		?>
		<fieldset class="sensei-edit-field-set inline-edit-lesson">
			<div class="sensei-inline-edit-col column-<?php echo esc_attr( $column_name ); ?>">
					<?php
					echo '<h4>' . esc_html__( 'Lesson Information', 'sensei-lms' ) . '</h4>';
					// create a nonce field to be  used as a security measure when saving the data
					wp_nonce_field( 'bulk-edit-lessons', '_edit_lessons_nonce' );
					wp_nonce_field( 'sensei-save-post-meta', 'woo_' . $this->token . '_nonce' );

					// unchanged option - we need this in because
					// the default option in bulk edit should not be empty. If it is
					// the user will erase data they didn't want to touch.
					$no_change_text = '-- ' . esc_html__( 'No Change', 'sensei-lms' ) . ' --';

					//
					// course selection
					//
					$courses        = Sensei_Course::get_all_courses();
					$course_options = array();
					if ( count( $courses ) > 0 ) {
						foreach ( $courses as $course ) {
							$course_options[ $course->ID ] = get_the_title( $course->ID );
						}
					}
					// pre-append the no change option
					$course_options['-1'] = $no_change_text;
					$course_attributes    = array(
						'name'  => 'lesson_course',
						'id'    => 'sensei-edit-lesson-course',
						'class' => ' ',
					);
					$course_field         = Sensei_Utils::generate_drop_down( '-1', $course_options, $course_attributes );

					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in called method.
					echo $this->generate_all_lessons_edit_field( esc_html__( 'Lesson Course', 'sensei-lms' ), $course_field );

					//
					// lesson complexity selection
					//
					$lesson_complexities = $this->lesson_complexities();
					// pre-append the no change option
					$lesson_complexities['-1']      = $no_change_text;
					$complexity_dropdown_attributes = array(
						'name'  => 'lesson_complexity',
						'id'    => 'sensei-edit-lesson-complexity',
						'class' => ' ',
					);
					$complexity_filed               = Sensei_Utils::generate_drop_down( '-1', $lesson_complexities, $complexity_dropdown_attributes );

					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in called method.
					echo $this->generate_all_lessons_edit_field( esc_html__( 'Lesson Complexity', 'sensei-lms' ), $complexity_filed );

					?>

					<h4><?php esc_html_e( 'Quiz Settings', 'sensei-lms' ); ?> </h4>

					<?php

					//
					// Lesson require pass to complete
					//
					$pass_required_options = array(
						'-1' => $no_change_text,
						'0'  => esc_html__( 'No', 'sensei-lms' ),
						'1'  => esc_html__( 'Yes', 'sensei-lms' ),
					);

					$pass_required_select_attributes = array(
						'name'  => 'pass_required',
						'id'    => 'sensei-edit-lesson-pass-required',
						'class' => ' ',
					);
					$require_pass_field              = Sensei_Utils::generate_drop_down( '-1', $pass_required_options, $pass_required_select_attributes, false );

					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in called method.
					echo $this->generate_all_lessons_edit_field( esc_html__( 'Pass required', 'sensei-lms' ), $require_pass_field );

					//
					// Quiz pass percentage
					//
					$quiz_pass_percentage_field = '<input name="quiz_passmark" id="sensei-edit-quiz-pass-percentage" type="number" />';

					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in called method.
					echo $this->generate_all_lessons_edit_field( esc_html__( 'Pass Percentage', 'sensei-lms' ), $quiz_pass_percentage_field );

					//
					// Enable quiz reset button
					//
					$quiz_reset_select__options   = array(
						'-1' => $no_change_text,
						'0'  => esc_html__( 'No', 'sensei-lms' ),
						'1'  => esc_html__( 'Yes', 'sensei-lms' ),
					);
					$quiz_reset_name_id           = 'sensei-edit-enable-quiz-reset';
					$quiz_reset_select_attributes = array(
						'name'  => 'enable_quiz_reset',
						'id'    => $quiz_reset_name_id,
						'class' => ' ',
					);
					$quiz_reset_field             = Sensei_Utils::generate_drop_down( '-1', $quiz_reset_select__options, $quiz_reset_select_attributes, false );

					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in called method.
					echo $this->generate_all_lessons_edit_field( esc_html__( 'Enable quiz reset button', 'sensei-lms' ), $quiz_reset_field );

					?>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Create the html for the edit field
	 *
	 * Wraps the passed in field and title combination with the correct html.
	 *
	 * @since 1.8.0
	 *
	 * @param string $title that will stand to the left of the field.
	 * @param string $field type markup for the field that must be wrapped.
	 * @return string $field_html
	 */
	public function generate_all_lessons_edit_field( $title, $field ) {

		$html  = '';
		$html  = '<div class="inline-edit-group" >';
		$html .= '<span class="title">' . esc_html( $title ) . '</span> ';
		$html .= '<span class="input-text-wrap">';
		$html .= $field;
		$html .= '</span>';
		$html .= '</div>';

		return wp_kses(
			$html,
			array_merge(
				wp_kses_allowed_html( 'post' ),
				array(
					'input'  => array(
						'id'   => array(),
						'name' => array(),
						'type' => array(),
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
	 * Respond to the ajax call from the bulk edit save function. This comes
	 * from the admin all lesson screen.
	 *
	 * @since 1.8.0
	 * @return void
	 */
	function save_all_lessons_edit_fields() {

		// verify all the data before attempting to save
		if ( ! isset( $_POST['security'] ) || ! check_ajax_referer( 'bulk-edit-lessons', 'security' )
			|| empty( $_POST['post_ids'] ) || ! is_array( $_POST['post_ids'] ) ) {
			die();
		}

		// get our variables
		$new_course            = sanitize_text_field( $_POST['sensei_edit_lesson_course'] );
		$new_complexity        = sanitize_text_field( $_POST['sensei_edit_complexity'] );
		$new_pass_required     = sanitize_text_field( $_POST['sensei_edit_pass_required'] );
		$new_pass_percentage   = sanitize_text_field( $_POST['sensei_edit_pass_percentage'] );
		$new_enable_quiz_reset = sanitize_text_field( $_POST['sensei_edit_enable_quiz_reset'] );
		// store the values for all selected posts
		foreach ( $_POST['post_ids'] as $lesson_id ) {

			// get the quiz id needed for the quiz meta
			$quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_id );

			// do not save the items if the value is -1 as this
			// means it was not changed
			// update lesson course
			if ( -1 != $new_course ) {
				update_post_meta( $lesson_id, '_lesson_course', $new_course );
			}
			// update lesson complexity
			if ( -1 != $new_complexity ) {
				update_post_meta( $lesson_id, '_lesson_complexity', $new_complexity );
			}

			// Quiz Related settings
			if ( isset( $quiz_id ) && 0 < intval( $quiz_id ) ) {

				// update pass required
				if ( -1 != $new_pass_required ) {

					$checked = $new_pass_required ? 'on' : '';
					update_post_meta( $quiz_id, '_pass_required', $checked );
					unset( $checked );
				}

				// update pass percentage
				if ( ! empty( $new_pass_percentage ) && is_numeric( $new_pass_percentage ) ) {

						update_post_meta( $quiz_id, '_quiz_passmark', $new_pass_percentage );

				}

				//
				// update enable quiz reset
				//
				if ( -1 != $new_enable_quiz_reset ) {

					$checked = $new_enable_quiz_reset ? 'on' : '';
					update_post_meta( $quiz_id, '_enable_quiz_reset', $checked );
					unset( $checked );

				}
			}
		}

		die();

	}

	/**
	 * Loading the quick edit fields defaults.
	 *
	 * This function will localise the default values along with the script that will
	 * add these values to the inputs.
	 *
	 * NOTE: this function runs for each row in the edit column
	 *
	 * @since 1.8.0
	 * @return void
	 */
	public function set_quick_edit_admin_defaults( $column_name, $post_id ) {

		if ( 'lesson-course' != $column_name ) {
			return;
		}

		// setup the values for all meta fields
		$data = array();
		foreach ( $this->meta_fields as $field ) {

			$data[ $field ] = get_post_meta( $post_id, '_' . $field, true );

		}
		// add quiz meta fields
		$quiz_id = Sensei()->lesson->lesson_quizzes( $post_id );
		foreach ( Sensei()->quiz->meta_fields as $field ) {

			$data[ $field ] = get_post_meta( $quiz_id, '_' . $field, true );

		}

		wp_localize_script( 'sensei-lesson-quick-edit', 'sensei_quick_edit_' . $post_id, $data );

	}

	/**
	 * Filter the classes for lessons on the single course page.
	 *
	 * Adds the necessary classes depending on the user data
	 *
	 * @since 1.9.0
	 * @param array $classes
	 * @return array $classes
	 */
	public static function single_course_lessons_classes( $classes ) {

		global $post;
		$course_id = $post->ID;

		$lesson_classes = array( 'course', 'post' );
		if ( is_user_logged_in() ) {

			// Check if Lesson is complete
			$single_lesson_complete = Sensei_Utils::user_completed_lesson( get_the_ID(), get_current_user_id() );
			if ( $single_lesson_complete ) {

				$lesson_classes[] = 'completed';

			}
		}

		$is_user_taking_course = Sensei_Course::is_user_enrolled( $course_id );
		if ( Sensei_Utils::is_preview_lesson( get_the_ID() ) && ! $is_user_taking_course ) {

			$lesson_classes[] = 'preview';

		}

		return array_merge( $classes, $lesson_classes );

	}

	/**
	 * Output the lesson meta for the given lesson
	 *
	 * @since 1.9.0
	 * @param $lesson_id
	 */
	public static function the_lesson_meta( $lesson_id ) {
		global $wp_query;

		$loop_lesson_number    = $wp_query->current_post + 1;
		$course_id             = Sensei()->lesson->get_course_id( $lesson_id );
		$is_user_taking_course = Sensei_Course::is_user_enrolled( $course_id );

		// Get Lesson data
		$complexity_array = Sensei()->lesson->lesson_complexities();

		$lesson_complexity = get_post_meta( $lesson_id, '_lesson_complexity', true );
		if ( '' != $lesson_complexity ) {

			$lesson_complexity = $complexity_array[ $lesson_complexity ];

		}
		$user_info     = get_userdata( absint( get_post()->post_author ) );
		$is_preview    = Sensei_Utils::is_preview_lesson( $lesson_id );
		$preview_label = '';
		if ( $is_preview && ! $is_user_taking_course ) {

			$preview_label = Sensei()->frontend->sensei_lesson_preview_title_tag( $course_id );

		}

		$count_markup = '';
		/**
		 * Filter whether to show lesson numbers next to the lesson.
		 *
		 * @since 1.0
		 * @hook sensei_show_lesson_numbers
		 *
		 * @param {bool} $show_lesson_numbers Whether to show lesson numbers. Default false.
		 * @return {bool} Whether to show lesson numbers.
		 */
		if ( apply_filters( 'sensei_show_lesson_numbers', false ) ) {
			$count_markup = '<span class="lesson-number">' . esc_html( $loop_lesson_number ) . '</span>';
		}

		// translators: Placeholder is the lesson title.
		$heading_link_title = sprintf( esc_html__( 'Start %s', 'sensei-lms' ), get_the_title( $lesson_id ) );

		?>
		<header class="lesson-title">
			<h2>
				<a href="<?php echo esc_url( get_permalink( $lesson_id ) ); ?>"
				   title="<?php echo esc_attr( $heading_link_title ); ?>" >
					<?php echo wp_kses_post( $count_markup ) . esc_html( get_the_title( $lesson_id ) ); ?>
				</a>
			</h2>

			<?php echo wp_kses_post( $preview_label ); ?>

			<p class="lesson-meta">

				<?php

				$meta_html          = '';
				$user_lesson_status = Sensei_Utils::user_lesson_status( get_the_ID(), get_current_user_id() );

				$lesson_length = get_post_meta( $lesson_id, '_lesson_length', true );
				if ( '' != $lesson_length ) {

					$meta_html .= '<span class="lesson-length">' . esc_html__( 'Length:', 'sensei-lms' ) . ' ' . esc_html( $lesson_length ) . ' ' . esc_html__( 'minutes', 'sensei-lms' ) . '</span>';

				}

				if ( Sensei()->settings->get( 'lesson_author' ) ) {

					$meta_html .= '<span class="lesson-author">' . esc_html__( 'Author:', 'sensei-lms' ) . ' ' . '<a href="' . esc_url( get_author_posts_url( absint( get_post()->post_author ) ) ) . '" title="' . esc_attr( $user_info->display_name ) . '">' . esc_html( $user_info->display_name ) . '</a></span>';

				}
				if ( '' != $lesson_complexity ) {

					$meta_html .= '<span class="lesson-complexity">' . esc_html__( 'Complexity:', 'sensei-lms' ) . ' ' . esc_html( $lesson_complexity ) . '</span>';

				}

				if ( Sensei_Utils::user_completed_lesson( $lesson_id, get_current_user_id() ) ) {

					$meta_html .= '<span class="lesson-status complete">' . esc_html__( 'Complete', 'sensei-lms' ) . '</span>';

				} elseif ( $user_lesson_status ) {

					$meta_html .= '<span class="lesson-status in-progress">' . esc_html__( 'In Progress', 'sensei-lms' ) . '</span>';

				}

				echo wp_kses_post( $meta_html );

				?>

			</p> <!-- lesson meta -->

		</header>

		<?php

	}

	/**
	 * Output the lessons thumbnail
	 *
	 * 1.9.0
	 *
	 * @param $lesson_id
	 */
	public static function the_lesson_thumbnail( $lesson_id ) {

		if ( empty( $lesson_id ) ) {

			$lesson_id = get_the_ID();

		}

		if ( 'lesson' != get_post_type( $lesson_id ) ) {
			return;
		}

		echo wp_kses_post( Sensei()->lesson->lesson_image( $lesson_id ) );
	}


	/**
	 * Alter the sensei lesson excerpt.
	 *
	 * @since 1.9.0
	 * @param string $excerpt
	 * @return string $excerpt
	 */
	public static function alter_the_lesson_excerpt( $excerpt ) {

		if ( 'lesson' == get_post_type( get_the_ID() ) ) {

			// remove this hooks to avoid an infinite loop.
			remove_filter( 'get_the_excerpt', array( 'Sensei_Lesson', 'alter_the_lesson_excerpt' ) );

			return self::lesson_excerpt( get_post( get_the_ID() ) );
		}

		return $excerpt;

	}

	/**
	 * Returns the lesson prerequisite for the given lesson id.
	 *
	 * @since 1.9.0
	 *
	 * @param $current_lesson_id
	 * @return mixed | bool | int $prerequisite_lesson_id or false
	 */
	public static function get_lesson_prerequisite_id( $current_lesson_id ) {

		$prerequisite_lesson_id = get_post_meta( $current_lesson_id, '_lesson_prerequisite', true );

		// set ti to false if not a valid prerequisite lesson id
		if ( empty( $prerequisite_lesson_id )
			|| 'lesson' != get_post_type( $prerequisite_lesson_id )
			|| $prerequisite_lesson_id == $current_lesson_id ) {

			$prerequisite_lesson_id = false;

		}

		/**
		 * Filter the lesson prerequisite.
		 *
		 * @since 1.0
		 * @hook sensei_lesson_prerequisite
		 *
		 * @param {string|bool} $prerequisite_lesson_id Prerequisite lesson ID. False if prerequisite lesson ID is
		 *                                                                      empty, is not a lesson, or
		 *                                                                      equals the current lesson ID.
		 * @param {int}         $current_lesson_id      Lesson ID.
		 * @return {string|bool} Prerequisite lesson ID.
		 */
		return apply_filters( 'sensei_lesson_prerequisite', $prerequisite_lesson_id, $current_lesson_id );
	}

	/**
	 * Start the lesson the first time the student visits the page.
	 *
	 * @param int|string $lesson_id
	 * @param int|string $user_id
	 */
	public static function maybe_start_lesson( $lesson_id = '', $user_id = '' ) {
		if ( empty( $lesson_id ) ) {
			$lesson_id = get_the_ID();
		}

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( empty( $lesson_id ) || empty( $user_id ) || 'lesson' !== get_post_type( $lesson_id ) ) {
			return;
		}

		$lesson_course_id   = get_post_meta( $lesson_id, '_lesson_course', true );
		$user_taking_course = Sensei_Course::is_user_enrolled( $lesson_course_id, $user_id );
		if ( ! $user_taking_course || ! sensei_can_user_view_lesson( $lesson_id, $user_id ) ) {
			return;
		}

		if ( ! self::is_prerequisite_complete( $lesson_id, $user_id ) ) {
			return;
		}

		if ( false !== Sensei_Utils::user_started_lesson( $lesson_id, $user_id ) ) {
			return;
		}

		Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );
	}

	/**
	 * This function requires that you pass in the lesson you would like to check for
	 * a pre-requisite and not the pre-requisite. It will check if the
	 * lesson has a pre-requiste and then check if it is completed.
	 *
	 * @since 1.9.0
	 *
	 * @param $lesson_id
	 * @param $user_id
	 * @return bool
	 */
	public static function is_prerequisite_complete( $lesson_id, $user_id ) {

		if ( empty( $lesson_id ) || empty( $user_id )
		|| 'lesson' != get_post_type( $lesson_id )
		|| ! is_a( get_user_by( 'id', $user_id ), 'WP_User' ) ) {

			return false;

		}

		$pre_requisite_id = (string) self::get_lesson_prerequisite_id( $lesson_id );

		// not a valid pre-requisite so pre-requisite is completed
		if ( 'lesson' != get_post_type( $pre_requisite_id )
			|| ! is_numeric( $pre_requisite_id ) ) {

			return true;

		}

		return Sensei_Utils::user_completed_lesson( $pre_requisite_id, $user_id );

	}

	/**
	 * Returns the lesson that the user needs to begin with, in a chain of prerequisites.
	 *
	 * @param int $lesson_id The lesson id to begin searching.
	 * @param int $user_id   The user id.
	 *
	 * @return int The first lesson id.
	 */
	public static function find_first_prerequisite_lesson( int $lesson_id, int $user_id ) : int {
		$lesson_prerequisites = [ $lesson_id ];
		$lesson_prerequisite  = (int) self::get_lesson_prerequisite_id( $lesson_id );

		while ( $lesson_prerequisite > 0 && ! self::is_prerequisite_complete( $lesson_id, $user_id ) ) {
			// We need to check each prerequisite against already found prerequisites to avoid an infinite loop in case of
			// a cycle of prerequisites.
			if ( in_array( $lesson_prerequisite, $lesson_prerequisites, true ) ) {
				return $lesson_prerequisite;
			}

			$lesson_prerequisites[] = $lesson_prerequisite;
			$lesson_id              = $lesson_prerequisite;
			$lesson_prerequisite    = self::get_lesson_prerequisite_id( $lesson_id );
		}

		return count( $lesson_prerequisites ) === 1 ? 0 : $lesson_id;
	}

	/**
	 * Show the user not taking course message if it is the case
	 *
	 * @since 1.9.0
	 * @deprecated 3.0.0
	 */
	public static function user_not_taking_course_message() {

		_deprecated_function( __METHOD__, '3.0.0' );

	}

	/**
	 * Outputs the lessons course signup lingk
	 *
	 * This hook runs inside the single lesson page.
	 *
	 * @since 1.9.0
	 */
	public static function course_signup_link() {

		$course_id = Sensei()->lesson->get_course_id( get_the_ID() );

		if ( empty( $course_id ) || 'course' !== get_post_type( $course_id ) || sensei_all_access() || Sensei_Utils::is_preview_lesson( get_the_ID() ) ) {
			return;
		}

		$show_course_signup_notice = sensei_is_login_required() && ! Sensei_Course::is_user_enrolled( $course_id );

		/**
		 * Filter whether to show the course sign up notice on the lesson page.
		 *
		 * @since 2.0.0
		 * @hook sensei_lesson_show_course_signup_notice
		 *
		 * @param {bool}   $show_course_signup_notice True if we should show the signup notice to the user.
		 * @param {string} $course_id                 Course ID.
		 * @return {bool} Whether to show the course sign up notice.
		 */
		if ( apply_filters( 'sensei_lesson_show_course_signup_notice', $show_course_signup_notice, $course_id ) ) {
			$course_link  = '<a href="' . esc_url( get_permalink( $course_id ) ) . '" title="' . esc_attr__( 'Sign Up', 'sensei-lms' ) . '">';
			$course_link .= esc_html__( 'course', 'sensei-lms' );
			$course_link .= '</a>';

			// translators: The placeholder %1$s is a link to the Course.
			$message_default = sprintf( esc_html__( 'Please sign up for the %1$s before starting the lesson.', 'sensei-lms' ), $course_link );

			/**
			 * Filter the course sign up notice message on the lesson page.
			 *
			 * @since 2.0.0
			 * @hook sensei_lesson_course_signup_notice_message
			 *
			 * @param {string} $message_default Message to show user.
			 * @param {string} $course_id       Course ID.
			 * @param {string} $course_link     HTML for the link to the course.
			 * @return {string} Message to show user.
			 */
			$message = apply_filters( 'sensei_lesson_course_signup_notice_message', $message_default, $course_id, $course_link );

			/**
			 * Filter the course sign up notice message alert level on the lesson page.
			 *
			 * @since 2.0.0
			 * @hook sensei_lesson_course_signup_notice_level
			 *
			 * @param {string} $notice_level Level to use for the sign up notice (alert, tick, download, info).
			 * @param {string} $course_id    Course ID.
			 * @return {string} Level to use for the sign up notice.
			 */
			$notice_level = apply_filters( 'sensei_lesson_course_signup_notice_level', 'info', $course_id );
			Sensei()->notices->add_notice( $message, $notice_level );
		}

	}

	/**
	 * Show a message telling the user to complete the previous message if they haven't done so yet
	 *
	 * @since 1.9.0
	 */
	public static function prerequisite_complete_message() {

		$lesson_prerequisite = self::find_first_prerequisite_lesson( get_the_ID(), get_current_user_id() );

		if ( $lesson_prerequisite > 0 ) {

			$prerequisite_lesson_link = '<a href="'
				. esc_url( get_permalink( $lesson_prerequisite ) )
				. '" title="'
				// translators: Placeholder is the lesson prerequisite title.
				. sprintf( esc_attr__( 'You must first complete: %1$s', 'sensei-lms' ), get_the_title( $lesson_prerequisite ) )
				. '">'
				. get_the_title( $lesson_prerequisite )
				. '</a>';
			// translators: Placeholder is the link to the prerequisite lesson.
			Sensei()->notices->add_notice( sprintf( esc_html__( 'You must first complete %1$s before viewing this Lesson', 'sensei-lms' ), $prerequisite_lesson_link ), 'info' );

		}

	}

	/**
	 * Outputs the lesson archive header.
	 *
	 * @since  1.9.0
	 * @return void
	 */
	public function the_archive_header() {

		$before_html = '<header class="archive-header"><h1>';
		$after_html  = '</h1></header>';

		$title = '';
		if ( is_post_type_archive( 'lesson' ) ) {

			$title = __( 'Lessons Archive', 'sensei-lms' );

		} elseif ( is_tax( 'module' ) ) {

			global $wp_query;
			$term  = $wp_query->get_queried_object();
			$title = $term->name;

		}

		$html = $before_html . $title . $after_html;

		/**
		 * Filter the lesson archive header.
		 *
		 * @hook sensei_lesson_archive_title
		 *
		 * @param {string} $html HTML for the lesson archive header.
		 * @return {string} HTML for the lesson archive header.
		 */
		echo wp_kses_post( apply_filters( 'sensei_lesson_archive_title', $html ) );

	} // sensei_course_archive_header()

	/**
	 * Output the title for the single lesson page
	 *
	 * @global $post
	 * @since 1.9.0
	 */
	public static function the_title() {

		global $post, $current_user;

		$course_id  = get_post_meta( $post->ID, '_lesson_course', true );
		$is_preview = isset( $post->ID )
			&& Sensei_Utils::is_preview_lesson( $post->ID )
			&& ! Sensei_Course::is_user_enrolled( $course_id, $current_user->ID );

		?>
		<header class="lesson-title">

			<h1>

				<?php
				/** This filter is documented in includes/class-sensei-messages.php */
				echo wp_kses_post( apply_filters( 'sensei_single_title', get_the_title( $post ), $post->post_type ) );
				?>

			</h1>

			<?php
			if ( $is_preview ) {
				echo wp_kses_post( Sensei()->frontend->sensei_lesson_preview_title_tag( $course_id ) );
			}
			?>

		</header>

		<?php

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
	 * Output the quiz specific buttons and messaging on the single lesson page
	 *
	 * @since 1.0.0 moved here from frontend class
	 *
	 * @param int $lesson_id
	 * @param int $user_id
	 */
	public static function footer_quiz_call_to_action( $lesson_id = 0, $user_id = 0 ) {

		$lesson_id = empty( $lesson_id ) ? get_the_ID() : $lesson_id;
		$user_id   = empty( $user_id ) ? get_current_user_id() : $user_id;

		if ( ! sensei_can_user_view_lesson( $lesson_id, $user_id ) ) {
			return;
		}

		$quiz_id                   = Sensei()->lesson->lesson_quizzes( $lesson_id );
		$has_user_completed_lesson = Sensei_Utils::user_completed_lesson( intval( $lesson_id ), $user_id );
		$show_actions              = self::should_show_lesson_actions( $lesson_id, $user_id );
		?>

		<footer>

			<?php
			if ( $show_actions && $quiz_id && Sensei()->access_settings() ) {

				if ( self::lesson_quiz_has_questions( $lesson_id ) ) {
					?>

					<p>

						<a class="button"
						   href="<?php echo esc_url( get_permalink( $quiz_id ) ); ?>"
						   title="<?php esc_attr_e( 'View the Lesson Quiz', 'sensei-lms' ); ?>">

							<?php esc_html_e( 'View the Lesson Quiz', 'sensei-lms' ); ?>

						</a>

					</p>

					<?php
				}
			}

			if ( $show_actions && ! $has_user_completed_lesson ) {

				sensei_complete_lesson_button();

			} elseif ( $show_actions ) {

				sensei_reset_lesson_button();

			}
			?>

		</footer>

		<?php
	}

	/**
	 * Helper method which checks if the lesson actions should be shown.
	 *
	 * @param int $lesson_id The lesson id.
	 * @param int $user_id   The user id. Defaults to current user.
	 *
	 * @return bool
	 */
	public static function should_show_lesson_actions( int $lesson_id, int $user_id = 0 ) : bool {
		$user_id = empty( $user_id ) ? get_current_user_id() : $user_id;

		if ( 0 === $user_id ) {
			return false;
		}

		$lesson_prerequisite = (int) get_post_meta( $lesson_id, '_lesson_prerequisite', true );

		if ( $lesson_prerequisite > 0 ) {

			// If the user hasn't completed the prerequisites then hide the current actions.
			// (If the user is either the lesson creator or admin, show actions).
			return Sensei_Utils::user_completed_lesson( $lesson_prerequisite, $user_id )
				|| Sensei()->lesson->is_lesson_author( $lesson_id, $user_id )
				|| current_user_can( 'manage_options' );
		}

		return true;
	}

	/**
	 * Shows the lesson comments. This should be used in the loop.
	 *
	 * @since 1.9.0
	 */
	public static function output_comments() {
		$allow_comments        = Sensei()->settings->settings['lesson_comments'];
		$user_can_view_lesson  = sensei_can_user_view_lesson();
		$lesson_allow_comments = $allow_comments && $user_can_view_lesson;

		if ( $lesson_allow_comments || is_singular( 'sensei_message' ) ) {
			comments_template( '', true );
		}
	}

	/**
	 * Display the leeson quiz status if it should be shown
	 *
	 * @param int $lesson_id defaults to the global lesson id
	 * @param int $user_id defaults to the current user id
	 *
	 * @since 1.9.0
	 */
	public static function user_lesson_quiz_status_message( $lesson_id = 0, $user_id = 0 ) {

		$lesson_id                 = empty( $lesson_id ) ? get_the_ID() : $lesson_id;
		$user_id                   = empty( $lesson_id ) ? get_current_user_id() : $user_id;
		$lesson_course_id          = (int) get_post_meta( $lesson_id, '_lesson_course', true );
		$quiz_id                   = Sensei()->lesson->lesson_quizzes( $lesson_id );
		$has_user_completed_lesson = Sensei_Utils::user_completed_lesson( intval( $lesson_id ), $user_id );

		if ( $quiz_id && is_user_logged_in()
			&& Sensei_Course::is_user_enrolled( $lesson_course_id, $user_id ) ) {
			$has_quiz_questions = self::lesson_quiz_has_questions( $lesson_id );

			// Display lesson quiz status message
			if ( $has_user_completed_lesson || $has_quiz_questions ) {
				$status = Sensei_Utils::sensei_user_quiz_status_message( $lesson_id, $user_id, true );

				if ( ! empty( $status['message'] ) ) {
					echo '<div class="sensei-message ' . esc_attr( $status['box_class'] ) . '">' .
						wp_kses_post( $status['message'] ) . '</div>';
				}
			}
		}

	}

	/**
	 * On the lesson archive limit the number of words the show up if the access settings are enabled
	 *
	 * @since 1.9.0
	 * @param $content
	 * @return string
	 */
	public static function limit_archive_content( $content ) {

		if ( is_post_type_archive( 'lesson' ) && Sensei()->settings->get( 'access_permission' ) ) {
			return wp_trim_words( $content, 30, '…' );
		}

		return $content;

	}

	/**
	 * Returns all publised lesson ID's
	 *
	 * @since 1.9.0
	 * @return array
	 */
	public static function get_all_lesson_ids() {

		return get_posts(
			array(
				'post_type'     => 'lesson',
				'fields'        => 'ids',
				'post_status'   => 'publish',
				'numberposts'   => 4000, // legacy support
				'post_per_page' => 4000,
			)
		);

	}

	/**
	 * Checks if a lesson has a "Complete" quiz that requires the passmark to be achieved
	 * before progressing. A complete quiz has at least one question.
	 *
	 * @param int $lesson_id The Lesson.
	 * @return bool
	 */
	public function lesson_has_quiz_with_questions_and_pass_required( $lesson_id ) {
		// Lesson quizzes
		$quiz_id = $this->lesson_quizzes( $lesson_id );
		if ( empty( $quiz_id ) ) {
			return false;
		}

		$has_quiz_questions = self::lesson_quiz_has_questions( $lesson_id );
		if ( false === $has_quiz_questions ) {
			return false;
		}

		$pass_required = (bool) get_post_meta( $quiz_id, '_pass_required', true );

		return $pass_required;
	}

	/**
	 * Checks if a lesson has a quiz that has at least one graded question.
	 *
	 * @param int $lesson_id The Lesson.
	 * @return bool
	 */
	public function lesson_has_quiz_with_graded_questions( $lesson_id ) {
		// Lesson quizzes
		$quiz_id = $this->lesson_quizzes( $lesson_id );
		if ( empty( $quiz_id ) ) {
			return false;
		}

		$has_quiz_questions = self::lesson_quiz_has_questions( $lesson_id );
		if ( false === $has_quiz_questions ) {
			return false;
		}

		$quiz_total = Sensei_Utils::sensei_get_quiz_total( $quiz_id );

		return 0 !== $quiz_total;
	}

	/**
	 * Lesson Quiz Has Questions
	 *
	 * @param int $lesson_id Lesson.
	 * @return bool
	 */
	public static function lesson_quiz_has_questions( $lesson_id ) {
		return (bool) get_post_meta( $lesson_id, '_quiz_has_questions', true );
	}

	/**
	 * Log an event when a lesson is initially published.
	 *
	 * @since 2.1.0
	 * @access private
	 *
	 * @param WP_Post $lesson The Lesson.
	 */
	public function log_initial_publish_event( $lesson ) {
		$event_properties = [
			'course_id' => -1,
			'module_id' => -1,
		];

		// Get course ID if it is set.
		$lesson_course_id = get_post_meta( $lesson->ID, '_lesson_course', true );
		if ( $lesson_course_id ) {
			$event_properties['course_id'] = $lesson_course_id;
		}

		// Get module ID if it is set.
		$modules = wp_get_post_terms( $lesson->ID, 'module' );
		if ( is_array( $modules ) && count( $modules ) > 0 ) {
			$event_properties['module_id'] = $modules[0]->term_id;
		}

		sensei_log_event( 'lesson_publish', $event_properties );
	}

	/**
	 * Mark updating lesson id.
	 *
	 * Hooked into `save_post_lesson`.
	 *
	 * @since 3.8.0
	 * @access private
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function mark_updating_lesson_id( $post_id, $post ) {
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		$this->lesson_id_updating = $post_id;
	}

	/**
	 * Log the lesson update.
	 *
	 * Hooked into `shutdown`.
	 *
	 * @since 3.8.0
	 * @access private
	 */
	public function log_lesson_update() {
		if ( empty( $this->lesson_id_updating ) ) {
			return;
		}

		$lesson_id = $this->lesson_id_updating;
		$post      = get_post( $lesson_id );

		if ( empty( $post ) ) {
			return;
		}

		$course_id = $this->get_course_id( $lesson_id );

		// Don't log if it's part of a sample course.
		if ( $course_id && 'getting-started-with-sensei-lms' === get_post_field( 'post_name', $course_id ) ) {
			return;
		}

		$content     = $post->post_content;
		$module_term = Sensei()->modules->get_lesson_module( $lesson_id );

		$event_properties = [
			'course_id'                 => $course_id ? $course_id : -1,
			'module_id'                 => $module_term ? $module_term->term_id : -1,
			'lesson_id'                 => $lesson_id,
			'has_contact_teacher_block' => has_block( 'sensei-lms/button-contact-teacher', $content ) ? 1 : 0,
			'has_lesson_actions_block'  => has_block( 'sensei-lms/lesson-actions', $content ) ? 1 : 0,
		];

		sensei_log_event( 'lesson_update', $event_properties );
	}

	/**
	 * Disable log lesson update when it's a REST request.
	 *
	 * Hooked into `rest_api_init`.
	 *
	 * @since 3.8.0
	 * @access private
	 */
	public function disable_log_lesson_update() {
		remove_action( 'shutdown', [ $this, 'log_lesson_update' ] );
	}

	/**
	 * Check if a user is the lesson author.
	 *
	 * @since 3.2.0
	 *
	 * @param int      $lesson_id ID of lesson being checked.
	 * @param int|null $user_id ID of user being checked. Defaults to null.
	 * @return boolean Returns TRUE if user is the lesson author, returns FALSE otherwise.
	 */
	private function is_lesson_author( $lesson_id, $user_id = null ) {

		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( empty( $user_id ) ) {
			return false;
		}

		if ( (int) get_post_field( 'post_author', $lesson_id ) === $user_id ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if a lesson has Sensei blocks.
	 *
	 * @param int|WP_Post $lesson Lesson ID or lesson object.
	 *
	 * @return bool
	 */
	public function has_sensei_blocks( $lesson = null ) {
		$lesson = get_post( $lesson );

		$lesson_blocks = [
			'sensei-lms/lesson-actions',
			'sensei-lms/lesson-properties',
			'sensei-lms/button-contact-teacher',
		];

		foreach ( $lesson_blocks as $block ) {
			if ( has_block( $block, $lesson ) ) {
				return true;
			}
		}

		return false;
	}
}

/**
 * Class WooThemes_Sensei_Lesson
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Lesson extends Sensei_Lesson{}
