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
	private static function restoreShimScheduler() {
		self::resetScheduler();

		add_filter( 'sensei_scheduler_class', [ 'Sensei_Unit_Tests_Bootstrap', 'scheduler_use_shim' ] );
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

	/**
	 * Scheduler: Use WP Cron.
	 *
	 * @return string
	 */
	public static function scheduler_use_wp_cron() {
		return Sensei_Scheduler_WP_Cron::class;
	}

	/**
	 * Scheduler: Use Action Scheduler.
	 *
	 * @return string
	 */
	public static function scheduler_use_action_scheduler() {
		return Sensei_Scheduler_Action_Scheduler::class;
	}
}
