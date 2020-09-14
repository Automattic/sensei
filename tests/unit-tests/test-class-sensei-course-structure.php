<?php

/**
 * Tests for Sensei_Course_Structure_Test class.
 *
 * @group course-structure
 */
class Sensei_Course_Structure_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;

	/**
	 * Set up the test.
	 */
	public function setUp() {
		parent::setUp();

		if ( ! isset( Sensei()->admin ) ) {
			Sensei()->admin = new Sensei_Admin();
		}

		$this->factory = new Sensei_Factory();
		$this->resetInstances();
	}

	/**
	 * Test getting course structure when just lessons.
	 */
	public function testGetJustLessons() {
		$course_id          = $this->factory->course->create();
		$course_lesson_args = [
			'meta_input' => [
				'_lesson_course' => $course_id,
			],
		];
		$lessons            = $this->factory->lesson->create_many( 3, $course_lesson_args );
		$lesson_unordered   = $this->factory->lesson->create( $course_lesson_args );

		// Rogue lesson.
		$this->factory->lesson->create();

		$expected_structure = [];
		foreach ( [ $lessons[1], $lessons[0], $lessons[2] ] as $lesson_id ) {
			$expected_structure[] = [
				'type' => 'lesson',
				'id'   => $lesson_id,
			];
		}

		$this->saveStructure( $course_id, $expected_structure );

		$expected_structure[] = [
			'type' => 'lesson',
			'id'   => $lesson_unordered,
		];

		$course_structure = Sensei_Course_Structure::instance( $course_id );
		$structure        = $course_structure->get();

		$this->assertExpectedStructure( $expected_structure, $structure );
	}

	/**
	 * Test getting course structure when just modules on the first level.
	 */
	public function testGetJustModules() {
		$course_id = $this->factory->course->create();

		$lessons = $this->factory->lesson->create_many( 4 );
		$modules = $this->factory->module->create_many( 2 );

		$expected_structure = [
			[
				'type'    => 'module',
				'id'      => $modules[1],
				'lessons' => [
					[
						'type' => 'lesson',
						'id'   => $lessons[1],
					],
					[
						'type' => 'lesson',
						'id'   => $lessons[2],
					],
				],
			],
			[
				'type'    => 'module',
				'id'      => $modules[0],
				'lessons' => [
					[
						'type' => 'lesson',
						'id'   => $lessons[3],
					],
					[
						'type' => 'lesson',
						'id'   => $lessons[0],
					],
				],
			],
		];

		$this->saveStructure( $course_id, $expected_structure );

		$course_structure = Sensei_Course_Structure::instance( $course_id );
		$structure        = $course_structure->get();

		$this->assertExpectedStructure( $expected_structure, $structure );
	}

	/**
	 * Test getting course structure when there is a mix of modules and lessons on the first level.
	 */
	public function testGetModulesLessonsMix() {
		$course_id = $this->factory->course->create();

		$lessons = $this->factory->lesson->create_many( 5 );
		$modules = $this->factory->module->create_many( 2 );

		$expected_structure = [
			[
				'type'    => 'module',
				'id'      => $modules[1],
				'lessons' => [
					[
						'type' => 'lesson',
						'id'   => $lessons[1],
					],
					[
						'type' => 'lesson',
						'id'   => $lessons[2],
					],
				],
			],
			[
				'type'    => 'module',
				'id'      => $modules[0],
				'lessons' => [
					[
						'type' => 'lesson',
						'id'   => $lessons[0],
					],
				],
			],
			[
				'type' => 'lesson',
				'id'   => $lessons[4],
			],
			[
				'type' => 'lesson',
				'id'   => $lessons[3],
			],
		];

		$this->saveStructure( $course_id, $expected_structure );

		$course_structure = Sensei_Course_Structure::instance( $course_id );
		$structure        = $course_structure->get();

		$this->assertExpectedStructure( $expected_structure, $structure );
	}

	/**
	 * Make sure new lessons are created when no ID is passed.
	 */
	public function testSaveNewLessons() {
		$this->login_as_teacher();

		$course_id = $this->factory->course->create();

		$new_structure = [
			[
				'type'  => 'lesson',
				'title' => 'New lesson',
			],
		];

		$course_structure = Sensei_Course_Structure::instance( $course_id );

		$this->assertTrue( $course_structure->save( $new_structure ) );

		$structure = $course_structure->get();

		$this->assertEquals( 1, count( $structure ) );

		$first_item = $structure[0];
		$this->assertEquals( 'lesson', $first_item['type'], 'Course should have one lesson object' );
		$this->assertEquals( 'lesson', get_post_type( $first_item['id'] ), 'Created post should be a lesson' );
		$this->assertEquals( $new_structure[0]['title'], $first_item['title'], 'New title should match' );
	}

	/**
	 * Tests to ensure lessons with empty titles are not created.
	 */
	public function testSaveInvalidLessonFail() {
		$this->login_as_teacher();

		$course_id = $this->factory->course->create();

		$new_structure = [
			[
				'type'  => 'lesson',
				'title' => '  ',
			],
		];

		$course_structure = Sensei_Course_Structure::instance( $course_id );

		$save_result = $course_structure->save( $new_structure );
		$this->assertWPError( $save_result );

		$this->assertEquals( 'sensei_course_structure_missing_title', $save_result->get_error_code() );

		$structure = $course_structure->get();
		$this->assertEquals( 0, count( $structure ) );
	}

	/**
	 * Make sure new modules (login as teacher) are created and existing lessons are recycled.
	 */
	public function testSaveNewModulesExistingLessons() {
		$this->login_as_teacher();

		$course_id  = $this->factory->course->create();
		$lesson_ids = $this->factory->lesson->create_many( 3 );

		$new_structure = [
			[
				'type'    => 'module',
				'title'   => 'New Module A',
				'lessons' => [
					[
						'type'  => 'lesson',
						'id'    => $lesson_ids[2],
						'title' => get_the_title( $lesson_ids[2] ),
					],
					[
						'type'  => 'lesson',
						'id'    => $lesson_ids[0],
						'title' => get_the_title( $lesson_ids[0] ),
					],
				],
			],
			[
				'type'    => 'module',
				'title'   => 'New Module B',
				'lessons' => [
					[
						'type'  => 'lesson',
						'id'    => $lesson_ids[1],
						'title' => get_the_title( $lesson_ids[1] ),
					],
				],
			],
		];

		$course_structure = Sensei_Course_Structure::instance( $course_id );

		$this->assertTrue( $course_structure->save( $new_structure ) );

		$structure = $course_structure->get();

		$this->assertEquals( 2, count( $structure ) );

		$this->assertEquals( 'module', $structure[0]['type'], 'Course should have two module items' );
		$module = get_term( $structure[0]['id'], 'module' );
		$this->assertEquals( get_current_user_id() . '-' . sanitize_title( $new_structure[0]['title'] ), $module->slug, 'Slug should be prefixed with teacher ID' );
		$this->assertEquals( $new_structure[0]['title'], $module->name );
		$this->assertExpectedStructure( $new_structure[0]['lessons'], $structure[0]['lessons'] );

		$this->assertEquals( 'module', $structure[1]['type'], 'Course should have another module item' );
		$module = get_term( $structure[1]['id'], 'module' );
		$this->assertEquals( get_current_user_id() . '-' . sanitize_title( $new_structure[1]['title'] ), $module->slug, 'Slug should be prefixed with teacher ID' );
		$this->assertEquals( $new_structure[1]['title'], $module->name );
		$this->assertExpectedStructure( $new_structure[1]['lessons'], $structure[1]['lessons'] );
	}

	/**
	 * Make sure new modules (logged in as admin) with no lessons are saved.
	 */
	public function testSaveNewModulesNoLessons() {
		$this->login_as_admin();

		$course_id = $this->factory->course->create();

		$new_structure = [
			[
				'type'    => 'module',
				'title'   => 'New Module A',
				'lessons' => [],
			],
		];

		$course_structure = Sensei_Course_Structure::instance( $course_id );

		$this->assertTrue( $course_structure->save( $new_structure ) );

		$structure = $course_structure->get();

		$this->assertEquals( 1, count( $structure ) );

		$this->assertEquals( 'module', $structure[0]['type'], 'Course should have one module item' );
		$module = get_term( $structure[0]['id'], 'module' );
		$this->assertEquals( sanitize_title( $new_structure[0]['title'] ), $module->slug, 'Slug should NOT be prefixed with teacher ID when logged in as an admin' );
		$this->assertEquals( $new_structure[0]['title'], $module->name );
		$this->assertEmpty( $structure[0]['lessons'], 'No lessons were added' );
	}

	/**
	 * Make sure module details are updated properly.
	 */
	public function testSaveUpdateModuleDetails() {
		$this->login_as_admin();

		$course_id     = $this->factory->course->create();
		$new_structure = [
			[
				'type'        => 'module',
				'title'       => 'New Module A',
				'description' => 'Very nice module',
				'lessons'     => [],
			],
		];

		$course_structure = Sensei_Course_Structure::instance( $course_id );

		$this->assertTrue( $course_structure->save( $new_structure ) );

		$modified_structure                   = $course_structure->get();
		$modified_structure[0]['title']       = 'Update Module Name';
		$modified_structure[0]['description'] = 'Now improved!';

		$this->assertTrue( $course_structure->save( $new_structure ) );

		$structure = $course_structure->get();
		$this->assertExpectedStructure( $modified_structure, $structure );
	}

	/**
	 * Make sure lesson titles are updated properly.
	 */
	public function testSaveUpdateLessonTitle() {
		$this->login_as_admin();

		$course_id     = $this->factory->course->create();
		$new_structure = [
			[
				'type'  => 'lesson',
				'title' => 'New Lesson',
			],
		];

		$course_structure = Sensei_Course_Structure::instance( $course_id );

		$this->assertTrue( $course_structure->save( $new_structure ) );

		$modified_structure             = $course_structure->get();
		$modified_structure[0]['title'] = 'Improved Lesson Title';

		$this->assertTrue( $course_structure->save( $modified_structure ) );

		$structure = $course_structure->get();
		$this->assertExpectedStructure( $modified_structure, $structure );
	}

	/**
	 * Make sure lessons coming from a previous course get moved over and removed from old course.
	 */
	public function testSaveMoveLessonToNewCourse() {
		$this->login_as_admin();

		$course_id_a   = $this->factory->course->create();
		$course_id_b   = $this->factory->course->create();
		$new_structure = [
			[
				'type'  => 'lesson',
				'title' => 'New Lesson',
			],
		];

		$course_structure_a = Sensei_Course_Structure::instance( $course_id_a );
		$course_structure_b = Sensei_Course_Structure::instance( $course_id_b );

		$save_result = $course_structure_a->save( $new_structure );
		$this->assertTrue( $save_result );

		$structure = $course_structure_a->get();

		// Give course A's structure to course B.
		$this->assertTrue( $course_structure_b->save( $structure ) );

		$this->assertExpectedStructure( $structure, $course_structure_b->get() );
		$this->assertEquals( [], $course_structure_a->get() );
	}

	/**
	 * Make sure we can properly reorder lessons.
	 */
	public function testSaveReorderLessons() {
		$this->login_as_admin();

		$course_id     = $this->factory->course->create();
		$new_structure = [
			[
				'type'  => 'lesson',
				'title' => 'Lesson A',
			],
			[
				'type'  => 'lesson',
				'title' => 'Lesson B',
			],
			[
				'type'  => 'lesson',
				'title' => 'Lesson C',
			],
		];

		$course_structure = Sensei_Course_Structure::instance( $course_id );

		$this->assertTrue( $course_structure->save( $new_structure ) );

		$structure = $course_structure->get();
		$this->assertEquals( $new_structure[0]['title'], $structure[0]['title'] );
		$this->assertEquals( $new_structure[1]['title'], $structure[1]['title'] );
		$this->assertEquals( $new_structure[2]['title'], $structure[2]['title'] );

		$updated_structure = [
			$structure[1],
			$structure[0],
			$structure[2],
		];

		$this->assertTrue( $course_structure->save( $updated_structure ) );
		$this->assertExpectedStructure( $updated_structure, $course_structure->get() );
	}

	/**
	 * Make sure we can properly reorder modules.
	 */
	public function testSaveReorderModules() {
		$this->login_as_admin();

		$course_id     = $this->factory->course->create();
		$new_structure = [
			[
				'type'    => 'module',
				'title'   => 'Module A',
				'lessons' => [
					[
						'type'  => 'lesson',
						'title' => 'Lesson A',
					],
				],
			],
			[
				'type'    => 'module',
				'title'   => 'Module B',
				'lessons' => [
					[
						'type'  => 'lesson',
						'title' => 'Lesson B',
					],
					[
						'type'  => 'lesson',
						'title' => 'Lesson C',
					],
				],
			],
			[
				'type'    => 'module',
				'title'   => 'Module C',
				'lessons' => [],
			],
		];

		$course_structure = Sensei_Course_Structure::instance( $course_id );

		$this->assertTrue( $course_structure->save( $new_structure ) );

		$structure = $course_structure->get();
		$this->assertEquals( $new_structure[0]['title'], $structure[0]['title'] );
		$this->assertEquals( $new_structure[1]['title'], $structure[1]['title'] );
		$this->assertEquals( $new_structure[2]['title'], $structure[2]['title'] );

		$updated_structure = [
			$structure[1],
			$structure[0],
			$structure[2],
		];

		$this->assertTrue( $course_structure->save( $updated_structure ) );
		$this->assertExpectedStructure( $updated_structure, $course_structure->get() );
	}

	/**
	 * Make sure old meta is removed when we remove a lesson from a course.
	 */
	public function testCleanupRemoveLesson() {
		$this->login_as_teacher();

		$course_id  = $this->factory->course->create();
		$lesson_ids = $this->factory->lesson->create_many( 2 );

		$new_structure = [
			[
				'type'    => 'module',
				'title'   => 'New Module A',
				'lessons' => [
					[
						'type'  => 'lesson',
						'id'    => $lesson_ids[0],
						'title' => get_the_title( $lesson_ids[0] ),
					],
				],
			],
			[
				'type'  => 'lesson',
				'id'    => $lesson_ids[1],
				'title' => get_the_title( $lesson_ids[1] ),
			],
		];

		$course_structure = Sensei_Course_Structure::instance( $course_id );

		$this->assertTrue( $course_structure->save( $new_structure ) );

		$modified_structure = $course_structure->get();

		$this->assertEquals( 2, count( $modified_structure ) );

		$modified_structure[0]['lessons'] = [];
		unset( $modified_structure[1] );

		$this->assertTrue( $course_structure->save( $modified_structure ) );
		$this->assertExpectedStructure( $modified_structure, $course_structure->get() );

		$this->assertEquals( null, get_post_meta( $lesson_ids[0], '_lesson_course', true ), 'Course lesson meta should have been cleared' );
		$this->assertEquals( null, get_post_meta( $lesson_ids[1], '_lesson_course', true ), 'Course lesson meta should have been cleared' );
		$this->assertEquals( null, get_post_meta( $lesson_ids[0], '_order_' . $course_id, true ), 'Course lesson order meta should have been cleared' );
		$this->assertEquals( null, get_post_meta( $lesson_ids[0], '_order_module_' . $modified_structure[0]['id'], true ), 'Course lesson order meta should have been cleared' );
	}

	/**
	 * Make sure no changes are made when we save an identical course structure.
	 */
	public function testSaveIdenticalStructureNoChange() {
		$this->login_as_admin();

		$course_id     = $this->factory->course->create();
		$new_structure = [
			[
				'type'    => 'module',
				'title'   => 'Module A',
				'lessons' => [
					[
						'type'  => 'lesson',
						'title' => 'Lesson A',
					],
				],
			],
			[
				'type'    => 'module',
				'title'   => 'Module B',
				'lessons' => [
					[
						'type'  => 'lesson',
						'title' => 'Lesson B',
					],
				],
			],
			[
				'type'    => 'module',
				'title'   => 'Module C',
				'lessons' => [],
			],
			[
				'type'  => 'lesson',
				'title' => 'Lesson C',
			],
		];

		$course_structure = Sensei_Course_Structure::instance( $course_id );

		$this->assertTrue( $course_structure->save( $new_structure ) );

		$structure = $course_structure->get();

		$this->assertTrue( $course_structure->save( $structure ) );
		$this->assertExpectedStructure( $structure, $course_structure->get() );
	}

	/**
	 * Make sure saving an empty array clears the structure.
	 */
	public function testSaveEmptyArrayClearsStructure() {
		$this->login_as_admin();

		$course_id     = $this->factory->course->create();
		$new_structure = [
			[
				'type'    => 'module',
				'title'   => 'Module A',
				'lessons' => [
					[
						'type'  => 'lesson',
						'title' => 'Lesson A',
					],
				],
			],
			[
				'type'    => 'module',
				'title'   => 'Module C',
				'lessons' => [],
			],
			[
				'type'  => 'lesson',
				'title' => 'Lesson C',
			],
		];

		$course_structure = Sensei_Course_Structure::instance( $course_id );

		$this->assertTrue( $course_structure->save( $new_structure ) );

		$this->assertTrue( $course_structure->save( [] ) );
		$this->assertExpectedStructure( [], $course_structure->get() );
	}

	/**
	 * Make sure we can move a lesson to a module.
	 */
	public function testSaveMoveLessonToModule() {
		$this->login_as_admin();

		$course_id     = $this->factory->course->create();
		$new_structure = [
			[
				'type'    => 'module',
				'title'   => 'Module A',
				'lessons' => [],
			],
			[
				'type'    => 'module',
				'title'   => 'Module B',
				'lessons' => [
					[
						'type'  => 'lesson',
						'title' => 'Lesson B',
					],
				],
			],
			[
				'type'  => 'lesson',
				'title' => 'Lesson C',
			],
		];

		$course_structure = Sensei_Course_Structure::instance( $course_id );

		$this->assertTrue( $course_structure->save( $new_structure ) );

		$modified_structure = $course_structure->get();

		$modified_structure[0]['lessons'][] = $modified_structure[2];
		unset( $modified_structure[2] );

		$this->assertTrue( $course_structure->save( $modified_structure ) );
		$this->assertExpectedStructure( $modified_structure, $course_structure->get() );
	}

	/**
	 * Make sure we can move a lesson from a module.
	 */
	public function testSaveMoveLessonFromModule() {
		$this->login_as_admin();

		$course_id     = $this->factory->course->create();
		$new_structure = [
			[
				'type'    => 'module',
				'title'   => 'Module A',
				'lessons' => [
					[
						'type'  => 'lesson',
						'title' => 'Lesson A',
					],
				],
			],
			[
				'type'    => 'module',
				'title'   => 'Module B',
				'lessons' => [
					[
						'type'  => 'lesson',
						'title' => 'Lesson B',
					],
				],
			],
		];

		$course_structure = Sensei_Course_Structure::instance( $course_id );

		$this->assertTrue( $course_structure->save( $new_structure ) );

		$modified_structure = $course_structure->get();

		$modified_structure[]             = $modified_structure[0]['lessons'][0];
		$modified_structure[0]['lessons'] = [];

		$this->assertTrue( $course_structure->save( $modified_structure ) );
		$this->assertExpectedStructure( $modified_structure, $course_structure->get() );
	}

	/**
	 * Reset the course structure instances array.
	 */
	private function resetInstances() {
		$instances_property = new ReflectionProperty( 'Sensei_Course_Structure', 'instances' );
		$instances_property->setAccessible( true );
		$instances_property->setValue( [] );
	}

	/**
	 * Assert that a given structure matches the expected structure.
	 *
	 * @param array $expected_structure  Expected structure.
	 * @param array $structure           Structure result.
	 * @param string $level              Level description.
	 */
	private function assertExpectedStructure( array $expected_structure, array $structure, $level = 'the top-level' ) {
		$this->assertEquals( count( $expected_structure ), count( $structure ), sprintf( 'Structure should have the same number of items in %s', $level ) );

		foreach ( $expected_structure as $index => $expected_item ) {
			$item = $structure[ $index ];
			$this->assertEquals( $expected_item['id'], $item['id'], sprintf( 'Expected the same `id` for the items with index %s in %s', $index, $level ) );
			$this->assertEquals( $expected_item['type'], $item['type'], sprintf( 'Expected the same `type` for the items with index %s in %s', $index, $level ) );

			if ( 'lesson' === $expected_item['type'] ) {
				$this->assertFalse( array_key_exists( 'lessons', $item ), sprintf( 'Expected no `lessons` key for item with index %s in %s', $index, $level ) );
				$this->assertFalse( array_key_exists( 'description', $item ), sprintf( 'Expected no `description` key for item with index %s in %s', $index, $level ) );
				$this->assertEquals( get_the_title( $expected_item['id'] ), $item['title'], sprintf( 'Expected the same `title` for the items with index %s in %s', $index, $level ) );
			} else {
				$this->assertTrue( array_key_exists( 'lessons', $item ), sprintf( 'Expected a `lessons` key for item with index %s in %s', $index, $level ) );
				$this->assertTrue( array_key_exists( 'description', $item ), sprintf( 'Expected a `description` key for item with index %s in %s', $index, $level ) );
				$term = get_term( $expected_item['id'] );
				$this->assertEquals( $term->name, $item['title'], sprintf( 'Expected the same `title` for the items with index %s in %s', $index, $level ) );
			}

			if ( isset( $expected_item['lessons'] ) ) {
				$this->assertExpectedStructure( $expected_item['lessons'], $structure[ $index ]['lessons'], 'module id:' . $expected_item['id'] );
			}
		}
	}

	/**
	 * Save a structure.
	 *
	 * @param int   $course_id     Course ID.
	 * @param array $structure     Structure to save.
	 * @param int   $module_parent Module ID.
	 */
	private function saveStructure( int $course_id, array $structure, $module_parent = null ) {
		$order_lesson_adjust = $module_parent ? 0 : 1;
		$order_meta_key      = $module_parent ? '_order_module_' . $module_parent : '_order_' . $course_id;
		$module_order        = [];
		$lesson_order        = [];

		foreach ( $structure as $item ) {
			if ( 'lesson' === $item['type'] ) {
				add_post_meta( $item['id'], $order_meta_key, count( $lesson_order ) + $order_lesson_adjust );
				add_post_meta( $item['id'], '_lesson_course', $course_id );
				$lesson_order[] = $item['id'];

				if ( $module_parent ) {
					wp_set_object_terms( $item['id'], $module_parent, 'module' );
				}
			} else {
				$this->saveStructure( $course_id, $item['lessons'], $item['id'] );

				$module_order[] = $item['id'];
			}
		}

		if ( ! empty( $module_order ) ) {
			wp_set_object_terms( $course_id, $module_order, 'module' );
			update_post_meta( $course_id, '_module_order', array_map( 'strval', $module_order ) );
		}

		if ( ! empty( $lesson_order ) ) {
			if ( ! $module_parent ) {
				Sensei()->admin->save_lesson_order( implode( ',', $lesson_order ), $course_id );
			}
		}
	}
}
