<?php

class Sensei_Reports_Overview_ListTable_Factory {
	public function create( string $type ) {
		switch ( $type ) {
			case 'students':
				return new Sensei_Reports_Overview_List_Table_Students();
			case 'courses':
				return new Sensei_Reports_Overview_List_Table_Courses(
					Sensei()->grading,
					Sensei()->course,
					new Sensei_Reports_Overview_Data_Provider_Courses()
				);
			case 'lessons':
				return new Sensei_Reports_Overview_List_Table_Lessons();
			default:
				throw new InvalidArgumentException( 'Unknown list table type' );
		}
	}
}
