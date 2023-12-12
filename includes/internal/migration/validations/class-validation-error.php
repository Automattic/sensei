<?php
/**
 * File containing the Validation_Error class.
 *
 * @package sensei
 * @since 4.19.1
 */

namespace Sensei\Internal\Migration\Validations;

/**
 * Validation_Error class.
 *
 * @internal
 *
 * @since 4.19.1
 */
class Validation_Error {
	/**
	 * Error message.
	 *
	 * @var string
	 */
	private string $message;

	/**
	 * Error data.
	 *
	 * @var array
	 */
	private array $data;

	/**
	 * Validation_Error constructor.
	 *
	 * @internal
	 *
	 * @since 4.19.1
	 *
	 * @param string $message Error message.
	 * @param array  $data    Error data.
	 */
	public function __construct( string $message, array $data = [] ) {
		$this->message = $message;
		$this->data    = $data;
	}

	/**
	 * Get the error message.
	 *
	 * @internal
	 *
	 * @since 4.19.1
	 *
	 * @return string
	 */
	public function get_message(): string {
		return $this->message;
	}

	/**
	 * Get the error data.
	 *
	 * @internal
	 *
	 * @since 4.19.1
	 *
	 * @return array
	 */
	public function get_data(): array {
		return $this->data;
	}

	/**
	 * Check if there is error data.
	 *
	 * @internal
	 *
	 * @since 4.19.1
	 *
	 * @return bool
	 */
	public function has_data(): bool {
		return (bool) $this->data;
	}
}
