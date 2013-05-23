<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Grading Overview List Table Class
 *
 * All functionality pertaining to the Admin Grading Overview Data Table in Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Core
 * @author WooThemes
 * @since 1.3.0
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
class WooThemes_Sensei_Grading_Main extends WooThemes_Sensei_List_Table {
	public $user_id;
	public $course_id;
	public $lesson_id;

	/**
	 * Constructor
	 * @since  1.3.0
	 * @return  void
	 */
	public function __construct ( $course_id = 0, $lesson_id = 0 ) {
		$this->course_id = intval( $course_id );
		$this->lesson_id = intval( $lesson_id );
		// Load Parent token into constructor
		parent::__construct( 'grading_main' );

		// Default Columns
		$this->columns = array(
			'user_login' => __( 'Learner', 'woothemes-sensei' ),
			'user_status' => __( 'Status', 'woothemes-sensei' ),
			'user_grade' => __( 'Grade', 'woothemes-sensei' )
		);
		// Sortable Columns
		$this->sortable_columns = array(
			'user_login' => array( 'user_login', false ),
			'user_status' => array( 'user_status', false ),
			'user_grade' => array( 'user_grade', false )
		);

		// Actions
		add_action( 'sensei_before_list_table', array( &$this, 'data_table_header' ) );
		add_action( 'sensei_after_list_table', array( &$this, 'data_table_footer' ) );
	} // End __construct()

	/**
	 * build_data_array builds the data for use in the table
	 * Overloads the parent method
	 * @since  1.3.0
	 * @return array
	 */
	public function build_data_array() {
		global $woothemes_sensei;
		$return_array = array();
		// Course Students or Lessons
		if ( isset( $this->lesson_id ) && 0 < intval( $this->lesson_id ) ) {
			$args_array = array();
			// Handle Search
			if ( isset( $_POST['s'] ) && '' != esc_html( $_POST['s'] ) ) {
				$args_array['search'] = esc_html( $_POST['s'] );
			} // End If Statement
			// Get Users data
			$users = get_users( $args_array );

			$lesson_id = $this->lesson_id;
			$output_counter = 0;
			$lesson_quizzes = $woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson_id );
			// Get Quiz ID
		    foreach ($lesson_quizzes as $quiz_item) {
		    	$lesson_quiz_id = $quiz_item->ID;
		    } // End For Loop
		    foreach ( $users as $user_key => $user_item ) {
				// Get Start Date
				$lesson_start_date =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_id, 'user_id' => $user_item->ID, 'type' => 'sensei_lesson_start', 'field' => 'comment_date' ) );
				// Check if Lesson is complete
				$lesson_end_date =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_id, 'user_id' => $user_item->ID, 'type' => 'sensei_lesson_end', 'field' => 'comment_date' ) );
				// Quiz Grade
				$lesson_grade =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_quiz_id, 'user_id' => $user_item->ID, 'type' => 'sensei_quiz_grade', 'field' => 'comment_content' ) );
				$quiz_grade = __( 'No Grade', 'woothemes-sensei' );
				if ( 0 < intval( $lesson_grade ) ) {
			    	$quiz_grade = $lesson_grade . '%';
			    } // End If Statement

			    if ( ( isset( $lesson_end_date ) && '' != $lesson_end_date ) && ( isset( $lesson_grade ) && '' == $lesson_grade ) ) {
					$status_html = '<span class="submitted">' . __( 'Submitted for Grading', 'woothemes-sensei' ) . '</span>';
					$to_be_graded_count++;
				} elseif ( isset( $lesson_grade ) && 0 < intval( $lesson_grade ) ) {
					$status_html = '<span class="graded">' . __( 'Graded', 'woothemes-sensei' ) . '</span>';
					$graded_count++;
				} elseif ( ( isset( $lesson_start_date ) && '' != $lesson_start_date ) && ( isset( $lesson_end_date ) && '' == $lesson_end_date  ) ) {
					$status_html = '<span class="in-progress">' . __( 'In Progress', 'woothemes-sensei' ) . '</span>';
					$in_progress_count++;
				}  // End If Statement

				// Output the users data
				if ( isset( $lesson_start_date ) && '' != $lesson_start_date ) {
					array_push( $return_array, array( 	'user_login' => '<a href="' . add_query_arg( array( 'page' => 'sensei_grading', 'user' => $user_item->ID, 'quiz_id' => $lesson_quiz_id ), admin_url( 'edit.php?post_type=lesson' ) ) . '">'.$user_item->display_name.'</a>',
													'user_status' => $status_html,
													'user_grade' => $quiz_grade
				 								)
							);
				} // End If Statement
			} // End For Loop
		} // End If Statement
		// Sort the data
		$return_array = $this->array_sort_reorder( $return_array );
		return $return_array;
	} // End build_data_array()

	/**
	 * load_stats loads stats into object
	 * @since  1.3.0
	 * @return void
	 */
	public function load_stats() {
		global $woothemes_sensei;
	} // End load_stats()

	/**
	 * stats_boxes loads which stats boxes to render
	 * @since  1.3.0
	 * @return $stats_to_render array of stats boxes and values
	 */
	public function stats_boxes () {
		$stats_to_render = array();
		return $stats_to_render;
	} // End stats_boxes

	/**
	 * no_items sets output when no items are found
	 * Overloads the parent method
	 * @since  1.3.0
	 * @return void
	 */
	public function no_items() {
		if ( isset( $this->lesson_id ) && 0 < intval( $this->lesson_id ) && isset( $this->course_id ) && 0 < intval( $this->course_id ) ) {
			_e( 'No learners found.', 'woothemes-sensei' );
		} else {
			_e( 'No learners found.', 'woothemes-sensei' );
  		} // End If Statement
	} // End no_items()

	/**
	 * data_table_header output for table heading
	 * @since  1.3.0
	 * @return void
	 */
	public function data_table_header() {
		if ( isset( $this->lesson_id ) && 0 < intval( $this->lesson_id ) && isset( $this->course_id ) && 0 < intval( $this->course_id ) ) {
			echo '<h3 class="grading-header">' . __( 'Learners to be Graded', 'woothemes-sensei' ) . '</h3>';
		} else {
			echo '<h3 class="grading-header">' . __( 'Please select a Lesson to be Graded', 'woothemes-sensei' ) . '</h3>';
		} // End If Statement
	} // End data_table_header()

	/**
	 * data_table_footer output for table footer
	 * @since  1.3.0
	 * @return void
	 */
	public function data_table_footer() {
		// Nothing right now
	} // End data_table_footer()

} // End Class
?>