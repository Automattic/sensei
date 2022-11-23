<?php
/**
 * This file contains Sensei_Scheduler class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Fetch the correct scheduler.
 *
 * @since 3.0.0
 */
class Sensei_Scheduler {
	/**
	 * Instance of the current handler.
	 *
	 * @var Sensei_Scheduler_Interface
	 */
	private static $instance;

	/**
	 * Get the instance of the Scheduler to use.
	 *
	 * @return Sensei_Scheduler_Interface
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class_name     = self::get_class();
			self::$instance = new $class_name();
		}

		return self::$instance;
	}

	/**
	 * Initialize actions.
	 */
	public static function init() {
		add_action( Sensei_Background_Job_Stateful::NAME, [ __CLASS__, 'run_stateful_job' ] );
	}

	/**
	 * Run a stateful job.
	 *
	 * @param array $args The job arguments.
	 *
	 * @return bool
	 */
	public static function run_stateful_job( $args ) : bool {
		if (
			empty( $args['id'] )
			|| empty( $args['class'] )
			|| ! class_exists( (string) $args['class'] )
			|| ! is_subclass_of( (string) $args['class'], Sensei_Background_Job_Stateful::class )
		) {
			return false;
		}

		$id         = (string) $args['id'];
		$class_name = (string) $args['class'];
		unset( $args['id'], $args['class'] );

		$job = new $class_name( $args, $id );
		self::instance()->run(
			$job,
			function() use ( $job ) {
				$job->cleanup();
			}
		);

		$job->persist();

		return true;
	}

	/**
	 * Get the class for the scheduler.
	 *
	 * @return string
	 */
	private static function get_class() {
		if ( 0 === did_action( 'plugins_loaded' ) ) {
			_doing_it_wrong( __METHOD__, 'Scheduler should not be used until after the plugins are loaded.', '3.0.0' );
		}

		$default_class_name = Sensei_Scheduler_WP_Cron::class;

		if ( self::is_action_scheduler_available() ) {
			$default_class_name = Sensei_Scheduler_Action_Scheduler::class;
		}

		/**
		 * Override the default class that implements `Sensei_Scheduler_Interface`.
		 *
		 * @since 3.0.0
		 *
		 * @param string $class_name Class for the scheduler that should be used by Sensei.
		 */
		$class_name = apply_filters( 'sensei_scheduler_class', $default_class_name );
		if ( ! is_subclass_of( $class_name, Sensei_Scheduler_Interface::class, true ) ) {
			_doing_it_wrong( __METHOD__, 'The filter "sensei_scheduler_class" returned an invalid scheduler.', '3.0.0' );

			$class_name = $default_class_name;
		}

		return $class_name;
	}

	/**
	 * Check to see if Action Scheduler is available.
	 *
	 * @return bool
	 */
	private static function is_action_scheduler_available() {
		return class_exists( 'ActionScheduler_Versions' )
				&& function_exists( 'as_unschedule_all_actions' )
				&& function_exists( 'as_next_scheduled_action' )
				&& function_exists( 'as_schedule_single_action' );
	}
}
