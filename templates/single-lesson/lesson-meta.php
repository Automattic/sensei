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
$is_preview = WooThemes_Sensei_Utils::is_preview_lesson( $post->ID );
// Get User Meta
get_currentuserinfo();
// Complete Lesson Logic
do_action( 'sensei_complete_lesson' );
// Check that the course has been started
if ( $woothemes_sensei->access_settings() || WooThemes_Sensei_Utils::user_started_course( $lesson_course_id, $current_user->ID ) || $is_preview ) { ?>
	<section class="lesson-meta" id="lesson_complete">
		<?php
		if( apply_filters( 'sensei_video_position', 'top', $post->ID ) == 'bottom' ) {
			do_action( 'sensei_lesson_video', $post->ID );
		}
		?>
        <?php do_action( 'sensei_frontend_messages' ); ?>
        <?php if ( ! $is_preview || WooThemes_Sensei_Utils::user_started_course( $lesson_course_id, $current_user->ID ) ) {
        	do_action( 'sensei_lesson_quiz_meta', $post->ID, $current_user->ID  );
    	} ?>
    </section>
    <?php do_action( 'sensei_lesson_back_link', $lesson_course_id ); ?>
<?php } else {
	 do_action( 'sensei_lesson_course_signup', $lesson_course_id );
} // End If Statement
do_action( 'sensei_lesson_meta_extra', $post->ID );
?>