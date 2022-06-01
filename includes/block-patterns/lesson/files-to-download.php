<?php
/**
 * Files to Download pattern.
 *
 * @package sensei-lms
 */

return [
	'title'      => __( 'Files to Download', 'sensei-lms' ),
	'categories' => [ \Sensei_Block_Patterns::get_patterns_category_name() ],
	'blockTypes' => [ \Sensei_Block_Patterns::get_post_content_block_type_name() ],
	'content'    => '<!-- wp:file /-->
					<!-- wp:file /-->
					<!-- wp:file /-->

					<!-- wp:sensei-lms/lesson-actions -->
					<div class="wp-block-sensei-lms-lesson-actions"><div class="sensei-buttons-container"><!-- wp:sensei-lms/button-view-quiz {"inContainer":true} -->
					<div class="wp-block-sensei-lms-button-view-quiz is-style-default sensei-buttons-container__button-block wp-block-sensei-lms-button-view-quiz__wrapper"><div class="wp-block-sensei-lms-button-view-quiz is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link">View Quiz</button></div></div>
					<!-- /wp:sensei-lms/button-view-quiz -->

					<!-- wp:sensei-lms/button-complete-lesson {"inContainer":true} -->
					<div class="wp-block-sensei-lms-button-complete-lesson is-style-default sensei-buttons-container__button-block wp-block-sensei-lms-button-complete-lesson__wrapper"><div class="wp-block-sensei-lms-button-complete-lesson is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link sensei-stop-double-submission">Complete Lesson</button></div></div>
					<!-- /wp:sensei-lms/button-complete-lesson -->

					<!-- wp:sensei-lms/button-next-lesson {"inContainer":true} -->
					<div class="wp-block-sensei-lms-button-next-lesson is-style-default sensei-buttons-container__button-block wp-block-sensei-lms-button-next-lesson__wrapper"><div class="wp-block-sensei-lms-button-next-lesson is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link">Next Lesson</button></div></div>
					<!-- /wp:sensei-lms/button-next-lesson -->

					<!-- wp:sensei-lms/button-reset-lesson {"inContainer":true} -->
					<div class="wp-block-sensei-lms-button-reset-lesson is-style-outline sensei-buttons-container__button-block wp-block-sensei-lms-button-reset-lesson__wrapper"><div class="wp-block-sensei-lms-button-reset-lesson is-style-outline wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link sensei-stop-double-submission">Reset Lesson</button></div></div>
					<!-- /wp:sensei-lms/button-reset-lesson --></div></div>
					<!-- /wp:sensei-lms/lesson-actions -->',
];
