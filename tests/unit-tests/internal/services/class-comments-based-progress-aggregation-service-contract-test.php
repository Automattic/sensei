<?php

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Comments_Based_Progress_Aggregation_Service;
use Sensei\Internal\Services\Progress_Aggregation_Service_Interface;

require_once __DIR__ . '/../../../framework/class-progress-aggregation-service-contract-test-abstract.php';

/**
 * Courses Overview aggregation behavior using comments storage.
 *
 * @covers \Sensei\Internal\Services\Comments_Based_Progress_Aggregation_Service
 */
class Comments_Based_Progress_Aggregation_Service_Contract_Test extends Progress_Aggregation_Service_Contract_Test_Abstract {
	protected function get_service(): Progress_Aggregation_Service_Interface {
		global $wpdb;
		return new Comments_Based_Progress_Aggregation_Service( $wpdb );
	}

	protected function seed_progress( int $post_id, int $user_id, string $type, string $status, ?string $started_at = '2022-01-01 00:00:00', ?string $completed_at = '2022-01-02 00:00:00' ): void {
		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'  => $post_id,
				'user_id'          => $user_id,
				'comment_type'     => 'course' === $type ? 'sensei_course_status' : 'sensei_lesson_status',
				'comment_approved' => $status,
				'comment_date'     => $completed_at,
			)
		);
		if ( null !== $started_at ) {
			update_comment_meta( $comment_id, 'start', $started_at );
		}
	}
}
