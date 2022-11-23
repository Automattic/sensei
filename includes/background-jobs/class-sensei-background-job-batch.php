<?php
/**
 * File containing the class Sensei_Background_Job_Batch.
 *
 * @since 3.9.0
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base class for jobs that do simple batches.
 */
abstract class Sensei_Background_Job_Batch extends Sensei_Background_Job_Stateful {
	const STATE_OFFSET = 'offset';

	/**
	 * Completion flag.
	 *
	 * @var bool
	 */
	private $complete = false;

	/**
	 * Get the job batch size.
	 *
	 * @return int
	 */
	abstract protected function get_batch_size() : int;

	/**
	 * Run batch.
	 *
	 * @param int $offset Current offset.
	 *
	 * @return bool Returns true if there is more to do.
	 */
	abstract protected function run_batch( int $offset ) : bool;

	/**
	 * Run the job.
	 */
	public function run() {
		$offset = $this->get_state( self::STATE_OFFSET, 0 );
		if ( $this->run_batch( $offset ) ) {
			$this->set_state( self::STATE_OFFSET, $offset + $this->get_batch_size() );
		} else {
			$this->complete = true;
		}
	}

	/**
	 * After the job runs, check to see if it needs to be re-queued for the next batch.
	 *
	 * @return bool
	 */
	public function is_complete() {
		return $this->complete;
	}
}
