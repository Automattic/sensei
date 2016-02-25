<?php
/**
 * Sensei Unit Tests Bootstrap
 *
 * @since 1.9
 */
class Sensei_Unit_Tests_Bootstrap {
	/** @var \Sensei_Unit_Tests_Bootstrap instance */
	protected static $instance = null;
	/** @var string directory where wordpress-tests-lib is installed */
	public $wp_tests_dir;
	/** @var string testing directory */
	public $tests_dir;
	/** @var string plugin directory */
	public $plugin_dir;
	/**
	 * Setup the unit testing environment.
	 *
	 * @since 2.2
	 */
	public function __construct() {
		ini_set( 'display_errors','on' );
		error_reporting( E_ALL );
		$this->tests_dir    = dirname( __FILE__ );
		$this->plugin_dir   = dirname( $this->tests_dir );
		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : '/tmp/wordpress-tests-lib';
		// load test function so tests_add_filter() is available
		require_once( $this->wp_tests_dir . '/includes/functions.php' );
		// load Sensei
		tests_add_filter( 'muplugins_loaded', array( $this, 'load_sensei' ) );
		// install Sensei
		tests_add_filter( 'setup_theme', array( $this, 'install_sensei' ) );
		// load the WP testing environment
		require_once( $this->wp_tests_dir . '/includes/bootstrap.php' );
		// load Sensei testing framework
		$this->includes();
	}
	/**
	 * Load Sensei.
	 *
	 * @since 1.9
	 */
	public function load_sensei() {
		require_once( $this->plugin_dir . '/woothemes-sensei.php' );
	}
	/**
	 * Install Sensei after the test environment and Sensei have been loaded.
	 *
	 * @since 2.2
	 */
	public function install_sensei() {
		// reload capabilities after install, see https://core.trac.wordpress.org/ticket/28374
		$GLOBALS['wp_roles']->reinit();
		echo "Installing Sensei..." . PHP_EOL;
	}
	/**
	 * Load Sensei-specific test cases and factories.
	 *
	 * @since 1.9
	 */
	public function includes() {
		// factories
		require_once( $this->tests_dir . '/framework/factories/Sensei-Factory.php' );
	}
	/**
	 * Get the single class instance.
	 *
	 * @since 1.9
	 * @return Sensei_Unit_Tests_Bootstrap
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
Sensei_Unit_Tests_Bootstrap::instance();

