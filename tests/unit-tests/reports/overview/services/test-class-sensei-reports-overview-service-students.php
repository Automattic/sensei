<?php

use Sensei\Internal\Services\Progress_Query_Service_Factory;

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

	/**
	 * Build the service with real progress services from the factory.
	 *
	 * @return Sensei_Reports_Overview_Service_Students
	 */
	private function create_service(): Sensei_Reports_Overview_Service_Students {
		$query_service_factory = new Progress_Query_Service_Factory();

		return new Sensei_Reports_Overview_Service_Students(
			$query_service_factory->create_aggregation_service(),
			$query_service_factory->create_grading_stats_service()
		);
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

		$service = $this->create_service();

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
		$service = $this->create_service();

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
		$service = $this->create_service();

		/* Act. */
		$actual = $service->get_course_counts_by_user( array() );

		/* Assert. */
		self::assertSame( array(), $actual );
	}

	public function testGetTotalCourseCounts_UsersWithActiveAndCompletedCourses_ReturnsSummedCounts() {
		/* Arrange. */
		$user_one            = $this->factory->user->create();
		$user_two            = $this->factory->user->create();
		$active_course_id    = $this->factory->course->create();
		$completed_course_id = $this->factory->course->create();

		Sensei()->course_progress_repository->save( Sensei()->course_progress_repository->create( $active_course_id, $user_one ) );

		foreach ( array( $user_one, $user_two ) as $user_id ) {
			$completed_course_progress = Sensei()->course_progress_repository->create( $completed_course_id, $user_id );
			$completed_course_progress->complete();
			Sensei()->course_progress_repository->save( $completed_course_progress );
		}

		$service = $this->create_service();

		/* Act. */
		$actual = $service->get_total_course_counts( array( $user_one, $user_two ) );

		/* Assert. */
		$expected = array(
			'active'    => 1,
			'completed' => 2,
		);
		self::assertSame( $expected, $actual );
	}

	public function testGetTotalCourseCounts_UsersWithoutCourses_ReturnsZeroCounts() {
		/* Arrange. */
		$user_id = $this->factory->user->create();
		$service = $this->create_service();

		/* Act. */
		$actual = $service->get_total_course_counts( array( $user_id ) );

		/* Assert. */
		$expected = array(
			'active'    => 0,
			'completed' => 0,
		);
		self::assertSame( $expected, $actual );
	}

	public function testGetTotalCourseCounts_EmptyUserIds_ReturnsZeroCounts() {
		/* Arrange. */
		$service = $this->create_service();

		/* Act. */
		$actual = $service->get_total_course_counts( array() );

		/* Assert. */
		$expected = array(
			'active'    => 0,
			'completed' => 0,
		);
		self::assertSame( $expected, $actual );
	}
}
