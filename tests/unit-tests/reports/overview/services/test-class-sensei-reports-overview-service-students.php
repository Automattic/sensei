<?php

/**
 * Tests for Sensei_Reports_Overview_Service_Students class.
 *
 * @covers Sensei_Reports_Overview_Service_Students
 */
class Sensei_Reports_Overview_Service_Students_Test extends WP_UnitTestCase {
	use Sensei_HPPS_Helpers;

	/**
	 * Factory for setting up testing data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
		$this->maybe_enable_hpps_tables_repository();
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		$this->maybe_reset_hpps_repository();

		parent::tearDown();

		$this->factory->tearDown();
	}

	public function testGetCourseCountsByUser_UserWithActiveAndCompletedCourses_ReturnsActiveAndCompletedCounts() {
		/* Arrange. */
		$user_id             = $this->factory->user->create();
		$active_course_id    = $this->factory->course->create();
		$completed_course_id = $this->factory->course->create();

		Sensei()->course_progress_repository->save( Sensei()->course_progress_repository->create( $active_course_id, $user_id ) );

		$completed_course_progress = Sensei()->course_progress_repository->create( $completed_course_id, $user_id );
		$completed_course_progress->complete();
		Sensei()->course_progress_repository->save( $completed_course_progress );

		$service = new Sensei_Reports_Overview_Service_Students();

		/* Act. */
		$actual = $service->get_course_counts_by_user( array( $user_id ) );

		/* Assert. */
		$expected = array(
			$user_id => array(
				'active'    => 1,
				'completed' => 1,
			),
		);
		self::assertSame( $expected, $actual );
	}

	public function testGetCourseCountsByUser_UserWithoutCourses_ReturnsZeroCounts() {
		/* Arrange. */
		$user_id = $this->factory->user->create();
		$service = new Sensei_Reports_Overview_Service_Students();

		/* Act. */
		$actual = $service->get_course_counts_by_user( array( $user_id ) );

		/* Assert. */
		$expected = array(
			$user_id => array(
				'active'    => 0,
				'completed' => 0,
			),
		);
		self::assertSame( $expected, $actual );
	}

	public function testGetCourseCountsByUser_EmptyUserIds_ReturnsEmptyArray() {
		/* Arrange. */
		$service = new Sensei_Reports_Overview_Service_Students();

		/* Act. */
		$actual = $service->get_course_counts_by_user( array() );

		/* Assert. */
		self::assertSame( array(), $actual );
	}
}
