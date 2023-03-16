<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Template_Repository;

/**
 * Tests for Sensei\Internal\Emails\Email_Template_Repository class.
 *
 * @covers \Sensei\Internal\Emails\Email_Template_Repository
 */
class Email_Template_Repository_Test extends \WP_UnitTestCase {


	public function testHas_EmailCreated_ReturnsPostId() {
		/* Arrange. */
		$repository = new Email_Template_Repository();

		/* Act. */
		$result = $repository->create( );

		/* Assert. */
		$this->assertTrue( $result > 0 );
	}


	public function testHas_EmailCreate_ReturnsOnlyTheFirstCreated() {
		/* Arrange. */
		$repository = new Email_Template_Repository();
		$first = $repository->create( );
		$second = $repository->create( );

		/* Act. */
		$result = $repository->get();

		/* Assert. */
		$this->assertTrue( $result->wp_id == $first);
	}

	public function testHas_EmailGet_ReturnsTheTemplate() {
		/* Arrange. */
		$repository = new Email_Template_Repository();
		$repository->create( );

		/* Act. */
		$result = $repository->get();

		/* Assert. */
		$this->assertNotNull( $result->wp_id );
	}

	public function testHas_EmailGet_WhenThereIsNoTemplateCreated_ReturnsNull() {
		/* Arrange. */
		$repository = new Email_Template_Repository();
		$repository->delete_all();

		/* Act. */
		$result = $repository->get();

		/* Assert. */
		$this->assertNull( $result );
	}


	public function testHas_EmailDeleteAll_ReturnsTrue() {
		/* Arrange. */
		$repository = new Email_Template_Repository();
		$repository->create( );
		$repository->create( );

		/* Act. */
		$result = $repository->delete_all();

		$this->assertTrue( $result );
	}

}
