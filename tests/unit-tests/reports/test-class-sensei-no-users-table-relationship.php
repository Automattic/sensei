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

	/**
	 * Course columns retain their activity when the student fallback is enabled.
	 *
	 * @covers Sensei_No_Users_Table_Relationship::filter_analysis_overview_column_data
	 */
	public function testFilterAnalysisOverviewColumnData_CoursesReportGiven_ReturnsUnchangedColumns(): void {
		global $wpdb;

		tests_add_filter( 'sensei_can_use_users_relationship', '__return_false' );
		$database           = $this->createStub( wpdb::class );
		$database->comments = 'wp_comments';
		$database->method( 'prepare' )->willReturn( 'student activity query' );
		$database->method( 'get_var' )->willReturn( '2022-02-20 12:00:00' );
		$table             = $this->createStub( Sensei_Reports_Overview_List_Table_Courses::class );
		$table->csv_output = true;
		$columns           = array( 'last_activity' => '2022-01-04 12:00:00' );
		$original_database = $wpdb;

		try {
			$wpdb   = $database;
			$actual = Sensei_No_Users_Table_Relationship::instance()->filter_analysis_overview_column_data(
				$columns,
				(object) array( 'ID' => 42 ),
				$table
			);
		} finally {
			$wpdb = $original_database;
		}

		self::assertSame( $columns, $actual );
	}
}
