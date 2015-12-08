<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Admin Grading Overview Data Table in Sensei.
 *
 * @package Assessment
 * @author Automattic
 * @since 1.3.0
 */
class Sensei_Grading_Main extends WooThemes_Sensei_List_Table {

    public $user_id;
	public $course_id;
	public $lesson_id;
	public $view;
	public $user_ids = false;
	public $page_slug = 'sensei_grading';

	/**
	 * Constructor
	 * @since  1.3.0
	 */
	public function __construct ( $args = null ) {

		$defaults = array(
			'course_id' => 0,
			'lesson_id' => 0,
			'user_id' => false,
			'view' => 'ungraded',
		);
		$args = wp_parse_args( $args, $defaults );

		$this->course_id = intval( $args['course_id'] );
		$this->lesson_id = intval( $args['lesson_id'] );
		if ( !empty($args['user_id']) ) {
			$this->user_id = intval( $args['user_id'] );
		}

		if( !empty( $args['view'] ) && in_array( $args['view'], array( 'in-progress', 'graded', 'ungraded', 'all' ) ) ) {
			$this->view = $args['view'];
		}

		// Load Parent token into constructor
		parent::__construct( 'grading_main' );

		// Actions
		add_action( 'sensei_before_list_table', array( $this, 'data_table_header' ) );
		add_action( 'sensei_after_list_table', array( $this, 'data_table_footer' ) );
	} // End __construct()

