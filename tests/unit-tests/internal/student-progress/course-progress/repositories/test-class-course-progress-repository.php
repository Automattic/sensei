<?php
/**
 * Shared course progress repository failure coverage.
 *
 * @package sensei-tests
 */

namespace SenseiTest\Internal\Student_Progress\Course_Progress\Repositories;

use Sensei\Internal\Persistence_Exception;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Comments_Based_Course_Progress_Repository;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Interface;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Tables_Based_Course_Progress_Repository;

require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-progress-insert-failure-helpers.php';

/**
 * Required course creation has the same failure contract in both stores.
 */
abstract class Course_Progress_Repository_Test extends \WP_UnitTestCase {
	use \Sensei_Progress_Insert_Failure_Helpers;

	/**
	 * Failed inserts must not return or cache progress in either store.
	 *
	 * @covers \Sensei\Internal\Student_Progress\Course_Progress\Repositories\Comments_Based_Course_Progress_Repository::create
	 * @covers \Sensei\Internal\Student_Progress\Course_Progress\Repositories\Tables_Based_Course_Progress_Repository::create
	 */
	public function testCreate_InsertFailed_ThrowsWithoutInvalidProgress(): void {
		global $wpdb;
		$course_id  = self::factory()->post->create( [ 'post_type' => 'course' ] );
		$user_id    = self::factory()->user->create();
		$repository = $this->get_repository();

		$this->with_failed_cached_insert( $this->get_insert_table(), function () use ( $wpdb, $repository, $course_id, $user_id ) {
			$failure = null;
			try {
				$repository->create( $course_id, $user_id );
			} catch ( Persistence_Exception $exception ) {
				$failure = $exception;
			}

			self::assertInstanceOf( Persistence_Exception::class, $failure, 'A failed insert must report a persistence failure.' );
			self::assertNull( ( new Tables_Based_Course_Progress_Repository( $wpdb ) )->get( $course_id, $user_id ), 'A failed operation must leave no table model or cached model.' );
			self::assertNull( ( new Comments_Based_Course_Progress_Repository() )->get( $course_id, $user_id ), 'A failed insert must leave no comments progress.' );
		} );
	}

	/**
	 * Construct the storage implementation under test.
	 *
	 * @return Course_Progress_Repository_Interface The repository.
	 */
	abstract protected function get_repository(): Course_Progress_Repository_Interface;

	/**
	 * Identify the physical table used for progress insertion.
	 *
	 * @return string Table name.
	 */
	abstract protected function get_insert_table(): string;

}
