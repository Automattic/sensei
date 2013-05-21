<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Grading Overview Class
 *
 * All functionality pertaining to the Admin Grading Overview in Sensei.
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
 * - display()
 */
class WooThemes_Sensei_Grading_Overview {

	/**
	 * Constructor
	 * @since  1.3.0
	 * @return  void
	 */
	public function __construct () {
		// Actions
		// add_action( 'sensei_before_list_table', array( &$this, 'data_table_header' ) );
	} // End __construct()

	/**
	 * build_data_array builds the data for use in the table
	 * Overloads the parent method
	 * @since  1.3.0
	 * @return array
	 */
	public function build_data_array() {

		global $woothemes_sensei;

		$return_array = array();

		// @JEFF - This is where you get the data

		$return_array = $this->array_sort_reorder( $return_array );
		return $return_array;
	} // End build_data_array()

	/**
	 * display output to the admin view
	 * @since  1.3.0
	 * @return html
	 */
	public function display() {
		// Get data for the user
		$data = $this->build_data_array();

		// Get the Course Posts
		$post_args = array(	'post_type' 		=> 'course',
							'numberposts' 		=> -1,
							'orderby'         	=> 'title',
    						'order'           	=> 'DESC',
    						'post_status'      	=> 'any',
    						'suppress_filters' 	=> 0
							);
		$posts_array = get_posts( $post_args );

		$html .= '<label>' . __( 'Select a Course to Grade', 'woothemes-sensei' ) . '</label>';

		$html .= '<select id="grading-course-options" name="grading_course" class="widefat">' . "\n";
			$html .= '<option value="">' . __( 'None', 'woothemes-sensei' ) . '</option>';
			if ( count( $posts_array ) > 0 ) {
				foreach ($posts_array as $post_item){
					$html .= '<option value="' . esc_attr( absint( $post_item->ID ) ) . '">' . esc_html( $post_item->post_title ) . '</option>' . "\n";
				} // End For Loop
			} // End If Statement
		$html .= '</select>' . "\n";

		$html .= '<label id="grading-lesson-options-label">' . __( 'Select a Lesson to Grade', 'woothemes-sensei' ) . '</label>';

		$html .= '<select id="grading-lesson-options" name="grading_lesson" class="widefat">' . "\n";

		$html .= '</select>' . "\n";

		$html .= '<div id="learners-container"></div>';

		echo $html;

		// echo '<p>';
		// 	echo 'Debugging...fix me Jeff!';
		// echo '</p>';

		?>
		<!-- <p><a href="<?php echo add_query_arg( array( 'page' => 'sensei_grading', 'user' => '1', 'quiz_id' => '1' ), admin_url( 'edit.php?post_type=lesson' ) ); ?>"><?php _e( 'Dummy Link to User Page', 'woothemes-sensei' ); ?></a></p> -->
		<?php

		// echo '<p>';
		// 	echo 'Look...some whitespace in the code ;-)';
		// echo '</p>';

	} // End display()

	/**
	 * REFACTOR - PLACE INTO AN ADMIN UTILS CLASS THE BELOW 2 FUNCTIONS
	 */

	/**
	 * array_sort_reorder handle sorting of table data
	 * @since  1.3.0
	 * @param  array $return_array data to be ordered
	 * @return array $return_array ordered data
	 */
	public function array_sort_reorder( $return_array ) {
		if ( isset( $_GET['orderby'] ) && '' != esc_html( $_GET['orderby'] ) ) {
			$sort_key = '';
			if ( array_key_exists( esc_html( $_GET['orderby'] ), $this->sortable_columns ) ) {
				$sort_key = esc_html( $_GET['orderby'] );
			} // End If Statement
			if ( '' != $sort_key ) {
					$this->sort_array_by_key($return_array,$sort_key);
				if ( isset( $_GET['order'] ) && 'desc' == esc_html( $_GET['order'] ) ) {
					$return_array = array_reverse( $return_array, true );
				} // End If Statement
			} // End If Statement
			return $return_array;
		} else {
			return $return_array;
		} // End If Statement
	} // End array_sort_reorder()

	/**
	 * sort_array_by_key sorts array by key
	 * @since  1.3.0
	 * @param  $array by ref
	 * @param  $key string column name in array
	 * @return void
	 */
	public function sort_array_by_key( &$array, $key ) {
	    $sorter = array();
	    $ret = array();
	    reset( $array );
	    foreach ( $array as $ii => $va ) {
	        $sorter[$ii] = $va[$key];
	    } // End For Loop
	    asort( $sorter );
	    foreach ( $sorter as $ii => $va ) {
	        $ret[$ii] = $array[$ii];
	    } // End For Loop
	    $array = $ret;
	} // End sort_array_by_key()

} // End Class
?>