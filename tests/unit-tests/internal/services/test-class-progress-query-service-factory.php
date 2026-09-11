<?php

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Comments_Based_Grading_Listing_Service;
use Sensei\Internal\Services\Comments_Based_Grading_Stats_Service;
use Sensei\Internal\Services\Comments_Based_Progress_Aggregation_Service;
use Sensei\Internal\Services\Comments_Based_Progress_Clauses_Service;
use Sensei\Internal\Services\Comments_Based_Reports_Listing_Service;
use Sensei\Internal\Services\Progress_Query_Service_Factory;
use Sensei\Internal\Services\Progress_Storage_Configuration;
use Sensei\Internal\Services\Progress_Storage_Settings;
use Sensei\Internal\Services\Tables_Based_Grading_Listing_Service;
use Sensei\Internal\Services\Tables_Based_Grading_Stats_Service;
use Sensei\Internal\Services\Tables_Based_Progress_Aggregation_Service;
use Sensei\Internal\Services\Tables_Based_Progress_Clauses_Service;
use Sensei\Internal\Services\Tables_Based_Reports_Listing_Service;

/**
 * Class Progress_Query_Service_Factory_Test.
 *
 * @covers \Sensei\Internal\Services\Progress_Query_Service_Factory
 */
class Progress_Query_Service_Factory_Test extends \WP_UnitTestCase {

	public function testCreateClausesService_WhenHppsDisabled_ReturnsCommentsBased(): void {
		/* Arrange. */
		$factory = $this->create_factory( false, Progress_Storage_Settings::COMMENTS_STORAGE, false );

		/* Act. */
		$service = $factory->create_clauses_service();

		/* Assert. */
		$this->assertInstanceOf( Comments_Based_Progress_Clauses_Service::class, $service );
	}

	public function testCreateClausesService_WhenHppsEnabled_ReturnsTablesBased(): void {
		/* Arrange. */
		$factory = $this->create_factory( true, Progress_Storage_Settings::TABLES_STORAGE, true );

		/* Act. */
		$service = $factory->create_clauses_service();

		/* Assert. */
		$this->assertInstanceOf( Tables_Based_Progress_Clauses_Service::class, $service );
	}

	public function testCreateAggregationService_WhenHppsDisabled_ReturnsCommentsBased(): void {
		/* Arrange. */
		$factory = $this->create_factory( false, Progress_Storage_Settings::COMMENTS_STORAGE, false );

		/* Act. */
		$service = $factory->create_aggregation_service();

		/* Assert. */
		$this->assertInstanceOf( Comments_Based_Progress_Aggregation_Service::class, $service );
	}

	public function testCreateAggregationService_WhenHppsEnabled_ReturnsTablesBased(): void {
		/* Arrange. */
		$factory = $this->create_factory( true, Progress_Storage_Settings::TABLES_STORAGE, true );

		/* Act. */
		$service = $factory->create_aggregation_service();

		/* Assert. */
		$this->assertInstanceOf( Tables_Based_Progress_Aggregation_Service::class, $service );
	}

	public function testCreateGradingListingService_WhenHppsDisabled_ReturnsCommentsBased(): void {
		/* Arrange. */
		$factory = $this->create_factory( false, Progress_Storage_Settings::COMMENTS_STORAGE, false );

		/* Act. */
		$service = $factory->create_grading_listing_service();

		/* Assert. */
		$this->assertInstanceOf( Comments_Based_Grading_Listing_Service::class, $service );
	}

	public function testCreateGradingListingService_WhenHppsEnabled_ReturnsTablesBased(): void {
		/* Arrange. */
		$factory = $this->create_factory( true, Progress_Storage_Settings::TABLES_STORAGE, true );

		/* Act. */
		$service = $factory->create_grading_listing_service();

		/* Assert. */
		$this->assertInstanceOf( Tables_Based_Grading_Listing_Service::class, $service );
	}

	public function testCreateGradingStatsService_WhenCommentsAreAuthoritative_ReturnsCommentsBased(): void {
		/* Arrange. */
		$factory = $this->create_factory( true, Progress_Storage_Settings::COMMENTS_STORAGE, true );

		/* Act. */
		$service = $factory->create_grading_stats_service();

		/* Assert. */
		$this->assertInstanceOf( Comments_Based_Grading_Stats_Service::class, $service );
	}

	public function testCreateGradingStatsService_WhenTablesAreAuthoritative_ReturnsTablesBased(): void {
		/* Arrange. */
		$factory = $this->create_factory( true, Progress_Storage_Settings::TABLES_STORAGE, true );

		/* Act. */
		$service = $factory->create_grading_stats_service();

		/* Assert. */
		$this->assertInstanceOf( Tables_Based_Grading_Stats_Service::class, $service );
	}

	public function testCreateReportsListingService_WhenCommentsAreAuthoritative_ReturnsCommentsBased(): void {
		/* Arrange. */
		$factory = $this->create_factory( true, Progress_Storage_Settings::COMMENTS_STORAGE, true );

		/* Act. */
		$service = $factory->create_reports_listing_service();

		/* Assert. */
		$this->assertInstanceOf( Comments_Based_Reports_Listing_Service::class, $service );
	}

	public function testCreateReportsListingService_WhenTablesAreAuthoritative_ReturnsTablesBased(): void {
		/* Arrange. */
		$factory = $this->create_factory( true, Progress_Storage_Settings::TABLES_STORAGE, true );

		/* Act. */
		$service = $factory->create_reports_listing_service();

		/* Assert. */
		$this->assertInstanceOf( Tables_Based_Reports_Listing_Service::class, $service );
	}

	/**
	 * Creates a query service factory for the requested raw settings.
	 *
	 * @param bool   $hpps_enabled            Whether HPPS is enabled.
	 * @param string $selected_repository     The selected repository setting.
	 * @param bool   $synchronization_enabled Whether synchronization is enabled.
	 * @return Progress_Query_Service_Factory
	 */
	private function create_factory( bool $hpps_enabled, string $selected_repository, bool $synchronization_enabled ): Progress_Query_Service_Factory {
		$configuration = Progress_Storage_Configuration::resolve(
			array(
				'experimental_progress_storage'            => $hpps_enabled,
				'experimental_progress_storage_repository' => $selected_repository,
				'experimental_progress_storage_synchronization' => $synchronization_enabled,
			)
		);

		return new Progress_Query_Service_Factory( $configuration );
	}
}
