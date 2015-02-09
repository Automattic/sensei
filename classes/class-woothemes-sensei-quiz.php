<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Quiz Class
 *
 * All functionality pertaining to the quiz post type in Sensei.
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
class WooThemes_Sensei_Quiz {
	public $token;
	public $meta_fields;
	public $file;

	/**
	 * Constructor.
	 * @since  1.0.0
	 *
	 * @param $file
	 */
	public function __construct ( $file = __FILE__ ) {
		$this->file = $file;
		$this->meta_fields = array( 'quiz_passmark', 'quiz_lesson', 'quiz_type', 'quiz_grade_type' );
		add_action( 'save_post', array( $this, 'update_author' ));
	} // End __construct()

	/**
	* Update the quiz author when the lesson post type is save
	*
	* @param int $post_id
	* @return void
	*/
	public function update_author( $post_id ){
		global $woothemes_sensei;

		// If this isn't a 'lesson' post, don't update it.
	    if ( isset( $_POST['post_type'] ) && 'lesson' != $_POST['post_type'] ) {
	        return;
	    }
	    // get the lesson author id to be use late
	    $saved_post = get_post( $post_id );
	    $new_lesson_author_id =  $saved_post->post_author;

	    //get the lessons quiz
		$lesson_quizzes = $woothemes_sensei->post_types->lesson->lesson_quizzes( $post_id );
	    foreach ( (array) $lesson_quizzes as $quiz_item ) {

	    	if( ! $quiz_item ) {
	    		continue;
	    	}

		    // setup the quiz items new author value
			$my_post = array(
			      'ID'           => $quiz_item,
			      'post_author' =>  $new_lesson_author_id
			);

			// Update the post into the database
		  	wp_update_post( $my_post );
	    }

	    return;
	}// end update_author

	/**
	 * Save the user answers for the given lesson's quiz
	 *
	 * @param int $lesson_id
	 * @param int $user_id
	 * @param
	 * @return bool $success
	 */
	public function save_user_answers( $lesson_id, $user_id, $quiz_answers  ){
		$success = false;

		if( empty( $lesson_id ) || empty( $user_id )  ){
			return $success;
		}

		if( 'lesson' != get_post_type( $lesson_id ) ){
			return $success;
		}

		if( !get_userdata( $user_id ) ){
			return $success;
		}


		return true;
	}// end save_user_answers

	/**
	 * Get the user answers for the given lesson's quiz
	 *
	 * @param int $lesson_id
	 * @param int $user_id
	 * @return array $answers
	 */
	public function get_user_answers( $lesson_id, $user_id ){
		$answers = [];

		return $answers;
	}// end save_user_answers

} // End Class