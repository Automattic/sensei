<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Learners Overview List Table Class
 *
 * All functionality pertaining to the Admin Learners Overview Data Table in Sensei.
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
class WooThemes_Sensei_Learners_Main extends WooThemes_Sensei_List_Table {
	public $course_id = 0;
	public $lesson_id = 0;
	public $view = 'default';
	public $page_slug = 'sensei_learners';

	/**
	 * Constructor
	 * @since  1.6.0
	 * @return  void
	 */
	public function __construct () {

		// Load Parent token into constructor
		parent::__construct( 'learners_main' );

		if( isset( $_GET['view'] ) && '' != $_GET['view'] ) {
			$this->view = $_GET['view'];
		}

		if( isset( $_GET['course_id'] )  && 0 < intval( $_GET['course_id'] ) ) {
			$this->course_id = intval( $_GET['course_id'] );
		}

		if( isset( $_GET['lesson_id'] )  && 0 < intval( $_GET['lesson_id'] ) ) {
			$this->lesson_id = intval( $_GET['lesson_id'] );
			$this->view = 'learners';
		}

		$this->columns = array();
		$this->sortable_columns = array();

		// Table Columns
		switch( $this->view ) {
			case 'learners':

				$this->columns = apply_filters( 'sensei_learners_learners_columns', array(
					'learner' => __( 'Learner', 'woothemes-sensei' ),
					'date_started' => __( 'Date Started', 'woothemes-sensei' ),
					'user_status' => __( 'Status', 'woothemes-sensei' ),
					'action' => __( '', 'woothemes-sensei' ),
				) );

				$this->sortable_columns = apply_filters( 'sensei_learners_learners_columns_sortable', array(
					'learner' => array( 'learner', false ),
				) );

			break;

			case 'lessons':

				$this->columns = apply_filters( 'sensei_learners_default_columns', array(
					'lesson' => __( 'Lesson', 'woothemes-sensei' ),
					'no_learners' => __( '# Learners', 'woothemes-sensei' ),
					'updated' => __( 'Last Updated', 'woothemes-sensei' ),
					'action' => __( '', 'woothemes-sensei' ),
				) );

				$this->sortable_columns = apply_filters( 'sensei_learners_default_columns_sortable', array(
					'lesson' => array( 'lesson', false ),
					'updated' => array( 'updated', false ),
				) );

			break;

			default:

				$this->columns = apply_filters( 'sensei_learners_default_columns', array(
					'course' => __( 'Course', 'woothemes-sensei' ),
					'no_learners' => __( '# Learners', 'woothemes-sensei' ),
					'updated' => __( 'Last Updated', 'woothemes-sensei' ),
					'action' => __( '', 'woothemes-sensei' ),
				) );

				$this->sortable_columns = apply_filters( 'sensei_learners_default_columns_sortable', array(
					'course' => array( 'course', false ),
					'updated' => array( 'updated', false ),
				) );

			break;
		}

		// Actions
		add_action( 'sensei_before_list_table', array( $this, 'data_table_header' ) );
		add_action( 'sensei_after_list_table', array( $this, 'data_table_footer' ) );

		add_filter( 'sensei_list_table_search_button_text', array( $this, 'search_button' ) );
	} // End __construct()

	/**
	 * build_data_array builds the data for use in the table
	 * Overloads the parent method
	 * @since  1.6.0
	 * @return array
	 */
	public function build_data_array() {
		global $woothemes_sensei;

		$return_array = array();
		$row_data = false;

		// Handle search
		$search = '';
		if ( isset( $_GET['s'] ) && '' != esc_html( $_GET['s'] ) ) {
			$search = esc_html( $_GET['s'] );
		} // End If Statement

		switch( $this->view ) {
			case 'learners':
				$users = $this->get_learners( $search );

				foreach ( $users as $user ) {
					// Get row data
					$row_data = $this->row_data( $user );

					// Add row to table data
					if( $row_data ) {
						array_push( $return_array, $row_data );
					}
				}
			break;

			case 'lessons':

				$lessons = $this->get_lessons( $search );

				foreach ( $lessons as $lesson ) {
					// Get row data
					$row_data = $this->row_data( false, $lesson );

					// Add row to table data
					if( $row_data ) {
						array_push( $return_array, $row_data );
					}
				}

			break;

			default:

				$courses = $this->get_courses( $search );

				foreach ( $courses as $course ) {
					// Get row data
					$row_data = $this->row_data( false, false, $course );

					// Add row to table data
					if( $row_data ) {
						array_push( $return_array, $row_data );
					}
				}

			break;
		}

		// Sort the data
		$return_array = $this->array_sort_reorder( $return_array );

		return $return_array;
	} // End build_data_array()

