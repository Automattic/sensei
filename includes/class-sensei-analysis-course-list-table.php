<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Admin Analysis Course Data Table in Sensei.
 *
 * @package Analytics
 * @author Automattic
 * @since 1.2.0
 */
class Sensei_Analysis_Course_List_Table extends WooThemes_Sensei_List_Table {
	public $user_id;
	public $course_id;
	public $total_lessons;
	public $user_ids;
	public $view = 'lesson';
	public $page_slug = 'sensei_analysis';

	/**
	 * Constructor
	 * @since  1.2.0
	 */
	public function __construct ( $course_id = 0, $user_id = 0 ) {
		$this->course_id = intval( $course_id );
		$this->user_id = intval( $user_id );

		if( isset( $_GET['view'] ) && in_array( $_GET['view'], array( 'user', 'lesson' ) ) ) {
			$this->view = $_GET['view'];
		}

		// Viewing a single Learner always sets the view to Lessons
		if( $this->user_id ) {
			$this->view = 'lesson';
		}

		// Load Parent token into constructor
		parent::__construct( 'analysis_course' );

		// Actions
		add_action( 'sensei_before_list_table', array( $this, 'data_table_header' ) );
		add_action( 'sensei_after_list_table', array( $this, 'data_table_footer' ) );

		add_filter( 'sensei_list_table_search_button_text', array( $this, 'search_button' ) );

	} // End __construct()

