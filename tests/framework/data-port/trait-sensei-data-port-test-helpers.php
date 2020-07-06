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
}
