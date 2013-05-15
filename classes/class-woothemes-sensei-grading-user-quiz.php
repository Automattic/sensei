<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Grading User Quiz Class
 *
 * All functionality pertaining to the Admin Grading User Profile in Sensei.
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
class WooThemes_Sensei_Grading_User_Quiz {
	public $user_id;

	/**
	 * Constructor
	 * @since  1.3.0
	 * @return  void
	 */
	public function __construct ( $user_id = 0, $quiz_id = 0 ) {
		$this->user_id = intval( $user_id );
		$this->quiz_id = intval( $quiz_id );
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

		$post_args = array(	'post_type' 		=> 'question',
							'numberposts' 		=> -1,
							'orderby'         	=> 'ID',
    						'order'           	=> 'ASC',
    						'meta_key'        	=> '_quiz_id',
    						'meta_value'      	=> $this->quiz_id,
    						'post_status'		=> 'publish',
							'suppress_filters' 	=> 0
							);
		$return_array = get_posts( $post_args );

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
		$questions = $this->build_data_array();

		echo '<form name="' . esc_attr( 'quiz_' . $this->quiz_id ) . '" action="" method="post">';

		foreach( $questions as $question ) {
			$qid = $question->ID;

			$types = wp_get_post_terms( $qid, 'question-type' );
			foreach( $types as $t ) {
				$type = $t->name;
				break;
			}

			$right_answer = get_post_meta( $qid, '_question_right_answer', true );
			$user_answer = maybe_unserialize( base64_decode( WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $qid, 'user_id' => $this->user_id, 'type' => 'sensei_user_answer', 'field' => 'comment_content' ) ) ) );

			switch( $type ) {
				case 'boolean':
					$type_name = __( 'True/False', 'woothemes-sensei' );
					$right_answer = ucfirst( $right_answer );
					$user_answer = ucfirst( $user_answer );
				break;
				case 'multiple-choice':
					$type_name = __( 'Multiple Choice', 'woothemes-sensei' );
				break;
				case 'gap-fill':
					$type_name = __( 'Gap Fill', 'woothemes-sensei' );

					$right_answer_array = explode( '|', $right_answer );
					if ( isset( $right_answer_array[0] ) ) { $gapfill_pre = $right_answer_array[0]; } else { $gapfill_pre = ''; }
					if ( isset( $right_answer_array[1] ) ) { $gapfill_gap = $right_answer_array[1]; } else { $gapfill_gap = ''; }
					if ( isset( $right_answer_array[2] ) ) { $gapfill_post = $right_answer_array[2]; } else { $gapfill_post = ''; }

					$right_answer = $gapfill_pre . ' <span class="highlight">' . $gapfill_gap . '</span> ' . $gapfill_post;
					$user_answer = $gapfill_pre . ' <span class="highlight">' . $user_answer . '</span> ' . $gapfill_post;

				break;
				case 'multi-line':
					$type_name = __( 'Multi Line', 'woothemes-sensei' );
				break;
				case 'essay-paste':
					$type_name = __( 'Essay Paste', 'woothemes-sensei' );
				break;
				case 'single-line':
					$type_name = __( 'Single Line', 'woothemes-sensei' );
				break;
				default:
					// Nothing
				break;
			}

			?><div class="postbox question_box" id="<?php esc_attr_e( 'question_' . $qid . '_box' ); ?>">
					<div class="handlediv" title="Click to toggle"><br></div>
					<h3 class="hndle"><span><?php echo $type_name; ?></span></h3>
					<div class="inside">
						<div class="sensei-grading-answer">
							<h4><?php echo $question->post_title; ?></h4>
							<p><?php echo $user_answer; ?></p>
						</div>
						<div class="sensei-grading-actions">
							<div class="right-answer">
								<?php echo $right_answer; ?>
							</div>
							<div class="actions">
								<input type="hidden" name="<?php esc_attr_e( 'grade_' . $qid ); ?>" value="1" />
								<span class="grading-mark icon_right"><input type="radio" name="<?php esc_attr_e( 'question_' . $qid ); ?>" value="right" /></span>
								<span class="grading-mark icon_wrong"><input type="radio" name="<?php esc_attr_e( 'question_' . $qid ); ?>" value="wrong" /></span>
							</div>
						</div>
						<div class="clear"></div>
					</div>
				</div><?php
		} ?>

			<div class="buttons">
				<input type="submit" value="Save" class="grade-button button-primary" /> <input type="reset" value="Reset" class="reset-button button-secondary" />
			</div>

		</form><?php
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