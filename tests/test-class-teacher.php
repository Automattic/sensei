<?php

class Sensei_Class_Teacher_Test extends WP_UnitTestCase {

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
     *
     */
    public function tearDown(){

        // remove all courses
        $lessons = get_posts( 'post_type=course' );
        foreach( $lessons as $index => $lesson ){
            wp_delete_post( $lesson->ID , true );
        }// end for each

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

    }// end tearDown

    /**
     * Testing the quiz class to make sure it is loaded
     */
    public function testClassInstance() {

        //test if the global sensei quiz class is loaded
        $this->assertTrue( isset( Sensei()->teacher ), 'Sensei Modules class is not loaded' );

    } // end testClassInstance

    /**
     * Testing Sensei_Teacher::update_course_modules_author
     * This test focus on changing module author
     *
     * @since 1.8.0
     */
    public function testUpdateCourseModulesAuthorChange(){

        // setup assertions
        $test_teacher_id = wp_create_user( 'teacherCourseModulesAuthor', 'teacherCourseModulesAuthor', 'teacherCourseModulesAuthor@test.com' );

        // create test course with current admin as owner
        $test_course_id = $this->factory->get_random_course_id();
        $administrator = get_user_by('email', get_bloginfo('admin_email') );
        wp_update_post( array( 'ID' => $test_course_id, 'post_author'=> $administrator->ID ) );

        //insert sample module terms
        $term_start = wp_insert_term('Sample Test Start', 'module');
        $term_end = wp_insert_term('Sample Test End', 'module');

        // assign sample terms to course
        wp_set_object_terms( $test_course_id, array( $term_start['term_id'], $term_end['term_id']  ), 'module', true );

        // run the function passing in new teacher
        Sensei_Teacher::update_course_modules_author( $test_course_id, $test_teacher_id );

        // set the current active user to be the teacher so that get object terms
        // only return the teachers terms
        $current_user = get_current_user_id();
        wp_set_current_user( $test_teacher_id );

        // check the if the object terms have change to the new new user within the slug
        $updated_module_terms = wp_get_object_terms( $test_course_id,'module' );
        $assert_message = 'Course module term authors not updated.';
        foreach( $updated_module_terms as $term ){

            // skip $term_start and $term_end
            if( $term_start['term_id'] == $term->term_id ||  $term_end['term_id'] ==  $term->term_id ){
                continue;
            }

            $updated_author = Sensei_Core_Modules::get_term_author( $term->slug );
            $this->assertEquals( $test_teacher_id, $updated_author->ID , $assert_message );

        }

        // reset current user for other tests
        wp_set_current_user( $current_user );

    } // end test author change

} // end class
