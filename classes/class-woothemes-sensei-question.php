<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Question Class
 *
 * All functionality pertaining to the questions post type in Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Core
 * @author WooThemes
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 */
class WooThemes_Sensei_Question {
	public $token;
	public $meta_fields;

	/**
	 * Constructor.
	 * @since  1.0.0
	 */
	public function __construct () {
		$this->meta_fields = array( 'question_right_answer', 'question_wrong_answers' );
		if ( is_admin() ) {
			// Custom Write Panel Columns
			add_filter( 'manage_edit-question_columns', array( $this, 'add_column_headings' ), 10, 1 );
			add_action( 'manage_posts_custom_column', array( $this, 'add_column_data' ), 10, 2 );
		} // End If Statement
	} // End __construct()

	public function question_types() {
		$types = array(
			'multiple-choice' => 'Multiple Choice',
			'boolean' => 'True/False',
			'gap-fill' => 'Gap Fill',
			'single-line' => 'Single Line',
			'multi-line' => 'Multi Line',
			'file-upload' => 'File Upload',
		);

		return apply_filters( 'sensei_question_types', $types );
	}

	/**
	 * Add column headings to the "lesson" post list screen.
	 * @access public
	 * @since  1.3.0
	 * @param  array $defaults
	 * @return array $new_columns
	 */
	public function add_column_headings ( $defaults ) {
		$new_columns['cb'] = '<input type="checkbox" />';
		// $new_columns['id'] = __( 'ID' );
		$new_columns['title'] = _x( 'Question', 'column name', 'woothemes-sensei' );
		$new_columns['question-type'] = _x( 'Type', 'column name', 'woothemes-sensei' );
		$new_columns['question-category'] = _x( 'Categories', 'column name', 'woothemes-sensei' );
		if ( isset( $defaults['date'] ) ) {
			$new_columns['date'] = $defaults['date'];
		}

		return $new_columns;
	} // End add_column_headings()

	/**
	 * Add data for our newly-added custom columns.
	 * @access public
	 * @since  1.3.0
	 * @param  string $column_name
	 * @param  int $id
	 * @return void
	 */
	public function add_column_data ( $column_name, $id ) {
		global $wpdb, $post;

		switch ( $column_name ) {
			case 'id':
				echo $id;
			break;
			case 'question-type':
				$output = get_the_term_list( $id, 'question-type', '', ', ', '' );
				if ( ! $output ) {
					$output = '&mdash;';
				} // End If Statement
				$question_type_orig = array(	'>boolean<',
												'>multiple-choice<',
												'>gap-fill<',
												'>multi-line<',
												'>single-line<',
												'>file-upload<'
										 );
				$question_type_replace = array(	'>True/False<',
												'>Multiple Choice<',
												'>Gap Fill<',
												'>Multi Line<',
												'>Single Line<',
												'>File Upload<'
										 );
				echo strip_tags( str_replace( $question_type_orig, $question_type_replace, $output ) );
			break;
			case 'question-category':
				$output = strip_tags( get_the_term_list( $id, 'question-category', '', ', ', '' ) );
				if( ! $output ) {
					$output = '&mdash;';
				}
				echo $output;
			break;
			default:
			break;
		}
	} // End add_column_data()

} // End Class
?>