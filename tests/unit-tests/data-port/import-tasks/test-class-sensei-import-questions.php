<?php
/**
 * This file contains the Sensei_Import_Questions_Tests class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Import_Questions class.
 *
 * @group data-port
 */
class Sensei_Import_Questions_Tests extends WP_UnitTestCase {

	/**
	 * Setup function.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Tests that the thumbnail post-process handler is wired into the questions task and stores the
	 * resolved attachment in the `_question_media` meta when the source resolves.
	 */
	public function testHandleAttachment_ResolvableSource_SetsQuestionMediaMeta() {
		$attachment_id = $this->factory->attachment->create( [ 'file' => 'localfilename.png' ] );
		$question_id   = $this->factory->post->create( [ 'post_type' => 'question' ] );

		$job    = Sensei_Import_Job::create( 'test', 0 );
		$task   = new Sensei_Import_Questions( $job );
		$method = new ReflectionMethod( $task, 'handle_attachment' );
		$method->setAccessible( true );

		$method->invoke(
			$task,
			[
				'post_id'     => $question_id,
				'source'      => 'localfilename.png',
				'mime_types'  => null,
				'line_number' => 1,
				'model_key'   => 'question',
				'meta_key'    => '_question_media', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Post-process task argument, not a query.
			]
		);

		$this->assertEquals( $attachment_id, (int) get_post_meta( $question_id, '_question_media', true ) );
	}
}
