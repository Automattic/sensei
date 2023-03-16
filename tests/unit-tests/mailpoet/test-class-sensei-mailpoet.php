<?php
/**
 * File with class for testing Sensei MailPoet.
 *
 * @package sensei-tests
 */

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Mocking an external library.
// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound -- Using PHPUnit conventions.

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
		$mailpoet_api  = Sensei_MailPoetAPIMockFactory::MP();
		new Sensei_MailPoet( $mailpoet_api );
	}

	/**
	 * Clean up after all tests.
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
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

		$mailpoet_api       = Sensei_MailPoetAPIMockFactory::MP();
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
		$user_ids  = $this->factory->user->create_many( 2 );
		$students  = Sensei_MailPoet_Repository::user_objects_to_array( get_users( array( 'include' => $user_ids ) ) );

		$post      = get_post( $course_id );
		$list_name = Sensei_MailPoet_Repository::get_list_name( $post->post_title, $post->post_type );

		$mailpoet_api       = Sensei_MailPoetAPIMockFactory::MP();
		$sensei_mp_instance = Sensei_MailPoet::instance( $mailpoet_api );
		foreach ( $students as $student ) {
			$sensei_mp_instance->add_student_subscriber( $course_id, $student['id'] );
		}

		$lists        = $mailpoet_api->getLists();
		$updated_list = array_column( $lists, null, 'name' );
		$list_id      = $updated_list[ $list_name ]['id'];
		$subscribers  = $mailpoet_api->getSubscribers( array( 'listId' => $list_id ) );
		$this->assertCount( 2, $subscribers );

		$other_user_ids = $this->factory->user->create_many( 3 );
		$other_students = Sensei_MailPoet_Repository::user_objects_to_array( get_users( array( 'include' => $other_user_ids ) ) );
		$sensei_mp_instance->sync_subscribers( $other_students, array(), $list_id );
		$subscribers = $mailpoet_api->getSubscribers( array( 'listId' => $list_id ) );
		$this->assertCount( 5, $subscribers );
	}

}

/**
 * Stub to instantiate the MailPoet API object.
 *
 * @since $$next-version$$
 */
class Sensei_MailPoetAPIMockFactory {
	/**
	 * Instance of the current handler.
	 */
	private static $instance;
	/**
	 * Mock MP static method.
	 */
	public static function MP() {
		return self::get_instance();
	}

	/**
	 * Get the singleton instance of MailPoet API.
	 *
	 * @return Sensei_MailPoetMockAPI_Test
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Sensei_MailPoetMockAPI_Test();
		}

		return self::$instance;
	}
}

/**
 * Stub to mock the MailPoet API object.
 *
 * @since $$next-version$$
 */
class Sensei_MailPoetMockAPI_Test {
	public $lists;
	public $subscribers;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->lists       = array(
			0 =>
				array(
					'id'          => '533',
					'name'        => 'Sensei LMS Course: A new course',
					'type'        => 'default',
					'description' => '',
					'created_at'  => '2023-03-14 13:42:54',
					'updated_at'  => '2023-03-14 13:42:54',
					'deleted_at'  => null,
				),
			1 =>
				array(
					'id'          => '534',
					'name'        => 'Sensei LMS Course: Becoming a Content Creator',
					'type'        => 'default',
					'description' => '',
					'created_at'  => '2023-03-14 13:42:54',
					'updated_at'  => '2023-03-14 13:42:54',
					'deleted_at'  => null,
				),
			2 =>
				array(
					'id'          => '536',
					'name'        => 'Sensei LMS Course: How to be famous',
					'type'        => 'default',
					'description' => '',
					'created_at'  => '2023-03-14 13:42:54',
					'updated_at'  => '2023-03-14 13:42:54',
					'deleted_at'  => null,
				),
			3 =>
				array(
					'id'          => '537',
					'name'        => 'Sensei LMS Course: Life 101',
					'type'        => 'default',
					'description' => '',
					'created_at'  => '2023-03-14 13:42:54',
					'updated_at'  => '2023-03-14 13:42:54',
					'deleted_at'  => null,
				),
		);
		$this->subscribers = array();
	}

	/**
	 * Mock isSetupComplete method.
	 */
	public function isSetupComplete() {
		return true;
	}

	/**
	 * Mock getLists method.
	 */
	public function getLists() {
		return $this->lists;
	}

	/**
	 * Mock addList method.
	 */
	public function addList( $list ) {
		$new_list      = array(
			'id'          => rand( 100, 500 ),
			'name'        => $list['name'],
			'description' => $list['description'],
		);
		$this->lists[] = $new_list;
		return $new_list;
	}

	/**
	 * Mock getSubscriber method.
	 */
	public function getSubscriber( $email ) {
		foreach ( $this->subscribers as $subscriber ) {
			if ( $subscriber['email'] == $email ) {
				return $subscriber;
			}
		}

		$id                       = rand( 10, 50 );
		$subscriber               = array(
			'id'         => $id,
			'email'      => $email,
			'first_name' => 'John',
			'last_name'  => 'Doe',
			'list_ids'   => array(),
		);
		$this->subscribers[ $id ] = $subscriber;
		return $subscriber;
	}

	/**
	 * Mock subscribeToList method.
	 */
	public function subscribeToList( $id, $list_id, $options ) {
		$this->subscribers[ $id ]['list_ids'][ $list_id ] = true;
		return true;
	}

	/**
	 * Mock unsubscribeFromList method.
	 */
	public function unsubscribeFromList( $id, $list_id ) {
		$this->subscribers[ $id ]['list_ids'][ $list_id ] = false;
		return true;
	}

	/**
	 * Mock getSubscribers method.
	 */
	public function getSubscribers( $args ) {
		$list_id     = $args['listId'];
		$subscribers = array();
		foreach ( $this->subscribers as $subscriber ) {
			if ( isset( $subscriber['list_ids'][ $list_id ] ) && $subscriber['list_ids'][ $list_id ] ) {
				$subscribers[] = $subscriber;
			}
		}
		return $subscribers;
	}
}
