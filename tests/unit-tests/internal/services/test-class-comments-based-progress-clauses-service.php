<?php

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Comments_Based_Progress_Clauses_Service;

/**
 * Class Comments_Based_Progress_Clauses_Service_Test.
 *
 * @covers \Sensei\Internal\Services\Comments_Based_Progress_Clauses_Service
 */
class Comments_Based_Progress_Clauses_Service_Test extends \WP_UnitTestCase {

	/**
	 * Get empty clauses array for testing.
	 *
	 * @return array
	 */
	private function get_empty_clauses(): array {
		return [
			'fields'   => '',
			'join'     => '',
			'where'    => '',
			'groupby'  => '',
			'orderby'  => '',
			'distinct' => '',
			'limits'   => '',
		];
	}

	public function testAddLastActivityToCourseClauses_WhenCalled_AddsLastActivityDateAndJoin(): void {
		/* Arrange. */
		global $wpdb;

		$service = new Comments_Based_Progress_Clauses_Service( $wpdb );
		$clauses = $this->get_empty_clauses();

		/* Act. */
		$clauses = $service->add_last_activity_to_courses_clauses( $clauses );

		/* Assert. */
		$this->assertStringContainsString( 'last_activity_date', $clauses['fields'], 'Expected last_activity_date in fields clause.' );
		$this->assertStringContainsString( 'LEFT JOIN', $clauses['join'], 'Expected LEFT JOIN in join clause.' );
		$this->assertStringContainsString( $wpdb->comments, $clauses['join'], 'Expected comments table in join clause.' );
	}

	public function testAddDaysToCompletionToCourseClauses_WhenCalled_AddsDaysCountJoinAndGroupBy(): void {
		/* Arrange. */
		global $wpdb;

		$service = new Comments_Based_Progress_Clauses_Service( $wpdb );
		$clauses = $this->get_empty_clauses();

		/* Act. */
		$clauses = $service->add_days_to_completion_to_courses_clauses( $clauses );

		/* Assert. */
		$this->assertStringContainsString( 'days_to_completion', $clauses['fields'], 'Expected days_to_completion in fields clause.' );
		$this->assertStringContainsString( 'count_of_completions', $clauses['fields'], 'Expected count_of_completions in fields clause.' );
		$this->assertStringContainsString( 'LEFT JOIN', $clauses['join'], 'Expected LEFT JOIN in join clause.' );
		$this->assertNotEmpty( $clauses['groupby'], 'Expected non-empty groupby clause.' );
	}

	public function testFilterCoursesByLastActivity_WithFromDate_AddsWhereClause(): void {
		/* Arrange. */
		global $wpdb;

		$service = new Comments_Based_Progress_Clauses_Service( $wpdb );
		$clauses = $this->get_empty_clauses();

		/* Act. */
		$clauses = $service->filter_courses_by_last_activity( $clauses, '2026-01-01', '' );

		/* Assert. */
		$this->assertStringContainsString( 'comment_date_gmt >=', $clauses['where'] );
	}

	public function testFilterCoursesByLastActivity_WithToDate_AddsWhereClause(): void {
		/* Arrange. */
		global $wpdb;

		$service = new Comments_Based_Progress_Clauses_Service( $wpdb );
		$clauses = $this->get_empty_clauses();

		/* Act. */
		$clauses = $service->filter_courses_by_last_activity( $clauses, '', '2026-12-31' );

		/* Assert. */
		$this->assertStringContainsString( 'comment_date_gmt <=', $clauses['where'] );
	}

	public function testFilterCoursesByLastActivity_WithBothDates_AddsBothConditions(): void {
		/* Arrange. */
		global $wpdb;

		$service = new Comments_Based_Progress_Clauses_Service( $wpdb );
		$clauses = $this->get_empty_clauses();

		/* Act. */
		$clauses = $service->filter_courses_by_last_activity( $clauses, '2026-01-01', '2026-12-31' );

		/* Assert. */
		$this->assertStringContainsString( 'comment_date_gmt >=', $clauses['where'], 'Expected from-date condition in where clause.' );
		$this->assertStringContainsString( 'comment_date_gmt <=', $clauses['where'], 'Expected to-date condition in where clause.' );
	}

	public function testFilterCoursesByLastActivity_WithNoDate_LeavesWhereUnchanged(): void {
		/* Arrange. */
		global $wpdb;

		$service = new Comments_Based_Progress_Clauses_Service( $wpdb );
		$clauses = $this->get_empty_clauses();

		/* Act. */
		$clauses = $service->filter_courses_by_last_activity( $clauses, '', '' );

		/* Assert. */
		$this->assertSame( '', $clauses['where'] );
	}

	public function testAddLastActivityToLessonsClauses_WhenCalled_AddsLastActivityDateField(): void {
		/* Arrange. */
		global $wpdb;

		$service = new Comments_Based_Progress_Clauses_Service( $wpdb );
		$clauses = $this->get_empty_clauses();

		/* Act. */
		$clauses = $service->add_last_activity_to_lessons_clauses( $clauses );

		/* Assert. */
		$this->assertStringContainsString( 'last_activity_date', $clauses['fields'], 'Expected last_activity_date in fields clause.' );
		$this->assertStringContainsString( $wpdb->comments, $clauses['fields'], 'Expected comments table in fields clause.' );
		$this->assertStringContainsString( 'sensei_lesson_status', $clauses['fields'], 'Expected lesson status comment type in fields clause.' );
	}

	public function testAddDaysToCompletionToLessonsClauses_WhenCalled_AddsDaysToCompleteField(): void {
		/* Arrange. */
		global $wpdb;

		$service = new Comments_Based_Progress_Clauses_Service( $wpdb );
		$clauses = $this->get_empty_clauses();

		/* Act. */
		$clauses = $service->add_days_to_completion_to_lessons_clauses( $clauses );

		/* Assert. */
		$this->assertStringContainsString( 'days_to_complete', $clauses['fields'], 'Expected days_to_complete in fields clause.' );
		$this->assertStringContainsString( $wpdb->comments, $clauses['fields'], 'Expected comments table in fields clause.' );
		$this->assertStringContainsString( $wpdb->commentmeta, $clauses['fields'], 'Expected commentmeta table in fields clause.' );
	}
}
