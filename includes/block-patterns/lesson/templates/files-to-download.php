<?php
/**
 * Files to Download pattern content.
 *
 * @package sensei-lms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<!-- wp:heading {"textAlign":"center"} -->
<h2 class="has-text-align-center"><?php esc_html_e( 'Lesson Resources', 'sensei-lms' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sensei-content-description"} -->
<p class="sensei-content-description"><?php esc_html_e( 'Download these resources to help you complete the lesson. The lesson guide contains detailed instructions, while the workbook includes exercises and practice materials.', 'sensei-lms' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:group {"style":{"spacing":{"blockGap":"1.5em"}}} -->
<div class="wp-block-group">
<!-- wp:file {"id":0,"href":"#","showDownloadButton":true} -->
<div class="wp-block-file"><a href="#"><?php esc_html_e( 'Lesson Guide (PDF)', 'sensei-lms' ); ?></a><a href="#" class="wp-block-file__button" download><?php esc_html_e( 'Download', 'sensei-lms' ); ?></a></div>
<!-- /wp:file -->

<!-- wp:file {"id":0,"href":"#","showDownloadButton":true} -->
<div class="wp-block-file"><a href="#"><?php esc_html_e( 'Exercise Workbook (PDF)', 'sensei-lms' ); ?></a><a href="#" class="wp-block-file__button" download><?php esc_html_e( 'Download', 'sensei-lms' ); ?></a></div>
<!-- /wp:file -->

<!-- wp:file {"id":0,"href":"#","showDownloadButton":true} -->
<div class="wp-block-file"><a href="#"><?php esc_html_e( 'Additional Resources (ZIP)', 'sensei-lms' ); ?></a><a href="#" class="wp-block-file__button" download><?php esc_html_e( 'Download', 'sensei-lms' ); ?></a></div>
<!-- /wp:file -->
</div>
<!-- /wp:group -->

<!-- wp:paragraph {"className":"sensei-content-note"} -->
<p class="sensei-content-note"><?php esc_html_e( 'Please download and review all materials before starting the lesson. These resources have been carefully selected to help you succeed in your learning journey. The PDF files can be opened with any standard PDF reader, while the ZIP file contains additional supplementary materials.', 'sensei-lms' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"sensei-content-note"} -->
<p class="sensei-content-note"><?php esc_html_e( 'If you have any trouble accessing or opening these files, please don\'t hesitate to contact your instructor for assistance. We recommend saving all files to a dedicated folder on your computer for easy access throughout the course. This will help you stay organized and make the most of your learning experience.', 'sensei-lms' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:sensei-lms/lesson-actions -->
<div class="wp-block-sensei-lms-lesson-actions"><div class="sensei-buttons-container"><!-- wp:sensei-lms/button-view-quiz {"inContainer":true} -->
<div class="wp-block-sensei-lms-button-view-quiz is-style-default sensei-buttons-container__button-block wp-block-sensei-lms-button-view-quiz__wrapper"><div class="wp-block-sensei-lms-button-view-quiz is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link"><?php esc_html_e( 'Take Quiz', 'sensei-lms' ); ?></button></div></div>
<!-- /wp:sensei-lms/button-view-quiz -->

<!-- wp:sensei-lms/button-complete-lesson {"inContainer":true} -->
<div class="wp-block-sensei-lms-button-complete-lesson is-style-default sensei-buttons-container__button-block wp-block-sensei-lms-button-complete-lesson__wrapper"><div class="wp-block-sensei-lms-button-complete-lesson is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link sensei-stop-double-submission"><?php esc_html_e( 'Complete Lesson', 'sensei-lms' ); ?></button></div></div>
<!-- /wp:sensei-lms/button-complete-lesson -->

<!-- wp:sensei-lms/button-next-lesson {"inContainer":true} -->
<div class="wp-block-sensei-lms-button-next-lesson is-style-default sensei-buttons-container__button-block wp-block-sensei-lms-button-next-lesson__wrapper"><div class="wp-block-sensei-lms-button-next-lesson is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link"><?php esc_html_e( 'Next Lesson', 'sensei-lms' ); ?></button></div></div>
<!-- /wp:sensei-lms/button-next-lesson -->

<!-- wp:sensei-lms/button-reset-lesson {"inContainer":true} -->
<div class="wp-block-sensei-lms-button-reset-lesson is-style-outline sensei-buttons-container__button-block wp-block-sensei-lms-button-reset-lesson__wrapper"><div class="wp-block-sensei-lms-button-reset-lesson is-style-outline wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link sensei-stop-double-submission"><?php esc_html_e( 'Reset Lesson', 'sensei-lms' ); ?></button></div></div>
<!-- /wp:sensei-lms/button-reset-lesson --></div></div>
<!-- /wp:sensei-lms/lesson-actions -->

