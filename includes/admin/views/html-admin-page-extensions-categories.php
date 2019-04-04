<?php
/**
 * Admin View: Page - Extensions - Category Listings
 *
 * @var array $resources Object containing types and categories.
 *
 * @package Sensei\Extensions
 */

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable

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
		<?php esc_html_e( 'All', 'sensei-lms' ); ?>
	</a>
</li>
<?php
if ( ! empty( $resources->types ) ) {
	foreach ( $resources->types as $product_type ) {
		?>
		<li>
			<a class="<?php echo $current_type === $product_type->slug ? 'current' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'type' => $product_type->slug ), admin_url( 'admin.php?page=sensei-extensions' ) ) ); ?>">
				<?php echo esc_html( $product_type->label ); ?>
			</a>
		</li>
		<?php
	}
}
if ( ! empty( $resources->categories ) ) {
	foreach ( $resources->categories as $category ) {
		?>
		<li>
			<a class="<?php echo $current_category === $category->slug ? 'current' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'category' => $category->slug ), admin_url( 'admin.php?page=sensei-extensions' ) ) ); ?>">
				<?php echo esc_html( $category->label ); ?>
			</a>
		</li>
		<?php
	}
}
echo '</ul>';

