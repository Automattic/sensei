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

	/**
	 * Tests that a disabled email is found in the repository.
	 */
	public function testHas_DisabledEmailInRepository_ReturnsTrue() {
		/* Arrange. */
		$repository = new Email_Repository();
		$repository->create( 'disabled', [ 'b' ], 'c', 'd', 'e', false, true );

		/* Act. */
		$result = $repository->has( 'disabled' );

		/* Assert. */
		$this->assertTrue( $result );
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

	public function testCreate_DisabledEmail_CreatesPostAsDraft() {
		/* Arrange. */
		$repository = new Email_Repository();

		/* Act. */
		$post_id = $repository->create( 'a', [ 'b' ], 'c', 'd', 'e', false, true );
		$post    = get_post( $post_id );

		/* Assert. */
		$this->assertEquals( 'draft', $post->post_status );
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

	public function testGet_EmailCreatedWithIsProParam_SavesIsProMetaAsExpected() {
		/* Arrange. */
		$repository = new Email_Repository();
		$repository->create( 'a', [ 'b' ], 'c', 'd', 'e' );
		$repository->create( 'x', [ 'b' ], 'y', 'd', 'e', true );

		/* Act. */
		$result1 = $repository->get( 'a' );
		$result2 = $repository->get( 'x' );

		/* Assert. */
		$this->assertEquals( 'c', $result1->post_title );
		$this->assertEquals( '', get_post_meta( $result1->ID, '_sensei_email_is_pro', true ) );
		$this->assertEquals( 'y', $result2->post_title );
		$this->assertEquals( 1, get_post_meta( $result2->ID, '_sensei_email_is_pro', true ) );
	}

	public function testGet_EmailCreatedWithDisabledParam_ReturnsNull() {
		/* Arrange. */
		$repository = new Email_Repository();
		$repository->create( 'a', [ 'b' ], 'c', 'd', 'e', false, true );

		/* Act. */
		$result = $repository->get( 'a' );

		/* Assert. */
		$this->assertNull( $result );
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

	/**
	 * Tests that when only disabled emails are created, that they are still found in the repository.
	 */
	public function testHasEmails_AllEmailsDisabled_ReturnsTrue() {
		/* Arrange. */
		$repository = new Email_Repository();
		$repository->create( 'a', [ 'b' ], 'c', 'd', 'e', false, true );
		$repository->create( 'f', [ 'g' ], 'h', 'i', 'j', false, true );

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
