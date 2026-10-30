<?php
/**
 * Persistence failure with safe diagnostic context.
 *
 * @package sensei
 */

namespace Sensei\Internal;

/**
 * A required progress record could not be created.
 *
 * @internal
 * @since $$next-version$$
 */
class Persistence_Exception extends \RuntimeException {
	/**
	 * Create a failure without retaining database messages or submitted values.
	 *
	 * @since $$next-version$$
	 *
	 * @param string $entity Progress entity.
	 * @param string $store Storage implementation.
	 * @param int    $post_id Course or lesson ID.
	 * @param int    $user_id Student ID.
	 * @param string $diagnostic Safe diagnostic code.
	 */
	public function __construct( string $entity, string $store, int $post_id, int $user_id, string $diagnostic = 'insert_failed' ) {
		parent::__construct( sprintf( 'Sensei persistence: operation=create repository=%s_progress store=%s post_id=%d user_id=%d diagnostic=%s', $entity, $store, $post_id, $user_id, $diagnostic ) );
	}

	/**
	 * Log the failure at the handling boundary.
	 *
	 * @since $$next-version$$
	 *
	 * @return void
	 */
	public function log(): void {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Safe persistence diagnostics for repair.
		error_log( $this->getMessage() );
	}
}
