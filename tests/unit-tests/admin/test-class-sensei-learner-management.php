<?php
/**
 * Tests student management functionality (formerly known as Learner Management).
 *
 * @package sensei
 */

/**
 * Tests for Sensei_Learner_Management class.
 */
class Sensei_Learner_Management_Test extends WP_UnitTestCase {
	/**
	 * Sensei_Factory instance.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Sensei_Learner_Management instance.
	 *
	 * @var Sensei_Learner_Management
	 */
	protected $learner_management;

	public function setUp(): void {
		parent::setUp();

		$this->factory            = new Sensei_Factory();
		$this->learner_management = new Sensei_Learner_Management( '' );
	}

	public function tearDown(): void {
		parent::tearDown();

		$this->factory->tearDown();
	}

	/**
	 * Tests that students cannot be added to a course if current user is not the teacher.
	 *
	 * @covers Sensei_Learner_Management::add_new_learners
	 */
	public function testAddNewLearners_ToCourseWhenCurrentUserIsNotTeacher_ReturnsFalse() {
		/* Arrange. */
		$teacher_id      = $this->factory->user->create();
		$student_id      = $this->factory->user->create();
		$current_user_id = $this->factory->user->create();

		wp_set_current_user( $current_user_id );

		$_POST['add_learner_submit'] = 'some_value';
		$_POST['add_learner_nonce']  = wp_create_nonce( 'add_learner_to_sensei' );
		$_POST['add_post_type']      = 'course';
		$_POST['add_user_id']        = [ $student_id ];
		$_POST['add_course_id']      = $this->factory->course->create( [ 'post_author' => $teacher_id ] );
		$_POST['add_lesson_id']      = 0;

		/* Act. */
		$result = $this->learner_management->add_new_learners();

		/* Assert. */
		$this->assertFalse( $result );
	}

