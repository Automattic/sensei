<?php
/**
 * Admin View: Page - Extensions - Product Results
 *
 * @package Sensei\Extensions
 */

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '<br class="clear" />';

if ( empty( $extensions ) ) {
	echo '<div class="notice notice-warning below-h2"><p><strong>' . esc_html__( 'No extensions were found.', 'sensei-lms' ) . '</strong></p></div>';
} else {
	echo '<ul class="products">';
	foreach ( $extensions as $extension ) {
		$url         = add_query_arg(
			array(
				'utm_source'   => str_replace( [ 'http://', 'https://' ], '', get_site_url() ),
				'utm_medium'   => 'extensions',
				'utm_campaign' => 'sensei-lms',
			),
			$extension->link
		);
		$installed   = Sensei_Plugins_Installation::instance()->is_plugin_active( $extension->plugin_file );
		$installable = ! empty( $extension->wccom_product_id ) || 'dotorg' === $extension->hosted_location;
		?>
		<li class="product">
			<div class="product-header">
				<?php if ( ! empty( $extension->image ) ) : ?>
					<img src="<?php echo esc_url( $extension->image ); ?>" />
				<?php endif; ?>
				<h2><?php echo esc_html( $extension->title ); ?></h2>
				<?php
				if ( 0 === $extension->price ) {
					echo '<div class="price free">' . esc_html__( 'Free', 'sensei-lms' ) . '</div>';
				} else {
					echo '<div class="price">' . esc_html( $extension->price ) . '</div>';
				}
				?>
			</div>
			<div class="product-body"><p class="excerpt">
					<?php echo esc_html( $extension->excerpt ); ?>
				</p>
			</div>
			<div class="product-buttons">
				<a href="<?php echo esc_url( $url, array( 'http', 'https' ) ); ?>" target="_blank"
					class=""><?php esc_html_e( 'More Details', 'sensei-lms' ); ?></a>
				<?php if ( $installed ) : ?>
					<button class="button-primary" disabled>
						<?php esc_html_e( 'Installed', 'sensei-lms' ); ?>
					</button>
				<?php elseif ( $installable ) : ?>
					<div class="sensei-extension-installer"
						data-slug="<?php echo esc_attr( $extension->wccom_product_id ? $extension->wccom_product_id : $extension->product_slug ); ?>"
						data-source="<?php echo $extension->wccom_product_id ? 'wccom' : 'wporg'; ?>">
						<button class="button-primary"><?php esc_html_e( 'Install', 'sensei-lms' ); ?></button>
					</div>
				<?php endif; ?>
			</div>

		</li>
		<?php
	}
	echo '</ul>';
}
