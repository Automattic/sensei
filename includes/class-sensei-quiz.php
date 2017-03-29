<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Quiz Class
 *
 * All functionality pertaining to the quiz post type in Sensei.
 *
 * @package Assessment
 * @author Automattic
 *
 * @since 1.0.0
 */
 class Sensei_Quiz {
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
        $this->token = 'quiz';
		$this->meta_fields = array( 'quiz_passmark', 'quiz_lesson', 'quiz_type', 'quiz_grade_type', 'pass_required','enable_quiz_reset' );
		add_action( 'save_post', array( $this, 'update_after_lesson_change' ));

		// listen to the reset button click
		add_action( 'template_redirect', array( $this, 'reset_button_click_listener'  ) );

        // fire the complete quiz button submit for grading action
        add_action( 'sensei_single_quiz_content_inside_before', array( $this, 'user_quiz_submit_listener' ) );

		// fire the save user answers quiz button click responder
		add_action( 'sensei_single_quiz_content_inside_before', array( $this, 'user_save_quiz_answers_listener' ) );

        // fire the load global data function
        add_action( 'sensei_single_quiz_content_inside_before', array( $this, 'load_global_quiz_data' ), 80 );

        add_action( 'template_redirect', array ( $this, 'quiz_has_no_questions') );

		// remove post when lesson is permanently deleted
		add_action( 'delete_post', array( $this, 'maybe_delete_quiz' ) );

    } // End __construct()

	/**
	* Update the quiz data when the lesson is changed
	*
	* @param int $post_id
	* @return void
	*/
	public function update_after_lesson_change( $post_id ){

		// If this isn't a 'lesson' post, don't update it.
		// if this is a revision don't save it
		if ( ! isset( $_POST['post_type'] )
		     || 'lesson' !== $_POST['post_type']
			|| wp_is_post_revision( $post_id ) ) {
				return;
		}

		// Get the lesson author id to be use late.
		$saved_lesson           = get_post( $post_id );
		$new_lesson_author_id = $saved_lesson->post_author;

		// Get the lessons quiz.
		$quiz_id = Sensei()->lesson->lesson_quizzes( $post_id );
		if ( ! $quiz_id ) {
			return;
		}

		// Setup the quiz items new author value.
		$my_post = array(
			  'ID'          => $quiz_id,
			  'post_author' => $new_lesson_author_id,
			  'post_name' => $saved_lesson->post_name,
		);

		// Remove the action so that it doesn't fire again.
		remove_action( 'save_post', array( $this, 'update_author' ) );

		// Update the post into the database.
		wp_update_post( $my_post );
	}


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
     * @since 1.7.3
     * @return bool $saved;
     */
    public function user_save_quiz_answers_listener() {

        if( ! isset( $_POST[ 'quiz_save' ])
            || !isset( $_POST[ 'sensei_question' ] )
            || empty( $_POST[ 'sensei_question' ] )
            ||  ! wp_verify_nonce( $_POST['woothemes_sensei_save_quiz_nonce'], 'woothemes_sensei_save_quiz_nonce'  ) > 1 ) {
            return;
        }

        global $post;
        $lesson_id = $this->get_lesson_id( $post->ID );
        $quiz_answers = $this->merge_quiz_answers_with_questions_asked( $_POST, $post->ID );

		// call the save function
		$answers_saved = self::save_user_answers( $quiz_answers, $_FILES , $lesson_id  , get_current_user_id() );

		if ( intval( $answers_saved ) > 0 ) {
			// update the message showed to user
			Sensei()->frontend->messages = '<div class="sensei-message note">' . __( 'Quiz Saved Successfully.', 'woothemes-sensei' )  . '</div>';
		}

        // remove the hook as it should only fire once per click
        remove_action( 'sensei_single_quiz_content_inside_before', 'user_save_quiz_answers_listener' );

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
     * @param array $files from global $_FILES
	 * @param int $lesson_id
	 * @param int $user_id
	 *
	 * @return false or int $answers_saved
	 */
	public static function save_user_answers( $quiz_answers, $files = array(), $lesson_id , $user_id = 0 ) {

        if( ! ( $user_id > 0 ) ){
            $user_id = get_current_user_id();
        }

        // make sure the parameters are valid before continuing
		if( empty( $lesson_id ) || empty( $user_id )
			|| 'lesson' != get_post_type( $lesson_id )
			||!get_userdata( $user_id )
			|| !is_array( $quiz_answers ) ){

			return false;

		}

        // start the lesson before saving the data in case the user has not started the lesson
        $activity_logged = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );

		//prepare the answers
		$prepared_answers = self::prepare_form_submitted_answers( $quiz_answers , $files );

		// save the user data
        $answers_saved = Sensei_Utils::add_user_data( 'quiz_answers', $lesson_id, $prepared_answers, $user_id ) ;

		// were the answers saved correctly?
		if( intval( $answers_saved ) > 0){

            // save transient to make retrieval faster
            $transient_key = 'sensei_answers_'.$user_id.'_'.$lesson_id;
            set_transient( $transient_key, $prepared_answers, 10 * DAY_IN_SECONDS );

			//ensure these questions are saved for the user
			//if saved they should not be overwritten on save
			// only through reset can they be removed
			$questions_asked_csv = get_comment_meta( $activity_logged, 'questions_asked', true );
			if( empty( $questions_asked_csv ) ){
				$questions_asked_csv = implode( ',', array_keys( $quiz_answers ) );
				update_comment_meta( $activity_logged, 'questions_asked', $questions_asked_csv );
			}
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

		if ( ! intval( $lesson_id ) > 0 || 'lesson' != get_post_type( $lesson_id )
		|| ! intval( $user_id )  > 0 || !get_userdata( $user_id )  ) {
			return false;
		}

        // save some time and get the transient cached data
        $transient_key = 'sensei_answers_'.$user_id.'_'.$lesson_id;
        $transient_cached_answers = get_transient( $transient_key );

        // return the transient or get the values get the values from the comment meta
        if( !empty( $transient_cached_answers  ) && false != $transient_cached_answers ){

            $encoded_user_answers = $transient_cached_answers;

        }else{

            $encoded_user_answers = Sensei_Utils::get_user_data( 'quiz_answers', $lesson_id  , $user_id );

        } // end if transient check

		if( ! is_array( $encoded_user_answers ) ){
			return false;
		}

        //set the transient with the new valid data for faster retrieval in future
        set_transient( $transient_key,  $encoded_user_answers, 10 * DAY_IN_SECONDS);

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

        // reset all user data
        $this->reset_user_lesson_data( $lesson_id, get_current_user_id() );

		//this function should only run once
		remove_action( 'template_redirect', array( $this, 'reset_button_click_listener'  ) );

	} // end reset_button_click_listener

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

        // only respond to valid quiz completion submissions
        if( ! isset( $_POST[ 'quiz_complete' ])
            || !isset( $_POST[ 'sensei_question' ] )
            || empty( $_POST[ 'sensei_question' ] )
            ||  ! wp_verify_nonce( $_POST['woothemes_sensei_complete_quiz_nonce'], 'woothemes_sensei_complete_quiz_nonce'  ) > 1 ) {
            return;
        }

        global $post, $current_user;
        $lesson_id = $this->get_lesson_id( $post->ID );
        $quiz_answers = $this->merge_quiz_answers_with_questions_asked( $_POST, $post->ID );

        self::submit_answers_for_grading( $quiz_answers, $_FILES ,  $lesson_id  , $current_user->ID );

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

        global  $post, $current_user;
        $this->data = new stdClass();

        // Default grade
        $grade = 0;

        // Get Quiz Questions
        $lesson_quiz_questions = Sensei()->lesson->lesson_quiz_questions( $post->ID );

        $quiz_lesson_id = absint( get_post_meta( $post->ID, '_quiz_lesson', true ) );

        // Get quiz grade type
        $quiz_grade_type = get_post_meta( $post->ID, '_quiz_grade_type', true );

        // Get quiz pass setting
        $pass_required = get_post_meta( $post->ID, '_pass_required', true );

        // Get quiz pass mark
        $quiz_passmark = Sensei_Utils::as_absolute_rounded_number( get_post_meta( $post->ID, '_quiz_passmark', true ), 2 );

        // Get latest quiz answers and grades
        $lesson_id = Sensei()->quiz->get_lesson_id( $post->ID );
        $user_quizzes = Sensei()->quiz->get_user_answers( $lesson_id, get_current_user_id() );
        $user_lesson_status = Sensei_Utils::user_lesson_status( $quiz_lesson_id, $current_user->ID );
        $user_quiz_grade = 0;
        if( isset( $user_lesson_status->comment_ID ) ) {
            $user_quiz_grade = get_comment_meta( $user_lesson_status->comment_ID, 'grade', true );
        }

        if ( ! is_array($user_quizzes) ) { $user_quizzes = array(); }

        // Check again that the lesson is complete
        $user_lesson_end = Sensei_Utils::user_completed_lesson( $user_lesson_status );
        $user_lesson_complete = false;
        if ( $user_lesson_end ) {
            $user_lesson_complete = true;
        } // End If Statement

        $reset_allowed = get_post_meta( $post->ID, '_enable_quiz_reset', true );
        //backwards compatibility
        if( 'on' == $reset_allowed ) {
            $reset_allowed = 1;
        }

        // Build frontend data object for backwards compatibility
        // using this is no longer recommended
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
	public static function prepare_form_submitted_answers( $unprepared_answers,  $files ){


		$prepared_answers = array();

		// validate incoming answers
		if( empty( $unprepared_answers  ) || ! is_array( $unprepared_answers ) ){
			return false;
		}

		// Loop through submitted quiz answers and save them appropriately
		foreach( $unprepared_answers as $question_id => $answer ) {

			//get the current questions question type
            $question_type = Sensei()->question->get_question_type( $question_id );

			// Sanitise answer
			if( 0 == get_magic_quotes_gpc() ) {
				$answer = wp_unslash( $answer );
			}

            // compress the answer for saving
			if( 'multi-line' == $question_type ) {
                $answer = esc_html( $answer );
            }elseif( 'file-upload' == $question_type  ){
                $file_key = 'file_upload_' . $question_id;
                if( isset( $files[ $file_key ] ) ) {
                        $attachment_id = Sensei_Utils::upload_file(  $files[ $file_key ] );
                        if( $attachment_id ) {
                            $answer = $attachment_id;
                        }
                    }
            } // end if

			$prepared_answers[ $question_id ] =  base64_encode( maybe_serialize( $answer ) );

		}// end for each $quiz_answers

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
    public function reset_user_lesson_data( $lesson_id , $user_id = 0 ){

        //make sure the parameters are valid
        if( empty( $lesson_id ) || empty( $user_id )
            || 'lesson' != get_post_type( $lesson_id )
            || ! get_userdata( $user_id ) ){
            return false;
        }



        //get the users lesson status to make
        $user_lesson_status = Sensei_Utils::user_lesson_status( $lesson_id, $user_id );
        if( ! isset( $user_lesson_status->comment_ID ) ) {
            // this user is not taking this lesson so this process is not needed
            return false;
        }

        //get the lesson quiz and course
        $quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_id );
        $course_id = Sensei()->lesson->get_course_id( $lesson_id );

        // reset the transients
        $answers_transient_key = 'sensei_answers_'.$user_id.'_'.$lesson_id;
        $grades_transient_key = 'quiz_grades_'.$user_id.'_'.$lesson_id;
        $answers_feedback_transient_key = 'sensei_answers_feedback_'.$user_id.'_'.$lesson_id;
        delete_transient( $answers_transient_key );
        delete_transient( $grades_transient_key );
        delete_transient( $answers_feedback_transient_key );

        // reset the quiz answers and feedback notes
        $deleted_answers = Sensei_Utils::delete_user_data( 'quiz_answers', $lesson_id, $user_id );
        $deleted_grades = Sensei_Utils::delete_user_data( 'quiz_grades', $lesson_id, $user_id );
        $deleted_user_feedback = Sensei_Utils::delete_user_data( 'quiz_answers_feedback', $lesson_id, $user_id );

        // Delete quiz answers, this auto deletes the corresponding meta data, such as the question/answer grade
        Sensei_Utils::sensei_delete_quiz_answers( $quiz_id, $user_id );

        Sensei_Utils::update_lesson_status( $user_id , $lesson_id, 'in-progress', array( 'questions_asked' => '', 'grade' => '' ) );

        // Update course completion
        Sensei_Utils::update_course_status( $user_id, $course_id );

        // Run any action on quiz/lesson reset (previously this didn't occur on resetting a quiz, see resetting a lesson in sensei_complete_lesson()
        do_action( 'sensei_user_lesson_reset', $user_id, $lesson_id );
	    if( ! is_admin() ) {
		    Sensei()->notices->add_notice( __( 'Quiz Reset Successfully.', 'woothemes-sensei' ) , 'info');
	    }

		return true;

    } // end reset_user_lesson_data

     /**
      * Submit the users quiz answers for grading
      *
      * This function accepts users answers and stores it but also initiates the grading
      * if a quiz can be graded automatically it will, if not the answers can be graded by the teacher.
      *
      * @since 1.7.4
      * @access public
      *
      * @param array $quiz_answers
      * @param array $files from $_FILES
      * @param int $user_id
      * @param int $lesson_id
      *
      * @return bool $answers_submitted
      */
     public static function submit_answers_for_grading( $quiz_answers , $files = array() , $lesson_id , $user_id = 0 ){

         $answers_submitted = false;

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

         // Default grade
         $grade = 0;

         // Get Quiz ID
         $quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_id );

         // Get quiz grade type
         $quiz_grade_type = get_post_meta( $quiz_id, '_quiz_grade_type', true );

         // Get quiz pass setting
         $pass_required = get_post_meta( $quiz_id, '_pass_required', true );

         // Get the minimum percentage need to pass this quiz
         $quiz_pass_percentage = Sensei_Utils::as_absolute_rounded_number( get_post_meta( $quiz_id, '_quiz_passmark', true ), 2 );

         // Handle Quiz Questions asked
         // This is to ensure we save the questions that we've asked this user and that this can't be change unless
         // the quiz is reset by admin or user( user: only if the setting is enabled ).
         // get the questions asked when when the quiz questions were generated for the user : Sensei_Lesson::lesson_quiz_questions
         $user_lesson_status = Sensei_Utils::user_lesson_status( $lesson_id, $user_id );
		if ( ! isset( $user_lesson_status->comment_ID ) ) {
			$user_lesson_status_id = Sensei_Utils::user_start_lesson( $user_id, $lesson_id );
			$user_lesson_status = get_comment( $user_lesson_status_id );
		}
         $questions_asked = isset(  $user_lesson_status->comment_ID ) ? get_comment_meta( $user_lesson_status->comment_ID, 'questions_asked', true ): array();
         if( empty( $questions_asked ) ){

             $questions_asked = array_keys( $quiz_answers );
             $questions_asked_string = implode( ',', $questions_asked );

             // Save questions that were asked in this quiz
             update_comment_meta( $user_lesson_status->comment_ID, 'questions_asked', $questions_asked_string );

         }

         // Save Quiz Answers for grading, the save function also calls the sensei_start_lesson
         self::save_user_answers( $quiz_answers , $files , $lesson_id , $user_id );

         // Grade quiz
         $grade = Sensei_Grading::grade_quiz_auto( $quiz_id, $quiz_answers, 0 , $quiz_grade_type );

         // Get Lesson Grading Setting
         $lesson_metadata = array();
         $lesson_status = 'ungraded'; // Default when completing a quiz

         // At this point the answers have been submitted
         $answers_submitted = true;

         // if this condition is false the quiz should manually be graded by admin
         if ('auto' == $quiz_grade_type && ! is_wp_error( $grade )  ) {

             // Quiz has been automatically Graded
             if ( 'on' == $pass_required ) {

                 // Student has reached the pass mark and lesson is complete
                 if ( $quiz_pass_percentage <= $grade ) {

                     $lesson_status = 'passed';

                 } else {

                     $lesson_status = 'failed';

                 } // End If Statement

             } else {

                 // Student only has to partake the quiz
                 $lesson_status = 'graded';

             }

             $lesson_metadata['grade'] = $grade; // Technically already set as part of "WooThemes_Sensei_Utils::sensei_grade_quiz_auto()" above

         } // end if ! is_wp_error( $grade ...

         Sensei_Utils::update_lesson_status( $user_id, $lesson_id, $lesson_status, $lesson_metadata );

         if( 'passed' == $lesson_status || 'graded' == $lesson_status ){

             /**
              * Lesson end action hook
              *
              * This hook is fired after a lesson quiz has been graded and the lesson status is 'passed' OR 'graded'
              *
              * @param int $user_id
              * @param int $lesson_id
              */
             do_action( 'sensei_user_lesson_end', $user_id, $lesson_id );

         }

         /**
          * User quiz has been submitted
          *
          * Fires the end of the submit_answers_for_grading function. It will fire irrespective of the submission
          * results.
          *
          * @param int $user_id
          * @param int $quiz_id
          * @param string $grade
          * @param string $quiz_pass_percentage
          * @param string $quiz_grade_type
          */
         do_action( 'sensei_user_quiz_submitted', $user_id, $quiz_id, $grade, $quiz_pass_percentage, $quiz_grade_type );

         return $answers_submitted;

     }// end submit_answers_for_grading

     /**
      * Get the user question answer
      *
      * This function gets the the users saved answer on given quiz for the given question parameter
      * this function allows for a fallback to users still using the question saved data from before 1.7.4
      *
      * @since 1.7.4
      *
      * @param int  $lesson_id
      * @param int $question_id
      * @param int  $user_id ( optional )
      *
      * @return bool|null $answers_submitted
      */
     public function get_user_question_answer( $lesson_id, $question_id, $user_id = 0 ){

         // parameter validation
         if( empty( $lesson_id ) || empty( $question_id )
             || ! ( intval( $lesson_id  ) > 0 )
             || ! ( intval( $question_id  ) > 0 )
             || 'lesson' != get_post_type( $lesson_id )
             || 'question' != get_post_type( $question_id )) {

             return false;
         }

         if( ! ( intval( $user_id ) > 0 )   ){
             $user_id = get_current_user_id();
         }

         $users_answers = $this->get_user_answers( $lesson_id, $user_id );

         if( !$users_answers || empty( $users_answers )
         ||  ! is_array( $users_answers ) || ! isset( $users_answers[ $question_id ] ) ){

             //Fallback for pre 1.7.4 data
             $comment =  Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $question_id, 'user_id' => $user_id, 'type' => 'sensei_user_answer' ), true );

             if( ! isset( $comment->comment_content ) ){
                 return NULL;
             }

             return maybe_unserialize( base64_decode( $comment->comment_content ) );
         }

         return $users_answers[ $question_id ];

     }// end get_user_question_answer

     /**
      * Saving the users quiz question grades
      *
      * This function save all the grades for all the question in a given quiz on the lesson
      * comment meta. It makes use of transients to save the grades for easier access at a later stage
      *
      * @since 1.7.4
      *
      * @param array $quiz_grades{
      *      @type int $question_id
      *      @type int $question_grade
      * }
      * @param $lesson_id
      * @param $user_id (Optional) will use the current user if not supplied
      *
      * @return bool
      */
     public function set_user_grades( $quiz_grades, $lesson_id, $user_id = 0 ){

         // get the user_id if none was passed in use the current logged in user
         if( ! intval( $user_id ) > 0 ) {
             $user_id = get_current_user_id();
         }

         // make sure the parameters are valid before continuing
         if( empty( $lesson_id ) || empty( $user_id )
             || 'lesson' != get_post_type( $lesson_id )
             ||!get_userdata( $user_id )
             || !is_array( $quiz_grades ) ){

             return false;

         }

         $success = false;

         // save that data for the user on the lesson comment meta
         $comment_meta_id = Sensei_Utils::add_user_data( 'quiz_grades', $lesson_id, $quiz_grades, $user_id   );

         // were the grades save successfully ?
         if( intval( $comment_meta_id ) > 0 ) {

             $success = true;
             // save transient
             $transient_key = 'quiz_grades_'. $user_id . '_' . $lesson_id;
             set_transient( $transient_key, $quiz_grades, 10 * DAY_IN_SECONDS );
         }

         return $success;

     }// end set_user_grades

     /**
      * Retrieve the users quiz question grades
      *
      * This function gets all the grades for all the questions in the given lesson quiz for a specific user.
      *
      * @since 1.7.4
      *
      * @param $lesson_id
      * @param $user_id (Optional) will use the current user if not supplied
      *
      * @return array $user_quiz_grades or false if none exists for this users
      */
     public function get_user_grades( $lesson_id, $user_id = 0 ){

         $user_grades = array();

         // get the user_id if none was passed in use the current logged in user
         if( ! intval( $user_id ) > 0 ) {
             $user_id = get_current_user_id();
         }

         if ( ! intval( $lesson_id ) > 0 || 'lesson' != get_post_type( $lesson_id )
             || ! intval( $user_id )  > 0 || !get_userdata( $user_id )  ) {
             return false;
         }

         // save some time and get the transient cached data
         $transient_key = 'quiz_grades_'. $user_id . '_' . $lesson_id;
         $user_grades = get_transient( $transient_key );

         // get the data if nothing was stored in the transient
         if( empty( $user_grades  ) || false != $user_grades ){

             $user_grades = Sensei_Utils::get_user_data( 'quiz_grades', $lesson_id, $user_id );

             //set the transient with the new valid data for faster retrieval in future
             set_transient( $transient_key,  $user_grades, 10 * DAY_IN_SECONDS );

         } // end if transient check

         // if there is no data for this user
         if( ! is_array( $user_grades ) ){
             return false;
         }

         return $user_grades;

     }// end  get_user_grades

     /**
      * Get the user question grade
      *
      * This function gets the grade on a quiz for the given question parameter
      * It does NOT do any grading. It simply retrieves the data that was stored during grading.
      * this function allows for a fallback to users still using the question saved data from before 1.7.4
      *
      * @since 1.7.4
      *
      * @param int  $lesson_id
      * @param int $question_id
      * @param int  $user_id ( optional )
      *
      * @return bool $question_grade
      */
     public function get_user_question_grade( $lesson_id, $question_id, $user_id = 0 ){

         // parameter validation
         if( empty( $lesson_id ) || empty( $question_id )
             || ! ( intval( $lesson_id  ) > 0 )
             || ! ( intval( $question_id  ) > 0 )
             || 'lesson' != get_post_type( $lesson_id )
             || 'question' != get_post_type( $question_id )) {

             return false;
         }

         $all_user_grades = self::get_user_grades( $lesson_id,$user_id );

         if( ! $all_user_grades || ! isset(  $all_user_grades[ $question_id ] ) ){

             //fallback to data pre 1.7.4
             $args = array(
                 'post_id' => $question_id,
                 'user_id' => $user_id,
                 'type'    => 'sensei_user_answer'
             );

             $question_activity = Sensei_Utils::sensei_check_for_activity( $args , true );
             $fall_back_grade = false;
             if( isset( $question_activity->comment_ID ) ){
                 $fall_back_grade = get_comment_meta(  $question_activity->comment_ID , 'user_grade', true );
             }

             return $fall_back_grade;

         } // end if $all_user_grades...

         return $all_user_grades[ $question_id ];

     }// end get_user_question_grade

     /**
      * Save the user's answers feedback
      *
      * For this function you must supply all three parameters. If will return false one is left out.
      * The data will be saved on the lesson ID supplied.
      *
      * @since 1.7.5
      * @access public
      *
      * @param array $answers_feedback{
      *  $type int $question_id
      *  $type string $question_feedback
      * }
      * @param int $lesson_id
      * @param int $user_id
      *
      * @return false or int $feedback_saved
      */
    public function save_user_answers_feedback( $answers_feedback, $lesson_id , $user_id = 0 ){

        // make sure the parameters are valid before continuing
        if( empty( $lesson_id ) || empty( $user_id )
            || 'lesson' != get_post_type( $lesson_id )
            ||!get_userdata( $user_id )
            || !is_array( $answers_feedback ) ){

            return false;

        }


        // check if the lesson is started before saving, if not start the lesson for the user
        if ( !( 0 < intval( Sensei_Utils::user_started_lesson( $lesson_id, $user_id) ) ) ) {
            Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );
        }

        // encode the feedback
        $encoded_answers_feedback =  array();
        foreach( $answers_feedback as $question_id => $feedback ){
            $encoded_answers_feedback[ $question_id ] = base64_encode( $feedback );
        }

        // save the user data
        $feedback_saved = Sensei_Utils::add_user_data( 'quiz_answers_feedback', $lesson_id , $encoded_answers_feedback, $user_id ) ;

        //Were the the question feedback save correctly?
        if( intval( $feedback_saved ) > 0){

            // save transient to make retrieval faster in future
             $transient_key = 'sensei_answers_feedback_'.$user_id.'_'.$lesson_id;
             set_transient( $transient_key, $encoded_answers_feedback, 10 * DAY_IN_SECONDS );

        }

        return $feedback_saved;

    } // end save_user_answers_feedback

     /**
      * Get the user's answers feedback.
      *
      * This function returns the feedback submitted by the teacher/admin
      * during grading. Grading occurs manually or automatically.
      *
      * @since 1.7.5
      * @access public
      *
      * @param int $lesson_id
      * @param int $user_id
      *
      * @return false | array $answers_feedback{
      *  $type int $question_id
      *  $type string $question_feedback
      * }
      */
     public function get_user_answers_feedback( $lesson_id , $user_id = 0 ){

         $answers_feedback = array();

         // get the user_id if none was passed in use the current logged in user
         if( ! intval( $user_id ) > 0 ) {
             $user_id = get_current_user_id();
         }

         if ( ! intval( $lesson_id ) > 0 || 'lesson' != get_post_type( $lesson_id )
             || ! intval( $user_id )  > 0 || !get_userdata( $user_id )  ) {
             return false;
         }

         // first check the transient to save a few split seconds
         $transient_key = 'sensei_answers_feedback_'.$user_id.'_'.$lesson_id;
         $encoded_feedback = get_transient( $transient_key );

         // get the data if nothing was stored in the transient
         if( empty( $encoded_feedback  ) || !$encoded_feedback ){

             $encoded_feedback = Sensei_Utils::get_user_data( 'quiz_answers_feedback', $lesson_id, $user_id );

             //set the transient with the new valid data for faster retrieval in future
             set_transient( $transient_key,  $encoded_feedback, 10 * DAY_IN_SECONDS);

         } // end if transient check

         // if there is no data for this user
         if( ! is_array( $encoded_feedback ) ){
             return false;
         }

         foreach( $encoded_feedback as $question_id => $feedback ){

             $answers_feedback[ $question_id ] = base64_decode( $feedback );

         }

         return $answers_feedback;

     } // end get_user_answers_feedback

     /**
      * Get the user's answer feedback for a specific question.
      *
      * This function gives you a single answer note/feedback string
      * for the user on the given question.
      *
      * @since 1.7.5
      * @access public
      *
      * @param int $lesson_id
      * @param int $question_id
      * @param int $user_id
      *
      * @return string $feedback or bool if false
      */
     public function get_user_question_feedback( $lesson_id, $question_id, $user_id = 0 ){

         $feedback = false;

         // parameter validation
         if( empty( $lesson_id ) || empty( $question_id )
             || ! ( intval( $lesson_id  ) > 0 )
             || ! ( intval( $question_id  ) > 0 )
             || 'lesson' != get_post_type( $lesson_id )
             || 'question' != get_post_type( $question_id )) {

             return false;
         }

         // get all the feedback for the user on the given lesson
         $all_feedback = $this->get_user_answers_feedback( $lesson_id, $user_id );

         if( !$all_feedback || empty( $all_feedback )
             || ! is_array( $all_feedback ) || ! isset( $all_feedback[ $question_id ] ) ){

             //fallback to data pre 1.7.4

             // setup the sensei data query
             $args = array(
                 'post_id' => $question_id,
                 'user_id' => $user_id,
                 'type'    => 'sensei_user_answer'
             );
             $question_activity = Sensei_Utils::sensei_check_for_activity( $args , true );

             // set the default to false and return that if no old data is available.
             if( isset( $question_activity->comment_ID ) ){
                 $feedback = base64_decode( get_comment_meta(  $question_activity->comment_ID , 'answer_note', true ) );
             }

             // finally use the default question feedback
             if( empty( $feedback ) ){
                 $feedback = get_post_meta( $question_id, '_answer_feedback', true );
             }

         } else {
            $feedback = $all_feedback[ $question_id ];
         }

         /**
          * Filter the user question feedback.
          *
          * @since 1.9.12
          * @param string $feedback
          * @param int    $lesson_id
          * @param int    $question_id
          * @param int    $user_id
          */
         return apply_filters( 'sensei_user_question_feedback', $feedback, $lesson_id, $question_id, $user_id );

     } // end get_user_question_feedback

     /**
      * Check if a quiz has no questions, and redirect back to lesson.
      *
      * Though a quiz is created for each lesson, it should not be visible
      * unless it has questions.
      *
      * @since 1.9.0
      * @access public
      * @param none
      * @return void
      */

     public function quiz_has_no_questions() {

         if( ! is_singular( 'quiz' ) )  {
             return;
         }

         global $post;

         $lesson_id = $this->get_lesson_id($post->ID);

         $has_questions = get_post_meta( $lesson_id, '_quiz_has_questions', true );

         $lesson = get_post($lesson_id);

         if ( is_singular('quiz') && ! $has_questions && $_SERVER['REQUEST_URI'] != "/lesson/$lesson->post_name" ) {

             wp_redirect(get_permalink($lesson->ID), 301);
             exit;

         }

     } // end quiz_has_no_questions