	/**
	 * Tests that students cannot be added to a lesson if current user is not the teacher.
	 *
	 * @covers Sensei_Learner_Management::add_new_learners
	 */
	public function testAddNewLearners_ToLessonWhenCurrentUserIsNotTeacher_ReturnsFalse() {
		/* Arrange. */
		$teacher_id      = $this->factory->user->create();
		$student_id      = $this->factory->user->create();
		$current_user_id = $this->factory->user->create();
		$course_id       = $this->factory->course->create( [ 'post_author' => $teacher_id ] );
		$lesson_id       = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course' => $course_id,
				],
			]
		);

		wp_set_current_user( $current_user_id );

		$_POST['add_learner_submit'] = 'some_value';
		$_POST['add_learner_nonce']  = wp_create_nonce( 'add_learner_to_sensei' );
		$_POST['add_post_type']      = 'lesson';
		$_POST['add_user_id']        = [ $student_id ];
		$_POST['add_course_id']      = $course_id;
		$_POST['add_lesson_id']      = $lesson_id;

		/* Act. */
		$result = $this->learner_management->add_new_learners();

		/* Assert. */
		$this->assertFalse( $result );
	}

	/**
	 * Tests that a teacher cannot enrol a student into a foreign lesson by pairing their own
	 * authored course id with another teacher's lesson id.
	 *
	 * @covers Sensei_Learner_Management::add_new_learners
	 */
	public function testAddNewLearners_OwnCourseWithForeignLessonGiven_DoesNotEnrolStudentInForeignLesson() {
		/* Arrange. */
		$attacker_id = $this->factory->user->create();
		$victim_id   = $this->factory->user->create();

		$own_course_id     = $this->factory->course->create( [ 'post_author' => $attacker_id ] );
		$foreign_course_id = $this->factory->course->create( [ 'post_author' => $this->factory->user->create() ] );
		$foreign_lesson_id = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course' => $foreign_course_id,
				],
			]
		);

		wp_set_current_user( $attacker_id );

		$_POST['add_learner_submit']  = 'some_value';
		$_POST['add_learner_nonce']   = wp_create_nonce( 'add_learner_to_sensei' );
		$_POST['add_post_type']       = 'lesson';
		$_POST['add_user_id']         = [ $victim_id ];
		$_POST['add_course_id']       = $own_course_id;
		$_POST['add_lesson_id']       = $foreign_lesson_id;
		$_POST['add_complete_lesson'] = 'yes';

		/* Act. */
		$this->invoke_add_new_learners();

		/* Assert. */
		$this->assertFalse(
			Sensei_Utils::user_started_lesson( $foreign_lesson_id, $victim_id ),
			'Student must not be started on a foreign teacher\'s lesson.'
		);
		$this->assertFalse(
			Sensei_Course::is_user_enrolled( $foreign_course_id, $victim_id ),
			'Student must not be enrolled into a foreign teacher\'s course.'
		);
	}

	/**
	 * Invokes add_new_learners(), swallowing the redirect the success path performs.
	 *
	 * The mutating path ends in wp_safe_redirect() followed by exit; a wp_redirect filter that
	 * throws lets the test intercept it before exit terminates the run, while the early-return
	 * guard paths simply do nothing. Either way the security invariants can then be asserted.
	 */
	private function invoke_add_new_learners(): void {
		$throw = static function () {
			throw new \Exception( 'redirected' );
		};
		add_filter( 'wp_redirect', $throw );

		try {
			$this->learner_management->add_new_learners();
		} catch ( \Exception $e ) {
			unset( $e ); // Redirect intercepted; expected on the mutating path.
		} finally {
			remove_filter( 'wp_redirect', $throw );
		}
	}

	/**
	 * Tests that a valid start date edit is saved to the progress repository.
	 *
	 * @covers Sensei_Learner_Management::edit_date_started
	 */
	public function testEditDateStarted_ValidCourseDateGiven_SavesStartedAtToRepository() {
		/* Arrange. */
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		$student_id = $this->factory->user->create();
		$course_id  = $this->factory->course->create();
		Sensei_Utils::user_start_course( $student_id, $course_id );

		$start_date = '2024-01-15 10:30:00';

		/* Act. */
		$response = $this->invoke_edit_date_started(
			array(
				'post_id'   => $course_id,
				'user_id'   => $student_id,
				'new_dates' => array( 'start-date' => $start_date ),
			)
		);

		/* Assert. */
		$progress   = Sensei()->course_progress_repository_factory->create()->get( $course_id, $student_id );
		$saved_date = wp_date( 'Y-m-d H:i:s', $progress->get_started_at()->getTimestamp(), wp_timezone() );
		$this->assertSame( $start_date, $response, 'Response should be the formatted start date.' );
		$this->assertSame( $start_date, $saved_date, 'Start date should be saved to the repository.' );
	}

	/**
	 * Tests that submitting the same start date returns an empty response and does not report an update.
	 *
	 * @covers Sensei_Learner_Management::edit_date_started
	 */
	public function testEditDateStarted_UnchangedDateGiven_ReturnsEmptyResponse() {
		/* Arrange. */
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		$student_id = $this->factory->user->create();
		$course_id  = $this->factory->course->create();
		Sensei_Utils::user_start_course( $student_id, $course_id );

		$data = array(
			'post_id'   => $course_id,
			'user_id'   => $student_id,
			'new_dates' => array( 'start-date' => '2024-01-15 10:30:00' ),
		);
		$this->invoke_edit_date_started( $data );

		/* Act. */
		$response = $this->invoke_edit_date_started( $data );

		/* Assert. */
		$this->assertSame( '', $response, 'Resubmitting the same date should return an empty response.' );
	}

	/**
	 * Invokes the edit_date_started AJAX handler and returns the response body.
	 *
	 * The success and no-op paths exercised by these tests terminate via wp_die(), which the
	 * test framework throws as WPDieException; this captures the emitted body and fails the
	 * test if the handler returns without dying. (Validation-failure paths use exit and are
	 * not exercised here.)
	 *
	 * @param array $data The POST data payload.
	 *
	 * @return string The response body emitted by the handler.
	 */
	private function invoke_edit_date_started( array $data ): string {
		$nonce                = wp_create_nonce( 'edit_date_nonce' );
		$_REQUEST['security'] = $nonce;
		$_POST['security']    = $nonce;
		$_POST['data']        = $data;

		try {
			$this->learner_management->edit_date_started();
		} catch ( \WPDieException $e ) {
			return $e->getMessage();
		}

		$this->fail( 'Expected edit_date_started() to call wp_die().' );
	}
}
