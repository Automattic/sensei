<?php
/**
 * This file contains the Sensei_Course_Pre_Publish_Panel_Test class.
 *
 * @package sensei
 */

/**
 * Tests for Sensei_Course_Pre_Publish_Panel class.
 */
class Sensei_Sensei_Course_Pre_Publish_Panel_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;

	/**
	 * Factory for setting up testing data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Course ID.
	 *
	 * @var int
	 */
	private $course_id;

	/**
	 * Lesson ID.
	 *
	 * @var int
	 */
	private $lesson_id;

	public function setUp(): void {
		parent::setUp();

		$this->factory   = new Sensei_Factory();
		$this->course_id = $this->factory->course->create();
		$this->lesson_id = $this->factory->lesson->create(
			[
				'post_status' => 'draft',
				'meta_input'  => [
					'_lesson_course' => $this->course_id,
				],
			]
		);
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	/**
	 * Lessons aren't published if the user doesn't have sufficient permissions.
	 *
	 *  @covers Sensei_Course_Pre_Publish_Panel::maybe_publish_lessons
	 */
	public function testMaybePublishLessons_InsufficientPermissions_DoesNotPublishLessons() {
		/* Arrange */
		$this->login_as_student();
		update_post_meta( $this->course_id, 'sensei_course_publish_lessons', true );

		/* Act */
		Sensei_Course_Pre_Publish_Panel::instance()->maybe_publish_lessons( $this->course_id, null, 'draft' );

		/* Assert */
		$this->assertEquals( 'draft', get_post_status( $this->lesson_id ) );
	}

	/**
	 * Lessons aren't published if the user has sufficient permissions but the meta value is false.
	 *
	 *  @covers Sensei_Course_Pre_Publish_Panel::maybe_publish_lessons
	 */
	public function testMaybePublishLessons_MetaIsFalse_DoesNotPublishLessons() {
		/* Arrange */
		$this->login_as_admin();
		update_post_meta( $this->course_id, 'sensei_course_publish_lessons', false );

		/* Act */
		Sensei_Course_Pre_Publish_Panel::instance()->maybe_publish_lessons( $this->course_id, null, 'draft' );

		/* Assert */
		$this->assertEquals( 'draft', get_post_status( $this->lesson_id ) );
	}

	/**
	 * Lessons are published if the user has sufficient permissions and the meta value is true.
	 *
	 *  @covers Sensei_Course_Pre_Publish_Panel::maybe_publish_lessons
	 */
	public function testMaybePublishLessons_SufficientPermissionsAndMetaIsTrue_DoesPublishLessons() {
		/* Arrange */
		$this->login_as_admin();
		update_post_meta( $this->course_id, 'sensei_course_publish_lessons', true );

		/* Act */
		Sensei_Course_Pre_Publish_Panel::instance()->maybe_publish_lessons( $this->course_id, null, 'draft' );

		/* Assert */
		$this->assertEquals( 'publish', get_post_status( $this->lesson_id ) );
	}

	/**
	 * Lessons aren't published if a published course is just being updated.
	 *
	 *  @covers Sensei_Course_Pre_Publish_Panel::maybe_publish_lessons
	 */
	public function testMaybePublishLessons_WhenPreviousStateAlreadyPublished_DoesNotPublishLessons() {
		/* Arrange */
		$this->login_as_admin();
		update_post_meta( $this->course_id, 'sensei_course_publish_lessons', true );

		/* Act */
		Sensei_Course_Pre_Publish_Panel::instance()->maybe_publish_lessons( $this->course_id, null, 'publish' );

		/* Assert */
		$this->assertEquals( 'draft', get_post_status( $this->lesson_id ) );
	}

	/**
	 * When Course is switched to publish state, the flag is set.
	 *
	 *  @covers Sensei_Course_Pre_Publish_Panel::maybe_publish_lessons
	 */
	public function testMaybePublishLessons_WhenFirstPublished_SetsThePublishContinuationFlag() {
		/* Arrange */
		$this->login_as_admin();
		update_post_meta( $this->course_id, 'sensei_course_publish_lessons', true );

		/* Act */
		Sensei_Course_Pre_Publish_Panel::instance()->maybe_publish_lessons( $this->course_id, null, 'draft' );

		/* Assert */
		$this->assertEquals( 1, get_post_meta( $this->course_id, '_sensei_course_publishing_started', true ) );
	}

	/**
	 * When request comes from metabox save call, the flag is not removed.
	 *
	 *  @covers Sensei_Course_Pre_Publish_Panel::maybe_publish_lessons
	 */
	public function testMaybePublishLessons_WhenCallIsFromMetaboxSave_DoesNotRemoveContinuationFlag() {
		/* Arrange */
		$this->login_as_admin();
		update_post_meta( $this->course_id, 'sensei_course_publish_lessons', true );

		Sensei_Course_Pre_Publish_Panel::instance()->maybe_publish_lessons( $this->course_id, null, 'draft' );
		$publish_call_flag      = get_post_meta( $this->course_id, '_sensei_course_publishing_started', true );
		$_SERVER['REQUEST_URI'] = 'example.com/test=1&meta-box-loader=1';

		/* Act */
		Sensei_Course_Pre_Publish_Panel::instance()->maybe_publish_lessons( $this->course_id, null, 'publish' );

		/* Assert */
		$meta_save_call_flag = get_post_meta( $this->course_id, '_sensei_course_publishing_started', true );
		$this->assertEquals( 1, $meta_save_call_flag, 'The flag should not be removed when a meta save call is made.' );
		$this->assertEquals( 1, $publish_call_flag, 'The flag should be set when the first publish call is made.' );

		$_SERVER['REQUEST_URI'] = '';
	}

	/**
	 * In the first subsequent call, the lessons are published.
	 *
	 *  @covers Sensei_Course_Pre_Publish_Panel::maybe_publish_lessons
	 */
	public function testMaybePublishLessons_WhenFirstPublished_OnlyTheSubsequentCallPublishesTheLessons() {
		/* Arrange */
		$this->login_as_admin();
		update_post_meta( $this->course_id, 'sensei_course_publish_lessons', true );

		// This call mimics the first publish call made by Gutenberg. The call to save the Course structure is not yet made. It's done after this call.
		Sensei_Course_Pre_Publish_Panel::instance()->maybe_publish_lessons( $this->course_id, null, 'draft' );

		// This mimics a new unsaved lesson that just became a saved one in draft state via the Course structure save call. This needs to get published in the next call.
		$unsaved_to_draft_lesson_id = $this->factory->lesson->create(
			[
				'post_status' => 'draft',
				'meta_input'  => [
					'_lesson_course' => $this->course_id,
				],
			]
		);

		/* Act */
		// Notice how the 'old_status' is 'publish' here. Because after publishing the post and the structure is saved, the old status is 'publish' for that Course,
		// because the Course already got published in the previous call.
		// Our Course publishing sequence from Gutenberg is like this:
		// GB sends Publish Course call -> Then we send the structure saving call -> Then we send a Course update call.
		Sensei_Course_Pre_Publish_Panel::instance()->maybe_publish_lessons( $this->course_id, null, 'publish' );

		/* Assert */
		$this->assertEquals( 'publish', get_post_status( $unsaved_to_draft_lesson_id ) );
	}

	/**
	 * In the first subsequent call, the lessons are published, but not in the second or more subsequent calls.
	 *
	 *  @covers Sensei_Course_Pre_Publish_Panel::maybe_publish_lessons
	 */
	public function testMaybePublishLessons_AfterFirstPublishSequence_FartherSubsequentCallsDoNotPublishLessons() {
		/* Arrange */
		// Check the comments in the previous test for the explanation of the testing.
		$this->login_as_admin();
		update_post_meta( $this->course_id, 'sensei_course_publish_lessons', true );

		Sensei_Course_Pre_Publish_Panel::instance()->maybe_publish_lessons( $this->course_id, null, 'draft' );

		$unsaved_to_draft_lesson_id = $this->factory->lesson->create(
			[
				'post_status' => 'draft',
				'meta_input'  => [
					'_lesson_course' => $this->course_id,
				],
			]
		);
		Sensei_Course_Pre_Publish_Panel::instance()->maybe_publish_lessons( $this->course_id, null, 'publish' );

		$unsaved_to_draft_lesson_id_2 = $this->factory->lesson->create(
			[
				'post_status' => 'draft',
				'meta_input'  => [
					'_lesson_course' => $this->course_id,
				],
			]
		);
		Sensei_Course_Pre_Publish_Panel::instance()->maybe_publish_lessons( $this->course_id, null, 'publish' );

		$unsaved_to_draft_lesson_id_3 = $this->factory->lesson->create(
			[
				'post_status' => 'draft',
				'meta_input'  => [
					'_lesson_course' => $this->course_id,
				],
			]
		);

		/* Act */
		// See comments in the previous test for the explanation of the 'old_status'.
		Sensei_Course_Pre_Publish_Panel::instance()->maybe_publish_lessons( $this->course_id, null, 'publish' );

		/* Assert */
		$this->assertEquals( 'publish', get_post_status( $this->lesson_id ), 'The first lesson should be published, because it was already part of the Course before publishing.' );
		$this->assertEquals( 'publish', get_post_status( $unsaved_to_draft_lesson_id ), 'The second lesson should be published, because it was added to the Course structure before second update call.' );
		$this->assertEquals( 'draft', get_post_status( $unsaved_to_draft_lesson_id_2 ), 'The third lesson should not be published, because it was added to the Course structure after the second update call.' );
		$this->assertEquals( 'draft', get_post_status( $unsaved_to_draft_lesson_id_3 ), 'The fourth lesson should not be published, because it was added to the Course structure after the second update call.' );
	}
}
