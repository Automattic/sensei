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
	 * @param array $quiz_answers
	 * @param
	 * @return bool $success
	 */
	public function save_user_answers( $quiz_answers, $user_id, $lesson_id   ){

		// get the user_id if none was passed in
		if( intval( $user_id ) == 0 ) {
			$user_id = get_current_user_id();
		}
		$answers_saved = false;

		// make sure the parameters are valid before continuing
		if( empty( $lesson_id ) || empty( $user_id )
			|| 'lesson' != get_post_type( $lesson_id )
			||!get_userdata( $user_id )
			|| !is_array( $quiz_answers ) ){

			return $answers_saved; // answers save is false at this point
		}

		// Loop through submitted quiz answers and save them appropriately
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
			$args = array(
				'post_id' => $question_id,
				'data' => base64_encode( $answer ),
				'type' => 'sensei_user_answer', /* FIELD SIZE 20 */
				'user_id' => $user_id,
				'action' => 'update'
			);
			$answers_saved = WooThemes_Sensei_Utils::sensei_log_activity( $args );

		}// end for each $quiz_answers


		// Handle file upload questions
		if( isset( $_FILES ) ) {
			foreach( $_FILES as $field => $file ) {
				if( strpos( $field, 'file_upload_' ) !== false ) {
					$question_id = str_replace( 'file_upload_', '', $field );
					if( $file && $question_id ) {
						$attachment_id = self::upload_file( $file );
						if( $attachment_id ) {
							$args = array(
								'post_id' => $question_id,
								'data' => base64_encode( $attachment_id ),
								'type' => 'sensei_user_answer', /* FIELD SIZE 20 */
								'user_id' => $user_id,
								'action' => 'update'
							);
							$answers_saved = WooThemes_Sensei_Utils::sensei_log_activity( $args );
						}
					}
				}
			}
		}

		return $answers_saved;
	}// end save_user_answers()

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
	}// end get_user_answers()

} // End Class