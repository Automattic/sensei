<?php
/**
 * Content-course.php template file
 *
 * responsible for content on archive like pages. Only shows the course excerpt
 *
 * @package Sensei
 * @category Templates
 * @since 1.9.0
 */
?>

<li class="<?php esc_attr_e( join( ' ', get_post_class( array( 'course', 'post' ) ) ) ); ?>">

    <section class="course-content">
        <?php
        /**
         * sensei_{post_type}_content_before
         * action that runs before the sensei {post_type} content. It runs inside the sensei
         * content.php template. This applies to the specific post type that you've targeted.
         *
         * @since 1.9
         * @param $post
         */
        do_action( 'sensei_course_content_before', get_post() );
        ?>

        <?php
        /**
         * sensei_content_before
         *
         * action that runs before the sensei content within the content.php. It will run for all post types.
         *
         * @since 1.9
         * @param WP_Post $post
         *
         */
        do_action('sensei_content_before', get_post() );
        ?>

        <section class="entry">

            <?php

            /**
             * sensei_content_inside_before
             *
             * Fires just before the post content in the content.php file. This fires
             * for all post types
             *
             * @since 1.9
             *
             * @param WP_Post $post
             */
            do_action('sensei_content_inside_before', get_post());
            ?>

            <?php
            /**
             * sensei_{$post_type}content_inside_before
             *
             * Fires just before the post content in the content.php file. This for the
             * specific {$post_type}.
             *
             * @since 1.9
             *
             * @param WP_Post $post
             */
            do_action('sensei_course_content_inside_before', get_post());
            ?>

            <p class="course-excerpt">

                <?php the_excerpt(); ?>

            </p>

            <?php
            /**
             * sensei_{$post_type}content_inside_before
             *
             * Fires just after the post content in the content.php file. This for the
             * specific {$post_type}.
             *
             * @since 1.9
             *
             * @param WP_Post $post
             */
            do_action('sensei_course_content_inside_after', get_post());
            ?>

            <?php
            /**
             * sensei_content_inside_before
             *
             * Fires just after the post content in the content.php file. This fires
             * for all post types
             *
             * @since 1.9
             *
             * @param WP_Post $post
             */
            do_action('sensei_content_inside_after', get_post());
            ?>

        </section> <!-- section .entry -->

    </section> <!-- section .course-content -->

</li> <!-- article .(<?php esc_attr_e( join( ' ', get_post_class( array( 'course', 'post' ) ) ) ); ?>  -->