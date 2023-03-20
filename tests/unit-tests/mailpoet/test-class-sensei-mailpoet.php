<?php
/**
 * File with class for testing Sensei MailPoet.
 *
 * @package sensei-tests
 */

/**
 * Class for testing Sensei_MailPoet class.
 *
 * @group Sensei MailPoet
 */
class Sensei_MailPoet_Test extends WP_UnitTestCase {
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
		$mailpoet_api  = Sensei_MailPoetAPIFactory::MP();
		new Sensei_MailPoet( $mailpoet_api );
	}

	/**
	 * Tests manual enrolment, we are not testing anything specific to the MailPoet API.
	 * This general test ensures the hook 'sensei_manual_enrolment_learner_enrolled' throws no error in the MailPoet class.
	 */
	public function testEnrolStudentNoErrors() {
		$provider   = $this->getManualEnrolmentProvider();
		$course_id  = $this->factory->course->create();
		$student_id = $this->factory->user->create();

		$this->assertFalse( $provider->is_enrolled( $student_id, $course_id ), 'Student should not be enrolled before we enrol them.' );

		$provider->enrol_learner( $student_id, $course_id );

		$this->assertTrue( $provider->is_enrolled( $student_id, $course_id ), 'Student should now be enrolled without any errors from the MailPoet class.' );
	}

	/**
	 * Tests manual unenrolment, we are not testing anything specific to the MailPoet API.
	 * This general test ensures the hook 'sensei_manual_enrolment_learner_withdrawn' throws no error in the MailPoet class.
	 */
	public function testWithdrawStudentNoErrors() {
		$provider   = $this->getManualEnrolmentProvider();
		$course_id  = $this->factory->course->create();
		$student_id = $this->factory->user->create();

		$this->directlyEnrolStudent( $student_id, $course_id );
		$this->assertTrue( $provider->is_enrolled( $student_id, $course_id ), 'Student should be enrolled after directly enrolling them.' );

		$provider->withdraw_learner( $student_id, $course_id );

		$this->assertFalse( $provider->is_enrolled( $student_id, $course_id ), 'Student should not be enrolled after withdrawing them from the course.' );
	}

	/**
	 * Tests adding and removing subscribers to/from MailPoet.
	 */
	public function testAddRemoveSubscribers() {
		$course_id   = $this->factory->course->create();
		$student_id1 = $this->factory->user->create();
		$student_id2 = $this->factory->user->create();

		$post      = get_post( $course_id );
		$list_name = Sensei_MailPoet_Repository::get_list_name( $post->post_title, $post->post_type );

		$mailpoet_api       = Sensei_MailPoetAPIFactory::MP();
		$sensei_mp_instance = Sensei_MailPoet::instance( $mailpoet_api );
		$sensei_mp_instance->add_student_subscriber( $course_id, $student_id1 );

		// First check that the new course: course_id has been added as a list.
		$lists        = $mailpoet_api->getLists();
		$updated_list = array_column( $lists, null, 'name' );
		$this->assertArrayHasKey( $list_name, $updated_list );
		//Then check that subscriber has been added.
		$list_id     = $updated_list[ $list_name ]['id'];
		$subscribers = $mailpoet_api->getSubscribers( array( 'listId' => $list_id ) );
		$this->assertCount( 1, $subscribers );

		$sensei_mp_instance->add_student_subscriber( $course_id, $student_id2 );

		$subscribers = $mailpoet_api->getSubscribers( array( 'listId' => $list_id ) );
		$this->assertCount( 2, $subscribers );

		// Lastly check that a subscriber can be removed.
		$sensei_mp_instance->remove_student_subscribers( $course_id, array( $student_id1 ) );
		$subscribers = $mailpoet_api->getSubscribers( array( 'listId' => $list_id ) );
		$this->assertCount( 1, $subscribers );

		$sensei_mp_instance->remove_student_subscribers( $course_id, array( $student_id2 ) );
		$subscribers = $mailpoet_api->getSubscribers( array( 'listId' => $list_id ) );
		$this->assertCount( 0, $subscribers );
	}

	/**
	 * Tests syncing subscribers to/from MailPoet.
	 */
	public function testSyncSubscribers() {
		$course_id = $this->factory->course->create();

		$post      = get_post( $course_id );
		$list_name = Sensei_MailPoet_Repository::get_list_name( $post->post_title, $post->post_type );

		// First add some subscribers(2) to the list for the course.
		$user_ids = $this->factory->user->create_many( 2 );
		$students = Sensei_MailPoet_Repository::user_objects_to_array( get_users( array( 'include' => $user_ids ) ) );

		$mailpoet_api       = Sensei_MailPoetAPIFactory::MP();
		$sensei_mp_instance = Sensei_MailPoet::instance( $mailpoet_api );
		foreach ( $students as $student ) {
			$sensei_mp_instance->add_student_subscriber( $course_id, $student['id'] );
		}
		// Check that we have 2 subscribers.
		$lists        = $mailpoet_api->getLists();
		$updated_list = array_column( $lists, null, 'name' );
		$list_id      = $updated_list[ $list_name ]['id'];
		$subscribers  = $mailpoet_api->getSubscribers( array( 'listId' => $list_id ) );
		$this->assertCount( 2, $subscribers );
		// now attempt to sync with 3 new students as subscribers.
		$other_user_ids = $this->factory->user->create_many( 3 );
		$other_students = Sensei_MailPoet_Repository::user_objects_to_array( get_users( array( 'include' => $other_user_ids ) ) );
		$sensei_mp_instance->sync_subscribers( $other_students, array(), $list_id );
		// Check that we now have 5 subscribers after sync.
		$subscribers = $mailpoet_api->getSubscribers( array( 'listId' => $list_id ) );
		$this->assertCount( 5, $subscribers );
	}
}
