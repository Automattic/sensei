<?php
/**
 * File containing the class Sensei_Background_Job_Stateful.
 *
 * @since 3.9.0
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base class for jobs that require state.
 */
abstract class Sensei_Background_Job_Stateful implements Sensei_Background_Job_Interface {
	const NAME                         = 'sensei_background_job_stateful';
	const TRANSIENT_PREFIX             = 'sensei_background_job_';
	const TRANSIENT_EXPIRATION_SECONDS = DAY_IN_SECONDS;

	/**
	 * ID for the job.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * Arguments for the job.
	 *
	 * @var array
	 */
	private $args;

	/**
	 * State for the current job.
	 *
	 * @var array
	 */
	private $state;

	/**
	 * Set if the state has changed.
	 *
	 * @var bool
	 */
	private $changed = false;

	/**
	 * Set if the job has been deleted.
	 *
	 * @var bool
	 */
	private $deleted = false;

	/**
	 * Sensei_Background_Job_Stateful constructor.
	 *
	 * @param array  $args  Arguments needed to run.
	 * @param string $id    The unique ID.
	 */
	public function __construct( $args = [], $id = null ) {
		$is_new = false;
		if ( null === $id ) {
			$is_new = true;
			$id     = md5( static::class );
			if ( $this->allow_multiple_instances() ) {
				$id = uniqid();
			}
		}

		$this->id   = $id;
		$this->args = $args;

		if ( ! $is_new ) {
			$this->restore_state();
		}
	}

	/**
	 * Can multiple instances be enqueued at the same time?
	 *
	 * @return bool
	 */
	abstract protected function allow_multiple_instances() : bool;

	/**
	 * Get the action name for the scheduled job.
	 *
	 * @return string
	 */
	public function get_name() {
		return self::NAME;
	}

	/**
	 * Get the state transient name.
	 *
	 * @return string
	 */
	private function get_state_transient_name() : string {
		return self::TRANSIENT_PREFIX . md5( wp_json_encode( $this->get_args() ) );
	}

	/**
	 * Restore a job state.
	 */
	private function restore_state() {
		$state_raw = get_transient( $this->get_state_transient_name() );

		$state = $state_raw ? json_decode( $state_raw, true ) : [];
		if ( ! is_array( $state ) ) {
			$state = [];
		}

		$this->state = $state;
	}

	/**
	 * Clean up.
	 */
	public function cleanup() {
		delete_transient( $this->get_state_transient_name() );
		$this->deleted = true;
	}

	/**
	 * Get the job ID.
	 *
	 * @return string
	 */
	public function get_id() : string {
		return $this->id;
	}

	/**
	 * Get a state variable.
	 *
	 * @param string $key   State key to update.
	 * @param mixed  $default The default value.
	 *
	 * @return mixed
	 */
	public function get_state( $key, $default = null ) {
		return $this->state[ $key ] ?? $default;
	}

	/**
	 * Update state.
	 *
	 * @param string $key   State key to update.
	 * @param mixed  $value Value to set.
	 */
	public function set_state( $key, $value ) {
		$current_value = $this->state[ $key ] ?? null;
		if ( $current_value !== $value ) {
			$this->changed = true;
		}

		$this->state[ $key ] = $value;
	}

	/**
	 * Persist the state.
	 */
	public function persist() {
		if ( $this->deleted || ! $this->changed ) {
			return;
		}

		set_transient( $this->get_state_transient_name(), wp_json_encode( $this->state ), self::TRANSIENT_EXPIRATION_SECONDS );
	}

	/**
	 * Get the arguments to run with the job.
	 *
	 * @return array
	 */
	public function get_args() {
		return array_merge(
			$this->args,
			[
				'id'    => $this->id,
				'class' => static::class,
			]
		);
	}
}
