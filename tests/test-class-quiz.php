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

        // remove all questions
        $questions = get_posts( 'post_type=question' );
        foreach( $questions as $index => $question ){
            wp_delete_post( $question->ID , true );
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
     * Generate and attach lesson questions.
     *
     * This will create a set of questions. These set of question will be added to every lesson.
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

        // create a new lesson quiz post type and attach it to to the lesson
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
        $questions = get_posts( array( 'post_type' => 'question' )  );

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
        $questions =  []; // will be used to store generated question
        $sample_questions = []; // will be used to store 1 sample from each question type

        // get all allowed question data
        //'multiple-choice' 'boolean' 'gap-fill' 'single-line' 'multi-line' 'file-upload'
        $question_types = $woothemes_sensei->post_types->question->question_types();

        // create test data array of questions
        foreach( $question_types as $type => $translation_string  ){

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
                'question_count' => '2'
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

        // add a random test question as many as were requested
        foreach( range( 0 , $number ) as $count ){

            // get random number range
            $range = count( $sample_questions);

            //select a random question
            $random_index = rand( 0, $range -1 );
            $chosen_question = $sample_questions[ $random_index ];

            // modify chosen question to make it unique

            // attache the chosen question to be returned
            $questions[] = $chosen_question;
        }

        return $questions;

    }// end generate_and_attach_questions

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
        //setup
        global $woothemes_sensei;

        $test_user_id = wp_create_user( 'student', 'student', 'student@test.com' );

        // does the save_user_answers function exist?
        $this->assertTrue( method_exists( $woothemes_sensei->quiz, 'save_user_answers'),
                            'The quiz class function `save_user_answers` does not exist ' );

        // does this save_user_answers return false for bogus data
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( [] ,-1000, -200 ) , 'save_user_answers does not return false for no existent users and lesson ' );
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( '', '' , '' ) , 'save_user_answers does not return false for empty parameters' );


        // does the function return the correct information when a user doesn't exist?
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( '' , '',$this->test_data->lesson_ids[0] ) , 'save_user_answers does not return false for empty user' );
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( '' ,  -500 ,  $this->test_data->lesson_ids[0] ) , 'save_user_answers does not return false for a non existant user' );

        // Test the answers array
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( 'Answers Text', $this->test_data->lesson_ids[0], $test_user_id ) , 'save_user_answers does not return false if answers is not passed in as an array' );
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( '' , $this->test_data->lesson_ids[0] , $test_user_id  ) , 'save_user_answers does not return false for empty answer array' );
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( '', '' , '' ) , 'save_user_answers does not return false incorrectly formatted answers' );


        // get the question id that is save on all lessons ( see attach_lessons_questions for how this was added  )
        $questions = get_posts( array( 'post_type' => 'question', 'field' => 'ID' )  );

        // get the
        $quiz_id_1 = $woothemes_sensei->lesson->lesson_quizzes( $this->test_data->lesson_ids[0]  );
        $quiz_id_2 = $woothemes_sensei->lesson->lesson_quizzes( $this->test_data->lesson_ids[1]  );

        // according to how this test is setup both ID should bring back the same set of question post
        // types.
        $quiz_question_posts = $woothemes_sensei->lesson->lesson_quiz_questions( $quiz_id_1  );

        // setup the sample
        // Save each respective array for each quiz id
        /*
        array(
            mcq : $question_id => array(
               0 = 12
            )

            mcq with multiple right : $question_id => array(
               0 = "b",
               1 = "b"
               2 = 'c'
            )

           bool : $question_id => 'true' / 'false'
           gap file : $question_id => 'fill'
           single line $question_id => 'Single line scentence'
           multiline : $question_id => 'Long paragarph, very long paragrap,Long paragarph, very long paragrap,Long paragarph,'
                                        . ' very long paragrap,Long paragarph, very long paragrap,' ;
            file upload: $question_id => base64 encode ( uploaded attachent ID  ) optionaly
            you'll need to load the file in the array

        $_FILES=> array (size=1)
  'file_upload_7741' =>git
    array (size=5)
      'name' => string 'apple.jpg' (length=9)
      'type' => string 'image/jpeg' (length=10)
      'tmp_name' => string '/tmp/phpsP0RJH' (length=14)
      'error' => int 0
      'size' => int 36414
        }
        */

        // loop through all question posts and create random dummy save quiz answers array for each quiz
        $saved_answers_quiz_1 = array();
        $saved_answers_quiz_2 = array();

        foreach( $quiz_question_posts as $question ){

            // get the current question type
            $question_types_array = wp_get_post_terms( $question->ID, 'question-type', array( 'fields' => 'names' ) );
            if ( isset( $question_types_array[0] ) && '' != $question_types_array[0] ) {
                $type = $question_types_array[0];
            }else{
                $type = 'multiple-choice';
            }

            // setup the demo data and store it in the respective array
            if ('multiple-choice' == $type ) {
                // these answer can be found the question generate and attach answers function
                $saved_answers_quiz_1[ $question->ID ] = array( 0 => 'right' );
                $saved_answers_quiz_2[ $question->ID ] = array( 0 => 'wrong1' );

            } elseif ('boolean' == $type ) {

                $saved_answers_quiz_1[ $question->ID ] = 'true';
                $saved_answers_quiz_2[ $question->ID ] = 'false';

            } elseif ( 'single-line' == $type  ) {

                $saved_answers_quiz_1[ $question->ID ] = '1 The single line answer 1';
                $saved_answers_quiz_2[ $question->ID ] = '2 The single line answer 2';

            } elseif ( 'gap-fill' == $type ) {

                $saved_answers_quiz_1[ $question->ID ] = 'OneWord';
                $saved_answers_quiz_2[ $question->ID ] = 'TwoWord';

            } elseif ( 'multi-line' == $type  ) {

                $saved_answers_quiz_1[ $question->ID ] = 'Very ver long text for 1 and only 1';
                $saved_answers_quiz_2[ $question->ID ] = 'Very ver long text for 2 and only 2';

            } elseif ( 'file-upload' == $type ) {

                $saved_answers_quiz_1[ $question->ID ] = '';
                $saved_answers_quiz_2[ $question->ID ] = '';
            }
        }// end for quiz_question_posts

        // save the data for each quiz
        $comment_id_1 = $woothemes_sensei->quiz->save_user_answers( $saved_answers_quiz_1, $this->test_data->lesson_ids[0],  $test_user_id  ) ;
        $comment_id_2 = $woothemes_sensei->quiz->save_user_answers( $saved_answers_quiz_2, $this->test_data->lesson_ids[1] , $test_user_id );

        // did the correct data return a valid comment id on the lesson as a result?
        $this->assertTrue(  intval( $comment_id_1 ) > 0 , 'The comment id returned after saving the quiz answer does not represent a valid comment ' );

        // was the data that was just stored stored correctly ? Check the comment meta on the lesson id
        $comments = get_comments( array( 'post_id' => $this->test_data->lesson_ids[0] ) );
        $comment_found = false;
        foreach( $comments as $comment  ){
            if( $comment_id_1 == $comment->comment_id ){
                $comment_found = true;
            }
        }
        $this->assertTrue( $comment_found , 'The saved answers were not stored correctly on the Quiz');

        // was check if the data that was saved on the different quizzes are not the same

    } // end testSaveUserAnswers

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
        $valid_lesson_quiz_id = $woothemes_sensei->lesson->lesson_quizzes( $this->get_random_lesson_id() );
        $this->assertTrue( $valid_lesson_quiz_id > 0 , 'Get quiz id should return a valid quiz id if a valid  lesson ID is passed in'  );

    }// end testGetQuizId

    /**
     * This test Woothemes_Sensei()->quiz->get_user_answers
     */
    function testGetUserAnswers(){

        global $woothemes_sensei;
        // make sure the function exists

        // save the user answers
        $this->assertTrue( method_exists( $woothemes_sensei->quiz, 'get_user_answers'),
            'The quiz class function `get_user_answers` does not exist ' );

        // get the answers

        // assert if this is valid results
        // make sure it is the same as the saved answers
        // make sure the answer retrieved


    } // end testGetUserAnswers



}
