<?php
/**
 * Stub implementing Sensei_Background_Job_Stateful.
 *
 * @package sensei-tests
 */

/**
 * Stub to help test the background job stateful runner.
 *
 * @since 3.9.0
 */
class Sensei_Background_Job_Stateful_Stub extends Sensei_Background_Job_Stateful {
	/**
	 * Run job.
	 */
	public function run() {
		$this->set_state( 'run', $this->get_state( 'run', 0 ) + 1 );
	}

	/**
	 * Check if complete.
	 *
	 * @return bool
	 */
	public function is_complete() {
		$run_for = $this->get_args()['run_for'] ?? 5;

		return $this->get_state( 'run', 0 ) > $run_for;
	}

	protected function allow_multiple_instances(): bool {
		return true;
	}

}
