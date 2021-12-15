<?php
/**
 * Single page template.
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:sensei-lms/container {"className":"sensei-course-theme__header"} -->
<div class="wp-block-sensei-lms-container sensei-course-theme__header sensei-course-theme__frame">
	<!-- wp:columns {"className":"sensei-course-theme__header"} -->
	<div class="sensei-course-theme__header__container">
		<!-- wp:column {"className":"sensei-course-theme__header__left"} -->
		<div class="sensei-course-theme__header__left">
			<!-- wp:sensei-lms/site-logo /-->
			<!-- wp:sensei-lms/course-title /-->
			<!-- wp:sensei-lms/course-theme-course-progress-counter /-->
		</div>
		<!-- /wp:column -->
		<!-- wp:column {"className":"sensei-course-theme__header__navigation"} -->
		<div class="sensei-course-theme__header__navigation">
			<!-- wp:sensei-lms/course-theme-prev-next-lesson -->
			<!-- wp:sensei-lms/course-theme-prev-lesson {"inContainer":true} /-->
			<!-- wp:sensei-lms/course-theme-next-lesson {"inContainer":true} /-->
			<!-- /wp:sensei-lms/course-theme-prev-next-lesson -->
			<!-- wp:sensei-lms/sidebar-toggle-button /-->
		</div>
		<!-- /wp:column -->
		<!-- wp:column {"className":"sensei-course-theme__actions"} -->
		<div class="sensei-course-theme__actions">
			<!-- wp:sensei-lms/course-theme-lesson-actions /-->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
	<!-- wp:sensei-lms/course-theme-course-progress-bar /-->
</div>
<!-- /wp:sensei-lms/container -->
<!-- wp:columns {"className":"sensei-course-theme__columns"} -->
<div class="sensei-course-theme__columns">
	<!-- wp:column {"width":"300px","className":"sensei-course-theme__sidebar"} -->
	<div class="sensei-course-theme__sidebar sensei-course-theme__frame" style="flex-basis:300px">
		<!-- wp:sensei-lms/sidebar-content -->
		<div class="sensei-course-theme__sidebar__content">
			<!-- wp:sensei-lms/focus-mode-toggle /-->
			<!-- wp:sensei-lms/course-navigation /-->
		</div>
		<!-- wp:sensei-lms/sidebar-footer -->
		<div class="sensei-course-theme__sidebar__footer">
			<!-- wp:sensei-lms/button-contact-teacher /-->
			<a href="/">Exit Course</a>
		</div>
		<!-- /wp:sensei-lms/sidebar-footer -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"width":"","className":"sensei-course-theme__main-content"} -->
	<div class="sensei-course-theme__main-content">
		<!-- wp:sensei-lms/course-theme-lesson-module /-->
		<!-- wp:sensei-lms/course-theme-post-title /-->

		<!-- wp:sensei-lms/course-theme-notices /-->
		<!-- wp:sensei-lms/course-theme-quiz-graded /-->
		<!-- wp:sensei-lms/course-content /-->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
