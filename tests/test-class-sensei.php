<?php

class Sensei_Globals_Test extends WP_UnitTestCase {

    /**
     * Test the global $woothemes_sensei object
     */
	function testSenseiGlobalObject() {
		//setup the test
		global $woothemes_sensei;
		//test if the global sensei object is loaded
		$this->assertTrue( isset($woothemes_sensei), 'Sensei global object loaded '  );

		// check if the version number is setup
		$this->assertTrue( isset($woothemes_sensei->version), 'Sensei version number is set'  );
	}

    /**
     * Test the Sensei() global function to ensure that it works and return and instance
     * for the main Sensei object
     */
    function testSenseiGlobalAccessFunction(){

        // make sure the function is loaded
        $this->assertTrue( function_exists( 'Sensei' ), "The global Sensei() function does not exist.");

        // make sure it return an instance of class WooThemes_Sensei
        $this->assertTrue( 'WooThemes_Sensei' ==  get_class( Sensei() ),
            'The Sensei() function does not return an instance of class WooThemes_Sensei' );

    }

	/**
	 * Testing the version numbers before releasing the plugin.
	 *
	 * The version number in the plugin information block should match the version number specified in the code.
	 */
	function testVersionNumber() {

        // make sure the version number was set on the new sensei instance
        $this->assertTrue( isset( Sensei()->version ), 'The version number is not set on the global Sensei object');

		// read the plugin and get the version number
		//--> use  built in wp function

		// load an instance of the main class and check the version number
		//--> this should be loaded in global $woothemes_sensei.
		//test if the lesson object is loaded
		//$this->assertTrue( false , 'The plugin listings version and the code variable version numbers do not match'  );
	}
}