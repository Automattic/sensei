<?php

class Sensei_Class_Lesson_Test extends WP_UnitTestCase {

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
        $this->assertTrue( class_exists('WooThemes_Sensei_Lesson'), 'Sensei Lesson class does not exist' );

        //test if the global sensei lesson class is loaded
        $this->assertTrue( isset( Sensei()->lesson ), 'Sensei lesson class is not loaded on the global sensei Object' );

    } // end testClassInstance


    /**
     * Testing the is lesson pre-requisite completed function.
     *
     * @since 1.9.0
     */
    public function testIsPreRequisiteComplete() {

        // does this function add_user_data exist?
        $this->assertTrue( method_exists( 'WooThemes_Sensei_Lesson', 'is_prerequisite_complete'),
            'The lesson class function `is_prerequisite_complete` does not exist ' );

        // falsy state
        $user_id = 0;
        $lesson_id = 0;
        $this->assertFalse( WooThemes_Sensei_Lesson::is_prerequisite_complete( $lesson_id, $user_id ),
            'None existing lesson or user should return false');

        $test_user_id = wp_create_user( 'studentPrerequisite', 'studentPrerequisite', 'studentPrerequisite@test.com' );
        $test_lesson = $this->factory->get_lessons();
        $test_lesson_id = $test_lesson[0];

        // truthy state
        $course_id = $this->factory->get_random_course_id();
        $lessons = $this->factory->get_lessons();
        $test_lesson_prerequisite_id = $lessons[1];

        // add lesson to random course
        update_post_meta( $test_lesson_prerequisite_id, '_lesson_course', $course_id   );
        update_post_meta( $test_lesson_id, '_lesson_course', $course_id );

        // setup prerequisite
        update_post_meta( $test_lesson_id,'_lesson_prerequisite', $test_lesson_prerequisite_id);

        Sensei_Utils::user_start_lesson( $test_user_id ,$test_lesson_prerequisite_id );
        $this->assertFalse( WooThemes_Sensei_Lesson::is_prerequisite_complete( $test_lesson_id, $test_user_id ),
            'Users that has NOT completeded prerequisite should return false.');

        Sensei_Utils::user_start_lesson( $test_user_id, $test_lesson_prerequisite_id, true );
        $this->assertTrue( Sensei_Lesson::is_prerequisite_complete( $test_lesson_id, $test_user_id ),
            'Users that has completeded prerequisite should return true.');

    } // end testIsPreRequisiteComplete

}// end class
