<?php

class Sensei_Class_Grading_Test extends WP_UnitTestCase {

    /**
     * Constructor function
     */
    public function __construct(){
        parent::__construct();
        include_once('factory/Sensei-Factory.php');
    }

    /**
     * setup function
     *
     * This function sets up the lessons, quizes and their questions. This function runs before
     * every single test in this class
     */
    public function setUp() {
        // load the factory class
        global $woothemes_sensei;
        require_once( dirname( dirname(__FILE__) ) . '/classes/class-woothemes-sensei-grading.php');
        $woothemes_sensei->grading = new WooThemes_Sensei_Grading( '' );
        $this->factory = new Sensei_Factory();
    }// end function setup()

    /**
     * Testing the quiz class to make sure it is loaded
     */
    public function testClassInstance() {
        //setup the test
        global $woothemes_sensei;

        //test if the global sensei quiz class is loaded
        $this->assertTrue(isset($woothemes_sensei->grading), 'Sensei Grading class is not loaded');

    } // end testClassInstance

    /**
     * Testing $woothemes->grading->set_user_quiz_grades
     */
    public function testSetUserQuizGrades(){

        global $woothemes_sensei;

        //setup the data needed for the assertions in this test
        $test_user_id = wp_create_user( 'studenttestSetUserQuizGrades', 'studenttestSetUserQuizGrades', 'studenttestSetUserQuizGrades@test.com' );
        $test_lesson_id = $this->factory->get_random_lesson_id();
        $test_quiz_id = $woothemes_sensei->lesson->lesson_quizzes( $test_lesson_id );
        $test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id  );
        $files = $this->factory->generate_test_files( $test_user_quiz_answers );
        $woothemes_sensei->quiz->save_user_answers( $test_user_quiz_answers, $files , $test_lesson_id  ,  $test_user_id  );
        $test_user_grades = $this->factory->generate_user_quiz_grades( $test_user_quiz_answers );

        // make sure the method is in the class before we proceed
        $this->assertTrue( method_exists ( $woothemes_sensei->grading,'set_user_quiz_grades' ),
            'The set_user_quiz_grades method is not in class WooThemes_Sensei_Grading' );

        //does this function return false for the invalid parameters
        $invalid_data_message = 'This function does not check invalid parameters correctly';
        $this->assertFalse( $woothemes_sensei->grading->set_user_quiz_grades('','','')  ,$invalid_data_message );
        $this->assertFalse( $woothemes_sensei->grading->set_user_quiz_grades(' ',' ',' ') ,$invalid_data_message );
        $this->assertFalse( $woothemes_sensei->grading->set_user_quiz_grades( -2, -3, -1 ) , $invalid_data_message );
        $this->assertFalse( $woothemes_sensei->grading->set_user_quiz_grades( 3000, 5000, 5000 ) , $invalid_data_message );

        // does it return true for the right data?
        $this->assertTrue( $woothemes_sensei->grading->set_user_quiz_grades( $test_user_grades , $test_lesson_id , $test_user_id),
                            'The function should return success for valid parameters');

        //setup for the next assertions
        $test_lesson_status = WooThemes_Sensei_Utils::user_lesson_status( $test_lesson_id, $test_user_id  );
        $retrieved_quiz_grades = get_comment_meta( $test_lesson_status->comment_ID, 'quiz_grades' , true );
        $random_index = array_rand( $test_user_grades  );

        // doest it save the passed in grades correctly
        $this->assertTrue( is_array( $retrieved_quiz_grades ), 'The quiz grades was not saved correctly');
        $this->assertEquals( $test_user_grades[ $random_index ], $retrieved_quiz_grades[ $random_index ],
            'The quiz grades retrieved is not the same as those passed in when it was saved.' );

        // was the transients saved correctly?
        $transient_key = 'quiz_grades_'.$test_user_id.'_'.$test_lesson_id;
        $transient_val = get_site_transient( $transient_key );
        $this->assertFalse( empty( $transient_val ) , 'Transients are not saved correctly for user answers ' );
        $this->assertEquals( $transient_val ,$test_user_grades ,
            'The transient should be the same as the prepared answer which was base64 encoded' );

        // if saved again will the transient be updated
        $old_transient_value = $transient_val;
        $new_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
        $new_files = $this->factory->generate_test_files( $test_user_quiz_answers );
        $new_test_user_grades = $this->factory->generate_user_quiz_grades( $test_user_quiz_answers );

        $woothemes_sensei->grading->set_user_quiz_grades( $new_test_user_grades, $test_lesson_id, $test_user_id );
        $new_transient_val = get_site_transient( $transient_key );

        $this->assertNotEquals( $new_transient_val, $old_transient_value,
            'Transient not updated on new save for the same user lesson combination' );

    }// end testSetUserQuizGrades


    /**
     * Testing $woothemes->grading->get_user_quiz_grades
     */
    public function testGetUserQuizGrades(){

        global $woothemes_sensei;

        //setup the data needed for the assertions in this test
        $test_user_id = wp_create_user( 'getQuizGrades', 'getQuizGrades', 'getQuizGrades@test.com' );
        $test_lesson_id = $this->factory->get_random_lesson_id();
        $test_quiz_id = $woothemes_sensei->lesson->lesson_quizzes( $test_lesson_id );
        $test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id  );
        $files = $this->factory->generate_test_files( $test_user_quiz_answers );
        $woothemes_sensei->quiz->save_user_answers( $test_user_quiz_answers, $files , $test_lesson_id  ,  $test_user_id  );
        $test_user_grades = $this->factory->generate_user_quiz_grades( $test_user_quiz_answers );

        // make sure the method is in the class before we proceed
        $this->assertTrue( method_exists ( $woothemes_sensei->grading,'get_user_quiz_grades' ),
            'The set_user_quiz_grades method is not in class WooThemes_Sensei_Grading' );

        //does this function return false for the invalid parameters
        $invalid_data_message = 'This function does not check invalid parameters correctly';
        $this->assertFalse( $woothemes_sensei->grading->get_user_quiz_grades('','')  ,$invalid_data_message );
        $this->assertFalse( $woothemes_sensei->grading->get_user_quiz_grades(' ',' ') ,$invalid_data_message );
        $this->assertFalse( $woothemes_sensei->grading->get_user_quiz_grades( -3, -1 ) , $invalid_data_message );
        $this->assertFalse( $woothemes_sensei->grading->get_user_quiz_grades( 5000, 5000 ) , $invalid_data_message );

    }
}// end Class