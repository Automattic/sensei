<?php

/**
 * Sensei Reports Overview Data Provider Courses Test Class
 *
 * @covers Sensei_Reports_Overview_Data_Provider_Courses
 */
class Sensei_Reports_Overview_Data_Provider_Courses_Test extends WP_UnitTestCase {
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
	 * Courses with completed or unfinished progress remain in the report.
	 *
	 * @covers Sensei_Reports_Overview_Data_Provider_Courses::get_items
	 */
	public function testGetItems_FiltersWithoutLastActivityGiven_ReturnsMatchingCourses(): void {
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$started   = new DateTimeImmutable( '2022-01-01 00:00:01', new DateTimeZone( 'UTC' ) );
		$progress  = Sensei()->course_progress_repository->create( $course_id, $user_id );
		$progress->start( $started );
		$progress->complete( new DateTimeImmutable( '2022-01-02 00:00:01', new DateTimeZone( 'UTC' ) ) );
		Sensei()->course_progress_repository->save( $progress );
		if ( ! self::is_hpps_tables_mode() ) {
			// Comments storage stamps saves with the current time; restore the fixture completion date.
			wp_update_comment(
				array(
					'comment_ID'       => $progress->get_id(),
					'comment_date'     => '2022-01-02 00:00:01',
					'comment_date_gmt' => '2022-01-02 00:00:01',
				)
			);
		}

		$unfinished_course_id = $this->factory->course->create();
		$unfinished_progress  = Sensei()->course_progress_repository->create( $unfinished_course_id, $user_id );
		$unfinished_progress->start( $started );
		Sensei()->course_progress_repository->save( $unfinished_progress );
		$data_provider = new Sensei_Reports_Overview_Data_Provider_Courses();

		$courses = $data_provider->get_items(
			array(
				'number'  => 2,
				'offset'  => 0,
				'orderby' => '',
				'order'   => 'ASC',
			)
		);

		$expected = array(
			array(
				'id'                 => $course_id,
				'last_activity_date' => null,
				'days_to_completion' => '2',
			),
			array(
				'id'                 => $unfinished_course_id,
				'last_activity_date' => null,
				'days_to_completion' => null,
			),
		);
		self::assertSame( $expected, $this->exportCourses( $courses ) );
	}

	/**
	 * Last Activity filtering retains completion days from the active progress backend.
	 *
	 * @covers Sensei_Reports_Overview_Data_Provider_Courses::get_items
	 */
	public function testGetItems_FiltersWithLastActivityGiven_ReturnsMatchingCourses(): void {
		global $wpdb;

		$user_id    = $this->factory->user->create();
		$course_ids = array();
		foreach ( array( '2022-01-02 00:00:01', '2022-01-01 00:00:01' ) as $date ) {
			$course_id    = $this->factory->course->create();
			$course_ids[] = $course_id;
			$lesson_id    = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );
			$progress     = Sensei()->lesson_progress_repository->create( $lesson_id, $user_id );
			$progress->start( new DateTimeImmutable( $date, new DateTimeZone( 'UTC' ) ) );
			$progress->complete( new DateTimeImmutable( $date, new DateTimeZone( 'UTC' ) ) );
			Sensei()->lesson_progress_repository->save( $progress );
			// Both repositories stamp saves with the current time; fix the date used by Last Activity.
			if ( self::is_hpps_tables_mode() ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Set a fixed activity date in the selected backend.
				$wpdb->update(
					$wpdb->prefix . 'sensei_lms_progress',
					array( 'updated_at' => $date ),
					array(
						'post_id' => $lesson_id,
						'user_id' => $user_id,
						'type'    => 'lesson',
					)
				);
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Set a fixed activity date in the selected backend.
				$wpdb->update(
					$wpdb->comments,
					array(
						'comment_date'     => $date,
						'comment_date_gmt' => $date,
					),
					array(
						'comment_post_ID' => $lesson_id,
						'user_id'         => $user_id,
						'comment_type'    => 'sensei_lesson_status',
					)
				);
			}
		}
		$course_progress = Sensei()->course_progress_repository->create( $course_ids[0], $user_id );
		$course_progress->start( new DateTimeImmutable( '2022-01-01 00:00:01', new DateTimeZone( 'UTC' ) ) );
		$course_progress->complete( new DateTimeImmutable( '2022-01-02 00:00:01', new DateTimeZone( 'UTC' ) ) );
		Sensei()->course_progress_repository->save( $course_progress );
		if ( ! self::is_hpps_tables_mode() ) {
			// Comments storage stamps saves with the current time; restore the fixture completion date.
			wp_update_comment(
				array(
					'comment_ID'       => $course_progress->get_id(),
					'comment_date'     => '2022-01-02 00:00:01',
					'comment_date_gmt' => '2022-01-02 00:00:01',
				)
			);
		}
		$provider = new Sensei_Reports_Overview_Data_Provider_Courses();

		$courses = $provider->get_items(
			array(
				'number'                  => 2,
				'offset'                  => 0,
				'last_activity_date_from' => '2022-01-02 00:00:00',
				'last_activity_date_to'   => '2022-01-03 23:59:59',
			)
		);

