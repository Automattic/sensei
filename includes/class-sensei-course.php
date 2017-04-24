<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

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
    public  $my_courses_page;

	/**
	 * @var array The HTML allowed for message boxes.
	 */
	public static $allowed_html;

	/**
	 * Constructor.
	 * @since  1.0.0
	 */
	public function __construct () {

        $this->token = 'course';

		// Setup meta fields for this post type
		$this->meta_fields = array( 'course_prerequisite', 'course_featured', 'course_video_embed', 'course_woocommerce_product' );
		// Admin actions
		if ( is_admin() ) {
			// Metabox functions
            add_action( 'add_meta_boxes', array( $this, 'meta_box_setup' ), 20 );
			add_action( 'save_post', array( $this, 'meta_box_save' ) );
			// Custom Write Panel Columns
			add_filter( 'manage_edit-course_columns', array( $this, 'add_column_headings' ), 10, 1 );
			add_action( 'manage_posts_custom_column', array( $this, 'add_column_data' ), 10, 2 );
		} else {
			$this->my_courses_page = false;
		} // End If Statement

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
			'source' => Sensei_Wp_Kses::get_source_html_tag_allowed_attributes()
		);

		// Update course completion upon completion of a lesson
		add_action( 'sensei_user_lesson_end', array( $this, 'update_status_after_lesson_change' ), 10, 2 );
		// Update course completion upon reset of a lesson
		add_action( 'sensei_user_lesson_reset', array( $this, 'update_status_after_lesson_change' ), 10, 2 );
		// Update course completion upon grading of a quiz
		add_action( 'sensei_user_quiz_grade', array( $this, 'update_status_after_quiz_submission' ), 10, 2 );

        // show the progress bar ont he single course page
        add_action( 'sensei_single_course_content_inside_before' , array( $this, 'the_progress_statement' ), 15 );
        add_action( 'sensei_single_course_content_inside_before' , array( $this, 'the_progress_meter' ), 16 );

        // provide an option to block all emails related to a selected course
        add_filter( 'sensei_send_emails', array( $this, 'block_notification_emails' ) );
        add_action( 'save_post', array( $this, 'save_course_notification_meta_box' ) );

        // preview lessons on the course content
        add_action( 'sensei_course_content_inside_after',array( $this, 'the_course_free_lesson_preview' ) );

        // the course meta
        add_action('sensei_course_content_inside_before', array( $this, 'the_course_meta' ) );

        // backwards compatible template hooks
        add_action('sensei_course_content_inside_before', array( $this, 'content_before_backwards_compatibility_hooks' ));
        add_action('sensei_loop_course_before', array( $this,'loop_before_backwards_compatibility_hooks' ) );

        // add the user status on the course to the markup as a class
        add_filter('post_class', array( __CLASS__ , 'add_course_user_status_class' ), 20, 3 );

        //filter the course query in Sensei specific instances
        add_filter( 'pre_get_posts', array( __CLASS__, 'course_query_filter' ) );

        //attache the sorting to the course archive
        add_action ( 'sensei_archive_before_course_loop' , array( 'Sensei_Course', 'course_archive_sorting' ) );

        //attach the filter links to the course archive
        add_action ( 'sensei_archive_before_course_loop' , array( 'Sensei_Course', 'course_archive_filters' ) );

        //filter the course query when featured filter is applied
        add_filter( 'pre_get_posts',  array( __CLASS__, 'course_archive_featured_filter'));

        // handle the order by title post submission
        add_filter( 'pre_get_posts',  array( __CLASS__, 'course_archive_order_by_title'));

        // ensure the course category page respects the manual order set for courses
        add_filter( 'pre_get_posts',  array( __CLASS__, 'alter_course_category_order'));

        // flush rewrite rules when saving a course
        add_action('save_post', array( 'Sensei_Course', 'flush_rewrite_rules' ) );

		// Allow course archive to be setup as the home page
		if ( (int) get_option( 'page_on_front' ) > 0 ) {
			add_action( 'pre_get_posts', array( $this, 'allow_course_archive_on_front_page' ) );
		}
	}

	/**
	 * @param $message
	 */
	private static function add_course_access_permission_message( $message )
	{
		global $post;
		if ( Sensei()->settings->get('access_permission') ) {
			$message = apply_filters( 'sensei_couse_access_permission_message', $message, $post->ID );
			if (!empty($message)) {
				Sensei()->notices->add_notice($message, 'info');
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
	 * meta_box_setup function.
	 *
	 * @access public
	 * @return void
	 */
	public function meta_box_setup () {

		if ( Sensei_WC::is_woocommerce_active() ) {
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
        // Add Meta Box to link to Manage Learners
        add_meta_box( 'course-manage', __( 'Course Management', 'woothemes-sensei' ), array( $this, 'course_manage_meta_box_content' ), $this->token, 'side', 'default' );
        // Remove "Custom Settings" meta box.
		remove_meta_box( 'woothemes-settings', $this->token, 'normal' );

        // add Disable email notification box
        add_meta_box( 'course-notifications', __( 'Course Notifications', 'woothemes-sensei' ), array( $this, 'course_notification_meta_box_content' ), 'course', 'normal', 'default' );

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
							'posts_per_page' 		=> -1,
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

						$product_object = Sensei_WC_Utils::get_product( $post_item->ID );

						if ( empty( $product_object ) ) {
							// Product variation has been orphaned. Treat it like it has also been deleted.
							continue;
						}

						$parent_id = wp_get_post_parent_id( $post_item->ID );

                        if( sensei_check_woocommerce_version( '2.1' ) ) {
							$formatted_variation = wc_get_formatted_variation( Sensei_WC_Utils::get_variation_data( $product_object ), true );

						} else {
                            // fall back to pre wc 2.1
							$formatted_variation = woocommerce_get_formatted_variation( Sensei_WC_Utils::get_variation_data( $product_object ), true );

						}

                        $product_name = ucwords( $formatted_variation );
                        if ( empty( $product_name ) ) {
                            $product_name = __( 'Variation #', 'woothemes-sensei' ) . Sensei_WC_Utils::get_product_variation_id( $product_object );
                        }
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
				if ( !empty( $select_course_woocommerce_product ) ) {
					$html .= '<input type="hidden" name="course_woocommerce_product" value="'. absint( $select_course_woocommerce_product ) . '">';
				}
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
							'posts_per_page' 		=> -1,
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
		$course_video_embed = Sensei_Wp_Kses::maybe_sanitize( $course_video_embed, self::$allowed_html );

		$html = '';

		$html .= '<label class="screen-reader-text" for="course_video_embed">' . __( 'Video Embed Code', 'woothemes-sensei' ) . '</label>';
		$html .= '<textarea rows="5" cols="50" name="course_video_embed" tabindex="6" id="course-video-embed">';

		$html .= $course_video_embed . '</textarea><p>';

		$html .= __( 'Paste the embed code for your video (e.g. YouTube, Vimeo etc.) in the box above.', 'woothemes-sensei' ) . '</p>';

		echo $html;

	} // End course_video_meta_box_content()

	/**
	 * meta_box_save function.
	 *
	 * Handles saving the meta data
	 *
	 * @access public
	 * @param int $post_id
	 * @return int
	 */
	public function meta_box_save ( $post_id ) {
		global $post;

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
	 * @return int new meta id | bool meta value saved status
	 */
	private function save_post_meta( $post_key = '', $post_id = 0 ) {
		// Get the meta key.
		$meta_key = '_' . $post_key;
		// Get the posted data and sanitize it for use as an HTML class.
		if ( 'course_video_embed' == $post_key ) {
			$new_meta_value = ( isset( $_POST[ $post_key ] ) ) ? $_POST[ $post_key ] : '';
			$new_meta_value = Sensei_Wp_Kses::maybe_sanitize( $new_meta_value, self::$allowed_html );
		} else {
			$new_meta_value = ( isset( $_POST[$post_key] ) ? sanitize_html_class( $_POST[$post_key] ) : '' );
		} // End If Statement

        // update field with the new value
        return update_post_meta( $post_id, $meta_key, $new_meta_value );

	} // End save_post_meta()

	/**
	 * course_lessons_meta_box_content function.
	 *
	 * @access public
	 * @return void
	 */
	public function course_lessons_meta_box_content () {

		global $post;

		// Setup Lesson Query
		$posts_array = array();
		if ( 0 < $post->ID ) {

			$posts_array = $this->course_lessons( $post->ID, 'any' );

		} // End If Statement

		$html = '';
		$html .= '<input type="hidden" name="' . esc_attr( 'woo_' . $this->token . '_noonce' ) . '" id="'
                 . esc_attr( 'woo_' . $this->token . '_noonce' )
                 . '" value="' . esc_attr( wp_create_nonce( plugin_basename(__FILE__) ) ) . '" />';

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

				$html .= '<a href="' . admin_url( 'post-new.php?post_type=lesson' . $course_id )
                         . '" title="' . esc_attr( __( 'Add a Lesson', 'woothemes-sensei' ) ) . '">'
                         . __( 'Please add some.', 'woothemes-sensei' ) . '</a>' . "\n";

			$html .= '</p>'."\n";
		} // End If Statement

		echo $html;

	} // End course_lessons_meta_box_content()

    /**
     * course_manage_meta_box_content function.
     *
     * @since 1.9.0
     * @access public
     * @return void
     */

    public function course_manage_meta_box_content () {
        global $post;

        $manage_url = esc_url( add_query_arg( array( 'page' => 'sensei_learners', 'course_id' => $post->ID, 'view' => 'learners' ), admin_url( 'admin.php') ) );

        $grading_url = esc_url( add_query_arg( array( 'page' => 'sensei_grading', 'course_id' => $post->ID, 'view' => 'learners' ), admin_url( 'admin.php') ) );


        echo "<ul><li><a href='$manage_url'>".__("Manage Learners", 'woothemes-sensei')."</a></li>";

        echo "<li><a href='$grading_url'>".__("Manage Grading", 'woothemes-sensei')."</a></li></ul>";



    } // End course_manage_meta_box_content()

	/**
	 * Add column headings to the "lesson" post list screen.
	 * @access public
	 * @since  1.0.0
	 * @param  array $defaults
	 * @return array $new_columns
	 */
	public function add_column_headings ( $defaults ) {
		$new_columns['cb'] = '<input type="checkbox" />';
		$new_columns['title'] = _x( 'Course Title', 'column name', 'woothemes-sensei' );
		$new_columns['course-prerequisite'] = _x( 'Pre-requisite Course', 'column name', 'woothemes-sensei' );
		if ( Sensei_WC::is_woocommerce_active() ) {
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
				if ( Sensei_WC::is_woocommerce_active() ) {
					$course_woocommerce_product_id = get_post_meta( $id, '_course_woocommerce_product', true);
					if ( 0 < absint( $course_woocommerce_product_id ) ) {
						if ( 'product_variation' == get_post_type( $course_woocommerce_product_id ) ) {
							$product_object = Sensei_WC_Utils::get_product( $course_woocommerce_product_id );
							if( sensei_check_woocommerce_version( '2.1' ) ) {
								$formatted_variation = wc_get_formatted_variation( Sensei_WC_Utils::get_product_variation_data( $product_object ), true );
							} else {
								$formatted_variation = Sensei_WC_Utils::get_formatted_variation( Sensei_WC_Utils::get_product_variation_data( $product_object ), true );
							}
							$course_woocommerce_product_id = Sensei_WC_Utils::get_product_id( $product_object );
							$parent = Sensei_WC_Utils::get_parent_product( $product_object );
							$product_name = $parent->get_title() . '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . ucwords( $formatted_variation );
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
	 * @return array
	 */
	public function course_query( $amount = 0, $type = 'default', $includes = array(), $excludes = array() ) {
		global $my_courses_page ;

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
	 * @return array
	 */
	public function get_archive_query_args( $type = '', $amount = 0 , $includes = array(), $excludes = array() ) {

		global $wp_query;

		if ( 0 == $amount && ( isset( Sensei()->settings->settings[ 'course_archive_amount' ] ) && 'usercourses' != $type && ( 0 < absint( Sensei()->settings->settings[ 'course_archive_amount' ] ) ) ) ) {
			$amount = absint( Sensei()->settings->settings[ 'course_archive_amount' ] );
		} else {
			if ( 0 == $amount) {
				$amount = $wp_query->get( 'posts_per_page' );
			} // End If Statement
		} // End If Statement

        $stored_order = get_option( 'sensei_course_order', '' );
        $order = 'ASC';
        $orderby = 'menu_order';
        if( empty( $stored_order ) ){

            $order = 'DESC';
            $orderby = 'date';

        }

		switch ($type) {

			case 'usercourses':
				$post_args = array(	'post_type' 		=> 'course',
									'orderby'         	=> $orderby,
    								'order'           	=> $order,
    								'post_status'      	=> 'publish',
    								'include'			=> $includes,
    								'exclude'			=> $excludes,
    								'suppress_filters' 	=> 0
									);
				break;
			case 'freecourses':

                $post_args = array(
                    'post_type' 		=> 'course',
                    'orderby'         	=> $orderby,
                    'order'           	=> $order,
                    'post_status'      	=> 'publish',
                    'exclude'			=> $excludes,
                    'suppress_filters' 	=> 0
                );
                // Sub Query to get all WooCommerce Products that have Zero price
                $post_args['meta_query'] = Sensei_WC::get_free_courses_meta_query_args();

                break;

			case 'paidcourses':

                $post_args = array(
                    'post_type' 		=> 'course',
                    'orderby'         	=> $orderby,
                    'order'           	=> $order,
                    'post_status'      	=> 'publish',
                    'exclude'			=> $excludes,
                    'suppress_filters' 	=> 0
                );

                // Sub Query to get all WooCommerce Products that have price greater than zero
                $post_args['meta_query'] = Sensei_WC::get_paid_courses_meta_query_args();

				break;

			case 'featuredcourses':
                $post_args = array(	'post_type' 		=> 'course',
                                    'orderby'         	=> $orderby,
                                    'order'           	=> $order,
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
                                    'orderby'         	=> $orderby,
                                    'order'           	=> $order,
    								'post_status'      	=> 'publish',
    								'exclude'			=> $excludes,
    								'suppress_filters' 	=> 0
									);
				break;

		}

        $post_args['posts_per_page'] = $amount;
        $paged = $wp_query->get( 'paged' );
        $post_args['paged'] = empty( $paged) ? 1 : $paged;

        if( 'newcourses' == $type ){

            $post_args[ 'orderby' ] = 'date';
            $post_args[ 'order' ] = 'DESC';
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
	 * @param string $width (default: '100')
	 * @param string $height (default: '100')
     * @param bool $return default false
     *
	 * @return string | void
	 */
	public function course_image( $course_id = 0, $width = '100', $height = '100', $return = false ) {

        if ( is_a( $course_id, 'WP_Post' ) ) {

	        $course_id = $course_id->ID;

        }

		if ( 'course' !== get_post_type( $course_id )  ){

			return;

		}

		$html = '';

		// Get Width and Height settings
		if ( ( $width == '100' ) && ( $height == '100' ) ) {

			if ( is_singular( 'course' ) ) {

				if ( !Sensei()->settings->settings[ 'course_single_image_enable' ] ) {
					return '';
				} // End If Statement
				$image_thumb_size = 'course_single_image';
				$dimensions = Sensei()->get_image_size( $image_thumb_size );
				$width = $dimensions['width'];
				$height = $dimensions['height'];

			} else {

				if ( !Sensei()->settings->settings[ 'course_archive_image_enable' ] ) {
					return '';
				} // End If Statement

				$image_thumb_size = 'course_archive_image';
				$dimensions = Sensei()->get_image_size( $image_thumb_size );
				$width = $dimensions['width'];
				$height = $dimensions['height'];

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
				if ( Sensei()->settings->get( 'placeholder_images_enable' ) ) {

                    $img_url = apply_filters( 'sensei_course_placeholder_image_url', '<img src="http://placehold.it/' . $width . 'x' . $height . '" class="woo-image thumbnail alignleft" />' );

				} // End If Statement

 			} // End If Statement

		} // End If Statement

		if ( '' != $img_url ) {

			$html .= '<a href="' . get_permalink( $course_id ) . '" title="' . esc_attr( get_post_field( 'post_title', $course_id ) ) . '">' . $img_url  .'</a>';

		} // End If Statement

        if( $return ){

            return $html;

        }else{

            echo $html;

        }

	} // End course_image()


	/**
	 * course_count function.
	 *
	 * @access public
	 * @param array $exclude (default: array())
	 * @param string $post_status (default: 'publish')
	 * @return int
	 */
	public function course_count( $post_status = 'publish' ) {

		$post_args = array(	'post_type'         => 'course',
							'posts_per_page'    => -1,
							'post_status'       => $post_status,
							'suppress_filters'  => 0,
							'fields'            => 'ids',
							);

		// Allow WP to generate the complex final query, just shortcut to only do an overall count
		$courses_query = new WP_Query( apply_filters( 'sensei_course_count', $post_args ) );

		return count( $courses_query->posts );
	} // End course_count()


	/**
	 * course_lessons function.
	 *
	 * @access public
	 * @param int $course_id (default: 0)
	 * @param string $post_status (default: 'publish')
	 * @param string $fields (default: 'all'). WP only allows 3 types, but we will limit it to only 'ids' or 'all'
	 * @return array{ type WP_Post }  $posts_array
	 */
	public function course_lessons( $course_id = 0, $post_status = 'publish', $fields = 'all' ) {

        if( is_a( $course_id, 'WP_Post' ) ){
            $course_id = $course_id->ID;
        }

		$post_args = array(	'post_type'         => 'lesson',
							'posts_per_page'       => -1,
							'orderby'           => 'date',
							'order'             => 'ASC',
							'meta_query'        => array(
								array(
									'key' => '_lesson_course',
									'value' => intval( $course_id ),
								),
							),
							'post_status'       => $post_status,
							'suppress_filters'  => 0,
							);
		$query_results = new WP_Query( $post_args );
        $lessons = $query_results->posts;

        // re order the lessons. This could not be done via the OR meta query as there may be lessons
        // with the course order for a different course and this should not be included. It could also not
        // be done via the AND meta query as it excludes lesson that does not have the _order_$course_id but
        // that have been added to the course.
        if( count( $lessons) > 1  ){

            foreach( $lessons as $lesson ){

                $order = intval( get_post_meta( $lesson->ID, '_order_'. $course_id, true ) );
                // for lessons with no order set it to be 10000 so that it show up at the end
                $lesson->course_order = $order ? $order : 100000;
            }

            uasort( $lessons, array( $this, '_short_course_lessons_callback' )   );
        }

        /**
         * Filter runs inside Sensei_Course::course_lessons function
         *
         * Returns all lessons for a given course
         *
         * @param array $lessons
         * @param int $course_id
         */
        $lessons = apply_filters( 'sensei_course_get_lessons', $lessons, $course_id  );

        //return the requested fields
        // runs after the sensei_course_get_lessons filter so the filter always give an array of lesson
        // objects
        if( 'ids' == $fields ) {
            $lesson_objects = $lessons;
            $lessons = array();

            foreach ($lesson_objects as $lesson) {
                $lessons[] = $lesson->ID;
            }
        }

        return $lessons;

	} // End course_lessons()

    /**
     * Used for the uasort in $this->course_lessons()
     * @since 1.8.0
     * @access protected
     *
     * @param array $lesson_1
     * @param array $lesson_2
     * @return int
     */
    protected function _short_course_lessons_callback( $lesson_1, $lesson_2 ){

        if ( $lesson_1->course_order == $lesson_2->course_order ) {
            return 0;
        }

        return ($lesson_1->course_order < $lesson_2->course_order) ? -1 : 1;
    }

	/**
	 * Fetch all quiz ids in a course
	 * @since  1.5.0
	 * @param  integer $course_id ID of course
	 * @param  boolean $boolean_check True if a simple yes/no is required
	 * @return array              Array of quiz post objects
	 */
	public function course_quizzes( $course_id = 0, $boolean_check = false ) {


		$course_quizzes = array();

		if( $course_id ) {
			$lesson_ids = Sensei()->course->course_lessons( $course_id, 'any', 'ids' );

			foreach( $lesson_ids as $lesson_id ) {
				$has_questions = get_post_meta( $lesson_id, '_quiz_has_questions', true );
				if ( $has_questions && $boolean_check ) {
					return true;
				}
				elseif ( $has_questions ) {
					$quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_id );
					$course_quizzes[] = $quiz_id;
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

        $lesson_args = array(	'post_type' 		=> 'lesson',
								'posts_per_page' 		=> -1,
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

		$lesson_args = array(	'post_type' 		=> 'lesson',
								'posts_per_page' 		=> -1,
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

		$lesson_args = array(	'post_type' 		=> 'lesson',
								'posts_per_page' 		=> -1,
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

		if ( ! Sensei_WC::is_woocommerce_active() || empty( $product_id ) ) {
			return array();
		}

		//check for variation
		$product = wc_get_product( $product_id );

		if ( ! is_object( $product ) ) {
			return array();
		}

		if ( in_array( $product->get_type(), array( 'variable-subscription', 'variable' ) ) ) {

			$variations = $product->get_available_variations();
			$courses    = array();

			// possibly check if the course is not linked to the variation parent product
			$variation_parent_courses = get_posts( self::get_product_courses_query_args( $product_id ) );

			if ( ! empty( $variation_parent_courses ) ) {
				$courses           = array_merge( $courses, $variation_parent_courses );
			}

			foreach ( $variations as $variation ) {

				$variation_courses = get_posts( self::get_product_courses_query_args( $variation['variation_id'] ) );
				$courses           = array_merge( $courses, $variation_courses );

			}

			return $courses;

		} else {

			return get_posts( self::get_product_courses_query_args( $product->get_id() ) );

		}

	} // End get_product_courses()

	/**
	 * @param $product_id
	 *
	 * @return array
	 */
	public static function get_product_courses_query_args ( $product_id ) {

		return array(	'post_type' 		=> 'course',
		                 'posts_per_page' 		=> -1,
		                 'meta_key'        	=> '_course_woocommerce_product',
		                 'meta_value'      	=> $product_id,
		                 'post_status'       => 'publish',
		                 'suppress_filters' 	=> 0,
		                 'orderby' 			=> 'menu_order date',
		                 'order' 			=> 'ASC',
		);

	}

	/**
	 * Fix posts_per_page for My Courses page
	 * @param  WP_Query $query
	 * @return void
	 */
	public function filter_my_courses( $query ) {
		global  $my_courses_section;

		if ( isset( Sensei()->settings->settings[ 'my_course_amount' ] ) && ( 0 < absint( Sensei()->settings->settings[ 'my_course_amount' ] ) ) ) {
			$amount = absint( Sensei()->settings->settings[ 'my_course_amount' ] );
			$query->set( 'posts_per_page', $amount );
		}

		if( isset( $_GET[ $my_courses_section . '_page' ] ) && 0 < intval( $_GET[ $my_courses_section . '_page' ] ) ) {
			$page = intval( $_GET[ $my_courses_section . '_page' ] );
			$query->set( 'paged', $page );
		}
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
		global $course, $my_courses_page, $my_courses_section;

        if( ! isset( Sensei()->settings->settings[ 'learner_profile_show_courses' ] )
            || ! Sensei()->settings->settings[ 'learner_profile_show_courses' ] ) {

            // do not show the content if the settings doesn't allow for it
            return;

        }

        $manage = ( $user->ID == get_current_user_id() ) ? true : false;

        do_action( 'sensei_before_learner_course_content', $user );

		// Build Output HTML
		$complete_html = $active_html = '';

		if( is_a( $user, 'WP_User' ) ) {

			$my_courses_page = true;

			// Allow action to be run before My Courses content has loaded
			do_action( 'sensei_before_my_courses', $user->ID );

			// Logic for Active and Completed Courses
			$per_page = 20;
			if ( isset( Sensei()->settings->settings[ 'my_course_amount' ] )
                && ( 0 < absint( Sensei()->settings->settings[ 'my_course_amount' ] ) ) ) {

				$per_page = absint( Sensei()->settings->settings[ 'my_course_amount' ] );

			}

			$course_statuses = Sensei_Utils::sensei_check_for_activity( array( 'user_id' => $user->ID, 'type' => 'sensei_course_status' ), true );
			// User may only be on 1 Course
			if ( !is_array($course_statuses) ) {
				$course_statuses = array( $course_statuses );
			}
			$completed_ids = $active_ids = array();
			foreach( $course_statuses as $course_status ) {
				if ( Sensei_Utils::user_completed_course( $course_status, $user->ID ) ) {
					$completed_ids[] = $course_status->comment_post_ID;
				} else {
					$active_ids[] = $course_status->comment_post_ID;
				}
			}

			$active_count = $completed_count = 0;

			$active_courses = array();
			if ( 0 < intval( count( $active_ids ) ) ) {
				$my_courses_section = 'active';
				$active_courses = Sensei()->course->course_query( $per_page, 'usercourses', $active_ids );
				$active_count = count( $active_ids );
			} // End If Statement

			$completed_courses = array();
			if ( 0 < intval( count( $completed_ids ) ) ) {
				$my_courses_section = 'completed';
				$completed_courses = Sensei()->course->course_query( $per_page, 'usercourses', $completed_ids );
				$completed_count = count( $completed_ids );
			} // End If Statement

			foreach ( $active_courses as $course_item ) {

				$course_lessons =  Sensei()->course->course_lessons( $course_item->ID );
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
                $active_html .= Sensei()->course->course_image( absint( $course_item->ID ), '100','100', true );

                // Title
                $active_html .= '<header>';

                $active_html .= '<h2><a href="' . esc_url( get_permalink( absint( $course_item->ID ) ) ) . '" title="' . esc_attr( $course_item->post_title ) . '">' . esc_html( $course_item->post_title ) . '</a></h2>';

                $active_html .= '</header>';

                $active_html .= '<section class="entry">';

                $active_html .= '<p class="sensei-course-meta">';

                // Author
                $user_info = get_userdata( absint( $course_item->post_author ) );
                if ( isset( Sensei()->settings->settings[ 'course_author' ] )
                    && ( Sensei()->settings->settings[ 'course_author' ] ) ) {

                    $active_html .= '<span class="course-author">'
                        . __( 'by ', 'woothemes-sensei' )
                        . '<a href="' . esc_url( get_author_posts_url( absint( $course_item->post_author ) ) )
                        . '" title="' . esc_attr( $user_info->display_name ) . '">'
                        . esc_html( $user_info->display_name )
                        . '</a></span>';

                } // End If Statement

                // Lesson count for this author
                $lesson_count = Sensei()->course->course_lesson_count( absint( $course_item->ID ) );
                // Handle Division by Zero
                if ( 0 == $lesson_count ) {

                    $lesson_count = 1;

                } // End If Statement
                $active_html .= '<span class="course-lesson-count">' . $lesson_count . '&nbsp;' .  __( 'Lessons', 'woothemes-sensei' ) . '</span>';
                // Course Categories
                if ( '' != $category_output ) {

                    $active_html .= '<span class="course-category">' . sprintf( __( 'in %s', 'woothemes-sensei' ), $category_output ) . '</span>';

                } // End If Statement
                $active_html .= '<span class="course-lesson-progress">' . sprintf( __( '%1$d of %2$d lessons completed', 'woothemes-sensei' ) , $lessons_completed, $lesson_count  ) . '</span>';

                $active_html .= '</p>';

                $active_html .= '<p class="course-excerpt">' . $course_item->post_excerpt . '</p>';



                $progress_percentage = Sensei_Utils::quotient_as_absolute_rounded_percentage( $lessons_completed, $lesson_count, 0 );

                $active_html .= $this->get_progress_meter( $progress_percentage );

                $active_html .= '</section>';

                if( is_user_logged_in() ) {

                    $active_html .= '<section class="entry-actions">';

                    $active_html .= '<form method="POST" action="' . esc_url( remove_query_arg( array( 'active_page', 'completed_page' ) ) ) . '">';

                    $active_html .= '<input type="hidden" name="' . esc_attr( 'woothemes_sensei_complete_course_noonce' ) . '" id="' . esc_attr( 'woothemes_sensei_complete_course_noonce' ) . '" value="' . esc_attr( wp_create_nonce( 'woothemes_sensei_complete_course_noonce' ) ) . '" />';

                    $active_html .= '<input type="hidden" name="course_complete_id" id="course-complete-id" value="' . esc_attr( absint( $course_item->ID ) ) . '" />';

                    if ( 0 < absint( count( $course_lessons ) )
                        && Sensei()->settings->settings['course_completion'] == 'complete' ){

                        $active_html .= '<span><input name="course_complete" type="submit" class="course-complete" value="'
                            .  __( 'Mark as Complete', 'woothemes-sensei' ) . '"/> </span>';

                    } // End If Statement

                    $course_purchased = false;
                    if ( Sensei_WC::is_woocommerce_active() ) {

                        // Get the product ID
                        $wc_post_id = get_post_meta( absint( $course_item->ID ), '_course_woocommerce_product', true );
                        if ( 0 < $wc_post_id ) {

                            $course_purchased = Sensei_WC::has_customer_bought_product(  $user->ID, $wc_post_id );

                        } // End If Statement

                    } // End If Statement

	                /**
	                 * documented in class-sensei-course.php the_course_action_buttons function
	                 */
	                $show_delete_course_button = apply_filters( 'sensei_show_delete_course_button', false );

                    if ( false == $course_purchased && $show_delete_course_button ) {

                        $active_html .= '<span><input name="course_complete" type="submit" class="course-delete" value="'
                            .  __( 'Delete Course', 'woothemes-sensei' ) . '"/></span>';

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

				if( $current_page > 1 ) {
					$prev_link = add_query_arg( 'active_page', $current_page - 1 );
					$active_html .= '<a class="prev page-numbers" href="' . esc_url( $prev_link ) . '">' . __( 'Previous' , 'woothemes-sensei' ) . '</a> ';
				}

				for ( $i = 1; $i <= $total_pages; $i++ ) {
					$link = add_query_arg( 'active_page', $i );

					if( $i == $current_page ) {
						$active_html .= '<span class="page-numbers current">' . $i . '</span> ';
					} else {
						$active_html .= '<a class="page-numbers" href="' . esc_url( $link ). '">' . $i . '</a> ';
					}
				}

				if( $current_page < $total_pages ) {
					$next_link = add_query_arg( 'active_page', $current_page + 1 );
					$active_html .= '<a class="next page-numbers" href="' . esc_url( $next_link ) . '">' . __( 'Next' , 'woothemes-sensei' ) . '</a> ';
				}

				$active_html .= '</nav>';
			}

			foreach ( $completed_courses as $course_item ) {
				$course = $course_item;

			    // Get Course Categories
			    $category_output = get_the_term_list( $course_item->ID, 'course-category', '', ', ', '' );

		    	$complete_html .= '<article class="' . join( ' ', get_post_class( array( 'course', 'post' ), $course_item->ID ) ) . '">';

		    	    // Image
		    		$complete_html .= Sensei()->course->course_image( absint( $course_item->ID ),100, 100, true );

		    		// Title
		    		$complete_html .= '<header>';

		    		    $complete_html .= '<h2><a href="' . esc_url( get_permalink( absint( $course_item->ID ) ) ) . '" title="' . esc_attr( $course_item->post_title ) . '">' . esc_html( $course_item->post_title ) . '</a></h2>';

		    		$complete_html .= '</header>';

		    		$complete_html .= '<section class="entry">';

		    			$complete_html .= '<p class="sensei-course-meta">';

		    		    	// Author
		    		    	$user_info = get_userdata( absint( $course_item->post_author ) );
		    		    	if ( isset( Sensei()->settings->settings[ 'course_author' ] ) && ( Sensei()->settings->settings[ 'course_author' ] ) ) {
		    		    		$complete_html .= '<span class="course-author">' . __( 'by ', 'woothemes-sensei' ) . '<a href="' . esc_url( get_author_posts_url( absint( $course_item->post_author ) ) ) . '" title="' . esc_attr( $user_info->display_name ) . '">' . esc_html( $user_info->display_name ) . '</a></span>';
		    		    	} // End If Statement

		    		    	// Lesson count for this author
		    		    	$complete_html .= '<span class="course-lesson-count">'
                                . Sensei()->course->course_lesson_count( absint( $course_item->ID ) )
                                . '&nbsp;' .  __( 'Lessons', 'woothemes-sensei' )
                                . '</span>';

		    		    	// Course Categories
		    		    	if ( '' != $category_output ) {

		    		    		$complete_html .= '<span class="course-category">' . sprintf( __( 'in %s', 'woothemes-sensei' ), $category_output ) . '</span>';

		    		    	} // End If Statement

						$complete_html .= '</p>';

						$complete_html .= '<p class="course-excerpt">' . $course_item->post_excerpt . '</p>';

                        $complete_html .= $this->get_progress_meter( 100 );

						if( $manage ) {
							$has_quizzes = Sensei()->course->course_quizzes( $course_item->ID, true );
							// Output only if there is content to display
							if ( has_filter( 'sensei_results_links' ) || $has_quizzes ) {


								$complete_html .= '<p class="sensei-results-links">';
								$results_link = '';
								if( $has_quizzes ) {

									$results_link = '<a class="button view-results" href="'
                                        . Sensei()->course_results->get_permalink( $course_item->ID )
                                        . '">' . __( 'View results', 'woothemes-sensei' )
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
			if( $completed_count > $per_page ) {

				$current_page = 1;
				if( isset( $_GET['completed_page'] ) && 0 < intval( $_GET['completed_page'] ) ) {
					$current_page = $_GET['completed_page'];
				}

				$complete_html .= '<nav class="pagination woo-pagination">';
				$total_pages = ceil( $completed_count / $per_page );


				if( $current_page > 1 ) {
					$prev_link = add_query_arg( 'completed_page', $current_page - 1 );
					$complete_html .= '<a class="prev page-numbers" href="' . esc_url( $prev_link ) . '">' . __( 'Previous' , 'woothemes-sensei' ) . '</a> ';
				}

				for ( $i = 1; $i <= $total_pages; $i++ ) {
					$link = add_query_arg( 'completed_page', $i );

					if( $i == $current_page ) {
						$complete_html .= '<span class="page-numbers current">' . $i . '</span> ';
					} else {
						$complete_html .= '<a class="page-numbers" href="' . esc_url( $link ) . '">' . $i . '</a> ';
					}
				}

				if( $current_page < $total_pages ) {
					$next_link = add_query_arg( 'completed_page', $current_page + 1 );
					$complete_html .= '<a class="next page-numbers" href="' . esc_url( $next_link ) . '">' . __( 'Next' , 'woothemes-sensei' ) . '</a> ';
				}

				$complete_html .= '</nav>';
			}

		} // End If Statement

		if( $manage ) {
			$no_active_message = __( 'You have no active courses.', 'woothemes-sensei' );
			$no_complete_message = __( 'You have not completed any courses yet.', 'woothemes-sensei' );
		} else {
			$no_active_message =  __( 'This learner has no active courses.', 'woothemes-sensei' );
			$no_complete_message =  __( 'This learner has not completed any courses yet.', 'woothemes-sensei' );
		}

		ob_start();
		?>

		<?php do_action( 'sensei_before_user_courses' ); ?>

		<?php
		if( $manage && ( ! isset( Sensei()->settings->settings['messages_disable'] ) || ! Sensei()->settings->settings['messages_disable'] ) ) {
			?>
			<p class="my-messages-link-container">
                <a class="my-messages-link" href="<?php echo get_post_type_archive_link( 'sensei_message' ); ?>"
                   title="<?php _e( 'View & reply to private messages sent to your course & lesson teachers.', 'woothemes-sensei' ); ?>">
                    <?php _e( 'My Messages', 'woothemes-sensei' ); ?>
                </a>
            </p>
			<?php
		}
		?>
		<div id="my-courses">

		    <ul>
		    	<li><a href="#active-courses"><?php  _e( 'Active Courses', 'woothemes-sensei' ); ?></a></li>
		    	<li><a href="#completed-courses"><?php  _e( 'Completed Courses', 'woothemes-sensei' ); ?></a></li>
		    </ul>

		    <?php do_action( 'sensei_before_active_user_courses' ); ?>

		    <?php
            $course_page_url = Sensei_Course::get_courses_page_url();
            ?>

		    <div id="active-courses">

		    	<?php if ( '' != $active_html ) {

		    		echo $active_html;

		    	} else { ?>

		    		<div class="sensei-message info">

                        <?php echo $no_active_message; ?>

                        <a href="<?php echo $course_page_url; ?>">

                            <?php  _e( 'Start a Course!', 'woothemes-sensei' ); ?>

                        </a>

                    </div>

		    	<?php } // End If Statement ?>

		    </div>

		    <?php do_action( 'sensei_after_active_user_courses' ); ?>

		    <?php do_action( 'sensei_before_completed_user_courses' ); ?>

		    <div id="completed-courses">

		    	<?php if ( '' != $complete_html ) {

		    		echo $complete_html;

		    	} else { ?>

		    		<div class="sensei-message info">

                        <?php echo $no_complete_message; ?>

                    </div>

		    	<?php } // End If Statement ?>

		    </div>

		    <?php do_action( 'sensei_after_completed_user_courses' ); ?>

		</div>

		<?php do_action( 'sensei_after_user_courses' ); ?>

		<?php
        echo ob_get_clean();

        do_action( 'sensei_after_learner_course_content', $user );

	} // end load_user_courses_content

    /**
     * Returns a list of all courses
     *
     * @since 1.8.0
     * @return array $courses{
     *  @type $course WP_Post
     * }
     */
    public static function get_all_courses(){

        $args = array(
               'post_type' => 'course',
                'posts_per_page' 		=> -1,
                'orderby'         	=> 'title',
                'order'           	=> 'ASC',
                'post_status'      	=> 'any',
                'suppress_filters' 	=> 0,
        );

        $wp_query_obj =  new WP_Query( $args );

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
        return apply_filters( 'sensei_get_all_courses' , $wp_query_obj->posts );

    }// end get_all_courses

    /**
     * Generate the course meter component
     *
     * @since 1.8.0
     * @param int $progress_percentage 0 - 100
     * @return string $progress_bar_html
     */
    public function get_progress_meter( $progress_percentage ){

        if ( 50 < $progress_percentage ) {
            $class = ' green';
        } elseif ( 25 <= $progress_percentage && 50 >= $progress_percentage ) {
            $class = ' orange';
        } else {
            $class = ' red';
        }
        $progress_bar_html = '<div class="meter' . esc_attr( $class ) . '"><span style="width: ' . $progress_percentage . '%">' . round( $progress_percentage ) . '%</span></div>';

        return $progress_bar_html;

    }// end get_progress_meter

    /**
     * Generate a statement that tells users
     * how far they are in the course.
     *
     * @param int $course_id
     * @param int $user_id
     *
     * @return string $statement_html
     */
    public function get_progress_statement( $course_id, $user_id ){

        if( empty( $course_id ) || empty( $user_id )
        || ! Sensei_Utils::user_started_course( $course_id, $user_id ) ){
            return '';
        }

        $completed = count( $this->get_completed_lesson_ids( $course_id, $user_id ) );
        $total_lessons = count( $this->course_lessons( $course_id ) );

        $statement = sprintf( _n('Currently completed %s lesson of %s in total', 'Currently completed %s lessons of %s in total', $completed, 'woothemes-sensei'), $completed, $total_lessons );

        /**
         * Filter the course completion statement.
         * Default Currently completed $var lesson($plural) of $var in total
         *
         * @param string $statement
         */
        return apply_filters( 'sensei_course_completion_statement', $statement );

    }// end generate_progress_statement

    /**
     * Output the course progress statement
     *
     * @param $course_id
     * @return void
     */
    public function the_progress_statement( $course_id = 0, $user_id = 0 ){
        if( empty( $course_id ) ){
            global $post;
            $course_id = $post->ID;
        }

        if( empty( $user_id ) ){
            $user_id = get_current_user_id();
        }

	    $progress_statement = $this->get_progress_statement( $course_id, $user_id  );
	    if( ! empty( $progress_statement ) ){

		    echo '<span class="progress statement  course-completion-rate">' . $progress_statement . '</span>';

	    }

    }

    /**
     * Output the course progress bar
     *
     * @param $course_id
     * @return void
     */
    public function the_progress_meter( $course_id = 0, $user_id = 0 ){

        if( empty( $course_id ) ){
            global $post;
            $course_id = $post->ID;
        }

        if( empty( $user_id ) ){
            $user_id = get_current_user_id();
        }

        if( 'course' != get_post_type( $course_id ) || ! get_userdata( $user_id )
            || ! Sensei_Utils::user_started_course( $course_id ,$user_id ) ){
            return;
        }
        $percentage_completed = $this->get_completion_percentage( $course_id, $user_id );

        echo $this->get_progress_meter( $percentage_completed );

    }// end the_progress_meter

    /**
     * Checks how many lessons are completed
     *
     * @since 1.8.0
     *
     * @param int $course_id
     * @param int $user_id
     * @return array $completed_lesson_ids
     */
    public function get_completed_lesson_ids( $course_id, $user_id = 0 ){

        if( !( intval( $user_id ) ) > 0 ){
            $user_id = get_current_user_id();
        }

        $completed_lesson_ids = array();

        $course_lessons = $this->course_lessons( $course_id );

        foreach( $course_lessons as $lesson ){

            $is_lesson_completed = Sensei_Utils::user_completed_lesson( $lesson->ID, $user_id );
            if( $is_lesson_completed ){
                $completed_lesson_ids[] = $lesson->ID;
            }

        }

        return $completed_lesson_ids;

    }// end get_completed_lesson_ids

    /**
     * Calculate the perceantage completed in the course
     *
     * @since 1.8.0
     *
     * @param int $course_id
     * @param int $user_id
     * @return int $percentage
     */
    public function get_completion_percentage( $course_id, $user_id = 0 ){

        if( !( intval( $user_id ) ) > 0 ){
            $user_id = get_current_user_id();
        }

        $completed = count( $this->get_completed_lesson_ids( $course_id, $user_id ) );

        if( ! (  $completed  > 0 ) ){
            return 0;
        }

        $total_lessons = count( $this->course_lessons( $course_id ) );
        $percentage = Sensei_Utils::quotient_as_absolute_rounded_percentage( $completed, $total_lessons, 2 );

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

    }// end get_completed_lesson_ids

    /**
     * Block email notifications for the specific courses
     * that the user disabled the notifications.
     *
     * @since 1.8.0
     * @param $should_send
     * @return bool
     */
    public function block_notification_emails( $should_send ){
        global $sensei_email_data;
        $email = $sensei_email_data;

        $course_id = '';

        if( isset( $email['course_id'] ) ){

            $course_id = $email['course_id'];

        }elseif( isset( $email['lesson_id'] ) ){

            $course_id = Sensei()->lesson->get_course_id( $email['lesson_id'] );

        }elseif( isset( $email['quiz_id'] ) ){

            $lesson_id = Sensei()->quiz->get_lesson_id( $email['quiz_id'] );
            $course_id = Sensei()->lesson->get_course_id( $lesson_id );

        }

        if( !empty( $course_id ) && 'course'== get_post_type( $course_id ) ) {

            $course_emails_disabled = get_post_meta($course_id, 'disable_notification', true);

            if ($course_emails_disabled) {

                return false;

            }

        }// end if

        return $should_send;
    }// end block_notification_emails

    /**
     * Render the course notification setting meta box
     *
     * @since 1.8.0
     * @param $course
     */
    public function course_notification_meta_box_content( $course ){

        $checked = get_post_meta( $course->ID , 'disable_notification', true );

        // generate checked html
        $checked_html = '';
        if( $checked ){
            $checked_html = 'checked="checked"';
        }
        wp_nonce_field( 'update-course-notification-setting','_sensei_course_notification' );

        echo '<input id="disable_sensei_course_notification" '.$checked_html .' type="checkbox" name="disable_sensei_course_notification" >';
        echo '<label for="disable_sensei_course_notification">'.__('Disable notifications on this course ?', 'woothemes-sensei'). '</label>';

    }// end course_notification_meta_box_content

    /**
     * Store the setting for the course notification setting.
     *
     * @hooked int save_post
     * @since 1.8.0
     *
     * @param $course_id
     */
    public function save_course_notification_meta_box( $course_id ){

        if( !isset( $_POST['_sensei_course_notification']  )
            || ! wp_verify_nonce( $_POST['_sensei_course_notification'], 'update-course-notification-setting' ) ){
            return;
        }

        if( isset( $_POST['disable_sensei_course_notification'] ) && 'on'== $_POST['disable_sensei_course_notification']  ) {
            $new_val = true;
        }else{
            $new_val = false;
        }

       update_post_meta( $course_id , 'disable_notification', $new_val );

    }// end save notification meta box

    /**
     * Backwards compatibility hooks added to ensure that
     * plugins and other parts of sensei still works.
     *
     * This function hooks into `sensei_course_content_inside_before`
     *
     * @since 1.9
     *
     * @param WP_Post $post
     */
    public function content_before_backwards_compatibility_hooks( $post_id ){

        sensei_do_deprecated_action( 'sensei_course_image','1.9.0','sensei_course_content_inside_before' );
        sensei_do_deprecated_action( 'sensei_course_archive_course_title','1.9.0','sensei_course_content_inside_before' );

    }

    /**
     * Backwards compatibility hooks that should be hooked into sensei_loop_course_before
     *
     * hooked into 'sensei_loop_course_before'
     *
     * @since 1.9
     *
     * @global WP_Post $post
     */
    public  function loop_before_backwards_compatibility_hooks( ){

        global $post;
        sensei_do_deprecated_action( 'sensei_course_archive_header','1.9.0','sensei_course_content_inside_before', $post->post_type  );

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
    public function the_course_free_lesson_preview( $course_id ){
        // Meta data
        $course = get_post( $course_id );
        $preview_lesson_count = intval( Sensei()->course->course_lesson_preview_count( $course->ID ) );
        $is_user_taking_course = Sensei_Utils::user_started_course( $course->ID, get_current_user_id() );

        if ( 0 < $preview_lesson_count && !$is_user_taking_course ) {
            ?>
            <p class="sensei-free-lessons">
                <a href="<?php echo get_permalink(); ?>">
                    <?php _e( 'Preview this course', 'woothemes-sensei' ) ?>
                </a>
                - <?php echo sprintf( __( '(%d preview lessons)', 'woothemes-sensei' ), $preview_lesson_count ) ; ?>
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
    public function the_course_meta( $course_id ){
        echo '<p class="sensei-course-meta">';

        $course = get_post( $course_id );
        $category_output = get_the_term_list( $course->ID, 'course-category', '', ', ', '' );
        $author_display_name = get_the_author_meta( 'display_name', $course->post_author  );

        if ( isset( Sensei()->settings->settings[ 'course_author' ] ) && ( Sensei()->settings->settings[ 'course_author' ] ) ) {?>

            <span class="course-author"><?php _e( 'by ', 'woothemes-sensei' ); ?>

                <a href="<?php echo esc_attr( get_author_posts_url( $course->post_author ) ); ?>" title="<?php echo esc_attr( $author_display_name ); ?>"><?php echo esc_attr( $author_display_name ); ?></a>

            </span>

        <?php } // End If Statement ?>

        <span class="course-lesson-count"><?php echo Sensei()->course->course_lesson_count( $course->ID ) . '&nbsp;' .  __( 'Lessons', 'woothemes-sensei' ); ?></span>

       <?php if ( '' != $category_output ) { ?>

            <span class="course-category"><?php echo sprintf( __( 'in %s', 'woothemes-sensei' ), $category_output ); ?></span>

        <?php } // End If Statement

        // number of completed lessons
        if( Sensei_Utils::user_started_course( $course->ID,  get_current_user_id() )
            || Sensei_Utils::user_completed_course( $course->ID,  get_current_user_id() )  ){

            $completed = count( $this->get_completed_lesson_ids( $course->ID, get_current_user_id() ) );
            $lesson_count = count( $this->course_lessons( $course->ID ) );
            echo '<span class="course-lesson-progress">' . sprintf( __( '%1$d of %2$d lessons completed', 'woothemes-sensei' ) , $completed, $lesson_count  ) . '</span>';

        }

        sensei_simple_course_price( $course->ID );

        echo '</p>';
    } // end the course meta

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
    public static function add_course_user_status_class( $classes, $class, $course_id ){

        if( 'course' == get_post_type( $course_id )  &&  is_user_logged_in() ){

            if( Sensei_Utils::user_completed_course( $course_id, get_current_user_id() ) ){

                $classes[] = 'user-status-completed';

            }else{

                $classes[] = 'user-status-active';

            }

        }

        return $classes;

    }// end add_course_user_status_class

    /**
     * Prints out the course action buttons links
     *
     * - complete course
     * - delete course
     *
     * @param WP_Post $course
     */
    public static function the_course_action_buttons( $course ){

        if( is_user_logged_in() ) { ?>

            <section class="entry-actions">
                <form method="POST" action="<?php  echo esc_url( remove_query_arg( array( 'active_page', 'completed_page' ) ) ); ?>">

                    <input type="hidden"
                           name="<?php echo esc_attr( 'woothemes_sensei_complete_course_noonce' ) ?>"
                           id="<?php  echo esc_attr( 'woothemes_sensei_complete_course_noonce' ); ?>"
                           value="<?php echo esc_attr( wp_create_nonce( 'woothemes_sensei_complete_course_noonce' ) ); ?>"
                        />

                    <input type="hidden" name="course_complete_id" id="course-complete-id" value="<?php echo esc_attr( intval( $course->ID ) ); ?>" />

                    <?php if ( 0 < absint( count( Sensei()->course->course_lessons( $course->ID ) ) )
                        && Sensei()->settings->settings['course_completion'] == 'complete'
                        && ! Sensei_Utils::user_completed_course( $course, get_current_user_id() )) { ?>

                        <span><input name="course_complete" type="submit" class="course-complete" value="<?php  _e( 'Mark as Complete', 'woothemes-sensei' ); ?>" /></span>

                   <?php  } // End If Statement

                    $course_purchased = false;
                    if ( Sensei_WC::is_woocommerce_active() ) {
                        // Get the product ID
                        $wc_post_id = get_post_meta( intval( $course->ID ), '_course_woocommerce_product', true );
                        if ( 0 < $wc_post_id ) {

                            $user = wp_get_current_user();
                            $course_purchased = Sensei_Utils::sensei_customer_bought_product( $user->user_email, $user->ID, $wc_post_id );

                        } // End If Statement
                    } // End If Statement

                    /**
                     * Hide or show the delete course button.
                     *
                     * This button on shows in certain instances, but this filter will hide it in those
                     * cases. For other instances the button will be hidden.
                     *
                     * @since 1.9.0
                     * @param bool $show_delete_course_button defaults to false
                     */
                    $show_delete_course_button = apply_filters( 'sensei_show_delete_course_button', false );

                    if ( ! $course_purchased
                         && ! Sensei_Utils::user_completed_course( $course->ID, get_current_user_id() )
                         && $show_delete_course_button ) { ?>

                        <span><input name="course_complete" type="submit" class="course-delete" value="<?php echo __( 'Delete Course', 'woothemes-sensei' ); ?>"/></span>

                    <?php } // End If Statement

                    $has_quizzes = Sensei()->course->course_quizzes( $course->ID, true );
                    $results_link = '';
                    if( $has_quizzes ){
                        $results_link = '<a class="button view-results" href="' . Sensei()->course_results->get_permalink( $course->ID ) . '">' . __( 'View results', 'woothemes-sensei' ) . '</a>';
                    }

                    // Output only if there is content to display
                    if ( has_filter( 'sensei_results_links' ) || $has_quizzes ) { ?>

                        <p class="sensei-results-links">
                            <?php
                            /**
                             * Filter the results links
                             *
                             * @param string $results_links_html
                             * @param integer $course_id
                             */
                            echo apply_filters( 'sensei_results_links', $results_link, $course->ID );
                            ?>
                        </p>

                    <?php } // end if has filter  ?>
                </form>
            </section>

        <?php  }// end if is user logged in

    }// end the_course_action_buttons

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
    public static function course_query_filter( $query ){

        // exit early for no course queries and admin queries
        if( is_admin( ) || 'course' != $query->get( 'post_type' ) ){
            return $query;
        }

        global $post; // used to get the current page id for my courses

        // for the course archive page
        if( $query->is_main_query() && is_post_type_archive('course') )
        {
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

        }
        // for the my courses page
        elseif( isset( $post ) && is_page() && Sensei()->settings->get( 'my_course_page' ) == $post->ID  )
        {
            /**
             * sensei_my_courses_per_page
             *
             * Sensei courses per page on the my courses page
             * as set in the settings
             *
             * @since 1.9.0
             * @param integer $posts_per_page default 10
             */
            $query->set( 'posts_per_page', apply_filters( 'sensei_my_courses_per_page', 10 ) );

        }

        return $query;

    }// end course_query_filter

    /**
     * Determine the class of the course loop
     *
     * This will output .first or .last and .course-item-number-x
     *
     * @return array $extra_classes
     * @since 1.9.0
     */
    public static function get_course_loop_content_class ()
    {

        global $sensei_course_loop;


        if( !isset( $sensei_course_loop ) ){
            $sensei_course_loop = array();
        }

        if (!isset($sensei_course_loop['counter'])) {
            $sensei_course_loop['counter'] = 0;
        }

        if (!isset($sensei_course_loop['columns'])) {
            $sensei_course_loop['columns'] = self::get_loop_number_of_columns();
        }

        // increment the counter
        $sensei_course_loop['counter']++;

        $extra_classes = array();
        if( 0 == ( $sensei_course_loop['counter'] - 1 ) % $sensei_course_loop['columns'] || 1 == $sensei_course_loop['columns']  ){
            $extra_classes[] = 'first';
        }

        if( 0 == $sensei_course_loop['counter'] % $sensei_course_loop['columns']  ){
            $extra_classes[] = 'last';
        }

        // add the item number to the classes as well.
        $extra_classes[] = 'loop-item-number-'. $sensei_course_loop['counter'];

        /**
         * Filter the course loop class the fires in the  in get_course_loop_content_class function
         * which is called from the course loop content-course.php
         *
         * @since 1.9.0
         *
         * @param array $extra_classes
         * @param WP_Post $loop_current_course
         */
        return apply_filters( 'sensei_course_loop_content_class', $extra_classes ,get_post() );

    }// end get_course_loop_class

    /**
     * Get the number of columns set for Sensei courses
     *
     * @since 1.9.0
     * @return mixed|void
     */
    public static function get_loop_number_of_columns(){

        /**
         * Filter the number of columns on the course archive page.
         *
         * @since 1.9.0
         * @param int $number_of_columns default 1
         */
        return apply_filters('sensei_course_loop_number_of_columns', 1);

    }

    /**
     * Output the course archive filter markup
     *
     * hooked into sensei_loop_course_before
     *
     * @since 1.9.0
     * @param
     */
    public static function course_archive_sorting( $query ){

        // don't show on category pages and other pages
        if( ! is_archive(  'course ') || is_tax('course-category') ){
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
        $course_order_by_options = apply_filters( 'sensei_archive_course_order_by_options', array(
            "newness"     => __( "Sort by newest first", "woothemes-sensei"),
            "title"       => __( "Sort by title A-Z", "woothemes-sensei" ),
        ));

        // setup the currently selected item
        $selected = 'newness';
        if( isset( $_GET['orderby'] ) ){

            $selected =  $_GET[ 'orderby' ];

        }

        ?>

        <form class="sensei-ordering" name="sensei-course-order" action="<?php echo esc_attr( Sensei_Utils::get_current_url() ) ; ?>" method="POST">
            <select name="course-orderby" class="orderby">
                <?php
                foreach( $course_order_by_options as $value => $text ){

                    echo '<option value="'. $value . ' "' . selected( $selected, $value, false ) . '>'. $text. '</option>';

                }
                ?>
            </select>
        </form>

    <?php
    }// end course archive filters

    /**
     * Output the course archive filter markup
     *
     * hooked into sensei_loop_course_before
     *
     * @since 1.9.0
     * @param
     */
    public static function course_archive_filters( $query ){

        // don't show on category pages
        if( is_tax('course-category') ){
            return;
        }

        /**
         * filter the course archive filter buttons
         *
         * @since 1.9.0
         * @param array $filters{
         *   @type array ( $id, $url , $title )
         * }
         *
         */
        $filters = apply_filters( 'sensei_archive_course_filter_by_options', array(
            array( 'id' => 'all', 'url' => self::get_courses_page_url(), 'title'=> __( 'All', 'woothemes-sensei' ) ),
            array( 'id' => 'featured', 'url' => add_query_arg( array( 'course_filter'=>'featured'), self::get_courses_page_url()  ), 'title'=> __( 'Featured', 'woothemes-sensei' ) ),
        ));


        ?>
        <ul class="sensei-course-filters clearfix" >
            <?php

            //determine the current active url
            $current_url = Sensei_Utils::get_current_url();

            foreach( $filters as $filter ) {

                $active_class =  $current_url == $filter['url'] ? ' class="active" ' : '';

                echo '<li><a '. $active_class .' id="'. $filter['id'] .'" href="'. esc_url( $filter['url'] ).'" >'. $filter['title']  .'</a></li>';

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
    public static function course_archive_featured_filter( $query ){

        if( isset ( $_GET[ 'course_filter' ] ) && 'featured'== $_GET['course_filter'] && $query->is_main_query()  ){
            //setup meta query for featured courses
            $query->set( 'meta_value', 'featured'  );
            $query->set( 'meta_key', '_course_featured'  );
            $query->set( 'meta_compare', '='  );
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
    public static function course_archive_order_by_title( $query ){

        if( isset ( $_POST[ 'course-orderby' ] ) && 'title '== $_POST['course-orderby']
            && 'course'== $query->get('post_type') && $query->is_main_query()  ){
            // setup the order by title for this query
            $query->set( 'orderby', 'title'  );
            $query->set( 'order', 'ASC'  );
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
    public static function get_courses_page_url(){

        $course_page_id = intval( Sensei()->settings->settings[ 'course_page' ] );
        $course_page_url = empty( $course_page_id ) ? get_post_type_archive_link('course') : get_permalink( $course_page_id );

        return $course_page_url;

    }// get_course_url

    /**
     * Output the headers on the course archive page
     *
     * Hooked into the sensei_archive_title
     *
     * @since 1.9.0
     * @param string $query_type
     * @param string $before_html
     * @param string $after_html
     * @return void
     */
    public static function archive_header( $query_type ='' , $before_html='', $after_html =''  ){

        if( ! is_post_type_archive('course') ){
            return;
        }

        // deprecated since 1.9.0
        sensei_do_deprecated_action('sensei_archive_title','1.9.0','sensei_archive_before_course_loop');

        $html = '';

        if( empty( $before_html ) ){

            $before_html = '<header class="archive-header"><h1>';

        }

        if( empty( $after_html ) ){

            $after_html = '</h1></header>';

        }

        if ( is_tax( 'course-category' ) ) {

            global $wp_query;

            $taxonomy_obj = $wp_query->get_queried_object();
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
                $html .= $before_html .  __( 'Featured Courses', 'woothemes-sensei' ) . $after_html;
                break;
            case 'freecourses':
                $html .= $before_html .  __( 'Free Courses', 'woothemes-sensei' ) . $after_html;
                break;
            case 'paidcourses':
                $html .= $before_html .  __( 'Paid Courses', 'woothemes-sensei' ) . $after_html;
                break;
            default:
                $html .= $before_html . __( 'Courses', 'woothemes-sensei' ) . $after_html;
                break;
        } // End Switch Statement

        echo apply_filters( 'course_archive_title', $html );

    }//course_archive_header


    /**
     * Filter the single course content
     * taking into account if the user has access.
     *
     * @1.9.0
     *
     * @param string $content
     * @return string $content or $excerpt
     */
    public static function single_course_content( $content ){

        if( ! is_singular('course') ){

            return $content;

        }

        // Content Access Permissions
        $access_permission = false;

        if ( ! Sensei()->settings->get('access_permission')  || sensei_all_access() ) {

            $access_permission = true;

        } // End If Statement

        // Check if the user is taking the course
        $is_user_taking_course = Sensei_Utils::user_started_course( get_the_ID(), get_current_user_id() );

        if(Sensei_WC::is_woocommerce_active()) {

            $wc_post_id = get_post_meta( get_the_ID(), '_course_woocommerce_product', true );
            $product = Sensei()->sensei_get_woocommerce_product_object( $wc_post_id );

            $has_product_attached = isset ( $product ) && is_object ( $product );

        } else {

            $has_product_attached = false;

        }

        if ( ( is_user_logged_in() && $is_user_taking_course )
            || ( $access_permission && !$has_product_attached)
            || 'full' == Sensei()->settings->get( 'course_single_content_display' ) ) {

	        // compensate for core providing and empty $content

	        if( empty( $content ) ){
		        remove_filter( 'the_content', array( 'Sensei_Course', 'single_course_content') );
		        $course = get_post( get_the_ID() );

		        $content = apply_filters( 'the_content', $course->post_content );

	        }

            return $content;

        } else {
            return '<p class="course-excerpt">' . get_post(  get_the_ID() )->post_excerpt . '</p>';
        }

    }// end single_course_content

    /**
     * Output the the single course lessons title with markup.
     *
     * @since 1.9.0
     */
    public static function the_course_lessons_title(){

	    if ( ! is_singular( 'course' )  ) {
		    return;
	    }

        global $post;
        $none_module_lessons = Sensei()->modules->get_none_module_lessons( $post->ID  );
        $course_lessons = Sensei()->course->course_lessons( $post->ID );

        // title should be Other Lessons if there are lessons belonging to models.
        $title = __('Other Lessons', 'woothemes-sensei');

        // show header if there are lessons the number of lesson in the course is the same as those that isn't assigned to a module
        if ( ! empty( $course_lessons ) && count( $course_lessons ) == count( $none_module_lessons ) ) {

            $title = __('Lessons', 'woothemes-sensei');

        }elseif( empty( $none_module_lessons ) ){ // if the none module lessons are simply empty the title should not be shown

            $title = '';
        }

        /**
         * hook document in class-woothemes-sensei-message.php
         */
        $title = apply_filters( 'sensei_single_title', $title, $post->post_type );

        ob_start(); // start capturing the following output.

        ?>

            <header>
                <h2> <?php echo $title; ?> </h2>
            </header>

        <?php

        /**
         * Filter the title and markup that appears above the lessons on a single course
         * page.
         *
         * @since 1.9.0
         * @param string $lessons_title_html
         */
        echo apply_filters('the_course_lessons_title', ob_get_clean() ); // output and filter the captured output and stop capturing.

    }// end the_course_lessons_title

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
    public static function load_single_course_lessons_query(){

        global $post, $wp_query;

        $course_id = $post->ID;

        if( 'course' != get_post_type( $course_id ) ){
            return;
        }

        $course_lesson_query_args = array(
	        'post_status'       => 'publish',
            'post_type'         => 'lesson',
            'posts_per_page'    => 500,
            'orderby'           => 'date',
            'order'             => 'ASC',
            'meta_query'        => array(
                array(
                    'key' => '_lesson_course',
                    'value' => intval( $course_id ),
                ),
            ),
            'suppress_filters'  => 0,
        );

        // Exclude lessons belonging to modules as they are queried along with the modules.
        $modules = Sensei()->modules->get_course_modules( $course_id );
        if( !is_wp_error( $modules ) && ! empty( $modules ) && is_array( $modules ) ){

            $terms_ids = array();
            foreach( $modules as $term ){

                $terms_ids[] = $term->term_id;

            }

            $course_lesson_query_args[ 'tax_query'] = array(
                array(
                    'taxonomy' => 'module',
                    'field'    => 'id',
                    'terms'    => $terms_ids,
                    'operator' => 'NOT IN',
                ),
            );
        }

        //setting lesson order
        $course_lesson_order = get_post_meta( $course_id, '_lesson_order', true);
        if( !empty( $course_lesson_order ) ){

            $course_lesson_query_args['post__in'] = explode( ',', $course_lesson_order );
            $course_lesson_query_args['orderby']= 'post__in' ;
            unset( $course_lesson_query_args['order'] );

        }

        $wp_query = new WP_Query( $course_lesson_query_args );

    }// load_single_course_lessons

    /**
     * Flush the rewrite rules for a course post type
     *
     * @since 1.9.0
     *
     * @param $post_id
     */
    public static function flush_rewrite_rules( $post_id ){

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){

            return;

        }


        if( 'course' == get_post_type( $post_id )  ){

            Sensei()->initiate_rewrite_rules_flush();

        }

    }

    /**
     * Optionally return the full content on the single course pages
     * depending on the users course_single_content_display setting
     *
     * @since 1.9.0
     * @param $excerpt
     * @return string
     */
    public static function full_content_excerpt_override( $excerpt ){

        if (   is_singular('course')  &&
                'full' == Sensei()->settings->get( 'course_single_content_display' ) ){

            return get_the_content();

        } else {

            return $excerpt;

        }

    }

    /**
     * Output the course actions like start taking course, register, add to cart etc.
     *
     * @since 1.9.0
     */
    public static function the_course_enrolment_actions(){

	    global $post;

	    if ( 'course' != $post->post_type ) {
			return;
	    }

        ?>
        <section class="course-meta course-enrolment">
        <?php
        global  $post, $current_user;
        $is_user_taking_course = Sensei_Utils::user_started_course( $post->ID, $current_user->ID );
		$is_course_content_restricted = (bool) apply_filters( 'sensei_is_course_content_restricted', false, $post->ID );

	    if ( is_user_logged_in() && ! $is_user_taking_course ) {

	        // Check for woocommerce
	        if ( Sensei_WC::is_woocommerce_active() && Sensei_WC::is_course_purchasable( $post->ID ) ) {

		        // Get the product ID
                Sensei_WC::the_add_to_cart_button_html($post->ID );

            } else {
                $should_display_start_course_form = (bool) apply_filters( 'sensei_display_start_course_form', true, $post->ID );
				if ( $is_course_content_restricted && false == $should_display_start_course_form ) {
					self::add_course_access_permission_message( '' );
				}
                if ( $should_display_start_course_form ) {
                  sensei_start_course_form( $post->ID );
                }
            } // End If Statement

        } elseif ( is_user_logged_in() ) {

            // Check if course is completed
            $user_course_status = Sensei_Utils::user_course_status( $post->ID, $current_user->ID );
            $completed_course = Sensei_Utils::user_completed_course( $user_course_status );
            // Success message
            if ( $completed_course ) { ?>
                <div class="status completed"><?php  _e( 'Completed', 'woothemes-sensei' ); ?></div>
                <?php
                $has_quizzes = Sensei()->course->course_quizzes( $post->ID, true );
                if( has_filter( 'sensei_results_links' ) || $has_quizzes ) { ?>
                    <p class="sensei-results-links">
                        <?php
                        $results_link = '';
                        if( $has_quizzes ) {
                            $results_link = '<a class="view-results" href="' . Sensei()->course_results->get_permalink( $post->ID ) . '">' .  __( 'View results', 'woothemes-sensei' ) . '</a>';
                        }
                        /**
                         * Filter documented in Sensei_Course::the_course_action_buttons
                         */
                        $results_link = apply_filters( 'sensei_results_links', $results_link, $post->ID );
                        echo $results_link;
                        ?></p>
                <?php } ?>
            <?php } else { ?>
                <div class="status in-progress"><?php echo __( 'In Progress', 'woothemes-sensei' ); ?></div>
            <?php }

        } else {

            // Check for woocommerce
		    if ( Sensei_WC::is_woocommerce_active() && Sensei_WC::is_course_purchasable( $post->ID ) ) {

	            $login_link =  '<a href="' . sensei_user_login_url() . '">' . __( 'log in', 'woothemes-sensei' ) . '</a>';
	            $message = sprintf( __( 'Or %1$s to access your purchased courses', 'woothemes-sensei' ), $login_link );
	            Sensei()->notices->add_notice( $message, 'info' ) ;
	            Sensei_WC::the_add_to_cart_button_html( $post->ID );

            } else {

                if( get_option( 'users_can_register') ) {

	                // set the permissions message
	                $anchor_before = '<a href="' . esc_url( sensei_user_login_url() ) . '" >';
	                $anchor_after = '</a>';
	                $notice = sprintf(
		                __('or %slog in%s to view this course.', 'woothemes-sensei'),
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

                    $wp_register_link = apply_filters('sensei_use_wp_register_link', false);

                    $settings = Sensei()->settings->get_settings();
                    if( isset( $settings[ 'my_course_page' ] )
                        && 0 < intval( $settings[ 'my_course_page' ] ) ){

                        $my_courses_page_id = $settings[ 'my_course_page' ];

                    }

                    // If a My Courses page was set in Settings, and 'sensei_use_wp_register_link'
                    // is false, link to My Courses. If not, link to default WordPress registration page.
                    if( !empty( $my_courses_page_id ) && $my_courses_page_id && !$wp_register_link){
						if ( true === (bool)apply_filters( 'sensei_user_can_register_for_course', true, $post->ID ) ) {
							$my_courses_url = get_permalink( $my_courses_page_id  );
							$register_link = '<a href="'.$my_courses_url. '">' . __('Register', 'woothemes-sensei') .'</a>';
							echo '<div class="status register">' . $register_link . '</div>' ;
						}
                    } else {

                        wp_register( '<div class="status register">', '</div>' );

                    }

                } // end if user can register

            } // End If Statement

        } // End If Statement ?>

        </section><?php

    }// end the_course_enrolment_actions

    /**
     * Output the course video inside the loop.
     *
     * @since 1.9.0
     */
    public static function the_course_video(){

        global $post;

	    if ( ! is_singular( 'course' )  ) {
		    return;
	    }

        // Get the meta info
        $course_video_embed = get_post_meta( $post->ID, '_course_video_embed', true );

        if ( 'http' == substr( $course_video_embed, 0, 4) ) {

            $course_video_embed = wp_oembed_get( esc_url( $course_video_embed ) );

        } // End If Statement

	$course_video_embed = do_shortcode( $course_video_embed );

	$course_video_embed = Sensei_Wp_Kses::maybe_sanitize( $course_video_embed, self::$allowed_html );

        if ( '' != $course_video_embed ) { ?>

            <div class="course-video">
                <?php echo $course_video_embed; ?>
            </div>

        <?php } // End If Statement
    }

    /**
     * Output the title for the single lesson page
     *
     * @global $post
     * @since 1.9.0
     */
    public static function the_title(){

	    if( ! is_singular( 'course' ) ){
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
                echo apply_filters( 'sensei_single_title', get_the_title( $post ), $post->post_type );
                ?>

            </h1>

        </header>

        <?php

    }//the_title

    /**
     * Show the title on the course category pages
     *
     * @since 1.9.0
     */
    public static function course_category_title(){

        if( ! is_tax( 'course-category' ) ){
            return;
        }

        $category_slug = get_query_var('course-category');
        $term  = get_term_by('slug',$category_slug,'course-category');

        if( ! empty($term) ){

            $title = __( 'Category', 'woothemes-sensei' ) . ' ' . $term->name;

        }else{

            $title = 'Course Category';

        }

        $html = '<h2 class="sensei-category-title">';
        $html .=  $title;
        $html .= '</h2>';

        echo apply_filters( 'course_category_title', $html , $term->term_id );

    }// course_category_title

    /**
     * Alter the course query to respect the order set for courses and apply
     * this on the course-category pages.
     *
     * @since 1.9.0
     *
     * @param WP_Query $query
     * @return WP_Query
     */
    public static function alter_course_category_order( $query ){

        if( ! $query->is_main_query() || ! is_tax( 'course-category' ) ) {
            return $query;
        }

        $order = get_option( 'sensei_course_order', '' );
        if( !empty( $order )  ){
            $query->set('orderby', 'menu_order' );
            $query->set('order', 'ASC' );
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
    public static function get_default_query_args(){
        return array(
            'post_type' 		=> 'course',
            'posts_per_page' 		=> 1000,
            'orderby'         	=> 'date',
            'order'           	=> 'DESC',
            'suppress_filters' 	=> 0
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
    public static function is_prerequisite_complete( $course_id ){

        $course_prerequisite_id = get_post_meta( $course_id, '_course_prerequisite', true );

        // if it has a pre requisite course check it
		$prerequisite_complete = true;

        if( ! empty(  $course_prerequisite_id ) ){

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

    }// end is_prerequisite_complete

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
		$posts = $query_check->get_posts();

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
		if( ! is_a( $settings_course_page, 'WP_Post')
		    ||  Sensei()->post_types->has_old_shortcodes( $settings_course_page->post_content )
			|| $settings_course_page->ID != get_option( 'page_on_front' ) ){

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
		if ( ! self::is_prerequisite_complete( get_the_ID(), get_current_user_id() ) ) {
			$course_prerequisite_id = absint( get_post_meta( get_the_ID(), '_course_prerequisite', true ) );
			$course_title = get_the_title( $course_prerequisite_id );
			$prerequisite_course_link = '<a href="' . esc_url( get_permalink( $course_prerequisite_id ) )
				. '" title="'
				. sprintf(
					esc_attr__( 'You must first complete: %1$s', 'woothemes-sensei' ),
					$course_title )
				 . '">' . $course_title . '</a>';

			$complete_prerequisite_message = sprintf(
				esc_html__( 'You must first complete %1$s before viewing this course', 'woothemes-sensei' ),
				$prerequisite_course_link );

			/**
			 * Filter sensei_course_complete_prerequisite_message.
			 *
			 * @since 1.9.10
			 * @param string $complete_prerequisite_message the message to filter
			 */
			$filtered_message = apply_filters( 'sensei_course_complete_prerequisite_message', $complete_prerequisite_message );

			Sensei()->notices->add_notice( $filtered_message, 'info' );
		}
	}

}// End Class

/**
 * Class WooThemes_Sensei_Course
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Course extends Sensei_Course{}
