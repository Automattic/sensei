<?php
/**
 * Single quiz page template.
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
<!-- wp:sensei-lms/container {"className":"sensei-course-theme__quiz"} -->
<div class="wp-block-sensei-lms-container sensei-course-theme__quiz">
	<!-- wp:columns {"className":"sensei-course-theme__quiz__header sensei-course-theme__frame"} -->
	<div class="wp-block-columns sensei-course-theme__quiz__header sensei-course-theme__frame">
		<!-- wp:column {"className":"sensei-course-theme__quiz__header__left"} -->
		<div class="wp-block-column sensei-course-theme__quiz__header__left">
			<!-- wp:sensei-lms/quiz-back-to-lesson /-->
			<!-- wp:post-title /-->
		</div>
		<!-- /wp:column -->
		<!-- wp:column {"className":"sensei-course-theme__quiz__header__right"} -->
		<div class="wp-block-column sensei-course-theme__quiz__header__right">
			<!-- wp:sensei-lms/quiz-progress-counter /-->
			<!-- wp:sensei-lms/quiz-progress-bar /-->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
	<!-- wp:sensei-lms/container {"className":"sensei-course-theme__quiz__main-content"} -->
	<div class="wp-block-sensei-lms-container sensei-course-theme__quiz__main-content">
		<!-- wp:sensei-lms/course-theme-notices /-->
		<!-- wp:sensei-lms/the-content /-->
	</div>
	<!-- /wp:sensei-lms/container -->

	<!-- wp:sensei-lms/container {"className":"sensei-course-theme__quiz__footer_wrapper"} -->
	<div class="sensei-course-theme__quiz__footer__wrapper sensei-course-theme__frame">
		<!-- wp:columns {"className":"sensei-course-theme__quiz__footer"} -->
			<div class="wp-block-columns sensei-course-theme__quiz__footer">
				<!-- wp:column {"className":"sensei-course-theme__quiz__footer__left"} -->
				<div class="wp-block-column sensei-course-theme__quiz__footer__left">
					<!-- wp:sensei-lms/quiz-pagination /-->
				</div>
				<!-- /wp:column -->
				<!-- wp:column {"className":"sensei-course-theme__quiz__footer__right"} -->
				<div class="wp-block-column sensei-course-theme__quiz__footer__right">
					<!-- wp:sensei-lms/quiz-actions /-->
					<?php Sensei_Quiz::action_buttons(); ?>
				</div>
				<!-- /wp:column -->
			</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:sensei-lms/container -->
</div>
<!-- /wp:sensei-lms/container -->
