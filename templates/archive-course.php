<?php
/**
 * The Template for displaying course archives, including the course page template.
 *
 * Override this template by copying it to yourtheme/sensei/archive-course.php
 *
 * @author 		WooThemes
 * @package 	Sensei
 * @category    Templates
 * @version     1.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

?>

<?php get_header(); ?>

    <?php
        /**
         * sensei_before_main_content hook
         *
         *
         * @hooked sensei_output_content_wrapper - 10 (outputs opening divs for the content)
         */
        do_action('sensei_before_main_content');
    ?>

    <?php

        /**
         * Sensei archive title hook
         *
         * @hooked Sensei_Templates::course_archive_title - 20
         */
        do_action('sensei_archive_title');

    ?>

    <?php if ( have_posts() ): ?>

        <?php

            /**
             * action before course archive loop
             *
             * @hooked Sensei_Templates::deprecated_archive_hook 80
             */
            do_action( 'sensei_archive_before_course_loop' );

        ?>

        <?php Sensei_Templates::get_template( 'loop-course.php' ); ?>

        <?php

            /**
             * action after course archive  loop
             *
             */
            do_action( 'sensei_archive_after_course_loop' );

        ?>

    <?php else: ?>

        <p><?php _e( 'No courses found that match your selection.', 'woothemes-sensei' ); ?></p>

    <?php  endif; // End If Statement ?>

    <?php

        /**
         * sensei_pagination hook
         *
         * @hooked sensei_pagination - 10 (outputs archive pagination)
         */
        do_action('sensei_pagination');

    ?>

    <?php

        /**
         * sensei_after_main_content hook
         *
         * @hooked sensei_output_content_wrapper_end - 10 (outputs closing divs for the content)
         */
        do_action('sensei_after_main_content');

    ?>

    <?php

        /**
         * sensei_sidebar hook
         *
         * @hooked sensei_get_sidebar - 10
         */
        do_action('sensei_sidebar');

    ?>

<?php get_footer(); ?>