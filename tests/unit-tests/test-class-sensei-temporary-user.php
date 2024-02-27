<?php
/**
 * File with class for testing Sensei Temporary User.
 *
 * @package sensei-tests
 */

/**
 * Class for testing Sensei_Temporary_User class.
 *
 * @group Temporary User
 */
class Sensei_Temporary_User_Test extends WP_UnitTestCase {
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

		$this->factory = new Sensei_Factory();
		Sensei_Temporary_User::init();
	}

	private function create_learners() {

		$user1_id        = $this->factory->user->create( [ 'user_email' => 'user1@example.com' ] );
		$user2_id        = $this->factory->user->create( [ 'user_email' => 'user2@example.com' ] );
		$previewuser1_id = $this->factory->user->create(
			[
				'user_login'   => 'sensei_preview_1_1',
				'user_email'   => 'sensei_preview_1_1@preview.senseilms',
				'role'         => 'preview_student',
				'display_name' => 'User Preview 1',
			]
		);

		$guestuser1_id = $this->factory->user->create(
			[
				'user_login'   => 'sensei_guest_01',
				'user_email'   => 'sensei_guest_01@guest.senseilms',
				'role'         => 'guest_student',
				'display_name' => 'User Guest 1',
			]
		);

		$course1_id = $this->factory->course->create( [ 'id' => 10 ] );
		$course2_id = $this->factory->course->create();

		Sensei_Utils::update_course_status( $user1_id, $course1_id );
		Sensei_Utils::update_course_status( $user1_id, $course2_id, 'complete' );
		Sensei_Utils::update_course_status( $user2_id, $course1_id, 'complete' );
		Sensei_Utils::update_course_status( $previewuser1_id, $course1_id );
		Sensei_Utils::update_course_status( $guestuser1_id, $course2_id );

		return [ $course1_id ];
	}

	/**
	 * Test that learner queries in Sensei_Db_Query_Learners::get_all don't return temporary users.
	 *
	 * @covers \Sensei_Db_Query_Learners::get_all
	 *
	 * @dataProvider data_LearnersQuery_args
	 *
	 * @param array $args
	 * @param array $expected
	 */
	public function test_LearnersQuery_GetAll_HasNoTemporaryUsers( $args, $expected ) {
		list( $course1_id ) = $this->create_learners();

		if ( isset( $args['filter_by_course_id'] ) ) {
			$args['filter_by_course_id'] = $course1_id;
		}

		$query = new Sensei_Db_Query_Learners( $args );

		$learners = $query->get_all();

		self::assertSame( $expected, wp_list_pluck( $learners, 'user_email' ) );
	}

	/**
	 * Dataset for test_LearnersQuery_GetAll_HasNoTemporaryUsers.
	 */
	public function data_LearnersQuery_args() {
		return [
			'all learners'     => [ [], [ 'admin@example.org', 'user1@example.com', 'user2@example.com' ] ],
			'search'           => [ [ 'search' => 'user' ], [ 'user1@example.com', 'user2@example.com' ] ],
			'filter by course' => [ [ 'filter_by_course_id' => '%d' ], [ 'user1@example.com', 'user2@example.com' ] ],
		];
	}

	/**
	 * Test guest and preview user role is not listed.
	 *
	 * @testWith [ "guest_student" ]
	 *           [ "preview_student" ]
	 *
	 * @return void
	 */
	public function testRolesList_WhenFetched_DoesNotContainGuestStudentRole( $role ) {
		/* Arrange */

		add_role( $role, $role );
		$this->factory->user->create( [ 'role' => $role ] );

		/* Act */
		$all_roles_except_guest = get_editable_roles();

		/* Assert */
		$this->assertArrayNotHasKey( $role, $all_roles_except_guest );
	}

	/**
	 * Test guest and preview user role is not listed.
	 *
	 * @testWith [ "sensei_guest_", "guest_student" ]
	 *           [ "sensei_preview_", "preview_student" ]
	 *
	 * @return void
	 */
	public function testUserList_WhenFetched_DoesNotContainGuestUsers( $prefix, $role ) {
		/* Arrange */
		$user_args = [
			'fields' => 'ID',
		];
		$result1   = ( new WP_User_Query( $user_args ) )->get_results();

		$this->factory->user->create_many(
			2,
			[
				'user_login' => $prefix . 'user',
				'role'       => $role,
			]
		);
		Sensei_Temporary_User::init();

		/* Act */
		$result2 = ( new WP_User_Query( $user_args ) )->get_results();

		/* Assert */
		$this->assertEquals( count( $result1 ), count( $result2 ) );
	}


	/**
	 * Test that WordPress user query returns no temporary users.
	 */
	public function test_WPUserQuery_HasNoTemporaryUsers() {
		$this->create_learners();

		$learners = get_users( array( 'fields' => 'user_email' ) );

		self::assertSame( array( 'admin@example.org', 'user1@example.com', 'user2@example.com' ), $learners );
	}
}
