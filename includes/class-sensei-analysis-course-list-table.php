<?php
use Sensei\Internal\Services\Reports_Item;
use Sensei\Internal\Services\Reports_Listing_Service_Interface;
use Sensei\Internal\Services\Progress_Query_Service_Factory;
use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Admin Analysis Course Data Table in Sensei.
 *
 * @package Analytics
 * @author Automattic
 * @since 1.2.0
 */
class Sensei_Analysis_Course_List_Table extends Sensei_List_Table {

	use Sensei_Reports_Helper_Date_Range_Trait;

	/**
	 * User ID.
	 *
	 * @var int
	 */
	public $user_id;

	/**
	 * Course ID.
	 *
	 * @var int
	 */
	public $course_id;

	/**
	 * Total number of lessons.
	 *
	 * @var int
	 */
	public $total_lessons;

	/**
	 * User IDs.
	 *
	 * @var array
	 */
	public $user_ids;

	/**
	 * Page slug.
	 *
	 * @var string
	 */
	public $page_slug;

	/**
	 * Selected view.
	 *
	 * @var string
	 */
	public $view = 'lesson';

	/**
	 * The reports listing service.
	 *
	 * @var Reports_Listing_Service_Interface
	 */
	private Reports_Listing_Service_Interface $reports_listing_service;

