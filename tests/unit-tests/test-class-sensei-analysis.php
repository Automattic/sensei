<?php

use PHPUnit\Framework\MockObject\MockObject;

/**
 * Sensei Analysis Unit Tests
 *
 * @covers Sensei_Analysis
 */
class Sensei_Analysis_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;

	private static $initial_hook_suffix;

	/**
	 * Setup method.
	 */
	public function setUp(): void {
		parent::setUp();

		Sensei_Test_Events::reset();

		// Disable `wp_die`.
		add_filter( 'wp_die_handler', [ $this, 'disable_wp_die' ] );
	}

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$initial_hook_suffix = $GLOBALS['hook_suffix'] ?? null;
		$GLOBALS['hook_suffix']    = null;
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		$GLOBALS['hook_suffix'] = self::$initial_hook_suffix;
	}

	/**
	 * Test load_data_object returns an expected list table instance
	 *
	 * @param string $name
	 * @param string $data
	 * @param string $expected_class
	 *
	 * @dataProvider providerLoadDataObject_ParamsGiven_ReturnsExpectedInstance
	 */
	public function testLoadDataObject_ParamsGiven_ReturnsExpectedInstance( $name, $data, $expected_class ) {
		$analysis    = new Sensei_Analysis( 'a' );
		$data_object = $analysis->load_data_object( $name, $data );
		$this->assertInstanceOf( $expected_class, $data_object );
	}

	public function providerLoadDataObject_ParamsGiven_ReturnsExpectedInstance(): array {
		return [
			'overview courses' => [
				'Overview',
				'courses',
				'Sensei_Reports_Overview_List_Table_Courses',
				'Sensei_Reports_Overview_Service_Courses',
			],
			'user profile'     => [
				'User_Profile',
				1,
				'Sensei_Analysis_User_Profile_List_Table',
			],
			'course'           => [
				'Course',
				2,
				'Sensei_Analysis_Course_List_Table',
			],
			'lesson'           => [
				'Lesson',
				3,
				'Sensei_Analysis_Lesson_List_Table',
			],
		];
	}

	public function testAnalysisUserCourseNav_WhenCalled_GeneratesProperHtml() {
		/* Arrange */
		$this->login_as_admin();
		$analysis        = new Sensei_Analysis( 'a' );
		$_GET['user_id'] = 1;

		/* Act */
		ob_start();
		$analysis->analysis_user_course_nav();
		$actual = trim( ob_get_clean() );

		/* Assert */
		$expected = '<h1>
			<a href="http://example.org/wp-admin/admin.php?page=sensei_reports">Reports</a>&nbsp;&nbsp;<span class="user-title">&gt;&nbsp;&nbsp;<a href="http://example.org/wp-admin/admin.php?page=sensei_reports&#038;user_id=1">admin</a></span>			</h1>';
		$this->assertEquals( $expected, $actual );
	}

	public function testAnalysisPage_WhenNoView_LogsUsersEvent() {
		/* Arrange */
		$analysis_mock = $this->createMockWithExcludedMethod( Sensei_Analysis::class, 'analysis_page' );

		/* Act */
		$analysis_mock->analysis_page();
		$events = Sensei_Test_Events::get_logged_events( 'sensei_analysis_view' );

		/* Assert */
		$this->assertSame( 'users', $events[0]['url_args']['view'] );
	}

	public function testAnalysisPage_WhenUsersView_LogsUsersEvent() {
		/* Arrange */
		$analysis_mock = $this->createMockWithExcludedMethod( Sensei_Analysis::class, 'analysis_page' );

		$_GET = [
			'view' => 'students',
		];

		/* Act */
		$analysis_mock->analysis_page();
		$events = Sensei_Test_Events::get_logged_events( 'sensei_analysis_view' );

		/* Assert */
		$this->assertSame( 'users', $events[0]['url_args']['view'] );
	}

	public function testAnalysisPage_WhenCoursesView_LogsCoursesEvent() {
		/* Arrange */
		$analysis_mock = $this->createMockWithExcludedMethod( Sensei_Analysis::class, 'analysis_page' );

		$_GET = [
			'view' => 'courses',
		];

		/* Act */
		$analysis_mock->analysis_page();
		$events = Sensei_Test_Events::get_logged_events( 'sensei_analysis_view' );

		/* Assert */
		$this->assertSame( 'courses', $events[0]['url_args']['view'] );
	}

	public function testAnalysisPage_WhenLessonsViewWithNoCourseSelected_DoesntLogEvent() {
		/* Arrange */
		$analysis_mock = $this->createMockWithExcludedMethod( Sensei_Analysis::class, 'analysis_page' );

		$_GET = [
			'view' => 'lessons',
		];

		/* Act */
		$analysis_mock->analysis_page();
		$events = Sensei_Test_Events::get_logged_events( 'sensei_analysis_view' );

		/* Assert */
		$this->assertEmpty( $events );
	}

	public function testAnalysisPage_WhenLessonsViewWithCourseSelected_LogsLessonsEvent() {
		/* Arrange */
		$analysis_mock = $this->createMockWithExcludedMethod( Sensei_Analysis::class, 'analysis_page' );

		$_GET = [
			'view'          => 'lessons',
			'course_filter' => 1,
		];

		/* Act */
		$analysis_mock->analysis_page();
		$events = Sensei_Test_Events::get_logged_events( 'sensei_analysis_view' );

		/* Assert */
		$this->assertSame( 'lessons', $events[0]['url_args']['view'] );
	}

	public function testAnalysisPage_WhenCourseLessonUsersView_LogsCourseLessonUsersEvent() {
		/* Arrange */
		$analysis_mock = $this->createMockWithExcludedMethod( Sensei_Analysis::class, 'analysis_page' );

		$_GET = [
			'lesson_id' => 1,
		];

		/* Act */
		$analysis_mock->analysis_page();
		$events = Sensei_Test_Events::get_logged_events( 'sensei_analysis_view' );

		/* Assert */
		$this->assertSame( 'course-lesson-users', $events[0]['url_args']['view'] );
	}

	public function testAnalysisPage_WhenCourseUsersView_LogsCourseUsersEvent() {
		/* Arrange */
		$analysis_mock = $this->createMockWithExcludedMethod( Sensei_Analysis::class, 'analysis_page' );

		$_GET = [
			'view'      => 'user',
			'course_id' => 1,
		];

		/* Act */
		$analysis_mock->analysis_page();
		$events = Sensei_Test_Events::get_logged_events( 'sensei_analysis_view' );

		/* Assert */
		$this->assertSame( 'course-users', $events[0]['url_args']['view'] );
	}

	public function testAnalysisPage_WhenUserCourseLessonsView_LogsUserCourseLessonsEvent() {
		/* Arrange */
		$analysis_mock = $this->createMockWithExcludedMethod( Sensei_Analysis::class, 'analysis_page' );

		$_GET = [
			'course_id' => 1,
			'user_id'   => 1,
		];

		/* Act */
		$analysis_mock->analysis_page();
		$events = Sensei_Test_Events::get_logged_events( 'sensei_analysis_view' );

		/* Assert */
		$this->assertSame( 'user-course-lessons', $events[0]['url_args']['view'] );
	}

	public function testAnalysisPage_WhenCourseLessonsView_LogsCourseLessonsEvent() {
		/* Arrange */
		$analysis_mock = $this->createMockWithExcludedMethod( Sensei_Analysis::class, 'analysis_page' );

		$_GET = [
			'course_id' => 1,
		];

		/* Act */
		$analysis_mock->analysis_page();
		$events = Sensei_Test_Events::get_logged_events( 'sensei_analysis_view' );

		/* Assert */
		$this->assertSame( 'course-lessons', $events[0]['url_args']['view'] );
	}

	public function testAnalysisPage_WhenUserCoursesView_LogsUserCoursesEvent() {
		/* Arrange */
		$analysis_mock = $this->createMockWithExcludedMethod( Sensei_Analysis::class, 'analysis_page' );

		$_GET = [
			'user_id' => 1,
		];

		/* Act */
		$analysis_mock->analysis_page();
		$events = Sensei_Test_Events::get_logged_events( 'sensei_analysis_view' );

		/* Assert */
		$this->assertSame( 'user-courses', $events[0]['url_args']['view'] );
	}

	public function testAnalysisPage_WhenNotAllowedToViewUser_ThrowsException() {
		/* Arrange */
		$this->login_as_teacher();
		$analysis_mock = $this->createMockWithExcludedMethod( Sensei_Analysis::class, 'analysis_page' );
		remove_filter( 'wp_die_handler', [ $this, 'disable_wp_die' ] );

		$_GET = [
			'user_id' => 1,
		];

		/* Assert */
		$this->expectException( 'WPDieException' );
		$this->expectExceptionMessage( 'Invalid user' );

		/* Act */
		$analysis_mock->analysis_page();
	}

	public function testAnalysisPage_WhenViewingUserAsAdmin_LoadsPage() {
		/* Arrange */
		$this->login_as_admin();
		$analysis_mock = $this->createMockWithExcludedMethod( Sensei_Analysis::class, 'analysis_page' );
		remove_filter( 'wp_die_handler', [ $this, 'disable_wp_die' ] );

		$_GET = [
			'user_id' => 1,
		];

		/* Act */
		$analysis_mock->analysis_page();

		/* Assert */
		$this->expectNotToPerformAssertions();
	}

	/**
	 * Returns a partial mock object for the specified class
	 * with all of its methods mocked but one.
	 *
	 * @param string $class_name The class to mock.
	 * @param string $method The method to skip.
	 *
	 * @return MockObject
	 */
	private function createMockWithExcludedMethod( string $class_name, string $method ): MockObject {
		$class_methods = get_class_methods( $class_name );

		return $this->createPartialMock(
			$class_name,
			array_diff( $class_methods, [ $method ] )
		);
	}

	/**
	 * Disable the `wp_die` handler.
	 *
	 * @return string
	 */
	public function disable_wp_die() {
		return '__return_false';
	}
}
