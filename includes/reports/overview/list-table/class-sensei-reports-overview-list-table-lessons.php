<?php
/**
 * File containing the Sensei_Reports_Overview_List_Table_Lessons class.
 *
 * @package sensei
 */

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
	 * Constructor
	 *
	 * @param Sensei_Course                                   $course Sensei course related services.
	 * @param Sensei_Reports_Overview_Data_Provider_Interface $data_provider Report data provider.
	 */
	public function __construct( Sensei_Course $course, Sensei_Reports_Overview_Data_Provider_Interface $data_provider ) {
		// Load Parent token into constructor.
		parent::__construct( 'lessons', $data_provider );
		$this->course = $course;

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
		$columns = apply_filters( 'sensei_analysis_overview_lessons_columns', $columns, $this );
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
		$column_value_map['days_to_completion'] = $total_counts->lesson_completed_count > 0
			? ceil( $total_counts->days_to_complete_sum / $total_counts->lesson_completed_count )
			: __( 'N/A', 'sensei-lms' );
		$column_value_map['completion_rate']    = $total_counts->lesson_start_count > 0
			? Sensei_Utils::quotient_as_absolute_rounded_percentage( $total_counts->lesson_completed_count, $total_counts->lesson_start_count ) . '%'
			: '0%';
		foreach ( $column_value_map as $key => $value ) {
			if ( key_exists( $key, $columns ) ) {
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
		$columns = apply_filters( 'sensei_analysis_overview_lessons_columns_sortable', $columns, $this );
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
		// Get Learners (i.e. those who have started).
		$lesson_args     = array(
			'post_id' => $item->ID,
			'type'    => 'sensei_lesson_status',
			'status'  => 'any',
		);
		$lesson_students = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_lesson_learners', $lesson_args, $item ) );

		// Get Course Completions.
		$lesson_args        = array(
			'post_id' => $item->ID,
			'type'    => 'sensei_lesson_status',
			'status'  => array( 'complete', 'graded', 'passed', 'failed', 'ungraded' ),
			'count'   => true,
		);
		$lesson_completions = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_lesson_completions', $lesson_args, $item ) );
		// Taking the ceiling value for the average.
		$average_completion_days = $lesson_completions > 0 ? ceil( $item->days_to_complete / $lesson_completions ) : __( 'N/A', 'sensei-lms' );

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
		global $wpdb;

		// Add search filter to query arguments.
		$query_args = [];
		// phpcs:ignore WordPress.Security.NonceVerification -- Argument is used for searching.
		if ( ! empty( $_GET['s'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$query_args['s'] = esc_html( $_GET['s'] );
		}
		$lessons    = $this->course->course_lessons( $course_id, array( 'publish', 'private' ), 'ids', $query_args );
		$lesson_ids = '0';

		$lesson_count = count( $lessons );
		if ( 0 < $lesson_count ) {
			$lesson_ids = implode( ',', $lessons );
		};

		$default_args  = array(
			'fields' => 'ids',
		);
		$modules_count = count( wp_get_object_terms( $lessons, 'module', $default_args ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Performance improvement.
		$lesson_completion_info                      = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT(lesson_students.user_id)) unique_student_count
			, COUNT(lesson_students.comment_id) lesson_start_count
			, SUM(IF(lesson_students.`comment_approved` IN ('graded','passed','complete','failed', 'ungraded' ), 1, 0)) lesson_completed_count
			, SUM(IF(lesson_students.`comment_approved` IN ('graded','passed','complete','failed', 'ungraded' ), ABS( DATEDIFF( STR_TO_DATE( lesson_start.meta_value, %s ), lesson_students.comment_date ) ) + 1, 0)) days_to_complete_sum
			FROM $wpdb->comments lesson_students
			LEFT JOIN $wpdb->commentmeta lesson_start ON lesson_start.comment_id = lesson_students.comment_id
			WHERE lesson_start.meta_key = 'start' AND lesson_students.comment_post_id IN ( $lesson_ids )", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'%Y-%m-%d %H:%i:%s'
			)
		);
		$lesson_completion_info->lesson_count        = $lesson_count;
		$lesson_completion_info->unique_module_count = $modules_count;
		return $lesson_completion_info;
	}
}
