<?php
/**
 * File containing the Sensei_Reports_Overview_List_Table_Factory class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

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
		switch ( $type ) {
			case 'users':
			case 'students':
				return new Sensei_Reports_Overview_List_Table_Students(
					new Sensei_Reports_Overview_Data_Provider_Students(),
					new Sensei_Reports_Overview_Service_Students()
				);
			case 'courses':
				return new Sensei_Reports_Overview_List_Table_Courses(
					Sensei()->grading,
					Sensei()->course,
					new Sensei_Reports_Overview_Data_Provider_Courses(),
					new Sensei_Reports_Overview_Service_Courses()
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