	/**
	 * Constructor
	 *
	 * @param int                                    $course_id               Course ID.
	 * @param int                                    $user_id                 User ID.
	 * @param Reports_Listing_Service_Interface|null $reports_listing_service Reports listing service.
	 *
	 * @since  1.2.0
	 */
	public function __construct( $course_id = 0, $user_id = 0, ?Reports_Listing_Service_Interface $reports_listing_service = null ) {
		$this->course_id               = (int) $course_id;
		$this->user_id                 = (int) $user_id;
		$this->page_slug               = Sensei_Analysis::PAGE_SLUG;
		$this->reports_listing_service = $reports_listing_service ?? ( new Progress_Query_Service_Factory() )->create_reports_listing_service();

		if ( isset( $_GET['view'] ) && in_array( $_GET['view'], array( 'user', 'lesson' ) ) ) {
			$this->view = $_GET['view'];
		}

		// Viewing a single Learner always sets the view to Lessons
		if ( $this->user_id ) {
			$this->view = 'lesson';
		}

		// Load Parent token into constructor
		parent::__construct( 'analysis_course' );

		// Actions
		add_action( 'sensei_before_list_table', array( $this, 'data_table_header' ) );
		add_action( 'sensei_after_list_table', array( $this, 'data_table_footer' ) );
		remove_action( 'sensei_before_list_table', array( $this, 'table_search_form' ), 5 );

		add_filter( 'sensei_list_table_search_button_text', array( $this, 'search_button' ) );
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @since  1.7.0
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_columns() {

		switch ( $this->view ) {
			case 'user':
				$columns = array(
					'title'       => __( 'Student', 'sensei-lms' ),
					'email'       => __( 'Email', 'sensei-lms' ),
					'started'     => __( 'Date Started', 'sensei-lms' ),
					'completed'   => __( 'Date Completed', 'sensei-lms' ),
					'user_status' => __( 'Status', 'sensei-lms' ),
					'percent'     => __( 'Percent Complete', 'sensei-lms' ),
				);
				break;

			case 'lesson':
			default:
				if ( $this->user_id ) {

					$columns = array(
						'title'       => __( 'Lesson', 'sensei-lms' ),
						'started'     => __( 'Date Started', 'sensei-lms' ),
						'completed'   => __( 'Date Completed', 'sensei-lms' ),
						'user_status' => __( 'Status', 'sensei-lms' ),
						'grade'       => __( 'Grade', 'sensei-lms' ),
					);

				} else {

					$columns = array(
						'title'         => __( 'Lesson', 'sensei-lms' ),
						'num_learners'  => __( 'Students', 'sensei-lms' ),
						'completions'   => __( 'Completed', 'sensei-lms' ),
						'average_grade' => __( 'Average Grade', 'sensei-lms' ),
					);

				}
				break;
		}

		/**
		 * Filter the columns that are going to be used in the Course Analysis list table.
		 * Backwards compatible filter. Use sensei_analysis_course_columns instead.
		 *
		 * @hook sensei_analysis_course_{view}_columns
		 *
		 * @param {array}                             $columns The array of columns to use in the table.
		 * @param {Sensei_Analysis_Course_List_Table} $this    The current instance of the class.
		 * @return {array} $columns The array of columns to use with the table.
		*/
		$columns = apply_filters( 'sensei_analysis_course_' . $this->view . '_columns', $columns, $this );

		/**
		 * Filter the columns that are going to be used in the Course Analysis list table.
		 *
		 * @hook sensei_analysis_course_columns
		 *
		 * @param {array}                             $columns The array of columns to use in the table.
		 * @param {Sensei_Analysis_Course_List_Table} $this    The current instance of the class.
		 * @return {array} $columns The array of columns to use with the table.
		 */
		$columns = apply_filters( 'sensei_analysis_course_columns', $columns, $this );

		return $columns;
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @since  1.7.0
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_sortable_columns() {

		switch ( $this->view ) {
			case 'user':
				$columns = array(
					'completed' => array( 'comment_date', false ),
				);
				break;

			case 'lesson':
			default:
				if ( $this->user_id ) {
					$columns = array(
						'title' => array( 'title', false ),
					);
				} else {
					$columns = array(
						'title' => array( 'title', false ),
					);
				}
				break;
		}

		/**
		 * Filter the sortable columns that are going to be used in the Course Analysis list table.
		 * Backwards compatible filter. Use sensei_analysis_course_columns_sortable instead.
		 *
		 * @hook sensei_analysis_course_{view}_columns_sortable
		 *
		 * @param {array}                             $columns The array of sortable columns to use in the table.
		 * @param {Sensei_Analysis_Course_List_Table} $this    The current instance of the class.
		 * @return {array} The array of sortable columns.
		 */
		$columns = apply_filters( 'sensei_analysis_course_' . $this->view . '_columns_sortable', $columns, $this );

		/**
		 * Filter the sortable columns that are going to be used in the Course Analysis list table.
		 *
		 * @hook sensei_analysis_course_columns_sortable
		 *
		 * @param {array}                             $columns The array of sortable columns to use in the table.
		 * @param {Sensei_Analysis_Course_List_Table} $this    The current instance of the class.
		 * @return {array} The array of sortable columns.
		 */
		$columns = apply_filters( 'sensei_analysis_course_columns_sortable', $columns, $this );

		return $columns;
	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 *
	 * @since  1.7.0
	 * @return void
	 */
	public function prepare_items() {
		// Handle orderby (needs work)
		$orderby = '';
		if ( ! empty( $_GET['orderby'] ) ) {
			if ( array_key_exists( esc_html( $_GET['orderby'] ), $this->get_sortable_columns() ) ) {
				$orderby = esc_html( $_GET['orderby'] );
			}
		}

		// Handle order
		$order = 'ASC';
		if ( ! empty( $_GET['order'] ) ) {
			$order = ( 'ASC' == strtoupper( $_GET['order'] ) ) ? 'ASC' : 'DESC';
		}

		// Handle search, need 4.1 version of WP to be able to restrict statuses to known post_ids
		$search = false;
		if ( ! empty( $_GET['s'] ) ) {
			$search = esc_html( $_GET['s'] );
		}
		$this->search = $search;

		$per_page = $this->get_items_per_page( 'sensei_comments_per_page' );
		/**
		 * Filter the number of items per page for the Course Analysis list table.
		 *
		 * @hook sensei_comments_per_page
		 *
		 * @param {int} $per_page The number of items per page.
		 * @param {string} $screen The current screen.
		 * @return {int} The number of items per page.
		 */
		$per_page = apply_filters( 'sensei_comments_per_page', $per_page, 'sensei_comments' );

		$paged  = $this->get_pagenum();
		$offset = 0;
		if ( ! empty( $paged ) ) {
			$offset = $per_page * ( $paged - 1 );
		}

		$args = array(
			'number'  => $per_page,
			'offset'  => $offset,
			'orderby' => $orderby,
			'order'   => $order,
		);

		if ( $this->search ) {
			$args['search'] = $this->search;
		}

		switch ( $this->view ) {
			case 'user':
				$this->items = $this->get_course_statuses( $args );
				break;

			case 'lesson':
			default:
				$this->items = $this->get_lessons( $args );
				break;
		}

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
	 * Generate a csv report with different parameters, pagination, columns and table elements
	 *
	 * @since  1.7.0
	 * @return data
	 */
	public function generate_report( $report ) {

		$data = array();

		$this->csv_output = true;

		// Handle orderby
		$orderby = '';
		if ( ! empty( $_GET['orderby'] ) ) {
			if ( array_key_exists( esc_html( $_GET['orderby'] ), $this->get_sortable_columns() ) ) {
				$orderby = esc_html( $_GET['orderby'] );
			}
		}

		// Handle order
		$order = 'ASC';
		if ( ! empty( $_GET['order'] ) ) {
			$order = ( 'ASC' == strtoupper( $_GET['order'] ) ) ? 'ASC' : 'DESC';
		}

		// Handle search
		$search = false;
		if ( ! empty( $_GET['s'] ) ) {
			$search = esc_html( $_GET['s'] );
		}
		$this->search = $search;

		$args = array(
			'offset'  => 0,
			'orderby' => $orderby,
			'order'   => $order,
		);
		if ( $this->search ) {
			$args['search'] = $this->search;
		}

		// Start the csv with the column headings
		$column_headers = array();
		$columns        = $this->get_columns();
		foreach ( $columns as $key => $title ) {
			$column_headers[] = $title;
		}
		$data[] = $column_headers;

		switch ( $this->view ) {
			case 'user':
				$args['number'] = '';
				$this->items    = $this->get_course_statuses( $args );

				break;
			case 'lesson':
			default:
				$args['number'] = -1;
				$this->items    = $this->get_lessons( $args );

				break;
		}

		// Process each row
		foreach ( $this->items as $item ) {
			$data[] = $this->get_row_data( $item );
		}

		return $data;
	}

	/**
	 * Generates the overall array for a single item in the display
	 *
	 * @since  1.7.0
	 * @param object $item The current item
	 */
	protected function get_row_data( $item ) {
		global $wpdb;

		switch ( $this->view ) {
			case 'user':
				$column_data = $this->get_user_view_row_data( $item );
				break;
			case 'lesson':
			default:
				if ( $this->user_id ) {
					$column_data = $this->get_user_lesson_view_row_data( $item );
				} else {
					$column_data = $this->get_lesson_overview_row_data( $item );
				}
				break;
		} // END switch

		return Sensei_Wp_Kses::wp_kses_array( $column_data );
	}

	/**
	 * Get row data for the "user" view (course students).
	 *
	 * @since 4.26.0
	 *
	 * @param object $item Reports_Item from get_course_statuses.
	 * @return array Column data.
	 */
	private function get_user_view_row_data( $item ) {
		$user_start_date = $item->started_at ?? '';
		$user_end_date   = $item->completed_at ?? '';
		$item_status     = $item->status;
		$course_percent  = $item->percent;
		$item_user_id    = $item->user_id;

		if ( 'complete' === $item_status ) {
			$status       = __( 'Completed', 'sensei-lms' );
			$status_class = 'graded';
		} else {
			$status        = __( 'In Progress', 'sensei-lms' );
			$status_class  = 'in-progress';
			$user_end_date = '';
		}

		// User data.
		$user_name  = Sensei_Learner::get_full_name( $item_user_id );
		$user       = get_user_by( 'id', $item_user_id );
		$user_email = $user ? $user->user_email : '';

		if ( ! $this->csv_output ) {
			$url = add_query_arg(
				array(
					'page'      => $this->page_slug,
					'user_id'   => $item_user_id,
					'course_id' => $this->course_id,
				),
				admin_url( 'admin.php' )
			);

			$user_name = '<strong><a class="row-title" href="' . esc_url( $url ) . '">' . esc_html( $user_name ) . '</a></strong>';
			$status    = sprintf( '<span class="%s">%s</span>', esc_attr( $status_class ), esc_html( $status ) );
			if ( is_numeric( $course_percent ) ) {
				$course_percent .= '%';
			}
		}

		return apply_filters(
			'sensei_analysis_course_column_data',
			array(
				'title'       => $user_name,
				'email'       => $user_email,
				'started'     => $user_start_date,
				'completed'   => $user_end_date,
				'user_status' => $status,
				'percent'     => $course_percent,
			),
			$item,
			$this
		);
	}

	/**
	 * Get row data for user-lesson view (one user's lessons in a course).
	 *
	 * @since 4.26.0
	 *
	 * @param object $item WP_Post lesson.
	 * @return array Column data.
	 */
	private function get_user_lesson_view_row_data( $item ) {
		$status          = __( 'Not started', 'sensei-lms' );
		$user_start_date = $user_end_date = $status_class = $grade = '';

		$lesson_args = array(
			'post_id' => $item->ID,
			'user_id' => $this->user_id,
			'type'    => 'sensei_lesson_status',
			'status'  => 'any',
		);
		/**
		 * Filter the lesson status arguments for the Course Analysis list table.
		 *
		 * @hook sensei_analysis_course_user_lesson
		 *
		 * @param {array}  $lesson_args The lesson status arguments.
		 * @param {object} $item The current item.
		 * @param {int}    $user_id The user ID.
		 * @return {array} The lesson status arguments.
		 */
		$lesson_args  = apply_filters( 'sensei_analysis_course_user_lesson', $lesson_args, $item, $this->user_id );
		$reports_item = $this->reports_listing_service->get_user_lesson_progress( $lesson_args );

		if ( null !== $reports_item ) {
			$user_start_date = $reports_item->started_at ?? '';
			$user_end_date   = $reports_item->completed_at ?? '';
			$item_status     = $reports_item->status;
			$item_grade      = $reports_item->grade;

			if ( 'complete' === $item_status ) {
				$status       = __( 'Completed', 'sensei-lms' );
				$status_class = 'graded';
				$grade        = __( 'No Grade', 'sensei-lms' );
			} elseif ( 'graded' === $item_status ) {
				$status       = __( 'Graded', 'sensei-lms' );
				$status_class = 'graded';
				$grade        = $item_grade;
			} elseif ( 'passed' === $item_status ) {
				$status       = __( 'Passed', 'sensei-lms' );
				$status_class = 'graded';
				$grade        = $item_grade;
			} elseif ( 'failed' === $item_status ) {
				$status       = __( 'Failed', 'sensei-lms' );
				$status_class = 'failed';
				$grade        = $item_grade;
			} elseif ( 'ungraded' === $item_status ) {
				$status       = __( 'Ungraded', 'sensei-lms' );
				$status_class = 'ungraded';
			} elseif ( 'in-progress' === $item_status ) {
				$status        = __( 'In Progress', 'sensei-lms' );
				$status_class  = 'in-progress';
				$user_end_date = '';
			}
		}

		// Output users data
		if ( $this->csv_output ) {
			$lesson_title = apply_filters( 'the_title', $item->post_title, $item->ID );
		} else {
			$url          = add_query_arg(
				array(
					'page'      => $this->page_slug,
					'lesson_id' => $item->ID,
				)
			);
			$lesson_title = '<strong><a class="row-title" href="' . esc_url( $url ) . '">' . apply_filters( 'the_title', $item->post_title, $item->ID ) . '</a></strong>';

			$status = sprintf( '<span class="%s">%s</span>', esc_attr( $status_class ), esc_html( $status ) );
			if ( is_numeric( $grade ) ) {
				$grade .= '%';
			}
		}

		return apply_filters(
			'sensei_analysis_course_column_data',
			array(
				'title'       => $lesson_title,
				'started'     => $user_start_date,
				'completed'   => $user_end_date,
				'user_status' => $status,
				'grade'       => $grade,
			),
			$item,
			$this
		);
	}

	/**
	 * Get row data for lesson overview (aggregates, no specific user).
	 *
	 * @since 4.26.0
	 *
	 * @param object $item WP_Post lesson.
	 * @return array Column data.
	 */
	private function get_lesson_overview_row_data( $item ) {
		$lesson_args = array(
			'post_id' => $item->ID,
			'type'    => 'sensei_lesson_status',
			'status'  => 'any',
		);
		/**
		 * Filter the lesson learners activity arguments for the Course Analysis list table.
		 *
		 * @hook sensei_analysis_lesson_learners
		 *
		 * @param {array}  $lesson_args The lesson learners activity arguments.
		 * @param {object} $item The current item.
		 * @return {array} The lesson learners activity arguments.
		 */
		$lesson_students = $this->reports_listing_service->get_lesson_student_count(
			apply_filters( 'sensei_analysis_lesson_learners', $lesson_args, $item )
		);

		$completion_args = array(
			'post_id' => $item->ID,
			'type'    => 'sensei_lesson_status',
			'status'  => Reports_Item::COMPLETED_STATUSES,
			'count'   => true,
		);
		/**
		 * Filter the lesson completions activity arguments for the Course Analysis list table.
		 *
		 * @hook sensei_analysis_lesson_completions
		 *
		 * @param {array}  $completion_args The lesson completions activity arguments.
		 * @param {object} $item The current item.
		 * @return {array} The lesson completions activity arguments.
		 */
		$lesson_completions = $this->reports_listing_service->get_lesson_completion_count(
			apply_filters( 'sensei_analysis_lesson_completions', $completion_args, $item )
		);

		$lesson_average_grade = __( 'N/A', 'sensei-lms' );
		if ( false !== Sensei_Lesson::lesson_quiz_has_questions( $item->ID ) ) {
			$grade_args = array(
				'post_id'  => $item->ID,
				'type'     => 'sensei_lesson_status',
				'status'   => array(
					Quiz_Progress_Interface::STATUS_GRADED,
					Quiz_Progress_Interface::STATUS_PASSED,
					Quiz_Progress_Interface::STATUS_FAILED,
				),
				'meta_key' => 'grade', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for grade aggregation.
			);
			/**
			 * Filter the lesson grades activity arguments for the Course Analysis list table.
			 *
			 * @hook sensei_analysis_lesson_grades
			 *
			 * @param {array}  $grade_args The lesson grades activity arguments.
			 * @param {object} $item The current item.
			 * @return {array} The lesson grades activity arguments.
			 */
			$avg = $this->reports_listing_service->get_lesson_average_grade(
				apply_filters( 'sensei_analysis_lesson_grades', $grade_args, $item )
			);
			if ( null !== $avg ) {
				$lesson_average_grade = $avg;
			}
		}

		// Output lesson data
		if ( $this->csv_output ) {
			$lesson_title = apply_filters( 'the_title', $item->post_title, $item->ID );
		} else {
			$url          = add_query_arg(
				array(
					'page'      => $this->page_slug,
					'lesson_id' => $item->ID,
				),
				admin_url( 'admin.php' )
			);
			$lesson_title = '<strong><a class="row-title" href="' . esc_url( $url ) . '">' . apply_filters( 'the_title', $item->post_title, $item->ID ) . '</a></strong>';

			if ( is_numeric( $lesson_average_grade ) ) {
				$lesson_average_grade .= '%';
			}
		}

		return apply_filters(
			'sensei_analysis_course_column_data',
			array(
				'title'         => $lesson_title,
				'num_learners'  => $lesson_students,
				'completions'   => $lesson_completions,
				'average_grade' => $lesson_average_grade,
			),
			$item,
			$this
		);
	}

	/**
	 * Return array of course statuses
	 *
	 * @since  1.7.0
	 * @return array statuses
	 */
	private function get_course_statuses( $args ) {

		$activity_args = array(
			'post_id' => $this->course_id,
			'type'    => 'sensei_course_status',
			'number'  => $args['number'],
			'offset'  => $args['offset'],
			'orderby' => $args['orderby'],
			'order'   => $args['order'],
			'status'  => 'any',
		);
		$activity_args = $this->add_filter_by_start_date( $activity_args );

		// Searching users on statuses requires sub-selecting the statuses by user_ids.
		if ( $this->search ) {
			$user_args = array(
				'search' => '*' . $this->search . '*',
				'fields' => 'ID',
			);
			/**
			 * Filter the user arguments for the Course Analysis list table.
			 *
			 * @hook sensei_analysis_course_search_users
			 *
			 * @param {array} $user_args The user arguments.
			 * @return {array} The user arguments.
			 */
			$user_args = apply_filters( 'sensei_analysis_course_search_users', $user_args );
			if ( ! empty( $user_args ) ) {
				$learners_search          = new WP_User_Query( $user_args );
				$activity_args['user_id'] = (array) $learners_search->get_results();
			}
		}

		/**
		 * Filter the course activity arguments for the Course Analysis list table.
		 *
		 * @hook sensei_analysis_course_filter_statuses
		 *
		 * @param {array} $activity_args The course statuses arguments.
		 * @return {array} The course statuses arguments.
		 */
		$activity_args = apply_filters( 'sensei_analysis_course_filter_statuses', $activity_args );

		$result            = $this->reports_listing_service->get_course_students( $activity_args );
		$this->total_items = $result['total_count'];

		return $result['items'];
	}

	/**
	 * Return array of Courses' lessons
	 *
	 * @since  1.7.0
	 * @return array statuses
	 */
	private function get_lessons( $args ) {

		$lessons_args = array(
			'post_type'        => 'lesson',
			'posts_per_page'   => $args['number'],
			'offset'           => $args['offset'],
			'order'            => $args['order'],
			'orderby'          => $args['orderby'],
			'meta_query'       => array(
				array(
					'key'   => '_lesson_course',
					'value' => intval( $this->course_id ),
				),
			),
			'post_status'      => array( 'publish', 'private' ),
			'suppress_filters' => 0,
		);

		if ( $this->search ) {
			$lessons_args['s'] = $this->search;
		}

		// Using WP_Query as get_posts() doesn't support 'found_posts'
		/**
		 * Filter the lessons arguments for the Course Analysis list table.
		 *
		 * @hook sensei_analysis_course_filter_lessons
		 *
		 * @param {array} $lessons_args The lessons arguments.
		 * @return {array} The lessons arguments.
		 */
		$lessons_query     = new WP_Query( apply_filters( 'sensei_analysis_course_filter_lessons', $lessons_args ) );
		$this->total_items = $lessons_query->found_posts;

		return $lessons_query->posts;
	}

	/**
	 * Sets output when no items are found
	 * Overloads the parent method
	 *
	 * @since  1.2.0
	 * @return void
	 */
	public function no_items() {
		switch ( $this->view ) {
			case 'user':
				$text = __( 'No students found.', 'sensei-lms' );
				break;

			case 'lesson':
			default:
				$text = __( 'No lessons found.', 'sensei-lms' );
				break;
		}
		/**
		 * Filter the text to display when no items are found in the Course Analysis list table.
		 *
		 * @hook sensei_analysis_course_no_items_text
		 *
		 * @param {string} $text The text to display.
		 * @return {string} Filtered text.
		 */
		echo wp_kses_post( apply_filters( 'sensei_analysis_course_no_items_text', $text ) );
	}

	/**
	 * Output for table heading
	 *
	 * @since  1.2.0
	 * @return void
	 */
	public function data_table_header() {
		if ( 'user' === $this->view ) {
			$this->output_top_filters();
		}
	}

	/**
	 * Return submenu for course reports.
	 */
	public function get_views() {
		if ( $this->user_id ) {
			$learners_text = __( 'Other Students taking this Course', 'sensei-lms' );
		} else {
			$learners_text = __( 'Students taking this Course', 'sensei-lms' );
		}
		$lessons_text = __( 'Lessons in this Course', 'sensei-lms' );

		$url_args     = array(
			'page'      => $this->page_slug,
			'course_id' => $this->course_id,
		);
		$learners_url = add_query_arg( array_merge( $url_args, array( 'view' => 'user' ) ), admin_url( 'admin.php' ) );
		$lessons_url  = add_query_arg( array_merge( $url_args, array( 'view' => 'lesson' ) ), admin_url( 'admin.php' ) );

		$learners_class = $lessons_class = '';

		$menu = array();
		switch ( $this->view ) {
			case 'user':
				$learners_class = 'current';
				break;

			case 'lesson':
			default:
				$lessons_class = 'current';
				break;
		}
		$menu['lesson'] = sprintf( '<a href="%s" class="%s">%s</a>', esc_url( $lessons_url ), esc_attr( $lessons_class ), esc_html( $lessons_text ) );
		$menu['user']   = sprintf( '<a href="%s" class="%s">%s</a>', esc_url( $learners_url ), esc_attr( $learners_class ), esc_html( $learners_text ) );

		/**
		 * Filter the sub menu for the Course Analysis list table.
		 *
		 * @hook sensei_analysis_course_sub_menu
		 *
		 * @param {array} $menu The sub menu.
		 * @return {array} The filtered sub menu.
		 */
		return apply_filters( 'sensei_analysis_course_sub_menu', $menu );
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
	 */
	public function extra_tablenav( $which ) {
		?>
		<div class="alignleft actions">
		<?php
		parent::extra_tablenav( $which );
		?>
		</div>
		<?php
	}

	/**
	 * Output search form for table.
	 */
	public function table_search_form() {
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		/**
		 * Filter the search button text for the Course Analysis list table.
		 *
		 * @hook sensei_list_table_search_button_text
		 *
		 * @param {string} $text The search button text.
		 * @return {string} The filtered search button text.
		 */
		$this->search_box( apply_filters( 'sensei_list_table_search_button_text', __( 'Search Users', 'sensei-lms' ) ), 'search_id' );
	}

	/**
	 * Output top filter form.
	 */
	private function output_top_filters() {
		?>
			<label for="sensei-start-date-filter">
				<?php esc_html_e( 'Date Started', 'sensei-lms' ); ?>:
			</label>

			<input
				class="sensei-date-picker"
				id="sensei-start-date-filter"
				name="start_date"
				type="text"
				autocomplete="off"
				placeholder="<?php echo esc_attr( __( 'Start Date', 'sensei-lms' ) ); ?>"
				value="<?php echo esc_attr( $this->get_start_date_filter_value() ); ?>"
			/>

			<input
				class="sensei-date-picker"
				id="sensei-end-date-filter"
				name="end_date"
				type="text"
				autocomplete="off"
				placeholder="<?php echo esc_attr( __( 'End Date', 'sensei-lms' ) ); ?>"
				value="<?php echo esc_attr( $this->get_end_date_filter_value() ); ?>"
			/>
		<?php
		submit_button( __( 'Filter', 'sensei-lms' ), '', '', false );
	}

	/**
	 * Output for table footer
	 *
	 * @since  1.2.0
	 * @return void
	 */
	public function data_table_footer() {
		if ( ! $this->total_items ) {
			return;
		}

		$course = get_post( $this->course_id );
		$report = sanitize_title( $course->post_title ) . '-' . $this->view . 's-overview';

		if ( $this->user_id ) {
			$user_name = Sensei_Learner::get_full_name( $this->user_id );
			$report    = sanitize_title( $user_name ) . '-' . $report;
		}

		$url_args = array(
			'page'                   => $this->page_slug,
			'course_id'              => $this->course_id,
			'view'                   => $this->view,
			'sensei_report_download' => $report,
			'start_date'             => $this->get_start_date_filter_value(),
			'end_date'               => $this->get_end_date_filter_value(),
			's'                      => $this->get_search_value(),
		);

		if ( $this->user_id ) {
			$url_args['user_id'] = $this->user_id;
		}

		$url = add_query_arg( $url_args, admin_url( 'admin.php' ) );

		echo '<a class="button button-primary" href="' . esc_url( wp_nonce_url( $url, 'sensei_csv_download', '_sdl_nonce' ) ) . '">' . esc_html__( 'Export all rows (CSV)', 'sensei-lms' ) . '</a>';
	}

	/**
	 * The text for the search button
	 *
	 * @since  1.7.0
	 * @return string $text
	 */
	public function search_button( $text = '' ) {
		switch ( $this->view ) {
			case 'user':
				$text = __( 'Search Students', 'sensei-lms' );
				break;

			case 'lesson':
			default:
				$text = __( 'Search Lessons', 'sensei-lms' );
				break;
		}

		return $text;
	}

	/**
	 * Filter users by start date
	 *
	 * @param array $args The query arguments.
	 * @return array The query arguments with added filter by start date.
	 */
	private function add_filter_by_start_date( array $args ): array {

		$date_from = $this->get_start_date_and_time();
		$date_to   = $this->get_end_date_and_time();

		if ( ! $date_from && ! $date_to ) {
			return $args;
		}

		$meta_query_conditions = array();

		if ( $date_from ) {
			$meta_query_conditions[] = array(
				'key'     => 'start',
				'value'   => $date_from,
				'compare' => '>=',
				'type'    => 'DATE',
			);
		}

		if ( $date_to ) {
			$meta_query_conditions[] = array(
				'key'     => 'start',
				'value'   => $date_to,
				'compare' => '<=',
				'type'    => 'DATE',
			);
		}

		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'relation' => 'AND',
			$meta_query_conditions,
		);

		return $args;
	}

	/**
	 * Get the search value.
	 *
	 * @return string search param value.
	 */
	private function get_search_value(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for filtering.
		return isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	}
}

/**
 * Class WooThemes_Sensei_Analysis_Course_List_Table
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 * @ignore
 */
class WooThemes_Sensei_Analysis_Course_List_Table extends Sensei_Analysis_Course_List_Table {}
