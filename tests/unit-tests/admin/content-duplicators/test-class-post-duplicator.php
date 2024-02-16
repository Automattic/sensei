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
}
