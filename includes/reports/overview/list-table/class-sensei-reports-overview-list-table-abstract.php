<?php
/**
 * File containing the abstract class Sensei_Reports_Overview_List_Table_Abstract.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Abstract reports overview list table class.
 *
 * @since 4.3.0
 */
abstract class Sensei_Reports_Overview_List_Table_Abstract extends Sensei_List_Table {

	/**
	 * Reports page slug.
	 *
	 * @var string
	 */
	protected $page_slug = Sensei_Analysis::PAGE_SLUG;

	/**
	 * Type of the overview report.
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * The post type under which is the page registered.
	 *
	 * @var string
	 */
	protected $post_type = 'course';

	/**
	 * Data provider for current report.
	 *
	 * @var Sensei_Reports_Overview_Data_Provider_Interface
	 */
	protected $data_provider;

	/**
	 * Return additional filters for current report.
	 *
	 * @return array
	 */
	abstract protected function get_additional_filters(): array;

	/**
	 * Constructor
	 *
	 * @param string                                          $type Type of the overview report.
	 * @param Sensei_Reports_Overview_Data_Provider_Interface $data_provider Data provider for current report.
	 */
	public function __construct( string $type, Sensei_Reports_Overview_Data_Provider_Interface $data_provider ) {
		// Load Parent token into constructor.
		parent::__construct( 'analysis_overview' );

		$this->type          = $type;
		$this->data_provider = $data_provider;

		// Actions.
		add_action( 'sensei_before_list_table', array( $this, 'output_top_filters' ) );
		add_action( 'sensei_after_list_table', array( $this, 'data_table_footer' ) );
		add_filter( 'sensei_list_table_search_button_text', array( $this, 'search_button' ) );
	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 *
	 * @return void
	 * @since  1.7.0
	 */
	public function prepare_items() {
		// Handle orderby.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required.
		$orderby = sanitize_key( wp_unslash( $_GET['orderby'] ?? '' ) );
		if ( empty( $orderby ) ) {
			$orderby = '';
		}

		// Handle order.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required.
		$order = sanitize_key( wp_unslash( $_GET['order'] ?? 'ASC' ) );
		$order = ( 'ASC' === strtoupper( $order ) ) ? 'ASC' : 'DESC';

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

		// Handle search.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required.
		$search = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
		if ( ! empty( $search ) ) {
			$args['search'] = esc_html( $search );
		}

		$filters           = array_merge( $args, $this->get_additional_filters() );
		$this->items       = $this->data_provider->get_items( $filters );
		$this->total_items = $this->data_provider->get_last_total_items();

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
	 * @return array
	 */
	public function generate_report() {
		$data = array();

		$this->csv_output = true;

		// Handle orderby.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required.
		$orderby = $this->get_orderby_value();

		// Handle order.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required.
		$order = $this->get_order_value();
		$order = ( 'ASC' === strtoupper( $order ) ) ? 'ASC' : 'DESC';

		$args = array(
			'number'  => -1,
			'offset'  => 0,
			'orderby' => $orderby,
			'order'   => $order,
		);

		// Handle search.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required.
		$search = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
		if ( ! empty( $search ) ) {
			$args['search'] = esc_html( $search );
		}

		$filters           = array_merge( $args, $this->get_additional_filters() );
		$this->items       = $this->data_provider->get_items( $filters );
		$this->total_items = $this->data_provider->get_last_total_items();

		// Start the CSV with the column headings.
		$column_headers = array();
		$columns        = $this->get_columns();

		foreach ( $columns as $title ) {
			$column_headers[] = $title;
		}

		$data[] = $column_headers;

		// Process each row.
		foreach ( $this->items as $item ) {
			$data[] = $this->get_row_data( $item );
		}

		return $data;
	}

	/**
	 * Sets output when no items are found
	 * Overloads the parent method
	 *
	 * @since  1.2.0
	 */
	public function no_items() {

		if ( 'lessons' === $this->type && ! $this->get_course_filter_value() ) {
			$message = __( 'View your Lessons data by first selecting a course.', 'sensei-lms' );
		} else {
			if ( ! $this->type || 'users' === $this->type ) {
				$type = __( 'students', 'sensei-lms' );
			} else {
				$type = $this->type;
			}

			// translators: Placeholders %1$s and %3$s are opening and closing <em> tags, %2$s is the view type.
			$message = sprintf( __( '%1$sNo %2$s found%3$s', 'sensei-lms' ), '<em>', $type, '</em>' );
		}

		?>
		<div class="sensei-analysis__no-items-message">
			<?php echo wp_kses_post( $message ); ?>
		</div>
		<?php
	}

	/**
	 * Output top filter form.
	 *
	 * @since  4.2.0
	 * @access private
	 */
	public function output_top_filters() {
		?>
		<form class="sensei-analysis__top-filters">
			<?php Sensei_Utils::output_query_params_as_inputs( [ 'course_filter', 'start_date', 'end_date', 's' ] ); ?>

			<?php if ( 'lessons' === $this->type ) : ?>
				<label for="sensei-course-filter">
					<?php esc_html_e( 'Course', 'sensei-lms' ); ?>:
				</label>

				<?php $this->output_course_select_input(); ?>
			<?php endif ?>

			<?php if ( in_array( $this->type, [ 'courses', 'users' ], true ) ) : ?>
				<label for="sensei-start-date-filter">
					<?php esc_html_e( 'Last Activity', 'sensei-lms' ); ?>:
				</label>

				<input
					class="sensei-date-picker"
					id="sensei-start-date-filter"
					name="start_date"
					type="text"
					autocomplete="off"
					placeholder="<?php echo esc_attr( __( 'Start Date', 'sensei-lms' ) ); ?>"
					value="<?php echo esc_attr( $this->get_start_date_filter_value() ); ?>"
				/>

				<input
					class="sensei-date-picker"
					id="sensei-end-date-filter"
					name="end_date"
					type="text"
					autocomplete="off"
					placeholder="<?php echo esc_attr( __( 'End Date', 'sensei-lms' ) ); ?>"
					value="<?php echo esc_attr( $this->get_end_date_filter_value() ); ?>"
				/>
			<?php endif ?>

			<?php submit_button( __( 'Filter', 'sensei-lms' ), '', '', false ); ?>
		</form>
		<?php
	}

	/**
	 * Output the course filter select input.
	 */
	private function output_course_select_input() {
		$courses            = Sensei_Course::get_all_courses();
		$selected_course_id = $this->get_course_filter_value();

		?>
		<select name="course_filter" id="sensei-course-filter">
			<option>
				<?php esc_html_e( 'Select a course', 'sensei-lms' ); ?>
			</option>
			<?php foreach ( $courses as $course ) : ?>
				<option
					value="<?php echo esc_attr( $course->ID ); ?>"
					<?php echo $selected_course_id === $course->ID ? 'selected' : ''; ?>
				>
					<?php echo esc_html( get_the_title( $course ) ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Output for table footer
	 */
	public function data_table_footer() {
		switch ( $this->type ) {
			case 'courses':
				$report = 'courses-overview';
				break;

			case 'lessons':
				$report = 'lessons-overview';
				break;

			case 'users':
			default:
				$report = 'user-overview';
				break;
		}

		$url = add_query_arg(
			array(
				'page'                   => $this->page_slug,
				'view'                   => $this->type,
				'sensei_report_download' => $report,
				'post_type'              => $this->post_type,
				'orderby'                => $this->get_orderby_value(),
				'order'                  => $this->get_order_value(),
				'course_filter'          => $this->get_course_filter_value(),
				'start_date'             => $this->get_start_date_filter_value(),
				'end_date'               => $this->get_end_date_filter_value(),
			),
			admin_url( 'edit.php' )
		);

		echo '<a class="button button-primary" href="' . esc_url( wp_nonce_url( $url, 'sensei_csv_download', '_sdl_nonce' ) ) . '">' . esc_html__( 'Export all rows (CSV)', 'sensei-lms' ) . '</a>';
	}

	/**
	 * The text for the search button.
	 */
	public function search_button() {
		return __( 'Search Courses', 'sensei-lms' );
	}

	/**
	 * Get the selected course ID.
	 *
	 * @return int The course ID or 0 if none is selected.
	 */
	protected function get_course_filter_value(): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for filtering.
		return isset( $_GET['course_filter'] ) ? (int) $_GET['course_filter'] : 0;
	}

	/**
	 * Get the orderby value.
	 *
	 * @return string orderby value.
	 */
	private function get_orderby_value(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for filtering.
		return isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '';
	}

	/**
	 * Get the order value.
	 *
	 * @return string order value.
	 */
	private function get_order_value(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for filtering.
		return isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'ASC';
	}

	/**
	 * Get the start date filter value.
	 *
	 * @return string The start date.
	 */
	private function get_start_date_filter_value(): string {
		$default = gmdate( 'Y-m-d', strtotime( '-30 days' ) );

		// phpcs:ignore WordPress.Security -- The date is sanitized by DateTime.
		$start_date = $_GET['start_date'] ?? $default;

		return DateTime::createFromFormat( 'Y-m-d', $start_date ) ? $start_date : '';
	}

	/**
	 * Get the start date filter value including the time.
	 *
	 * @return string The start date including the time or empty string if none.
	 */
	protected function get_start_date_and_time(): string {
		$start_date = DateTime::createFromFormat( 'Y-m-d', $this->get_start_date_filter_value() );

		if ( ! $start_date ) {
			return '';
		}

		$start_date->setTime( 0, 0, 0 );

		return $start_date->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Get the end date filter value.
	 *
	 * @return string The end date or empty string if none.
	 */
	private function get_end_date_filter_value(): string {
		// phpcs:ignore WordPress.Security -- The date is sanitized by DateTime.
		$end_date = $_GET['end_date'] ?? '';

		return DateTime::createFromFormat( 'Y-m-d', $end_date ) ? $end_date : '';
	}

	/**
	 * Get the end date filter value including the time.
	 *
	 * @return string The end date including the time or empty string if none.
	 */
	protected function get_end_date_and_time(): string {
		$end_date = DateTime::createFromFormat( 'Y-m-d', $this->get_end_date_filter_value() );

		if ( ! $end_date ) {
			return '';
		}

		$end_date->setTime( 23, 59, 59 );

		return $end_date->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Format the last activity date to a more readable form.
	 *
	 * @param string $date The last activity date.
	 *
	 * @return string The formatted last activity date.
	 */
	protected function format_last_activity_date( string $date ) {
		// Don't do any formatting if this is a CSV export.
		if ( $this->csv_output ) {
			return $date;
		}

		$timezone     = new DateTimeZone( 'GMT' );
		$now          = new DateTime( 'now', $timezone );
		$date         = new DateTime( $date, $timezone );
		$diff_in_days = $now->diff( $date )->days;

		// Show a human readable date if activity is within 6 days.
		if ( $diff_in_days < 7 ) {
			return sprintf(
				/* translators: Time difference between two dates. %s: Number of seconds/minutes/etc. */
				__( '%s ago', 'sensei-lms' ),
				human_time_diff( $date->getTimestamp() )
			);
		}

		return wp_date( get_option( 'date_format' ), $date->getTimestamp(), $timezone );
	}
}
