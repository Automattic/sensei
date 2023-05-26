<?php
/**
 * File containing view for the tools page.
 *
 * @package sensei
 * @since 4.12.0
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
		<tr><td></td></tr>
		<tr>
			<th scope="row">
				<?php
				esc_html_e( 'MailPoet', 'sensei-lms' );
				echo Sensei()->assets->get_icon( 'mailpoet-logo', 'sensei-mailpoet-icon' ); // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Dynamic parts escaped in the method.
				?>
			</th>
			<td>
				<p class="sensei-settings__description--small">
					<?php esc_html_e( 'Send an email to all students in a course or a group.', 'sensei-lms' ); ?>
				</p>
				<div class="sensei-link-navigation">
					<?php if ( is_plugin_active( 'mailpoet/mailpoet.php' ) ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=mailpoet-segments#/lists' ) ); ?>" target="_blank">
							<?php esc_html_e( 'MailPoet Lists', 'sensei-lms' ); ?>
						</a>
					<?php else : ?>
						<a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=mailpoet&tab=search&type=term' ) ); ?>">
							<?php esc_html_e( 'Install MailPoet', 'sensei-lms' ); ?>
						</a>
						<a href="https://www.mailpoet.com/" target="_blank">
							<?php esc_html_e( 'Learn More', 'sensei-lms' ); ?>
						</a>
					<?php endif ?>
				</div>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<?php
				esc_html_e( 'AutomateWoo', 'sensei-lms' );
				echo Sensei()->assets->get_icon( 'woo-logo', 'sensei-woo-icon' ); // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Dynamic parts escaped in the method.
				?>
			</th>
			<td>
				<p class="sensei-settings__description--small">
					<?php esc_html_e( 'Create automated marketing email flows based on course progress and WooCommerce transactions.', 'sensei-lms' ); ?>
				</p>
				<div class="sensei-link-navigation">
					<?php if ( is_plugin_active( 'automatewoo/automatewoo.php' ) ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=automatewoo-settings' ) ); ?>" target="_blank">
							<?php esc_html_e( 'AutomateWoo Settings', 'sensei-lms' ); ?>
						</a>
					<?php else : ?>
						<a href="https://woocommerce.com/products/automatewoo/?utm_source=sensei&utm_medium=referral" target="_blank">
							<?php esc_html_e( 'Get AutomateWoo', 'sensei-lms' ); ?>
						</a>
						<a href="https://automatewoo.com/" target="_blank">
							<?php esc_html_e( 'Learn More', 'sensei-lms' ); ?>
						</a>
					<?php endif ?>
				</div>
			</td>
		</tr>
	</table>
	<?php submit_button(); ?>
</form>
