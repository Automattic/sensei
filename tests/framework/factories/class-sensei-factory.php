<?php

/**
 * Class Sensei Factory
 *
 * This class takes care of creating testing data for the Sensei Unit tests
 *
 * @since 1.7.4
 */
class Sensei_Factory extends WP_UnitTest_Factory {
	/**
	 * All the course ids generated for the basic test setup.
	 * @since 1.8.0
	 * @var array $basic_test_course_ids
	 */
	protected $basic_test_course_ids = array();

	/**
	 * Module term used in basic test setup.
	 * @var array $basic_test_module_term
	 */
	protected $basic_test_module_term;

	/**
	 * All the lessons ids used in the basic test setup.
	 * @var array $basic_test_lesson_ids
	 */
	protected $basic_test_lesson_ids;

	/**
	 * Only those lessons IDs that are not associated with a module in the basic test setup.
	 * @var array $basic_test_other_lesson_ids
	 */
	protected $basic_test_other_lesson_ids;

	/**
	 * Question IDs for basic test setup.
	 * @var array $lesson_ids
	 */
	protected $basic_test_question_ids;

	/**
	 * @var WP_UnitTest_Factory_For_Course
	 */
	public $course;

	/**
	 * @var WP_UnitTest_Factory_For_Lesson
	 */
	public $lesson;

	/**
	 * @var WP_UnitTest_Factory_For_Quiz
	 */
	public $quiz;

	/**
	 * @var WP_UnitTest_Factory_For_Question
	 */
	public $question;

	/**
	 * @var WP_UnitTest_Factory_For_Module
	 */
	public $module;

	/**
	 * constructor function
	 *
	 * This sets up some basic demo data
	 */
	public function __construct() {
		parent::__construct();
		require_once dirname( __FILE__ ) . '/class-wp-unittest-factory-for-course.php';
		require_once dirname( __FILE__ ) . '/class-wp-unittest-factory-for-quiz.php';
		require_once dirname( __FILE__ ) . '/class-wp-unittest-factory-for-question.php';
		require_once dirname( __FILE__ ) . '/class-wp-unittest-factory-for-lesson.php';
		require_once dirname( __FILE__ ) . '/class-wp-unittest-factory-for-module.php';

		$this->course   = new WP_UnitTest_Factory_For_Course( $this );
		$this->lesson   = new WP_UnitTest_Factory_For_Lesson( $this );
		$this->quiz     = new WP_UnitTest_Factory_For_Quiz( $this );
		$this->question = new WP_UnitTest_Factory_For_Question( $this );
		$this->module   = new WP_UnitTest_Factory_For_Module( $this );
	}// end construct

	/**
	 * Create basic courses, lessons, and quizzes.
	 */
	public function generate_basic_setup() {
		if ( ! empty( $this->basic_test_course_ids ) ) {
			return;
		}

		$this->basic_test_module_term = $this->module->create_and_get()->to_array();
		$this->basic_test_lesson_ids  = $this->lesson->create_many( 10 );

		// Add all but the last lesson to the module.
		foreach ( array_slice( $this->basic_test_lesson_ids, 0, 9 ) as $lesson_id ) {
			wp_set_object_terms( $lesson_id, $this->basic_test_module_term['term_id'], 'module' );
			add_post_meta( $lesson_id, '_order_module_' . $this->basic_test_module_term['term_id'], 0 );
		}
		$this->basic_test_other_lesson_ids = array_slice( $this->basic_test_lesson_ids, 9 );

		$this->basic_test_course_ids = $this->course->create_many( 10 );

		// generate lesson questions
		foreach ( $this->basic_test_lesson_ids as $lesson_id ) {
			try {
				$this->attach_lessons_questions( 12, $lesson_id );
			} catch ( Exception $e ) {
				// ignore
			}
		}

		$this->attach_modules_and_lessons_to_courses();
	}

	/**
	 * Teardown data that the factory creates.
	 *
	 */
	public function tearDown() {
		if ( empty( $this->basic_test_course_ids ) ) {
			return;
		}

		// Courses
		foreach ( $this->basic_test_course_ids as $course_id ) {
			wp_remove_object_terms( $course_id, $this->basic_test_module_term['term_id'], 'module' );

			// Other lessons
			foreach ( $this->basic_test_other_lesson_ids as $other_lesson_id ) {
				delete_post_meta( $other_lesson_id, '_order_' . $course_id );
			}

			wp_delete_post( $course_id, true );
		}

		// Module
		wp_delete_term( $this->basic_test_module_term['term_id'], 'module' );

		// Lessons
		foreach ( $this->basic_test_lesson_ids as $lesson_id ) {
			delete_post_meta( $lesson_id, '_lesson_course' );
			delete_post_meta( $lesson_id, '_order_module_' . $this->basic_test_module_term['term_id'] );
			wp_remove_object_terms( $lesson_id, $this->basic_test_module_term['term_id'], 'module' );
			wp_delete_post( $lesson_id, true );
		}
	}