/**
  * Deprecate the sensei_single_main_content on the single-quiz template.
  *
  * @deprecated since 1.9.0
  */
 public static function deprecate_quiz_sensei_single_main_content_hook(){

     sensei_do_deprecated_action('sensei_single_main_content', '1.9.0', 'sensei_single_quiz_content_inside_before or sensei_single_quiz_content_inside_after');

 }
    /*
     * Deprecate the sensei_quiz_single_title on the single-quiz template.
     *
     * @deprecated since 1.9.0
     */
     public static function deprecate_quiz_sensei_quiz_single_title_hook(){

         sensei_do_deprecated_action('sensei_quiz_single_title', '1.9.0', 'sensei_single_quiz_content_inside_before ');

     }

     /**
      * Filter the single title and add the Quiz to it.
      *
      * @param string $title
      * @param int $post_id title post id
      *
      * @return string $quiz_title
      */
     public static function single_quiz_title( $title, $post_id = 0 ){

         if( 'quiz' == get_post_type( $post_id ) ){

             $title_with_no_quizzes = $title;

             // if the title has quiz, remove it: legacy titles have the word quiz stored.
             if( 1 < substr_count( strtoupper( $title_with_no_quizzes ), 'QUIZ' ) ){

                 // remove all possible appearances of quiz
                 $title_with_no_quizzes = str_replace( 'quiz', '', $title  );
                 $title_with_no_quizzes = str_replace( 'Quiz', '', $title_with_no_quizzes  );
                 $title_with_no_quizzes = str_replace( 'QUIZ', '', $title_with_no_quizzes  );

             }

             $title = sprintf( __( '%s Quiz', 'woothemes-sensei' ), $title_with_no_quizzes );
         }

         /**
          * hook document in class-woothemes-sensei-message.php
          */
         return apply_filters( 'sensei_single_title', $title, get_post_type( ) );

     }

     /**
      * Initialize the quiz question loop on the single quiz template
      *
      * The function will create a global quiz loop varialbe.
      *
      * @since 1.9.0
      *
      */
     public static function start_quiz_questions_loop(){

         global $sensei_question_loop;

         //intialize the questions loop object
         $sensei_question_loop['current'] = -1;
         $sensei_question_loop['total']   =  0;
         $sensei_question_loop['questions'] = array();


         $questions = Sensei()->lesson->lesson_quiz_questions( get_the_ID() );

         if( count( $questions  ) > 0  ){

             $sensei_question_loop['total']   =  count( $questions );
             $sensei_question_loop['questions'] = $questions;
             $sensei_question_loop['quiz_id'] = get_the_ID();

         }

     }// static function

     /**
      * Initialize the quiz question loop on the single quiz template
      *
      * The function will create a global quiz loop varialbe.
      *
      * @since 1.9.0
      *
      */
     public static function stop_quiz_questions_loop(){

         $sensei_question_loop['total']   =  0;
         $sensei_question_loop['questions'] = array();
         $sensei_question_loop['quiz_id'] = '';

     }

     /**
      * Output the title for the single quiz page
      *
      * @since 1.9.0
      */
     public static function the_title(){
         ?>
         <header>

             <h1>

                 <?php
                 /**
                  * Filter documented in class-sensei-messages.php the_title
                  */
                 echo apply_filters( 'sensei_single_title', get_the_title( get_post() ), get_post_type( get_the_ID() ) );
                 ?>

             </h1>

         </header>

         <?php
     }//the_title

     /**
      * Output the sensei quiz status message.
      *
      * @param $quiz_id
      */
    public static function  the_user_status_message( $quiz_id ){

        $lesson_id =  Sensei()->quiz->get_lesson_id( $quiz_id );
        $status = Sensei_Utils::sensei_user_quiz_status_message( $lesson_id , get_current_user_id() );
        $message = '<div class="sensei-message ' . $status['box_class'] . '">' . $status['message'] . '</div>';
        $messages = Sensei()->frontend->messages;

        if ( !empty( $messages ) ) {
          $message .= $messages;
        }

        echo $message;
    }

     /**
      * This functions runs the old sensei_quiz_action_buttons action
      * for backwards compatiblity sake.
      *
      * @since 1.9.0
      * @deprecated
      */
     public static function deprecate_sensei_quiz_action_buttons_hook(){

         sensei_do_deprecated_action( 'sensei_quiz_action_buttons', '1.9.0', 'sensei_single_quiz_questions_after');

     }

     /**
      * The quiz action buttons needed to ouput quiz
      * action such as reset complete and save.
      *
      * @since 1.3.0
      */
     public static function action_buttons() {

         global $post, $current_user;

	     $lesson_id = Sensei()->quiz->get_lesson_id( $post->ID );
         $lesson_course_id = (int) get_post_meta( $lesson_id, '_lesson_course', true );
         $lesson_prerequisite = (int) get_post_meta( $lesson_id, '_lesson_prerequisite', true );
         $show_actions = true;
         $user_lesson_status = Sensei_Utils::user_lesson_status( $lesson_id, $current_user->ID );

         //setup quiz grade
         $user_quiz_grade = '';
         if( ! empty( $user_lesson_status  ) ){

	         // user lesson status can return as an array.
	         if ( is_array( $user_lesson_status ) ) {
		         $comment_ID = $user_lesson_status[0]->comment_ID;

	         } else {
		         $comment_ID = $user_lesson_status->comment_ID;
	         }

	         $user_quiz_grade = get_comment_meta( $comment_ID, 'grade', true );
         }


         if( intval( $lesson_prerequisite ) > 0 ) {

             // If the user hasn't completed the prereq then hide the current actions
             $show_actions = Sensei_Utils::user_completed_lesson( $lesson_prerequisite, $current_user->ID );

         }
         if ( $show_actions && is_user_logged_in() && Sensei_Utils::user_started_course( $lesson_course_id, $current_user->ID ) ) {

             // Get Reset Settings
             $reset_quiz_allowed = get_post_meta( $post->ID, '_enable_quiz_reset', true ); ?>

             <!-- Action Nonce's -->
             <input type="hidden" name="woothemes_sensei_complete_quiz_nonce" id="woothemes_sensei_complete_quiz_nonce"
                    value="<?php echo esc_attr(  wp_create_nonce( 'woothemes_sensei_complete_quiz_nonce' ) ); ?>" />
             <input type="hidden" name="woothemes_sensei_reset_quiz_nonce" id="woothemes_sensei_reset_quiz_nonce"
                    value="<?php echo esc_attr(  wp_create_nonce( 'woothemes_sensei_reset_quiz_nonce' ) ); ?>" />
             <input type="hidden" name="woothemes_sensei_save_quiz_nonce" id="woothemes_sensei_save_quiz_nonce"
                    value="<?php echo esc_attr(  wp_create_nonce( 'woothemes_sensei_save_quiz_nonce' ) ); ?>" />
             <!--#end Action Nonce's -->

             <?php if ( '' == $user_quiz_grade && ( ! $user_lesson_status || 'ungraded' !== $user_lesson_status->comment_approved ) ) { ?>

                 <span><input type="submit" name="quiz_complete" class="quiz-submit complete" value="<?php  _e( 'Complete Quiz', 'woothemes-sensei' ); ?>"/></span>

                 <span><input type="submit" name="quiz_save" class="quiz-submit save" value="<?php _e( 'Save Quiz', 'woothemes-sensei' ); ?>"/></span>

             <?php } // End If Statement ?>

             <?php if ( isset( $reset_quiz_allowed ) && $reset_quiz_allowed ) { ?>

                 <span><input type="submit" name="quiz_reset" class="quiz-submit reset" value="<?php _e( 'Reset Quiz', 'woothemes-sensei' ); ?>"/></span>

             <?php } ?>

         <?php }

     } // End sensei_quiz_action_buttons()

     /**
      * Fetch the quiz grade
      *
      * @since 1.9.0
      *
      * @param int $lesson_id
      * @param int $user_id
      *
      * @return double $user_quiz_grade
      */
     public static function get_user_quiz_grade( $lesson_id, $user_id ){

         // get the quiz grade
         $user_lesson_status = Sensei_Utils::user_lesson_status( $lesson_id, $user_id );
         $user_quiz_grade = 0;
         if( isset( $user_lesson_status->comment_ID ) ) {
             $user_quiz_grade = get_comment_meta( $user_lesson_status->comment_ID, 'grade', true );
         }

         return (double) $user_quiz_grade;

     }

     /**
      * Check the quiz reset property for a given lesson's quiz.
      *
      * The data is stored on the quiz but going forward the quiz post
      * type will be retired, hence the lesson_id is a require parameter.
      *
      * @since 1.9.0
      *
      * @param int $lesson_id
      * @return bool
      */
     public static function is_reset_allowed( $lesson_id ){

         $quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_id );

         $reset_allowed = get_post_meta( $quiz_id, '_enable_quiz_reset', true );
         //backwards compatibility
         if( 'on' == $reset_allowed ) {
             $reset_allowed = 1;
         }

         return (bool) $reset_allowed;

     }

	 /**
	  * @param $lesson_id
	  *
	  * @return bool
	  */
	 public static function is_pass_required( $lesson_id ) {

		 $quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_id );

         $reset_allowed = get_post_meta( $quiz_id, '_pass_required', true );
         //backwards compatibility
         if( 'on' == $reset_allowed ) {
	         $reset_allowed = 1;
         }

         return (bool) $reset_allowed;
	 }

	 /**
	  * @since 1.9.5
	  *
	  * @param integer $post_id of the post being permanently deleted
	  */
	 public function maybe_delete_quiz( $post_id ){

		 $quiz_id = Sensei()->lesson->lesson_quizzes( $post_id );

		 if ( empty( $quiz_id ) || 'lesson' != get_post_type( $post_id ) ) {
			 return;
		 }

		 wp_delete_post( $quiz_id );


	 }

     /**
      * Merge quiz answers with questions asked
      *
      * Also, remove any question_ids not part of
      * the question set for this lesson quiz
      *
      * @param $post_global
      * @param $quiz_id
      * @return array
      */
     private function merge_quiz_answers_with_questions_asked( $post_global, $quiz_id )
     {
         $quiz_answers = isset( $post_global[ 'sensei_question' ] ) ? $post_global[ 'sensei_question' ] : array() ;
         $questions_asked_this_time = isset( $post_global['questions_asked'] ) ? $post_global['questions_asked'] : array();
         $merged = array();

         foreach ( array_unique( $questions_asked_this_time ) as $question_id ) {
             $merged[$question_id] = isset( $quiz_answers[$question_id] ) ? $quiz_answers[$question_id] : '';
         }

         return $merged;
     }

 } // End Class WooThemes_Sensei_Quiz



/**
 * Class WooThemes_Sensei_Quiz
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Quiz extends Sensei_Quiz{}
