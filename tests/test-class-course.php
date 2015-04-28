<?php

class Sensei_Class_Course_Test extends WP_UnitTestCase {

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
     * Testing the quiz class to make sure it is loaded
     */
    public function testClassInstance() {

        //test if the global sensei quiz class is loaded
        $this->assertTrue( isset( Sensei()->course ), 'Sensei Course class is not loaded' );

    } // end testClassInstance

    /**
     * This tests Woothemes_Sensei()->quiz->get_question_type
     */
    //public function testGetQuestionType() {

    //}// end testGetQuestionType()
}