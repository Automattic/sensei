<?php
/**
 * Shared lesson progress repository tests.
 *
 * @package sensei-tests
 */

namespace SenseiTest\Internal\Student_Progress\Lesson_Progress\Repositories;

use Sensei\Internal\Persistence_Exception;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress_Interface;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Interface;

require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-progress-insert-failure-helpers.php';

/**
 * Tests behavior shared by both lesson progress storage implementations.
 */
abstract class Lesson_Progress_Repository_Test extends \WP_UnitTestCase {
	use \Sensei_Progress_Insert_Failure_Helpers;

	/**
	 * Failed inserts must not return or cache progress in either store.
	 *
	 * @covers \Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Comments_Based_Lesson_Progress_Repository::create
	 * @covers \Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Tables_Based_Lesson_Progress_Repository::create
	 */
	public function testCreate_InsertFailed_ThrowsWithoutInvalidProgress(): void {
		$lesson_id  = self::factory()->post->create( array( 'post_type' => 'lesson' ) );
		$user_id    = self::factory()->user->create();
		$repository = $this->get_repository();

		$this->with_failed_cached_insert(
			$this->get_insert_table(),
			function () use ( $repository, $lesson_id, $user_id ) {
				$failure = null;
				try {
					$repository->create( $lesson_id, $user_id );
				} catch ( Persistence_Exception $exception ) {
					$failure = $exception;
				}

				self::assertInstanceOf( Persistence_Exception::class, $failure, 'A failed insert must report a persistence failure.' );
				self::assertNull( $repository->get( $lesson_id, $user_id ), 'A failed insert must leave no persisted or cached progress.' );
			}
		);
	}

	/**
	 * Orders lesson progress before applying the result limit.
	 *
	 * @covers \Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Comments_Based_Lesson_Progress_Repository::find
	 * @covers \Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Tables_Based_Lesson_Progress_Repository::find
	 * @dataProvider provideFindOrdering
	 *
	 * @param string $orderby Requested sort field.
	 * @param string $direction Requested sort direction.
	 * @param int    $expected_lesson_index Index of the lesson that should be returned.
	 */
	public function testFind_WhenOrderingWasRequested_ReturnsFirstMatchingLesson( string $orderby, string $direction, int $expected_lesson_index ): void {
		$repository = $this->get_repository();
		$lesson_ids = self::factory()->post->create_many( 3, array( 'post_type' => 'lesson' ) );
		$user_id    = self::factory()->user->create();
		$dates      = array( '2025-01-03 00:00:00', '2025-01-02 00:00:00', '2025-01-01 00:00:00' );

		// Create progress in a different order from the lesson IDs and timestamps.
		foreach ( array( 1, 0, 2 ) as $index ) {
			$progress = $repository->create( $lesson_ids[ $index ], $user_id );
			$this->set_progress_update_time( $progress->get_id(), $dates[ $index ] );
		}

		$results = $repository->find(
			array(
				'lesson_id' => $lesson_ids,
				'user_id'   => $user_id,
				'status'    => array( Lesson_Progress_Interface::STATUS_IN_PROGRESS ),
				'orderby'   => $orderby,
				'order'     => $direction,
				'number'    => 1,
			)
		);

		self::assertCount( 1, $results, 'The query should return one record.' );
		self::assertSame( $lesson_ids[ $expected_lesson_index ], $results[0]->get_lesson_id(), 'The query should limit results after sorting.' );
	}

	/**
	 * Provides sort fields, directions, and the first matching lesson for each.
	 *
	 * @return array
	 */
	public static function provideFindOrdering(): array {
		return array(
			'most recently updated first'  => array( 'updated_at', 'DESC', 0 ),
			'least recently updated first' => array( 'updated_at', 'ASC', 2 ),
			'lowest lesson ID first'       => array( 'lesson_id', 'ASC', 0 ),
			'highest progress ID first'    => array( 'id', 'DESC', 2 ),
		);
	}

	/**
	 * Construct the storage implementation under test.
	 *
	 * @return Lesson_Progress_Repository_Interface The repository.
	 */
	abstract protected function get_repository(): Lesson_Progress_Repository_Interface;

	/**
	 * Identify the physical table used for progress insertion.
	 *
	 * @return string Table name.
	 */
	abstract protected function get_insert_table(): string;

	/**
	 * Set the persisted update time of a test record.
	 *
	 * @param int    $progress_id Progress record ID.
	 * @param string $timestamp Update time.
	 */
	abstract protected function set_progress_update_time( int $progress_id, string $timestamp ): void;
}
