<?php
/**
 * File containing the Sensei_Reports_Overview_List_Table_Students class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Students overview list table class.
 *
 * @since 4.3.0
 */
class Sensei_Reports_Overview_List_Table_Students extends Sensei_Reports_Overview_List_Table_Abstract {

	/**
	 * Sensei reports courses service.
	 *
	 * @var Sensei_Reports_Overview_Service_Students
	 */
	private $reports_overview_service_students;

	/**
	 * Constructor
	 *
	 * @param Sensei_Reports_Overview_Data_Provider_Interface $data_provider Report data provider.
	 * @param Sensei_Reports_Overview_Service_Students        $reports_overview_service_students reports students service.
	 */
	public function __construct( Sensei_Reports_Overview_Data_Provider_Interface $data_provider, Sensei_Reports_Overview_Service_Students $reports_overview_service_students ) {
		// Load Parent token into constructor.
		parent::__construct( 'users', $data_provider );

		$this->reports_overview_service_students = $reports_overview_service_students;
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

		$total_completed_courses = 0;
		$total_courses_started   = 0;
		$total_average_grade     = 0;

		$user_ids = $this->get_all_item_ids();
		if ( $user_ids ) {
			// Get total value for Courses Completed column in users table.
			$total_completed_courses = Sensei_Utils::sensei_check_for_activity(
				[
					'user_id' => $user_ids,
					'type'    => 'sensei_course_status',
					'status'  => 'complete',
				]
			);

			// Get the number of the courses that users have started.
			$total_courses_started = Sensei_Utils::sensei_check_for_activity(
				[
					'user_id' => $user_ids,
					'type'    => 'sensei_course_status',
					'status'  => 'any',
				]
			);

			// Get total average students grade.
			$total_average_grade = $this->reports_overview_service_students->get_graded_lessons_average_grade( $user_ids );
		}

		$columns = array(
			// translators: Placeholder value is total count of students.
			'title'             => sprintf( __( 'Student (%d)', 'sensei-lms' ), count( $user_ids ) ),
			'email'             => __( 'Email', 'sensei-lms' ),
			'date_registered'   => __( 'Date Registered', 'sensei-lms' ),
			'last_activity'     => __( 'Last Activity', 'sensei-lms' ),
			// translators: Placeholder value is all active courses.
			'active_courses'    => sprintf( __( 'Active Courses (%d)', 'sensei-lms' ), $total_courses_started - $total_completed_courses ),
			// translators: Placeholder value is all completed courses.
			'completed_courses' => sprintf( __( 'Completed Courses (%d)', 'sensei-lms' ), $total_completed_courses ),
			// translators: Placeholder value is graded average value.
			'average_grade'     => sprintf( __( 'Average Grade (%d%%)', 'sensei-lms' ), $total_average_grade ),
		);

		// Backwards compatible filter name, moving forward should have single filter name.
		$columns = apply_filters( 'sensei_analysis_overview_users_columns', $columns, $this );
		$columns = apply_filters( 'sensei_analysis_overview_columns', $columns, $this );

		$this->columns = $columns;

		return $this->columns;
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @return array The array of columns to use with the table
	 */
	public function get_sortable_columns() {
		$columns = [
			'title'           => array( 'display_name', false ),
			'email'           => array( 'user_email', false ),
			'date_registered' => array( 'user_registered', false ),
			'last_activity'   => array( 'last_activity_date', false ),
		];

		// Backwards compatible filter name, moving forward should have single filter name.
		$columns = apply_filters( 'sensei_analysis_overview_users_columns_sortable', $columns, $this );
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
		// Get Started Courses.
		$course_args          = array(
			'user_id' => $item->ID,
			'type'    => 'sensei_course_status',
			'status'  => 'any',
		);
		$user_courses_started = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_user_courses_started', $course_args, $item ) );

		// Get Completed Courses.
		$course_args        = array(
			'user_id' => $item->ID,
			'type'    => 'sensei_course_status',
			'status'  => 'complete',
		);
		$user_courses_ended = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_user_courses_ended', $course_args, $item ) );

		// Get Quiz Grades.
		$grade_args = array(
			'user_id'  => $item->ID,
			'type'     => 'sensei_lesson_status',
			'status'   => 'any',
			'meta_key' => 'grade', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Filtering graded only.
		);

		$grade_count        = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_user_lesson_grades', $grade_args, $item ), false );
		$grade_total        = Sensei_Grading::get_user_graded_lessons_sum( $item->ID );
		$user_average_grade = 0;

		if ( $grade_total > 0 && $grade_count > 0 ) {
			$user_average_grade = Sensei_Utils::quotient_as_absolute_rounded_number( $grade_total, $grade_count, 2 );
		}

		$user_email = $item->user_email;

		// Output the users data.
		if ( ! $this->csv_output ) {
			$user_average_grade .= '%';
		}

		$last_activity_date = __( 'N/A', 'sensei-lms' );

		if ( ! empty( $item->last_activity_date ) ) {
			$last_activity_date = $this->csv_output ? $item->last_activity_date : Sensei_Utils::format_last_activity_date( $item->last_activity_date );
		}

		$column_data = apply_filters(
			'sensei_analysis_overview_column_data',
			array(
				'title'             => $this->format_user_name( $item->ID, $this->csv_output ),
				'email'             => $user_email,
				'date_registered'   => $this->format_date_registered( $item->user_registered ),
				'last_activity'     => $last_activity_date,
				'active_courses'    => ( $user_courses_started - $user_courses_ended ),
				'completed_courses' => $user_courses_ended,
				'average_grade'     => $user_average_grade,
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
	 * The text for the search button.
	 *
	 * @return string
	 */
	public function search_button() {
		return __( 'Search Students', 'sensei-lms' );
	}

	/**
	 * Return additional filters for current report.
	 *
	 * @return array
	 */
	protected function get_additional_filters(): array {
		return [
			'last_activity_date_from' => $this->get_start_date_and_time(),
			'last_activity_date_to'   => $this->get_end_date_and_time(),
		];
	}

	/**
	 * Format the registration date.
	 *
	 * @param string $date Registration date.
	 *
	 * @return string Formatted registration date.
	 */
	private function format_date_registered( string $date ) {
		$timezone = new DateTimeZone( 'GMT' );
		$date     = new DateTime( $date, $timezone );

		return wp_date( get_option( 'date_format' ), $date->getTimestamp(), $timezone );
	}

	/**
	 * Format user name wrapping or not with a link.
	 *
	 * @param int  $user_id user's id.
	 * @param bool $use_raw_name Indicate if it should return the wrap the name with the student link.
	 *
	 * @return string Return the student full name (first_name+last_name) optionally wrapped by a link
	 */
	private function format_user_name( $user_id, $use_raw_name ) {

		$user_name = Sensei_Learner::get_full_name( $user_id );

		if ( $use_raw_name ) {
			return $user_name;
		}

		$url = add_query_arg(
			array(
				'page'    => $this->page_slug,
				'user_id' => $user_id,
			),
			admin_url( 'admin.php' )
		);

		return '<strong><a class="row-title" href="' . esc_url( $url ) . '">' . esc_html( $user_name ) . '</a></strong>';
	}
}
