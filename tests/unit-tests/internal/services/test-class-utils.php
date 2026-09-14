<?php

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Utils;

/**
 * Tests for the internal services utilities.
 *
 * @covers \Sensei\Internal\Services\Utils
 */
class Utils_Test extends \WP_UnitTestCase {

	public function testGetStatusesSql_ScalarStatusGiven_ReturnsQuotedStatus(): void {
		/* Arrange. */
		global $wpdb;

		/* Act. */
		$result = Utils::get_statuses_sql( $wpdb, array( 'status' => 'passed' ) );

		/* Assert. */
		$this->assertSame( "'passed'", $result );
	}

	public function testGetStatusesSql_MultipleStatusesGiven_ReturnsQuotedList(): void {
		/* Arrange. */
		global $wpdb;

		/* Act. */
		$result = Utils::get_statuses_sql( $wpdb, array( 'status' => array( 'graded', 'passed', 'failed' ) ) );

		/* Assert. */
		$this->assertSame( "'graded','passed','failed'", $result );
	}

	public function testGetStatusesSql_StatusMissing_ReturnsNoMatchStatus(): void {
		/* Arrange. */
		global $wpdb;

		/* Act. */
		$result = Utils::get_statuses_sql( $wpdb, array() );

		/* Assert. */
		$this->assertSame( "'__none__'", $result );
	}

	public function testGetStatusesSql_EmptyStatusesGiven_ReturnsNoMatchStatus(): void {
		/* Arrange. */
		global $wpdb;

		/* Act. */
		$result = Utils::get_statuses_sql( $wpdb, array( 'status' => array() ) );

		/* Assert. */
		$this->assertSame( "'__none__'", $result );
	}

	public function testGetStatusesSql_StatusContainingQuoteGiven_ReturnsEscapedStatus(): void {
		/* Arrange. */
		global $wpdb;

		/* Act. */
		$result = Utils::get_statuses_sql( $wpdb, array( 'status' => "passed' OR 1=1 --" ) );

		/* Assert. */
		$this->assertSame( "'passed\\' OR 1=1 --'", $result );
	}
}
