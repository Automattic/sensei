<?php
/**
 * Persistence failure with diagnostic context.
 *
 * @package sensei
 */

namespace Sensei\Internal;

/**
 * A required persistence operation failed.
 *
 * @internal
 * @since 4.27.0
 */
class Persistence_Exception extends \RuntimeException {
	/**
	 * Primary backend when the failure occurred.
	 *
	 * @var string
	 */
	private $primary;
	/**
	 * Whether synchronization was enabled when the failure occurred.
	 *
	 * @var bool
	 */
	private $synchronization;
	/**
	 * Safe failure details.
	 *
	 * @var array{entity:string,store:string,post_id:int,user_id:int,diagnostic:string}
	 */
	private $context;
	/**
	 * Database error text available at the failing operation.
	 *
	 * @var string
	 */
	private $database_error = 'unavailable';
	/**
	 * Whether this exception has already been logged.
	 *
	 * @var bool
	 */
	private $logged = false;

	/**
	 * Create a failure with record identity and available database error text.
	 *
	 * @since 4.27.0
	 *
	 * @param string $entity Progress or quiz submission entity.
	 * @param string $store Storage implementation.
	 * @param int    $post_id Course, lesson or quiz ID.
	 * @param int    $user_id Student ID.
	 * @param string $diagnostic Safe diagnostic code.
	 * @param string $operation Storage operation that failed.
	 */
	public function __construct( string $entity, string $store, int $post_id, int $user_id, string $diagnostic = 'insert_failed', string $operation = 'create' ) {
		// Comment helpers run other queries before throwing; only table writes retain a reliable error.
		$error   = 'tables' === $store ? $GLOBALS['wpdb']->last_error : '';
		$failure = 'insert_failed' === $diagnostic ? 'Could not create a' : 'Could not verify a newly created';
		if ( 'create' !== $operation ) {
			$failure = sprintf( 'Could not %s a', sanitize_key( $operation ) );
		} elseif ( 'lesson_progress_missing' === $diagnostic ) {
			$failure = 'Missing lesson progress required to create a';
		}

		$record = 0 === strpos( $entity, 'quiz_' )
			? str_replace( '_', ' ', sanitize_key( $entity ) ) . ' record'
			: sanitize_key( $entity ) . ' progress record';
		parent::__construct( sprintf( '%s %s in %s', $failure, $record, sanitize_key( $store ) ) );
		$configuration         = Sensei()->progress_storage_configuration;
		$this->primary         = $configuration->get_read_backend();
		$this->synchronization = $configuration->is_dual_write_enabled();
		$this->context         = compact( 'entity', 'store', 'post_id', 'user_id', 'diagnostic' );

		if ( '' !== $error ) {
			$this->database_error = str_replace( array( "\r", "\n" ), ' ', $error );
		}
	}

	/**
	 * Log the failure at the handling boundary.
	 *
	 * @since 4.27.0
	 *
	 * @param string $operation Application action handling the failure.
	 * @return void
	 */
	public function log( string $operation = 'creating progress' ): void {
		if ( $this->logged ) {
			return;
		}
		$this->logged = true;

		$context  = $this->context;
		$entity   = sanitize_key( $context['entity'] );
		$file     = wp_normalize_path( $this->getFile() );
		$root     = wp_normalize_path( dirname( __DIR__, 2 ) ) . '/';
		$location = 0 === strpos( $file, $root ) ? substr( $file, strlen( $root ) ) : basename( $file );
		$message  = sprintf(
			'Sensei: %s while %s. %s ID: %d. Student ID: %d. Primary storage: %s. Failed storage: %s. Synchronization: %s. Database detail: %s. Diagnostic code: %s. Origin: %s:%d. Sensei version: %s.',
			$this->getMessage(),
			sanitize_text_field( $operation ),
			0 === strpos( $entity, 'quiz_' ) ? 'Quiz' : ucfirst( $entity ),
			$context['post_id'],
			$context['user_id'],
			$this->primary,
			sanitize_key( $context['store'] ),
			$this->synchronization ? 'enabled' : 'disabled',
			$this->database_error,
			sanitize_key( $context['diagnostic'] ),
			$location,
			$this->getLine(),
			SENSEI_LMS_VERSION
		);
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional diagnostics for progress write failures.
		error_log( $message );
	}
}
