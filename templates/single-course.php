<?php
/**
 * The Template for displaying all single courses.
 *
 * Override this template by copying it to yourtheme/sensei/single-course.php
 *
 * @author 		WooThemes
 * @package 	Sensei
 * @category    Templates
 * @version     1.9.0
 */
?>

<?php if ( ! defined( 'ABSPATH' ) ) { exit; } // This template is only accessible via WordPress  ?>

<?php get_header(); ?>

<?php

    /**
     * sensei_before_main_content hook
     *
     * @hooked sensei_output_content_wrapper - 10 (outputs opening divs for the content)
     */
    do_action( 'sensei_before_main_content' );

?>

<article <?php post_class( array( 'course', 'post' ) ); ?>>

    <?php

    /**
     * Hook inside the single course post above the content
     *
     * @since 1.9.0
     *
     * @hooked
     *
     */
    do_action( 'sensei_single_course_content_inside_before' );

    ?>

    <section class="entry fix">

        <?php the_content(); ?>

    </section>

    <?php

    /**
     * Hook inside the single course post above the content
     *
     * @since 1.9.0
     *
     * @hooked
     *
     */
    do_action( 'sensei_single_course_content_inside_after' );

    ?>
</article><!-- .post .single-course -->

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