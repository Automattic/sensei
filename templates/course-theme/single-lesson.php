<?php
/**
 * The Template for displaying all single lessons when using Course Theme.
 *
 * Override this template by copying it to yourtheme/sensei/course-theme/single-lesson.php
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     3.13.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( have_posts() ) {
	the_post();
}
?>

<!-- wp:columns {"className":"sensei-course-theme__header"} -->
<div class="wp-block-columns sensei-course-theme__header sensei-course-theme__frame">
	<!-- wp:column {"className":"sensei-course-theme__header__left"} -->
	<div class="wp-block-column sensei-course-theme__header__left">
		<!-- wp:sensei-lms/site-logo /-->
		<!-- wp:sensei-lms/course-title /-->
		<!-- wp:sensei-lms/progress-counter /-->
	</div>
	<!-- /wp:column -->
	<!-- wp:column {"className":"sensei-course-theme__header__right"} -->
	<div class="wp-block-column sensei-course-theme__header__right">
		<!-- wp:sensei-lms/course-theme-prev-next-lesson /-->
		<!-- wp:sensei-lms/lesson-actions /-->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->

<!-- wp:columns {"className":"sensei-course-theme__columns"} -->
<div class="wp-block-columns sensei-course-theme__columns">
	<!-- wp:column {"width":"300px","className":"sensei-course-theme__sidebar"} -->
	<div class="wp-block-column sensei-course-theme__sidebar sensei-course-theme__frame" style="flex-basis:300px">
		<!-- wp:sensei-lms/course-navigation /-->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"width":"","className":"sensei-course-theme__main-content"} -->
	<div class="wp-block-column sensei-course-theme__main-content">
		<!-- wp:post-title /-->
		<!-- wp:html -->
		<?php
		if ( sensei_can_user_view_lesson() ) {
			the_content();
		} else {
			?>
			<p>
				<?php echo wp_kses_post( get_the_excerpt() ); ?>
			</p>
			<?php
		}
		?>
		<!-- /wp:html -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
