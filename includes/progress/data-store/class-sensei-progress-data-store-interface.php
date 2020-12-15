<?php
/**
 * File containing the interface Sensei_Progress_Data_Store_Interface.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data store interface.
 */
interface Sensei_Progress_Data_Store_Interface {
	/**
	 * Query progress results.
	 *
	 * @param array $args Arguments used in query.
	 *
	 * @return Sensei_Progress_Data_Results
	 * @todo Write documentation for `$args`.
	 */
	public function query( $args = [] );

	/**
	 * Delete a progress record.
	 *
	 * @param Sensei_Progress $progress Progress object.
	 *
	 * @return bool
	 */
	public function delete( Sensei_Progress $progress );

	/**
	 * Save a progress record.
	 *
	 * @param Sensei_Progress $progress Progress object.
	 *
	 * @return bool
	 */
	public function save( Sensei_Progress $progress );
}
