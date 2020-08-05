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
	const CONTENT_TYPES_STATE_KEY = 'content_types';

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
			foreach ( $this->get_state( self::CONTENT_TYPES_STATE_KEY ) as $type ) {
				$this->tasks[ $type ] = new Sensei_Export_Task( $this, $type );
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
		return [];
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
	 * Set the content types to be exported.
	 *
	 * @param string[] $content_types Content types.
	 */
	public function set_content_types( $content_types ) {
		$this->set_state( self::CONTENT_TYPES_STATE_KEY, $content_types );
	}

}
