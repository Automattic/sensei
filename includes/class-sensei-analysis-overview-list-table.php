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
 */
class Sensei_Analysis_Overview_List_Table extends Sensei_List_Table {

	public $type;
	public $page_slug;

	/**
	 * The post type under which is the page registered.
	 *
	 * @var string
	 */
	private $post_type = 'course';

	/**
	 * Constructor
	 *
	 * @since  1.2.0
	 * @return  void
	 */
	public function __construct( $type = 'users' ) {
		$this->type      = in_array( $type, array( 'courses', 'lessons', 'users' ) ) ? $type : 'users';
		$this->page_slug = Sensei_Analysis::PAGE_SLUG;

		// Load Parent token into constructor
		parent::__construct( 'analysis_overview' );

		// Actions
		add_action( 'sensei_after_list_table', array( $this, 'data_table_footer' ) );

		add_filter( 'sensei_list_table_search_button_text', array( $this, 'search_button' ) );
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @since  1.7.0
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_columns() {

		switch ( $this->type ) {
			case 'courses':
				$columns = array(
					'title'            => __( 'Course', 'sensei-lms' ),
					'last_activity'    => __( 'Last Activity', 'sensei-lms' ),
					'completions'      => __( 'Completed', 'sensei-lms' ),
					'average_progress' => __( 'Average Progress', 'sensei-lms' ),
					'average_percent'  => __( 'Average Percentage', 'sensei-lms' ),
				);
				break;

			case 'lessons':
				$columns = array(
					'title'              => __( 'Lesson', 'sensei-lms' ),
					'students'           => __( 'Students', 'sensei-lms' ),
					'last_activity'      => __( 'Last Activity', 'sensei-lms' ),
					'completions'        => __( 'Completed', 'sensei-lms' ),
					'days_to_completion' => __( 'Days to Completion', 'sensei-lms' ),
				);
				break;

			case 'users':
			default:
				$columns = array(
					'title'             => __( 'Student', 'sensei-lms' ),
					'email'             => __( 'Email', 'sensei-lms' ),
					'last_activity'     => __( 'Last Activity', 'sensei-lms' ),
					'active_courses'    => __( 'Active Courses', 'sensei-lms' ),
					'completed_courses' => __( 'Completed Courses', 'sensei-lms' ),
					'average_grade'     => __( 'Average Grade', 'sensei-lms' ),
				);
				break;
		}
		// Backwards compatible filter name, moving forward should have single filter name
		$columns = apply_filters( 'sensei_analysis_overview_' . $this->type . '_columns', $columns, $this );
		$columns = apply_filters( 'sensei_analysis_overview_columns', $columns, $this );
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
					'title'            => array( 'title', false ),
					'completions'      => array( 'completions', false ),
					'average_progress' => array( 'average_progress', false ),
					'average_percent'  => array( 'average_percent', false ),
				);
				break;

			case 'lessons':
				$columns = array(
					'title'       => array( 'title', false ),
					'students'    => array( 'students', false ),
					'completions' => array( 'completions', false ),
				);
				break;

			case 'users':
			default:
				$columns = array(
					'title'             => array( 'user_login', false ),
					'email'             => array( 'user_email', false ),
					'active_courses'    => array( 'active_courses', false ),
					'completed_courses' => array( 'completed_courses', false ),
					'average_grade'     => array( 'average_grade', false ),
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
		if ( ! empty( $_GET['orderby'] ) ) {
			if ( array_key_exists( esc_html( $_GET['orderby'] ), $this->get_sortable_columns() ) ) {
				$orderby = esc_html( $_GET['orderby'] );
			}
		}

		// Handle order.
		$order = 'ASC';
		if ( ! empty( $_GET['order'] ) ) {
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

		// Handle search
		if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
			$args['search'] = esc_html( $_GET['s'] );
		}

		switch ( $this->type ) {
			case 'courses':
				$this->items = $this->get_courses( $args );
				break;

			case 'lessons':
				$this->items = $this->get_lessons( $args );
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

		$args = array(
			'orderby' => $orderby,
			'order'   => $order,
		);

		// Handle search
		if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
			$args['search'] = esc_html( $_GET['s'] );
		}

		// Start the csv with the column headings
		$column_headers = array();
		$columns        = $this->get_columns();
		foreach ( $columns as $key => $title ) {
			$column_headers[] = $title;
		}
		$data[] = $column_headers;

		switch ( $this->type ) {
			case 'courses':
				$this->items = $this->get_courses( $args );
				break;

			case 'lessons':
				$this->items = $this->get_lessons( $args );
				break;

			case 'users':
			default:
				$this->items = $this->get_learners( $args );
				break;
		}

		// Process each row.
		foreach ( $this->items as $item ) {
			$data[] = $this->get_row_data( $item );
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
				// Get Learners (i.e. those who have started)
				$course_args           = array(
					'post_id' => $item->ID,
					'type'    => 'sensei_course_status',
					'status'  => 'any',
				);
				$course_students_count = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_course_learners', $course_args, $item ) );

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

				// Course Lessons.
				$course_lessons = Sensei()->lesson->lesson_count( array( 'publish', 'private' ), $item->ID );

				// Get Percent Complete.
				$grade_args = array(
					'post_id'  => $item->ID,
					'type'     => 'sensei_course_status',
					'status'   => 'any',
					'meta_key' => 'percent',
				);

				$percent_count          = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_course_percentage', $grade_args, $item ), false );
				$percent_total          = Sensei_Grading::get_course_users_grades_sum( $item->ID );
				$course_average_percent = 0;

				if ( $percent_count > 0 && $percent_total > 0 ) {
					$course_average_percent = Sensei_Utils::quotient_as_absolute_rounded_number( $percent_total, $percent_count, 2 );
				}

				// Output course data
				if ( $this->csv_output ) {
					$course_title = apply_filters( 'the_title', $item->post_title, $item->ID );
				} else {
					$url = add_query_arg(
						array(
							'page'      => $this->page_slug,
							'course_id' => $item->ID,
							'post_type' => $this->post_type,
						),
						admin_url( 'edit.php' )
					);

					$course_title            = '<strong><a class="row-title" href="' . esc_url( $url ) . '">' . apply_filters( 'the_title', $item->post_title, $item->ID ) . '</a></strong>';
					$course_average_percent .= '%';
				}

				$average_course_progress = 0;
				if ( 0 !== $course_students_count && 0 !== $course_lessons ) {
					// Average course progress is calculated based on lessons completed for the course
					// divided by the total possible lessons completed.
					$average_course_progress = $item->completed_lesson_count / ( $course_students_count * $course_lessons ) * 100;
					$average_course_progress = esc_html(
					/* translators: Progress value. */
						sprintf( '%d%%', $average_course_progress )
					);
				}

				$column_data = apply_filters(
					'sensei_analysis_overview_column_data',
					array(
						'title'            => $course_title,
						'last_activity'    => $last_activity_date,
						'completions'      => $course_completions,
						'average_progress' => $average_course_progress,
						'average_percent'  => $course_average_percent,
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
					'status'  => array( 'complete', 'graded', 'passed', 'failed' ),
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
							'post_type' => $this->post_type,
						),
						admin_url( 'edit.php' )
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
							'page'      => $this->page_slug,
							'user_id'   => $item->ID,
							'post_type' => $this->post_type,
						),
						admin_url( 'edit.php' )
					);
					$user_name           = '<strong><a class="row-title" href="' . esc_url( $url ) . '">' . esc_html( $item->display_name ) . '</a></strong>';
					$user_average_grade .= '%';
				}
				$column_data = apply_filters(
					'sensei_analysis_overview_column_data',
					array(
						'title'             => $user_name,
						'email'             => $user_email,
						'last_activity'     => $this->get_last_activity_date( array( 'user_id' => $item->ID ) ),
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

		// Return the full date when doing a CSV export.
		if ( $this->csv_output ) {
			return $last_activity->comment_date_gmt;
		}

		$timezone           = new DateTimeZone( 'GMT' );
		$now                = new DateTime( 'now', $timezone );
		$last_activity_date = new DateTime( $last_activity->comment_date_gmt, $timezone );
		$diff_in_days       = $now->diff( $last_activity_date )->days;

		// Show a human readable date if activity is within 6 days.
		if ( $diff_in_days < 7 ) {
			return sprintf(
				/* translators: Time difference between two dates. %s: Number of seconds/minutes/etc. */
				__( '%s ago', 'sensei-lms' ),
				human_time_diff( strtotime( $last_activity->comment_date_gmt ) )
			);
		}

		return wp_date( get_option( 'date_format' ), $last_activity_date->getTimestamp(), $timezone );
	}

	/**
	 * Return array of course
	 *
	 * @since  1.7.0
	 * @return array courses
	 */
	private function get_courses( $args ) {
		$course_args = array(
			'post_type'        => $this->post_type,
			'post_status'      => array( 'publish', 'private' ),
			'posts_per_page'   => $args['number'],
			'offset'           => $args['offset'],
			'orderby'          => $args['orderby'],
			'order'            => $args['order'],
			'suppress_filters' => 0,
		);

		if ( $this->csv_output ) {
			$course_args['posts_per_page'] = '-1';
		}

		if ( isset( $args['search'] ) ) {
			$course_args['s'] = $args['search'];
		}
		add_filter( 'posts_clauses', [ $this, 'add_lesson_completion_count_to_course_query' ] );
		// Using WP_Query as get_posts() doesn't support 'found_posts'
		$courses_query = new WP_Query( apply_filters( 'sensei_analysis_overview_filter_courses', $course_args ) );
		remove_filter( 'posts_clauses', [ $this, 'add_lesson_completion_count_to_course_query' ] );
		$this->total_items = $courses_query->found_posts;
		return $courses_query->posts;
	}
	/**
	 * Add count of completed lessons per course for all the active students.
	 *
	 * @since  4.2.0
	 * @access public
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 *
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_lesson_completion_count_to_course_query( $clauses ) {
		global $wpdb;

		$clauses['fields'] .= ", COUNT( {$wpdb->comments}.comment_approved ) as completed_lesson_count";
		// Get postmeta rows that have meta_value of course ID and meta_key of '_lesson_course' to get relation
		// between lessons and the course.
		$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} as lessons ON lessons.meta_value = {$wpdb->posts}.ID";
		$clauses['join'] .= " AND lessons.meta_key IN ('_lesson_course')";
		// Get comments of type 'sensei_lesson_status' that have the post_id same as lessons ids
		// form the previous join and have one of the completed statuses.
		$clauses['join'] .= " LEFT JOIN {$wpdb->comments} ON {$wpdb->comments}.comment_post_ID = lessons.post_id";
		$clauses['join'] .= " AND {$wpdb->comments}.comment_type IN ('sensei_lesson_status')";
		$clauses['join'] .= " AND {$wpdb->comments}.comment_approved IN ( 'complete', 'graded', 'passed', 'failed' )";
		// Include only comments that have user ID of a users that are currently enroled in the course.
		$clauses['join'] .= " AND {$wpdb->comments}.user_id IN ( SELECT DISTINCT  {$wpdb->comments}.user_id
		 FROM {$wpdb->comments} WHERE {$wpdb->comments}.comment_type IN ('sensei_course_status')
		 AND {$wpdb->comments}.comment_post_ID IN ( {$wpdb->posts}.ID )
		 AND {$wpdb->comments}.comment_approved IN ('in-progress', 'complete', 'failed'))";

		$clauses['groupby'] .= "{$wpdb->posts}.ID";

		return $clauses;
	}

	/**
	 * Return array of lessons
	 *
	 * @since  1.7.0
	 *
	 * @param array $args Associative array for query.
	 *
	 * @return array lessons
	 */
	private function get_lessons( $args ) {
		$lessons_args = array(
			'post_type'        => 'lesson',
			'post_status'      => array( 'publish', 'private' ),
			'posts_per_page'   => $args['number'],
			'offset'           => $args['offset'],
			'orderby'          => $args['orderby'],
			'order'            => $args['order'],
			'suppress_filters' => 0,
		);

		if ( $this->csv_output ) {
			$lessons_args['posts_per_page'] = '-1';
		}

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
		$args['fields'] = array( 'ID', 'user_login', 'user_email', 'display_name' );

		/**
		 * Filter the WP_User_Query arguments
		 *
		 * @since 1.6.0
		 * @param $args
		 */
		$args              = apply_filters( 'sensei_analysis_overview_filter_users', $args );
		$wp_user_search    = new WP_User_Query( $args );
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
	 * Sets output when no items are found
	 * Overloads the parent method
	 *
	 * @since  1.2.0
	 * @return void
	 */
	public function no_items() {
		if ( ! $this->view || 'users' == $this->view ) {
			$type = 'learners';
		} else {
			$type = $this->view;
		}
		// translators: Placeholders %1$s and %3$s are opening and closing <em> tages, %2$s is the view type.
		echo wp_kses_post( sprintf( __( '%1$sNo %2$s found%3$s', 'sensei-lms' ), '<em>', $type, '</em>' ) );
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
			'page'      => $this->page_slug,
			'post_type' => $this->post_type,
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

		$menu['learners'] = '<a class="' . esc_attr( $learners_class ) . '" href="' . esc_url( add_query_arg( $learner_args, admin_url( 'edit.php' ) ) ) . '">' . esc_html__( 'Students', 'sensei-lms' ) . '</a>';
		$menu['courses']  = '<a class="' . esc_attr( $courses_class ) . '" href="' . esc_url( add_query_arg( $courses_args, admin_url( 'edit.php' ) ) ) . '">' . esc_html__( 'Courses', 'sensei-lms' ) . '</a>';
		$menu['lessons']  = '<a class="' . esc_attr( $lessons_class ) . '" href="' . esc_url( add_query_arg( $lesson_args, admin_url( 'edit.php' ) ) ) . '">' . esc_html__( 'Lessons', 'sensei-lms' ) . '</a>';

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
				'post_type'              => $this->post_type,
			),
			admin_url( 'edit.php' )
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

		$clauses['fields']  .= ", sum( CEILING( timestampdiff( second, STR_TO_DATE( {$wpdb->commentmeta}.meta_value, '%Y-%m-%d %H:%i:%s' ), {$wpdb->comments}.comment_date ) / (24 * 60 * 60) )) as days_to_complete";
		$clauses['join']    .= " LEFT JOIN {$wpdb->comments} ON {$wpdb->comments}.comment_post_ID = {$wpdb->posts}.ID";
		$clauses['join']    .= " AND {$wpdb->comments}.comment_type IN ('sensei_lesson_status')";
		$clauses['join']    .= " AND {$wpdb->comments}.comment_approved IN ( 'complete', 'graded', 'passed', 'failed' )";
		$clauses['join']    .= " AND {$wpdb->comments}.comment_post_ID = {$wpdb->posts}.ID";
		$clauses['join']    .= " LEFT JOIN {$wpdb->commentmeta} ON {$wpdb->comments}.comment_ID = {$wpdb->commentmeta}.comment_id";
		$clauses['join']    .= " AND {$wpdb->commentmeta}.meta_key = 'start'";
		$clauses['groupby'] .= " {$wpdb->posts}.ID";

		return $clauses;
	}

}

/**
 * Class WooThemes_Sensei_Analysis_Overview_List_Table
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Analysis_Overview_List_Table extends Sensei_Analysis_Overview_List_Table {}
