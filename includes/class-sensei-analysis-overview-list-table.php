<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Analysis Overview Data Table.
 *
 * @package Analytics
 * @author Automattic
 *
 * @since 1.2.0
 * @deprecated 4.3.0 Use Sensei_Reports_Overview_List_Table_Factory to create proper instances.
 */
class Sensei_Analysis_Overview_List_Table extends Sensei_List_Table {

	public $type;
	public $page_slug;

	/**
	 * Constructor
	 *
	 * @param string $type Report type.
	 *
	 * @since  1.2.0
	 */
	public function __construct( $type = 'users' ) {
		_deprecated_function( __METHOD__, '4.3.0', 'Sensei_Reports_Overview_List_Table_Factory::create()' );

		$this->type      = in_array( $type, array( 'courses', 'lessons', 'users' ), true ) ? $type : 'users';
		$this->page_slug = Sensei_Analysis::PAGE_SLUG;

		// Load Parent token into constructor.
		parent::__construct( 'analysis_overview' );

		// Actions.
		add_action( 'sensei_before_list_table', array( $this, 'output_top_filters' ) );
		add_action( 'sensei_after_list_table', array( $this, 'data_table_footer' ) );
		add_filter( 'sensei_list_table_search_button_text', array( $this, 'search_button' ) );
		add_filter( 'sensei_analysis_overview_columns', array( $this, 'add_totals_to_report_column_headers' ) );
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @since  1.7.0
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_columns() {
		if ( $this->columns ) {
			return $this->columns;
		}

		switch ( $this->type ) {
			case 'courses':
				$total_completions = Sensei_Utils::sensei_check_for_activity(
					array(
						'type'   => 'sensei_course_status',
						'status' => 'complete',
					)
				);
				$columns           = array(
					'title'              => sprintf(
						// translators: Placeholder value is the number of courses.
						__( 'Course (%d)', 'sensei-lms' ),
						esc_html( $this->total_items )
					),
					'last_activity'      => __( 'Last Activity', 'sensei-lms' ),
					'completions'        => sprintf(
						// translators: Placeholder value is the number of completed courses.
						__( 'Completed (%d)', 'sensei-lms' ),
						esc_html( $total_completions )
					),
					'average_progress'   => __( 'Average Progress', 'sensei-lms' ),
					'average_grade'      => sprintf(
					// translators: Placeholder value is the average grade of all courses.
						__( 'Average Grade (%s%%)', 'sensei-lms' ),
						esc_html( ceil( Sensei()->grading->get_courses_average_grade() ) )
					),
					'days_to_completion' => sprintf(
						// translators: Placeholder value is average days to completion.
						__( 'Days to Completion (%d)', 'sensei-lms' ),
						ceil( Sensei()->course->get_average_days_to_completion() )
					),
				);
				break;

			case 'lessons':
				$columns = array(
					'title'              => __( 'Lesson', 'sensei-lms' ),
					'students'           => __( 'Students', 'sensei-lms' ),
					'last_activity'      => __( 'Last Activity', 'sensei-lms' ),
					'completions'        => __( 'Completed', 'sensei-lms' ),
					'completion_rate'    => __( 'Completion Rate', 'sensei-lms' ),
					'days_to_completion' => __( 'Days to Completion', 'sensei-lms' ),
				);
				break;

			case 'users':
			default:
				// Get total value for Courses Completed column in users table.
				$course_args_completed   = array(
					'type'   => 'sensei_course_status',
					'status' => 'complete',
				);
				$total_completed_courses = Sensei_Utils::sensei_check_for_activity( $course_args_completed );

				// Get the number of the courses that users have started.
				$course_args_started   = array(
					'type'   => 'sensei_course_status',
					'status' => 'any',
				);
				$total_courses_started = Sensei_Utils::sensei_check_for_activity( $course_args_started );

				// Get total average students grade.
				$total_average_grade = Sensei()->grading->get_graded_lessons_average_grade();

				$columns = array(
					// translators: Placeholder value is total count of students.
					'title'             => sprintf( __( 'Student (%d)', 'sensei-lms' ), esc_html( $this->total_items ) ),
					'email'             => __( 'Email', 'sensei-lms' ),
					'date_registered'   => __( 'Date Registered', 'sensei-lms' ),
					'last_activity'     => __( 'Last Activity', 'sensei-lms' ),
					// translators: Placeholder value is all active courses.
					'active_courses'    => sprintf( __( 'Active Courses (%d)', 'sensei-lms' ), esc_html( $total_courses_started - $total_completed_courses ) ),
					// translators: Placeholder value is all completed courses.
					'completed_courses' => sprintf( __( 'Completed Courses (%d)', 'sensei-lms' ), esc_html( $total_completed_courses ) ),
					// translators: Placeholder value is graded average value.
					'average_grade'     => sprintf( __( 'Average Grade (%d%%)', 'sensei-lms' ), esc_html( $total_average_grade ) ),
				);
				break;
		}

		// Backwards compatible filter name, moving forward should have single filter name
		$columns = apply_filters( 'sensei_analysis_overview_' . $this->type . '_columns', $columns, $this );
		$columns = apply_filters( 'sensei_analysis_overview_columns', $columns, $this );

		$this->columns = $columns;

		return $this->columns;
	}
	/**
	 * Append the count value to column headers where applicable
	 *
	 * @since  4.2.0
	 * @access private
	 *
	 * @param array $columns Array of columns for the report table.
	 * @return array The array of columns to use with the table with columns appended to their title
	 */
	public function add_totals_to_report_column_headers( array $columns ) {
		$column_value_map = array();
		switch ( $this->type ) {
			case 'lessons' && $this->get_course_filter_value():
				$total_counts                           = $this->get_totals_for_lesson_report_column_headers( $this->get_course_filter_value() );
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
				break;
			default:
				break;
		}
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
	 * @since  1.7.0
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_sortable_columns() {

		switch ( $this->type ) {
			case 'courses':
				$columns = array(
					'title'       => array( 'title', false ),
					'completions' => array( 'count_of_completions', false ),
				);
				break;

			case 'lessons':
				$columns = array(
					'title' => array( 'title', false ),
				);
				break;

			case 'users':
			default:
				$columns = array(
					'title'           => array( 'display_name', false ),
					'email'           => array( 'user_email', false ),
					'date_registered' => array( 'user_registered', false ),
					'last_activity'   => array( 'last_activity_date', false ),
				);
				break;
		}
		// Backwards compatible filter name, moving forward should have single filter name.
		$columns = apply_filters( 'sensei_analysis_overview_' . $this->type . '_columns_sortable', $columns, $this );
		$columns = apply_filters( 'sensei_analysis_overview_columns_sortable', $columns, $this );
		return $columns;
	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 *
	 * @since  1.7.0
	 * @return void
	 */
	public function prepare_items() {
		// Handle orderby.
		$orderby = '';
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! empty( $_GET['orderby'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$orderby = esc_html( $_GET['orderby'] );
		}

		// Handle order.
		$order = 'ASC';
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! empty( $_GET['order'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification,WordPress.PHP.StrictComparisons.LooseComparison,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$order = ( 'ASC' == strtoupper( $_GET['order'] ) ) ? 'ASC' : 'DESC';
		}

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
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$args['search'] = esc_html( wp_unslash( $_GET['s'] ) );
		}

		switch ( $this->type ) {
			case 'courses':
				$this->items = $this->get_courses( $args );
				break;

			case 'lessons':
				$this->items = $this->get_lessons( $args, $this->get_course_filter_value() );
				break;

			case 'users':
			default:
				$this->items = $this->get_learners( $args );
				break;
		}

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

	// phpcs:ignore Squiz.Commenting.FunctionComment.MissingParamTag
	/**
	 * Generate a csv report with different parameters, pagination, columns and table elements
	 *
	 * @since  1.7.0
	 * @return data
	 */
	public function generate_report( $report ) {
		$data = array();

		$this->csv_output = true;

		// Handle orderby.
		$orderby = '';
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! empty( $_GET['orderby'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$orderby = esc_html( $_GET['orderby'] );
		}

		// Handle order.
		$order = 'ASC';
		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['order'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification,WordPress.PHP.StrictComparisons.LooseComparison,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$order = ( 'ASC' == strtoupper( $_GET['order'] ) ) ? 'ASC' : 'DESC';
		}

		$args = array(
			'number'  => -1,
			'offset'  => 0,
			'orderby' => $orderby,
			'order'   => $order,
		);

		// Handle search.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$args['search'] = esc_html( $_GET['s'] );
		}

		switch ( $this->type ) {
			case 'courses':
				$this->items = $this->get_courses( $args );
				break;

			case 'lessons':
				$this->items = $this->get_lessons( $args, $this->get_course_filter_value() );
				break;

			case 'users':
			default:
				$this->items = $this->get_learners( $args );
				break;
		}

		// Start the CSV with the column headings.
		$column_headers = array();
		$columns        = $this->get_columns();

		foreach ( $columns as $key => $title ) {
			$column_headers[] = $title;
		}

		$data[] = $column_headers;

		// Process each row.
		foreach ( $this->items as $item ) {
			$data[] = array_replace( $columns, $this->get_row_data( $item ) );
		}

		return $data;
	}

	/**
	 * Generates the overall array for a single item in the display
	 *
	 * @since  1.7.0
	 * @param object $item The current item.
	 * @return array $column_data;
	 */
	protected function get_row_data( $item ) {

		switch ( $this->type ) {
			case 'courses':
				// Last Activity.
				$last_activity_date = __( 'N/A', 'sensei-lms' );
				$lessons            = Sensei()->course->course_lessons( $item->ID, 'any', 'ids' );

				if ( 0 < count( $lessons ) ) {
					$last_activity_date = $this->get_last_activity_date( array( 'post__in' => $lessons ) );
				}

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
				if ( ! empty( $lessons ) && Sensei()->course->course_quizzes( $item->ID, true ) ) {
					$grade_args = array(
						'post__in' => $lessons,
						'type'     => 'sensei_lesson_status',
						'status'   => array( 'graded', 'passed', 'failed' ),
						'meta_key' => 'grade',
					);

					$percent_count = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_course_percentage', $grade_args, $item ), false );
					$percent_total = Sensei_Grading::get_course_users_grades_sum( $item->ID );

					if ( $percent_count > 0 && $percent_total >= 0 ) {
						$average_grade = Sensei_Utils::quotient_as_absolute_rounded_number( $percent_total, $percent_count, 2 ) . '%';
					}
				}

				// Properties `count_of_completions` and `days_to_completion` where added to items in
				// `Sensei_Analysis_Overview_List_Table::add_days_to_completion_to_courses_queries`.
				// We made it due to improve performance of the report. Don't try to access these properties outside.
				$average_completion_days = $item->count_of_completions > 0 ? ceil( $item->days_to_completion / $item->count_of_completions ) : __( 'N/A', 'sensei-lms' );

				// Output course data
				if ( $this->csv_output ) {
					$course_title = apply_filters( 'the_title', $item->post_title, $item->ID );
				} else {
					$url = add_query_arg(
						array(
							'page'      => $this->page_slug,
							'course_id' => $item->ID,
						),
						admin_url( 'admin.php' )
					);

					$course_title = '<strong><a class="row-title" href="' . esc_url( $url ) . '">' . apply_filters( 'the_title', $item->post_title, $item->ID ) . '</a></strong>';
				}

				$average_course_progress = $this->get_average_progress_for_courses_table( $item->ID );
				$column_data             = apply_filters(
					'sensei_analysis_overview_column_data',
					array(
						'title'              => $course_title,
						'last_activity'      => $last_activity_date,
						'completions'        => $course_completions,
						'average_progress'   => $average_course_progress,
						'average_grade'      => $average_grade,
						'days_to_completion' => $average_completion_days,
					),
					$item,
					$this
				);
				break;

			case 'lessons':
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
					$lesson_title = apply_filters( 'the_title', $item->post_title, $item->ID );
				} else {
					$url          = add_query_arg(
						array(
							'page'      => $this->page_slug,
							'lesson_id' => $item->ID,
						),
						admin_url( 'admin.php' )
					);
					$lesson_title = '<strong><a class="row-title" href="' . esc_url( $url ) . '">' . apply_filters( 'the_title', $item->post_title, $item->ID ) . '</a></strong>';

				}
				$column_data = apply_filters(
					'sensei_analysis_overview_column_data',
					array(
						'title'              => $lesson_title,
						'students'           => $lesson_students,
						'last_activity'      => $this->get_last_activity_date( array( 'post_id' => $item->ID ) ),
						'completions'        => $lesson_completions,
						'completion_rate'    => $this->get_completion_rate( $lesson_completions, $lesson_students ),
						'days_to_completion' => $average_completion_days,
					),
					$item,
					$this
				);
				break;

			case 'users':
			default:
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
					'meta_key' => 'grade',
				);

				$grade_count        = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_user_lesson_grades', $grade_args, $item ), false );
				$grade_total        = Sensei_Grading::get_user_graded_lessons_sum( $item->ID );
				$user_average_grade = 0;

				if ( $grade_total > 0 && $grade_count > 0 ) {
					$user_average_grade = Sensei_Utils::quotient_as_absolute_rounded_number( $grade_total, $grade_count, 2 );
				}

				$user_email = $item->user_email;

				// Output the users data.
				if ( $this->csv_output ) {
					$user_name = Sensei_Learner::get_full_name( $item->ID );
				} else {
					$url                 = add_query_arg(
						array(
							'page'    => $this->page_slug,
							'user_id' => $item->ID,
						),
						admin_url( 'admin.php' )
					);
					$user_name           = '<strong><a class="row-title" href="' . esc_url( $url ) . '">' . esc_html( $item->display_name ) . '</a></strong>';
					$user_average_grade .= '%';
				}
				$last_activity_date = __( 'N/A', 'sensei-lms' );
				if ( $item->last_activity_date ) {
					$last_activity_date = $this->csv_output ? $item->last_activity_date : Sensei_Utils::format_last_activity_date( $item->last_activity_date );
				}
				$column_data = apply_filters(
					'sensei_analysis_overview_column_data',
					array(
						'title'             => $user_name,
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
				break;
		}

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
	 * Format the registration date.
	 *
	 * @since 4.3.0
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
	 * Get the date on which the last lesson was marked complete.
	 *
	 * @since 4.2.0
	 *
	 * @param array $args Array of arguments to pass to comments query.
	 *
	 * @return string The last activity date, or N/A if none.
	 */
	private function get_last_activity_date( array $args ): string {
		$default_args  = array(
			'number' => 1,
			'type'   => 'sensei_lesson_status',
			'status' => [ 'complete', 'passed', 'graded' ],
		);
		$args          = wp_parse_args( $args, $default_args );
		$last_activity = Sensei_Utils::sensei_check_for_activity( $args, true );

		if ( ! $last_activity ) {
			return __( 'N/A', 'sensei-lms' );
		}

		return $this->csv_output ? $last_activity->comment_date_gmt : Sensei_Utils::format_last_activity_date( $last_activity->comment_date_gmt );
	}

	/**
	 * Return array of course
	 *
	 * @since  1.7.0
	 * @return array courses
	 */
	private function get_courses( $args ) {
		$course_args = array(
			'post_type'        => 'course',
			'post_status'      => array( 'publish', 'private' ),
			'posts_per_page'   => $args['number'],
			'offset'           => $args['offset'],
			'orderby'          => $args['orderby'],
			'order'            => $args['order'],
			'suppress_filters' => 0,
		);

		if ( isset( $args['search'] ) ) {
			$course_args['s'] = $args['search'];
		}

		add_filter( 'posts_clauses', [ $this, 'filter_courses_by_last_activity' ] );
		add_filter( 'posts_clauses', [ $this, 'add_days_to_completion_to_courses_queries' ] );
		if ( isset( $args['orderby'] ) && ( 'count_of_completions' === $args['orderby'] ) ) {
			add_filter( 'posts_orderby', array( $this, 'add_orderby_custom_field_to_non_user_query' ), 10, 2 );
		}
		$courses_query = new WP_Query( apply_filters( 'sensei_analysis_overview_filter_courses', $course_args ) );
		remove_filter( 'posts_orderby', array( $this, 'add_orderby_custom_field_to_non_user_query' ), 10, 2 );
		remove_filter( 'posts_clauses', [ $this, 'filter_courses_by_last_activity' ] );
		remove_filter( 'posts_clauses', [ $this, 'add_days_to_completion_to_courses_queries' ] );

		$this->total_items = $courses_query->found_posts;

		return $courses_query->posts;

	}

	/**
	 * Return array of lessons.
	 *
	 * @since  1.7.0
	 *
	 * @param array $args      The query arguments.
	 * @param int   $course_id The selected course ID.
	 *
	 * @return array Lesson posts or empty array if no course is selected.
	 */
	private function get_lessons( array $args, int $course_id ): array {

		if ( ! $course_id ) {
			return [];
		}
		// Fetching the lesson ids beforehand because joining both postmeta and comment + commentmeta makes WP_Query very slow.
		$course_lessons = Sensei()->course->course_lessons( $course_id, 'any', 'ids' );
		$lessons_args   = array(
			'post_type'        => 'lesson',
			'post_status'      => array( 'publish', 'private' ),
			'posts_per_page'   => $args['number'],
			'offset'           => $args['offset'],
			'orderby'          => $args['orderby'],
			'order'            => $args['order'],
			'post__in'         => $course_lessons,
			'suppress_filters' => 0,
		);

		if ( isset( $args['search'] ) ) {
			$lessons_args['s'] = $args['search'];
		}

		add_filter( 'posts_clauses', [ $this, 'add_days_to_complete_to_lessons_query' ] );
		// Using WP_Query as get_posts() doesn't support 'found_posts'.
		$lessons_query = new WP_Query( apply_filters( 'sensei_analysis_overview_filter_lessons', $lessons_args ) );
		remove_filter( 'posts_clauses', [ $this, 'add_days_to_complete_to_lessons_query' ] );
		$this->total_items = $lessons_query->found_posts;
		return $lessons_query->posts;
	}

	/**
	 * Return array of learners
	 *
	 * @since  1.7.0
	 * @return array learners
	 */
	private function get_learners( $args ) {

		if ( ! empty( $args['search'] ) ) {
			$args = array(
				'search' => '*' . trim( $args['search'], '*' ) . '*',
			);
		}

		// This stops the full meta data of each user being loaded
		$args['fields'] = array( 'ID', 'user_login', 'user_email', 'user_registered', 'display_name' );

		/**
		 * Filter the WP_User_Query arguments
		 *
		 * @since 1.6.0
		 * @param $args
		 */
		$args = apply_filters( 'sensei_analysis_overview_filter_users', $args );

		add_action( 'pre_user_query', [ $this, 'add_last_activity_to_user_query' ] );
		add_action( 'pre_user_query', [ $this, 'filter_users_by_last_activity' ] );

		if ( isset( $args['orderby'] ) && 'last_activity_date' === $args['orderby'] ) {
			add_action( 'pre_user_query', [ $this, 'add_orderby_custom_field_to_query' ] );
		}

		$wp_user_search = new WP_User_Query( $args );
		remove_action( 'pre_user_query', [ $this, 'add_orderby_custom_field_to_query' ] );
		remove_action( 'pre_user_query', [ $this, 'add_last_activity_to_user_query' ] );
		remove_action( 'pre_user_query', [ $this, 'filter_users_by_last_activity' ] );

		$learners          = $wp_user_search->get_results();
		$this->total_items = $wp_user_search->get_total();

		return $learners;
	}

	/**
	 * Sets the stats boxes to render
	 *
	 * @since      1.2.0
	 * @deprecated 4.2.0
	 * @return     array $stats_to_render of stats boxes and values
	 */
	public function stats_boxes() {

		_deprecated_function( __METHOD__, '4.2.0' );

		// Get the data required
		$user_count          = count_users();
		$user_count          = apply_filters( 'sensei_analysis_total_users', $user_count['total_users'], $user_count );
		$total_courses       = Sensei()->course->course_count( array( 'publish', 'private' ) );
		$total_lessons       = Sensei()->lesson->lesson_count( array( 'publish', 'private' ) );
		$total_grade_count   = Sensei_Grading::get_graded_lessons_count();
		$total_grade_total   = Sensei_Grading::get_graded_lessons_sum();
		$total_average_grade = 0;

		if ( $total_grade_total > 0 && $total_grade_count > 0 ) {
			$total_average_grade = Sensei_Utils::quotient_as_absolute_rounded_number( $total_grade_total, $total_grade_count, 2 );
		}

		$course_args                 = array(
			'type'   => 'sensei_course_status',
			'status' => 'any',
		);
		$total_courses_started       = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_total_courses_started', $course_args ) );
		$course_args                 = array(
			'type'   => 'sensei_course_status',
			'status' => 'complete',
		);
		$total_courses_ended         = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_total_courses_ended', $course_args ) );
		$average_courses_per_learner = Sensei_Utils::quotient_as_absolute_rounded_number( $total_courses_started, $user_count, 2 );

		// Setup the boxes to render.
		$stats_to_render = array(
			__( 'Total Courses', 'sensei-lms' )           => $total_courses,
			__( 'Total Lessons', 'sensei-lms' )           => $total_lessons,
			__( 'Total Students', 'sensei-lms' )          => $user_count,
			__( 'Average Courses per Student', 'sensei-lms' ) => $average_courses_per_learner,
			__( 'Average Grade', 'sensei-lms' )           => $total_average_grade . '%',
			__( 'Total Completed Courses', 'sensei-lms' ) => $total_courses_ended,
		);
		return apply_filters( 'sensei_analysis_stats_boxes', $stats_to_render );
	}

	/**
	 * Get completion rate for a lesson.
	 *
	 * @since 4.2.0
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
	 *
	 * @since 4.2.0
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
	 * Output for table heading
	 *
	 * @since  1.2.0
	 * @deprecated 4.2.0
	 * @return void
	 */
	public function data_table_header() {
		_deprecated_function( __METHOD__, '4.2.0' );

		$menu = array();

		$query_args     = array(
			'page' => $this->page_slug,
		);
		$learners_class = $courses_class = $lessons_class = '';
		switch ( $this->type ) {
			case 'courses':
				$courses_class = 'current';
				break;

			case 'lessons':
				$lessons_class = 'current';
				break;

			default:
				$learners_class = 'current';
				break;
		}
		$learner_args         = $lesson_args = $courses_args = $query_args;
		$learner_args['view'] = 'users';
		$lesson_args['view']  = 'lessons';
		$courses_args['view'] = 'courses';

		$menu['learners'] = '<a class="' . esc_attr( $learners_class ) . '" href="' . esc_url( add_query_arg( $learner_args, admin_url( 'admin.php' ) ) ) . '">' . esc_html__( 'Students', 'sensei-lms' ) . '</a>';
		$menu['courses']  = '<a class="' . esc_attr( $courses_class ) . '" href="' . esc_url( add_query_arg( $courses_args, admin_url( 'admin.php' ) ) ) . '">' . esc_html__( 'Courses', 'sensei-lms' ) . '</a>';
		$menu['lessons']  = '<a class="' . esc_attr( $lessons_class ) . '" href="' . esc_url( add_query_arg( $lesson_args, admin_url( 'admin.php' ) ) ) . '">' . esc_html__( 'Lessons', 'sensei-lms' ) . '</a>';

		$menu = apply_filters( 'sensei_analysis_overview_sub_menu', $menu );
		if ( ! empty( $menu ) ) {
			echo '<ul class="subsubsub">' . "\n";
			foreach ( $menu as $class => $item ) {
				$menu[ $class ] = "\t<li class='$class'>$item";
			}
			echo wp_kses_post( implode( " |</li>\n", $menu ) ) . "</li>\n";
			echo '</ul>' . "\n";
		}
	}

	/**
	 * Output for table footer
	 *
	 * @since  1.7.0
	 * @return void
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
				'course_filter'          => $this->get_course_filter_value(),
				'start_date'             => $this->get_start_date_filter_value(),
				'end_date'               => $this->get_end_date_filter_value(),
			),
			admin_url( 'admin.php' )
		);

		echo '<a class="button button-primary" href="' . esc_url( wp_nonce_url( $url, 'sensei_csv_download', '_sdl_nonce' ) ) . '">' . esc_html__( 'Export all rows (CSV)', 'sensei-lms' ) . '</a>';
	}

	/**
	 * The text for the search button
	 *
	 * @since  1.7.0
	 * @return string $text
	 */
	public function search_button( $text = '' ) {
		switch ( $this->type ) {
			case 'courses':
				$text = __( 'Search Courses', 'sensei-lms' );
				break;

			case 'lessons':
				$text = __( 'Search Lessons', 'sensei-lms' );
				break;

			case 'users':
			default:
				$text = __( 'Search Students', 'sensei-lms' );
				break;
		}

		return $text;
	}

	/**
	 * Add the sum of days taken by each student to complete a lesson with returning lesson row.
	 *
	 * @since  4.2.0
	 * @access private
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 *
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_days_to_complete_to_lessons_query( $clauses ) {
		global $wpdb;

		$clauses['fields'] .= ", (SELECT SUM( ABS( DATEDIFF( STR_TO_DATE( {$wpdb->commentmeta}.meta_value, '%Y-%m-%d %H:%i:%s' ), {$wpdb->comments}.comment_date )) + 1 ) as days_to_complete";
		$clauses['fields'] .= " FROM {$wpdb->comments}";
		$clauses['fields'] .= " INNER JOIN {$wpdb->commentmeta} ON {$wpdb->comments}.comment_ID = {$wpdb->commentmeta}.comment_id";
		$clauses['fields'] .= " WHERE {$wpdb->comments}.comment_post_ID = {$wpdb->posts}.ID";
		$clauses['fields'] .= " AND {$wpdb->comments}.comment_type IN ('sensei_lesson_status')";
		$clauses['fields'] .= " AND {$wpdb->comments}.comment_approved IN ( 'complete', 'graded', 'passed', 'failed', 'ungraded' )";
		$clauses['fields'] .= " AND {$wpdb->commentmeta}.meta_key = 'start') as days_to_complete";

		return $clauses;
	}

	/**
	 * Filter the courses by last activity start/end date.
	 *
	 * @since  4.2.0
	 * @access private
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 *
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function filter_courses_by_last_activity( array $clauses ): array {
		global $wpdb;

		$start_date = $this->get_start_date_and_time();
		$end_date   = $this->get_end_date_and_time();

		if ( ! $start_date && ! $end_date ) {
			return $clauses;
		}
		// Fetch the lessons within the expected last activity range.
		$lessons_query = "SELECT cm.comment_post_id lesson_id, MAX(cm.comment_date_gmt) as comment_date_gmt
			FROM {$wpdb->comments} cm
			WHERE cm.comment_approved IN ('complete', 'passed', 'graded')
			AND cm.comment_type = 'sensei_lesson_status'";
		// Filter by start date.
		if ( $start_date ) {
			$lessons_query .= $wpdb->prepare(
				' AND cm.comment_date_gmt >= %s',
				$start_date
			);
		}
		$lessons_query .= ' GROUP BY cm.comment_post_id';
		// Fetch the course IDs associated with those lessons.
		$course_query = "SELECT DISTINCT(pm.meta_value) course_id
		FROM {$wpdb->postmeta} pm JOIN ({$lessons_query}) cm
		ON cm.lesson_id = pm.post_id
		AND pm.meta_key = '_lesson_course'
		GROUP BY pm.meta_value
		";
		// Filter by end date.
		if ( $end_date ) {
			$course_query .= $wpdb->prepare(
				' HAVING MAX(cm.comment_date_gmt) <= %s',
				$end_date
			);
		}
		$clauses['where'] .= " AND {$wpdb->posts}.ID IN ({$course_query})";

		return $clauses;
	}

	/**
	 * Add the `last_activity` field to the user query.
	 *
	 * @since  4.3.0
	 * @access private
	 *
	 * @param WP_User_Query $query The user query.
	 */
	public function add_last_activity_to_user_query( WP_User_Query $query ) {
		global $wpdb;

		$query->query_fields .= ", (
			SELECT MAX({$wpdb->comments}.comment_date_gmt)
			FROM {$wpdb->comments}
			WHERE {$wpdb->comments}.user_id = {$wpdb->users}.ID
			AND {$wpdb->comments}.comment_approved IN ('complete', 'passed', 'graded')
			AND {$wpdb->comments}.comment_type = 'sensei_lesson_status'
		) AS last_activity_date";
	}

	/**
	 * Order query based on the custom field.
	 *
	 * @since  4.3.0
	 * @access private
	 *
	 * @param WP_User_Query $query The user query.
	 */
	public function add_orderby_custom_field_to_query( WP_User_Query $query ) {
		$query->query_orderby = 'ORDER BY ' . $query->query_vars['orderby'] . ' ' . $query->query_vars['order'];
	}

	/**
	 * Order query based on the custom field.
	 *
	 * @since  4.3.0
	 * @access private
	 *
	 * @param array  $args Arguments Old orderby arguments.
	 * @param object $query Query.
	 */
	public function add_orderby_custom_field_to_non_user_query( $args, $query ) {
		return $query->query_vars['orderby'] . ' ' . $query->query_vars['order'];
	}

	/**
	 * Filter the users by last activity start/end date.
	 *
	 * This action should be called after `Sensei_Analysis_Overview_List_Table::add_last_activity_to_user_query`.
	 *
	 * @since  4.2.0
	 * @access private
	 *
	 * @param WP_User_Query $query The user query.
	 */
	public function filter_users_by_last_activity( WP_User_Query $query ) {
		global $wpdb;

		$start_date = $this->get_start_date_and_time();
		$end_date   = $this->get_end_date_and_time();

		if ( ! $start_date && ! $end_date ) {
			return;
		}

		$query->query_where .= ' HAVING 1 = 1';

		// Filter by start date.
		if ( $start_date ) {
			$query->query_where .= $wpdb->prepare(
				' AND last_activity_date >= %s',
				$start_date
			);
		}

		// Filter by end date.
		if ( $end_date ) {
			$query->query_where .= $wpdb->prepare(
				' AND last_activity_date <= %s',
				$end_date
			);
		}
	}

	/**
	 * Add the sum of days taken by each student to complete a course and the number of completions for each course.
	 *
	 * @since  4.2.0
	 * @access private
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 *
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_days_to_completion_to_courses_queries( $clauses ) {
		global $wpdb;

		// Get the number of days to complete a course: `days to complete = complete date - start date + 1`.
		$clauses['fields'] .= ", SUM(  ABS( DATEDIFF( {$wpdb->comments}.comment_date, STR_TO_DATE( {$wpdb->commentmeta}.meta_value, '%Y-%m-%d %H:%i:%s' ) ) ) + 1 ) AS days_to_completion";
		// We consider the course as completed if there is a comment and corresponding meta for it.
		$clauses['fields']  .= ", COUNT({$wpdb->commentmeta}.comment_id) AS count_of_completions";
		$clauses['join']    .= " LEFT JOIN {$wpdb->comments} ON {$wpdb->comments}.comment_post_ID = {$wpdb->posts}.ID";
		$clauses['join']    .= " AND {$wpdb->comments}.comment_type IN ('sensei_course_status')";
		$clauses['join']    .= " AND {$wpdb->comments}.comment_approved IN ( 'complete' )";
		$clauses['join']    .= " AND {$wpdb->comments}.comment_post_ID = {$wpdb->posts}.ID";
		$clauses['join']    .= " LEFT JOIN {$wpdb->commentmeta} ON {$wpdb->comments}.comment_ID = {$wpdb->commentmeta}.comment_id";
		$clauses['join']    .= " AND {$wpdb->commentmeta}.meta_key = 'start'";
		$clauses['groupby'] .= " {$wpdb->posts}.ID";

		return $clauses;
	}

	/**
	 * Get the selected course ID.
	 *
	 * @return int The course ID or 0 if none is selected.
	 */
	private function get_course_filter_value(): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for filtering.
		return isset( $_GET['course_filter'] ) ? (int) $_GET['course_filter'] : 0;
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
	private function get_start_date_and_time(): string {
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
	private function get_end_date_and_time(): string {
		$end_date = DateTime::createFromFormat( 'Y-m-d', $this->get_end_date_filter_value() );

		if ( ! $end_date ) {
			return '';
		}

		$end_date->setTime( 23, 59, 59 );

		return $end_date->format( 'Y-m-d H:i:s' );
	}
	/**
	 * Fetch the values required for the total counts added to column headers in lesson reports.
	 *
	 * @since  4.2.0
	 * @access private
	 *
	 * @param int $course_id Course Id to filter lessons with.
	 *
	 * @return object Object containing the required totals for column header.
	 */
	private function get_totals_for_lesson_report_column_headers( int $course_id ) {
		global $wpdb;
		$lessons      = Sensei()->course->course_lessons( $course_id, array( 'publish', 'private' ), 'ids' );
		$lesson_ids   = '0';
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

/**
 * Class WooThemes_Sensei_Analysis_Overview_List_Table
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Analysis_Overview_List_Table extends Sensei_Analysis_Overview_List_Table {}