	/**
	 * Define the columns that are going to be used in the table
	 * @since  1.7.0
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_columns() {

		switch( $this->view ) {
			case 'user' :
				$columns = array(
					'title' => __( 'Learner', 'woothemes-sensei' ),
					'started' => __( 'Date Started', 'woothemes-sensei' ),
					'completed' => __( 'Date Completed', 'woothemes-sensei' ),
					'user_status' => __( 'Status', 'woothemes-sensei' ),
					'percent' => __( 'Percent Complete', 'woothemes-sensei' ),
				);
				break;

			case 'lesson' :
			default:
				if ( $this->user_id ) {

					$columns = array(
						'title' => __( 'Lesson', 'woothemes-sensei' ),
						'started' => __( 'Date Started', 'woothemes-sensei' ),
						'completed' => __( 'Date Completed', 'woothemes-sensei' ),
						'user_status' => __( 'Status', 'woothemes-sensei' ),
						'grade' => __( 'Grade', 'woothemes-sensei' ),
					);

				} else {

					$columns = array(
						'title' => __( 'Lesson', 'woothemes-sensei' ),
						'num_learners' => __( 'Learners', 'woothemes-sensei' ),
						'completions' => __( 'Completed', 'woothemes-sensei' ),
						'average_grade' => __( 'Average Grade', 'woothemes-sensei' ),
					);

				}
				break;
		}
		// Backwards compatible
		$columns = apply_filters( 'sensei_analysis_course_' . $this->view . '_columns', $columns, $this );
		// Moving forward, single filter with args
		$columns = apply_filters( 'sensei_analysis_course_columns', $columns, $this );
		return $columns;
	}

	/**
	 * Define the columns that are going to be used in the table
	 * @since  1.7.0
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_sortable_columns() {

		switch( $this->view ) {
			case 'user' :
				$columns = array(
					'title' => array( 'title', false ),
					'started' => array( 'started', false ),
					'completed' => array( 'completed', false ),
					'user_status' => array( 'user_status', false ),
					'percent' => array( 'percent', false )
				);
				break;

			case 'lesson' :
			default:
				if ( $this->user_id ) {

					$columns = array(
						'title' => array( 'title', false ),
						'started' => array( 'started', false ),
						'completed' => array( 'completed', false ),
						'user_status' => array( 'user_status', false ),
						'grade' => array( 'grade', false ),
					);

				} else {

					$columns = array(
						'title' => array( 'title', false ),
						'num_learners' => array( 'num_learners', false ),
						'completions' => array( 'completions', false ),
						'average_grade' => array( 'average_grade', false )
					);

				}
				break;
		}
		// Backwards compatible
		$columns = apply_filters( 'sensei_analysis_course_' . $this->view . '_columns_sortable', $columns, $this );
		// Moving forward, single filter with args
		$columns = apply_filters( 'sensei_analysis_course_columns_sortable', $columns, $this );
		return $columns;
	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 * @since  1.7.0
	 * @return void
	 */
	public function prepare_items() {
		global $per_page;

		// Handle orderby (needs work)
		$orderby = '';
		if ( !empty( $_GET['orderby'] ) ) {
			if ( array_key_exists( esc_html( $_GET['orderby'] ), $this->get_sortable_columns() ) ) {
				$orderby = esc_html( $_GET['orderby'] );
			} // End If Statement
		}

		// Handle order
		$order = 'ASC';
		if ( !empty( $_GET['order'] ) ) {
			$order = ( 'ASC' == strtoupper($_GET['order']) ) ? 'ASC' : 'DESC';
		}

		// Handle search, need 4.1 version of WP to be able to restrict statuses to known post_ids
		$search = false;
		if ( !empty( $_GET['s'] ) ) {
			$search = esc_html( $_GET['s'] );
		} // End If Statement
		$this->search = $search;

		$per_page = $this->get_items_per_page( 'sensei_comments_per_page' );
		$per_page = apply_filters( 'sensei_comments_per_page', $per_page, 'sensei_comments' );

		$paged = $this->get_pagenum();
		$offset = 0;
		if ( !empty($paged) ) {
			$offset = $per_page * ( $paged - 1 );
		} // End If Statement

		$args = array(
			'number' => $per_page,
			'offset' => $offset,
			'orderby' => $orderby,
			'order' => $order,
		);
		if ( $this->search ) {
			$args['search'] = $this->search;
		} // End If Statement

		switch( $this->view ) {
			case 'user' :
				$this->items = $this->get_course_statuses( $args );
				break;

			case 'lesson':
			default:
				$this->items = $this->get_lessons( $args );
				break;
		}

		$total_items = $this->total_items;
		$total_pages = ceil( $total_items / $per_page );
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'total_pages' => $total_pages,
			'per_page' => $per_page
		) );
	}

	/**
	 * Generate a csv report with different parameters, pagination, columns and table elements
	 * @since  1.7.0
	 * @return data
	 */
	public function generate_report( $report ) {

		$data = array();

		$this->csv_output = true;

		// Handle orderby
		$orderby = '';
		if ( !empty( $_GET['orderby'] ) ) {
			if ( array_key_exists( esc_html( $_GET['orderby'] ), $this->get_sortable_columns() ) ) {
				$orderby = esc_html( $_GET['orderby'] );
			} // End If Statement
		}

		// Handle order
		$order = 'ASC';
		if ( !empty( $_GET['order'] ) ) {
			$order = ( 'ASC' == strtoupper($_GET['order']) ) ? 'ASC' : 'DESC';
		}

		// Handle search
		$search = false;
		if ( !empty( $_GET['s'] ) ) {
			$search = esc_html( $_GET['s'] );
		} // End If Statement
		$this->search = $search;

		$args = array(
			'orderby' => $orderby,
			'order' => $order,
		);
		if ( $this->search ) {
			$args['search'] = $this->search;
		} // End If Statement

		// Start the csv with the column headings
		$column_headers = array();
		$columns = $this->get_columns();
		foreach( $columns AS $key => $title ) {
			$column_headers[] = $title;
		}
		$data[] = $column_headers;

		switch( $this->view ) {
			case 'user' :
				$this->items = $this->get_course_statuses( $args );
				break;

			case 'lesson':
			default:
				$this->items = $this->get_lessons( $args );
				break;
		}

		// Process each row
		foreach( $this->items AS $item) {
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

		switch( $this->view ) {
			case 'user' :
				$user_start_date = get_comment_meta( $item->comment_ID, 'start', true );
				$user_end_date = $item->comment_date;

				if( 'complete' == $item->comment_approved ) {

					$status =  __( 'Completed', 'woothemes-sensei' );
					$status_class = 'graded';

				} else {

					$status =  __( 'In Progress', 'woothemes-sensei' );
					$status_class = 'in-progress';
					$user_end_date = '';

				}
				$course_percent = get_comment_meta( $item->comment_ID, 'percent', true );

				// Output users data
				$user_name = Sensei_Learner::get_full_name( $item->user_id );

				if ( !$this->csv_output ) {

					$url = add_query_arg( array( 'page' => $this->page_slug, 'user_id' => $item->user_id, 'course_id' => $this->course_id ), admin_url( 'admin.php' ) );

					$user_name = '<strong><a class="row-title" href="' . esc_url( $url ) . '">' . $user_name . '</a></strong>';
					$status = sprintf( '<span class="%s">%s</span>', $status_class, $status );
					if ( is_numeric($course_percent) ) {

						$course_percent .= '%';

					}

				} // End If Statement

				$column_data = apply_filters( 'sensei_analysis_course_column_data', array( 'title' => $user_name,
												'started' => $user_start_date,
												'completed' => $user_end_date,
												'user_status' => $status,
												'percent' => $course_percent,
											), $item, $this );
				break;

			case 'lesson':
			default:
				// Displaying lessons for this Course for a specific User
				if ( $this->user_id ) {
					$status = __( 'Not started', 'woothemes-sensei' );
					$user_start_date = $user_end_date = $status_class = $grade = '';

					$lesson_args = array(
							'post_id' => $item->ID,
							'user_id' => $this->user_id,
							'type' => 'sensei_lesson_status',
							'status' => 'any',
						);
					$lesson_status = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_course_user_lesson', $lesson_args, $item, $this->user_id ), true );

					if ( !empty($lesson_status) ) {
						$user_start_date = get_comment_meta( $lesson_status->comment_ID, 'start', true );
						$user_end_date = $lesson_status->comment_date;

						if( 'complete' == $lesson_status->comment_approved ) {
							$status = __( 'Completed', 'woothemes-sensei' );
							$status_class = 'graded';

							$grade = __( 'No Grade', 'woothemes-sensei' );
						}
						elseif( 'graded' == $lesson_status->comment_approved ) {
							$status =  __( 'Graded', 'woothemes-sensei' );
							$status_class = 'graded';

							$grade = get_comment_meta( $lesson_status->comment_ID, 'grade', true);
						}
						elseif( 'passed' == $lesson_status->comment_approved ) {
							$status =  __( 'Passed', 'woothemes-sensei' );
							$status_class = 'graded';

							$grade = get_comment_meta( $lesson_status->comment_ID, 'grade', true);
						}
						elseif( 'failed' == $lesson_status->comment_approved ) {
							$status =  __( 'Failed', 'woothemes-sensei' );
							$status_class = 'failed';

							$grade = get_comment_meta( $lesson_status->comment_ID, 'grade', true);
						}
						elseif( 'ungraded' == $lesson_status->comment_approved ) {
							$status =  __( 'Ungraded', 'woothemes-sensei' );
							$status_class = 'ungraded';

						}
						elseif( 'in-progress' == $lesson_status->comment_approved ) {
							$status =  __( 'In Progress', 'woothemes-sensei' );
							$user_end_date = '';
						}
					} // END lesson_status

					// Output users data
					if ( $this->csv_output ) {
						$lesson_title = apply_filters( 'the_title', $item->post_title, $item->ID );
					}
					else {
						$url = add_query_arg( array( 'page' => $this->page_slug, 'lesson_id' => $item->ID ), admin_url( 'admin.php' ) );
						$lesson_title = '<strong><a class="row-title" href="' . esc_url( $url ) . '">' . apply_filters( 'the_title', $item->post_title, $item->ID ) . '</a></strong>';

						$status = sprintf( '<span class="%s">%s</span>', $status_class, $status );
						if ( is_numeric($grade) ) {
							$grade .= '%';
						}
					} // End If Statement
					$column_data = apply_filters( 'sensei_analysis_course_column_data', array( 'title' => $lesson_title,
													'started' => $user_start_date,
													'completed' => $user_end_date,
													'user_status' => $status,
													'grade' => $grade,
												), $item, $this );
				}
				// Display lessons for this Course regardless of users
				else {
					// Get Learners (i.e. those who have started)
					$lesson_args = array(
							'post_id' => $item->ID,
							'type' => 'sensei_lesson_status',
							'status' => 'any',
						);
					$lesson_students = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_lesson_learners', $lesson_args, $item ) );

					// Get Course Completions
					$lesson_args = array(
							'post_id' => $item->ID,
							'type' => 'sensei_lesson_status',
							'status' => array( 'complete', 'graded', 'passed', 'failed' ),
							'count' => true,
						);
					$lesson_completions = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_lesson_completions', $lesson_args, $item ) );

					$lesson_average_grade = __('n/a', 'woothemes-sensei');
					if ( false != get_post_meta($item->ID, '_quiz_has_questions', true) ) {
						// Get Percent Complete
						$grade_args = array(
								'post_id' => $item->ID,
								'type' => 'sensei_lesson_status',
								'status' => array( 'graded', 'passed', 'failed' ),
								'meta_key' => 'grade',
							);
						add_filter( 'comments_clauses', array( 'WooThemes_Sensei_Utils', 'comment_total_sum_meta_value_filter' ) );
						$lesson_grades = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_lesson_grades', $grade_args, $item ), true );
						remove_filter( 'comments_clauses', array( 'WooThemes_Sensei_Utils', 'comment_total_sum_meta_value_filter' ) );

						$grade_count = !empty( $lesson_grades->total ) ? $lesson_grades->total : 1;
						$grade_total = !empty( $lesson_grades->meta_sum ) ? doubleval( $lesson_grades->meta_sum ) : 0;
						$lesson_average_grade = Sensei_Utils::quotient_as_absolute_rounded_number( $grade_total, $grade_count, 2 );
					}
					// Output lesson data
					if ( $this->csv_output ) {
						$lesson_title = apply_filters( 'the_title', $item->post_title, $item->ID );
					}
					else {
						$url = add_query_arg( array( 'page' => $this->page_slug, 'lesson_id' => $item->ID ), admin_url( 'admin.php' ) );
						$lesson_title = '<strong><a class="row-title" href="' . esc_url( $url ) . '">' . apply_filters( 'the_title', $item->post_title, $item->ID ) . '</a></strong>';

						if ( is_numeric( $lesson_average_grade ) ) {
							$lesson_average_grade .= '%';
						}
					} // End If Statement
					$column_data = apply_filters( 'sensei_analysis_course_column_data', array( 'title' => $lesson_title,
													'num_learners' => $lesson_students,
													'completions' => $lesson_completions,
													'average_grade' => $lesson_average_grade,
												), $item, $this );
				} // END if
				break;
		} // END switch

		return $column_data;
	}

	/**
	 * Return array of course statuses
	 * @since  1.7.0
	 * @return array statuses
	 */
	private function get_course_statuses( $args ) {

		$activity_args = array(
				'post_id' => $this->course_id,
				'type' => 'sensei_course_status',
				'number' => $args['number'],
				'offset' => $args['offset'],
				'orderby' => $args['orderby'],
				'order' => $args['order'],
				'status' => 'any',
			);

		// Searching users on statuses requires sub-selecting the statuses by user_ids
		if ( $this->search ) {
			$user_args = array(
				'search' => '*' . $this->search . '*',
				'fields' => 'ID',
			);
			// Filter for extending
			$user_args = apply_filters( 'sensei_analysis_course_search_users', $user_args );
			if ( !empty( $user_args ) ) {
				$learners_search = new WP_User_Query( $user_args );
				// Store for reuse on counts
				$activity_args['user_id'] = (array) $learners_search->get_results();
			}
		} // End If Statement

		$activity_args = apply_filters( 'sensei_analysis_course_filter_statuses', $activity_args );

		// WP_Comment_Query doesn't support SQL_CALC_FOUND_ROWS, so instead do this twice
		$this->total_items = Sensei_Utils::sensei_check_for_activity( array_merge( $activity_args, array('count' => true, 'offset' => 0, 'number' => 0) ) );

		// Ensure we change our range to fit (in case a search threw off the pagination) - Should this be added to all views?
		if ( $this->total_items < $activity_args['offset'] ) {
			$new_paged = floor( $this->total_items / $activity_args['number'] );
			$activity_args['offset'] = $new_paged * $activity_args['number'];
		}
		$statuses = Sensei_Utils::sensei_check_for_activity( $activity_args, true );
		// Need to always return an array, even with only 1 item
		if ( !is_array($statuses) ) {
			$statuses = array( $statuses );
		}
		return $statuses;
	} // End get_course_statuses()

	/**
	 * Return array of Courses' lessons
	 * @since  1.7.0
	 * @return array statuses
	 */
	private function get_lessons( $args ) {

		$lessons_args = array( 'post_type'         => 'lesson',
							'posts_per_page'      => $args['number'],
							'offset'              => $args['offset'],
							'meta_key'            => '_order_' . $this->course_id,
							'order'               => $args['order'],
							'meta_query'          => array(
								array(
									'key' => '_lesson_course',
									'value' => intval( $this->course_id ),
								),
							),
							'post_status'         => array('publish', 'private'),
							'suppress_filters'    => 0
							);
		if ( $this->search ) {
			$lessons_args['s'] = $this->search;
		}
		if ( $this->csv_output ) {
			$lessons_args['posts_per_page'] = '-1';
		}

		// Using WP_Query as get_posts() doesn't support 'found_posts'
		$lessons_query = new WP_Query( apply_filters( 'sensei_analysis_course_filter_lessons', $lessons_args ) );
		$this->total_items = $lessons_query->found_posts;
		return $lessons_query->posts;
	} // End get_lessons()

	/**
	 * Sets output when no items are found
	 * Overloads the parent method
	 * @since  1.2.0
	 * @return void
	 */
	public function no_items() {
		switch( $this->view ) {
			case 'user' :
				$text = __( 'No learners found.', 'woothemes-sensei' );
				break;

			case 'lesson':
			default:
				$text = __( 'No lessons found.', 'woothemes-sensei' );
				break;
		}
		echo apply_filters( 'sensei_analysis_course_no_items_text', $text );
	} // End no_items()

	/**
	 * Output for table heading
	 * @since  1.2.0
	 * @return void
	 */
	public function data_table_header() {
		if ( $this->user_id ) {
			$learners_text = __( 'Other Learners taking this Course', 'woothemes-sensei' );
		}
		else {
			$learners_text = __( 'Learners taking this Course', 'woothemes-sensei' );
		}
		$lessons_text = __( 'Lessons in this Course', 'woothemes-sensei' );

		$url_args = array(
			'page' => $this->page_slug,
			'course_id' => $this->course_id,
		);
		$learners_url = esc_url( add_query_arg( array_merge( $url_args, array( 'view' => 'user' ) ), admin_url( 'admin.php' ) ) );
		$lessons_url = esc_url( add_query_arg( array_merge( $url_args, array( 'view' => 'lesson' ) ), admin_url( 'admin.php' ) ) );

		$learners_class = $lessons_class = '';

		$menu = array();
		switch( $this->view ) {
			case 'user' :
				$learners_class = 'current';
				break;

			case 'lesson':
			default:
				$lessons_class = 'current';
				break;
		}
		$menu['lesson'] = sprintf( '<a href="%s" class="%s">%s</a>', $lessons_url, $lessons_class, $lessons_text );
		$menu['user'] = sprintf( '<a href="%s" class="%s">%s</a>', $learners_url, $learners_class, $learners_text );

		$menu = apply_filters( 'sensei_analysis_course_sub_menu', $menu );
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
	 * @since  1.2.0
	 * @return void
	 */
	public function data_table_footer() {

		$course = get_post( $this->course_id );
		$report = sanitize_title( $course->post_title ) . '-' . $this->view . 's-overview';
		if ( $this->user_id ) {
            $user_name = Sensei_Learner::get_full_name( $this->user_id );
			$report = sanitize_title( $user_name  ) . '-' . $report;
		}

		$url_args = array( 'page' => $this->page_slug, 'course_id' => $this->course_id, 'view' => $this->view, 'sensei_report_download' => $report );
		if ( $this->user_id ) {
			$url_args['user_id'] = $this->user_id;
		}
		$url =  add_query_arg( $url_args, admin_url( 'admin.php' ) );
		echo '<a class="button button-primary" href="' . esc_url( wp_nonce_url( $url, 'sensei_csv_download-' . $report, '_sdl_nonce' ) ) . '">' . __( 'Export all rows (CSV)', 'woothemes-sensei' ) . '</a>';
	} // End data_table_footer()

	/**
	 * The text for the search button
	 * @since  1.7.0
	 * @return string $text
	 */
	public function search_button( $text = '' ) {
		switch( $this->view ) {
			case 'user':
				$text = __( 'Search Learners', 'woothemes-sensei' );
			break;

			case 'lesson':
			default:
				$text = __( 'Search Lessons', 'woothemes-sensei' );
			break;
		} // End Switch Statement

		return $text;
	}

} // End Class

/**
 * Class WooThemes_Sensei_Analysis_Course_List_Table
 * @ignore only for backward compatibility
 * @since 1.9.0
 * @ignore
 */
class WooThemes_Sensei_Analysis_Course_List_Table extends Sensei_Analysis_Course_List_Table {}
