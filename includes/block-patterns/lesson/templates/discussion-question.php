<?php
/**
 * Discussion Question pattern content.
 *
 * @package sensei-lms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<!-- wp:group {"backgroundColor":"tertiary"} -->
<div class="wp-block-group has-tertiary-background-color has-background"><!-- wp:paragraph {"placeholder":"<?php esc_html_e( 'Write your discussion question topic or prompt here.', 'sensei-lms' ); ?>"} -->
<p></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:comments-query-loop -->
<div class="wp-block-comments-query-loop"><!-- wp:post-comments-form /-->

<!-- wp:comment-template -->
<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"width":"40px"} -->
<div class="wp-block-column" style="flex-basis:40px"><!-- wp:avatar {"size":40,"style":{"border":{"radius":"20px"}}} /--></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:comment-author-name /-->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"0px","bottom":"0px"}}},"layout":{"type":"flex"}} -->
<div class="wp-block-group" style="margin-top:0px;margin-bottom:0px"><!-- wp:comment-date /-->

<!-- wp:comment-edit-link /--></div>
<!-- /wp:group -->

<!-- wp:comment-content /-->

<!-- wp:comment-reply-link /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- /wp:comment-template -->

<!-- wp:comments-pagination -->
<!-- wp:comments-pagination-previous /-->

<!-- wp:comments-pagination-numbers /-->

<!-- wp:comments-pagination-next /-->
<!-- /wp:comments-pagination --></div>
<!-- /wp:comments-query-loop -->

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
