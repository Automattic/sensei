<?php
/**
 * AJAX tests for Sensei_Learner_Management.
 *
 * @package sensei
 *
 * @group ajax-calls
 */
class Sensei_Learner_Management_AJAX_Test extends WP_Ajax_UnitTestCase {
	public function setUp(): void {
		parent::setUp();

		Sensei()->teacher->create_role();

		new Sensei_Learner_Management( '' );

		// _handleAjax() fires admin_init, which triggers WordPress's api.wordpress.org update checks.
		add_filter( 'pre_http_request', '__return_empty_array' );
	}

	public function tearDown(): void {
		remove_filter( 'pre_http_request', '__return_empty_array' );

		parent::tearDown();
	}

	/**
	 * Runs the search-users AJAX action and returns the decoded response.
	 *
	 * @param string $term Search term.
	 *
	 * @return array
	 */
	private function do_search( string $term ): array {
		$nonce = wp_create_nonce( 'search-users' );

		$_GET['term']         = $term;
		$_GET['security']     = $nonce;
		$_REQUEST['security'] = $nonce;

		try {
			$this->_handleAjax( 'sensei_json_search_users' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		return (array) json_decode( $this->_last_response, true );
	}

	/**
	 * A teacher must not be able to see administrators through the user search.
	 *
	 * @covers Sensei_Learner_Management::json_search_users
	 */
	public function testJsonSearchUsers_TeacherSearchedForMatchingAdministrator_ExcludesAdminFromResults() {
		/* Arrange. */
		$teacher_id = self::factory()->user->create( array( 'role' => 'teacher' ) );
		$admin_id   = self::factory()->user->create(
			array(
				'role'       => 'administrator',
				'user_login' => 'searchme_admin',
			)
		);

		wp_set_current_user( $teacher_id );

		/* Act. */
		$response = $this->do_search( 'searchme' );

		/* Assert. */
		$this->assertArrayNotHasKey( $admin_id, $response );
	}

	/**
	 * The user search must not expose email addresses.
	 *
	 * @covers Sensei_Learner_Management::json_search_users
	 */
	public function testJsonSearchUsers_TeacherSearchedForMatchingStudent_OmitsEmailFromLabel() {
		/* Arrange. */
		$teacher_id = self::factory()->user->create( array( 'role' => 'teacher' ) );
		$student_id = self::factory()->user->create(
			array(
				'role'       => 'subscriber',
				'user_login' => 'searchme_student',
				'user_email' => 'searchme_student@example.com',
			)
		);

		wp_set_current_user( $teacher_id );

		/* Act. */
		$response = $this->do_search( 'searchme' );

		/* Assert. */
		$this->assertStringNotContainsString( 'searchme_student@example.com', $response[ $student_id ] ?? '' );
	}

	/**
	 * A user without the grades capability must not get any search results.
	 *
	 * @covers Sensei_Learner_Management::json_search_users
	 */
	public function testJsonSearchUsers_UserWithoutGradesCapGiven_ReturnsNoUsers() {
		/* Arrange. */
		$requester_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		self::factory()->user->create(
			array(
				'role'       => 'subscriber',
				'user_login' => 'searchme_target',
			)
		);

		wp_set_current_user( $requester_id );

		/* Act. */
		$response = $this->do_search( 'searchme' );

		/* Assert. */
		$this->assertEmpty( array_filter( array_keys( $response ), 'is_numeric' ) );
	}
}
