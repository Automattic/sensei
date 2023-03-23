<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Generic Data Table parent Class in Sensei.
 *
 * @author Automattic
 *
 * @since 1.2.0
 * @package Core
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
	 */
	public $sortable_columns = array();

	/**
	 * @var array columns
	 */
	public $columns = array();

	/**
	 * Constructor
	 *
	 * @since  1.2.0
	 * @return  void
	 */
	public function __construct( $token ) {
		// Class Variables
		$this->token = $token;

		parent::__construct(
			array(
				'singular' => 'wp_list_table_' . $this->token, // Singular label
				'plural'   => 'wp_list_table_' . $this->token . 's', // Plural label
				'ajax'     => false, // No Ajax for this table
			)
		);

		// Actions
		add_action( 'sensei_before_list_table', array( $this, 'table_search_form' ), 5 );

	}

	/**
	 * remove_sortable_columns removes all sortable columns by returning an empty array
	 *
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
	 *
	 * @since  1.2.0
	 * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
	 */
	public function extra_tablenav( $which ) {
		if ( $which == 'top' ) {
			// The code that goes before the table is here
			do_action( 'sensei_before_list_table' );
		}
		if ( $which == 'bottom' ) {
			// The code that goes after the table is there
			do_action( 'sensei_after_list_table' );
		}
	}

	/**
	 * table_search_form outputs search form for table
	 *
	 * @since  1.2.0
	 * @return void
	 */
	public function table_search_form() {
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
			return;
		}
		?><form method="get">
			<?php
			Sensei_Utils::output_query_params_as_inputs( [ 's' ] );
			$this->search_box( apply_filters( 'sensei_list_table_search_button_text', __( 'Search Users', 'sensei-lms' ) ), 'search_id' );
			?>
		</form>
		<?php
	}

	/**
	 * get_columns Define the columns that are going to be used in the table
	 *
	 * @since  1.2.0
	 * @return array $columns, the array of columns to use with the table
	 */
	public function get_columns() {
		return $this->columns;
	}

	/**
	 * get_sortable_columns Decide which columns to activate the sorting functionality on
	 *
	 * @since  1.2.0
	 * @return array $sortable, the array of columns that can be sorted by the user
	 */
	public function get_sortable_columns() {
		return $this->sortable_columns;
	}

	/**
	 * Overriding parent WP-List-Table get_column_info()
	 *
	 * @since  1.7.0
	 * @return array
	 */
	function get_column_info() {
		if ( isset( $this->_column_headers ) ) {
			return $this->_column_headers;
		}

		$columns = $this->get_columns();
		$hidden  = get_hidden_columns( $this->screen );

		$sortable_columns = $this->get_sortable_columns();

		$legacy_screen_id = preg_replace( '/^sensei\-lms\_/', 'sensei_', $this->screen->id );
		if ( has_filter( "manage_{$legacy_screen_id}_sortable_columns" ) ) {
			_deprecated_hook( esc_html( "manage_{$legacy_screen_id}_sortable_columns" ), '2.0.1', esc_html( "manage_{$this->screen->id}_sortable_columns" ) );
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$sortable_columns = apply_filters( "manage_{$legacy_screen_id}_sortable_columns", $sortable_columns );
		}

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
		$_sortable = apply_filters( "manage_{$this->screen->id}_sortable_columns", $sortable_columns );

		$sortable = array();
		foreach ( $_sortable as $id => $data ) {
			if ( empty( $data ) ) {
				continue;
			}

			$data = (array) $data;
			if ( ! isset( $data[1] ) ) {
				$data[1] = false;
			}

			$sortable[ $id ] = $data;
		}

		$primary               = $this->get_primary_column_name();
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
		$column_data = $this->get_row_data( $item );
		$row_class   = $this->get_row_class( $item );

		echo '<tr class="' . esc_attr( $row_class ) . '">';

		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$classes = esc_attr( $column_name ) . ' column-' . esc_attr( $column_name );
			$data    = '';
			$style   = '';

			if ( $primary === $column_name ) {
				$classes .= ' column-primary';
			} elseif ( 'cb' === $column_name ) {
				$classes .= ' check-column';
			}

			if ( 'cb' !== $column_name ) {
				$data = 'data-colname="' . esc_attr( $column_display_name ) . '"';
			}

			if ( in_array( $column_name, $hidden ) ) {
				$style = 'style="display: none;"';
			}

			$attributes = "class='$classes' $data $style";

			if ( 'cb' === $column_name ) {
				// Checkbox element needs to be wrapped in a table header cell to have proper WordPress styles applied.
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $attributes escaped when prepared.
				echo "<th $attributes>";
			} else {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $attributes escaped when prepared.
				echo "<td $attributes>";
			}

			if ( isset( $column_data[ $column_name ] ) ) {
				// $column_data is escaped in the individual get_row_data functions.
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in `get_row_data` method implementations.
				echo $column_data[ $column_name ];
			}

			if ( ! $this->has_native_row_actions() && $column_name === $primary ) {
				echo '<button type="button" class="toggle-row"><span class="screen-reader-text">' . esc_html__( 'Show more details', 'sensei-lms' ) . '</span></button>';
			}

			if ( 'cb' === $column_name ) {
				echo '</th>';
			} else {
				echo '</td>';
			}
		}

		echo '</tr>';
	}

	/**
	 * Returns if current implementation uses native row actions.
	 *
	 * @since 4.12.0
	 *
	 * @return bool
	 */
	protected function has_native_row_actions() {
		return false;
	}

	/**
	 * @since 1.7.0
	 * @access public
	 * @abstract
	 */
	protected function get_row_data( $item ) {
		die( 'either function Sensei_List_Table::get_row_data() must be over-ridden in a sub-class or Sensei_List_Table::single_row() should be.' );
	}

	/**
	 * Get the CSS class of the row.
	 *
	 * @param object|array $item The current item.
	 *
	 * @return string
	 */
	protected function get_row_class( $item ): string {
		static $row_class = '';

		$row_class = '' === $row_class ? 'alternate' : '';

		return $row_class;
	}

	/**
	 * no_items sets output when no items are found
	 * Overloads the parent method
	 *
	 * @since  1.2.0
	 * @return void
	 */
	function no_items() {

		esc_html_e( 'No items found.', 'sensei-lms' );

	}

	/**
	 * get_bulk_actions sets the bulk actions list
	 *
	 * @since  1.2.0
	 * @return array action list
	 */
	public function get_bulk_actions() {
		return array();
	}

	/**
	 * Bulk_actions output for the bulk actions area.
	 *
	 * @param string $which The location of the bulk actions: 'top' or 'bottom'. Default 'top'.
	 *
	 * @since  1.2.0
	 */
	public function bulk_actions( $which = '' ) {
		ob_start();

		parent::bulk_actions( $which );

		$bulk_action_html = ob_get_clean();

		// This will be output Above the table headers on the left.
		echo wp_kses(
			/**
			 * Filter the output of bulk action for sensei list table.
			 *
			 * @hook sensei_list_bulk_actions
			 *
			 * @param {string} $bulk_action_html Output of bulk action function.
			 *
			 * @return {string} Filtered output of bulk action function.
			 */
			apply_filters( 'sensei_list_bulk_actions', $bulk_action_html ),
			[
				'div'    => [
					'class' => [],
				],
				'label'  => [
					'for'   => [],
					'class' => [],
				],
				'select' => [
					'name'  => [],
					'id'    => [],
					'class' => [],
				],
				'option' => [
					'value' => [],
				],
				'input'  => [
					'type'  => [],
					'id'    => [],
					'name'  => [],
					'value' => [],
					'class' => [],
				],
			]
		);
	}

	/**
	 * Generates the table navigation above or below the table.
	 *
	 * @param string $which Which type of navigation to generate: top or bottom.
	 */
	protected function display_tablenav( $which ) {
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">

			<?php if ( $this->has_items() ) : ?>
				<div class="alignleft actions bulkactions">
					<?php $this->bulk_actions( $which ); ?>
				</div>
				<?php
			endif;
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>

			<br class="clear" />
		</div>
		<?php
	}
}

/**
 * Class WooThemes_Sensei_List_Table
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_List_Table extends Sensei_List_Table {}
