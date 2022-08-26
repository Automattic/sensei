<?php
/**
 * File containing the Course_Progress_Abstract_Test class.
 */

namespace SenseiTest\Student_Progress\Models;

use Sensei\Student_Progress\Models\Course_Progress_Abstract;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Course_Progress_Abstract_Test.
 *
 * @covers \Sensei\Student_Progress\Models\Course_Progress_Abstract
 */
class Course_Progress_Abstract_Test extends \WP_UnitTestCase {
	public function testGetId_ConstructedWithId_ReturnsSameId(): void {
		$course_progress = $this->createCourseProgress();
		self::assertSame( 1, $course_progress->get_id() );
	}

	public function testGetCourseId_ConstructedWithCourseId_ReturnsSameCourseId(): void {
		$course_progress = $this->createCourseProgress();
		self::assertSame( 2, $course_progress->get_course_id() );
	}

	public function testGetUserId_ConstructedWithUserId_ReturnsSameUserId(): void {
		$course_progress = $this->createCourseProgress();
		self::assertSame( 3, $course_progress->get_user_id() );
	}

	public function testGetStatus_ConstructedWithStatus_ReturnsSameStatus(): void {
		$course_progress = $this->createCourseProgress();
		self::assertSame( 'complete', $course_progress->get_status() );
	}

	public function testGetCreatedAt_ConstructedWithCreatedAt_ReturnsSameCreatedAt(): void {
		$course_progress = $this->createCourseProgress();
		self::assertSame( '2020-01-01 00:00:00', $course_progress->get_created_at()->format( 'Y-m-d H:i:s' ) );
	}

	public function testGetStartedAt_ConstructedWithStartedAt_ReturnsSameStartedAt(): void {
		$course_progress = $this->createCourseProgress();
		self::assertSame( '2020-01-01 00:00:01', $course_progress->get_started_at()->format( 'Y-m-d H:i:s' ) );
	}

	public function testGetCompletedAt_ConstructedWithCompletedAt_ReturnsSameCompletedAt(): void {
		$course_progress = $this->createCourseProgress();
		self::assertSame( '2020-01-01 00:00:02', $course_progress->get_completed_at()->format( 'Y-m-d H:i:s' ) );
	}

	public function testGetUpdatedAt_ConstructedWithUpdatedAt_ReturnsSameUpdatedAt(): void {
		$course_progress = $this->createCourseProgress();
		self::assertSame( '2020-01-01 00:00:03', $course_progress->get_updated_at()->format( 'Y-m-d H:i:s' ) );
	}

	public function testGetUpdatedAt_WhenUpdatedAtSet_ReturnsSameUpdatedAt(): void {
		$course_progress = $this->createCourseProgress();
		$course_progress->set_updated_at( new \DateTime( '2020-01-01 00:00:04' ) );
		self::assertSame( '2020-01-01 00:00:04', $course_progress->get_updated_at()->format( 'Y-m-d H:i:s' ) );
	}

	public function testGetMetadata_ConstructedWithMetadata_ReturnsSameMetadata(): void {
		$course_progress = $this->createCourseProgress();
		self::assertSame( [ 'a' => 'b' ], $course_progress->get_metadata() );
	}

	private function createCourseProgress(): Course_Progress_Abstract {
		$constructor_arguments = [
			1,
			2,
			3,
			new \DateTime( '2020-01-01 00:00:00' ),
			'complete',
			new \DateTime( '2020-01-01 00:00:01' ),
			new \DateTime( '2020-01-01 00:00:02' ),
			new \DateTime( '2020-01-01 00:00:03' ),
			[ 'a' => 'b' ],
		];

		return $this->getMockForAbstractClass( Course_Progress_Abstract::class, $constructor_arguments );
	}
}
