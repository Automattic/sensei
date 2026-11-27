<?php

namespace SenseiTest\Internal\Course_Lessons\Services;

/**
 * Tests for Course_Lessons_Sync_Service.
 *
 * The service registered by Sensei is already hooked to the meta and module term actions (the test
 * bootstrap turns the `course_lessons_table` flag on), so the tests write real metas and terms.
 *
 * @covers \Sensei\Internal\Course_Lessons\Services\Course_Lessons_Sync_Service
 */
class Course_Lessons_Sync_Service_Test extends \WP_UnitTestCase {
	/**
	 * Sensei factory.
	 *
	 * @var \Sensei_Factory
	 */
	protected $factory;

	public function setUp(): void {
		parent::setUp();
		$this->factory = new \Sensei_Factory();
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	/**
	 * A lesson created with its course meta gets the row of that course.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Services\Course_Lessons_Sync_Service::handle_meta_change
	 */
	public function testHandleMetaChange_LessonCreatedWithCourseMeta_InsertsItsRow(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create();

		/* Act. */
		$lesson_id = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );

		/* Assert. */
		self::assertSame( array( (string) $course_id ), array_column( $this->get_rows( $lesson_id ), 'course_id' ) );
	}

	/**
	 * Moving a lesson to another course leaves it with the row of the new course only.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Services\Course_Lessons_Sync_Service::handle_meta_change
	 */
	public function testHandleMetaChange_CourseMetaChangedToAnotherCourse_ReplacesTheRow(): void {
		/* Arrange. */
		$old_course_id = $this->factory->course->create();
		$new_course_id = $this->factory->course->create();
		$lesson_id     = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $old_course_id ) ) );

		/* Act. */
		update_post_meta( $lesson_id, '_lesson_course', $new_course_id );

		/* Assert. */
		self::assertSame( array( (string) $new_course_id ), array_column( $this->get_rows( $lesson_id ), 'course_id' ) );
	}

	/**
	 * A lesson detached from its course has no row.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Services\Course_Lessons_Sync_Service::handle_meta_change
	 */
	public function testHandleMetaChange_CourseMetaDeleted_DeletesTheRow(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );

		/* Act. */
		delete_post_meta( $lesson_id, '_lesson_course' );

		/* Assert. */
		self::assertSame( array(), $this->get_rows( $lesson_id ) );
	}

	/**
	 * A lesson at the course root stores its order in the course.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Services\Course_Lessons_Sync_Service::handle_meta_change
	 */
	public function testHandleMetaChange_CourseOrderMetaSet_StoresItAsTheLessonOrder(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );

		/* Act. */
		update_post_meta( $lesson_id, '_order_' . $course_id, 3 );

		/* Assert. */
		self::assertSame(
			array(
				array(
					'course_id'      => (string) $course_id,
					'module_term_id' => '0',
					'lesson_order'   => '3',
				),
			),
			$this->get_rows( $lesson_id )
		);
	}

	/**
	 * A lesson in a module stores its order in that module.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Services\Course_Lessons_Sync_Service::handle_meta_change
	 */
	public function testHandleMetaChange_ModuleOrderMetaSet_StoresItAsTheLessonOrder(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$module_id = $this->factory->module->create();
		wp_set_object_terms( $course_id, array( $module_id ), 'module' );
		$lesson_id = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );
		wp_set_object_terms( $lesson_id, array( $module_id ), 'module' );

		/* Act. */
		update_post_meta( $lesson_id, '_order_module_' . $module_id, 2 );

		/* Assert. */
		self::assertSame(
			array(
				array(
					'course_id'      => (string) $course_id,
					'module_term_id' => (string) $module_id,
					'lesson_order'   => '2',
				),
			),
			$this->get_rows( $lesson_id )
		);
	}

	/**
	 * An order meta of a course the lesson is not in leaves its row as it is.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Services\Course_Lessons_Sync_Service::handle_meta_change
	 */
	public function testHandleMetaChange_OrderMetaOfACourseTheLessonIsNotIn_LeavesTheRowAsItIs(): void {
		/* Arrange. */
		$course_id       = $this->factory->course->create();
		$other_course_id = $this->factory->course->create();
		$lesson_id       = $this->factory->lesson->create(
			array(
				'meta_input' => array(
					'_lesson_course'       => $course_id,
					'_order_' . $course_id => 3,
				),
			)
		);

		/* Act. */
		update_post_meta( $lesson_id, '_order_' . $other_course_id, 1 );

		/* Assert. */
		self::assertSame(
			array(
				array(
					'course_id'      => (string) $course_id,
					'module_term_id' => '0',
					'lesson_order'   => '3',
				),
			),
			$this->get_rows( $lesson_id )
		);
	}

	/**
	 * A lesson added to a module stores the module and its order in it, not its order in the course.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Services\Course_Lessons_Sync_Service::handle_set_object_terms
	 */
	public function testHandleSetObjectTerms_LessonAddedToModule_StoresTheModuleAndItsOrderInIt(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$module_id = $this->factory->module->create();
		wp_set_object_terms( $course_id, array( $module_id ), 'module' );
		$lesson_id = $this->factory->lesson->create(
			array(
				'meta_input' => array(
					'_lesson_course'              => $course_id,
					'_order_' . $course_id        => 3,
					'_order_module_' . $module_id => 1,
				),
			)
		);

		/* Act. */
		wp_set_object_terms( $lesson_id, array( $module_id ), 'module' );

		/* Assert. */
		self::assertSame(
			array(
				array(
					'course_id'      => (string) $course_id,
					'module_term_id' => (string) $module_id,
					'lesson_order'   => '1',
				),
			),
			$this->get_rows( $lesson_id )
		);
	}

	/**
	 * A module term that is not a module of the lesson's course does not count: the lesson is at the course root.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Services\Course_Lessons_Sync_Service::handle_set_object_terms
	 */
	public function testHandleSetObjectTerms_ModuleNotOneOfTheCoursesModules_StoresTheCourseRoot(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$module_id = $this->factory->module->create();
		$lesson_id = $this->factory->lesson->create(
			array(
				'meta_input' => array(
					'_lesson_course'              => $course_id,
					'_order_' . $course_id        => 3,
					'_order_module_' . $module_id => 1,
				),
			)
		);

		/* Act. */
		wp_set_object_terms( $lesson_id, array( $module_id ), 'module' );

		/* Assert. */
		self::assertSame(
			array(
				array(
					'course_id'      => (string) $course_id,
					'module_term_id' => '0',
					'lesson_order'   => '3',
				),
			),
			$this->get_rows( $lesson_id )
		);
	}

	/**
	 * Adding a module to a course puts the lessons that carry its term into that module.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Services\Course_Lessons_Sync_Service::handle_set_object_terms
	 */
	public function testHandleSetObjectTerms_ModuleAddedToTheCourse_StoresTheModuleForItsLessons(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$module_id = $this->factory->module->create();
		// A draft, so the resync is proven not to be limited to published lessons.
		$lesson_id = $this->factory->lesson->create(
			array(
				'post_status' => 'draft',
				'meta_input'  => array(
					'_lesson_course'              => $course_id,
					'_order_module_' . $module_id => 1,
				),
			)
		);
		wp_set_object_terms( $lesson_id, array( $module_id ), 'module' );

		/* Act. */
		wp_set_object_terms( $course_id, array( $module_id ), 'module' );

		/* Assert. */
		self::assertSame(
			array(
				array(
					'course_id'      => (string) $course_id,
					'module_term_id' => (string) $module_id,
					'lesson_order'   => '1',
				),
			),
			$this->get_rows( $lesson_id )
		);
	}

	/**
	 * A lesson removed from its module goes back to the course root, with no order until one is stored.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Services\Course_Lessons_Sync_Service::handle_deleted_term_relationships
	 */
	public function testHandleDeletedTermRelationships_LessonRemovedFromModule_StoresTheCourseRoot(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$module_id = $this->factory->module->create();
		wp_set_object_terms( $course_id, array( $module_id ), 'module' );
		$lesson_id = $this->factory->lesson->create(
			array(
				'meta_input' => array(
					'_lesson_course'              => $course_id,
					'_order_module_' . $module_id => 1,
				),
			)
		);
		wp_set_object_terms( $lesson_id, array( $module_id ), 'module' );

		/* Act. */
		wp_remove_object_terms( $lesson_id, array( $module_id ), 'module' );

		/* Assert. */
		self::assertSame(
			array(
				array(
					'course_id'      => (string) $course_id,
					'module_term_id' => '0',
					'lesson_order'   => null,
				),
			),
			$this->get_rows( $lesson_id )
		);
	}

	/**
	 * Removing a module from a course puts the lessons that carry its term back at the course root.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Services\Course_Lessons_Sync_Service::handle_deleted_term_relationships
	 */
	public function testHandleDeletedTermRelationships_ModuleRemovedFromTheCourse_StoresTheCourseRootForItsLessons(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$module_id = $this->factory->module->create();
		wp_set_object_terms( $course_id, array( $module_id ), 'module' );
		$lesson_id = $this->factory->lesson->create(
			array(
				'meta_input' => array(
					'_lesson_course'              => $course_id,
					'_order_' . $course_id        => 3,
					'_order_module_' . $module_id => 1,
				),
			)
		);
		wp_set_object_terms( $lesson_id, array( $module_id ), 'module' );

		/* Act. */
		wp_remove_object_terms( $course_id, array( $module_id ), 'module' );

		/* Assert. */
		self::assertSame(
			array(
				array(
					'course_id'      => (string) $course_id,
					'module_term_id' => '0',
					'lesson_order'   => '3',
				),
			),
			$this->get_rows( $lesson_id )
		);
	}

	/**
	 * Read the rows of a lesson.
	 *
	 * @param int $lesson_id Lesson ID.
	 * @return array[]
	 */
	private function get_rows( int $lesson_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->prepare( "SELECT course_id, module_term_id, lesson_order FROM {$wpdb->prefix}sensei_lms_course_lessons WHERE lesson_id = %d ORDER BY id", $lesson_id ),
			ARRAY_A
		);
	}
}
