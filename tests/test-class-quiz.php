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
     * This function sets up the lessons, quizes and their questions.
     */
    function setup(){

        $this->test_data = new stdClass();

        // generate sample lessons
        $this->test_data->lesson_ids = $this->generate_test_lessons();

        // generate lesson questions
        foreach( $this->test_data->lesson_ids as $lesson_id ){

            $this->attach_lessons_questions( 12 , $lesson_id );

        }

    }// end function setup()

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
     * Generate and attach lesson questions
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
                $test_question_data['question_wrong_answers'] = array( 'wrong 1', 'wrong 2',  'wrong3' )  ;

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
    function testClassInstance() {
        //setup the test
        global $woothemes_sensei;

        //test if the global sensei quiz class is loaded
        $this->assertTrue( isset( $woothemes_sensei->quiz ), 'Sensei quiz class is not loaded' );

    } // end testClassInstance

    /**
     * This test Woothemes_Sensei()->quiz->save_user_answers
     */
    function testSaveUserAnswers(){
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
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( 'Answers Text', $test_user_id, $this->test_data->lesson_ids[0] ) , 'save_user_answers does not return false if answers is not passed in as an array' );
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( '' , $test_user_id , $this->test_data->lesson_ids[0] ) , 'save_user_answers does not return false for empty answer array' );
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( '', '' , '' ) , 'save_user_answers does not return false incorrectly formatted answers' );


        //setup the answers to be saved


        //save the answers

        // did the correct data return a valid comment id on the lesson as a result?

        // was the data that was just stored stored correctly ? Check the comment meta on the lesson id

    } // end testSaveUserAnswers

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
