<?php

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

/**
 * Tests for Sensei_Course_Outline_Block class.
 *
 * @group course-structure
 */
class Sensei_Course_Outline_Block_Test extends WP_UnitTestCase {

	use ArraySubsetAsserts;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->factory = new Sensei_Factory();

		Sensei()->blocks->course->outline->clear_block_content();
	}

	/**
	 * Test that a message is shown when there is no content.
	 */
	public function testEmptyBlock() {
		$property = new ReflectionProperty( 'Sensei_Notices', 'has_printed' );
		$property->setAccessible( true );
		$property->setValue( Sensei()->notices, false );

		$post_content = file_get_contents( 'sample-data/outline-block-post-content.html', true );

		$this->mockPostCourseStructure( [] );

		ob_start();
		do_blocks( $post_content );
		Sensei()->notices->maybe_print_notices();
		$result = ob_get_clean();

		$this->assertStringContainsString( 'There is no published content in this course yet.', $result );
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

	public function testCourseOutlineModuleRender_WhenAttributeSubheadingHasFontSize_ThatFontsizeIsAddedAsStyle() {
		/* Arrange */
		$post_content = file_get_contents( 'sample-data/outline-block-post-content.html', true );
		$module       = $this->factory->module->create_and_get();
		$post_content = str_replace( 'course-outline-module {"id":1', 'course-outline-module {"id":' . $module->term_id, $post_content );

		$this->mockPostCourseStructure(
			[
				[
					'id'          => $module->term_id,
					'type'        => 'module',
					'title'       => $module->name,
					'description' => 'Test Description',
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

		/* Act */
		$result = do_blocks( $post_content );

		/* Assert */
		$this->assertStringContainsString( 'font-size:2.225rem;', $result );
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
}
