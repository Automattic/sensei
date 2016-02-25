<?php
/**
 * The Template for displaying teacher author archives, this template wil show the teacher
 * and all course that belong to to them.
 *
 * Override this template by copying it to your_theme/sensei/teacher-archive.php
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
         * This action before teacher courses loop. This hook fires within the archive-course.php
         * It fires even if the current archive has no posts.
         *
         * @since 1.9.0
         *
         */
        do_action( 'sensei_teacher_archive_course_loop_before' );

    ?>

    <?php if ( have_posts() ): ?>

        <?php sensei_load_template( 'loop-course.php' ); ?>

    <?php else: ?>

        <p><?php _e( 'There are no courses for this teacher.', 'woothemes-sensei' ); ?></p>

    <?php  endif; // End If Statement ?>

    <?php

        /**
         * This action runs after including the teacher archive loop. This hook fires within the teacher-archive.php
         * It fires even if the current archive has no posts.
         *
         * @since 1.9.0
         */
        do_action( 'sensei_teacher_archive_course_loop_after' );

    ?>

<?php get_sensei_footer(); ?>
