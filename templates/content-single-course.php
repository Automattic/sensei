<?php
/**
 * The template for displaying product content in the single-course.php template
 *
 * Override this template by copying it to yourtheme/sensei/content-single-course.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */
?>

<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

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
        do_action( 'sensei_single_course_inside_before' );

    ?>

    <section class="entry fix">

       <? the_content(); ?>

    </section>

    <?php
        do_action( 'sensei_course_single_lessons' );
    ?>

</article><!-- .post .single-course -->