<?php
/**
 * Deprecated Default pattern content.
 * Remove after the course_outline_ai feature is released.
 *
 * @package sensei-lms
 * @deprecated 4.16.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<!-- wp:paragraph {"placeholder":"<?php esc_html_e( 'Course description, objectives, and overview.', 'sensei-lms' ); ?>","className":"sensei-content-description"} -->
<p class="sensei-content-description"></p>
<!-- /wp:paragraph -->

<!-- wp:sensei-lms/button-take-course -->
<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link"><?php esc_html_e( 'Take Course', 'sensei-lms' ); ?></button></div>
<!-- /wp:sensei-lms/button-take-course -->

<!-- wp:sensei-lms/course-progress /-->

<!-- wp:sensei-lms/course-outline -->
<!-- wp:sensei-lms/course-outline-lesson {"title":"<?php esc_html_e( 'Lesson 1', 'sensei-lms' ); ?>"} /-->

<!-- wp:sensei-lms/course-outline-lesson {"title":"<?php esc_html_e( 'Lesson 2', 'sensei-lms' ); ?>"} /-->

<!-- wp:sensei-lms/course-outline-lesson {"title":"<?php esc_html_e( 'Lesson 3', 'sensei-lms' ); ?>"} /-->
<!-- /wp:sensei-lms/course-outline -->
