<?php
/**
 * File containing enrolment debug tool.
 *
 * @package sensei-lms
 * @since 3.7.0
 *
 * @var array $results Processed result.
 */

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$allowed_debug_html = [
	'a'      => [
		'href' => true,
	],
	'strong' => true,
	'em'     => true,
	'span'   => [
		'style' => true,
		'class' => true,
	],
];
?>
<div class="sensei-lms-enrolment-debug">
<table class="form-table sensei-lms-tool-info" role="presentation">
	<tr>
		<th scope="row"><?php esc_html_e( 'User', 'sensei-lms' ); ?></th>
		<td>
			<div class="info">
			<?php
			echo esc_html( $results['user'] );
			?>
			</div>
			<div class="info-buttons">
			<?php
			$edit_url = admin_url( 'user-edit.php?user_id=' . intval( $results['user_id'] ) );
			echo '<a href="' . esc_url( $edit_url ) . '" class="button">' . esc_html__( 'Edit User', 'sensei-lms' ) . '</a>';

			if ( class_exists( 'WooCommerce' ) ) {
				$orders_url = admin_url( sprintf( 'edit.php?post_type=shop_order&_customer_user=%d', $results['user_id'] ) );
				echo ' <a href="' . esc_url( $orders_url ) . '" class="button">' . esc_html__( 'Orders', 'sensei-lms' ) . '</a>';
			}
			?>
			</div>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Course', 'sensei-lms' ); ?></th>
		<td>
			<div class="info">
			<?php echo esc_html( $results['course'] ); ?>
			</div>
			<div class="info-buttons">
				<?php
				$view_course_url = get_permalink( $results['course_id'] );
				echo '<a href="' . esc_url( $view_course_url ) . '" class="button">' . esc_html__( 'View Course', 'sensei-lms' ) . '</a>';

				$edit_course_url = admin_url( sprintf( 'post.php?post=%d&action=edit', $results['course_id'] ) );
				echo ' <a href="' . esc_url( $edit_course_url ) . '" class="button">' . esc_html__( 'Edit Course', 'sensei-lms' ) . '</a>';

				$manage_learners_url = admin_url( sprintf( 'admin.php?page=sensei_learners&course_id=%d&view=learners', $results['course_id'] ) );
				echo ' <a href="' . esc_url( $manage_learners_url ) . '" class="button">' . esc_html__( 'Manage Learners', 'sensei-lms' ) . '</a>';
				?>
			</div>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Enrollment status', 'sensei-lms' ); ?></th>
		<td>
			<?php
			if ( $results['is_enrolled'] ) {
				echo '<div class="info info-positive">';
				echo esc_html__( 'Enrolled', 'sensei-lms' );
				echo '</div>';
			} else {
				echo '<div class="info info-negative">';
				echo esc_html__( 'Not Enrolled', 'sensei-lms' );
				echo '</div>';

				if ( $results['is_removed'] ) {
					echo '<div class="info info-neutral">';
					echo esc_html__( 'Learner manually removed', 'sensei-lms' );
					echo '</div>';
				}
			}
			?>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Course progress status', 'sensei-lms' ); ?></th>
		<td>
			<?php
			if ( $results['progress'] ) {
				echo '<div class="info info-positive">';
				echo esc_html( $results['progress']['status'] );
				echo ' (' . intval( $results['progress']['percent_complete'] ) . '%)';
				echo '</div>';

				echo '<div class="info info-neutral">';
				// translators: %s placeholder is datetime progress was started.
				echo esc_html( sprintf( __( 'Started on %s', 'sensei-lms' ), $results['progress']['start_date'] ) );
				echo '</div>';

				if ( ! empty( $results['progress']['last_activity'] ) ) {
					echo '<div class="info info-neutral">';
					// translators: %s placeholder is datetime progress was started.
					echo esc_html( sprintf( __( 'Last activity on %s', 'sensei-lms' ), $results['progress']['last_activity'] ) );
					echo '</div>';
				}
			} else {
				echo '<div class="info info-negative">';
				echo esc_html__( 'No Progress', 'sensei-lms' );
				echo '</div>';
			}
			?>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Cached enrollment status', 'sensei-lms' ); ?></th>
		<td>
			<?php
			if ( $results['results_match'] ) {
				echo '<div class="info info-positive">';
				echo esc_html__( 'Matches Calculated Enrollment', 'sensei-lms' );
				echo '</div>';
			} else {
				echo '<div class="info info-negative">';
				echo esc_html__( 'Does Not Match Calculated Enrollment', 'sensei-lms' );
				echo '</div>';
			}

			if ( $results['results_time'] ) {
				echo '<div class="info info-neutral">';
				// translators: %s placeholder is datetime results were last calculated.
				echo esc_html( sprintf( __( 'Last calculated on %s', 'sensei-lms' ), $results['results_time'] ) );
				echo '</div>';
			}
			?>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Providers', 'sensei-lms' ); ?></th>
		<td>
			<?php
			foreach ( $results['providers'] as $provider ) {
				?>
				<div class="provider
				<?php
				if ( $provider['handles_course'] ) {
					echo ' handles';
				} else {
					echo ' does-not-handle';
				}

				if ( $provider['is_enrolled'] ) {
					echo ' enrolled';
				} else {
					echo ' is-not-enrolled';
				}
				?>
				">
					<div class="name">
						<?php
						echo esc_html( $provider['name'] );

						if ( ! $provider['handles_course'] ) {
							echo '<div class="tag">';
							echo esc_html__( 'Does Not Handle Course', 'sensei-lms' );
							echo '</div>';
						} elseif ( $provider['is_enrolled'] ) {
							echo '<div class="tag">';
							echo esc_html__( 'Enrolls Learner', 'sensei-lms' );
							echo '</div>';
						} else {
							echo '<div class="tag">';
							echo esc_html__( 'Does Not Enroll Learner', 'sensei-lms' );
							echo '</div>';
						}
						?>
					</div>
					<?php
					$columns = [];
					if ( ! empty( $provider['debug'] ) ) {
						$column   = [];
						$column[] = '<h4>' . __( 'Information', 'sensei-lms' ) . '</h4>';
						$column[] = '<div class="debug">';
						foreach ( $provider['debug'] as $message ) {
							$column[] = '<div class="message">';
							$column[] = wp_kses( $message, $allowed_debug_html );
							$column[] = '</div>';
						}
						$column[] = '</div>';

						$columns['info'] = $column;
					}

					if ( ! empty( $provider['logs'] ) ) {
						$column   = [];
						$column[] = '<div class="logs">';
						$column[] = '<h4>' . __( 'Logs', 'sensei-lms' ) . '</h4>';
						foreach ( $provider['logs'] as $message ) {
							$column[] = '<div class="message">';
							$column[] = '<span class="time">' . Sensei_Tool_Enrolment_Debug::format_date( $message['timestamp'] ) . '</span>';

							$column[] = '<span class="content">';
							$column[] = esc_html( $message['message'] );

							if ( ! empty( $message['data'] ) ) {
								$column[] = '<details>';
								$column[] = '<summary>' . esc_html__( 'More information...', 'sensei-lms' ) . '</summary>';
								$column[] = '<pre>';
								// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Special debug case.
								$column[] = esc_html( print_r( $message['data'], true ) );
								$column[] = '</pre>';
								$column[] = '</details>';
							}
							$column[] = '</span>';

							$column[] = '</div>';
						}
						$column[] = '</div>';

						$columns['logs'] = $column;
					}

					if ( ! empty( $provider['history'] ) ) {
						$column   = [];
						$column[] = '<div class="history">';
						$column[] = '<h4>' . __( 'History', 'sensei-lms' ) . '</h4>';
						foreach ( $provider['history'] as $history ) {
							$item_class = 'neutral';
							if ( null === $history['enrolment_status'] ) {
								$description = __( 'Stopped handling', 'sensei-lms' );
							} elseif ( $history['enrolment_status'] ) {
								$description = __( 'Provided', 'sensei-lms' );
								$item_class  = 'positive';
							} else {
								$description = __( 'Withdrawn', 'sensei-lms' );
								$item_class  = 'negative';
							}

							$column[] = '<div class="history-item ' . esc_attr( $item_class ) . '">';
							$column[] = '<span class="time">' . Sensei_Tool_Enrolment_Debug::format_date( $history['timestamp'] ) . '</span>';

							$column[] = '<span class="content">';
							$column[] = esc_html( $description );
							$column[] = '</span>';
							$column[] = '</div>';
						}
						$column[] = '</div>';

						$columns['history'] = $column;
					}


					if ( ! empty( $columns ) ) {
						echo '<div class="provider-details">';
						foreach ( $columns as $column_id => $column ) {
							echo '<div class="column column-' . esc_attr( $column_id ) . '">';
							echo wp_kses_post( implode( $column ) );
							echo '</div>';
						}
						echo '</div>';
					}
					?>
				</div>
				<?php
			}
			?>
		</td>
	</tr>
</table>
</div>
<hr />
