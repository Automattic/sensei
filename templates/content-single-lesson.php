<?php
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

 global $woothemes_sensei, $post, $current_user;
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

                $lesson_prerequisite = absint( get_post_meta( $post->ID, '_lesson_prerequisite', true ) );
                // Check for prerequisite lesson completions
				$user_prerequisite_lesson_end =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_prerequisite, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_end', 'field' => 'comment_content' ) );
				$user_lesson_prerequisite_complete = false;
				if ( '' != $user_prerequisite_lesson_end ) {
				    $user_lesson_prerequisite_complete = true;
				}

				if ( $lesson_prerequisite > 0 ) {
					$view_lesson = false;
                    if ( ( isset( $user_lesson_prerequisite_complete ) && $user_lesson_prerequisite_complete ) ) {
                    	$view_lesson = true;
                 	}
				}

				if( current_user_can( 'administrator' ) ) {
					$view_lesson = true;
				}

				if( $view_lesson ) {
					$lesson_course_id = get_post_meta( $post->ID, '_lesson_course', true );
					$user_taking_course = sensei_has_user_started_course( $lesson_course_id, $current_user->ID ); ?>
				<section class="entry fix">
                	<?php if ( $access_permission || ( is_user_logged_in() && $user_taking_course ) ) { the_content(); } else { echo '<p>' . $post->post_excerpt . '</p>'; } ?>
				</section>

				<?php if ( $access_permission || ( is_user_logged_in() && $user_taking_course ) ) {
					do_action( 'sensei_lesson_single_meta' );
				} else {
					do_action( 'sensei_lesson_course_signup', $lesson_course_id );
				} ?>

				<?php

				} else {
					if ( $lesson_prerequisite > 0 ) {
						echo sprintf( __( 'You must first complete %1$s before viewing this Lesson', 'woothemes-sensei' ), '<a href="' . esc_url( get_permalink( $lesson_prerequisite ) ) . '" title="' . esc_attr(  sprintf( __( 'You must first complete: %1$s', 'woothemes-sensei' ), get_the_title( $lesson_prerequisite ) ) ) . '">' . get_the_title( $lesson_prerequisite ). '</a>' );
					}
				}

				?>


            </article><!-- .post -->

            <?php do_action('sensei_pagination'); ?>