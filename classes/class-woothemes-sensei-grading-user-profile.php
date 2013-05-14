<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Grading User Profile Class
 *
 * All functionality pertaining to the Admin Grading User Profile in Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Core
 * @author WooThemes
 * @since 1.3.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 * - build_data_array()
 * - display()
 */
class WooThemes_Sensei_Grading_User_Profile {
	public $user_id;

	/**
	 * Constructor
	 * @since  1.3.0
	 * @return  void
	 */
	public function __construct ( $user_id = 0, $quiz_id = 0 ) {
		$this->user_id = intval( $user_id );
		$this->quiz_id = intval( $quiz_id );
		// Actions
		// add_action( 'sensei_before_list_table', array( &$this, 'data_table_header' ) );
	} // End __construct()

	/**
	 * build_data_array builds the data for use in the table
	 * Overloads the parent method
	 * @since  1.3.0
	 * @return array
	 */
	public function build_data_array() {

		global $woothemes_sensei;

		$return_array = array();

		// @HUGH - This is where you get the list of questions
		// Remember $this->user_id is available and $this->quiz_id

		$return_array = $this->array_sort_reorder( $return_array );
		return $return_array;
	} // End build_data_array()

	/**
	 * display output to the admin view
	 * @since  1.3.0
	 * @return html
	 */
	public function display() {
		// Get data for the user
		$data = $this->build_data_array();

		echo '<p>';
			echo 'Debugging...fix me Hugh!';
		echo '</p>';

		echo '<p>';
			echo 'User ID - ' . $this->user_id;
		echo '</p>';

		echo '<p>';
			echo 'Quiz ID - ' . $this->quiz_id;
		echo '</p>';

		echo '<p>';
			echo 'Look...some whitespace in the code ;-)';
		echo '</p>';

	} // End display()

	/**
	 * REFACTOR - PLACE INTO AN ADMIN UTILS CLASS THE BELOW 2 FUNCTIONS
	 */

	/**
	 * array_sort_reorder handle sorting of table data
	 * @since  1.3.0
	 * @param  array $return_array data to be ordered
	 * @return array $return_array ordered data
	 */
	public function array_sort_reorder( $return_array ) {
		if ( isset( $_GET['orderby'] ) && '' != esc_html( $_GET['orderby'] ) ) {
			$sort_key = '';
			if ( array_key_exists( esc_html( $_GET['orderby'] ), $this->sortable_columns ) ) {
				$sort_key = esc_html( $_GET['orderby'] );
			} // End If Statement
			if ( '' != $sort_key ) {
					$this->sort_array_by_key($return_array,$sort_key);
				if ( isset( $_GET['order'] ) && 'desc' == esc_html( $_GET['order'] ) ) {
					$return_array = array_reverse( $return_array, true );
				} // End If Statement
			} // End If Statement
			return $return_array;
		} else {
			return $return_array;
		} // End If Statement
	} // End array_sort_reorder()

	/**
	 * sort_array_by_key sorts array by key
	 * @since  1.3.0
	 * @param  $array by ref
	 * @param  $key string column name in array
	 * @return void
	 */
	public function sort_array_by_key( &$array, $key ) {
	    $sorter = array();
	    $ret = array();
	    reset( $array );
	    foreach ( $array as $ii => $va ) {
	        $sorter[$ii] = $va[$key];
	    } // End For Loop
	    asort( $sorter );
	    foreach ( $sorter as $ii => $va ) {
	        $ret[$ii] = $array[$ii];
	    } // End For Loop
	    $array = $ret;
	} // End sort_array_by_key()

} // End Class
?>