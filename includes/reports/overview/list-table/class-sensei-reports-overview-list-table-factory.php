<?php
/**
 * File containing the Sensei_Reports_Overview_List_Table_Factory class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Sensei\Internal\Services\Grading_Stats_Service_Interface;
use Sensei\Internal\Services\Progress_Aggregation_Service_Interface;
use Sensei\Internal\Services\Progress_Clauses_Service_Interface;
use Sensei\Internal\Services\Progress_Query_Service_Factory;

/**
 * Overview list table factory.
 *
 * @since 4.3.0
 */
class Sensei_Reports_Overview_List_Table_Factory {
	/**
	 * Course-related functionality.
	 *
	 * @var Sensei_Course|null
	 */
	private ?Sensei_Course $course;

	/**
	 * Progress clauses service.
	 *
	 * @var Progress_Clauses_Service_Interface|null
	 */
	private ?Progress_Clauses_Service_Interface $progress_clauses_service;

	/**
	 * Progress aggregation service.
	 *
	 * @var Progress_Aggregation_Service_Interface|null
	 */
	private ?Progress_Aggregation_Service_Interface $aggregation_service;

	/**
	 * Grading statistics service.
	 *
	 * @var Grading_Stats_Service_Interface|null
	 */
	private ?Grading_Stats_Service_Interface $grading_stats_service;

	/**
	 * Constructor.
	 *
	 * @param Sensei_Course|null                          $course                   Course-related functionality.
	 * @param Progress_Clauses_Service_Interface|null     $progress_clauses_service Progress clauses service.
	 * @param Progress_Aggregation_Service_Interface|null $aggregation_service      Progress aggregation service.
	 * @param Grading_Stats_Service_Interface|null        $grading_stats_service    Grading statistics service.
	 */
	public function __construct( ?Sensei_Course $course = null, ?Progress_Clauses_Service_Interface $progress_clauses_service = null, ?Progress_Aggregation_Service_Interface $aggregation_service = null, ?Grading_Stats_Service_Interface $grading_stats_service = null ) {
		$this->course                   = $course;
		$this->progress_clauses_service = $progress_clauses_service;
		$this->aggregation_service      = $aggregation_service;
		$this->grading_stats_service    = $grading_stats_service;
	}

	/**
	 * Creates a new list table instance for reports overview.
	 *
	 * @param string $type The report type.
	 *
	 * @return Sensei_List_Table
	 *
	 * @throws InvalidArgumentException If the report type is not supported.
	 */
	public function create( string $type ) {
		$progress_clauses_service = $this->progress_clauses_service;
		$aggregation_service      = $this->aggregation_service;
		$grading_stats_service    = $this->grading_stats_service;

		if ( null === $progress_clauses_service || null === $aggregation_service || null === $grading_stats_service ) {
			$query_service_factory    = new Progress_Query_Service_Factory( Sensei()->progress_storage_configuration );
			$progress_clauses_service = $progress_clauses_service ?? $query_service_factory->create_clauses_service();
			$aggregation_service      = $aggregation_service ?? $query_service_factory->create_aggregation_service();
			$grading_stats_service    = $grading_stats_service ?? $query_service_factory->create_grading_stats_service();

			$this->progress_clauses_service = $progress_clauses_service;
			$this->aggregation_service      = $aggregation_service;
			$this->grading_stats_service    = $grading_stats_service;
		}

		switch ( $type ) {
			case 'users':
			case 'students':
				return new Sensei_Reports_Overview_List_Table_Students(
					new Sensei_Reports_Overview_Data_Provider_Students(),
					new Sensei_Reports_Overview_Service_Students(
						$aggregation_service,
						$grading_stats_service
					)
				);
			case 'courses':
				return new Sensei_Reports_Overview_List_Table_Courses(
					Sensei()->grading,
					$this->get_course(),
					new Sensei_Reports_Overview_Data_Provider_Courses( $progress_clauses_service ),
					new Sensei_Reports_Overview_Service_Courses( $grading_stats_service, $aggregation_service ),
					$aggregation_service
				);
			case 'lessons':
				return new Sensei_Reports_Overview_List_Table_Lessons(
					$this->get_course(),
					new Sensei_Reports_Overview_Data_Provider_Lessons( $this->get_course(), $progress_clauses_service ),
					$aggregation_service
				);
			default:
				throw new InvalidArgumentException( 'Unknown list table type' );
		}
	}

	/**
	 * Get the injected course functionality or the legacy default when needed.
	 *
	 * @return Sensei_Course
	 */
	private function get_course(): Sensei_Course {
		return $this->course ??= Sensei()->course;
	}
}
