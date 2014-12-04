<?php
/**
 * The Template for displaying all single lesson meta data.
 *
 * Override this template by copying it to yourtheme/sensei/single-lesson/lesson-meta.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $post, $woothemes_sensei, $current_user;

// Complete Lesson Logic
do_action( 'sensei_complete_lesson' );
?>
<section class="lesson-meta" id="lesson_complete">
    <?php do_action( 'sensei_single_lesson_meta' ); ?>
    <?php
    $lesson_course_id = get_post_meta( $post->ID , '_lesson_course', true);

    if ( $woothemes_sensei->access_settings() || sensei_has_user_started_course( $lesson_course_id, $current_user->ID ) || $is_preview ) {

        //possibly show the video
        if( apply_filters( 'sensei_video_position', 'top', $post->ID ) == 'bottom' ) {
         do_action( 'sensei_lesson_video', $post->ID );
        }

        do_action( 'sensei_frontend_messages' );

        do_action( 'sensei_breadcrumb', $lesson_course_id );


    } else {

        do_action( 'sensei_lesson_course_signup', $lesson_course_id );

    } // End If Statement
    ?>
</section>

<?php do_action( 'sensei_lesson_meta_extra', $post->ID ); ?>