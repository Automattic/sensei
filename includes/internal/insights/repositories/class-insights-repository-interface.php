<?php
/**
 * File containing the Insights_Repository_Interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Insights\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Insights_Repository_Interface.
 *
 * Aggregate queries for the Course Insights feature. Implementations must
 * produce identical output from equivalent data regardless of storage
 * backend (HPPS tables vs. comments).
 *
 * See docs/plans/2026-04-22-course-insights-api-contract.md for the shape
 * of each returned array.
 *
 * @internal
 *
 * @since $$next-version$$
 */
interface Insights_Repository_Interface {
	/**
	 * Get a list of course summaries for the overview view.
	 *
	 * @internal
	 *
	 * @param string $sort       One of 'enrollment_desc', 'completion_asc', 'completion_desc', 'title_asc'.
	 * @param int    $limit      Maximum number of rows to return.
	 * @param string $window     One of 'all', '12m', '90d', '30d'.
	 * @param int[]  $course_ids Optional filter to a specific set of course IDs (empty array = all visible courses).
	 * @return array[] Array of course summary rows.
	 */
	public function get_course_summaries( string $sort, int $limit, string $window, array $course_ids = [] ): array;

	/**
	 * Get the detail payload for a single course (summary, funnel, lessons).
	 *
	 * Does not include the cohort trend; use {@see get_course_cohort_trend()}.
	 *
	 * @internal
	 *
	 * @param int    $course_id Course post ID.
	 * @param string $window    One of 'all', '12m', '90d', '30d'.
	 * @return array Course detail payload.
	 */
	public function get_course_detail( int $course_id, string $window ): array;

	/**
	 * Get the detail payload for a single lesson.
	 *
	 * @internal
	 *
	 * @param int    $course_id Course post ID.
	 * @param int    $lesson_id Lesson post ID.
	 * @param string $window    One of 'all', '12m', '90d', '30d'.
	 * @return array Lesson detail payload.
	 */
	public function get_lesson_detail( int $course_id, int $lesson_id, string $window ): array;

	/**
	 * Get the detail payload for a single quiz (summary, per-question, score distribution).
	 *
	 * @internal
	 *
	 * @param int    $course_id Course post ID.
	 * @param int    $quiz_id   Quiz post ID.
	 * @param string $window    One of 'all', '12m', '90d', '30d'.
	 * @return array Quiz detail payload.
	 */
	public function get_quiz_detail( int $course_id, int $quiz_id, string $window ): array;

	/**
	 * Get the last-12-months cohort trend for a course.
	 *
	 * Always returns exactly 12 entries, most-recent-last. Months with no
	 * enrollments are still present with null completed_pct.
	 *
	 * @internal
	 *
	 * @param int $course_id Course post ID.
	 * @return array[] Array of 12 monthly cohort rows.
	 */
	public function get_course_cohort_trend( int $course_id ): array;
}
