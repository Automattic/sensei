<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Analysis User Profile List Table Class
 *
 * All functionality pertaining to the Admin Analysis User Profile Data Table in Sensei.
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
 */
class WooThemes_Sensei_Analysis_User_Profile_List_Table extends WooThemes_Sensei_List_Table {
	public $user_id;

	/**
	 * Constructor
	 * @since  1.2.0
	 * @return  void
	 */
	public function __construct ( $user_id = 0 ) {
		$this->user_id = intval( $user_id );
		// Load Parent token into constructor
		parent::__construct( 'analysis_user_profile' );
		// Default Columns
		$this->columns = apply_filters( 'sensei_analysis_user_profile_columns', array(
			'course_title' => __( 'Course', 'woothemes-sensei' ),
			'course_started' => __( 'Date Started', 'woothemes-sensei' ),
			'course_completed' => __( 'Date Completed', 'woothemes-sensei' ),
			'course_status' => __( 'Status', 'woothemes-sensei' ),
			'course_grade' => __( 'Grade', 'woothemes-sensei' )
		) );
		// Sortable Columns
		$this->sortable_columns = apply_filters( 'sensei_analysis_user_profile_columns_sortable', array(
			'course_title' => array( 'course_title', false ),
			'course_started' => array( 'course_started', false ),
			'course_completed' => array( 'course_completed', false ),
			'course_status' => array( 'course_status', false ),
			'course_grade' => array( 'course_grade', false )
		) );
		$this->hidden_columns = apply_filters( 'sensei_analysis_user_profile_columns_hidden', array(
			'course_grade'
		) );
		// Actions
		add_action( 'sensei_before_list_table', array( $this, 'data_table_header' ) );
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

		$course_start_date = '';

		$course_ids = WooThemes_Sensei_Utils::sensei_activity_ids( array( 'user_id' => $this->user_id, 'type' => 'sensei_course_start' ) );
		$posts_array = array();
		if ( 0 < intval( count( $course_ids ) ) ) {
			$posts_array = $woothemes_sensei->post_types->course->course_query( -1, 'usercourses', $course_ids );
		} // End If Statement

		// MAIN LOOP
		foreach ($posts_array as $course_item) {
			$title_keyword_count = 1;
			if ( isset( $_GET['s'] ) && '' != $_GET['s'] ) {
			$title_keyword_count = substr_count( strtolower( sanitize_title( $course_item->post_title ) ) , strtolower( sanitize_title( $_GET['s'] ) ) );
			} // End If Statement
			if ( 0 < intval( $title_keyword_count ) ) {
				$course_status = apply_filters( 'sensei_in_progress_text', __( 'In Progress', 'woothemes-sensei' ) );
				$course_end_date = '';
				// Check if Course is complete
		    	$user_course_end =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $course_item->ID, 'user_id' => $this->user_id, 'type' => 'sensei_course_end', 'field' => 'comment_content' ) );
		    	$course_start_date =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $course_item->ID, 'user_id' => $this->user_id, 'type' => 'sensei_course_start', 'field' => 'comment_date' ) );
		    	if ( isset( $user_course_end ) && '' != $user_course_end ) {
		    		$course_status = __( 'Complete', 'woothemes-sensei' );
		    		$course_end_date =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $course_item->ID, 'user_id' => $this->user_id, 'type' => 'sensei_course_end', 'field' => 'comment_date' ) );
		    	} // End If Statement

				array_push( $return_array, apply_filters( 'sensei_analysis_user_profile_column_data', array( 	'course_title' => '<a href="' . add_query_arg( array( 'page' => 'sensei_analysis', 'user' => $this->user_id, 'course_id' => $course_item->ID ), admin_url( 'admin.php' ) ) . '">'.$course_item->post_title.'</a>',
													'course_started' => $course_start_date,
													'course_completed' => $course_end_date,
													'course_status' => $course_status,
													'course_grade' => 'TODO'
				 								), $course_item->ID )
							);
			} // End If Statement
		} // End For Loop
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
	 * @since  1.2.0
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
  		_e( 'No courses found.', 'woothemes-sensei' );
	} // End no_items()

	/**
	 * course_data_table_header output for table heading
	 * @since  1.2.0
	 * @return void
	 */
	public function data_table_header() {
		echo '<strong>' . __( 'Courses', 'woothemes-sensei' ) . '</strong>';
	}

} // End Class
?>