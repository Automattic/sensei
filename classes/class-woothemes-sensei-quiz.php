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
	 * Get the lesson this quiz belongs to
	 *
	 * @since 1.7.2
	 * @param int $quiz_id
	 * @return int @lesson_id
	 */
	public function get_lesson_id( $quiz_id ){

		if( empty( $quiz_id ) || ! intval( $quiz_id ) > 0 ){
			global $post;
			if( 'quiz' == get_post_type( $post ) ){
				$quiz_id = $post->ID;
			}else{
				return false;
			}

		}

		$quiz = get_post( $quiz_id );
		$lesson_id = $quiz->post_parent;

		return $lesson_id;

	} // end lesson

	/**
	 * sensei_save_quiz_answers
	 *
	 * This answer calls the main save_user_answers function. It was added for backwards compatibility . It als a more
	 * forgiving function the simply takes the answers and then finds the user and the lesson id.
	 *
	 * @param array $quiz_answers
	 * @return bool $saved;
	 */
	public function sensei_save_quiz_answers( $quiz_answers ){
		global $post;

		$quiz_id = $post->ID;
		$lesson_id = WooThemes_Sensei_Quiz::get_lesson_id( $quiz_id );
		$saved = WooThemes_Sensei_Quiz::save_user_answers( $quiz_answers,  $lesson_id  , get_current_user_id() );
		return $saved;

	}// end sensei_save_quiz_answers

	/**
	 * Save the user answers for the given lesson's quiz
	 *
	 * For this function you must supply all three parameters. If will return false one is left out.
	 *
	 * @since 1.7.2
	 * @access public
	 *
	 * @param array $quiz_answers
	 * @param int $lesson_id
	 * @param int $user_id
	 *
	 * @return false or int $answers_saved
	 */
	public function save_user_answers( $quiz_answers, $lesson_id , $user_id = 0 ){

		$answers_saved = false;

		// get the user_id if none was passed in use the current logged in user
		if( ! intval( $user_id ) > 0 ) {
			$user_id = get_current_user_id();
		}

		// make sure the parameters are valid before continuing
		if( empty( $lesson_id ) || empty( $user_id )
			|| 'lesson' != get_post_type( $lesson_id )
			||!get_userdata( $user_id )
			|| !is_array( $quiz_answers ) ){

			return $answers_saved; // answers_saved is false at this point
		}

		// Loop through submitted quiz answers and save them appropriately
		$prepared_answers = array();
		foreach( $quiz_answers as $question_id => $answer ) {

			//Setup the question types
			$question_types = wp_get_post_terms( $question_id, 'question-type' );
			foreach( $question_types as $type ) {
				$question_type = $type->slug;
			}
			if( ! $question_type ) {
				$question_type = 'multiple-choice';
			}

			// Sanitise answer
			if( 0 == get_magic_quotes_gpc() ) {
				$answer = wp_unslash( $answer );
			}
			switch( $question_type ) {
				case 'multi-line': $answer = nl2br( $answer ); break;
				case 'single-line': break;
				case 'gap-fill': break;
				default: $answer = maybe_serialize( $answer ); break;
			}


			$prepared_answers[ $question_id ] =  base64_encode( $answer );

		}// end for each $quiz_answers



		// Handle file upload questions
		if( isset( $_FILES ) ) {
			foreach( $_FILES as $field => $file ) {
				if( strpos( $field, 'file_upload_' ) !== false ) {
					$question_id = str_replace( 'file_upload_', '', $field );
					if( $file && $question_id ) {
						$attachment_id = self::upload_file( $file );
						if( $attachment_id ) {

							$prepared_answers[ $question_id ] = base64_encode( $attachment_id );

						}
					}
				}
			}
		}

		// get the lesson status comment type on the lesson
		$user_lesson_status = WooThemes_Sensei_Utils::user_lesson_status( $lesson_id, $user_id );

		// if this is not set the user is has not started this lesson
		if( ! empty( $user_lesson_status )  && isset( $user_lesson_status->comment_ID )  ){
			$answers_saved  = update_comment_meta( $user_lesson_status->comment_ID, 'quiz_answers' , $prepared_answers  ) ;
		}

		return $answers_saved;
	}// end save_user_answers()

	/**
	 * Get the user answers for the given lesson's quiz.
	 *
	 *
	 * @since 1.7.2
	 * @access public
	 *
	 * @param int $lesson_id
	 * @param int $user_id
	 *
	 * @return array $answers or false
	 */
	public function get_user_answers( $lesson_id, $user_id ){

		$answers = false;
		global $woothemes_sensei;

		$user_answers = array();

		if ( ! intval( $lesson_id ) > 0 || 'lesson' != get_post_type( $lesson_id )
		|| ! intval( $user_id )  > 0 || !get_userdata( $user_id )  ) {
			return false;
		}
		// get the lesson status comment type on the lesson

		$user_lesson_status = WooThemes_Sensei_Utils::user_lesson_status( $lesson_id, $user_id );
		$encoded_user_answers  = get_comment_meta( $user_lesson_status->comment_ID, 'quiz_answers', true) ;

		if( ! is_array( $encoded_user_answers ) ){
			return false;
		}

		foreach( $encoded_user_answers as $question_id => $encoded_answer ) {
			$decoded_answer = base64_decode( $encoded_answer );
			$answers[$question_id] = maybe_unserialize( $decoded_answer );
		}

		return $answers;
	}// end get_user_answers()
} // End Class