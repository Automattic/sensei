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
 * @since 4.12.0
 */
abstract class Email_Generators_Abstract {

	/**
	 * Identifier of the email.
	 *
	 * @var string
	 */
	const IDENTIFIER_NAME = '';

	/**
	 * Identifier used in usage tracking.
	 *
	 * @var string
	 */
	const USAGE_TRACKING_TYPE = '';

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
	 * @since 4.12.0
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
	 * @since 4.12.0
	 *
	 * @return void
	 */
	abstract public function init();

	/**
	 * Check if email exists and is published.
	 *
	 * @since 4.12.0
	 *
	 * @internal
	 *
	 * @return boolean Indicates if the email is published or not
	 */
	public function is_email_active() {
		$email = $this->repository->get( $this->get_identifier() );
		return $email && 'publish' === $email->post_status;
	}

	/**
	 * Get name of the identifier of the Email.
	 *
	 * @since 4.12.0
	 *
	 * @internal
	 *
	 * @return string Identifier name.
	 */
	public function get_identifier() {
		return static::IDENTIFIER_NAME;
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
	 * @since 4.12.0
	 */
	protected function send_email_action( $replacements ) {
		/**
		 * Send HTML email.
		 *
		 * @since 4.12.0
		 * @hook sensei_email_send
		 *
		 * @param {string} $email_name          The email name.
		 * @param {Array}  $replacements        The replacements.
		 * @param {string} $usage_tracking_type Usage tracking type.
		 */
		do_action( 'sensei_email_send', $this->get_identifier(), $replacements, static::USAGE_TRACKING_TYPE );
	}
}
