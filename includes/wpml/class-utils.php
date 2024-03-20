<?php
/**
 * File containing the \Sensei\WPML\Utils class.
 *
 * @package sensei
 */

namespace Sensei\WPML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Utils
 *
 * Compatibility code with WPML.
 *
 * @since $$next-version$$
 *
 * @internal
 */
class Utils {

	/**
	 * Init hooks.
	 */
	public function init() {
		add_action( 'sensei_utils_check_for_activity_before_get_comments', array( $this, 'add_filter_query_not_filtered' ), 10, 0 );
		add_action( 'sensei_utils_check_for_activity_after_get_comments', array( $this, 'remove_filter_query_not_filtered' ), 10, 0 );
	}


	/**
	 * Add filter query not filtered.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 */
	public function add_filter_query_not_filtered() {
		add_filter( 'wpml_is_comment_query_filtered', '__return_false', 10, 0 );
	}

	/**
	 * Remove filter query not filtered.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 */
	public function remove_filter_query_not_filtered() {
		remove_filter( 'wpml_is_comment_query_filtered', '__return_false', 10 );
	}
}
