<?php

/**
 * Sensei Reports Overview Data Provider Students Test Class
 *
 * @covers Sensei_Reports_Overview_Data_Provider_Students
 */
class Sensei_Reports_Overview_Data_Provider_Students_Test extends WP_UnitTestCase {
	/**
	 * Factory for setting up testing data.
	 *
	 * @var Sensei_Factory
	 */
	private $factory;

	/**
	 * Set up before each test.
	 */
	public function setup() {
		parent::setUp();

		$this->factory = new Sensei_Factory();

		add_filter( 'sensei_analysis_overview_filter_users', [ $this, 'excludeAdminFromUserQuery' ] );
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown() {
		parent::tearDown();

		$this->factory->tearDown();
	}

	public function testGetItems_FiltersWithoutLastActivityGiven_ReturnsAllStudents() {
		/* Arrange. */
		$no_last_activity_user   = $this->factory->user->create();
		$user_with_last_activity = $this->createUserWithActivity( '2022-03-01 00:00:00' );

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items( [ 'number' => -1 ] );

		/* Assert. */
		$this->assertCount( 2, $students );
	}

	public function testGetItems_FiltersWithoutLastActivityGiven_ReturnsStudentsWithCorrectFields() {
		/* Arrange. */
		$user_id = $this->createUserWithActivity(
			'2022-03-01 00:00:00',
			[
				'user_login'   => 'test',
				'user_email'   => 'test@example.org',
				'display_name' => 'test',
			]
		);

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items( [ 'number' => -1 ] );

		/* Assert. */
		$expected = [
			(object) [
				'ID'                 => $user_id,
				'user_login'         => 'test',
				'user_email'         => 'test@example.org',
				'display_name'       => 'test',
				'last_activity_date' => '2022-03-01 00:00:00',
			],
		];

		$this->assertEquals( $expected, $students );
	}

	public function testGetItems_FiltersWithSearchGiven_ReturnsTheSearchedStudents() {
		/* Arrange. */
		$searched_user = $this->factory->user->create( [ 'user_login' => 'awesome_nickname' ] );
		$other_user    = $this->factory->user->create( [ 'user_login' => 'cool_nickname' ] );

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items( [ 'search' => 'awesome_nickname' ] );

		/* Assert. */
		$expected = [
			[
				'id'                 => $searched_user,
				'last_activity_date' => null,
			],
		];

		$this->assertEquals( $expected, $this->exportStudents( $students ) );
	}

	public function testGetItems_FiltersWithLastActivityDateGiven_ReturnsStudentsWithActivityOnly() {
		/* Arrange. */
		$user_with_activity    = $this->createUserWithActivity( '2022-03-01 00:00:00' );
		$user_with_no_activity = $this->factory->user->create();

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items(
			[
				'last_activity_date_from' => '2022-03-01',
			]
		);

		/* Assert. */
		$expected = [
			[
				'id'                 => $user_with_activity,
				'last_activity_date' => '2022-03-01 00:00:00',
			],
		];

		$this->assertEquals( $expected, $this->exportStudents( $students ) );
	}

	public function testGetItems_FiltersWithTheSameFromAndToLastActivityDateGiven_ReturnsActiveStudentsInThatDay() {
		/* Arrange. */
		$user_1 = $this->createUserWithActivity( '2022-03-01 00:00:00' );
		$user_2 = $this->createUserWithActivity( '2022-03-02 00:00:00' );

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items(
			[
				'last_activity_date_from' => '2022-03-01',
				'last_activity_date_to'   => '2022-03-01',
			]
		);

		/* Assert. */
		$expected = [
			[
				'id'                 => $user_1,
				'last_activity_date' => '2022-03-01 00:00:00',
			],
		];

		$this->assertEquals( $expected, $this->exportStudents( $students ) );
	}

	public function testGetItems_FiltersWithOnlyLastActivityDateFromGiven_ReturnsActiveStudentsFromThatDate() {
		/* Arrange. */
		$user_1 = $this->createUserWithActivity( '2022-03-01 00:00:00' );
		$user_2 = $this->createUserWithActivity( '2022-03-02 00:00:00' );

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items(
			[
				'last_activity_date_from' => '2022-03-02',
			]
		);

		/* Assert. */
		$expected = [
			[
				'id'                 => $user_2,
				'last_activity_date' => '2022-03-02 00:00:00',
			],
		];

		$this->assertEquals( $expected, $this->exportStudents( $students ) );
	}

	public function testGetItems_FiltersWithOnlyLastActivityDateToGiven_ReturnsActiveStudentsToThatDate() {
		/* Arrange. */
		$user_1 = $this->createUserWithActivity( '2022-03-01 00:00:00' );
		$user_2 = $this->createUserWithActivity( '2022-03-02 00:00:00' );

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items(
			[
				'last_activity_date_to' => '2022-03-01',
			]
		);

		/* Assert. */
		$expected = [
			[
				'id'                 => $user_1,
				'last_activity_date' => '2022-03-01 00:00:00',
			],
		];

		$this->assertEquals( $expected, $this->exportStudents( $students ) );
	}

	public function testGetItems_FiltersWithLastActivityDateFromBiggerThanLastActivityDateToGiven_ReturnsNoStudents() {
		/* Arrange. */
		$user_1 = $this->createUserWithActivity( '2022-03-01 00:00:00' );
		$user_2 = $this->createUserWithActivity( '2022-03-02 00:00:00' );

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items(
			[
				'last_activity_date_from' => '2022-03-02',
				'last_activity_date_to'   => '2022-03-01',
			]
		);

		/* Assert. */
		$this->assertEmpty( $students );
	}

	public function testGetLastTotalItems_WithNoItems_ReturnsZero() {
		/* Arrange. */
		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */

		/* Assert. */
		$this->assertSame( 0, $data_provider->get_last_total_items() );
	}

	public function testGetLastTotalItems_WithItems_ReturnsTheTotalNumberOfItems() {
		/* Arrange. */
		$this->factory->user->create_many( 5 );

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items( [ 'number' => -1 ] );

		/* Assert. */
		$this->assertSame( 5, $data_provider->get_last_total_items() );
	}

	/**
	 * Export the students to an array that is easier to assert against.
	 *
	 * @param array $students The students array containing the user objects.
	 *
	 * @return array
	 */
	private function exportStudents( array $students ): array {
		$result = [];

		foreach ( $students as $student ) {
			$result[] = [
				'id'                 => $student->ID,
				'last_activity_date' => $student->last_activity_date,
			];
		}

		return $result;
	}

	/**
	 * Create a user that has an activity.
	 *
	 * @param string $activity_date The activity date.
	 * @param array  $user_args The user args.
	 *
	 * @return int The user ID.
	 */
	private function createUserWithActivity( string $activity_date, array $user_args = [] ): int {
		$user_id = $this->factory->user->create( $user_args );

		$lesson_id = $this->factory->lesson->create(
			[
				'meta_input' => [ '_lesson_course' => $this->factory->course->create() ],
			]
		);

		$activity_comment_id = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id, true );

		wp_update_comment(
			[
				'comment_ID'   => $activity_comment_id,
				'comment_date' => $activity_date,
			]
		);

		return $user_id;
	}

	/**
	 * Exclude the default admin user from the WP_User_Query args.
	 *
	 * @param array $query_args The WP_User_Query args.
	 *
	 * @return array
	 */
	public function excludeAdminFromUserQuery( array $query_args ): array {
		$query_args['exclude'] = [ 1 ];

		return $query_args;
	}
}
