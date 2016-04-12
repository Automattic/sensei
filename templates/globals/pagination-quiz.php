<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Pagination - Lesson
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.1.0
 */

global $post;
$quiz_lesson = absint( get_post_meta( $post->ID, '_quiz_lesson', true ) );
$nav_id_array = sensei_get_prev_next_lessons( $quiz_lesson );
$previous_lesson_id = absint( $nav_id_array['prev_lesson'] );
$next_lesson_id = absint( $nav_id_array['next_lesson'] );
// Output HTML
if ( ( 0 < $previous_lesson_id ) || ( 0 < $next_lesson_id ) ) { ?>
    <nav id="post-entries" class="post-entries fix">
        <?php if ( 0 < $previous_lesson_id ) { ?><div class="nav-prev fl"><a href="<?php echo esc_url( get_permalink( $previous_lesson_id ) ); ?>" rel="prev"><span class="meta-nav"></span> <?php echo get_the_title( $previous_lesson_id ); ?></a></div><?php } ?>
	    <?php if ( 0 < $next_lesson_id ) { ?><div class="nav-next fr"><a href="<?php echo esc_url( get_permalink( $next_lesson_id ) ); ?>" rel="prev"><?php echo get_the_title( $next_lesson_id ); ?> <span class="meta-nav"></span></a></div><?php } ?>
    </nav><!-- #post-entries -->
<?php } ?>