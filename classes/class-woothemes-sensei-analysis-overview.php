<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Analysis Overview List Table Class
 *
 * All functionality pertaining to the Admin Analysis Overview Data Table in Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Core
 * @author WooThemes
 * @since 1.2.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 * - build_data_array()
 * - overview_users()
 * - overview_courses()
 * - overview_lessons()
 * - load_stats()
 * - stats_boxes()
 * - no_items()
 * - data_table_header()
 */
class WooThemes_Sensei_Analysis_Overview_List_Table extends WooThemes_Sensei_List_Table {
	public $user_count;
	public $total_courses;
	public $total_lessons;
	public $total_average_grade;
	public $total_courses_started;
	public $total_courses_ended;
	public $average_courses_per_learner;
	public $type;

	/**
	 * Constructor
	 * @since  1.2.0
	 * @return  void
	 */
	public function __construct ( $type = '' ) {
		$this->type = $type;
		// Load Parent token into constructor
		parent::__construct( 'analysis_overview' );
		// Default Columns
		switch ( $this->type ) {
			case 'courses':
				$this->columns = apply_filters( 'sensei_analysis_overview_courses_columns', array(
					'course_title' => __( 'Course', 'woothemes-sensei' ),
					'course_students' => __( 'Learners', 'woothemes-sensei' ),
					'course_lessons' => __( 'Lessons', 'woothemes-sensei' ),
					'course_completions' => __( 'Completed', 'woothemes-sensei' )
				) );
				// Sortable Columns
				$this->sortable_columns = apply_filters( 'sensei_analysis_overview_courses_columns_sortable', array(
					'course_title' => array( 'course_title', false ),
					'course_students' => array( 'course_students', false ),
					'course_lessons' => array( 'course_lessons', false ),
					'course_completions' => array( 'course_completions', false )
				) );
			break;
			case 'lessons':
				$this->columns = apply_filters( 'sensei_analysis_overview_lessons_columns', array(
					'lesson_title' => __( 'Lesson', 'woothemes-sensei' ),
					'lesson_course' => __( 'Course', 'woothemes-sensei' ),
					'lesson_students' => __( 'Learners', 'woothemes-sensei' ),
					'lesson_completions' => __( 'Completed', 'woothemes-sensei' ),
					'lesson_average_grade' => __( 'Average Grade', 'woothemes-sensei' )
				) );
				// Sortable Columns
				$this->sortable_columns = apply_filters( 'sensei_analysis_overview_lessons_columns_sortable', array(
					'lesson_title' => array( 'lesson_title', false ),
					'lesson_course' => array( 'lesson_course', false ),
					'lesson_students' => array( 'lesson_students', false ),
					'lesson_completions' => array( 'lesson_completions', false ),
					'lesson_average_grade' => array( 'lesson_average_grade', false )
				) );
			break;
			default :
				$this->columns = apply_filters( 'sensei_analysis_overview_users_columns', array(
					'user_login' => __( 'Learner', 'woothemes-sensei' ),
					'user_registered' => __( 'Date Registered', 'woothemes-sensei' ),
					'user_active_courses' => __( 'Active Courses', 'woothemes-sensei' ),
					'user_completed_courses' => __( 'Completed Courses', 'woothemes-sensei' ),
					'user_average_grade' => __( 'Average Grade', 'woothemes-sensei' )
				) );
				// Sortable Columns
				$this->sortable_columns = apply_filters( 'sensei_analysis_overview_users_columns_sortable', array(
					'user_login' => array( 'user_login', false ),
					'user_registered' => array( 'user_registered', false ),
					'user_active_courses' => array( 'user_active_courses', false ),
					'user_completed_courses' => array( 'user_completed_courses', false ),
					'user_average_grade' => array( 'user_average_grade', false )
				) );
			break;
		} // End Switch Statement
		// Actions
		add_action( 'sensei_before_list_table', array( $this, 'data_table_header' ) );
	} // End __construct()

