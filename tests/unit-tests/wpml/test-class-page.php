<?php

namespace SenseiTest\WPML;

use Sensei\WPML\Page;

class Page_Test extends \WP_UnitTestCase {

	public function testInit_WhenCalled_AddsFilters() {
		/* Arrange. */
		$page = new Page();

		/* Act. */
		$page->init();

		/* Assert. */
		$this->assertEquals( 10, has_filter( 'sensei_course_completed_page_id', array( $page, 'get_translated_course_completed_page_id' ) ) );
	}

	public function testGetTranslatedCourseCompletedPageId_WhenCalled_AddsFilter() {
		/* Arrange. */
		$page = new Page();

		$filter_applied = false;
		$filter_function = function ( $page_id ) use ( &$filter_applied ) {
			$filter_applied = true;
			return $page_id;
		};
		add_filter( 'wpml_object_id', $filter_function, 10, 1 );

		/* Act. */
		$page->get_translated_course_completed_page_id( 1 );

		/* Assert. */
		remove_filter( 'wpml_object_id', $filter_function, 10 );
		$this->assertTrue( $filter_applied );
	}

}
