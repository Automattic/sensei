<?php
/**
 * Real database fault injection for progress tests.
 *
 * @package sensei-tests
 */

use Sensei\Internal\Services\Progress_Storage_Configuration;
use Sensei\Internal\Services\Progress_Storage_Settings;

/**
 * Inject progress insert failures and restore test state afterward.
 */
trait Sensei_Progress_Insert_Failure_Helpers {
	/**
	 * Configurations used by required-write caller regressions.
	 *
	 * @return array
	 */
	public static function progress_storage_modes(): array {
		return [
			'comments only'    => [ 'comments-only' ],
			'comments primary' => [ 'comments' ],
			'tables primary'   => [ 'tables' ],
		];
	}

	/**
	 * Dual-write configurations and their other store.
	 *
	 * @return array
	 */
	public static function dual_write_storage_modes(): array {
		return [
			'comments primary' => [ 'comments', 'tables' ],
			'tables primary'   => [ 'tables', 'comments' ],
		];
	}

	private function with_failed_progress_insert( string $mode, string $entity, callable $operation, ?string $failed_store = null ) {
		global $wpdb;
		$original      = [];
		$configuration = Progress_Storage_Configuration::resolve(
			[
				'experimental_progress_storage'                 => true,
				'experimental_progress_storage_synchronization' => 'comments-only' !== $mode,
				'experimental_progress_storage_repository'      => 'tables' === $mode ? Progress_Storage_Settings::TABLES_STORAGE : Progress_Storage_Settings::COMMENTS_STORAGE,
			]
		);
		$factories = [
			'course_progress' => \Sensei\Internal\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Factory::class,
			'lesson_progress' => \Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Factory::class,
			'quiz_progress'   => \Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Quiz_Progress_Repository_Factory::class,
			'quiz_submission' => \Sensei\Internal\Quiz_Submission\Submission\Repositories\Submission_Repository_Factory::class,
			'quiz_answer'     => \Sensei\Internal\Quiz_Submission\Answer\Repositories\Answer_Repository_Factory::class,
			'quiz_grade'      => \Sensei\Internal\Quiz_Submission\Grade\Repositories\Grade_Repository_Factory::class,
		];
		foreach ( [ 'course_progress', 'lesson_progress', 'quiz_progress', 'quiz_submission', 'quiz_answer', 'quiz_grade' ] as $property ) {
			$original[ $property . '_repository' ] = Sensei()->{$property . '_repository'};
			$factory_class = $factories[ $property ];
			Sensei()->{$property . '_repository'} = ( new $factory_class( $configuration ) )->create();
		}
		$original_configuration = Sensei()->progress_storage_configuration;
		Sensei()->progress_storage_configuration = $configuration;
		$failed_store = $failed_store ?? $mode;
		$table  = 'tables' === $failed_store ? $wpdb->prefix . 'sensei_lms_progress' : $wpdb->comments;
		$type   = 'tables' === $failed_store ? "'" . $entity . "'" : 'sensei_' . $entity . '_status';
		try {
			return $this->with_failed_insert( $table, $operation, null, $type );
		} finally {
			foreach ( $original as $property => $repository ) {
				Sensei()->$property = $repository;
			}
			Sensei()->progress_storage_configuration = $original_configuration;
		}
	}

	/**
	 * Run a repository insert fault with progress caching enabled.
	 *
	 * @param string      $table Physical table receiving inserts.
	 * @param callable    $operation Operation to run under the fault.
	 * @param string|null $replacement Replacement query, or null for a failed insert.
	 * @return mixed Operation result.
	 */
	protected function with_failed_cached_insert( string $table, callable $operation, ?string $replacement = null ) {
		add_filter( 'sensei_hpps_cache_enabled', '__return_true' );
		Progress_Storage_Settings::reset_cache_enabled();

		try {
			return $this->with_failed_insert( $table, $operation, $replacement );
		} finally {
			remove_filter( 'sensei_hpps_cache_enabled', '__return_true' );
			Progress_Storage_Settings::reset_cache_enabled();
		}
	}

	/**
	 * Replace matching inserts and restore the query filter and error settings.
	 *
	 * @param string      $table Physical table receiving inserts.
	 * @param callable    $operation Operation receiving the removable fault filter.
	 * @param string|null $replacement Replacement query, or null for a failed insert.
	 * @param string|null $type Optional entity value to match in the insert.
	 * @return mixed Operation result.
	 */
	protected function with_failed_insert( string $table, callable $operation, ?string $replacement = null, ?string $type = null ) {
		global $wpdb;
		// Target a missing table by default to cause a real insert failure.
		$replacement = $replacement ?? 'INSERT INTO sensei_missing_progress_table (id) VALUES (1)';
		$filter = static function ( $query ) use ( $table, $replacement, $type ) {
			if ( preg_match( '/^INSERT INTO `?' . preg_quote( $table, '/' ) . '`?\s/i', $query )
				&& ( null === $type || false !== strpos( $query, $type ) ) ) {
				return $replacement;
			}
			return $query;
		};
		$suppress_errors = $wpdb->suppress_errors( true );
		add_filter( 'query', $filter );

		try {
			return $operation( $filter );
		} finally {
			remove_filter( 'query', $filter );
			$wpdb->suppress_errors( $suppress_errors );
			wp_cache_flush();
		}
	}
}
