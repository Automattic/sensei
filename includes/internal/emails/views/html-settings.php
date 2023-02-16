<?php
/**
 * File containing view for the tools page.
 *
 * @package sensei
 * @since $$next-version$$
 *
 * @var array              $options Array of Sensei settings and corresponding values.
 * @var array              $fields_to_display Array of Email settings to display.
 * @var Email_Settings_Tab $this Email_Settings_Tab instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$fields_to_display = isset( $fields_to_display ) ? $fields_to_display : [];
$options           = isset( $options ) ? $options : [];
?>
<form id="email-notification-settings-form" action="options.php" method="post">
	<?php settings_fields( 'sensei-settings' ); ?>
	<?php
	foreach ( $options as $option_name => $option_data ) {
		// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
		$this->form_field_hidden( $option_name, $option_data );
	}
	?>
	<table class="form-table">
		<?php foreach ( (array) $fields_to_display as $field ) { ?>
			<tr<?php echo $field['args']['class']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php if ( ! empty( $field['args']['label_for'] ) ) { ?>
					<th scope="row">
						<label for="<?php echo esc_attr( $field['args']['label_for'] ); ?>">
							<?php echo esc_html( $field['title'] ); ?>
						</label>
					</th>
				<?php } else { ?>
					<th scope="row">
						<?php echo esc_html( $field['title'] ); ?>
					</th>
				<?php } ?>
				<td>
					<?php call_user_func( $field['callback'], $field['args'] ); ?>
				</td>
			</tr>
		<?php } ?>
	</table>
	<?php submit_button(); ?>
</form>
