<?php
/**
 * File containing the Email_Preview class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class responsible for the email preview.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Email_Preview {
	/**
	 * Initialize the class and add hooks.
	 *
	 * @internal
	 */
	public function init(): void {
		add_filter( 'template_include', [ $this, 'load_preview_template' ], 20, 1 );
	}

	/**
	 * Load the email preview template file when needed.
	 *
	 * @param string $template The path of the template to include.
	 *
	 * @return string
	 */
	public function load_preview_template( $template ) {
		// phpcs:ignore WordPress.Security.NonceVerification -- Nonce validated when the preview template is loaded.
		if ( ! empty( $_GET['sensei_email_preview_id'] ) ) {
			show_admin_bar( false );

			return __DIR__ . '/templates/preview.php';
		}

		return $template;
	}
}
