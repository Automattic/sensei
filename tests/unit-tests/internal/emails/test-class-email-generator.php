<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Generator;
use Sensei\Internal\Emails\Email_Repository;
use Sensei\Internal\Emails\Generators\Email_Generators_Abstract;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Interface;

/**
 * Tests for Sensei\Internal\Emails\Email_Generator class.
 *
 * @covers \Sensei\Internal\Emails\Email_Generator
 */
class Email_Generator_Test extends \WP_UnitTestCase {
	/**
	 * Email repository instance.
	 *
	 * @var Email_Repository
	 */
	protected $email_repository;

	/**
	 * Lesson progress repository mock.
	 *
	 * @var Lesson_Progress_Repository_Interface
	 */
	private $lesson_progress_repository;

	public function setUp(): void {
		parent::setUp();

		$this->email_repository           = $this->createMock( Email_Repository::class );
		$this->lesson_progress_repository = $this->createMock( Lesson_Progress_Repository_Interface::class );
	}

	public function testInit_WhenCalled_AddsHooksForInitializingIndividualEmails() {
		/* Arrange. */
		$generator = new Email_Generator( $this->email_repository, $this->lesson_progress_repository );

		/* Act. */
		$generator->init();

		/* Assert. */
		$this->assertEquals( 10, has_action( 'init', [ $generator, 'init_email_generators' ] ) );
	}
}
