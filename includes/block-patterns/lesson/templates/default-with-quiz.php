<?php
/**
 * Default with Quiz lesson pattern content.
 *
 * @package sensei-lms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<!-- wp:paragraph {"placeholder":"<?php esc_html_e( 'Write lesson content...', 'sensei-lms' ); ?>"} -->
<p></p>
<!-- /wp:paragraph -->

<!-- wp:sensei-lms/quiz {"options":{"passRequired":false,"quizPassmark":0,"autoGrade":false,"allowRetakes":false,"showQuestions":null,"randomQuestionOrder":false,"failedIndicateIncorrect":true,"failedShowCorrectAnswers":true,"failedShowAnswerFeedback":true,"buttonTextColor":null,"buttonBackgroundColor":null,"pagination":{"paginationNumber":null,"showProgressBar":false,"progressBarRadius":6,"progressBarHeight":12,"progressBarColor":null,"progressBarBackground":null},"enableQuizTimer":"","timerValue":null}} -->
<!-- wp:sensei-lms/quiz-question {"id":1630,"title":"<?php esc_html_e( 'Question 1', 'sensei-lms' ); ?>","answer":{"answers":[{"label":"<?php esc_html_e( 'Yes', 'sensei-lms' ); ?>","correct":true},{"label":"<?php esc_html_e( 'No', 'sensei-lms' ); ?>","correct":false}]},"options":{"grade":1,"answerFeedback":null,"randomOrder":false}} -->

<!-- wp:sensei-lms/question-description -->
<!-- wp:paragraph {"placeholder":"<?php esc_html_e( 'The question description goes here.', 'sensei-lms' ); ?>"} -->
<p></p>
<!-- /wp:paragraph -->
<!-- /wp:sensei-lms/question-description -->

<!-- wp:sensei-lms/question-answers /-->

<!-- wp:sensei-lms/quiz-question-feedback-correct -->
<!-- wp:paragraph {"placeholder":"<?php esc_html_e( 'Show a message when the question is answered correctly. Type / to choose a block.', 'sensei-lms' ); ?>"} -->
<p></p>
<!-- /wp:paragraph -->
<!-- /wp:sensei-lms/quiz-question-feedback-correct -->

<!-- wp:sensei-lms/quiz-question-feedback-incorrect -->
<!-- wp:paragraph {"placeholder":"<?php esc_html_e( 'Show a message when the question is answered incorrectly. Type / to choose a block.', 'sensei-lms' ); ?>"} -->
<p></p>
<!-- /wp:paragraph -->
<!-- /wp:sensei-lms/quiz-question-feedback-incorrect -->
<!-- /wp:sensei-lms/quiz-question -->
<!-- /wp:sensei-lms/quiz -->

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
