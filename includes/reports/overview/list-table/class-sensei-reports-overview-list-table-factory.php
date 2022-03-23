<?php

class Sensei_Reports_Overview_ListTable_Factory {
	public function create( string $type ) {
		switch ( $type ) {
			case 'students':
				return new Sensei_Reports_Overview_ListTable_Students();
			case 'courses':
				return new Sensei_Reports_Overview_ListTable_Courses(
					Sensei()->grading,
					Sensei()->course,
					new Sensei_Reports_Overview_Data_Provider_Courses()
				);
			case 'lessons':
				return new Sensei_Reports_Overview_ListTable_Lessons();
			default:
				throw new InvalidArgumentException( 'Unknown list table type' );
		}
	}
}
