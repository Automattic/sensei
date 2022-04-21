<?php

class Sensei_Db_Query_Learners_Test extends WP_UnitTestCase {
	public function setUp() {
		parent::setup();
		$this->factory = new Sensei_Factory();
	}

	public function tearDown() {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testGetAll_EmptyArgsGiven_ReturnsMatchingResult() {
		$user1_id = $this->factory->user->create( [ 'user_email' => 'user1@example.com' ] );
		$user2_id = $this->factory->user->create( [ 'user_email' => 'user2@example.com' ] );
		$user3_id = $this->factory->user->create( [ 'user_email' => 'user3@example.com' ] );

		$course1_id = $this->factory->course->create();
		$course2_id = $this->factory->course->create();

		Sensei_Utils::update_course_status( $user1_id, $course1_id );
		Sensei_Utils::update_course_status( $user1_id, $course2_id, 'complete' );
		Sensei_Utils::update_course_status( $user2_id, $course1_id, 'complete' );
		Sensei_Utils::update_course_status( $user3_id, $course2_id );

		$query = new Sensei_Db_Query_Learners( [] );

		$learners = $query->get_all();

		$expected = [
			[
				'user_email' => 'admin@example.org', // admin
			],
			[
				'user_email' => 'user1@example.com',
			],
			[
				'user_email' => 'user2@example.com',
			],
			[
				'user_email' => 'user3@example.com',
			],
		];

		self::assertSame( $expected, $this->exportLearners( $learners ) );
	}

	public function testGetAll_CourseIdGiven_ReturnsMatchingResult() {
		$user1_id = $this->factory->user->create( [ 'user_email' => 'user1@example.com' ] );
		$user2_id = $this->factory->user->create( [ 'user_email' => 'user2@example.com' ] );
		$user3_id = $this->factory->user->create( [ 'user_email' => 'user3@example.com' ] );

		$course1_id = $this->factory->course->create();
		$course2_id = $this->factory->course->create();

		Sensei_Utils::update_course_status( $user1_id, $course1_id );
		Sensei_Utils::update_course_status( $user1_id, $course2_id, 'complete' );
		Sensei_Utils::update_course_status( $user2_id, $course1_id, 'complete' );
		Sensei_Utils::update_course_status( $user3_id, $course2_id );

		$args  = [
			'filter_by_course_id' => $course1_id,
		];
		$query = new Sensei_Db_Query_Learners( $args );

		$learners = $query->get_all();

		$expected = [
			[
				'user_email' => 'user1@example.com',
			],
			[
				'user_email' => 'user2@example.com',
			],
		];

		self::assertSame( $expected, $this->exportLearners( $learners ) );
	}

	public function testGetAll_CourseIdAndPerPageGiven_ReturnsMatchingResult() {
		$user1_id = $this->factory->user->create( [ 'user_email' => 'user1@example.com' ] );
		$user2_id = $this->factory->user->create( [ 'user_email' => 'user2@example.com' ] );
		$user3_id = $this->factory->user->create( [ 'user_email' => 'user3@example.com' ] );

		$course1_id = $this->factory->course->create();
		$course2_id = $this->factory->course->create();

		Sensei_Utils::update_course_status( $user1_id, $course1_id );
		Sensei_Utils::update_course_status( $user1_id, $course2_id, 'complete' );
		Sensei_Utils::update_course_status( $user2_id, $course1_id, 'complete' );
		Sensei_Utils::update_course_status( $user3_id, $course2_id );

		$args  = [
			'filter_by_course_id' => $course1_id,
			'per_page'            => 1,
		];
		$query = new Sensei_Db_Query_Learners( $args );

		$learners = $query->get_all();

		$expected = [
			[
				'user_email' => 'user1@example.com',
			],
		];

		self::assertSame( $expected, $this->exportLearners( $learners ) );
	}

	public function testGetAll_CourseIdExcluded_ReturnsMatchingResult() {
		$user1_id = $this->factory->user->create( [ 'user_email' => 'user1@example.com' ] );
		$user2_id = $this->factory->user->create( [ 'user_email' => 'user2@example.com' ] );
		$user3_id = $this->factory->user->create( [ 'user_email' => 'user3@example.com' ] );

		$course1_id = $this->factory->course->create();
		$course2_id = $this->factory->course->create();

		Sensei_Utils::user_start_course( $user1_id, $course1_id );
		Sensei_Utils::user_start_course( $user1_id, $course2_id, 'complete' );
		Sensei_Utils::user_start_course( $user2_id, $course1_id, 'complete' );
		Sensei_Utils::user_start_course( $user3_id, $course2_id );

		$args  = [
			'filter_by_course_id' => $course1_id,
			'filter_type'         => 'exc',
		];
		$query = new Sensei_Db_Query_Learners( $args );

		$learners = $query->get_all();

		$expected = [
			[
				'user_email' => 'user1@example.com',
			],
			[
				'user_email' => 'user3@example.com',
			],
		];

		self::assertSame( $expected, $this->exportLearners( $learners ) );
	}

	private function exportLearners( array $learners ): array {
		$ret = [];

		foreach ( $learners as $learner ) {
			$ret[] = [
				'user_email' => $learner->user_email,
			];
		}

		return $ret;
	}
}
