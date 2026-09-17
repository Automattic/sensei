<?php

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Tables_Based_Progress_Aggregation_Service;
use Sensei\Internal\Services\Progress_Aggregation_Service_Interface;

require_once __DIR__ . '/../../../framework/class-progress-aggregation-service-contract-test-abstract.php';

/**
 * Courses Overview aggregation behavior using tables storage.
 *
 * @covers \Sensei\Internal\Services\Tables_Based_Progress_Aggregation_Service
 */
class Tables_Based_Progress_Aggregation_Service_Contract_Test extends Progress_Aggregation_Service_Contract_Test_Abstract {
	protected function get_service(): Progress_Aggregation_Service_Interface {
		global $wpdb;
		return new Tables_Based_Progress_Aggregation_Service( $wpdb );
	}

	protected function seed_progress( int $post_id, int $user_id, string $type, string $status, ?string $started_at = '2022-01-01 00:00:00', ?string $completed_at = '2022-01-02 00:00:00' ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Shared contract fixture.
		$wpdb->insert(
			$wpdb->prefix . 'sensei_lms_progress',
			array(
				'post_id'      => $post_id,
				'user_id'      => $user_id,
				'type'         => $type,
				'status'       => $status,
				'started_at'   => null === $started_at ? null : get_gmt_from_date( $started_at ),
				'completed_at' => null === $completed_at || in_array( $status, array( 'in-progress', 'ungraded', 'failed' ), true ) ? null : get_gmt_from_date( $completed_at ),
				'created_at'   => current_time( 'mysql', true ),
				'updated_at'   => current_time( 'mysql', true ),
			)
		);
	}
	protected function seed_lesson_with_quiz_status( int $lesson_id, int $quiz_id, int $user_id, string $lesson_status, string $quiz_status ): void {
		$this->seed_progress( $lesson_id, $user_id, 'lesson', $lesson_status );
		$this->seed_progress( $quiz_id, $user_id, 'quiz', $quiz_status );
	}
}
