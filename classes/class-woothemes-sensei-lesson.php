<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Lessons Class
 *
 * All functionality pertaining to the lessons post type in Sensei.
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
 * - meta_box_setup()
 * - lesson_info_meta_box_content()
 * - lesson_prerequisite_meta_box_content()
 * - meta_box_save()
 * - post_updated()
 * - save_post_meta()
 * - lesson_course_meta_box_content()
 * - lesson_quiz_meta_box_content()
 * - enqueue_scripts()
 * - add_column_headings()
 * - add_column_data()
 * - lesson_add_course()
 * - lesson_update_questions()
 * - lesson_save_course()
 * - lesson_save_question()
 * - lesson_delete_question()
 * - lesson_complexities()
 * - lesson_count()
 * - lesson_quizzes()
 * - lesson_quiz_questions()
 * - lesson_image()
 */
class WooThemes_Sensei_Lesson {
	public $token;
	public $meta_fields;

	/**
	 * Constructor.
	 * @since  1.0.0
	 */
	public function __construct () {
		// Setup meta fields for this post type
		$this->meta_fields = array( 'lesson_prerequisite', 'lesson_course', 'lesson_length', 'lesson_complexity', 'lesson_video_embed' );
		// Admin actions
		if ( is_admin() ) {
			// Metabox functions
			add_action( 'admin_menu', array( &$this, 'meta_box_setup' ), 20 );
			add_action( 'save_post', array( &$this, 'meta_box_save' ) );
			add_action( 'save_post', array( &$this, 'post_updated' ) );
			// Custom Write Panel Columns
			add_filter( 'manage_edit-lesson_columns', array( &$this, 'add_column_headings' ), 10, 1 );
			add_action( 'manage_posts_custom_column', array( &$this, 'add_column_data' ), 10, 2 );
			// Ajax functions
			add_action( 'wp_ajax_lesson_update_question', array( &$this, 'lesson_update_question' ) );
			add_action( 'wp_ajax_nopriv_lesson_update_question', array( &$this, 'lesson_update_question' ) );
			add_action( 'wp_ajax_lesson_add_course', array( &$this, 'lesson_add_course' ) );
			add_action( 'wp_ajax_nopriv_lesson_add_course', array( &$this, 'lesson_add_course' ) );
			add_action( 'wp_ajax_lesson_update_grade_type', array( &$this, 'lesson_update_grade_type' ) );
			add_action( 'wp_ajax_nopriv_lesson_update_grade_type', array( &$this, 'lesson_update_grade_type' ) );
		} else {
			// Frontend actions
		} // End If Statement
	} // End __construct()

	/**
	 * meta_box_setup function.
	 *
	 * @access public
	 * @return void
	 */
	public function meta_box_setup () {
		// Add Meta Box for Prerequisite Lesson
		add_meta_box( 'lesson-prerequisite', __( 'Lesson Prerequisite', 'woothemes-sensei' ), array( &$this, 'lesson_prerequisite_meta_box_content' ), $this->token, 'side', 'default' );
		// Add Meta Box for Lesson Course
		add_meta_box( 'lesson-course', __( 'Lesson Course', 'woothemes-sensei' ), array( &$this, 'lesson_course_meta_box_content' ), $this->token, 'side', 'default' );
		// Add Meta Box for Lesson Quiz information
		add_meta_box( 'lesson-info', __( 'Lesson Information', 'woothemes-sensei' ), array( &$this, 'lesson_info_meta_box_content' ), $this->token, 'normal', 'default' );
		// Add Meta Box for Lesson Quiz Questions
		add_meta_box( 'lesson-quiz', __( 'Lesson Quiz', 'woothemes-sensei' ), array( &$this, 'lesson_quiz_meta_box_content' ), $this->token, 'normal', 'default' );
		// Remove "Custom Settings" meta box.
		remove_meta_box( 'woothemes-settings', $this->token, 'normal' );
		// Add JS scripts
		add_action( 'admin_print_scripts', array( &$this, 'enqueue_scripts' ) );
	} // End meta_box_setup()


	/**
	 * lesson_info_meta_box_content function.
	 *
	 * @access public
	 * @return void
	 */
	public function lesson_info_meta_box_content () {
		global $post;

		$lesson_length = get_post_meta( $post->ID, '_lesson_length', true );
		$lesson_complexity = get_post_meta( $post->ID, '_lesson_complexity', true );
		$complexity_array = $this->lesson_complexities();
		$lesson_video_embed = get_post_meta( $post->ID, '_lesson_video_embed', true );

		$html = '';
		// Lesson Length
		$html .= '<label for="lesson_length">' . __( 'Lesson Length in minutes', 'woothemes-sensei' ) . '</label>';
		$html .= '<input type="text" id="lesson-length" name="lesson_length" value="' . esc_attr( $lesson_length ) . '" size="25" class="widefat" />';
		// Lesson Complexity
		$html .= '<label for="lesson_complexity">' . __( 'Lesson Complexity', 'woothemes-sensei' ) . '</label>';
		$html .= '<select id="lesson-complexity-options" name="lesson_complexity" class="widefat lesson-complexity-select">' . "\n";
			$html .= '<option value="">' . __( 'None', 'woothemes-sensei' ) . '</option>';
			foreach ($complexity_array as $key => $value){
				$html .= '<option value="' . esc_attr( $key ) . '"' . selected( $key, $lesson_complexity, false ) . '>' . esc_html( $value ) . '</option>' . "\n";
			} // End For Loop
		$html .= '</select>' . "\n";

		$html .= '<label for="lesson_video_embed">' . __( 'Video Embed Code', 'woothemes-sensei' ) . '</label>';
		$html .= '<textarea rows="1" cols="40" name="lesson_video_embed" tabindex="6" id="course-video-embed">' . $lesson_video_embed . '</textarea>';

		echo $html;

	} // End lesson_info_meta_box_content()

	/**
	 * lesson_prerequisite_meta_box_content function.
	 *
	 * @access public
	 * @return void
	 */
	public function lesson_prerequisite_meta_box_content () {
		global $post;
		// Get existing post meta
		$select_lesson_prerequisite = get_post_meta( $post->ID, '_lesson_prerequisite', true );
		// Get the Lesson Posts
		$post_args = array(	'post_type' 		=> 'lesson',
							'numberposts' 		=> -1,
							'orderby'         	=> 'title',
    						'order'           	=> 'DESC',
    						'exclude' 			=> $post->ID,
							'suppress_filters' 	=> 0
							);
		$posts_array = get_posts( $post_args );
		// Build the HTML to Output
		$html = '';
		$html .= '<input type="hidden" name="' . esc_attr( 'woo_' . $this->token . '_noonce' ) . '" id="' . esc_attr( 'woo_' . $this->token . '_noonce' ) . '" value="' . esc_attr( wp_create_nonce( plugin_basename(__FILE__) ) ) . '" />';
		if ( count( $posts_array ) > 0 ) {
			$html .= '<select id="lesson-prerequisite-options" name="lesson_prerequisite" class="widefat">' . "\n";
			$html .= '<option value="">' . __( 'None', 'woothemes-sensei' ) . '</option>';
				foreach ($posts_array as $post_item){
					$html .= '<option value="' . esc_attr( absint( $post_item->ID ) ) . '"' . selected( $post_item->ID, $select_lesson_prerequisite, false ) . '>' . esc_html( $post_item->post_title ) . '</option>' . "\n";
				} // End For Loop
			$html .= '</select>' . "\n";
		} else {
			$html .= '<p>' . esc_html( __( 'No lessons exist yet. Please add some first.', 'woothemes-sensei' ) ) . '</p>';
		} // End If Statement
		// Output the HTML
		echo $html;
	} // End lesson_prerequisite_meta_box_content()