	/**
	 * Accesses the test_data lesson_id's and return any one of them
	 *
	 * @since 1.7.2
	 *
	 * @param int $number_of_items optional, defaults to 1
	 *
	 * @return int | array $result. If number of items is greater than one, this function will return an array
	 */
	public function get_random_lesson_id( $number_of_items = 1 ) {
		$this->generate_basic_setup();

		if ( $number_of_items > 1 ) {

			$result         = array();
			$random_index_s = array_rand( $this->basic_test_lesson_ids, $number_of_items );
			foreach ( $random_index_s as $index ) {
				array_push( $result, $this->basic_test_lesson_ids[ $index ] );
			}// end for each

		} else {

			$random_index = array_rand( $this->basic_test_lesson_ids );
			$result       = $this->basic_test_lesson_ids[ $random_index ];

		}

		return $result;

	} // end get_random_valid_lesson_id()

	/**
	 * Accesses the test_data course_id's and return any one of them
	 *
	 * @since 1.8.0
	 *
	 * @param int $number_of_items optional, defaults to 1
	 *
	 * @return int | array $result. If number of items is greater than one, this function will return an array
	 */
	public function get_random_course_id( $number_of_items = 1 ) {
		$this->generate_basic_setup();

		if ( $number_of_items > 1 ) {

			$result         = array();
			$random_index_s = array_rand( $this->basic_test_course_ids, $number_of_items );
			foreach ( $random_index_s as $index ) {
				array_push( $result, $this->basic_test_course_ids[ $index ] );
			}// end for each

		} else {

			$random_index = array_rand( $this->basic_test_course_ids );
			$result       = $this->basic_test_course_ids[ $random_index ];

		}

		return $result;

	} // end get_random_course_id()

	/**
	 * Attach modules and lessons to each course.
	 *
	 */
	public function attach_modules_and_lessons_to_courses() {
		foreach ( $this->basic_test_course_ids as $course_id ) {
			// Module
			wp_set_object_terms( $course_id, $this->basic_test_module_term['term_id'], 'module' );
		}

		// Add lessons to the first course, since a lesson can only be associated with a single course.
		foreach ( $this->basic_test_lesson_ids as $lesson_id ) {
			add_post_meta( $lesson_id, '_lesson_course', $this->basic_test_course_ids[0] );
		}

		$i = 1;

		// Do the same for other lessons.
		foreach ( $this->basic_test_other_lesson_ids as $other_lesson_id ) {
			add_post_meta( $other_lesson_id, '_order_' . $this->basic_test_course_ids[0], $i );
			$i ++;
		}
	}

	/**
	 * @since 1.8.0
	 * @return array $lesson_ids
	 */
	public function get_lessons() {
		$this->generate_basic_setup();

		$lesson_ids = $this->basic_test_lesson_ids;

		return $lesson_ids;

	}// end get courses

	/**
	 * @since 1.9.20
	 * @return array $other_lesson_ids
	 */
	public function get_other_lessons() {
		$this->generate_basic_setup();

		return $this->basic_test_other_lesson_ids;
	}

	/**
	 * Get all the courses created in the factory
	 *
	 * @since 1.8.0
	 * @return array $course_ids
	 */
	public function get_courses() {
		$this->generate_basic_setup();

		$course_ids = $this->basic_test_course_ids;

		return $course_ids;

	}// end get courses

	/**
	 * Get a course that has modules.
	 *
	 * @since 1.9.20
	 * @return string Course ID
	 */
	public function get_course_with_modules() {
		$this->generate_basic_setup();

		return $this->basic_test_course_ids[0];
	}

