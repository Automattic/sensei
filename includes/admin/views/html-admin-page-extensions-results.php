<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '<br class="clear" />';

if ( empty( $extensions ) ) {
	echo '<div class="notice notice-warning below-h2"><p><strong>' . esc_html__( 'No extensions were found.', 'woothemes-sensei' ) . '</strong></p></div>';
} else {
	echo '<ul class="products">';
	foreach ( $extensions as $extension ) {
		$url = add_query_arg(
			array(
				'utm_source'   => 'product',
				'utm_medium'   => 'extensionpage',
				'utm_campaign' => 'sensei',
				'utm_content'  => 'listing',
			),
			$extension->link
		);
		?>
		<li class="product">
			<div class="product-header">
				<?php if ( ! empty( $extension->image ) ) : ?>
					<img src="<?php echo esc_url( $extension->image ); ?>" />
				<?php endif; ?>
				<h2><?php echo esc_html( $extension->title ); ?></h2>
				<?php
				if ( 0 === $extension->price ) {
					echo '<div class="price free">' . esc_html__( 'Free', 'woothemes-sensei' ) . '</div>';
				} else {
					echo '<div class="price">' . esc_html( $extension->price ) . '</div>';
				}
				?>
			</div>
			<p class="buttons">
				<a href="<?php echo esc_url( $url, array( 'http', 'https' ) ); ?>" class="button-primary"><?php esc_html_e( 'More Details', 'woothemes-sensei' ); ?></a>
			</p>
			<p class="excerpt">
				<?php echo esc_html( $extension->excerpt ); ?>
			</p>
		</li>
		<?php
	}
	echo '</ul>';
}
