<?php

/**
 * Tests for Sensei_Course_List_Categories_Filter class.
 *
 * @group course-structure
 */
class Sensei_Course_List_Categories_Filter_Test extends WP_UnitTestCase {
	/**
	 * Factory for setting up testing data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	public function set_up(): void {
		parent::set_up();
		$this->factory = new Sensei_Factory();
	}

	public function tear_down(): void {
		$this->factory->tearDown();
		parent::tear_down();
	}

	public function testGetCourseIdsToBeExcluded_CourseHiddenFromFilteredQueries_LeavesItOut() {
		/* Arrange. */
		$category                 = $this->factory->course_category->create_and_get();
		$hidden_course_id         = $this->factory->course->create();
		$in_category_course_id    = $this->factory->course->create();
		$other_category_course_id = $this->factory->course->create();

		$this->factory->course_category->add_post_terms( $in_category_course_id, array( $category->term_id ), 'course-category' );
		$this->hide_course_from_filtered_queries( $hidden_course_id );
		$_GET['course-list-category-filter-13'] = $category->term_id;

		$filter = new Sensei_Course_List_Categories_Filter();

		/* Act. */
		$actual = $filter->get_course_ids_to_be_excluded( 13 );

		/* Assert. */
		$this->assertSame( array( $other_category_course_id ), array_values( $actual ) );
	}

	/**
	 * Hide a course from every post query that runs with filters, the way a
	 * multilingual plugin hides the courses of other languages.
	 *
	 * @param int $course_id Course ID.
	 */
	private function hide_course_from_filtered_queries( int $course_id ): void {
		add_filter(
			'posts_where',
			function ( $where ) use ( $course_id ) {
				global $wpdb;

				return $where . $wpdb->prepare( " AND {$wpdb->posts}.ID != %d", $course_id );
			}
		);
	}
}
