<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Generic List Table Class
 *
 * All functionality pertaining to the Generic Data Table Class in Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Core
 * @author WooThemes
 * @since 1.2.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 * - extra_tablenav()
 * - table_search_form()
 * - get_columns()
 * - get_sortable_columns()
 * - build_data_array()
 * - array_sort_reorder()
 * - prepare_items()
 * - display_rows()
 * - sort_array_by_key()
 * - column_default()
 * - no_items()
 * - get_bulk_actions()
 * - bulk_actions()
 */
class WooThemes_Sensei_List_Table extends WP_List_Table {
	public $token;
	public $columns;
	public $sortable_columns;
	public $hidden_columns;
	public $per_page;

	/**
	 * Constructor
	 * @since  1.2.0
	 * @return  void
	 */
	public function __construct ( $token ) {
		// Class Variables
		$this->token = $token;
		$this->columns = array();
		$this->sortable_columns = array();
		$this->hidden_columns = array();
		$this->per_page = 10;
		parent::__construct( array(
									'singular'=> 'wp_list_table_' . $this->token, // Singular label
									'plural' => 'wp_list_table_' . $this->token . 's', // Plural label
									'ajax'	=> false // No Ajax for this table
		) );
		// Actions
		add_action( 'sensei_before_list_table', array( &$this, 'table_search_form' ) );
	} // End __construct()

	/**
	 * extra_tablenav adds extra markup in the toolbars before or after the list
	 * @since  1.2.0
	 * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
	 */
	public function extra_tablenav( $which ) {
		if ( $which == "top" ){
			//The code that goes before the table is here
			do_action( 'sensei_before_list_table' );
		} // End If Statement
		if ( $which == "bottom" ){
			//The code that goes after the table is there
			do_action( 'sensei_after_list_table' );
		} // End If Statement
	} // End extra_tablenav()

	/**
	 * table_search_form outputs search form for table
	 * @since  1.2.0
	 * @return void
	 */
	public function table_search_form() {
		?><form method="post">
  				<input type="hidden" name="page" value="<?php esc_attr( $this->token ); ?>" />
  				<?php $this->search_box('search', 'search_id'); ?>
			</form><?php
	} // End table_search_form()

	/**
	 * get_columns Define the columns that are going to be used in the table
	 * @since  1.2.0
	 * @return array $columns, the array of columns to use with the table
	 */
	public function get_columns() {
		return $columns = $this->columns;
	} // End get_columns()

	/**
	 * get_sortable_columns Decide which columns to activate the sorting functionality on
	 * @since  1.2.0
	 * @return array $sortable, the array of columns that can be sorted by the user
	 */
	public function get_sortable_columns() {
		return $sortable = $this->sortable_columns;
	} // End get_sortable_columns()

	/**
	 * build_data_array build the data array for output
	 * @since  1.2.0
	 * @return array
	 */
	public function build_data_array() {
		return array();
	} // End build_data_array()

	/**
	 * array_sort_reorder handle sorting of table data
	 * @since  1.2.0
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
	 * Prepare the table with different parameters, pagination, columns and table elements
	 * @since  1.2.0
	 * @return void
	 */
	public function prepare_items() {
		// Register Columns
		$columns = $this->get_columns();
		$hidden = $this->hidden_columns;
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
		// Get Table Data and build Pagination
		$this->items = $this->build_data_array();
		$per_page = $this->per_page;
		$current_page = $this->get_pagenum();
		$total_items = count($this->items);
		// Subset for pagination
		$this->items = array_slice($this->items,(($current_page-1)*$per_page),$per_page);
		$this->set_pagination_args( array(
		    'total_items' => $total_items,
		    'per_page'    => $per_page
		) );
	} // End prepare_items()

	/**
	 * Display the rows of records in the table
	 * Overloads the parent method
	 * @since  1.2.0
	 * @return echo the markup of the rows
	 */
	public function display_rows() {
		//Get the records registered in the prepare_items method
		$records = $this->items;
		//Get the columns registered in the get_columns and get_sortable_columns methods
		list( $columns, $hidden ) = $this->get_column_info();
		// Loop for each record
		$record_count = 0;
		if( !empty( $records ) ) {
			foreach( $records as $rec ) {
				// Table Row
				echo '<tr id="record_'.$record_count.'">';
				// Table Columns Loop
				foreach ( $columns as $column_name => $column_display_name ) {
					$class = "class='$column_name column-$column_name'";
					$style = "";
					if ( in_array( $column_name, $hidden ) ) $style = ' style="display:none;"';
					$attributes = $class . $style;
					//Display the cell
					echo '<td '.$attributes.'>'.stripslashes($rec[$column_name]).'</td>';
				} // End For Loop
				$record_count++;
				echo '</tr>';
			} // End For Loop
		} // End If Statement
	} // End display_rows()

	/**
	 * sort_array_by_key sorts array by key
	 * @since  1.2.0
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

	/**
	 * column_default handles default column output
	 * Overloads the parent method
	 * @since  1.2.0
	 * @param  $item array of columns
	 * @param  $column_name string column name
	 * @return string output
	 */
	public function column_default( $item, $column_name ) {
		if ( array_key_exists( $column_name, $this->columns ) ) {
			return $item[ $column_name ];
		} else {
			return print_r( $item, true ) ;
		} // End If Statement
	} // End column_default()

	/**
	 * no_items sets output when no items are found
	 * Overloads the parent method
	 * @since  1.2.0
	 * @return void
	 */
	public function no_items() {
  		_e( 'No items found.', 'woothemes-sensei' );
	} // End no_items()

	/**
	 * get_bulk_actions sets the bulk actions list
	 * @since  1.2.0
	 * @return array action list
	 */
	public function get_bulk_actions() {
		return array();
	} // End overview_actions_filters()

	/**
	 * bulk_actions output for the bulk actions area
	 * @since  1.2.0
	 * @return void
	 */
	public function bulk_actions() {
		// This will be output Above the table headers on the left
	} // End bulk_actions()

} // End Class
?>