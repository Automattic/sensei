<?php
/**
 * File containing the class Sensei_Progress_Data_Results.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data store results.
 */
class Sensei_Progress_Data_Results {

	/**
	 * Arguments used for query.
	 *
	 * @var array
	 */
	private $args;

	/**
	 * Results of query.
	 *
	 * @var Sensei_Progress[]
	 */
	private $results;

	/**
	 * Number of total results found.
	 *
	 * @var int
	 */
	private $total_found;

	/**
	 * Sensei_Progress_Data_Results constructor.
	 *
	 * @param array             $args
	 * @param Sensei_Progress[] $results
	 * @param int               $total_found
	 */
	public function __construct(
		$args,
		$results,
		$total_found
	) {
		$this->args        = $args;
		$this->results     = $results;
		$this->total_found = $total_found;
	}

	/**
	 * Get the arguments used to query for the results.
	 *
	 * @return array
	 */
	public function get_args() {
		return $this->args;
	}

	/**
	 * Get the results. Note: If this was just a `count` query, only the `total_found` will be populated.
	 *
	 * @return Sensei_Progress[]
	 */
	public function get_results() {
		return $this->results;
	}

	/**
	 * Get the total number of results available.
	 *
	 * @return int
	 */
	public function get_total_found() {
		return $this->total_found;
	}

	/**
	 * Merge another data store's results.
	 *
	 * @param Sensei_Progress_Data_Results $results
	 *
	 * @return $this
	 */
	public function merge( Sensei_Progress_Data_Results $results ) {
		$this->results     = array_merge( $this->results, $results->get_results() );
		$this->total_found = $this->total_found + $results->get_total_found();

		return $this;
	}
}
