<?php
/**
 * File containing the Email_Generators_Abstract class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails\Generators;

use Sensei\Internal\Emails\Email_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Email_Generators_Abstract
 *
 * @internal
 *
 * @since $$next-version$$
 */
abstract class Email_Generators_Abstract {

	/**
	 * Identifier of the email.
	 *
	 * @var string
	 */
	const IDENTIFIER_NAME = '';

	/**
	 * Email_Repository instance.
	 *
	 * @var Email_Repository
	 */
	protected $repository;

	/**
	 * Email_Generators_Abstract constructor.
	 *
	 * @param Email_Repository $repository Email_Repository instance.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 */
	public function __construct( $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Initialize the email hooks.
	 *
	 * @access public
	 * @since $$next-version$$
	 *
	 * @return void
	 */
	abstract public function init();

	/**
	 * Check if email exists and is published.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 *
	 * @return boolean Indicates if the email is published or not
	 */
	public function is_email_active() {
		$email = $this->repository->get( static::IDENTIFIER_NAME );
		return $email && 'publish' === $email->post_status;
	}

	/**
	 * Invokes the sensei_send_html_email action.
	 *
	 * @param array $replacements The replacements.
	 *
	 * @access protected
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 */
	protected function send_email_action( $replacements ) {
		/**
		 * Send HTML email.
		 *
		 * @since $$next-version$$
		 * @hook sensei_send_html_email
		 *
		 * @param {string} $email_name    The email name.
		 * @param {Array}  $replacements  The replacements.
		 */
		do_action( 'sensei_email_send', static::IDENTIFIER_NAME, $replacements );
	}
}
