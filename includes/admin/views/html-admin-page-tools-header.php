<?php
/**
 * File containing header view for the tools page.
 *
 * @package sensei-lms
 * @since 3.7.0
 *
 * @var Sensei_Tool_Interface $tool     Active tool, if set.
 * @var array                 $messages Messages to show user.
 */

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="wrap">
	<h1>
		<?php
		esc_html_e( 'Tools', 'sensei-lms' );

		if ( ! empty( $tool ) ) {
			$back_url = admin_url( 'admin.php?page=sensei-tools' );
			?>
			<a href="<?php echo esc_url( $back_url ); ?>" class="button"><?php echo esc_html__( 'All Tools', 'sensei-lms' ); ?></a>
			<?php
		}
		?>
	</h1>
	<?php
	if ( ! empty( $tool ) ) {
		echo '<h2>' . esc_html( $tool->get_name() ) . '</h2>';
	}

	foreach ( $messages as $message ) {
		$div_class = 'notice below-h2 ';
		if ( ! empty( $message['is_error'] ) ) {
			$div_class .= 'notice-warning';
		} else {
			$div_class .= 'notice-info';
		}

		echo '<div class="' . esc_attr( $div_class ) . '"><p>';
		echo wp_kses_post( $message['message'] );
		echo '</p></div>';
	}

