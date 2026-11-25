<?php

namespace SenseiTest\Admin\Content_Duplicators;

use Sensei\Admin\Content_Duplicators\Post_Duplicator;

/**
 * Class Post_Duplicator_Test
 *
 * @covers Sensei\Admin\Content_Duplicators\Post_Duplicator
 */
class Post_Duplicator_Test extends \WP_UnitTestCase {

	public function testDuplicate_OriginalPostGiven_ReturnsNewPost() {
		// Arrange.
		$original_post   = $this->factory()->post->create_and_get();
		$post_duplicator = new Post_Duplicator();

		// Act.
		$new_post = $post_duplicator->duplicate( $original_post );

		// Assert.
		$this->assertNotEquals( $original_post->ID, $new_post->ID );
	}

	/**
	 * The duplicate of a lesson is in the same course and module, with the same orders, next to
	 * its other metas and terms.
	 *
	 * @covers \Sensei\Admin\Content_Duplicators\Post_Duplicator::duplicate
	 */
	public function testDuplicate_LessonInAModuleOfACourseGiven_CopiesItsStructureOnce() {
		/* Arrange. */
		$repository = Sensei()->course_lessons_repository;
		$course_id  = $this->factory()->post->create( array( 'post_type' => 'course' ) );
		$module_id  = $this->factory()->term->create( array( 'taxonomy' => 'module' ) );
		$tag_id     = $this->factory()->term->create( array( 'taxonomy' => 'lesson-tag' ) );
		$lesson     = $this->factory()->post->create_and_get( array( 'post_type' => 'lesson' ) );
		$repository->attach_lesson( $lesson->ID, $course_id );
		$repository->set_lesson_order( $lesson->ID, $course_id, 3 );
		$repository->set_lesson_module( $lesson->ID, $module_id );
		$repository->set_lesson_module_order( $lesson->ID, $module_id, 2 );
		wp_set_object_terms( $lesson->ID, array( $tag_id ), 'lesson-tag' );
		add_post_meta( $lesson->ID, '_lesson_length', '15' );

		/* Act. */
		$copy = ( new Post_Duplicator() )->duplicate( $lesson );

		/* Assert. */
		$this->assertSame( $course_id, $repository->get_course_id( $copy->ID ), 'The copy should be in the course.' );
		$this->assertSame( 3, $repository->get_lesson_order( $copy->ID, $course_id ), 'The copy should keep the order in the course.' );
		$this->assertCount( 1, get_post_meta( $copy->ID, '_order_' . $course_id, false ), 'The order should be copied once.' );
		$this->assertTrue( $repository->has_lesson_module( $copy->ID, $module_id ), 'The copy should be in the module.' );
		$this->assertSame( 2, $repository->get_lesson_module_order( $copy->ID, $module_id ), 'The copy should keep the order in the module.' );
		$this->assertSame( array( '15' ), get_post_meta( $copy->ID, '_lesson_length', false ), 'The other metas should be copied once.' );
		$this->assertTrue( has_term( $tag_id, 'lesson-tag', $copy->ID ), 'The other terms should be copied.' );
	}

	/**
	 * The duplicate of a lesson that is in more than one module is in all of them.
	 *
	 * @covers \Sensei\Admin\Content_Duplicators\Post_Duplicator::duplicate
	 */
	public function testDuplicate_LessonInTwoModulesGiven_CopiesBothModules() {
		/* Arrange. */
		$module_ids = $this->factory()->term->create_many( 2, array( 'taxonomy' => 'module' ) );
		$lesson     = $this->factory()->post->create_and_get( array( 'post_type' => 'lesson' ) );
		wp_set_object_terms( $lesson->ID, $module_ids, 'module' );

		/* Act. */
		$copy = ( new Post_Duplicator() )->duplicate( $lesson );

		/* Assert. */
		$copy_module_ids = wp_list_pluck( Sensei()->course_lessons_repository->get_lesson_modules( $copy->ID ), 'term_id' );
		$this->assertEqualsCanonicalizing( $module_ids, $copy_module_ids );
	}

	/**
	 * A lesson whose course meta is added to the ignore list is duplicated out of the course.
	 *
	 * @covers \Sensei\Admin\Content_Duplicators\Post_Duplicator::duplicate
	 */
	public function testDuplicate_LessonCourseMetaAddedToTheIgnoreList_LeavesTheCopyOutOfTheCourse() {
		/* Arrange. */
		$repository = Sensei()->course_lessons_repository;
		$course_id  = $this->factory()->post->create( array( 'post_type' => 'course' ) );
		$lesson     = $this->factory()->post->create_and_get( array( 'post_type' => 'lesson' ) );
		$repository->attach_lesson( $lesson->ID, $course_id );
		add_filter(
			'sensei_duplicate_post_ignore_meta',
			function ( $ignore_meta ) {
				$ignore_meta[] = '_lesson_course';

				return $ignore_meta;
			}
		);

		/* Act. */
		$copy = ( new Post_Duplicator() )->duplicate( $lesson );

		/* Assert. */
		$this->assertNull( $repository->get_course_id( $copy->ID ) );
	}
}
