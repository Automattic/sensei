<?php
/**
 * Tests student management functionality (formerly known as Learner Management).
 *
 * @package sensei
 */

require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-progress-insert-failure-helpers.php';

/**
 * Tests for Sensei_Learner_Management class.
 */
class Sensei_Learner_Management_Test extends WP_UnitTestCase {
	use Sensei_Progress_Insert_Failure_Helpers;

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
		$this->restore_progress_insert_failure();
		$this->restore_progress_storage_mode();

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
	 * Tests that a teacher cannot enrol a student into a lesson that belongs to another course by
	 * pairing their own authorized course id with that lesson id.
	 *
	 * @covers Sensei_Learner_Management::add_new_learners
	 */
	public function testAddNewLearners_LessonBelongsToAnotherCourse_DoesNotEnrolStudent() {
		/* Arrange. */
		$attacker_id = $this->factory->user->create();
		$victim_id   = $this->factory->user->create();

		$own_course_id   = $this->factory->course->create( array( 'post_author' => $attacker_id ) );
		$other_course_id = $this->factory->course->create( array( 'post_author' => $this->factory->user->create() ) );
		$other_lesson_id = $this->factory->lesson->create(
			array(
				'meta_input' => array(
					'_lesson_course' => $other_course_id,
				),
			)
		);

		wp_set_current_user( $attacker_id );

		$_POST['add_learner_submit']  = 'some_value';
		$_POST['add_learner_nonce']   = wp_create_nonce( 'add_learner_to_sensei' );
		$_POST['add_post_type']       = 'lesson';
		$_POST['add_user_id']         = array( $victim_id );
		$_POST['add_course_id']       = $own_course_id;
		$_POST['add_lesson_id']       = $other_lesson_id;
		$_POST['add_complete_lesson'] = 'yes';

		/* Act. */
		$this->learner_management->add_new_learners();

		/* Assert. */
		$this->assertFalse(
			Sensei_Utils::user_started_lesson( $other_lesson_id, $victim_id ),
			'Student must not be started on a lesson that belongs to another course.'
		);
		$this->assertFalse(
			Sensei_Course::is_user_enrolled( $other_course_id, $victim_id ),
			'Student must not be enrolled into another course.'
		);
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
		$exception = $this->invoke_edit_date_started(
			array(
				'post_id'   => $course_id,
				'user_id'   => $student_id,
				'new_dates' => array( 'start-date' => $start_date ),
			)
		);

		/* Assert. */
		$progress   = Sensei()->course_progress_repository_factory->create()->get( $course_id, $student_id );
		$saved_date = wp_date( 'Y-m-d H:i:s', $progress->get_started_at()->getTimestamp(), wp_timezone() );
		$this->assertSame( $start_date, $exception->getMessage(), 'Response should be the formatted start date.' );
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
		$exception = $this->invoke_edit_date_started( $data );

		/* Assert. */
		$this->assertSame( '', $exception->getMessage(), 'Resubmitting the same date should return an empty response.' );
	}

	/**
	 * A failed start-date save must return HTTP 500 without announcing an update.
	 *
	 * @dataProvider date_edit_storage_modes
	 * @covers Sensei_Learner_Management::edit_date_started
	 */
	public function testEditDateStarted_CommentInsertFailedDuringSave_ReturnsServerError( string $mode, string $post_type ): void {
		$this->set_progress_storage_mode( $mode );
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		$user_id    = $this->factory->user->create();
		$post_id    = $this->factory->post->create( array( 'post_type' => $post_type ) );
		$repository = Sensei()->{ $post_type . '_progress_repository' };
		$repository->create( $post_id, $user_id );
		$factory_property = $post_type . '_progress_repository_factory';
		$original_factory = Sensei()->{$factory_property};
		$factory_class    = get_class( $original_factory );

		Sensei()->{$factory_property} = new $factory_class( Sensei()->progress_storage_configuration );
		$updated                     = did_filter( 'sensei_students_row_updated' );
		$this->fail_progress_comment_insert( $post_type, $post_id, $user_id, 'in-progress' );

		try {
			$exception = $this->invoke_edit_date_started(
				array(
					'post_id'   => $post_id,
					'user_id'   => $user_id,
					'new_dates' => array( 'start-date' => '2024-01-15 10:30:00' ),
				)
			);
		} finally {
			Sensei()->{$factory_property} = $original_factory;
		}

		self::assertSame( 500, $exception->getCode(), 'A failed save must return a server error.' );
		self::assertSame( '', $exception->getMessage(), 'A failed save must not return a saved date.' );
		self::assertSame( $updated, did_filter( 'sensei_students_row_updated' ), 'A failed save must not announce a row update.' );
	}

	/**
	 * Course and lesson date edits in each progress storage configuration.
	 *
	 * @return array
	 */
	public static function date_edit_storage_modes(): array {
		$cases = array();
		foreach ( self::progress_storage_modes() as $label => $arguments ) {
			foreach ( array( 'course', 'lesson' ) as $post_type ) {
				$cases[ $label . ' ' . $post_type ] = array( $arguments[0], $post_type );
			}
		}
		return $cases;
	}

	/**
	 * Invoke the date-edit handler and capture its wp_die() response.
	 *
	 * @param array $data The POST data payload.
	 *
	 * @return WPDieException Exception containing the response body and status code.
	 */
	private function invoke_edit_date_started( array $data ): WPDieException {
		$nonce                = wp_create_nonce( 'edit_date_nonce' );
		$_REQUEST['security'] = $nonce;
		$_POST['security']    = $nonce;
		$_POST['data']        = $data;

		try {
			$this->learner_management->edit_date_started();
		} catch ( \WPDieException $e ) {
			return $e;
		}

		$this->fail( 'Expected edit_date_started() to call wp_die().' );
	}
}
