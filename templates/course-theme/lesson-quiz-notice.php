<?php
/**
 * The Template for displaying quiz notice in lesson page when using Course Theme.
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
		<div class="sensei-lms-notice sensei-course-theme-lesson-quiz-notice">
			<div class="sensei-course-theme-lesson-quiz-notice__content">
				<?php if ( ! empty( $notice['title'] ) ) { ?>
				<h3 class="sensei-course-theme-lesson-quiz-notice__title">
					<?php echo wp_kses_post( $notice['title'] ); ?>
				</h3>
				<?php } ?>
				<p class="sensei-course-theme-lesson-quiz-notice__text"><?php echo wp_kses_post( $notice['text'] ); ?></p>
			</div>

			<?php if ( ! empty( $notice['actions'] ) ) { ?>
			<ul class="sensei-course-theme-lesson-quiz-notice__actions">
				<?php implode( '', array_map( 'sensei_lesson_quiz_notice_actions_map', $notice['actions'] ) ); ?>
			</ul>
			<?php } ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'sensei_lesson_quiz_notice_actions_map' ) ) {
	/**
	 * Notice actions map to echo the actions.
	 *
	 * @param array $action
	 */
	function sensei_lesson_quiz_notice_actions_map( $action ) {
		?>
		<li>
			<?php
			if ( ! is_array( $action ) ) {
				echo wp_kses_post( $action );
			} else {
				?>
				<a href="<?php echo esc_url( $action['url'] ); ?>" class="sensei-course-theme__button is-<?php echo esc_attr( $action['style'] ); ?>">
					<?php echo wp_kses_post( $action['label'] ); ?>
				</a>
				<?php
			}
			?>
		</li>
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