	/**
	 * This function creates dummy answers for the user based on the quiz questions for the
	 * quiz id that is passed in.
	 *
	 * @since 1.7.2
	 * @access public
	 *
	 * @param int $quiz_id
	 *
	 * @return array $user_quiz_answers
	 */
	public function generate_user_quiz_answers( $quiz_id ) {
		$user_quiz_answers = array();

		if ( empty( $quiz_id ) || 'quiz' != get_post_type( $quiz_id ) ) {

			return $user_quiz_answers;

		}

		// get all the quiz questions that is added to the passed in quiz
		$quiz_question_posts = Sensei()->lesson->lesson_quiz_questions( $quiz_id );

		if ( empty( $quiz_question_posts ) || count( $quiz_question_posts ) == 0
			 || ! isset( $quiz_question_posts[0]->ID ) ) {

			return $user_quiz_answers;

		}

		// loop through all the question and generate random answer data
		foreach ( $quiz_question_posts as $question ) {

			// get the current question type
			$type = Sensei()->question->get_question_type( $question->ID );

			// setup the demo data and store it in the respective array
			if ( 'multiple-choice' == $type ) {
				// these answer can be found the question generate and attach answers function
				$question_meta                      = get_post_meta( $question->ID );
				$user_quiz_answers[ $question->ID ] = array( 0 => 'wrong1' . rand() );

			} elseif ( 'boolean' == $type ) {

				$bool_answer = 'false';
				$random_is_1 = rand( 0, 1 );

				if ( $random_is_1 ) {
					$bool_answer = 'true';
				}

				$user_quiz_answers[ $question->ID ] = $bool_answer;

			} elseif ( 'single-line' == $type ) {

				$user_quiz_answers[ $question->ID ] = 'Single line answer for basic testing ' . rand();

			} elseif ( 'gap-fill' == $type ) {

				$user_quiz_answers[ $question->ID ] = 'OneWordScentencesForSampleAnswer ' . rand();

			} elseif ( 'multi-line' == $type ) {

				$user_quiz_answers[ $question->ID ] = 'Sample paragraph to test the answer ' . rand();

			} elseif ( 'file-upload' == $type ) {

				$user_quiz_answers[ $question->ID ] = '';

			}

		}// end for quiz_question_posts

		return $user_quiz_answers;

	}// end generate_user_quiz_answers()

	/**
	 * Generate an array of user quiz grades
	 *
	 * @param array $quiz_answers
	 *
	 * @return array
	 *
	 * @throws Exception 'Generate questions needs a valid lesson ID.' if the ID passed in is not a valid lesson
	 */
	public function generate_user_quiz_grades( $quiz_answers ) {

		if ( empty( $quiz_answers ) || ! is_array( $quiz_answers ) ) {
			throw new Exception( ' The generate_user_quiz_grades parameter must be a valid array ' );
		}

		$quiz_grades = array();
		foreach ( $quiz_answers as $question_id => $answer ) {

			$quiz_grades[ $question_id ] = rand( 1, 5 );

		}//  end foreach

		return $quiz_grades;

	}// generate_user_quiz_grades

	/**
	 * Generate and attach lesson questions.
	 *
	 * This will create a set of questions. These set of questions will be added to every lesson.
	 * So all lessons the makes use of this function will have the same set of questions in their
	 * quiz.
	 *
	 * @param int $number number of questions to generate. Default 10
	 * @param int $lesson_id
	 * @param array $question_args
	 *
	 * @throws Exception 'Generate questions needs a valid lesson ID.' if the ID passed in is not a valid lesson
	 */
	protected function attach_lessons_questions( $number = 10, $lesson_id, $question_args = array() ) {

		if ( empty( $lesson_id ) || ! intval( $lesson_id ) > 0
			 || ! get_post( $lesson_id ) || 'lesson' != get_post_type( $lesson_id ) ) {
			throw new Exception( 'Generate questions needs a valid lesson ID.' );
		}

		$quiz_id = $this->quiz->create(
			array(
				'post_parent' => $lesson_id,
				'meta_input'  => array(
					'_quiz_grade_type' => 'manual',
					'_pass_required'   => 'on',
					'_quiz_passmark'   => 50,
				),
			)
		);

		if ( $number > 0 ) {
			update_post_meta( $lesson_id, '_quiz_has_questions', true );
		}

		// if the database already contains questions don't create more but add
		// the existing questions to the passed in lesson id's lesson
		$question_post_query = new WP_Query( array( 'post_type' => 'question' ) );
		$questions           = $question_post_query->get_posts();

		if ( empty( $questions ) || ! empty( $question_args ) ) {

			// generate questions if none exists
			$question_args['quiz_id']      = $quiz_id;
			$question_args['post_author']  = get_post( $quiz_id )->post_author;
			$this->basic_test_question_ids = $this->question->create_many( $number, $question_args );

		} else {

			// simply add questions to incoming lesson id

			foreach ( $questions as $index => $question ) {

				// Add to quiz
				add_post_meta( $question->ID, '_quiz_id', $quiz_id, false );

				// Set order of question
				$question_order = $quiz_id . '000' . $index;
				add_post_meta( $question->ID, '_quiz_question_order' . $quiz_id, $question_order );

			}
		} // end if count

		return;
	}

