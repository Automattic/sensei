<?php
class Sensei_Class_Quiz_Test extends WP_UnitTestCase {

    /**
     * setup function
     */
    function setup(){

    }

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
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( -1000, -200, [] ) , 'save_user_answers does not return false for no existent users and lesson ' );
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( '', '' , '' ) , 'save_user_answers does not return false for empty parameters' );


        // does the function return the correct information when a user doesn't exist?
        // create test lesson
        $new_lesson_args = array (
            'post_content'   => 'test content',
            'post_name'      => 'test-lesson' ,
            'post_title'     => 'test-lesson' ,
            'post_status'    => 'publish',
            'post_type' => 'lesson' );
        $new_lesson_id = wp_insert_post ( $new_lesson_args );
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( $new_lesson_id , '' , '' ) , 'save_user_answers does not return false for empty user' );
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( $new_lesson_id , -500 , '' ) , 'save_user_answers does not return false for a non existant user' );

        // Test the answers array
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( 1, $test_user_id , '' ) , 'save_user_answers does not return false for empty answer array' );
        $this->assertFalse(  $woothemes_sensei->quiz->save_user_answers( '', '' , '' ) , 'save_user_answers does not return false incorrectly formatted answers' );


        //setup the answers to be saved

        //save the answers

        // check the database to see if it was saved correctly

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
