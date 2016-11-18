<?php

class Sensei_Class_Course_Test extends WP_UnitTestCase {

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
     *
     * @since 1.8.0
     */
    public function testGetAllCourses() {
        // check if the function is there
        $this->assertTrue( method_exists( 'WooThemes_Sensei_Course', 'get_all_courses' ) , 'The course class get_all_courses function does not exist.' );

        //setup the assertion
        $retrieved_courses = get_posts( array('post_type' =>'course', 'posts_per_page'=>10000 ) );

        //make sure the same course were retrieved as what we just created
        $this->assertEquals( count( $retrieved_courses) , count( WooThemes_Sensei_Course::get_all_courses() )
            , 'The number of course returned is not equal to what is actually available' );

    }// end testGetAllCourses()

    /**
     *
     * This tests Sensei_Courses::get_completed_lesson_ids
     * @since 1.8.0
     */
    public function testGetCompletedLessonIds() {

        //does the function exist?
        $this->assertTrue( method_exists( 'WooThemes_Sensei_Course', 'get_completed_lesson_ids' ) , 'The course class get_completed_lesson_ids function does not exist.' );

        // setup the test
        $test_user_id = wp_create_user('getCompletedLessonIds', 'getCompletedLessonIds', 'getCompletedLessonIds@tes.co');
        $test_lessons = $this->factory->get_lessons();
        $test_course_id = $this->factory->get_random_course_id();
        remove_all_actions('sensei_user_course_start');
        WooThemes_Sensei_Utils::user_start_course( $test_user_id, $test_course_id );

        // add lessons to the course
        foreach( $test_lessons as $lesson_id ){
            add_post_meta( $lesson_id, '_lesson_course', intval( $test_course_id ) );
        }

        // complete 3 lessons
        $i = 0;
        for(  $i = 0 ; $i < 3; $i++ ){
            WooThemes_Sensei_Utils::update_lesson_status(  $test_user_id, $test_lessons[$i], 'complete');
        }

        $this->assertEquals(3 , count ( Sensei()->course->get_completed_lesson_ids( $test_course_id, $test_user_id ) ),'Course completed lesson count not accurate' );

        // complete all lessons
        foreach( $test_lessons as $lesson_id ){
            WooThemes_Sensei_Utils::update_lesson_status(  $test_user_id, $lesson_id, 'complete');
        }

        // does it return all lessons
        $this->assertEquals( count( $test_lessons ) , count ( Sensei()->course->get_completed_lesson_ids( $test_course_id, $test_user_id ) ),'Course completed lesson count not accurate' );


    }// testCountCompletedLessons

    /**
     * This tests Sensei_Courses::get_completion_percentage
     * @since 1.8.0
     */
    public function testGetCompletionPercentage() {
        //does the function exist?
        $this->assertTrue( method_exists( 'WooThemes_Sensei_Course', 'get_completion_percentage' ) , 'The course class get_completion_percentage function does not exist.' );

        // setup the test
        $test_user_id = wp_create_user('testGetCompletionPercentage', 'testGetCompletionPercentage', 'testGetCompletionPercentage@tes.co');
        $test_lessons = $this->factory->get_lessons();
        $test_course_id = $this->factory->get_random_course_id();
        remove_all_actions('sensei_user_course_start');
        WooThemes_Sensei_Utils::user_start_course( $test_user_id, $test_course_id );

        // add lessons to the course
        foreach( $test_lessons as $lesson_id ){
            add_post_meta( $lesson_id, '_lesson_course', intval( $test_course_id ) );
        }

        // complete 3 lessons and check if the correct percentage returns
        $i = 0;
        for(  $i = 0 ; $i < 3; $i++ ){
            WooThemes_Sensei_Utils::update_lesson_status(  $test_user_id, $test_lessons[$i], 'complete');
        }
        $expected_percentage = round( 3/count( $test_lessons ) * 100, 2 );
        $this->assertEquals( $expected_percentage , Sensei()->course->get_completion_percentage( $test_course_id, $test_user_id ),'Course completed percentage is not accurate' );

        // complete all lessons
        foreach( $test_lessons as $lesson_id ){
            WooThemes_Sensei_Utils::update_lesson_status(  $test_user_id, $lesson_id, 'complete');
        }
        //all lessons should no be completed
        $this->assertEquals( 100 , Sensei()->course->get_completion_percentage( $test_course_id, $test_user_id ),'Course completed percentage is not accurate' );


    }
}// end class