	/**
	 * meta_box_save function.
	 *
	 * @access public
	 * @param int $post_id
	 * @return void
	 */
	public function meta_box_save ( $post_id ) {
		global $post, $messages;
		// Verify the nonce before proceeding.
		if ( ( get_post_type() != $this->token ) || ! wp_verify_nonce( $_POST[ 'woo_' . $this->token . '_noonce' ], plugin_basename(__FILE__) ) ) {
			return $post_id;
		} // End If Statement
		// Get the post type object.
		$post_type = get_post_type_object( $post->post_type );
		// Check if the current user has permission to edit the post.
		if ( !current_user_can( $post_type->cap->edit_post, $post_id ) ) {
			return $post_id;
		} // End If Statement
		// Check if the current post type is a page
		if ( 'page' == $_POST[ 'post_type' ] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			} // End If Statement
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			} // End If Statement
		} // End If Statement
		// Save the post meta data fields
		if ( isset($this->meta_fields) && is_array($this->meta_fields) ) {
			foreach ( $this->meta_fields as $meta_key ) {
				$this->save_post_meta( $meta_key, $post_id );
			} // End For Loop
		} // End If Statement
	} // End meta_box_save()


	/**
	 * post_updated function.
	 *
	 * @access public
	 * @return void
	 */
	public function post_updated() {
		global $post;
		// Verify the nonce before proceeding.
		if ( ( get_post_type() != $this->token ) || ! wp_verify_nonce( $_POST[ 'woo_' . $this->token . '_noonce' ], plugin_basename(__FILE__) ) ) {
			if ( isset($post->ID) ) {
				return $post->ID;
			} else {
				return false;
			} // End If Statement
		} // End If Statement
		// Temporarily disable the filter
   		remove_action('save_post', array(&$this, __FUNCTION__));
		// Save the Quiz
		$quiz_id = 0;
		$quiz_passmark = 0;
		 // Sanitize and setup the post data
		$_POST = stripslashes_deep( $_POST );
		if ( isset( $_POST[ 'quiz_id' ] ) && ( 0 < absint( $_POST[ 'quiz_id' ] ) ) ) {
			$quiz_id = absint( $_POST[ 'quiz_id' ] );
		} // End If Statement
		$post_title = esc_html( $_POST[ 'post_title' ] ) . ' ' . __( 'Quiz', 'woothemes-sensei' );
		$post_author = esc_html( $_POST[ 'post_author' ] );
		$post_status = esc_html( $_POST[ 'post_status' ] );
		$post_type = 'quiz';
		$post_content = '';
		if ( isset( $_POST[ 'quiz_passmark' ] ) && ( 0 < absint( $_POST[ 'quiz_passmark' ] ) ) ) {
			$quiz_passmark = absint( $_POST[ 'quiz_passmark' ] );
		} // End If Statement
		if ( isset( $_POST[ 'quiz_grade_type' ] ) && $_POST[ 'quiz_grade_type' ] == 'on' ) {
			$quiz_grade_type = 'auto';
		} else {
			$quiz_grade_type = 'manual';
		}// End If Statement
		if ( isset( $_POST[ 'quiz_grade_type_disabled' ] ) ) {
			$quiz_grade_type_disabled = esc_html( $_POST[ 'quiz_grade_type_disabled' ] );
		}
		// Setup Query Arguments
		$post_type_args = array(	'post_content' => $post_content,
  		    						'post_status' => $post_status,
  		    						'post_title' => $post_title,
  		    						'post_type' => $post_type
  		    						);
  		// Update or Insert the Lesson Quiz
		if ( 0 < $quiz_id ) {
			// Update the Quiz
			$post_type_args[ 'ID' ] = $quiz_id;
		    wp_update_post($post_type_args);
		    // Update the post meta data
		    update_post_meta( $quiz_id, '_quiz_lesson', $post->ID );
		    update_post_meta( $quiz_id, '_quiz_passmark', $quiz_passmark );
		    update_post_meta( $quiz_id, '_quiz_grade_type', $quiz_grade_type );
		    update_post_meta( $quiz_id, '_quiz_grade_type_disabled', $quiz_grade_type_disabled );
		    // Set the post terms for quiz-type
		    wp_set_post_terms( $quiz_id, array( 'multiple-choice' ), 'quiz-type' ); // EXTENSIONS - possible refactor to get term slug after ID selection
		} else {
			// Create the Quiz
		    $quiz_id = wp_insert_post($post_type_args);
		    // Add the post meta data
		    add_post_meta( $quiz_id, '_quiz_lesson', $post->ID );
		    add_post_meta( $quiz_id, '_quiz_passmark', $quiz_passmark );
		    add_post_meta( $quiz_id, '_quiz_grade_type', $quiz_grade_type );
		    add_post_meta( $quiz_id, '_quiz_grade_type_disabled', $quiz_grade_type_disabled );
		    // Set the post terms for quiz-type
		    wp_set_post_terms( $quiz_id, array( 'multiple-choice' ), 'quiz-type' ); // EXTENSIONS - possible refactor to get term slug after ID selection
		} // End If Statement
		// Restore the previously disabled filter
    	add_action('save_post', array(&$this, __FUNCTION__));
	} // End post_updated()


	/**
	 * save_post_meta function.
	 * Saves lesson meta data
	 * @access private
	 * @param string $post_key (default: '')
	 * @param int $post_id (default: 0)
	 * @return void
	 */
	private function save_post_meta( $post_key = '', $post_id = 0 ) {
		// Get the meta key.
		$meta_key = '_' . $post_key;
		// Get the posted data and sanitize it for use as an HTML class.
		if ( 'lesson_video_embed' == $post_key) {
			$new_meta_value = esc_html( $_POST[$post_key] );
		} else {
			$new_meta_value = ( isset( $_POST[$post_key] ) ? sanitize_html_class( $_POST[$post_key] ) : '' );
		} // End If Statement
		// Get the meta value of the custom field key.
		$meta_value = get_post_meta( $post_id, $meta_key, true );
		// If a new meta value was added and there was no previous value, add it.
		if ( $new_meta_value && '' == $meta_value ) {
			add_post_meta( $post_id, $meta_key, $new_meta_value, true );
		} elseif ( $new_meta_value && $new_meta_value != $meta_value ) {
			// If the new meta value does not match the old value, update it.
			update_post_meta( $post_id, $meta_key, $new_meta_value );
		} elseif ( '' == $new_meta_value && $meta_value ) {
			// If there is no new meta value but an old value exists, delete it.
			delete_post_meta( $post_id, $meta_key, $meta_value );
		} // End If Statement
	} // End save_post_meta()

	/**
	 * lesson_course_meta_box_content function.
	 *
	 * @access public
	 * @return void
	 */
	public function lesson_course_meta_box_content () {
		global $post;
		// Setup Lesson Meta Data
		$select_lesson_prerequisite = 0;
		if ( 0 < $post->ID ) {
			$select_lesson_prerequisite = get_post_meta( $post->ID, '_lesson_course', true );
		} // End If Statement
		// Handle preselected course
		if ( isset( $_GET[ 'course_id' ] ) && ( 0 < absint( $_GET[ 'course_id' ] ) ) ) {
			$select_lesson_prerequisite = absint( $_GET[ 'course_id' ] );
		} // End If Statement
		// Get the Lesson Posts
		$post_args = array(	'post_type' 		=> 'course',
							'numberposts' 		=> -1,
							'orderby'         	=> 'title',
    						'order'           	=> 'DESC',
    						'post_status'      	=> 'any',
    						'suppress_filters' 	=> 0
							);
		$posts_array = get_posts( $post_args );
		// Buid the HTML to Output
		$html = '';
		// Nonce
		$html .= '<input type="hidden" name="' . esc_attr( 'woo_' . $this->token . '_noonce' ) . '" id="' . esc_attr( 'woo_' . $this->token . '_noonce' ) . '" value="' . esc_attr( wp_create_nonce( plugin_basename(__FILE__) ) ) . '" />';
			// Select the course for the lesson
			$html .= '<select id="lesson-course-options" name="lesson_course" class="widefat">' . "\n";
				$html .= '<option value="">' . __( 'None', 'woothemes-sensei' ) . '</option>';
				if ( count( $posts_array ) > 0 ) {
				foreach ($posts_array as $post_item){
					$html .= '<option value="' . esc_attr( absint( $post_item->ID ) ) . '"' . selected( $post_item->ID, $select_lesson_prerequisite, false ) . '>' . esc_html( $post_item->post_title ) . '</option>' . "\n";
				} // End For Loop
				} // End If Statement
			$html .= '</select>' . "\n";
			// Course Actions Panel
			if ( current_user_can( 'publish_courses' )) {
				$html .= '<div id="lesson-course-actions">';
					$html .= '<p>';
						// Add a course action link
						$html .= '<a id="lesson-course-add" href="#course-add" class="lesson-add-course">+ ' . __('Add New Course', 'woothemes-sensei' ) . '</a>';
					$html .= '</p>';
				$html .= '</div>';
				// Add a course input fields
				$html .= '<div id="lesson-course-details" class="hidden">';
					$html .= '<p>';
						// Course Title input
						$html .= '<label>' . __( 'Course Title' , 'woothemes-sensei' ) . '</label> ';
	  					$html .= '<input type="text" id="course-title" name="course_title" value="" size="25" class="widefat" />';
	  					// Course Description input
	  					$html .= '<label>' . __( 'Description' , 'woothemes-sensei' ) . '</label> ';
	  					$html .= '<textarea rows="10" cols="40" id="course-content" name="course_content" value="" size="300" class="widefat"></textarea>';
	  					// Course Prerequisite
	  					$html .= '<label>' . __( 'Course Prerequisite' , 'woothemes-sensei' ) . '</label> ';
	  					$html .= '<select id="course-prerequisite-options" name="course_prerequisite" class="widefat">' . "\n";
							$html .= '<option value="">' . __( 'None', 'woothemes-sensei' ) . '</option>';
							foreach ($posts_array as $post_item){
								$html .= '<option value="' . esc_attr( absint( $post_item->ID ) ) . '">' . esc_html( $post_item->post_title ) . '</option>' . "\n";
							} // End For Loop
						$html .= '</select>' . "\n";
						// Course Product
	  					if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() ) {
	  						// Get the Products
							$select_course_woocommerce_product = get_post_meta( $post_item->ID, '_course_woocommerce_product', true );

							$product_args = array(	'post_type' 		=> 'product',
													'numberposts' 		=> -1,
													'orderby'         	=> 'title',
	    											'order'           	=> 'DESC',
	    											'post_status'		=> array( 'publish', 'private', 'draft' ),
	    											'suppress_filters' 	=> 0
													);
							$products_array = get_posts( $product_args );
							$html .= '<label>' . __( 'WooCommerce Product' , 'woothemes-sensei' ) . '</label> ';
	  						$html .= '<select id="course-woocommerce-product-options" name="course_woocommerce_product" class="widefat">' . "\n";
								$html .= '<option value="-">' . __( 'None', 'woothemes-sensei' ) . '</option>';
								foreach ($products_array as $products_item){
									$html .= '<option value="' . esc_attr( absint( $products_item->ID ) ) . '">' . esc_html( $products_item->post_title ) . '</option>' . "\n";
								} // End For Loop
							$html .= '</select>' . "\n";
						} else {
							// Default
							$html .= '<input type="hidden" name="course_woocommerce_product" id="course-woocommerce-product-options" value="-" />';
						}
						// Course Category
	  					$html .= '<label>' . __( 'Course Category' , 'woothemes-sensei' ) . '</label> ';
	  					$cat_args = array( 'echo' => false, 'hierarchical' => true, 'show_option_none' => __( 'None', 'woothemes-sensei' ), 'taxonomy' => 'course-category', 'orderby' => 'name', 'id' => 'course-category-options', 'name' => 'course_category', 'class' => 'widefat' );
						$html .= wp_dropdown_categories(apply_filters('widget_course_categories_dropdown_args', $cat_args)) . "\n";
	  					// Save the course action button
	  					$html .= '<a title="' . esc_attr( __( 'Save Course', 'woothemes-sensei' ) ) . '" href="#add-course-metadata" class="lesson_course_save button button-highlighted">' . esc_html( __( 'Add Course', 'woothemes-sensei' ) ) . '</a>';
						$html .= '&nbsp;&nbsp;&nbsp;';
						// Cancel action link
						$html .= '<a href="#course-add-cancel" class="lesson_course_cancel">' . __( 'Cancel', 'woothemes-sensei' ) . '</a>';
					$html .= '</p>';
				$html .= '</div>';
			} // End If Statement
		// Output the HTML
		echo $html;
	} // End lesson_course_meta_box_content()

	/**
	 * lesson_quiz_meta_box_content function.
	 *
	 * @access public
	 * @return void
	 */
	public function lesson_quiz_meta_box_content () {
		global $post;
		// Setup Lesson Meta Data
		$select_lesson_prerequisite = 0;
		if ( 0 < $post->ID ) {
			$select_lesson_prerequisite = get_post_meta( $post->ID, '_lesson_quiz', true );
		} // End If Statement
		// Setup Quiz Meta Data
		$lesson_quiz_passmark = '';
		$quiz_id = 0;
		// Setup Questions Query
		$posts_array = array();
		if ( 0 < $post->ID ) {

			$posts_array = $this->lesson_quizzes( $post->ID, 'any' );

		} // End If Statement
		// Set Quiz ID
		if ( $posts_array ) {
			foreach ( $posts_array as $quiz ) {
				setup_postdata($quiz);
				$quiz_id = $quiz->ID;
				$lesson_quiz_passmark = get_post_meta( $quiz_id, '_quiz_passmark', true );
				$quiz_grade_type_disabled = get_post_meta( $quiz_id, '_quiz_grade_type_disabled', true );
				if( $quiz_grade_type_disabled == 'disabled' ) {
					$quiz_grade_type = 'manual';
				} else {
					$quiz_grade_type = get_post_meta( $quiz_id, '_quiz_grade_type', true );
				}
			} // End For Loop
		} // End If Statement
		// Quiz Panel CSS Class
		$quiz_class = '';
		if ( 0 == $quiz_id ) {
			$quiz_class = ' class="hidden"';
		} // End If Statement
		// Build the HTML to Output
		$message_class = '';
		$html = '';
		// Nonce
		$html .= '<input type="hidden" name="' . esc_attr( 'woo_' . $this->token . '_noonce' ) . '" id="' . esc_attr( 'woo_' . $this->token . '_noonce' ) . '" value="' . esc_attr( wp_create_nonce( plugin_basename(__FILE__) ) ) . '" />';
		// Quiz Container DIV
		$html .= '<div id="add-quiz-main">';
			// Add Quiz HTML
			if ( 0 == $quiz_id ) {
				$html .= '<p>';
					// Default message and Add a Quiz button
					$html .= esc_html( __( 'There is no quiz for this lesson yet. Please add one.', 'woothemes-sensei' ) );
					$html .= '<button type="button" class="button button-highlighted add_quiz">' . esc_html( __( 'Add', 'woothemes-sensei' ) )  . '</button>';
				$html .= '</p>';
			} // End If Statement

			// Inner DIV
			$html .= '<div id="add-quiz-metadata"' . $quiz_class . '>';

				// Quiz Meta data
				$html .= '<p>';

					// Quiz Pass Percentage
					$html .= '<input type="hidden" name="quiz_id" id="quiz_id" value="' . esc_attr( $quiz_id ) . '" />';
					$html .= '<label for="quiz_passmark">' . __( 'Quiz passmark percentage' , 'woothemes-sensei' ) . '</label> ';
  					$html .= '<input type="text" id="quiz_passmark" name="quiz_passmark" value="' . esc_attr( $lesson_quiz_passmark ) . '" size="25" class="widefat" />';
  					$html .= '<br/>';

  					// Quiz grade type
  					$html .= '<input type="hidden" id="quiz_grade_type_disabled" name="quiz_grade_type_disabled" value="' . esc_attr( $quiz_grade_type_disabled ) . '" /> ';
  					$html .= '<input type="checkbox" id="quiz_grade_type" name="quiz_grade_type"' . checked( $quiz_grade_type, 'auto', false ) . ' ' . disabled( $quiz_grade_type_disabled, 'disabled', false ) . ' /> ';
  					$html .= '<label for="quiz_grade_type">' . __( 'Grade quiz automatically', 'woothemes-sensei' ) . '</label>';

				$html .= '</p>';

				// Default Message
				if ( 0 == $quiz_id ) {
					$html .= '<p class="save-note">';
						$html .= esc_html( __( 'Please save your lesson in order to add questions to your quiz.', 'woothemes-sensei' ) );
					$html .= '</p>';
				} // End If Statement

			$html .= '</div>';
		$html .= '</div>';

		// Question Container DIV
		$html .= '<div id="add-question-main"' . $quiz_class . '>';
			// Inner DIV
			$html .= '<div id="add-question-metadata">';
				// Setup Question counter
				$question_counter = 1;
				// Setup Question Meta Data
				$question_id = 0;
				// Setup Questions Query
				$posts_array = array();
				if ( 0 < $quiz_id ) {
					$post_args = array(	'post_type' 		=> 'question',
										'numberposts' 		=> -1,
										'orderby'         	=> 'ID',
    									'order'           	=> 'ASC',
    									'meta_key'        	=> '_quiz_id',
    									'meta_value'      	=> $quiz_id,
    									'post_status'		=> 'any',
										'suppress_filters' 	=> 0
										);
					$posts_array = get_posts( $post_args );
				} // End If Statement
				// Build Questions Table HTML
				if ( $posts_array ) {
					$post_count = count( $posts_array );
					// Count of questions
					$html .= '<input type="hidden" name="question_counter" id="question_counter" value="' . esc_attr( $post_count ) . '" />';
					// Table headers
					$html .= '<table class="widefat">
								<thead>
								    <tr>
								        <th class="hidden">#</th>
								        <th>' . __( 'Question', 'woothemes-sensei' ) . '</th>
								        <th style="width:125px;">' . __( 'Type', 'woothemes-sensei' ) . '</th>
								        <th style="width:125px;">' . __( 'Action', 'woothemes-sensei' ) . '</th>
								    </tr>
								</thead>
								<tfoot>
								    <tr>
								    <th class="hidden">#</th>
								    <th>' . __( 'Question', 'woothemes-sensei' ) . '</th>
								    <th>' . __( 'Type', 'woothemes-sensei' ) . '</th>
								    <th>' . __( 'Action', 'woothemes-sensei' ) . '</th>
								    </tr>
								</tfoot>
								<tbody>';
					if ( 0 < $post_count) { $message_class = "hidden"; }
					$html .= '<tr id="no-questions-message" class="' . esc_attr( $message_class ) . '">';
						$html .= '<td colspan="4">' . __( 'There are no Questions for this Quiz yet. Please add some below.', 'woothemes-sensei' ) . '</td>';
					$html .= '</tr>';
					// Existing questions
					foreach ( $posts_array as $question ) {
						setup_postdata($question);
						$question_id = $question->ID;
						$question_type = 'multiple-choice';
						// Get existing questions meta data
						$select_question_right_answer = get_post_meta( $question_id, '_question_right_answer', true);
						$select_question_wrong_answers = get_post_meta( $question_id, '_question_wrong_answers', true);
						$question_types = wp_get_post_terms( $question_id, 'question-type', array( 'fields' => 'names' ) );
						if ( isset( $question_types[0] ) && '' != $question_types[0] ) {
							$question_type = $question_types[0];
						} // End If Statement
						// Row with question and actions
						$html .= '<tr>';
							$html .= '<td class="table-count hidden">' . $question_counter . '</td>';
   							$html .= '<td>' . esc_html( stripslashes( get_the_title( $question_id ) ) ) . '</td>';
   							$question_types_filtered = str_replace( array( '-', 'boolean' ), array( ' ', 'True/False' ), $question_type );
   							$html .= '<td>' . esc_html( ucwords( $question_types_filtered ) ) . '</td>';
   							$html .= '<td><a title="' . esc_attr( __( 'Edit Question', 'woothemes-sensei' ) ) . '" href="#question_' . $question_counter .'" class="question_table_edit">' . esc_html( __( 'Edit', 'woothemes-sensei' ) ) . '</a>&nbsp;&nbsp;&nbsp;<a title="' . esc_attr( __( 'Delete Question', 'woothemes-sensei' ) ) . '" href="#add-question-metadata" class="question_table_delete">' . esc_html( __( 'Delete', 'woothemes-sensei' ) ) . '</a></td>';
						$html .= '</tr>';
						// Edit question form
						$html .= '<tr class="question-quick-edit hidden">';
							$html .= '<td colspan="4">';
						    	// Question
						    	$html .= '<div class="question_required_fields">';
							    	$html .= '<label>' . __( 'Question' . ' ' . $question_counter  , 'woothemes-sensei' ) . '</label> ';
	  						    	$html .= '<input type="text" id="question_' . $question_counter . '" name="question" value="' . esc_attr( stripslashes( get_the_title( $question_id ) ) ) . '" size="25" class="widefat" />';
	  						    $html .= '</div>';
  						    	switch ( $question_type ) {
									case 'multiple-choice':
										$html .= '<div class="question_default_fields">';
											// Right Answer
											$html .= '<label>' . __( 'Right Answer' , 'woothemes-sensei' ) . '</label> ';
			  						    	$html .= '<input type="text" id="question_' . $question_counter . '_right_answer" name="question_right_answer" value="' . esc_attr( stripslashes( $select_question_right_answer ) ) . '" size="25" class="widefat" />';
									    	// Wrong Answers - TO DO
									    	$html .= '<label>' . __( 'Wrong Answers' , 'woothemes-sensei' ) . '</label> ';
			  						    	// Setup Wrong Answer HTML
									    	for ( $i = 0; $i < 4; $i++ ) {
									    		if ( !isset( $select_question_wrong_answers[ $i ] ) ) { $select_question_wrong_answers[ $i ] = ''; }
									    		$html .= '<input type="text" name="question_wrong_answers[]" value="' . esc_attr( stripslashes( $select_question_wrong_answers[ $i ] ) ) . '" size="25" class="widefat" />';
			  						    	} // End For Loop
		  						    	$html .= '</div>';
									break;
									case 'boolean':
										$html .= '<div class="question_boolean_fields">';
					  						$html .= '<input type="radio" name="question_' . $question_id . '_right_answer_boolean" value="true" '. checked( $select_question_right_answer, 'true', false ) . ' />&nbsp;&nbsp;True&nbsp;&nbsp;&nbsp;&nbsp;';
											$html .= '<input type="radio" name="question_' . $question_id . '_right_answer_boolean" value="false" '. checked( $select_question_right_answer, 'false', false ) . ' />&nbsp;&nbsp;False';
										$html .= '</div>';
									break;
									case 'gap-fill':
										$gapfill_array = explode( '|', $select_question_right_answer );
										if ( isset( $gapfill_array[0] ) ) { $gapfill_pre = $gapfill_array[0]; } else { $gapfill_pre = ''; }
										if ( isset( $gapfill_array[1] ) ) { $gapfill_gap = $gapfill_array[1]; } else { $gapfill_gap = ''; }
										if ( isset( $gapfill_array[2] ) ) { $gapfill_post = $gapfill_array[2]; } else { $gapfill_post = ''; }
										$html .= '<div class="question_gapfill_fields">';
					  						// Fill in the Gaps
											$html .= '<label>' . __( 'Text before the Gap' , 'woothemes-sensei' ) . '</label> ';
											$html .= '<input type="text" id="question_' . $question_counter . '_add_question_right_answer_gapfill_pre" name="add_question_right_answer_gapfill_pre" value="' . $gapfill_pre . '" size="25" class="widefat gapfill-field" />';
						  					$html .= '<label>' . __( 'The Gap' , 'woothemes-sensei' ) . '</label> ';
						  					$html .= '<input type="text" id="question_' . $question_counter . '_add_question_right_answer_gapfill_gap" name="add_question_right_answer_gapfill_gap" value="' . $gapfill_gap . '" size="25" class="widefat gapfill-field" />';
						  					$html .= '<label>' . __( 'Text after the Gap' , 'woothemes-sensei' ) . '</label> ';
						  					$html .= '<input type="text" id="question_' . $question_counter . '_add_question_right_answer_gapfill_post" name="add_question_right_answer_gapfill_post" value="' . $gapfill_post . '" size="25" class="widefat gapfill-field" />';
						  					$html .= '<label>' . __( 'Preview:' , 'woothemes-sensei' ) . '</label> ';
						  					$html .= '<p class="gapfill-preview">' . $gapfill_pre . '&nbsp;<u>' . $gapfill_gap . '</u>&nbsp;' . $gapfill_post . '</p>';
						  				$html .= '</div>';
									break;
									case 'essay-paste':
										$html .= '<div class="question_essay_fields">';
					  						// Guides for grading
											$html .= '<label>' . __( 'Guide/Teacher Notes for grading the Essay' , 'woothemes-sensei' ) . '</label> ';
											$html .= '<textarea id="question_' . $question_counter . '_add_question_right_answer_essay" name="add_question_right_answer_essay" rows="15" cols="40" class="widefat">' . $select_question_right_answer . '</textarea>';
										$html .= '</div>';
									break;
									case 'multi-line':
										$html .= '<div class="question_multiline_fields">';
					  						// Guides for grading
											$html .= '<label>' . __( 'Guide/Teacher Notes for grading the answer' , 'woothemes-sensei' ) . '</label> ';
											$html .= '<textarea id="question_' . $question_counter . '_add_question_right_answer_multiline" name="add_question_right_answer_multiline" rows="3" cols="40" class="widefat">' . $select_question_right_answer . '</textarea>';
										$html .= '</div>';
									break;
									case 'single-line':
										$html .= '<div class="question_singleline_fields">';
					  						// Recommended Answer
											$html .= '<label>' . __( 'Recommended Answer' , 'woothemes-sensei' ) . '</label> ';
											$html .= '<input type="text" id="question_' . $question_counter . '_add_question_right_answer_singleline" name="add_question_right_answer_singleline" value="' . $select_question_right_answer . '" size="25" class="widefat" />';
										$html .= '</div>';
									break;
									default :
										// Right Answer
								    	$html .= '<label>' . __( 'Right Answer' , 'woothemes-sensei' ) . '</label> ';
		  						    	$html .= '<input type="text" id="question_' . $question_counter . '_right_answer" name="question_right_answer" value="' . esc_attr( stripslashes( $select_question_right_answer ) ) . '" size="25" class="widefat" />';
								    	// Wrong Answers - TO DO
								    	$html .= '<label>' . __( 'Wrong Answers' , 'woothemes-sensei' ) . '</label> ';
		  						    	// Setup Wrong Answer HTML
								    	for ( $i = 0; $i < 4; $i++ ) {
								    		if ( !isset( $select_question_wrong_answers[ $i ] ) ) { $select_question_wrong_answers[ $i ] = ''; }
								    		$html .= '<input type="text" name="question_wrong_answers[]" value="' . esc_attr( stripslashes( $select_question_wrong_answers[ $i ] ) ) . '" size="25" class="widefat" />';
		  						    	} // End For Loop
									break;
								} // End Switch Statement
								// Question Type
								$html .= '<input type="hidden" id="question_' . $question_counter . '_question_type" class="question_type" name="question_type" value="' . $question_type . '" />';
  						    	// Question ID
  						    	$html .= '<input type="hidden" name="question_id" id="question_' . $question_counter . '_id" value="' . $question_id . '" />';
						    	// Update question button
						    	$html .= '<a title="' . esc_attr( __( 'Update Question', 'woothemes-sensei' ) ) . '" href="#add-question-metadata" class="question_table_save button button-highlighted">' . esc_html( __( 'Update', 'woothemes-sensei' ) ) . '</a>';
						    	$html .= '&nbsp;&nbsp;&nbsp;';
						    	// Cancel the edit button
						    	$html .= '<a href="#question-edit-cancel" class="lesson_question_cancel" title="' . esc_attr( __( 'Cancel', 'woothemes-sensei' ) ) . '">' . __( 'Cancel', 'woothemes-sensei' ) . '</a>';
						    $html .= '</td>';
						$html .= '</tr>';
						$question_counter++;
					} // End For Loop
					$html .= '</tbody>
							</table>';
				} else {
					// Build the default table - V2 refactor this into a generic function for use with the above output
					$post_count = 0;
					// Build Questions Table
					$html .= '<input type="hidden" name="question_counter" id="question_counter" value="' . esc_attr( $post_count ) . '" />';
					// Table headers
					$html .= '<table class="widefat">
								<thead>
								    <tr>
								        <th class="hidden">#</th>
								        <th>' . __( 'Question', 'woothemes-sensei' ) . '</th>
								        <th style="width:125px;">' . __( 'Type', 'woothemes-sensei' ) . '</th>
								        <th style="width:125px;">' . __( 'Action', 'woothemes-sensei' ) . '</th>
								    </tr>
								</thead>
								<tfoot>
								    <tr>
								    <th class="hidden">#</th>
								    <th>' . __( 'Question', 'woothemes-sensei' ) . '</th>
								    <th>' . __( 'Type', 'woothemes-sensei' ) . '</th>
								    <th>' . __( 'Action', 'woothemes-sensei' ) . '</th>
								    </tr>
								</tfoot>
								<tbody>';
					if ( 0 < $post_count) { $message_class = "hidden"; }
					$html .= '<tr id="no-questions-message" class="' . esc_attr( $message_class ) . '">';
						$html .= '<td colspan="4">' . __( 'There are no Questions for this Quiz yet. Please add some below.', 'woothemes-sensei' ) . '</td>';
					$html .= '</tr>';
					$html .= '</tbody>
							</table>';
				} // End If Statement
			$html .= '</div>';
			// Question Action Container DIV
			$html .= '<div id="add-question-actions">';
				// Question Actions panel
				$html .= '<p>';
					// Add another question action button
  					$html .= esc_html( __( 'Add a question.', 'woothemes-sensei' ) );
					$html .= '<button type="button" class="button button-highlighted add_question_answer">' . esc_html( __( 'Add', 'woothemes-sensei' ) )  . '</button>';
				$html .= '</p>';
				// Add Question form
				$html .= '<div id="add-new-question" class="hidden">';
					// Question Type
					$html .= '<label>' . __( 'Question Type'  , 'woothemes-sensei' ) . '</label> ';
					$html .= '<select id="add-question-type-options" name="question_type" class="widefat question-type-select">' . "\n";
						$question_types_array = array( 'multiple-choice' => 'Multiple Choice', 'boolean' => 'True/False', 'gap-fill' => 'Gap Fill', 'essay-paste' => 'Essay Paste', 'multi-line' => 'Multi Line Reply', 'single-line' => 'Single Line Reply' );
						$question_type = '';
						foreach ($question_types_array as $key => $value){
							$html .= '<option value="' . esc_attr( $key ) . '"' . selected( $key, $question_type, false ) . '>' . esc_html( $value ) . '</option>' . "\n";
						} // End For Loop
					$html .= '</select>' . "\n";
					$html .= '<div class="question_required_fields">';
						// Question
						$html .= '<label>' . __( 'Question'  , 'woothemes-sensei' ) . '</label> ';
	  					$html .= '<input type="text" id="add_question" name="question" value="" size="25" class="widefat" />'; // V2 - additional validation on this field
					$html .= '</div>';
					$html .= '<div class="question_default_fields">';
						// Right Answer
						$html .= '<label>' . __( 'Right Answer' , 'woothemes-sensei' ) . '</label> ';
	  					$html .= '<input type="text" id="add_question_right_answer" name="question_right_answer" value="" size="25" class="widefat" />';
						// Wrong Answers - V2 make dynamic
						$html .= '<label>' . __( 'Wrong Answers' , 'woothemes-sensei' ) . '</label> ';
	  					$html .= '<input type="text" name="question_wrong_answers[]" value="" size="25" class="widefat" />';
	  					$html .= '<input type="text" name="question_wrong_answers[]" value="" size="25" class="widefat" />';
	  					$html .= '<input type="text" name="question_wrong_answers[]" value="" size="25" class="widefat" />';
	  					$html .= '<input type="text" name="question_wrong_answers[]" value="" size="25" class="widefat" />';
  					$html .= '</div>';
  					// True/False Inputs
  					$html .= '<div class="question_boolean_fields hidden">';
  						$html .= '<input type="radio" name="question_right_answer_boolean" value="true" checked="checked"/>&nbsp;&nbsp;True&nbsp;&nbsp;&nbsp;&nbsp;';
						$html .= '<input type="radio" name="question_right_answer_boolean" value="false" />&nbsp;&nbsp;False';
					$html .= '</div>';
					// GapFill Inputs
  					$html .= '<div class="question_gapfill_fields hidden">';
  						// The Gaps
						$html .= '<label>' . __( 'Text before the Gap' , 'woothemes-sensei' ) . '</label> ';
						$html .= '<input type="text" id="add_question_right_answer_gapfill_pre" name="add_question_right_answer_gapfill_pre" value="" size="25" class="widefat gapfill-field" />';
						$html .= '<label>' . __( 'The Gap' , 'woothemes-sensei' ) . '</label> ';
	  					$html .= '<input type="text" id="add_question_right_answer_gapfill_gap" name="add_question_right_answer_gapfill_gap" value="" size="25" class="widefat gapfill-field" />';
	  					$html .= '<label>' . __( 'Text after the Gap' , 'woothemes-sensei' ) . '</label> ';
	  					$html .= '<input type="text" id="add_question_right_answer_gapfill_post" name="add_question_right_answer_gapfill_post" value="" size="25" class="widefat gapfill-field" />';
	  					$html .= '<label>' . __( 'Preview:' , 'woothemes-sensei' ) . '</label> ';
	  					$html .= '<p class="gapfill-preview"></span>';
					$html .= '</div>';
					// Essay Inputs
  					$html .= '<div class="question_essay_fields hidden">';
  						// Guides for grading
						$html .= '<label>' . __( 'Guide/Teacher Notes for grading the Essay' , 'woothemes-sensei' ) . '</label> ';
						$html .= '<textarea id="add_question_right_answer_essay" name="add_question_right_answer_essay" rows="15" cols="40" class="widefat"></textarea>';
					$html .= '</div>';
					// Multi Line Inputs
  					$html .= '<div class="question_multiline_fields hidden">';
  						// Guides for grading
						$html .= '<label>' . __( 'Guide/Teacher Notes for grading the answer' , 'woothemes-sensei' ) . '</label> ';
						$html .= '<textarea id="add_question_right_answer_multiline" name="add_question_right_answer_multiline" rows="3" cols="40" class="widefat"></textarea>';
					$html .= '</div>';
					// Single Line Inputs
  					$html .= '<div class="question_singleline_fields hidden">';
  						// Recommended Answer
						$html .= '<label>' . __( 'Recommended Answer' , 'woothemes-sensei' ) . '</label> ';
						$html .= '<input type="text" id="add_question_right_answer_singleline" name="add_question_right_answer_singleline" value="" size="25" class="widefat" />';
					$html .= '</div>';
  					// Save the question
  					$html .= '<a title="' . esc_attr( __( 'Add Question', 'woothemes-sensei' ) ) . '" href="#add-question-metadata" class="add_question_save button button-highlighted">' . esc_html( __( 'Add Question', 'woothemes-sensei' ) ) . '</a>';
  					$html .= '&nbsp;&nbsp;&nbsp;';
					// Cancel the question add
					$html .= '<a href="#question-add-cancel" class="lesson_question_cancel">' . __( 'Cancel', 'woothemes-sensei' ) . '</a>';
				$html .= '</p>';
			$html .= '</div>';
		$html .= '</div>';
		// Output the HTML
		echo $html;
	} // End lesson_quiz_meta_box_content()


	/**
	 * enqueue_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function enqueue_scripts() {
		global $woothemes_sensei;
		// Load the lessons script
		wp_enqueue_script( 'woosensei-lesson-metadata', $woothemes_sensei->plugin_url . 'assets/js/lesson-metadata.js', array( 'jquery' ), '1.3.5' );
		wp_enqueue_script( 'woosensei-lesson-chosen', $woothemes_sensei->plugin_url . 'assets/chosen/chosen.jquery.min.js', array( 'jquery' ), '1.3.0' );
		$translation_strings = array();
		$ajax_vars = array( 'lesson_update_question_nonce' => wp_create_nonce( 'lesson_update_question_nonce' ), 'lesson_add_course_nonce' => wp_create_nonce( 'lesson_add_course_nonce' ), 'lesson_update_grade_type_nonce' => wp_create_nonce( 'lesson_update_grade_type_nonce' ) );
		$data = array_merge( $translation_strings, $ajax_vars );
		// V2 - Specify variables to be made available to the lesson-metadata.js file.
		wp_localize_script( 'woosensei-lesson-metadata', 'woo_localized_data', $data );
	} // End enqueue_scripts()

	/**
	 * Add column headings to the "lesson" post list screen.
	 * @access public
	 * @since  1.0.0
	 * @param  array $defaults
	 * @return array $new_columns
	 */
	public function add_column_headings ( $defaults ) {
		$new_columns['cb'] = '<input type="checkbox" />';
		$new_columns['title'] = _x( 'Lesson Title', 'column name', 'woothemes-sensei' );
		$new_columns['lesson-course'] = _x( 'Course', 'column name', 'woothemes-sensei' );
		$new_columns['lesson-prerequisite'] = _x( 'Pre-requisite Lesson', 'column name', 'woothemes-sensei' );
		if ( isset( $defaults['date'] ) ) {
			$new_columns['date'] = $defaults['date'];
		}
		return $new_columns;
	} // End add_column_headings()

	/**
	 * Add data for our newly-added custom columns.
	 * @access public
	 * @since  1.0.0
	 * @param  string $column_name
	 * @param  int $id
	 * @return void
	 */
	public function add_column_data ( $column_name, $id ) {
		global $wpdb, $post;
		switch ( $column_name ) {
			case 'id':
				echo $id;
			break;
			case 'lesson-course':
				$lesson_course_id = get_post_meta( $id, '_lesson_course', true);
				if ( 0 < absint( $lesson_course_id ) ) {
					echo '<a href="' . esc_url( get_edit_post_link( absint( $lesson_course_id ) ) ) . '" title="' . esc_attr( sprintf( __( 'Edit %s', 'woothemes-sensei' ), get_the_title( absint( $lesson_course_id ) ) ) ) . '">' . get_the_title( absint( $lesson_course_id ) ) . '</a>';
				} // End If Statement
			break;
			case 'lesson-prerequisite':
				$lesson_prerequisite_id = get_post_meta( $id, '_lesson_prerequisite', true);
				if ( 0 < absint( $lesson_prerequisite_id ) ) {
					echo '<a href="' . esc_url( get_edit_post_link( absint( $lesson_prerequisite_id ) ) ) . '" title="' . esc_attr( sprintf( __( 'Edit %s', 'woothemes-sensei' ), get_the_title( absint( $lesson_prerequisite_id ) ) ) ) . '">' . get_the_title( absint( $lesson_prerequisite_id ) ) . '</a>';
				} // End If Statement
			break;
			default:
			break;
		} // End Switch Statement
	} // End add_column_data()

	/**
	 * lesson_update_question function.
	 *
	 * @access public
	 * @return void
	 */
	public function lesson_add_course () {
		global $current_user;
		//Add nonce security to the request
		if ( isset($_POST['lesson_add_course_nonce']) ) {
			$nonce = esc_html( $_POST['lesson_add_course_nonce'] );
		} // End If Statement
		if ( ! wp_verify_nonce( $nonce, 'lesson_add_course_nonce' ) ) {
			die('');
		} // End If Statement
		// Parse POST data
		$data = $_POST['data'];
		$course_data = array();
		parse_str($data, $course_data);
		// Save the Course
		$updated = false;
		$current_user = wp_get_current_user();
		$question_data['post_author'] = $current_user->ID;
		$updated = $this->lesson_save_course($course_data);
		echo $updated;
		die(); // WordPress may print out a spurious zero without this can be particularly bad if using JSON
	} // End lesson_add_course()

	/**
	 * lesson_update_question function.
	 *
	 * @access public
	 * @return void
	 */
	public function lesson_update_question () {
		global $current_user;
		//Add nonce security to the request
		if ( isset($_POST['lesson_update_question_nonce']) ) {
			$nonce = esc_html( $_POST['lesson_update_question_nonce'] );
		} // End If Statement
		if ( ! wp_verify_nonce( $nonce, 'lesson_update_question_nonce' ) ) {
			die('');
		} // End If Statement
		// Parse POST data
		$data = $_POST['data'];
		$question_data = array();
		parse_str($data, $question_data);
		// Save the question
		$updated = false;
		// Question Save and Delete logic
		if ( isset( $question_data['action'] ) && ( $question_data['action'] == 'delete' ) ) {
			// Delete the Question
			$updated = $this->lesson_delete_question($question_data);
		} else {
			// Save the Question
			if ( isset( $question_data[ 'quiz_id' ] ) && ( 0 < absint( $question_data[ 'quiz_id' ] ) ) ) {
				$current_user = wp_get_current_user();
				$question_data['post_author'] = $current_user->ID;
				$updated = $this->lesson_save_question($question_data);
			} // End If Statement
		} // End If Statement
		echo $updated;
		die(); // WordPress may print out a spurious zero without this can be particularly bad if using JSON
	} // End lesson_update_question()

	public function lesson_update_grade_type() {
		//Add nonce security to the request
		if ( isset($_POST['lesson_update_grade_type_nonce']) ) {
			$nonce = esc_html( $_POST['lesson_update_grade_type_nonce'] );
		} // End If Statement
		if ( ! wp_verify_nonce( $nonce, 'lesson_update_grade_type_nonce' ) ) {
			die('');
		} // End If Statement
		// Parse POST data
		$data = $_POST['data'];
		$quiz_data = array();
		parse_str($data, $quiz_data);
		update_post_meta( $quiz_data['quiz_id'], '_quiz_grade_type', $quiz_data['quiz_grade_type'] );
		update_post_meta( $quiz_data['quiz_id'], '_quiz_grade_type_disabled', $quiz_data['quiz_grade_type_disabled'] );
		die();
	}


	/**
	 * lesson_save_course function.
	 *
	 * @access private
	 * @param array $data (default: array())
	 * @return void
	 */
	private function lesson_save_course( $data = array() ) {
		global $current_user;
		$return = false;
		// Setup the course data
		$course_id = 0;
		$course_content = '';
		$course_title = '';
		$course_prerequisite = 0;
		$course_category = 0;
		if ( isset( $data[ 'course_id' ] ) && ( 0 < absint( $data[ 'course_id' ] ) ) ) {
			$course_id = absint( $data[ 'course_id' ] );
		} // End If Statement
		if ( isset( $data[ 'course_title' ] ) && ( '' != $data[ 'course_title' ] ) ) {
			$course_title = $data[ 'course_title' ];
		} // End If Statement
		$post_title = $course_title;
		if ( isset($data[ 'post_author' ]) ) {
			$post_author = $data[ 'post_author' ];
		} else {
			$current_user = wp_get_current_user();
			$post_author = $current_user->ID;
		} // End If Statement
		$post_status = 'publish';
		$post_type = 'course';
		if ( isset( $data[ 'course_content' ] ) && ( '' != $data[ 'course_content' ] ) ) {
			$course_content = $data[ 'course_content' ];
		} // End If Statement
		$post_content = $course_content;
		// Course Query Arguments
		$post_type_args = array(	'post_content' => $post_content,
  		    						'post_status' => $post_status,
  		    						'post_title' => $post_title,
  		    						'post_type' => $post_type
  		    						);
  		// Only save if there is a valid title
  		if ( $post_title != '' ) {
  		    // Check for prerequisite courses & product id
  		    $course_prerequisite_id = absint( $data[ 'course_prerequisite' ] );
  		    $course_woocommerce_product_id = absint( $data[ 'course_woocommerce_product' ] );
  		    $course_category_id = absint( $data[ 'course_category' ] );
  		    if ( 0 == $course_woocommerce_product_id ) { $course_woocommerce_product_id = '-'; }
  		    // Insert or Update the Lesson Quiz
		    if ( 0 < $course_id ) {
		    	$post_type_args[ 'ID' ] = $course_id;
		    	$course_id = wp_update_post($post_type_args);
		    	update_post_meta( $course_id, '_course_prerequisite', $course_prerequisite_id );
		    	update_post_meta( $course_id, '_course_woocommerce_product', $course_woocommerce_product_id );
		    	if ( 0 < $course_category_id ) {
		    		wp_set_object_terms( $course_id, $course_category_id, 'course-category' );
		    	} // End If Statement
		    } else {
		    	$course_id = wp_insert_post($post_type_args);
		    	add_post_meta( $course_id, '_course_prerequisite', $course_prerequisite_id );
		    	add_post_meta( $course_id, '_course_woocommerce_product', $course_woocommerce_product_id );
		    	if ( 0 < $course_category_id ) {
		    		wp_set_object_terms( $course_id, $course_category_id, 'course-category' );
		    	} // End If Statement
		    } // End If Statement
		} // End If Statement
  		// Check that the insert or update saved by testing the post id
  		if ( 0 < $course_id ) {
  			$return = $course_id;
  		} // End If Statement
  		return $return;
  	} // End lesson_save_course()


	/**
	 * lesson_save_question function.
	 *
	 * @access private
	 * @param array $data (default: array())
	 * @return void
	 */
	private function lesson_save_question( $data = array() ) {
		$return = false;
		// Save the Questions
		// Setup the Question data
		$question_id = 0;
		$question_text = '';
		$question_right_answer = '';
		$question_wrong_answers = array();
		$question_type = 'multiple-choice';
		if ( isset( $data[ 'question_id' ] ) && ( 0 < absint( $data[ 'question_id' ] ) ) ) {
			$question_id = absint( $data[ 'question_id' ] );
		} // End If Statement
		if ( isset( $data[ 'question' ] ) && ( '' != $data[ 'question' ] ) ) {
			$question_text = $data[ 'question' ];
		} // End If Statement
		$post_title = $question_text;
		// Handle Default Fields (multiple choice)
		if ( isset( $data[ 'question_right_answer' ] ) && ( '' != $data[ 'question_right_answer' ] ) ) {
			$question_right_answer = $data[ 'question_right_answer' ];
		} // End If Statement
		if ( isset( $data[ 'question_wrong_answers' ] ) && ( '' != $data[ 'question_wrong_answers' ] ) ) {
			$question_wrong_answers = $data[ 'question_wrong_answers' ];
		} // End If Statement
		// Handle Boolean Fields - Edit
		if ( isset( $data[ 'question_' . $question_id . '_right_answer_boolean' ] ) && ( '' != $data[ 'question_' . $question_id . '_right_answer_boolean' ] ) ) {
			$question_right_answer = $data[ 'question_' . $question_id . '_right_answer_boolean' ];
		} // End If Statement
		// Handle Boolean Fields - Add
		if ( isset( $data[ 'question_right_answer_boolean' ] ) && ( '' != $data[ 'question_right_answer_boolean' ] ) ) {
			$question_right_answer = $data[ 'question_right_answer_boolean' ];
		} // End If Statement
		// Handle Gap Fill Fields
		if ( isset( $data[ 'add_question_right_answer_gapfill_pre' ] ) && ( '' != $data[ 'add_question_right_answer_gapfill_pre' ] ) ) {
			$question_right_answer = $data[ 'add_question_right_answer_gapfill_pre' ] . '|' . $data[ 'add_question_right_answer_gapfill_gap' ] . '|' . $data[ 'add_question_right_answer_gapfill_post' ];
		} // End If Statement
		// Handle Essay Fields
		if ( isset( $data[ 'add_question_right_answer_essay' ] ) && ( '' != $data[ 'add_question_right_answer_essay' ] ) ) {
			$question_right_answer = $data[ 'add_question_right_answer_essay' ];
		} // End If Statement
		// Handle Multi Line Fields
		if ( isset( $data[ 'add_question_right_answer_multiline' ] ) && ( '' != $data[ 'add_question_right_answer_multiline' ] ) ) {
			$question_right_answer = $data[ 'add_question_right_answer_multiline' ];
		} // End If Statement
		// Handle Single Line Fields
		if ( isset( $data[ 'add_question_right_answer_singleline' ] ) && ( '' != $data[ 'add_question_right_answer_singleline' ] ) ) {
			$question_right_answer = $data[ 'add_question_right_answer_singleline' ];
		} // End If Statement
		// Handle Question Type
		if ( isset( $data[ 'question_type' ] ) && ( '' != $data[ 'question_type' ] ) ) {
			$question_type = $data[ 'question_type' ];
		} // End If Statement
		$post_title = $question_text;
		$post_author = $data[ 'post_author' ];
		$post_status = 'publish';
		$post_type = 'question';
		$post_content = '';
		// Question Query Arguments
		$post_type_args = array(	'post_content' => $post_content,
  		    						'post_status' => $post_status,
  		    						'post_title' => $post_title,
  		    						'post_type' => $post_type
  		    						);
  		// Remove empty values and reindex the array
  		if ( is_array( $question_wrong_answers ) ) {
  			$question_wrong_answers = array_values( array_filter( $question_wrong_answers, 'strlen' ) );
  		} // End If Statement
  		// Only save if there is a valid title
  		if ( $post_title != '' ) {
  			// Get Quiz ID for the question
  		    $quiz_id = $data[ 'quiz_id' ];
  		    // Insert or Update the Lesson Quiz
  		    if ( 0 < $question_id ) {
		    	$post_type_args[ 'ID' ] = $question_id;
		    	$question_id = wp_update_post($post_type_args);
		    	update_post_meta( $question_id, '_quiz_id', $quiz_id );
		    	update_post_meta( $question_id, '_question_right_answer', $question_right_answer );
		    	update_post_meta( $question_id, '_question_wrong_answers', $question_wrong_answers );
		    } else {
				$question_id = wp_insert_post($post_type_args);
		    	add_post_meta( $question_id, '_quiz_id', $quiz_id );
		    	add_post_meta( $question_id, '_question_right_answer', $question_right_answer );
		    	add_post_meta( $question_id, '_question_wrong_answers', $question_wrong_answers );
		    	// Set the post terms for question-type
			    wp_set_post_terms( $question_id, array( $question_type ), 'question-type' ); // EXTENSIONS
		    } // End If Statement
		} // End If Statement
  		// Check that the insert or update saved by testing the post id
  		if ( 0 < $question_id ) {
  			$return = $question_id;
  		} // End If Statement
  		return $return;
  	} // End lesson_question_save()


	/**
	 * lesson_delete_question function.
	 *
	 * @access private
	 * @param array $data (default: array())
	 * @return void
	 */
	private function lesson_delete_question( $data = array() ) {
		$return = false;
		// Get which question to delete
		$question_id = 0;
		if ( isset( $data[ 'question_id' ] ) && ( 0 < absint( $data[ 'question_id' ] ) ) ) {
			$question_id = absint( $data[ 'question_id' ] );
		} // End If Statement
		// Delete the question
		if ( 0 < $question_id ) {
			$deleted = wp_delete_post( $question_id, true ); // 2nd param forces delete even from the trash, returns false if it fails
			// Check if it deleted successfully
			if ( $deleted ) {
				$return = true;
			} // End If Statement
		} // End If Statement
		return $return;
	} // End lesson_delete_question()


	/**
	 * lesson_complexities function.
	 *
	 * @access public
	 * @return void
	 */
	public function lesson_complexities() {

		// V2 - make filter for this array
		$complexity_array = array( 	'easy' => __( 'Easy', 'woothemes-sensei' ),
									'std' => __( 'Standard', 'woothemes-sensei' ),
									'hard' => __( 'Hard', 'woothemes-sensei' )
									);

		return $complexity_array;

	} // End lesson_complexities


	/**
	 * lesson_count function.
	 *
	 * @access public
	 * @param string $post_status (default: 'publish')
	 * @return void
	 */
	public function lesson_count( $post_status = 'publish' ) {

		$posts_array = array();

		$post_args = array(	'post_type' 		=> 'lesson',
							'numberposts' 		=> -1,
							'orderby'         	=> 'menu_order',
    						'order'           	=> 'ASC',
    						'meta_key'        	=> '_lesson_course',
    						'meta_value_num'   	=> 0,
    						'meta_compare'		=> '>=',
    						'post_status'       => $post_status,
							'suppress_filters' 	=> 0
							);

		$posts_array = get_posts( $post_args );

		return intval( count( $posts_array ) );

	} // End lesson_count()


	/**
	 * lesson_quizzes function.
	 *
	 * @access public
	 * @param int $lesson_id (default: 0)
	 * @param string $post_status (default: 'publish')
	 * @return void
	 */
	public function lesson_quizzes( $lesson_id = 0, $post_status = 'publish' ) {

		// V2 - refactor into post types class for further use
		$posts_array = array();

		$post_args = array(	'post_type' 		=> 'quiz',
							'numberposts' 		=> -1,
							'orderby'         	=> 'title',
    						'order'           	=> 'DESC',
    						'meta_key'        	=> '_quiz_lesson',
    						'meta_value'      	=> $lesson_id,
    						'post_status'		=> $post_status,
							'suppress_filters' 	=> 0
							);
		$posts_array = get_posts( $post_args );

		return $posts_array;

	} // End lesson_quizzes()


	/**
	 * lesson_quiz_questions function.
	 *
	 * @access public
	 * @param int $quiz_id (default: 0)
	 * @param string $post_status (default: 'publish')
	 * @return void
	 */
	public function lesson_quiz_questions( $quiz_id = 0, $post_status = 'publish', $orderby = 'ID', $order = 'ASC' ) {

		$posts_array = array();

		$post_args = array(	'post_type' 		=> 'question',
							'numberposts' 		=> -1,
							'orderby'         	=> $orderby,
    						'order'           	=> $order,
    						'meta_key'        	=> '_quiz_id',
    						'meta_value'      	=> $quiz_id,
    						'post_status'		=> $post_status,
							'suppress_filters' 	=> 0
							);
		$posts_array = get_posts( $post_args );

		return $posts_array;

	} // End lesson_quiz_questions()


	/**
	 * lesson_image function.
	 *
	 * Handles output of the lesson image
	 *
	 * @access public
	 * @param int $lesson_id (default: 0)
	 * @param string $width (default: '100')
	 * @param string $height (default: '100')
	 * @return void
	 */
	public function lesson_image( $lesson_id = 0, $width = '100', $height = '100' ) {

		global $woothemes_sensei;

		$html = '';

		// Get Width and Height settings
		if ( ( $width == '100' ) && ( $height == '100' ) ) {
			if ( is_singular( 'lesson' ) ) {
				if ( !$woothemes_sensei->settings->settings[ 'lesson_single_image_enable' ] ) {
					return '';
				} // End If Statement
				$width = $woothemes_sensei->settings->settings[ 'lesson_single_image_width' ];
				$height = $woothemes_sensei->settings->settings[ 'lesson_single_image_height' ];
			} else {
				if ( !$woothemes_sensei->settings->settings[ 'course_lesson_image_enable' ] ) {
					return '';
				} // End If Statement
				$width = $woothemes_sensei->settings->settings[ 'lesson_archive_image_width' ];
				$height = $woothemes_sensei->settings->settings[ 'lesson_archive_image_height' ];
			} // End If Statement
		} // End If Statement

		if ( has_post_thumbnail( $lesson_id ) ) {
   			// Get Featured Image
   			$img_url = get_the_post_thumbnail( $lesson_id, array( $width, $height ), array( 'class' => 'woo-image thumbnail alignleft') );
 		} else {
 			// Display Image Placeholder if none
			if ( $woothemes_sensei->settings->settings[ 'placeholder_images_enable' ] ) {
				$img_url = apply_filters( 'sensei_lesson_placeholder_image_url', '<img src="http://placehold.it/' . $width . 'x' . $height . '" class="woo-image thumbnail alignleft" />' );
			} // End If Statement
		} // End If Statement
		$html .= '<a href="' . get_permalink( $lesson_id ) . '" title="' . esc_attr( get_post_field( 'post_title', $lesson_id ) ) . '">' . $img_url . '</a>';

		return $html;

	} // End lesson_image()

} // End Class
?>