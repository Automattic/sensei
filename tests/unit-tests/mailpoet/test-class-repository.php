<?php
/**
 * File with class for testing Sensei MailPoet integration.
 *
 * @package sensei-tests
 */

/**
 * Class for testing Sensei\Emails\MailPoet\Repository class.
 *
 * @group Sensei MailPoet
 */
class Repository_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Manual_Test_Helpers;
	/**
	 * Factory object.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
		$mailpoet_api  = Sensei_MailPoet_API_Factory::MP();
		new Sensei\Emails\MailPoet\Main( $mailpoet_api );
	}

	/**
	 * Tests adding and removing subscribers to/from MailPoet.
	 */
	public function testGetStudents_Enrolled_IsArray() {
		$provider  = $this->getManualEnrolmentProvider();
		$course_id = $this->factory->course->create();
		$user_id   = $this->factory->user->create();

		$provider->enrol_learner( $user_id, $course_id );
		$students = Sensei\Emails\MailPoet\Repository::get_students( $course_id, 'course' );

		foreach ( $students as $student ) {
			$this->assertIsArray( $student );
		}
	}

	/**
	 * Tests generating a prefixed list name from a course title.
	 */
	public function testGetListName_CourseName_IsPrefixed() {
		$course_id = $this->factory->course->create();
		$post      = get_post( $course_id );

		$list_name     = Sensei\Emails\MailPoet\Repository::get_list_name( $post->post_title, $post->post_type );
		$expected_name = 'Sensei LMS Course: ' . $post->post_title;

		$this->assertEquals( $list_name, $expected_name );
	}

	/**
	 * Tests fetching all Sensei lists which are courses (and groups).
	 */
	public function testFetchSenseiLists_Courses() {
		$this->factory->course->create_many( 4 );

		$lists = Sensei\Emails\MailPoet\Repository::fetch_sensei_lists();

		$this->assertCount( 4, $lists );
		foreach ( $lists as $item ) {
			$this->assertIsArray( $item );
		}
	}

	/**
	 * Tests fetching all Sensei lists which are courses (and groups).
	 */
	public function testUserObjectsToArray_Success() {
		$user_ids = $this->factory->user->create_many( 3 );
		$users    = get_users( array( 'userIds' => $user_ids ) );

		$array_of_user_arrays = Sensei\Emails\MailPoet\Repository::user_objects_to_array( $users );

		foreach ( $array_of_user_arrays as $item ) {
			$this->assertIsArray( $item );
		}
	}
}
