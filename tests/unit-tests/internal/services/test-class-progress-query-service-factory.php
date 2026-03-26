<?php

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Comments_Based_Grading_Listing_Service;
use Sensei\Internal\Services\Comments_Based_Progress_Aggregation_Service;
use Sensei\Internal\Services\Comments_Based_Progress_Clauses_Service;
use Sensei\Internal\Services\Progress_Query_Service_Factory;
use Sensei\Internal\Services\Tables_Based_Grading_Listing_Service;
use Sensei\Internal\Services\Tables_Based_Progress_Aggregation_Service;
use Sensei\Internal\Services\Tables_Based_Progress_Clauses_Service;

/**
 * Class Progress_Query_Service_Factory_Test.
 *
 * @covers \Sensei\Internal\Services\Progress_Query_Service_Factory
 */
class Progress_Query_Service_Factory_Test extends \WP_UnitTestCase {

	public function testCreateClausesService_WhenHppsDisabled_ReturnsCommentsBased(): void {
		/* Arrange. */
		\Sensei()->settings->settings['experimental_progress_storage']            = false;
		\Sensei()->settings->settings['experimental_progress_storage_repository'] = 'comments';
		$factory = new Progress_Query_Service_Factory();

		/* Act. */
		$service = $factory->create_clauses_service();

		/* Assert. */
		$this->assertInstanceOf( Comments_Based_Progress_Clauses_Service::class, $service );
	}

	public function testCreateClausesService_WhenHppsEnabled_ReturnsTablesBased(): void {
		/* Arrange. */
		\Sensei()->settings->settings['experimental_progress_storage']            = true;
		\Sensei()->settings->settings['experimental_progress_storage_repository'] = 'custom_tables';
		$factory = new Progress_Query_Service_Factory();

		/* Act. */
		$service = $factory->create_clauses_service();

		/* Assert. */
		$this->assertInstanceOf( Tables_Based_Progress_Clauses_Service::class, $service );
	}

	public function testCreateAggregationService_WhenHppsDisabled_ReturnsCommentsBased(): void {
		/* Arrange. */
		\Sensei()->settings->settings['experimental_progress_storage']            = false;
		\Sensei()->settings->settings['experimental_progress_storage_repository'] = 'comments';
		$factory = new Progress_Query_Service_Factory();

		/* Act. */
		$service = $factory->create_aggregation_service();

		/* Assert. */
		$this->assertInstanceOf( Comments_Based_Progress_Aggregation_Service::class, $service );
	}

	public function testCreateAggregationService_WhenHppsEnabled_ReturnsTablesBased(): void {
		/* Arrange. */
		\Sensei()->settings->settings['experimental_progress_storage']            = true;
		\Sensei()->settings->settings['experimental_progress_storage_repository'] = 'custom_tables';
		$factory = new Progress_Query_Service_Factory();

		/* Act. */
		$service = $factory->create_aggregation_service();

		/* Assert. */
		$this->assertInstanceOf( Tables_Based_Progress_Aggregation_Service::class, $service );
	}

	public function testCreateGradingListingService_WhenHppsDisabled_ReturnsCommentsBased(): void {
		/* Arrange. */
		\Sensei()->settings->settings['experimental_progress_storage']            = false;
		\Sensei()->settings->settings['experimental_progress_storage_repository'] = 'comments';
		$factory = new Progress_Query_Service_Factory();

		/* Act. */
		$service = $factory->create_grading_listing_service();

		/* Assert. */
		$this->assertInstanceOf( Comments_Based_Grading_Listing_Service::class, $service );
	}

	public function testCreateGradingListingService_WhenHppsEnabled_ReturnsTablesBased(): void {
		/* Arrange. */
		\Sensei()->settings->settings['experimental_progress_storage']            = true;
		\Sensei()->settings->settings['experimental_progress_storage_repository'] = 'custom_tables';
		$factory = new Progress_Query_Service_Factory();

		/* Act. */
		$service = $factory->create_grading_listing_service();

		/* Assert. */
		$this->assertInstanceOf( Tables_Based_Grading_Listing_Service::class, $service );
	}
}
