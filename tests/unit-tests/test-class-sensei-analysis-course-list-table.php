<?php

/**
 * Sensei Analysis Course List Table Unit Test.
 *
 * @covers Sensei_Analysis_Course_List_Table
 */
class Sensei_Analysis_Course_List_Table_Test extends WP_UnitTestCase {
	private static $initial_hook_suffix;

	/**
	 * Factory object.
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
		$GLOBALS['hook_suffix'] = self::$initial_hook_suffix;
	}

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	public function testPrepareItems_DateStartedFilterSet_SetsMatchingItems() {
		/* Arrange. */
		$course_id = $this->factory->course->create();

		$user1_id = $this->factory->user->create();
		$user2_id = $this->factory->user->create();
		$user3_id = $this->factory->user->create();
		$user4_id = $this->factory->user->create();

		$activity1_id = Sensei_Utils::start_user_on_course( $user1_id, $course_id );
		$activity2_id = Sensei_Utils::start_user_on_course( $user2_id, $course_id );
		$activity3_id = Sensei_Utils::start_user_on_course( $user3_id, $course_id );
		$activity4_id = Sensei_Utils::start_user_on_course( $user4_id, $course_id );

		update_comment_meta( $activity1_id, 'start', '2018-01-01' );
		update_comment_meta( $activity2_id, 'start', '2018-01-02' );
		update_comment_meta( $activity3_id, 'start', '2018-01-03' );
		update_comment_meta( $activity4_id, 'start', '2018-01-04' );

		$_GET['start_date'] = '2018-01-02';
		$_GET['end_date']   = '2018-01-03';
		$_GET['view']       = 'user';

		/* Act. */
		$table = new Sensei_Analysis_Course_List_Table( $course_id );
		$table->prepare_items();

		/* Assert. */
		$expected = [
			$user2_id,
			$user3_id,
		];
		sort( $expected );
		self::assertSame( $expected, $this->export_items( $table->items ) );
	}

	public function testPrepareItems_DefaultDateFilterSet_SetsMatchingItems() {
		/* Arrange. */
		$course_id = $this->factory->course->create();

		$user1_id = $this->factory->user->create();
		$user2_id = $this->factory->user->create();
		$user3_id = $this->factory->user->create();
		$user4_id = $this->factory->user->create();

		$activity1_id = Sensei_Utils::start_user_on_course( $user1_id, $course_id );
		$activity2_id = Sensei_Utils::start_user_on_course( $user2_id, $course_id );
		$activity3_id = Sensei_Utils::start_user_on_course( $user3_id, $course_id );
		$activity4_id = Sensei_Utils::start_user_on_course( $user4_id, $course_id );

		$more_than_30days_ago = new DateTime( '-31 day' );
		$exactly_30days_ago   = new DateTime( '-30 day' );
		$week_ago             = new DateTime( '-7 day' );
		$today                = new DateTime( 'now' );

		update_comment_meta( $activity1_id, 'start', $more_than_30days_ago->format( 'Y-m-d' ) );
		update_comment_meta( $activity2_id, 'start', $exactly_30days_ago->format( 'Y-m-d' ) );
		update_comment_meta( $activity3_id, 'start', $week_ago->format( 'Y-m-d' ) );
		update_comment_meta( $activity4_id, 'start', $today->format( 'Y-m-d' ) );

		$_GET['view'] = 'user';

		/* Act. */
		$table = new Sensei_Analysis_Course_List_Table( $course_id );
		$table->prepare_items();

		/* Assert. */
		$expected = [
			$user1_id,
			$user2_id,
			$user3_id,
			$user4_id,
		];
		sort( $expected );
		self::assertSame( $expected, $this->export_items( $table->items ) );
	}

	public function testTableFooter_WhenCalledWithNoData_NotDisplayTheExportButton() {
		/* Arrange. */
		$list_table = new Sensei_Analysis_Course_List_Table();

		/* Act. */
		ob_start();
		$list_table->data_table_footer();
		$actual = ob_get_clean();

		/* Assert. */
		$expected = '';
		self::assertSame( $expected, $actual, 'The export button should not be displayed' );
	}

	public function testTableFooter_WhenCalled_DisplayTheExportButtonWithCorrectArgs() {
		/* Arrange. */
		$course = $this->factory->course->create_and_get();
		$user   = $this->factory->user->create_and_get();
		$nonce  = wp_create_nonce( 'sensei_csv_download' );

		$_GET = [
			's'          => 'course',
			'start_date' => '2022-03-01',
			'end_date'   => '2022-03-02',
			'_wpnonce'   => $nonce,
		];

		$list_table = new Sensei_Analysis_Course_List_Table( $course->ID, $user->ID );

		$list_table->total_items = 1;

		/* Act. */
		ob_start();
		$list_table->data_table_footer();
		$actual = ob_get_clean();

		/* Assert. */
		$expected = sprintf(
			'<a class="button button-primary" href="http://example.org/wp-admin/admin.php?page=sensei_reports&#038;course_id=%d&#038;view=lesson&#038;sensei_report_download=%s-%s-lessons-overview&#038;start_date=2022-03-01&#038;end_date=2022-03-02&#038;s=course&#038;user_id=%d&#038;_sdl_nonce=%s">Export all rows (CSV)</a>',
			$course->ID,
			$user->user_nicename,
			$course->post_name,
			$user->ID,
			$nonce
		);

		self::assertSame( $expected, $actual, 'The export button should be displayed with the correct args.' );
	}

	private function export_items( array $items ) {
		$ret = [];
		foreach ( $items as $item ) {
			$ret[] = (int) $item->user_id;
		}
		sort( $ret );
		return $ret;
	}
}
