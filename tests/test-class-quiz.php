<?php
class Sensei_Class_Quiz_Test extends WP_UnitTestCase {

    /**
     * @protect $test_data
     *
     * An object that will be used to reference data setup for tests
     */
     protected $test_data;

    /**
     * setup function
     *
     * This function sets up the lessons, quizes and their questions. This function runs before
     * every single test in this class
     */
    public function setup(){

        $this->test_data = new stdClass();

        // generate sample lessons
        $this->test_data->lesson_ids = $this->generate_test_lessons();

        // generate lesson questions
        foreach( $this->test_data->lesson_ids as $lesson_id ){

            $this->attach_lessons_questions( 12 , $lesson_id );

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
     * Accesses the test_data lesson_id's and return any one of them
     *
     * @since 1.7.2
     */
    protected function get_random_lesson_id(){

        $valid_lesson_ids = $this->test_data->lesson_ids;
        $random_index = rand( 1 , count( $valid_lesson_ids )  ) - 1;
        return $valid_lesson_ids[ $random_index ] ;

    } // end get_random_valid_lesson_id()

    /**
     * generate random lessons
     *
     * @param int $number how many lessons would you like to generate. Default 10.
     * @return array $lesson_ids
     */
    protected function generate_test_lessons( $number = 10  ){

        $lesson_ids = [];

        // create random $number of test lessons needed in the class tests
        foreach (range( 0, $number ) as $count ) {

            $new_lesson_args = array(
                'post_content' => 'lesson ' . ( $count + 1 ) . ' test content',
                'post_name' => 'test-lesson ' . ( $count + 1 ) ,
                'post_title' => 'test-lesson ' . ( $count + 1 ) ,
                'post_status' => 'publish',
                'post_type' => 'lesson'
            );
            // add the lesson id to the array of ids
           $lesson_ids[ $count ] = wp_insert_post( $new_lesson_args );

        } // end for each range 0 to 12

        return $lesson_ids;

    }// end generate_test_lessons

    /**
     * This function creates dummy answers for the user based on the quiz questions for the
     * quiz id that is passed in.
     *
     * @since 1.7.2
     * @access public
     *
     * @param int $quiz_id
     * @returns array $user_quiz_answers
     */
    public function generate_user_quiz_answers( $quiz_id ){

        global $woothemes_sensei;
        $user_quiz_answers =  array();

        if( empty( $quiz_id ) ||  'quiz' != get_post_type( $quiz_id ) ){

            return $user_quiz_answers;

        }

        // get all the quiz questions that is added to the passed in quiz
        $quiz_question_posts = $woothemes_sensei->lesson->lesson_quiz_questions( $quiz_id );

        if( empty( $quiz_question_posts ) || count( $quiz_question_posts ) == 0
            || ! isset(  $quiz_question_posts[0]->ID ) ){

            return $user_quiz_answers;

        }

        // loop through all the question and generate random answer data
        foreach( $quiz_question_posts as $question ){

            // get the current question type
            $question_types_array = wp_get_post_terms( $question->ID, 'question-type', array( 'fields' => 'slugs' ) );

            if ( isset( $question_types_array[0] ) && '' != $question_types_array[0] ) {
                $type = $question_types_array[0];
            }else{
                $type = 'multiple-choice';
            }

            // setup the demo data and store it in the respective array
            if ('multiple-choice' == $type ) {
                // these answer can be found the question generate and attach answers function
                $question_meta = get_post_meta( $question->ID );
                $user_quiz_answers[ $question->ID ] = array( 0 => 'wrong1' );

            } elseif ('boolean' == $type ) {

                $bool_answer = 'false';
                $random_is_1 = rand(0,1);

                if( $random_is_1 ){
                    $bool_answer = 'true';
                }

                $user_quiz_answers[ $question->ID ] = $bool_answer;

            } elseif ( 'single-line' == $type  ) {

                $user_quiz_answers[ $question->ID ] = 'Single lin answer for basic testing';

            } elseif ( 'gap-fill' == $type ) {

                $user_quiz_answers[ $question->ID ] = 'OneWordScentencesForSampleAnswer';

            } elseif ( 'multi-line' == $type  ) {

                $user_quiz_answers[ $question->ID ] = 'Sample paragraph to test the answer';

            } elseif ( 'file-upload' == $type ) {

                $user_quiz_answers[ $question->ID ] = '';

            }

        }// end for quiz_question_posts

        return $user_quiz_answers;

    }// end generate_user_quiz_answers()

    /**
     * Generate and attach lesson questions.
     *
     * This will create a set of questions. These set of questions will be added to every lesson.
     * So all lessons the makes use of this function will have the same set of questions in their
     * quiz.
     *
     * @param int $number number of questions to generate. Default 10
     * @param int $lesson_id
     * @throws Exception
     * @trows new 'Generate questions needs a valid lesson ID.' if the ID passed in is not a valid lesson
     */
    protected function attach_lessons_questions( $number = 10 , $lesson_id ){

        global $woothemes_sensei;

        if( empty( $lesson_id ) || ! intval( $lesson_id ) > 0
            || ! get_post( $lesson_id ) ||  'lesson'!= get_post_type( $lesson_id )  ){
            throw new Exception('Generate questions needs a valid lesson ID.');
        }

        // create a new quiz and attach it to the lesson
        $new_quiz_args = array(
            'post_type' => 'quiz',
            'post_name' => 'lesson_id_ ' .  $lesson_id . '_quiz' ,
            'post_title' => 'lesson_id_ ' .  $lesson_id . '_quiz' ,
            'post_status' => 'publish',
            'post_parent' => $lesson_id

        );
        $quiz_id = wp_insert_post( $new_quiz_args );

        // if the database already contains questions don't create more but add
        // the existing questions to the passed in lesson id's lesson
        $question_post_query = new WP_Query( array( 'post_type' => 'question' ) );
        $questions = $question_post_query->get_posts();

        if( ! count( $questions ) > 0 ){

            // generate questions if none exists
            $questions = $this->generate_questions( $number );

            // create random $number of question   lessons needed in the class tests
            foreach ( $questions as $question ) {

                $question[ 'quiz_id' ] = $quiz_id;
                $question[ 'post_author'] = get_post( $quiz_id )->post_author;
                $woothemes_sensei->lesson->lesson_save_question( $question );

            } // end for each range 0 to 12



        } else {

            // simply add questions to incoming lesson id

            foreach ( $questions as $index => $question  ) {

                // Add to quiz
                add_post_meta( $question->ID, '_quiz_id', $quiz_id , false );

                // Set order of question
                $question_order = $quiz_id . '000' . $index;
                add_post_meta( $question->ID, '_quiz_question_order' . $quiz_id , $question_order );

            }
        } // end if count

        return;
    }

    /**
     * Generates questions from each question type with the correct data and then attaches that to the quiz
     *
     * @param int $number the amount of questions we want to attach defaults to 10
     * @return array $questions
     */
    protected function generate_questions( $number = 10 ){

        global $woothemes_sensei;
        $chosen_questions =  []; // will be used to store generated question
        $sample_questions = []; // will be used to store 1 sample from each question type

        // get all allowed question data
        //'multiple-choice' 'boolean' 'gap-fill' 'single-line' 'multi-line' 'file-upload'
        $question_types = $woothemes_sensei->post_types->question->question_types();

        // get the question type slug as this is used to determine the slug and not the string type
        $question_type_slugs = array_keys($question_types);

        // generate ten random-ish questions
        foreach( range( 0, ( $number - 1 ) )  as $count ) {

              //make sure that at least on question from each type is included
            if( $count < ( count( $question_types ) )  ){

                //setup the question type at the current index
                $type =  $question_type_slugs[ $count ];

            }else{

                // setup a random question type
                $random_index = rand( 0, count( $question_types ) - 1 );
                $type =  $question_type_slugs[$random_index];

              }

            $test_question_data = array(
                'question_type' => $type ,
                'question_category' => 'undefined' ,
                'action' => 'add',
                'question' => 'Is this a sample' . $type  . ' question ? _ ' . rand() ,
                'question_grade' => '1' ,
                'answer_feedback' => 'Answer Feedback sample ' . rand() ,
                'question_description' => ' Basic description for the question' ,
                'question_media' => '' ,
                'answer_order' => '' ,
                'random_order' => 'yes' ,
                'question_count' => $number
            );

            // setup the right / wrong answers base on the question type
            if ('multiple-choice' == $type ) {

                $test_question_data['question_right_answers'] = array( 'right' ) ;
                $test_question_data['question_wrong_answers'] = array( 'wrong1', 'wrong2',  'wrong3' )  ;

            } elseif ('boolean' == $type ) {

                $test_question_data[ 'question_right_answer_boolean' ] = true;

            } elseif ( 'single-line' == $type  ) {

                $test_question_data[ 'add_question_right_answer_multiline' ] = '';

            } elseif ( 'gap-fill' == $type ) {

                $test_question_data[ 'add_question_right_answer_gapfill_pre' ] = '';
                $test_question_data[ 'add_question_right_answer_gapfill_gap' ] = '';
                $test_question_data[ 'add_question_right_answer_gapfill_post'] = '';

            } elseif ( 'multi-line' == $type  ) {

                $test_question_data [ 'add_question_right_answer_singleline' ] = '';

            } elseif ( 'file-upload' == $type ) {

                $test_question_data [ 'add_question_right_answer_fileupload'] = '';
                $test_question_data [ 'add_question_wrong_answer_fileupload' ] = '';

            }

            $sample_questions[] = $test_question_data;
        }

        // create the requested number tests from the sample questions
        foreach( range( 1 , $number ) as $count ){

            // get the available question types
            $available_question_types = count( $sample_questions);
            $highest_question_type_array_index = $available_question_types-1;

            //select a random question
            $random_index = rand( 0, $highest_question_type_array_index  );
            $randomly_chosen_question = $sample_questions[ $random_index ];

            // attache the chosen question to be returned
            $chosen_questions[] = $randomly_chosen_question;
        }

        return $chosen_questions;

    }// end generate_and_attach_questions

    /**
     * This functions take answers submitted by a user, extracts ones that is of type file-upload
     * and then creates and array of test $_FILES
     *
     * @param array $test_user_quiz_answers
     * @return array $files
     */
    public function generate_test_files( $test_user_quiz_answers ){

        $files = array();
        //check if there are any file-upload question types and generate the dummy file data
        foreach( $test_user_quiz_answers as $question_id => $answer ){

            //Setup the question types
            $question_types = wp_get_post_terms( $question_id, 'question-type' );
            foreach( $question_types as $type ) {
                $question_type = $type->slug;
            }

            if( 'file-upload' == $question_type){
                //setup the sample image file location within the test folders
                $test_images_directory = dirname( __FILE__) . '/images/';

                // make a copy of the file intended for upload as
                // it will be moved to the new location during the upload
                // and no longer available for the next test
                $new_test_image_name = 'test-question-' . $question_id . '-greenapple.jpg';
                $new_test_image_location = $test_images_directory . $new_test_image_name  ;
                copy ( $test_images_directory . 'greenapple.jpg', $new_test_image_location   );

                $file = array(
                    'name' => $new_test_image_name,
                    'type' => 'image/jpeg' ,
                    'tmp_name' => $new_test_image_location ,
                    'error' => 0,
                    'size' => 4576 );

                // pop the file on top of the car
                $files[ 'file_upload_' . $question_id ] = $file;
            }

        } // end for each $test_user_quiz_answers

        return $files;

    }// end generate_test_files()

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
        $test_lesson_id = $this->get_random_lesson_id();
        $test_quiz_id = $woothemes_sensei->lesson->lesson_quizzes( $test_lesson_id );

        // does the save_user_answers function exist?
        $this->assertTrue( method_exists( $woothemes_sensei->quiz, 'save_user_answers'),
                            'The quiz class function `save_user_answers` does not exist ' );

        // does this save_user_answers return false for bogus data
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( [] ,-1000, -200 ) , 'save_user_answers does not return false for no existent users and lesson ' );
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( '', '' , '' ) , 'save_user_answers does not return false for empty parameters' );

