<?php
/**
 * The template for displaying the quiz pagination.
 *
 * @author   Automattic
 * @package  Sensei
 * @category Templates
 * @version  3.15.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $sensei_question_loop;

?>

<div class="sensei-quiz-pagination">
	<div class="sensei-quiz-pagination__list">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- No need to escape the pagination links.
		echo paginate_links(
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
		?>
	</div>

	<div class="sensei-quiz-pagination__actions">
		<?php if ( Sensei_Quiz::is_reset_allowed( Sensei()->quiz->get_lesson_id( get_the_ID() ) ) ) : ?>
			<div class="sensei-quiz-pagination__button-link">
				<button type="submit" name="quiz_reset" class="sensei-stop-double-submission">
					<?php esc_attr_e( 'Reset', 'sensei-lms' ); ?>
				</button>

				<input type="hidden" name="woothemes_sensei_reset_quiz_nonce" id="woothemes_sensei_reset_quiz_nonce" value="<?php echo esc_attr( wp_create_nonce( 'woothemes_sensei_reset_quiz_nonce' ) ); ?>" />
			</div>
		<?php endif ?>

		<div class="sensei-quiz-pagination__button-link">
			<button type="submit" name="quiz_save" class="sensei-stop-double-submission">
				<?php esc_attr_e( 'Save', 'sensei-lms' ); ?>
			</button>

			<input type="hidden" name="woothemes_sensei_save_quiz_nonce" id="woothemes_sensei_save_quiz_nonce" value="<?php echo esc_attr( wp_create_nonce( 'woothemes_sensei_save_quiz_nonce' ) ); ?>" />
		</div>
	</div>

	<div class="sensei-quiz-pagination__nav">
		<div class="wp-block-buttons">
			<?php if ( $sensei_question_loop['current_page'] > 1 ) : ?>
				<div class="wp-block-button is-style-outline">
					<a
						href="<?php echo esc_attr( add_query_arg( 'quiz-page', $sensei_question_loop['current_page'] - 1 ) ); ?>"
						class="wp-block-button__link button sensei-quiz-pagination__prev-button"
					>
						<?php esc_attr_e( 'Previous', 'sensei-lms' ); ?>
					</a>
				</div>
			<?php endif ?>

			<?php if ( $sensei_question_loop['current_page'] === $sensei_question_loop['total_pages'] ) : ?>
				<div class="wp-block-button">
					<button type="submit" name="quiz_complete" class="wp-block-button__link button quiz-submit complete sensei-stop-double-submission">
						<?php esc_attr_e( 'Complete', 'sensei-lms' ); ?>
					</button>

					<input type="hidden" name="woothemes_sensei_complete_quiz_nonce" id="woothemes_sensei_complete_quiz_nonce" value="<?php echo esc_attr( wp_create_nonce( 'woothemes_sensei_complete_quiz_nonce' ) ); ?>" />
				</div>
			<?php else : ?>
				<div class="wp-block-button">
					<a
						href="<?php echo esc_attr( add_query_arg( 'quiz-page', $sensei_question_loop['current_page'] + 1 ) ); ?>"
						class="wp-block-button__link button sensei-quiz-pagination__next-button"
					>
						<?php esc_attr_e( 'Next', 'sensei-lms' ); ?>
					</a>
				</div>
			<?php endif ?>
		</div>
	</div>
</div>
