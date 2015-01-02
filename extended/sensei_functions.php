<?php
// This file contains material which is the pre-existing property of Psycle Interactive Limited.
// Copyright (c) 2014 Psycle Interactive. All rights reserved.

// Contains Functions specifically for Sensei 

/**
 * Check and verify if the current Quiz needs a password.
 * Very similar to WP post_password_required()
 * 
 * @param type $quiz_id
 * @return boolean
 */
function imperial_sensei_quiz_password_required( $post = null ) {
	$post = get_post( $post );

	$quiz_password = trim( get_post_meta( $post->ID, '_quiz_password', true ) );
	if ( empty( $quiz_password ) ) {
		return false;
	}
	if ( ! isset( $_COOKIE['wp-quizpass_' . COOKIEHASH] ) ) {
		return true;
	}
	require_once ABSPATH . 'wp-includes/class-phpass.php';
	$hasher = new PasswordHash( 8, true );

	$hash = wp_unslash( $_COOKIE[ 'wp-quizpass_' . COOKIEHASH ] );
	if ( 0 !== strpos( $hash, '$P$B' ) ) {
		return true;
	}
	return ! $hasher->CheckPassword( $quiz_password, $hash );
}

/**
 * Retrieve protected quiz password form content.
 *
 * @uses apply_filters() Calls 'sensei_the_quiz_password_form' filter on output.
 * @param int|WP_Post $post Optional. A post ID or post object.
 * @return string HTML content for password form for password protected post.
 */
function imperial_sensei_get_the_password_form( $post = 0 ) {
	$post = get_post( $post );
	$label = 'pwbox-' . ( empty($post->ID) ? rand() : $post->ID );
	$output  = '<form action="' . esc_url( get_the_permalink( $post->ID ) ) . '" class="post-password-form" method="post">';
	$output .= wp_nonce_field( 'quiz-password-'.$post->ID, '_wpnonce', true, false );
	if ( isset($_GET['message']) && $_GET['message'] ) {
		$output .= '<div class="sensei-message alert">' . __('Invalid Password', 'imperial') .'</div>';
	}
	$output .= '<p>' . __( 'This quiz is password protected. To view it please enter the password below:', 'imperial' ) . '</p>
	<p><label for="' . $label . '">' . __( 'Password:' ) . ' <input name="quiz_password" id="' . $label . '" type="password" size="20" /></label> <input type="submit" name="sensei_quiz_password_form" value="' . esc_attr__( 'Submit' ) . '" /></p></form>
	';

	return apply_filters( 'sensei_the_quiz_password_form', $output );
}

/**
 * Display help text for when an admin user is on the Sensei page
 * 
 * @param string $contextual_help
 * @param type $screen_id
 * @param type $screen
 * @return string
 */
function imperial_sensei_help_text( $contextual_help, $screen_id, $screen ) { 

	// The add_help_tab function for screen was introduced in WordPress 3.3. Add it only to the following pages.
	if( ! method_exists($screen, 'add_help_tab') || ! in_array($screen_id, array('question')) ) {
		return $contextual_help;
	}

	switch($screen_id):
		//Add help for Question editing / creating page
		case 'question':
			$screen->add_help_tab( array(
				'id' => 'gap-fill',
				'title' => __('Gap Fill'),
				'content' =>
					'<h3>' . __('Regular Expressions', 'imperial') . '</h3>' .
					'<p>' . __("The Gap within Gap Fill questions can contain a regular expression to validate the answer that students submit. <br>An overview reference to regular expressions is available at <a href='http://webcheatsheet.com/php/regular_expressions.php' target='_blank'>http://webcheatsheet.com/php/regular_expressions.php</a>.",'imperial'). '</p>' .
					'<p>' . __('Examples:','imperial'). '</p><ol>' .
					'<li>"(alice|bob|charlie)" - ' . __("This will effectively check that the answer is either 'alice', 'bob' or 'charlie'. <a href='#fn-1'>[1]</a>",'imperial'). '</li>' .
					'<li>"c[o]+kies" - ' . __("This will effectively check that the answer is 'cookies' with any number of 'o's as long as there is at least 1.",'imperial'). '</li>' .
					'<li>"0?\.[5-8][0-9]*%?" - ' . __("This will effectively check that the answer is >=0.5 and <0.9 to any number of decimal places with the 0 at the front and the percentage at the end both optional. Valid answers include '.51234', '0.6' and '.81%",'imperial'). '</li>' .
					'</ol>' .
					'<p id="fn-1">' . __("[1] All expressions are case insensitive, a student typing 'BOB' is the same as 'bob'.",'imperial'). '</p>' 
			));
			break;
	endswitch;

	return $contextual_help;
}
add_action( 'contextual_help', 'imperial_sensei_help_text', 10, 3 );


