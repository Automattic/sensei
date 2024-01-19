<?php
/**
 * The template for displaying the quiz pagination.
 *
 * @author   Automattic
 * @package  Sensei
 * @category Templates
 * @version  4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $sensei_question_loop;

$sensei_is_quiz_available    = Sensei_Quiz::is_quiz_available();
$sensei_is_quiz_completed    = Sensei_Quiz::is_quiz_completed();
$sensei_is_reset_allowed     = Sensei_Quiz::is_reset_allowed( Sensei()->quiz->get_lesson_id() );
$sensei_has_actions          = $sensei_is_reset_allowed || ! $sensei_is_quiz_completed;
$sensei_button_inline_styles = Sensei_Quiz::get_button_inline_styles();
$sensei_next_question_count  = min( $sensei_question_loop['total'] - $sensei_question_loop['current_page'] * $sensei_question_loop['posts_per_page'], $sensei_question_loop['posts_per_page'] );

?>

<div class="sensei-quiz-pagination">
	<input type="hidden" name="sensei_quiz_page_change_nonce" form="sensei-quiz-form" id="sensei_quiz_page_change_nonce" value="<?php echo esc_attr( wp_create_nonce( 'sensei_quiz_page_change_nonce' ) ); ?>" />

	<div class="sensei-quiz-pagination__list">
		<?php

		$sensei_pagination_list = paginate_links(
			/**
			 * Filters the quiz questions paginate links arguments.
			 *
			 * @see   https://developer.wordpress.org/reference/functions/paginate_links/
			 * @hook  sensei_quiz_pagination_args
			 * @since 3.15.0
			 *
			 * @param {array} $args The pagination arguments.
			 *
			 * @return {array}
			 */
			apply_filters(
				'sensei_quiz_pagination_args',
				[
					'total'     => $sensei_question_loop['total_pages'],
					'current'   => $sensei_question_loop['current_page'],
					'format'    => '?quiz-page=%#%',
					'type'      => 'list',
					'mid_size'  => 1,
					'prev_next' => false,
				]
			)
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- No need to escape the pagination.
		echo Sensei()->quiz->replace_pagination_links_with_buttons( $sensei_pagination_list );

		?>
	</div>

	<div class="sensei-quiz-actions wp-block-sensei-lms-quiz-actions">
		<div class="sensei-quiz-actions-primary">
			<?php if ( $sensei_question_loop['current_page'] > 1 ) : ?>
				<div class="sensei-quiz-action wp-block-button is-style-outline">
					<button
						type="submit"
						name="quiz_target_page"
						form="sensei-quiz-form"
						value="<?php echo esc_attr( add_query_arg( 'quiz-page', $sensei_question_loop['current_page'] - 1 ) ); ?>"
						class="wp-block-button__link button sensei-stop-double-submission sensei-quiz-pagination__prev-button"
						style="<?php echo esc_attr( $sensei_button_inline_styles ); ?>"
					>
						<?php echo esc_attr( _n( 'Previous Question', 'Previous Questions', $sensei_question_loop['posts_per_page'], 'sensei-lms' ) ); ?>
					</button>
				</div>
			<?php endif ?>

			<?php if ( $sensei_question_loop['current_page'] < $sensei_question_loop['total_pages'] ) : ?>
				<div class="sensei-quiz-action wp-block-button">
					<button
						type="submit"
						name="quiz_target_page"
						form="sensei-quiz-form"
						value="<?php echo esc_attr( add_query_arg( 'quiz-page', $sensei_question_loop['current_page'] + 1 ) ); ?>"
						class="wp-block-button__link button sensei-stop-double-submission sensei-quiz-pagination__next-button"
						style="<?php echo esc_attr( $sensei_button_inline_styles ); ?>"
					>
						<?php echo esc_attr( _n( 'Next Question', 'Next Questions', $sensei_next_question_count, 'sensei-lms' ) ); ?>
					</button>
				</div>
			<?php elseif ( $sensei_is_quiz_available && ! $sensei_is_quiz_completed ) : ?>
				<div class="sensei-quiz-action wp-block-button">
					<button
						type="submit"
						name="quiz_complete"
						form="sensei-quiz-form"
						class="wp-block-button__link button quiz-submit complete sensei-stop-double-submission"
						style="<?php echo esc_attr( $sensei_button_inline_styles ); ?>"
					>
						<?php esc_attr_e( 'Complete Quiz', 'sensei-lms' ); ?>
					</button>

					<input type="hidden" name="woothemes_sensei_complete_quiz_nonce" form="sensei-quiz-form" id="woothemes_sensei_complete_quiz_nonce" value="<?php echo esc_attr( wp_create_nonce( 'woothemes_sensei_complete_quiz_nonce' ) ); ?>" />
				</div>
			<?php endif ?>
		</div>

		<?php if ( $sensei_is_quiz_available && $sensei_has_actions ) : ?>
			<div class="sensei-quiz-actions-secondary">
				<?php if ( $sensei_is_reset_allowed ) : ?>
					<div class="sensei-quiz-action">
						<button type="submit" name="quiz_reset" form="sensei-quiz-form" class="quiz-submit reset sensei-stop-double-submission sensei-course-theme__button is-link">
							<?php esc_attr_e( 'Restart Quiz', 'sensei-lms' ); ?>
						</button>

						<input type="hidden" name="woothemes_sensei_reset_quiz_nonce" form="sensei-quiz-form" id="woothemes_sensei_reset_quiz_nonce" value="<?php echo esc_attr( wp_create_nonce( 'woothemes_sensei_reset_quiz_nonce' ) ); ?>" />
					</div>
				<?php endif ?>

				<?php if ( ! $sensei_is_quiz_completed ) : ?>
					<div class="sensei-quiz-action">
						<button type="submit" name="quiz_save" form="sensei-quiz-form" class="quiz-submit save sensei-stop-double-submission sensei-course-theme__button is-link">
							<?php esc_attr_e( 'Save Progress', 'sensei-lms' ); ?>
						</button>

						<input type="hidden" name="woothemes_sensei_save_quiz_nonce" form="sensei-quiz-form" id="woothemes_sensei_save_quiz_nonce" value="<?php echo esc_attr( wp_create_nonce( 'woothemes_sensei_save_quiz_nonce' ) ); ?>" />
					</div>
				<?php endif ?>
			</div>
		<?php endif ?>
	</div>
</div>
