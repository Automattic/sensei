<?php

class Sensei_Globals_Test extends WP_UnitTestCase {

    /**
     * Test the global $woothemes_sensei object
     */
	function testSenseiGlobalObject() {
		//setup the test
        global $woothemes_sensei;

		//test if the global sensei object is loaded
		$this->assertTrue( isset( $woothemes_sensei ), 'Sensei global object loaded '  );

		// check if the version number is setup
		$this->assertTrue( isset(Sensei()->version), 'Sensei version number is set'  );
	}

    /**
     * Test the Sensei() global function to ensure that it works and return and instance
     * for the main Sensei object
     */
    function testSenseiGlobalAccessFunction(){

        // make sure the function is loaded
        $this->assertTrue( function_exists( 'Sensei' ), "The global Sensei() function does not exist.");

        // make sure it return an instance of class WooThemes_Sensei
        $this->assertTrue( 'Sensei_Main' ==  get_class( Sensei() ),
            'The Sensei() function does not return an instance of class WooThemes_Sensei' );

    }

	function testSenseiFunctionReturnSameSenseiInstance() {
		$this->assertSame( Sensei(), Sensei(), 'Sensei() should always return the same Sensei_Main instance' );
	}

	/**
	 * Testing the version numbers before releasing the plugin.
	 *
	 * The version number in the plugin information block should match the version number specified in the code.
	 */
	function testVersionNumber() {

        // make sure the version number was set on the new sensei instance
        $this->assertTrue( isset( Sensei()->version ), 'The version number is not set on the global Sensei object');
	}
}
