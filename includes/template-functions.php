<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; } // Exit if accessed directly

	/***************************************************************************************************
	 *  Output tags.
	 ***************************************************************************************************/

	 /**
	  * course_single_lessons function.
	  *
	  * @access public
	  * @return void
	  */
function course_single_lessons() {
	if ( ! Sensei_Utils::show_course_lessons( get_the_ID() ) ) {
		return;
	}

	add_filter( 'post_class', [ 'Sensei_Lesson', 'single_course_lessons_classes' ] );

	// load backwards compatible template name if it exists in the users theme
	$located_template = locate_template( Sensei()->template_url . 'single-course/course-lessons.php' );
	if ( $located_template ) {

		Sensei_Templates::get_template( 'single-course/course-lessons.php' );
		return;

	}

	Sensei_Templates::get_template( 'single-course/lessons.php' );

	remove_filter( 'post_class', [ 'Sensei_Lesson', 'single_course_lessons_classes' ] );

}

	 /**
	  * quiz_questions function.
	  *
	  * @access public
	  * @param bool $return (default: false)
	  * @return void
	  * @deprecated since 1.9.0 use Sensei_Templates::get_template
	  */
function quiz_questions( $return = false ) {

	// To be removed in 5.0.0.
	_deprecated_function( __FUNCTION__, '1.9.0', 'Sensei_Templates::get_template' );
	Sensei_Templates::get_template( 'single-quiz/quiz-questions.php' );

}

	 /**
	  * quiz_question_type function.
	  *
	  * @access public
	  * @since  1.3.0
	  * @return void
	  * @deprecated
	  */
function quiz_question_type( $question_type = 'multiple-choice' ) {

	Sensei_Question::load_question_template( $question_type );

}

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

	// To be removed in 5.0.0.
	_deprecated_function( __FUNCTION__, '1.9.0', 'Sensei_Course::is_prerequisite_complete' );
	return Sensei_Course::is_prerequisite_complete( $course_id );

}


	/**
	 * sensei_start_course_form function.
	 *
	 * @access public
	 * @param mixed $course_id
	 * @return void
	 */
function sensei_start_course_form( $course_id ) {

	$prerequisite_complete = Sensei_Course::is_prerequisite_complete( $course_id );

	if ( $prerequisite_complete ) {
		wp_enqueue_script( 'sensei-stop-double-submission' );

		?><form method="POST" action="<?php echo esc_url( get_permalink( $course_id ) ); ?>">

				<input type="hidden" name="<?php echo esc_attr( 'woothemes_sensei_start_course_noonce' ); ?>" id="<?php echo esc_attr( 'woothemes_sensei_start_course_noonce' ); ?>" value="<?php echo esc_attr( wp_create_nonce( 'woothemes_sensei_start_course_noonce' ) ); ?>" />

				<span><input name="course_start" type="submit" class="course-start sensei-stop-double-submission" value="<?php esc_html_e( 'Take This Course', 'sensei-lms' ); ?>"/></span>

			</form>
			<?php
	}
}


/**
 * sensei_wc_add_to_cart function.
 *
 * @deprecated since Sensei_WC::the_add_to_cart_button_html( $course_id );
 * @access public
 *
 * @param mixed $course_id Course Post ID.
 * @return void
 */
function sensei_wc_add_to_cart( $course_id ) {
	_deprecated_function( __FUNCTION__, '1.9.0', 'Sensei_WC::the_add_to_cart_button_html' );

	if ( ! method_exists( 'Sensei_WC', 'the_add_to_cart_button_html' ) ) {
		return;
	}

	Sensei_WC::the_add_to_cart_button_html( $course_id );
}


/**
 * sensei_check_if_product_is_in_cart function.
 *
 * @deprecated since 1.9.0 use is_product_in_cart()
 *
 * @param int $wc_post_id Post ID for product (default: 0).
 * @return bool
 */
