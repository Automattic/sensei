<?php
/**
 * File containing view for the tools page.
 *
 * @package sensei-lms
 * @since 3.7.0
 *
 * @var Sensei_Tool_Interface[] $tools Array of the tools.
 */

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require __DIR__ . '/html-admin-page-tools-header.php';
?>
<table id="sensei-tools" class="widefat striped" cellspacing="0">
	<tbody>
	<?php
	foreach ( $tools as $tool ) {
		?>
			<tr>
				<th>
					<div class="name"><?php echo esc_html( $tool->get_name() ); ?></div>
					<div class="description"><?php echo esc_html( $tool->get_description() ); ?></div>
					<?php
					/**
					 * Display additional information for a specific tool, such as status.
					 *
					 * @hook sensei_tools_listing_after_{$tool_id}
					 * @since 3.7.0
					 *
					 * @param {Sensei_Tool_Interface} $tool Tool object.
					 */
					do_action( "sensei_tools_listing_after_{$tool->get_id()}", $tool );
					?>
				</th>
				<td>
					<p>
						<?php
						$label = __( 'Visit Tool', 'sensei-lms' );
						$url   = Sensei_Tools::instance()->get_tool_url( $tool );
						if ( ! Sensei_Tools::instance()->is_interactive_tool( $tool ) ) {
							$label = __( 'Run Action', 'sensei-lms' );
						}

						if ( $tool->is_available() ) {
							echo '<a href="' . esc_url( $url ) . '" class="button button-large">' . esc_html( $label ) . '</a>';
						} else {
							$helper = __( 'This tool is not currently available', 'sensei-lms' );
							echo '<button class="button button-large" disabled="disabled" title="' . esc_attr( $helper ) . '">' . esc_html( $label ) . '</button>';
						}
						?>
					</p>
				</td>
			</tr>
			<?php
	}
	?>
	</tbody>
</table>
<?php
require __DIR__ . '/html-admin-page-tools-footer.php';
