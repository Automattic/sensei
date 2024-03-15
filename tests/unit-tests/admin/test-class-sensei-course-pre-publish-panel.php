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
		Sensei_Course_Pre_Publish_Panel::instance()->maybe_publish_lessons( $this->course_id );

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
		Sensei_Course_Pre_Publish_Panel::instance()->maybe_publish_lessons( $this->course_id );

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
		Sensei_Course_Pre_Publish_Panel::instance()->maybe_publish_lessons( $this->course_id );

		/* Assert */
		$this->assertEquals( 'publish', get_post_status( $this->lesson_id ) );
	}
}
