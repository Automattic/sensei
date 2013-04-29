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
// Get the meta info
$lesson_course_id = absint( get_post_meta( $post->ID, '_lesson_course', true ) );
// Get User Meta
get_currentuserinfo();
// Complete Lesson Logic
do_action( 'sensei_complete_lesson' );
// Check that the course has been started
if ( $woothemes_sensei->access_settings() || sensei_has_user_started_course( $lesson_course_id, $current_user->ID ) ) { ?>
	<section class="lesson-meta">
        <?php do_action( 'sensei_lesson_video', $post->ID ); ?>
        <?php do_action( 'sensei_frontend_messages' ); ?>
        <?php do_action( 'sensei_lesson_quiz_meta', $post->ID, $current_user->ID  ); ?>
    </section>
    <?php do_action( 'sensei_lesson_back_link', $lesson_course_id ); ?>
<?php } else {
	 do_action( 'sensei_lesson_course_signup', $lesson_course_id );
} // End If Statement ?>