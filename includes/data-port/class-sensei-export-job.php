<?php
/**
 * File containing the Sensei_Export_Job class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class represents a data export job.
 */
class Sensei_Export_Job extends Sensei_Data_Port_Job {
	const SELECTIONS_STATE_KEY = 'selections';

	/**
	 * The array of the export tasks.
	 *
	 * @var Sensei_Data_Port_Task_Interface[]
	 */
	private $tasks;

	/**
	 * Sensei_Export_Job constructor.
	 *
	 * @param string $job_id Unique job id.
	 * @param string $json   A json string to restore internal state from.
	 */
	public function __construct( $job_id, $json = '' ) {
		parent::__construct( $job_id, $json );

		if ( null === $this->results ) {
			$this->results = self::get_default_results();
		}
	}

	/**
	 * Get the tasks of this export job.
	 *
	 * @return Sensei_Data_Port_Task_Interface[]
	 */
	public function get_tasks() {
		if ( ! isset( $this->tasks ) ) {
			$this->tasks = [];
			$task_class  = [
				'course'   => Sensei_Export_Courses::class,
				'lesson'   => Sensei_Export_Lessons::class,
				'question' => Sensei_Export_Questions::class,
			];

			foreach ( $this->get_content_types() as $type ) {
				if ( isset( $task_class[ $type ] ) ) {
					$this->tasks[ $type ] = $this->initialize_task( $task_class[ $type ] );
				}
			}

			if ( class_exists( 'ZipArchive' ) ) {
				$this->tasks['package'] = $this->initialize_task( Sensei_Export_Package::class );
			}
		}

		return $this->tasks;
	}


	/**
	 * Get the configuration for expected files.
	 *
	 * @return array
	 */
	public static function get_file_config() {
		return [
			'course'   => [],
			'lesson'   => [],
			'question' => [],
			'package'  => [],
		];
	}

	/**
	 * Check if a job is ready to be started.
	 *
	 * @return bool
	 */
	public function is_ready() {
		return true;
	}


	/**
	 * Get the result counts for each model.
	 */
	public function get_result_counts() {
	}

	/**
	 * Get the default results array.
	 *
	 * @return array
	 */
	public static function get_default_results() {
		return [];
	}

	/**
	 * Get the content types to be exported.
	 *
	 * Derived from the keys of the persisted selections.
	 *
	 * @return array
	 */
	public function get_content_types() {
		return array_keys( $this->get_selections_state() );
	}

	/**
	 * Set the content types to be exported.
	 *
	 * @deprecated 4.26.0 Use {@see Sensei_Export_Job::set_selections()} instead.
	 *
	 * @param string[] $content_types Content types to include in the export.
	 */
	public function set_content_types( $content_types ) {
		_deprecated_function( __METHOD__, '4.26.0', 'Sensei_Export_Job::set_selections' );

		$this->set_selections( array_fill_keys( $content_types, array() ) );
	}

	/**
	 * Set the per-type item selections to be exported.
	 *
	 * Each entry's key is the content type ('course', 'lesson', 'question').
	 * The value is an array of post IDs to restrict the export to, or an empty
	 * array to export every item of that type. Types absent from the input are
	 * skipped entirely (no CSV is produced for them).
	 *
	 * @since 4.26.0
	 *
	 * @param mixed $selections Expected shape: per-type ID arrays keyed by 'course', 'lesson', 'question'. A non-array argument is replaced with an empty selection set; unknown keys and per-type values that aren't arrays are silently dropped during normalisation.
	 */
	public function set_selections( $selections ) {
		if ( ! is_array( $selections ) ) {
			$selections = array();
		}

		$normalized = array();
		foreach ( array( 'course', 'lesson', 'question' ) as $type ) {
			if ( ! array_key_exists( $type, $selections ) || ! is_array( $selections[ $type ] ) ) {
				continue;
			}

			$ids = array_map( 'absint', $selections[ $type ] );
			$ids = array_values( array_filter( $ids ) );

			$normalized[ $type ] = array_values( array_unique( $ids ) );
		}

		$this->set_state( self::SELECTIONS_STATE_KEY, $normalized );
	}

	/**
	 * Get the post IDs the given content type should be restricted to.
	 *
	 * Empty array means "export all of that type".
	 *
	 * @since 4.26.0
	 *
	 * @param string $type Content type ('course', 'lesson', 'question').
	 *
	 * @return int[]
	 */
	public function get_selection( $type ) {
		$selections = $this->get_selections_state();

		if ( ! array_key_exists( $type, $selections ) ) {
			return array();
		}

		return $selections[ $type ];
	}

	/**
	 * Read the persisted selections, translating the legacy storage shape if needed.
	 *
	 * Pre-partial-export jobs persisted a flat list of type names under the
	 * 'content_types' key (e.g. [ 'course', 'lesson' ]). That shape maps
	 * losslessly onto the current per-type-filter shape with empty filters,
	 * since those jobs always exported every item of each enabled type.
	 *
	 * @return array<string, int[]>
	 */
	private function get_selections_state() {
		$state = $this->get_state( self::SELECTIONS_STATE_KEY );
		if ( is_array( $state ) && ! empty( $state ) ) {
			return $state;
		}

		$legacy = $this->get_state( 'content_types' );
		if ( is_array( $legacy ) && ! empty( $legacy ) ) {
			return array_fill_keys( $legacy, array() );
		}

		return array();
	}

	/**
	 * Type order in the logs.
	 *
	 * @return array
	 */
	public function get_log_type_order() {
		return [ 'course', 'lesson', 'question' ];
	}
}
