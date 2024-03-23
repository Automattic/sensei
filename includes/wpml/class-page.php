<?php
/**
 * File containing the \Sensei\WPML\Page class.
 *
 * @package sensei
 */

namespace Sensei\WPML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Page
 *
 * Compatibility code with WPML.
 *
 * @since $$next-version$$
 *
 * @internal
 */
class Page {
	/**
	 * Init hooks.
	 */
	public function init() {
		add_filter( 'sensei_course_completed_page_id', array( $this, 'get_translated_course_completed_page_id' ), 10, 1 );
	}

	/**
	 * Get translated course completed page ID.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 *
	 * @param int $page_id Page ID.
	 * @return int Translated page ID.
	 */
	public function get_translated_course_completed_page_id( $page_id ) {
		return apply_filters( 'wpml_object_id', $page_id, 'page', true );
	}
}
