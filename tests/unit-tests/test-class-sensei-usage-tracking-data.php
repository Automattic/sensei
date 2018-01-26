<?php

class Sensei_Usage_Tracking_Data_Test extends WP_UnitTestCase {
	private $course_ids;
	private $modules;

	private function setupCoursesAndModules() {
		$this->course_ids = $this->factory->post->create_many( 3, array(
			'post_status' => 'publish',
			'post_type' => 'course',
		) );

		$this->modules = array();

		for ( $i = 1; $i <= 3; $i++ ) {
			$this->modules[] = wp_insert_term( 'Module ' . $i, 'module' );
		}

		// Add modules to courses.
		wp_set_object_terms( $this->course_ids[0],
			array(
				$this->modules[0]['term_id'],
				$this->modules[1]['term_id'],
			),
			'module'
		);
		wp_set_object_terms( $this->course_ids[1],
			array(
				$this->modules[1]['term_id'],
				$this->modules[2]['term_id'],
			),
			'module'
		);
		wp_set_object_terms( $this->course_ids[2],
			array(
				$this->modules[0]['term_id'],
				$this->modules[1]['term_id'],
				$this->modules[2]['term_id'],
			),
			'module'
		);
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 */
	public function testGetUsageDataCourses() {
		$published = 4;

		// Create some published and unpublished courses.
		$this->factory->post->create_many( 2, array(
			'post_status' => 'draft',
			'post_type' => 'course',
		) );
		$this->factory->post->create_many( $published, array(
			'post_status' => 'publish',
			'post_type' => 'course',
		) );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'courses', $usage_data, 'Key' );
		$this->assertEquals( $published, $usage_data['courses'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_learner_count
	 */
	public function testGetUsageDataLearners() {
		// Create some users.
		$subscribers = $this->factory->user->create_many( 8, array( 'role' => 'subscriber' ) );
		$editors = $this->factory->user->create_many( 3, array( 'role' => 'editor' ) );

		// Enroll some users in multiple courses.
		foreach( $subscribers as $subscriber ) {
			$this->factory->comment->create( array(
				'user_id' => $subscriber,
				'comment_post_ID' => $this->course_ids[0],
				'comment_type' => 'sensei_course_status',
			) );

			$this->factory->comment->create( array(
				'user_id' => $subscriber,
				'comment_post_ID' => $this->course_ids[1],
				'comment_type' => 'sensei_course_status',
			) );
		}

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		// Despite being enrolled in multiple courses, a learner is only counted once.
		$this->assertArrayHasKey( 'learners', $usage_data, 'Key' );
		$this->assertEquals( count( $subscribers ), $usage_data['learners'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 */
	public function testGetUsageDataLessons() {
		$published = 3;

		// Create some published and unpublished lessons.
		$this->factory->post->create_many( 2, array(
			'post_status' => 'draft',
			'post_type' => 'lesson',
		) );
		$this->factory->post->create_many( $published, array(
			'post_status' => 'publish',
			'post_type' => 'lesson',
		) );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'lessons', $usage_data, 'Key' );
		$this->assertEquals( $published, $usage_data['lessons'], 'Count' );
	}

		/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_lesson_prerequisite_count
	 */
	public function testGetLessonPrerequisiteCount() {
		// Create some lessons.
		$lessons = $this->factory->post->create_many( 5, array(
			'post_status' => 'publish',
			'post_type' => 'lesson',
		) );

		// Make some lessons prerequisites of others.
		add_post_meta( $lessons[1], '_lesson_prerequisite', $lessons[0] );
		add_post_meta( $lessons[2], '_lesson_prerequisite', $lessons[1] );
		add_post_meta( $lessons[3], '_lesson_prerequisite', $lessons[2] );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'lesson_prereqs', $usage_data, 'Key' );
		$this->assertEquals( 3, $usage_data['lesson_prereqs'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_lesson_prerequisite_count
	 */
	public function testGetLessonPrerequisiteCountNoPrerequisites() {
		// Create some lessons, but don't add any prerequisites.
		$lessons = $this->factory->post->create_many( 5, array(
			'post_status' => 'publish',
			'post_type' => 'lesson',
		) );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'lesson_prereqs', $usage_data, 'Key' );
		$this->assertEquals( 0, $usage_data['lesson_prereqs'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 */
	public function testGetUsageDataMessages() {
		$published = 10;

		// Create some published and unpublished messages.
		$this->factory->post->create_many( 5, array(
			'post_status' => 'pending',
			'post_type' => 'sensei_message',
		) );
		$this->factory->post->create_many( $published, array(
			'post_status' => 'publish',
			'post_type' => 'sensei_message',
		) );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'messages', $usage_data, 'Key' );
		$this->assertEquals( $published, $usage_data['messages'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 */
	public function testGetUsageDataModules() {
		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'modules', $usage_data, 'Key' );
		$this->assertEquals( count( $this->modules ), $usage_data['modules'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_max_module_count
	 */
	public function testGetUsageDataMaxModules() {
		$this->setupCoursesAndModules();

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'modules_max', $usage_data, 'Key' );
		$this->assertEquals( 3, $usage_data['modules_max'], 'Count' ); // Course 2 has 3 modules.
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_min_module_count
	 */
	public function testGetUsageDataMinModules() {
		$this->setupCoursesAndModules();

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'modules_min', $usage_data, 'Key' );
		$this->assertEquals( 2, $usage_data['modules_min'], 'Count' ); // Courses 1 and 2 have 2 modules.
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 */
	public function testGetUsageDataQuestions() {
		$published = 15;

		// Create some published and unpublished questions.
		$this->factory->post->create_many( 12, array(
			'post_status' => 'private',
			'post_type' => 'question',
		) );
		$this->factory->post->create_many( $published, array(
			'post_status' => 'publish',
			'post_type' => 'question',
		) );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'questions', $usage_data, 'Key' );
		$this->assertEquals( $published, $usage_data['questions'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_teacher_count
	 */
	public function testGetUsageDataTeachers() {
		$teachers = 3;

		// Create some users and teachers.
		$this->factory->user->create_many( 10, array( 'role' => 'subscriber' ) );
		$this->factory->user->create_many( $teachers, array( 'role' => 'teacher' ) );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'teachers', $usage_data, 'Key' );
		$this->assertEquals( $teachers, $usage_data['teachers'], 'Count' );
	}
}
