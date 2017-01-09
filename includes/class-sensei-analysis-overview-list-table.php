<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Analysis Overview Data Table.
 *
 * @package Analytics
 * @author Automattic
 *
 * @since 1.2.0
 */
class Sensei_Analysis_Overview_List_Table extends WooThemes_Sensei_List_Table {
	public $type;
	public $page_slug = 'sensei_analysis';

	/**
	 * Constructor
	 * @since  1.2.0
	 * @return  void
	 */
	public function __construct ( $type = 'users' ) {
		$this->type = in_array( $type, array( 'courses', 'lessons', 'users' ) ) ? $type : 'users';

		// Load Parent token into constructor
		parent::__construct( 'analysis_overview' );

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

		switch( $this->type ) {
			case 'courses':
				$columns = array(
					'title' => __( 'Course', 'woothemes-sensei' ),
					'students' => __( 'Learners', 'woothemes-sensei' ),
					'lessons' => __( 'Lessons', 'woothemes-sensei' ),
					'completions' => __( 'Completed', 'woothemes-sensei' ),
					'average_percent' => __( 'Average Percentage', 'woothemes-sensei' ),
				);
				break;

			case 'lessons':
				$columns = array(
					'title' => __( 'Lesson', 'woothemes-sensei' ),
					'course' => __( 'Course', 'woothemes-sensei' ),
					'students' => __( 'Learners', 'woothemes-sensei' ),
					'completions' => __( 'Completed', 'woothemes-sensei' ),
					'average_grade' => __( 'Average Grade', 'woothemes-sensei' ),
				);
				break;

			case 'users':
			default:
				$columns = array(
					'title' => __( 'Learner', 'woothemes-sensei' ),
					'registered' => __( 'Date Registered', 'woothemes-sensei' ),
					'active_courses' => __( 'Active Courses', 'woothemes-sensei' ),
					'completed_courses' => __( 'Completed Courses', 'woothemes-sensei' ),
					'average_grade' => __( 'Average Grade', 'woothemes-sensei' ),
				);
				break;
		}
		// Backwards compatible filter name, moving forward should have single filter name
		$columns = apply_filters( 'sensei_analysis_overview_' . $this->type . '_columns', $columns, $this );
		$columns = apply_filters( 'sensei_analysis_overview_columns', $columns, $this );
		return $columns;
	}