        // does the function return the correct information when a user doesn't exist?
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( '' , '', $test_lesson_id ) , 'save_user_answers does not return false for empty user' );
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( '' ,  -500 ,  $test_lesson_id ) , 'save_user_answers does not return false for a non existant user' );

        // Test the answers_array parameter
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( 'Answers Text', $test_lesson_id, $test_user_id ) , 'save_user_answers does not return false if answers is not passed in as an array' );
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( '' , $test_lesson_id , $test_user_id  ) , 'save_user_answers does not return false for empty answer array' );
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( '', '' , '' ) , 'save_user_answers does not return false incorrectly formatted answers' );

        // Test a case that is setup correctly which should return a positive result
        $test_user_quiz_answers = $this->generate_user_quiz_answers( $test_quiz_id  );
        WooThemes_Sensei_Utils::sensei_start_lesson( $test_lesson_id , $test_user_id  );
        $lesson_data_saved = $woothemes_sensei->quiz->save_user_answers( $test_user_quiz_answers, $test_lesson_id  ,  $test_user_id  ) ;

        // did the correct data return a valid comment id on the lesson as a result?
        $this->assertTrue(  intval(  $lesson_data_saved ) > 0 , 'The comment id returned after saving the quiz answer does not represent a valid comment ' );

        // was the data that was just stored stored correctly ? Check the comment meta on the lesson id
        $sensei_activity_logged = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $test_lesson_id, 'user_id'=> $test_user_id ) );
        $this->assertTrue( ( bool ) $sensei_activity_logged , 'The saved answers were not stored correctly on the Quiz');

        // was check if the data that was saved on the different quizzes are not the same
        $activity_value = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $test_lesson_id, 'user_id'=> $test_user_id ) , true );//WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $this->test_data->lesson_ids[0], 'user_id'=> $test_user_id ) );

        /* todo: test the file saving process
            This is how the incomming data will look
             * we already have the test images which we can use to alter the global $_FILES
             * get the tests/imges/ file size
             $_FILES=> array (size=1)  'file_upload_$question_id' => git  array (size=5)
                          'name' => string 'apple.jpg' (length=9)
                          'type' => string 'image/jpeg' (length=10)
                          'tmp_name' => string '/tmp/phpsP0RJH' (length=14)
                          'error' => int 0
                          'size' => int 36414
             }
         */

    } // end testSaveUserAnswers


    /**
     * This test Woothemes_Sensei()->quiz->sensei_save_quiz_answers
     */
    public function testSenseiSaveQuizAnswers(){
        // todo : test the function instance ,
        // todo: setup a few test users added to lessons in the setup function and remove it in teardown
        // todo: create get random user function
        // todo: alter the global post variable to be a quiz that the user is taking
        // todo: setup the global current user
        // todo : test if it returns success when it should and also failure
    }


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
        $valid_lesson_quiz_id = $woothemes_sensei->lesson->lesson_quizzes( $this->get_random_lesson_id() );
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
        $test_lesson_id = $this->get_random_lesson_id();
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
        $user_quiz_answers = $this->generate_user_quiz_answers( $test_lesson_quiz_id  );

        // assign the user to the lesson
        WooThemes_Sensei_Utils::sensei_start_lesson( $test_lesson_id, $test_user_id  );

        // test for when there is no answers saved.
        $is_false_when_no_answers_saved = $woothemes_sensei->quiz->get_user_answers( $test_lesson_id, $test_user_id);
        $this->assertFalse(  $is_false_when_no_answers_saved  , 'The function should return false when no answers are saved on the Lesson' );

        // save the test users answers on the tes lesson
        $lesson_data_saved = $woothemes_sensei->quiz->save_user_answers( $user_quiz_answers, $test_lesson_id,  $test_user_id  ) ;
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
        $test_lesson_id = $this->get_random_lesson_id();
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

        $result_for_empty_user_id = $woothemes_sensei->quiz->reset_user_saved_answers( $this->get_random_lesson_id() , '');
        $this->assertFalse(  $result_for_empty_user_id , 'The function should return false for an empty user id.' );

        $result_for_invalid_user_id = $woothemes_sensei->quiz->reset_user_saved_answers( $this->get_random_lesson_id() , -500 );
        $this->assertFalse(  $result_for_invalid_user_id , 'The function should return false for an invalid user id.' );

        // test for a valid user and lesson that has no lesson_status comment on the lesson
        $valid_parameters_for_user_with_no_lesson_status =
            $woothemes_sensei->quiz->reset_user_saved_answers( $test_lesson_id , $test_user_id );
        $this->assertFalse(  $valid_parameters_for_user_with_no_lesson_status ,
            'The function should return false if the user that has no lesson status data stored' );
        // test for a valid user and lesson that has a sensei_lesson_status comment by this user
        $user_quiz_answers = $this->generate_user_quiz_answers( $test_quiz_id );
        WooThemes_Sensei_Utils::sensei_start_lesson( $test_lesson_id , $test_user_id  );
        $lesson_data_saved = $woothemes_sensei->quiz->save_user_answers( $user_quiz_answers, $test_lesson_id,  $test_user_id  ) ;
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
        $test_lesson_id = $this->get_random_lesson_id();
        $test_quiz_id = $woothemes_sensei->lesson->lesson_quizzes( $test_lesson_id );
        $test_user_quiz_answers = $this->generate_user_quiz_answers( $test_quiz_id  );
        $files = $this->generate_test_files( $test_user_quiz_answers );

        // setup for the next group of assertions
        //setup this function to override the arguments passed to WordPress upload function
        function unit_test_override_sensei_file_upload_args( $args ){
            $args['action'] = 'custom_testing_upload_function';
            return $args;
        }

        // for the valid data does it return an array ?
        add_filter( 'sensei_file_upload_args', 'unit_test_override_sensei_file_upload_args' );
        $prepared_test_data = $woothemes_sensei->quiz->prepare_form_submitted_answers( $test_user_quiz_answers , $files );
        $this->assertTrue( is_array( $prepared_test_data ) ,
        'function function does not return an array for valid parameters' );
        $this->assertTrue( count( $prepared_test_data ) == count( $test_user_quiz_answers ) ,
            'function does not return the same number of items that was passed in' );
        $this->assertTrue( array_keys( $prepared_test_data ) == array_keys( $test_user_quiz_answers ) ,
            'function does not return the same array keys( question ids ) that was passed in' );

        // for valid data, is the answers in the array returned the same as the values passed in
        $random_index = array_rand( $prepared_test_data  );
        $input_array_sample_element_val = $test_user_quiz_answers[$random_index];
        $output_array_sample_element_val =  maybe_unserialize( base64_decode(  $prepared_test_data[ $random_index ] ));
        $this->assertTrue( $input_array_sample_element_val == $output_array_sample_element_val ,
            'The function changes the array values so much that they are not the same as when passed in'  );

    }// end testPrepareFormSubmittedAnswers()

}// end class Sensei_Class_Quiz_Test