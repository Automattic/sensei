<?php
/**
 * Default lesson pattern content.
 *
 * @package sensei-lms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<!-- wp:heading {"className":"sensei-content-title"} -->
<h2 class="sensei-content-title"><?php esc_html_e( 'Lesson Title', 'sensei-lms' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sensei-content-description"} -->
<p class="sensei-content-description"><?php esc_html_e( 'Add a brief description of your lesson here. This will help students understand what they will learn.', 'sensei-lms' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3><?php esc_html_e( 'Learning Objectives', 'sensei-lms' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul>
<li><?php esc_html_e( 'First learning objective', 'sensei-lms' ); ?></li>
<li><?php esc_html_e( 'Second learning objective', 'sensei-lms' ); ?></li>
<li><?php esc_html_e( 'Third learning objective', 'sensei-lms' ); ?></li>
</ul>
<!-- /wp:list -->

<!-- wp:heading {"level":3} -->
<h3><?php esc_html_e( 'Lesson Content', 'sensei-lms' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Start writing your lesson content here. You can add text, images, videos, and other media to create an engaging learning experience.', 'sensei-lms' ); ?></p>
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
