<?php
/*
 * TODO: These are stub classes to make the tests pass while developing 2.0.
 * Before 2.0 is released, these should be removed.
 */
class Sensei_WC {
	public static function is_woocommerce_active() { return false; }
	public static function is_woocommerce_present() { return false; }
	public static function load_woocommerce_integration_hooks() {}
}
class Sensei_WC_Memberships {
	public static function is_wc_memberships_active() { return false; }
	public static function load_wc_memberships_integration_hooks() {}
}
class Sensei_WC_Subscriptions {
	public static function load_wc_subscriptions_integration_hooks() {}
}
