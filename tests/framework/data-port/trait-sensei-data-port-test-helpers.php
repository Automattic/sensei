<?php
/**
 * File with trait Sensei_Data_Port_Test_Helpers.
 *
 * @package sensei-tests
 */

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Using PHPUnit conventions.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helpers for data port related tests.
 *
 * @since 3.2.0
 */
trait Sensei_Data_Port_Test_Helpers {
	/**
	 * Assert a job has a log entry.
	 *
	 * @param Sensei_Data_Port_Job $job       Job object.
	 * @param string               $log_entry Log message to look for.
	 * @param null                 $message   Message to show in assertion.
	 */
	protected function assertJobHasLogEntry( Sensei_Data_Port_Job $job, $log_entry, $message = null ) {
		$logs = $job->get_logs();
		$has_entry = false;
		foreach ( $logs as $log ) {
			if ( $log_entry === $log['message'] ) {
				$has_entry = true;
				break;
			}
		}

		$this->assertTrue( $has_entry, $message );
	}


	/**
	 * Assert that a REST API response is valid.
	 *
	 * @param $result
	 */
	protected function assertResultValidJob( $result, $expected = [] ) {
		$this->assertTrue( isset( $result['id'], $result['status'], $result['files'] ) );
		$this->assertTrue( is_string( $result['id'] ) );
		$this->assertTrue( is_array( $result['status'] ) );
		$this->assertTrue( is_array( $result['files'] ) );
		$this->assertNotEmpty( $result['id'] );
		$this->assertNotEmpty( $result['status'] );
		$this->assertTrue( isset( $result['status']['status'], $result['status']['percentage'] ) );

		foreach ( $expected as $key => $value ) {
			$this->assertEquals( $result[ $key ], $value );
		}
	}
}
