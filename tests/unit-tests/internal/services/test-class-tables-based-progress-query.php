<?php
/**
 * Tests for shared HPPS progress selections.
 *
 * @package sensei-tests
 */

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Tables_Based_Progress_Query;
use Sensei\Internal\Services\Utils;

/**
 * @covers \Sensei\Internal\Services\Tables_Based_Progress_Query
 */
class Tables_Based_Progress_Query_Test extends \WP_UnitTestCase {

	/**
	 * Quiz-aware eligibility applies to both selected rows and their count.
	 *
	 * @covers \Sensei\Internal\Services\Tables_Based_Progress_Query::build_selection
	 * @covers \Sensei\Internal\Services\Tables_Based_Progress_Query::build_pagination
	 */
	public function testBuildSelection_QuizStatusAndTemporaryUserExceptionGiven_CountsEligibleProgress(): void {
		global $wpdb;
		$factory = new \Sensei_Factory();
		$lesson  = $factory->lesson->create();
		$quiz    = $factory->quiz->create();
		update_post_meta( $lesson, '_lesson_quiz', $quiz );
		$registered = $factory->user->create();
		$ungraded   = $factory->user->create( array( 'user_login' => 'sensei_guest_ungraded' ) );
		$passed     = $factory->user->create( array( 'user_login' => 'sensei_guest_passed' ) );
		foreach ( array( $registered => 'graded', $ungraded => 'ungraded', $passed => 'passed' ) as $user => $status ) {
			foreach ( array( $lesson => array( 'lesson', 'complete' ), $quiz => array( 'quiz', $status ) ) as $post => $progress ) {
				$wpdb->insert(
					$wpdb->prefix . 'sensei_lms_progress',
					array(
						'post_id'      => $post,
						'user_id'      => $user,
						'type'         => $progress[0],
						'status'       => $progress[1],
						'started_at'   => '2022-01-01 00:00:00',
						'completed_at' => '2022-01-02 00:00:00',
						'created_at'   => '2022-01-01 00:00:00',
						'updated_at'   => '2022-01-02 00:00:00',
					)
				);
			}
		}
		$query = new Tables_Based_Progress_Query( $wpdb );

		$selection = $query->build_selection(
			array(
				'type'            => 'lesson',
				'post_id'         => $lesson,
				'status_source'   => 'quiz',
				'status'          => array( 'graded', 'ungraded', 'passed' ),
				'user_exclusions' => array(
					'exclude_user_login_prefixes' => Utils::REPORTS_EXCLUDED_USER_LOGIN_PREFIXES,
					'include_statuses_override'   => array( 'ungraded' ),
				),
			)
		);
		$pagination = $query->build_pagination( $selection, array() );
		$rows       = $wpdb->get_results( 'SELECT ' . $selection['fields'] . $selection['from'] . $selection['where'] );

		$this->assertSame( 2, $pagination['total_count'], 'Count includes the registered student and the ungraded temporary student.' );
		$this->assertEqualsCanonicalizing( array( $registered, $ungraded ), array_map( 'intval', wp_list_pluck( $rows, 'user_id' ) ), 'The selected progress has the same eligibility as its count.' );
		$this->assertEqualsCanonicalizing( array( 'graded', 'ungraded' ), wp_list_pluck( $rows, 'effective_status' ), 'Eligibility and display use quiz progress even without a submission.' );
	}
}