/**
 * Replicate the function WooThemes_Sensei_Course::load_user_courses_content() but with additions for Imperial
 * 
 * @global type $woothemes_sensei
 * @global type $post
 * @global type $course
 * @global boolean $my_courses_page
 * @global type $my_courses_section
 * @param type $user
 * @param type $manage
 * @return type
 */
function imperial_sensei_load_user_courses_content( $user = false, $manage = false ) {
	global $woothemes_sensei, $post, $course, $my_courses_page, $my_courses_section;

	$imp = imperial();
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
		$completed_ids = $active_ids = array();
		foreach( $course_statuses as $course_status ) {
			if ( WooThemes_Sensei_Utils::user_completed_course( $course_status->comment_post_ID, $user->ID ) ) {
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
			$course_lessons = $woothemes_sensei->frontend->course->course_lessons( $course_item->ID );
			$user_course_status = WooThemes_Sensei_Utils::user_course_status( $course_item->ID, $user->ID );
			$lessons_completed = get_comment_meta( $user_course_status->comment_ID, 'complete', true );
			$progress_percentage = get_comment_meta( $user_course_status->comment_ID, 'percent', true );

//			foreach ( $course_lessons as $lesson ) {
//				if ( WooThemes_Sensei_Utils::user_completed_lesson( $lesson->ID, $user->ID ) ) {
//					++$lessons_completed;
//				}
//			}

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
				$active_html .= '<span class="course-author"><a href="' . esc_url( get_author_posts_url( absint( $course_item->post_author ) ) ) . '" title="' . esc_attr( $user_info->display_name ) . '">' . __( 'by ', 'woothemes-sensei' ) . esc_html( $user_info->display_name ) . '</a></span>';
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
			$active_html .= '<span class="course-lesson-progress">' . sprintf( __( '<span class="course-progress-highlight">%1$d of %2$d</span> lessons completed', 'woothemes-sensei' ) , $lessons_completed, $lesson_count  ) . '</span>';

			$active_html .= '</p>';

			$active_html .= '<p class="course-excerpt">' . apply_filters( 'get_the_excerpt', $course_item->post_excerpt ) . '</p>';

			$active_html .= '<p class="start-end-dates">';
			$active_html .= '<span>' . __('Start Date:', 'imperial') . ' <strong>' . date('d/m/Y', get_post_meta( $course_item->ID, 'course_start_date', true ) ) . '</strong></span>';
			$active_html .= '<span>' . __('End Date:', 'imperial') . ' <strong>' . date('d/m/Y', get_post_meta( $course_item->ID, 'course_end_date', true ) ) . '</strong></span>';
			$active_html .= '</p>';
			$leaders = $imp->get_course_leaders( $course_item );
			if( !empty($leaders) ) {
				$active_html .= '<p class="course-leader"><span class="title">' . _n('Course Leader:', 'Course Leaders:', count($leaders), 'imperial') . '</span> ';
				$active_html .= '<span class="leaders">';
				$i = 0;
				foreach ( $leaders as $leader ) {
					$active_html .= (( $i++ ) ? ', ' : '');
					$active_html .= esc_html__( $leader->display_name );
				}
				$active_html .= '</span></p>';
			} // leaders

//			$progress_percentage = abs( round( ( doubleval( $lessons_completed ) * 100 ) / ( $lesson_count ), 0 ) );

			if ( 50 < $progress_percentage ) { $class = ' green'; } elseif ( 25 <= $progress_percentage && 50 >= $progress_percentage ) { $class = ' orange'; } else { $class = ' red'; }

			/* if ( 0 == $progress_percentage ) { $progress_percentage = 5; } */

			$active_html .= '<div class="meter' . esc_attr( $class ) . '"><span style="width: ' . $progress_percentage . '%">' . $progress_percentage . '%</span></div>';

			$active_html .= '</section>';

//			if( $manage ) {
//
//				$active_html .= '<section class="entry-actions">';
//
//					$active_html .= '<form method="POST" action="' . remove_query_arg( array( 'active_page', 'completed_page' ) ) . '">';
//
//						$active_html .= '<input type="hidden" name="' . esc_attr( 'woothemes_sensei_complete_course_noonce' ) . '" id="' . esc_attr( 'woothemes_sensei_complete_course_noonce' ) . '" value="' . esc_attr( wp_create_nonce( 'woothemes_sensei_complete_course_noonce' ) ) . '" />';
//
//						$active_html .= '<input type="hidden" name="course_complete_id" id="course-complete-id" value="' . esc_attr( absint( $course_item->ID ) ) . '" />';
//
//						if ( 0 < absint( count( $course_lessons ) ) && $woothemes_sensei->settings->settings['course_completion'] == 'complete' ) {
//							$active_html .= '<span><input name="course_complete" type="submit" class="course-complete" value="' . apply_filters( 'sensei_mark_as_complete_text', __( 'Mark as Complete', 'woothemes-sensei' ) ) . '"/></span>';
//						} // End If Statement
//
//						$course_purchased = false;
//						if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() ) {
//							// Get the product ID
//							$wc_post_id = get_post_meta( absint( $course_item->ID ), '_course_woocommerce_product', true );
//							if ( 0 < $wc_post_id ) {
//								$course_purchased = WooThemes_Sensei_Utils::sensei_customer_bought_product( $user->user_email, $user->ID, $wc_post_id );
//							} // End If Statement
//						} // End If Statement
//
//						if ( !$course_purchased ) {
//							$active_html .= '<span><input name="course_complete" type="submit" class="course-delete" value="' . apply_filters( 'sensei_delete_course_text', __( 'Delete Course', 'woothemes-sensei' ) ) . '"/></span>';
//						} // End If Statement
//
//					$active_html .= '</form>';
//
//				$active_html .= '</section>';
//			}

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

			$complete_html .= '<p class="start-end-dates">';
			$complete_html .= '<span>' . __('Start Date:', 'imperial') . ' <strong>' . date('d/m/Y', get_post_meta( $course_item->ID, 'course_start_date', true ) ) . '</strong></span>';
			$complete_html .= '<span>' . __('End Date:', 'imperial') . ' <strong>' . date('d/m/Y', get_post_meta( $course_item->ID, 'course_end_date', true ) ) . '</strong></span>';
			$complete_html .= '</p>';
			$leaders = $imp->get_course_leaders( $course_item );
			if( !empty($leaders) ) {
				$complete_html .= '<p class="course-leader"><span class="title">' . _n('Course Leader:', 'Course Leaders:', count($leaders), 'imperial') . '</span> ';
				$complete_html .= '<span class="leaders">';
				$i = 0;
				foreach ( $leaders as $leader ) {
					$complete_html .= (( $i++ ) ? ', ' : '');
					$complete_html .= esc_html__( $leader->display_name );
				}
				$complete_html .= '</span></p>';
			} // leaders

			$complete_html .= '<div class="meter green"><span style="width: 100%">100%</span></div>';

			if( $manage ) {
				$has_quizzes = count( $woothemes_sensei->frontend->course->course_quizzes( $course_item->ID ) ) > 0 ? true : false;
				// Output only if there is content to display
				if ( has_filter( 'sensei_results_links' ) || false != $has_quizzes ) {
					$complete_html .= '<p class="sensei-results-links">';
					$results_link = '';
					if( false != $has_quizzes ) {
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

	do_action( 'sensei_before_user_courses' ); 

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

	<?php 
	do_action( 'sensei_after_user_courses' );

	return ob_get_clean();
}