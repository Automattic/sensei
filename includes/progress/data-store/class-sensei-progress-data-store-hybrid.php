<?php
/**
 * File containing the class Sensei_Progress_Data_Store_Hybrid.
 *
 * @since [STORAGE_MILESTONE]
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data store for managing progress in stored in both the comments and DB table.
 */
class Sensei_Progress_Data_Store_Hybrid implements Sensei_Progress_Data_Store_Interface {
	/**
	 * Data stores for hybrid store.
	 *
	 * @var Sensei_Progress_Data_Store_Interface[]
	 */
	private $data_stores;

	/**
	 * Sensei_Progress_Data_Store_Comments constructor.
	 *
	 * @param Sensei_Progress_Data_Store_Interface[] $data_stores Data stores.
	 */
	public function __construct( $data_stores ) {
		$this->data_stores = $data_stores;
	}

	/**
	 * Query progress results.
	 *
	 * @param array $args Arguments used in query.
	 *
	 * @return Sensei_Progress_Data_Results
	 */
	public function query( $args = [] ) {
		if ( isset( $args['number'] ) && 1 === (int) $args['number'] ) {
			// If we're fetching just a single record, try the table first.
			$db_table_query = $this->data_stores['table']->query( $args );
			if ( $db_table_query->get_total_found() > 0 ) {
				return $db_table_query;
			}
		}

		// While the course is migrating, return other queries from the comments table.
		return $this->data_stores['comments']->query( $args );
	}

	/**
	 * Delete progress.
	 *
	 * @param Sensei_Progress $progress Progress object.
	 *
	 * @return bool
	 */
	public function delete( Sensei_Progress $progress ) {
		if ( ! $progress->get_data_store() ) {
			return false;
		}

		// Data store should be responsible for its own deletion.
		return $progress->get_data_store()->delete( $progress );
	}

	/**
	 * Save a progress record.
	 *
	 * @param Sensei_Progress $progress Progress object.
	 *
	 * @return bool
	 */
	public function save( Sensei_Progress $progress ) {
		if ( ! $progress->get_data_store() ) {
			// We should assume if no data store set, we'll want to start tracking with table data store.
			// @todo Figure out if we should always copy the other course records (lessons) for a single course/user.
			return $this->data_stores['table']->save( $progress );
		}

		// Data store should be responsible for its own saving.
		return $progress->get_data_store()->save( $progress );
	}
}
