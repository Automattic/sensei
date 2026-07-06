<?php
/**
 * This file contains the Sensei_Import_Lessons class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Import_Lessons class.
 *
 * @group data-port
 */
class Sensei_Import_Lessons_Tests extends WP_UnitTestCase {

	/**
	 * Setup function.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Test to make sure prerequisites are handled correctly.
	 */
	public function testHandlePrerequisiteHandled() {
		$lesson_id        = $this->factory->lesson->create();
		$lesson_prereq_id = $this->factory->lesson->create(
			[
				'post_name' => 'a-secret-lesson',
			]
		);

		$job    = Sensei_Import_Job::create( 'test', 0 );
		$task   = new Sensei_Import_Lessons( $job );
		$method = new ReflectionMethod( $task, 'handle_prerequisite' );
		$method->setAccessible( true );

		$task_args = [
			$lesson_id,
			'slug:a-secret-lesson',
			1,
			'Post title',
		];

		$method->invoke( $task, $task_args );

		$this->assertEquals( (string) $lesson_prereq_id, get_post_meta( $lesson_id, '_lesson_prerequisite', true ) );
	}

	/**
	 * Test to make sure prerequisites can't be set to themselves.
	 */
	public function testHandlePrerequisiteNoLoop() {
		$lesson_prereq_id = $this->factory->lesson->create(
			[
				'post_name' => 'a-secret-lesson',
			]
		);
		$lesson_id        = $lesson_prereq_id;

		$job    = Sensei_Import_Job::create( 'test', 0 );
		$task   = new Sensei_Import_Lessons( $job );
		$method = new ReflectionMethod( $task, 'handle_prerequisite' );
		$method->setAccessible( true );

		$task_args = [
			$lesson_id,
			'slug:a-secret-lesson',
			1,
			'Post title',
		];

		$method->invoke( $task, $task_args );

		$this->assertEquals( null, get_post_meta( $lesson_id, '_lesson_prerequisite', true ) );

		$logs = $job->get_logs();
		$this->assertTrue( isset( $logs[0] ), 'A log entry should have been written' );
		$this->assertEquals( 'Unable to set the prerequisite to the same entry', $logs[0]['message'], 'Log entry should warn users when they try to set a prereq to the same object' );
	}

	/**
	 * Test to make we log when a bad reference comes through.
	 */
	public function testHandlePrerequisiteLogNoticeBad() {
		$lesson_id = $this->factory->lesson->create();

		$job    = Sensei_Import_Job::create( 'test', 0 );
		$task   = new Sensei_Import_Lessons( $job );
		$method = new ReflectionMethod( $task, 'handle_prerequisite' );
		$method->setAccessible( true );

		$task_args = [
			$lesson_id,
			'slug:a-missing-lesson',
			1,
			'Post title',
		];

		$method->invoke( $task, $task_args );

		$this->assertEquals( null, get_post_meta( $lesson_id, '_lesson_prerequisite', true ) );

		$logs = $job->get_logs();
		$this->assertTrue( isset( $logs[0] ), 'A log entry should have been written' );
		$this->assertEquals( 'Unable to set the prerequisite to "slug:a-missing-lesson"', $logs[0]['message'], 'Log entry should warn users when they try to set a prereq to the same object' );
	}

	/**
	 * Tests that a queued thumbnail whose source resolves is set as the post's featured image.
	 */
	public function testHandleAttachment_ResolvableSource_SetsAttachmentMeta() {
		$attachment_id = $this->factory->attachment->create( [ 'file' => 'localfilename.png' ] );
		$lesson_id     = $this->factory->lesson->create();

		$job    = Sensei_Import_Job::create( 'test', 0 );
		$task   = new Sensei_Import_Lessons( $job );
		$method = new ReflectionMethod( $task, 'handle_attachment' );
		$method->setAccessible( true );

		$method->invoke(
			$task,
			[
				'post_id'     => $lesson_id,
				'source'      => 'localfilename.png',
				'mime_types'  => null,
				'line_number' => 1,
				'model_key'   => 'lesson',
			]
		);

		$this->assertEquals( $attachment_id, (int) get_post_meta( $lesson_id, '_thumbnail_id', true ) );
	}

	/**
	 * Tests that a queued thumbnail whose source cannot be resolved leaves the post without a
	 * featured image instead of failing.
	 */
	public function testHandleAttachment_UnresolvableSource_LeavesAttachmentUnset() {
		$lesson_id = $this->factory->lesson->create();

		$job    = Sensei_Import_Job::create( 'test', 0 );
		$task   = new Sensei_Import_Lessons( $job );
		$method = new ReflectionMethod( $task, 'handle_attachment' );
		$method->setAccessible( true );

		$method->invoke(
			$task,
			[
				'post_id'     => $lesson_id,
				'source'      => 'does-not-exist-in-media-library.png',
				'mime_types'  => null,
				'line_number' => 1,
				'model_key'   => 'lesson',
			]
		);

		$this->assertEmpty( get_post_meta( $lesson_id, '_thumbnail_id', true ) );
	}
}
