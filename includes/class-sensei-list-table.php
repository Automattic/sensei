<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Generic Data Table parent Class in Sensei.
 *
 *
 * @package Core
 * @author Automattic
 *
 * @since 1.2.0
 *
 */
class Sensei_List_Table extends WP_List_Table {
	public $token;

	/**
	 * Used for indicating if the output is for csv or not
	 *
	 * @var bool $csv_output
	 * @access public
	 */
	public $csv_output = false;

	/**
	 * Used for storing the string of a search for passing between functions
	 *
	 * @var string $search
	 * @access public
	 */
	public $search = false;

	/**
	 * Used for storing the total number of items available for the given query
	 * also used for generating the pagination.
	 *
	 * @var int $total_items
	 * @access public
	 */
	public $total_items = 0;


    /**
     * @var array $sortable_columns
     *
     */
    public $sortable_columns = array();

    /**
     * @var array columns
     */
    public $columns = array();

	/**
	 * Constructor
	 * @since  1.2.0
	 * @return  void
	 */
	public function __construct ( $token ) {
		// Class Variables
		$this->token = $token;

		parent::__construct( array(
								'singular' => 'wp_list_table_' . $this->token, // Singular label
								'plural'   => 'wp_list_table_' . $this->token . 's', // Plural label
								'ajax'     => false // No Ajax for this table
		) );

		// Actions
		add_action( 'sensei_before_list_table', array( $this, 'table_search_form' ), 5 );

	} // End __construct()

	/**
	 * remove_sortable_columns removes all sortable columns by returning an empty array
	 * @param  array $columns Existing columns
	 * @return array          Modified columns
	 */
	public function remove_sortable_columns( $columns ) {
		return array();
	}

	/**
	 * Makes the table non-fixed to display action buttons properly.
	 *
	 * @access protected
	 *
	 * @return array List of CSS classes for the table tag.
	 */
	protected function get_table_classes() {
		return array_diff( parent::get_table_classes(), array( 'fixed' ) );
	}

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
		if ( empty( $_REQUEST['s'] ) && !$this->has_items() ) {
			return;
		}
		?><form method="get">
			<?php
			if( isset( $_GET ) && count( $_GET ) > 0 ) {
				foreach( $_GET as $k => $v ) {
					if( 's' != $k ) { ?>

                        <input type="hidden" name="<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $v ); ?>" />

                    <?php  }
				}
			}
			?>
			<?php $this->search_box( apply_filters( 'sensei_list_table_search_button_text', __( 'Search Users' ,'woothemes-sensei' ) ), 'search_id' ); ?>
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
	 * Overriding parent WP-List-Table get_column_info()
	 * @since  1.7.0
	 * @return array
	 */
	function get_column_info() {
		if ( isset( $this->_column_headers ) )
			return $this->_column_headers;

		$columns = $this->get_columns();
		$hidden = get_hidden_columns( $this->screen );

		/**
		 * Filter the list table sortable columns for a specific screen.
		 *
		 * The dynamic portion of the hook name, $this->screen->id, refers
		 * to the ID of the current screen, usually a string.
		 *
		 * @since 3.5.0
		 *
		 * @param array $sortable_columns An array of sortable columns.
		 */
		$_sortable = apply_filters( "manage_{$this->screen->id}_sortable_columns", $this->get_sortable_columns() );

		$sortable = array();
		foreach ( $_sortable as $id => $data ) {
			if ( empty( $data ) )
				continue;

			$data = (array) $data;
			if ( !isset( $data[1] ) )
				$data[1] = false;

			$sortable[$id] = $data;
		}

        $primary = $this->get_primary_column_name();
        $this->_column_headers = array( $columns, $hidden, $sortable, $primary );

		return $this->_column_headers;
	}

	/**
	 * Called by WP-List-Table and wrapping get_row_data() (needs overriding) with the elements needed for HTML output
	 *
	 * @since  1.7.0
	 * @param object $item The current item
	 */
	function single_row( $item ) {
		static $row_class = '';
		$row_class = ( $row_class == '' ? ' class="alternate"' : '' );

		echo '<tr' . $row_class . '>';

		$column_data = $this->get_row_data( $item );

		list( $columns, $hidden ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$class = "class='$column_name column-$column_name'";

			$style = '';
			if ( in_array( $column_name, $hidden ) )
				$style = ' style="display:none;"';

			$attributes = "$class$style";

			echo "<td $attributes>";
			if ( isset($column_data[$column_name]) ) {
				echo $column_data[$column_name];
			}
			echo "</td>";
		}

		echo '</tr>';
	}

	/**
	 * @since 1.7.0
	 * @access public
	 * @abstract
	 */
	protected function get_row_data( $item ) {
		die( 'either function WooThemes_Sensei_List_Table::get_row_data() must be over-ridden in a sub-class or WooThemes_Sensei_List_Table::single_row() should be.' );
	}

	/**
	 * no_items sets output when no items are found
	 * Overloads the parent method
	 * @since  1.2.0
	 * @return void
	 */
	function no_items() {

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
	public function bulk_actions( $which = '' ) {
		// This will be output Above the table headers on the left
		echo apply_filters( 'sensei_list_bulk_actions', '' );
	} // End bulk_actions()

} // End Class

/**
 * Class WooThemes_Sensei_List_Table
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_List_Table extends Sensei_List_Table {}