function sensei_check_if_product_is_in_cart( $wc_product_id = 0 ) {
	_deprecated_function( __FUNCTION__, '1.9.0', 'Sensei_WC::is_product_in_cart' );

	if ( ! method_exists( 'Sensei_WC', 'is_product_in_cart' ) ) {
		return false;
	}

	return Sensei_WC::is_product_in_cart( $wc_product_id );
}

	/**
	 * sensei_simple_course_price function.
	 *
	 * @deprecated 2.0.0 Use `\Sensei_WC_Paid_Courses\Frontend\Courses::output_course_price()` if it exists.
	 * @param mixed $post_id
	 * @return void
	 */
function sensei_simple_course_price( $post_id ) {
	_deprecated_function( __FUNCTION__, '2.0.0', 'Sensei_WC_Paid_Courses\Frontend\Courses::output_course_price()' );

	if ( ! method_exists( 'Sensei_WC_Paid_Courses\Frontend\Courses', 'output_course_price' ) ) {
		return;
	}

	\Sensei_WC_Paid_Courses\Frontend\Courses::instance()->output_course_price( $post_id );
}

	/**
	 * sensei_recent_comments_widget_filter function.
	 *
	 * @access public
	 * @param array $widget_args (default: array())
	 * @return array
	 */
function sensei_recent_comments_widget_filter( $widget_args = array() ) {
	if ( ! isset( $widget_args['post_type'] ) ) {
		$widget_args['post_type'] = array( 'post', 'page' );
	}
	return $widget_args;
}
	add_filter( 'widget_comments_args', 'sensei_recent_comments_widget_filter', 10, 1 );

	/**
	 * sensei_course_archive_filter function.
	 *
	 * @access public
	 * @param WP_Query $query ( default: array ( ) )
	 * @return void
	 */
function sensei_course_archive_filter( $query ) {

	if ( ! $query->is_main_query() ) {
		return;
	}

	// Apply Filter only if on frontend and when course archive is running
	$course_page_id = intval( Sensei()->settings->settings['course_page'] );

	if ( ! is_admin() && 0 < $course_page_id && 0 < intval( $query->get( 'page_id' ) ) && $query->get( 'page_id' ) == $course_page_id ) {
		// Check for pagination settings
		if ( isset( Sensei()->settings->settings['course_archive_amount'] ) && ( 0 < absint( Sensei()->settings->settings['course_archive_amount'] ) ) ) {
			$amount = absint( Sensei()->settings->settings['course_archive_amount'] );
		} else {
			$amount = $query->get( 'posts_per_page' );
		}
		$query->set( 'posts_per_page', $amount );
	}
}
	add_filter( 'pre_get_posts', 'sensei_course_archive_filter', 10, 1 );

	/**
	 * sensei_complete_lesson_button description
	 * since 1.0.3
	 *
	 * @return html
	 */
function sensei_complete_lesson_button() {
	do_action( 'sensei_complete_lesson_button' );
}

	/**
	 * sensei_reset_lesson_button description
	 * since 1.0.3
	 *
	 * @return html
	 */
function sensei_reset_lesson_button() {
	do_action( 'sensei_reset_lesson_button' );
}

	/**
	 * Returns all of the modules and lessons in a course, in order.
	 *
	 * @since  1.9.20
	 * @param  string|bool $course_id Course ID
	 * @return array Course modules and lessons
	 */
function sensei_get_modules_and_lessons( $course_id ) {
	$lesson_ids          = array();
	$modules_and_lessons = array();
	$course_modules      = Sensei()->modules->get_course_modules( $course_id );

	// Add all modules and lessons for the current course to an array.
	if ( ! empty( $course_modules ) ) {
		foreach ( (array) $course_modules as $module ) {
			$module_lessons = Sensei()->modules->get_lessons( $course_id, $module->term_id );

			if ( count( $module_lessons ) === 0 ) {
				continue;
			}

			$modules_and_lessons[] = $module;

			foreach ( $module_lessons as $lesson_item ) {
				$modules_and_lessons[] = $lesson_item;
				$lesson_ids[]          = $lesson_item->ID;
			}
		}
	}

	// Append all lessons not associated with a particular module to the array.
	$other_lessons = sensei_get_other_lessons( $course_id, $lesson_ids );

	if ( count( $other_lessons ) > 0 ) {
		foreach ( $other_lessons as $other_lesson ) {
			$modules_and_lessons[] = $other_lesson;
		}
	}

	return $modules_and_lessons;
}

	/**
	 * Returns the lessons in a course that are not associated with a module.
	 *
	 * @since  1.9.20
	 * @param  string|bool $course_id Course ID
	 * @param  array       $lesson_ids Lesson IDs to exclude
	 * @return array Other lessons not part of a module
	 */
