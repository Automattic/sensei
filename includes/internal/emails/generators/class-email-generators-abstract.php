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
	 * Action name.
	 *
	 * @var string
	 */
	private $action = '';

	/**
	 * Callback name.
	 *
	 * @var callable
	 */
	private $callback = '';

	/**
	 * Priority.
	 *
	 * @var int
	 */
	private $priority = 10;

	/**
	 * Accepted arguments.
	 *
	 * @var int
	 */
	private $accepted_args = 1;

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
	 * Add action if email is active.
	 *
	 * @since 4.18.0
	 *
	 * @internal
	 *
	 * @param string   $action        Action name.
	 * @param callable $callback      Callback.
	 * @param int      $priority      Priority.
	 * @param int      $accepted_args Accepted arguments.
	 */
	protected function maybe_add_action( $action, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->action        = $action;
		$this->callback      = $callback;
		$this->priority      = $priority;
		$this->accepted_args = $accepted_args;

		add_action( $action, [ $this, 'add_action_if_email_active' ], 1 );
	}

	/**
	 * Add action if email is active.
	 *
	 * @since 4.18.0
	 *
	 * @internal
	 */
	public function add_action_if_email_active() {
		if ( $this->is_email_active() ) {
			add_action( $this->action, $this->callback, $this->priority, $this->accepted_args );
		}
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

	/**
	 * Return recipients' email addresses based on given user IDs.
	 *
	 * @param array $user_ids User IDs.
	 * @return array Array of email addresses.
	 */
	protected function get_recipients( $user_ids ): array {
		$recipients = array();
		foreach ( $user_ids as $user_id ) {
			$user         = new \WP_User( $user_id );
			$recipients[] = stripslashes( $user->user_email );
		}
		return $recipients;
	}
}
