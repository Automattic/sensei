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
?>
<div>
	<?php
	array_map(
		function( $notice ) {
			?>
			<div class="sensei-lms-notice sensei-course-theme-lesson-quiz-notice">
				<div class="sensei-course-theme-lesson-quiz-notice__content">
					<?php
					if ( ! empty( $notice['title'] ) ) {
						?>
						<h3 class="sensei-course-theme-lesson-quiz-notice__title">
							<?php
							echo wp_kses_post( $notice['title'] );
							?>
						</h3>
						<?php
					}
					?>
					<p class="sensei-course-theme-lesson-quiz-notice__text"><?php echo wp_kses_post( $notice['text'] ); ?></p>
				</div>

				<?php if ( ! empty( $notice['actions'] ) ) { ?>
					<ul class="sensei-course-theme-lesson-quiz-notice__actions">
					<?php
					array_map(
						function( $action ) {
							?>
							<li>
								<a
									href="<?php echo esc_url( $action['url'] ); ?>"
									class="button is-<?php echo esc_attr( $action['style'] ); ?>"
								>
									<?php echo wp_kses_post( $action['label'] ); ?>
								</a>
							</li>
							<?php
						},
						$notice['actions']
					);
					?>
					</ul>
				<?php } ?>
			</div>
			<?php
		},
		$notices
	);
	?>
</div>
