<?php
/**
 * The Template for displaying course archives, including the course page template.
 *
 * Override this template by copying it to your_theme/sensei/archive-course.php
 *
 * @author 		Automattic
 * @package 	Sensei
 * @category    Templates
 * @version     1.9.0
 */
?>

<?php  get_sensei_header();  ?>

    <?php

        /**
         * action before course archive loop
         *
         * @deprecated since 1.9.0 use sensei_loop_course_before instead
         * @hooked Sensei_Templates::deprecated_archive_hook 80
         */
        do_action( 'sensei_archive_before_course_loop' );

    ?>

    <?php if ( have_posts() ): ?>

        <?php Sensei_Templates::get_template( 'loop-course.php' ); ?>

    <?php else: ?>

        <p><?php _e( 'No courses found that match your selection.', 'woothemes-sensei' ); ?></p>

    <?php  endif; // End If Statement ?>

    <?php

        /**
         * action after course archive  loop
         *
         * @deprecated since 1.9.0 use sensei_loop_course_after instead.
         */
        do_action( 'sensei_archive_after_course_loop' );

    ?>

<?php get_sensei_footer(); ?>