	/**
	 * Fetch data for single table row
	 * @since  1.6.0
	 * @param  integer $user   User object
	 * @param  integer $course Course object
	 * @return array           Data for table row
	 */
	private function row_data( $user = false, $lesson = false, $course = false ) {
		global $woothemes_sensei;

		if( $user ) {

			$post_id = 0;
			$activity = '';
			$completed = false;
			$object_type = __( 'course', 'woothemes-sensei' );
			$post_type = 'course';

			if( $this->lesson_id ) {
				$post_id = intval( $this->lesson_id );
				$activity = 'sensei_lesson_start';
				$completed = WooThemes_Sensei_Utils::user_completed_lesson( $post_id, $user->ID );
				$object_type = __( 'lesson', 'woothemes-sensei' );
				$post_type = 'lesson';
			} else {
				if( $this->course_id ) {
					$post_id = intval( $this->course_id );
					$activity = 'sensei_course_start';
					$completed = WooThemes_Sensei_Utils::user_completed_course( $post_id, $user->ID );
				}
			}

			$start_date =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $post_id, 'user_id' => $user->ID, 'type' => $activity, 'field' => 'comment_date' ) );

			if( $completed ) {
				$status_html = '<span class="graded">' . apply_filters( 'sensei_completed_text', __( 'Completed', 'woothemes-sensei' ) ) . '</span>';
			} else {
				$status_html = '<span class="in-progress">' . apply_filters( 'sensei_in_progress_text', __( 'In Progress', 'woothemes-sensei' ) ) . '</span>';
			}

