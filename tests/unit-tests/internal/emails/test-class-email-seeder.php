<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Repository;
use Sensei\Internal\Emails\Email_Seeder;
use Sensei\Internal\Emails\Email_Seeder_Data;

/**
 * Tests for Sensei\Internal\Emails\Email_Seeder class.
 *
 * @covers \Sensei\Internal\Emails\Email_Seeder
 */
class Email_Seeder_Test extends \WP_UnitTestCase {

	public function testInit_Always_AppliesFilter() {
		/* Arrange. */
		$data = $this->createMock( Email_Seeder_Data::class );
		$data->method( 'get_email_data' )->willReturn( [] );

		$repository = $this->createMock( Email_Repository::class );

		$seeder = new Email_Seeder( $data, $repository );

		$changed_value = false;
		$filter        = function( $value ) use ( &$changed_value ) {
			$changed_value = true;
			return $value;
		};
		add_filter( 'sensei_email_seeder_data', $filter );

		/* Act. */
		$seeder->init();

		/* Assert. */
		$this->assertTrue( $changed_value );

		/* Cleanup. */
		remove_filter( 'sensei_email_seeder_data', $filter );
	}

	public function testCreateEmail_Always_ChecksIfEmailExists() {
		/* Arrange. */
		$data = $this->createMock( Email_Seeder_Data::class );
		$data->method( 'get_email_data' )->willReturn( [] );

		$repository = $this->createMock( Email_Repository::class );

		$seeder = new Email_Seeder( $data, $repository );
		$seeder->init();

		/* Expect & Act. */
		$repository
			->expects( $this->once() )
			->method( 'has' )
			->with( 'test' )
			->willReturn( false );
		$seeder->create_email( 'test' );
	}

	public function testCreateEmail_WhenEmailExistsAndForceIsFalse_ReturnsFalse() {
		/* Arrange. */
		$data = $this->createMock( Email_Seeder_Data::class );
		$data->method( 'get_email_data' )->willReturn( [] );

		$repository = $this->createMock( Email_Repository::class );
		$repository->method( 'has' )->with( 'test' )->willReturn( true );

		$seeder = new Email_Seeder( $data, $repository );
		$seeder->init();

		/* Act. */
		$result = $seeder->create_email( 'test', false );

		/* Assert. */
		$this->assertFalse( $result );
	}

	public function testCreateEmail_WhenEmailExistsAndForceIsFalse_NeverDeletesFromRepository() {
		/* Arrange. */
		$data = $this->createMock( Email_Seeder_Data::class );
		$data->method( 'get_email_data' )->willReturn( [] );

		$repository = $this->createMock( Email_Repository::class );
		$repository->method( 'has' )->with( 'test' )->willReturn( true );

		$seeder = new Email_Seeder( $data, $repository );
		$seeder->init();

		/* Expect & Act. */
		$repository
			->expects( $this->never() )
			->method( 'delete' );
		$seeder->create_email( 'test', false );
	}

	public function testCreateEmail_WhenEmailExistsAndForceIsTrue_DeletesFromRepository() {
		/* Arrange. */
		$data = $this->createMock( Email_Seeder_Data::class );
		$data->method( 'get_email_data' )->willReturn( [] );

		$repository = $this->createMock( Email_Repository::class );
		$repository->method( 'has' )->with( 'test' )->willReturn( true );

		$seeder = new Email_Seeder( $data, $repository );
		$seeder->init();

		/* Expect & Act. */
		$repository
			->expects( $this->once() )
			->method( 'delete' )
			->with( 'test' );
		$seeder->create_email( 'test', true );
	}


	/**
	 * Tests that it returns false when the email data is incomplete.
	 *
	 * @dataProvider providerCreateEmail_WhenIncomleteData_ReturnsFalse
	 */
	public function testCreateEmail_WhenIncomleteData_ReturnsFalse() {
		/* Arrange. */
		$data = $this->createMock( Email_Seeder_Data::class );
		$data->method( 'get_email_data' )->willReturn( [] );

		$repository = $this->createMock( Email_Repository::class );
		$repository->method( 'has' )->with( 'test' )->willReturn( false );

		$seeder = new Email_Seeder( $data, $repository );
		$seeder->init();

		/* Act. */
		$result = $seeder->create_email( 'test' );

		/* Assert. */
		$this->assertFalse( $result );
	}

	public function providerCreateEmail_WhenIncomleteData_ReturnsFalse(): array {
		return [
			'no data'    => [],
			'no types'   => [
				'test' => [
					'subject'     => 'a',
					'content'     => 'b',
					'description' => 'c',
				],
			],
			'no subject' => [
				'test' => [
					'types'       => [ 'a' ],
					'content'     => 'b',
					'description' => 'c',
				],
			],
			'no content' => [
				'test' => [
					'types'       => [ 'a' ],
					'subject'     => 'b',
					'description' => 'c',
				],
			],
		];
	}

