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
	public $user_id;
	public $course_id;
	public $lesson_id;

	/**
	 * Constructor
	 * @since  1.6.0
	 * @return  void
	 */
	public function __construct ( $course_id = 0, $lesson_id = 0 ) {
		$this->course_id = intval( $course_id );
		$this->lesson_id = intval( $lesson_id );
		// Load Parent token into constructor
		parent::__construct( 'learners_main' );

		// Default Columns
		$this->columns = apply_filters( 'sensei_learners_main_columns', array(
			'course' => __( 'Course', 'woothemes-sensei' ),
			'no_learners' => __( '# Learners', 'woothemes-sensei' ),
			'updated' => __( 'Updated', 'woothemes-sensei' ),
			'action' => __( '', 'woothemes-sensei' ),
		) );
		// Sortable Columns
		$this->sortable_columns = apply_filters( 'sensei_learners_main_columns_sortable', array(
			'course' => array( 'course', false ),
		) );

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

		foreach ( $courses as $course ) {
			// Get row data
			$row_data = $this->row_data( $course );

			// Add row to table data
			if( $row_data ) {
				array_push( $return_array, $row_data );
			}
		}

		// Sort the data
		$return_array = $this->array_sort_reorder( $return_array );

		return $return_array;
	} // End build_data_array()

	/**
	 * Fetch data for single table row
	 * @since  1.6.0
	 * @param  integer $course Course object
	 * @return array           Data for table row
	 */
	private function row_data( $course = false ) {
		global $woothemes_sensei;

		if( ! $course->ID ) return false;

		$course_learners = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $course->ID, 'type' => 'sensei_course_start' ), true );
		$course_learners = intval( count( $course_learners ) );

		return apply_filters( 'sensei_learners_main_column_data', array(
				'course' => '<a href="' . admin_url( 'post.php?action=edit&post=' . $course->ID ) . '">' . get_the_title( $course->ID ) . '</a>',
				'no_learners' => $course_learners,
				'updated' => $course->post_modified,
				'action' => '<a href="" class="button">' . __( 'Manage Learners', 'woothemes-sensei' ) . '</a>',
		), $course->ID );

	}

	/**
	 * no_items sets output when no items are found
	 * Overloads the parent method
	 * @since  1.6.0
	 * @return void
	 */
	public function no_items() {
		_e( 'No courses found.', 'woothemes-sensei' );
	} // End no_items()

	/**
	 * data_table_header output for table heading
	 * @since  1.6.0
	 * @return void
	 */
	public function data_table_header() {
		global $woothemes_sensei;

		$selected_cat = 0;
		if ( isset( $_GET['course_cat'] ) && '' != esc_html( $_GET['course_cat'] ) ) {
			$selected_cat = intval( $_GET['course_cat'] );
		}

		$cats = get_terms( 'course-category', array( 'hide_empty' => false ) );

		?>
		<div class="learners-selects">
			<?php

			echo '<div class="select-box">' . "\n";

				echo '<select id="course-category-options" data-placeholder="' . __( 'Course Category', 'woothemes-sensei' ) . '" name="learners_course_cat" class="chosen_select widefat">' . "\n";

					echo '<option value="0">' . __( 'All Course Categories', 'woothemes-sensei' ) . '</option>' . "\n";

					foreach( $cats as $cat ) {
						echo '<option value="' . $cat->term_id . '"' . selected( $cat->term_id, $selected_cat, false ) . '>' . $cat->name . '</option>' . "\n";
					}

				echo '</select>' . "\n";

			echo '</div>' . "\n";

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
		$text = __( 'Search Courses', 'woothemes-sensei' );
		return $text;
	}

} // End Class
?>