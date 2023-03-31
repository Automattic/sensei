<?php
/**
 * File containing the Sensei_Reports_Overview_List_Table_Courses class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Courses overview list table class.
 *
 * @since 4.3.0
 */
class Sensei_Reports_Overview_List_Table_Courses extends Sensei_Reports_Overview_List_Table_Abstract {
	/**
	 * Sensei grading related services.
	 *
	 * @var Sensei_Grading
	 */
	private $grading;

	/**
	 * Sensei course related services.
	 *
	 * @var Sensei_Course
	 */
	private $course;

	/**
	 * Sensei reports courses service.
	 *
	 * @var Sensei_Reports_Overview_Service_Courses
	 */
	private $reports_overview_service_courses;


	/**
	 * Constructor
	 *
	 * @param Sensei_Grading                                  $grading Sensei grading related services.
	 * @param Sensei_Course                                   $course Sensei course related services.
	 * @param Sensei_Reports_Overview_Data_Provider_Interface $data_provider Report data provider.
	 * @param Sensei_Reports_Overview_Service_Courses         $reports_overview_service_courses reports courses service.
	 */
	public function __construct( Sensei_Grading $grading, Sensei_Course $course, Sensei_Reports_Overview_Data_Provider_Interface $data_provider, Sensei_Reports_Overview_Service_Courses $reports_overview_service_courses ) {
		// Load Parent token into constructor.
		parent::__construct( 'courses', $data_provider );

		$this->grading                          = $grading;
		$this->course                           = $course;
		$this->reports_overview_service_courses = $reports_overview_service_courses;
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

		$all_course_ids    = $this->get_all_item_ids();
		$total_completions = 0;
		if ( ! empty( $all_course_ids ) ) {
			$total_completions = Sensei_Utils::sensei_check_for_activity(
				array(
					'type'     => 'sensei_course_status',
					'status'   => 'complete',
					'post__in' => $all_course_ids,
				)
			);
		}

		$total_average_progress = $this->reports_overview_service_courses->get_total_average_progress( $all_course_ids );

		$columns = array(
			'title'              => sprintf(
			// translators: Placeholder value is the number of courses.
				__( 'Course (%d)', 'sensei-lms' ),
				esc_html( count( $all_course_ids ) )
			),
			'last_activity'      => __( 'Last Activity', 'sensei-lms' ),
			'completions'        => sprintf(
			// translators: Placeholder value is the number of completed courses.
				__( 'Completed (%d)', 'sensei-lms' ),
				esc_html( $total_completions )
			),
			'average_progress'   => sprintf(
			// translators: Placeholder value is the total average progress for all courses.
				__( 'Average Progress (%s)', 'sensei-lms' ),
				esc_html( sprintf( '%d%%', $total_average_progress ) )
			),
			'average_percent'    => sprintf(
			// translators: Placeholder value is the average grade of all courses.
				__( 'Average Grade (%s%%)', 'sensei-lms' ),
				esc_html( ceil( $this->reports_overview_service_courses->get_courses_average_grade( $all_course_ids ) ) )
			),
			'days_to_completion' => sprintf(
			// translators: Placeholder value is average days to completion.
				__( 'Days to Completion (%d)', 'sensei-lms' ),
				ceil( $this->reports_overview_service_courses->get_average_days_to_completion( $all_course_ids ) )
			),
		);

		// Backwards compatible filter name, moving forward should have single filter name.
		$columns = apply_filters( 'sensei_analysis_overview_courses_columns', $columns, $this );
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
		$columns = array(
			'title'       => array( 'title', false ),
			'completions' => array( 'count_of_completions', false ),
		);

		// Backwards compatible filter name, moving forward should have single filter name.
		$columns = apply_filters( 'sensei_analysis_overview_courses_columns_sortable', $columns, $this );
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
		// Last Activity.
		$lessons = $this->course->course_lessons( $item->ID, 'any', 'ids' );

		// Get Course Completions.
		$course_args        = array(
			'post_id' => $item->ID,
			'type'    => 'sensei_course_status',
			'status'  => 'complete',
		);
		$course_completions = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_course_completions', $course_args, $item ) );

		// Average Grade will be N/A if the course has no lessons or quizzes, if none of the lessons
		// have a status of 'graded', 'passed' or 'failed', or if none of the quizzes have grades.
		$average_grade = __( 'N/A', 'sensei-lms' );

		// Get grades only if the course has lessons and quizzes.
		if ( ! empty( $lessons ) && $this->course->course_quizzes( $item->ID, true ) ) {
			$grade_args = array(
				'post__in' => $lessons,
				'type'     => 'sensei_lesson_status',
				'status'   => array( 'graded', 'passed', 'failed' ),
				'meta_key' => 'grade', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			);

			$percent_count = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_course_percentage', $grade_args, $item ), false );
			$percent_total = $this->grading::get_course_users_grades_sum( $item->ID );

			if ( $percent_count > 0 && $percent_total >= 0 ) {
				$average_grade = Sensei_Utils::quotient_as_absolute_rounded_number( $percent_total, $percent_count, 2 ) . '%';
			}
		}

		// Properties `count_of_completions` and `days_to_completion` where added to items in
		// `Sensei_Analysis_Overview_List_Table::add_days_to_completion_to_courses_queries`.
		// We made it due to improve performance of the report. Don't try to access these properties outside.
		$average_completion_days = $item->count_of_completions > 0 ? ceil( $item->days_to_completion / $item->count_of_completions ) : __( 'N/A', 'sensei-lms' );

		// Output course data.
		/** This filter is documented in wp-includes/post-template.php */
		$course_title = apply_filters( 'the_title', $item->post_title, $item->ID ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		if ( ! $this->csv_output ) {
			$url = add_query_arg(
				array(
					'page'      => $this->page_slug,
					'course_id' => $item->ID,
				),
				admin_url( 'admin.php' )
			);

			$course_title = '<strong><a class="row-title" href="' . esc_url( $url ) . '">' . $course_title . '</a></strong>';
		}

		$average_course_progress = $this->get_average_progress_for_courses_table( $item->ID );

		$column_data = apply_filters(
			'sensei_analysis_overview_column_data',
			array(
				'title'              => $course_title,
				'last_activity'      => $item->last_activity_date ? Sensei_Utils::format_last_activity_date( $item->last_activity_date ) : __( 'N/A', 'sensei-lms' ),
				'completions'        => $course_completions,
				'average_progress'   => $average_course_progress,
				'average_percent'    => $average_grade,
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
	 * Calculate average lesson progress per student for course.
	 *
	 * @since 4.3.0
	 *
	 * @param int $course_id Id of the course for which average progress is calculated.
	 *
	 * @return string The average progress for the course, or N/A if none.
	 */
	private function get_average_progress_for_courses_table( $course_id ) {
		// Fetch learners in course.
		$course_args = array(
			'post_id' => $course_id,
			'type'    => 'sensei_course_status',
			'status'  => array( 'in-progress', 'complete' ),
		);

		$course_students_count = Sensei_Utils::sensei_check_for_activity( $course_args );

		// Get all course lessons.
		$lessons        = Sensei()->course->course_lessons( $course_id, 'publish', 'ids' );
		$course_lessons = is_array( $lessons ) ? $lessons : array( $lessons );
		$total_lessons  = count( $course_lessons );

		// Get all completed lessons.
		$lesson_args     = array(
			'post__in' => $course_lessons,
			'type'     => 'sensei_lesson_status',
			'status'   => array( 'graded', 'ungraded', 'passed', 'failed', 'complete' ),
			'count'    => true,
		);
		$completed_count = (int) Sensei_Utils::sensei_check_for_activity( $lesson_args );
		// Calculate average progress.
		$average_course_progress = __( 'N/A', 'sensei-lms' );
		if ( $course_students_count && $total_lessons ) {
			// Average course progress is calculated based on lessons completed for the course
			// divided by the total possible lessons completed.
			$average_course_progress_value = $completed_count / ( $course_students_count * $total_lessons ) * 100;
			$average_course_progress       = esc_html(
				sprintf( '%d%%', round( $average_course_progress_value ) )
			);
		}
		return $average_course_progress;
	}

	/**
	 * The text for the search button.
	 *
	 * @return string
	 */
	public function search_button() {
		return __( 'Search Courses', 'sensei-lms' );
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
}
