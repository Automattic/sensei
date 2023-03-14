<?php
/**
 * File with class for testing Sensei MailPoet.
 *
 * @package sensei-tests
 */

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Mocking an external library.
// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound -- Using PHPUnit conventions.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Using PHPUnit conventions.

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
		$mailpoet_api  = MailPoetAPIMockFactory::MP();
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

}

/**
 * Stub to instantiate the MailPoet API object.
 *
 * @since $$next-version$$
 */
class MailPoetAPIMockFactory {
	/**
	 * Mock MP static method.
	 */
	public static function MP() {
		return new MailPoetMockAPI();
	}
}

/**
 * Stub to mock the MailPoet API object.
 *
 * @since $$next-version$$
 */
class MailPoetMockAPI {
	public $lists;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->lists = array(
			'Sensei LMS Course: A new course'     =>
				array(
					'id'          => '533',
					'name'        => 'Sensei LMS Course: A new course',
					'type'        => 'default',
					'description' => '',
					'created_at'  => '2023-03-14 13:42:54',
					'updated_at'  => '2023-03-14 13:42:54',
					'deleted_at'  => null,
				),
			'Sensei LMS Course: Becoming a Content Creator' =>
				array(
					'id'          => '534',
					'name'        => 'Sensei LMS Course: Becoming a Content Creator',
					'type'        => 'default',
					'description' => '',
					'created_at'  => '2023-03-14 13:42:54',
					'updated_at'  => '2023-03-14 13:42:54',
					'deleted_at'  => null,
				),
			'Sensei LMS Course: How to be famous' =>
				array(
					'id'          => '536',
					'name'        => 'Sensei LMS Course: How to be famous',
					'type'        => 'default',
					'description' => '',
					'created_at'  => '2023-03-14 13:42:54',
					'updated_at'  => '2023-03-14 13:42:54',
					'deleted_at'  => null,
				),
			'Sensei LMS Course: Life 101'         =>
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
			'id'          => 122,
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
		return array( 'id' => 12 );
	}

	/**
	 * Mock subscribeToList method.
	 */
	public function subscribeToList( $id, $list_id, $options ) {
		return true;
	}

	/**
	 * Mock unsubscribeFromList method.
	 */
	public function unsubscribeFromList( $subscriber_id, $list_id ) {
		return true;
	}
}
