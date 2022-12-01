<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Admin Grading Overview Data Table in Sensei.
 *
 * @package Assessment
 * @author Automattic
 * @since 1.3.0
 */
class Sensei_Grading_Main extends Sensei_List_Table {

	public $user_id;
	public $course_id;
	public $lesson_id;
	public $view;
	public $user_ids  = false;
	public $page_slug = 'sensei_grading';

	/**
	 * Constructor
	 *
	 * @since  1.3.0
	 */
	public function __construct( $args = null ) {

		$defaults = array(
			'course_id' => 0,
			'lesson_id' => 0,
			'user_id'   => false,
			'view'      => 'ungraded',
		);
		$args     = wp_parse_args( $args, $defaults );

		$this->course_id = intval( $args['course_id'] );
		$this->lesson_id = intval( $args['lesson_id'] );
		if ( ! empty( $args['user_id'] ) ) {
			$this->user_id = intval( $args['user_id'] );
		}

		if ( ! empty( $args['view'] ) && in_array( $args['view'], array( 'in-progress', 'graded', 'ungraded', 'all' ) ) ) {
			$this->view = $args['view'];
		}

		// Load Parent token into constructor
		parent::__construct( 'grading_main' );

		// Actions
		add_action( 'sensei_before_list_table', array( $this, 'data_table_header' ) );
		add_action( 'sensei_after_list_table', array( $this, 'data_table_footer' ) );
		remove_action( 'sensei_before_list_table', array( $this, 'table_search_form' ), 5 );
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @since  1.7.0
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_columns() {
		$columns = array(
			'title'       => __( 'Student', 'sensei-lms' ),
			'course'      => __( 'Course', 'sensei-lms' ),
			'lesson'      => __( 'Lesson', 'sensei-lms' ),
			'updated'     => __( 'Updated', 'sensei-lms' ),
			'user_status' => __( 'Status', 'sensei-lms' ),
			'user_grade'  => __( 'Grade', 'sensei-lms' ),
			'action'      => '',
		);

		$columns = apply_filters( 'sensei_grading_default_columns', $columns, $this );
		return $columns;
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @since  1.7.0
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_sortable_columns() {
		$columns = array(
			'title'       => array( 'title', false ),
			'course'      => array( 'course', false ),
			'lesson'      => array( 'lesson', false ),
			'updated'     => array( 'updated', false ),
			'user_status' => array( 'user_status', false ),
			'user_grade'  => array( 'user_grade', false ),
		);
		$columns = apply_filters( 'sensei_grading_default_columns_sortable', $columns, $this );
		return $columns;
	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 *
	 * @since  1.7.0
	 * @return void
	 */
	public function prepare_items() {
		// Handle orderby
		$orderby = '';
		if ( ! empty( $_GET['orderby'] ) ) {
			if ( array_key_exists( esc_html( $_GET['orderby'] ), $this->get_sortable_columns() ) ) {
				$orderby = esc_html( $_GET['orderby'] );
			}
		}

		// Handle order
		$order = 'DESC';
		if ( ! empty( $_GET['order'] ) ) {
			$order = ( 'ASC' == strtoupper( $_GET['order'] ) ) ? 'ASC' : 'DESC';
		}

		// Handle search
		$search = false;
		if ( ! empty( $_GET['s'] ) ) {
			$search = esc_html( $_GET['s'] );
		}
		$this->search = $search;

		// Searching users on statuses requires sub-selecting the statuses by user_ids
		if ( $this->search ) {
			$user_args = array(
				'search' => '*' . $this->search . '*',
				'fields' => 'ID',
			);
			// Filter for extending
			$user_args = apply_filters( 'sensei_grading_search_users', $user_args );
			if ( ! empty( $user_args ) ) {
				$learners_search = new WP_User_Query( $user_args );
				// Store for reuse on counts
				$this->user_ids = $learners_search->get_results();
			}
		}

		$per_page = $this->get_items_per_page( 'sensei_comments_per_page' );
		$per_page = apply_filters( 'sensei_comments_per_page', $per_page, 'sensei_comments' );

		$paged  = $this->get_pagenum();
		$offset = 0;
		if ( ! empty( $paged ) ) {
			$offset = $per_page * ( $paged - 1 );
		}

		$activity_args = array(
			'type'    => 'sensei_lesson_status',
			'number'  => $per_page,
			'offset'  => $offset,
			'orderby' => $orderby,
			'order'   => $order,
			'status'  => 'any',
		);

		if ( $this->lesson_id ) {
			$activity_args['post_id'] = $this->lesson_id;
		} elseif ( $this->course_id ) {
			$activity_args['post__in'] = Sensei()->course->course_lessons( $this->course_id, 'any', 'ids' );
		}
		// Sub select to group of learners
		if ( $this->user_ids ) {
			$activity_args['user_id'] = (array) $this->user_ids;
		}
		// Restrict to a single Learner
		if ( $this->user_id ) {
			$activity_args['user_id'] = $this->user_id;
		}

		switch ( $this->view ) {
			case 'in-progress':
				$activity_args['status'] = 'in-progress';
				break;

			case 'ungraded':
				$activity_args['status'] = 'ungraded';
				break;

			case 'graded':
				$activity_args['status'] = array( 'graded', 'passed', 'failed' );
				break;

			case 'all':
			default:
				$activity_args['status'] = 'any';
				break;
		}

		$activity_args = apply_filters( 'sensei_grading_filter_statuses', $activity_args );

		// WP_Comment_Query doesn't support SQL_CALC_FOUND_ROWS, so instead do this twice
		$total_statuses = Sensei_Utils::sensei_check_for_activity(
			array_merge(
				$activity_args,
				array(
					'count'  => true,
					'offset' => 0,
					'number' => 0,
				)
			)
		);

		// Ensure we change our range to fit (in case a search threw off the pagination) - Should this be added to all views?
		if ( $total_statuses < $activity_args['offset'] ) {
			$new_paged               = floor( $total_statuses / $activity_args['number'] );
			$activity_args['offset'] = $new_paged * $activity_args['number'];
		}
		$statuses = Sensei_Utils::sensei_check_for_activity( $activity_args, true );
		// Need to always return an array, even with only 1 item
		if ( ! is_array( $statuses ) ) {
			$statuses = array( $statuses );
		}
		$this->total_items = $total_statuses;
		$this->items       = $statuses;

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
	 * Generates content for a single row of the table, overriding parent
	 *
	 * @since  1.7.0
	 * @param object $item The current item
	 */
	protected function get_row_data( $item ) {
		global $wp_version;

		$grade = '';
		if ( 'complete' == $item->comment_approved ) {
			$status_html = '<span class="graded">' . esc_html__( 'Completed', 'sensei-lms' ) . '</span>';
			$grade       = __( 'No Grade', 'sensei-lms' );
		} elseif ( 'graded' == $item->comment_approved ) {
			$status_html = '<span class="graded">' . esc_html__( 'Graded', 'sensei-lms' ) . '</span>';
			$grade       = get_comment_meta( $item->comment_ID, 'grade', true ) . '%';
		} elseif ( 'passed' == $item->comment_approved ) {
			$status_html = '<span class="passed">' . esc_html__( 'Passed', 'sensei-lms' ) . '</span>';
			$grade       = get_comment_meta( $item->comment_ID, 'grade', true ) . '%';
		} elseif ( 'failed' == $item->comment_approved ) {
			$status_html = '<span class="failed">' . esc_html__( 'Failed', 'sensei-lms' ) . '</span>';
			$grade       = get_comment_meta( $item->comment_ID, 'grade', true ) . '%';
		} elseif ( 'ungraded' == $item->comment_approved ) {
			$status_html = '<span class="ungraded">' . esc_html__( 'Ungraded', 'sensei-lms' ) . '</span>';
			$grade       = __( 'N/A', 'sensei-lms' );
		} else {
			$status_html = '<span class="in-progress">' . esc_html__( 'In Progress', 'sensei-lms' ) . '</span>';
			$grade       = __( 'N/A', 'sensei-lms' );
		}

		$title = Sensei_Learner::get_full_name( $item->user_id );

		$quiz_id   = Sensei()->lesson->lesson_quizzes( $item->comment_post_ID, 'any' );
		$quiz_link = add_query_arg(
			array(
				'page'    => $this->page_slug,
				'user'    => $item->user_id,
				'quiz_id' => $quiz_id,
			),
			admin_url( 'admin.php' )
		);

		$grade_link = '';
		switch ( $item->comment_approved ) {
			case 'ungraded':
				$grade_link = '<a class="button-primary button" href="' . esc_url( $quiz_link ) . '">' . esc_html__( 'Grade quiz', 'sensei-lms' ) . '</a>';
				break;

			case 'graded':
			case 'passed':
			case 'failed':
				$grade_link = '<a class="button-secondary button" href="' . esc_url( $quiz_link ) . '">' . esc_html__( 'Review grade', 'sensei-lms' ) . '</a>';
				break;
		}

		$course_id    = get_post_meta( $item->comment_post_ID, '_lesson_course', true );
		$course_title = '';

		if ( ! empty( $course_id ) ) {
			$course_title = '<a href="' . esc_url(
				add_query_arg(
					array(
						'page'      => $this->page_slug,
						'course_id' => $course_id,
					),
					admin_url( 'admin.php' )
				)
			) . '">' . esc_html( get_the_title( $course_id ) ) . '</a>';
		}

		$lesson_title = '<a href="' . esc_url(
			add_query_arg(
				array(
					'page'      => $this->page_slug,
					'lesson_id' => $item->comment_post_ID,
				),
				admin_url( 'admin.php' )
			)
		) . '">' . esc_html( get_the_title( $item->comment_post_ID ) ) . '</a>';

		$column_data = apply_filters(
			'sensei_grading_main_column_data',
			array(
				'title'       => '<strong><a class="row-title" href="' . esc_url(
					add_query_arg(
						array(
							'page'    => $this->page_slug,
							'user_id' => $item->user_id,
						),
						admin_url( 'admin.php' )
					)
				) . '">' . esc_html( $title ) . '</a></strong>',
				'course'      => $course_title,
				'lesson'      => $lesson_title,
				'updated'     => $item->comment_date,
				'user_status' => $status_html,
				'user_grade'  => $grade,
				'action'      => $grade_link,
			),
			$item,
			$course_id
		);

		$escaped_column_data = array();

		foreach ( $column_data as $key => $data ) {
			$escaped_column_data[ $key ] = wp_kses_post( $data );
		}

		return $escaped_column_data;
	}

	/**
	 * Sets output when no items are found
	 * Overloads the parent method
	 *
	 * @since  1.3.0
	 * @return void
	 */
	public function no_items() {

		esc_html_e( 'No submissions found.', 'sensei-lms' );

	}

	/**
	 * Output for table heading
	 *
	 * @since  1.3.0
	 * @return void
	 */
	public function data_table_header() {
		do_action( 'sensei_grading_before_dropdown_filters' );

		echo '<select id="grading-course-options" name="grading_course" class="chosen_select widefat">' . "\n";
			echo wp_kses(
				Sensei()->grading->courses_drop_down_html( $this->course_id ),
				array(
					'option' => array(
						'selected' => array(),
						'value'    => array(),
					),
				)
			);
		echo '</select>' . "\n";

		echo '<select id="grading-lesson-options" data-placeholder="&larr; ' . esc_attr__( 'Select a course', 'sensei-lms' ) . '" name="grading_lesson" class="chosen_select widefat">' . "\n";
			echo wp_kses(
				Sensei()->grading->lessons_drop_down_html( $this->course_id, $this->lesson_id ),
				array(
					'option' => array(
						'selected' => array(),
						'value'    => array(),
					),
				)
			);
		echo '</select>' . "\n";

		$reset_button_enabled = $this->course_id && $this->lesson_id;
		$reset_button_href    = $reset_button_enabled ? remove_query_arg( array( 'lesson_id', 'course_id' ) ) : '#';
		$reset_button_classes = [ 'button-secondary', 'sensei-grading-filters__reset-button' ];
		if ( ! $reset_button_enabled ) {
			$reset_button_classes[] = 'disabled';
		}
		echo '<a class="' . esc_attr( implode( ' ', $reset_button_classes ) ) . '" href="' . esc_url( $reset_button_href ) . '">' . esc_html__( 'Reset filter', 'sensei-lms' ) . '</a>' . "\n";
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			echo '<div class="alignleft actions sensei-actions__always-visible">';
		}
		parent::extra_tablenav( $which );

		if ( 'top' === $which ) {
			echo '</div>';
		}
	}

	/**
	 * Output search form for table.
	 */
	public function table_search_form() {
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$this->search_box( apply_filters( 'sensei_list_table_search_button_text', __( 'Search Users', 'sensei-lms' ) ), 'search_id' );
	}

	/**
	 * Gets the list of views available on this table.
	 *
	 * @return array
	 */
	public function get_views() {
		$menu = array();

		// Setup counters.
		$count_args = array(
			'type' => 'lesson',
		);
		$query_args = array(
			'page' => $this->page_slug,
		);
		if ( $this->course_id ) {
			$query_args['course_id'] = $this->course_id;
			$count_args['post__in']  = Sensei()->course->course_lessons( $this->course_id, 'any', 'ids' );
		}
		if ( $this->lesson_id ) {
			$query_args['lesson_id'] = $this->lesson_id;
			// Restrict to a single lesson.
			$count_args['post_id'] = $this->lesson_id;
		}
		if ( $this->search ) {
			$query_args['s'] = $this->search;
		}
		if ( ! empty( $this->user_ids ) ) {
			$count_args['user_id'] = $this->user_ids;
		}
		if ( ! empty( $this->user_id ) ) {
			$query_args['user_id'] = $this->user_id;
			$count_args['user_id'] = $this->user_id;
		}

		$all_lessons_count = $ungraded_lessons_count = $graded_lessons_count = $inprogress_lessons_count = 0;
		$all_class         = $ungraded_class = $graded_class = $inprogress_class = '';

		switch ( $this->view ) :
			case 'ungraded':
				$ungraded_class = 'current';
				break;
			case 'graded':
				$graded_class = 'current';
				break;
			case 'in-progress':
				$inprogress_class = 'current';
				break;
			case 'all':
			default:
				$all_class = 'current';
				break;
		endswitch;

		$counts = Sensei()->grading->count_statuses( apply_filters( 'sensei_grading_count_statues', $count_args ) );

		$inprogress_lessons_count = $counts['in-progress'];
		$ungraded_lessons_count   = $counts['ungraded'];
		$graded_lessons_count     = $counts['graded'] + $counts['passed'] + $counts['failed'];
		$all_lessons_count        = $counts['complete'] + $ungraded_lessons_count + $graded_lessons_count + $inprogress_lessons_count;

		// Display counters and status links
		$all_args = $ungraded_args = $graded_args = $inprogress_args = $query_args;

		$all_args['view']        = 'all';
		$ungraded_args['view']   = 'ungraded';
		$graded_args['view']     = 'graded';
		$inprogress_args['view'] = 'in-progress';

		$format              = '<a class="%s" href="%s">%s <span class="count">(%s)</span></a>';
		$menu['all']         = sprintf(
			$format,
			$all_class,
			esc_url( add_query_arg( $all_args, admin_url( 'admin.php' ) ) ),
			__( 'All', 'sensei-lms' ),
			number_format( (int) $all_lessons_count )
		);
		$menu['ungraded']    = sprintf(
			$format,
			$ungraded_class,
			esc_url( add_query_arg( $ungraded_args, admin_url( 'admin.php' ) ) ),
			__( 'Ungraded', 'sensei-lms' ),
			number_format( (int) $ungraded_lessons_count )
		);
		$menu['graded']      = sprintf(
			$format,
			$graded_class,
			esc_url( add_query_arg( $graded_args, admin_url( 'admin.php' ) ) ),
			__( 'Graded', 'sensei-lms' ),
			number_format( (int) $graded_lessons_count )
		);
		$menu['in-progress'] = sprintf(
			$format,
			$inprogress_class,
			esc_url( add_query_arg( $inprogress_args, admin_url( 'admin.php' ) ) ),
			__( 'In Progress', 'sensei-lms' ),
			number_format( (int) $inprogress_lessons_count )
		);

		return apply_filters( 'sensei_grading_sub_menu', $menu );
	}

	/**
	 * Output for table footer
	 *
	 * @since  1.3.0
	 * @return void
	 */
	public function data_table_footer() {
		// Nothing right now
	}

}

/**
 * Class WooThems_Sensei_Grading_Main
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Grading_Main extends Sensei_Grading_Main{}
