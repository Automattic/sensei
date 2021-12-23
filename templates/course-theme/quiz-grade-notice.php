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
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- No user data is being outputted.
		?>
		<div class='sensei-course-theme__frame sensei-lms-notice sensei-course-theme-quiz-graded-notice'>
			<?php if ( isset( $notice['title'] ) && ! empty( $notice['title'] ) ) { ?>
			<h1 class='sensei-course-theme-quiz-graded-notice__title'><?php echo $notice['title']; ?></h1>
			<?php } ?>

			<?php if ( isset( $notice['text'] ) && ! empty( $notice['text'] ) ) { ?>
			<p class='sensei-course-theme-quiz-graded-notice__text'><?php echo $notice['text']; ?></p>
			<?php } ?>

			<?php if ( isset( $notice['actions'] ) && ! empty( $notice['actions'] ) ) { ?>
			<div class='sensei-course-theme-quiz-graded-notice__actions'>
				<?php echo implode( '', $notice['actions'] ); ?>
			</div>
			<?php } ?>

		</div>
		<?php
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped -- No user data is being outputted.
	}
}

?>

<div>
	<?php
		// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable -- Template variable.
		array_map( 'sensei_quiz_grade_notices_map', $notices );
	?>
</div>
