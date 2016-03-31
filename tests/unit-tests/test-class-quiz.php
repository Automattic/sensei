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

        // override the default upload to ensure file upload tests pass
        add_filter( 'sensei_file_upload_args', 'testSaveUserAnswers_override_file_upload_args' );
        if( ! function_exists( 'testSaveUserAnswers_override_file_upload_args'  ) ) {
            function testSaveUserAnswers_override_file_upload_args($args){
                $args['action'] = 'custom_testing_upload_function';
                return $args;
            }
        }

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


        //test if the global sensei quiz class is loaded
        $this->assertTrue( isset( Sensei()->quiz ), 'Sensei quiz class is not loaded' );

    } // end testClassInstance

    /**
     * This test Woothemes_Sensei()->quiz->save_user_answers
     */
    public function testSaveUserAnswers(){

        // setup the data and objects needed for this test

        $test_user_id = wp_create_user( 'student', 'student', 'student@test.com' );
        $test_lesson_id = $this->factory->get_random_lesson_id();
        $test_quiz_id = Sensei()->lesson->lesson_quizzes( $test_lesson_id );

        // does the save_user_answers function exist?
        $this->assertTrue( method_exists( Sensei()->quiz, 'save_user_answers'),
                            'The quiz class function `save_user_answers` does not exist ' );

        // does this save_user_answers return false for bogus data
        $this->assertFalse(  Sensei()->quiz->save_user_answers( array(), array() ,-1000, -200 ) , 'save_user_answers does not return false for no existent users and lesson ' );
        $this->assertFalse(  Sensei()->quiz->save_user_answers( '', array(), '' , '' ) , 'save_user_answers does not return false for empty parameters' );

        // does the function return the correct information when a user doesn't exist?
        $this->assertFalse(  Sensei()->quiz->save_user_answers( '' , array() , '', $test_lesson_id ) , 'save_user_answers does not return false for empty user' );
        $this->assertFalse(  Sensei()->quiz->save_user_answers( '' , array() ,  -500 ,  $test_lesson_id ) , 'save_user_answers does not return false for a non existant user' );

        // Test the answers_array parameter
        $this->assertFalse(  Sensei()->quiz->save_user_answers( 'Answers Text', array(), $test_lesson_id, $test_user_id ) , 'save_user_answers does not return false if answers is not passed in as an array' );
        $this->assertFalse(  Sensei()->quiz->save_user_answers( '' , array(), $test_lesson_id , $test_user_id  ) , 'save_user_answers does not return false for empty answer array' );
        $this->assertFalse(  Sensei()->quiz->save_user_answers( '', array(), '' , '' ) , 'save_user_answers does not return false incorrectly formatted answers' );


        // Test a case that is setup correctly which should return a positive result
        $test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id  );
        Sensei_Utils::sensei_start_lesson( $test_lesson_id , $test_user_id  );
        $files = $this->factory->generate_test_files( $test_user_quiz_answers );
        $lesson_data_saved = Sensei()->quiz->save_user_answers( $test_user_quiz_answers, $files , $test_lesson_id  ,  $test_user_id  ) ;

        // did the correct data return a valid comment id on the lesson as a result?
        $this->assertTrue(  intval(  $lesson_data_saved ) > 0 , 'The comment id returned after saving the quiz answer does not represent a valid comment ' );

        //setup for the next group of assertions
        $sensei_activity_logged = Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $test_lesson_id, 'user_id'=> $test_user_id ) );
        $status_comment = Sensei_Utils::user_lesson_status( $test_lesson_id, $test_user_id );
        $saved_data = get_comment_meta( $status_comment->comment_ID, 'quiz_answers', true );

        // was the data that was just stored stored correctly ? Check the comment meta on the lesson id
        $this->assertTrue( ( bool ) $sensei_activity_logged , 'The saved answers were not stored correctly on the Quiz');
        $this->assertFalse( empty($saved_data) , 'The saved answers were not stored correctly on the Quiz');
        $this->assertTrue( is_array( maybe_unserialize( $saved_data) ), 'The saved answers were not stored correctly on the Quiz');

        // can you retrieve data and is it the same as what was stored?
        //compare every single answer
        $retrieved_saved_array = maybe_unserialize( $saved_data );

        foreach( $test_user_quiz_answers as $question_id => $answer ){

            $type = Sensei()->question->get_question_type( $question_id );
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

    } // end testSaveUserAnswers

    /**
     * This test Woothemes_Sensei()->quiz->save_user_answers
     *
     * The test confirms that a user can have unique answers for the same question in different lesson quizzes.
     *
     * @ticket 618 ( GitHub issue)
     */
    public function testSaveUserAnswersUniquelyPerQuiz(){

        // setup data for the tests assertions
        $test_user_id = wp_create_user( 'UniquelyPerQuiz' , 'UniquelyPerQuiz','UniquelyPerQuiz@test-unique.com' );
        $test_lessons = $this->factory->get_random_lesson_id( 3 );
        $lesson_1 = $test_lessons[ 0 ];
        $lesson_2 = $test_lessons[ 1 ];
        $lesson_3 = $test_lessons[ 2 ];

        $lesson_1_quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_1 );
        $lesson_2_quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_2 );
        $lesson_3_quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_3 );

        $test_question_data = array(
            'question_type' => 'single-line' ,
            'question_category' => 'undefined' ,
            'action' => 'add',
            'question' => 'Is this a sample' . 'single-line'  . ' question ? _ ' . rand() ,
            'question_grade' => '1' ,
            'answer_feedback' => 'Answer Feedback sample ' . rand() ,
            'question_description' => ' Basic description for the question' ,
            'question_media' => '' ,
            'answer_order' => '' ,
            'random_order' => 'yes' ,
            'question_count' => 1,
            'add_question_right_answer_singleline' => '',
            'quiz_id' => $lesson_1_quiz_id,
            'post_author' => 1
        );

        // add question to the the first quiz
        $test_question_id = Sensei()->lesson->lesson_save_question( $test_question_data );

        // add question to quiz 2
        add_post_meta( $test_question_id, '_quiz_id', $lesson_2_quiz_id , false );
        $question_order = $lesson_2_quiz_id . '0001' ;
        add_post_meta( $test_question_id, '_quiz_question_order' . $lesson_2_quiz_id , $question_order );

        // add question to quiz 3
        add_post_meta( $test_question_id, '_quiz_id', $lesson_3_quiz_id , false );
        $question_order = $lesson_3_quiz_id . '0001' ;
        add_post_meta( $test_question_id, '_quiz_question_order' . $lesson_3_quiz_id , $question_order );

        //create sample answer array and save it on each lesson
        foreach ( $test_lessons as $lesson_id ){
            $answers = array( $test_question_id => 'Sample Answer for lesson '. $lesson_id  );
            Sensei()->quiz->save_user_answers( $answers, array(), $lesson_id, $test_user_id  );
        }

        //check if the answers are not the same
        $answer_from_lesson_1 = Sensei()->quiz->get_user_question_answer( $lesson_1  , $test_question_id , $test_user_id  );
        $answer_from_lesson_2 = Sensei()->quiz->get_user_question_answer( $lesson_2  , $test_question_id , $test_user_id  );
        $answer_from_lesson_3 = Sensei()->quiz->get_user_question_answer( $lesson_3  , $test_question_id , $test_user_id  );

        $answers_the_same = $answer_from_lesson_1 == $answer_from_lesson_2
                            && $answer_from_lesson_2 == $answer_from_lesson_3
                            && $answer_from_lesson_1 == $answer_from_lesson_3;

        $this->assertFalse( $answers_the_same, 'The unique answer saved by one user for the same question on different lessons was not really saved uniquely.' );

    }// en testSaveUserAnswersUniquelyPerQuiz

    /**
     * This test is for Woothemes_Sensei()->quiz->save_user_answers. We check the transients only.
     *
     * @group transient
     */
    public function testSaveUserAnswersTransients(){

        // setup the data and objects needed for this test

        $test_user_id = wp_create_user('studentTransients', 'transients', 'transients@test.com');
        $test_lesson_id = $this->factory->get_random_lesson_id();
        $test_quiz_id = Sensei()->lesson->lesson_quizzes($test_lesson_id);

        // generate and save the test data
        $test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
        Sensei_Utils::sensei_start_lesson( $test_lesson_id , $test_user_id  );
        $files = $this->factory->generate_test_files( $test_user_quiz_answers );
        Sensei()->quiz->save_user_answers( $test_user_quiz_answers, $files, $test_lesson_id, $test_user_id );
        $users_retrieved_answers = Sensei()->quiz->get_user_answers( $test_lesson_id, $test_user_id );

        // was it saved correctly?
        $transient_key = 'sensei_answers_'.$test_user_id.'_'.$test_lesson_id;
        $transient_val = get_transient( $transient_key );
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
        Sensei()->quiz->save_user_answers( $new_answers, $new_files, $test_lesson_id, $test_user_id );
        $new_users_retrieved_answers = Sensei()->quiz->get_user_answers( $test_lesson_id, $test_user_id );

        $this->assertNotEquals( $old_transient_value, $new_users_retrieved_answers ,
            'Transient not updated on new save for the same user lesson combination' );

    } // end testSaveUserAnswersTransients

    /**
     * This test Woothemes_Sensei()->quiz->get_user_answers transients only
     *
     * @group transient
     */
    function testGetUserAnswersTransient(){

        // setup the test data
        $test_user_id = wp_create_user('studentTransientsGet', 'transientsGet', 'transientsGet@test.com');
        $test_lesson_id = $this->factory->get_random_lesson_id();
        $transient_key = 'sensei_answers_'.$test_user_id.'_'.$test_lesson_id;
        $transient_get_test = array( base64_encode( 'transientGetTest' )  );
        $transient_get_test_decoded = array( 'transientGetTest' );
        set_transient( $transient_key, $transient_get_test, 10 * DAY_IN_SECONDS );
        $users_retrieved_answers = Sensei()->quiz->get_user_answers( $test_lesson_id, $test_user_id );

        // test if the answer is taken from the transient
        $this->assertEquals( $transient_get_test_decoded , $users_retrieved_answers ,
            'The transient was not used before proceeding to get the users answers from DB' );

        //setup next assertion
        $test_quiz_id = Sensei()->lesson->lesson_quizzes($test_lesson_id);
        $test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
        Sensei_Utils::sensei_start_lesson( $test_lesson_id , $test_user_id  );
        $files = $this->factory->generate_test_files( $test_user_quiz_answers );
        Sensei()->quiz->save_user_answers( $test_user_quiz_answers, $files, $test_lesson_id, $test_user_id );
        delete_site_transient( $transient_key );
        Sensei()->quiz->get_user_answers( $test_lesson_id, $test_user_id );
        $transient_data_after_retrieval = get_transient( $transient_key );

        // test if a transient is created when one does not exist
        // in this test we first delete the transient after it is been added in the save_user_answers
        // function above, then we get the data again and test if the function added the transient
        $this->assertNotFalse( $transient_data_after_retrieval,
                ' The get_user_answers function does not set the transient after retrieving the data ');

        // make sure the one of the keys passed in is in the transient
        $random_key = array_rand( $test_user_quiz_answers  );
        $this->assertArrayHasKey( $random_key , $transient_data_after_retrieval  ,
            'The transient does not contain the same elements that we passed in' );

        //make sure the number of elements passes in is the same as what is in the new transient cache
        $this->assertEquals( count( $test_user_quiz_answers ), count( $transient_data_after_retrieval ),
            'The number of elements in the transient does not match those the user submitted');
    } // testGetUserAnswersTransient

    /**
     * This test Woothemes_Sensei()->lesson->lesson_quizzes( $lesson_id )
     */
    public function testGetQuizId(){

	    // save the user answers
        $this->assertTrue( method_exists( Sensei()->lesson, 'lesson_quizzes'),
            'The lesson class function `lesson_quizzes` does not exist ' );

        // test with an invalid lesson id
        $invalid_lesson_quiz_id = Sensei()->lesson->lesson_quizzes(-13333);
        $this->assertTrue( 0 == $invalid_lesson_quiz_id, 'Get quiz id does not return Zero for an invalid lesson ID'  );

        //test with a valid lesson that has a quiz
        // the get_random_lesson_id gets a lesson that is already setup with quizzes and questions
        $valid_lesson_quiz_id = Sensei()->lesson->lesson_quizzes( $this->factory->get_random_lesson_id() );
        $this->assertTrue( $valid_lesson_quiz_id > 0 , 'Get quiz id should return a valid quiz id if a valid  lesson ID is passed in'  );

    }// end testGetQuizId

    /**
     * This test Woothemes_Sensei()->quiz->get_user_answers
     */
    function testGetUserAnswers(){

		// make sure the function exists
        $this->assertTrue( method_exists( Sensei()->quiz, 'get_user_answers'),
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
        $test_lesson_quiz_id = Sensei()->lesson->lesson_quizzes( $test_lesson_id );
        $this->assertTrue( intval( $test_lesson_quiz_id ) > 0 && 'quiz' == get_post_type( $test_lesson_quiz_id )  ,
            'The random lesson id need for the rest of this test is not a valid lesson.' );

        // get the quiz questions
        $quiz_question_posts = Sensei()->lesson->lesson_quiz_questions( $test_lesson_quiz_id  );
        $this->assertTrue( is_array( $quiz_question_posts )
            && isset( $quiz_question_posts[ 0 ] ) && isset( $quiz_question_posts[ 0 ]->ID )
            && 'question' == get_post_type( $quiz_question_posts[ 0 ]->ID ) ,
            'The quiz questions for quiz_id: ' . $test_lesson_quiz_id . ' does not exist or is not returned as expected.'  );

        // create the sample data to save
        $user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_lesson_quiz_id  );

        // assign the user to the lesson
        Sensei_Utils::sensei_start_lesson( $test_lesson_id, $test_user_id  );

        // test for when there is no answers saved.
        $is_false_when_no_answers_saved = Sensei()->quiz->get_user_answers( $test_lesson_id, $test_user_id);
        $this->assertFalse(  $is_false_when_no_answers_saved  , 'The function should return false when no answers are saved on the Lesson' );

        // save the test users answers on the tes lesson
        $lesson_data_saved = Sensei()->quiz->save_user_answers( $user_quiz_answers, array() ,$test_lesson_id,  $test_user_id  ) ;
        $this->assertTrue(  intval(  $lesson_data_saved ) > 0, 'The comment id returned after saving the quiz answer does not represent a valid comment ' );

        // test the function with the wrong parameters
        $result_for_invalid_user = Sensei()->quiz->get_user_answers('', $test_user_id);
        $this->assertFalse(  $result_for_invalid_user , 'The function should return false for and invalid lesson id' );

        $result_invalid_lesson = Sensei()->quiz->get_user_answers($test_lesson_id, '');
        $this->assertFalse( $result_invalid_lesson, 'The function should return false for and invalid user id' );

        // test with the correct parameters
        $user_saved_lesson_answers = Sensei()->quiz->get_user_answers($test_lesson_id, $test_user_id);
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
     * This test Woothemes_Sensei()->quiz->reset_user_lesson_data( $lesson_id, $user_id )
     *
     * @group transient
     */
    public function testResetUserLessonData(){

        // setup globals for access by this method
        $test_lesson_id = $this->factory->get_random_lesson_id();
        $test_quiz_id = Sensei()->lesson->lesson_quizzes( $test_lesson_id );
        // save the user answers
        $this->assertTrue( method_exists( Sensei()->quiz, 'reset_user_lesson_data'),
            'The quiz class method `reset_user_saved_answers` does not exist ' );

        $test_user_id = wp_create_user( 'testUserReset', '$%##$#', 'test@reset.users' );

        // test the function with the wrong parameters
        $result_for_empty_lesson_id = Sensei()->quiz->reset_user_lesson_data('', $test_user_id);
        $this->assertFalse(  $result_for_empty_lesson_id , 'The function should return false for an empty lesson id.' );

        $result_for_invalid_lesson_id = Sensei()->quiz->reset_user_lesson_data(-4000 , $test_user_id);
        $this->assertFalse(  $result_for_invalid_lesson_id , 'The function should return false for an invalid lesson id.' );

        $result_for_empty_user_id = Sensei()->quiz->reset_user_lesson_data( $this->factory->get_random_lesson_id() , '');
        $this->assertFalse(  $result_for_empty_user_id , 'The function should return false for an empty user id.' );

        $result_for_invalid_user_id = Sensei()->quiz->reset_user_lesson_data( $this->factory->get_random_lesson_id() , -500 );
        $this->assertFalse(  $result_for_invalid_user_id , 'The function should return false for an invalid user id.' );

        // test for a valid user and lesson that has no lesson_status comment on the lesson
        $valid_parameters_for_user_with_no_lesson_status =
            Sensei()->quiz->reset_user_lesson_data( $test_lesson_id , $test_user_id );
        $this->assertFalse(  $valid_parameters_for_user_with_no_lesson_status ,
            'The function should return false if the user that has no lesson status data stored' );

        // test for a valid user and lesson that has a sensei_lesson_status comment by this user
        $user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
        $user_quiz_grades = $this->factory->generate_user_quiz_grades( $user_quiz_answers );
        Sensei_Utils::sensei_start_lesson( $test_lesson_id , $test_user_id  );
        Sensei()->quiz->save_user_answers( $user_quiz_answers, array(), $test_lesson_id,  $test_user_id  ) ;
        Sensei()->quiz->set_user_grades( $user_quiz_grades, $test_lesson_id,  $test_user_id );

        // was the lesson data reset?
        $lesson_data_reset = Sensei()->quiz->reset_user_lesson_data( $test_lesson_id,  $test_user_id  ) ;
        $this->assertTrue( $lesson_data_reset  , 'The lesson data was not reset for a valid use case'  );

        //make sure transients are remove as well
        $transient_key = 'sensei_answers_'.$test_user_id.'_'.$test_lesson_id;
        $transient_data  = get_transient( $transient_key );
        $this->assertFalse( $transient_data, 'The transient was not reset along with the users saved data. The result should be false.'  );

    }// end testResetUserLessonData

    /**
     * This tests Woothemes_Sensei()->quiz->prepare_form_submitted_answers
     */
    public function testPrepareFormSubmittedAnswers(){


        // make sure the method is in the class before we proceed
        $this->assertTrue( method_exists ( Sensei()->quiz,'prepare_form_submitted_answers' ),
            'The prepare_form_submitted_answers method is not in class WooThemes_Sensei_Quiz' );

        //does it return false for empty and non array parameters
        $this->assertFalse( Sensei()->quiz->prepare_form_submitted_answers('', '' ) ,
            'prepare_form_submitted_answers should return false for a non array parameter ' );

        //setup valid data
        $test_lesson_id = $this->factory->get_random_lesson_id();
        $test_quiz_id = Sensei()->lesson->lesson_quizzes( $test_lesson_id );
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

        $prepared_test_data = Sensei()->quiz->prepare_form_submitted_answers( $test_user_quiz_answers , $files );
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
        $question_type = Sensei()->question->get_question_type( $random_index );
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

        $test_lesson_id = $this->factory->get_random_lesson_id();
        $test_quiz_id = Sensei()->lesson->lesson_quizzes( $test_lesson_id );
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
        Sensei_Utils::user_start_lesson($test_user_id,$test_lesson_id);
        $result_for_valid_data =  WooThemes_Sensei_Quiz::submit_answers_for_grading( $test_user_quiz_answers, $files,
                                                                                $test_lesson_id , $test_user_id );
        $this->assertTrue( $result_for_valid_data ,
            'The function should return true for valid parameters' );

    }// end testSubmittedAnswersForGrading

    /**
     * This tests Woothemes_Sensei()->quiz->get_user_question_answer
     */
    public function testGetUserQuestionAnswer(){


        //setup the data needed for the assertions
        $test_user_id = wp_create_user( 'studentGetQuestionAnswer', 'studentGetQuestionAnswer', 'studentGetQuestionAnswer@test.com' );
        $test_lesson_id = $this->factory->get_random_lesson_id();
        $test_quiz_id = Sensei()->lesson->lesson_quizzes( $test_lesson_id );
        $test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id  );
        $files = $this->factory->generate_test_files( $test_user_quiz_answers );
        Sensei()->quiz->save_user_answers( $test_user_quiz_answers, $files , $test_lesson_id  ,  $test_user_id  );

        // make sure the method is in the class before we proceed
        $this->assertTrue( method_exists ( Sensei()->quiz,'get_user_question_answer' ),
            'The get_user_question_answer method is not in class WooThemes_Sensei_Quiz' );

        // does it return false for invalid data
        $invalid_data_message = 'This function does not check false data correctly';
        $this->assertFalse( Sensei()->quiz->get_user_question_answer('','','')  ,$invalid_data_message );
        $this->assertFalse( Sensei()->quiz->get_user_question_answer(' ',' ',' ') ,$invalid_data_message );
        $this->assertFalse( Sensei()->quiz->get_user_question_answer( -2, -3, -1 ) , $invalid_data_message );
        $this->assertFalse( Sensei()->quiz->get_user_question_answer( 3000, 5000, 5000 ) , $invalid_data_message );

        // setup data for the next assertion
        $assertion_message = ' Comparing the answer retrieved with the answer saved ';
        $random_question_id = array_rand( $test_user_quiz_answers );
        $users_saved_answers = Sensei()->quiz->get_user_answers( $test_lesson_id, $test_user_id  );
        $question_answer = Sensei()->quiz->get_user_question_answer( $test_lesson_id, $random_question_id, $test_user_id );

        // testing if the data is returned
        $this->assertEquals( $users_saved_answers[ $random_question_id ] ,$question_answer, $assertion_message  );

        //setup the data for the next assertion
        $assertion_message = 'This function does not fall back to the old data';
        $question_id = $random_question_id;
        $answer = $users_saved_answers[ $question_id ];
        $old_data_user_id = wp_create_user( 'olddata', 'olddata', 'olddata@test.com' );
        $question_type = Sensei()->question->get_question_type( $question_id );

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
            'user_id' => $old_data_user_id,
            'action' => 'update'
        );
        Sensei_Utils::sensei_log_activity( $args );

        $old_data_answer = Sensei()->quiz->get_user_question_answer( $test_lesson_id, $random_question_id, $old_data_user_id );

        // testing for users on the pre 1.7.4 data
        $this->assertEquals( maybe_unserialize( $answer ) ,$old_data_answer, $assertion_message  );

        // make sure that after a reset this function returns false

    }// end testGetUserQuestionAnswer

    /**
     * Testing $woothemes->quiz->set_user_grades
     */
    public function testSetUserGrades(){



        //setup the data needed for the assertions in this test
        $test_user_id = wp_create_user( 'studenttestSetUserQuizGrades', 'studenttestSetUserQuizGrades', 'studenttestSetUserQuizGrades@test.com' );
        $test_lesson_id = $this->factory->get_random_lesson_id();
        $test_quiz_id = Sensei()->lesson->lesson_quizzes( $test_lesson_id );
        $test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id  );
        $files = $this->factory->generate_test_files( $test_user_quiz_answers );
        Sensei()->quiz->save_user_answers( $test_user_quiz_answers, $files , $test_lesson_id  ,  $test_user_id  );
        $test_user_grades = $this->factory->generate_user_quiz_grades( $test_user_quiz_answers );

        // make sure the method is in the class before we proceed
        $this->assertTrue( method_exists ( Sensei()->quiz,'set_user_grades' ),
            'The set_user_grades method is not in class WooThemes_Sensei_quiz' );

        //does this function return false for the invalid parameters
        $invalid_data_message = 'This function does not check invalid parameters correctly';
        $this->assertFalse( Sensei()->quiz->set_user_grades('','','')  ,$invalid_data_message );
        $this->assertFalse( Sensei()->quiz->set_user_grades(' ',' ',' ') ,$invalid_data_message );
        $this->assertFalse( Sensei()->quiz->set_user_grades( -2, -3, -1 ) , $invalid_data_message );
        $this->assertFalse( Sensei()->quiz->set_user_grades( 3000, 5000, 5000 ) , $invalid_data_message );

        // does it return true for the right data?
        $this->assertTrue( Sensei()->quiz->set_user_grades( $test_user_grades , $test_lesson_id , $test_user_id),
            'The function should return success for valid parameters');

        //setup for the next assertions
        $test_lesson_status = Sensei_Utils::user_lesson_status( $test_lesson_id, $test_user_id  );
        $retrieved_quiz_grades = get_comment_meta( $test_lesson_status->comment_ID, 'quiz_grades' , true );
        $random_index = array_rand( $test_user_grades  );

        // doest it save the passed in grades correctly
        $this->assertTrue( is_array( $retrieved_quiz_grades ), 'The quiz grades was not saved correctly');
        $this->assertEquals( $test_user_grades[ $random_index ], $retrieved_quiz_grades[ $random_index ],
            'The quiz grades retrieved is not the same as those passed in when it was saved.' );

        // was the transients saved correctly?
        $transient_key = 'quiz_grades_'.$test_user_id.'_'.$test_lesson_id;
        $transient_val = get_transient( $transient_key );
        $this->assertFalse( empty( $transient_val ) , 'Transients are not saved correctly for user answers ' );
        $this->assertEquals( $transient_val ,$test_user_grades ,
            'The transient should be the same as the prepared answer which was base64 encoded' );

        // if saved again will the transient be updated
        $old_transient_value = $transient_val;
        $new_test_user_grades = $this->factory->generate_user_quiz_grades( $test_user_quiz_answers );

        Sensei()->quiz->set_user_grades( $new_test_user_grades, $test_lesson_id, $test_user_id );
        $new_transient_val = get_transient( $transient_key );

        $this->assertNotEquals( $new_transient_val, $old_transient_value,
            'Transient not updated on new save for the same user lesson combination' );

    }// end testSetUserGrades

    /**
     * Testing $woothemes->quiz->get_user_grades
     */
    public function testGetUserGrades(){



        //setup the data needed for the assertions in this test
        $test_user_id = wp_create_user( 'getQuizGrades', 'getQuizGrades', 'getQuizGrades@test.com' );
        $test_lesson_id = $this->factory->get_random_lesson_id();
        $test_quiz_id = Sensei()->lesson->lesson_quizzes( $test_lesson_id );
        $test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id  );
        $files = $this->factory->generate_test_files( $test_user_quiz_answers );
        Sensei()->quiz->save_user_answers( $test_user_quiz_answers, $files , $test_lesson_id  ,  $test_user_id  );
        $test_user_grades = $this->factory->generate_user_quiz_grades( $test_user_quiz_answers );

        // make sure the method is in the class before we proceed
        $this->assertTrue( method_exists ( Sensei()->quiz,'get_user_grades' ),
            'The get_user_grades method is not in class WooThemes_Sensei_quiz' );

        //does this function return false for the invalid parameters
        $invalid_data_message = 'This function does not check invalid parameters correctly';
        $this->assertFalse( Sensei()->quiz->get_user_grades('','')  ,$invalid_data_message );
        $this->assertFalse( Sensei()->quiz->get_user_grades(' ',' ') ,$invalid_data_message );
        $this->assertFalse( Sensei()->quiz->get_user_grades( -3, -1 ) , $invalid_data_message );
        $this->assertFalse( Sensei()->quiz->get_user_grades( 5000, 5000 ) , $invalid_data_message );

        //setup the next assertion
        Sensei()->quiz->set_user_grades($test_user_grades, $test_lesson_id, $test_user_id);
        $retrieved_grades = Sensei()->quiz->get_user_grades( $test_lesson_id, $test_user_id  );

        //doest this function return the saved data correctly?
        $this->assertEquals( $test_user_grades , $retrieved_grades, 'The grades saved and retrieved do not match.' );

        //set up the next assertion data
        $transient_key = 'quiz_grades_'. $test_user_id . '_' . $test_lesson_id;
        Sensei()->quiz->set_user_grades($test_user_grades, $test_lesson_id, $test_user_id);
        delete_site_transient( $transient_key );
        Sensei()->quiz->get_user_grades( $test_lesson_id, $test_user_id );
        $transient_val = get_transient( $transient_key );

        //ensure the transients work
        $this->assertEquals( $test_user_grades, $transient_val,
                            'The empty transient was not set after querying for the quiz answers data.' );

    } // end testGetUserGrades

    /**
     * Testing $woothemes->quiz->get_user_question_grade
     */
    public function testGetUserQuestionGrade(){



        //make sure the method exists
        $this->assertTrue( method_exists( Sensei()->quiz,
            'get_user_question_grade'), 'The function get_user_question_grade does not exist within the quiz class.' );


        // does it return false for invalid data
        $invalid_data_message = 'This function does not check false data correctly';
        $this->assertFalse( Sensei()->quiz->get_user_question_grade('','','')  ,$invalid_data_message );
        $this->assertFalse( Sensei()->quiz->get_user_question_grade(' ',' ',' ') ,$invalid_data_message );
        $this->assertFalse( Sensei()->quiz->get_user_question_grade( -2, -3, -1 ) , $invalid_data_message );
        $this->assertFalse( Sensei()->quiz->get_user_question_grade( 3000, 5000, 5000 ) , $invalid_data_message );

        //setup the data needed for the assertions in this test
        $test_user_id = wp_create_user( 'testGetUserQuestionGrade', 'testGetUserQuestionGrade', 'testGetUserQuestionGrade@test.com' );
        $test_lesson_id = $this->factory->get_random_lesson_id();
        $test_quiz_id = Sensei()->lesson->lesson_quizzes( $test_lesson_id );
        $test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id  );
        $test_user_grades = $this->factory->generate_user_quiz_grades( $test_user_quiz_answers );
        Sensei()->quiz->set_user_grades( $test_user_grades, $test_lesson_id, $test_user_id  );
        $test_question_id = array_rand( $test_user_grades );
        $retrieved_grade = Sensei()->quiz->get_user_question_grade( $test_lesson_id, $test_question_id, $test_user_id );

        //test if the the question grade can be retrieved
        $this->assertEquals( $test_user_grades[ $test_question_id ], $retrieved_grade,
            'The grade retrieved is not equal to the one that was set for this question ID' );

        //setup the next assertion
        $transient_key = 'quiz_grades_'. $test_user_id . '_' . $test_lesson_id;
        delete_site_transient( $transient_key );
        Sensei_Utils::delete_user_data( 'quiz_grades',$test_lesson_id,  $test_user_id );
        $random_question_id = array_rand( $test_user_grades );
        $old_data_args = array( 'post_id' => $random_question_id ,
                                'user_id' => $test_user_id,
                                'type' => 'sensei_user_answer',
                                'data' => 'test answer' );
        $old_data_activity_id = Sensei_Utils::sensei_log_activity( $old_data_args );
        update_comment_meta( $old_data_activity_id, 'user_grade', 1950  );
        $retrieved_grade = Sensei()->quiz->get_user_question_grade( $test_lesson_id, $random_question_id, $test_user_id );

        // Does the fall back to 1.7.3 data work?
        $this->assertEquals( 1950, $retrieved_grade, 'The get user question grade does not fall back th old data' );

    }// end testGetUserQuestionGrade

    /**
     * This tests Sensei()->quiz->save_user_answers_feedback
     */
    public function testSaveUserAnswersFeedback(){

        // setup the data and objects needed for this test

        $test_user_id = wp_create_user( 'studentFeedbackSave', 'studentFeedbackSave', 'studentFeedbackSave@test.com' );
        $test_lesson_id = $this->factory->get_random_lesson_id();
        $test_quiz_id = Sensei()->lesson->lesson_quizzes( $test_lesson_id );

        // does the save_user_answers function exist?
        $this->assertTrue( method_exists( Sensei()->quiz, 'save_user_answers_feedback'),
            'The quiz class function `save_user_answers_feedback` does not exist ' );

        // does this save_user_answers return false for bogus data
        $this->assertFalse(  Sensei()->quiz->save_user_answers_feedback( array(), array() ,-1000, -200 ) , 'save_user_answers_feedback does not return false for no existent users and lesson ' );
        $this->assertFalse(  Sensei()->quiz->save_user_answers_feedback( '', array(), '' , '' ) , 'save_user_answers_feedback does not return false for empty parameters' );

        // does the function return the correct information when a user doesn't exist?
        $this->assertFalse(  Sensei()->quiz->save_user_answers_feedback( '' , array() , '', $test_lesson_id ) , 'save_user_answers_feedback does not return false for empty user' );
        $this->assertFalse(  Sensei()->quiz->save_user_answers_feedback( '' , array() ,  -500 ,  $test_lesson_id ) , 'save_user_answers_feedback does not return false for a non existant user' );

        // Test the answers_array parameter
        $this->assertFalse(  Sensei()->quiz->save_user_answers_feedback( 'Answers Text', array(), $test_lesson_id, $test_user_id ) , 'save_user_answers_feedback does not return false if answers is not passed in as an array' );
        $this->assertFalse(  Sensei()->quiz->save_user_answers_feedback( '' , array(), $test_lesson_id , $test_user_id  ) , 'save_user_answers_feedback does not return false for empty answer array' );
        $this->assertFalse(  Sensei()->quiz->save_user_answers_feedback( '', array(), '' , '' ) , 'save_user_answers_feedback does not return false incorrectly formatted answers' );


        // Test a case that is setup correctly which should return a positive result
        $test_user_answers_feedback = $this->factory->generate_user_answers_feedback( $test_quiz_id  );
        Sensei_Utils::sensei_start_lesson( $test_lesson_id , $test_user_id  );
        $lesson_data_saved = Sensei()->quiz->save_user_answers_feedback( $test_user_answers_feedback , $test_lesson_id  ,  $test_user_id  ) ;

        // did the correct data return a valid comment id on the lesson as a result?
        $this->assertTrue(  intval(  $lesson_data_saved ) > 0 , 'The comment id returned after saving the quiz feedback does not represent a valid comment ' );

        //setup for the next group of assertions
        $sensei_activity_logged = Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $test_lesson_id, 'user_id'=> $test_user_id ) );
        $status_comment = Sensei_Utils::user_lesson_status( $test_lesson_id, $test_user_id );
        $saved_feedback = get_comment_meta( $status_comment->comment_ID, 'quiz_answers_feedback', true );

        // was the data that was just stored stored correctly ? Check the comment meta on the lesson id
        $this->assertTrue( ( bool ) $sensei_activity_logged , 'The saved answers feedback was not stored correctly on the Lesson');
        $this->assertFalse( empty($saved_feedback) , 'The saved feedback was not stored correctly on the Quiz');
        $this->assertTrue( is_array( maybe_unserialize( $saved_feedback) ), 'The saved feedback was not stored correctly on the Lesson');

        // can you retrieve data and is it the same as what was stored?
        //compare every single answer
        $retrieved_feedback_array = maybe_unserialize( $saved_feedback );

        foreach( $test_user_answers_feedback as $question_id => $feedback ){

            $saved_single_answer = $retrieved_feedback_array[ $question_id ];
            $assert_message = 'The saved feedback does not correspond to what was passed into the save_user_answers_feedback function ';
            $this->assertEquals( $feedback  , base64_decode( $saved_single_answer ),
                $assert_message );
        }// end for each

    } // end testSaveUserAnswersFeedback

    /**
     * This tests Sensei()->quiz->get_user_answers_feedback
     */
    public function testGetUserAnswersFeedback(){

        // setup the data and objects needed for this test

        $test_user_id = wp_create_user( 'studentFeedbackGet', 'studentFeedbackGet', 'studentFeedbackGet@test.com' );
        $test_lesson_id = $this->factory->get_random_lesson_id();
        $test_quiz_id = Sensei()->lesson->lesson_quizzes( $test_lesson_id );

        // does the save_user_answers function exist?
        $this->assertTrue( method_exists( Sensei()->quiz, 'get_user_answers_feedback'),
            'The quiz class function `get_user_answers_feedback` does not exist ' );

        //Does this function handle incorrect parameters correctly?
        $this->assertFalse( Sensei()->quiz->get_user_answers_feedback( '', ''  )  , 'The function should return false for incorrect parameters');
        $this->assertFalse(Sensei()->quiz->get_user_answers_feedback( 5000, 1000 ) , 'The function should return false for incorrect parameters');
        $this->assertFalse(Sensei()->quiz->get_user_answers_feedback( -1000, -121 ) , 'The function should return false for incorrect parameters');

        // save the answers to setup the next assertion
        Sensei_Utils::sensei_start_lesson( $test_lesson_id , $test_user_id  );
        $test_lesson_id = $this->factory->get_random_lesson_id();
        $test_user_answers_feedback = $this->factory->generate_user_answers_feedback( $test_quiz_id  );
        Sensei()->quiz->save_user_answers_feedback( $test_user_answers_feedback , $test_lesson_id  ,  $test_user_id  ) ;
        $retrieved_answer_feedback = Sensei()->quiz->get_user_answers_feedback(  $test_lesson_id  ,  $test_user_id  );

        $this->assertEquals( $test_user_answers_feedback, $retrieved_answer_feedback, 'Feedback retrieved does not match the saved data.' );

    } //end testGetUserAnswersFeedback

    /**
     * This test Sensei()->quiz->get_user_question_feedback
     */
    public function testGetUserQuestionFeedback(){

        // does this function add_user_data exist?
        $this->assertTrue( method_exists( Sensei()->quiz , 'get_user_question_feedback'),
            'The utils class function `get_user_question_feedback` does not exist ' );

        // does it return false for invalid data
        $invalid_data_message = 'This get_user_question_feedback function does not check false data correctly';
        $this->assertFalse( Sensei()->quiz->get_user_question_feedback('','','')  ,$invalid_data_message );
        $this->assertFalse( Sensei()->quiz->get_user_question_feedback(' ',' ',' ') ,$invalid_data_message );
        $this->assertFalse( Sensei()->quiz->get_user_question_feedback( -2, -3, -1 ) , $invalid_data_message );
        $this->assertFalse( Sensei()->quiz->get_user_question_feedback( 3000, 5000, 5000 ) , $invalid_data_message );

        // setup the next assertion
        $test_user_id = wp_create_user( 'studentQuestionFeedback', 'studentQuestionFeedback', 'studQFeedback@test.com' );
        $test_lesson_id = $this->factory->get_random_lesson_id();
        $test_quiz_id = Sensei()->lesson->lesson_quizzes( $test_lesson_id );
        $test_user_answers_feedback = $this->factory->generate_user_answers_feedback( $test_quiz_id  );
        Sensei_Utils::sensei_start_lesson( $test_lesson_id , $test_user_id  );
        Sensei()->quiz->save_user_answers_feedback( $test_user_answers_feedback , $test_lesson_id  ,  $test_user_id  ) ;
        $test_question_id = array_rand( $test_user_answers_feedback );
        $retrieved_grade = Sensei()->quiz->get_user_question_feedback( $test_lesson_id, $test_question_id, $test_user_id );

        //test if the the question grade can be retrieved
        $this->assertEquals( $test_user_answers_feedback[ $test_question_id ], $retrieved_grade,
            'The feedback retrieved is not equal to the one that was set for this question ID' );

        //setup the next assertion for backwards compatibility.
        $transient_key = 'sensei_answers_feedback_'.$test_user_id.'_'.$test_lesson_id;
        delete_transient( $transient_key );
        Sensei_Utils::delete_user_data( 'quiz_answers_feedback',$test_lesson_id,  $test_user_id );
        $random_question_id = array_rand( $test_user_answers_feedback );
        $old_data_args = array( 'post_id' => $random_question_id ,
            'user_id' => $test_user_id,
            'type' => 'sensei_user_answer',
            'data' => 'test answer feedback' );
        $old_data_activity_id = Sensei_Utils::sensei_log_activity( $old_data_args );
        update_comment_meta( $old_data_activity_id, 'answer_note', base64_encode( 'Sensei sample feedback' ) );
        $retrieved_feedback = Sensei()->quiz->get_user_question_feedback( $test_lesson_id, $random_question_id, $test_user_id );

        // Does the fall back to 1.7.3 data work?
        $this->assertEquals( 'Sensei sample feedback', $retrieved_feedback, 'The get user feedback does not fall back the old data' );

    }// end testGetUserQuestionFeedback

    /**
     * This test is for Woothemes_Sensei()->quiz->save_user_answers_feedback. We check the transients only.
     *
     * @group transient
     */
    public function testSaveUserFeedbackTransients(){

        // setup the data and objects needed for this test

        $test_user_id = wp_create_user('studFBTransients', 'studFBTransients', 'studFBTransients@test.com');
        $test_lesson_id = $this->factory->get_random_lesson_id();
        $test_quiz_id = Sensei()->lesson->lesson_quizzes( $test_lesson_id );
        $test_user_answers_feedback = $this->factory->generate_user_answers_feedback( $test_quiz_id  );
        Sensei_Utils::sensei_start_lesson( $test_lesson_id , $test_user_id  );
        Sensei()->quiz->save_user_answers_feedback( $test_user_answers_feedback , $test_lesson_id  ,  $test_user_id  ) ;

        // was it saved correctly?
        $transient_key = 'sensei_answers_feedback_'.$test_user_id.'_'. $test_lesson_id;
        $transient_val = get_transient( $transient_key );
        $decoded_transient_val = array();
        if( is_array( $transient_val ) ) {
            foreach ($transient_val as $question_id => $encoded_feedback) {
                $decoded_transient_val[$question_id] = base64_decode($encoded_feedback);
            }
        }

        $this->assertFalse( empty( $transient_val ) , 'Transients are not saved correctly for user feedback ' );
        $this->assertEquals( $test_user_answers_feedback ,$decoded_transient_val ,
            'The transient should be the same as the prepared answer which was base64 encoded' );

        // if saved again will the transient be updated
        $old_transient_value = $decoded_transient_val;
        $new_feedback = $this->factory->generate_user_answers_feedback( $test_quiz_id );
        Sensei()->quiz->save_user_answers_feedback( $new_feedback , $test_lesson_id  ,  $test_user_id  ) ;
        $new_users_retrieved_feedback = Sensei()->quiz->get_user_answers_feedback( $test_lesson_id, $test_user_id );

        $this->assertNotEquals( $old_transient_value, $new_users_retrieved_feedback ,
            'Transient not updated on new save for the same user lesson combination' );

    } // end testSaveUserFeedbackTransients

    /**
     * This test Woothemes_Sensei()->quiz->get_user_answers_feedback transients only
     *
     * @group transient
     */
    function testGetUserFeedbackTransients(){

        // setup the test data

        $test_user_id = wp_create_user('studFBTransientsGet', 'studFBTransientsGet', 'studFBTransientsGet@test.com');
        $test_lesson_id = $this->factory->get_random_lesson_id();
        $transient_key = 'sensei_answers_feedback_'.$test_user_id.'_'. $test_lesson_id;
        $transient_get_test = array( base64_encode( 'studFBTransientsGet' )  );
        $transient_get_test_decoded = array( 'studFBTransientsGet' );
        set_transient( $transient_key, $transient_get_test, 10 * DAY_IN_SECONDS  );
        $users_retrieved_answers = Sensei()->quiz->get_user_answers_feedback( $test_lesson_id, $test_user_id );

        // test if the answer is taken from the transient
        $this->assertEquals( $transient_get_test_decoded , $users_retrieved_answers ,
            'The transient was not used before proceeding to get the users answers from DB' );

        //setup next assertion
        $test_quiz_id = Sensei()->lesson->lesson_quizzes($test_lesson_id);
        $test_user_answers_feedback = $this->factory->generate_user_answers_feedback( $test_quiz_id  );
        Sensei_Utils::sensei_start_lesson( $test_lesson_id , $test_user_id  );

        Sensei()->quiz->save_user_answers_feedback( $test_user_answers_feedback , $test_lesson_id  ,  $test_user_id  ) ;
        delete_site_transient( $transient_key );
        Sensei()->quiz->get_user_answers_feedback( $test_lesson_id, $test_user_id );
        $transient_data_after_get_call = get_transient( $transient_key );

        // test if a transient is created when one does not exist
        // in this test we first delete the transient after it is been added in the save_user_answers
        // function above, then we get the data again and test if the function added the transient
        $this->assertNotFalse( $transient_data_after_get_call,
            ' The get_user_answers function does not set the transient after retrieving the data ');

        // make sure the one of the keys passed in is in the transient
        $random_key = array_rand( $test_user_answers_feedback  );
        $this->assertArrayHasKey( $random_key , $transient_data_after_get_call  ,
            'The transient does not contain the same elements that we passed in' );

        //make sure the number of elements passes in is the same as what is in the new transient cache
        $this->assertEquals( count( $test_user_answers_feedback ), count( $transient_data_after_get_call ),
            'The number of elements in the transient does not match those the user submitted');

    } // testGetUserFeedbackTransients

    /**
     * This test Woothemes_Sensei()->quiz->save_user_answers to see if the function
     * can overwrite the questions asked. This function should not be able to overwrite questions
     * asked none was recorded in the first place.
     *
     * @group questions
     */
    function testSaveUserAnswersQuestionsAskedNotOverwriteable(){

        global $current_user;
        $test_user_id = wp_create_user('studQuestionsaskedOverwrite', 'studQuestionsaskedOverwrite', 'studQuestionsaskedOverwrite@test.com');
        $current_user =  get_user_by( 'id', $test_user_id );
        $test_lesson_id = $this->factory->get_random_lesson_id();
        $user_lesson_status_comment_id = Sensei_Utils::sensei_start_lesson( $test_lesson_id , $test_user_id  );

        // setup the quiz questions asked
        $test_quiz_id = Sensei()->lesson->lesson_quizzes($test_lesson_id);

        // set the show questions to be less than the actual question the quiz has
        $show_questions = update_post_meta( $test_quiz_id, '_show_questions',  10 );

        //
        // setup and accident example where the users is asked less questions by mistake
        //

        // function that gets questions also load the quiz questions asked if none was set
        $test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id  );
        $files = $this->factory->generate_test_files( $test_user_quiz_answers );

        // questions asked as it was saved initial
        $questions_asked_count = count( $test_user_quiz_answers );

        // remove some 2 questions to illustrate mistake
        array_pop( $test_user_quiz_answers);
        array_pop( $test_user_quiz_answers);


        // Submit answers and remove the hooks within the submit function to avoid side effects
        remove_all_actions( 'sensei_user_quiz_submitted' );
        remove_all_actions( 'sensei_user_lesson_end' );
        $result_for_valid_data =  WooThemes_Sensei_Quiz::submit_answers_for_grading( $test_user_quiz_answers, $files,
            $test_lesson_id , $test_user_id );


        // get questions after submitting
        $questions_asked_string = get_comment_meta( $user_lesson_status_comment_id , 'questions_asked', true);
        $questions_asked_count_after_submitting = count( explode( ',', $questions_asked_string ) );

        //check if questions asked have not been overwritten
        $this->assertEquals( $questions_asked_count, $questions_asked_count_after_submitting,
            'Questions asked user data does not match what was set when the lesson quiz questions was generated.' );


    }// end testSaveUserAnswersQuestionsAskedNotOverwriteable



}// end class Sensei_Class_Quiz_Test