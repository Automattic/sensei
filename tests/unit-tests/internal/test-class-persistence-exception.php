<?php
/**
 * Progress failure diagnostics through the configured PHP error log.
 *
 * @package sensei-tests
 */
namespace SenseiTest\Internal;

use Sensei\Internal\Persistence_Exception;

require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-progress-insert-failure-helpers.php';

/**
 * Verifies critical progress-failure logging behavior.
 *
 * @covers Sensei\Internal\Persistence_Exception
 */
class Persistence_Exception_Test extends \WP_UnitTestCase {
	use \Sensei_Progress_Insert_Failure_Helpers;

	/**
	 * Temporary PHP log destination.
	 *
	 * @var string
	 */
	private $log_path;
	/**
	 * Previous PHP log destination.
	 *
	 * @var string
	 */
	private $previous_log;

	public function setUp(): void {
		parent::setUp();
		$this->log_path     = wp_tempnam( 'sensei-persistence' );
		$this->previous_log = ini_get( 'error_log' );
		// phpcs:ignore WordPress.PHP.IniSet.Risky -- Capture the real PHP error log for this test.
		ini_set( 'error_log', $this->log_path );
	}

	public function tearDown(): void {
		$this->restore_progress_insert_failure();
		$this->restore_progress_storage_mode();
		// phpcs:ignore WordPress.PHP.IniSet.Risky -- Restore the PHP configuration changed during setup.
		ini_set( 'error_log', $this->previous_log );
		wp_delete_file( $this->log_path );
		parent::tearDown();
	}

	/**
	 * Logging a real insert failure preserves its result and identifies the affected records.
	 *
	 * @dataProvider progress_storage_modes
	 * @covers Sensei\Internal\Persistence_Exception::log
	 */
	public function testLog_PrimaryInsertFailed_PreservesFailureAndIdentifiesRecords( string $mode ): void {
		$lesson_id = self::factory()->post->create( array( 'post_type' => 'lesson' ) );
		$user_id   = self::factory()->user->create();
		$this->with_failed_progress_insert(
			$mode,
			'lesson',
			function () use ( $lesson_id, $user_id, $mode ) {
				$backend = 'tables' === $mode ? 'tables' : 'comments';

				$actual = \Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read the local PHP log captured by this test.
				$log = file_get_contents( $this->log_path );
				self::assertFalse( $actual, 'Logging must preserve the failed operation result.' );
				self::assertMatchesRegularExpression( '/Failed storage\W+' . $backend . '\b/i', $log, 'The failed backend must be identifiable.' );
				self::assertMatchesRegularExpression( '/Lesson ID\W+' . $lesson_id . '\b/i', $log, 'The affected lesson must be identifiable.' );
				self::assertMatchesRegularExpression( '/Student ID\W+' . $user_id . '\b/i', $log, 'The affected student must be identifiable.' );
			}
		);
	}

	/**
	 * A secondary failure identifies its backend and is logged once across callers.
	 *
	 * @dataProvider dual_write_storage_modes
	 * @covers Sensei\Internal\Persistence_Exception::log
	 */
	public function testLog_SecondaryCourseInsertFailed_RecordsBackendAndLogsOnce( string $mode, string $secondary ): void {
		$course_id = self::factory()->post->create( array( 'post_type' => 'course' ) );
		$user_id   = self::factory()->user->create();
		$this->with_failed_progress_insert(
			$mode,
			'course',
			function () use ( $course_id, $user_id, $secondary ) {
				try {
					Sensei()->course_progress_repository->create( $course_id, $user_id );
					self::fail( 'The existing secondary exception must still propagate.' );
				} catch ( Persistence_Exception $exception ) {
					$exception->log( 'starting a course' );
					$exception->log( 'handling the same failure' );
				}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read the local PHP log captured by this test.
				$log = file_get_contents( $this->log_path );
				self::assertMatchesRegularExpression( '/Failed storage\W+' . $secondary . '\b/i', $log, 'The secondary failure must identify the actual failed backend.' );
				self::assertCount( 1, file( $this->log_path ), 'One exception should produce one entry across callers.' );
			},
			$secondary
		);
	}
}
