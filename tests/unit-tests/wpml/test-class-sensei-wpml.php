<?php

namespace SenseiTest\WPML;

use \Sensei_WPML;

/**
* Class Sensei_WPML_Test.
*
* @covers \Sensei_WPML
*/
class Sensei_WPML_Test extends \WP_UnitTestCase {
	public function testSetLanguageDetailsWhenLessonCreated_WhenCalled_AppliesWpmlElementLanguageCodeFilter() {
		/* Arrange. */
		$wpml = new Sensei_WPML();

		$filter_applied  = false;
		$filter_function = function( $language_code, $element_data ) use ( &$filter_applied ) {
			$filter_applied = true;
			return $language_code;
		};

		add_filter( 'wpml_element_language_code', $filter_function, 10, 2 );

		/* Act. */
		$wpml->set_language_details_when_lesson_created( 1, 2 );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $filter_function, 10 );

		$this->assertTrue( $filter_applied );
	}

	public function testSetLanguageDetailsWhenLessonCreated_WhenCalled_AppliesWpmlCurrentLangugeFilter() {
		/* Arrange. */
		$wpml = new Sensei_WPML();

		$filter_language_code_function = function( $language_code, $element_data ) use ( &$filter_applied ) {
			return null;
		};
		add_filter( 'wpml_element_language_code', $filter_language_code_function, 10, 2 );

		$filter_applied  = false;
		$filter_function = function( $language_code ) use ( &$filter_applied ) {
			$filter_applied = true;
			return $language_code;
		};
		add_filter( 'wpml_current_language', $filter_function, 10, 1 );

		/* Act. */
		$wpml->set_language_details_when_lesson_created( 1, 2 );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $filter_language_code_function, 10 );
		remove_filter( 'wpml_current_language', $filter_function, 10 );

		$this->assertTrue( $filter_applied );
	}

	public function testSetLanguageDetailsWhenLessonCreated_WhenCalled_AppliesWpmlSetElementLanguageDetails() {
		/* Arrange. */
		$wpml = new Sensei_WPML();

		$filter_applied  = false;
		$filter_function = function( $data ) use ( &$filter_applied ) {
			$filter_applied = true;
			return $data;
		};

		add_filter( 'wpml_set_element_language_details', $filter_function, 10, 1 );

		/* Act. */
		$wpml->set_language_details_when_lesson_created( 1, 2 );

		/* Clean up & Assert. */
		remove_filter( 'wpml_set_element_language_details', $filter_function, 10 );

		$this->assertTrue( $filter_applied );
	}

	public function testSetLanguageDetailsWhenQuizCreated_WhenCalled_AppliesWpmlElementLanguageCodeFilter() {
		/* Arrange. */
		$wpml = new Sensei_WPML();

		$filter_applied  = false;
		$filter_function = function( $language_code, $element_data ) use ( &$filter_applied ) {
			$filter_applied = true;
			return $language_code;
		};

		add_filter( 'wpml_element_language_code', $filter_function, 10, 2 );

		/* Act. */
		$wpml->set_language_details_when_quiz_created( 1, 2 );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $filter_function, 10 );

		$this->assertTrue( $filter_applied );
	}

	public function testSetLanguageDetailsWhenQuizCreated_WhenCalled_AppliesWpmlCurrentLangugeFilter() {
		/* Arrange. */
		$wpml = new Sensei_WPML();

		$filter_language_code_function = function( $language_code, $element_data ) use ( &$filter_applied ) {
			return null;
		};
		add_filter( 'wpml_element_language_code', $filter_language_code_function, 10, 2 );

		$filter_applied  = false;
		$filter_function = function( $language_code ) use ( &$filter_applied ) {
			$filter_applied = true;
			return $language_code;
		};
		add_filter( 'wpml_current_language', $filter_function, 10, 1 );

		/* Act. */
		$wpml->set_language_details_when_quiz_created( 1, 2 );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $filter_language_code_function, 10 );
		remove_filter( 'wpml_current_language', $filter_function, 10 );

		$this->assertTrue( $filter_applied );
	}
	public function testSetLanguageDetailsWhenQuizCreated_WhenCalled_AppliesWpmlSetElementLanguageDetails() {
		/* Arrange. */
		$wpml = new Sensei_WPML();

		$filter_applied  = false;
		$filter_function = function( $data ) use ( &$filter_applied ) {
			$filter_applied = true;
			return $data;
		};

		add_filter( 'wpml_set_element_language_details', $filter_function, 10, 1 );

		/* Act. */
		$wpml->set_language_details_when_quiz_created( 1, 2 );

		/* Clean up & Assert. */
		remove_filter( 'wpml_set_element_language_details', $filter_function, 10 );

		$this->assertTrue( $filter_applied );
	}
}
