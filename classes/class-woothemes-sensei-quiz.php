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

		// listen to the reset button click
		add_action( 'template_redirect', array( $this, 'reset_button_click_listener'  ) );

        // fire the complete quiz button submit for grading action
        add_action( 'sensei_complete_quiz', array( $this, 'user_quiz_submit_listener' ) );

		// fire the save user answers quiz button click responder
		add_action( 'sensei_complete_quiz', array( $this, 'user_save_quiz_answers_listener' ) );

        // fire the load global data function
        add_action( 'sensei_complete_quiz', array( $this, 'load_global_quiz_data' ), 80 );

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
        //todo: make sure this doesn't fire on ajax and auto-save
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

            //todo: remove this hook so it doesn't run for the next post update again

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
     * user_save_quiz_answers_listener
     *
     * This function hooks into the quiz page and accepts the answer form save post.
     *
     * @param array $quiz_answers
     * @return bool $saved;
     */
    public function user_save_quiz_answers_listener(){

        if( ! isset( $_POST[ 'quiz_save' ])
            || !isset( $_POST[ 'sensei_question' ] )
            || empty( $_POST[ 'sensei_question' ] )
            ||  ! wp_verify_nonce( $_POST['woothemes_sensei_save_quiz_nonce'], 'woothemes_sensei_save_quiz_nonce'  ) > 1 ) {
            return;
        }

        global $post;
        $lesson_id = $this->get_lesson_id( $post->ID );
        $quiz_answers = $_POST[ 'sensei_question' ];
        self::save_user_answers( $quiz_answers,  $lesson_id  , get_current_user_id() );
    } // end user_save_quiz_answers_listener

	/**
	 * Save the user answers for the given lesson's quiz
	 *
	 * For this function you must supply all three parameters. If will return false one is left out.
	 *
	 * @since 1.7.4
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

			return false;

		}

        global $woothemes_sensei;
        // start the lesson before saving the data in case the user has not started the lesson
        $activity_logged = WooThemes_Sensei_Utils::sensei_start_lesson( $lesson_id );

        if( $activity_logged ) {
            // Save questions that were asked in this quiz
            if( !empty( $questions_asked_string ) ) {
                update_comment_meta( $activity_logged, 'questions_asked', $questions_asked_string );
            }
        } // end if $activity_logged

        // Need message in case the data wasn't saved?
        $woothemes_sensei->frontend->messages = '<div class="sensei-message note">' . apply_filters( 'sensei_quiz_saved_text', __( 'Quiz Saved Successfully.', 'woothemes-sensei' ) ) . '</div>';

		//prepare the answers
		$prepared_answers = $this->prepare_form_submitted_answers( $quiz_answers , $_FILES );

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
     * This function returns the data that is stored on the lesson as meta and is not compatible with
     * retrieving data for quiz answer before sensei 1.7.4
	 *
	 *
	 * @since 1.7.4
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

		// decode an unserialize all answers
		foreach( $encoded_user_answers as $question_id => $encoded_answer ) {
			$decoded_answer = base64_decode( $encoded_answer );
			$answers[$question_id] = maybe_unserialize( $decoded_answer );
		}

		return $answers;

	}// end get_user_answers()


	/**
	 *
	 * This function runs on the init hook and checks if the reset quiz button was clicked.
	 *
	 * @since 1.7.2
	 * @hooked init
	 *
	 * @return void;
	 */
	public function reset_button_click_listener( ){

		if( ! isset( $_POST[ 'quiz_reset' ])
			||  ! wp_verify_nonce( $_POST['woothemes_sensei_reset_quiz_nonce'], 'woothemes_sensei_reset_quiz_nonce'  ) > 1 ) {

			return; // exit
		}

		global $post;
		$current_quiz_id = $post->ID;
		$lesson_id = $this->get_lesson_id( $current_quiz_id );
		$this->reset_user_saved_answers( $lesson_id, get_current_user_id() );

        // reset the user submitted answer and update their status on the lesson
        self::reset_user_submitted_answers( $lesson_id, get_current_user_id()   );

		//this function should only run once
		remove_action( 'template_redirect', array( $this, 'reset_button_click_response'  ) );
	}

	/**
	 * Reset the users answers saved on a given lesson.
	 *
	 * @since 1.7.2
	 * @access public
	 *
	 * @param int $lesson_id
	 * @param int $user_id
	 * @return bool @success
	 */
	public function reset_user_saved_answers ( $lesson_id, $user_id  ){

		if( empty( $lesson_id ) || ! get_post( $lesson_id )
			|| empty( $user_id ) || ! get_userdata( $user_id ) ){
			return false;
		}

		// get the user data on the lesson
		$user_lesson_status = WooThemes_Sensei_Utils::user_lesson_status( $lesson_id, $user_id );


		if( empty( $user_lesson_status ) || ! isset( $user_lesson_status->comment_ID )  ){
			return false;
		}

		$success = update_comment_meta( $user_lesson_status->comment_ID , 'quiz_answers', '' );
		return $success;

	}// end reset_user_saved_answers()

	/**
	 * Complete/ submit  quiz hooked function
	 *
	 * This function listens to the complete button submit action and processes the users submitted answers
     * not that this function submits the given users quiz answers for grading.
	 *
	 * @since  1.7.4
	 * @access public
	 *
	 * @since
	 * @return void
	 */
	public function user_quiz_submit_listener() {

		global $post, $woothemes_sensei, $current_user;

		// Default grade
		$grade = 0;

		// Get Quiz Questions
		$lesson_quiz_questions = $woothemes_sensei->post_types->lesson->lesson_quiz_questions( $post->ID );

		$quiz_lesson_id = absint( get_post_meta( $post->ID, '_quiz_lesson', true ) );

		// Get quiz grade type
		$quiz_grade_type = get_post_meta( $post->ID, '_quiz_grade_type', true );

		// Get quiz pass setting
		$pass_required = get_post_meta( $post->ID, '_pass_required', true );

		// Get quiz pass mark
		$quiz_passmark = abs( round( doubleval( get_post_meta( $post->ID, '_quiz_passmark', true ) ), 2 ) );

		// Handle Quiz Completion submit for grading
		if ( isset( $_POST['quiz_complete'] )
            && wp_verify_nonce( $_POST[ 'woothemes_sensei_complete_quiz_nonce' ], 'woothemes_sensei_complete_quiz_nonce' ) ) {

			$questions_asked = array_filter( array_map( 'absint', $_POST['questions_asked'] ) );
			$questions_asked_string = implode( ',', $questions_asked );

            // Mark the Lesson as in-progress (if it isn't already), the entry is needed for WooThemes_Sensei_Utils::sensei_grade_quiz_auto() (optimise at some point?)
            $activity_logged = WooThemes_Sensei_Utils::sensei_start_lesson( $quiz_lesson_id );

            $lesson_status = 'ungraded'; // Default when completing a quiz

            // Save questions that were asked in this quiz
            if( !empty( $questions_asked_string ) ) {
                update_comment_meta( $activity_logged, 'questions_asked', $questions_asked_string );
            }

            // Save Quiz Answers for grading
            if( isset( $_POST['sensei_question'] ) ) {

                WooThemes_Sensei_Utils::sensei_save_quiz_answers( $_POST['sensei_question'] );

            }

            // Grade quiz
            // 3rd arg is count of total number of questions but it's not used by sensei_grade_quiz_auto()
            $grade = WooThemes_Sensei_Utils::sensei_grade_quiz_auto( $post->ID, $_POST['sensei_question'], count( $lesson_quiz_questions ), $quiz_grade_type );
            $lesson_metadata = array();
            // Get Lesson Grading Setting
            if ( is_wp_error( $grade ) || 'auto' != $quiz_grade_type ) {
                $lesson_status = 'ungraded'; // Quiz is manually graded and this was a user submission
            }
            else {
                // Quiz has been automatically Graded
                if ( $pass_required ) {
                    // Student has reached the pass mark and lesson is complete
                    if ( $quiz_passmark <= $grade ) {
                        $lesson_status = 'passed';
                    }
                    else {
                        $lesson_status = 'failed';
                    } // End If Statement
                }
                // Student only has to partake the quiz
                else {
                    $lesson_status = 'graded';
                }
                $lesson_metadata['grade'] = $grade; // Technically already set as part of "WooThemes_Sensei_Utils::sensei_grade_quiz_auto()" above
            }

            WooThemes_Sensei_Utils::update_lesson_status( $current_user->ID, $quiz_lesson_id, $lesson_status, $lesson_metadata );

            switch( $lesson_status ) {
                case 'passed' :
                case 'graded' :
                    do_action( 'sensei_user_lesson_end', $current_user->ID, $quiz_lesson_id );
                    break;
            }

            do_action( 'sensei_user_quiz_submitted', $current_user->ID, $post->ID, $grade, $quiz_passmark, $quiz_grade_type );

			// Refresh page to avoid re-posting todo: figure out what this does and fix it in php
			?>
            <script type="text/javascript"> window.location = '<?php echo get_permalink( $post->ID ); ?>'; </script>
		<?php

		} // End If Statement, submission of quiz

	} // End sensei_complete_quiz()

    /**
     * This function set's up the data need for the quiz page
     *
     * This function hooks into sensei_complete_quiz and load the global data for the
     * current quiz.
     *
     * @since 1.7.4
     * @access public
     *
     */
    public function load_global_quiz_data(){

        global $woothemes_sensei, $post, $current_user;
        $this->data = new stdClass();

        // Default grade
        $grade = 0;

        // Get Quiz Questions
        $lesson_quiz_questions = $woothemes_sensei->post_types->lesson->lesson_quiz_questions( $post->ID );

        $quiz_lesson_id = absint( get_post_meta( $post->ID, '_quiz_lesson', true ) );

        // Get quiz grade type
        $quiz_grade_type = get_post_meta( $post->ID, '_quiz_grade_type', true );

        // Get quiz pass setting
        $pass_required = get_post_meta( $post->ID, '_pass_required', true );

        // Get quiz pass mark
        $quiz_passmark = abs( round( doubleval( get_post_meta( $post->ID, '_quiz_passmark', true ) ), 2 ) );

        // Get latest quiz answers and grades
        $lesson_id = $woothemes_sensei->quiz->get_lesson_id( $post->ID );
        $user_quizzes = $woothemes_sensei->quiz->get_user_answers( $lesson_id, get_current_user_id() );
        $user_lesson_status = WooThemes_Sensei_Utils::user_lesson_status( $quiz_lesson_id, $current_user->ID );
        $user_quiz_grade = 0;
        if( isset( $user_lesson_status->comment_ID ) ) {
            $user_quiz_grade = get_comment_meta( $user_lesson_status->comment_ID, 'grade', true );
        }

        if ( ! is_array($user_quizzes) ) { $user_quizzes = array(); }

        // Check again that the lesson is complete
        $user_lesson_end = WooThemes_Sensei_Utils::user_completed_lesson( $user_lesson_status );
        $user_lesson_complete = false;
        if ( $user_lesson_end ) {
            $user_lesson_complete = true;
        } // End If Statement

        $reset_allowed = get_post_meta( $post->ID, '_enable_quiz_reset', true );

        // Build frontend data object
        $this->data->user_quizzes = $user_quizzes;
        $this->data->user_quiz_grade = $user_quiz_grade;
        $this->data->quiz_passmark = $quiz_passmark;
        $this->data->quiz_lesson = $quiz_lesson_id;
        $this->data->quiz_grade_type = $quiz_grade_type;
        $this->data->user_lesson_end = $user_lesson_end;
        $this->data->user_lesson_complete = $user_lesson_complete;
        $this->data->lesson_quiz_questions = $lesson_quiz_questions;
        $this->data->reset_quiz_allowed = $reset_allowed;

    } // end load_global_quiz_data


	/**
	 * This function converts the submitted array and makes it ready it for storage
	 *
	 * Creating a single array of all question types including file id's to be stored
	 * as comment meta by the calling function.
	 *
	 * @since 1.7.4
	 * @access public
	 *
	 * @param array $unprepared_answers
	 * @param $files
	 * @return array
	 */
	public function prepare_form_submitted_answers( $unprepared_answers,  $files ){

		$prepared_answers = array();

		// validate incoming answers
		if( empty( $unprepared_answers  ) || ! is_array( $unprepared_answers ) ){
			return false;
		}

		// Loop through submitted quiz answers and save them appropriately
		foreach( $unprepared_answers as $question_id => $answer ) {

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
		if( isset( $files ) && ! empty( $files  ) ) {
			foreach( $files as $field => $file ) {
				if( strpos( $field, 'file_upload_' ) !== false ) {
					$question_id = str_replace( 'file_upload_', '', $field );
					if( $file && $question_id ) {
						$attachment_id = WooThemes_Sensei_Utils::upload_file( $file );
						if( $attachment_id ) {

							$prepared_answers[ $question_id ] = base64_encode( $attachment_id );

						}
					}
				}
			}
		}

		return $prepared_answers;
	} // prepare_form_submitted_answers

    /**
     * Reset user submitted questions
     *
     * This function resets the quiz data for a user that has been submitted fro grading already. It is different to
     * the save_user_answers as currently the saved and submitted answers are stored differently.
     *
     * @since 1.7.4
     * @access public
     *
     * @return bool $reset_success
     * @param int $user_id
     * @param int $lesson_id
     */
    public function reset_user_submitted_answers( $lesson_id , $user_id = 0 ){

        //make sure the parameters are valid
        if( empty( $lesson_id ) || empty( $user_id )
            || 'lesson' != get_post_type( $lesson_id
            || ! get_userdata( $user_id )) ){
            return false;
        }

        global $woothemes_sensei;

        //get the lesson quiz and course
        $quiz_id = $woothemes_sensei->lesson->lesson_quizzes( $lesson_id );
        $course_id = $woothemes_sensei->lesson->get_course_id( $lesson_id );

        // Delete quiz answers, this auto deletes the corresponding meta data, such as the question/answer grade
        WooThemes_Sensei_Utils::sensei_delete_quiz_answers( $quiz_id, $user_id );
        WooThemes_Sensei_Utils::update_lesson_status( $user_id , $lesson_id, 'in-progress', array( 'questions_asked' => '', 'grade' => '' ) );

        // Update course completion
        WooThemes_Sensei_Utils::update_course_status( $user_id, $course_id );

        // Run any action on quiz/lesson reset (previously this didn't occur on resetting a quiz, see resetting a lesson in sensei_complete_lesson()
        do_action( 'sensei_user_lesson_reset', $user_id, $lesson_id );
        $woothemes_sensei->frontend->messages = '<div class="sensei-message note">' . apply_filters( 'sensei_quiz_reset_text', __( 'Quiz Reset Successfully.', 'woothemes-sensei' ) ) . '</div>';

    } // end reset_user_submitted_answers

} // End Class WooThemes_Sensei_Quiz