			return apply_filters( 'sensei_learners_main_column_data', array(
					'learner' => '<a href="' . admin_url( 'user-edit.php?user_id=' . $user->ID ) . '">' . $user->display_name . '</a>',
					'date_started' => $start_date,
					'user_status' => $status_html,
					'action' => '<a class="remove-learner button" data-user_id="' . $user->ID . '" data-post_id="' . $post_id . '" data-post_type="' . $post_type . '">' . sprintf( __( 'Remove from %1$s', 'woothemes-sensei' ), $object_type ) . '</a>',
			), $user->ID, $post_id );
		}

		if( $lesson ) {
			$lesson_learners = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $lesson->ID, 'type' => 'sensei_lesson_start' ), true );
			$lesson_learners = intval( count( $lesson_learners ) );

			return apply_filters( 'sensei_learners_main_column_data', array(
					'lesson' => '<a href="' . admin_url( 'post.php?action=edit&post=' . $lesson->ID ) . '">' . get_the_title( $lesson->ID ) . '</a>',
					'no_learners' => $lesson_learners,
					'updated' => $lesson->post_modified,
					'action' => '<a href="' . add_query_arg( array( 'page' => $this->page_slug, 'lesson_id' => $lesson->ID, 'course_id' => $this->course_id, 'view' => 'learners' ), admin_url( 'admin.php' ) ) . '" class="button">' . __( 'Manage learners', 'woothemes-sensei' ) . '</a>',
			), $lesson->ID, $this->course_id );
		}

		if( $course ) {
			$course_learners = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $course->ID, 'type' => 'sensei_course_start' ), true );
			$course_learners = intval( count( $course_learners ) );

			return apply_filters( 'sensei_learners_main_column_data', array(
					'course' => '<a href="' . admin_url( 'post.php?action=edit&post=' . $course->ID ) . '">' . get_the_title( $course->ID ) . '</a>',
					'no_learners' => $course_learners,
					'updated' => $course->post_modified,
					'action' => '<a href="' . add_query_arg( array( 'page' => $this->page_slug, 'course_id' => $course->ID, 'view' => 'learners' ), admin_url( 'admin.php' ) ) . '" class="button">' . __( 'Manage learners', 'woothemes-sensei' ) . '</a>',
			), $course->ID );
		}
	}

	public function get_courses( $search = '' ) {
		// Handle category selection
		$course_cat = '';
		if ( isset( $_GET['course_cat'] ) && '' != esc_html( $_GET['course_cat'] ) ) {
			$course_cat = intval( $_GET['course_cat'] );
		} // End If Statement

		$args = array(
			'post_type' => 'course',
			'post_status' => 'publish',
			'posts_per_page' => -1,
		);

		if( 0 < $course_cat ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'course-category',
				'field' => 'id',
				'terms' => $course_cat,
			);
		}

		if( $search ) {
			$args['s'] = $search;
		}

		$courses = get_posts( $args );

		return $courses;
	} // End get_courses()

	public function get_learners( $search = '' ) {

		$user_ids = false;
		$post_id = 0;
		$activity = '';

		if( $this->lesson_id ) {
			$post_id = intval( $this->lesson_id );
			$activity = 'sensei_lesson_start';
		} else {
			if( $this->course_id ) {
				$post_id = intval( $this->course_id );
				$activity = 'sensei_course_start';
			}
		}

		if( ! $post_id || ! $activity ) return array();

		$user_ids = WooThemes_Sensei_Utils::sensei_activity_ids( array( 'post_id' => $post_id, 'type' => $activity, 'field' => 'user_id' ) );

		if( ! $user_ids ) return array();

		$total = count( $user_ids );

		$offset = '';
		if ( isset( $_GET['paged'] ) && 0 < intval( $_GET['paged'] ) ) {
			$offset = $this->per_page * ( $_GET['paged'] - 1 );
		} // End If Statement

		// Don't run the query if there are no users taking this course.
		if ( empty($user_ids) ) return false;

		if ( isset( $orderby ) && 'rand' == $orderby ) {
			$orderwas = 'rand';
			$orderby = 'user_registered';
		}

		$args = array(
			'number' => $total,
			'include' => $user_ids,
			'offset' => $offset,
			'search' => $search,
			'fields' => 'all_with_meta'
		);

		if ( '' !== $args['search'] ) {
			$args['search'] = '*' . $args['search'] . '*';
		}

		$learners_search = new WP_User_Query( $args );
		$learners = $learners_search->get_results();

		return $learners;
	} // End get_learners()

	public function get_lessons( $search = '' ) {

		$args = array(
			'post_type' => 'lesson',
			'post_status' => 'publish',
			'posts_per_page' => -1,
		);

		if( $this->course_id ) {
			$args['meta_key'] = '_lesson_course';
			$args['meta_value'] = $this->course_id;
		}

		if( $search ) {
			$args['s'] = $search;
		}

		$lessons = get_posts( $args );

		return $lessons;
	} // End get_lessons()

	/**
	 * no_items sets output when no items are found
	 * Overloads the parent method
	 * @since  1.6.0
	 * @return void
	 */
	public function no_items() {

		if( ! $this->view || 'default' == $this->view ) {
			$type = 'courses';
		} else {
			$type = $this->view;
		}

		printf( __( '%1$sNo %2$s found%3$s', 'woothemes-sensei' ), '<em>', $type, '</em>' );
	} // End no_items()

	/**
	 * data_table_header output for table heading
	 * @since  1.6.0
	 * @return void
	 */
	public function data_table_header() {

		if( $this->course_id && ! $this->lesson_id ) {

			$learners_class = $lessons_class = '';
			switch( $this->view ) {
				case 'learners':
					$learners_class = 'current';
				break;

				case 'lessons':
					$lessons_class = 'current';
				break;
			}

			$query_args = array(
				'page' => $this->page_slug,
				'course_id' => $this->course_id,
			);

			$learner_args = $lesson_args = $query_args;
			$learner_args['view'] = 'learners';
			$lesson_args['view'] = 'lessons';

			echo '<ul class="subsubsub">' . "\n";

				echo '<li><a class="' . $learners_class . '" href="' . add_query_arg( $learner_args, admin_url( 'admin.php' ) ) . '">' . __( 'Learners', 'woothemes-sensei' ) . '</a> | </li>' . "\n";
				echo '<li><a class="' . $lessons_class . '" href="' . add_query_arg( $lesson_args, admin_url( 'admin.php' ) ) . '">' . __( 'Lessons', 'woothemes-sensei' ) . '</a></li>' . "\n";

			echo '</ul>' . "\n";

		} elseif( $this->course_id && $this->lesson_id ) {

			$query_args = array(
				'page' => $this->page_slug,
				'course_id' => $this->course_id,
				'view' => 'lessons'
			);

			$course = get_the_title( $this->course_id );

			echo '<ul class="subsubsub">' . "\n";

				echo '<li><a class="" href="' . add_query_arg( $query_args, admin_url( 'admin.php' ) ) . '">' . sprintf( __( '%1$sBack to %2$s%3$s', 'woothemes-sensei' ), '<em>&larr; ', $course, '</em>' ) . '</a></li>' . "\n";

			echo '</ul>' . "\n";
		}

		?>
		<div class="learners-selects">
			<?php

			if( 'default' == $this->view ) {

				$selected_cat = 0;
				if ( isset( $_GET['course_cat'] ) && '' != esc_html( $_GET['course_cat'] ) ) {
					$selected_cat = intval( $_GET['course_cat'] );
				}

				$cats = get_terms( 'course-category', array( 'hide_empty' => false ) );

				echo '<div class="select-box">' . "\n";

					echo '<select id="course-category-options" data-placeholder="' . __( 'Course Category', 'woothemes-sensei' ) . '" name="learners_course_cat" class="chosen_select widefat">' . "\n";

						echo '<option value="0">' . __( 'All Course Categories', 'woothemes-sensei' ) . '</option>' . "\n";

						foreach( $cats as $cat ) {
							echo '<option value="' . $cat->term_id . '"' . selected( $cat->term_id, $selected_cat, false ) . '>' . $cat->name . '</option>' . "\n";
						}

					echo '</select>' . "\n";

				echo '</div>' . "\n";
			}

			?>
		</div><!-- /.learners-selects -->
		<?php
	} // End data_table_header()

	/**
	 * data_table_footer output for table footer
	 * @since  1.6.0
	 * @return void
	 */
	public function data_table_footer() {
		// Nothing right now
	} // End data_table_footer()

	public function search_button( $text = '' ) {

		switch( $this->view ) {
			case 'learners':
				$text = __( 'Search Learners', 'woothemes-sensei' );
			break;

			case 'lessons':
				$text = __( 'Search Lessons', 'woothemes-sensei' );
			break;

			default:
				$text = __( 'Search Courses', 'woothemes-sensei' );
			break;
		}

		return $text;
	}

} // End Class
?>