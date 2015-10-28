<?php
/**
 * The Template for displaying all single courses.
 *
 * Override this template by copying it to yourtheme/sensei/single-course.php
 *
 * @author 		Automattic
 * @package 	Sensei
 * @category    Templates
 * @version     1.9.0
 */
?>

<?php  get_sensei_header();  ?>

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

<?php get_sensei_footer(); ?>