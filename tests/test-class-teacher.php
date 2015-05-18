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

    /**
     * Testing Sensei_Teacher::update_course_modules_author
     * Test to see if the lessons in the course was assigned to
     * a new author.
     *
     * @since 1.8.0
     */
    public function testUpdateCourseModulesAuthorChangeLessons(){

        // setup assertions
        $test_teacher_id = wp_create_user( 'teacherCourseModulesAuthorLessons', 'teacherCourseModulesAuthorLessons', 'teacherCourseModulesAuthorLessons@test.com' );

        // create test course with current admin as owner
        $test_course_id = $this->factory->get_random_course_id();
        $administrator = get_user_by('email', get_bloginfo('admin_email') );
        wp_update_post( array( 'ID' => $test_course_id, 'post_author'=> $administrator->ID ) );

        //insert sample module terms
        $test_module_1 = wp_insert_term('Lesson Test Module', 'module');
        $test_module_2 = wp_insert_term('Lesson Test Module 2', 'module');

        // assign sample terms to course
        wp_set_object_terms( $test_course_id, array( $test_module_1['term_id'], $test_module_2['term_id']  ), 'module', true );

        // add sample lessons to course and assign them to modules
        $test_lessons = $this->factory->get_lessons();
        foreach( $test_lessons as $lesson_id ){
            update_post_meta( $lesson_id, '_lesson_course', intval( $test_course_id ) );
        }

        // split array in 2 and assign each group of lessons to one of the modules
        $array_middle = round( ( count( $test_lessons ) + 1 ) /2 );
        $lesson_in_module_1 = array_slice( $test_lessons, 0, $array_middle);
        $lesson_in_module_2 = array_slice( $test_lessons, $array_middle );

        // assign lessons to module 1
        foreach( $lesson_in_module_1 as $lesson_id ){
            wp_set_object_terms( $lesson_id, $test_module_1['term_id'], 'module', false );
        }

        // assign lessons to module 2
        foreach( $lesson_in_module_2 as $lesson_id ){
            wp_set_object_terms( $lesson_id, $test_module_2['term_id'], 'module', false );
        }

        // Do the update changing the author
        Sensei_Teacher::update_course_modules_author( $test_course_id, $test_teacher_id );

        // check each lesson

        // do the lessons for module 1 group now belong to ta new module term with the new teacher as owner?
        $expected_module_1_slug = $test_teacher_id .  '-' . str_ireplace(' ', '-', strtolower( ( 'Lesson Test Module' ) ) );
        foreach( $lesson_in_module_1 as $lesson_id ){

            $term_after_update = wp_get_object_terms( $lesson_id, 'module' );
            $this->assertEquals( $expected_module_1_slug, $term_after_update[0]->slug , 'Lesson module was not updated, ID: '. $lesson_id    );

        }

        // do the lessons for module 2 group now belong to ta new module term with the new teacher as owner?
        $expected_module_2_slug = $test_teacher_id .  '-' . str_ireplace(' ', '-', strtolower( trim( 'Lesson Test Module 2' ) ) );
        foreach( $lesson_in_module_2 as $lesson_id ){
            $term_after_update = wp_get_object_terms( $lesson_id, 'module' );
            $this->assertEquals( $expected_module_2_slug, $term_after_update[0]->slug, 'Lesson module was not updated, ID: '. $lesson_id    );
        }

    }// end testUpdateCourseModulesAuthorChangeLessons

} // end class
