<?php
/**
 * File containing the Progress_Clauses_Service_Interface interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Interface Progress_Clauses_Service_Interface.
 *
 * Provides methods to modify WP_Query clauses for progress-related data
 * (last activity, days to completion) in course reports.
 *
 * @internal
 *
 * @since 4.26.0
 */
interface Progress_Clauses_Service_Interface {

	/**
	 * Modify WP_Query clauses to add last activity date to course posts.
	 *
	 * @since 4.26.0
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_last_activity_to_courses_clauses( array $clauses ): array;

	/**
	 * Modify WP_Query clauses to add days-to-completion data to course posts.
	 *
	 * @since 4.26.0
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_days_to_completion_to_courses_clauses( array $clauses ): array;

	/**
	 * Modify WP_Query clauses to filter courses by last activity date range.
	 *
	 * Note: `add_last_activity_to_courses_clauses` must be applied first, as this
	 * method references the `la` alias it creates.
	 *
	 * @since 4.26.0
	 *
	 * @param array  $clauses Associative array of the clauses for the query.
	 * @param string $from    Start date for filtering (empty string for no start date).
	 * @param string $to      End date for filtering (empty string for no end date).
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function filter_courses_by_last_activity( array $clauses, string $from = '', string $to = '' ): array;

	/**
	 * Modify WP_Query clauses to add last activity date to lesson posts.
	 *
	 * @since 4.26.0
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_last_activity_to_lessons_clauses( array $clauses ): array;

	/**
	 * Modify WP_Query clauses to add days-to-complete data to lesson posts.
	 *
	 * @since 4.26.0
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_days_to_completion_to_lessons_clauses( array $clauses ): array;
}
