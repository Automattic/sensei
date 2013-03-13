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
 * @since 1.1.3
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 * - build_data_array()
 * - load_stats()
 * - stats_boxes()
 * - no_items()
 */
class WooThemes_Sensei_Analysis_Overview_List_Table extends WooThemes_Sensei_List_Table {
	public $token;
	public $user_count;
	public $total_courses;
	public $total_lessons;
	public $total_average_grade;
	public $total_courses_started;
	public $total_courses_ended;
	public $average_courses_per_learner;

	/**
	 * Constructor
	 * @since  1.1.3
	 * @return  void
	 */
	public function __construct () {
		// Load Parent token into constructor
		parent::__construct( 'analysis_overview' );
		// Default Columns
		$this->columns = array(
			'user_login' => __( 'User', 'woothemes-sensei' ),
			'user_registered' => __( 'Date Registered', 'woothemes-sensei' ),
			'user_active_courses' => __( 'Active Courses', 'woothemes-sensei' ),
			'user_completed_courses' => __( 'Completed Courses', 'woothemes-sensei' ),
			'user_average_grade' => __( 'Average Grade', 'woothemes-sensei' )
		);
		// Sortable Columns
		$this->sortable_columns = array(
			'user_login' => array( 'user_login', false ),
			'user_registered' => array( 'user_registered', false ),
			'user_active_courses' => array( 'user_active_courses', false ),
			'user_completed_courses' => array( 'user_completed_courses', false ),
			'user_average_grade' => array( 'user_average_grade', false )
		);
	} // End __construct()

	/**
	 * build_data_array builds the data for use in the table
	 * Overloads the parent method
	 * @since  1.1.3
	 * @return array
	 */
	public function build_data_array() {

		global $woothemes_sensei;

		$return_array = array();
		// Get the data required
		$args_array = array();
		if ( isset( $_POST['s'] ) && '' != esc_html( $_POST['s'] ) ) {
			$args_array['search'] = esc_html( $_POST['s'] );
		} // End If Statement
		$users = get_users( $args_array );
		$user_offset = 0;
		if ( isset( $_GET['user_offset'] ) && 0 <= abs( intval( $_GET['user_offset'] ) ) ) {
			$user_offset = abs( intval( $_GET['user_offset'] ) );
		} // End If Statement
		$user_length = 15;
		$output_counter = 0;
		foreach ( $users as $user_key => $user_item ) {
			$output_counter++;
			$user_courses_started = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'user_id' => $user_item->ID, 'type' => 'sensei_course_start' ), true );
			$user_courses_ended = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'user_id' => $user_item->ID, 'type' => 'sensei_course_end' ), true );
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
			array_push( $return_array, array( 	'user_login' => $user_item->user_login,
												'user_registered' => $user_item->user_registered,
												'user_active_courses' => ( count( $user_courses_started ) - count( $user_courses_ended ) ),
												'user_completed_courses' => count( $user_courses_ended ),
												'user_average_grade' => $user_average_grade . '%',

			 								)
						);
		} // End For Loop
		$return_array = $this->array_sort_reorder( $return_array );
		return $return_array;
	} // End build_data_array()

	/**
	 * load_stats loads stats into object
	 * @since  1.1.3
	 * @return void
	 */
	public function load_stats() {
		global $woothemes_sensei;
		// Get the data required
		$users = get_users();
		$this->user_count = count( $users );
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
	} // End stats_boxes

	/**
	 * no_items sets output when no items are found
	 * Overloads the parent method
	 * @since  1.1.3
	 * @return void
	 */
	public function no_items() {
  		_e( 'No users found.', 'woothemes-sensei' );
	} // End no_items()

} // End Class
?>