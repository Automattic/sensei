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
		$use_tables = $this->use_tables();

		switch ( $type ) {
			case 'users':
			case 'students':
				return new Sensei_Reports_Overview_List_Table_Students(
					$use_tables
						? new Sensei_Reports_Overview_Data_Provider_Students_Tables()
						: new Sensei_Reports_Overview_Data_Provider_Students(),
					$use_tables
						? new Sensei_Reports_Overview_Service_Students_Tables()
						: new Sensei_Reports_Overview_Service_Students()
				);
			case 'courses':
				return new Sensei_Reports_Overview_List_Table_Courses(
					Sensei()->grading,
					Sensei()->course,
					$use_tables
						? new Sensei_Reports_Overview_Data_Provider_Courses_Tables()
						: new Sensei_Reports_Overview_Data_Provider_Courses(),
					$use_tables
						? new Sensei_Reports_Overview_Service_Courses_Tables()
						: new Sensei_Reports_Overview_Service_Courses()
				);
			case 'lessons':
				return new Sensei_Reports_Overview_List_Table_Lessons(
					Sensei()->course,
					$use_tables
						? new Sensei_Reports_Overview_Data_Provider_Lessons_Tables( Sensei()->course )
						: new Sensei_Reports_Overview_Data_Provider_Lessons( Sensei()->course )
				);
			default:
				throw new InvalidArgumentException( 'Unknown list table type' );
		}
	}

	/**
	 * Check if HPPS tables mode is active.
	 *
	 * @since $$next-version$$
	 *
	 * @return bool
	 */
	private function use_tables(): bool {
		return \Sensei\Internal\Services\Progress_Storage_Settings::is_hpps_enabled()
			&& \Sensei\Internal\Services\Progress_Storage_Settings::is_tables_repository();
	}
}
