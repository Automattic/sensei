<?php

use Sensei\Internal\Services\Grading_Stats_Service_Interface;
use Sensei\Internal\Services\Utils;

/**
 * Shared grade-total behavior for both progress backends.
 *
 * @covers \Sensei\Internal\Services\Comments_Based_Grading_Stats_Service
 * @covers \Sensei\Internal\Services\Tables_Based_Grading_Stats_Service
 */
abstract class Grading_Stats_Service_Test extends WP_UnitTestCase {
	/**
	 * Bulk exclusions apply equally to grade counts and sums.
	 *
	 * @covers \Sensei\Internal\Services\Grading_Stats_Service_Interface::get_grade_totals_by_lesson
	 * @dataProvider grade_exclusions
	 */
	public function testGetGradeTotalsByLesson_RegisteredAndTemporaryGradesGiven_ReturnsMatchingCountAndSum( array $prefixes, array $expected ): void {
		$factory = new Sensei_Factory();
		$lesson  = $factory->lesson->create();
		foreach ( array(
			'registered'             => 40,
			'sensei_guest_student'   => 80,
			'sensei_preview_student' => 100,
		) as $login => $grade ) {
			$user = $factory->user->create( array( 'user_login' => $login ) );
			$this->create_grade( $lesson, $user, 'graded', $grade );
		}

		$actual = $this->get_service()->get_grade_totals_by_lesson( array( $lesson ), array( 'exclude_user_login_prefixes' => $prefixes ) );

		self::assertSame( array( $lesson => $expected ), $actual );
	}

	/**
	 * Bulk lesson totals retain requested IDs and restrict both inputs to eligible student attempts.
	 *
	 * @covers \Sensei\Internal\Services\Grading_Stats_Service_Interface::get_grade_totals_by_lesson
	 */
	public function testGetGradeTotalsByLesson_FilteredTranslatedLessonsGiven_ReturnsMatchingLessonTotals(): void {
		$factory     = new Sensei_Factory();
		$lesson      = $factory->lesson->create();
		$translation = $factory->lesson->create();
		$second      = $factory->lesson->create();
		$empty       = $factory->lesson->create();
		$student     = $factory->user->create();
		$this->create_grade( $lesson, $student, 'failed', 40 );
		$this->create_grade( $lesson, $factory->user->create(), 'failed', 80 );
		$this->create_grade( $second, $student, 'passed', 60 );
		foreach ( array( 'sensei_guest_student', 'sensei_preview_student' ) as $login ) {
			$user = $factory->user->create( array( 'user_login' => $login ) );
			$this->create_grade( $lesson, $user, 'failed', 100 );
		}
		add_filter(
			'sensei_lesson_progress_get_lesson_id',
			static function ( $id ) use ( $lesson, $translation ) {
				return $translation === $id ? $lesson : $id;
			}
		);

		$actual = $this->get_service()->get_grade_totals_by_lesson(
			array( $translation, $lesson, $second, $empty ),
			array( 'exclude_user_login_prefixes' => Utils::REPORTS_EXCLUDED_USER_LOGIN_PREFIXES )
		);

		self::assertSame(
			array(
				$translation => array(
					'count' => 2,
					'sum'   => 120.0,
				),
				$lesson      => array(
					'count' => 2,
					'sum'   => 120.0,
				),
				$second      => array(
					'count' => 1,
					'sum'   => 60.0,
				),
				$empty       => array(
					'count' => 0,
					'sum'   => 0.0,
				),
			),
			$actual
		);
	}

	/**
	 * Ungraded attempts affect neither the grade count nor the grade sum.
	 *
	 * @covers \Sensei\Internal\Services\Grading_Stats_Service_Interface::get_grade_totals_by_lesson
	 */
	public function testGetGradeTotalsByLesson_UngradedAttemptGiven_ExcludesItsGrade(): void {
		$factory = new Sensei_Factory();
		$lesson  = $factory->lesson->create();
		$this->create_grade( $lesson, $factory->user->create(), 'graded', 40 );
		$this->create_grade( $lesson, $factory->user->create(), 'ungraded', 80 );

		$actual = $this->get_service()->get_grade_totals_by_lesson( array( $lesson ) );

		self::assertSame(
			array(
				$lesson => array(
					'count' => 1,
					'sum'   => 40.0,
				),
			),
			$actual
		);
	}

	/**
	 * Empty selections cannot read grades from other lessons.
	 *
	 * @covers \Sensei\Internal\Services\Grading_Stats_Service_Interface::get_grade_totals_by_lesson
	 */
	public function testGetGradeTotalsByLesson_EmptySelectionGiven_ReturnsNoTotals(): void {
		$factory = new Sensei_Factory();
		$lesson  = $factory->lesson->create();
		$this->create_grade( $lesson, $factory->user->create(), 'graded', 40 );

		$actual = $this->get_service()->get_grade_totals_by_lesson( array() );

		self::assertSame( array(), $actual );
	}

	/**
	 * Grades without submitted answers must affect neither input of an average.
	 *
	 * @covers \Sensei\Internal\Services\Grading_Stats_Service_Interface::get_grade_totals_by_lesson
	 */
	public function testGetGradeTotalsByLesson_UnsubmittedAttemptGiven_ExcludesItsGrade(): void {
		$factory = new Sensei_Factory();
		$lesson  = $factory->lesson->create();
		$this->create_grade( $lesson, $factory->user->create(), 'passed', 40 );
		$this->create_grade( $lesson, $factory->user->create(), 'passed', 100, false );

		$actual = $this->get_service()->get_grade_totals_by_lesson( array( $lesson ) );

		self::assertSame(
			array(
				$lesson => array(
					'count' => 1,
					'sum'   => 40.0,
				),
			),
			$actual
		);
	}

	public static function grade_exclusions(): array {
		return array(
			'reports'        => array(
				Utils::REPORTS_EXCLUDED_USER_LOGIN_PREFIXES,
				array(
					'count' => 1,
					'sum'   => 40.0,
				),
			),
			'public default' => array(
				array(),
				array(
					'count' => 3,
					'sum'   => 220.0,
				),
			),
		);
	}

	abstract protected function get_service(): Grading_Stats_Service_Interface;

	abstract protected function create_grade( int $lesson, int $user, string $status, float $grade, bool $submitted = true ): void;
}
