<?php
/**
 * File with class for testing Sensei Preview User.
 *
 * @package sensei-tests
 */

/**
 * Class for testing Sensei_Preview_User class.
 *
 * @group Preview User
 */
class Sensei_Preview_User_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;

	/**
	 * Factory object.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory      = new Sensei_Factory();
		$this->preview_user = new Sensei_Preview_User();
		add_filter( 'wp_redirect', [ $this, 'go_to' ] );
	}

	/**
	 * Clean up after the test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_filter( 'wp_redirect', [ $this, 'go_to' ] );
	}

	/**
	 * Switch to preview user, then switch back.
	 */
	public function testSwitchToPreviewUser() {

		$course_id   = $this->factory->course->create();
		$course_link = get_permalink( $course_id );
		$admin_id    = $this->get_user_by_role( 'administrator' );
		$this->login_as( $admin_id );

		// Switch to preview user.

		$this->go_to( add_query_arg( [ 'sensei-preview-as-student' => wp_create_nonce( 'sensei-preview-as-student' ) ], $course_link ) );

		$this->assertNotEquals( $admin_id, get_current_user_id(), 'Current user should be changed.' );
		$preview_user = wp_get_current_user();
		$this->assertRegexp( '/^Preview Student.*$/', $preview_user->display_name, 'Current user should be a preview student.' );

		// Switch off preview user.

		$this->go_to( add_query_arg( [ 'sensei-exit-student-preview' => wp_create_nonce( 'sensei-exit-student-preview' ) ], $course_link ) );

		$this->assertEquals( $admin_id, get_current_user_id(), 'Current user is not changed back.' );
		$this->assertEmpty( get_user_by( 'ID', $preview_user->ID ), 'Preview user is not removed.' );

	}

	/**
	 * Courses should have independent preview users.
	 */
	public function testPreviewUserPerCourse() {

		$course_1 = $this->factory->course->create();
		$course_2 = $this->factory->course->create();
		$admin_id = $this->get_user_by_role( 'administrator' );
		$this->login_as( $admin_id );

		// Switch to preview user for course 1.

		$this->go_to( add_query_arg( [ 'sensei-preview-as-student' => wp_create_nonce( 'sensei-preview-as-student' ) ], get_permalink( $course_1 ) ) );

		$preview_user_1 = get_current_user_id();
		wp_set_current_user( $admin_id );
		$this->go_to( get_permalink( $course_2 ) );

		$this->assertNotEquals( $preview_user_1, get_current_user_id(), 'Course 2 should not use course 1\'s preview user.' );

		// Switch to preview user for course 2.

		$this->go_to( add_query_arg( [ 'sensei-preview-as-student' => wp_create_nonce( 'sensei-preview-as-student' ) ], get_permalink( $course_2 ) ) );

		$preview_user_2 = get_current_user_id();
		wp_set_current_user( $admin_id );
		$this->go_to( get_permalink( $course_2 ) );

		$this->assertEquals( $preview_user_2, get_current_user_id(), 'Course 2 should use its own preview user.' );

		wp_set_current_user( $admin_id );
		$this->go_to( get_permalink( $course_1 ) );

		$this->assertNotEquals( $preview_user_2, get_current_user_id(), 'Course 1 should not use course 2\'s preview user.' );

	}

	/**
	 * Switch to preview user, then switch back.
	 */
	public function testPreviewUserForLessons() {

		list( 'course_id' => $course_id, 'lesson_ids' => list( $lesson_id ) ) = $this->factory->get_course_with_lessons();
		$admin_id = $this->get_user_by_role( 'administrator' );
		$this->login_as( $admin_id );

		// Switch to preview user.

		$this->go_to( add_query_arg( [ 'sensei-preview-as-student' => wp_create_nonce( 'sensei-preview-as-student' ) ], get_permalink( $course_id ) ) );

		$this->go_to( get_permalink( $lesson_id ) );

		$this->assertNotEquals( $admin_id, get_current_user_id(), 'Preview user should be used for the lesson.' );

	}

	/**
	 * Reset user ID when navigating to a new page.
	 *
	 * Normally when the tested class changes the user, it should be for the current request only. It is however persisted in the test environment, so we need to set it back to the original user (admin) when using go_to() or on redirects.
	 *
	 * @param string $url URL.
	 */
	public function go_to( $url ) {
		wp_set_current_user( $this->get_user_by_role( 'administrator' ) );
		parent::go_to( $url );
	}


	/**
	 * Preview user can see unpublished course and its lessons. Regular student cannot.
	 */
	public function testPreviewUnpublishedCourse() {

		list( 'course_id' => $course_id, 'lesson_ids' => list( $lesson_id ) ) = $this->factory->get_course_with_lessons(
			[
				'lesson_args' => [
					'post_status' => 'draft',
				],
				'course_args' => [
					'post_status' => 'draft',
				],
			]
		);

		$lesson_link    = get_preview_post_link( $lesson_id, [], get_permalink( $lesson_id ) );
		$lesson_content = get_the_content( null, false, $lesson_id );

		$this->login_as_student();
		parent::go_to( $lesson_link );
		$this->assertEmpty( get_the_content(), 'Regular student user should not see unpublished lesson content.' );

		$this->login_as_admin();
		$this->go_to( add_query_arg( [ 'sensei-preview-as-student' => wp_create_nonce( 'sensei-preview-as-student' ) ], $lesson_link ) );
		parent::go_to( $lesson_link );

		$this->assertEquals( $lesson_content, get_the_content(), 'Preview user should see unpublished lesson content.' );
	}

	/**
	 * Feature can be disabled with a filter.
	 */
	public function testFeatureDisabled() {

		add_filter( 'sensei_feature_preview_students', '__return_false' );

		$course_id = $this->factory->course->create();
		$admin_id  = $this->get_user_by_role( 'administrator' );
		$this->login_as( $admin_id );

		$this->go_to( add_query_arg( [ 'sensei-preview-as-student' => wp_create_nonce( 'sensei-preview-as-student' ) ], get_permalink( $course_id ) ) );

		$this->assertEquals( $admin_id, get_current_user_id(), 'Preview user should not be used when feature is disabled.' );

	}

	public function testSkipWpMail_ReturnIsTrueAndHasPreviewUser_ReturnsFalse() {
		// Arrange
		$atts = [
			'to'      => [ 'user1@example.com', 'user2@preview.senseilms' ],
			'headers' => [
				'Cc'       => 'user3@example.com,user4@example.com',
				'Bcc'      => 'user5@example.com,user6@example.com',
				'Reply-To' => 'user7@example.com,user8@example.com',
			],
			'message' => 'Hello world',
			'subject' => 'Test email',
		];

		// Act
		$result = $this->preview_user->skip_wp_mail( true, $atts );

		// Assert
		$this->assertFalse( $result );
	}

	public function testSkipWpMail_HasPreviewUserInTo_ReturnsFalse() {
		// Arrange
		$atts = [
			'to'      => 'user1@example.com,user2@preview.senseilms',
			'message' => 'Hello world',
			'subject' => 'Test email',
		];

		// Act
		$result = $this->preview_user->skip_wp_mail( null, $atts );

		// Assert
		$this->assertFalse( $result );
	}

	public function testSkipWpMail_HasPreviewUserInCc_ReturnsFalse() {
		// Arrange
		$atts = [
			'to'      => 'user1@example.com',
			'headers' => [
				'Cc' => 'user2@preview.senseilms',
			],
			'message' => 'Hello world',
			'subject' => 'Test email',
		];

		// Act
		$result = $this->preview_user->skip_wp_mail( null, $atts );

		// Assert
		$this->assertFalse( $result );
	}


	public function testSkipWpMail_HasPreviewUserInBcc_ReturnsFalse() {
		// Arrange
		$atts = [
			'to'      => 'user1@example.com',
			'headers' => [
				'Bcc' => 'user2@preview.senseilms',
			],
			'message' => 'Hello world',
			'subject' => 'Test email',
		];

		// Act
		$result = $this->preview_user->skip_wp_mail( null, $atts );

		// Assert
		$this->assertFalse( $result );
	}

	public function testSkipWpMail_HasPreviewUserInReplyTo_ReturnsFalse() {
		// Arrange
		$atts = [
			'to'      => 'user1@example.com',
			'headers' => [
				'Reply-To' => 'user2@preview.senseilms',
			],
			'message' => 'Hello world',
			'subject' => 'Test email',
		];

		// Act
		$result = $this->preview_user->skip_wp_mail( null, $atts );

		// Assert
		$this->assertFalse( $result );
	}


	public function testSkipWpMail_HasPreviewUserInFrom_ReturnsFalse() {
		// Arrange
		$atts = [
			'to'      => 'user1@example.com',
			'headers' => [
				'From' => 'User 2 <user2@preview.senseilms>',
			],
			'message' => 'Hello world',
			'subject' => 'Test email',
		];

		// Act
		$result = $this->preview_user->skip_wp_mail( null, $atts );

		// Assert
		$this->assertFalse( $result );
	}


	public function testSkipWpMail_HeadersPassedAsString_ReturnsFalse() {
		// Arrange
		$atts = [
			'to'      => 'user1@example.com',
			'headers' => "Cc: user3@example.com,user4@preview.senseilms\r\nBcc: user5@example.com,user6@example.com\r\nReply-To: user7@example.com,user8@example.com",
			'message' => 'Hello world',
			'subject' => 'Test email',
		];

		// Act
		$result = $this->preview_user->skip_wp_mail( null, $atts );

		// Assert
		$this->assertFalse( $result );
	}

	public function testSkipWpMail_EmailsArentPreviewUsers_ReturnsAtts() {
		// Arrange
		$atts = [
			'to'      => [ 'user1@example.com', 'user2@example.com' ],
			'headers' => [
				'Cc'       => 'user3@example.com,user4@example.com',
				'Bcc'      => 'user5@example.com,user6@example.com',
				'Reply-To' => 'user7@example.com,user8@example.com',
			],
			'message' => 'Hello world',
			'subject' => 'Test email',
		];

		// Act
		$result = $this->preview_user->skip_wp_mail( null, $atts );

		// Assert
		$this->assertNull( $result );
	}


	public function testSkipWpMail_ChecksOnlyCcBccReplyToAndFromHeaders_ReturnsNull() {
		// Arrange
		$atts = [
			'to'      => [ 'user1@example.com', 'user2@example.com' ],
			'headers' => [
				'Cc'         => 'User 3 <user3@example.com>,user4@example.com',
				'Bcc'        => 'user5@example.com,User 6 <user6@example.com>',
				'Reply-To'   => 'user7@example.com,user8@example.com',
				'From'       => 'User 9 <user9@example.com>',
				'X-Reply-To' => 'user10@preview.senseilms',
			],
			'message' => 'Hello world',
			'subject' => 'Test email',
		];

		// Act
		$result = $this->preview_user->skip_wp_mail( null, $atts );

		// Assert
		$this->assertNull( $result );
	}

	public function testSkipWpMail_SingleToWithPreviewEmail_ReturnsFalse() {
		// Arrange
		$atts = [
			'to'      => 'John Doe <user1@preview.senseilms>',
			'message' => 'Hello world',
			'subject' => 'Test email',
		];

		// Act
		$result = $this->preview_user->skip_wp_mail( null, $atts );

		// Assert
		$this->assertFalse( $result );
	}

	public function testSkipWpMail_SingleToWithValidEmail_ReturnsNull() {
		// Arrange
		$atts = [
			'to'      => 'John Doe <user1@example.com>',
			'message' => 'Hello world',
			'subject' => 'Test email',
		];

		// Act
		$result = $this->preview_user->skip_wp_mail( null, $atts );

		// Assert
		$this->assertNull( $result );
	}


}
