<?php
/**
 * The Template for displaying lesson archives, including the lesson page template.
 *
 * Override this template by copying it to your_theme/sensei/archive-lesson.php
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
         * action before lesson archive loop
         *
         * @deprecated since 1.9.0 use sensei_loop_lesson_before instead
         * @hooked Sensei_Templates::deprecated_archive_hook 80
         */
        do_action( 'sensei_archive_before_lesson_loop' );

    ?>

    <?php if ( have_posts() ): ?>

        <?php Sensei_Templates::get_template( 'loop-lesson.php' ); ?>

    <?php else: ?>

        <p><?php _e( 'No lessons found that match your selection.', 'woothemes-sensei' ); ?></p>

    <?php  endif; // End If Statement ?>

    <?php

        /**
         * action after lesson archive  loop
         *
         * @deprecated since 1.9.0 use sensei_loop_lesson_after instead.
         */
        do_action( 'sensei_archive_after_lesson_loop' );

    ?>

<?php get_sensei_footer(); ?>
