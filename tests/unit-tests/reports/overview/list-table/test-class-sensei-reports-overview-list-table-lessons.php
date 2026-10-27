<?php

/**
 * Sensei Reports Overview List Table Lessons Test Class
 *
 * @covers Sensei_Reports_Overview_List_Table_Lessons
 */
class Sensei_Reports_Overview_List_Table_Lessons_Test extends WP_UnitTestCase {
	use Sensei_HPPS_Helpers;

	/**
	 * Factory for setting up testing data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Request arguments to restore after each test.
	 *
	 * @var array
	 */
	private array $initial_get;

	/**
	 * Date options to restore after each test.
	 *
	 * @var array
	 */
	private array $date_options;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preserve the request used by report fixtures.
		$this->initial_get  = $_GET;
		$this->date_options = array(
			'timezone_string' => get_option( 'timezone_string' ),
			'gmt_offset'      => get_option( 'gmt_offset' ),
			'date_format'     => get_option( 'date_format' ),
		);
		$this->factory      = new Sensei_Factory();
		$this->maybe_enable_hpps_tables_repository();
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		$_GET = $this->initial_get;
		foreach ( $this->date_options as $option => $value ) {
			update_option( $option, $value );
		}
		$this->maybe_reset_hpps_repository();

		parent::tearDown();

