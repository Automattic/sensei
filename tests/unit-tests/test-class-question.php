<?php

class Sensei_Class_Question_Test extends WP_UnitTestCase {

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
    }// end function setup()

    /**
     * Testing the quiz class to make sure it is loaded
     */
    public function testClassInstance() {
        //setup the test


        //test if the global sensei quiz class is loaded
        $this->assertTrue( isset( Sensei()->question ), 'Sensei Question class is not loaded' );

    } // end testClassInstance

    /**
     * This tests Woothemes_Sensei()->quiz->get_question_type
     */
    public function testGetQuestionType() {



        // doe this method exist on the quiz class?
        $this->assertTrue( method_exists( 'WooThemes_Sensei_Quiz', 'submit_answers_for_grading'  ) ,
            'The method get_question_type does not exist ');

        // does this method return false for the wrong data?
        $should_be_false = Sensei()->question->get_question_type('');
        $this->assertFalse( $should_be_false ,
            'The method get_question_type should return false for an empty string parameter');

        // does this method return false for the wrong data?
        $should_be_false = Sensei()->question->get_question_type('');
        $this->assertFalse( $should_be_false ,
            'The method get_question_type should return false for an empty string parameter');
        $should_be_false = Sensei()->question->get_question_type( 2000 );
        $this->assertFalse( $should_be_false ,
            'The method get_question_type should return false for an empty string parameter');

        // does this method return a string for a valid question id
        $questions = get_posts( 'post_type=question' );
        $should_be_question_type = Sensei()->question->get_question_type( $questions[  array_rand( $questions ) ]->ID );
        $sensei_question_types =   array_keys( Sensei()->question->question_types() );
        $this->assertTrue( in_array( $should_be_question_type, $sensei_question_types   ) ,
            'The method get_question_type should return false for an empty string parameter');

    }// end testGetQuestionType()
}