	/**
	 * Define the columns that are going to be used in the table
	 * @since  1.7.0
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_sortable_columns() {

		switch( $this->type ) {
			case 'courses':
				$columns = array(
					'title' => array( 'title', false ),
					'students' => array( 'students', false ),
					'lessons' => array( 'lessons', false ),
					'completions' => array( 'completions', false ),
					'average_percent' => array( 'average_percent', false ),
				);
				break;

			case 'lessons':
				$columns = array(
					'title' => array( 'title', false ),
					'course' => array( 'course', false ),
					'students' => array( 'students', false ),
					'completions' => array( 'completions', false ),
					'average_grade' => array( 'average_grade', false ),
				);
				break;

			case 'users':
			default:
				$columns = array(
					'title' => array( 'user_login', false ),
					'registered' => array( 'registered', false ),
					'active_courses' => array( 'active_courses', false ),
					'completed_courses' => array( 'completed_courses', false ),
					'average_grade' => array( 'average_grade', false )
				);
				break;
		}
		// Backwards compatible filter name, moving forward should have single filter name
		$columns = apply_filters( 'sensei_analysis_overview_' . $this->type . '_columns_sortable', $columns, $this );
		$columns = apply_filters( 'sensei_analysis_overview_columns_sortable', $columns, $this );
		return $columns;
	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 * @since  1.7.0
	 * @return void
	 */
	public function prepare_items() {
		global $per_page;

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

        // Handle search
        if ( isset( $_GET['s'] ) && !empty( $_GET['s'] ) ) {
            $args['search'] = esc_html( $_GET['s'] );
        }

		switch ( $this->type ) {
			case 'courses':
				$this->items = $this->get_courses( $args );
				break;

			case 'lessons':
				$this->items = $this->get_lessons( $args );
				break;

			case 'users':
			default :
				$this->items = $this->get_learners( $args );
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

		$args = array(
			'orderby' => $orderby,
			'order' => $order,
		);


        // Handle search
        if ( isset( $_GET['s'] ) && !empty( $_GET['s'] ) ) {
            $args['search'] = esc_html( $_GET['s'] );
        }


		// Start the csv with the column headings
		$column_headers = array();
		$columns = $this->get_columns();
		foreach( $columns AS $key => $title ) {
			$column_headers[] = $title;
		}
		$data[] = $column_headers;

		switch ( $this->type ) {
			case 'courses':
				$this->items = $this->get_courses( $args );
				break;

			case 'lessons':
				$this->items = $this->get_lessons( $args );
				break;

			case 'users':
			default :
				$this->items = $this->get_learners( $args );
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
	 * @since  1.7.0
	 * @param object $item The current item
     * @return array $column_data;
	 */
	protected function get_row_data( $item ) {

		switch( $this->type ) {
			case 'courses' :
				// Get Learners (i.e. those who have started)
				$course_args = array( 
						'post_id' => $item->ID,
						'type' => 'sensei_course_status',
						'status' => 'any',
					);
				$course_students = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_course_learners', $course_args, $item ) );

				// Get Course Completions
				$course_args = array( 
						'post_id' => $item->ID,
						'type' => 'sensei_course_status',
						'status' => 'complete',
					);
				$course_completions = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_course_completions', $course_args, $item ) );

				// Course Lessons
				$course_lessons = Sensei()->lesson->lesson_count( array('publish', 'private'), $item->ID );

				// Get Percent Complete
				$grade_args = array( 
						'post_id' => $item->ID,
						'type' => 'sensei_course_status',
						'status' => 'any',
						'meta_key' => 'percent',
					);

				$percent_count = count( Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_course_percentage', $grade_args, $item ), true ) );
				$percent_total = Sensei_Grading::get_course_users_grades_sum( $item->ID );

                $course_average_percent = 0;
                if( $percent_count > 0 && $percent_total > 0 ){
                    $course_average_percent = Sensei_Utils::quotient_as_absolute_rounded_number( $percent_total, $percent_count, 2 );
                }


				// Output course data
				if ( $this->csv_output ) {
					$course_title = apply_filters( 'the_title', $item->post_title, $item->ID );
				}
				else {
					$url = add_query_arg( array( 'page' => $this->page_slug, 'course_id' => $item->ID ), admin_url( 'admin.php' ) );

					$course_title = '<strong><a class="row-title" href="' . esc_url( $url ) . '">' . apply_filters( 'the_title', $item->post_title, $item->ID ) . '</a></strong>';
					$course_average_percent .= '%';
				} // End If Statement
				$column_data = apply_filters( 'sensei_analysis_overview_column_data', array( 'title' => $course_title,
												'students' => $course_students,
												'lessons' => $course_lessons,
												'completions' => $course_completions,
												'average_percent' => $course_average_percent,
											), $item, $this );
				break;

			case 'lessons' :
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

				// Course 
				$course_id = get_post_meta( $item->ID, '_lesson_course', true );
				$course_title = $course_id ? get_the_title( $course_id ) : '';

				$lesson_average_grade = __('n/a', 'woothemes-sensei');
				if ( false != get_post_meta($item->ID, '_quiz_has_questions', true) ) {
					// Get Percent Complete
					$grade_args = array( 
							'post_id' => $item->ID,
							'type' => 'sensei_lesson_status',
							'status' => array( 'graded', 'passed', 'failed' ),
							'meta_key' => 'grade',
						);

					$grade_count = count( Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_lesson_grades', $grade_args, $item ), true ));
					$grade_total = Sensei_Grading::get_lessons_users_grades_sum( $item->ID );

                    $lesson_average_grade = 0;
                    if( $grade_total > 0 && $grade_count > 0 ){
                        $lesson_average_grade = Sensei_Utils::quotient_as_absolute_rounded_number( $grade_total, $grade_count, 2 );
                    }

                }
				// Output lesson data
				if ( $this->csv_output ) {
					$lesson_title = apply_filters( 'the_title', $item->post_title, $item->ID );
				}
				else {
					$url = add_query_arg( array( 'page' => $this->page_slug, 'lesson_id' => $item->ID ), admin_url( 'admin.php' ) );
					$lesson_title = '<strong><a class="row-title" href="' . esc_url( $url ) . '">' . apply_filters( 'the_title', $item->post_title, $item->ID ) . '</a></strong>';

					if ( $course_id ) {
						$url = add_query_arg( array( 'page' => $this->page_slug, 'course_id' => $course_id ), admin_url( 'admin.php' ) );
						$course_title = '<a href="' . esc_url( $url ) . '">' . $course_title . '</a>';
					}
					else {
						$course_title = __('n/a', 'woothemes-sensei');
					}
					if ( is_numeric( $lesson_average_grade ) ) {
						$lesson_average_grade .= '%';
					}
				} // End If Statement
				$column_data = apply_filters( 'sensei_analysis_overview_column_data', array( 'title' => $lesson_title,
												'course' => $course_title,
												'students' => $lesson_students,
												'completions' => $lesson_completions,
												'average_grade' => $lesson_average_grade,
											), $item, $this );
				break;

			case 'users' :
			default:
				// Get Started Courses
				$course_args = array( 
						'user_id' => $item->ID,
						'type' => 'sensei_course_status',
						'status' => 'any',
					);
				$user_courses_started = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_user_courses_started', $course_args, $item ) );

				// Get Completed Courses
				$course_args = array( 
						'user_id' => $item->ID,
						'type' => 'sensei_course_status',
						'status' => 'complete',
					);
				$user_courses_ended = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_user_courses_ended', $course_args, $item ) );

				// Get Quiz Grades
				$grade_args = array( 
						'user_id' => $item->ID,
						'type' => 'sensei_lesson_status',
						'status' => 'any',
						'meta_key' => 'grade',
					);

				$grade_count = count( Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_user_lesson_grades', $grade_args, $item ), true ));
				$grade_total = Sensei_Grading::get_user_graded_lessons_sum( $item->ID );

                $user_average_grade = 0;
                if( $grade_total > 0 && $grade_count > 0 ){
                    $user_average_grade = Sensei_Utils::quotient_as_absolute_rounded_number( $grade_total, $grade_count, 2 );
                }

				// Output the users data
				if ( $this->csv_output ) {
                    $user_name = Sensei_Learner::get_full_name( $item->ID );
                }
				else {
					$url = add_query_arg( array( 'page' => $this->page_slug, 'user_id' => $item->ID ), admin_url( 'admin.php' ) );
					$user_name = '<strong><a class="row-title" href="' . esc_url( $url ) . '">' . $item->display_name . '</a></strong>';
					$user_average_grade .= '%';
				} // End If Statement
				$column_data = apply_filters( 'sensei_analysis_overview_column_data', array( 'title' => $user_name,
												'registered' => $item->user_registered,
												'active_courses' => ( $user_courses_started - $user_courses_ended ),
												'completed_courses' => $user_courses_ended,
												'average_grade' => $user_average_grade,
											), $item, $this );
				break;
		} // end switch
		return $column_data;
	}

	/**
	 * Return array of course
	 * @since  1.7.0
	 * @return array courses
	 */
	private function get_courses( $args ) {
		$course_args = array(
			'post_type' => 'course',
			'post_status' => array('publish', 'private'),
			'posts_per_page' => $args['number'],
			'offset' => $args['offset'],
			'orderby' => $args['orderby'],
			'order' => $args['order'],
			'suppress_filters' => 0,
		);

		if ( $this->csv_output ) {
			$course_args['posts_per_page'] = '-1';
		}

		if( isset( $args['search'] ) ) {
			$course_args['s'] = $args['search'];
		}

		// Using WP_Query as get_posts() doesn't support 'found_posts'
		$courses_query = new WP_Query( apply_filters( 'sensei_analysis_overview_filter_courses', $course_args ) );
		$this->total_items = $courses_query->found_posts;
		return $courses_query->posts;

	} // End get_courses()

	/**
	 * Return array of lessons
	 * @since  1.7.0
	 * @return array lessons
	 */
	private function get_lessons( $args ) {
		$lessons_args = array(
			'post_type' => 'lesson',
			'post_status' => array('publish', 'private'),
			'posts_per_page' => $args['number'],
			'offset' => $args['offset'],
			'orderby' => $args['orderby'],
			'order' => $args['order'],
			'suppress_filters' => 0,
		);

		if ( $this->csv_output ) {
			$lessons_args['posts_per_page'] = '-1';
		}

		if( isset( $args['search'] ) ) {
			$lessons_args['s'] = $args['search'];
		}

		// Using WP_Query as get_posts() doesn't support 'found_posts'
		$lessons_query = new WP_Query( apply_filters( 'sensei_analysis_overview_filter_lessons', $lessons_args ) );
		$this->total_items = $lessons_query->found_posts;
		return $lessons_query->posts;
	} // End get_lessons()

	/**
	 * Return array of learners
	 * @since  1.7.0
	 * @return array learners
	 */
	private function get_learners( $args ) {

		if ( !empty($args['search']) ) {
			$args = array(
				'search' => '*' . trim( $args['search'], '*' ) . '*',
			);
		}

		// This stops the full meta data of each user being loaded
		$args['fields'] = array( 'ID', 'user_login', 'user_email', 'user_registered', 'display_name' );

        /**
         * Filter the WP_User_Query arguments
         * @since 1.6.0
         * @param $args
         */
        $args = apply_filters( 'sensei_analysis_overview_filter_users', $args );
		$wp_user_search = new WP_User_Query( $args );
        $learners = $wp_user_search->get_results();
		$this->total_items = $wp_user_search->get_total();

        return $learners;

	} // End get_learners()

	/**
	 * Sets the stats boxes to render
	 * @since  1.2.0
	 * @return array $stats_to_render of stats boxes and values
	 */
	public function stats_boxes () {

		// Get the data required
		$user_count = count_users();
		$user_count = apply_filters( 'sensei_analysis_total_users', $user_count['total_users'], $user_count );
		$total_courses = Sensei()->course->course_count( array('publish', 'private') );
		$total_lessons = Sensei()->lesson->lesson_count( array('publish', 'private') );

        /**
         * filter the analysis tot grades query args
         */
		$grade_args = apply_filters( 'sensei_analysis_total_quiz_grades', array(
				'type' => 'sensei_lesson_status',
				'status' => 'any',

                'meta_key' => 'grade'
        ));

		$total_grade_count = Sensei_Grading::get_graded_lessons_count();
		$total_grade_total = Sensei_Grading::get_graded_lessons_sum();
		$total_average_grade = 0;
		if( $total_grade_total > 0 &&  $total_grade_count >0   ){
			$total_average_grade = Sensei_Utils::quotient_as_absolute_rounded_number( $total_grade_total, $total_grade_count, 2 );
		}


		$course_args = array( 
				'type' => 'sensei_course_status',
				'status' => 'any',
			);
		$total_courses_started = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_total_courses_started', $course_args ) );
		$course_args = array( 
				'type' => 'sensei_course_status',
				'status' => 'complete',
			);
		$total_courses_ended = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_total_courses_ended', $course_args ) );
		$average_courses_per_learner = Sensei_Utils::quotient_as_absolute_rounded_number( $total_courses_started, $user_count, 2 );

		// Setup the boxes to render
		$stats_to_render = array( 
								__( 'Total Courses', 'woothemes-sensei' ) => $total_courses,
								__( 'Total Lessons', 'woothemes-sensei' ) => $total_lessons,
								__( 'Total Learners', 'woothemes-sensei' ) => $user_count,
								__( 'Average Courses per Learner', 'woothemes-sensei' ) => $average_courses_per_learner,
								__( 'Average Grade', 'woothemes-sensei' ) => $total_average_grade . '%',
								__( 'Total Completed Courses', 'woothemes-sensei' ) => $total_courses_ended,
							);
		return apply_filters( 'sensei_analysis_stats_boxes', $stats_to_render );
	} // End stats_boxes()

	/**
	 * Sets output when no items are found
	 * Overloads the parent method
	 * @since  1.2.0
	 * @return void
	 */
	public function no_items() {
		if( ! $this->view || 'users' == $this->view ) {
			$type = 'learners';
		} else {
			$type = $this->view;
		}
		echo  sprintf( __( '%1$sNo %2$s found%3$s', 'woothemes-sensei' ), '<em>', $type, '</em>' );
	} // End no_items()

	/**
	 * Output for table heading
	 * @since  1.2.0
	 * @return void
	 */
	public function data_table_header() {
		$menu = array();

		$query_args = array(
			'page' => $this->page_slug,
		);
		$learners_class = $courses_class = $lessons_class = '';
		switch( $this->type ) {
			case 'courses':
				$courses_class = 'current';
				break;

			case 'lessons':
				$lessons_class = 'current';
				break;

			default:
				$learners_class = 'current';
				break;
		}
		$learner_args = $lesson_args = $courses_args = $query_args;
		$learner_args['view'] = 'users';
		$lesson_args['view'] = 'lessons';
		$courses_args['view'] = 'courses';

		$menu['learners'] = '<a class="' . $learners_class . '" href="' . esc_url( add_query_arg( $learner_args, admin_url( 'admin.php' ) ) ). '">' . __( 'Learners', 'woothemes-sensei' ) . '</a>';
		$menu['courses'] = '<a class="' . $courses_class . '" href="' . esc_url ( add_query_arg( $courses_args, admin_url( 'admin.php' ) ) ) . '">' . __( 'Courses', 'woothemes-sensei' ) . '</a>';
		$menu['lessons'] = '<a class="' . $lessons_class . '" href="' . esc_url( add_query_arg( $lesson_args, admin_url( 'admin.php' ) ) ) . '">' . __( 'Lessons', 'woothemes-sensei' ) . '</a>';

		$menu = apply_filters( 'sensei_analysis_overview_sub_menu', $menu );
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
	 * @since  1.7.0
	 * @return void
	 */
	public function data_table_footer() {
		switch ( $this->type ) {
			case 'courses':
				$report = 'courses-overview';
				break;

			case 'lessons':
				$report = 'lessons-overview';
				break;

			case 'users':
			default :
				$report = 'user-overview';
			break;
		} // End Switch Statement
		$url = add_query_arg( array( 'page' => $this->page_slug, 'view' => $this->type, 'sensei_report_download' => $report ), admin_url( 'admin.php' ) );
		echo '<a class="button button-primary" href="' . esc_url( wp_nonce_url( $url, 'sensei_csv_download-' . $report, '_sdl_nonce' ) ) . '">' . __( 'Export all rows (CSV)', 'woothemes-sensei' ) . '</a>';
	} // End data_table_footer()

	/**
	 * The text for the search button
	 * @since  1.7.0
	 * @return string $text
	 */
	public function search_button( $text = '' ) {
		switch( $this->type ) {
			case 'courses':
				$text = __( 'Search Courses', 'woothemes-sensei' );
			break;

			case 'lessons':
				$text = __( 'Search Lessons', 'woothemes-sensei' );
			break;

			case 'users':
			default:
				$text = __( 'Search Learners', 'woothemes-sensei' );
			break;
		} // End Switch Statement

		return $text;
	}

} // End Class

/**
 * Class WooThemes_Sensei_Analysis_Overview_List_Table
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Analysis_Overview_List_Table extends Sensei_Analysis_Overview_List_Table {}
