<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

	/***************************************************************************************************
	 * 	1 - Shortcodes.
	 ***************************************************************************************************/

	add_shortcode( 'allcourses', 'shortcode_all_courses' );
	add_shortcode( 'newcourses', 'shortcode_new_courses' );
	add_shortcode( 'featuredcourses', 'shortcode_featured_courses' );
	add_shortcode( 'freecourses', 'shortcode_free_courses' );
	add_shortcode( 'paidcourses', 'shortcode_paid_courses' );
	add_shortcode( 'usercourses', 'shortcode_user_courses' );

	add_action('pre_get_posts', 'sensei_filter_courses_archive' );


	/**
	 * sensei_filter_courses_archive function.
	 *
	 * @access public
	 * @param mixed $wp_query
	 * @return void
	 */
	function sensei_filter_courses_archive( $query ) {

		if ( ! $query->is_main_query() )
        	return;

		$query_type = '';
		// Handle course archive page
		if ( is_post_type_archive( 'course' ) ) {

			if ( isset( $_GET[ 'action' ] ) && ( '' != esc_html( $_GET[ 'action' ] ) ) ) {
   				$query_type = esc_html( $_GET[ 'action' ] );
   			} // End If Statement

   			switch ( $query_type ) {
				case 'newcourses':
					set_query_var( 'orderby', 'date' );
					set_query_var( 'order', 'DESC' );
					break;
				case 'freecourses':
					set_query_var( 'orderby', 'date' );
					set_query_var( 'order', 'DESC' );
					set_query_var( 'meta_value', '-' ); /* TODO - WC */
					set_query_var( 'meta_key', '_course_woocommerce_product' );
					set_query_var( 'meta_compare', '=' );
					break;
				case 'paidcourses':
					set_query_var( 'orderby', 'date' );
					set_query_var( 'order', 'DESC' );
					set_query_var( 'meta_value', '0' );
					set_query_var( 'meta_key', '_course_woocommerce_product' );
					set_query_var( 'meta_compare', '>' );
					break;
				case 'featuredcourses':
					set_query_var( 'orderby', 'date' );
					set_query_var( 'order', 'DESC' );
					set_query_var( 'meta_value', 'featured' );
					set_query_var( 'meta_key', '_course_featured' );
					set_query_var( 'meta_compare', '=' );
					break;
				default:

					break;

			} // End Switch Statement

		} // End If Statement
	} // End sensei_filter_courses_archive()


	/**
	 * sensei_course_archive_next_link function.
	 *
	 * @access public
	 * @param string $type (default: 'newcourses')
	 * @return void
	 */
	function sensei_course_archive_next_link( $type = 'newcourses' ) {
		global $woothemes_sensei;
		$course_pagination_link = get_post_type_archive_link( 'course' );
   		$more_link_text = esc_html( $woothemes_sensei->settings->settings[ 'course_archive_more_link_text' ] );
   		$html = '<div class="navigation"><div class="nav-next"><a href="' . esc_url( add_query_arg( array( 'paged' => '2', 'action' => $type ), $course_pagination_link ) ). '">' . sprintf( __( '%1$s', 'woothemes-sensei' ), $more_link_text ) . ' <span class="meta-nav"></span></a></div><div class="nav-previous"></div></div>';

   		return apply_filters( 'course_archive_next_link', $html );
	} // End sensei_course_archive_next_link()


	/**
	 * shortcode_all_courses function.
	 *
	 * @access public
	 * @param mixed $atts
	 * @param mixed $content (default: null)
	 * @return void
	 */
	function shortcode_all_courses( $atts, $content = null ) {

   		global $woothemes_sensei;
   		ob_start();
		$woothemes_sensei->frontend->sensei_get_template( 'loop-course.php' );
		$content = ob_get_clean();
		return $content;

	} // End shortcode_all_courses()


	/**
	 * shortcode_new_courses function.
	 *
	 * @access public
	 * @param mixed $atts
	 * @param mixed $content (default: null)
	 * @return void
	 */
	function shortcode_new_courses( $atts, $content = null ) {
   		global $woothemes_sensei, $shortcode_override;
   		extract( shortcode_atts( array(	'amount' => 0 ), $atts ) );

   		$shortcode_override = 'newcourses';

   		ob_start();
		$woothemes_sensei->frontend->sensei_get_template( 'loop-course.php' );
		$content = ob_get_clean();
		return $content;

	} // End shortcode_new_courses()


	/**
	 * shortcode_featured_courses function.
	 *
	 * @access public
	 * @param mixed $atts
	 * @param mixed $content (default: null)
	 * @return void
	 */
	function shortcode_featured_courses( $atts, $content = null ) {

   		global $woothemes_sensei, $shortcode_override;
   		extract( shortcode_atts( array(	'amount' => 0 ), $atts ) );

   		if ( isset( $woothemes_sensei->settings->settings[ 'course_archive_featured_enable' ] ) && $woothemes_sensei->settings->settings[ 'course_archive_featured_enable' ] ) {
   			$shortcode_override = 'featuredcourses';
   			ob_start();
			$woothemes_sensei->frontend->sensei_get_template( 'loop-course.php' );
			$content = ob_get_clean();
   		} // End If Statement
   		return $content;
	} // End shortcode_featured_courses()


	/**
	 * shortcode_free_courses function.
	 *
	 * @access public
	 * @param mixed $atts
	 * @param mixed $content (default: null)
	 * @return void
	 */
	function shortcode_free_courses( $atts, $content = null ) {
   		global $woothemes_sensei, $shortcode_override;
   		extract( shortcode_atts( array(	'amount' => 0 ), $atts ) );

   		if ( isset( $woothemes_sensei->settings->settings[ 'course_archive_free_enable' ] ) && $woothemes_sensei->settings->settings[ 'course_archive_free_enable' ] ) {
   			$shortcode_override = 'freecourses';
   			ob_start();
			$woothemes_sensei->frontend->sensei_get_template( 'loop-course.php' );
			$content = ob_get_clean();
			return $content;
   		} // End If Statement
   		return $content;
	} // End shortcode_free_courses()


	/**
	 * shortcode_paid_courses function.
	 *
	 * @access public
	 * @param mixed $atts
	 * @param mixed $content (default: null)
	 * @return void
	 */
	function shortcode_paid_courses( $atts, $content = null ) {
   		global $woothemes_sensei, $shortcode_override;
   		extract( shortcode_atts( array(	'amount' => 0 ), $atts ) );

   		if ( isset( $woothemes_sensei->settings->settings[ 'course_archive_paid_enable' ] ) && $woothemes_sensei->settings->settings[ 'course_archive_paid_enable' ] ) {
   			$shortcode_override = 'paidcourses';
   			ob_start();
			$woothemes_sensei->frontend->sensei_get_template( 'loop-course.php' );
			$content = ob_get_clean();
			return $content;
   		} // End If Statement
   		return $content;
	} // End shortcode_paid_courses()


	/**
	 * shortcode_user_courses function.
	 *
	 * @access public
	 * @param mixed $atts
	 * @param mixed $content (default: null)
	 * @return void
	 */
	function shortcode_user_courses( $atts, $content = null ) {
   		global $woothemes_sensei, $shortcode_override;
   		extract( shortcode_atts( array(	'amount' => 0 ), $atts ) );

   		$shortcode_override = 'usercourses'; // V2 - use this when creating the author archive page

   		ob_start();
   		$woothemes_sensei->frontend->sensei_get_template( 'user/my-courses.php' );
   		$content = ob_get_clean();
   		return $content;

	} // End shortcode_user_courses()

	/***************************************************************************************************
	 * 	2 - Output tags.
	 ***************************************************************************************************/


	 /**
	  * course_single_meta function.
	  *
	  * @access public
	  * @return void
	  */
	 function course_single_meta() {

	 	global $woothemes_sensei;
	 	$woothemes_sensei->frontend->sensei_get_template( 'single-course/course-meta.php' );

	 } // End course_single_meta()


	 /**
	  * course_single_lessons function.
	  *
	  * @access public
	  * @return void
	  */
	 function course_single_lessons() {

	 	global $woothemes_sensei;
	 	$woothemes_sensei->frontend->sensei_get_template( 'single-course/course-lessons.php' );

	 } // End course_single_lessons()


	 /**
	  * lesson_single_meta function.
	  *
	  * @access public
	  * @return void
	  */
	 function lesson_single_meta() {

	 	global $woothemes_sensei;
	 	$woothemes_sensei->frontend->sensei_get_template( 'single-lesson/lesson-meta.php' );

	 } // End lesson_single_meta()


	 /**
	  * quiz_questions function.
	  *
	  * @access public
	  * @param bool $return (default: false)
	  * @return void
	  */
	 function quiz_questions( $return = false ) {

	 	global $woothemes_sensei;
	 	$woothemes_sensei->frontend->sensei_get_template( 'single-quiz/quiz-questions.php' );

	 } // End quiz_questions()

	 /**
	  * quiz_question_type function.
	  *
	  * @access public
	  * @since  1.3.0
	  * @return void
	  */
	 function quiz_question_type( $question_type = 'multiple-choice' ) {

	 	global $woothemes_sensei;
	 	$woothemes_sensei->frontend->sensei_get_template( 'single-quiz/question_type-' . $question_type . '.php' );

	 } // End lesson_single_meta()

	 /***************************************************************************************************
	 * 	3 - Helper functions.
	 ***************************************************************************************************/


	/**
	 * sensei_check_prerequisite_course function.
	 *
	 * @access public
	 * @param mixed $course_id
	 * @return void
	 */
	function sensei_check_prerequisite_course( $course_id ) {
		global $current_user;
		// Get User Meta
		get_currentuserinfo();
		$course_prerequisite_id = (int) get_post_meta( $course_id, '_course_prerequisite', true);
		$prequisite_complete = false;
		if ( 0 < absint( $course_prerequisite_id ) ) {
			$prequisite_complete = WooThemes_Sensei_Utils::user_completed_course( $course_prerequisite_id, $current_user->ID );
		} else {
			$prequisite_complete = true;
		} // End If Statement

		return $prequisite_complete;

	} // End sensei_check_prerequisite_course()


	/**
	 * sensei_start_course_form function.
	 *
	 * @access public
	 * @param mixed $course_id
	 * @return void
	 */
	function sensei_start_course_form( $course_id ) {

		$prerequisite_complete = sensei_check_prerequisite_course( $course_id );

		if ( $prerequisite_complete ) {
		?><form method="POST" action="<?php echo esc_url( get_permalink() ); ?>">

    			<input type="hidden" name="<?php echo esc_attr( 'woothemes_sensei_start_course_noonce' ); ?>" id="<?php echo esc_attr( 'woothemes_sensei_start_course_noonce' ); ?>" value="<?php echo esc_attr( wp_create_nonce( 'woothemes_sensei_start_course_noonce' ) ); ?>" />

    			<span><input name="course_start" type="submit" class="course-start" value="<?php echo apply_filters( 'sensei_start_course_text', __( 'Start taking this Course', 'woothemes-sensei' ) ); ?>"/></span>

    		</form><?php
    	} // End If Statement
	} // End sensei_start_course_form()


	/**
	 * sensei_wc_add_to_cart function.
	 *
	 * @access public
	 * @param mixed $course_id
	 * @return void
	 */
	function sensei_wc_add_to_cart( $course_id ) {

		$prerequisite_complete = sensei_check_prerequisite_course( $course_id );

		if ( $prerequisite_complete ) {
			global $woothemes_sensei, $post;
	 		$woothemes_sensei->frontend->sensei_get_template( 'woocommerce/add-to-cart.php' );
	 	} // End If Statement

	} // End sensei_wc_add_to_cart()


	/**
	 * sensei_check_if_product_is_in_cart function.
	 *
	 * @access public
	 * @param int $wc_post_id (default: 0)
	 * @return void
	 */
	function sensei_check_if_product_is_in_cart( $wc_product_id = 0 ) {
		if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() ) {
			global $woocommerce;

			if ( 0 < $wc_product_id ) {
				$product = get_product( $wc_product_id );
				$parent_id = '';
				if( isset( $product->variation_id ) && 0 < intval( $product->variation_id ) ) {
					$wc_product_id = $product->parent->id;
				}
				foreach( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
					$_product = $values['data'];
					if( $wc_product_id == $_product->id ) {
						return true;
					}
				}
		    } // End If Statement
		}

		return false;
	} // End sensei_check_if_product_is_in_cart()

	/**
	 * sensei_simple_course_price function.
	 *
	 * @access public
	 * @param mixed $post_id
	 * @return void
	 */
	function sensei_simple_course_price( $post_id ) {
		global $woothemes_sensei;
		//WooCommerce Pricing
    	if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() ) {
    	    $wc_post_id = get_post_meta( $post_id, '_course_woocommerce_product', true );
    	    if ( 0 < $wc_post_id ) {
    	    	// Get the product
    	    	$product = $woothemes_sensei->sensei_get_woocommerce_product_object( $wc_post_id );

    	    	if ( isset( $product ) && !empty( $product )  &&  $product->is_purchasable() && $product->is_in_stock() && !sensei_check_if_product_is_in_cart( $wc_post_id ) ) { ?>
    	    		<span class="course-price"><?php echo $product->get_price_html(); ?></span>
    	    	<?php } // End If Statement
    	    } // End If Statement
    	} // End If Statement
	} // End sensei_simple_course_price()

	/**
	 * sensei_recent_comments_widget_filter function.
	 *
	 * @access public
	 * @param array $widget_args (default: array())
	 * @return void
	 */
	function sensei_recent_comments_widget_filter( $widget_args = array() ) {
		if ( ! isset( $widget_args['post_type'] ) ) $widget_args['post_type'] = array( 'post', 'page' );
		return $widget_args;
	} // End sensei_recent_comments_widget_filter()
	add_filter( 'widget_comments_args', 'sensei_recent_comments_widget_filter', 10, 1 );

	/**
	 * sensei_course_archive_filter function.
	 *
	 * @access public
	 * @param array $query (default: array())
	 * @return void
	 */
	function sensei_course_archive_filter( $query ) {
		global $woothemes_sensei;

		if ( ! $query->is_main_query() )
        	return;

		// Apply Filter only if on frontend and when course archive is running
		$course_page_id = intval( $woothemes_sensei->settings->settings[ 'course_page' ] );

		if ( ! is_admin() && 0 < $course_page_id && 0 < intval( $query->get( 'page_id' ) ) && $query->get( 'page_id' ) == $course_page_id ) {
			// Check for pagination settings
   			if ( isset( $woothemes_sensei->settings->settings[ 'course_archive_amount' ] ) && ( 0 < absint( $woothemes_sensei->settings->settings[ 'course_archive_amount' ] ) ) ) {
    			$amount = absint( $woothemes_sensei->settings->settings[ 'course_archive_amount' ] );
    		} else {
    			$amount = $query->get( 'posts_per_page' );
    		} // End If Statement
    		$query->set( 'posts_per_page', $amount );
		} // End If Statement
	} // End sensei_course_archive_filter()
	add_filter( 'pre_get_posts', 'sensei_course_archive_filter', 10, 1 );

	/**
	 * sensei_complete_lesson_button description
	 * since 1.0.3
	 * @return html
	 */
	function sensei_complete_lesson_button() {
		do_action( 'sensei_complete_lesson_button' );
	} // End sensei_complete_lesson_button()

	/**
	 * sensei_reset_lesson_button description
	 * since 1.0.3
	 * @return html
	 */
	function sensei_reset_lesson_button() {
		do_action( 'sensei_reset_lesson_button' );
	} // End sensei_reset_lesson_button()

	/**
	 * sensei_get_prev_next_lessons Returns the next and previous Lessons in the Course
	 * since 1.0.9
	 * @param  integer $lesson_id
	 * @return array $return_values
	 */
	function sensei_get_prev_next_lessons( $lesson_id = 0 ) {
		global $woothemes_sensei;
		$return_values = array();
		$return_values['prev_lesson'] = 0;
		$return_values['next_lesson'] = 0;
		if ( 0 < $lesson_id ) {
			// Get the List of Lessons in the Course
			$lesson_course_id = get_post_meta( $lesson_id, '_lesson_course', true );
			$all_lessons = array();

            $modules = Sensei()->modules->get_course_modules( intval( $lesson_course_id ) );

            foreach( (array) $modules as $module ) {

                $args = array(
                    'post_type' => 'lesson',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        array(
                            'key' => '_lesson_course',
                            'value' => intval( $lesson_course_id ),
                            'compare' => '='
                        )
                    ),
                    'tax_query' => array(
                        array(
                            'taxonomy' => Sensei()->modules->taxonomy,
                            'field' => 'id',
                            'terms' => intval( $module->term_id )
                        )
                    ),
                    'meta_key' => '_order_module_' . $module->term_id,
                    'orderby' => 'meta_value_num date',
                    'order' => 'ASC',
                    'suppress_filters' => 0
                );

                $lessons = get_posts( $args );
                if ( 0 < count( $lessons ) ) {
                    foreach ($lessons as $lesson_item){
                        $all_lessons[] = $lesson_item->ID;
                    } // End For Loop
                } // End If Statement
            }

            $args = array(
                'post_type' => 'lesson',
                'posts_per_page' => -1,
                'suppress_filters' => 0,
                'meta_key' => '_order_' . $lesson_course_id,
                'orderby' => 'meta_value_num date',
                'order' => 'ASC',
                'meta_query' => array(
                    array(
                        'key' => '_lesson_course',
                        'value' => intval( $lesson_course_id ),
                    ),
                ),
                'post__not_in' => $all_lessons,
            );

            $other_lessons = get_posts( $args );
            if ( 0 < count( $other_lessons ) ) {
				foreach ($other_lessons as $lesson_item){
					$all_lessons[] = $lesson_item->ID;
				} // End For Loop
			} // End If Statement

            if ( 0 < count( $all_lessons ) ) {
				$found_index = false;
				foreach ( $all_lessons as $lesson ){
					if ( $found_index && $return_values['next_lesson'] == 0 ) {
						$return_values['next_lesson'] = $lesson;
					} // End If Statement
					if ( $lesson == $lesson_id ) {
						// Is the current post
						$found_index = true;
					} // End If Statement
					if ( !$found_index ) {
						$return_values['prev_lesson'] = $lesson;
					} // End If Statement
				} // End For Loop
			} // End If Statement

		} // End If Statement
		return $return_values;
	} // End sensei_get_prev_next_lessons()

	function sensei_has_user_started_course( $post_id = 0, $user_id = 0 ) {
		_deprecated_function( __FUNCTION__, '1.7', "WooThemes_Sensei_Utils::user_started_course()" );
		return WooThemes_Sensei_Utils::user_started_course( $post_id, $user_id );
	} // End sensei_has_user_started_course()

	function sensei_has_user_completed_lesson( $post_id = 0, $user_id = 0 ) {
		_deprecated_function( __FUNCTION__, '1.7', "WooThemes_Sensei_Utils::user_completed_lesson()" );
		return WooThemes_Sensei_Utils::user_completed_lesson( $post_id, $user_id );
	} // End sensei_has_user_completed_lesson()

	function sensei_has_user_completed_prerequisite_lesson( $post_id = 0, $user_id = 0 ) {
		return sensei_has_user_completed_lesson( $post_id, $user_id );
	} // End sensei_has_user_completed_prerequisite_lesson()