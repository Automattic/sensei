<?php
namespace SenseiTest\WPML;

use Sensei\WPML\Language_Details;

/**
 * Class Language_Details_Test
 *
 * @covers \Sensei\WPML\Language_Details
 */
class Language_Details_Test extends \WP_UnitTestCase {
	public function testSetLanguageDetailsWhenLessonCreated_WhenCalled_AppliesWpmlElementLanguageCodeFilter() {
		/* Arrange. */
		$language_details = new Language_Details();

		$filter_applied  = false;
		$filter_function = function ( $language_code ) use ( &$filter_applied ) {
			$filter_applied = true;
			return $language_code;
		};

		add_filter( 'wpml_element_language_code', $filter_function, 10, 1 );

		/* Act. */
		$language_details->set_language_details_when_lesson_created( 1, 2 );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $filter_function, 10 );

		$this->assertTrue( $filter_applied );
	}

	public function testSetLanguageDetailsWhenLessonCreated_WhenCalled_AppliesWpmlCurrentLangugeFilter() {
		/* Arrange. */
		$language_details = new Language_Details();

		$filter_language_code_function = function () {
			return null;
		};
		add_filter( 'wpml_element_language_code', $filter_language_code_function, 10, 0 );

		$filter_applied  = false;
		$filter_function = function ( $language_code ) use ( &$filter_applied ) {
			$filter_applied = true;
			return $language_code;
		};
		add_filter( 'wpml_current_language', $filter_function, 10, 1 );

		/* Act. */
		$language_details->set_language_details_when_lesson_created( 1, 2 );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $filter_language_code_function, 10 );
		remove_filter( 'wpml_current_language', $filter_function, 10 );

		$this->assertTrue( $filter_applied );
	}

	public function testSetLanguageDetailsWhenLessonCreated_WhenCalled_AppliesWpmlSetElementLanguageDetails() {
		/* Arrange. */
		$language_details = new Language_Details();

		$filter_applied  = false;
		$filter_function = function ( $data ) use ( &$filter_applied ) {
			$filter_applied = true;
			return $data;
		};

		add_filter( 'wpml_set_element_language_details', $filter_function, 10, 1 );

		/* Act. */
		$language_details->set_language_details_when_lesson_created( 1, 2 );

		/* Clean up & Assert. */
		remove_filter( 'wpml_set_element_language_details', $filter_function, 10 );

		$this->assertTrue( $filter_applied );
	}

	public function testSetLanguageDetailsWhenQuizCreated_WhenCalled_AppliesWpmlElementLanguageCodeFilter() {
		/* Arrange. */
		$language_details = new Language_Details();

		$filter_applied  = false;
		$filter_function = function ( $language_code ) use ( &$filter_applied ) {
			$filter_applied = true;
			return $language_code;
		};

		add_filter( 'wpml_element_language_code', $filter_function, 10, 1 );

		/* Act. */
		$language_details->set_language_details_when_quiz_created( 1, 2 );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $filter_function, 10 );

		$this->assertTrue( $filter_applied );
	}

	public function testSetLanguageDetailsWhenQuizCreated_WhenCalled_AppliesWpmlCurrentLangugeFilter() {
		/* Arrange. */
		$language_details = new Language_Details();

		$filter_language_code_function = function () {
			return null;
		};
		add_filter( 'wpml_element_language_code', $filter_language_code_function, 10, 0 );

		$filter_applied  = false;
		$filter_function = function ( $language_code ) use ( &$filter_applied ) {
			$filter_applied = true;
			return $language_code;
		};
		add_filter( 'wpml_current_language', $filter_function, 10, 1 );

		/* Act. */
		$language_details->set_language_details_when_quiz_created( 1, 2 );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $filter_language_code_function, 10 );
		remove_filter( 'wpml_current_language', $filter_function, 10 );

		$this->assertTrue( $filter_applied );
	}

	public function testSetLanguageDetailsWhenQuizCreated_WhenCalled_AppliesWpmlSetElementLanguageDetails() {
		/* Arrange. */
		$language_details = new Language_Details();

		$filter_applied  = false;
		$filter_function = function ( $data ) use ( &$filter_applied ) {
			$filter_applied = true;
			return $data;
		};

		add_filter( 'wpml_set_element_language_details', $filter_function, 10, 1 );

		/* Act. */
		$language_details->set_language_details_when_quiz_created( 1, 2 );

		/* Clean up & Assert. */
		remove_filter( 'wpml_set_element_language_details', $filter_function, 10 );

		$this->assertTrue( $filter_applied );
	}

	public function testSetLanguageDetailsWhenQuestionCreated_WhenCalled_AppliesWpmlCurrentLanguageFilter() {
		/* Arrange. */
		$language_details = new Language_Details();

		$filter_applied  = false;
		$filter_function = function () use ( &$filter_applied ) {
			$filter_applied = true;
			return 'a';
		};

		add_filter( 'wpml_current_language', $filter_function, 10, 0 );

		/* Act. */
		$language_details->set_language_details_when_question_created( 1 );

		/* Clean up & Assert. */
		remove_filter( 'wpml_current_language', $filter_function, 10 );

		$this->assertTrue( $filter_applied );
	}

	public function testSetLanguageDetailsWhenQuestionCreated_WhenCalled_AppliesWpmlSetElementLanguageDetails() {
		/* Arrange. */
		$language_details = new Language_Details();

		$filter_applied  = false;
		$filter_function = function ( $data ) use ( &$filter_applied ) {
			$filter_applied = true;
			return $data;
		};

		add_filter( 'wpml_set_element_language_details', $filter_function, 10, 1 );

		/* Act. */
		$language_details->set_language_details_when_question_created( 1 );

		/* Clean up & Assert. */
		remove_filter( 'wpml_set_element_language_details', $filter_function, 10 );

		$this->assertTrue( $filter_applied );
	}

	public function testSetLanguageDetailsWhenMultipleQuestionCreated_WhenCalled_AppliesWpmlCurrentLanguageFilter() {
		/* Arrange. */
		$language_details = new Language_Details();

		$filter_applied  = false;
		$filter_function = function () use ( &$filter_applied ) {
			$filter_applied = true;
			return 'a';
		};

		add_filter( 'wpml_current_language', $filter_function, 10, 0 );

		/* Act. */
		$language_details->set_language_details_when_multiple_question_created( 1 );

		/* Clean up & Assert. */
		remove_filter( 'wpml_current_language', $filter_function, 10 );

		$this->assertTrue( $filter_applied );
	}

	public function testSetLanguageDetailsWhenMultipleQuestionCreated_WhenCalled_AppliesWpmlSetElementLanguageDetails() {
		/* Arrange. */
		$language_details = new Language_Details();

		$filter_applied  = false;
		$filter_function = function ( $data ) use ( &$filter_applied ) {
			$filter_applied = true;
			return $data;
		};

		add_filter( 'wpml_set_element_language_details', $filter_function, 10, 1 );

		/* Act. */
		$language_details->set_language_details_when_multiple_question_created( 1 );

		/* Clean up & Assert. */
		remove_filter( 'wpml_set_element_language_details', $filter_function, 10 );

		$this->assertTrue( $filter_applied );
	}
}
