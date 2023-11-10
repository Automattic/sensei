<?php
/**
 * This file contains the Sensei_Course_Theme_Templates_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Course_Theme_Templates class.
 *
 * @group course-theme
 */
class Sensei_Course_Theme_Templates_Test extends WP_UnitTestCase {

	/**
	 * Sensei factory.
	 *
	 * @var Sensei_Facotry
	 */
	protected $factory;

	public function setUp(): void {
		parent::setUp();
		$this->factory = new Sensei_Factory();
	}

	public function testSenseiCourseThemeTemplates_WhenClassInitialized_PatternCreationFunctionIsAttachedWithInit() {
		/* Arrange */
		$registry = \WP_Block_Patterns_Registry::get_instance();

		/* Act */
		$course_theme_templates = Sensei_Course_Theme_Templates::instance();

		/* Assert */
		self::assertSame( 10, has_filter( 'init', [ $course_theme_templates, 'load_course_theme_patterns' ] ) );
	}

	public function testSenseiCourseThemeTemplates_WhenPatternAccessed_IsCreatedAlreadyByInit() {
		/* Arrange */
		$registry = \WP_Block_Patterns_Registry::get_instance();

		/* Assert */
		self::assertTrue( $registry->is_registered( 'sensei-course-theme/header' ) );
	}

	public function testLoadCoursePattern_WhenCalled_CreatesTheHeaderPattern() {
		/* Arrange */
		$registry               = \WP_Block_Patterns_Registry::get_instance();
		$course_theme_templates = Sensei_Course_Theme_Templates::instance();

		$registry->unregister( 'sensei-course-theme/header' );

		$is_registered_before = $registry->is_registered( 'sensei-course-theme/header' );

		/* Act */
		$course_theme_templates->load_course_theme_patterns();

		$is_registered_after = $registry->is_registered( 'sensei-course-theme/header' );

		/* Assert */
		self::assertFalse( $is_registered_before );
		self::assertTrue( $is_registered_after );
	}

	public function testTemplateFilter_WhenCourseThemeEnabled_ReturnsFilteredTemplateList() {
		/* Arrange */
		$templates = [
			(object) [
				'template' => 'template-1.php',
				'id'       => 'test/test',
			],
			(object) [
				'template' => 'template-2.php',
				'id'       => 'course//single-lesson',
			],
		];

		/* Act */
		$filtered_templates = Sensei_Course_Theme_Templates::instance()->filter_single_lesson_template_in_learning_mode( $templates, 'Course' );

		/* Assert */
		$this->assertCount( 1, $filtered_templates );
	}

	public function testTemplateFilter_WhenCourseThemeNotEnabled_ReturnsSameTemplateList() {
		/* Arrange */
		$templates = [
			(object) [
				'template' => 'template-1.php',
				'id'       => 'test/test',
			],
			(object) [
				'template' => 'template-2.php',
				'id'       => 'course//single-lesson',
			],
		];

		/* Act */
		$filtered_templates = Sensei_Course_Theme_Templates::instance()->filter_single_lesson_template_in_learning_mode( $templates, 'Twenty' );

		/* Assert */
		$this->assertCount( 2, $filtered_templates );
	}

	public function testShouldUseQuizTemplate_QuizPassed_ReturnsFalse(): void {
		/* Arrange. */
		global $post;

		$lesson_id = $this->factory->lesson->create();
		$quiz      = $this->factory->quiz->create_and_get( array( 'post_parent' => $lesson_id ) );
		$user      = $this->factory->user->create_and_get();
		$post      = $quiz;
		wp_set_current_user( $user->ID );

		Sensei()->lesson_progress_repository->create( $lesson_id, $user->ID );
		$quiz_progress = Sensei()->quiz_progress_repository->create( $quiz->ID, $user->ID );
		$quiz_progress->pass();
		Sensei()->quiz_progress_repository->save( $quiz_progress );

		/* Act. */
		$actual = Sensei_Course_Theme_Templates::instance()->should_use_quiz_template();

		/* Assert. */
		$this->assertFalse( $actual );
	}

	public function testShouldUseQuizTemplate_QuizStarted_ReturnsTrue(): void {
		/* Arrange. */
		global $post;

		$lesson_id = $this->factory->lesson->create();
		$quiz      = $this->factory->quiz->create_and_get( array( 'post_parent' => $lesson_id ) );
		$user      = $this->factory->user->create_and_get();
		$post      = $quiz;
		wp_set_current_user( $user->ID );

		Sensei()->lesson_progress_repository->create( $lesson_id, $user->ID );
		$quiz_progress = Sensei()->quiz_progress_repository->create( $quiz->ID, $user->ID );
		$quiz_progress->start();
		Sensei()->quiz_progress_repository->save( $quiz_progress );

		/* Act. */
		$actual = Sensei_Course_Theme_Templates::instance()->should_use_quiz_template();

		/* Assert. */
		$this->assertTrue( $actual );
	}

	public function testShouldUseQuizTemplate_NonQuizPost_ReturnFalse(): void {
		/* Arrange. */
		global $post;

		$lesson = $this->factory->lesson->create_and_get();
		$quiz   = $this->factory->quiz->create_and_get( array( 'post_parent' => $lesson->ID ) );
		$user   = $this->factory->user->create_and_get();
		$post   = $lesson;
		wp_set_current_user( $user->ID );

		Sensei()->lesson_progress_repository->create( $lesson->ID, $user->ID );
		$quiz_progress = Sensei()->quiz_progress_repository->create( $quiz->ID, $user->ID );
		$quiz_progress->start();
		Sensei()->quiz_progress_repository->save( $quiz_progress );

		/* Act. */
		$actual = Sensei_Course_Theme_Templates::instance()->should_use_quiz_template();

		/* Assert. */
		$this->assertFalse( $actual );
	}
}