	/**
	 * This functions take answers submitted by a user, extracts ones that is of type file-upload
	 * and then creates and array of test $_FILES
	 *
	 * @param array $test_user_quiz_answers
	 *
	 * @return array $files
	 */
	public function generate_test_files( $test_user_quiz_answers ) {

		$files = array();
		//check if there are any file-upload question types and generate the dummy file data
		foreach ( $test_user_quiz_answers as $question_id => $answer ) {

			//Setup the question types
			$question_type = Sensei()->question->get_question_type( $question_id );

			if ( 'file-upload' == $question_type ) {
				//setup the sample image file location within the test folders
				$test_images_directory = dirname( dirname( dirname( __FILE__ ) ) ) . '/images/';

				// make a copy of the file intended for upload as
				// it will be moved to the new location during the upload
				// and no longer available for the next test
				$new_test_image_name     = 'test-question-' . $question_id . '-greenapple.jpg';
				$new_test_image_location = $test_images_directory . $new_test_image_name;
				copy( $test_images_directory . 'greenapple.jpg', $new_test_image_location );

				$file = array(
					'name'     => $new_test_image_name,
					'type'     => 'image/jpeg',
					'tmp_name' => $new_test_image_location,
					'error'    => 0,
					'size'     => 4576
				);

				// pop the file on top of the car
				$files[ 'file_upload_' . $question_id ] = $file;
			}

		} // end for each $test_user_quiz_answers

		return $files;

	}// end generate_test_files()

	/**
	 * Returns a random none file question id from the given user input array
	 *
	 * @since 1.7.4
	 *
	 * @param array $user_answers
	 *
	 * @return int $index
	 */
	public function get_random_none_file_question_index( $user_answers ) {

		if ( empty( $user_answers )
			 || ! is_array( $user_answers ) ) {

			return false;

		}

		// create a new array without questions of type file
		$answers_without_files = array();
		foreach ( $user_answers as $question_id => $answer ) {

			$type = Sensei()->question->get_question_type( $question_id );

			if ( 'file-upload' != $type ) {
				$answers_without_files[ $question_id ] = $answer;
			}
		}// end foreach

		$index = array_rand( $answers_without_files );

		return $index;
	}// end get_random_none_file_question_index


	/**
	 * Returns a random file question id from the given user input array
	 *
	 * @since 1.7.4
	 *
	 * @param array $user_answers
	 *
	 * @return int $index
	 */
	public function get_random_file_question_index( $user_answers ) {

		if ( empty( $user_answers )
			 || ! is_array( $user_answers ) ) {

			return false;

		}

		// create a new array without questions of type file
		$file_type_answers = array();
		foreach ( $user_answers as $question_id => $answer ) {

			$type = Sensei()->question->get_question_type( $question_id );

			if ( 'file-upload' == $type ) {
				$file_type_answers[ $question_id ] = $answer;
			}
		}// end foreach

		$index = array_rand( $file_type_answers );

		return $index;
	}// end get_random_none_file_question_index


	/**
	 * This function creates dummy answers for the user based on the quiz questions for the
	 * quiz id that is passed in.
	 *
	 * @since 1.7.2
	 * @access public
	 *
	 * @param int $quiz_id
	 *
	 * @returns array $user_quiz_answers
	 */
	public function generate_user_answers_feedback( $quiz_id ) {

		$answers_feedback = array();

		if ( empty( $quiz_id ) || 'quiz' != get_post_type( $quiz_id ) ) {

			return $answers_feedback;

		}

		$answers = $this->generate_user_quiz_answers( $quiz_id );

		foreach ( $answers as $question_id => $answer ) {

			$answers_feedback[ $question_id ] = 'Sample Feedback ' . rand();

		}

		return $answers_feedback;

	} // end generate_user_answers_feedback

	/**
	 * @return int|WP_Error
	 */
	public function get_lesson_no_quiz() {
		return $this->lesson->create();
	}

	/**
	 * @return int|WP_Error
	 * @throws Exception
	 */
	public function get_lesson_empty_quiz() {
		$lesson_id = $this->get_lesson_no_quiz();
		$this->attach_lessons_questions( 0, $lesson_id );

		return $lesson_id;
	}

	/**
	 * @return int|WP_Error
	 * @throws Exception
	 */
	public function get_lesson_graded_quiz() {
		$lesson_id = $this->get_lesson_no_quiz();
		$this->attach_lessons_questions( 10, $lesson_id, array( 'question_grade' => '1' ) );

		return $lesson_id;
	}

	/**
	 * @return int|WP_Error
	 * @throws Exception
	 */
	public function get_lesson_no_graded_quiz() {
		$lesson_id = $this->get_lesson_no_quiz();
		$this->attach_lessons_questions( 10, $lesson_id, array( 'question_grade' => '0' ) );

		return $lesson_id;
	}

}// end Sensei Factory class
