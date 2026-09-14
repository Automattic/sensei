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

/**
 * Overview list table factory.
 *
 * @since 4.3.0
 */
class Sensei_Reports_Overview_List_Table_Factory {
	/**
	 * Sensei course services.
	 *
	 * @var Sensei_Course
	 */
	private Sensei_Course $course;

	/**
	 * Progress clauses service.
	 *
	 * @var Progress_Clauses_Service_Interface
	 */
	private Progress_Clauses_Service_Interface $progress_clauses_service;

	/**
	 * Progress aggregation service.
	 *
	 * @var Progress_Aggregation_Service_Interface
	 */
	private Progress_Aggregation_Service_Interface $aggregation_service;

	/**
	 * Grading statistics service.
	 *
	 * @var Grading_Stats_Service_Interface
	 */
	private Grading_Stats_Service_Interface $grading_stats_service;

	/**
	 * Constructor.
	 *
	 * @param Sensei_Course                          $course                   Sensei course services.
	 * @param Progress_Clauses_Service_Interface     $progress_clauses_service Progress clauses service.
	 * @param Progress_Aggregation_Service_Interface $aggregation_service      Progress aggregation service.
	 * @param Grading_Stats_Service_Interface        $grading_stats_service    Grading statistics service.
	 */
	public function __construct( Sensei_Course $course, Progress_Clauses_Service_Interface $progress_clauses_service, Progress_Aggregation_Service_Interface $aggregation_service, Grading_Stats_Service_Interface $grading_stats_service ) {
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
		switch ( $type ) {
			case 'users':
			case 'students':
				return new Sensei_Reports_Overview_List_Table_Students(
					new Sensei_Reports_Overview_Data_Provider_Students(),
					new Sensei_Reports_Overview_Service_Students(
						$this->aggregation_service,
						$this->grading_stats_service
					)
				);
			case 'courses':
				return new Sensei_Reports_Overview_List_Table_Courses(
					Sensei()->grading,
					$this->course,
					new Sensei_Reports_Overview_Data_Provider_Courses( $this->progress_clauses_service ),
					new Sensei_Reports_Overview_Service_Courses( $this->grading_stats_service ),
					$this->aggregation_service
				);
			case 'lessons':
				return new Sensei_Reports_Overview_List_Table_Lessons(
					$this->course,
					new Sensei_Reports_Overview_Data_Provider_Lessons( $this->course, $this->progress_clauses_service ),
					$this->aggregation_service
				);
			default:
				throw new InvalidArgumentException( 'Unknown list table type' );
		}
	}
}
