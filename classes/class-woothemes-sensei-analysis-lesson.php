<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Analysis Lesson List Table Class
 *
 * All functionality pertaining to the Admin Analysis Lesson Data Table in Sensei.
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
 * - load_stats()
 * - stats_boxes()
 * - no_items()
 * - data_table_header()
 * - data_table_footer()
 */
class WooThemes_Sensei_Analysis_Lesson_List_Table extends WooThemes_Sensei_List_Table {
	public $lesson_id;
	public $course_id;

	/**
	 * Constructor
	 * @since  1.2.0
	 * @return  void
	 */
	public function __construct ( $lesson_id = 0 ) {
		$this->lesson_id = intval( $lesson_id );
		$this->course_id = intval( get_post_meta( $this->lesson_id, '_lesson_course', true ) );
		// Load Parent token into constructor
		parent::__construct( 'analysis_lesson' );
		// Default Columns
		$this->columns = array(
			'user_login' => __( 'Learner', 'woothemes-sensei' ),
			'user_lesson_date_started' => __( 'Date Started', 'woothemes-sensei' ),
			'user_lesson_date_completed' => __( 'Date Completed', 'woothemes-sensei' ),
			'user_lesson_grade' => __( 'Grade', 'woothemes-sensei' )
		);
		// Sortable Columns
		$this->sortable_columns = array(
			'user_login' => array( 'user_login', false ),
			'user_lesson_date_started' => array( 'user_lesson_date_started', false ),
			'user_lesson_date_completed' => array( 'user_lesson_date_completed', false ),
			'user_lesson_grade' => array( 'user_lesson_grade', false )
		);
		// Actions
		add_action( 'sensei_before_list_table', array( &$this, 'data_table_header' ) );
		add_action( 'sensei_after_list_table', array( &$this, 'data_table_footer' ) );
	} // End __construct()

	/**
	 * build_data_array builds the data for use in the table
	 * Overloads the parent method
	 * @since  1.2.0
	 * @return array
	 */
	public function build_data_array() {
		global $woothemes_sensei;
		$return_array = array();
		// Handle search
		$args_array = array();
		if ( isset( $_POST['s'] ) && '' != esc_html( $_POST['s'] ) ) {
			$args_array['search'] = esc_html( $_POST['s'] );
		} // End If Statement
		// Get the data required
		$users = get_users( $args_array );
		$output_counter = 0;
		$lesson_quizzes = $woothemes_sensei->post_types->lesson->lesson_quizzes( $this->lesson_id );
		// Get Quiz ID
	    foreach ($lesson_quizzes as $quiz_item) {
	    	$lesson_quiz_id = $quiz_item->ID;
	    } // End For Loop
		foreach ( $users as $user_key => $user_item ) {
			// Check if Lesson has started
			$lesson_start_date =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $this->lesson_id, 'user_id' => $user_item->ID, 'type' => 'sensei_lesson_start', 'field' => 'comment_date' ) );
			// Check if Lesson is complete
			$lesson_end_date =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $this->lesson_id, 'user_id' => $user_item->ID, 'type' => 'sensei_lesson_end', 'field' => 'comment_date' ) );
			// Quiz Grade
			$lesson_grade =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_quiz_id, 'user_id' => $user_item->ID, 'type' => 'sensei_quiz_grade', 'field' => 'comment_content' ) );
			$quiz_grade = __( 'No Grade', 'woothemes-sensei' );
			if ( 0 < intval( $lesson_grade ) ) {
		    	$quiz_grade = $lesson_grade . '%';
		    } else {
		    	$lesson_end_date = '';
		    } // End If Statement
			// Output the users data
			if ( isset( $lesson_start_date ) && '' != $lesson_start_date ) {
				array_push( $return_array, array( 	'user_login' => '<a href="' . add_query_arg( array( 'page' => 'sensei_analysis', 'user' => $user_item->ID, 'course_id' => $this->course_id ), admin_url( 'edit.php?post_type=lesson' ) ) . '">'.$user_item->user_login.'</a>',
												'user_lesson_date_started' => $lesson_start_date,
												'user_lesson_date_completed' => $lesson_end_date,
												'user_lesson_grade' => $quiz_grade . ''
			 								)
						);
			} // End If Statement
		} // End For Loop
		// Sort the data
		$return_array = $this->array_sort_reorder( $return_array );
		return $return_array;
	} // End build_data_array()

	/**
	 * load_stats loads stats into object
	 * @since  1.2.0
	 * @return void
	 */
	public function load_stats() {
		global $woothemes_sensei;
	} // End load_stats()

	/**
	 * stats_boxes loads which stats boxes to render
	 * @return $stats_to_render array of stats boxes and values
	 */
	public function stats_boxes () {
		$stats_to_render = array();
		return $stats_to_render;
	} // End stats_boxes

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
		echo '<strong>' . __( 'Learners taking this Lesson', 'woothemes-sensei' ) . '</strong>';
	} // End data_table_header()

	/**
	 * data_table_footer output for table footer
	 * @since  1.2.0
	 * @return void
	 */
	public function data_table_footer() {
		if ( 0 < intval( $this->course_id ) ) {
			echo '<a href="' . add_query_arg( array( 'page' => 'sensei_analysis', 'course_id' => $this->course_id ), admin_url( 'edit.php?post_type=lesson' ) ) . '">' . __( 'View the Course', 'woothemes-sensei' ) . '</a>';
		} // End If Statement
	} // End data_table_footer()

} // End Class
?>