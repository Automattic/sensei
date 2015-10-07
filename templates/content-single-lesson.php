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

 global $woothemes_sensei, $post, $current_user, $view_lesson, $user_taking_course;
 // Content Access Permissions

 // If WC is active, check if the course is attached to a product.
    if(WooThemes_Sensei_Utils::sensei_is_woocommerce_activated()) {
        $wc_post_id = get_post_meta( $post->ID, '_course_woocommerce_product', true );
        $product = $woothemes_sensei->sensei_get_woocommerce_product_object( $wc_post_id );

        $is_product = isset ( $product ) && is_object ( $product );
    } else {
        $is_product = false;
    }

$access_permission = false;

 // If Settings > General > 'Users must be logged in to view Course and Lesson content' is turned off,
 // give logged out users full access.
  if ( isset( $woothemes_sensei->settings->settings['access_permission'] ) && ! $woothemes_sensei->settings->settings['access_permission']  || sensei_all_access() ) {

      $access_permission = true;

 }

?>
        	<article <?php post_class( array( 'lesson', 'post' ) ); ?>>

				<?php do_action( 'sensei_lesson_image', $post->ID ); ?>

                <?php do_action( 'sensei_lesson_single_title' ); ?>

                <?php

                $view_lesson = true;

                wp_get_current_user();

                $lesson_prerequisite = absint( get_post_meta( $post->ID, '_lesson_prerequisite', true ) );


				if ( $lesson_prerequisite > 0 ) {
					// Check for prerequisite lesson completions
					$view_lesson = WooThemes_Sensei_Utils::user_completed_lesson( $lesson_prerequisite, $current_user->ID );
				}

				$lesson_course_id = get_post_meta( $post->ID, '_lesson_course', true );
				$user_taking_course = WooThemes_Sensei_Utils::user_started_course( $lesson_course_id, $current_user->ID );

				if( current_user_can( 'administrator' ) ) {
					$view_lesson = true;
					$user_taking_course = true;
				}

				$is_preview = false;
				if( WooThemes_Sensei_Utils::is_preview_lesson( $post->ID ) ) {
					$is_preview = true;
					$view_lesson = true;
				};

				if( $view_lesson ) { ?>

					<section class="entry fix">
					<?php if ( $is_preview && !$user_taking_course ) { ?>
						<div class="sensei-message alert"><?php echo $woothemes_sensei->permissions_message['message']; ?></div>
					<?php } ?>

	                	<?php
	                	if ( ! $is_product && $access_permission || ( is_user_logged_in() && $user_taking_course ) || $is_preview ) {
	                		if( apply_filters( 'sensei_video_position', 'top', $post->ID ) == 'top' ) {
	                			do_action( 'sensei_lesson_video', $post->ID );
	                		}
	                		the_content();
	                	} else {
	                		echo '<p>' . sensei_get_excerpt( $post ) . '</p>';
	                	}
	            		?>
					</section>

					<?php if ( ! $is_product && $access_permission || ( is_user_logged_in() && $user_taking_course ) || $is_preview ) {
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