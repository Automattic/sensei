<?php

/**
 * Sensei No users Table Relationship Test Class
 *
 * @covers Sensei_No_Users_Table_Relationship
 */
class Sensei_No_Users_Table_Relationship_Test extends WP_UnitTestCase {
	/**
	 * Tests that check returns `false` for users relationship when there are no posts.
	 *
	 * @covers Sensei_No_Users_Table_Relationship::can_use_users_relationship
	 */
	public function testCanUseUserRelationshipNoPosts() {
		/* Act */
		$actual = Sensei_No_Users_Table_Relationship::instance()->can_use_users_relationship();

		/* Assert */
		$this->assertFalse( $actual, 'Users relationship check should be false when there are no posts' );
	}

	/**
	 * Tests that check returns `false` for users relationship when there are no posts related to valid users.
	 *
	 * @covers Sensei_No_Users_Table_Relationship::can_use_users_relationship
	 */
	public function testCanUseUserRelationshipWithInvalidAuthors() {
		/* Arrange */
		$this->factory->user->create();
		$this->factory->post->create( [ 'post_author' => '0' ] );

		/* Act */
		$actual = Sensei_No_Users_Table_Relationship::instance()->can_use_users_relationship();

		/* Assert */
		$this->assertFalse( $actual, 'Users relationship check should be false when there are no posts related to valid users' );
	}

	/**
	 * Tests that check returns `true` for users relationship when there are posts related to users.
	 *
	 * @covers Sensei_No_Users_Table_Relationship::can_use_users_relationship
	 */
	public function testCanUseUserRelationshipWithPosts() {
		/* Arrange */
		$user_id = $this->factory->user->create();
		$this->factory->post->create( [ 'post_author' => $user_id ] );

		/* Act */
		$actual = Sensei_No_Users_Table_Relationship::instance()->can_use_users_relationship();

		/* Assert */
		$this->assertTrue( $actual, 'Users relationship check should be true when there are posts related to users' );
	}
}
