<?php
require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-course-enrolment-test-helpers.php';

/**
 * Sensei Learners Admin Bulk Actions View Class Unit Tests
 *
 * @covers Sensei_Learners_Admin_Bulk_Actions_View
 */
class Sensei_Learners_Admin_Bulk_Actions_View_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Test_Helpers;

	/**
	 * Hook suffix.
	 *
	 * @var string|null
	 */
	private static $initial_hook_suffix;

	/**
	 * Factory for setting up testing data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$initial_hook_suffix = $GLOBALS['hook_suffix'] ?? null;
		$GLOBALS['hook_suffix']    = null;
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		self::resetEnrolmentProviders();
		$GLOBALS['hook_suffix'] = self::$initial_hook_suffix;
	}

	public function setUp(): void {
		parent::setUp();
		$this->factory = new Sensei_Factory();
		self::resetEnrolmentProviders();
	}

	/**
	 * Test that prepared items have the last activity date field with matching data.
	 */
	public function testPrepareItems_WhenCalled_ReturnsStudentsWithLastActivityDate() {
		$GLOBALS['current_screen'] = null;
		// Arrange
		$bulk_action_view_instance = new Sensei_Learners_Admin_Bulk_Actions_View(
			Sensei()->learners->bulk_actions_controller,
			Sensei()->learners,
			Sensei_Learner::instance()
		);

		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		$comment_date               = gmdate( 'Y-m-d H:i:s', strtotime( '48 hours' ) );
		$lesson_activity_comment_id = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id, true );
		wp_update_comment(
			[
				'comment_ID'   => $lesson_activity_comment_id,
				'comment_date' => $comment_date,
			]
		);

		// Act
		$bulk_action_view_instance->prepare_items();

		// Assert
		$expected = get_gmt_from_date( $comment_date );
		$actual   = $bulk_action_view_instance->items[1]->last_activity_date;
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test single row output.
	 *
	 * @param array $enrolled_courses
	 * @param string $expected
	 *
	 * @dataProvider providerSingleRow_ItemGiven_ReturnsMatchingRow
	 */
	public function testSingleRow_ItemGiven_ReturnsMatchingRow( array $enrolled_courses, string $expected ) {
		/* Arrange. */
		$controller         = $this->createMock( Sensei_Learners_Admin_Bulk_Actions_Controller::class );
		$learner_management = $this->createMock( Sensei_Learner_Management::class );

		$enrolled_courses_query        = $this->createMock( WP_Query::class );
		$enrolled_courses_query->posts = $enrolled_courses;

		$learner = $this->createMock( Sensei_Learner::class );
		$learner
			->method( 'get_enrolled_courses_query' )
			->willReturn( $enrolled_courses_query );

		$item                     = new stdClass();
		$item->user_id            = 1;
		$item->last_activity_date = '2020-01-01';
		$item->user_email         = 'a';
		$item->user_login         = 'b';

		$view = new Sensei_Learners_Admin_Bulk_Actions_View( $controller, $learner_management, $learner );

		/* Act. */
		ob_start();
		$view->single_row( $item );
		$actual = ob_get_clean();

		/* Assert. */
		self::assertSame( $expected, $actual );
	}

	public function providerSingleRow_ItemGiven_ReturnsMatchingRow() {
		return [
			'no enrolled courses'          => [
				[
					(object) [
						'ID'         => 2,
						'post_title' => 'Course 1',
					],
					(object) [
						'ID'         => 3,
						'post_title' => 'Course 2',
					],
				],
				'<tr class="alternate"><th class=\'cb column-cb check-column\'  ><label class="screen-reader-text">Select All</label><input type="checkbox" name="user_id" value="1" class="sensei_user_select_id"></th><td class=\'learner column-learner column-primary\' data-colname="Students (0)" ><strong><a class="row-title" href="http://example.org/wp-admin/user-edit.php?user_id=1" title="Edit &#8220;admin&#8221;">admin</a></strong><button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td><td class=\'email column-email\' data-colname="Email" >a</td><td class=\'progress column-progress\' data-colname="Enrolled Courses" ><a href="" class="sensei-students__enrolled-course" data-course-id="2">Course 1</a><a href="" class="sensei-students__enrolled-course" data-course-id="3">Course 2</a><div class="sensei-students__enrolled-courses-detail"></div></td><td class=\'last_activity_date column-last_activity_date\' data-colname="Last Activity" >January 1, 2020</td><td class=\'actions column-actions\' data-colname="" ><div class="student-action-menu" data-user-id="1" data-user-name="b" data-user-display-name="admin"></div></td></tr>',
			],
			'no hidden enrolled courses'   => [
				[],
				'<tr class=""><th class=\'cb column-cb check-column\'  ><label class="screen-reader-text">Select All</label><input type="checkbox" name="user_id" value="1" class="sensei_user_select_id"></th><td class=\'learner column-learner column-primary\' data-colname="Students (0)" ><strong><a class="row-title" href="http://example.org/wp-admin/user-edit.php?user_id=1" title="Edit &#8220;admin&#8221;">admin</a></strong><button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td><td class=\'email column-email\' data-colname="Email" >a</td><td class=\'progress column-progress\' data-colname="Enrolled Courses" >N/A</td><td class=\'last_activity_date column-last_activity_date\' data-colname="Last Activity" >January 1, 2020</td><td class=\'actions column-actions\' data-colname="" ><div class="student-action-menu" data-user-id="1" data-user-name="b" data-user-display-name="admin"></div></td></tr>',
			],
			'with hidden enrolled courses' => [
				[
					(object) [
						'ID'         => 2,
						'post_title' => 'Course 1',
					],
					(object) [
						'ID'         => 3,
						'post_title' => 'Course 2',
					],
					(object) [
						'ID'         => 4,
						'post_title' => 'Course 3',
					],
					(object) [
						'ID'         => 5,
						'post_title' => 'Course 4',
					],
				],
				'<tr class="alternate"><th class=\'cb column-cb check-column\'  ><label class="screen-reader-text">Select All</label><input type="checkbox" name="user_id" value="1" class="sensei_user_select_id"></th><td class=\'learner column-learner column-primary\' data-colname="Students (0)" ><strong><a class="row-title" href="http://example.org/wp-admin/user-edit.php?user_id=1" title="Edit &#8220;admin&#8221;">admin</a></strong><button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td><td class=\'email column-email\' data-colname="Email" >a</td><td class=\'progress column-progress\' data-colname="Enrolled Courses" ><a href="" class="sensei-students__enrolled-course" data-course-id="2">Course 1</a><a href="" class="sensei-students__enrolled-course" data-course-id="3">Course 2</a><a href="" class="sensei-students__enrolled-course" data-course-id="4">Course 3</a><div class="sensei-students__enrolled-courses-detail"></div></td><td class=\'last_activity_date column-last_activity_date\' data-colname="Last Activity" >January 1, 2020</td><td class=\'actions column-actions\' data-colname="" ><div class="student-action-menu" data-user-id="1" data-user-name="b" data-user-display-name="admin"></div></td></tr>',
			],
		];
	}

	public function testGetSortableColumns_WhenCalled_ReturnsMatchingColumns() {
		/* Arrange. */
		$controller         = $this->createMock( Sensei_Learners_Admin_Bulk_Actions_Controller::class );
		$learner_management = $this->createMock( Sensei_Learner_Management::class );
		$learner            = $this->createMock( Sensei_Learner::class );
		$view               = new Sensei_Learners_Admin_Bulk_Actions_View( $controller, $learner_management, $learner );

		/* Act. */
		$actual = $view->get_sortable_columns();

		/* Assert. */
		$expected = [
			'learner' => [ 'learner', false ],
		];

		$this->assertEquals( $expected, $actual );
	}
}
