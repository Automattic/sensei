<?php

/**
 * Tests for Sensei_Course_Structure_Test class.
 *
 * @group course-structure
 */
class Sensei_Course_Structure_Test extends WP_UnitTestCase {
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
