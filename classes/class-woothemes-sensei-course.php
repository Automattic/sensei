<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Course Class
 *
 * All functionality pertaining to the Courses Post Type in Sensei.
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
 * - course_woocommerce_product_meta_box_content()
 * - course_prerequisite_meta_box_content()
 * - course_featured_meta_box_content()
 * - course_video_meta_box_content()
 * - meta_box_save()
 * - save_post_meta()
 * - course_lessons_meta_box_content
 * - add_column_headings()
 * - add_column_data()
 * - course_query()
 * - get_archive_query_args()
 * - course_image()
 * - course_count()
 * - course_lessons()
 * - course_lessons_completed()
 * - course_author_lesson_count()
 */
class WooThemes_Sensei_Course {
	public $token;
	public $meta_fields;

	/**
	 * Constructor.
	 * @since  1.0.0
	 */
	public function __construct () {
		// Setup meta fields for this post type
		$this->meta_fields = array( 'course_prerequisite', 'course_featured', 'course_video_embed', 'course_woocommerce_product' );
		// Admin actions
		if ( is_admin() ) {
			// Metabox functions
			add_action( 'admin_menu', array( $this, 'meta_box_setup' ), 20 );
			add_action( 'save_post', array( $this, 'meta_box_save' ) );
			// Custom Write Panel Columns
			add_filter( 'manage_edit-course_columns', array( $this, 'add_column_headings' ), 10, 1 );
			add_action( 'manage_posts_custom_column', array( $this, 'add_column_data' ), 10, 2 );
		} else {
			$this->my_courses_page = false;
		} // End If Statement

		// Update course completion upon completion of a lesson
		add_action( 'sensei_user_lesson_end', array( $this, 'update_status_after_lesson_change' ), 10, 2 );
		// Update course completion upon reset of a lesson
		add_action( 'sensei_user_lesson_reset', array( $this, 'update_status_after_lesson_change' ), 10, 2 );
		// Update course completion upon grading of a quiz
		add_action( 'sensei_user_quiz_grade', array( $this, 'update_status_after_quiz_submission' ), 10, 2 );

	} // End __construct()

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
				WooThemes_Sensei_Utils::user_complete_course( $course_id, $user_id );
			}
		}
	}

	/**
	 * meta_box_setup function.
	 *
	 * @access public
	 * @return void
	 */
	public function meta_box_setup () {

		if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() ) {
			// Add Meta Box for WooCommerce Course
			add_meta_box( 'course-wc-product', __( 'WooCommerce Product', 'woothemes-sensei' ), array( $this, 'course_woocommerce_product_meta_box_content' ), $this->token, 'side', 'default' );
		} // End If Statement
		// Add Meta Box for Prerequisite Course
		add_meta_box( 'course-prerequisite', __( 'Course Prerequisite', 'woothemes-sensei' ), array( $this, 'course_prerequisite_meta_box_content' ), $this->token, 'side', 'default' );
		// Add Meta Box for Featured Course
		add_meta_box( 'course-featured', __( 'Featured Course', 'woothemes-sensei' ), array( $this, 'course_featured_meta_box_content' ), $this->token, 'side', 'default' );
		// Add Meta Box for Course Meta
		add_meta_box( 'course-video', __( 'Course Video', 'woothemes-sensei' ), array( $this, 'course_video_meta_box_content' ), $this->token, 'normal', 'default' );
		// Add Meta Box for Course Lessons
		add_meta_box( 'course-lessons', __( 'Course Lessons', 'woothemes-sensei' ), array( $this, 'course_lessons_meta_box_content' ), $this->token, 'normal', 'default' );
		// Remove "Custom Settings" meta box.
		remove_meta_box( 'woothemes-settings', $this->token, 'normal' );
	} // End meta_box_setup()

	/**
	 * course_woocommerce_product_meta_box_content function.
	 *
	 * @access public
	 * @return void
	 */
	public function course_woocommerce_product_meta_box_content () {
		global $post;

		$select_course_woocommerce_product = get_post_meta( $post->ID, '_course_woocommerce_product', true );

		$post_args = array(	'post_type' 		=> array( 'product', 'product_variation' ),
							'numberposts' 		=> -1,
							'orderby'         	=> 'title',
    						'order'           	=> 'DESC',
    						'exclude' 			=> $post->ID,
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
		$posts_array = get_posts( $post_args );

		$html = '';

		$html .= '<input type="hidden" name="' . esc_attr( 'woo_' . $this->token . '_noonce' ) . '" id="' . esc_attr( 'woo_' . $this->token . '_noonce' ) . '" value="' . esc_attr( wp_create_nonce( plugin_basename(__FILE__) ) ) . '" />';

		if ( count( $posts_array ) > 0 ) {
			$html .= '<select id="course-woocommerce-product-options" name="course_woocommerce_product" class="chosen_select widefat">' . "\n";
			$html .= '<option value="-">' . __( 'None', 'woothemes-sensei' ) . '</option>';
				$prev_parent_id = 0;
				foreach ( $posts_array as $post_item ) {

					if ( 'product_variation' == $post_item->post_type ) {
						$product_object = get_product( $post_item->ID );
						$parent_id = wp_get_post_parent_id( $post_item->ID );
						if( sensei_check_woocommerce_version( '2.1' ) ) {
							$formatted_variation = wc_get_formatted_variation( $product_object->variation_data, true );
						} else {
							$formatted_variation = woocommerce_get_formatted_variation( $product_object->variation_data, true );
						}
						$product_name = ucwords( $formatted_variation );
					} else {
						$parent_id = false;
						$prev_parent_id = 0;
						$product_name = $post_item->post_title;
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

					$html .= '<option value="' . esc_attr( absint( $post_item->ID ) ) . '"' . selected( $post_item->ID, $select_course_woocommerce_product, false ) . '>' . esc_html( $product_name ) . '</option>' . "\n";
				} // End For Loop
			$html .= '</select>' . "\n";
			if ( current_user_can( 'publish_product' )) {
				$html .= '<p>' . "\n";
					$html .= '<a href="' . admin_url( 'post-new.php?post_type=product' ) . '" title="' . esc_attr( __( 'Add a Product', 'woothemes-sensei' ) ) . '">' . __( 'Add a Product', 'woothemes-sensei' ) . '</a>' . "\n";
				$html .= '</p>'."\n";
			} // End If Statement
		} else {
			if ( current_user_can( 'publish_product' )) {
				$html .= '<p>' . "\n";
					$html .= esc_html( __( 'No products exist yet.', 'woothemes-sensei' ) ) . '&nbsp;<a href="' . admin_url( 'post-new.php?post_type=product' ) . '" title="' . esc_attr( __( 'Add a Product', 'woothemes-sensei' ) ) . '">' . __( 'Please add some first', 'woothemes-sensei' ) . '</a>' . "\n";
				$html .= '</p>'."\n";
			} else {
				$html .= '<p>' . "\n";
					$html .= esc_html( __( 'No products exist yet.', 'woothemes-sensei' ) ) . "\n";
				$html .= '</p>'."\n";
			} // End If Statement
		} // End If Statement

		echo $html;

	} // End course_woocommerce_product_meta_box_content()

	/**
	 * course_prerequisite_meta_box_content function.
	 *
	 * @access public
	 * @return void
	 */
	public function course_prerequisite_meta_box_content () {
		global $post;

		$select_course_prerequisite = get_post_meta( $post->ID, '_course_prerequisite', true );

		$post_args = array(	'post_type' 		=> 'course',
							'numberposts' 		=> -1,
							'orderby'         	=> 'title',
    						'order'           	=> 'DESC',
    						'exclude' 			=> $post->ID,
							'suppress_filters' 	=> 0
							);
		$posts_array = get_posts( $post_args );

		$html = '';

		$html .= '<input type="hidden" name="' . esc_attr( 'woo_' . $this->token . '_noonce' ) . '" id="' . esc_attr( 'woo_' . $this->token . '_noonce' ) . '" value="' . esc_attr( wp_create_nonce( plugin_basename(__FILE__) ) ) . '" />';

		if ( count( $posts_array ) > 0 ) {
			$html .= '<select id="course-prerequisite-options" name="course_prerequisite" class="chosen_select widefat">' . "\n";
			$html .= '<option value="">' . __( 'None', 'woothemes-sensei' ) . '</option>';
				foreach ($posts_array as $post_item){
					$html .= '<option value="' . esc_attr( absint( $post_item->ID ) ) . '"' . selected( $post_item->ID, $select_course_prerequisite, false ) . '>' . esc_html( $post_item->post_title ) . '</option>' . "\n";
				} // End For Loop
			$html .= '</select>' . "\n";
		} else {
			$html .= '<p>' . esc_html( __( 'No courses exist yet. Please add some first.', 'woothemes-sensei' ) ) . '</p>';
		} // End If Statement

		echo $html;

	} // End course_prerequisite_meta_box_content()

	/**
	 * course_featured_meta_box_content function.
	 *
	 * @access public
	 * @return void
	 */
	public function course_featured_meta_box_content () {
		global $post;

		$course_featured = get_post_meta( $post->ID, '_course_featured', true );

		$html = '';

		$html .= '<input type="hidden" name="' . esc_attr( 'woo_' . $this->token . '_noonce' ) . '" id="' . esc_attr( 'woo_' . $this->token . '_noonce' ) . '" value="' . esc_attr( wp_create_nonce( plugin_basename(__FILE__) ) ) . '" />';

		$checked = '';
		if ( isset( $course_featured ) && ( '' != $course_featured ) ) {
	 	    $checked = checked( 'featured', $course_featured, false );
	 	} // End If Statement

	 	$html .= '<input type="checkbox" name="course_featured" value="featured" ' . $checked . '>&nbsp;' . __( 'Feature this course', 'woothemes-sensei' ) . '<br>';

		echo $html;

	} // End course_featured_meta_box_content()

	/**
	 * course_video_meta_box_content function.
	 *
	 * @access public
	 * @return void
	 */
	public function course_video_meta_box_content () {
		global $post;

		$course_video_embed = get_post_meta( $post->ID, '_course_video_embed', true );

		$html = '';

		$html .= '<label class="screen-reader-text" for="course_video_embed">' . __( 'Video Embed Code', 'woothemes-sensei' ) . '</label>';
		$html .= '<textarea rows="5" cols="50" name="course_video_embed" tabindex="6" id="course-video-embed">' . $course_video_embed . '</textarea>';
		$html .= '<p>' .  __( 'Paste the embed code for your YouTube or Vimeo videos in the box above.', 'woothemes-sensei' ) . '</p>';

		echo $html;

	} // End course_video_meta_box_content()

	/**
	 * meta_box_save function.
	 *
	 * Handles saving the meta data
	 *
	 * @access public
	 * @param int $post_id
	 * @return void
	 */
	public function meta_box_save ( $post_id ) {
		global $post, $messages;

		/* Verify the nonce before proceeding. */
		if ( ( get_post_type() != $this->token ) || ! wp_verify_nonce( $_POST['woo_' . $this->token . '_noonce'], plugin_basename(__FILE__) ) ) {
			return $post_id;
		}

		/* Get the post type object. */
		$post_type = get_post_type_object( $post->post_type );

		/* Check if the current user has permission to edit the post. */
		if ( !current_user_can( $post_type->cap->edit_post, $post_id ) ) {
			return $post_id;
		} // End If Statement

		if ( 'page' == $_POST['post_type'] ) {
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
	 * save_post_meta function.
	 *
	 * Does the save
	 *
	 * @access private
	 * @param string $post_key (default: '')
	 * @param int $post_id (default: 0)
	 * @return void
	 */
	private function save_post_meta( $post_key = '', $post_id = 0 ) {
		// Get the meta key.
		$meta_key = '_' . $post_key;
		// Get the posted data and sanitize it for use as an HTML class.
		if ( 'course_video_embed' == $post_key) {
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
	 * course_lessons_meta_box_content function.
	 *
	 * @access public
	 * @return void
	 */
	public function course_lessons_meta_box_content () {
		global $post;

		// Setup Lesson Meta Data
		$select_lesson_prerequisite = 0;
		if ( 0 < $post->ID ) { $select_course_prerequisite = get_post_meta( $post->ID, '_lesson_course', true ); }

		// Setup Lesson Query
		$posts_array = array();
		if ( 0 < $post->ID ) {

			$posts_array = $this->course_lessons( $post->ID, 'any' );

		} // End If Statement

		$html = '';

		$html .= '<input type="hidden" name="' . esc_attr( 'woo_' . $this->token . '_noonce' ) . '" id="' . esc_attr( 'woo_' . $this->token . '_noonce' ) . '" value="' . esc_attr( wp_create_nonce( plugin_basename(__FILE__) ) ) . '" />';

		if ( count( $posts_array ) > 0 ) {

			foreach ($posts_array as $post_item){

				$html .= '<p>'."\n";

					$html .= $post_item->post_title."\n";
					$html .= '<a href="' . esc_url( get_edit_post_link( $post_item->ID ) ) . '" title="' . esc_attr( sprintf( __( 'Edit %s', 'woothemes-sensei' ), $post_item->post_title ) ) . '" class="edit-lesson-action">' . __( 'Edit this lesson', 'woothemes-sensei' ) . '</a>';

				$html .= '</p>'."\n";

			} // End For Loop

		} else {
			$course_id = '';
			if ( 0 < $post->ID ) { $course_id = '&course_id=' . $post->ID; }
			$html .= '<p>' . esc_html( __( 'No lessons exist yet for this course.', 'woothemes-sensei' ) ) . "\n";
				$html .= '<a href="' . admin_url( 'post-new.php?post_type=lesson' . $course_id ) . '" title="' . esc_attr( __( 'Add a Lesson', 'woothemes-sensei' ) ) . '">' . __( 'Please add some.', 'woothemes-sensei' ) . '</a>' . "\n";
			$html .= '</p>'."\n";
		} // End If Statement

		echo $html;

	} // End course_lessons_meta_box_content()

	/**
	 * Add column headings to the "lesson" post list screen.
	 * @access public
	 * @since  1.0.0
	 * @param  array $defaults
	 * @return array $new_columns
	 */
	public function add_column_headings ( $defaults ) {
		$new_columns['cb'] = '<input type="checkbox" />';
		// $new_columns['id'] = __( 'ID' );
		$new_columns['title'] = _x( 'Course Title', 'column name', 'woothemes-sensei' );
		$new_columns['course-prerequisite'] = _x( 'Pre-requisite Course', 'column name', 'woothemes-sensei' );
		if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() ) {
			$new_columns['course-woocommerce-product'] = _x( 'WooCommerce Product', 'column name', 'woothemes-sensei' );
		} // End If Statement
		$new_columns['course-category'] = _x( 'Category', 'column name', 'woothemes-sensei' );
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

			case 'course-prerequisite':
				$course_prerequisite_id = get_post_meta( $id, '_course_prerequisite', true);
				if ( 0 < absint( $course_prerequisite_id ) ) { echo '<a href="' . esc_url( get_edit_post_link( absint( $course_prerequisite_id ) ) ) . '" title="' . esc_attr( sprintf( __( 'Edit %s', 'woothemes-sensei' ), get_the_title( absint( $course_prerequisite_id ) ) ) ) . '">' . get_the_title( absint( $course_prerequisite_id ) ) . '</a>'; }

			break;

			case 'course-woocommerce-product':
				if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() ) {
					$course_woocommerce_product_id = get_post_meta( $id, '_course_woocommerce_product', true);
					if ( 0 < absint( $course_woocommerce_product_id ) ) {
						if ( 'product_variation' == get_post_type( $course_woocommerce_product_id ) ) {
							$product_object = get_product( $course_woocommerce_product_id );
							if( sensei_check_woocommerce_version( '2.1' ) ) {
								$formatted_variation = wc_get_formatted_variation( $product_object->variation_data, true );
							} else {
								$formatted_variation = woocommerce_get_formatted_variation( $product_object->variation_data, true );
							}
							$product_name = $product_object->parent->post->post_title . '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . ucwords( $formatted_variation );
						} else {
							$product_name = get_the_title( absint( $course_woocommerce_product_id ) );
						} // End If Statement
						echo '<a href="' . esc_url( get_edit_post_link( absint( $course_woocommerce_product_id ) ) ) . '" title="' . esc_attr( sprintf( __( 'Edit %s', 'woothemes-sensei' ), $product_name ) ) . '">' . $product_name . '</a>';
					} // End If Statement
				} // End If Statement
			break;

			case 'course-category':
				$output = get_the_term_list( $id, 'course-category', '', ', ', '' );
				if ( '' == $output ) {
					$output = __( 'None', 'woothemes-sensei' );
				} // End If Statement
				echo $output;
			break;

			default:
			break;
		}
	} // End add_column_data()


	/**
	 * course_query function.
	 *
	 * @access public
	 * @param int $amount (default: 0)
	 * @param string $type (default: 'default')
	 * @param array $includes (default: array())
	 * @return void
	 */
	public function course_query( $amount = 0, $type = 'default', $includes = array(), $excludes = array() ) {
		global $my_courses_page;

		$results_array = array();

		if( $my_courses_page ) { add_action( 'pre_get_posts', array( $this, 'filter_my_courses' ) ); }

		$post_args = $this->get_archive_query_args( $type, $amount, $includes, $excludes );

		// get the posts
		if( empty( $post_args ) ) {

			return $results_array;

		}else{

			//reset the pagination as this widgets do not need it
			$post_args['paged'] = 1;
			$results_array = get_posts( $post_args );

		}

		if( $my_courses_page ) { remove_action( 'pre_get_posts', array( $this, 'filter_my_courses' ) ); }

		return $results_array;

	} // End course_query()


	/**
	 * get_archive_query_args function.
	 *
	 * @access public
	 * @param string $type (default: '')
	 * @param int $amount (default: 0)
	 * @param array $includes (default: array())
	 * @return void
	 */
	public function get_archive_query_args( $type = '', $amount = 0 , $includes = array(), $excludes = array() ) {

		global $wp_query, $woothemes_sensei;

		$post_args = array();

		if ( 0 == $amount && ( isset( $woothemes_sensei->settings->settings[ 'course_archive_amount' ] ) && 'usercourses' != $type && ( 0 < absint( $woothemes_sensei->settings->settings[ 'course_archive_amount' ] ) ) ) ) {
			$amount = absint( $woothemes_sensei->settings->settings[ 'course_archive_amount' ] );
		} else {
			if ( 0 == $amount) {
				$amount = $wp_query->get( 'posts_per_page' );
			} // End If Statement
		} // End If Statement

		switch ($type) {
			case 'usercourses':
				$post_args = array(	'post_type' 		=> 'course',
									'orderby'         	=> 'menu_order date',
    								'order'           	=> 'ASC',
    								'post_status'      	=> 'publish',
    								'include'			=> $includes,
    								'exclude'			=> $excludes,
    								'suppress_filters' 	=> 0
									);
				break;
			case 'freecourses':
				// Sub Query to get all WooCommerce Products that have Zero price
				$args = array(
							   'post_type' => 'product',
							   'post_status' => 'publish',
							   'posts_per_page' => -1,
							   'meta_query' => array(
		   							array(
								        'key' => '_price',
								        'value' => '0',
								        'compare' => '=',
								        'type' => 'NUMERIC'
								       )
								),
								'orderby' => 'menu_order date',
								'order' => 'ASC',
				);
 				$posts = get_posts($args);
 				$free_wc_posts = array();
 				foreach ( $posts as $post_item ) {
 					array_push( $free_wc_posts , $post_item->ID );
 				} // End For Loop
 				$post_args = array(	'post_type' 		=> 'course',
									'orderby'         	=> 'menu_order date',
    								'order'           	=> 'ASC',
    								'post_status'      	=> 'publish',
    								'exclude'			=> $excludes,
    								'suppress_filters' 	=> 0
									);
 				if ( 0 < count( $free_wc_posts ) ) {
 					$post_args['meta_query'] = array(
							   							'relation' => 'OR',
													    array(
													        'key' => '_course_woocommerce_product',
													        'value' => '-',
													        'compare' => '='
													       ),
													    array(
													        'key' => '_course_woocommerce_product',
													        'value' => $free_wc_posts,
													        'compare' => 'IN'
													       )
													);
 				} else {
 					$post_args['meta_query'] = array(
							   							array(
													        'key' => '_course_woocommerce_product',
													        'value' => '-',
													        'compare' => '='
													       )
													);
 				}
				break;
			case 'paidcourses':
				// Sub Query to get all WooCommerce Products that have price greater than zero
				$args = array(
							   'post_type' => 'product',
							   'post_status' => 'publish',
							   'posts_per_page' => -1,
							   'meta_query' => array(
							   							array(
													        'key' => '_price',
													        'value' => '0',
													        'compare' => '>',
													        'type' => 'NUMERIC'
													       )
													)
								);
 				$posts = get_posts($args);
 				$paid_wc_posts = array();
 				foreach ( $posts as $post_item ) {
 					array_push( $paid_wc_posts , $post_item->ID );
 				} // End For Loop
				$post_args = array(	'post_type' 		=> 'course',
									'orderby'         	=> 'menu_order date',
    								'order'           	=> 'ASC',
    								'post_status'      	=> 'publish',
    								'exclude'			=> $excludes,
    								'suppress_filters' 	=> 0
									);
				if ( 0 < count( $paid_wc_posts) ) {
 					$post_args['meta_query'] = array(
							   							'relation' => 'AND',
													    array(
													        'key' => '_course_woocommerce_product',
													        'value' => '0',
													        'compare' => '>',
													        'type' => 'NUMERIC'
													       ),
													    array(
													        'key' => '_course_woocommerce_product',
													        'value' => $paid_wc_posts,
													        'compare' => 'IN'
													       )
													);
 				} else {
 					$post_args['meta_query'] = array(
													        'key' => '_course_woocommerce_product',
													        'value' => '0',
													        'compare' => '>',
													        'type' => 'NUMERIC'
													       );
 				}
				break;
			case 'featuredcourses':
				$post_args = array(	'post_type' 		=> 'course',
									'orderby'         	=> 'menu_order date',
    								'order'           	=> 'ASC',
    								'post_status'      	=> 'publish',
    								'meta_value' 		=> 'featured',
    								'meta_key' 			=> '_course_featured',
    								'meta_compare' 		=> '=',
    								'exclude'			=> $excludes,
    								'suppress_filters' 	=> 0
									);
				break;
			default:
				$post_args = array(	'post_type' 		=> 'course',
									'orderby'         	=> 'menu_order date',
    								'order'           	=> 'ASC',
    								'post_status'      	=> 'publish',
    								'include'			=> $includes,
    								'exclude'			=> $excludes,
    								'suppress_filters' 	=> 0
									);
				break;

		}

		if ( ! is_post_type_archive( 'course' ) ) {
			$post_args['posts_per_page'] = $amount;
			$post_args['paged'] = $wp_query->get( 'paged' );
		} else {
			$post_args['posts_per_page'] = $amount;
			$post_args['paged'] = $wp_query->get( 'paged' );
		} // End If Statement

		return $post_args;
	}


	/**
	 * course_image function.
	 *
	 * Outputs the courses image, or first image from a lesson within a course
	 *
	 * @access public
	 * @param int $course_id (default: 0)
	 * @param string $width (default: '100')
	 * @param string $height (default: '100')
	 * @return void
	 */
	public function course_image( $course_id = 0, $width = '100', $height = '100' ) {

		global $woothemes_sensei;

		$html = '';

		// Get Width and Height settings
		if ( ( $width == '100' ) && ( $height == '100' ) ) {
			if ( is_singular( 'course' ) ) {
				if ( !$woothemes_sensei->settings->settings[ 'course_single_image_enable' ] ) {
					return '';
				} // End If Statement
				$image_thumb_size = 'course_single_image';
				$dimensions = $woothemes_sensei->get_image_size( $image_thumb_size );
				$width = $dimensions['width'];
				$height = $dimensions['height'];
				$crop = $dimensions['crop'];
			} else {
				if ( !$woothemes_sensei->settings->settings[ 'course_archive_image_enable' ] ) {
					return '';
				} // End If Statement
				$image_thumb_size = 'course_archive_image';
				$dimensions = $woothemes_sensei->get_image_size( $image_thumb_size );
				$width = $dimensions['width'];
				$height = $dimensions['height'];
				$crop = $dimensions['crop'];
			} // End If Statement
		} // End If Statement

		$img_url = '';
		if ( has_post_thumbnail( $course_id ) ) {
   			// Get Featured Image
   			$img_url = get_the_post_thumbnail( $course_id, array( $width, $height ), array( 'class' => 'woo-image thumbnail alignleft') );
 		} else {

			// Check for a Lesson Image
			$course_lessons = $this->course_lessons( $course_id );

			foreach ($course_lessons as $lesson_item){
				if ( has_post_thumbnail( $lesson_item->ID ) ) {
					// Get Featured Image
					$img_url = get_the_post_thumbnail( $lesson_item->ID, array( $width, $height ), array( 'class' => 'woo-image thumbnail alignleft') );
					if ( '' != $img_url ) {
						break;
					} // End If Statement

				} // End If Statement
			} // End For Loop

 			if ( '' == $img_url ) {
 				// Display Image Placeholder if none
				if ( $woothemes_sensei->settings->settings[ 'placeholder_images_enable' ] ) {
					$img_url = apply_filters( 'sensei_course_placeholder_image_url', '<img src="http://placehold.it/' . $width . 'x' . $height . '" class="woo-image thumbnail alignleft" />' );
				} // End If Statement
 			} // End If Statement

		} // End If Statement

		if ( '' != $img_url ) {
			$html .= '<a href="' . get_permalink( $course_id ) . '" title="' . esc_attr( get_post_field( 'post_title', $course_id ) ) . '">' . $img_url . '</a>';
		} // End If Statement

		return $html;

	} // End course_image()


	/**
	 * course_count function.
	 *
	 * @access public
	 * @param array $exclude (default: array())
	 * @param string $post_status (default: 'publish')
	 * @return void
	 */
	public function course_count( $post_status = 'publish' ) {

		$post_args = array(	'post_type'         => 'course',
							'posts_per_page'    => -1,
//							'orderby'           => 'menu_order date',
//							'order'             => 'ASC',
							'post_status'       => $post_status,
							'suppress_filters'  => 0,
							'fields'            => 'ids',
							);

		// Allow WP to generate the complex final query, just shortcut to only do an overall count
//		add_filter( 'posts_clauses', array( 'WooThemes_Sensei_Utils', 'get_posts_count_only_filter' ) );
		$courses_query = new WP_Query( apply_filters( 'sensei_course_count', $post_args ) );
//		remove_filter( 'posts_clauses', array( 'WooThemes_Sensei_Utils', 'get_posts_count_only_filter' ) );

		return count( $courses_query->posts );
	} // End course_count()


	/**
	 * course_lessons function.
	 *
	 * @access public
	 * @param int $course_id (default: 0)
	 * @param string $post_status (default: 'publish')
	 * @param string $fields (default: 'all')
	 * @return void
	 */
	public function course_lessons( $course_id = 0, $post_status = 'publish', $fields = 'all' ) {

		$posts_array = array();

		$post_args = array(	'post_type'         => 'lesson',
							'numberposts'       => -1,
							'meta_key'          => '_order_' . $course_id,
							'orderby'           => 'meta_value_num date',
							'order'             => 'ASC',
							'meta_query'        => array(
								array(
									'key' => '_lesson_course',
									'value' => intval( $course_id ),
								),
							),
							'post_status'       => $post_status,
							'suppress_filters'  => 0,
							'fields'            => $fields,
							);
		$posts_array = get_posts( $post_args );

		return $posts_array;

	} // End course_lessons()

	/**
	 * Fetch all quiz ids in a course
	 * @since  1.5.0
	 * @param  integer $course_id ID of course
	 * @param  boolean $boolean_check True if a simple yes/no is required
	 * @return array              Array of quiz post objects
	 */
	public function course_quizzes( $course_id = 0, $boolean_check = false ) {
		global $woothemes_sensei;

		$course_quizzes = array();

		if( $course_id ) {
			$lesson_ids = $woothemes_sensei->post_types->course->course_lessons( $course_id, 'any', 'ids' );

			foreach( $lesson_ids as $lesson_id ) {
				$has_questions = get_post_meta( $lesson_id, '_quiz_has_questions', true );
				if ( $has_questions && $boolean_check ) {
					return true;
				}
				elseif ( $has_questions ) {
					$quiz_id = $woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson_id );
//					$questions = $woothemes_sensei->post_types->lesson->lesson_quiz_questions( $quiz_id );
//					if( count( $questions ) > 0 ) {
						$course_quizzes[] = $quiz_id;
//					}
				}
			}
		}
		if ( $boolean_check && empty($course_quizzes) ) {
			$course_quizzes = false;
		}
		return $course_quizzes;
	}


	/**
	 * course_lessons_completed function. Appears to be completely unused and a duplicate of course_lessons()!
	 *
	 * @access public
	 * @param  int $course_id (default: 0)
	 * @param  string $post_status (default: 'publish')
	 * @return array
	 */
	public function course_lessons_completed( $course_id = 0, $post_status = 'publish' ) {

		return $this->course_lessons( $course_id, $post_status );

	} // End course_lessons_completed()


	/**
	 * course_author_lesson_count function.
	 *
	 * @access public
	 * @param  int $author_id (default: 0)
	 * @param  int $course_id (default: 0)
	 * @return int
	 */
	public function course_author_lesson_count( $author_id = 0, $course_id = 0 ) {

		$count = 0;

		$lesson_args = array(	'post_type' 		=> 'lesson',
								'numberposts' 		=> -1,
		    					'author'         	=> $author_id,
		    					'meta_key'        	=> '_lesson_course',
    							'meta_value'      	=> $course_id,
    	    					'post_status'      	=> 'publish',
    	    					'suppress_filters' 	=> 0,
								'fields'            => 'ids', // less data to retrieve
		    				);
		$lessons_array = get_posts( $lesson_args );
		$count = count( $lessons_array );
		return $count;

	} // End course_author_lesson_count()

	/**
	 * course_lesson_count function.
	 *
	 * @access public
	 * @param  int $course_id (default: 0)
	 * @return int
	 */
	public function course_lesson_count( $course_id = 0 ) {

		$count = 0;

		$lesson_args = array(	'post_type' 		=> 'lesson',
								'numberposts' 		=> -1,
		    					'meta_key'        	=> '_lesson_course',
    							'meta_value'      	=> $course_id,
    	    					'post_status'      	=> 'publish',
    	    					'suppress_filters' 	=> 0,
								'fields'            => 'ids', // less data to retrieve
		    				);
		$lessons_array = get_posts( $lesson_args );
		$count = count( $lessons_array );
		return $count;

	} // End course_lesson_count()

	/**
	 * course_lesson_preview_count function.
	 *
	 * @access public
	 * @param  int $course_id (default: 0)
	 * @return int
	 */
	public function course_lesson_preview_count( $course_id = 0 ) {

		$count = 0;

		$lesson_args = array(	'post_type' 		=> 'lesson',
								'numberposts' 		=> -1,
    	    					'post_status'      	=> 'publish',
    	    					'suppress_filters' 	=> 0,
    	    					'meta_query' => array(
									array(
										'key' => '_lesson_course',
										'value' => $course_id
									),
									array(
										'key' => '_lesson_preview',
										'value' => 'preview'
									)
								),
								'fields'            => 'ids', // less data to retrieve
		    				);
		$lessons_array = get_posts( $lesson_args );
		$count = count( $lessons_array );
		return $count;

	} // End course_lesson_count()

	/**
	 * get_product_courses function.
	 *
	 * @access public
	 * @param  int $product_id (default: 0)
	 * @return array
	 */
	public function get_product_courses( $product_id = 0 ) {

		$posts_array = array();
		// Check for WooCommerce
		if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() && 0 < $product_id ) {
			$post_args = array(	'post_type' 		=> 'course',
								'numberposts' 		=> -1,
								'meta_key'        	=> '_course_woocommerce_product',
	    						'meta_value'      	=> $product_id,
	    						'post_status'       => 'publish',
								'suppress_filters' 	=> 0,
								'orderby' 			=> 'menu_order date',
								'order' 			=> 'ASC',
								);
			$posts_array = get_posts( $post_args );
		} // End If Statement
		return $posts_array;

	} // End get_product_courses()

	/**
	 * single_course_lesson_data sets up frontend data for single course lesson output
	 * @since  1.2.1
	 * @return void
	 */
	public function single_course_lesson_data() {

	} // End single_course_lesson_data()

	/**
	 * Fix posts_per_page for My Courses page
	 * @param  object $query WP_Query object
	 * @return void
	 */
	public function filter_my_courses( $query ) {
		global $woothemes_sensei, $my_courses_section;

		if ( isset( $woothemes_sensei->settings->settings[ 'my_course_amount' ] ) && ( 0 < absint( $woothemes_sensei->settings->settings[ 'my_course_amount' ] ) ) ) {
			$amount = absint( $woothemes_sensei->settings->settings[ 'my_course_amount' ] );
			$query->set( 'posts_per_page', $amount );
		}

		if( isset( $_GET[ $my_courses_section . '_page' ] ) && 0 < intval( $_GET[ $my_courses_section . '_page' ] ) ) {
			$page = intval( $_GET[ $my_courses_section . '_page' ] );
			$query->set( 'paged', $page );
		}
	}

	/**
	 * load_user_courses_content generates HTML for user's active & completed courses
	 * @since  1.4.0
	 * @param  object  $user   Queried user object
	 * @param  boolean $manage Whether the user has permission to manage the courses
	 * @return string          HTML displayng course data
	 */
	public function load_user_courses_content( $user = false, $manage = false ) {
		global $woothemes_sensei, $post, $wp_query, $course, $my_courses_page, $my_courses_section;

		// Build Output HTML
		$complete_html = $active_html = '';

		if( $user ) {

			$my_courses_page = true;

			// Allow action to be run before My Courses content has loaded
			do_action( 'sensei_before_my_courses', $user->ID );

			// Logic for Active and Completed Courses
			$per_page = 20;
			if ( isset( $woothemes_sensei->settings->settings[ 'my_course_amount' ] ) && ( 0 < absint( $woothemes_sensei->settings->settings[ 'my_course_amount' ] ) ) ) {
				$per_page = absint( $woothemes_sensei->settings->settings[ 'my_course_amount' ] );
			}

			$course_statuses = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'user_id' => $user->ID, 'type' => 'sensei_course_status' ), true );
			// User may only be on 1 Course
			if ( !is_array($course_statuses) ) {
				$course_statuses = array( $course_statuses );
			}
			$completed_ids = $active_ids = array();
			foreach( $course_statuses as $course_status ) {
				if ( WooThemes_Sensei_Utils::user_completed_course( $course_status, $user->ID ) ) {
					$completed_ids[] = $course_status->comment_post_ID;
				} else {
					$active_ids[] = $course_status->comment_post_ID;
				}
			}

			$active_count = $completed_count = 0;

			$active_courses = array();
			if ( 0 < intval( count( $active_ids ) ) ) {
				$my_courses_section = 'active';
				$active_courses = $woothemes_sensei->post_types->course->course_query( $per_page, 'usercourses', $active_ids );
				$active_count = count( $active_ids );
			} // End If Statement

			$completed_courses = array();
			if ( 0 < intval( count( $completed_ids ) ) ) {
				$my_courses_section = 'completed';
				$completed_courses = $woothemes_sensei->post_types->course->course_query( $per_page, 'usercourses', $completed_ids );
				$completed_count = count( $completed_ids );
			} // End If Statement
			$lesson_count = 1;

			$active_page = 1;
			if( isset( $_GET['active_page'] ) && 0 < intval( $_GET['active_page'] ) ) {
				$active_page = $_GET['active_page'];
			}

			$completed_page = 1;
			if( isset( $_GET['completed_page'] ) && 0 < intval( $_GET['completed_page'] ) ) {
				$completed_page = $_GET['completed_page'];
			}
			foreach ( $active_courses as $course_item ) {

				$course_lessons = $woothemes_sensei->post_types->course->course_lessons( $course_item->ID );
				$lessons_completed = 0;
				foreach ( $course_lessons as $lesson ) {
					if ( WooThemes_Sensei_Utils::user_completed_lesson( $lesson->ID, $user->ID ) ) {
						++$lessons_completed;
					}
				}

			    // Get Course Categories
			    $category_output = get_the_term_list( $course_item->ID, 'course-category', '', ', ', '' );

		    	$active_html .= '<article class="' . esc_attr( join( ' ', get_post_class( array( 'course', 'post' ), $course_item->ID ) ) ) . '">';

		    	    // Image
		    		$active_html .= $woothemes_sensei->post_types->course->course_image( absint( $course_item->ID ) );

		    		// Title
		    		$active_html .= '<header>';

		    		    $active_html .= '<h2><a href="' . esc_url( get_permalink( absint( $course_item->ID ) ) ) . '" title="' . esc_attr( $course_item->post_title ) . '">' . esc_html( $course_item->post_title ) . '</a></h2>';

		    		$active_html .= '</header>';

		    		$active_html .= '<section class="entry">';

		    			$active_html .= '<p class="sensei-course-meta">';

		    		    	// Author
		    		    	$user_info = get_userdata( absint( $course_item->post_author ) );
		    		    	if ( isset( $woothemes_sensei->settings->settings[ 'course_author' ] ) && ( $woothemes_sensei->settings->settings[ 'course_author' ] ) ) {
		    		    		$active_html .= '<span class="course-author">' . __( 'by ', 'woothemes-sensei' ) . '<a href="' . esc_url( get_author_posts_url( absint( $course_item->post_author ) ) ) . '" title="' . esc_attr( $user_info->display_name ) . '">' . esc_html( $user_info->display_name ) . '</a></span>';
		    		    	} // End If Statement
		    		    	// Lesson count for this author
		    		    	$lesson_count = $woothemes_sensei->post_types->course->course_lesson_count( absint( $course_item->ID ) );
		    		    	// Handle Division by Zero
							if ( 0 == $lesson_count ) {
								$lesson_count = 1;
							} // End If Statement
		    		    	$active_html .= '<span class="course-lesson-count">' . $lesson_count . '&nbsp;' . apply_filters( 'sensei_lessons_text', __( 'Lessons', 'woothemes-sensei' ) ) . '</span>';
		    		    	// Course Categories
		    		    	if ( '' != $category_output ) {
		    		    		$active_html .= '<span class="course-category">' . sprintf( __( 'in %s', 'woothemes-sensei' ), $category_output ) . '</span>';
		    		    	} // End If Statement
							$active_html .= '<span class="course-lesson-progress">' . sprintf( __( '%1$d of %2$d lessons completed', 'woothemes-sensei' ) , $lessons_completed, $lesson_count  ) . '</span>';

		    		    $active_html .= '</p>';

		    		    $active_html .= '<p class="course-excerpt">' . apply_filters( 'get_the_excerpt', $course_item->post_excerpt ) . '</p>';

		    		   	$progress_percentage = abs( round( ( doubleval( $lessons_completed ) * 100 ) / ( $lesson_count ), 0 ) );

		    		   	if ( 50 < $progress_percentage ) { $class = ' green'; } elseif ( 25 <= $progress_percentage && 50 >= $progress_percentage ) { $class = ' orange'; } else { $class = ' red'; }

		    		   	/* if ( 0 == $progress_percentage ) { $progress_percentage = 5; } */

		    		   	$active_html .= '<div class="meter' . esc_attr( $class ) . '"><span style="width: ' . $progress_percentage . '%">' . $progress_percentage . '%</span></div>';

		    		$active_html .= '</section>';

		    		if( $manage ) {

			    		$active_html .= '<section class="entry-actions">';

			    			$active_html .= '<form method="POST" action="' . remove_query_arg( array( 'active_page', 'completed_page' ) ) . '">';

			    				$active_html .= '<input type="hidden" name="' . esc_attr( 'woothemes_sensei_complete_course_noonce' ) . '" id="' . esc_attr( 'woothemes_sensei_complete_course_noonce' ) . '" value="' . esc_attr( wp_create_nonce( 'woothemes_sensei_complete_course_noonce' ) ) . '" />';

			    				$active_html .= '<input type="hidden" name="course_complete_id" id="course-complete-id" value="' . esc_attr( absint( $course_item->ID ) ) . '" />';

			    				if ( 0 < absint( count( $course_lessons ) ) && $woothemes_sensei->settings->settings['course_completion'] == 'complete' ) {
			    					$active_html .= '<span><input name="course_complete" type="submit" class="course-complete" value="' . apply_filters( 'sensei_mark_as_complete_text', __( 'Mark as Complete', 'woothemes-sensei' ) ) . '"/></span>';
			    				} // End If Statement

			    				$course_purchased = false;
			    				if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() ) {
			    					// Get the product ID
			    					$wc_post_id = get_post_meta( absint( $course_item->ID ), '_course_woocommerce_product', true );
			    					if ( 0 < $wc_post_id ) {
			    						$course_purchased = WooThemes_Sensei_Utils::sensei_customer_bought_product( $user->user_email, $user->ID, $wc_post_id );
			    					} // End If Statement
			    				} // End If Statement

			    				if ( ! $course_purchased ) {
			    					$active_html .= '<span><input name="course_complete" type="submit" class="course-delete" value="' . apply_filters( 'sensei_delete_course_text', __( 'Delete Course', 'woothemes-sensei' ) ) . '"/></span>';
			    				} // End If Statement

			    			$active_html .= '</form>';

			    		$active_html .= '</section>';
			    	}

		    	$active_html .= '</article>';
			}

			// Active pagination
			if( $active_count > $per_page ) {

				$current_page = 1;
				if( isset( $_GET['active_page'] ) && 0 < intval( $_GET['active_page'] ) ) {
					$current_page = $_GET['active_page'];
				}

				$active_html .= '<nav class="pagination woo-pagination">';
				$total_pages = ceil( $active_count / $per_page );

				$link = '';

				if( $current_page > 1 ) {
					$prev_link = add_query_arg( 'active_page', $current_page - 1 );
					$active_html .= '<a class="prev page-numbers" href="' . $prev_link . '">' . __( 'Previous' , 'woothemes-sensei' ) . '</a> ';
				}

				for ( $i = 1; $i <= $total_pages; $i++ ) {
					$link = add_query_arg( 'active_page', $i );

					if( $i == $current_page ) {
						$active_html .= '<span class="page-numbers current">' . $i . '</span> ';
					} else {
						$active_html .= '<a class="page-numbers" href="' . $link . '">' . $i . '</a> ';
					}
				}

				if( $current_page < $total_pages ) {
					$next_link = add_query_arg( 'active_page', $current_page + 1 );
					$active_html .= '<a class="next page-numbers" href="' . $next_link . '">' . __( 'Next' , 'woothemes-sensei' ) . '</a> ';
				}

				$active_html .= '</nav>';
			}

			foreach ( $completed_courses as $course_item ) {
				$course = $course_item;

			    // Get Course Categories
			    $category_output = get_the_term_list( $course_item->ID, 'course-category', '', ', ', '' );

		    	$complete_html .= '<article class="' . join( ' ', get_post_class( array( 'course', 'post' ), $course_item->ID ) ) . '">';

		    	    // Image
		    		$complete_html .= $woothemes_sensei->post_types->course->course_image( absint( $course_item->ID ) );

		    		// Title
		    		$complete_html .= '<header>';

		    		    $complete_html .= '<h2><a href="' . esc_url( get_permalink( absint( $course_item->ID ) ) ) . '" title="' . esc_attr( $course_item->post_title ) . '">' . esc_html( $course_item->post_title ) . '</a></h2>';

		    		$complete_html .= '</header>';

		    		$complete_html .= '<section class="entry">';

		    			$complete_html .= '<p class="sensei-course-meta">';

		    		    	// Author
		    		    	$user_info = get_userdata( absint( $course_item->post_author ) );
		    		    	if ( isset( $woothemes_sensei->settings->settings[ 'course_author' ] ) && ( $woothemes_sensei->settings->settings[ 'course_author' ] ) ) {
		    		    		$complete_html .= '<span class="course-author">' . __( 'by ', 'woothemes-sensei' ) . '<a href="' . esc_url( get_author_posts_url( absint( $course_item->post_author ) ) ) . '" title="' . esc_attr( $user_info->display_name ) . '">' . esc_html( $user_info->display_name ) . '</a></span>';
		    		    	} // End If Statement

		    		    	// Lesson count for this author
		    		    	$complete_html .= '<span class="course-lesson-count">' . $woothemes_sensei->post_types->course->course_lesson_count( absint( $course_item->ID ) ) . '&nbsp;' . apply_filters( 'sensei_lessons_text', __( 'Lessons', 'woothemes-sensei' ) ) . '</span>';
		    		    	// Course Categories
		    		    	if ( '' != $category_output ) {
		    		    		$complete_html .= '<span class="course-category">' . sprintf( __( 'in %s', 'woothemes-sensei' ), $category_output ) . '</span>';
		    		    	} // End If Statement

						$complete_html .= '</p>';

						$complete_html .= '<p class="course-excerpt">' . apply_filters( 'get_the_excerpt', $course_item->post_excerpt ) . '</p>';

						$complete_html .= '<div class="meter green"><span style="width: 100%">100%</span></div>';

						if( $manage ) {
							$has_quizzes = $woothemes_sensei->post_types->course->course_quizzes( $course_item->ID, true );
							// Output only if there is content to display
							if ( has_filter( 'sensei_results_links' ) || $has_quizzes ) {
								$complete_html .= '<p class="sensei-results-links">';
								$results_link = '';
								if( $has_quizzes ) {
									$results_link = '<a class="button view-results" href="' . $woothemes_sensei->course_results->get_permalink( $course_item->ID ) . '">' . apply_filters( 'sensei_view_results_text', __( 'View results', 'woothemes-sensei' ) ) . '</a>';
								}
								$complete_html .= apply_filters( 'sensei_results_links', $results_link );
								$complete_html .= '</p>';
							}
						}

		    		$complete_html .= '</section>';

		    	$complete_html .= '</article>';
			}

			// Active pagination
			if( $completed_count > $per_page ) {

				$current_page = 1;
				if( isset( $_GET['completed_page'] ) && 0 < intval( $_GET['completed_page'] ) ) {
					$current_page = $_GET['completed_page'];
				}

				$complete_html .= '<nav class="pagination woo-pagination">';
				$total_pages = ceil( $completed_count / $per_page );

				$link = '';

				if( $current_page > 1 ) {
					$prev_link = add_query_arg( 'completed_page', $current_page - 1 );
					$complete_html .= '<a class="prev page-numbers" href="' . $prev_link . '">' . __( 'Previous' , 'woothemes-sensei' ) . '</a> ';
				}

				for ( $i = 1; $i <= $total_pages; $i++ ) {
					$link = add_query_arg( 'completed_page', $i );

					if( $i == $current_page ) {
						$complete_html .= '<span class="page-numbers current">' . $i . '</span> ';
					} else {
						$complete_html .= '<a class="page-numbers" href="' . $link . '">' . $i . '</a> ';
					}
				}

				if( $current_page < $total_pages ) {
					$next_link = add_query_arg( 'completed_page', $current_page + 1 );
					$complete_html .= '<a class="next page-numbers" href="' . $next_link . '">' . __( 'Next' , 'woothemes-sensei' ) . '</a> ';
				}

				$complete_html .= '</nav>';
			}

		} // End If Statement

		if( $manage ) {
			$no_active_message = apply_filters( 'sensei_no_active_courses_user_text', __( 'You have no active courses.', 'woothemes-sensei' ) );
			$no_complete_message = apply_filters( 'sensei_no_complete_courses_user_text', __( 'You have not completed any courses yet.', 'woothemes-sensei' ) );
		} else {
			$no_active_message = apply_filters( 'sensei_no_active_courses_learner_text', __( 'This learner has no active courses.', 'woothemes-sensei' ) );
			$no_complete_message = apply_filters( 'sensei_no_complete_courses_learner_text', __( 'This learner has not completed any courses yet.', 'woothemes-sensei' ) );
		}

		ob_start();
		?>

		<?php do_action( 'sensei_before_user_courses' ); ?>

		<?php
		if( $manage && ( ! isset( $woothemes_sensei->settings->settings['messages_disable'] ) || ! $woothemes_sensei->settings->settings['messages_disable'] ) ) {
			?>
			<p class="my-messages-link-container"><a class="my-messages-link" href="<?php echo get_post_type_archive_link( 'sensei_message' ); ?>" title="<?php _e( 'View & reply to private messages sent to your course & lesson teachers.', 'woothemes-sensei' ); ?>"><?php _e( 'My Messages', 'woothemes-sensei' ); ?></a></p>
			<?php
		}
		?>
		<div id="my-courses">

		    <ul>
		    	<li><a href="#active-courses"><?php echo apply_filters( 'sensei_active_courses_text', __( 'Active Courses', 'woothemes-sensei' ) ); ?></a></li>
		    	<li><a href="#completed-courses"><?php echo apply_filters( 'sensei_completed_courses_text', __( 'Completed Courses', 'woothemes-sensei' ) ); ?></a></li>
		    </ul>

		    <?php do_action( 'sensei_before_active_user_courses' ); ?>

		    <?php $course_page_id = intval( $woothemes_sensei->settings->settings[ 'course_page' ] );
		    	if ( 0 < $course_page_id ) {
		    		$course_page_url = get_permalink( $course_page_id );
		    	} elseif ( 0 == $course_page_id ) {
		    		$course_page_url = get_post_type_archive_link( 'course' );
		    	} ?>

		    <div id="active-courses">

		    	<?php if ( '' != $active_html ) {
		    		echo $active_html;
		    	} else { ?>
		    		<div class="sensei-message info"><?php echo $no_active_message; ?> <a href="<?php echo $course_page_url; ?>"><?php apply_filters( 'sensei_start_a_course_text', _e( 'Start a Course!', 'woothemes-sensei' ) ); ?></a></div>
		    	<?php } // End If Statement ?>

		    </div>

		    <?php do_action( 'sensei_after_active_user_courses' ); ?>

		    <?php do_action( 'sensei_before_completed_user_courses' ); ?>

		    <div id="completed-courses">

		    	<?php if ( '' != $complete_html ) {
		    		echo $complete_html;
		    	} else { ?>
		    		<div class="sensei-message info"><?php echo $no_complete_message; ?></div>
		    	<?php } // End If Statement ?>

		    </div>

		    <?php do_action( 'sensei_after_completed_user_courses' ); ?>

		</div>

		<?php do_action( 'sensei_after_user_courses' ); ?>

		<?php
		return ob_get_clean();
	}

} // End Class