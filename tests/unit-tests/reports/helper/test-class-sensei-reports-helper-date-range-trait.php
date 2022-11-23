<?php

class Sensei_Reports_Helper_Date_Range_Trait_Test extends WP_UnitTestCase {
	use Sensei_Reports_Helper_Date_Range_Trait;

	public function testGetTimezone_WhenCalledWithUserTimezoneSet_ReturnsTheUserTimezone() {
		$_GET['timezone'] = 'UTC';

		$this->assertEquals( 'UTC', $this->get_timezone() );
	}

	public function testGetTimezone_WhenCalledWithNotUserTimezoneSet_ReturnsTheSiteTimezone() {
		$this->assertEquals( '+00:00', $this->get_timezone() );
	}

	public function testGetStartDateAndTime_WhenCalledWithUserTimezoneSet_ReturnsTheDateTimeInUTC() {
		$_GET['start_date'] = '2022-01-02';
		$_GET['timezone']   = '+02:00';

		$this->assertEquals( '2022-01-01 22:00:00', $this->get_start_date_and_time() );
	}

	public function testGetEndDateAndTime_WhenCalledWithUserTimezoneSet_ReturnsTheDateTimeInUTC() {
		$_GET['end_date'] = '2022-01-02';
		$_GET['timezone'] = '+02:00';

		$this->assertEquals( '2022-01-02 21:59:59', $this->get_end_date_and_time() );
	}
}