function sensei_get_other_lessons( $course_id, $lesson_ids ) {
	$args = array(
		'post_type'        => 'lesson',
		'posts_per_page'   => -1,
		'suppress_filters' => 0,
		'meta_key'         => '_order_' . $course_id,
		'orderby'          => 'meta_value_num date',
		'order'            => 'ASC',
		'meta_query'       => array(
			array(
				'key'   => '_lesson_course',
				'value' => intval( $course_id ),
			),
		),
		'post__not_in'     => $lesson_ids,
	);

	return get_posts( $args );
}

	/**
	 * Returns the URL for a navigation link.
	 *
	 * @since  1.9.20
	 * @param  string|bool     $course_id Course ID
	 * @param  WP_Post|WP_Term $item      WP_Post (lesson/quiz) or WP_Term (module)
	 * @return string URL or empty string
	 */
function sensei_get_navigation_url( $course_id, $item ) {
	if ( ! $item || empty( $course_id ) ) {
		return '';
	}

	if ( $item->term_id ) { // Module
		return add_query_arg(
			'course_id',
			intval( $course_id ),
			get_term_link( $item, Sensei()->modules->taxonomy )
		);
	} else {    // Lesson
		return get_permalink( $item->ID );
	}
}

	/**
	 * Returns the text for a navigation link.
	 *
	 * @since  1.9.20
	 * @param  WP_Post|WP_Term $item WP_Post for a lesson/quiz or WP_Term for a module
	 * @return string Link text or empty string
	 */
function sensei_get_navigation_link_text( $item ) {
	if ( ! $item ) {
		return '';
	}

	if ( $item->term_id ) { // Module
		return $item->name;
	} else {    // Lesson
		return $item->post_title;
	}
}

	/**
	 * Returns navigation links for the modules and lessons in a course.
	 *
	 * @since  1.0.9
	 * @param  integer $lesson_id Lesson ID.
	 * @return array Multi-dimensional array of previous and next links.
	 */
function sensei_get_prev_next_lessons( $lesson_id = 0 ) {
	// For modules, $lesson_id is the first lesson in the module.
	$links               = array();
	$course_id           = Sensei()->lesson->get_course_id( $lesson_id );
	$modules_and_lessons = sensei_get_modules_and_lessons( $course_id );

	if ( is_array( $modules_and_lessons ) && count( $modules_and_lessons ) > 0 ) {
		$found = false;

		foreach ( $modules_and_lessons as $item ) {
			$item_is_linkable = true;

			if ( $item instanceof WP_Term
				 && 'module' === $item->taxonomy
				 && ! Sensei()->modules->do_link_to_module( $item, true )
			) {
				$item_is_linkable = false;
			}

			if ( $found && $item_is_linkable ) {
				$next = $item;
				break;
			}

			if (
				// Is it the current module?
				( isset( $item->term_id ) && is_tax( Sensei()->modules->taxonomy, $item->term_id ) )

				// Is it the current lesson?
				|| ( isset( $item->ID ) && absint( $item->ID ) === absint( $lesson_id ) )
			) {
				$found = true;
			} elseif ( $item_is_linkable ) {
				$previous = $item;
			}
		}
	}

	if ( isset( $previous ) ) {
		$links['previous'] = array(
			'url'  => sensei_get_navigation_url( $course_id, $previous ),
			'name' => sensei_get_navigation_link_text( $previous ),
		);
	}

	if ( isset( $next ) ) {
		$links['next'] = array(
			'url'  => sensei_get_navigation_url( $course_id, $next ),
			'name' => sensei_get_navigation_link_text( $next ),
		);
	}

	return $links;
}

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

	return Sensei_Lesson::is_prerequisite_complete( $current_lesson_id, $user_id );

}

