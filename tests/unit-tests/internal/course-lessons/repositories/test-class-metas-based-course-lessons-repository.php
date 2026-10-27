<?php

namespace SenseiTest\Internal\Course_Lessons\Repositories;

use Sensei\Internal\Course_Lessons\Repositories\Metas_Based_Course_Lessons_Repository;

/**
 * Tests for Metas_Based_Course_Lessons_Repository.
 *
 * @covers \Sensei\Internal\Course_Lessons\Repositories\Metas_Based_Course_Lessons_Repository
 */
class Metas_Based_Course_Lessons_Repository_Test extends \WP_UnitTestCase {
	/**
	 * Sensei factory.
	 *
	 * @var \Sensei_Factory
	 */
	protected $factory;

	/**
	 * Repository under test.
	 *
	 * @var Metas_Based_Course_Lessons_Repository
	 */
	private $repository;

	public function setUp(): void {
		parent::setUp();
		$this->factory    = new \Sensei_Factory();
		$this->repository = new Metas_Based_Course_Lessons_Repository();
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	/**
	 * The course ID is returned as an integer.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Repositories\Metas_Based_Course_Lessons_Repository::get_course_id
	 */
	public function testGetCourseId_LessonAttachedToCourse_ReturnsTheCourseId(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );

		/* Act. */
		$actual = $this->repository->get_course_id( $lesson_id );

		/* Assert. */
		self::assertSame( $course_id, $actual );
	}

	/**
	 * An empty, zero or non-numeric course meta means no course.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Repositories\Metas_Based_Course_Lessons_Repository::get_course_id
	 * @dataProvider noValueMetasProvider
	 *
	 * @param mixed $meta_value Value stored in the meta.
	 */
	public function testGetCourseId_NoCourseStored_ReturnsNull( $meta_value ): void {
		/* Arrange. */
		$lesson_id = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $meta_value ) ) );

		/* Act. */
		$actual = $this->repository->get_course_id( $lesson_id );

		/* Assert. */
		self::assertNull( $actual );
	}

	/**
	 * The prerequisite lesson ID is returned.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Repositories\Metas_Based_Course_Lessons_Repository::get_lesson_prerequisite_id
	 */
	public function testGetLessonPrerequisiteId_PrerequisiteStored_ReturnsTheLessonId(): void {
		/* Arrange. */
		$prerequisite_id = $this->factory->lesson->create();
		$lesson_id       = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_prerequisite' => $prerequisite_id ) ) );

		/* Act. */
		$actual = $this->repository->get_lesson_prerequisite_id( $lesson_id );

		/* Assert. */
		self::assertSame( $prerequisite_id, $actual );
	}

	/**
	 * An empty, zero or non-numeric prerequisite meta means no prerequisite.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Repositories\Metas_Based_Course_Lessons_Repository::get_lesson_prerequisite_id
	 * @dataProvider noValueMetasProvider
	 *
	 * @param mixed $meta_value Value stored in the meta.
	 */
	public function testGetLessonPrerequisiteId_NoPrerequisiteStored_ReturnsNull( $meta_value ): void {
		/* Arrange. */
		$lesson_id = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_prerequisite' => $meta_value ) ) );

		/* Act. */
		$actual = $this->repository->get_lesson_prerequisite_id( $lesson_id );

		/* Assert. */
		self::assertNull( $actual );
	}

	/**
	 * Provides meta values that mean "no value": empty, zero, not numeric and not a scalar.
	 *
	 * @return array
	 */
	public function noValueMetasProvider(): array {
		return array(
			'empty string'       => array( '' ),
			'zero string'        => array( '0' ),
			'non-numeric string' => array( '1abc' ),
			'array'              => array( array( 1 ) ),
		);
	}
}
