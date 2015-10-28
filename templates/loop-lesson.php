<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * The Template for outputting Lesson Archive items
 *
 * Override this template by copying it to yourtheme/sensei/loop-lesson.php
 *
 * @author 		Automattic
 * @package 	Sensei
 * @category    Templates
 * @version     1.9.0
 */

global $lesson_count, $wp_query;
$lesson_count= 0;
?>

<?php
/**
 * sensei_loop_lesson_before
 *
 * This runs before the post type items in the loop-lesson.php template.
 *
 * @since 1.9
 * @param WP_Query
 */
do_action( 'sensei_loop_lesson_before', $wp_query );
?>

<section class="lesson-container columns-<?php echo  WooThemes_Sensei_Lesson::get_loop_number_of_columns(); ?>" >

    <?php
    /**
     * sensei_loop_lesson_inside_before
     *
     * This runs before the lesson items in the loop-lesson.php template.
     *
     * @since 1.9
     * @param WP_Query
     */
    do_action( 'sensei_loop_lesson_inside_before', $wp_query );
    ?>


    <?php
    /*
     * Loop through all lessons
     */
    while ( have_posts() ) { the_post();

        $lesson_count++;

        Sensei_Templates::get_part('content','lesson');

    }
    ?>

    <?php
    /**
     * sensei_loop_lesson_inside_after
     *
     * This runs inside the <ul> after the lesson items in the loop-lesson.php template.
     *
     * @since 1.9
     * @param WP_Query
     */
    do_action( 'sensei_loop_lesson_inside_after', $wp_query );
    ?>

</section>

<?php
/**
 * sensei_loop_lesson_after
 *
 * This runs after the lesson items <ul> in the loop-lesson.php template.
 *
 * @since 1.9
 * @param WP_Query
 */
do_action( 'sensei_loop_lesson_after', $wp_query );
