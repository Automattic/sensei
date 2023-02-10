<?php
/**
 * File containing the class Sensei_MailPoet.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( \MailPoet\API\API::class ) ) {
	return;
}

/**
 * MailPoet integration class.
 *
 * Handles the integration with the MailPoet plugin,
 * creates a list for each course and group, adds enrolled students.
 *
 * @package Core
 * @since $$next-version$$
 */
class Sensei_MailPoet {

	/**
	 * MailPoet handle.
	 *
	 * @var object
	 */
	private $mailpoet_api = null;

	/**
	 * Singleton instance.
	 *
	 * @var Sensei_MailPoet
	 */
	private static $instance;

	/**
	 * Constructor
	 *
	 * @since 1.9.0
	 */
	public function __construct() {
		if ( class_exists( \MailPoet\API\API::class ) ) {
			$this->mailpoet_api = \MailPoet\API\API::MP( 'v1' );
			if ( $this->mailpoet_api->isSetupComplete() ) {
				// Todo: maybe exit here?
				return;
			}
		}
	}

	/**
	 * Get instance.
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Create a list on MailPoet for a course/group.
	 */
	public function create_list() {
	}

	/**
	 * Get all groups and courses in Sensei.
	 */
	public function get_sensei_courses_groups() {
	}

	/**
	 * Get all lists in MailPoet. Separates them into groups and courses.
	 */
	public function get_mailpoet_lists() {
	}

	/**
	 * Compares MailPoet lists with courses and groups on Sensei site.
	 */
	public function compare_courses_groups() {
	}

	/**
	 * Sync MailPoet lists with Sensei site courses and grooups.
	 */
	public static function sync_lists() {
	}

	/**
	 * Sync MailPoet list students with Sensei site courses and groups students.
	 */
	public static function sync_enrolles() {
	}
}
