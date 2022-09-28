<?php
/**
 * The Template for displaying quiz notice in lesson page when using Learning Mode.
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     3.15.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'sensei_lesson_quiz_notices_map' ) ) {
	/**
	 * Notices map to echo notices HTML.
	 *
	 * @param array $notice
	 */
	function sensei_lesson_quiz_notices_map( $notice ) {
		?>
		<div class="sensei-learning-mode__frame sensei-lms-notice sensei-learning-mode-lesson-quiz-notice">
			<div class="sensei-learning-mode-lesson-quiz-notice__content">
				<?php if ( ! empty( $notice['title'] ) ) { ?>
				<div class="sensei-learning-mode-lesson-quiz-notice__title">
					<?php echo wp_kses_post( $notice['title'] ); ?>
				</div>
				<?php } ?>
				<div class="sensei-learning-mode-lesson-quiz-notice__text"><?php echo wp_kses_post( $notice['text'] ); ?></div>
			</div>

			<?php if ( ! empty( $notice['actions'] ) ) { ?>
			<div class="sensei-learning-mode-lesson-quiz-notice__actions">
				<?php implode( '', array_map( 'sensei_lesson_quiz_notice_actions_map', $notice['actions'] ) ); ?>
			</div>
			<?php } ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'sensei_lesson_quiz_notice_actions_map' ) ) {
	/**
	 * Notice actions map to echo the actions.
	 *
	 * @param array|string $action
	 */
	function sensei_lesson_quiz_notice_actions_map( $action ) {
		?>
		<div>
			<?php
			if ( ! is_array( $action ) ) {
				echo wp_kses_post( $action );
			} else {
				?>
				<a href="<?php echo esc_url( $action['url'] ); ?>" class="sensei-learning-mode-lesson-quiz-notice__action sensei-learning-mode__button is-<?php echo esc_attr( $action['style'] ); ?>">
					<?php echo wp_kses_post( $action['label'] ); ?>
					<?php
						// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Dynamic parts escaped in the function.
						echo Sensei()->assets->get_icon( 'chevron-right', 'sensei-learning-mode-lesson-quiz-notice__link-chevron' );
					?>
				</a>
				<?php
			}
			?>
		</div>
		<?php
	}
}

?>
<div>
	<?php
	// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable -- A template variable
	implode( '', array_map( 'sensei_lesson_quiz_notices_map', $notices ) );
	?>
</div>
