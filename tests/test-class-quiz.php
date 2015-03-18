<?php
class Sensei_Class_Quiz_Test extends WP_UnitTestCase {

    /**
     * @var $factory
     */
    protected $factory;

    /**
     * Constructor function
     */
    public function __construct(){
        parent::__construct();
        include_once( 'factory/Sensei-Factory.php' );
    }

    /**
     * setup function
     *
     * This function sets up the lessons, quizes and their questions. This function runs before
     * every single test in this class
     */
    public function setup(){
        // load the factory class
        $this->factory = new Sensei_Factory();
    }// end function setup()

    /**
     *
     */
    public function tearDown(){

        // remove all lessons
        $lessons = get_posts( 'post_type=lesson' );
        foreach( $lessons as $index => $lesson ){
            wp_delete_post( $lesson->ID , true );
        }// end for each

        // remove all quizzes
        $quizzes = get_posts( 'post_type=quiz' );
        foreach( $quizzes as $index => $quiz ){
            wp_delete_post( $quiz->ID , true );
        }// end for each

    }// end tearDown

    /**
     * Testing the quiz class to make sure it is loaded
     */
    public function testClassInstance() {
        //setup the test
        global $woothemes_sensei;

        //test if the global sensei quiz class is loaded
        $this->assertTrue( isset( $woothemes_sensei->quiz ), 'Sensei quiz class is not loaded' );

    } // end testClassInstance

