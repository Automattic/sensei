<?php
/**
 * This file contains the Sensei_Import_Courses_Tests class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Import_Courses class.
 *
 * @group data-port
 */
class Sensei_Import_Courses_Tests extends WP_UnitTestCase {

	/**
	 * Setup function.
	 */
	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Test to make sure prerequisites are handled correctly.
	 */
	public function testHandlePrerequisiteHandled() {
		$course_id        = $this->factory->course->create();
		$course_prereq_id = $this->factory->course->create(
			[
				'post_name' => 'a-secret-course',
			]
		);

		$job    = Sensei_Import_Job::create( 'test', 0 );
		$task   = new Sensei_Import_Courses( $job );
		$method = new ReflectionMethod( $task, 'handle_prerequisite' );
		$method->setAccessible( true );

		$task_args = [
			$course_id,
			'slug:a-secret-course',
			1,
			'Post title',
		];

		$method->invoke( $task, $task_args );

		$this->assertEquals( (string) $course_prereq_id, get_post_meta( $course_id, '_course_prerequisite', true ) );
	}

	/**
	 * Test to make sure prerequisites can't be set to themselves.
	 */
	public function testHandlePrerequisiteNoLoop() {
		$course_prereq_id = $this->factory->course->create(
			[
				'post_name' => 'a-secret-course',
			]
		);
		$course_id        = $course_prereq_id;

		$job    = Sensei_Import_Job::create( 'test', 0 );
		$task   = new Sensei_Import_Courses( $job );
		$method = new ReflectionMethod( $task, 'handle_prerequisite' );
		$method->setAccessible( true );

		$task_args = [
			$course_id,
			'slug:a-secret-course',
			1,
			'Post title',
		];

		$method->invoke( $task, $task_args );

		$this->assertEquals( null, get_post_meta( $course_id, '_course_prerequisite', true ) );

		$logs = $job->get_logs();
		$this->assertTrue( isset( $logs[0] ), 'A log entry should have been written' );
		$this->assertEquals( 'Unable to set the prerequisite to the same entry', $logs[0]['message'], 'Log entry should warn users when they try to set a prereq to the same object' );
	}

	/**
	 * Test to make we log when a bad reference comes through.
	 */
	public function testHandlePrerequisiteLogNoticeBad() {
		$course_id = $this->factory->course->create();

		$job    = Sensei_Import_Job::create( 'test', 0 );
		$task   = new Sensei_Import_Courses( $job );
		$method = new ReflectionMethod( $task, 'handle_prerequisite' );
		$method->setAccessible( true );

		$task_args = [
			$course_id,
			'slug:a-missing-course',
			1,
			'Post title',
		];

		$method->invoke( $task, $task_args );

		$this->assertEquals( null, get_post_meta( $course_id, '_course_prerequisite', true ) );

		$logs = $job->get_logs();
		$this->assertTrue( isset( $logs[0] ), 'A log entry should have been written' );
		$this->assertEquals( 'Unable to set the prerequisite to "slug:a-missing-course"', $logs[0]['message'], 'Log entry should warn users when they try to set a prereq to the same object' );
	}
}
