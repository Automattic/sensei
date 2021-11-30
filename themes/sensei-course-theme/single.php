<?php
/**
 * Single page template.
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     4.0.0
 */

?>
<!-- wp:sensei-lms/container {"className":"sensei-course-theme__header"} -->
<div class="wp-block-sensei-lms-container sensei-course-theme__header sensei-course-theme__frame">
	<!-- wp:columns {"className":"sensei-course-theme__header"} -->
	<div class="wp-block-columns sensei-course-theme__header__container">
		<!-- wp:column {"className":"sensei-course-theme__header__left"} -->
		<div class="wp-block-column sensei-course-theme__header__left">
			<!-- wp:sensei-lms/site-logo /-->
			<!-- wp:sensei-lms/course-title /-->
			<!-- wp:sensei-lms/course-theme-course-progress-counter /-->
		</div>
		<!-- /wp:column -->
		<!-- wp:column {"className":"sensei-course-theme__header__right"} -->
		<div class="wp-block-column sensei-course-theme__header__right">
			<!-- wp:sensei-lms/course-theme-prev-next-lesson -->
			<!-- wp:sensei-lms/course-theme-prev-lesson {"inContainer":true} /-->
			<!-- wp:sensei-lms/course-theme-next-lesson {"inContainer":true} /-->
			<!-- /wp:sensei-lms/course-theme-prev-next-lesson -->
			<!-- wp:sensei-lms/course-theme-complete-lesson /-->
			<!-- wp:sensei-lms/course-theme-quiz-button /-->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
	<!-- wp:sensei-lms/course-theme-course-progress-bar /-->
</div>
<!-- /wp:sensei-lms/container -->
<!-- wp:columns {"className":"sensei-course-theme__columns"} -->
<div class="wp-block-columns sensei-course-theme__columns">
	<!-- wp:column {"width":"300px","className":"sensei-course-theme__sidebar"} -->
	<div class="wp-block-column sensei-course-theme__sidebar sensei-course-theme__frame" style="flex-basis:300px">
		<!-- wp:sensei-lms/focus-mode-toggle /-->
		<!-- wp:sensei-lms/course-navigation /-->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"width":"","className":"sensei-course-theme__main-content"} -->
	<div class="wp-block-column sensei-course-theme__main-content">
		<!-- wp:post-title /-->

		<!-- wp:sensei-lms/course-theme-notices /-->

		<?php get_template_part( 'content', get_post_type() ); ?>
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
