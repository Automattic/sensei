<?php

class Sensei_Globals_Test extends WP_UnitTestCase {
	/**
	 * Setup function.
	 */
	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Test the global $woothemes_sensei object
	 */
	function testSenseiGlobalObject() {
		// setup the test
		global $woothemes_sensei;

		// test if the global sensei object is loaded
		$this->assertTrue( isset( $woothemes_sensei ), 'Sensei global object loaded ' );

		// check if the version number is setup
		$this->assertTrue( isset( Sensei()->version ), 'Sensei version number is set' );
	}

	/**
	 * Test the Sensei() global function to ensure that it works and return and instance
	 * for the main Sensei object
	 */
	function testSenseiGlobalAccessFunction() {

		// make sure the function is loaded
		$this->assertTrue( function_exists( 'Sensei' ), 'The global Sensei() function does not exist.' );

		// make sure it return an instance of class WooThemes_Sensei
		$this->assertTrue(
			'Sensei_Main' == get_class( Sensei() ),
			'The Sensei() function does not return an instance of class WooThemes_Sensei'
		);

	}

	function testSenseiFunctionReturnSameSenseiInstance() {
		$this->assertSame( Sensei(), Sensei(), 'Sensei() should always return the same Sensei_Main instance' );
	}

	/**
	 * Tests to make sure the version is set on new installs but the legacy update flag option isn't set.
	 */
	public function testUpdateNewInstall() {
		$this->resetUpdateOptions();

		Sensei()->update();

		$this->assertEquals( Sensei()->version, get_option( 'sensei-version' ) );
		$this->assertEmpty( get_option( 'sensei_enrolment_legacy' ), 'Legacy update flag option should not be set on new installs' );
	}

	/**
	 * Tests to make sure the version and legacy update flag option are set when both course and progress
	 * artifacts exist.
	 */
	public function testUpdateOldInstallWithProgress() {
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		Sensei_Utils::user_start_course( $user_id, $course_id );

		$this->resetUpdateOptions();

		Sensei()->update();

		$this->assertEquals( Sensei()->version, get_option( 'sensei-version' ) );
		$this->assertNotEmpty( get_option( 'sensei_enrolment_legacy' ), 'Legacy update flag option should be set on updates even when course and progress artifacts exist' );
	}

	/**
	 * Tests to make sure the version is set on v1 updates and the legacy update flag option is set when there are
	 * progress artifacts.
	 */
	public function testUpdatev1UpdateWithProgress() {
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		Sensei_Utils::user_start_course( $user_id, $course_id );

		$this->resetUpdateOptions();

		update_option( 'woothemes-sensei-version', '1.9.0' );
		update_option( 'woothemes-sensei-settings', [ 'settings' => true ] );

		Sensei()->update();

		$this->assertEquals( Sensei()->version, get_option( 'sensei-version' ) );
		$this->assertNotEmpty( get_option( 'sensei_enrolment_legacy' ), 'Legacy update flag option should be set during v1 updates with progress artifacts' );
	}

	/**
	 * Tests to make sure the version is set on v1 updates and the legacy update flag option is NOT set when there are
	 * no progress artifacts.
	 */
	public function testUpdatev1UpdateWithoutProgress() {
		$this->resetUpdateOptions();

		update_option( 'woothemes-sensei-version', '1.9.0' );
		update_option( 'woothemes-sensei-settings', [ 'settings' => true ] );

		Sensei()->update();

		$this->assertEquals( Sensei()->version, get_option( 'sensei-version' ) );
		$this->assertEmpty( get_option( 'sensei_enrolment_legacy' ), 'Legacy update flag option should NOT be set during v1 updates without progress artifacts' );
	}

	/**
	 * Tests to make sure the version is set on v2 updates and the legacy update flag option is set, even without
	 * progress artifacts.
	 */
	public function testUpdatev2UpdateWithoutProgress() {
		$this->resetUpdateOptions();

		update_option( 'sensei-version', '2.4.0' );

		Sensei()->update();

		$this->assertEquals( Sensei()->version, get_option( 'sensei-version' ) );
		$this->assertNotEmpty( get_option( 'sensei_enrolment_legacy' ), 'Legacy update flag option should be set during v2 updates with known previous version' );
	}

	/**
	 * Tests to make sure the version is set on v2 updates and the legacy update flag option is set when the previous
	 * version wasn't known but there were progress artifacts.
	 */
	public function testUpdatev2UpdateWithProgress() {
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		Sensei_Utils::user_start_course( $user_id, $course_id );

		$this->resetUpdateOptions();

		Sensei()->update();

		$this->assertEquals( Sensei()->version, get_option( 'sensei-version' ) );
		$this->assertNotEmpty( get_option( 'sensei_enrolment_legacy' ), 'Legacy update flag option should be set during v2 updates with progress' );
	}

	/**
	 * Resets the update options.
	 */
	private function resetUpdateOptions() {
		delete_option( 'sensei-version' );
		delete_option( 'sensei_enrolment_legacy' );
	}

	/**
	 * Testing the version numbers before releasing the plugin.
	 *
	 * The version number in the plugin information block should match the version number specified in the code.
	 */
	function testVersionNumber() {

		// make sure the version number was set on the new sensei instance
		$this->assertTrue( isset( Sensei()->version ), 'The version number is not set on the global Sensei object' );
	}

