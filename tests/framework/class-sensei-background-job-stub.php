<?php
/**
 * Stub implementing Sensei_Job.
 *
 * @package sensei-tests
 */

/**
 * Stub to help test the background job scheduler.
 *
 * @since 3.0.0
 */
class Sensei_Background_Job_Stub implements Sensei_Background_Job_Interface {
	/**
	 * Name of job.
	 *
	 * @var string
	 */
	const NAME = 'test-job';

	/**
	 * Args of job.
	 *
	 * @var array
	 */
	public $args = [];

	/**
	 * Is Complete flag.
	 *
	 * @var bool
	 */
	public $is_complete = false;

	/**
	 * Turns true if it runs.
	 *
	 * @var bool
	 */
	public $did_run = false;

	/**
	 * Callable to run.
	 *
	 * @var callable
	 */
	public $run_callback;

	public function get_name() {
		return self::NAME;
	}

	public function run() {
		$this->did_run = true;

		if ( is_callable( $this->run_callback ) ) {
			call_user_func( $this->run_callback, $this );
		}
	}

	public function is_complete() {
		return $this->is_complete;
	}

	public function get_args() {
		return $this->args;
	}

}
