<?php
/**
 * File with trait Sensei_HPPS_Helpers.
 *
 * @package sensei-tests
 */

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Using PHPUnit conventions.
// phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore

use Sensei\Internal\Quiz_Submission\Answer\Repositories\Answer_Repository_Factory;
use Sensei\Internal\Quiz_Submission\Grade\Repositories\Grade_Repository_Factory;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Submission_Repository_Factory;
use Sensei\Internal\Services\Progress_Storage_Configuration;
use Sensei\Internal\Services\Progress_Storage_Settings;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Factory;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Factory;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Quiz_Progress_Repository_Factory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helpers related to the High-Performance Progress Storage feature.
 *
 * @since 4.20.0
 */
trait Sensei_HPPS_Helpers {
	/**
	 * Repositories to restore after enabling HPPS.
	 *
	 * @var array
	 */
	private $_original_progress_repositories;

	private function enable_hpps_tables_repository() {
		Sensei()->settings->settings['experimental_progress_storage_repository'] = Progress_Storage_Settings::TABLES_STORAGE;
		$storage_configuration                 = Progress_Storage_Configuration::resolve(
			array(
				'experimental_progress_storage'            => true,
				'experimental_progress_storage_repository' => Progress_Storage_Settings::TABLES_STORAGE,
				'experimental_progress_storage_synchronization' => true,
			)
		);
		$this->_original_progress_repositories = $this->set_progress_repositories( $storage_configuration );
	}

	private function reset_hpps_repository() {
		Sensei()->settings->settings['experimental_progress_storage_repository'] = Progress_Storage_Settings::COMMENTS_STORAGE;
		Sensei()->progress_storage_configuration                                 = Progress_Storage_Configuration::resolve( Sensei()->settings->settings );

		foreach ( $this->_original_progress_repositories as $property => $repository ) {
			Sensei()->$property = $repository;
		}
	}

	/**
	 * Configurations used by required-write caller regressions.
	 *
	 * @return array
	 */
	public static function progress_storage_modes(): array {
		return array(
			'comments only'    => array( 'comments-only' ),
			'comments primary' => array( 'comments' ),
			'tables primary'   => array( 'tables' ),
		);
	}

	/**
	 * Dual-write configurations and their other store.
	 *
	 * @return array
	 */
	public static function dual_write_storage_modes(): array {
		return array(
			'comments primary' => array( 'comments', 'tables' ),
			'tables primary'   => array( 'tables', 'comments' ),
		);
	}

	/**
	 * Select progress repositories for an operation and restore them afterward.
	 *
	 * @param string   $mode Storage configuration.
	 * @param callable $operation Operation to run.
	 * @return mixed Operation result.
	 */
	protected function with_progress_storage_mode( string $mode, callable $operation ) {
		$configuration          = Progress_Storage_Configuration::resolve(
			array(
				'experimental_progress_storage'            => true,
				'experimental_progress_storage_synchronization' => 'comments-only' !== $mode,
				'experimental_progress_storage_repository' => 'tables' === $mode ? Progress_Storage_Settings::TABLES_STORAGE : Progress_Storage_Settings::COMMENTS_STORAGE,
			)
		);
		$original_configuration = Sensei()->progress_storage_configuration;
		$original               = $this->set_progress_repositories( $configuration );

		try {
			return $operation();
		} finally {
			foreach ( $original as $property => $repository ) {
				Sensei()->$property = $repository;
			}
			Sensei()->progress_storage_configuration = $original_configuration;
		}
	}

	/**
	 * Replace progress repositories using one configuration, returning the originals.
	 *
	 * @param Progress_Storage_Configuration $configuration Storage configuration.
	 * @return array Original repositories keyed by plugin property.
	 */
	private function set_progress_repositories( Progress_Storage_Configuration $configuration ): array {
		$factories = array(
			'course_progress_repository' => Course_Progress_Repository_Factory::class,
			'lesson_progress_repository' => Lesson_Progress_Repository_Factory::class,
			'quiz_progress_repository'   => Quiz_Progress_Repository_Factory::class,
			'quiz_submission_repository' => Submission_Repository_Factory::class,
			'quiz_answer_repository'     => Answer_Repository_Factory::class,
			'quiz_grade_repository'      => Grade_Repository_Factory::class,
		);
		$original  = array();
		foreach ( $factories as $property => $factory_class ) {
			$original[ $property ] = Sensei()->$property;
			Sensei()->$property    = ( new $factory_class( $configuration ) )->create();
		}
		Sensei()->progress_storage_configuration = $configuration;

		return $original;
	}

	/**
	 * Switch to the custom tables repositories only when HPPS tables mode is active.
	 */
	private function maybe_enable_hpps_tables_repository(): void {
		if ( self::is_hpps_tables_mode() ) {
			$this->enable_hpps_tables_repository();
		}
	}

	/**
	 * Restore the original repositories only when HPPS tables mode is active.
	 */
	private function maybe_reset_hpps_repository(): void {
		if ( self::is_hpps_tables_mode() ) {
			$this->reset_hpps_repository();
		}
	}

	/**
	 * Check if HPPS tables mode is enabled via the environment variable.
	 *
	 * @return bool
	 */
	public static function is_hpps_tables_mode(): bool {
		return (bool) getenv( 'ENABLE_HPPS' );
	}

	/**
	 * Skip the current test when HPPS tables mode is active.
	 *
	 * @param string $reason Reason for skipping.
	 */
	private function skip_in_hpps_mode( string $reason = 'Skipped in HPPS tables mode.' ): void {
		if ( self::is_hpps_tables_mode() ) {
			$this->markTestSkipped( $reason );
		}
	}
}
