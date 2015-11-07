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
 * This runs before the post type items in the loop-lesson.php template.
 *
 * @since 1.9
 */
do_action( 'sensei_loop_lesson_before' );
?>

<section class="lesson-container" >

    <?php
    /**
     * This runs before the lesson items in the loop-lesson.php template.
     *
     * @since 1.9.0
     *
     * @hooked Sensei()->lesson->lesson_tag_archive_description - 11
     * @hooked Sensei()->lesson->the_archive_header - 20
     */
    do_action( 'sensei_loop_lesson_inside_before' );
    ?>


    <?php
    //Loop through all lessons
    while ( have_posts() ) { the_post();

        sensei_load_template_part( 'content', 'lesson' );

    }
    ?>

    <?php
    /**
     * This runs inside the <ul> after the lesson items in the loop-lesson.php template.
     *
     * @since 1.9.0
     */
    do_action( 'sensei_loop_lesson_inside_after' );
    ?>

</section>

<?php
/**
 * This runs after the lesson items <ul> in the loop-lesson.php template.
 *
 * @since 1.9.0
 */
do_action( 'sensei_loop_lesson_after' );
