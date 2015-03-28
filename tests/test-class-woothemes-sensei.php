<?php

class Sensei_Globals_Test extends WP_UnitTestCase {


	function testSensei() {
		//setup the test
		global $woothemes_sensei;
		//test if the global sensei object is loaded
		$this->assertTrue( isset($woothemes_sensei), 'Sensei global object loaded '  );

		// check if the version number is setup
		$this->assertTrue( isset($woothemes_sensei->version), 'Sensei version number is set'  );
	}

	function testSubClassLesson() {
		//setup the test
		global $woothemes_sensei;

		//test if the lesson object is loaded
		$this->assertTrue( isset($woothemes_sensei->lesson ), 'Sensei lesson is not object loaded'  );
	}

	/**
	 * Testing the version numbers before releasing the plugin.
	 *
	 * The version number in the plugin information block should match the version number specified in the code.
	 */
	function testVersionNumber() {
		// read the plugin and get the version number
		//--> use  built in wp function

		// load an instance of the main class and check the version number
		//--> this should be loaded in global $woothemes_sensei.
		//test if the lesson object is loaded
		//$this->assertTrue( false , 'The plugin listings version and the code variable version numbers do not match'  );
	}
}