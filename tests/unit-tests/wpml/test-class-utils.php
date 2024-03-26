<?php

namespace SenseiTest\WPML;

use Sensei\WPML\Utils;

class Utils_Test extends \WP_UnitTestCase {

	public function testInit_WhenCalled_AddsFilters() {
		/* Arrange. */
		$utils = new Utils();

		/* Act. */
		$utils->init();

		/* Assert. */
		$this->assertEquals( 10, has_filter( 'sensei_utils_check_for_activity_before_get_comments', array( $utils, 'add_filter_query_not_filtered' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_utils_check_for_activity_after_get_comments', array( $utils, 'remove_filter_query_not_filtered' ) ) );
	}

	public function testAddFilterQueryNotFiltered_WhenCalled_AddsFilter() {
		/* Arrange. */
		$utils = new Utils();

		/* Act. */
		$utils->add_filter_query_not_filtered();

		/* Assert. */
		$this->assertEquals( 10, has_filter( 'wpml_is_comment_query_filtered', '__return_false' ) );
	}

	public function testRemoveFilterQueryNotFiltered_WhenCalled_RemovesFilter() {
		/* Arrange. */
		$utils = new Utils();
		$utils->add_filter_query_not_filtered();

		/* Act. */
		$utils->remove_filter_query_not_filtered();

		/* Assert. */
		$this->assertFalse( has_filter( 'wpml_is_comment_query_filtered', '__return_false' ) );
	}
}
