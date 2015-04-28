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
     * @since 1.8.0
     */
    public function testClassInstance() {

        //test if the class exists
        $this->assertTrue( class_exists('WooThemes_Sensei_Course'), 'Sensei course class does not exist' );

        //test if the global sensei quiz class is loaded
        $this->assertTrue( isset( Sensei()->course ), 'Sensei Course class is not loaded' );

    } // end testClassInstance

    /**
     * This tests Sensei_Courses::get_all_course
     */
    public function testCountCompletedLessons() {
        // setup the test
        foreach( $this->factory->lesson_ids as $lesson_id ){
    public function testGetAllCourses() {

        }
    }// end testGetQuestionType()
}        // check if the function is there
        $this->assertTrue( method_exists( 'WooThemes_Sensei_Course', 'get_all_courses' ) , 'The course class get_all_courses function does not exist.' );

        //setup the assertion
        $created_courses_count = 4;
        $this->factory->generate_courses( $created_courses_count );
        $retrieved_courses = get_posts(array('post_type'=>'course'));
        //make sure the same course were retrieved as what we just created

        $this->assertEquals( count( $retrieved_courses) , count( WooThemes_Sensei_Course::get_all_courses() )
            , 'The number of course returned is not equal to what is actually available' );
    }// end testGetAllCourses()

}// end class