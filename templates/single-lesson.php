<?php
/**
 * The Template for displaying all single lessons.
 *
 * Override this template by copying it to yourtheme/sensei/single-lesson.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.9.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>


<?php
get_header();

    /**
     * sensei_before_main_content hook
     *
     * @hooked sensei_output_content_wrapper - 10 (outputs opening divs for the content)
     */
    do_action( 'sensei_before_main_content' );
?>

<?php the_post(); ?>

<article <?php post_class( array( 'lesson', 'post' ) ); ?>>

    <?php

        /**
         * Hook inside the single lesson above the content
         *
         * @since 1.9.0
         *
         * @hooked WooThemes_Sensei_Lesson::lesson_image() -  10
         * @hooked deprecated_lesson_image_hook - 10
         * @hooked deprecate_sensei_lesson_single_title - 15
         * @hooked deprecate_lesson_single_main_content_hook - 20
         */
        do_action( 'sensei_single_lesson_content_inside_before' );

    ?>

    <section class="entry fix">

        <?php

        if ( sensei_can_user_view_lesson() ) {

            if( apply_filters( 'sensei_video_position', 'top', $post->ID ) == 'top' ) {

                do_action( 'sensei_lesson_video', $post->ID );

            }

            the_content();

        } else {

            echo '<p>' . sensei_get_excerpt( get_the_ID() ) . '</p>';

        }

        ?>

    </section>

    <?php

        /**
         * Hook inside the single lesson template after the content
         *
         * @since 1.9.0
         *
         */
        do_action( 'sensei_single_lesson_content_inside_after' );

    ?>

</article><!-- .post -->


<?php

    /**
     *
     * Add sensei pagination to the single course page
     *
     */
    do_action('sensei_pagination');

?>

<?php

    /**
     * sensei_after_main_content hook
     *
     * @hooked sensei_output_content_wrapper_end - 10 (outputs closing divs for the content)
     */
    do_action( 'sensei_after_main_content' );

?>

<?php

    /**
     * sensei_sidebar hook
     *
     * @hooked sensei_get_sidebar - 10
     */
    do_action( 'sensei_sidebar' );

?>

<?php get_footer(); ?>