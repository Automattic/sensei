<?php
/**
 * This file contains the Sensei_Import_Block_Migrator_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once SENSEI_TEST_FRAMEWORK_DIR . '/data-port/class-sensei-data-port-job-mock.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/data-port/class-sensei-import-model-mock.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/data-port/class-sensei-data-port-schema-mock.php';

/**
 * Tests for Sensei_Import_Block_Migrator class.
 *
 * @group data-port
 */
class Sensei_Import_Block_Migrator_Test extends WP_UnitTestCase {

	/**
	 * Set up the tests.
	 */
	public function setUp() {
		$this->factory = new Sensei_Factory();

		return parent::setUp();
	}

	/**
	 * Tests that content is not modified when Sensei blocks do not exists.
	 */
	public function testContentWithNoBlockUnmodified() {
		$job  = new Sensei_Data_Port_Job_Mock( 'test' );
		$task = new Sensei_Import_Courses( $job );

		$migrator = new Sensei_Import_Block_Migrator( 1, $task, Sensei_Import_Model_Mock::from_source_array( 1, [], new Sensei_Data_Port_Schema_Mock() ) );

		$content = '
			<!-- wp:paragraph -->
				<p>Some content</p>
			<!-- /wp:paragraph -->';

		$this->assertEquals( $content, $migrator->migrate( $content ) );
	}

	/**
	 * Tests that a lesson block has it's id mapped when translate_import_id returns a value.
	 */
	public function testLessonBlockIsMapped() {
		$job = $this->getMockBuilder( Sensei_Import_Job::class )
			->setConstructorArgs( [ 'test' ] )
			->getMock();

		$job->method( 'translate_import_id' )
			->willReturn( 20 );

		$task = new Sensei_Import_Courses( $job );

		$migrator = new Sensei_Import_Block_Migrator( 1, $task, Sensei_Import_Model_Mock::from_source_array( 1, [], new Sensei_Data_Port_Schema_Mock() ) );

		$content = '
			<!-- wp:sensei-lms/course-outline -->
				<!-- wp:sensei-lms/course-outline-lesson {"id":10,"title":"Without module","draft":false} -->
					<div class="wp-block-sensei-lms-course-outline-lesson"></div>
				<!-- /wp:sensei-lms/course-outline-lesson -->
			<!-- /wp:sensei-lms/course-outline -->';

		$expected_content = '
			<!-- wp:sensei-lms/course-outline -->
				<!-- wp:sensei-lms/course-outline-lesson {"id":20,"title":"Without module","draft":false} -->
					<div class="wp-block-sensei-lms-course-outline-lesson"></div>
				<!-- /wp:sensei-lms/course-outline-lesson -->
			<!-- /wp:sensei-lms/course-outline -->';

		$this->assertEquals( $expected_content, $migrator->migrate( $content ) );
	}

	/**
	 * Tests that lessons are not mapped when:
	 * 1) translate_import_id doesn't return a translated id and the id doesn't exist in the database.
	 * 2) The lesson id exists in the database but the Titles do not match.
	 */
	public function testLessonBlockIsNotIncludedWhenNotMapped() {
		$job = $this->getMockBuilder( Sensei_Import_Job::class )
			->setConstructorArgs( [ 'test' ] )
			->getMock();

		$job->method( 'translate_import_id' )
			->willReturn( null );

		$task = new Sensei_Import_Courses( $job );

		$migrator = new Sensei_Import_Block_Migrator( 1, $task, Sensei_Import_Model_Mock::from_source_array( 1, [], new Sensei_Data_Port_Schema_Mock() ) );

		$matched_lesson_id     = $this->factory->lesson->create( [ 'post_title' => 'Title Matched' ] );
		$not_matched_lesson_id = $this->factory->lesson->create( [ 'post_title' => 'Title Not Matched' ] );

		$content = '
			<!-- wp:sensei-lms/course-outline -->
			<!-- wp:sensei-lms/course-outline-lesson {"id":10,"title":"Without module","draft":false} -->
			<div class="wp-block-sensei-lms-course-outline-lesson"></div>
			<!-- /wp:sensei-lms/course-outline-lesson -->
			<!-- wp:sensei-lms/course-outline-lesson {"id":' . $matched_lesson_id . ',"title":"Title Matched","draft":false} -->
			<div class="wp-block-sensei-lms-course-outline-lesson"></div>
			<!-- /wp:sensei-lms/course-outline-lesson -->
			<!-- wp:sensei-lms/course-outline-lesson {"id":' . $not_matched_lesson_id . ',"title":"Not Matching","draft":false} -->
			<div class="wp-block-sensei-lms-course-outline-lesson"></div>
			<!-- /wp:sensei-lms/course-outline-lesson -->
			<!-- /wp:sensei-lms/course-outline -->';

		$expected_content = '
			<!-- wp:sensei-lms/course-outline -->
			
			<!-- wp:sensei-lms/course-outline-lesson {"id":' . $matched_lesson_id . ',"title":"Title Matched","draft":false} -->
			<div class="wp-block-sensei-lms-course-outline-lesson"></div>
			<!-- /wp:sensei-lms/course-outline-lesson -->
			
			<!-- /wp:sensei-lms/course-outline -->';

		$this->assertEquals( $expected_content, $migrator->migrate( $content ) );
	}

