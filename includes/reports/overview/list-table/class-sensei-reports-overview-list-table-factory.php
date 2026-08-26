<?php
/**
 * File containing the Sensei_Reports_Overview_List_Table_Factory class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Sensei\Internal\Services\Progress_Query_Service_Factory;

/**
 * Overview list table factory.
 *
 * @since 4.3.0
 */
class Sensei_Reports_Overview_List_Table_Factory {

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
		$query_service_factory = new Progress_Query_Service_Factory();

		switch ( $type ) {
			case 'users':
			case 'students':
				return new Sensei_Reports_Overview_List_Table_Students(
					new Sensei_Reports_Overview_Data_Provider_Students(),
					new Sensei_Reports_Overview_Service_Students(
						$query_service_factory->create_aggregation_service(),
						$query_service_factory->create_grading_stats_service()
					)
				);
			case 'courses':
				return new Sensei_Reports_Overview_List_Table_Courses(
					Sensei()->grading,
					Sensei()->course,
					new Sensei_Reports_Overview_Data_Provider_Courses(),
					new Sensei_Reports_Overview_Service_Courses(),
					$query_service_factory->create_aggregation_service()
				);
			case 'lessons':
				return new Sensei_Reports_Overview_List_Table_Lessons(
					Sensei()->course,
					new Sensei_Reports_Overview_Data_Provider_Lessons( Sensei()->course )
				);
			default:
				throw new InvalidArgumentException( 'Unknown list table type' );
		}
	}
}
