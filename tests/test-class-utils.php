<?php

class Sensei_Class_Utils_Test extends WP_UnitTestCase {

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
     * This function sets up the lessons, quizzes and their questions. This function runs before
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
        global $woothemes_sensei;

        //test if the global sensei quiz class is loaded
        $this->assertTrue( class_exists( 'WooThemes_Sensei_Utils' ), 'Sensei Utils class constant is not loaded' );
    } // end testClassInstance

    /**
     * This tests Woothemes_Sensei_Utils::update_user_data
     */
    public function testUpdateUserData(){

        //setup data needed for this test
        $test_user_id = wp_create_user( 'testUpdateUserData', 'testUpdateUserData', 'testUpdateUserData@test.com' );

        //remove this action so that no emails are sent during this test
        remove_all_actions( 'sensei_user_course_start' );

        // does this function add_user_data exist?
        $this->assertTrue( method_exists( 'WooThemes_Sensei_Utils', 'update_user_data'),
            'The utils class function `update_user_data` does not exist ' );

        // does it return false for invalid data
        $invalid_data_message = 'This function does not check false data correctly';
        $this->assertFalse( WooThemes_Sensei_Utils::update_user_data('','','','')  ,
                            $invalid_data_message. ": '','','','' "  );
        $this->assertFalse( WooThemes_Sensei_Utils::update_user_data( ' ', ' ',' ',' ') ,
                            $invalid_data_message . ": ' ', ' ', ' ', ' ' " );
        $this->assertFalse( WooThemes_Sensei_Utils::update_user_data( -1,-2, -3, -1 ) ,
                            $invalid_data_message.": -1,-2, -3, -1 " );
        $this->assertFalse( WooThemes_Sensei_Utils::update_user_data(500, 'key', 'val', 5000 ) ,
                            $invalid_data_message.": 500, 'key', 'val', 5000 " );

        //does this function return false when attempting to add user data on non sensei post types
        $test_post = $this->factory->post->create();
        $this->assertFalse( WooThemes_Sensei_Utils::update_user_data( $test_post , 'key', 'val', $test_user_id ) ,
            'This function does not reject unsupported post types' );

        //does this function return false when attempting to add user data on non sensei post types
        $test_array = array( 1, 2, 3 , 4);
        $test_course_id = $this->factory->post->create(array( 'post_type'=>"course" ) );
        $test_data_key = 'test_key';
        WooThemes_Sensei_Utils::update_user_data( $test_course_id, $test_data_key ,$test_array, $test_user_id  );
        $course_status = WooThemes_Sensei_Utils::user_course_status( $test_course_id, $test_user_id );

        // is the status updated on the passed in sensei post type ?
        $this->assertTrue( isset( $course_status->comment_ID ),
            'This function did not create the status on the passed in sensei post type' );

        // setup the next group of assertions
        $retrieved_array = get_comment_meta( $course_status->comment_ID, $test_data_key , true );

        // is the data saved still intact
        $this->assertEquals( $test_array, $retrieved_array, 'The saved and retrieved data does not match' );

    }// end testUpdateUserData

}// end test class