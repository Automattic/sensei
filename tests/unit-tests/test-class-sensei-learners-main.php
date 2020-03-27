<?php

require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-course-enrolment-test-helpers.php';

global $hook_suffix;

/**
 * Tests for Sensei_Learners_Main class.
 *
 * @group bulk-actions
 */
class Sensei_Learners_Main_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Test_Helpers;
	use Sensei_Course_Enrolment_Manual_Test_Helpers;

	private $factory;

	private $course_id;

	public function setUp() {
		parent::setUp();
		$this->factory = new Sensei_Factory();

		$this->course_id = $this->createCourseEnrolments();
	}

	/**
	 * Clean up after all tests.
	 */
	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
		self::resetEnrolmentProviders();
	}

	/**
	 * Create a course with learners of various enrollment status
	 */
	private function createCourseEnrolments() {
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Provides_For_Dinosaurs::class );
		$this->resetCourseEnrolmentManager();

		$course_id        = $this->factory->course->create();
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$users_manual        = $this->factory->user->create_many( 2 );
		$users_unenrolled    = $this->factory->user->create_many( 3 );
		$users_otherprovider = array_map( [ $this, 'createDinosaurStudent' ], range( 1, 4 ) );

		foreach ( $users_manual as $user_id ) {
			$this->manuallyEnrolStudentInCourse( $user_id, $course_id );
		}

		foreach ( $users_unenrolled as $user_id ) {
			Sensei_Utils::user_start_course( $user_id, $course_id );
		}

		foreach ( $users_otherprovider as $user_id ) {
			$course_enrolment->is_enrolled( $user_id );
		}

		return $course_id;
	}

	/**
	 * Tests that only enrolled users are shown when filtered
	 *
	 * @dataProvider enrolmentFilterTestCases
	 * @covers       Sensei_Learners_Main::get_learners
	 */
	public function testUsersAreFilteredByEnrolmentStatus( $enrolment_status, $expected_result ) {

		$_GET['course_id']        = $this->course_id;
		$_GET['view']             = 'learners';
		$_GET['enrolment_status'] = $enrolment_status;

		$sensei_learners_main = new Sensei_Learners_Main();
		$sensei_learners_main->prepare_items();

		$this->assertCount( $expected_result, $sensei_learners_main->items );

	}

	public function enrolmentFilterTestCases() {
		return [
			'All learners'               => [ 'all', 9 ],
			'Enrolled learners'          => [ 'enrolled', 6 ],
			'Unenrolled learners'        => [ 'unenrolled', 3 ],
			'Manually enrolled learners' => [ 'manual', 2 ],
		];
	}
}