	public function testCreateEmail_WhenDataIsComplete_CreatesEmail() {
		/* Arrange. */
		$data = $this->createMock( Email_Seeder_Data::class );
		$data
			->method( 'get_email_data' )
			->willReturn(
				[
					'test' => [
						'types'       => [ 'a' ],
						'subject'     => 'b',
						'content'     => 'c',
						'description' => 'd',
					],
				]
			);

		$repository = $this->createMock( Email_Repository::class );
		$repository->method( 'has' )->with( 'test' )->willReturn( false );

		$seeder = new Email_Seeder( $data, $repository );
		$seeder->init();

		/* Expect & Act. */
		$repository
			->expects( $this->once() )
			->method( 'create' )
			->with(
				'test',
				[ 'a' ],
				'b',
				'd',
				'c'
			)
			->willReturn( 1 );
		$seeder->create_email( 'test' );
	}

	public function testCreateEmail_WhenEmailCreated_ReturnsTrue() {
		/* Arrange. */
		$data = $this->createMock( Email_Seeder_Data::class );
		$data
			->method( 'get_email_data' )
			->willReturn(
				[
					'test' => [
						'types'       => [ 'a' ],
						'subject'     => 'b',
						'content'     => 'c',
						'description' => 'd',
					],
				]
			);

		$repository = $this->createMock( Email_Repository::class );
		$repository->method( 'has' )->with( 'test' )->willReturn( false );
		$repository
			->expects( $this->once() )
			->method( 'create' )
			->with(
				'test',
				[ 'a' ],
				'b',
				'd',
				'c'
			)
			->willReturn( 1 );

		$seeder = new Email_Seeder( $data, $repository );
		$seeder->init();

		/* Act. */
		$result = $seeder->create_email( 'test' );

		/* Assert. */
		self::assertTrue( $result );
	}

	public function testCreateAll_WhenCalled_CreatesEmailsInRepository() {
		/* Arrange. */
		$data = $this->createMock( Email_Seeder_Data::class );
		$data
			->method( 'get_email_data' )
			->willReturn(
				[
					'test'  => [
						'types'       => [ 'a' ],
						'subject'     => 'b',
						'content'     => 'c',
						'description' => 'd',
					],
					'test2' => [
						'types'       => [ 'e' ],
						'subject'     => 'f',
						'content'     => 'g',
						'description' => 'h',
					],
				]
			);

		$repository = $this->createMock( Email_Repository::class );

		$seeder = new Email_Seeder( $data, $repository );
		$seeder->init();

		/* Expect & Act. */
		$repository
			->expects( $this->exactly( 2 ) )
			->method( 'create' )
			->willReturnMap(
				[
					[
						'test',
						[ 'a' ],
						'b',
						'd',
						'c',
						1,
					],
					[
						'test2',
						[ 'e' ],
						'f',
						'h',
						'g',
						2,
					],
				]
			);
		$seeder->create_all();
	}

	public function testCreateAll_WhenEmailsCreatedSuccessfully_ReturnsTrue() {
		/* Arrange. */
		$data = $this->createMock( Email_Seeder_Data::class );
		$data
			->method( 'get_email_data' )
			->willReturn(
				[
					'test'  => [
						'types'       => [ 'a' ],
						'subject'     => 'b',
						'content'     => 'c',
						'description' => 'd',
					],
					'test2' => [
						'types'       => [ 'e' ],
						'subject'     => 'f',
						'content'     => 'g',
						'description' => 'h',
					],
				]
			);

		$repository = $this->createMock( Email_Repository::class );
		$repository->method( 'create' )->willReturnOnConsecutiveCalls( 1, 2 );

		$seeder = new Email_Seeder( $data, $repository );
		$seeder->init();

		/* Act. */
		$result = $seeder->create_all();

		/* Assert. */
		self::assertTrue( $result );
	}

	public function testCreateAll_WhenOneEmailNotCreated_ReturnsFalse() {
		/* Arrange. */
		$data = $this->createMock( Email_Seeder_Data::class );
		$data
			->method( 'get_email_data' )
			->willReturn(
				[
					'test'  => [
						'types'       => [ 'a' ],
						'subject'     => 'b',
						'content'     => 'c',
						'description' => 'd',
					],
					'test2' => [
						'types'       => [ 'e' ],
						'subject'     => 'f',
						'content'     => 'g',
						'description' => 'h',
					],
				]
			);

		$repository = $this->createMock( Email_Repository::class );
		$repository->method( 'create' )->willReturnOnConsecutiveCalls( 1, false );

		$seeder = new Email_Seeder( $data, $repository );
		$seeder->init();

		/* Act. */
		$result = $seeder->create_all();

		/* Assert. */
		self::assertFalse( $result );
	}
}
