<?php
/**
 * File containing the Sensei_Reports_Overview_Service_Students class.
 *
 * @package sensei
 */

use Sensei\Internal\Services\Grading_Stats_Service_Interface;
use Sensei\Internal\Services\Progress_Aggregation_Service_Interface;
use Sensei\Internal\Services\Progress_Query_Service_Factory;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Service (business-logic) class for the Students tab of Reports → Overview.
 *
 * Calculates the figures shown there, so the list table that displays them
 * holds no calculation logic of its own.
 *
 * @since 4.4.1
 */
class Sensei_Reports_Overview_Service_Students {

	/**
	 * The progress aggregation service.
	 *
	 * @var Progress_Aggregation_Service_Interface
	 */
	private Progress_Aggregation_Service_Interface $aggregation_service;

	/**
	 * The grading stats service.
	 *
	 * @var Grading_Stats_Service_Interface
	 */
	private Grading_Stats_Service_Interface $grading_stats_service;

	/**
	 * Constructor.
	 *
	 * @since $$next-version$$
	 *
	 * @param Progress_Aggregation_Service_Interface|null $aggregation_service   Progress aggregation service.
	 * @param Grading_Stats_Service_Interface|null        $grading_stats_service Grading stats service.
	 */
	public function __construct( ?Progress_Aggregation_Service_Interface $aggregation_service = null, ?Grading_Stats_Service_Interface $grading_stats_service = null ) {
		$this->aggregation_service   = $aggregation_service ?? ( new Progress_Query_Service_Factory() )->create_aggregation_service();
		$this->grading_stats_service = $grading_stats_service ?? ( new Progress_Query_Service_Factory() )->create_grading_stats_service();
	}

	/**
	 * Get average grade of all lessons graded in all the courses filtered by students.
	 *
	 * @since 4.4.1
	 * @access public
	 *
	 * @param array $user_ids user ids.
	 * @return double $graded_lesson_average_grade Average value of all the graded lessons in all the courses.
	 */
	public function get_graded_lessons_average_grade( $user_ids ) {
		if ( empty( $user_ids ) ) {
			return 0;
		}

		return ceil( $this->grading_stats_service->get_users_average_grade( $user_ids ) );
	}

	/**
	 * Get the active and completed course counts for each of the given students.
	 *
	 * @since $$next-version$$
	 *
	 * @param int[] $user_ids Student user IDs.
	 * @return array<int, array{active:int, completed:int}> Map of user_id => [ active, completed ].
	 */
	public function get_course_counts_by_user( array $user_ids ): array {
		if ( empty( $user_ids ) ) {
			return array();
		}

		$counts = $this->aggregation_service->count_statuses_by_user(
			array(
				'type'    => 'course',
				'user_id' => $user_ids,
			)
		);

		$result = array();
		foreach ( $user_ids as $user_id ) {
			$statuses           = $counts[ $user_id ] ?? array();
			$completed          = $statuses['complete'] ?? 0;
			$result[ $user_id ] = array(
				'active'    => array_sum( $statuses ) - $completed,
				'completed' => $completed,
			);
		}

		return $result;
	}

	/**
	 * Get the active and completed course totals across all the given students.
	 *
	 * Powers the Active/Completed Courses column headers.
	 *
	 * @since $$next-version$$
	 *
	 * @param int[] $user_ids Student user IDs.
	 * @return array{active:int, completed:int} Totals for active and completed courses.
	 */
	public function get_total_course_counts( array $user_ids ): array {
		if ( empty( $user_ids ) ) {
			return array(
				'active'    => 0,
				'completed' => 0,
			);
		}

		$counts = $this->aggregation_service->count_statuses(
			array(
				'type'    => 'course',
				'user_id' => $user_ids,
			)
		);

		$completed = $counts['complete'] ?? 0;

		return array(
			'active'    => array_sum( $counts ) - $completed,
			'completed' => $completed,
		);
	}
}
