<?php
/**
 * File containing tests for Sensei_PostTypes class.
 *
 * @package sensei-tests
 */

/**
 * Class for testing Sensei_PostTypes.
 */
class Sensei_Class_PostTypes extends WP_UnitTestCase {
	/**
	 * Setup function.
	 */
	public function setup() {
		parent::setup();
		$this->factory = new Sensei_Factory();
	}

	/**
	 * Tear down function.
	 */
	public function tearDown() {
		$this->factory->tearDown();
		parent::tearDown();
	}

	/**
	 * Test actions are fired on shutdown.
	 *
	 * @covers Sensei_PostType::setup_initial_publish_action
	 */
	public function testFireActionsOnShutdown() {
		$this->assertNotFalse(
			has_action(
				'shutdown',
				[ Sensei()->post_types, 'fire_scheduled_initial_publish_actions' ]
			)
		);
	}

	/**
	 * Test action firing on first publish.
	 *
	 * @covers Sensei_PostType::setup_initial_publish_action
	 */
	public function testFireActionOnFirstPublish() {
		$this->factory->course->create();
		Sensei()->post_types->fire_scheduled_initial_publish_actions();
		$this->assertEquals( 1, did_action( 'sensei_course_initial_publish' ) );
	}

	/**
	 * Test action fires for all Sensei post types.
	 *
	 * @covers Sensei_PostType::setup_initial_publish_action
	 */
	public function testFireActionForSenseiPostTypes() {
		$this->factory->course->create();
		$this->factory->lesson->create();
		$quiz = $this->factory->quiz->create();
		$this->factory->question->create( [ 'quiz_id' => $quiz ] );
		$this->factory->message->create();

		// Ensure all delayed actions are fired.
		Sensei()->post_types->fire_scheduled_initial_publish_actions();

		$this->assertEquals( 1, did_action( 'sensei_course_initial_publish' ) );
		$this->assertEquals( 1, did_action( 'sensei_lesson_initial_publish' ) );
		$this->assertEquals( 1, did_action( 'sensei_quiz_initial_publish' ) );
		$this->assertEquals( 1, did_action( 'sensei_question_initial_publish' ) );
		$this->assertEquals( 1, did_action( 'sensei_sensei_message_initial_publish' ) );
	}

	/**
	 * Ensure intitial publish action is fired after other hooks.
	 *
	 * @covers Sensei_PostType::setup_initial_publish_action
	 */
	public function testFireActionAfterHooks() {
		add_action(
			'save_post',
			function( $post_id ) {
				if ( 'course' === get_post_type( $post_id ) ) {
					add_post_meta( $post_id, 'test_meta', 'test_value', true );
				}
			}
		);

		// Ensure that meta exists on `sensei_course_initial_publish`.
		$test_suite = $this;
		add_action(
			'sensei_course_initial_publish',
			function( $post ) use ( $test_suite ) {
				$meta_value = get_post_meta( $post->ID, 'test_meta', true );
				$test_suite->assertEquals( 'test_value', $meta_value );
			}
		);

		// Ensure the action was called.
		$course_id = $this->factory->course->create();
		Sensei()->post_types->fire_scheduled_initial_publish_actions();
		$this->assertEquals( 1, did_action( 'sensei_course_initial_publish' ) );
	}

	/**
	 * Ensure initial publish action is not called for REST API requests. See
	 * documentation for Sensei_Course::setup_initial_publish_action
	 *
	 * @covers Sensei_PostType::setup_initial_publish_action
	 */
	public function testFireNoActionOnRestApiRequest() {
		$this->assertNotFalse(
			has_action(
				'rest_api_init',
				[ Sensei()->post_types, 'disable_fire_scheduled_initial_publish_actions' ]
			)
		);

		// Simulate `rest_api_init`.
		Sensei()->post_types->disable_fire_scheduled_initial_publish_actions();

		// Ensure the firing action was removed.
		$this->assertFalse(
			has_action(
				'shutdown',
				[ Sensei()->post_types, 'fire_scheduled_initial_publish_actions' ]
			)
		);
	}

