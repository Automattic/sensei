<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Generator;
use Sensei\Internal\Emails\Email_Generators_Abstract;
use Sensei\Internal\Emails\Email_Repository;
use Sensei_Factory;

/**
 * Tests for Sensei\Internal\Emails\Email_Generator class.
 *
 * @covers \Sensei\Internal\Emails\Email_Generator
 */
class Email_Generator_Test extends \WP_UnitTestCase {

	/**
	 * Factory for creating test data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Email repository instance.
	 *
	 * @var Email_Repository
	 */
	protected $email_repository;

	public function setUp(): void {
		parent::setUp();

		$this->factory          = new Sensei_Factory();
		$this->email_repository = new Email_Repository();
	}

	public function testEmailGenerator_WhenInitIsCalled_AddsHooksForInitializingIndividualEmails() {
		/* Arrange. */
		$generator = new Email_Generator( $this->email_repository );

		/* Act. */
		$generator->init();

		/* Assert. */
		$this->assertEquals( 10, has_action( 'init', [ $generator, 'init_email_generators' ] ) );
	}

	public function testEmailGenerator_WhenInitHookCallsEmailGeneratorFunction_InitializesTheActiveEmails() {
		/* Arrange. */
		$generator = new Email_Generator( $this->email_repository );
		$generator->init();

		$test_generator1 = $this->getMockBuilder( Email_Generators_Abstract::class )
			->setMethods( [ 'is_email_active' ] )
			->setConstructorArgs( [ $this->email_repository ] )
			->getMockForAbstractClass();

		$test_generator1->method( 'is_email_active' )
			->willReturn( true );

		$test_generator1->expects( $this->once() )->method( 'init' );

		$test_generator2 = $this->getMockBuilder( Email_Generators_Abstract::class )
			->setMethods( [ 'is_email_active' ] )
			->setConstructorArgs( [ $this->email_repository ] )
			->getMockForAbstractClass();

		$test_generator2->method( 'is_email_active' )
			->willReturn( false );

		$test_generator2->expects( $this->never() )->method( 'init' );

		/* Assert */
		add_filter(
			'sensei_email_generators',
			function () use ( $test_generator1, $test_generator2 ) {
				return [
					'test1' => $test_generator1,
					'test2' => $test_generator2,
				];
			}
		);

		/* Act. */
		$generator->init_email_generators();
	}
}
