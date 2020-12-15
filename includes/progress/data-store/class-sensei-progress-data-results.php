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
	 * Data store that queried results.
	 *
	 * @var Sensei_Progress_Data_Store_Interface
	 */
	private $data_store;

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
	 * @param Sensei_Progress_Data_Store_Interface $data_store
	 * @param array                                $args
	 * @param Sensei_Progress[]                    $results
	 * @param int                                  $total_found
	 */
	public function __construct(
		Sensei_Progress_Data_Store_Interface $data_store,
		$args,
		$results,
		$total_found
	) {
		$this->data_store  = $data_store;
		$this->args        = $args;
		$this->results     = $results;
		$this->total_found = $total_found;
	}

	/**
	 * Get the data store object that produced these results.
	 *
	 * @return Sensei_Progress_Data_Store_Interface
	 */
	public function get_data_store() {
		return $this->data_store;
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
	 * Get the results.
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
}
