<?php

/**
 * Tests for Sensei_Course_Outline_Block class.
 *
 * @group course-structure
 */
class Sensei_Course_Outline_Block_Test extends WP_UnitTestCase {

	/**
	 * Set up the test.
	 */
	public function setUp() {
		parent::setUp();
		$this->factory = new Sensei_Factory();

		Sensei()->blocks->course_outline->init();
	}

	/**
	 * Test that a message is shown when there is no content.
	 */
	public function testEmptyBlock() {
		$post_content = file_get_contents( 'sample-data/outline-block-post-content.html', true );

		$this->mockPostCourseStructure( [] );
		$result = do_blocks( $post_content );

		$this->assertContains( 'There is no published content in this course yet.', $result );
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

		$this->assertContains( 'Test Lesson', $result );
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

		$this->assertContains( $module->name, $result );
		$this->assertContains( $module->description, $result );
		$this->assertContains( $module_link, $result );
		$this->assertContains( 'Test Lesson', $result );
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

		$this->assertContains( $module->name, $result );
		$this->assertNotContains( $module_link, $result );
	}

	/**
	 * Test that attributes parsed from the block are passed over to the dynamic render function.
	 */
	public function testBlockAttributesMatched() {

		unregister_block_type( 'sensei-lms/course-outline' );
		unregister_block_type( 'sensei-lms/course-outline-lesson' );
		unregister_block_type( 'sensei-lms/course-outline-module' );

		$outline_block = new Sensei_Course_Outline_Block();
		$outline_block->register_blocks();

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
