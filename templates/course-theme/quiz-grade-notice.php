<?php
/**
 * The Template for displaying quiz grade notice in quiz page when using Course Theme.
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     3.15.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'sensei_quiz_grade_notices_map' ) ) {
	/**
	 * Notices map to echo notices HTML.
	 *
	 * @param array $notice
	 */
	function sensei_quiz_grade_notices_map( $notice ) {
		?>
		<div class='sensei-course-theme__frame sensei-lms-notice sensei-course-theme-quiz-graded-notice'>
			<?php if ( isset( $notice['title'] ) && ! empty( $notice['title'] ) ) { ?>
			<div class='sensei-course-theme-quiz-graded-notice__title'><?php echo wp_kses_post( $notice['title'] ); ?></div>
			<?php } ?>

			<?php if ( isset( $notice['text'] ) && ! empty( $notice['text'] ) ) { ?>
			<div class='sensei-course-theme-quiz-graded-notice__text'><?php echo wp_kses_post( $notice['text'] ); ?></div>
			<?php } ?>

			<?php if ( isset( $notice['actions'] ) && ! empty( $notice['actions'] ) ) { ?>
			<div class='sensei-course-theme-quiz-graded-notice__actions'>
				<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- No user data is being outputted.
					echo implode( '', $notice['actions'] );
				?>
			</div>
			<?php } ?>

		</div>
		<?php
	}
}

?>

<div>
	<?php
		// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable -- Template variable.
		array_map( 'sensei_quiz_grade_notices_map', $notices );
	?>
</div>
