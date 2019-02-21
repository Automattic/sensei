<?php
/**
 * Admin View: Page - Extensions - Category Listings
 *
 * @package Sensei\Extensions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


// phpcs:ignore WordPress.Security.NonceVerification
$current_category = isset( $_GET['category'] ) ? $_GET['category'] : '_all';

// phpcs:ignore WordPress.Security.NonceVerification
$current_type = isset( $_GET['type'] ) ? $_GET['type'] : '';

if ( $current_type ) {
	$current_category = '';
}

echo '<ul class="subsubsub">';
?>
<li>
	<a class="<?php echo '_all' === $current_category ? 'current' : ''; ?>"
		href="<?php echo esc_url( add_query_arg( [ 'category' => '_all' ], admin_url( 'admin.php?page=sensei-extensions' ) ) ); ?>"
	>
		<?php esc_html_e( 'All', 'woothemes-sensei' ); ?>
	</a>
</li>
<li>
	<a class="<?php echo 'plugin' === $current_type ? 'current' : ''; ?>" href="<?php echo esc_url( add_query_arg( [ 'type' => 'plugin' ], admin_url( 'admin.php?page=sensei-extensions' ) ) ); ?>">
		<?php esc_html_e( 'All Plugins', 'woothemes-sensei' ); ?>
	</a>
</li>
<li>
	<a class="<?php echo 'theme' === $current_type ? 'current' : ''; ?>" href="<?php echo esc_url( add_query_arg( [ 'type' => 'theme' ], admin_url( 'admin.php?page=sensei-extensions' ) ) ); ?>">
		<?php esc_html_e( 'All Themes', 'woothemes-sensei' ); ?>
	</a>
</li>
<?php
foreach ( $categories as $category ) {
	?>
	<li>
		<a class="<?php echo $current_category === $category->slug ? 'current' : ''; ?>" href="<?php echo esc_url( add_query_arg( [ 'category' => $category->slug ], admin_url( 'admin.php?page=sensei-extensions' ) ) ); ?>">
			<?php echo esc_html( $category->label ); ?>
		</a>
	</li>
	<?php
}
echo '</ul>';

