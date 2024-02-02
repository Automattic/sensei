<?php
/**
 * File containing the Migration_Job class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Migration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Migration_Job
 *
 * @internal
 *
 * @since 4.17.0
 */
class Migration_Job {
	/**
	 * Job name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Migration.
	 *
	 * @var Migration_Abstract
	 */
	private $migration;

	/**
	 * Is migration complete.
	 *
	 * @var bool
	 */
	private $is_complete = false;

	/**
	 * Migration_Job constructor.
	 *
	 * @param string             $name The job name. Should be hook friendly (lowercase, underscored).
	 * @param Migration_Abstract $migration Migration.
	 */
	public function __construct( string $name, Migration_Abstract $migration ) {
		$this->name      = $name;
		$this->migration = $migration;
	}

	/**
	 * Run the job.
	 *
	 * @internal
	 *
	 * @since 4.17.0
	 */
	public function run(): void {
		$rows_inserted     = $this->migration->run( false );
		$this->is_complete = 0 === $rows_inserted;
	}

	/**
	 * Get job errors.
	 *
	 * @internal
	 *
	 * @since 4.17.0
	 *
	 * @return array
	 */
	public function get_errors(): array {
		return $this->migration->get_errors();
	}

	/**
	 * Is job complete.
	 *
	 * @internal
	 *
	 * @since 4.17.0
	 *
	 * @return bool
	 */
	public function is_complete(): bool {
		return $this->is_complete;
	}

	/**
	 * Get the job name.
	 *
	 * @internal
	 *
	 * @since 4.17.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}
}

