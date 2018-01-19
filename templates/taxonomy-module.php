<?php
/**
 * The template for displaying a module.
 *
 * Override this template by copying it to your_theme/sensei/taxonomy-module.php.
 *
 * @author    Automattic
 * @package   Sensei
 * @category  Templates
 * @version   1.9.20
 */
?>

<?php get_sensei_header(); ?>

	<?php
		/**
		 * Action before lesson archive loop. This action runs within the archive-lesson.php.
		 *
		 * It will be executed even if there are no posts on the archive page.
		 */
		do_action( 'sensei_taxonomy_module_content_before' );
	?>

	<?php if ( have_posts() ): ?>
			<section class="lesson-container" >
				<?php
					/**
					 * This runs before the lesson items in the loop-lesson.php template.
					 *
					 * @since 1.9.20
					 *
					 * @hooked Sensei()->lesson->lesson_tag_archive_description - 11
					 * @hooked Sensei()->lesson->the_archive_header - 20
					 */
					// Prints module title, description and progress indicator.
					do_action( 'sensei_taxonomy_module_content_inside_before' );
				?>

				<?php
					/**
					 * This runs inside the <ul> after the lesson items in the loop-lesson.php template.
					 *
					 * @since 1.9.20
					 */
					do_action( 'sensei_taxonomy_module_content_inside_after' );
					?>
			</section>

	<?php endif; // End If Statement ?>

	<?php

		/**
		 * Action after lesson archive  loop on the archive-lesson.php template file
		 * It will be executed even if there are no posts on the archive page.
		 *
		 * @since 1.9.20
		 */
		do_action( 'sensei_taxonomy_module_content_after' );
	?>

<?php get_sensei_footer(); ?>
