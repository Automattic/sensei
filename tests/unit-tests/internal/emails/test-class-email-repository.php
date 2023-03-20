<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Repository;

/**
 * Tests for Sensei\Internal\Emails\Email_Repository class.
 *
 * @covers \Sensei\Internal\Emails\Email_Repository
 */
class Email_Repository_Test extends \WP_UnitTestCase {

	public function testHas_EmailNotInRepository_ReturnsFalse() {
		/* Arrange. */
		$repository = new Email_Repository();

		/* Act. */
		$result = $repository->has( 'a' );

		/* Assert. */
		$this->assertFalse( $result );
	}

	public function testHas_EmailCreated_ReturnsTrue() {
		/* Arrange. */
		$repository = new Email_Repository();
		$repository->create( 'a', [ 'b' ], 'c', 'd', 'e' );

		/* Act. */
		$result = $repository->has( 'a' );

		/* Assert. */
		$this->assertTrue( $result );
	}

	public function testHas_EmailDeletedFromRepository_ReturnsFalse() {
		/* Arrange. */
		$repository = new Email_Repository();
		$repository->create( 'a', [ 'b' ], 'c', 'd', 'e' );
		$repository->delete( 'a' );

		/* Act. */
		$result = $repository->has( 'a' );

		/* Assert. */
		$this->assertFalse( $result );
	}

	public function testGet_EmailNotInRepository_ReturnsNull() {
		/* Arrange. */
		$repository = new Email_Repository();

		/* Act. */
		$result = $repository->get( 'a' );

		/* Assert. */
		$this->assertNull( $result );
	}

	public function testGet_EmailCreated_ReturnsEmail() {
		/* Arrange. */
		$repository = new Email_Repository();
		$repository->create( 'a', [ 'b' ], 'c', 'd', 'e' );

		/* Act. */
		$result = $repository->get( 'a' );

		/* Assert. */
		$this->assertEquals( 'c', $result->post_title );
	}

	public function testHasEmails_EmailsNotInRepository_ReturnsFalse() {
		/* Arrange. */
		$repository = new Email_Repository();

		/* Act. */
		$result = $repository->has_emails();

		/* Assert. */
		$this->assertFalse( $result );
	}

	public function testHasEmails_EmailsCreated_ReturnsTrue() {
		/* Arrange. */
		$repository = new Email_Repository();
		$repository->create( 'a', [ 'b' ], 'c', 'd', 'e' );
		$repository->create( 'f', [ 'g' ], 'h', 'i', 'j' );

		/* Act. */
		$result = $repository->has_emails();

		/* Assert. */
		$this->assertTrue( $result );
	}

	public function testGetAll_EmailsNotInRepository_ReturnsEmptyResult() {
		/* Arrange. */
		$repository = new Email_Repository();

		/* Act. */
		$result = $repository->get_all();

		/* Assert. */
		$expected = [
			'items'       => [],
			'total_items' => 0,
			'total_pages' => 0,
		];
		$this->assertSame( $expected, (array) $result );
	}


	public function testGetAll_EmailsFound_ReturnsMatchingResult() {
		/* Arrange. */
		$repository = new Email_Repository();
		$repository->create( 'a', [ 'b' ], 'c', 'd', 'e' );
		$repository->create( 'f', [ 'g' ], 'h', 'i', 'j' );
		$email_a = $repository->get( 'a' );
		$email_f = $repository->get( 'f' );

		/* Act. */
		$result = $repository->get_all();

		/* Assert. */
		$expected = [
			'items'       => [ $email_a, $email_f ],
			'total_items' => 2,
			'total_pages' => 1,
		];
		$this->assertEquals( $expected, (array) $result );
	}

	public function testGetAll_TypeProvided_ReturnsMatchingResult() {
		/* Arrange. */
		$repository = new Email_Repository();
		$repository->create( 'a', [ 'b' ], 'c', 'd', 'e' );
		$repository->create( 'f', [ 'g' ], 'h', 'i', 'j' );
		$email_a = $repository->get( 'a' );

		/* Act. */
		$result = $repository->get_all( 'b' );

		/* Assert. */
		$expected = [
			'items'       => [ $email_a ],
			'total_items' => 1,
			'total_pages' => 1,
		];
		$this->assertEquals( $expected, (array) $result );
	}

	public function testGetAll_PaginationParamsGiven_ReturnsMatchingResult() {
		/* Arrange. */
		$repository = new Email_Repository();
		$repository->create( 'a', [ 'b' ], 'c', 'd', 'e' );
		$repository->create( 'f', [ 'g' ], 'h', 'i', 'j' );
		$email_f = $repository->get( 'f' );

		/* Act. */
		$result = $repository->get_all( null, 1, 1 );

		/* Assert. */
		$expected = [
			'items'       => [ $email_f ],
			'total_items' => 2,
			'total_pages' => 2,
		];
		$this->assertEquals( $expected, (array) $result );
	}
}