		$this->factory->tearDown();
	}

	/**
	 * Displayed lesson rows and headers reflect the selected activity.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Lessons::prepare_items
	 * @covers Sensei_Reports_Overview_List_Table_Lessons::get_row_data
	 * @covers Sensei_Reports_Overview_List_Table_Lessons::get_columns
	 * @dataProvider lesson_report_scenarios
	 */
	public function testPrepareItems_LessonActivityGiven_DisplaysMatchingProgressMetrics( array $activity, int $offset, array $expected ): void {
		/* Arrange. */
		list( $table, $lesson ) = $this->create_lesson_report( $activity, $offset );
		$row_method             = new ReflectionMethod( $table, 'get_row_data' );
		Sensei_Unit_Tests_Bootstrap::make_reflection_accessible( $row_method );

		/* Act. */
		$table->prepare_items();
		$items   = $table->items;
		$columns = $table->get_columns();
		$row     = $row_method->invoke( $table, $items[0] );

		/* Assert. */
		self::assertSame( array( $lesson ), wp_list_pluck( $items, 'ID' ), 'The row must retain the requested lesson ID.' );
		self::assertSame( 'Students (' . $expected['students'] . ')', $columns['students'], 'The header must count the selected students.' );
		self::assertSame( 'Completed (' . $expected['completions'] . ')', $columns['completions'], 'The header must count eligible completions.' );
		self::assertSame( 'Completion Rate (' . ( 'N/A' === $expected['completion_rate'] ? '0%' : $expected['completion_rate'] ) . ')', $columns['completion_rate'], 'The header must use eligible starts and completions.' );
		self::assertSame( 'Days to Completion (' . $expected['days_to_completion'] . ')', $columns['days_to_completion'], 'The header must use the expected average duration.' );
		self::assertSame( $expected, array_intersect_key( $row, $expected ), 'The row must display the expected progress metrics.' );
	}

	/**
	 * Search requests select the same lessons for displayed rows, totals and CSV exports.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Lessons::prepare_items
	 * @covers Sensei_Reports_Overview_List_Table_Lessons::get_columns
	 * @covers Sensei_Reports_Overview_List_Table_Lessons::data_table_footer
	 * @covers Sensei_Reports_Overview_List_Table_Lessons::generate_report
	 * @dataProvider lesson_search_requests
	 */
	public function testPrepareItems_SearchRequestGiven_ReturnsMatchingRowsTotalsAndCsv( string $search, string $matching_title, string $other_title ): void {
		$course = $this->factory->course->create();
		$lesson = $this->factory->lesson->create(
			array(
				'post_title'   => wp_slash( $matching_title ),
				'post_content' => '',
				'post_excerpt' => '',
				'meta_input'   => array( '_lesson_course' => $course ),
			)
		);
		$other  = $this->factory->lesson->create(
			array(
				'post_title'   => wp_slash( $other_title ),
				'post_content' => '',
				'post_excerpt' => '',
				'meta_input'   => array( '_lesson_course' => $course ),
			)
		);
		$this->create_lesson_progress( $lesson, '2022-01-01 12:00:00', '2022-01-02 12:00:00' );
		$this->create_lesson_progress( $other, '2022-01-01 12:00:00', null );
		$_GET  = array(
			'course_filter' => $course,
			's'             => wp_slash( $search ),
		);
		$table = new Sensei_Reports_Overview_List_Table_Lessons( Sensei()->course, new Sensei_Reports_Overview_Data_Provider_Lessons( Sensei()->course ) );

		$table->prepare_items();
		$items   = $table->items;
		$columns = $table->get_columns();
		ob_start();
		$table->data_table_footer();
		$footer = ob_get_clean();
		preg_match( '/href="([^"]+)"/', $footer, $matches );
		parse_str( wp_parse_url( html_entity_decode( $matches[1] ?? '' ), PHP_URL_QUERY ) ?? '', $export_args );
		$_GET = wp_slash( $export_args );
		$csv  = $table->generate_report();

		self::assertSame( array( $lesson ), wp_list_pluck( $items, 'ID' ), 'The displayed rows must include only the matching lesson.' );
		self::assertSame( 'Lesson (1)', $columns['title'], 'The lesson total must reflect the search.' );
		self::assertSame( 'Students (1)', $columns['students'], 'The student total must exclude the unmatched lesson.' );
		self::assertSame( 'Completed (1)', $columns['completions'], 'The completed total must reflect the matching lesson.' );
		self::assertSame( 'Completion Rate (100%)', $columns['completion_rate'], 'The completion rate must exclude unmatched activity.' );
		self::assertSame( $search, $export_args['s'] ?? null, 'The export link must preserve the submitted search text.' );
		self::assertCount( 2, $csv, 'The CSV must contain its header and only the matching lesson.' );
		self::assertStringStartsWith( 'Selected ', $csv[1]['title'], 'The CSV must export the matching lesson after following the export link.' );
		self::assertSame( 'Students (1)', $csv[0][1], 'The CSV header must retain the selected student total.' );
	}

	/**
	 * Exported lesson rows and headers reflect the selected activity.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Lessons::generate_report
	 * @dataProvider lesson_report_scenarios
	 */
	public function testGenerateReport_LessonActivityGiven_ExportsMatchingProgressMetrics( array $activity, int $offset, array $expected ): void {
		/* Arrange. */
		list( $table ) = $this->create_lesson_report( $activity, $offset );

		/* Act. */
		$csv = $table->generate_report();

		/* Assert. */
		self::assertSame( 'Students (' . $expected['students'] . ')', $csv[0][1], 'The CSV header must count the selected students.' );
		self::assertSame( 'Completed (' . $expected['completions'] . ')', $csv[0][3], 'The CSV header must count eligible completions.' );
		self::assertSame( 'Completion Rate (' . ( 'N/A' === $expected['completion_rate'] ? '0%' : $expected['completion_rate'] ) . ')', $csv[0][4], 'The CSV header must use eligible starts and completions.' );
		self::assertSame( 'Days to Completion (' . $expected['days_to_completion'] . ')', $csv[0][5], 'The CSV header must use the expected average duration.' );
		self::assertSame( $expected, array_intersect_key( $csv[1], $expected ), 'CSV must export the expected progress metrics.' );
	}

	/**
	 * Mixed translated and untranslated rows retain their own progress and totals.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Lessons::generate_report
	 * @covers Sensei_Reports_Overview_List_Table_Lessons::get_row_data
	 * @covers Sensei_Reports_Overview_List_Table_Lessons::get_columns
	 */
	public function testGenerateReport_TranslatedAndUntranslatedLessonsGiven_PreservesPerLessonMetrics(): void {
		/* Arrange. */
		$course    = $this->factory->course->create();
		$originals = $this->factory->lesson->create_many( 2 );
		$first     = $this->factory->lesson->create(
			array(
				'post_title' => 'Translation A',
				'meta_input' => array( '_lesson_course' => $course ),
			)
		);
		$second    = $this->factory->lesson->create(
			array(
				'post_title' => 'Translation B',
				'meta_input' => array( '_lesson_course' => $course ),
			)
		);
		$third     = $this->factory->lesson->create(
			array(
				'post_title' => 'Untranslated',
				'meta_input' => array( '_lesson_course' => $course ),
			)
		);
		$this->create_lesson_progress( $originals[0], '2022-01-01 12:00:00', '2022-01-02 12:00:00' );
		$this->create_lesson_progress( $originals[1], '2022-01-01 12:00:00', '2022-01-04 12:00:00' );
		$this->create_lesson_progress( $originals[1], '2022-01-05 12:00:00', null );
		$this->create_lesson_progress( $third, '2022-01-01 12:00:00', '2022-01-06 12:00:00' );
		$this->create_lesson_progress( $originals[0], '2022-01-01 12:00:00', '2022-01-11 12:00:00', array( 'user_login' => Sensei_Guest_User::LOGIN_PREFIX . 'translated' ) );
		$this->create_lesson_progress( $originals[1], '2022-01-01 12:00:00', '2022-01-21 12:00:00', array( 'user_login' => Sensei_Preview_User::LOGIN_PREFIX . 'translated' ) );
		$id_map = array(
			$first  => $originals[0],
			$second => $originals[1],
		);
		add_filter(
			'sensei_lesson_progress_get_lesson_id',
			static function ( $id ) use ( $id_map ) {
				return $id_map[ $id ] ?? $id;
			}
		);
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preserve the request used by the report fixture.
		$initial_get           = $_GET;
		$_GET['course_filter'] = $course;
		$table                 = new Sensei_Reports_Overview_List_Table_Lessons( Sensei()->course, new Sensei_Reports_Overview_Data_Provider_Lessons( Sensei()->course ) );
		$row_method            = new ReflectionMethod( $table, 'get_row_data' );
		Sensei_Unit_Tests_Bootstrap::make_reflection_accessible( $row_method );
		$expected = array(
			'Translation A' => array(
				'students'           => '1',
				'last_activity'      => Sensei_Utils::format_last_activity_date( '2022-01-02 12:00:00' ),
				'completions'        => '1',
				'completion_rate'    => '100%',
				'days_to_completion' => '2',
			),
			'Translation B' => array(
				'students'           => '2',
				'last_activity'      => Sensei_Utils::format_last_activity_date( '2022-01-04 12:00:00' ),
				'completions'        => '1',
				'completion_rate'    => '50%',
				'days_to_completion' => '4',
			),
			'Untranslated'  => array(
				'students'           => '1',
				'last_activity'      => Sensei_Utils::format_last_activity_date( '2022-01-06 12:00:00' ),
				'completions'        => '1',
				'completion_rate'    => '100%',
				'days_to_completion' => '6',
			),
		);

		/* Act. */
		try {
			$table->prepare_items();
			$items   = $table->items;
			$columns = $table->get_columns();
			$rows    = array();
			foreach ( $items as $item ) {
				$rows[ $item->post_title ] = array_intersect_key( $row_method->invoke( $table, $item ), $expected['Translation A'] );
			}
			$csv      = $table->generate_report();
			$csv_rows = array();
			foreach ( array_slice( $csv, 1 ) as $row ) {
				$csv_rows[ $row['title'] ] = array_intersect_key( $row, $expected['Translation A'] );
			}
		} finally {
			$_GET = $initial_get;
		}
		ksort( $rows );
		ksort( $csv_rows );

		/* Assert. */
		self::assertEqualsCanonicalizing( array( $first, $second, $third ), wp_list_pluck( $items, 'ID' ), 'Mixed mappings must retain exactly the requested lessons without duplicates.' );
		self::assertSame( $expected, $rows, 'Each displayed lesson must retain its own progress metrics.' );
		self::assertSame( $expected, $csv_rows, 'CSV must retain the same metrics for every requested lesson.' );
		self::assertSame( 'Students (4)', $columns['students'], 'Header totals must include students from mapped and unmapped lessons.' );
		self::assertSame( 'Days to Completion (4)', $columns['days_to_completion'], 'Header duration must average the three stored completions once each.' );
	}

	/**
	 * Submitted quiz status determines which completions contribute to duration.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Lessons::generate_report
	 * @dataProvider quiz_duration_statuses
	 */
	public function testGenerateReport_SubmittedQuizStatusGiven_UsesEligibleCompletionDurations( string $status, string $days ): void {
		/* Arrange. */
		$created = $this->factory->get_course_with_lessons(
			array(
				'lesson_count'   => 1,
				'question_count' => 1,
			)
		);
		$lesson  = $created['lesson_ids'][0];
		$quiz    = $created['quiz_ids'][0];
		$this->create_lesson_progress( $lesson, '2022-01-01 12:00:00', '2022-01-03 12:00:00' );
		$user     = $this->create_lesson_progress( $lesson, '2022-01-01 12:00:00', '2022-01-07 12:00:00' );
		$progress = Sensei()->quiz_progress_repository->create( $quiz, $user );
		$progress->start( new DateTimeImmutable( '2022-01-01 12:00:00', new DateTimeZone( 'UTC' ) ) );
		if ( 'grade' === $status ) {
			$progress->grade( new DateTimeImmutable( '2022-01-07 12:00:00', new DateTimeZone( 'UTC' ) ) );
		} else {
			$progress->ungrade();
		}
		Sensei()->quiz_progress_repository->save( $progress );
		Sensei()->quiz_submission_repository->create( $quiz, $user, 0 );
		if ( ! self::is_hpps_tables_mode() ) {
			wp_update_comment(
				array(
					'comment_ID'       => $progress->get_id(),
					'comment_date'     => '2022-01-07 12:00:00',
					'comment_date_gmt' => '2022-01-07 12:00:00',
				)
			);
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preserve the request used by the report fixture.
		$initial_get           = $_GET;
		$_GET['course_filter'] = $created['course_id'];
		$table                 = new Sensei_Reports_Overview_List_Table_Lessons( Sensei()->course, new Sensei_Reports_Overview_Data_Provider_Lessons( Sensei()->course ) );

		/* Act. */
		try {
			$csv     = $table->generate_report();
			$columns = $table->get_columns();
		} finally {
			$_GET = $initial_get;
		}

		/* Assert. */
		self::assertSame( $days, $csv[1]['days_to_completion'], 'The average must use only statuses eligible for completion duration.' );
		self::assertSame( '2', $csv[1]['completions'], 'Both finished activities must count toward Completed.' );
		self::assertSame( 'Days to Completion (' . $days . ')', $columns['days_to_completion'], 'The header must use the same eligible durations.' );
	}

	/**
	 * Tests that we are getting right data for Completion Rate column.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Lessons::generate_report
	 * @dataProvider lessonCompletionRateData
	 * @test
	 */
	public function testGenerateReport_StudentEnrollmentsFound_ReturnsRowsWithMatchingCompletionRate( $enrolled_student_count, $completed_student_count, $expected_output ) {
		/* Arrange */
		$data_provider_instance = new Sensei_Reports_Overview_Data_Provider_Lessons( Sensei()->course );
		$lesson_table_instance  = new Sensei_Reports_Overview_List_Table_Lessons( Sensei()->course, $data_provider_instance );
		$user_ids               = $this->factory->user->create_many( $enrolled_student_count );
		$course_lessons         = $this->factory->get_course_with_lessons(
			array(
				'lesson_count' => 1,
			)
		);
		$lesson_id              = array_pop( $course_lessons['lesson_ids'] );
		foreach ( $user_ids as $key => $user_id ) {
			Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id, $key < $completed_student_count );
		}
		$_GET['course_filter'] = $course_lessons['course_id'];

		/* Act */
		$row_data = $lesson_table_instance->generate_report();

		/* Assert */
		$this->assertEquals( $row_data[1]['completion_rate'], $expected_output, "The calculated 'Completion Rate' is not correct" );
	}

	/**
	 * Tests that we are getting the correct value for totals in column headers for lesson table.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Lessons::get_columns
	 */
	public function testGetColumns_WhenCalled_ReturnsColumnsWithMatchingTotals() {
		/* Arrange */
		$user_ids   = $this->factory->user->create_many( 3 );
		$course_id  = $this->factory->course->create();
		$lesson_ids = $this->factory->lesson->create_many(
			3,
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$days_count = 7;
		$started    = new DateTimeImmutable( '2022-01-01 12:00:00', new DateTimeZone( 'UTC' ) );

		$data_provider_instance = new Sensei_Reports_Overview_Data_Provider_Lessons( Sensei()->course );
		$lesson_table_instance  = new Sensei_Reports_Overview_List_Table_Lessons( Sensei()->course, $data_provider_instance );

		$_GET['course_filter'] = $course_id;
		// Complete a lesson for each student on a different date.
		foreach ( $user_ids as $user_id ) {
			$date     = $started->modify( '+' . $days_count . ' days' );
			$progress = Sensei()->lesson_progress_repository->create( $lesson_ids[0], $user_id );
			$progress->start( $started );
			$progress->complete( $date );
			Sensei()->lesson_progress_repository->save( $progress );
			if ( ! self::is_hpps_tables_mode() ) {
				wp_update_comment(
					array(
						'comment_ID'   => $progress->get_id(),
						'comment_date' => $date->format( 'Y-m-d H:i:s' ),
					)
				);
			}
			++$days_count;
		}

		/* ACT */
		$actual = $lesson_table_instance->get_columns();

		/* Assert */
		$expected = array(
			'title'              => 'Lesson (3)',
			'students'           => 'Students (3)',
			'last_activity'      => 'Last Activity',
			'completions'        => 'Completed (1)',
			'completion_rate'    => 'Completion Rate (100%)',
			'days_to_completion' => 'Days to Completion (9)',
		);
		self::assertSame( $expected, $actual, 'The expected column headers for lessons table in overview report does not match the actual output' );
	}

	/**
	 * Search text that must survive request normalization and export links.
	 *
	 * @return array
	 */
	public function lesson_search_requests(): array {
		return array(
			'ordinary text'     => array( 'needle', 'Selected needle lesson', 'Another lesson' ),
			'apostrophe'        => array( "King's", "Selected King's guide", 'Kings guide' ),
			'ampersand'         => array( 'Rock & Roll', 'Selected Rock & Roll workshop', 'Rock and Roll workshop' ),
			'quoted phrase'     => array( '"red fox"', 'Selected red fox workshop', 'A red swift fox workshop' ),
			'zero'              => array( '0', 'Selected Lesson 0', 'Lesson 1' ),
			'literal backslash' => array( 'path\\chapter', 'Selected path\\chapter guide', 'A pathchapter guide' ),
		);
	}

	/**
	 * Distinct progress populations and local-calendar boundaries for lesson metrics.
	 *
	 * @return array
	 */
	public function lesson_report_scenarios(): array {
		$completed = array(
			'students'           => '1',
			'last_activity'      => 'January 3, 2022',
			'completions'        => '1',
			'completion_rate'    => '100%',
			'days_to_completion' => '3',
		);
		return array(
			'completed activity'                        => array( array( array( '2022-01-01 12:00:00', '2022-01-03 12:00:00' ) ), 0, $completed ),
			'registered, guest and preview completions' => array(
				array(
					array( '2022-01-01 12:00:00', '2022-01-03 12:00:00' ),
					array( '2022-01-01 12:00:00', '2022-01-11 12:00:00', array( 'user_login' => Sensei_Guest_User::LOGIN_PREFIX . 'report' ) ),
					array( '2022-01-01 12:00:00', '2022-01-21 12:00:00', array( 'user_login' => Sensei_Preview_User::LOGIN_PREFIX . 'report' ) ),
				),
				0,
				$completed,
			),
			'only guest and preview completions'        => array(
				array(
					array( '2022-01-01 12:00:00', '2022-01-11 12:00:00', array( 'user_login' => Sensei_Guest_User::LOGIN_PREFIX . 'report' ) ),
					array( '2022-01-01 12:00:00', '2022-01-21 12:00:00', array( 'user_login' => Sensei_Preview_User::LOGIN_PREFIX . 'report' ) ),
				),
				0,
				array(
					'students'           => '0',
					'last_activity'      => 'N/A',
					'completions'        => '0',
					'completion_rate'    => 'N/A',
					'days_to_completion' => 'N/A',
				),
			),
			'no activity'                               => array(
				array(),
				0,
				array(
					'students'           => '0',
					'last_activity'      => 'N/A',
					'completions'        => '0',
					'completion_rate'    => 'N/A',
					'days_to_completion' => 'N/A',
				),
			),
			'unfinished activity'                       => array(
				array( array( '2022-01-01 12:00:00', null ) ),
				0,
				array(
					'students'           => '1',
					'last_activity'      => 'N/A',
					'completions'        => '0',
					'completion_rate'    => '0%',
					'days_to_completion' => 'N/A',
				),
			),
			'multiple completions and an unfinished student' => array(
				array(
					array( '2022-01-01 12:00:00', '2022-01-02 12:00:00' ),
					array( '2022-01-02 12:00:00', '2022-01-04 12:00:00' ),
					array( '2022-01-05 12:00:00', null ),
				),
				0,
				array(
					'students'           => '3',
					'last_activity'      => 'January 4, 2022',
					'completions'        => '2',
					'completion_rate'    => '67%',
					'days_to_completion' => '3',
				),
			),
			'UTC midnight within one local day'         => array(
				array( array( '2022-01-01 23:30:00', '2022-01-02 01:30:00' ) ),
				-5,
				array(
					'students'           => '1',
					'last_activity'      => 'January 2, 2022',
					'completions'        => '1',
					'completion_rate'    => '100%',
					'days_to_completion' => '1',
				),
			),
		);
	}

	/**
	 * Quiz grading states with different duration eligibility.
	 *
	 * @return array
	 */
	public function quiz_duration_statuses(): array {
		return array(
			'graded'           => array( 'grade', '5' ),
			'awaiting grading' => array( 'ungrade', '3' ),
		);
	}

	/**
	 * Returns an associative array with parameters needed to run lesson completion test.
	 *
	 * @return array
	 */
	public function lessonCompletionRateData(): array {
		return array(
			'100%' => array( 5, 5, '100%' ),
			'80%'  => array( 5, 4, '80%' ),
			'67%'  => array( 3, 2, '67%' ),
			'N/A'  => array( 0, 0, 'N/A' ),
			'0%'   => array( 1, 0, '0%' ),
		);
	}

	/**
	 * Create a lesson report with fixed activity and date formatting.
	 *
	 * @param array $activity Student activity dates and optional user arguments.
	 * @param int   $offset   Site UTC offset in hours.
	 * @return array{Sensei_Reports_Overview_List_Table_Lessons, int} Table and requested lesson ID.
	 */
	private function create_lesson_report( array $activity, int $offset ): array {
		$course = $this->factory->course->create();
		$lesson = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course ) ) );
		update_option( 'timezone_string', '' );
		update_option( 'gmt_offset', $offset );
		update_option( 'date_format', 'F j, Y' );
		foreach ( $activity as $dates ) {
			$this->create_lesson_progress( $lesson, $dates[0], $dates[1], $dates[2] ?? array() );
		}
		$_GET['course_filter'] = $course;
		$table                 = new Sensei_Reports_Overview_List_Table_Lessons( Sensei()->course, new Sensei_Reports_Overview_Data_Provider_Lessons( Sensei()->course ) );

		return array( $table, $lesson );
	}

	/**
	 * Create one student's lesson activity with fixed UTC dates.
	 *
	 * @param int         $lesson    Lesson ID.
	 * @param string      $start     UTC start date.
	 * @param string|null $completed UTC completion date, or null for unfinished activity.
	 * @param array       $user_args User factory arguments.
	 * @return int Student ID.
	 */
	private function create_lesson_progress( int $lesson, string $start, ?string $completed, array $user_args = array() ): int {
		$user     = $this->factory->user->create( $user_args );
		$progress = Sensei()->lesson_progress_repository->create( $lesson, $user );
		$progress->start( new DateTimeImmutable( $start, new DateTimeZone( 'UTC' ) ) );
		if ( null !== $completed ) {
			$progress->complete( new DateTimeImmutable( $completed, new DateTimeZone( 'UTC' ) ) );
		}
		Sensei()->lesson_progress_repository->save( $progress );
		if ( ! self::is_hpps_tables_mode() && null !== $completed ) {
			wp_update_comment(
				array(
					'comment_ID'       => $progress->get_id(),
					'comment_date'     => wp_date( 'Y-m-d H:i:s', strtotime( $completed . ' UTC' ) ),
					'comment_date_gmt' => $completed,
				)
			);
		}
		return $user;
	}
}