/*******************************
 *
 * Module specific template tags
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
 */
function sensei_have_modules() {

	global $sensei_modules_loop;

	// check the current item compared to the total number of modules
	if ( $sensei_modules_loop['current'] + 1 > $sensei_modules_loop['total'] ) {

		return false;

	} else {

		return true;

	}

} //sensei_have_modules


/**
 * Setup the next module int the module loop
 *
 * @since 1.9.0
 */
function sensei_setup_module() {

	global  $sensei_modules_loop, $wp_query;

	// increment the index
	$sensei_modules_loop['current']++;
	$index = $sensei_modules_loop['current'];
	if ( isset( $sensei_modules_loop['modules'][ $index ] ) ) {

		$sensei_modules_loop['current_module'] = $sensei_modules_loop['modules'][ $index ];
		// setup the query for the module lessons
		$course_id      = $sensei_modules_loop['course_id'];
		$module_term_id = $sensei_modules_loop['current_module']->term_id;
		$modules_query  = Sensei()->modules->get_lessons_query( $course_id, $module_term_id );

		// setup the global wp-query only if the lessons
		if ( $modules_query->have_posts() ) {

			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Use modules query for modules loop. Reset in `Sensei_Core_Modules::teardown_single_course_module_loop()`
			$wp_query = $modules_query;

		} else {

			wp_reset_query();

		}
	} else {

		wp_reset_query();

	}

}

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
function sensei_module_has_lessons() {

	global $wp_query, $sensei_modules_loop;

	if ( 'lesson' == $wp_query->get( 'post_type' ) ) {

		return have_posts();

	} else {

		// if the loop has not been initiated check the first module has lessons
		if ( -1 == $sensei_modules_loop['current'] ) {

			$index = 0;

			if ( isset( $sensei_modules_loop['modules'][ $index ] ) ) {
				// setup the query for the module lessons
				$course_id = $sensei_modules_loop['course_id'];

				$module_term_id = $sensei_modules_loop['modules'][ $index ]->term_id;
				$modules_query  = Sensei()->modules->get_lessons_query( $course_id, $module_term_id );

				// setup the global wp-query only if the lessons
				if ( $modules_query->have_posts() ) {

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
function sensei_the_module_title_attribute() {

	echo esc_attr( sensei_get_the_module_title() );

}

/**
 * Returns a permalink to the module currently loaded within the Single Course module loop.
 *
 * This function should only be used with the Sensei modules loop.
 *
 * @return string
 */
function sensei_the_module_permalink() {

	global $sensei_modules_loop;
	$course_id      = $sensei_modules_loop['course_id'];
	$module_url     = add_query_arg( 'course_id', $course_id, get_term_link( $sensei_modules_loop['current_module'], 'module' ) );
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
	 echo esc_url_raw( apply_filters( 'sensei_the_module_permalink', $module_url, $module_term_id, $course_id ) );

}

/**
 * Returns the current module name. This must be used
 * within the Sensei module loop.
 *
 * @since 1.9.0
 *
 * @return string
 */
function sensei_get_the_module_title() {

	global $sensei_modules_loop;

	$module_title   = $sensei_modules_loop['current_module']->name;
	$module_term_id = $sensei_modules_loop['current_module']->term_id;
	$course_id      = $sensei_modules_loop['course_id'];

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
	return apply_filters( 'sensei_the_module_title', $module_title, $module_term_id, $course_id );

}

/**
 * Ouputs the current module name. This must be used
 * within the Sensei module loop.
 *
 * @since 1.9.0
 * @uses sensei_get_the_module_title
 * @return string
 */
function sensei_the_module_title() {

	echo esc_html( sensei_get_the_module_title() );

}

/**
 * Give the current user's lesson progress status
 * Used in the module loop on the courses page
 *
 * @since 1.9.0
 * @return string
 */
function sensei_get_the_module_status() {

	if ( ! is_user_logged_in() ) {
		return '';
	}

	global $sensei_modules_loop;

	$module_term_id  = $sensei_modules_loop['current_module']->term_id;
	$course_id       = $sensei_modules_loop['course_id'];
	$module_progress = Sensei()->modules->get_user_module_progress( $module_term_id, $course_id, get_current_user_id() );

	$module_status = '';
	$status_class  = '';
	if ( $module_progress && $module_progress > 0 ) {

		$module_status = __( 'Completed', 'sensei-lms' );
		$status_class  = 'completed';

		if ( $module_progress < 100 ) {

			$module_status = __( 'In progress', 'sensei-lms' );
			$status_class  = 'in-progress';

		}
	}

	if ( empty( $module_status ) ) {
		return '';
	}

	$module_status_html = '<span class="status module-status ' . esc_attr( $status_class ) . '">'
							. esc_html( $module_status )
							. '</span>';

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
	return apply_filters( 'sensei_the_module_status_html', $module_status_html, $module_term_id, $course_id );

}

/**
 * Print out the current module status
 *
 * @since 1.9.0
 */
function sensei_the_module_status() {

	echo wp_kses_post( sensei_get_the_module_status() );

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
 *
 * @since 1.9.7
 */
function sensei_the_module_id() {

	echo esc_html( sensei_get_the_module_id() );

}

/**
 * Gets a count of the lessons in the current module in the modules loop.
 *
 * @since 3.1.0
 *
 * @return int Number of lessons in the current module.
 */
function sensei_module_lesson_count() {
	global $sensei_modules_loop;

	if ( ! isset( $sensei_modules_loop['course_id'] ) || ! isset( $sensei_modules_loop['current_module'] ) || ! isset( $sensei_modules_loop['current_module']->term_id ) ) {
		return 0;
	}

	$course_id      = $sensei_modules_loop['course_id'];
	$module_term_id = $sensei_modules_loop['current_module']->term_id;

	return count( Sensei()->modules->get_lessons( $course_id, $module_term_id ) );
}

/************************
 *
 * Single Quiz Functions
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
function sensei_quiz_has_questions() {

	global $sensei_question_loop;

	if ( ! isset( $sensei_question_loop['total'] ) ) {
		return false;
	}

	return $sensei_question_loop['current'] + 1 < $sensei_question_loop['total'];
}

/**
 * This funciton must only be run inside the quiz question loop.
 *
 * It will setup the next question in the loop into the current spot within the loop for further
 * execution.
 *
 * @since 1.9.0
 */
function sensei_setup_the_question() {

	global $sensei_question_loop;

	$sensei_question_loop['current']++;
	$index                                    = $sensei_question_loop['current'];
	$sensei_question_loop['current_question'] = $sensei_question_loop['questions'][ $index ];

}

/**
 * This function must only be run inside the quiz question loop.
 *
 * This function gets the type and loads the template that will handle it.
 */
function sensei_the_question_content() {

	global $sensei_question_loop;

	$question_type = Sensei()->question->get_question_type( $sensei_question_loop['current_question']->ID );

	// load the template that displays the question information.
	Sensei_Question::load_question_template( $question_type );

}

/**
 * Outputs the question class. This must only be run withing the single quiz question loop.
 *
 * @since 1.9.0
 */
function sensei_the_question_class() {

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
	foreach ( $classes as $class ) {

		$html_classes .= $class . ' ';

	}

	echo esc_attr( trim( $html_classes ) );

}

/**
 * Output the ID of the current question within the quiz question loop.
 *
 * @since 1.9.0
 */
function sensei_get_the_question_id() {

	global $sensei_question_loop;
	if ( isset( $sensei_question_loop['current_question']->ID ) ) {

		return $sensei_question_loop['current_question']->ID;

	}

}

/************************
 *
 * Single Lesson Functions
 ***********************/

/**
 * Template function to determine if the current user can
 * access the current lesson content being viewed.
 *
 * This function checks in the following order
 * - if the current user has all access based on their permissions
 * - If the access permission setting is enabled for this site, if not the user has access
 * - if the lesson has a pre-requisite and if the user has completed that
 * - If it is a preview the user has access as well
 *
 * @since 1.9.0
 *
 * @param int $lesson_id Lesson post ID. Default: Use global post in loop.
 * @param int $user_id   User ID. Default: Use currently logged in user ID.
 * @return bool
 */
function sensei_can_user_view_lesson( $lesson_id = null, $user_id = null ) {
	if ( empty( $lesson_id ) ) {
		$lesson_id = get_the_ID();
	}

	$context = 'lesson';
	if ( 'quiz' === get_post_type( get_the_ID() ) ) {
		$context   = 'quiz';
		$lesson_id = Sensei()->quiz->get_lesson_id( get_the_ID() );
	}

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$user_can_view_course_content = false;
	$course_id                    = Sensei()->lesson->get_course_id( $lesson_id );
	if ( $course_id ) {
		$user_can_view_course_content = Sensei()->course->can_access_course_content( $course_id, $user_id, $context );
	}

	// Check for prerequisite lesson completions.
	$pre_requisite_complete = Sensei_Lesson::is_prerequisite_complete( $lesson_id, $user_id );
	$is_preview_lesson      = false;

	if ( Sensei_Utils::is_preview_lesson( $lesson_id ) ) {
		$is_preview_lesson      = true;
		$pre_requisite_complete = true;
	};

	$can_user_view_lesson = ! sensei_is_login_required()
							|| sensei_all_access( $user_id )
							|| ( $user_can_view_course_content && $pre_requisite_complete )
							|| $is_preview_lesson;

	/**
	 * Filter if the user can view lesson and quiz content.
	 *
	 * @since 1.9.0
	 *
	 * @param bool $can_user_view_lesson True if they can view lesson/quiz content.
	 * @param int  $lesson_id            Lesson post ID.
	 * @param int  $user_id              User ID.
	 */
	return apply_filters( 'sensei_can_user_view_lesson', $can_user_view_lesson, $lesson_id, $user_id );
}

/**
 * Ouput the single lesson meta
 *
 * The function should only be called on the single lesson
 */
function sensei_the_single_lesson_meta() {

	// if the lesson meta is included within theme load that instead of the function content
	$template = Sensei_Templates::locate_template( 'single-lesson/lesson-meta.php' );
	if ( ! empty( $template ) ) {

		Sensei_Templates::get_template( 'single-lesson/lesson-meta.php' );
		return;

	}

	// Get the meta info
	$lesson_course_id = absint( get_post_meta( get_the_ID(), '_lesson_course', true ) );
	$is_preview       = Sensei_Utils::is_preview_lesson( get_the_ID() );

	// Complete Lesson Logic
	do_action( 'sensei_complete_lesson' );
	// Check that the course has been started
	if ( Sensei()->access_settings()
		|| Sensei_Course::is_user_enrolled( $lesson_course_id )
		|| $is_preview ) {
		?>
		<section class="lesson-meta">
			<?php
			if ( apply_filters( 'sensei_video_position', 'top', get_the_ID() ) == 'bottom' ) {

				do_action( 'sensei_lesson_video', get_the_ID() );

			}
			?>
			<?php do_action( 'sensei_frontend_messages' ); ?>

		</section>

		<?php do_action( 'sensei_lesson_back_link', $lesson_course_id ); ?>

		<?php
	}

	do_action( 'sensei_lesson_meta_extra', get_the_ID() );

}

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
function get_sensei_header() {

	/**
	 * Allow user to stop the output of
	 * get_sensei_header which also includes a call to get_header.
	 *
	 * @since 1.9.5 introduced
	 */
	if ( ! apply_filters( 'sensei_show_main_header', true ) ) {
		return;
	}

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	get_header();

	/**
	 * sensei_before_main_content hook
	 *
	 * @hooked sensei_output_content_wrapper - 10 (outputs opening divs for the content)
	 */
	do_action( 'sensei_before_main_content' );

}

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
function get_sensei_footer() {

	/**
	 * Allow user to stop the output of
	 * get_sensei_footer which also includes a call to get_header.
	 *
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

}

/**
 * Output the permissions message
 * title.
 *
 * @since 1.9.0
 */
function the_no_permissions_title() {

	/**
	 * Filter the no permissions title just before it is echo'd on the
	 * no-permissions.php file.
	 *
	 * @since 1.9.0
	 * @param $no_permissions_title
	 */
	echo wp_kses_post( apply_filters( 'sensei_the_no_permissions_title', Sensei()->permissions_message['title'] ) );

}

/**
 * Output the permissions message.
 *
 * @since 1.9.0
 */
function the_no_permissions_message( $post_id ) {

	/**
	 * Filter the no permissions message just before it is echo'd on the
	 * no-permissions.php file.
	 *
	 * @since 1.9.0
	 * @param $no_permissions_message
	 */
	echo wp_kses_post( apply_filters( 'sensei_the_no_permissions_message', Sensei()->permissions_message['message'], $post_id ) );
}

/**
 * Output the sensei excerpt
 *
 * @since 1.9.0
 * @deprecated 3.2.0
 */
function sensei_the_excerpt( $post_id ) {

	_deprecated_function( __FUNCTION__, '3.2.0' );

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
function sensei_get_current_page_url() {

	global $wp;
	$current_page_url = home_url( $wp->request );
	return $current_page_url;

}

/**
 * Outputs the content for the my courses page
 *
 * @since 1.9.0
 */
function sensei_the_my_courses_content() {

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in called method.
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
function sensei_load_template( $template_name ) {

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
function sensei_load_template_part( $slug, $name ) {

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

	if ( empty( $lesson_id ) ) {

		$lesson_id = get_the_ID();

	}

	if ( 'lesson' != get_post_type( $lesson_id ) ) {
		return;
	}

	echo wp_kses_post( Sensei_Lesson::lesson_excerpt( get_post( $lesson_id ), false ) );

}

/**
 * The the course result lessons template
 *
 * @since 1.9.0
 */
function sensei_the_course_results_lessons() {
	// load backwards compatible template name if it exists in the users theme
	$located_template = locate_template( Sensei()->template_url . 'course-results/course-lessons.php' );
	if ( $located_template ) {

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
function sensei_courses_per_row() {

	echo esc_html( Sensei_Course::get_loop_number_of_columns() );

}

/**
 * Wrapper function for Sensei_Templates::get_template( $template_name, $args, $path )
 *
 * @since 1.9.0
 * @param $template_name
 * @param $args
 * @param $path
 */
function sensei_get_template( $template_name, $args, $path ) {

	Sensei_Templates::get_template( $template_name, $args, $path );

}

/**
 * Returns the lesson status class
 *
 * must be used in the loop.
 *
 * @since 1.9.0
 *
 * @return string $status_class
 */
function get_the_lesson_status_class() {

	$status_class     = '';
	$lesson_completed = Sensei_Utils::user_completed_lesson( get_the_ID(), get_current_user_id() );

	if ( $lesson_completed ) {
		$status_class = 'completed';
	}

	return $status_class;

}
/**
 * Outputs the lesson status class
 *
 * must be used in the lesson loop
 *
 * @since 1.9.0
 */
function sensei_the_lesson_status_class() {

	echo esc_html( get_the_lesson_status_class() );
}

/**
 * Get the module description.
 * This is to be used within the Sensei module loop.
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
function sensei_the_module_description() {
	echo esc_html( sensei_get_the_module_description() );
}
