<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Analysis User Profile Data Table in Sensei.
 *
 * @package Analytics
 * @author Automattic
 *
 * @since 1.2.0
 */
class Sensei_Analysis_User_Profile_List_Table extends Sensei_List_Table {

	public $user_id;
	public $page_slug;

	/**
	 * Constructor
	 *
	 * @since  1.2.0
	 * @return  void
	 */
	public function __construct( $user_id = 0 ) {
		$this->user_id   = intval( $user_id );
		$this->page_slug = Sensei_Analysis::PAGE_SLUG;

		// Load Parent token into constructor
		parent::__construct( 'analysis_user_profile' );

		// Actions
		add_action( 'sensei_before_list_table', array( $this, 'data_table_header' ) );
		add_action( 'sensei_after_list_table', array( $this, 'data_table_footer' ) );
		remove_action( 'sensei_before_list_table', array( $this, 'table_search_form' ), 5 );

		add_filter( 'sensei_list_table_search_button_text', array( $this, 'search_button' ) );
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @since  1.7.0
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_columns() {
		$columns = array(
			'title'     => __( 'Course', 'sensei-lms' ),
			'started'   => __( 'Date Started', 'sensei-lms' ),
			'completed' => __( 'Date Completed', 'sensei-lms' ),
			'status'    => __( 'Status', 'sensei-lms' ),
			'percent'   => __( 'Percent Complete', 'sensei-lms' ),
		);
		$columns = apply_filters( 'sensei_analysis_user_profile_columns', $columns );
		return $columns;
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @since  1.7.0
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_sortable_columns() {
		$columns = array(
			'completed' => array( 'comment_date', false ),
		);
		$columns = apply_filters( 'sensei_analysis_user_profile_columns_sortable', $columns );
		return $columns;
	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 *
	 * @since  1.7.0
	 * @return void
	 */
	public function prepare_items() {
		// Handle orderby (needs work)
		$orderby = '';
		if ( ! empty( $_GET['orderby'] ) ) {
			if ( array_key_exists( esc_html( $_GET['orderby'] ), $this->get_sortable_columns() ) ) {
				$orderby = esc_html( $_GET['orderby'] );
			}
		}

		// Handle order
		$order = 'ASC';
		if ( ! empty( $_GET['order'] ) ) {
			$order = ( 'ASC' == strtoupper( $_GET['order'] ) ) ? 'ASC' : 'DESC';
		}

		// Handle search, need 4.1 version of WP to be able to restrict statuses to known post_ids
		$search = false;
		if ( ! empty( $_GET['s'] ) ) {
			$search = esc_html( $_GET['s'] );
		}
		$this->search = $search;

		$per_page = $this->get_items_per_page( 'sensei_comments_per_page' );
		$per_page = apply_filters( 'sensei_comments_per_page', $per_page, 'sensei_comments' );

		$paged  = $this->get_pagenum();
		$offset = 0;
		if ( ! empty( $paged ) ) {
			$offset = $per_page * ( $paged - 1 );
		}

		$args = array(
			'number'  => $per_page,
			'offset'  => $offset,
			'orderby' => $orderby,
			'order'   => $order,
		);
		if ( $this->search ) {
			$args['search'] = $this->search;
		}

		$this->items = $this->get_course_statuses( $args );

		$total_items = $this->total_items;
		$total_pages = ceil( $total_items / $per_page );
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'total_pages' => $total_pages,
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Generate a csv report with different parameters, pagination, columns and table elements
	 *
	 * @since  1.7.0
	 * @return data
	 */
	public function generate_report( $report ) {

		$data = array();

		$this->csv_output = true;

		// Handle orderby
		$orderby = '';
		if ( ! empty( $_GET['orderby'] ) ) {
			if ( array_key_exists( esc_html( $_GET['orderby'] ), $this->get_sortable_columns() ) ) {
				$orderby = esc_html( $_GET['orderby'] );
			}
		}

		// Handle order
		$order = 'ASC';
		if ( ! empty( $_GET['order'] ) ) {
			$order = ( 'ASC' == strtoupper( $_GET['order'] ) ) ? 'ASC' : 'DESC';
		}

		// Handle search
		$search = false;
		if ( ! empty( $_GET['s'] ) ) {
			$search = esc_html( $_GET['s'] );
		}
		$this->search = $search;

		$args = array(
			'orderby' => $orderby,
			'order'   => $order,
		);
		if ( $this->search ) {
			$args['search'] = $this->search;
		}

		// Start the csv with the column headings
		$column_headers = array();
		$columns        = $this->get_columns();
		foreach ( $columns as $key => $title ) {
			$column_headers[] = $title;
		}
		$data[] = $column_headers;

		$this->items = $this->get_course_statuses( $args );

		// Process each row
		foreach ( $this->items as $item ) {
			$data[] = $this->get_row_data( $item );
		}

		return $data;
	}

	/**
	 * Generates the overall array for a single item in the display
	 *
	 * @since  1.7.0
	 * @param object $item The current item
	 */
	protected function get_row_data( $item ) {
		$course_title      = get_the_title( $item->comment_post_ID );
		$course_percent    = get_comment_meta( $item->comment_ID, 'percent', true );
		$course_start_date = get_comment_meta( $item->comment_ID, 'start', true );
		$course_end_date   = '';

		if ( 'complete' == $item->comment_approved ) {

			$status       = __( 'Completed', 'sensei-lms' );
			$status_class = 'graded';

			$course_end_date = $item->comment_date;

		} else {

			$status       = __( 'In Progress', 'sensei-lms' );
			$status_class = 'in-progress';

		}

		// Output users data
		if ( ! $this->csv_output ) {
			$url = add_query_arg(
				array(
					'page'      => $this->page_slug,
					'user_id'   => $this->user_id,
					'course_id' => $item->comment_post_ID,
				),
				admin_url( 'admin.php' )
			);

			$course_title = '<strong><a class="row-title" href="' . esc_url( $url ) . '">' . esc_html( $course_title ) . '</a></strong>';
			$status       = sprintf( '<span class="%s">%s</span>', esc_attr( $status_class ), esc_html( $status ) );
			if ( is_numeric( $course_percent ) ) {
				$course_percent .= '%';
			}
		}
		$column_data = apply_filters(
			'sensei_analysis_user_profile_column_data',
			array(
				'title'     => $course_title,
				'started'   => $course_start_date,
				'completed' => $course_end_date,
				'status'    => $status,
				'percent'   => $course_percent,
			),
			$item
		);

		$escaped_column_data = array();

		foreach ( $column_data as $key => $data ) {
			$escaped_column_data[ $key ] = wp_kses_post( $data );
		}

		return $escaped_column_data;
	}

	/**
	 * Return array of Course statuses
	 *
	 * @since  1.7.0
	 * @return array statuses
	 */
	private function get_course_statuses( $args ) {

		$activity_args = array(
			'user_id' => $this->user_id,
			'type'    => 'sensei_course_status',
			'number'  => isset( $args['number'] ) ? $args['number'] : 0,
			'offset'  => isset( $args['offset'] ) ? $args['offset'] : 0,
			'orderby' => $args['orderby'],
			'order'   => $args['order'],
			'status'  => 'any',
		);

		if ( ! current_user_can( 'manage_sensei' ) ) {
			$activity_args['post_author'] = get_current_user_id();
		}

		$activity_args = apply_filters( 'sensei_analysis_user_profile_filter_statuses', $activity_args );

		// WP_Comment_Query doesn't support SQL_CALC_FOUND_ROWS, so instead do this twice
		$this->total_items = Sensei_Utils::sensei_check_for_activity(
			array_merge(
				$activity_args,
				array(
					'count'  => true,
					'offset' => 0,
					'number' => 0,
				)
			)
		);

		// Ensure we change our range to fit (in case a search threw off the pagination) - Should this be added to all views?
		if ( $this->total_items < $activity_args['offset'] ) {

			$new_paged               = floor( $this->total_items / $activity_args['number'] );
			$activity_args['offset'] = $new_paged * $activity_args['number'];

		}
		$statuses = Sensei_Utils::sensei_check_for_activity( $activity_args, true );

		// Need to always return an array, even with only 1 item
		if ( ! is_array( $statuses ) ) {
			$statuses = array( $statuses );
		}

		return $statuses;
	}

	/**
	 * Sets output when no items are found
	 * Overloads the parent method
	 *
	 * @since  1.2.0
	 * @return void
	 */
	public function no_items() {
		echo esc_html__( 'No courses found.', 'sensei-lms' );
	}

	/**
	 * Output for table heading
	 *
	 * @since  1.2.0
	 * @return void
	 */
	public function data_table_header() {
		echo '<strong>' . esc_html__( 'Courses', 'sensei-lms' ) . '</strong>';
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
	 */
	public function extra_tablenav( $which ) {
		?>
		<div class="alignleft actions">
			<?php
			parent::extra_tablenav( $which );
			?>
		</div>
		<?php
	}

	/**
	 * Output search form for table.
	 */
	public function table_search_form() {
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$this->search_box( apply_filters( 'sensei_list_table_search_button_text', __( 'Search Users', 'sensei-lms' ) ), 'search_id' );
	}

	/**
	 * Output for table footer
	 *
	 * @since  1.7.0
	 * @return void
	 */
	public function data_table_footer() {
		$user   = get_user_by( 'id', $this->user_id );
		$report = sanitize_title( $user->display_name ) . '-course-overview';
		$url    = add_query_arg(
			array(
				'page'                   => $this->page_slug,
				'user_id'                => $this->user_id,
				'sensei_report_download' => $report,
			),
			admin_url( 'admin.php' )
		);
		echo '<a class="button button-primary" href="' . esc_url( wp_nonce_url( $url, 'sensei_csv_download', '_sdl_nonce' ) ) . '">' . esc_html__( 'Export all rows (CSV)', 'sensei-lms' ) . '</a>';
	}

	/**
	 * The text for the search button
	 *
	 * @since  1.7.0
	 * @return string
	 */
	public function search_button( $text = '' ) {
		return __( 'Search Courses', 'sensei-lms' );
	}

}

/**
 * Class WooThemes_Sensei_Analysis_User_Profile_List_Table
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Analysis_User_Profile_List_Table extends Sensei_Analysis_User_Profile_List_Table {}