	/**
	 * Tests to make sure that when Sensei comments are included in count (for example: with WooCommerce)
	 * they are properly removed.
	 */
	public function testSenseiCommentCountsAllIncludeSenseiCounts() {
		add_filter( 'sensei_comment_counts_include_sensei_comments', '__return_true' );

		$user_ids = $this->factory->user->create_many( 2 );

		$course_status_map = [
			'in-progress' => 2,
			'complete'    => 1,
		];
		$this->createCourseAndProgress( 2, $course_status_map, $user_ids );

		$stats_with_sensei = (object) [
			'approved'       => 3,
			'spam'           => 1,
			'trash'          => 0,
			'post-trashed'   => 0,
			'total_comments' => 6,
			'all'            => 5,
			'moderated'      => 2,
		];

		$stats_without_sensei = (object) [
			'approved'       => 3,
			'spam'           => 1,
			'trash'          => 0,
			'post-trashed'   => 0,
			'total_comments' => 3,
			'all'            => 2,
			'moderated'      => 2,
		];

		$stats = Sensei()->sensei_count_comments( $stats_with_sensei, 0 );

		remove_filter( 'sensei_comment_counts_include_sensei_comments', '__return_true' );

		$this->assertEquals( (array) $stats_without_sensei, (array) $stats, 'Stats should not have Sensei comment counts included' );
	}


	/**
	 * Tests to make sure that when Sensei comments are NOT included in count (no WooCommerce) they aren't removed.
	 */
	public function testSenseiCommentCountsAllExcludeSenseiCountsWithoutPost() {
		add_filter( 'sensei_comment_counts_include_sensei_comments', '__return_false' );

		$user_ids = $this->factory->user->create_many( 2 );

		$course_status_map = [
			'in-progress' => 2,
			'complete'    => 1,
		];
		$this->createCourseAndProgress( 2, $course_status_map, $user_ids );

		$stats_without_sensei = (object) [
			'approved'       => 3,
			'spam'           => 1,
			'trash'          => 0,
			'post-trashed'   => 0,
			'total_comments' => 6,
			'all'            => 5,
			'moderated'      => 2,
		];

		$stats = Sensei()->sensei_count_comments( $stats_without_sensei, 0 );

		remove_filter( 'sensei_comment_counts_include_sensei_comments', '__return_false' );

		$this->assertEquals( (array) $stats_without_sensei, (array) $stats, 'Stats should be what we passed back' );
	}

	/**
	 * Tests to make sure that when Sensei comments are NOT included in count (has post ID) they aren't properly removed.
	 */
	public function testSenseiCommentCountsAllExcludeSenseiCountsWithPost() {
		add_filter( 'sensei_comment_counts_include_sensei_comments', '__return_false' );

		$user_ids = $this->factory->user->create_many( 2 );

		$course_status_map = [
			'in-progress' => 2,
			'complete'    => 1,
		];
		$this->createCourseAndProgress( 2, $course_status_map, $user_ids );

		$post_id = $this->factory->post->create();

		$stats_without_sensei = (object) [
			'approved'       => 3,
			'spam'           => 1,
			'trash'          => 0,
			'post-trashed'   => 0,
			'total_comments' => 6,
			'all'            => 5,
			'moderated'      => 2,
		];

		$stats = Sensei()->sensei_count_comments( $stats_without_sensei, $post_id );

		remove_filter( 'sensei_comment_counts_include_sensei_comments', '__return_false' );

		$this->assertEquals( (array) $stats_without_sensei, (array) $stats, 'Stats should be what we passed back' );
	}

	/**
	 * Create courses and comments for the course.
	 *
	 * @param int   $course_count         Number of courses.
	 * @param array $comment_approved_map Map of status => n.
	 * @param array $user_ids             User IDs to use for comment generation.
	 *
	 * @return array
	 */
	private function createCourseAndProgress( $course_count, $comment_approved_map, $user_ids ) {
		$comment_args = [
			'user_id'      => function() use ( $user_ids ) {
				shuffle( $user_ids );

				return $user_ids[0];
			},
			'comment_type' => function() {
				$types = [ 'sensei_course_status', 'sensei_lesson_status', 'sensei_user_answer' ];

				shuffle( $types );

				return $types[0];
			}
		];


		$post_ids    = $this->factory->course->create_many( $course_count );
		$comment_ids = $this->createCommentsForPosts( $post_ids, $comment_approved_map, $comment_args );

		return [ $post_ids, $comment_ids ];
	}

	/**
	 * Create comments for post IDs.
	 *
	 * @param int[] $post_ids             Post IDs.
	 * @param array $comment_approved_map Map of status => n.
	 * @param array $comment_args         Arguments to pass to comment generator.
	 *
	 * @return int[]
	 */
	private function createCommentsForPosts( $post_ids, $comment_approved_map, $comment_args ) {
		$comment_args['comment_post_ID'] = function() use ( $post_ids ) {
			shuffle( $post_ids );

			return $post_ids[0];
		};

		$comment_ids = [];
		foreach( $comment_approved_map as $status => $n ) {
			$comment_args['comment_approved'] = $status;

			for( $i=0; $i < $n; $i++ ) {
				$comment_ids[] = $this->createComment( $comment_args );
			}
		}

		return $comment_ids;
	}

	/**
	 * Create a comment based on certain arguments.
	 *
	 * @param array $comment_args Arguments to pass to comment factory.
	 *
	 * @return int
	 */
	private function createComment( $comment_args ) {
		foreach ( $comment_args as $name => $value ) {
			if ( is_callable( $value ) ) {
				$comment_args[ $name ] = call_user_func( $value );
			}
		}

		return $this->factory->comment->create( $comment_args );
	}
}