	/**
	 * Ensure post is not marked as "already published" on metabox update
	 * request. See documentation for
	 * Sensei_Course::setup_initial_publish_action
	 *
	 * @covers Sensei_PostType::setup_initial_publish_action
	 */
	public function testNoMarkPublishedOnMetaboxUpdate() {
		$_REQUEST['meta-box-loader'] = '1';

		$course_id = $this->factory->course->create();
		Sensei()->post_types->fire_scheduled_initial_publish_actions();
		$this->assertEquals( 1, did_action( 'sensei_course_initial_publish' ) );

		// Remove the meta to simulate an existing course.
		delete_post_meta( $course_id, '_sensei_already_published' );

		// Unpublish course.
		wp_update_post(
			[
				'ID'          => $course_id,
				'post_status' => 'draft',
			]
		);

		// Republish course.
		wp_update_post(
			[
				'ID'          => $course_id,
				'post_status' => 'publish',
			]
		);

		// Ensure that the second publish fired a second action.
		Sensei()->post_types->fire_scheduled_initial_publish_actions();
		$this->assertEquals( 2, did_action( 'sensei_course_initial_publish' ) );

		unset( $_REQUEST['meta-box-loader'] );
	}

	/**
	 * Test action fires for no non-Sensei post types.
	 *
	 * @covers Sensei_PostType::setup_initial_publish_action
	 */
	public function testFireNoActionForNonSenseiPostType() {
		$this->factory->post->create();
		Sensei()->post_types->fire_scheduled_initial_publish_actions();
		$this->assertEquals( 0, did_action( 'sensei_post_initial_publish' ) );
	}

	/**
	 * Test no action firing on second publish.
	 *
	 * @covers Sensei_PostType::setup_initial_publish_action
	 */
	public function testFireNoActionOnSecondPublish() {
		$course_id = $this->factory->course->create();
		Sensei()->post_types->fire_scheduled_initial_publish_actions();
		$this->assertEquals( 1, did_action( 'sensei_course_initial_publish' ) );

		// Unpublish course.
		wp_update_post(
			[
				'ID'          => $course_id,
				'post_status' => 'draft',
			]
		);

		// Republish course.
		wp_update_post(
			[
				'ID'          => $course_id,
				'post_status' => 'publish',
			]
		);

		// Ensure that the second publish did not fire a second action.
		Sensei()->post_types->fire_scheduled_initial_publish_actions();
		$this->assertEquals( 1, did_action( 'sensei_course_initial_publish' ) );
	}

	/**
	 * Test no action firing on existing post second publish.
	 *
	 * @covers Sensei_PostType::setup_initial_publish_action
	 */
	public function testFireNoActionOnExistingPostSecondPublish() {
		$course_id = $this->factory->course->create();
		Sensei()->post_types->fire_scheduled_initial_publish_actions();
		$this->assertEquals( 1, did_action( 'sensei_course_initial_publish' ) );

		// Remove the meta to simulate an existing course.
		delete_post_meta( $course_id, '_sensei_already_published' );

		// Unpublish course.
		wp_update_post(
			[
				'ID'          => $course_id,
				'post_status' => 'draft',
			]
		);

		// Republish course.
		wp_update_post(
			[
				'ID'          => $course_id,
				'post_status' => 'publish',
			]
		);

		// Ensure that the second publish did not fire a second action.
		Sensei()->post_types->fire_scheduled_initial_publish_actions();
		$this->assertEquals( 1, did_action( 'sensei_course_initial_publish' ) );
	}

	/**
	 * Test no action firing on update.
	 *
	 * @covers Sensei_PostType::setup_initial_publish_action
	 */
	public function testFireNoActionOnExistingCourseUpdate() {
		$course_id = $this->factory->course->create();
		Sensei()->post_types->fire_scheduled_initial_publish_actions();
		$this->assertEquals( 1, did_action( 'sensei_course_initial_publish' ) );

		// Remove the meta to simulate an existing published course.
		delete_post_meta( $course_id, '_sensei_already_published' );

		// Update course without changing the status.
		wp_update_post(
			[
				'ID'           => $course_id,
				'post_content' => 'New content',
				'post_status'  => 'publish',
			]
		);

		// Ensure that the second publish did not fire an action.
		Sensei()->post_types->fire_scheduled_initial_publish_actions();
		$this->assertEquals( 1, did_action( 'sensei_course_initial_publish' ) );
	}
}
