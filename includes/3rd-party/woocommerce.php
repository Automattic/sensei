<?php
/**
 * Adds additional compatibility with WooCommerce.
 *
 * @package 3rd-Party
 */

/**
 * Allow Teachers to access the admin area when WooCommerce is installed.
 *
 * @param  bool $prevent_access Whether to prevent access to WP Admin.
 * @return bool
 */
function sensei_woocommerce_prevent_admin_access( $prevent_access ) {
	if ( current_user_can( 'manage_sensei_grades' ) ) {
		return false;
	}

	return $prevent_access;
}
add_filter( 'woocommerce_prevent_admin_access', 'sensei_woocommerce_prevent_admin_access' );
