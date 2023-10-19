<?php
/**
 * File containing the form for Progress_Tables_Eraser.
 *
 * @package sensei
 *
 * @var string $tool_id Tool ID for this tool.
 */

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<form method="post" action="">
	<?php wp_nonce_field( \Sensei\Internal\Tools\Progress_Tables_Eraser::NONCE_ACTION, '_wpnonce', false ); ?>
	<input type="hidden" name="delete-tables" value="yes">
	<input type="hidden" name="page" value="sensei-tools">
	<input type="hidden" name="tool" value="<?php echo esc_attr( $tool_id ); ?>">
	<div>
		<?php esc_html_e( 'This tool will delete all progress tables from the database. This action cannot be undone.', 'sensei-lms' ); ?>
	</div>
	<p class="confirm">
		<input
			type="checkbox"
			name="confirm"
			value="yes"
			id="sensei-tools-progress-tables-eraser-confirm"
		/>
		<label for="sensei-tools-progress-tables-eraser-confirm">
			<?php esc_html_e( 'I understand this action cannot be undone.', 'sensei-lms' ); ?>
		</label>
	<p class="submit">
		<input
			type="submit"
			class="button button-primary"
			name="submit"
			value="<?php esc_attr_e( 'Delete Progress Tables', 'sensei-lms' ); ?>"
			confirm="<?php esc_attr_e( 'Are you sure you want to delete all progress tables?', 'sensei-lms' ); ?>"
		/>
	</p>
</form>
