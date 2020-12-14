<?php
/**
 * File containing Sensei_Bootstrap.
 *
 * @package Sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Sensei_Bootstrap loads the functionality needed for Sensei_Main to initialize properly
 *
 * @package Core
 */
class Sensei_Bootstrap {
	/**
	 * Singleton instance.
	 *
	 * @var Sensei_Bootstrap
	 */
	private static $instance;

	/**
	 * Autoloader instance.
	 *
	 * @var null|Sensei_Autoloader
	 */
	private $autoloader = null;

	/**
	 * Flag to make sure bootstrapping is only run once.
	 *
	 * @var bool
	 */
	private $is_bootstrapped = false;

	/**
	 * Sensei_Bootstrap constructor.
	 */
	private function __construct() {
	}

	/**
	 * Set up Sensei class loaders and includes.
	 *
	 * @return $this
	 */
	public function bootstrap() {
		if ( $this->is_bootstrapped ) {
			return $this;
		}
		$this->init_autoloader();
		$this->init_must_have_includes();

		$this->is_bootstrapped = true;
		return $this;
	}

	/**
	 * Initialize Sensei class autoloader.
	 */
	private function init_autoloader() {
		require_once dirname( __FILE__ ) . '/class-sensei-autoloader.php';
		$this->autoloader = new Sensei_Autoloader();
	}

	/**
	 * Get singleton.
	 *
	 * @return Sensei_Bootstrap
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Include global functions and compatibility helpers.
	 */
	private function init_must_have_includes() {
		require_once dirname( __FILE__ ) . '/sensei-functions.php';
		require_once dirname( __FILE__ ) . '/3rd-party/3rd-party.php';
		require_once dirname( __FILE__ ) . '/blocks/compat.php';
	}
}
