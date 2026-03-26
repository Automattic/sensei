<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Sensei\Internal\Services\Grading_Listing_Service_Interface;
use Sensei\Internal\Services\Grading_Item;
use Sensei\Internal\Services\Progress_Query_Service_Factory;

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
	 * The grading listing service.
	 *
	 * @var Grading_Listing_Service_Interface
	 */
	private Grading_Listing_Service_Interface $grading_listing_service;

	/**
	 * Constructor
	 *
	 * @since  1.3.0
	 *
	 * @param array|null                             $args                    Constructor arguments.
	 * @param Grading_Listing_Service_Interface|null $grading_listing_service The grading listing service.
	 */
	public function __construct( $args = null, ?Grading_Listing_Service_Interface $grading_listing_service = null ) {

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

		$this->grading_listing_service = $grading_listing_service
			?? ( new Progress_Query_Service_Factory() )->create_grading_listing_service();

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

		/**
		 * Filter columns for the grading list table.
		 *
		 * @hook sensei_grading_default_columns
		 *
		 * @param {array} Columns.
		 * @param {Sensei_Grading_Main} The grading list table.
		 * @return {array} Filtered columns.
		 */
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

		/**
		 * Filter sortable columns for the grading list table.
		 *
		 * @hook sensei_grading_default_columns_sortable
		 *
		 * @param {array} Sortable columns.
		 * @param {Sensei_Grading_Main} The grading list table.
		 * @return {array} Filtered sortable columns.
		 */
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

			/**
			 * Filter user searching arguments in Grading.
			 *
			 * @hook sensei_grading_search_users
			 *
			 * @param {array} $user_args User search arguments.
			 * @return {array} Filtered user search arguments.
			 */
			$user_args = apply_filters( 'sensei_grading_search_users', $user_args );

			if ( ! empty( $user_args ) ) {
				$learners_search = new WP_User_Query( $user_args );
				// Store for reuse on counts
				$this->user_ids = $learners_search->get_results();
			}
		}

		$per_page = $this->get_items_per_page( 'sensei_comments_per_page' );

		/**
		 * Filter number of comments per page.
		 *
		 * @hook sensei_comments_per_page
		 *
		 * @param {int} $per_page Comments per page.
		 * @param {string} $comments_type Type of comments.
		 * @return {int} Filtered comments per page.
		 */
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

		/**
		 * Filter activity statuses arguments for Grading.
		 *
		 * @hook sensei_grading_filter_statuses
		 *
		 * @param {array} $activity_args Student activity arguments.
		 * @return {array} Filtered activity arguments.
		 */
		$activity_args = apply_filters( 'sensei_grading_filter_statuses', $activity_args );

		// Apply teacher and temp-user restrictions so that both listing rows
		// and cached per-status counts reflect these filters. For tables-based
		// storage, these args are applied as SQL clauses. For comments-based,
		// post__in flows through to WP_Comment_Query and the remaining args
		// are handled by existing post-filters on sensei_check_for_activity.
		$count_restrictions = apply_filters( 'sensei_count_statuses_args', array( 'type' => 'lesson' ) );

		// Merge teacher's post__in restriction.
		if ( ! empty( $count_restrictions['post__in'] ) ) {
			if ( ! empty( $activity_args['post__in'] ) ) {
				// Intersect: keep only lessons in both the course filter and teacher filter.
				$intersected = array_values(
					array_intersect( $activity_args['post__in'], $count_restrictions['post__in'] )
				);

				// Force no-results when the intersection is empty (e.g. teacher
				// does not own any lessons in the selected course).
				$activity_args['post__in'] = empty( $intersected ) ? array( 0 ) : $intersected;
			} elseif ( ! empty( $activity_args['post_id'] ) ) {
				// Validate that the specific lesson belongs to this teacher's courses.
				if ( ! in_array( (int) $activity_args['post_id'], array_map( 'intval', $count_restrictions['post__in'] ), true ) ) {
					$activity_args['post__in'] = array( 0 );
				}
			} else {
				$activity_args['post__in'] = $count_restrictions['post__in'];
			}
		}

		// Pass through temp-user exclusion for the listing service.
		if ( ! empty( $count_restrictions['exclude_user_login_prefixes'] ) ) {
			$activity_args['exclude_user_login_prefixes'] = $count_restrictions['exclude_user_login_prefixes'];
			if ( ! empty( $count_restrictions['include_statuses_override'] ) ) {
				$activity_args['include_statuses_override'] = $count_restrictions['include_statuses_override'];
			}
		}

		$result            = $this->grading_listing_service->get_lesson_progress_items( $activity_args );
		$this->total_items = $result['total_count'];
		$this->items       = $result['items'];

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
	 * @param Grading_Item $item The current item.
	 */
	protected function get_row_data( $item ) {
		$status    = $item->status;
		$user_id   = $item->user_id;
		$lesson_id = $item->lesson_id;
		$updated   = $item->updated_at;
		$grade_val = $item->grade;

		$grade_display = null !== $grade_val ? $grade_val . '%' : __( 'N/A', 'sensei-lms' );

		$grade = '';
		if ( 'complete' == $status ) {
			$status_html = '<span class="graded">' . esc_html__( 'Completed', 'sensei-lms' ) . '</span>';
			$grade       = __( 'No Grade', 'sensei-lms' );
		} elseif ( 'graded' == $status ) {
			$status_html = '<span class="graded">' . esc_html__( 'Graded', 'sensei-lms' ) . '</span>';
			$grade       = $grade_display;
		} elseif ( 'passed' == $status ) {
			$status_html = '<span class="passed">' . esc_html__( 'Passed', 'sensei-lms' ) . '</span>';
			$grade       = $grade_display;
		} elseif ( 'failed' == $status ) {
			$status_html = '<span class="failed">' . esc_html__( 'Failed', 'sensei-lms' ) . '</span>';
			$grade       = $grade_display;
		} elseif ( 'ungraded' == $status ) {
			$status_html = '<span class="ungraded">' . esc_html__( 'Ungraded', 'sensei-lms' ) . '</span>';
			$grade       = __( 'N/A', 'sensei-lms' );
		} else {
			$status_html = '<span class="in-progress">' . esc_html__( 'In Progress', 'sensei-lms' ) . '</span>';
			$grade       = __( 'N/A', 'sensei-lms' );
		}

		$title = Sensei_Learner::get_full_name( $user_id );

		$quiz_id   = Sensei()->lesson->lesson_quizzes( $lesson_id, 'any' );
		$quiz_link = add_query_arg(
			array(
				'page'    => $this->page_slug,
				'user'    => $user_id,
				'quiz_id' => $quiz_id,
			),
			admin_url( 'admin.php' )
		);

		$grade_link = '';
		switch ( $status ) {
			case 'ungraded':
				$grade_link = '<a class="button-primary button" href="' . esc_url( $quiz_link ) . '">' . esc_html__( 'Grade quiz', 'sensei-lms' ) . '</a>';
				break;

			case 'graded':
			case 'passed':
			case 'failed':
				$grade_link = '<a class="button-secondary button" href="' . esc_url( $quiz_link ) . '">' . esc_html__( 'Review grade', 'sensei-lms' ) . '</a>';
				break;
		}

		$course_id    = get_post_meta( $lesson_id, '_lesson_course', true );
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
					'lesson_id' => $lesson_id,
				),
				admin_url( 'admin.php' )
			)
		) . '">' . esc_html( get_the_title( $lesson_id ) ) . '</a>';

		/**
		 * Filter columns data for the Grading list table.
		 *
		 * @hook sensei_grading_main_column_data
		 *
		 * @param {array}  $column_data Column data for a row.
		 * @param {Grading_Item} $item Grading item for the row.
		 * @param {int}    $course_id The course ID.
		 * @return {array} Filtered column data.
		 */
		$column_data = apply_filters(
			'sensei_grading_main_column_data',
			array(
				'title'       => '<strong><a class="row-title" href="' . esc_url(
					add_query_arg(
						array(
							'page'    => $this->page_slug,
							'user_id' => $user_id,
						),
						admin_url( 'admin.php' )
					)
				) . '">' . esc_html( $title ) . '</a></strong>',
				'course'      => $course_title,
				'lesson'      => $lesson_title,
				'updated'     => $updated,
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
		/**
		 * Fires before the filter dropdowns in the grading list table.
		 *
		 * @hook sensei_grading_before_dropdown_filters
		 */
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

		/**
		 * Filter the search button text for the list table.
		 *
		 * @hook sensei_list_table_search_button_text
		 *
		 * @param {string} $text The search button text.
		 * @return {string} The filtered search button text.
		 */
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

		/**
		 * Filter count statuses arguments in Grading.
		 *
		 * @hook sensei_grading_count_statues
		 *
		 * @deprecated 4.19.0 Contains typo. Use sensei_grading_count_statuses.
		 *
		 * @param {array} $count_args Count statuses arguments.
		 * @return {array} Filtered count arguments.
		 */
		$count_args = apply_filters_deprecated( 'sensei_grading_count_statues', array( $count_args ), '4.19.0', 'sensei_grading_count_statuses' );

		/**
		 * Filter count statuses arguments in Grading.
		 *
		 * @hook sensei_grading_count_statuses
		 *
		 * @param {array} $count_args Count statuses arguments.
		 * @return {array} Filtered count arguments.
		 */
		$count_args = apply_filters( 'sensei_grading_count_statuses', $count_args );

		// Use cached per-status counts from prepare_items() when available,
		// avoiding a second full-table scan with the same JOINs.
		// Skip the cache if a plugin is filtering $count_args, since the
		// cached counts would not reflect those modifications.
		$has_count_filter = has_filter( 'sensei_grading_count_statuses' ) || has_filter( 'sensei_grading_count_statues' );
		$cached_counts    = $this->grading_listing_service->get_status_counts();
		if ( null !== $cached_counts && ! $has_count_filter ) {
			// Ensure all expected statuses exist with 0 defaults, matching
			// the shape that count_statuses() returns.
			$defaults = array_fill_keys(
				array( 'graded', 'ungraded', 'passed', 'failed', 'in-progress', 'complete' ),
				0
			);
			$counts   = array_merge( $defaults, $cached_counts );

			/** This filter is documented in includes/class-sensei-grading.php */
			$counts = apply_filters( 'sensei_count_statuses', $counts, 'sensei_lesson_status' );
		} else {
			$counts = Sensei()->grading->count_statuses( $count_args );
		}

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

		/**
		 * Filter submenu for Grading.
		 *
		 * @hook sensei_grading_sub_menu
		 *
		 * @param {array} $submunu Submenu.
		 * @return {array} Filtered submenu.
		 */
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
