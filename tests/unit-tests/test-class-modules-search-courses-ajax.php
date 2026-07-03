<?php
/**
 * AJAX tests for the modules course search endpoint (`sensei_json_search_courses`).
 *
 * @group ajax-calls
 */
class Sensei_Modules_Search_Courses_AJAX_Test extends WP_Ajax_UnitTestCase {

	/**
	 * Factory for creating test data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	public function setUp(): void {
		parent::setUp();
		$this->factory = new Sensei_Factory();
		add_filter( 'pre_http_request', '__return_empty_array' );
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testSearchCoursesJson_WhenUserCannotEditOthersCourses_ExcludesOtherAuthorsCourses() {
		/* Arrange. */
		$teacher_a = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$this->factory->course->create(
			array(
				'post_title'  => 'Secret Course Alpha',
				'post_status' => 'private',
				'post_author' => $teacher_a,
			)
		);

		// Search as a different teacher, who lacks the edit_others_courses capability.
		$teacher_b = $this->factory->user->create( array( 'role' => 'teacher' ) );
		wp_set_current_user( $teacher_b );

		/* Act. */
		$results = $this->search_for_courses( 'Secret' );

		/* Assert. */
		$this->assertNotContains( 'Secret Course Alpha', $results, "A teacher should not see another author's private course." );
	}

	public function testSearchCoursesJson_WhenUserCannotEditOthersCourses_IncludesOwnCourses() {
		/* Arrange. */
		// Search as the teacher who authored the course.
		$teacher = $this->factory->user->create( array( 'role' => 'teacher' ) );
		wp_set_current_user( $teacher );
		$this->factory->course->create(
			array(
				'post_title'  => 'Secret Course Beta',
				'post_status' => 'private',
				'post_author' => $teacher,
			)
		);

		/* Act. */
		$results = $this->search_for_courses( 'Secret' );

		/* Assert. */
		$this->assertContains( 'Secret Course Beta', $results, 'A teacher should still see their own course.' );
	}

	public function testSearchCoursesJson_WhenUserCanEditOthersCourses_IncludesAllAuthorsCourses() {
		/* Arrange. */
		$teacher_a = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$this->factory->course->create(
			array(
				'post_title'  => 'Secret Course Gamma',
				'post_status' => 'private',
				'post_author' => $teacher_a,
			)
		);

		// Search as a different user: an admin, who has the edit_others_courses capability.
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		/* Act. */
		$results = $this->search_for_courses( 'Secret' );

		/* Assert. */
		$this->assertContains( 'Secret Course Gamma', $results, "An admin should see other authors' courses." );
	}

	/**
	 * Dispatch the `sensei_json_search_courses` AJAX action and return the decoded results.
	 *
	 * @param string $term Search term.
	 * @return array Map of course ID => title.
	 */
	private function search_for_courses( string $term ): array {
		$nonce                = wp_create_nonce( 'search-courses' );
		$_GET['security']     = $nonce;
		$_REQUEST['security'] = $nonce;
		$_GET['term']         = $term;

		try {
			$this->_handleAjax( 'sensei_json_search_courses' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		return (array) json_decode( $this->_last_response, true );
	}
}
