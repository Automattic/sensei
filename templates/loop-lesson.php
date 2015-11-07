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
?>

<?php
/**
 * sensei_loop_lesson_before
 *
 * This runs before the post type items in the loop-lesson.php template.
 *
 * @since 1.9
 */
do_action( 'sensei_loop_lesson_before' );
?>

<section class="lesson-container columns-<?php sensei_lessons_per_row(); ?>" >

    <?php
    /**
     * sensei_loop_lesson_inside_before
     *
     * This runs before the lesson items in the loop-lesson.php template.
     *
     * @since 1.9
     */
    do_action( 'sensei_loop_lesson_inside_before' );
    ?>


    <?php
    /*
     * Loop through all lessons
     */
    while ( have_posts() ) { the_post();

        sensei_load_template_part( 'content', 'lesson' );

    }
    ?>

    <?php
    /**
     * sensei_loop_lesson_inside_after
     *
     * This runs inside the <ul> after the lesson items in the loop-lesson.php template.
     *
     * @since 1.9
     */
    do_action( 'sensei_loop_lesson_inside_after' );
    ?>

</section>

<?php
/**
 * sensei_loop_lesson_after
 *
 * This runs after the lesson items <ul> in the loop-lesson.php template.
 *
 * @since 1.9
 */
do_action( 'sensei_loop_lesson_after' );
