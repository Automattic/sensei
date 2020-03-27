<?php
/**
 * File with trait Sensei_Scheduler_Test_Helpers.
 *
 * @package sensei-tests
 */

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Using PHPUnit conventions.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helpers for scheduler related tests.
 *
 * @since 3.0.0
 */
trait Sensei_Scheduler_Test_Helpers {
	/**
	 * Restore the scheduler's shim.
	 */
	private static function restoreScheduler() {
		self::resetScheduler();

		tests_add_filter( 'sensei_scheduler_class', [ __CLASS__, 'use_scheduler_shim' ] );
	}

	/**
	 * Reset the scheduler's instance.
	 */
	private static function resetScheduler() {
		$scheduler_instance = new ReflectionProperty( Sensei_Scheduler::class, 'instance' );
		$scheduler_instance->setAccessible( true );
		$scheduler_instance->setValue( null );

		remove_all_filters( 'sensei_scheduler_class' );
	}
}
