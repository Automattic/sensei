<?php
if ( ! defined( 'ABSPATH' ) ){ exit; } // Exit if accessed directly

	/***************************************************************************************************
	 * 	Output tags.
	 ***************************************************************************************************/

    /**
     * sensei_course_archive_next_link function.
     *
     * @access public
     * @param string $type (default: 'newcourses')
     * @return void
     */
    function sensei_course_archive_next_link( $type = 'newcourses' ) {

        _deprecated_function('sensei_course_archive_next_link', '1.9.0','This is no longer used or required in Sensei.');

    } // End sensei_course_archive_next_link()

	 /**
	  * course_single_lessons function.
	  *
	  * @access public
	  * @return void
	  */
	 function course_single_lessons() {

         // load backwards compatible template name if it exists in the users theme
         $located_template= locate_template( Sensei()->template_url . 'single-course/course-lessons.php' );
         if( $located_template ){

             Sensei_Templates::get_template( 'single-course/course-lessons.php' );
             return;

        }

		Sensei_Templates::get_template( 'single-course/lessons.php' );

	 } // End course_single_lessons()


	 /**
	  * lesson_single_meta function.
	  *
	  * @access public
	  * @return void
	  */
	 function lesson_single_meta() {

         _deprecated_function('lesson_single_meta','1.9;0', 'WooThemes_Sensei_Lesson::the_lesson_meta' );
         sensei_the_single_lesson_meta();

	 } // End lesson_single_meta()


	 /**
	  * quiz_questions function.
	  *
	  * @access public
	  * @param bool $return (default: false)
	  * @return void
      * @deprecated since 1.9.0
	  */
	 function quiz_questions( $return = false ) {

	 	Sensei_Templates::get_template( 'single-quiz/quiz-questions.php' );

	 } // End quiz_questions()

	 /**
	  * quiz_question_type function.
	  *
	  * @access public
	  * @since  1.3.0
	  * @return void
      * @deprecated
	  */
	 function quiz_question_type( $question_type = 'multiple-choice' ) {

         Sensei_Templates::get_template( 'single-quiz/question_type-' . $question_type . '.php' );

	 } // End lesson_single_meta()

	 /***************************************************************************************************
	 * Helper functions.
	 ***************************************************************************************************/

	/**
	 * sensei_check_prerequisite_course function.
	 *
     * @deprecated since 1.9.0 use Sensei_Course::is_prerequisite_complete( $course_id );
	 * @access public
	 * @param mixed $course_id
	 * @return bool
	 */
	function sensei_check_prerequisite_course( $course_id ) {

        return Sensei_Course::is_prerequisite_complete( $course_id );

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
		?><form method="POST" action="<?php echo esc_url( get_permalink( $course_id ) ); ?>">

    			<input type="hidden" name="<?php echo esc_attr( 'woothemes_sensei_start_course_noonce' ); ?>" id="<?php echo esc_attr( 'woothemes_sensei_start_course_noonce' ); ?>" value="<?php echo esc_attr( wp_create_nonce( 'woothemes_sensei_start_course_noonce' ) ); ?>" />

    			<span><input name="course_start" type="submit" class="course-start" value="<?php _e( 'Start taking this Course', 'woothemes-sensei' ); ?>"/></span>

    		</form><?php
    	} // End If Statement
	} // End sensei_start_course_form()


	/**
	 * sensei_wc_add_to_cart function.
	 *
     * @deprecated since Sensei_WC::the_add_to_cart_button_html( $course_id );
	 * @access public
	 * @param mixed $course_id
	 * @return void
	 */
	function sensei_wc_add_to_cart( $course_id ) {

		Sensei_WC::the_add_to_cart_button_html( $course_id );

	} // End sensei_wc_add_to_cart()


	/**
	 * sensei_check_if_product_is_in_cart function.
	 *
	 * @deprecated since 1.9.0
	 * @param int $wc_post_id (default: 0)
	 * @return bool
	 */
	function sensei_check_if_product_is_in_cart( $wc_product_id = 0 ) {
        return Sensei_WC::is_product_in_cart( $wc_product_id );
	} // End sensei_check_if_product_is_in_cart()

	/**
	 * sensei_simple_course_price function.
	 *
	 * @access public
	 * @param mixed $post_id
	 * @return void
	 */
	function sensei_simple_course_price( $post_id ) {

		global $wp_the_query;

		// check for the my courses shortcode
		if ( strpos( $wp_the_query->post->post_content, 'sensei_user_courses' ) || strpos( $wp_the_query->post->post_content, 'usercourses' ) ) {
			return;
		}

		$wc_post_id = get_post_meta( $post_id, '_course_woocommerce_product', true );
        if ( ! Sensei_WC::is_woocommerce_active() || empty( $wc_post_id ) || false  ) {
			return;
        }

        // Get the product
        $product = Sensei_WC::get_product_object( $wc_post_id );

        if ( isset( $product ) && !empty( $product )  &&  $product->is_purchasable()
             && $product->is_in_stock() && !sensei_check_if_product_is_in_cart( $wc_post_id ) ) { ?>

            <span class="course-price">
	            <?php echo $product->get_price_html(); ?>
            </span>

        <?php } // End If Statement

	} // End sensei_simple_course_price()

	/**
	 * sensei_recent_comments_widget_filter function.
	 *
	 * @access public
	 * @param array $widget_args (default: array())
	 * @return array
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
	 * @param WP_Query $query ( default: array ( ) )
	 * @return void
	 */
	function sensei_course_archive_filter( $query ) {


		if ( ! $query->is_main_query() )
        	return;

		// Apply Filter only if on frontend and when course archive is running
		$course_page_id = intval( Sensei()->settings->settings[ 'course_page' ] );

		if ( ! is_admin() && 0 < $course_page_id && 0 < intval( $query->get( 'page_id' ) ) && $query->get( 'page_id' ) == $course_page_id ) {
			// Check for pagination settings
   			if ( isset( Sensei()->settings->settings[ 'course_archive_amount' ] ) && ( 0 < absint( Sensei()->settings->settings[ 'course_archive_amount' ] ) ) ) {
    			$amount = absint( Sensei()->settings->settings[ 'course_archive_amount' ] );
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

		$return_values = array();
		$return_values['prev_lesson'] = 0;
		$return_values['next_lesson'] = 0;
		if ( 0 < $lesson_id ) {
			// Get the List of Lessons in the Course
			$lesson_course_id = get_post_meta( $lesson_id, '_lesson_course', true );
			$all_lessons = array();

            $modules = Sensei()->modules->get_course_modules( intval( $lesson_course_id ) );

            if( !empty( $modules )  ){
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

                }//end for each

            }// end if empty modules

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

  /**
   * sensei_get_excerpt Returns the excerpt for the $post
   *
   * Unhooks wp_trim_excerpt() so to disable excerpt auto-gen.
   *
   * @deprecated since  1.9.0
   * @param  int|WP_Post $post_id Optional. Defaults to current post
   * @return string $excerpt
   */
  function sensei_get_excerpt( $post_id = '' ) {

      global $post;
      _deprecated_function('sensei_get_excerpt', 'use the wordpress excerpt functionality.');
      return get_the_excerpt();

  }

	function sensei_has_user_started_course( $post_id = 0, $user_id = 0 ) {
		_deprecated_function( __FUNCTION__, '1.7', "WooThemes_Sensei_Utils::user_started_course()" );
		return Sensei_Utils::user_started_course( $post_id, $user_id );
	} // End sensei_has_user_started_course()

	function sensei_has_user_completed_lesson( $post_id = 0, $user_id = 0 ) {
		_deprecated_function( __FUNCTION__, '1.7', "WooThemes_Sensei_Utils::user_completed_lesson()" );
		return Sensei_Utils::user_completed_lesson( $post_id, $user_id );
	} // End sensei_has_user_completed_lesson()

/**
 * Determine if a user has completed the pre-requisite lesson.
 *
 * @uses
 *
 * @param int $current_lesson_id
 * @param int $user_id
 * @return bool
*/
function sensei_has_user_completed_prerequisite_lesson( $current_lesson_id, $user_id ) {

    return WooThemes_Sensei_Lesson::is_prerequisite_complete( $current_lesson_id, $user_id );

} // End sensei_has_user_completed_prerequisite_lesson()

/*******************************
 *
 * Module specific template tags
 *
 ******************************/

/**
 * This function checks if the current course has modules.
 *
 * This must only be used within the loop.
 *
 * I checks the current global post (course) if it has modules.
 *
 * @since 1.9.0
 *
 * @param string $course_post_id options
 * @return bool
 *
 */
function sensei_have_modules(  ){

	global $sensei_modules_loop;

	// check the current item compared to the total number of modules
	if( $sensei_modules_loop[ 'current' ] + 1 > $sensei_modules_loop[ 'total' ]  ){

		return false;

	}else{

		return true;

	}

} //sensei_have_modules


/**
 * Setup the next module int the module loop
 *
 * @since 1.9.0
 */
function sensei_setup_module(){

	global  $sensei_modules_loop, $wp_query;

	// increment the index
	$sensei_modules_loop[ 'current' ]++;
	$index = $sensei_modules_loop[ 'current' ];
	if( isset( $sensei_modules_loop['modules'][ $index ] ) ) {

		$sensei_modules_loop['current_module'] = $sensei_modules_loop['modules'][$index];
		// setup the query for the module lessons
		$course_id = $sensei_modules_loop['course_id'];
		$module_term_id = $sensei_modules_loop['current_module']->term_id;
		$modules_query = Sensei()->modules->get_lessons_query( $course_id , $module_term_id );

		// setup the global wp-query only if the lessons
		if( $modules_query->have_posts() ){

			$wp_query = $modules_query;

		}else{

			wp_reset_query();

		}

	} else {

		wp_reset_query();

	}

}// end sensei_the_module

/**
 * Check if the current module in the modules loop has any lessons.
 * This relies on the global $wp_query. Which will be setup for each module
 * by sensei_the_module(). This function must only be used withing the module lessons loop.
 *
 * If the loop has not been initiated this function will check if the first
 * module has lessons.
 *
 * @return bool
 */
function sensei_module_has_lessons(){

	global $wp_query, $sensei_modules_loop;

	if( 'lesson' == $wp_query->get('post_type') ){

		return have_posts();

	}else{

        // if the loop has not been initiated check the first module has lessons
        if( -1 == $sensei_modules_loop[ 'current' ]  ){

            $index = 0;

            if( isset( $sensei_modules_loop['modules'][ $index ] ) ) {
                // setup the query for the module lessons
                $course_id = $sensei_modules_loop['course_id'];

                $module_term_id = $sensei_modules_loop['modules'][ $index ] ->term_id;
                $modules_query = Sensei()->modules->get_lessons_query( $course_id , $module_term_id );

                // setup the global wp-query only if the lessons
                if( $modules_query->have_posts() ){

                    return true;

                }
            }
        }
        // default to false if the first module doesn't have posts
		return false;

	}

}

/**
 * This function return the Module title to be used as an html element attribute value.
 *
 * Should only be used within the Sensei modules loop.
 *
 * @since 1.9.0
 *
 * @uses sensei_the_module_title
 * @return string
 */
function sensei_the_module_title_attribute(){

	echo esc_attr( sensei_get_the_module_title() );

}

/**
 * Returns a permalink to the module currently loaded within the Single Course module loop.
 *
 * This function should only be used with the Sensei modules loop.
 *
 * @return string
 */
function sensei_the_module_permalink(){

	global $sensei_modules_loop;
	$course_id = $sensei_modules_loop['course_id'];
	$module_url = add_query_arg('course_id', $course_id, get_term_link( $sensei_modules_loop['current_module'], 'module' ) );
	$module_term_id = $sensei_modules_loop['current_module']->term_id;

	/**
	 * Filter the module permalink url. This fires within the sensei_the_module_permalink function.
	 *
	 * @since 1.9.0
	 *
	 * @param string $module_url
	 * @param int $module_term_id
	 * @param string $course_id
	 */
	 echo esc_url_raw( apply_filters( 'sensei_the_module_permalink', $module_url, $module_term_id  ,$course_id ) );

}// end sensei_the_module_permalink

/**
 * Returns the current module name. This must be used
 * within the Sensei module loop.
 *
 * @since 1.9.0
 *
 * @return string
 */
function sensei_get_the_module_title(){

	global $sensei_modules_loop;

	$module_title = $sensei_modules_loop['current_module']->name;
	$module_term_id = $sensei_modules_loop['current_module']->term_id;
	$course_id = $sensei_modules_loop['course_id'];

	/**
	 * Filter the module title.
	 *
	 * This fires within the sensei_the_module_title function.
	 *
	 * @since 1.9.0
	 *
	 * @param $module_title
	 * @param $module_term_id
	 * @param $course_id
	 */
	return apply_filters( 'sensei_the_module_title',  $module_title , $module_term_id, $course_id );

}

/**
 * Ouputs the current module name. This must be used
 * within the Sensei module loop.
 *
 * @since 1.9.0
 * @uses sensei_get_the_module_title
 * @return string
 */
function sensei_the_module_title(){

	echo sensei_get_the_module_title();

}

/**
 * Give the current user's lesson progress status
 * Used in the module loop on the courses page
 *
 * @since 1.9.0
 * @return string
 */
function sensei_get_the_module_status(){

	if( ! is_user_logged_in() ){
		return '';
	}

    global $sensei_modules_loop;
    $module_title = $sensei_modules_loop['current_module']->name;
    $module_term_id = $sensei_modules_loop['current_module']->term_id;
    $course_id = $sensei_modules_loop['course_id'];
    $module_progress = Sensei()->modules->get_user_module_progress( $module_term_id, $course_id, get_current_user_id() );

    $module_status =  '';
    if ( $module_progress && $module_progress > 0) {

        $module_status = __('Completed', 'woothemes-sensei');

        if ($module_progress < 100) {

            $module_status = __('In progress', 'woothemes-sensei');

        }

    }

	if ( empty( $module_status ) ){
		return '';
	}

	$status_class = strtolower( str_replace( ' ', '-', $module_status  ) );
    $module_status_html = '<p class="status module-status ' . $status_class . '">'
                            . $module_status
                            . '</p>';

    /**
     * Filter the module status.
     *
     * This fires within the sensei_get_the_module_status function.
     *
     * @since 1.9.0
     *
     * @param $module_status_html
     * @param $module_term_id
     * @param $course_id
     */
    return apply_filters( 'sensei_the_module_status_html',  $module_status_html , $module_term_id, $course_id );

}

/**
 * Print out the current module status
 * @since 1.9.0
 */
function sensei_the_module_status(){

    echo sensei_get_the_module_status();

}

/**
 * Get the module ID.
 * This must be used within the Sensei module loop.
 *
 * @since 1.9.7
 *
 * @return int $id Module ID.
 */
function sensei_get_the_module_id() {
	global $sensei_modules_loop;

	$module_term_id = $sensei_modules_loop['current_module']->term_id;

	/**
	 * Filter the module ID.
	 *
	 * This fires within the sensei_get_the_module_id function.
	 *
	 * @since 1.9.7
	 *
	 * @param int $module_term_id Module ID.
	 */
	return apply_filters( 'sensei_the_module_id', $module_term_id );
}

/**
 * Print out the current module ID
 * @since 1.9.7
 */
function sensei_the_module_id(){

	echo sensei_get_the_module_id();

}

/************************
 *
 * Single Quiz Functions
 *
 ***********************/

/**
 * This function can only be run inside the the quiz question lessons loop.
 *
 * It will check if the current lessons loop has questions
 *
 * @since 1.9.0
 *
 * @return bool
 */
function sensei_quiz_has_questions(){

    global $sensei_question_loop;

    if( !isset( $sensei_question_loop['total'] ) ){
        return false;
    }

    if( $sensei_question_loop['current'] + 1 < $sensei_question_loop['total']  ){

        return true;

    }else{

        return false;

    }

}// end sensei_quiz_has_questions

/**
 * This funciton must only be run inside the quiz question loop.
 *
 * It will setup the next question in the loop into the current spot within the loop for further
 * execution.
 *
 * @since 1.9.0

 */
function sensei_setup_the_question(){

    global $sensei_question_loop;

    $sensei_question_loop['current']++;
    $index = $sensei_question_loop['current'];
    $sensei_question_loop['current_question'] =  $sensei_question_loop['questions'][ $index ] ;

}// end sensei_setup_the_question

/**
 * This function must only be run inside the quiz question loop.
 *
 * This function gets the type and loads the template that will handle it.
 *
 */
function sensei_the_question_content(){

    global $sensei_question_loop;

    $question_type = Sensei()->question->get_question_type( $sensei_question_loop['current_question']->ID );

    // load the template that displays the question information.
    WooThemes_Sensei_Question::load_question_template( $question_type );

}// end sensei_the_question_content

/**
 * Outputs the question class. This must only be run withing the single quiz question loop.
 *
 * @since 1.9.0
 */
function sensei_the_question_class(){

    global $sensei_question_loop;

    $question_type = Sensei()->question->get_question_type( $sensei_question_loop['current_question']->ID );

    /**
     * filter the sensei question class within
     * the quiz question loop.
     *
     * @since 1.9.0
     */
     $classes = apply_filters( 'sensei_question_classes', array( $question_type ) );

    $html_classes = '';
    foreach( $classes as $class ){

        $html_classes .= $class . ' ';

    }// end foreach

    echo esc_attr( trim( $html_classes ) );

}

/**
 * Output the ID of the current question within the quiz question loop.
 *
 * @since 1.9.0
 */
function sensei_get_the_question_id( ){

    global $sensei_question_loop;
    if( isset( $sensei_question_loop['current_question']->ID ) ){

        return $sensei_question_loop['current_question']->ID;

    }

}// end sensei_the_question_id

/************************
 *
 * Single Lesson Functions
 *
 ***********************/

/**
 * Template function to determine if the current user can
 * access the current lesson content being viewed.
 *
 * This function checks in the folowing order
 * - if the current user has all access based on their permissions
 * - If the access permission setting is enabled for this site, if not the user has accces
 * - if the lesson has a pre-requisite and if the user has completed that
 * - If it is a preview the user has access as well
 *
 * @since 1.9.0
 *
 * @param string $lesson_id
 * @return bool
 */
function sensei_can_user_view_lesson( $lesson_id = '', $user_id = ''  ){

    if( empty( $lesson_id ) ){

        $lesson_id = get_the_ID();

    }

	if ( 'quiz'== get_post_type( get_the_ID() ) ){

		$lesson_id = Sensei()->quiz->get_lesson_id( get_the_ID() );

	}

    if( empty( $user_id ) ){

        $user_id = get_current_user_id();

    }

    // Check for prerequisite lesson completions
    $pre_requisite_complete = WooThemes_Sensei_Lesson::is_prerequisite_complete( $lesson_id, $user_id );
    $lesson_course_id = get_post_meta( $lesson_id, '_lesson_course', true );
    $user_taking_course = Sensei_Utils::user_started_course( $lesson_course_id, $user_id );

    $is_preview = false;
    if( Sensei_Utils::is_preview_lesson( $lesson_id ) ) {

        $is_preview = true;
        $pre_requisite_complete = true;

    };


    $user_can_access_lesson =  false;

    if( is_user_logged_in() && $user_taking_course ){

        $user_can_access_lesson =  true;

    }


    $access_permission = false;

    if ( ! Sensei()->settings->get('access_permission')  || sensei_all_access() ) {

        $access_permission = true;

    }

    $can_user_view_lesson = $access_permission || ( $user_can_access_lesson && $pre_requisite_complete ) || $is_preview;

    /**
     * Filter the can user view lesson function
     *
     * @since 1.9.0
     *
     * @hooked Sensei_WC::alter_can_user_view_lesson
     *
     * @param bool $can_user_view_lesson
     * @param string $lesson_id
     * @param string $user_id
     */
    return apply_filters( 'sensei_can_user_view_lesson', $can_user_view_lesson, $lesson_id, $user_id );

} // end sensei_can_current_user_view_lesson

/**
 * Ouput the single lesson meta
 *
 * The function should only be called on the single lesson
 *
 */
function sensei_the_single_lesson_meta(){

    // if the lesson meta is included within theme load that instead of the function content
    $template = Sensei_Templates::locate_template( 'single-lesson/lesson-meta.php' );
    if( ! empty( $template ) ){

        Sensei_Templates::get_template( 'single-lesson/lesson-meta.php' );
        return;

    }

    // Get the meta info
    $lesson_course_id = absint( get_post_meta( get_the_ID(), '_lesson_course', true ) );
    $is_preview = Sensei_Utils::is_preview_lesson( get_the_ID() );

    // Complete Lesson Logic
    do_action( 'sensei_complete_lesson' );
    // Check that the course has been started
    if ( Sensei()->access_settings()
        || Sensei_Utils::user_started_course( $lesson_course_id, get_current_user_id())
        || $is_preview ) {
        ?>
        <section class="lesson-meta">
            <?php
            if( apply_filters( 'sensei_video_position', 'top', get_the_ID() ) == 'bottom' ) {

                do_action( 'sensei_lesson_video', get_the_ID() );

            }
            ?>
            <?php do_action( 'sensei_frontend_messages' ); ?>

            <?php if ( ! $is_preview
                || Sensei_Utils::user_started_course( $lesson_course_id, get_current_user_id()) ) {

                sensei_do_deprecated_action( 'sensei_lesson_quiz_meta','1.9.0', 'sensei_single_lesson_content_inside_before' ,array( get_the_ID(), get_current_user_id() )  );

            } ?>
        </section>

        <?php do_action( 'sensei_lesson_back_link', $lesson_course_id ); ?>

    <?php }

    do_action( 'sensei_lesson_meta_extra', get_the_ID() );

} // end the_single_lesson_meta

/**
 * This function runs the most common header hooks and ensures
 * templates are setup correctly.
 *
 * This function also runs the get_header for the general WP header setup.
 *
 * @uses get_header
 *
 * @since 1.9.0
 */
function get_sensei_header(){

	/**
	 * Allow user to stop the output of
	 * get_sensei_header which also includes a call to get_header.
	 * @since 1.9.5 introduced
	 */
	if ( ! apply_filters( 'sensei_show_main_header', true ) ) {
		return;
	}

    if ( ! defined( 'ABSPATH' ) ) exit;

    get_header();

    /**
     * sensei_before_main_content hook
     *
     * @hooked sensei_output_content_wrapper - 10 (outputs opening divs for the content)
     */
    do_action( 'sensei_before_main_content' );

}// end get_sensei_header

/**
 * This function runs the most common footer hooks and ensures
 * templates are setup correctly.
 *
 * This function also runs the get_header for the general WP header setup.
 *
 * @uses get_footer
 *
 * @since 1.9.0
 */
function get_sensei_footer(){

	/**
	 * Allow user to stop the output of
	 * get_sensei_footer which also includes a call to get_header.
	 * @since 1.9.5 introduced
	 */
	if ( ! apply_filters( 'sensei_show_main_footer', true ) ) {
		return;
	}

    /**
     * sensei_pagination hook
     *
     * @hooked sensei_pagination - 10 (outputs pagination)
     */
    do_action( 'sensei_pagination' );

    /**
     * sensei_after_main_content hook
     *
     * @hooked sensei_output_content_wrapper_end - 10 (outputs closing divs for the content)
     */
    do_action( 'sensei_after_main_content' );

    /**
     * sensei_sidebar hook
     *
     * @hooked sensei_get_sidebar - 10
     */
    do_action( 'sensei_sidebar' );

    get_footer();

}// end get_sensei_header

/**
 * Output the permissions message
 * title.
 *
 * @since 1.9.0
 */
function the_no_permissions_title(){

    /**
     * Filter the no permissions title just before it is echo'd on the
     * no-permissions.php file.
     *
     * @since 1.9.0
     * @param $no_permissions_title
     */
    echo apply_filters( 'sensei_the_no_permissions_title', Sensei()->permissions_message['title'] );

}

/**
 * Output the permissions message.
 *
 * @since 1.9.0
 */
function the_no_permissions_message( $post_id ){

    /**
     * Filter the no permissions message just before it is echo'd on the
     * no-permissions.php file.
     *
     * @since 1.9.0
     * @param $no_permissions_message
     */
    echo apply_filters( 'sensei_the_no_permissions_message', Sensei()->permissions_message['message'] , $post_id );
}

/**
 * Output the sensei excerpt
 *
 * @since 1.9.0
 */
function sensei_the_excerpt( $post_id ){

    global $post;
    the_excerpt( $post );

}

/**
 * Get current url on the frontend
 *
 * @since 1.9.0
 *
 * @global WP $wp
 * @return string $current_page_url
 */
 function sensei_get_current_page_url(){

     global $wp;
     $current_page_url =  home_url( $wp->request );
     return $current_page_url;

 }

/**
 * Outputs the content for the my courses page
 *
 *
 * @since 1.9.0
 */
function sensei_the_my_courses_content(){

    echo Sensei()->course->load_user_courses_content( wp_get_current_user() );

} // sensei_the_my_courses_content

/**
 * This is a wrapper function for Sensei_Templates::get_template
 * It helps simplify templates for designers by removing the class::function call.
 *
 * @param string $template_name the name of the template.
 *              If it is in a sub directory please suply the directory name as well e.g. globals/wrapper-end.php
 *
 * @since 1.9.0
 */
function sensei_load_template( $template_name ){

    Sensei_Templates::get_template( $template_name );

}

/**
 * This is a wrapper function for Sensei_Templates::get_part
 * It helps simplify templates for designers by removing the class::function call.
 *
 * @param string $slug the first part to the template file name
 * @param string $name the name of the template.
 * @since 1.9.0
 */
function sensei_load_template_part( $slug, $name ){

    Sensei_Templates::get_part( $slug, $name );

}

/**
 * Returns the the lesson excerpt.
 *
 * This function will not wrap the the excerpt with <p> tags.
 * For the p tags call Sensei_Lesson::lesson_excerpt( $lesson)
 *
 * This function will only work for the lesson post type. All other post types will
 * be ignored.
 *
 * @since 1.9.0
 * @access public
 * @param string $lesson_id
 */
function sensei_the_lesson_excerpt( $lesson_id = '' ) {

    if( empty( $lesson_id )){

        $lesson_id = get_the_ID();

    }

    if( 'lesson' != get_post_type( $lesson_id ) ){
        return;
    }

    echo Sensei_Lesson::lesson_excerpt( get_post( $lesson_id ), false );

}// End lesson_excerpt()

/**
 * The the course result lessons template
 *
 * @since 1.9.0
 */
function sensei_the_course_results_lessons(){
    // load backwards compatible template name if it exists in the users theme
    $located_template= locate_template( Sensei()->template_url . 'course-results/course-lessons.php' );
    if( $located_template ){

        Sensei_Templates::get_template( 'course-results/course-lessons.php' );
        return;

    }

    Sensei_Templates::get_template( 'course-results/lessons.php' );
}

/**
 * Echo the number of columns (also number of items per row) on the
 * the course archive.
 *
 * @uses Sensei_Course::get_loop_number_of_columns
 * @since 1.9.0
 */
function sensei_courses_per_row(){

    echo Sensei_Course::get_loop_number_of_columns();

}

/**
 * Wrapper function for Sensei_Templates::get_template( $template_name, $args, $path )
 *
 * @since 1.9.0
 * @param $template_name
 * @param $args
 * @param $path
 */
function sensei_get_template( $template_name, $args, $path ){

    Sensei_Templates::get_template( $template_name, $args, $path );

}

/**
 * Returns the lesson status class
 *
 * must be used in the loop.
 * @since 1.9.0
 *
 * @return string $status_class
 */
function get_the_lesson_status_class(){

    $status_class = '';
    $lesson_completed = Sensei_Utils::user_completed_lesson( get_the_ID(), get_current_user_id() );

    if ( $lesson_completed ) {
        $status_class = 'completed';
    }

    return  $status_class;

}// end get_the_lesson_status_class
/**
 * Outputs the lesson status class
 *
 * must be used in the lesson loop
 *
 * @since 1.9.0
 */
function sensei_the_lesson_status_class(){

    echo get_the_lesson_status_class();
}

/**
 * Get the module description.
 * This is to be used within the Sensei module loop.
 *
 *
 * @return string Module description.
 */
function sensei_get_the_module_description() {
	global $sensei_modules_loop;
	$module_description = $sensei_modules_loop['current_module']->description;
	/**
	 * Filter the module description.
	 *
	 * This fires within the sensei_get_the_module_description function.
	 *
	 * @param $module_description Module Description.
	 */
	return apply_filters( 'sensei_the_module_description', $module_description );
}

/**
 * Print out the current module Description
 */
function sensei_the_module_description(){
	echo sensei_get_the_module_description();
}