    /**
     * This test Woothemes_Sensei()->quiz->save_user_answers
     */
    public function testSaveUserAnswers(){

        // setup the data and objects needed for this test
        global $woothemes_sensei;
        $test_user_id = wp_create_user( 'student', 'student', 'student@test.com' );
        $test_lesson_id = $this->factory->get_random_lesson_id();
        $test_quiz_id = $woothemes_sensei->lesson->lesson_quizzes( $test_lesson_id );

        // does the save_user_answers function exist?
        $this->assertTrue( method_exists( $woothemes_sensei->quiz, 'save_user_answers'),
                            'The quiz class function `save_user_answers` does not exist ' );

        // does this save_user_answers return false for bogus data
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( array(), array() ,-1000, -200 ) , 'save_user_answers does not return false for no existent users and lesson ' );
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( '', array(), '' , '' ) , 'save_user_answers does not return false for empty parameters' );

        // does the function return the correct information when a user doesn't exist?
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( '' , array() , '', $test_lesson_id ) , 'save_user_answers does not return false for empty user' );
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( '' , array() ,  -500 ,  $test_lesson_id ) , 'save_user_answers does not return false for a non existant user' );

        // Test the answers_array parameter
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( 'Answers Text', array(), $test_lesson_id, $test_user_id ) , 'save_user_answers does not return false if answers is not passed in as an array' );
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( '' , array(), $test_lesson_id , $test_user_id  ) , 'save_user_answers does not return false for empty answer array' );
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( '', array(), '' , '' ) , 'save_user_answers does not return false incorrectly formatted answers' );

        add_filter( 'sensei_file_upload_args', 'testSaveUserAnswers_override_file_upload_args' );
        function testSaveUserAnswers_override_file_upload_args( $args ){
            $args['action'] = 'custom_testing_upload_function';
            return $args;
        }

        // Test a case that is setup correctly which should return a positive result
        $test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id  );
        WooThemes_Sensei_Utils::sensei_start_lesson( $test_lesson_id , $test_user_id  );
        $files = $this->factory->generate_test_files( $test_user_quiz_answers );
        $lesson_data_saved = $woothemes_sensei->quiz->save_user_answers( $test_user_quiz_answers, $files , $test_lesson_id  ,  $test_user_id  ) ;

        // did the correct data return a valid comment id on the lesson as a result?
        $this->assertTrue(  intval(  $lesson_data_saved ) > 0 , 'The comment id returned after saving the quiz answer does not represent a valid comment ' );

        //setup for the next group of assertions
        $sensei_activity_logged = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $test_lesson_id, 'user_id'=> $test_user_id ) );
        $status_comment = WooThemes_Sensei_Utils::user_lesson_status( $test_lesson_id, $test_user_id );
        $saved_data = get_comment_meta( $status_comment->comment_ID, 'quiz_answers', true );

        // was the data that was just stored stored correctly ? Check the comment meta on the lesson id
        $this->assertTrue( ( bool ) $sensei_activity_logged , 'The saved answers were not stored correctly on the Quiz');
        $this->assertFalse( empty($saved_data) , 'The saved answers were not stored correctly on the Quiz');
        $this->assertTrue( is_array( maybe_unserialize( $saved_data) ), 'The saved answers were not stored correctly on the Quiz');

        // can you retrieve data and is it the same as what was stored?
        //compare every single answer
        $retrieved_saved_array = maybe_unserialize( $saved_data );

        foreach( $test_user_quiz_answers as $question_id => $answer ){

            $type = $woothemes_sensei->question->get_question_type( $question_id );
            //if file skip it because  files going in comes out as attachment ids
            if( 'file-upload'== $type ){
                continue;
            }
            $saved_single_answer = $retrieved_saved_array[ $question_id ];
            $assert_message = 'The saved answer of type "'. strtoupper( $type )
                                . '" does not correspond to what was passed into the function';
            $this->assertEquals( $answer  , maybe_unserialize( base64_decode( $saved_single_answer ) ) ,
                $assert_message );

        }// end for each

        // was the files submitted uploaded and saved correctly?
        if( isset( $files ) && !empty( $files )   ) {
            $file_keys = array_keys($files);
            foreach ($file_keys as $key) {

                $question_id = str_replace('file_upload_', '', $key);
                $attachment_id = base64_decode($retrieved_saved_array[$question_id]);
                // make sure this is an attachment
                $image_location = get_attached_file($attachment_id, false);
                $this->assertFalse( empty($image_location), 'The ' . $files[ $key ][ 'name' ] . ' image was not attached');

            }// end for each $file_keys
        }

        // todo: the qustion types are too random. We need at least one of each. Sometimes files do not show up.
        // todo: was check if the data that was saved on the different quizzes are not the same
        //$activity_value = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $test_lesson_id, 'user_id'=> $test_user_id ) , true );

    } // end testSaveUserAnswers

    /**
     * This test is for Woothemes_Sensei()->quiz->save_user_answers. We check the transients only.
     */
    public function testSaveUserAnswersTransients(){

        // setup the data and objects needed for this test
        global $woothemes_sensei;
        $test_user_id = wp_create_user('studentTransients', 'transients', 'transients@test.com');
        $test_lesson_id = $this->factory->get_random_lesson_id();
        $test_quiz_id = $woothemes_sensei->lesson->lesson_quizzes($test_lesson_id);

        // generate and save the test data
        $test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
        WooThemes_Sensei_Utils::sensei_start_lesson( $test_lesson_id , $test_user_id  );
        $files = $this->factory->generate_test_files( $test_user_quiz_answers );
        $woothemes_sensei->quiz->save_user_answers( $test_user_quiz_answers, $files, $test_lesson_id, $test_user_id );
        $users_retrieved_answers = $woothemes_sensei->quiz->get_user_answers( $test_lesson_id, $test_user_id );

        // was it saved correctly?
        $transient_key = 'sensei_answers_'.$test_user_id.'_'.$test_lesson_id;
        $transient_val = get_site_transient( $transient_key );
        $decoded_transient_val = array();
        if( is_array( $transient_val ) ) {
            foreach ($transient_val as $question_id => $encoded_answer) {
                $decoded_transient_val[$question_id] = maybe_unserialize( base64_decode($encoded_answer) );
            }
        }

        $this->assertFalse( empty( $transient_val ) , 'Transients are not saved correctly for user answers ' );
        $this->assertEquals( $users_retrieved_answers ,$decoded_transient_val ,
            'The transient should be the same as the prepared answer which was base64 encoded' );

        // if saved again will the transient be updated
        $old_transient_value = $decoded_transient_val;
        $new_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
        $new_files = $this->factory->generate_test_files( $test_user_quiz_answers );
        $woothemes_sensei->quiz->save_user_answers( $test_user_quiz_answers, $files, $test_lesson_id, $test_user_id );
        $new_users_retrieved_answers = $woothemes_sensei->quiz->get_user_answers( $test_lesson_id, $test_user_id );

        $this->assertNotEquals( $old_transient_value, $new_users_retrieved_answers ,
            'Transient not updated on new save for the same user lesson combination' );

    } // end testSaveUserAnswersTransients

    /**
     * This test Woothemes_Sensei()->lesson->lesson_quizzes( $lesson_id )
     */
    public function testGetQuizId(){

        global $woothemes_sensei;
        // save the user answers
        $this->assertTrue( method_exists( $woothemes_sensei->lesson, 'lesson_quizzes'),
            'The lesson class function `lesson_quizzes` does not exist ' );

        // test with an invalid lesson id
        $invalid_lesson_quiz_id = $woothemes_sensei->lesson->lesson_quizzes(-13333);
        $this->assertTrue( 0 == $invalid_lesson_quiz_id, 'Get quiz id does not return Zero for an invalid lesson ID'  );

        //test with a valid lesson that has a quiz
        // the get_random_lesson_id gets a lesson that is already setup with quizzes and questions
        $valid_lesson_quiz_id = $woothemes_sensei->lesson->lesson_quizzes( $this->factory->get_random_lesson_id() );
        $this->assertTrue( $valid_lesson_quiz_id > 0 , 'Get quiz id should return a valid quiz id if a valid  lesson ID is passed in'  );

    }// end testGetQuizId

    /**
     * This test Woothemes_Sensei()->quiz->get_user_answers
     */
    function testGetUserAnswers(){

        global $woothemes_sensei;

        // make sure the function exists
        $this->assertTrue( method_exists( $woothemes_sensei->quiz, 'get_user_answers'),
            'The quiz class function `get_user_answers` does not exist ' );

        // create a user
        $test_user_id = wp_create_user( 'StudentTest', 'samplestudent', 'samplestudent@test.com' );
        $this->assertTrue( intval( $test_user_id ) > 0 && get_userdata( $test_user_id ) ,
            'WordPress did not give us a valid user id.' );

        // get a lesson and assign the user to the lesson
        $test_lesson_id = $this->factory->get_random_lesson_id();
        $this->assertTrue( intval( $test_lesson_id ) > 0 && 'lesson' == get_post_type( $test_lesson_id )  ,
            'The random lesson id need for the rest of this test is not a valid lesson' );

        //get the lessons quiz
        $test_lesson_quiz_id = $woothemes_sensei->lesson->lesson_quizzes( $test_lesson_id );
        $this->assertTrue( intval( $test_lesson_quiz_id ) > 0 && 'quiz' == get_post_type( $test_lesson_quiz_id )  ,
            'The random lesson id need for the rest of this test is not a valid lesson.' );

        // get the quiz questions
        $quiz_question_posts = $woothemes_sensei->lesson->lesson_quiz_questions( $test_lesson_quiz_id  );
        $this->assertTrue( is_array( $quiz_question_posts )
            && isset( $quiz_question_posts[ 0 ] ) && isset( $quiz_question_posts[ 0 ]->ID )
            && 'question' == get_post_type( $quiz_question_posts[ 0 ]->ID ) ,
            'The quiz questions for quiz_id: ' . $test_lesson_quiz_id . ' does not exist or is not returned as expected.'  );

        // create the sample data to save
        $user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_lesson_quiz_id  );

        // assign the user to the lesson
        WooThemes_Sensei_Utils::sensei_start_lesson( $test_lesson_id, $test_user_id  );

        // test for when there is no answers saved.
        $is_false_when_no_answers_saved = $woothemes_sensei->quiz->get_user_answers( $test_lesson_id, $test_user_id);
        $this->assertFalse(  $is_false_when_no_answers_saved  , 'The function should return false when no answers are saved on the Lesson' );

        // save the test users answers on the tes lesson
        $lesson_data_saved = $woothemes_sensei->quiz->save_user_answers( $user_quiz_answers, array() ,$test_lesson_id,  $test_user_id  ) ;
        $this->assertTrue(  intval(  $lesson_data_saved ) > 0, 'The comment id returned after saving the quiz answer does not represent a valid comment ' );

        // test the function with the wrong parameters
        $result_for_invalid_user = $woothemes_sensei->quiz->get_user_answers('', $test_user_id);
        $this->assertFalse(  $result_for_invalid_user , 'The function should return false for and invalid lesson id' );

        $result_invalid_lesson = $woothemes_sensei->quiz->get_user_answers($test_lesson_id, '');
        $this->assertFalse( $result_invalid_lesson, 'The function should return false for and invalid user id' );

        // test with the correct parameters
        $user_saved_lesson_answers = $woothemes_sensei->quiz->get_user_answers($test_lesson_id, $test_user_id);
        $this->assertTrue( is_array( $user_saved_lesson_answers ), 'The function should return an array when an exiting user and lesson with saved answers is passed in' );

        // check all the answers returned
        foreach( $user_saved_lesson_answers as $question_id => $answer ) {
            // test if the returned questions relate to valid question post types
            $this->assertTrue( 'question' == get_post_type( $question_id )  , 'The answers returned  does not relate to valid question post types');
            // make sure it is the same as the saved answers
            $this->assertTrue( $user_quiz_answers[$question_id] == $user_saved_lesson_answers[$question_id]   , 'The answers returned are not the same as the answers saved');

        }

    } // end testGetUserAnswers


    /**
     * This test Woothemes_Sensei()->quiz->reset_user_saved_answers( $lesson_id, $user_id )
     */
    public function testResetQuizSavedAnswers(){

        // setup globals for access by this method
        global $woothemes_sensei;
        $test_lesson_id = $this->factory->get_random_lesson_id();
        $test_quiz_id = $woothemes_sensei->lesson->lesson_quizzes( $test_lesson_id );
        // save the user answers
        $this->assertTrue( method_exists( $woothemes_sensei->quiz, 'reset_user_saved_answers'),
            'The quiz class method `reset_user_saved_answers` does not exist ' );

        $test_user_id = wp_create_user( 'testUserReset', '$%##$#', 'test@reset.users' );

        // test the function with the wrong parameters
        $result_for_empty_lesson_id = $woothemes_sensei->quiz->reset_user_saved_answers('', $test_user_id);
        $this->assertFalse(  $result_for_empty_lesson_id , 'The function should return false for an empty lesson id.' );

        $result_for_invalid_lesson_id = $woothemes_sensei->quiz->reset_user_saved_answers(-4000 , $test_user_id);
        $this->assertFalse(  $result_for_invalid_lesson_id , 'The function should return false for an invalid lesson id.' );

        $result_for_empty_user_id = $woothemes_sensei->quiz->reset_user_saved_answers( $this->factory->get_random_lesson_id() , '');
        $this->assertFalse(  $result_for_empty_user_id , 'The function should return false for an empty user id.' );

        $result_for_invalid_user_id = $woothemes_sensei->quiz->reset_user_saved_answers( $this->factory->get_random_lesson_id() , -500 );
        $this->assertFalse(  $result_for_invalid_user_id , 'The function should return false for an invalid user id.' );

        // test for a valid user and lesson that has no lesson_status comment on the lesson
        $valid_parameters_for_user_with_no_lesson_status =
            $woothemes_sensei->quiz->reset_user_saved_answers( $test_lesson_id , $test_user_id );
        $this->assertFalse(  $valid_parameters_for_user_with_no_lesson_status ,
            'The function should return false if the user that has no lesson status data stored' );
        // test for a valid user and lesson that has a sensei_lesson_status comment by this user
        $user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
        WooThemes_Sensei_Utils::sensei_start_lesson( $test_lesson_id , $test_user_id  );
        $lesson_data_saved = $woothemes_sensei->quiz->save_user_answers( $user_quiz_answers, array(), $test_lesson_id,  $test_user_id  ) ;
        $this->assertTrue(  intval( $lesson_data_saved ) > 0  ,
            'The lesson quiz answers was not saved' );
        $lesson_data_reset = $woothemes_sensei->quiz->reset_user_saved_answers( $test_lesson_id,  $test_user_id  ) ;
        $this->assertTrue($lesson_data_reset  , 'The lesson data was not reset for a valid use case'  );

    }// end testGetQuizId

    /**
     * This tests Woothemes_Sensei()->quiz->prepare_form_submitted_answers
     */
    public function testPrepareFormSubmittedAnswers(){
        global $woothemes_sensei;

        // make sure the method is in the class before we proceed
        $this->assertTrue( method_exists ( $woothemes_sensei->quiz,'prepare_form_submitted_answers' ),
            'The prepare_form_submitted_answers method is not in class WooThemes_Sensei_Quiz' );

        //does it return false for empty and non array parameters
        $this->assertFalse( $woothemes_sensei->quiz->prepare_form_submitted_answers('', '' ) ,
            'prepare_form_submitted_answers should return false for a non array parameter ' );

        //setup valid data
        $test_lesson_id = $this->factory->get_random_lesson_id();
        $test_quiz_id = $woothemes_sensei->lesson->lesson_quizzes( $test_lesson_id );
        $test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id  );
        $files = $this->factory->generate_test_files( $test_user_quiz_answers );

        // setup for the next group of assertions
        //setup this function to override the arguments passed to WordPress upload function
        add_filter( 'sensei_file_upload_args', 'unit_test_override_sensei_file_upload_args' );
        function unit_test_override_sensei_file_upload_args( $args ){
            $args['action'] = 'custom_testing_upload_function';
            return $args;
        }

        // for the valid data does it return an array ?

        $prepared_test_data = $woothemes_sensei->quiz->prepare_form_submitted_answers( $test_user_quiz_answers , $files );
        $this->assertTrue( is_array( $prepared_test_data ) ,
        'function function does not return an array for valid parameters' );
        $this->assertTrue( count( $prepared_test_data ) == count( $test_user_quiz_answers ) ,
            'function does not return the same number of items that was passed in' );
        $this->assertTrue( array_keys( $prepared_test_data ) == array_keys( $test_user_quiz_answers ) ,
            'function does not return the same array keys( question ids ) that was passed in' );

        /**
         * For valid data, is the answers in the array returned the same as the values passed in
         */

        // testing non file questions
        $random_index = $this->factory->get_random_none_file_question_index(  $prepared_test_data  );
        $input_array_sample_element_val = $test_user_quiz_answers[$random_index];
        $output_array_sample_element_val =  maybe_unserialize( base64_decode(  $prepared_test_data[ $random_index ] ));
        $question_type = $woothemes_sensei->question->get_question_type( $random_index );
        $test_message = 'The function changes the array values so much that they are not the same as when passed in. ';
        $test_message .= 'We inspected a random answer saved for the "' . strtoupper( $question_type ) . '" question type' ;
        $this->assertEquals( $input_array_sample_element_val, $output_array_sample_element_val ,
           $test_message  );

        // testing file type questions
        if( isset( $files ) && !empty( $files ) ) {
            $random_file_index = $this->factory->get_random_file_question_index( $prepared_test_data );
            $file_answer =   $prepared_test_data[ $random_file_index ];
            $this->assertFalse( empty( $file_answer ),
            'The file type question returns nothing, it should return an attachment ID');
        }
    }// end testPrepareFormSubmittedAnswers()

    /**
     * This tests Woothemes_Sensei()->quiz->submit_answers_for_grading
     */
    public function testSubmitAnswersForGrading(){

        //setup test data
        global $woothemes_sensei;
        $test_lesson_id = $this->factory->get_random_lesson_id();
        $test_quiz_id = $woothemes_sensei->lesson->lesson_quizzes( $test_lesson_id );
        $test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id  );
        $files = $this->factory->generate_test_files( $test_user_quiz_answers );

        // remove the hooks within the submit function to avoid side effects
        remove_all_actions( 'sensei_user_quiz_submitted' );
        remove_all_actions( 'sensei_user_lesson_end' );

        $test_user_id = wp_create_user( 'student_submitting', 'student_submitting', 'student_submiting@test.com' );

        // make sure the function exists
        $this->assertTrue( method_exists( 'WooThemes_Sensei_Quiz', 'submit_answers_for_grading'  ) ,
                            'The method submit_answers_for_grading does not exist ');

        // Doest this function return false for bogus data?
        $this->assertFalse( WooThemes_Sensei_Quiz::submit_answers_for_grading('', '' ,'','' ),
                            'The function should return false for the wrong parameters' );
        $this->assertFalse( WooThemes_Sensei_Quiz::submit_answers_for_grading('-100',array(), '-1000','-90909' ),
            'The function should return false for the wrong parameters' );
        $this->assertFalse( WooThemes_Sensei_Quiz::submit_answers_for_grading( array(),array(), '20000','30000' ),
            'The function should return false for the wrong parameters' );

        // Doest this function return true for valid data?
        $result_for_valid_data =  WooThemes_Sensei_Quiz::submit_answers_for_grading( $test_user_quiz_answers, $files,
                                                                                $test_lesson_id , $test_user_id );
        $this->assertTrue( $result_for_valid_data ,
            'The function should return true for valid parameters' );

        // todo: setup quizzes that can be autograded and create a function that can get auto and manual randoms

    }// end testSubmittedAnswersForGrading

}// end class Sensei_Class_Quiz_Test