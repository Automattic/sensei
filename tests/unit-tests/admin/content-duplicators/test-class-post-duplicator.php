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
}
