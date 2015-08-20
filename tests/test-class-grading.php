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
}// end Class