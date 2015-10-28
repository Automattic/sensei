<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Content-lesson.php template file
 *
 * responsible for content on archive like pages. Only shows the lesson excerpt.
 *
 * For single lesson content please see single-lesson.php
 *
 * @author 		Automattic
 * @package 	Sensei
 * @category    Templates
 * @version     1.9.0
 */
?>

<article <?php post_class( get_the_ID() ); ?> >

    <section class="lesson-content">

        <?php
        /**
         * sensei_content_lesson_before
         * action that runs before the sensei {post_type} content. It runs inside the sensei
         * content.php template. This applies to the specific post type that you've targeted.
         *
         * @since 1.9
         * @param string $post_id
         */
        do_action( 'sensei_content_lesson_before', get_the_ID() );
        ?>

        <section class="entry">

            <?php
            /**
             * sensei_content_lesson_inside_before
             *
             * Fires just before the post content in the content-lesson.php file.
             *
             * @since 1.9
             *
             * @param string $post_id
             */
            do_action('sensei_content_lesson_inside_before', get_the_ID());
            ?>

            <p class="lesson-excerpt">

                <?php Woothemes_Sensei_Lesson::the_lesson_excerpt( get_post() );  ?>

            </p>

            <?php
            /**
             * sensei_{$post_type}content_inside_before
             *
             * Fires just after the post content in the lesson-content.php file.
             *
             * @since 1.9
             *
             * @param string $post_id
             */
            do_action('sensei_content_lesson_inside_after', get_the_ID());
            ?>

        </section> <!-- section .entry -->

        <?php
        /**
         * sensei_content_lesson_after
         * action that runs after the sensei lesson content. It runs inside the sensei
         * lesson-content.php template.
         *
         * @since 1.9
         * @param string $post_id
         */
        do_action( 'sensei_content_lesson_after', get_the_ID() );
        ?>

    </section> <!-- article .lesson-content -->

</article> <!-- article .(<?php esc_attr_e( join( ' ', get_post_class( array( 'lesson', 'post' ) ) ) ); ?>  -->