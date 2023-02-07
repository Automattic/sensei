<?php
/**
 * File containing the Email_Customization class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Email_Customization
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Email_Customization {

	private static $instance;

	/**
	 * Email_Customization constructor.
	 *
	 * Prevents other instances from being created outside of `self::instance()`.
	 */
	private function __construct() {}

	/**
	 * Get the singleton instance.
	 *
	 * @internal
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the class and add hooks.
	 *
	 * @internal
	 */
	public function init(): void {
		( new Email_Post_Type() )->init();
		( new Settings_Menu() )->init();
	}

}
