<?php
/**
 * Zoom/Meeting lesson pattern content.
 *
 * @package sensei-lms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<!-- wp:group {"align":"wide","style":{"color":{"background":"#f6f6f6"}}} -->
<div class="wp-block-group alignwide has-background" style="background-color:#f6f6f6"><!-- wp:columns {"verticalAlignment":"center","align":"wide"} -->
	<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center"} -->
		<div class="wp-block-column is-vertically-aligned-center"><!-- wp:paragraph {"style":{"typography":{"lineHeight":"2"}}} -->
			<p style="line-height:2"><strong><?php esc_html_e( 'Date:', 'sensei-lms' ); ?></strong><br><?php echo esc_html( wp_date( 'F jS, Y' ) ); ?></p>
			<!-- /wp:paragraph --></div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center"} -->
		<div class="wp-block-column is-vertically-aligned-center"><!-- wp:paragraph {"style":{"typography":{"lineHeight":"2"}}} -->
			<p style="line-height:2"><strong><?php esc_html_e( 'Time:', 'sensei-lms' ); ?></strong><br>11am GMT+1</p>
			<!-- /wp:paragraph --></div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center"} -->
		<div class="wp-block-column is-vertically-aligned-center"><!-- wp:buttons -->
			<div class="wp-block-buttons"><!-- wp:button {"width":100,"style":{"color":{"background":"#262626","text":"#efefef"}}} -->
				<div class="wp-block-button has-custom-width wp-block-button__width-100"><a class="wp-block-button__link has-text-color has-background" style="background-color:#262626;color:#efefef"><?php esc_html_e( 'Join Meeting', 'sensei-lms' ); ?></a></div>
				<!-- /wp:button --></div>
			<!-- /wp:buttons --></div>
		<!-- /wp:column --></div>
	<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":24} -->
<div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:paragraph -->
<p><strong><?php esc_html_e( 'Agenda:', 'sensei-lms' ); ?></strong></p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul>
	<li><?php esc_html_e( 'Introductions', 'sensei-lms' ); ?></li>
	<li><?php esc_html_e( 'Review last lesson', 'sensei-lms' ); ?></li>
	<li><?php esc_html_e( 'Discuss the final assessment', 'sensei-lms' ); ?></li>
	<li><?php esc_html_e( 'Open Q&amp;A', 'sensei-lms' ); ?></li>
</ul>
<!-- /wp:list -->

<!-- wp:sensei-lms/lesson-actions -->
<div class="wp-block-sensei-lms-lesson-actions"><div class="sensei-buttons-container"><!-- wp:sensei-lms/button-view-quiz {"inContainer":true} -->
		<div class="wp-block-sensei-lms-button-view-quiz is-style-default sensei-buttons-container__button-block wp-block-sensei-lms-button-view-quiz__wrapper"><div class="wp-block-sensei-lms-button-view-quiz is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link"><?php esc_html_e( 'View Quiz', 'sensei-lms' ); ?></button></div></div>
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