	/**
	 * Tests that a module block is mapped when the module with the given name exists and it is linked with the course.
	 */
	public function testModuleBlockIsMapped() {
		$job = $this->getMockBuilder( Sensei_Import_Job::class )
			->setConstructorArgs( [ 'test' ] )
			->getMock();

		$job->method( 'translate_import_id' )
			->willReturn( 20 );

		$task = new Sensei_Import_Courses( $job );

		$module_id = $this->factory->module->create( [ 'name' => 'Module' ] );
		$course_id = $this->factory->course->create();
		wp_set_object_terms( $course_id, $module_id, 'module' );

		$migrator = new Sensei_Import_Block_Migrator( $course_id, $task, Sensei_Import_Model_Mock::from_source_array( 1, [], new Sensei_Data_Port_Schema_Mock() ) );

		$content = '
		<!-- wp:sensei-lms/course-outline -->
			<!-- wp:sensei-lms/course-outline-module {"id":50,"title":"Module","description":"Module desc"} -->
				<!-- wp:sensei-lms/course-outline-lesson {"id":10,"title":"Lesson","draft":false} -->
				<div class="wp-block-sensei-lms-course-outline-lesson"></div>
				<!-- /wp:sensei-lms/course-outline-lesson -->
			<!-- /wp:sensei-lms/course-outline-module -->
		<!-- /wp:sensei-lms/course-outline -->';

		$expected_content = '
		<!-- wp:sensei-lms/course-outline -->
			<!-- wp:sensei-lms/course-outline-module {"id":' . $module_id . ',"title":"Module","description":"Module desc"} -->
				<!-- wp:sensei-lms/course-outline-lesson {"id":20,"title":"Lesson","draft":false} -->
				<div class="wp-block-sensei-lms-course-outline-lesson"></div>
				<!-- /wp:sensei-lms/course-outline-lesson -->
			<!-- /wp:sensei-lms/course-outline-module -->
		<!-- /wp:sensei-lms/course-outline -->';

		$this->assertEquals( $expected_content, $migrator->migrate( $content ) );
	}

	/**
	 * Tests that modules which do not exist in the databases are not mapped.
	 */
	public function testNotFoundModuleIsNotMapped() {
		$job = $this->getMockBuilder( Sensei_Import_Job::class )
			->setConstructorArgs( [ 'test' ] )
			->getMock();

		$task = new Sensei_Import_Courses( $job );

		$migrator = new Sensei_Import_Block_Migrator( 1, $task, Sensei_Import_Model_Mock::from_source_array( 1, [], new Sensei_Data_Port_Schema_Mock() ) );

		$content = '
		<!-- wp:sensei-lms/course-outline -->
			<!-- wp:sensei-lms/course-outline-module {"id":50,"title":"Module","description":"Module desc"} -->
				<!-- wp:sensei-lms/course-outline-lesson {"id":10,"title":"Lesson","draft":false} -->
				<div class="wp-block-sensei-lms-course-outline-lesson"></div>
				<!-- /wp:sensei-lms/course-outline-lesson -->
			<!-- /wp:sensei-lms/course-outline-module -->
		<!-- /wp:sensei-lms/course-outline -->';

		$expected_content = '
		<!-- wp:sensei-lms/course-outline -->
			
		<!-- /wp:sensei-lms/course-outline -->';

		$this->assertEquals( $expected_content, $migrator->migrate( $content ) );
	}
}
