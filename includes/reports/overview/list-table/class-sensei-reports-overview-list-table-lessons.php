<?php
/**
 * File containing the Sensei_Reports_Overview_List_Table_Lessons class.
 *
 * @package sensei
 */

use Sensei\Internal\Services\Grading_Item;
use Sensei\Internal\Services\Progress_Aggregation_Service_Interface;
use Sensei\Internal\Services\Progress_Query_Service_Factory;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Lessons overview list table class.
 *
 * @since 4.3.0
 */
class Sensei_Reports_Overview_List_Table_Lessons extends Sensei_Reports_Overview_List_Table_Abstract {
	/**
	 * Sensei course related services.
	 *
	 * @var Sensei_Course
	 */
	private $course;

	/**
	 * The progress aggregation service.
	 *
	 * @var Progress_Aggregation_Service_Interface
	 */
	private Progress_Aggregation_Service_Interface $aggregation_service;

	/**
	 * Constructor.
	 *
	 * @param Sensei_Course                                   $course              Sensei course related services.
	 * @param Sensei_Reports_Overview_Data_Provider_Interface $data_provider       Report data provider.
	 * @param Progress_Aggregation_Service_Interface|null     $aggregation_service The progress aggregation service.
	 */
	public function __construct( Sensei_Course $course, Sensei_Reports_Overview_Data_Provider_Interface $data_provider, ?Progress_Aggregation_Service_Interface $aggregation_service = null ) {
		// Load Parent token into constructor.
		parent::__construct( 'lessons', $data_provider );
		$this->course              = $course;
		$this->aggregation_service = $aggregation_service
			?? ( new Progress_Query_Service_Factory() )->create_aggregation_service();

		add_filter( 'sensei_analysis_overview_columns', array( $this, 'add_totals_to_report_column_headers' ) );
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @return array The array of columns to use with the table
	 */
	public function get_columns() {
		if ( $this->columns ) {
			return $this->columns;
		}
		$columns = array(
			'title'              => __( 'Lesson', 'sensei-lms' ),
			'students'           => __( 'Students', 'sensei-lms' ),
			'last_activity'      => __( 'Last Activity', 'sensei-lms' ),
			'completions'        => __( 'Completed', 'sensei-lms' ),
			'completion_rate'    => __( 'Completion Rate', 'sensei-lms' ),
			'days_to_completion' => __( 'Days to Completion', 'sensei-lms' ),
		);

		// Backwards compatible filter name, moving forward should have single filter name.
		/**
		 * Filter the columns for the lesson report.
		 *
		 * @hook sensei_analysis_overview_lessons_columns
		 *
		 * @param {array} $columns The array of columns to use with the table.
		 * @param {Sensei_Reports_Overview_List_Table_Lessons} $this The current instance of the class.
		 * @return {array} The array of columns to use with the table.
		 */
		$columns = apply_filters( 'sensei_analysis_overview_lessons_columns', $columns, $this );

		/**
		 * Filter the columns for the lesson report.
		 *
		 * @hook sensei_analysis_overview_columns
		 *
		 * @param {array} $columns The array of columns to use with the table.
		 * @param {Sensei_Reports_Overview_List_Table_Lessons} $this The current instance of the class.
		 * @return {array} The array of columns to use with the table.
		 */
		$columns = apply_filters( 'sensei_analysis_overview_columns', $columns, $this );

		$this->columns = $columns;

		return $this->columns;
	}
	/**
	 * Append the count value to column headers where applicable
	 *
	 * @since  4.3.0
	 * @access private
	 *
	 * @param array $columns Array of columns for the report table.
	 * @return array The array of columns to use with the table with columns appended to their title
	 */
	public function add_totals_to_report_column_headers( array $columns ) {
		if ( 0 === $this->get_course_filter_value() ) {
			return $columns;
		}
		$total_counts     = $this->get_totals_for_lesson_report_column_headers( $this->get_course_filter_value() );
		$column_value_map = array();

		$column_value_map['title']              = $total_counts->lesson_count;
		$column_value_map['lesson_module']      = $total_counts->unique_module_count;
		$column_value_map['students']           = $total_counts->unique_student_count;
		$column_value_map['completions']        = $total_counts->lesson_completed_count > 0 && $total_counts->lesson_count > 0
			? ceil( $total_counts->lesson_completed_count / $total_counts->lesson_count )
			: 0;
		$column_value_map['days_to_completion'] = $total_counts->days_to_complete_count > 0
			? ceil( $total_counts->days_to_complete_sum / $total_counts->days_to_complete_count )
			: __( 'N/A', 'sensei-lms' );
		$column_value_map['completion_rate']    = $total_counts->lesson_start_count > 0
			? Sensei_Utils::quotient_as_absolute_rounded_percentage( $total_counts->lesson_completed_count, $total_counts->lesson_start_count ) . '%'
			: '0%';
		foreach ( $column_value_map as $key => $value ) {
			if ( array_key_exists( $key, $columns ) ) {
				$columns[ $key ] = $columns[ $key ] . ' (' . esc_html( $value ) . ')';
			}
		}
		return $columns;
	}
	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @return array The array of columns to use with the table
	 */
	public function get_sortable_columns() {
		$columns = array(
			'title' => array( 'title', false ),
		);

		// Backwards compatible filter name, moving forward should have single filter name.
		/**
		 * Filter the sortable columns for the lesson report.
		 *
		 * @hook sensei_analysis_overview_lessons_columns_sortable
		 *
		 * @param {array} $columns The array of sortable columns to use with the table.
		 * @param {Sensei_Reports_Overview_List_Table_Lessons} $this The current instance of the class.
		 * @return {array} The array of sortable columns to use with the table.
		 */
		$columns = apply_filters( 'sensei_analysis_overview_lessons_columns_sortable', $columns, $this );

		/**
		 * Filter the sortable columns for the lesson report.
		 *
		 * @hook sensei_analysis_overview_columns_sortable
		 *
		 * @param {array} $columns The array of sortable columns to use with the table.
		 * @param {Sensei_Reports_Overview_List_Table_Lessons} $this The current instance of the class.
		 * @return {array} The array of sortable columns to use with the table.
		 */
		$columns = apply_filters( 'sensei_analysis_overview_columns_sortable', $columns, $this );

		return $columns;
	}

	/**
	 * Generates the overall array for a single item in the display
	 *
	 * @param object $item The current item.
	 *
	 * @return array Report row data.
	 * @throws Exception If date-time conversion fails.
	 */
	protected function get_row_data( $item ) {
		if ( has_filter( 'sensei_analysis_lesson_learners' ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- _deprecated_hook handles its own output.
			_deprecated_hook( 'sensei_analysis_lesson_learners', '4.26.0', '', __( 'This filter is no longer used. Lesson counts now use the progress aggregation service.', 'sensei-lms' ) );
		}
		if ( has_filter( 'sensei_analysis_lesson_completions' ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- _deprecated_hook handles its own output.
			_deprecated_hook( 'sensei_analysis_lesson_completions', '4.26.0', '', __( 'This filter is no longer used. Lesson counts now use the progress aggregation service.', 'sensei-lms' ) );
		}

		$status_counts = $this->aggregation_service->count_statuses(
			[
				'type'    => 'lesson',
				'post_id' => $item->ID,
			]
		);

		$lesson_students    = array_sum( $status_counts );
		$lesson_completions = 0;
		foreach ( Grading_Item::COMPLETED_STATUSES as $status ) {
			$lesson_completions += $status_counts[ $status ] ?? 0;
		}

		// Days-to-complete can only be averaged over statuses that have a
		// completion date (excludes failed/ungraded).
		$days_divisor = 0;
		foreach ( Grading_Item::STATUSES_WITH_COMPLETION_DATE as $status ) {
			$days_divisor += $status_counts[ $status ] ?? 0;
		}

		// Taking the ceiling value for the average.
		$average_completion_days = $days_divisor > 0 ? ceil( $item->days_to_complete / $days_divisor ) : __( 'N/A', 'sensei-lms' );

		// Output lesson data.
		if ( $this->csv_output ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$lesson_title = apply_filters( 'the_title', $item->post_title, $item->ID );
		} else {
			$url = add_query_arg(
				array(
					'page'      => $this->page_slug,
					'lesson_id' => $item->ID,
				),
				admin_url( 'admin.php' )
			);
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$lesson_title = '<strong><a class="row-title" href="' . esc_url( $url ) . '">' . apply_filters( 'the_title', $item->post_title, $item->ID ) . '</a></strong>';
		}

		/**
		 * Filter the row data for the Analysis Overview list table.
		 *
		 * @hook sensei_analysis_overview_column_data
		 *
		 * @param {array} $column_data Array of column data for the report table.
		 * @param {object|WP_Post|WP_User} $item Current row object.
		 * @param {Sensei_Reports_Overview_List_Table_Lessons} $this Current instance of the list table.
		 * @return {array} Filtered array of column data for the report table.
		 */
		$column_data = apply_filters(
			'sensei_analysis_overview_column_data',
			array(
				'title'              => $lesson_title,
				'lesson_module'      => $this->get_row_module( $item->ID ),
				'students'           => $lesson_students,
				'last_activity'      => $item->last_activity_date ? Sensei_Utils::format_last_activity_date( $item->last_activity_date ) : __( 'N/A', 'sensei-lms' ),
				'completions'        => $lesson_completions,
				'completion_rate'    => $this->get_completion_rate( $lesson_completions, $lesson_students ),
				'days_to_completion' => $average_completion_days,
			),
			$item,
			$this
		);

		$escaped_column_data = array();

		foreach ( $column_data as $key => $data ) {
			$escaped_column_data[ $key ] = wp_kses_post( $data );
		}

		return $escaped_column_data;
	}

	/**
	 * Get the module data for a row.
	 *
	 * @param int $lesson_id The lesson post ID.
	 *
	 * @return string
	 */
	private function get_row_module( int $lesson_id ): string {
		$module        = '';
		$modules_terms = wp_get_post_terms( $lesson_id, 'module' );

		foreach ( $modules_terms as $term ) {
			if ( $this->csv_output ) {
				$module = esc_html( $term->name );
			} else {
				$module = sprintf(
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'edit-tags.php?action=edit&taxonomy=module&tag_ID=' . $term->term_id ) ),
					esc_html( $term->name )
				);
			}

			break;
		}

		return $module;
	}

	/**
	 * Get completion rate for a lesson.
	 *
	 * @since 4.2.1
	 *
	 * @param int $lesson_completion_count Number of students who has completed this lesson.
	 * @param int $lesson_student_count Number of students who has started this lesson.
	 *
	 * @return string The completion rate or 'N/A' if there are no students.
	 */
	private function get_completion_rate( int $lesson_completion_count, int $lesson_student_count ): string {
		if ( 0 >= $lesson_student_count ) {
			return __( 'N/A', 'sensei-lms' );
		}
		return Sensei_Utils::quotient_as_absolute_rounded_percentage( $lesson_completion_count, $lesson_student_count ) . '%';
	}
	/**
	 * The text for the search button.
	 *
	 * @return string
	 */
	public function search_button() {
		return __( 'Search Lessons', 'sensei-lms' );
	}

	/**
	 * Return additional filters for current report.
	 *
	 * @return array
	 */
	protected function get_additional_filters(): array {
		return [
			'course_id' => $this->get_course_filter_value(),
		];
	}
	/**
	 * Fetch the values required for the total counts added to column headers in lesson reports.
	 *
	 * @since  4.3.0
	 * @access private
	 *
	 * @param int $course_id Course Id to filter lessons with.
	 *
	 * @return object Object containing the required totals for column header.
	 */
	private function get_totals_for_lesson_report_column_headers( int $course_id ) {
		// Add search filter to query arguments.
		$query_args = [];
		// phpcs:ignore WordPress.Security.NonceVerification -- Argument is used for searching.
		if ( ! empty( $_GET['s'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$query_args['s'] = esc_html( $_GET['s'] );
		}
		$lessons = $this->course->course_lessons( $course_id, array( 'publish', 'private' ), 'ids', $query_args );

		$lesson_count = count( $lessons );

		$default_args  = array(
			'fields' => 'ids',
		);
		$modules       = wp_get_object_terms( $lessons, 'module', $default_args );
		$modules_count = is_countable( $modules ) ? count( $modules ) : 0;

		$totals = $this->aggregation_service->get_lesson_totals( array_map( 'intval', $lessons ) );

		$result                      = (object) $totals;
		$result->lesson_count        = $lesson_count;
		$result->unique_module_count = $modules_count;
		return $result;
	}
}