	/**
	 * build_data_array builds the data for use in the table
	 * Overloads the parent method
	 * @since  1.2.0
	 * @return array
	 */
	public function build_data_array( $raw = false ) {
		global $woothemes_sensei;
		$return_array = array();
		// Get the data required
		$args_array = array( 'raw' => $raw );
		if ( isset( $_GET['s'] ) && '' != esc_html( $_GET['s'] ) ) {
			$args_array['search'] = esc_html( $_GET['s'] );
		} // End If Statement
		switch ( $this->type ) {
			case 'courses':
				$return_array = $this->overview_courses( $args_array );
			break;
			case 'lessons':
				$return_array = $this->overview_lessons( $args_array );
			break;
			default :
				$this->use_users = true;
				$return_array = $this->overview_users( $args_array );
			break;
		} // End Switch Statement
		$return_array = $this->array_sort_reorder( $return_array );
		return $return_array;
	} // End build_data_array()

	/**
	 * overview_users loads users overview data
	 * @since  1.2.0
	 * @param  array $args_array arguments for data
	 * @return array $return_array data for table
	 */
	public function overview_users( $args_array ) {
		global $woothemes_sensei;
		$return_array = array();
		$raw = $args_array['raw'];
		// Get Users
		$offset = '';
		if ( isset($_GET['paged']) && 0 < intval($_GET['paged']) ) {
			$offset = $this->per_page * ( $_GET['paged'] - 1 );
		} // End If Statement
		$usersearch = isset( $_GET['s'] ) ? trim( $_GET['s'] ) : '';
		$role = isset( $_REQUEST['role'] ) ? $_REQUEST['role'] : '';
		$args_array = array(
			'number' => $this->per_page,
			'offset' => $offset,
			'role' => $role,
			'search' => $usersearch,
			'fields' => 'all_with_meta'
		);
		if ( '' !== $args_array['search'] ) {
			$args_array['search'] = '*' . $args_array['search'] . '*';
		} // End If Statement

		// Get Users
		$users = $this->user_query_results( $args_array );

		// User Loop
		foreach ( $users as $user_key => $user_item ) {
			// Get Started Courses
			$user_courses_started = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'user_id' => $user_item->ID, 'type' => 'sensei_course_start' ), true );
			// Get Completed Courses
			$user_courses_ended = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'user_id' => $user_item->ID, 'type' => 'sensei_course_end' ), true );
			// Get Quiz Grades
			$user_quiz_grades = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'user_id' => $user_item->ID, 'type' => 'sensei_quiz_grade' ), true );
			// Calculate the average grade for the user
			$grade_count = 0;
			$grade_total = 0.00;
			foreach ( $user_quiz_grades as $quiz_key => $quiz_value ) {
				$grade_total = $grade_total + doubleval( $quiz_value->comment_content );
				$grade_count++;
			} // End For Loop
			// Handle Division by Zero
			if ( 0 == $grade_count ) {
				$grade_count = 1;
			} // End If Statement
			$user_average_grade = abs( round( doubleval( $grade_total / $grade_count ), 2 ) );
			// Output the users data
			if ( $raw ) {
				$user_login = $user_item->display_name;
			} else {
				$user_login = '<a href="' . add_query_arg( array( 'page' => 'sensei_analysis', 'user' => $user_item->ID ), admin_url( 'admin.php' ) ) . '">'.$user_item->display_name.'</a>';
				$user_average_grade = $user_average_grade . '%';
			} // End If Statement
			array_push( $return_array, apply_filters( 'sensei_analysis_overview_users_column_data', array( 	'user_login' => $user_login,
												'user_registered' => $user_item->user_registered,
												'user_active_courses' => ( count( $user_courses_started ) - count( $user_courses_ended ) ),
												'user_completed_courses' => count( $user_courses_ended ),
												'user_average_grade' => $user_average_grade
			 								), $user_item->ID )
						);
		} // End For Loop
		// Sort the data
		return $return_array;
	} // End overview_users()

	/**
	 * overview_courses loads course overview data
	 * @since  1.2.0
	 * @param  array $args_array arguments for data
	 * @return array $return_array data for table
	 */
	public function overview_courses( $args_array ) {
		global $woothemes_sensei;
		$return_array = array();
		$course_start_date = '';
		// Get Courses
		$posts_array = $woothemes_sensei->post_types->course->course_query( -1, 'usercourses' );
		// MAIN LOOP
		foreach ($posts_array as $course_item) {
			// Manual Keyword Search
			$title_keyword_count = 1;
			if ( isset( $_GET['s'] ) && '' != $_GET['s'] ) {
			$title_keyword_count = substr_count( strtolower( sanitize_title( $course_item->post_title ) ) , strtolower( sanitize_title( $_GET['s'] ) ) );
			} // End If Statement
			// If Matches are found
			if ( 0 < intval( $title_keyword_count ) ) {
				// Course Completions
				$course_completions = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $course_item->ID, 'type' => 'sensei_course_end' ), true );
				$course_completions = intval( count( $course_completions ) );
				// Course Students
				$course_students = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $course_item->ID, 'type' => 'sensei_course_start' ), true );
				$course_students = intval( count( $course_students ) );
				// Course Lessons
				$course_lessons = $woothemes_sensei->post_types->course->course_lessons( $course_item->ID );
				$course_lessons = intval( count( $course_lessons ) );
				// Output course data
				if ( $args_array['raw'] ) {
					$course_title = $course_item->post_title;
				} else {
					$course_title = '<a href="' . add_query_arg( array( 'page' => 'sensei_analysis', 'course_id' => $course_item->ID ), admin_url( 'admin.php' ) ) . '">'.$course_item->post_title.'</a>';
				} // End If Statement
				array_push( $return_array, apply_filters( 'sensei_analysis_overview_columns_column_data', array( 	'course_title' => $course_title,
													'course_students' => $course_students,
													'course_lessons' => $course_lessons,
													'course_completions' => $course_completions
				 								), $course_item->ID )
							);
			} // End If Statement
		} // End For Loop
		return $return_array;
	} // End overview_courses()

	/**
	 * overview_lessons loads lessons overview data
	 * @since  1.2.0
	 * @param  array $args_array arguments for data
	 * @return array $return_array data for table
	 */
	public function overview_lessons( $args_array ) {
		global $woothemes_sensei;
		$return_array = array();
		$course_start_date = '';
		// Get Lessons
		$post_args = array(	'post_type' 		=> 'lesson',
							'numberposts' 		=> -1,
							'orderby'         	=> 'menu_order',
    						'order'           	=> 'ASC',
    						'post_status'       => 'publish',
							'suppress_filters' 	=> 0
							);
		$posts_array = get_posts( $post_args );
		// MAIN LOOP
		foreach ($posts_array as $lesson_item) {
			// Manual keyword search
			$title_keyword_count = 1;
			if ( isset( $_GET['s'] ) && '' != $_GET['s'] ) {
			$title_keyword_count = substr_count( strtolower( sanitize_title( $lesson_item->post_title ) ) , strtolower( sanitize_title( $_GET['s'] ) ) );
			} // End If Statement
			if ( 0 < intval( $title_keyword_count ) ) {
				// Lesson Course
				$course_id = get_post_meta( $lesson_item->ID, '_lesson_course', true );
				$course_title = '';
				if ( 0 < $course_id ) {
					$course_title = get_the_title( intval( $course_id ) );
				} // End If Statement
				// Get Quiz ID
				$lesson_quizzes = $woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson_item->ID );
		    	foreach ($lesson_quizzes as $quiz_item) {
		    		$lesson_quiz_id = $quiz_item->ID;
		    	} // End For Loop
				// Lesson Students
				$lesson_students = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $lesson_item->ID, 'type' => 'sensei_lesson_start' ), true );
				$lesson_students = intval( count( $lesson_students ) );
				// Lesson Completions
				$lesson_completions = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $lesson_item->ID, 'type' => 'sensei_lesson_end' ), true );
				$lesson_completions = intval( count( $lesson_completions ) );
				// Lesson Grades
				$lesson_grades = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $lesson_quiz_id, 'type' => 'sensei_quiz_grade' ), true );
				$total_grade_count = 0;
				$total_grade_total = 0.00;
				// Calculate the average quiz grade
				foreach ( $lesson_grades as $lesson_quiz_key => $lesson_quiz_value ) {
				    $total_grade_total = $total_grade_total + doubleval( $lesson_quiz_value->comment_content );
				    $total_grade_count++;
				} // End For Loop
				// Handle Division by Zero
				if ( 0 == $total_grade_count ) {
					$total_grade_count = 1;
				} // End If Statement
				$lesson_average_grade = abs( round( doubleval( $total_grade_total / $total_grade_count ), 2 ) );
				// Output Lesson data
				if ( $args_array['raw'] ) {
					$lesson_title = $lesson_item->post_title;
					$lesson_course = $course_title;
				} else {
					$lesson_title = '<a href="' . add_query_arg( array( 'page' => 'sensei_analysis', 'lesson_id' => $lesson_item->ID ), admin_url( 'admin.php' ) ) . '">'.$lesson_item->post_title.'</a>';
					$lesson_course = '<a href="' . add_query_arg( array( 'page' => 'sensei_analysis', 'course_id' => $course_id ), admin_url( 'admin.php' ) ) . '">'.$course_title.'</a>';
					$lesson_average_grade = $lesson_average_grade . '%';
				} // End If Statement
				array_push( $return_array, apply_filters( 'sensei_analysis_overview_lessons_column_data', array( 	'lesson_title' => $lesson_title,
													'lesson_course' => $lesson_course,
													'lesson_students' => $lesson_students,
													'lesson_completions' => $lesson_completions,
													'lesson_average_grade' => $lesson_average_grade
				 								), $lesson_item->ID )
							);
			} // End If Statement
		} // End For Loop
		return $return_array;
	} // overview_lessons()

	/**
	 * load_stats loads stats into object
	 * @since  1.2.0
	 * @return void
	 */
	public function load_stats() {
		global $woothemes_sensei;
		// Get the data required
		$user_count = count_users();
		$this->user_count = $user_count['total_users'];
		$this->total_courses = $woothemes_sensei->post_types->course->course_count();
		$this->total_lessons = $woothemes_sensei->post_types->lesson->lesson_count();
		$total_quiz_grades = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'type' => 'sensei_quiz_grade' ), true );
		$total_grade_count = 0;
		$total_grade_total = 0.00;
		// Calculate the average quiz grade
		foreach ( $total_quiz_grades as $total_quiz_key => $total_quiz_value ) {
		    $total_grade_total = $total_grade_total + doubleval( $total_quiz_value->comment_content );
		    $total_grade_count++;
		} // End For Loop
		// Handle Division by Zero
		if ( 0 == $total_grade_count ) {
			$total_grade_count = 1;
		} // End If Statement
		$this->total_average_grade = abs( round( doubleval( $total_grade_total / $total_grade_count ), 2 ) );
		$this->total_courses_started = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'type' => 'sensei_course_start' ), true );
		$this->total_courses_ended = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'type' => 'sensei_course_end' ), true );
		$this->average_courses_per_learner = abs( round( doubleval( count( $this->total_courses_started ) / $this->user_count ), 2 ) );
	} // End load_stats()

	/**
	 * stats_boxes loads which stats boxes to render
	 * @since  1.2.0
	 * @return $stats_to_render array of stats boxes and values
	 */
	public function stats_boxes () {
		$stats_to_render = array( 	__( 'Total Courses', 'woothemes-sensei' ) => $this->total_courses,
									__( 'Total Lessons', 'woothemes-sensei' ) => $this->total_lessons,
									__( 'Total Learners', 'woothemes-sensei' ) => $this->user_count,
									__( 'Average Courses per Learner', 'woothemes-sensei' ) => $this->average_courses_per_learner,
									__( 'Average Grade', 'woothemes-sensei' ) => $this->total_average_grade . '%',
									__( 'Total Completed Courses', 'woothemes-sensei' ) => count( $this->total_courses_ended ),
								);
		return $stats_to_render;
	} // End stats_boxes()

	/**
	 * no_items sets output when no items are found
	 * Overloads the parent method
	 * @since  1.2.0
	 * @return void
	 */
	public function no_items() {
  		_e( 'No learners found.', 'woothemes-sensei' );
	} // End no_items()

	/**
	 * data_table_header output for table heading
	 * @since  1.2.0
	 * @return void
	 */
	public function data_table_header() {
		switch ( $this->type ) {
			case 'courses':
				$report_id = 'courses-overview';
			break;
			case 'lessons':
				$report_id = 'lessons-overview';
			break;
			default :
				$report_id = 'user-overview';
			break;
		} // End Switch Statement
		echo '<a href="' . add_query_arg( array( 'page' => 'sensei_analysis', 'report_id' => $report_id ), admin_url( 'admin.php' ) ) . '">' . __( 'Export page (CSV)', 'woothemes-sensei' ) . '</a>';
	} // End data_table_header()

} // End Class
?>