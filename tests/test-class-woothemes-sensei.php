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
}
