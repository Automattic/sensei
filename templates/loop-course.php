<?php
/**
 * The Template for outputting Lists of any Sensei content type.
 *
 * This template expects the global wp_query to setup and ready for the loop
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.9.0
 */
if ( ! defined( 'ABSPATH' ) ){ exit; }

global $wp_query;
?>

<?php
/**
 * sensei_loop_course_before
 *
 * This runs before the post type items in the loop.php template. It runs
 * only for the specified post type
 *
 * @since 1.9
 * @param WP_Query
 */
do_action( 'sensei_loop_course_before', $wp_query );
?>
<ul class="course-container columns-<?php echo  WooThemes_Sensei_Course::get_course_loop_number_of_columns(); ?>" >

    <?php
    /**
     * sensei_loop_course_inside_before
     *
     * This runs before the post type items in the loop.php template. It runs
     * only for the specified post type
     *
     * @since 1.9
     * @param WP_Query
     */
    do_action( 'sensei_loop_course_inside_before', $wp_query );
    ?>


    <?php
    /*
     * Loop through all posts
     */
    while ( have_posts() ) { the_post();

        Sensei_Templates::get_part('content','course');

    }
    ?>

    <?php
    /**
     * sensei_loop_course_inside_after
     *
     * This runs after the post type items in the loop.php template. It runs
     * only for the specified post type
     *
     * @since 1.9
     * @param WP_Query
     */
    do_action( 'sensei_loop_course_inside_after', $wp_query );
    ?>

</ul>

<?php
/**
 * sensei_loop_course_after
 *
 * This runs after the post type items in the loop.php template. It runs
 * only for the specified post type
 *
 * @since 1.9
 * @param WP_Query
 */
do_action( 'sensei_loop_course_after', $wp_query );
?>
