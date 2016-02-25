<?php

class Sensei_Class_Student_Test extends WP_UnitTestCase {

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
    public function setUp() {

        // load the factory class
        $this->factory = new Sensei_Factory();

    }// end function setup()

    /**
     * Testing the quiz class to make sure it is loaded
     */
    public function testClassInstance(){

        //test if the global sensei quiz class is loaded
        $this->assertTrue( class_exists('Sensei_Learner'), 'the Sensei student class is not loaded' );

    } // end testClassInstance

    /**
     * Testing the get_learner_full_name function. This function tests the basic assumptions.
     */
    public function testGetLearnerFullNameBasicAssumptions(){


        //does the function exist?
        $this->assertTrue( method_exists( 'Sensei_Learner', 'get_full_name'),
            'The learner class function `get_full_name` does not exist ' );

        // make sure it blocks invalid parameters and returns false
        $this->assertFalse( Sensei_Learner::get_full_name( '' ), 'Invalid user_id should return false'  );
        $this->assertFalse( Sensei_Learner::get_full_name( -200 ), 'Invalid user_id should return false'  );
        $this->assertFalse( Sensei_Learner::get_full_name( 'abc' ), 'Invalid user_id should return false'  );
        $this->assertFalse( Sensei_Learner::get_full_name( 4000000 ), 'Invalid user_id should return false'  );

    }// end testGetLearnerFullName

    /**
     * Testing the get_learner_full_name function to see if it returns what is expected.
     */
    public function testGetLearnerFullName(){

        //setup assertion
        $test_user_id = wp_create_user( 'getlearnertestuser','password', 'getlearnertestuser@sensei-test.com'  );

        $this->assertEquals( 'getlearnertestuser', Sensei_Learner::get_full_name( $test_user_id ),
            'The user name should be equal to the display name when no first name and last name is specified');

        //setup the next assertion
        $first_name = 'Test';
        $last_name =  'User';
        $updated_user_data = array( 'ID' => $test_user_id, 'first_name'=> $first_name, 'last_name'=> $last_name  );
        wp_update_user( $updated_user_data );

        // does the function return 'first-name last-name' string?
        $this->assertEquals( 'Test User', Sensei_Learner::get_full_name( $test_user_id ),
            'This function should return the users first name and last name.');

    }//

}// end class Sensei_Class_Learners_Test