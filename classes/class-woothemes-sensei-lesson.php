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
			add_action( 'admin_menu', array( $this, 'meta_box_setup' ), 20 );
			add_action( 'save_post', array( $this, 'meta_box_save' ) );
			add_action( 'save_post', array( $this, 'post_updated' ) );
			// Custom Write Panel Columns
			add_filter( 'manage_edit-lesson_columns', array( $this, 'add_column_headings' ), 10, 1 );
			add_action( 'manage_posts_custom_column', array( $this, 'add_column_data' ), 10, 2 );
			// Ajax functions
			add_action( 'wp_ajax_lesson_update_question', array( $this, 'lesson_update_question' ) );
			add_action( 'wp_ajax_nopriv_lesson_update_question', array( $this, 'lesson_update_question' ) );
			add_action( 'wp_ajax_lesson_add_course', array( $this, 'lesson_add_course' ) );
			add_action( 'wp_ajax_nopriv_lesson_add_course', array( $this, 'lesson_add_course' ) );
			add_action( 'wp_ajax_lesson_update_grade_type', array( $this, 'lesson_update_grade_type' ) );
			add_action( 'wp_ajax_nopriv_lesson_update_grade_type', array( $this, 'lesson_update_grade_type' ) );
			add_action( 'wp_ajax_lesson_update_question_order', array( $this, 'lesson_update_question_order' ) );
			add_action( 'wp_ajax_nopriv_lesson_update_question_order', array( $this, 'lesson_update_question_order' ) );
			add_action( 'wp_ajax_question_get_answer_id', array( $this, 'question_get_answer_id' ) );
			add_action( 'wp_ajax_nopriv_question_get_answer_id', array( $this, 'question_get_answer_id' ) );
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
		add_meta_box( 'lesson-prerequisite', __( 'Lesson Prerequisite', 'woothemes-sensei' ), array( $this, 'lesson_prerequisite_meta_box_content' ), $this->token, 'side', 'default' );
		// Add Meta Box for Lesson Course
		add_meta_box( 'lesson-course', __( 'Lesson Course', 'woothemes-sensei' ), array( $this, 'lesson_course_meta_box_content' ), $this->token, 'side', 'default' );
		// Add Meta Box for Lesson Quiz information
		add_meta_box( 'lesson-info', __( 'Lesson Information', 'woothemes-sensei' ), array( $this, 'lesson_info_meta_box_content' ), $this->token, 'normal', 'default' );
		// Add Meta Box for Lesson Quiz Questions
		add_meta_box( 'lesson-quiz', __( 'Lesson Quiz', 'woothemes-sensei' ), array( $this, 'lesson_quiz_meta_box_content' ), $this->token, 'normal', 'default' );
		// Remove "Custom Settings" meta box.
		remove_meta_box( 'woothemes-settings', $this->token, 'normal' );
		// Add JS scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		// Add CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
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
		$html .= '<p><label for="lesson_length">' . __( 'Lesson Length in minutes', 'woothemes-sensei' ) . ': </label>';
		$html .= '<input type="number" id="lesson-length" name="lesson_length" class="small-text" value="' . esc_attr( $lesson_length ) . '" /></p>' . "\n";
		// Lesson Complexity
		$html .= '<p><label for="lesson_complexity">' . __( 'Lesson Complexity', 'woothemes-sensei' ) . ': </label>';
		$html .= '<select id="lesson-complexity-options" name="lesson_complexity" class="lesson-complexity-select">';
			$html .= '<option value="">' . __( 'None', 'woothemes-sensei' ) . '</option>';
			foreach ($complexity_array as $key => $value){
				$html .= '<option value="' . esc_attr( $key ) . '"' . selected( $key, $lesson_complexity, false ) . '>' . esc_html( $value ) . '</option>' . "\n";
			} // End For Loop
		$html .= '</select></p>' . "\n";

		$html .= '<p><label for="lesson_video_embed">' . __( 'Video Embed Code', 'woothemes-sensei' ) . ':</label><br/>' . "\n";
		$html .= '<textarea rows="5" cols="50" name="lesson_video_embed" tabindex="6" id="course-video-embed">' . $lesson_video_embed . '</textarea></p>' . "\n";

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
   		remove_action('save_post', array($this, __FUNCTION__));
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
    	add_action('save_post', array($this, __FUNCTION__));
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

							$product_args = array(	'post_type' 		=> array( 'product', 'product_variation' ),
													'numberposts' 		=> -1,
													'orderby'         	=> 'title',
	    											'order'           	=> 'DESC',
	    											'post_status'		=> array( 'publish', 'private', 'draft' ),
	    											'tax_query'			=> array(
														array(
															'taxonomy'	=> 'product_type',
															'field'		=> 'slug',
															'terms'		=> array( 'variable', 'grouped' ),
															'operator'	=> 'NOT IN'
														)
													),
	    											'suppress_filters' 	=> 0
													);
							$products_array = get_posts( $product_args );
							$html .= '<label>' . __( 'WooCommerce Product' , 'woothemes-sensei' ) . '</label> ';
	  						$html .= '<select id="course-woocommerce-product-options" name="course_woocommerce_product" class="widefat">' . "\n";
								$html .= '<option value="-">' . __( 'None', 'woothemes-sensei' ) . '</option>';
								$prev_parent_id = 0;
								foreach ($products_array as $products_item){

									if ( 'product_variation' == $products_item->post_type ) {
										$product_object = get_product( $products_item->ID );
										$parent_id = wp_get_post_parent_id( $products_item->ID );
										$product_name = ucwords( woocommerce_get_formatted_variation( $product_object->variation_data, true ) );
									} else {
										$parent_id = false;
										$prev_parent_id = 0;
										$product_name = $products_item->post_title;
									}

									// Show variations in groups
									if( $parent_id && $parent_id != $prev_parent_id ) {
										if( 0 != $prev_parent_id ) {
											$html .= '</optgroup>';
										}
										$html .= '<optgroup label="' . get_the_title( $parent_id ) . '">';
										$prev_parent_id = $parent_id;
									} elseif( ! $parent_id && 0 == $prev_parent_id ) {
										$html .= '</optgroup>';
									}

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

	public function quiz_panel( $quiz_id = 0 ) {

		$html = '<input type="hidden" name="' . esc_attr( 'woo_' . $this->token . '_noonce' ) . '" id="' . esc_attr( 'woo_' . $this->token . '_noonce' ) . '" value="' . esc_attr( wp_create_nonce( plugin_basename(__FILE__) ) ) . '" />';
		$html .= '<div id="add-quiz-main">';
		if ( 0 == $quiz_id ) {
			$html .= '<p>';
				// Default message and Add a Quiz button
				$html .= esc_html( __( 'There is no quiz for this lesson yet. Please add one.', 'woothemes-sensei' ) );
				$html .= '<button type="button" class="button button-highlighted add_quiz">' . esc_html( __( 'Add', 'woothemes-sensei' ) )  . '</button>';
			$html .= '</p>';
		}

		$lesson_quiz_passmark = 0;
		$quiz_grade_type_disabled = false;
		$quiz_grade_type = 'auto';
		if( $quiz_id ) {
			$lesson_quiz_passmark = get_post_meta( $quiz_id, '_quiz_passmark', true );
			$quiz_grade_type_disabled = get_post_meta( $quiz_id, '_quiz_grade_type_disabled', true );
			if( $quiz_grade_type_disabled == 'disabled' ) {
				$quiz_grade_type = 'manual';
			} else {
				$quiz_grade_type = get_post_meta( $quiz_id, '_quiz_grade_type', true );
			}
		}

		// Quiz Panel CSS Class
		$quiz_class = '';
		if ( 0 == $quiz_id ) {
			$quiz_class = ' class="hidden"';
		} // End If Statement
		// Build the HTML to Output
		$message_class = '';

		// Inner DIV
		$html .= '<div id="add-quiz-metadata"' . $quiz_class . '>';

			// Quiz Meta data
			$html .= '<p>';

				// Quiz Pass Percentage
				$html .= '<input type="hidden" name="quiz_id" id="quiz_id" value="' . esc_attr( $quiz_id ) . '" />';
				$html .= '<label for="quiz_passmark">' . __( 'Quiz passmark percentage' , 'woothemes-sensei' ) . '</label> ';
					$html .= '<input type="number" id="quiz_passmark" name="quiz_passmark" value="' . esc_attr( $lesson_quiz_passmark ) . '" class="small-text" /> ';

					// Quiz grade type
					$html .= '<input type="hidden" id="quiz_grade_type_disabled" name="quiz_grade_type_disabled" value="' . esc_attr( $quiz_grade_type_disabled ) . '" /> ';
					$html .= '<input type="checkbox" id="quiz_grade_type" name="quiz_grade_type"' . checked( $quiz_grade_type, 'auto', false ) . ' ' . disabled( $quiz_grade_type_disabled, 'disabled', false ) . ' /> ';
					$html .= '<label class="grade-label" for="quiz_grade_type">' . __( 'Grade quiz automatically', 'woothemes-sensei' ) . '</label>';

			$html .= '</p>';

			// Default Message
			if ( 0 == $quiz_id ) {
				$html .= '<p class="save-note">';
					$html .= esc_html( __( 'Please save your lesson in order to add questions to your quiz.', 'woothemes-sensei' ) );
				$html .= '</p>';
			} // End If Statement

		$html .= '</div>';

		// Question Container DIV
		$html .= '<div id="add-question-main"' . $quiz_class . '>';
			// Inner DIV
			$html .= '<div id="add-question-metadata">';

				// Setup Questions Query
				$questions = array();
				if ( 0 < $quiz_id ) {
					$questions = $this->lesson_quiz_questions( $quiz_id );
				} // End If Statement

				$question_count = count( $questions );

				$this->question_order = '';

				// Count of questions
				$html .= '<input type="hidden" name="question_counter" id="question_counter" value="' . esc_attr( $question_count ) . '" />';
				// Table headers
				$html .= '<table class="widefat" id="sortable-questions">
							<thead>
							    <tr>
							        <th class="question-count-column">#</th>
							        <th>' . __( 'Question', 'woothemes-sensei' ) . '</th>
							        <th style="width:45px;">' . __( 'Grade', 'woothemes-sensei' ) . '</th>
							        <th style="width:125px;">' . __( 'Type', 'woothemes-sensei' ) . '</th>
							        <th style="width:125px;">' . __( 'Action', 'woothemes-sensei' ) . '</th>
							    </tr>
							</thead>
							<tfoot>
							    <tr>
								    <th class="question-count-column">#</th>
								    <th>' . __( 'Question', 'woothemes-sensei' ) . '</th>
								    <th>' . __( 'Grade', 'woothemes-sensei' ) . '</th>
								    <th>' . __( 'Type', 'woothemes-sensei' ) . '</th>
								    <th>' . __( 'Action', 'woothemes-sensei' ) . '</th>
							    </tr>
							</tfoot>';

				$message_class = '';
				if ( 0 < $question_count ) { $message_class = 'hidden'; }

				$html .= '<tbody id="no-questions-message" class="' . esc_attr( $message_class ) . '">';
					$html .= '<tr>';
						$html .= '<td colspan="5">' . __( 'There are no Questions for this Quiz yet. Please add some below.', 'woothemes-sensei' ) . '</td>';
					$html .= '</tr>';
				$html .= '</tbody>';

				if( 0 < $question_count ) {
					$html .= $this->quiz_panel_questions( $questions );
				}

				$html .= '</table>';

				$html .= '<input type="hidden" id="question-order" name="question-order" value="' . $this->question_order . '" />';

			$html .= '</div>';

			// Question Action Container DIV
			$html .= '<div id="add-question-actions">';
				// Question Actions panel
				$html .= '<p>';
					// Add question action button
					$html .= '<button type="button" class="button button-highlighted add_question_answer">' . esc_html( __( 'Add A Question', 'woothemes-sensei' ) )  . '</button>';
				$html .= '</p>';

				$html .= $this->quiz_panel_question();

			$html .= '</div>';

		$html .= '</div>';

		return $html;

	}

	public function quiz_panel_questions( $questions = array() ) {

		$html = '';

		if( count( $questions ) > 0 ) {

			$question_class = '';
			$question_counter = 1;

			foreach ( $questions as $question ) {

				$question_id = $question->ID;

				$question_type = '';
				$question_types = wp_get_post_terms( $question_id, 'question-type', array( 'fields' => 'names' ) );
				if ( isset( $question_types[0] ) && '' != $question_types[0] ) {
					$question_type = $question_types[0];
				} // End If Statement

				if( ! $question_type ) {
					$question_type = 'multiple-choice';
				}

				// Row with question and actions
				$html .= $this->quiz_panel_question( $question_type, $question_counter, $question_id );
				$question_counter++;

				if( strlen( $this->question_order ) > 0 ) { $this->question_order .= ','; }
				$this->question_order .= $question_id;
			} // End For Loop
		}

		return $html;

	}

	public function quiz_panel_question( $question_type = '', $question_counter = 0, $question_id = 0 ) {

		$html = '';

		$question_class = '';
		if( $question_counter && $question_counter % 2 ) { $question_class = 'alternate'; }

		if( $question_id ) {

			$question_grade = intval( get_post_meta( $question_id, '_question_grade', true ) );
			if( 0 == $question_grade ) { $question_grade = 1; }

			$question_media = get_post_meta( $question_id, '_question_media', true );
			$question_media_type = $question_media_thumb = $question_media_link = $question_media_filename = '';
			$question_media_thumb_class = $question_media_link_class = $question_media_delete_class = 'hidden';
			if( 0 < intval( $question_media ) ) {
				$mimetype = get_post_mime_type( $question_media );
				if( $mimetype ) {
					$mimetype_array = explode( '/', $mimetype);
					if( isset( $mimetype_array[0] ) && $mimetype_array[0] ) {
						$question_media_delete_class = '';
						$question_media_type = $mimetype_array[0];
						if( 'image' == $question_media_type ) {
							$question_media_thumb = wp_get_attachment_thumb_url( $question_media );
							if( $question_media_thumb ) {
								$question_media_thumb_class = '';
							}
						}
						$question_media_url = wp_get_attachment_url( $question_media );
						if( $question_media_url ) {
							$question_media_filename = basename( $question_media_url );
							$question_media_link = '<a href="' . esc_url( $question_media_url ) . '" target="_blank">' . $question_media_filename . '</a>';
							$question_media_link_class = '';
						}
					}
				}
			}

			$random_order = get_post_meta( $question_id, '_random_order', true );
			if( ! $random_order ) {
				$random_order = 'yes';
			}

			if( ! $question_type ) { $question_type = 'multiple-choice'; }

			$html .= '<tbody class="' . $question_class . '">';

				$html .= '<tr>';
					$html .= '<td class="table-count question-number question-count-column">' . $question_counter . '</td>';
					$html .= '<td>' . esc_html( stripslashes( get_the_title( $question_id ) ) ) . '</td>';
					$html .= '<td class="question-grade-column">' . esc_html( $question_grade ) . '</td>';
					$question_types_filtered = ucwords( str_replace( array( '-', 'boolean' ), array( ' ', 'True/False' ), $question_type ) );
					$html .= '<td>' . esc_html( $question_types_filtered ) . '</td>';
					$html .= '<td><a title="' . esc_attr( __( 'Edit Question', 'woothemes-sensei' ) ) . '" href="#question_' . $question_counter .'" class="question_table_edit">' . esc_html( __( 'Edit', 'woothemes-sensei' ) ) . '</a> <a title="' . esc_attr( __( 'Delete Question', 'woothemes-sensei' ) ) . '" href="#add-question-metadata" class="question_table_delete">' . esc_html( __( 'Delete', 'woothemes-sensei' ) ) . '</a></td>';
				$html .= '</tr>';

				$html .= '<tr class="question-quick-edit hidden">';
					$html .= '<td colspan="5">';
						$html .= '<span class="hidden question_original_counter">' . $question_counter . '</span>';
				    	$html .= '<div class="question_required_fields">';

					    	// Question title
					    	$html .= '<div>';
						    	$html .= '<label for="question_' . $question_counter . '">' . __( 'Question:', 'woothemes-sensei' ) . '</label> ';
						    	$html .= '<input type="text" id="question_' . $question_counter . '" name="question" value="' . esc_attr( stripslashes( get_the_title( $question_id ) ) ) . '" size="25" class="widefat" />';
					    	$html .= '</div>';

					    	// Question grade
					    	$html .= '<div>';
						    	$html .= '<label for="question_' . $question_counter . '_grade">' . __( 'Question grade:', 'woothemes-sensei' ) . '</label> ';
						    	$html .= '<input type="number" id="question_' . $question_counter . '_grade" class="question_grade small-text" name="question_grade" min="1" value="' . $question_grade . '" />';
					    	$html .= '</div>';

					    	// Random order
					    	if( $question_type == 'multiple-choice' ) {
					    		$html .= '<div>';
					    			$html .= '<label for="' . $question_counter . '_random_order"><input type="checkbox" name="random_order" class="random_order" id="' . $question_counter . '_random_order" value="yes" ' . checked( $random_order, 'yes', false ) . ' /> ' . __( 'Randomise answer order', 'woothemes-sensei' ) . '</label>';
					    		$html .= '</div>';
					    	}

					    	// Question media
					    	$html .= '<div>';
						    	$html .= '<label for="question_' . $question_counter . '_media_button">' . __( 'Question media:', 'woothemes-sensei' ) . '</label><br/>';
						    	$html .= '<button id="question_' . $question_counter . '_media_button" class="upload_media_file_button button-secondary" data-uploader_title="' . __( 'Upload file to question', 'woothemes-sensei' ) . '" data-uploader_button_text="' . __( 'Add to question', 'woothemes-sensei' ) . '">' . __( 'Upload file', 'woothemes-sensei' ) . '</button>';
						    	$html .= '<button id="question_' . $question_counter . '_media_button_delete" class="delete_media_file_button button-secondary ' . $question_media_delete_class . '">' . __( 'Delete file', 'woothemes-sensei' ) . '</button>';
						    	$html .= '<span id="question_' . $question_counter . '_media_link" class="question_media_link ' . $question_media_link_class . ' ' . $question_media_type . '">' . $question_media_link . '</span>';
						    	$html .= '<br/><img id="question_' . $question_counter . '_media_preview" class="question_media_preview ' . $question_media_thumb_class . '" src="' . $question_media_thumb . '" /><br/>';
						    	$html .= '<input type="hidden" id="question_' . $question_counter . '_media" class="question_media" name="question_media" value="' . $question_media . '" />';
					    	$html .= '</div>';

					    $html .= '</div>';

					    $html .= $this->quiz_panel_question_field( $question_type, $question_id, $question_counter );

					    $html .= '<input type="hidden" id="question_' . $question_counter . '_question_type" class="question_type" name="question_type" value="' . $question_type . '" />';
						$html .= '<input type="hidden" name="question_id" class="row_question_id" id="question_' . $question_counter . '_id" value="' . $question_id . '" />';

			    		$html .= '<div class="update-question">';
				    		$html .= '<a href="#question-edit-cancel" class="lesson_question_cancel" title="' . esc_attr( __( 'Cancel', 'woothemes-sensei' ) ) . '">' . __( 'Cancel', 'woothemes-sensei' ) . '</a> ';
				    		$html .= '<a title="' . esc_attr( __( 'Update Question', 'woothemes-sensei' ) ) . '" href="#add-question-metadata" class="question_table_save button button-highlighted">' . esc_html( __( 'Update', 'woothemes-sensei' ) ) . '</a>';
			    		$html .= '</div>';

		    		$html .= '</td>';
				$html .= '</tr>';

			$html .= '</tbody>';

		} else {
			$html .= '<div id="add-new-question" class="hidden">';
		    	$html .= '<h2>' . __( 'New Question'  , 'woothemes-sensei' ) . '</h2>';

				$html .= '<div class="question">';
					$html .= '<div class="question_required_fields">';

						// Question type
						$html .= '<label>' . __( 'Question Type' , 'woothemes-sensei' ) . '</label> ';
						$html .= '<select id="add-question-type-options" name="question_type" class="widefat question-type-select">' . "\n";
							$question_types = WooThemes_Sensei_Question::question_types();
							foreach ( $question_types as $type => $label ) {
								$html .= '<option value="' . esc_attr( $type ) . '">' . esc_html( $label ) . '</option>' . "\n";
							} // End For Loop
						$html .= '</select>' . "\n";

						// Question title
						$html .= '<p><label>' . __( 'Question:'  , 'woothemes-sensei' ) . '</label> ';
	  					$html .= '<input type="text" id="add_question" name="question" value="" size="25" class="widefat" /></p>';

	  					// Question grade
						$html .= '<p><label>' . __( 'Question Grade:'  , 'woothemes-sensei' ) . '</label> ';
						$html .= '<input type="number" id="add-question-grade" name="question_grade" class="small-text" min="1" value="1" /></p>' . "\n";

						// Random order
						$html .= '<p class="add_question_random_order">';
			    			$html .= '<label for="add_random_order"><input type="checkbox" name="random_order" class="random_order" id="add_random_order" value="yes" checked="checked" /> ' . __( 'Randomise answer order', 'woothemes-sensei' ) . '</label>';
			    		$html .= '</p>';

			    		// Question media
						$html .= '<p>';
					    	$html .= '<label for="question_add_new_media_button">' . __( 'Question media:', 'woothemes-sensei' ) . '</label><br/>';
					    	$html .= '<button id="question_add_new_media_button" class="upload_media_file_button button-secondary" data-uploader_title="' . __( 'Upload file to question', 'woothemes-sensei' ) . '" data-uploader_button_text="' . __( 'Add to question', 'woothemes-sensei' ) . '">' . __( 'Upload file', 'woothemes-sensei' ) . '</button>';
					    	$html .= '<button id="question_add_new_media_button_delete" class="delete_media_file_button button-secondary hidden">' . __( 'Delete file', 'woothemes-sensei' ) . '</button>';
					    	$html .= '<span id="question_add_new_media_link" class="question_media_link hidden"></span>';
					    	$html .= '<br/><img id="question_add_new_media_preview" class="question_media_preview hidden" src="" /><br/>';
					    	$html .= '<input type="hidden" id="question_add_new_media" class="question_media" name="question_media" value="" />';
				    	$html .= '</p>';

					$html .= '</div>';
				$html .= '</div>';

				foreach ( $question_types as $type => $label ) {
					$html .= $this->quiz_panel_question_field( $type );
				}

				$html .= '<div class="add-question">';
		    		$html .= '<a href="#question-add-cancel" class="lesson_question_cancel">' . __( 'Cancel', 'woothemes-sensei' ) . '</a> ';
		    		$html .= '<a title="' . esc_attr( __( 'Add Question', 'woothemes-sensei' ) ) . '" href="#add-question-metadata" class="add_question_save button button-highlighted">' . esc_html( __( 'Add Question', 'woothemes-sensei' ) ) . '</a>';
	    		$html .= '</div>';
		}

		return $html;
	}

	public function quiz_panel_question_field( $question_type = '', $question_id = 0, $question_counter = 0 ) {

		$html = '';

		if( $question_type ) {

			$right_answer = '';
			$wrong_answers = array();
			$answer_order_string = '';
			$answer_order = array();
			if( $question_id ) {
				$right_answer = get_post_meta( $question_id, '_question_right_answer', true);
				$wrong_answers = get_post_meta( $question_id, '_question_wrong_answers', true);
				$answer_order_string = get_post_meta( $question_id, '_answer_order', true );
				$answer_order = array_filter( explode( ',', $answer_order_string ) );
				$question_class = '';
			} else {
				$question_id = '';
				$question_class = 'answer-fields question_required_fields hidden';
			}

			switch ( $question_type ) {
				case 'multiple-choice':
					$html .= '<div class="question_default_fields multiple-choice-answers ' . str_replace( ' hidden', '', $question_class ) . '">';

						$right_answer_id = $this->get_answer_id( $right_answer );

						// Right Answer
				    	$right_answer = '<label for="question_' . $question_counter . '_right_answer"><span>' . __( 'Right:' , 'woothemes-sensei' ) . '</span> <input rel="' . esc_attr( $right_answer_id ) . '" type="text" id="question_' . $question_counter . '_right_answer" name="question_right_answer" value="' . esc_attr( stripslashes( $right_answer ) ) . '" size="25" class="question_answer widefat" /></label>';
				    	if( $question_id ) {
				    		$answers[ $right_answer_id ] = $right_answer;
				    	} else {
				    		$answers[] = $right_answer;
				    	}

				    	// Calculate total wrong answers available (defaults to 4)
				    	$total_wrong = 0;
				    	if( $question_id ) {
				    		$total_wrong = get_post_meta( $question_id, '_wrong_answer_count', true );
				    	}
				    	if( 0 == intval( $total_wrong ) ) {
				    		$total_wrong = 4;
				    	}

					    // Setup Wrong Answer HTML
				    	for ( $i = 0; $i < $total_wrong; $i++ ) {
				    		if ( !isset( $wrong_answers[ $i ] ) ) { $wrong_answers[ $i ] = ''; }
				    		$answer_id = $this->get_answer_id( $wrong_answers[ $i ] );
				    		$wrong_answer = '<label for="question_' . $question_counter . '_wrong_answer_' . $i . '"><span>' . __( 'Wrong:' , 'woothemes-sensei' ) . '</span> <input rel="' . esc_attr( $answer_id ) . '" type="text" id="question_' . $question_counter . '_wrong_answer_' . $i . '" name="question_wrong_answers[]" value="' . esc_attr( stripslashes( $wrong_answers[ $i ] ) ) . '" size="25" class="question_answer widefat" /> <a class="remove_answer_option"></a></label>';
				    		if( $question_id ) {
					    		$answers[ $answer_id ] = $wrong_answer;
					    	} else {
					    		$answers[] = $wrong_answer;
					    	}
				    	}

				    	$answers_sorted = $answers;
				    	if( $question_id && count( $answer_order ) > 0 ) {
				    		$answers_sorted = array();
				    		foreach( $answer_order as $answer_id ) {
				    			if( $answers[ $answer_id ] ) {
				    				$answers_sorted[ $answer_id ] = $answers[ $answer_id ];
				    				unset( $answers[ $answer_id ] );
				    			}
				    		}

				    		if( count( $answers ) > 0 ) {
						    	foreach( $answers as $id => $answer ) {
						    		$answers_sorted[ $id ] = $answer;
						    	}
						    }
				    	}

				    	foreach( $answers_sorted as $id => $answer ) {
				    		$html .= $answer;
				    	}

				    	$html .= '<input type="hidden" class="answer_order" name="answer_order" value="' . $answer_order_string . '" />';
				    	$html .= '<span class="hidden wrong_answer_count">' . $total_wrong . '</span>';

				    	$html .= '<a class="add_answer_option" rel="' . $question_counter . '">' . __( 'Add answer', 'woothemes-sensei' ) . '</a>';

				    	$html .= '</div>';
				break;
				case 'boolean':
					$html .= '<div class="question_boolean_fields ' . $question_class . '">';
						if( $question_id ) {
							$field_name = 'question_' . $question_id . '_right_answer_boolean';
						} else {
							$field_name = 'question_right_answer_boolean';
							$right_answer = 'true';
						}
						$html .= '<label for="question_' . $question_id . '_boolean_true"><input id="question_' . $question_id . '_boolean_true" type="radio" name="' . $field_name . '" value="true" '. checked( $right_answer, 'true', false ) . ' /> ' . __( 'True', 'woothemes-sensei' ) . '</label>';
						$html .= '<label for="question_' . $question_id . '_boolean_false"><input id="question_' . $question_id . '_boolean_false" type="radio" name="' . $field_name . '" value="false" '. checked( $right_answer, 'false', false ) . ' /> ' . __( 'False', 'woothemes-sensei' ) . '</label>';
					$html .= '</div>';
				break;
				case 'gap-fill':
					$gapfill_array = explode( '|', $right_answer );
					if ( isset( $gapfill_array[0] ) ) { $gapfill_pre = $gapfill_array[0]; } else { $gapfill_pre = ''; }
					if ( isset( $gapfill_array[1] ) ) { $gapfill_gap = $gapfill_array[1]; } else { $gapfill_gap = ''; }
					if ( isset( $gapfill_array[2] ) ) { $gapfill_post = $gapfill_array[2]; } else { $gapfill_post = ''; }
					$html .= '<div class="question_gapfill_fields ' . $question_class . '">';
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
					$html .= '<div class="question_essay_fields ' . $question_class . '">';
							// Guides for grading
						$html .= '<label>' . __( 'Guide/Teacher Notes for grading the Essay' , 'woothemes-sensei' ) . '</label> ';
						$html .= '<textarea id="question_' . $question_counter . '_add_question_right_answer_essay" name="add_question_right_answer_essay" rows="15" cols="40" class="widefat">' . $right_answer . '</textarea>';
					$html .= '</div>';
				break;
				case 'multi-line':
					$html .= '<div class="question_multiline_fields ' . $question_class . '">';
						// Guides for grading
						if( $question_counter ) {
							$field_id = 'question_' . $question_counter . '_add_question_right_answer_multiline';
						} else {
							$field_id = 'add_question_right_answer_multiline';
						}
						$html .= '<label>' . __( 'Guide/Teacher Notes for grading the answer' , 'woothemes-sensei' ) . '</label> ';
						$html .= '<textarea id="' . $field_id . '" name="add_question_right_answer_multiline" rows="3" cols="40" class="widefat">' . $right_answer . '</textarea>';
					$html .= '</div>';
				break;
				case 'single-line':
					$html .= '<div class="question_singleline_fields ' . $question_class . '">';
						// Recommended Answer
						$html .= '<label>' . __( 'Recommended Answer' , 'woothemes-sensei' ) . '</label> ';
						$html .= '<input type="text" id="question_' . $question_counter . '_add_question_right_answer_singleline" name="add_question_right_answer_singleline" value="' . $right_answer . '" size="25" class="widefat" />';
					$html .= '</div>';
				break;
			}
		}

		return $html;
	}

	public function question_get_answer_id() {
		$data = $_POST['data'];
		$answer_data = array();
		parse_str( $data, $answer_data );
		$answer = $answer_data['answer_value'];
		$answer_id = $this->get_answer_id( $answer );
		echo $answer_id;
		die();
	}

	public function get_answer_id( $answer = '' ) {

		$answer_id = '';

		if( $answer ) {
			$answer_id = strtolower( str_replace( array( ',', ' ', '-', '&', '\'', '"', '`', '?', ':', ';', '!', '<', '>', '/', '.' ), '', stripslashes( $answer ) ) );
		}

		return $answer_id;

	}

	/**
	 * lesson_quiz_meta_box_content function.
	 *
	 * @access public
	 * @return void
	 */
	public function lesson_quiz_meta_box_content () {
		global $post;

		// Get quiz panel
		$quiz_id = 0;
		$quizzes = array();
		if ( 0 < $post->ID ) {
			$quizzes = $this->lesson_quizzes( $post->ID, 'any' );
		}

		if ( $quizzes ) {
			foreach ( $quizzes as $quiz ) {
				$quiz_id = $quiz->ID;
				break;
			}
		}

		echo $this->quiz_panel( $quiz_id );

	} // End lesson_quiz_meta_box_content()


	/**
	 * enqueue_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
		global $woothemes_sensei, $post_type;

		$allowed_post_types = apply_filters( 'sensei_scripts_allowed_post_types', array( 'lesson', 'course' ) );
		$allowed_post_type_pages = apply_filters( 'sensei_scripts_allowed_post_type_pages', array( 'edit.php', 'post-new.php', 'post.php', 'edit-tags.php' ) );
		$allowed_hooks = apply_filters( 'sensei_scripts_allowed_hooks', array( 'sensei_page_sensei_grading', 'sensei_page_sensei_analysis', 'sensei_page_sensei_updates', 'sensei_page_woothemes-sensei-settings' ) );

		// Test for Write Panel Pages
		if ( ( ( isset( $post_type ) && in_array( $post_type, $allowed_post_types ) ) && ( isset( $hook ) && in_array( $hook, $allowed_post_type_pages ) ) ) || ( isset( $hook ) && in_array( $hook, $allowed_hooks ) ) ) {

			// Load the lessons script
			wp_enqueue_script( 'woosensei-lesson-metadata', $woothemes_sensei->plugin_url . 'assets/js/lesson-metadata.js', array( 'jquery', 'jquery-ui-sortable' ), '1.5.0' );
			wp_enqueue_script( 'woosensei-lesson-chosen', $woothemes_sensei->plugin_url . 'assets/chosen/chosen.jquery.min.js', array( 'jquery' ), '1.3.0' );
			wp_enqueue_script( 'woosensei-chosen-ajax', $woothemes_sensei->plugin_url . 'assets/chosen/ajax-chosen.jquery.min.js', array( 'jquery', 'woosensei-lesson-chosen' ), '1.4.6' );
			$translation_strings = array( 'wrong_colon' => __( 'Wrong:', 'woothemes-sensei' ) );
			$ajax_vars = array( 'lesson_update_question_nonce' => wp_create_nonce( 'lesson_update_question_nonce' ), 'lesson_add_course_nonce' => wp_create_nonce( 'lesson_add_course_nonce' ), 'lesson_update_grade_type_nonce' => wp_create_nonce( 'lesson_update_grade_type_nonce' ), 'lesson_update_question_order_nonce' => wp_create_nonce( 'lesson_update_question_order_nonce' ) );
			$data = array_merge( $translation_strings, $ajax_vars );
			// V2 - Specify variables to be made available to the lesson-metadata.js file.
			wp_localize_script( 'woosensei-lesson-metadata', 'woo_localized_data', $data );

		} else {

			return;

		} // End If Statement

	} // End enqueue_scripts()

	/**
	 * Load in CSS styles where necessary.
	 *
	 * @access public
	 * @since  1.4.0
	 * @return void
	 */
	public function enqueue_styles ( $hook ) {
		global $woothemes_sensei, $post_type;

		$allowed_post_types = apply_filters( 'sensei_scripts_allowed_post_types', array( 'lesson', 'course' ) );
		$allowed_post_type_pages = apply_filters( 'sensei_scripts_allowed_post_type_pages', array( 'edit.php', 'post-new.php', 'post.php', 'edit-tags.php' ) );
		$allowed_hooks = apply_filters( 'sensei_scripts_allowed_hooks', array( 'sensei_page_sensei_grading', 'sensei_page_sensei_analysis', 'sensei_page_sensei_updates', 'sensei_page_woothemes-sensei-settings' ) );

		// Test for Write Panel Pages
		if ( ( ( isset( $post_type ) && in_array( $post_type, $allowed_post_types ) ) && ( isset( $hook ) && in_array( $hook, $allowed_post_type_pages ) ) ) || ( isset( $hook ) && in_array( $hook, $allowed_hooks ) ) ) {

			wp_enqueue_style( 'woothemes-sensei-settings-api', esc_url( $woothemes_sensei->plugin_url . 'assets/css/settings.css' ), '', '1.5.0' );

		}

	} // End enqueue_styles()

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
	 * lesson_add_course function.
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
		$return = false;
		// Question Save and Delete logic
		if ( isset( $question_data['action'] ) && ( $question_data['action'] == 'delete' ) ) {
			// Delete the Question
			$return = $this->lesson_delete_question($question_data);
		} else {
			// Save the Question
			if ( isset( $question_data['quiz_id'] ) && ( 0 < absint( $question_data['quiz_id'] ) ) ) {
				$current_user = wp_get_current_user();
				$question_data['post_author'] = $current_user->ID;
				$question_id = $this->lesson_save_question( $question_data );
				$question_types = wp_get_post_terms( $question_id, 'question-type', array( 'fields' => 'names' ) );
				$question_counter = 0;
				$question_type = '';
				if ( isset( $question_types[0] ) && '' != $question_types[0] ) {
					$question_type = $question_types[0];
				} // End If Statement

				if( ! $question_type ) {
					$question_type = 'multiple-choice';
				}

				$question_count = intval( $question_data['question_count'] );
				++$question_count;

				$return = $this->quiz_panel_question( $question_type, $question_count, $question_id );
			} // End If Statement
		} // End If Statement

		echo $return;

		die();
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

	public function lesson_update_question_order() {
		// Add nonce security to the request
		if ( isset($_POST['lesson_update_question_order_nonce']) ) {
			$nonce = esc_html( $_POST['lesson_update_question_order_nonce'] );
		} // End If Statement
		if ( ! wp_verify_nonce( $nonce, 'lesson_update_question_order_nonce' ) ) {
			die('');
		} // End If Statement
		// Parse POST data
		$data = $_POST['data'];
		$quiz_data = array();
		parse_str($data, $quiz_data);
		if( strlen( $quiz_data['question_order'] ) > 0 ) {
			$questions = explode( ',', $quiz_data['question_order'] );
			$o = 1;
			foreach( $questions as $question_id ) {
				update_post_meta( $question_id, '_quiz_question_order', $quiz_data['quiz_id'] . '000' . $o );
				++$o;
			}
			update_post_meta( $quiz_data['quiz_id'], '_question_order', $questions );
		}
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

		// Handle Question Type
		if ( isset( $data[ 'question_type' ] ) && ( '' != $data[ 'question_type' ] ) ) {
			$question_type = $data[ 'question_type' ];
		} // End If Statement

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
		if ( isset( $data[ 'question_' . $question_id . '_right_answer_boolean' ] ) && ( '' != $data[ 'question_' . $question_id . '_right_answer_boolean' ] ) && 'boolean' == $question_type ) {
			$question_right_answer = $data[ 'question_' . $question_id . '_right_answer_boolean' ];
		} // End If Statement
		// Handle Boolean Fields - Add
		if ( isset( $data[ 'question_right_answer_boolean' ] ) && ( '' != $data[ 'question_right_answer_boolean' ] ) && 'boolean' == $question_type ) {
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
		// Handle Question Grade
		if ( isset( $data[ 'question_grade' ] ) && ( '' != $data[ 'question_grade' ] ) ) {
			$question_grade = $data[ 'question_grade' ];
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
  			$question_wrong_answers_array = array_values( array_filter( $question_wrong_answers, 'strlen' ) );
  			$question_wrong_answers = array();
  		} // End If Statement

  		foreach( $question_wrong_answers_array as $answer ) {
  			if( ! in_array( $answer, $question_wrong_answers ) ) {
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
			$answer_order = $data['answer_order'];

			// Get random order selection
			$random_order = $data['random_order'];

  		    // Insert or Update the question
  		    if ( 0 < $question_id ) {
		    	$post_type_args[ 'ID' ] = $question_id;
		    	$question_id = wp_update_post( $post_type_args );

		    	// Update poast meta
		    	update_post_meta( $question_id, '_quiz_id', $quiz_id );
		    	update_post_meta( $question_id, '_question_grade', $question_grade );
		    	update_post_meta( $question_id, '_question_right_answer', $question_right_answer );
		    	update_post_meta( $question_id, '_question_wrong_answers', $question_wrong_answers );
		    	update_post_meta( $question_id, '_wrong_answer_count', $wrong_answer_count );
		    	update_post_meta( $question_id, '_question_media', $question_media );
		    	update_post_meta( $question_id, '_answer_order', $answer_order );
		    	update_post_meta( $question_id, '_random_order', $random_order );
		    } else {
				$question_id = wp_insert_post( $post_type_args );
				$question_count = intval( $data['question_count'] );
				++$question_count;

				// Set post meta
				add_post_meta( $question_id, '_quiz_id', $quiz_id );
		    	add_post_meta( $question_id, '_question_grade', $question_grade );
		    	add_post_meta( $question_id, '_question_right_answer', $question_right_answer );
		    	add_post_meta( $question_id, '_question_wrong_answers', $question_wrong_answers );
		    	add_post_meta( $question_id, '_wrong_answer_count', $wrong_answer_count );
		    	add_post_meta( $question_id, '_quiz_question_order', $quiz_id . '000' . $question_count );
		    	add_post_meta( $question_id, '_question_media', $question_media );
		    	add_post_meta( $question_id, '_answer_order', $answer_order );
		    	add_post_meta( $question_id, '_random_order', $random_order );

		    	// Set the post terms for question-type
			    wp_set_post_terms( $question_id, array( $question_type ), 'question-type' );
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
	public function lesson_quiz_questions( $quiz_id = 0, $post_status = 'any', $orderby = 'meta_value_num title', $order = 'ASC' ) {

		$posts_array = array();

		$this->set_default_question_order( $quiz_id );

		$post_args = array(	'post_type' 		=> 'question',
							'numberposts' 		=> -1,
							'meta_key'        	=> '_quiz_question_order',
							'orderby'         	=> $orderby,
    						'order'           	=> $order,
    						'meta_query'		=> array(
    							array(
	    							'key'       => '_quiz_id',
	    							'value'     => $quiz_id
    							)
							),
    						'post_status'		=> $post_status,
							'suppress_filters' 	=> 0
							);
		$posts_array = get_posts( $post_args );

		return $posts_array;

	} // End lesson_quiz_questions()

	/**
	 * Set the default quiz order
	 * @param integer $quiz_id ID of quiz
	 */
	public function set_default_question_order( $quiz_id = 0 ) {

		if( $quiz_id ) {

			$question_order = get_post_meta( $quiz_id, '_question_order', true );

			if( ! $question_order ) {

				$args = array(
					'post_type' 		=> 'question',
					'numberposts' 		=> -1,
					'orderby'         	=> 'ID',
					'order'           	=> 'ASC',
					'meta_query'		=> array(
						array(
							'key'       => '_quiz_id',
							'value'     => $quiz_id
						)
					),
					'post_status'		=> 'any',
					'suppress_filters' 	=> 0
				);
				$questions = get_posts( $args );

				$o = 1;
				foreach( $questions as $question ) {
					add_post_meta( $question->ID, '_quiz_question_order', $quiz_id . '000' . $o, true );
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
				$image_thumb_size = 'lesson_single_image';
				$dimensions = $woothemes_sensei->get_image_size( $image_thumb_size );
				$width = $dimensions['width'];
				$height = $dimensions['height'];
				$crop = $dimensions['crop'];
			} else {
				if ( !$woothemes_sensei->settings->settings[ 'course_lesson_image_enable' ] ) {
					return '';
				} // End If Statement
				$image_thumb_size = 'lesson_archive_image';
				$dimensions = $woothemes_sensei->get_image_size( $image_thumb_size );
				$width = $dimensions['width'];
				$height = $dimensions['height'];
				$crop = $dimensions['crop'];
			} // End If Statement
		} // End If Statement

		$img_url = '';
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