<?php

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

/**
 * Tests for Sensei_Course_Outline_Block class.
 *
 * @group course-structure
 */
class Sensei_Course_Outline_Block_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;
	use ArraySubsetAsserts;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->factory = new Sensei_Factory();

		Sensei()->blocks->course->outline->clear_block_content();
	}

	public function testOutlineNotices_WhenNoLessonsAsStudent_PrintsMessageWithoutCTA() {
		// Arrange.
		$this->login_as_student();
		$this->mock_sensei_notices();

		$course_id       = $this->factory->course->create();
		$GLOBALS['post'] = (object) [
			'ID'        => $course_id,
			'post_type' => 'course',
		];

		// Act.
		$result = $this->render_and_get_frontend_notices();

		// Assert.
		$this->assertStringContainsString( 'There are no published lessons in this course yet.', $result );
		$this->assertStringNotContainsString( "When you're ready, let's publish", $result );
	}

	public function testOutlineNotices_WhenNoLessonsAsAdmin_PrintsMessageWithCTA() {
		// Arrange.
		$this->login_as_admin();
		$this->mock_sensei_notices();

		$course_id       = $this->factory->course->create();
		$GLOBALS['post'] = (object) [
			'ID'        => $course_id,
			'post_type' => 'course',
		];

		// Act.
		$result = $this->render_and_get_frontend_notices();

		// Assert.
		$this->assertStringContainsString( 'There are no published lessons in this course yet.', $result );
		$this->assertStringContainsString( 'Add some now.', $result );
	}

	public function testOutlineNotices_WhenHavingDraftLessonsAsAdmin_PrintsMessageWithCTA() {
		// Arrange.
		$this->login_as_admin();
		$this->mock_sensei_notices();

		$course_id       = $this->factory->course->create();
		$lesson_id       = $this->factory->lesson->create(
			[
				'post_status' => 'draft',
			]
		);
		$GLOBALS['post'] = (object) [
			'ID'        => $course_id,
			'post_type' => 'course',
		];

		add_post_meta( $lesson_id, '_lesson_course', $course_id, true );

		// Act.
		$result = $this->render_and_get_frontend_notices();

		// Assert.
		$this->assertStringContainsString( 'Draft lessons are only visible in preview mode.', $result );
		$this->assertStringContainsString( "When you're ready, let's publish", $result );
	}

	public function testOutlineNotices_WhenHavingDraftLessonsAsStudent_PrintsMessageWithoutCTA() {
		// Arrange.
		$this->login_as_student();
		$this->mock_sensei_notices();

		$course_id       = $this->factory->course->create();
		$lesson_id       = $this->factory->lesson->create(
			[
				'post_status' => 'draft',
			]
		);
		$GLOBALS['post'] = (object) [
			'ID'        => $course_id,
			'post_type' => 'course',
		];

		add_post_meta( $lesson_id, '_lesson_course', $course_id, true );

		// Act.
		$result = $this->render_and_get_frontend_notices();

		// Assert.
		$this->assertStringContainsString( 'There are no published lessons in this course yet.', $result );
		$this->assertStringNotContainsString( "When you're ready, let's publish", $result );
	}

	public function testOutlineNotices_WhenComingFromDraftCourseRegistrationRedirect_PrintsMessageWithCTA() {
		// Arrange.
		$this->login_as_admin();
		$this->mock_sensei_notices();

		$course_id           = $this->factory->course->create(
			[
				'post_status' => 'draft',
			]
		);
		$GLOBALS['post']     = (object) [
			'ID'        => $course_id,
			'post_type' => 'course',
		];
		$_GET['draftcourse'] = 'true';

		// Act.
		$result = $this->render_and_get_frontend_notices();

		// Assert.
		$this->assertStringContainsString( 'Cannot register for an unpublished course.', $result );
		$this->assertStringContainsString( 'publish the course', $result );
	}

	public function testOutlineBlock_WithoutLessons_ReturnsEmpty() {
		// Arrange.
		$post_content = file_get_contents( 'sample-data/outline-block-post-content.html', true );
		$this->mockPostCourseStructure( [] );

		// Act.
		$result = do_blocks( $post_content );

		// Assert.
		$this->assertEmpty( $result );
	}

	/**
	 * Test lesson in the structure is rendered.
	 */
	public function testLessonsRendered() {
		$post_content = file_get_contents( 'sample-data/outline-block-post-content.html', true );

		$this->mockPostCourseStructure(
			[
				[
					'id'    => 1,
					'type'  => 'lesson',
					'title' => 'Test Lesson',
				],
			]
		);
		$result = do_blocks( $post_content );

		$this->assertStringContainsString( 'Test Lesson', $result );
	}

	/**
	 * Test lesson preview badge is rendered.
	 */
	public function testLessonPreviewRendered() {
		$post_content = file_get_contents( 'sample-data/outline-block-post-content.html', true );

		$this->mockPostCourseStructure(
			[
				[
					'id'      => 1,
					'type'    => 'lesson',
					'title'   => 'Test Lesson',
					'preview' => true,
				],
			]
		);
		$result = do_blocks( $post_content );

		$this->assertStringContainsString( 'Preview', $result );
	}

	/**
	 * Test lesson preview badge is not rendered.
	 */
	public function testLessonNoPreviewRendered() {
		$post_content = file_get_contents( 'sample-data/outline-block-post-content.html', true );

		$this->mockPostCourseStructure(
			[
				[
					'id'      => 1,
					'type'    => 'lesson',
					'title'   => 'Test Lesson',
					'preview' => false,
				],
				[
					'id'    => 2,
					'type'  => 'lesson',
					'title' => 'Test Lesson 2',
				],
			]
		);
		$result = do_blocks( $post_content );

		$this->assertStringNotContainsString( 'Preview', $result );
	}

	/**
	 * Test module with a lesson in the structure is rendered.
	 */
	public function testModulesRendered() {
		$post_content = file_get_contents( 'sample-data/outline-block-post-content.html', true );
		$module       = $this->factory->module->create_and_get();

		$this->mockPostCourseStructure(
			[
				[
					'id'          => $module->term_id,
					'type'        => 'module',
					'title'       => $module->name,
					'description' => $module->description,
					'lessons'     => [
						[
							'id'    => 1,
							'type'  => 'lesson',
							'title' => 'Test Lesson',
						],
					],
				],
			]
		);

		$result      = do_blocks( $post_content );
		$module_link = get_term_link( $module->term_id, Sensei()->modules->taxonomy );

		$this->assertStringContainsString( $module->name, $result );
		$this->assertStringContainsString( $module->description, $result );
		$this->assertStringContainsString( $module_link, $result );
		$this->assertStringContainsString( 'Test Lesson', $result );
	}

	/**
	 * Test module without description in the structure is rendered.
	 */
	public function testModuleWithoutDescriptionRendered() {
		$post_content = file_get_contents( 'sample-data/outline-block-post-content.html', true );
		$module       = $this->factory->module->create_and_get();

		$this->mockPostCourseStructure(
			[
				[
					'id'          => $module->term_id,
					'type'        => 'module',
					'title'       => $module->name,
					'description' => '',
					'lessons'     => [
						[
							'id'    => 1,
							'type'  => 'lesson',
							'title' => 'Test Lesson',
						],
					],
				],
			]
		);

		$result      = do_blocks( $post_content );
		$module_link = get_term_link( $module->term_id, Sensei()->modules->taxonomy );

		$this->assertStringContainsString( $module->name, $result );
		$this->assertStringNotContainsString( $module_link, $result );
	}

	/**
	 * Test that attributes parsed from the block are passed over to the dynamic render function.
	 */
	public function testBlockAttributesMatched() {

		unregister_block_type( 'sensei-lms/course-outline' );
		unregister_block_type( 'sensei-lms/course-outline-lesson' );
		unregister_block_type( 'sensei-lms/course-outline-module' );

		$outline_block = new Sensei_Course_Outline_Block();

		$this->mockPostCourseStructure(
			[
				[
					'id'    => 1,
					'type'  => 'lesson',
					'title' => 'Test Lesson',
				],
			]
		);
		render_block(
			[
				'blockName'    => 'sensei-lms/course-outline',
				'attrs'        => [ 'id' => 1 ],
				'innerContent' => [ [] ],
				'innerBlocks'  => [
					[
						'blockName'    => 'sensei-lms/course-outline-lesson',
						'attrs'        => [
							'id'    => 1,
							'style' => 'blue',
						],
						'innerContent' => [],
					],
				],
			]
		);

		$lesson_block = $outline_block->get_block_structure()['blocks'][0];
		$this->assertArraySubset(
			[
				'id'         => 1,
				'type'       => 'lesson',
				'title'      => 'Test Lesson',
				'attributes' => [
					'id'    => 1,
					'style' => 'blue',
				],
			],
			$lesson_block
		);
	}


	/**
	 * Mock global post ID and its course structure.
	 *
	 * @param array $structure
	 */
	private function mockPostCourseStructure( $structure = [] ) {

		$GLOBALS['post'] = (object) [ 'ID' => 0 ];

		$mock = $this->getMockBuilder( Sensei_Course_Structure::class )
			->disableOriginalConstructor()
			->setMethods( [ 'get' ] )
			->getMock();

		$mock->method( 'get' )->willReturn( $structure );

		$instances = new ReflectionProperty( Sensei_Course_Structure::class, 'instances' );
		$instances->setAccessible( true );
		$instances->setValue( [ 0 => $mock ] );
	}

	/**
	 * Mock Sensei notices.
	 */
	private function mock_sensei_notices() {
		$property = new ReflectionProperty( 'Sensei_Notices', 'has_printed' );
		$property->setAccessible( true );
		$property->setValue( Sensei()->notices, false );
	}

	/**
	 * Render the block and get the frontend notices.
	 *
	 * @return string
	 */
	private function render_and_get_frontend_notices() {
		$outline_block = new Sensei_Course_Outline_Block();

		$outline_block->frontend_notices();
		ob_start();
		Sensei()->notices->maybe_print_notices();
		return ob_get_clean();
	}
}
