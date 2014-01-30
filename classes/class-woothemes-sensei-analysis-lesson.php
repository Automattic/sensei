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
	public $user_ids;

	/**
	 * Constructor
	 * @since  1.2.0
	 * @return  void
	 */
	public function __construct ( $lesson_id = 0 ) {
		$this->lesson_id = intval( $lesson_id );
		$this->course_id = intval( get_post_meta( $this->lesson_id, '_lesson_course', true ) );
		// Get Lessons Users
		$this->user_ids = array();
		if ( isset( $this->lesson_id ) && 0 < intval( $this->lesson_id ) ) {
			$this->user_ids = WooThemes_Sensei_Utils::sensei_activity_ids( array( 'post_id' => intval( $this->lesson_id ), 'type' => 'sensei_lesson_start', 'field' => 'user_id' ) );
		} // End If Statement
		// Load Parent token into constructor
		parent::__construct( 'analysis_lesson' );
		// Default Columns
		$this->columns = apply_filters( 'sensei_analysis_lesson_columns', array(
			'user_login' => __( 'Learner', 'woothemes-sensei' ),
			'user_lesson_date_started' => __( 'Date Started', 'woothemes-sensei' ),
			'user_lesson_date_completed' => __( 'Date Completed', 'woothemes-sensei' ),
			'user_lesson_grade' => __( 'Grade', 'woothemes-sensei' )
		) );
		// Sortable Columns
		$this->sortable_columns = apply_filters( 'sensei_analysis_lesson_columns_sortable', array(
			'user_login' => array( 'user_login', false ),
			'user_lesson_date_started' => array( 'user_lesson_date_started', false ),
			'user_lesson_date_completed' => array( 'user_lesson_date_completed', false ),
			'user_lesson_grade' => array( 'user_lesson_grade', false )
		) );
		// Actions
		add_action( 'sensei_before_list_table', array( $this, 'data_table_header' ) );
		add_action( 'sensei_after_list_table', array( $this, 'data_table_footer' ) );
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
		if ( isset( $_GET['s'] ) && '' != esc_html( $_GET['s'] ) ) {
			$args_array['search'] = esc_html( $_GET['s'] );
		} // End If Statement
		// Get the data required
		$offset = '';
		if ( isset($_GET['paged']) && 0 < intval($_GET['paged']) ) {
			$offset = $this->per_page * ( $_GET['paged'] - 1 );
		} // End If Statement
		$usersearch = isset( $_GET['s'] ) ? trim( $_GET['s'] ) : '';
		$role = isset( $_REQUEST['role'] ) ? $_REQUEST['role'] : '';
		$args_array = array(
			'number' => $this->per_page,
			'include' => $this->user_ids,
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

		$output_counter = 0;
		$lesson_quizzes = $woothemes_sensei->post_types->lesson->lesson_quizzes( $this->lesson_id );
		// Get Quiz ID
	    foreach ($lesson_quizzes as $quiz_item) {
	    	$lesson_quiz_id = $quiz_item->ID;
	    } // End For Loop

	    // Users Loop
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
				array_push( $return_array, apply_filters( 'sensei_analysis_lesson_column_data', array( 	'user_login' => '<a href="' . add_query_arg( array( 'page' => 'sensei_analysis', 'user' => $user_item->ID, 'course_id' => $this->course_id ), admin_url( 'admin.php' ) ) . '">'.$user_item->display_name.'</a>',
												'user_lesson_date_started' => $lesson_start_date,
												'user_lesson_date_completed' => $lesson_end_date,
												'user_lesson_grade' => $quiz_grade . ''
			 								), $this->lesson_id )
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
			echo '<a href="' . add_query_arg( array( 'page' => 'sensei_analysis', 'course_id' => $this->course_id ), admin_url( 'admin.php' ) ) . '">' . __( 'View the Course', 'woothemes-sensei' ) . '</a>';
		} // End If Statement
	} // End data_table_footer()

} // End Class
?>