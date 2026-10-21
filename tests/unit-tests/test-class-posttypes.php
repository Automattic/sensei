<?php
/**
 * File containing tests for Sensei_PostTypes class.
 *
 * @package sensei-tests
 */

/**
 * Class for testing Sensei_PostTypes.
 *
 * @covers Sensei_PostTypes
 */
class Sensei_Class_PostTypes extends WP_UnitTestCase {
	/**
	 * Setup function.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->factory = new Sensei_Factory();
	}

	/**
	 * Tear down function.
	 */
	public function tearDown(): void {
		$this->factory->tearDown();
		parent::tearDown();
	}

	/**
	 * Test actions are fired on shutdown.
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
	 */
	public function testFireActionOnFirstPublish() {
		$this->factory->course->create();
		Sensei()->post_types->fire_scheduled_initial_publish_actions();
		$this->assertEquals( 1, did_action( 'sensei_course_initial_publish' ) );
	}

	/**
	 * Test action fires for all Sensei post types.
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
	 */
	public function testFireNoActionForNonSenseiPostType() {
		$this->factory->post->create();
		Sensei()->post_types->fire_scheduled_initial_publish_actions();
		$this->assertEquals( 0, did_action( 'sensei_post_initial_publish' ) );
	}

	/**
	 * Test no action firing on second publish.
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

	/**
	 * The comment feeds list a lesson's comments only for viewers who can view the lesson.
	 *
	 * @dataProvider providerProtectFeedsCommentFeed
	 */
	public function testProtectFeeds_CommentFeedRequested_ListsLessonCommentOnlyForViewersWithAccess( string $feed_url, bool $enrolled, bool $expected_listed, bool $expected_post_comment_listed ): void {
		/* Arrange. */
		$course_id       = $this->factory->course->create();
		$lesson_id       = $this->factory->lesson->create(
			array(
				'meta_input' => array(
					'_lesson_course' => $course_id,
				),
			)
		);
		$comment_id      = $this->factory->comment->create(
			array(
				'comment_post_ID'  => $lesson_id,
				'comment_approved' => 1,
			)
		);
		$post_comment_id = $this->factory->comment->create(
			array(
				'comment_post_ID'  => $this->factory->post->create(),
				'comment_approved' => 1,
			)
		);

		$user_id = 0;
		if ( $enrolled ) {
			$user_id = $this->factory->user->create();
			Sensei_Course_Enrolment::get_course_instance( $course_id )->enrol( $user_id );
		}
		wp_set_current_user( $user_id );

		/* Act. */
		$this->go_to( str_replace( '{lesson}', get_post_field( 'post_name', $lesson_id ), $feed_url ) );
		$listed_comment_ids = array_map( 'intval', wp_list_pluck( $GLOBALS['wp_query']->comments, 'comment_ID' ) );

		/* Assert. */
		$this->assertSame( $expected_listed, in_array( $comment_id, $listed_comment_ids, true ), 'The lesson comment should be listed only for viewers with access.' );
		$this->assertSame( $expected_post_comment_listed, in_array( $post_comment_id, $listed_comment_ids, true ), 'The site feed should keep listing comments on other posts.' );
		$this->assertCount( $GLOBALS['wp_query']->comment_count, $listed_comment_ids, 'The comment count should match the listed comments.' );
	}

	public function providerProtectFeedsCommentFeed(): array {
		return array(
			'site feed, visitor without access'   => array( '/?feed=rss2&withcomments=1', false, false, true ),
			'site feed, enrolled student'         => array( '/?feed=rss2&withcomments=1', true, true, true ),
			'lesson feed, visitor without access' => array( '/?lesson={lesson}&feed=rss2', false, false, false ),
			'lesson feed, enrolled student'       => array( '/?lesson={lesson}&feed=rss2', true, true, false ),
		);
	}

	public function testExcludePostTypesFromRestSearch_WhenHasPostTypes_RemovesExcludedPostTypes() {
		/* Arrange. */
		$post_types = [
			'lesson',
			'sensei_message',
			'sensei_email',
		];

		/* Act. */
		$args = Sensei()->post_types->exclude_post_types_from_rest_search(
			[
				'post_type' => $post_types,
			]
		);

		/* Assert. */
		$this->assertEquals( [ 'lesson' ], $args['post_type'] );
	}

	public function testSetupSenseiMessagePostType_MessageGiven_IsNotEmbeddable() {
		/* Arrange. */
		$message_id = $this->factory->post->create( array( 'post_type' => 'sensei_message' ) );

		/* Act. */
		$actual = is_post_embeddable( $message_id );

		/* Assert. */
		$this->assertFalse( $actual );
	}
}