	/**
	 * Define the columns that are going to be used in the table
	 * @since  1.7.0
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_columns() {
		$columns = array(
			'title' => __( 'Learner', 'woothemes-sensei' ),
			'course' => __( 'Course', 'woothemes-sensei' ),
			'lesson' => __( 'Lesson', 'woothemes-sensei' ),
			'updated' => __( 'Updated', 'woothemes-sensei' ),
			'user_status' => __( 'Status', 'woothemes-sensei' ),
			'user_grade' => __( 'Grade', 'woothemes-sensei' ),
			'action' => '',
		);

		$columns = apply_filters( 'sensei_grading_default_columns', $columns, $this );
		return $columns;
	}

	/**
	 * Define the columns that are going to be used in the table
	 * @since  1.7.0
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_sortable_columns() {
		$columns = array(
			'title' => array( 'title', false ),
			'course' => array( 'course', false ),
			'lesson' => array( 'lesson', false ),
			'updated' => array( 'updated', false ),
			'user_status' => array( 'user_status', false ),
			'user_grade' => array( 'user_grade', false ),
		);
		$columns = apply_filters( 'sensei_grading_default_columns_sortable', $columns, $this );
		return $columns;
	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 * @since  1.7.0
	 * @return void
	 */
	public function prepare_items() {
		global  $per_page, $wp_version;

		// Handle orderby
		$orderby = '';
		if ( !empty( $_GET['orderby'] ) ) {
			if ( array_key_exists( esc_html( $_GET['orderby'] ), $this->get_sortable_columns() ) ) {
				$orderby = esc_html( $_GET['orderby'] );
			} // End If Statement
		}

		// Handle order
		$order = 'DESC';
		if ( !empty( $_GET['order'] ) ) {
			$order = ( 'ASC' == strtoupper($_GET['order']) ) ? 'ASC' : 'DESC';
		}

		// Handle search
		$search = false;
		if ( !empty( $_GET['s'] ) ) {
			$search = esc_html( $_GET['s'] );
		} // End If Statement
		$this->search = $search;

		// Searching users on statuses requires sub-selecting the statuses by user_ids
		if ( $this->search ) {
			$user_args = array(
				'search' => '*' . $this->search . '*',
				'fields' => 'ID',
			);
			// Filter for extending
			$user_args = apply_filters( 'sensei_grading_search_users', $user_args );
			if ( !empty( $user_args ) ) {
				$learners_search = new WP_User_Query( $user_args );
				// Store for reuse on counts
				$this->user_ids = $learners_search->get_results();
			}
		} // End If Statement

		$per_page = $this->get_items_per_page( 'sensei_comments_per_page' );
		$per_page = apply_filters( 'sensei_comments_per_page', $per_page, 'sensei_comments' );

		$paged = $this->get_pagenum();
		$offset = 0;
		if ( !empty($paged) ) {
			$offset = $per_page * ( $paged - 1 );
		} // End If Statement

		$activity_args = array(
			'type' => 'sensei_lesson_status',
			'number' => $per_page,
			'offset' => $offset,
			'orderby' => $orderby,
			'order' => $order,
			'status' => 'any',
		);

		if( $this->lesson_id ) {
			$activity_args['post_id'] = $this->lesson_id;
		}
		elseif( $this->course_id ) {
			// Currently not possible to restrict to a single Course, as that requires WP_Comment to support multiple
			// post_ids (i.e. every lesson within the Course), WP 4.1 ( https://core.trac.wordpress.org/changeset/29808 )
			if ( version_compare($wp_version, '4.1', '>=') ) {
				$activity_args['post__in'] = Sensei()->course->course_lessons( $this->course_id, 'any', 'ids' );
			}
		}
		// Sub select to group of learners
		if ( $this->user_ids ) {
			$activity_args['user_id'] = (array) $this->user_ids;
		}
		// Restrict to a single Learner
		if( $this->user_id ) {
			$activity_args['user_id'] = $this->user_id;
		}


		switch( $this->view ) {
			case 'in-progress' :
				$activity_args['status'] = 'in-progress';
				break;

			case 'ungraded' :
				$activity_args['status'] = 'ungraded';
				break;

			case 'graded' :
				$activity_args['status'] = array( 'graded', 'passed', 'failed' );
				break;

			case 'all' :
			default:
				$activity_args['status'] = 'any';
				break;
		} // End switch

		$activity_args = apply_filters( 'sensei_grading_filter_statuses', $activity_args );

		// WP_Comment_Query doesn't support SQL_CALC_FOUND_ROWS, so instead do this twice
		$total_statuses = Sensei_Utils::sensei_check_for_activity( array_merge( $activity_args, array('count' => true, 'offset' => 0, 'number' => 0) ) );

		// Ensure we change our range to fit (in case a search threw off the pagination) - Should this be added to all views?
		if ( $total_statuses < $activity_args['offset'] ) {
			$new_paged = floor( $total_statuses / $activity_args['number'] );
			$activity_args['offset'] = $new_paged * $activity_args['number'];
		}
		$statuses = Sensei_Utils::sensei_check_for_activity( $activity_args, true );
		// Need to always return an array, even with only 1 item
		if ( !is_array($statuses) ) {
			$statuses = array( $statuses );
		}
		$this->total_items = $total_statuses;
		$this->items = $statuses;

		$total_items = $this->total_items;
		$total_pages = ceil( $total_items / $per_page );
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'total_pages' => $total_pages,
			'per_page' => $per_page
		) );
	}

	/**
	 * Generates content for a single row of the table, overriding parent
	 * @since  1.7.0
	 * @param object $item The current item
	 */
	protected function get_row_data( $item ) {
		global $wp_version;

		$grade = '';
		if( 'complete' == $item->comment_approved ) {
			$status_html = '<span class="graded">' . __( 'Completed', 'woothemes-sensei' ) . '</span>';
			$grade =  __( 'No Grade', 'woothemes-sensei' );
		}
		elseif( 'graded' == $item->comment_approved ) {
			$status_html = '<span class="graded">' .  __( 'Graded', 'woothemes-sensei' )  . '</span>';
			$grade = get_comment_meta( $item->comment_ID, 'grade', true) . '%';
		}
		elseif( 'passed' == $item->comment_approved ) {
			$status_html = '<span class="passed">' .  __( 'Passed', 'woothemes-sensei' )  . '</span>';
			$grade = get_comment_meta( $item->comment_ID, 'grade', true) . '%';
		}
		elseif( 'failed' == $item->comment_approved ) {
			$status_html = '<span class="failed">' .  __( 'Failed', 'woothemes-sensei' )  . '</span>';
			$grade = get_comment_meta( $item->comment_ID, 'grade', true) . '%';
		}
		elseif( 'ungraded' == $item->comment_approved ) {
			$status_html = '<span class="ungraded">' .  __( 'Ungraded', 'woothemes-sensei' )  . '</span>';
			$grade = __( 'N/A', 'woothemes-sensei' );
		}
		else {
			$status_html = '<span class="in-progress">' . __( 'In Progress', 'woothemes-sensei' ) . '</span>';
			$grade = __( 'N/A', 'woothemes-sensei' );
		}

        $title = Sensei_Learner::get_full_name( $item->user_id );

		// QuizID to be deprecated
		$quiz_id = get_post_meta( $item->comment_post_ID, '_lesson_quiz', true );
		$quiz_link = esc_url( add_query_arg( array( 'page' => $this->page_slug, 'user' => $item->user_id, 'quiz_id' => $quiz_id ), admin_url( 'admin.php' ) ) );

		$grade_link = '';
		switch( $item->comment_approved ) {
			case 'ungraded':
				$grade_link = '<a class="button-primary button" href="' . $quiz_link . '">' . __('Grade quiz', 'woothemes-sensei' ) . '</a>';
				break;

			case 'graded':
			case 'passed':
			case 'failed':
				$grade_link = '<a class="button-secondary button" href="' . $quiz_link . '">' . __('Review grade', 'woothemes-sensei' ) . '</a>';
				break;
		}

		$course_id = get_post_meta( $item->comment_post_ID, '_lesson_course', true );
		$course_title = '';
		if ( !empty($course_id) && version_compare($wp_version, '4.1', '>=') ) {
			$course_title = '<a href="' . esc_url( add_query_arg( array( 'page' => $this->page_slug, 'course_id' => $course_id ), admin_url( 'admin.php' ) ) ) . '">' . get_the_title( $course_id ) . '</a>';
		}
		else if ( !empty($course_id) ) {
			$course_title = get_the_title( $course_id );
		}
		$lesson_title = '<a href="' . add_query_arg( array( 'page' => $this->page_slug, 'lesson_id' => $item->comment_post_ID ), admin_url( 'admin.php' ) ) . '">' . get_the_title( $item->comment_post_ID ) . '</a>';

		$column_data = apply_filters( 'sensei_grading_main_column_data', array(
				'title' => '<strong><a class="row-title" href="' . esc_url( add_query_arg( array( 'page' => $this->page_slug, 'user_id' => $item->user_id ), admin_url( 'admin.php' ) ) ) . '"">' . $title . '</a></strong>',
				'course' => $course_title,
				'lesson' => $lesson_title,
				'updated' => $item->comment_date,
				'user_status' => $status_html,
				'user_grade' => $grade,
				'action' => $grade_link,
			), $item, $course_id );

		return $column_data;
	}

	/**
	 * Sets output when no items are found
	 * Overloads the parent method
	 * @since  1.3.0
	 * @return void
	 */
	public function no_items() {

        _e( 'No submissions found.', 'woothemes-sensei' );

	} // End no_items()

	/**
	 * Output for table heading
	 * @since  1.3.0
	 * @return void
	 */
	public function data_table_header() {
		global  $wp_version;

		echo '<div class="grading-selects">';
		do_action( 'sensei_grading_before_dropdown_filters' );

		echo '<div class="select-box">' . "\n";

			echo '<select id="grading-course-options" name="grading_course" class="chosen_select widefat">' . "\n";

				echo Sensei()->grading->courses_drop_down_html( $this->course_id );

			echo '</select>' . "\n";

		echo '</div>' . "\n";

		echo '<div class="select-box">' . "\n";

			echo '<select id="grading-lesson-options" data-placeholder="&larr; ' . __( 'Select a course', 'woothemes-sensei' ) . '" name="grading_lesson" class="chosen_select widefat">' . "\n";

				echo Sensei()->grading->lessons_drop_down_html( $this->course_id, $this->lesson_id );

			echo '</select>' . "\n";

		echo '</div>' . "\n";

		if( $this->course_id && $this->lesson_id ) {

			echo '<div class="select-box reset-filter">' . "\n";

				echo '<a class="button-secondary" href="' . esc_url( remove_query_arg( array( 'lesson_id', 'course_id' ) ) ) . '">' . __( 'Reset filter', 'woothemes-sensei' ) . '</a>' . "\n";

			echo '</div>' . "\n";

		}

		echo '</div><!-- /.grading-selects -->';

		$menu = array();

		// Setup counters
		$count_args = array(
			'type' => 'lesson',
		);
		$query_args = array(
			'page' => $this->page_slug,
		);
		if( $this->course_id ) {
			// Currently not possible to restrict to a single Course, as that requires WP_Comment to support multiple
			// post_ids (i.e. every lesson within the Course), WP 4.1 ( https://core.trac.wordpress.org/changeset/29808 )
			$query_args['course_id'] = $this->course_id;
			if ( version_compare($wp_version, '4.1', '>=') ) {
				$count_args['post__in'] = Sensei()->course->course_lessons( $this->course_id, 'any', 'ids' );
			}
		}
		if( $this->lesson_id ) {
			$query_args['lesson_id'] = $this->lesson_id;
			// Restrict to a single lesson
			$count_args['post_id'] = $this->lesson_id;
		}
		if( $this->search ) {
			$query_args['s'] = $this->search;
		}
		if ( !empty($this->user_ids) ) {
			$count_args['user_id'] = $this->user_ids;
		}
		if( !empty($this->user_id) ) {
			$query_args['user_id'] = $this->user_id;
			$count_args['user_id'] = $this->user_id;
		}

		$all_lessons_count = $ungraded_lessons_count = $graded_lessons_count = $inprogress_lessons_count = 0;
		$all_class = $ungraded_class = $graded_class = $inprogress_class = '';

		switch( $this->view ) :
			case 'all':
				$all_class = 'current';
				break;
			case 'ungraded' :
			default:
				$ungraded_class = 'current';
				break;
			case 'graded' :
				$graded_class = 'current';
				break;
			case 'in-progress' :
				$inprogress_class = 'current';
				break;
		endswitch;

		$counts = Sensei()->grading->count_statuses( apply_filters( 'sensei_grading_count_statues', $count_args ) );

		$inprogress_lessons_count = $counts['in-progress'];
		$ungraded_lessons_count = $counts['ungraded'];
		$graded_lessons_count = $counts['graded'] + $counts['passed'] + $counts['failed'];
		$all_lessons_count = $counts['complete'] + $ungraded_lessons_count + $graded_lessons_count + $inprogress_lessons_count;

		// Display counters and status links
		$all_args = $ungraded_args = $graded_args = $inprogress_args = $query_args;

		$all_args['view'] = 'all';
		$ungraded_args['view'] = 'ungraded';
		$graded_args['view'] = 'graded';
		$inprogress_args['view'] = 'in-progress';

		$format = '<a class="%s" href="%s">%s <span class="count">(%s)</span></a>';
		$menu['all'] = sprintf( $format, $all_class, esc_url( add_query_arg( $all_args, admin_url( 'admin.php' ) ) ), __( 'All', 'woothemes-sensei' ), number_format( (int) $all_lessons_count ) );
		$menu['ungraded'] = sprintf( $format, $ungraded_class, esc_url( add_query_arg( $ungraded_args, admin_url( 'admin.php' ) ) ), __( 'Ungraded', 'woothemes-sensei' ), number_format( (int) $ungraded_lessons_count ) );
		$menu['graded'] = sprintf( $format, $graded_class, esc_url( add_query_arg( $graded_args, admin_url( 'admin.php' ) ) ), __( 'Graded', 'woothemes-sensei' ), number_format( (int) $graded_lessons_count ) );
		$menu['in-progress'] = sprintf( $format, $inprogress_class, esc_url( add_query_arg( $inprogress_args, admin_url( 'admin.php' ) ) ), __( 'In Progress', 'woothemes-sensei' ), number_format( (int) $inprogress_lessons_count ) );

		$menu = apply_filters( 'sensei_grading_sub_menu', $menu );
		if ( !empty($menu) ) {
			echo '<ul class="subsubsub">' . "\n";
			foreach ( $menu as $class => $item ) {
				$menu[ $class ] = "\t<li class='$class'>$item";
			}
			echo implode( " |</li>\n", $menu ) . "</li>\n";
			echo '</ul>' . "\n";
		}

	} // End data_table_header()

	/**
	 * Output for table footer
	 * @since  1.3.0
	 * @return void
	 */
	public function data_table_footer() {
		// Nothing right now
	} // End data_table_footer()

} // End Class

/**
 * Class WooThems_Sensei_Grading_Main
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Grading_Main extends Sensei_Grading_Main{}
