<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Content-course.php template file
 *
 * responsible for content on archive like pages. Only shows the course excerpt.
 *
 * For single course content please see single-course.php
 *
 * @author 		Automattic
 * @package 	Sensei
 * @category    Templates
 * @version     1.9.0
 */
?>

<li <?php post_class(  WooThemes_Sensei_Course::get_course_loop_content_class() ); ?> >

    <section class="course-content">

        <?php
        /**
         * This action runs before the sensei course content. It runs inside the sensei
         * content-course.php template. This applies to the specific post type that you've targeted.
         *
         * @since 1.9
         *
         * @param $post
         *
         * @hooked Sensei()->course->course_image - 10
         * @hooked Sensei_Templates::the_title - 15
         */
        do_action( 'sensei_course_content_before', get_post() );
        ?>

        <section class="entry">

            <?php
            /**
             * Fires just before the post content in the content-course.php file.
             *
             * @since 1.9
             *
             * @param WP_Post $post
             *
             * @hooked  Sensei()->course->the_course_meta - 20
             */
            do_action('sensei_course_content_inside_before', get_post());
            ?>

            <p class="course-excerpt">

                <?php the_excerpt(); ?>

            </p>

            <?php
            /**
             * Fires just after the post content in the content-course.php file.
             *
             * @since 1.9
             *
             * @param WP_Post $post
             *
             * @hooked  Sensei()->course->the_course_free_lesson_preview - 20
             */
            do_action('sensei_course_content_inside_after', get_post());
            ?>

        </section> <!-- section .entry -->

    </section> <!-- section .course-content -->

</li> <!-- article .(<?php esc_attr_e( join( ' ', get_post_class( array( 'course', 'post' ) ) ) ); ?>  -->