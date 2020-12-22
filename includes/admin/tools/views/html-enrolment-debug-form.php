<?php
/**
 * File containing enrolment debug tool form.
 *
 * @package sensei-lms
 * @since 3.7.0
 *
 * @var false|WP_User $users   List of all users or false if too big.
 * @var false|WP_Post $courses List of all courses or false if too big.
 * @var string        $tool_id Tool ID for this tool.
 */

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<form method="get" action="">
	<?php wp_nonce_field( Sensei_Tool_Enrolment_Debug::NONCE_ACTION, '_wpnonce', false ); ?>
	<input type="hidden" name="page" value="sensei-tools">
	<input type="hidden" name="tool" value="<?php echo esc_attr( $tool_id ); ?>">
	<table class="form-table" role="presentation">
	<tr class="form-field form-required">
		<th scope="row"><label for="user_id"><?php esc_html_e( 'User', 'sensei-lms' ); ?> <span class="description">(<?php esc_html_e( 'Required', 'sensei-lms' ); ?>)</span></label></th>
		<td>
		<?php
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Pre-selects user.
		$user_value = isset( $_GET['user_id'] ) ? intval( $_GET['user_id'] ) : null;
		if ( $users ) {
			?>
			<select name="user_id" id="user_id" class="input">
				<option value="""></option>
				<?php
				foreach ( $users as $user ) {
					echo '<option value="' . intval( $user->ID ) . '"';
					if ( $user_value === $user->ID ) {
						echo ' selected="selected"';
					}
					echo '>';
					echo esc_html( $user->display_name ) . ' (' . intval( $user->ID ) . ')';
					echo '</option>';
				}
				?>
			</select>
			<?php
		} else {
			echo '<input type="number" name="user_id" class="input" value="' . esc_attr( $user_value ) . '" size="20" placeholder="' . esc_attr__( 'User ID', 'sensei-lms' ) . '">';
		}
		?>
		</td>
	</tr>
		<tr class="form-field form-required">
			<th scope="row"><label for="course_id"><?php esc_html_e( 'Course', 'sensei-lms' ); ?> <span class="description">(<?php esc_html_e( 'Required', 'sensei-lms' ); ?>)</span></label></th>
			<td>
			<?php
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Pre-selects course.
			$course_value = isset( $_GET['course_id'] ) ? intval( $_GET['course_id'] ) : null;
			if ( $courses ) {
				?>
				<select name="course_id" id="course_id" class="input">
					<option value="""></option>
					<?php
					foreach ( $courses as $course ) {
						echo '<option value="' . intval( $course->ID ) . '"';
						if ( $course_value === $course->ID ) {
							echo ' selected="selected"';
						}
						echo '>';
						echo esc_html( $course->post_title ) . ' (' . intval( $course->ID ) . ')';
						echo '</option>';
					}
					?>
				</select>
				<?php
			} else {
				echo '<input type="number" name="course_id" class="input" value="' . esc_attr( $course_value ) . '" size="20" placeholder="' . esc_attr__( 'Course ID', 'sensei-lms' ) . '">';
			}
			?>
			</td>
		</tr>
	</table>
	<p class="submit">
		<input type="submit" class="button button-primary" name="submit" value="<?php esc_attr_e( 'View Enrollment Information', 'sensei-lms' ); ?>" />
	</p>
</form>
