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
		$this->columns = apply_filters( 'sensei_grading_main_columns', array(
			'user_login' => __( 'Learner', 'woothemes-sensei' ),
			'course' => __( 'Course', 'woothemes-sensei' ),
			'lesson' => __( 'Lesson', 'woothemes-sensei' ),
			'updated' => __( 'Updated', 'woothemes-sensei' ),
			'user_status' => __( 'Status', 'woothemes-sensei' ),
			'user_grade' => __( 'Grade', 'woothemes-sensei' ),
			'action' => __( '', 'woothemes-sensei' )
		) );
		// Sortable Columns
		$this->sortable_columns = apply_filters( 'sensei_grading_main_columns_sortable', array(
			'user_login' => array( 'user_login', false ),
			'course' => array( 'course', false ),
			'lesson' => array( 'lesson', false ),
			'updated' => array( 'updated', false ),
			'user_status' => array( 'user_status', false ),
			'user_grade' => array( 'user_grade', false )
		) );

		// Actions
		add_action( 'sensei_before_list_table', array( $this, 'data_table_header' ) );
		add_action( 'sensei_after_list_table', array( $this, 'data_table_footer' ) );
	} // End __construct()

	/**
	 * build_data_array builds the data for use in the table
	 * Overloads the parent method
	 * @since  1.3.0
	 * @return array
	 */
	public function build_data_array() {
		global $woothemes_sensei, $wpdb;

		$return_array = array();
		$row_data = false;

		$activity_args = array();

		$user_args = array();
		// Handle Search
		if ( isset( $_GET['s'] ) && '' != esc_html( $_GET['s'] ) ) {
			$this->search = trim( $_GET['s'] ); // Used in the table header
			$user_args['search'] = '*' . esc_html( $this->search ) . '*';
		} // End If Statement
		// Handle user Role
		if ( isset( $_REQUEST['role'] ) ) {
			$user_args['role'] = $_REQUEST['role'];
		} // End If Statement
		// Filter for extending
		$user_args = apply_filters( 'sensei_grading_filter_users', $user_args );
		// Get Users data restricted based on user args, if set
		if ( !empty($user_args) ) {
			$user_args['fields'] = 'ID'; // Return just the IDs
			$wp_user_search = new WP_User_Query( $user_args );
			$this->user_ids = $wp_user_search->get_results();
		} else {
			$this->user_ids = array();
		} // End If Statement
		if ( !empty($this->user_ids) && 1 <= count($this->user_ids) ) {
			$activity_args['user_id'] = $this->user_ids;
		}
//		error_log( 'count this->user_ids: '.count($this->user_ids));

		// Restrict based on grading status
		if( isset( $_GET['grading_status'] ) && in_array( $_GET['grading_status'], array( 'ungraded', 'graded', 'in-progress' ) ) ) {
			$grading_status = $_GET['grading_status'];
		} elseif ( !isset( $_GET['grading_status'] ) ) {
			$grading_status = 'ungraded';
		} else {
			$grading_status = ''; // all
		}
		$this->grading_status = $grading_status;
//		error_log( "this->grading_status: $this->grading_status");

		// Restrict returns based on a single Lesson
		if ( isset( $this->lesson_id ) && 0 < intval( $this->lesson_id ) ) {
			$activity_args['post_id'] = intval( $this->lesson_id );
		}

		// Filter for extending
//		add_filter( 'comments_clauses', array( $this, 'filter_lesson_ids' ) );
		$lesson_is_quiz = false; // Sometimes we get the Quiz IDs rather than the Lesson IDs so have to work up rather than down
		switch ( $this->grading_status ) :
			case 'graded':
				$activity_args['type'] = 'sensei_quiz_grade';
				if ( $this->lesson_id ) {
					// Need to switch the ID to the Quiz, won't have to once Lesson==Quiz
					$activity_args['post_id'] = $woothemes_sensei->post_types->lesson->lesson_quizzes( $this->lesson_id );
				}
				$lesson_is_quiz = true;
				break;
			case '': // all
				$activity_args['type'] = 'sensei_lesson_start';
				break;
			case 'ungraded' :
			default :
//				$this->exclude_comments = WooThemes_Sensei_Utils::sensei_activity_ids( array_merge( array( 'type' => 'sensei_quiz_grade', 'field' => 'comment' ), $activity_args ) );
//		error_log('exclude_comments: '.print_r($this->exclude_comments, true));
				$activity_args['type'] = 'sensei_quiz_asked';
				$lesson_is_quiz = true;
				if ( $this->lesson_id ) {
					// Need to switch the ID to the Quiz, won't have to once Lesson==Quiz
					$activity_args['post_id'] = $woothemes_sensei->post_types->lesson->lesson_quizzes( $this->lesson_id );
				}
				else {
					// Revert to original, kinda
					$activity_args['type'] = 'sensei_lesson_start';
					$lesson_is_quiz = false;
				}
				break;
		endswitch;
//		remove_filter( 'comments_clauses', array( $this, 'filter_lesson_ids' ) );

		// Handle offsets
		$activity_args['number'] = $this->per_page;
		$activity_args['offset'] = 0;
		if ( isset($_GET['paged']) && 0 < intval($_GET['paged']) ) {
			$activity_args['offset'] = $this->per_page * ( absint($_GET['paged']) - 1 );
		} // End If Statement
//		error_log( "offset: ".$activity_args['offset'] );

		// For now break! **WARNING**
		if ( empty( $this->lesson_id ) ) {
			$this->items = array();
			return $this->items;
		}

//		error_log(print_r($activity_args, true));
		$activity_args = apply_filters( 'sensei_grading_lesson_args', $activity_args );
		// Set for reuse in header counts
		$this->activity_args = $activity_args;
		add_filter( 'comments_clauses', array( $this, 'filter_lessons' ) );
		$this->lesson_ids = WooThemes_Sensei_Utils::sensei_check_for_activity( $this->activity_args, true );
		remove_filter( 'comments_clauses', array( $this, 'filter_lessons' ) );

		// Populate the pagination
		$this->total_items = $wpdb->get_var( 'SELECT FOUND_ROWS()' );
//		error_log( "found_lessons: $this->total_items" );

//		error_log('$all_lessons sensei_quiz_asked: '.count($this->lesson_ids) . sprintf( __( '; %s: %s(): memory: %s, %s secs', 'imperial' ), __CLASS__, __FUNCTION__, size_format( memory_get_usage() ), timer_stop() ) );
		if( isset( $this->lesson_ids ) && count( $this->lesson_ids ) > 0 ) {

			foreach( $this->lesson_ids as $lesson ) {
				$lesson_id = $lesson->comment_post_ID;
				$user_id = $lesson->user_id;

				// Get row data
				$row_data = $this->row_data( $lesson_id, $user_id, $lesson_is_quiz );
				// Add row to table data
				if( $row_data ) {
					array_push( $return_array, $row_data );
				}
			}
		} // End If Statement
		if ( 0 == count($return_array) ) {
			$this->total_items = 0;
		}
		// Sort the data
//		$return_array = $this->array_sort_reorder( $return_array );

		return $return_array;
	} // End build_data_array()

	/**
	 * Used for filtering the resultant Lessons used for Grading
	 * @since 1.7.0
	 * @param array $pieces
	 * @return array
	 */
	public function filter_lessons( $pieces ) {
		global $wpdb;
		if ( !empty($this->exclude_comments) ) {
			$pieces['where'] .= " AND {$wpdb->comments}.comment_ID NOT IN (" . implode( ',', array_map( 'absint', $this->exclude_comments ) ) . ')';
		}
		$pieces = apply_filters( 'sensei_grading_filter_lessons_pieces', $pieces );
		if ( !empty($pieces['limits']) ) {
			// Allows counting the total rows
			$pieces['fields'] = ' SQL_CALC_FOUND_ROWS ' . $pieces['fields'];
		}
//		error_log( __FUNCTION__ . ' - Pieces: '.print_r($pieces, true));
		return $pieces;
	}

	/**
	 * Allows extensions to filter the lessons used for Grading
	 * @since 1.7.0
	 * @param array $pieces
	 * @return array
	 */
	public function filter_lesson_ids( $pieces ) {
		global $wpdb;
		$pieces = apply_filters( 'sensei_grading_filter_lesson_ids', $pieces );
		// Only want the IDs, no objects
		$pieces['fields'] = " {$wpdb->comments}.comment_ID ";
//		error_log( __FUNCTION__ . ' - Pieces: '.print_r($pieces, true));
		return $pieces;
	}

	/**
	 * Fetch data for single table row
	 * @since  1.5.0
	 * @param  integer $lesson_id ID of lesson
	 * @param  integer $user_id   ID of user
	 * @return array              Data for table row
	 */
	private function row_data( $lesson_id, $user_id, $lesson_is_quiz = false ) {
		global $woothemes_sensei;

		// Get Quiz ID
		if ( $lesson_is_quiz ) {
			$lesson_quiz_id = $lesson_id; //$woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson_id );
			$lesson_id = get_post_meta( $lesson_id, '_quiz_lesson', true );
		}
		else {
			$lesson_quiz_id = $woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson_id );
		}

	    // Get course ID
	    $course_id = get_post_meta( $lesson_id, '_lesson_course', true );

		// Get Start Date
		$lesson_start_date =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_id, 'user_id' => $user_id, 'type' => 'sensei_lesson_start', 'field' => 'comment_date' ) );

		// Check if Lesson is complete
		$lesson_end_date =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_id, 'user_id' => $user_id, 'type' => 'sensei_lesson_end', 'field' => 'comment_date' ) );

		// Quiz Grade
		$lesson_grade =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_quiz_id, 'user_id' => $user_id, 'type' => 'sensei_quiz_grade', 'field' => 'comment_content' ) );
		$quiz_grade = __( 'No Grade', 'woothemes-sensei' );
		if ( '' != $lesson_grade ) {
	    	$quiz_grade = $lesson_grade . '%';
	    } // End If Statement

	    $updated = '';
	    if ( ( isset( $lesson_end_date ) && '' != $lesson_end_date ) && ( isset( $lesson_grade ) && '' == $lesson_grade ) ) {
	    	if( $this->grading_status && $this->grading_status != 'ungraded' ) { return false; }
	    	$status = 'ungraded';
			$status_html = '<span class="ungraded">' . apply_filters( 'sensei_ungraded_text', __( 'Ungraded', 'woothemes-sensei' ) ) . '</span>';
			$updated = $lesson_end_date;
		} elseif ( isset( $lesson_grade ) && '' != $lesson_grade ) {
			if( $this->grading_status && $this->grading_status != 'graded' ) { return false; }
			$status = 'graded';
			$status_html = '<span class="graded">' . apply_filters( 'sensei_graded_text', __( 'Graded', 'woothemes-sensei' ) ) . '</span>';
			$updated =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_quiz_id, 'user_id' => $user_id, 'type' => 'sensei_quiz_grade', 'field' => 'comment_date' ) );
		} elseif ( ( isset( $lesson_start_date ) && '' != $lesson_start_date ) && ( isset( $lesson_end_date ) && '' == $lesson_end_date  ) ) {
			if( $this->grading_status && $this->grading_status != 'in-progress' ) { return false; }
			$status = 'in-progress';
			$status_html = '<span class="in-progress">' . apply_filters( 'sensei_in_progress_text', __( 'In Progress', 'woothemes-sensei' ) ) . '</span>';
			$updated = $lesson_start_date;
		}  // End If Statement

		// Output the users data
		if ( isset( $lesson_start_date ) && '' != $lesson_start_date ) {

			$quiz_link = add_query_arg( array( 'page' => 'sensei_grading', 'user' => $user_id, 'quiz_id' => $lesson_quiz_id ), admin_url( 'admin.php' ) );
			switch( $status ) {
				case 'ungraded': $grade_link = '<a class="button-primary" href="' . $quiz_link . '">Grade quiz</a>'; break;
				case 'graded': $grade_link = '<a class="button-secondary" href="' . $quiz_link . '">Review grade</a>'; break;
				case 'in-progress': $grade_link = ''; break;
			}
			if( $status == 'ungraded' ) {
				$button_class = 'button-primary';
			} else {
				$button_class = 'button-secondary';
			}
			// Get user object
			$user = get_userdata( $user_id );

			return apply_filters( 'sensei_grading_main_column_data', array(
					'user_login' => '<a href="' . admin_url( 'user-edit.php?user_id=' . $user_id ) . '">' . $user->display_name . '</a>',
					'course' => '<a href="' . admin_url( 'post.php?action=edit&post=' . $course_id ) . '">' . get_the_title( $course_id ) . '</a>',
					'lesson' => '<a href="' . admin_url( 'post.php?action=edit&post=' . $lesson_id ) . '">' . get_the_title( $lesson_id ) . '</a>',
					'updated' => $updated,
					'user_status' => $status_html,
					'user_grade' => $quiz_grade,
					'action' => $grade_link,
			), $lesson_id, $user_id );

		} // End If Statement
	}

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
		echo apply_filters( 'sensei_grading_no_items_text', __( 'No learners/quizzes found.', 'woothemes-sensei' ) );
	} // End no_items()

	/**
	 * data_table_header output for table heading
	 * @since  1.3.0
	 * @return void
	 */
	public function data_table_header() {
		global $woothemes_sensei;

		// Setup counters
		$all_lessons_count = $ungraded_lessons_count = $graded_lessons_count = $inprogress_lessons_count = '?';//0;
		$all_class = $ungraded_class = $graded_class = $inprogress_class = '';
		// Already processed in build_data_array()
		switch( $this->grading_status ) :
			case '':
				$all_class = 'current'; 
				$all_lessons_count = $this->total_items;
				break;
			case 'ungraded' :
			default:
				$ungraded_class = 'current'; 
				$ungraded_lessons_count = $this->total_items;
				break;
			case 'graded' :
				$graded_class = 'current'; 
				$graded_lessons_count = $this->total_items;
				break;
			case 'in-progress' :
				$inprogress_class = 'current'; 
				$inprogress_lessons_count = $this->total_items;
				break;
		endswitch;

//		error_log(__FUNCTION__);
		?>
		<div class="grading-selects">
			<?php
//			$activity_args = $this->activity_args;
//			$activity_args['count'] = true;
//			add_filter( 'comments_clauses', array( $this, 'filter_lessons' ) );
//			if ( 0 == $graded_lessons_count && 'graded' != $this->grading_status ) {
//				$activity_args['type'] = 'sensei_quiz_grade';
//				$ungraded_lessons_count = WooThemes_Sensei_Utils::sensei_check_for_activity( $activity_args );
//			}
//			if ( 0 == $ungraded_lessons_count && 'ungraded' != $this->grading_status ) {
//				$activity_args['type'] = '';
//				$ungraded_lessons_count = WooThemes_Sensei_Utils::sensei_check_for_activity( $activity_args );
//			}
//			if ( 0 == $inprogress_lessons_count && 'in-progress' != $this->grading_status ) {
//				$activity_args['type'] = 'sensei_quiz_grade';
//				$ungraded_lessons_count = WooThemes_Sensei_Utils::sensei_check_for_activity( $activity_args );
//			}
//			remove_filter( 'comments_clauses', array( $this, 'filter_lessons' ) );
//			$all_lessons_count = $ungraded_lessons_count + $graded_lessons_count + $inprogress_lessons_count;

			// Display counters and status links
			echo '<ul class="subsubsub">' . "\n";

				$all_query['grading_status'] = 'all';
				$ungraded_query['grading_status'] = 'ungraded';
				$graded_query['grading_status'] = 'graded';
				$inprogress_query['grading_status'] = 'in-progress';

				if( $this->search ) {
					$all_query['s'] = $this->search;
					$ungraded_query['s'] = $this->search;
					$graded_query['s'] = $this->search;
					$inprogress_query['s'] = $this->search;
				}

				echo '<li class="all"><a class="' . $all_class . '" href="' . add_query_arg( $all_query ) . '">' . __( 'All', 'woothemes-sensei' ) . ' <span class="count">(' . $all_lessons_count . ')</span></a> | </li>' . "\n";
				echo '<li class="ungraded"><a class="' . $ungraded_class . '" href="' . add_query_arg( $ungraded_query ) . '">' . __( 'Ungraded', 'woothemes-sensei' ) . ' <span class="count">(' . $ungraded_lessons_count . ')</span></a> | </li>' . "\n";
				echo '<li class="graded"><a class="' . $graded_class . '" href="' . add_query_arg( $graded_query ) . '">' . __( 'Graded', 'woothemes-sensei' ) . ' <span class="count">(' . $graded_lessons_count . ')</span></a> | </li>' . "\n";
				echo '<li class="in-progress"><a class="' . $inprogress_class . '" href="' . add_query_arg( $inprogress_query ) . '">' . __( 'In Progress', 'woothemes-sensei' ) . ' <span class="count">(' . $inprogress_lessons_count . ')</span></a></li>' . "\n";

			echo '</ul>' . "\n";

			do_action( 'sensei_grading_before_dropdown_filters' );

			// Get the Course Posts
			$post_args = array(	'post_type' 		=> 'course',
								'numberposts' 		=> -1,
								'orderby'         	=> 'title',
	    						'order'           	=> 'ASC',
	    						'post_status'      	=> 'any',
	    						'suppress_filters' 	=> 0,
								);
			$posts_array = get_posts( apply_filters( 'sensei_grading_filter_courses', $post_args ) );

			$selected_course_id = 0;
			if ( isset( $_GET['course_id'] ) ) {
				$selected_course_id = intval( $_GET['course_id'] );
			} // End If Statement

			echo '<div class="select-box">' . "\n";

				echo '<select id="grading-course-options" name="grading_course" class="chosen_select widefat">' . "\n";
					echo '<option value="">' . __( 'Select a course', 'woothemes-sensei' ) . '</option>';
					if ( count( $posts_array ) > 0 ) {
						foreach ($posts_array as $post_item){
							echo '<option value="' . esc_attr( absint( $post_item->ID ) ) . '" ' . selected( $post_item->ID, $selected_course_id, false ) . '>' . esc_html( $post_item->post_title ) . '</option>' . "\n";
						} // End For Loop
					} // End If Statement
				echo '</select>' . "\n";

			echo '</div>' . "\n";

			echo '<div class="select-box">' . "\n";

				echo '<select id="grading-lesson-options" data-placeholder="&larr; Select a course" name="grading_lesson" class="chosen_select widefat">' . "\n";

					if ( 0 < $selected_course_id ) {
						$selected_lesson_id = 0;
						if ( isset( $_GET['lesson_id'] ) ) {
							$selected_lesson_id = intval( $_GET['lesson_id'] );
						} // End If Statement
						echo $woothemes_sensei->grading->lessons_drop_down_html( $selected_course_id, $selected_lesson_id );
					} // End If Statement

				echo '</select>' . "\n";

			echo '</div>' . "\n";

			if( $selected_course_id && $selected_lesson_id ) {

				echo '<div class="select-box reset-filter">' . "\n";

					echo '<a class="button-secondary" href="' . remove_query_arg( array( 'lesson_id', 'course_id' ) ) . '">' . __( 'Reset filter', 'woothemes-sensei' ) . '</a>' . "\n";

				echo '</div>' . "\n";

			}

			?>
		</div><!-- /.grading-selects -->
		<?php
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