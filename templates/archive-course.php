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

	<?php

	$sensei_settings_course_page = get_post( Sensei()->settings->get( 'course_page' ) );

	if (
		is_a( $sensei_settings_course_page, 'WP_Post' ) &&
		! Sensei()->post_types->has_old_shortcodes( $sensei_settings_course_page->post_content ) &&
		! empty( $sensei_settings_course_page->post_content ) &&
		has_block( 'core/query', $sensei_settings_course_page->post_content )
	) {
		echo wp_kses(
			do_blocks( $sensei_settings_course_page->post_content ),
			array_merge(
				wp_kses_allowed_html( 'post' ),
				[
					'option' => [
						'selected' => [],
						'value'    => [],
					],
					'select' => [
						'class'          => [],
						'id'             => [],
						'name'           => [],
						'data-param-key' => [],
					],
					'form'   => [
						'action' => [],
						'method' => [],
					],
					'input'  => [
						'type'  => [],
						'name'  => [],
						'value' => [],
					],
				]
			)
		);

		remove_all_actions( 'sensei_pagination' );
	} elseif ( have_posts() ) {
		sensei_load_template( 'loop-course.php' );
	} else {
		?>

		<p><?php esc_html_e( 'No courses found that match your selection.', 'sensei-lms' ); ?></p>

		<?php
	}

	/**
	 * This action runs after including the course archive loop. This hook fires within the archive-course.php
	 * It fires even if the current archive has no posts.
	 *
	 * @since 1.9.0
	 */
	do_action( 'sensei_archive_after_course_loop' );

	get_sensei_footer();
?>
