<?php

/**
 * @group usage-tracking
 */
class Sensei_Usage_Tracking_Data_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Manual_Test_Helpers;

	private $course_ids;
	private $modules;

	/**
	 * Sets up the factory.
	 */
	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}//end setUp()

	public function tearDown() {
		parent::tearDown();
		$this->factory->tearDown();
	} // end tearDown

	private function setupCoursesAndModules() {
		$this->course_ids = $this->factory->post->create_many(
			3,
			array(
				'post_status' => 'publish',
				'post_type'   => 'course',
			)
		);

		$this->modules = array();

		for ( $i = 1; $i <= 3; $i++ ) {
			$this->modules[] = wp_insert_term( 'Module ' . $i, 'module' );
		}

		// Add modules to courses.
		wp_set_object_terms(
			$this->course_ids[0],
			array(
				$this->modules[0]['term_id'],
				$this->modules[1]['term_id'],
			),
			'module'
		);
		wp_set_object_terms(
			$this->course_ids[1],
			array(
				$this->modules[1]['term_id'],
				$this->modules[2]['term_id'],
			),
			'module'
		);
		wp_set_object_terms(
			$this->course_ids[2],
			array(
				$this->modules[0]['term_id'],
				$this->modules[1]['term_id'],
				$this->modules[2]['term_id'],
			),
			'module'
		);
	}

	// Create some published and unpublished lessons.
	private function createLessons() {
		$drafts    = $this->factory->post->create_many(
			2,
			array(
				'post_status' => 'draft',
				'post_type'   => 'lesson',
			)
		);
		$published = $this->factory->post->create_many(
			3,
			array(
				'post_status' => 'publish',
				'post_type'   => 'lesson',
			)
		);

		return array_merge( $drafts, $published );
	}

	/**
	 * Enroll users in some courses.
	 */
	private function enrollUsers() {
		// Create some users.
		$users = $this->factory->user->create_many( 5, array( 'role' => 'subscriber' ) );

		// Enroll users in some courses.
		foreach ( $users as $user ) {
			$this->factory->comment->create(
				array(
					'user_id'          => $user,
					'comment_post_ID'  => $this->course_ids[0],
					'comment_type'     => 'sensei_course_status',
					'comment_approved' => 'in-progress',
				)
			);

			$this->factory->comment->create(
				array(
					'user_id'          => $user,
					'comment_post_ID'  => $this->course_ids[1],
					'comment_type'     => 'sensei_course_status',
					'comment_approved' => 'complete',
				)
			);

			$this->factory->comment->create(
				array(
					'user_id'          => $user,
					'comment_post_ID'  => $this->course_ids[2],
					'comment_type'     => 'sensei_course_status',
					'comment_approved' => 'in-progress',
				)
			);
		}
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_quiz_stats
	 */
	public function testGetMinMaxQuestionsSimple() {
		$this->factory->get_course_with_lessons( array( 'question_count' => 0 ) );
		$this->factory->get_course_with_lessons( array( 'question_count' => 2 ) );
		$this->factory->get_course_with_lessons( array( 'question_count' => 7 ) );
		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( 2, $usage_data['questions_min'] );
		$this->assertEquals( 7, $usage_data['questions_max'] );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_quiz_stats
	 */
	public function testGetMinMaxQuestionsNoQuestions() {
		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( 0, $usage_data['quiz_total'] );
		$this->assertEquals( null, $usage_data['questions_min'] );
		$this->assertEquals( null, $usage_data['questions_max'] );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_quiz_stats
	 */
	public function testGetMinMaxQuestionsMinMaxSame() {
		$this->factory->get_course_with_lessons(
			array(
				'lesson_count'   => 1,
				'question_count' => 2,
			)
		);
		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( 1, $usage_data['quiz_total'] );
		$this->assertEquals( 2, $usage_data['questions_min'] );
		$this->assertEquals( 2, $usage_data['questions_max'] );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_quiz_stats
	 */
	public function testGetMinMaxQuestionsLessonVariance() {
		$this->factory->course->create();
		$this->factory->lesson->create();

		$this->factory->get_course_with_lessons(
			array(
				'lesson_count'   => 1,
				'question_count' => 0,
			)
		);
		$this->factory->get_course_with_lessons(
			array(
				'question_count' => array( 1, 2 ),
				'lesson_count'   => 2,
			)
		);
		$this->factory->get_course_with_lessons(
			array(
				'question_count' => array( 0, 1, 6 ),
				'lesson_count'   => 4, // Missing one should be 5 questions.
			)
		);
		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( 5, $usage_data['quiz_total'] );
		$this->assertEquals( 1, $usage_data['questions_min'] );
		$this->assertEquals( 6, $usage_data['questions_max'] );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_quiz_stats
	 */
	public function testGetMinMaxQuestionsDrafts() {
		$this->factory->get_course_with_lessons(
			array(
				'lesson_count'   => 1,
				'question_count' => 4,
				'lesson_args'    => array(
					'post_status' => 'draft',
				),
			)
		);
		$this->factory->get_course_with_lessons(
			array(
				'lesson_count'   => 1,
				'question_count' => 4,
				'course_args'    => array(
					'post_status' => 'draft',
				),
			)
		);
		$this->factory->get_course_with_lessons(
			array(
				'question_count' => array( 2, 3 ),
				'lesson_count'   => 2,
			)
		);
		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( 2, $usage_data['quiz_total'] );
		$this->assertEquals( 2, $usage_data['questions_min'] );
		$this->assertEquals( 3, $usage_data['questions_max'] );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_quiz_stats
	 * @covers Sensei_Usage_Tracking_Data::get_category_question_count
	 */
	public function testCategoryQuestionsNone() {
		$this->factory->get_course_with_lessons(
			array(
				'lesson_count'            => 1,
				'question_count'          => 2,
				'multiple_question_count' => 0,
			)
		);
		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( 0, $usage_data['category_questions'] );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_quiz_stats
	 * @covers Sensei_Usage_Tracking_Data::get_category_question_count
	 */
	public function testCategoryQuestionsSimple() {
		$this->factory->get_course_with_lessons(
			array(
				'lesson_count'            => 1,
				'question_count'          => 2,
				'multiple_question_count' => 1,
			)
		);
		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( 1, $usage_data['category_questions'] );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_quiz_stats
	 * @covers Sensei_Usage_Tracking_Data::get_category_question_count
	 */
	public function testCategoryQuestionsWithDraft() {
		$this->factory->get_course_with_lessons(
			array(
				'lesson_count'            => 2,
				'question_count'          => array( 0, 1 ),
				'multiple_question_count' => array( 1, 2 ),
			)
		);

		$this->factory->get_course_with_lessons(
			array(
				'lesson_count'            => 1,
				'question_count'          => 2,
				'multiple_question_count' => 1,
				'lesson_args'             => array(
					'post_status' => 'draft',
				),
			)
		);
		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( 3, $usage_data['category_questions'] );
	}

	/**
	 * Used as a data provider for quiz settings test.
	 *
	 * @return array
	 */
	public function quizSettingData() {
		return array(
			array(
				'quiz_num_questions',
				'_show_questions',
				array( '' ),
				array( 1, 2, 3 ),
			),
			array(
				'quiz_passmark',
				'_quiz_passmark',
				array( '', 0 ),
				array( 1, 20, 100 ),
			),
			array(
				'quiz_pass_required',
				'_pass_required',
				array( 'off', '' ),
				array( 'on' ),
			),
			array(
				'quiz_rand_questions',
				'_random_question_order',
				array( 'no', '' ),
				array( 'yes' ),
			),
			array(
				'quiz_auto_grade',
				'_quiz_grade_type',
				array( 'manual', '' ),
				array( 'auto' ),
			),
			array(
				'quiz_allow_retake',
				'_enable_quiz_reset',
				array( 'off', '' ),
				array( 'on' ),
			),
		);
	}

	/**
	 * @param string $stat_key
	 * @param string $meta_key
	 * @param array  $invalid_values
	 * @param array  $valid_values
	 *
	 * @dataProvider quizSettingData
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_quiz_stats
	 * @covers Sensei_Usage_Tracking_Data::get_quiz_setting_value_count
	 * @covers Sensei_Usage_Tracking_Data::get_quiz_setting_non_empty_count
	 */
	public function testQuizSettingCounts( $stat_key, $meta_key, $invalid_values, $valid_values ) {
		$default_values = array(
			'_pass_required'         => '',
			'_quiz_passmark'         => 70,
			'_show_questions'        => '',
			'_random_question_order' => 'no',
			'_quiz_grade_type'       => 'auto',
			'_enable_quiz_reset'     => '',
		);

		foreach ( $invalid_values as $value ) {
			$this->factory->get_course_with_lessons(
				array(
					'lesson_count'   => 1,
					'question_count' => 3,
					'quiz_args'      => array(
						'meta_input' => array_merge(
							$default_values,
							array(
								$meta_key => $value,
							)
						),
					),
				)
			);
		}

		foreach ( $valid_values as $value ) {
			$this->factory->get_course_with_lessons(
				array(
					'lesson_count'   => 1,
					'question_count' => 3,
					'quiz_args'      => array(
						'meta_input' => array_merge(
							$default_values,
							array(
								$meta_key => $value,
							)
						),
					),
				)
			);
			$this->factory->get_course_with_lessons(
				array(
					'lesson_count'   => 1,
					'question_count' => 3,
					'lesson_args'    => array(
						'post_status' => 'draft',
					),
					'quiz_args'      => array(
						'meta_input' => array_merge(
							$default_values,
							array(
								$meta_key => $value,
							)
						),
					),
				)
			);
		}

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();
		$this->assertEquals( count( $valid_values ), $usage_data[ $stat_key ] );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_quiz_stats
	 * @covers Sensei_Usage_Tracking_Data::get_quiz_setting_value_count
	 * @covers Sensei_Usage_Tracking_Data::get_quiz_setting_non_empty_count
	 */
	public function testQuizSettingCountsWithBadLesson() {
		$values           = array(
			'_pass_required'         => '',
			'_quiz_passmark'         => 70,
			'_show_questions'        => '',
			'_random_question_order' => 'no',
			'_quiz_grade_type'       => 'auto',
			'_enable_quiz_reset'     => 'on',
		);
		$course_lessons_a = $this->factory->get_course_with_lessons(
			array(
				'lesson_count'   => 1,
				'question_count' => 3,
				'quiz_args'      => array(
					'meta_input' => $values,
				),
			)
		);
		$course_lessons_b = $this->factory->get_course_with_lessons(
			array(
				'lesson_count'   => 1,
				'question_count' => 0,
				'quiz_args'      => array(
					'meta_input' => $values,
				),
			)
		);

		// Fake out! Replicate a data integrity issue that exists in Sensei.
		update_post_meta( $course_lessons_b['lesson_ids'][0], '_quiz_has_questions', '1' );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();
		$this->assertEquals( 1, $usage_data['quiz_allow_retake'] );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 */
	public function testGetUsageDataCourses() {
		$published = 4;

		// Create some published and unpublished courses.
		$this->factory->post->create_many(
			2,
			array(
				'post_status' => 'draft',
				'post_type'   => 'course',
			)
		);
		$this->factory->post->create_many(
			$published,
			array(
				'post_status' => 'publish',
				'post_type'   => 'course',
			)
		);

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'courses', $usage_data, 'Key' );
		$this->assertEquals( $published, $usage_data['courses'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_learner_count
	 */
	public function testGetUsageDataLearners() {
		$this->setupCoursesAndModules();

		// Create some users.
		$subscribers = $this->factory->user->create_many( 8, array( 'role' => 'subscriber' ) );
		$editors     = $this->factory->user->create_many( 3, array( 'role' => 'editor' ) );

		// Enroll some users in multiple courses.
		foreach ( $subscribers as $subscriber ) {
			$this->factory->comment->create(
				array(
					'user_id'          => $subscriber,
					'comment_post_ID'  => $this->course_ids[0],
					'comment_type'     => 'sensei_course_status',
					'comment_approved' => 'in-progress',
				)
			);

			$this->factory->comment->create(
				array(
					'user_id'          => $subscriber,
					'comment_post_ID'  => $this->course_ids[1],
					'comment_type'     => 'sensei_course_status',
					'comment_approved' => 'complete',
				)
			);
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
		$this->createLessons();

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'lessons', $usage_data, 'Key' );
		$this->assertEquals( 3, $usage_data['lessons'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_lesson_prerequisite_count
	 */
	public function testGetLessonPrerequisiteCount() {
		$lessons = $this->createLessons();

		// Make some lessons prerequisites of others.
		add_post_meta( $lessons[1], '_lesson_prerequisite', $lessons[0] );  // Draft
		add_post_meta( $lessons[2], '_lesson_prerequisite', $lessons[1] );  // Published
		add_post_meta( $lessons[3], '_lesson_prerequisite', $lessons[2] );  // Published

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'lesson_prereqs', $usage_data, 'Key' );
		$this->assertEquals( 2, $usage_data['lesson_prereqs'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_lesson_prerequisite_count
	 */
	public function testGetLessonPrerequisiteCountNoPrerequisites() {
		$lessons = $this->createLessons();

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'lesson_prereqs', $usage_data, 'Key' );
		$this->assertEquals( 0, $usage_data['lesson_prereqs'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_lesson_preview_count
	 */
	public function testGetLessonPreviewCount() {
		$lessons = $this->createLessons();

		// Turn on previews for some lessons.
		add_post_meta( $lessons[0], '_lesson_preview', 'preview' ); // Draft
		add_post_meta( $lessons[2], '_lesson_preview', 'preview' ); // Published
		add_post_meta( $lessons[3], '_lesson_preview', 'preview' ); // Published

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'lesson_previews', $usage_data, 'Key' );
		$this->assertEquals( 2, $usage_data['lesson_previews'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_lesson_preview_count
	 */
	public function testGetLessonPreviewCountNoPreviews() {
		$lessons = $this->createLessons();

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'lesson_previews', $usage_data, 'Key' );
		$this->assertEquals( 0, $usage_data['lesson_previews'], 'Count' );
	}

		/**
		 * @covers Sensei_Usage_Tracking_Data::get_usage_data
		 * @covers Sensei_Usage_Tracking_Data::get_lesson_module_count
		 */
	public function testGetLessonModuleCount() {
		$lessons = $this->createLessons();
		$terms   = $this->factory->term->create_many( 3, array( 'taxonomy' => 'module' ) );

		// Assign modules to some lessons.
		wp_set_object_terms( $lessons[0], $terms[0], 'module', false ); // Draft
		wp_set_object_terms( $lessons[2], $terms[1], 'module', false ); // Published
		wp_set_object_terms( $lessons[4], $terms[2], 'module', false ); // Published

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'lesson_modules', $usage_data, 'Key' );
		$this->assertEquals( 2, $usage_data['lesson_modules'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_lesson_module_count
	 */
	public function testGetLessonModuleCountNoModules() {
		$lessons = $this->createLessons();

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'lesson_modules', $usage_data, 'Key' );
		$this->assertEquals( 0, $usage_data['lesson_modules'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 */
	public function testGetUsageDataMessages() {
		$published = 10;

		// Create some published and unpublished messages.
		$this->factory->post->create_many(
			5,
			array(
				'post_status' => 'pending',
				'post_type'   => 'sensei_message',
			)
		);
		$this->factory->post->create_many(
			$published,
			array(
				'post_status' => 'publish',
				'post_type'   => 'sensei_message',
			)
		);

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
		$this->assertEquals( 0, $usage_data['modules'], 'Count' );
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
		$this->factory->post->create_many(
			12,
			array(
				'post_status' => 'private',
				'post_type'   => 'question',
			)
		);
		$this->factory->post->create_many(
			$published,
			array(
				'post_status' => 'publish',
				'post_type'   => 'question',
			)
		);

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'questions', $usage_data, 'Key' );
		$this->assertEquals( $published, $usage_data['questions'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_question_type_count
	 * @covers Sensei_Usage_Tracking_Data::get_question_type_key
	 */
	public function testGetUsageDataQuestionTypes() {
		// Create some questions.
		$questions = $this->factory->post->create_many(
			10,
			array(
				'post_type'   => 'question',
				'post_status' => 'publish',
			)
		);

		// Set the type of each question.
		wp_set_post_terms( $questions[0], array( 'multiple-choice' ), 'question-type' );
		wp_set_post_terms( $questions[1], array( 'multi-line' ), 'question-type' );
		wp_set_post_terms( $questions[2], array( 'multiple-choice' ), 'question-type' );
		wp_set_post_terms( $questions[3], array( 'multi-line' ), 'question-type' );
		wp_set_post_terms( $questions[4], array( 'multiple-choice' ), 'question-type' );
		wp_set_post_terms( $questions[5], array( 'gap-fill' ), 'question-type' );
		wp_set_post_terms( $questions[6], array( 'single-line' ), 'question-type' );
		wp_set_post_terms( $questions[7], array( 'boolean' ), 'question-type' );
		wp_set_post_terms( $questions[8], array( 'multi-line' ), 'question-type' );
		wp_set_post_terms( $questions[9], array( 'boolean' ), 'question-type' );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'question_multiple_choice', $usage_data, 'Multiple choice key' );
		$this->assertArrayHasKey( 'question_gap_fill', $usage_data, 'Gap fill key' );
		$this->assertArrayHasKey( 'question_boolean', $usage_data, 'Boolean key' );
		$this->assertArrayHasKey( 'question_single_line', $usage_data, 'Single line key' );
		$this->assertArrayHasKey( 'question_multi_line', $usage_data, 'Multi line key' );
		$this->assertArrayHasKey( 'question_file_upload', $usage_data, 'File upload key' );

		$this->assertEquals( 3, $usage_data['question_multiple_choice'], 'Multiple choice count' );
		$this->assertEquals( 1, $usage_data['question_gap_fill'], 'Gap fill count' );
		$this->assertEquals( 2, $usage_data['question_boolean'], 'Boolean count' );
		$this->assertEquals( 1, $usage_data['question_single_line'], 'Single line count' );
		$this->assertEquals( 3, $usage_data['question_multi_line'], 'Multi line count' );
		$this->assertEquals( 0, $usage_data['question_file_upload'], 'File upload count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_question_type_count
	 */
	public function testGetUsageDataQuestionTypesInvalidType() {
		// Create a question.
		$question = $this->factory->post->create(
			array(
				'post_type'   => 'question',
				'post_status' => 'publish',
			)
		);

		// Set the question to use an invalid type.
		wp_set_post_terms( $question, array( 'automattic' ), 'question-type' );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayNotHasKey( 'question_automattic', $usage_data, 'Multiple choice key' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_question_media_count
	 */
	public function testGetUsageDataQuestionMediaCount() {
		// Create some questions.
		$questions = $this->factory->post->create_many(
			5,
			array(
				'post_type'   => 'question',
				'post_status' => 'publish',
			)
		);
		// Create some media.
		$media = $this->factory->attachment->create_many(
			3,
			array(
				'post_type'   => 'question',
				'post_status' => 'publish',
			)
		);

		// Attach media to some questions.
		add_post_meta( $questions[0], '_question_media', $media[0] );
		add_post_meta( $questions[1], '_question_media', $media[1] );
		add_post_meta( $questions[2], '_question_media', $media[2] );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'question_media', $usage_data, 'Key' );
		$this->assertEquals( 3, $usage_data['question_media'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_question_media_count
	 */
	public function testGetUsageDataQuestionMediaCountNoMedia() {
		// Create some questions, but don't attach any media.
		$questions = $this->factory->post->create_many(
			5,
			array(
				'post_type'   => 'question',
				'post_status' => 'publish',
			)
		);

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'question_media', $usage_data, 'Key' );
		$this->assertEquals( 0, $usage_data['question_media'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_random_order_count
	 */
	public function testGetRandomOrderCount() {
		// Create some questions.
		$questions = $this->factory->post->create_many(
			3,
			array(
				'post_type'   => 'question',
				'post_status' => 'publish',
			)
		);

		// Set the type of each question to be multiple choice.
		wp_set_post_terms( $questions[0], array( 'multiple-choice' ), 'question-type' );
		wp_set_post_terms( $questions[1], array( 'multiple-choice' ), 'question-type' );
		wp_set_post_terms( $questions[2], array( 'multiple-choice' ), 'question-type' );

		// Set the random answer order.
		add_post_meta( $questions[0], '_random_order', 'yes' );
		add_post_meta( $questions[1], '_random_order', 'no' );
		add_post_meta( $questions[2], '_random_order', 'yes' );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'question_random_order', $usage_data, 'Key' );
		$this->assertEquals( 2, $usage_data['question_random_order'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_random_order_count
	 */
	public function testGetRandomOrderCountMultipleChoiceOnly() {
		// Create some questions.
		$questions = $this->factory->post->create_many(
			6,
			array(
				'post_type'   => 'question',
				'post_status' => 'publish',
			)
		);

		// Create a question of each type.
		wp_set_post_terms( $questions[0], array( 'multiple-choice' ), 'question-type' );
		wp_set_post_terms( $questions[1], array( 'multi-line' ), 'question-type' );
		wp_set_post_terms( $questions[2], array( 'single-line' ), 'question-type' );
		wp_set_post_terms( $questions[3], array( 'boolean' ), 'question-type' );
		wp_set_post_terms( $questions[4], array( 'file-upload' ), 'question-type' );
		wp_set_post_terms( $questions[5], array( 'gap-fill' ), 'question-type' );

		// Turn on random answer order for non-multiple choice questions.
		add_post_meta( $questions[0], '_random_order', 'no' );
		add_post_meta( $questions[1], '_random_order', 'yes' );
		add_post_meta( $questions[2], '_random_order', 'yes' );
		add_post_meta( $questions[3], '_random_order', 'yes' );
		add_post_meta( $questions[4], '_random_order', 'yes' );
		add_post_meta( $questions[5], '_random_order', 'yes' );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'question_random_order', $usage_data, 'Key' );
		$this->assertEquals( 0, $usage_data['question_random_order'], 'Count' );
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

	/**
	 * Count of active courses.
	 *
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_course_active_count
	 */
	public function testGetCourseActiveCount() {
		$this->setupCoursesAndModules();
		$this->enrollUsers();

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'course_active', $usage_data, 'Key' );
		$this->assertEquals( 10, $usage_data['course_active'], 'Count' );
	}

	/**
	 * Count of completed courses.
	 *
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_course_completed_count
	 */
	public function testGetCourseCompletedCount() {
		$this->setupCoursesAndModules();
		$this->enrollUsers();

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'course_completed', $usage_data, 'Key' );
		$this->assertEquals( 5, $usage_data['course_completed'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_courses_with_video_count
	 */
	public function testGetCoursesWithVideoCount() {
		$with_video = 4;

		$course_ids_without_video = $this->factory->post->create_many(
			3,
			array(
				'post_type' => 'course',
			)
		);
		$course_ids_with_video    = $this->factory->post->create_many(
			$with_video,
			array(
				'post_type' => 'course',
			)
		);

		// Set video on courses
		update_post_meta( $course_ids_with_video[0], '_course_video_embed', '<iframe src="video.com"></iframe>' );
		update_post_meta( $course_ids_with_video[1], '_course_video_embed', '<iframe></iframe>' );
		update_post_meta( $course_ids_with_video[2], '_course_video_embed', 'blah' );
		update_post_meta( $course_ids_with_video[3], '_course_video_embed', 'blah with spaces' );

		// Set some non-null values on the others
		update_post_meta( $course_ids_without_video[0], '_course_video_embed', '' );
		update_post_meta( $course_ids_without_video[1], '_course_video_embed', '   ' );
		update_post_meta( $course_ids_without_video[2], '_course_video_embed', "\t\n" );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'course_videos', $usage_data, 'Key' );
		$this->assertEquals( $with_video, $usage_data['course_videos'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_courses_with_disabled_notification_count
	 */
	public function testGetCoursesWithDisabledNotificationCount() {
		$with_disabled_notification = 2;

		$course_ids_without_disabled = $this->factory->post->create_many(
			3,
			array(
				'post_type' => 'course',
			)
		);
		$course_ids_with_disabled    = $this->factory->post->create_many(
			$with_disabled_notification,
			array(
				'post_type' => 'course',
			)
		);

		// Disable notifications
		foreach ( $course_ids_with_disabled as $course_id ) {
			update_post_meta( $course_id, 'disable_notification', true );
		}

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'course_no_notifications', $usage_data, 'Key' );
		$this->assertEquals( $with_disabled_notification, $usage_data['course_no_notifications'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_courses_with_prerequisite count
	 */
	public function testGetCoursesWithPrerequisiteCount() {
		$with_prereq = 2;

		$course_ids_without_prereq = $this->factory->post->create_many(
			3,
			array(
				'post_type' => 'course',
			)
		);
		$course_ids_with_prereq    = $this->factory->post->create_many(
			$with_prereq,
			array(
				'post_type' => 'course',
			)
		);

		// Set prerequisite on courses
		foreach ( $course_ids_with_prereq as $course_id ) {
			update_post_meta( $course_id, '_course_prerequisite', $course_ids_without_prereq[0] );
		}

		// Another value for no prereq
		update_post_meta( $course_ids_without_prereq[1], '_course_prerequisite', '0' );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'course_prereqs', $usage_data, 'Key' );
		$this->assertEquals( $with_prereq, $usage_data['course_prereqs'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_course_featured_count
	 */
	public function testGetFeaturedCoursesCount() {
		$featured = 2;

		$non_featured_course_ids = $this->factory->post->create_many(
			3,
			array(
				'post_type' => 'course',
			)
		);
		$featured_course_ids     = $this->factory->post->create_many(
			$featured,
			array(
				'post_type' => 'course',
			)
		);

		// Set courses to featured
		foreach ( $featured_course_ids as $course_id ) {
			update_post_meta( $course_id, '_course_featured', 'featured' );
		}

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'course_featured', $usage_data, 'Key' );
		$this->assertEquals( $featured, $usage_data['course_featured'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_course_enrolments
	 */
	public function testGetCourseEnrolments() {
		$enrolments = 5;

		// Create course and users.
		$course_id   = $this->factory->post->create(
			array(
				'post_status' => 'publish',
				'post_type'   => 'course',
			)
		);
		$subscribers = $this->factory->user->create_many( $enrolments, array( 'role' => 'subscriber' ) );

		// Enroll users in course.
		foreach ( $subscribers as $subscriber ) {
			$this->manuallyEnrolStudentInCourse( $subscriber, $course_id );
		}

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'enrolments', $usage_data, 'Key' );
		$this->assertEquals( $enrolments, $usage_data['enrolments'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_course_enrolments
	 */
	public function testGetCourseEnrolmentsNoAdminUsers() {
		$enrolments = 3;

		// Create course and users.
		$course_id      = $this->factory->post->create(
			array(
				'post_status' => 'publish',
				'post_type'   => 'course',
			)
		);
		$administrators = $this->factory->user->create_many( 2, array( 'role' => 'administrator' ) );
		$subscribers    = $this->factory->user->create_many( $enrolments, array( 'role' => 'subscriber' ) );

		// Enroll users in course.
		foreach ( array_merge( $administrators, $subscribers ) as $user ) {
			$this->manuallyEnrolStudentInCourse( $user, $course_id );
		}

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( $enrolments, $usage_data['enrolments'] );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_course_enrolments
	 */
	public function testGetCourseEnrolmentsPublishedCourses() {
		// Create course and users.
		$course_id   = $this->factory->post->create(
			array(
				'post_status' => 'draft',
				'post_type'   => 'course',
			)
		);
		$subscribers = $this->factory->user->create_many( 5, array( 'role' => 'subscriber' ) );

		// Enroll users in course.
		foreach ( $subscribers as $subscriber ) {
			$this->manuallyEnrolStudentInCourse( $subscriber, $course_id );
		}

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( 0, $usage_data['enrolments'] );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_last_course_enrolment
	 */
	public function testGetLastCourseEnrolment() {
		$last_enrolment_date = '2018-11-09 09:48:05';

		// Create course and users.
		$course_id   = $this->factory->post->create(
			array(
				'post_status' => 'publish',
				'post_type'   => 'course',
			)
		);
		$subscribers = $this->factory->user->create_many( 3, array( 'role' => 'subscriber' ) );
		$comment_ids = array();

		// Enroll users in course.
		foreach ( $subscribers as $subscriber ) {
			$this->manuallyEnrolStudentInCourse( $subscriber, $course_id );
		}

		update_comment_meta( $comment_ids[0], 'start', '2017-05-23 10:59:00' );
		update_comment_meta( $comment_ids[1], 'start', $last_enrolment_date );
		update_comment_meta( $comment_ids[2], 'start', '2018-10-01 13:25:25' );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'enrolment_last', $usage_data, 'Key' );
		$this->assertEquals( $last_enrolment_date, $usage_data['enrolment_last'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_last_course_enrolment
	 */
	public function testGetLastCourseEnrolmentNoAdminUsers() {
		$last_enrolment_date = '2017-05-23 10:59:00';

		// Create course and users.
		$course_id      = $this->factory->post->create(
			array(
				'post_status' => 'publish',
				'post_type'   => 'course',
			)
		);
		$subscribers    = $this->factory->user->create_many( 1, array( 'role' => 'subscriber' ) );
		$administrators = $this->factory->user->create_many( 2, array( 'role' => 'administrator' ) );
		$comment_ids    = array();

		// Enroll users in course.
		foreach ( array_merge( $subscribers, $administrators ) as $user ) {
			$comment_ids[] = $this->factory->comment->create(
				array(
					'user_id'         => $user,
					'comment_post_ID' => $course_id,
					'comment_type'    => 'sensei_course_status',
				)
			);
		}

		update_comment_meta( $comment_ids[0], 'start', $last_enrolment_date ); // Subscriber.
		update_comment_meta( $comment_ids[1], 'start', '2018-11-09 09:48:05' ); // Admin.
		update_comment_meta( $comment_ids[2], 'start', '2018-10-01 13:25:25' ); // Admin.

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( $last_enrolment_date, $usage_data['enrolment_last'] );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_last_course_enrolment
	 */
	public function testGetLastCourseEnrolmentPublishedCourses() {
		// Create course and users.
		$course_id   = $this->factory->post->create(
			array(
				'post_status' => 'draft',
				'post_type'   => 'course',
			)
		);
		$subscribers = $this->factory->user->create_many( 3, array( 'role' => 'subscriber' ) );
		$comment_ids = array();

		// Enroll users in course.
		foreach ( $subscribers as $subscriber ) {
			$comment_ids[] = $this->factory->comment->create(
				array(
					'user_id'         => $subscriber,
					'comment_post_ID' => $course_id,
					'comment_type'    => 'sensei_course_status',
				)
			);
		}

		update_comment_meta( $comment_ids[0], 'start', '2017-05-23 10:59:00' );
		update_comment_meta( $comment_ids[1], 'start', '2018-11-09 09:48:05' );
		update_comment_meta( $comment_ids[2], 'start', '2018-10-01 13:25:25' );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( 'N/A', $usage_data['enrolment_last'] );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_first_course_enrolment
	 */
	public function testGetFirstCourseEnrolment() {
		$first_enrolment_date = '2017-04-13 19:07:43';

		// Create course and users.
		$course_id   = $this->factory->post->create(
			array(
				'post_status' => 'publish',
				'post_type'   => 'course',
			)
		);
		$subscribers = $this->factory->user->create_many( 3, array( 'role' => 'subscriber' ) );
		$comment_ids = array();

		// Enroll users in course.
		foreach ( $subscribers as $subscriber ) {
			$comment_ids[] = $this->factory->comment->create(
				array(
					'user_id'         => $subscriber,
					'comment_post_ID' => $course_id,
					'comment_type'    => 'sensei_course_status',
				)
			);
		}

		update_comment_meta( $comment_ids[0], 'start', '2017-05-23 10:59:00' );
		update_comment_meta( $comment_ids[1], 'start', $first_enrolment_date );
		update_comment_meta( $comment_ids[2], 'start', '2018-10-01 13:25:25' );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'enrolment_first', $usage_data, 'Key' );
		$this->assertEquals( $first_enrolment_date, $usage_data['enrolment_first'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_first_course_enrolment
	 */
	public function testGetFirstCourseEnrolmentNoAdminUsers() {
		$first_enrolment_date = '2018-11-09 09:48:05';

		// Create course and users.
		$course_id      = $this->factory->post->create(
			array(
				'post_status' => 'publish',
				'post_type'   => 'course',
			)
		);
		$administrators = $this->factory->user->create_many( 2, array( 'role' => 'administrator' ) );
		$subscribers    = $this->factory->user->create_many( 1, array( 'role' => 'subscriber' ) );
		$comment_ids    = array();

		// Enroll users in course.
		foreach ( array_merge( $administrators, $subscribers ) as $user ) {
			$comment_ids[] = $this->factory->comment->create(
				array(
					'user_id'         => $user,
					'comment_post_ID' => $course_id,
					'comment_type'    => 'sensei_course_status',
				)
			);
		}

		update_comment_meta( $comment_ids[0], 'start', '2017-05-23 10:59:00' ); // Admin.
		update_comment_meta( $comment_ids[1], 'start', '2018-10-01 13:25:25' ); // Admin.
		update_comment_meta( $comment_ids[2], 'start', $first_enrolment_date ); // Subscriber.

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( $first_enrolment_date, $usage_data['enrolment_first'] );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_first_course_enrolment
	 */
	public function testGetFirstCourseEnrolmentPublishedCourses() {
		// Create course and users.
		$course_id   = $this->factory->post->create(
			array(
				'post_status' => 'draft',
				'post_type'   => 'course',
			)
		);
		$subscribers = $this->factory->user->create_many( 3, array( 'role' => 'subscriber' ) );
		$comment_ids = array();

		// Enroll users in course.
		foreach ( $subscribers as $subscriber ) {
			$comment_ids[] = $this->factory->comment->create(
				array(
					'user_id'         => $subscriber,
					'comment_post_ID' => $course_id,
					'comment_type'    => 'sensei_course_status',
				)
			);
		}

		update_comment_meta( $comment_ids[0], 'start', '2017-05-23 10:59:00' );
		update_comment_meta( $comment_ids[1], 'start', '2018-11-09 09:48:05' );
		update_comment_meta( $comment_ids[2], 'start', '2018-10-01 13:25:25' );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertEquals( 'N/A', $usage_data['enrolment_first'] );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_lesson_has_length_count
	 */
	public function testGetLessonHasLengthCount() {
		$lessons_with_length = 3;

		// Create some lessons
		$lesson_without_length_ids = $this->factory->post->create_many(
			2,
			array(
				'post_type' => 'lesson',
			)
		);
		$lesson_with_length_ids    = $this->factory->post->create_many(
			$lessons_with_length,
			array(
				'post_type' => 'lesson',
			)
		);

		// Set lesson length
		foreach ( $lesson_with_length_ids as $lesson_id ) {
			update_post_meta( $lesson_id, '_lesson_length', '15' );
		}
		update_post_meta( $lesson_without_length_ids[0], '_lesson_length', '' );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'lesson_length', $usage_data, 'Key' );
		$this->assertEquals( $lessons_with_length, $usage_data['lesson_length'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_lesson_with_complexity_count
	 */
	public function testGetLessonWithComplexityCount() {
		$lessons_with_complexity = 3;

		// Create some lessons
		$lesson_without_complexity_ids = $this->factory->post->create_many(
			2,
			array(
				'post_type' => 'lesson',
			)
		);
		$lesson_with_complexity_ids    = $this->factory->post->create_many(
			$lessons_with_complexity,
			array(
				'post_type' => 'lesson',
			)
		);

		// Set lesson complexity
		foreach ( $lesson_with_complexity_ids as $lesson_id ) {
			update_post_meta( $lesson_id, '_lesson_complexity', 'Hard' );
		}
		update_post_meta( $lesson_without_complexity_ids[0], '_lesson_complexity', '' );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'lesson_complexity', $usage_data, 'Key' );
		$this->assertEquals( $lessons_with_complexity, $usage_data['lesson_complexity'], 'Count' );
	}

	/**
	 * Tests getting lessons with video count.
	 *
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_lesson_with_video_count
	 */
	public function testGetLessonWithVideoCount() {
		$lessons_with_video = 4;

		// Create some lessons
		$lesson_without_video_ids = $this->factory->post->create_many(
			4,
			array(
				'post_type' => 'lesson',
			)
		);
		$lesson_with_video_ids    = $this->factory->post->create_many(
			$lessons_with_video,
			array(
				'post_type' => 'lesson',
			)
		);

		// Set lesson videos
		update_post_meta( $lesson_with_video_ids[0], '_lesson_video_embed', '<iframe src="http://example.com/video"></iframe>' );
		update_post_meta( $lesson_with_video_ids[1], '_lesson_video_embed', '<iframe> </iframe>' );
		update_post_meta( $lesson_with_video_ids[2], '_lesson_video_embed', 'blah' );
		update_post_meta( $lesson_with_video_ids[3], '_lesson_video_embed', 'blah with spaces' );
		update_post_meta( $lesson_without_video_ids[0], '_lesson_video_embed', '' );
		update_post_meta( $lesson_without_video_ids[1], '_lesson_video_embed', '    ' );
		update_post_meta( $lesson_without_video_ids[2], '_lesson_video_embed', "\t\n" );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'lesson_videos', $usage_data, 'Key' );
		$this->assertEquals( $lessons_with_video, $usage_data['lesson_videos'], 'Count' );
	}
}
