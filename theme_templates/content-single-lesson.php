<?php
// This file contains material which is the pre-existing property of Psycle Interactive Limited.
// Copyright (c) 2014 Psycle Interactive. All rights reserved.

/**
 * The template for displaying product content in the single-lessons.php template
 *
 * Override this template by copying it to yourtheme/sensei/content-single-lesson.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $woothemes_sensei, $post, $current_user, $view_lesson, $user_taking_course;

$date_format = 'j F Y \a\t H:i';
$timezone_now = current_time( 'timestamp' ); // Adjusts to timezone

// Content Access Permissions
$access_permission = false;
if ( ( isset( $woothemes_sensei->settings->settings['access_permission'] ) && ! $woothemes_sensei->settings->settings['access_permission'] ) || sensei_all_access() ) {
	$access_permission = true;
} // End If Statement
?>
	<article <?php post_class( array( 'lesson', 'post' ) ); ?>>

		<?php do_action( 'sensei_lesson_image', $post->ID ); ?>

		<?php do_action( 'sensei_lesson_single_title' ); ?>

		<?php

		$view_lesson = true;

		wp_get_current_user();

		$lesson_start_date = get_post_meta( $post->ID, '_lesson_start_date', true );
		$lesson_close_date = get_post_meta( $post->ID, '_lesson_close_date', true );
		// $lesson_block_completion is auto checked and correctly updated upon loading of a Lesson or Quiz
		$lesson_block_completion = ( 'on' == get_post_meta( $post->ID, '_lesson_block_completion', true ) ) ? true : false;

		// Check for prerequisite lesson completions
		$lesson_prerequisite = absint( get_post_meta( $post->ID, '_lesson_prerequisite', true ) );
		if ( $lesson_prerequisite > 0 ) {
			$user_prerequisite_lesson_status = WooThemes_Sensei_Utils::user_lesson_status( $lesson_prerequisite, $current_user->ID );
			$view_lesson = WooThemes_Sensei_Utils::user_completed_lesson( $user_prerequisite_lesson_status );
		}
		// Lesson hasn't opened yet, so block all content from showing
		if ( !empty($lesson_start_date) && $lesson_start_date > $timezone_now ) {
			$view_lesson = false;
		}

		$lesson_course_id = get_post_meta( $post->ID, '_lesson_course', true );
		$user_taking_course = WooThemes_Sensei_Utils::user_started_course( $lesson_course_id, $current_user->ID );

		if( current_user_can( 'administrator' ) || imperial()->is_staff( $current_user ) ) {
			$view_lesson = true;
			$user_taking_course = true;
		}

		$is_preview = false;
		if( !empty($lesson_start_date) && WooThemes_Sensei_Utils::is_preview_lesson( $post->ID ) ) {
			$is_preview = true;
			$view_lesson = true;
		};

		if( $view_lesson ) { ?>
		<section class="entry fix">
		<?php 
			// Warn users that the content has expired
			if ( empty($lesson_close_date) && $lesson_block_completion ){
				echo '<div class="sensei-message info">';
				_e( 'This activity has been closed. You can still view the content, but you will not be able to complete the activity/quiz.', 'imperial' );
				echo '</div>';
			}
			// Warn users that the content expired automatically
			elseif ( $lesson_close_date && $lesson_block_completion ) {
				echo '<div class="sensei-message info">';
				printf( __( 'This activity closed on %s. You can still view the content, but you will not be able to complete the activity/quiz.', 'imperial' ), date($date_format, $lesson_close_date) );
				echo '</div>';
			}
			// Warn users that the content will expire automatically
			elseif ( $lesson_close_date && !$lesson_block_completion ) {
				echo '<div class="sensei-message alert">';
				printf( __( 'This activity will close on %s. You will still be able to view the content after this time, but you will not be able to complete the activity/quiz.', 'imperial' ), date($date_format, $lesson_close_date) );
				echo '</div>';
			}

			if ( $is_preview && !$user_taking_course ) { ?>
			<div class="sensei-message alert"><?php echo $woothemes_sensei->permissions_message['message']; ?></div>
		<?php } ?>

			<?php
			if ( $access_permission || ( is_user_logged_in() && $user_taking_course ) || $is_preview ) {
				if( apply_filters( 'sensei_video_position', 'top', $post->ID ) == 'top' ) {
					do_action( 'sensei_lesson_video', $post->ID );
				}
				the_content();
				// These 3 lines were in the lesson-meta.php template
				if( apply_filters( 'sensei_video_position', 'top', $post->ID ) == 'bottom' ) {
					do_action( 'sensei_lesson_video', $post->ID );
				}
			} else {
				echo '<p>' . $post->post_excerpt . '</p>';
			}
			?>
		</section>

			<?php if ( $access_permission || ( is_user_logged_in() && $user_taking_course ) || $is_preview ) {
				do_action( 'sensei_lesson_single_meta' );
			} else {
				do_action( 'sensei_lesson_course_signup', $lesson_course_id );
			} 

		}
		// Not viewing Lesson
		else {

			// Lesson hasn't opened yet, so block all content from showing
			if ( !empty($lesson_start_date) && $lesson_block_completion ) {
				echo '<div class="sensei-message info">';
				printf( __( 'This activity will be accessible from %s', 'imperial' ), date($date_format, $lesson_start_date) );
				echo '</div>';
			}

			if ( $lesson_prerequisite > 0 ) {
				echo '<div class="sensei-message info">';
				printf( __( 'You must first complete %1$s before viewing this Lesson', 'woothemes-sensei' ), '<a href="' . esc_url( get_permalink( $lesson_prerequisite ) ) . '" title="' . esc_attr(  sprintf( __( 'You must first complete: %1$s', 'woothemes-sensei' ), get_the_title( $lesson_prerequisite ) ) ) . '">' . get_the_title( $lesson_prerequisite ). '</a>' );
				echo '</div>';
			}
		}

		?>


	</article><!-- .post -->

	<?php do_action('sensei_pagination'); ?>