		$expected = array(
			array(
				'id'                 => $course_ids[0],
				'last_activity_date' => '2022-01-02 00:00:01',
				'days_to_completion' => '2',
			),
		);
		self::assertSame( $expected, $this->exportCourses( $courses ) );
	}

	/**
	 * Translated courses retain original lesson activity and date selection.
	 *
	 * @covers Sensei_Reports_Overview_Data_Provider_Courses::get_items
	 * @dataProvider translated_activity_filters
	 */
	public function testGetItems_TranslatedCourseGiven_ResolvesOriginalLastActivity( array $dates, bool $included ): void {
		global $wpdb;

		$original           = $this->factory->course->create();
		$translation        = $this->factory->course->create();
		$second_translation = $this->factory->course->create();
		$lesson             = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $original ) ) );
		$user               = $this->factory->user->create();
		$date               = '2022-01-02 12:00:00';
		$progress           = Sensei()->lesson_progress_repository->create( $lesson, $user );
		$progress->start();
		$progress->complete();
		Sensei()->lesson_progress_repository->save( $progress );
		// Saves stamp the current time; fix the timestamp read by each backend.
		if ( self::is_hpps_tables_mode() ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Set a fixed activity date in the selected backend.
			$wpdb->update( $wpdb->prefix . 'sensei_lms_progress', array( 'updated_at' => $date ), array( 'id' => $progress->get_id() ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Set a fixed activity date in the selected backend.
			$wpdb->update( $wpdb->comments, array( 'comment_date_gmt' => $date ), array( 'comment_ID' => $progress->get_id() ) );
		}
		add_filter(
			'sensei_course_progress_get_course_id',
			static function ( $id ) use ( $original, $translation, $second_translation ) {
				return in_array( $id, array( $translation, $second_translation ), true ) ? $original : $id;
			}
		);
		$provider = new Sensei_Reports_Overview_Data_Provider_Courses();

		$courses = $provider->get_items(
			array_merge(
				array(
					'number' => -1,
					'offset' => 0,
				),
				$dates
			)
		);
		$actual  = array_column( $courses, 'last_activity_date', 'ID' );
		ksort( $actual );

		$expected = $included ? array(
			$original           => $date,
			$translation        => $date,
			$second_translation => $date,
		) : array();
		self::assertSame( $expected, $actual, 'Original and requested translated IDs should share the stored activity and date selection.' );
		self::assertSame( $included ? 3 : 0, $provider->get_last_total_items(), 'The total should count the selected course posts.' );
	}

	/**
	 * Date ranges selecting or excluding the original activity.
	 *
	 * @return array
	 */
	public function translated_activity_filters(): array {
		return array(
			'no date filter'      => array( array(), true ),
			'matching date range' => array(
				array(
					'last_activity_date_from' => '2022-01-02 00:00:00',
					'last_activity_date_to'   => '2022-01-02 23:59:59',
				),
				true,
			),
			'outside date range'  => array(
				array(
					'last_activity_date_from' => '2022-01-03 00:00:00',
					'last_activity_date_to'   => '2022-01-03 23:59:59',
				),
				false,
			),
		);
	}

	/**
	 * Temporary progress does not supply Last Activity.
	 *
	 * @covers Sensei_Reports_Overview_Data_Provider_Courses::get_items
	 */
	public function testGetItems_TemporaryUserProgressCreated_ReturnsMatchingCourse() {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );
		$started   = current_datetime();
		$completed = $started;

		foreach ( array( 'sensei_guest_student', 'sensei_preview_student' ) as $login ) {
			$user_id = $this->factory->user->create( array( 'user_login' => $login ) );

			$course_progress = Sensei()->course_progress_repository->create( $course_id, $user_id );
			$course_progress->start( $started );
			$course_progress->complete( $completed );
			Sensei()->course_progress_repository->save( $course_progress );

			$lesson_progress = Sensei()->lesson_progress_repository->create( $lesson_id, $user_id );
			$lesson_progress->start( $started );
			$lesson_progress->complete( $completed );
			Sensei()->lesson_progress_repository->save( $lesson_progress );
		}

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Courses();

		/* Act. */
		$courses = $data_provider->get_items(
			array(
				'number'  => 1,
				'offset'  => 0,
				'orderby' => '',
				'order'   => 'ASC',
			)
		);

		/* Assert. */
		$expected = array(
			array(
				'id'                 => $course_id,
				'last_activity_date' => null,
				'days_to_completion' => null,
			),
		);

		self::assertSame( $expected, $this->exportCourses( $courses ), 'Temporary-only courses should remain without Last Activity.' );
	}

	/**
	 * Published and private courses remain eligible for report rows.
	 *
	 * @covers Sensei_Reports_Overview_Data_Provider_Courses::get_items
	 */
	public function testGetItems_MixedCoursePostStatusesGiven_ReturnsOnlyPublishedAndPrivateCourses(): void {
		/* Arrange. */
		$course_ids = array();
		foreach ( array( 'publish', 'private', 'draft', 'trash' ) as $status ) {
			$course_ids[ $status ] = $this->factory->course->create( array( 'post_status' => $status ) );
		}
		$provider = new Sensei_Reports_Overview_Data_Provider_Courses();

		/* Act. */
		$actual = $provider->get_items(
			array(
				'number'  => 10,
				'offset'  => 0,
				'orderby' => 'title',
				'order'   => 'ASC',
			)
		);

		/* Assert. */
		self::assertEqualsCanonicalizing( array( $course_ids['publish'], $course_ids['private'] ), wp_list_pluck( $actual, 'ID' ), 'Only published and private courses should appear.' );
	}

	private function exportCourses( array $courses ): array {
		$ret = array();

		foreach ( $courses as $course ) {
			$ret[] = array(
				'id'                 => $course->ID,
				'last_activity_date' => $course->last_activity_date,
				'days_to_completion' => $course->days_to_completion,
			);
		}

		return $ret;
	}
}
