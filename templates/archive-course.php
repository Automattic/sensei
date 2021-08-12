<?php
/**
 * The Template for displaying course archives, including the course page template.
 *
 * Override this template by copying it to your_theme/sensei/archive-course.php
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     3.0.0
 */
?>

<?php get_sensei_header(); ?>

	<div class="sensei-archive-controls">
		<?php

			/**
			 * This action before course archive loop. This hook fires within the archive-course.php
			 * It fires even if the current archive has no posts.
			 *
			 * @since 1.9.0
			 *
			 * @hooked Sensei_Course::course_archive_sorting 20
			 * @hooked Sensei_Course::course_archive_filters 20
			 * @hooked Sensei_Templates::deprecated_archive_hook 80
			 */
			do_action( 'sensei_archive_before_course_loop' );

		?>
	</div>

	<?php if ( have_posts() ) : ?>

		<?php sensei_load_template( 'loop-course.php' ); ?>

	<?php else : ?>

		<p><?php esc_html_e( 'No courses found that match your selection.', 'sensei-lms' ); ?></p>

	<?php endif; ?>

	<?php

		/**
		 * This action runs after including the course archive loop. This hook fires within the archive-course.php
		 * It fires even if the current archive has no posts.
		 *
		 * @since 1.9.0
		 */
		do_action( 'sensei_archive_after_course_loop' );

	?>

<?php get_sensei_footer(); ?>
