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
		ini_set( 'display_errors', 'on' );
		error_reporting( E_ALL );
		$this->tests_dir    = dirname( __FILE__ );
		$this->plugin_dir   = dirname( $this->tests_dir );
		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : '/tmp/wordpress-tests-lib';

		define( 'SENSEI_TEST_FRAMEWORK_DIR', $this->tests_dir . '/framework' );

		// load test function so tests_add_filter() is available
		require_once $this->wp_tests_dir . '/includes/functions.php';
		// load Sensei
		tests_add_filter( 'muplugins_loaded', array( $this, 'load_sensei' ) );
		// install Sensei
		tests_add_filter( 'setup_theme', array( $this, 'install_sensei' ) );

		// Enrolment checks should happen immediately in tests. Filter can be removed for specific tests.
		tests_add_filter( 'sensei_should_defer_enrolment_check', '__return_false' );

		// Prevent requests from `WP_Http::request` while testing.
		tests_add_filter( 'pre_http_request', [ $this, 'prevent_requests' ], 99 );

		// Enable features.
		tests_add_filter( 'sensei_feature_flag_tables_based_progress', '__return_true' );

		// Init clock.
		tests_add_filter( 'sensei_clock_init', [ $this, 'init_clock' ] );

		/*
		* Load PHPUnit Polyfills for the WP testing suite.
		* @see https://github.com/WordPress/wordpress-develop/pull/1563/
		*/
		define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', __DIR__ . '/../vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php' );

		// load the WP testing environment
		require_once $this->wp_tests_dir . '/includes/bootstrap.php';

		// load Sensei testing framework
		$this->includes();
	}

	/**
	 * Filter to alert to prevent requests in the tests.
	 *
	 * @param mixed $preempt
	 * @return mixed
	 */
	public function prevent_requests( $preempt ) {
		if ( false === $preempt ) {
			throw new Exception( 'You should use the filter `pre_http_request` to prevent requests in the tests' );
		}
		return $preempt;
	}

	/**
	 * Load Sensei.
	 *
	 * @since 1.9
	 */
	public function load_sensei() {
		// Testing setup for scheduler.
		require_once SENSEI_TEST_FRAMEWORK_DIR . '/class-sensei-scheduler-shim.php';
		require_once SENSEI_TEST_FRAMEWORK_DIR . '/actionscheduler-mocks.php';

		require_once $this->plugin_dir . '/sensei-lms.php';

		add_filter( 'sensei_scheduler_class', [ __CLASS__, 'scheduler_use_shim' ] );
	}

	public function init_clock() {
		// Testing setup for clock.
		require_once SENSEI_TEST_FRAMEWORK_DIR . '/class-sensei-clock-stub.php';

		// Set the clock to a fixed time.
		return new Sensei_Clock_Stub();
	}

	/**
	 * Scheduler: Use shim.
	 *
	 * @return string
	 */
	public static function scheduler_use_shim() {
		return Sensei_Scheduler_Shim::class;
	}

	/**
	 * Install Sensei after the test environment and Sensei have been loaded.
	 *
	 * @since 2.2
	 */
	public function install_sensei() {
		// reload capabilities after install, see https://core.trac.wordpress.org/ticket/28374
		$GLOBALS['wp_roles']->for_site();
		echo 'Installing Sensei...' . PHP_EOL;

		Sensei()->activate();
	}
	/**
	 * Load Sensei-specific test cases and factories.
	 *
	 * @since 1.9
	 */
	public function includes() {
		require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-test-login-helpers.php';
		require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-wp-cron-helpers.php';
		require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-rest-api-test-helpers.php';
		require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-course-enrolment-test-helpers.php';
		require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-course-enrolment-manual-test-helpers.php';
		require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-scheduler-test-helpers.php';
		require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-test-redirect-helpers.php';
		require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-hpps-helpers.php';
		require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-clock-helpers.php';
		require_once SENSEI_TEST_FRAMEWORK_DIR . '/class-sensei-background-job-stub.php';
		require_once SENSEI_TEST_FRAMEWORK_DIR . '/factories/class-sensei-factory.php';
		require_once SENSEI_TEST_FRAMEWORK_DIR . '/factories/class-wp-unittest-factory-for-post-sensei.php';
		require_once SENSEI_TEST_FRAMEWORK_DIR . '/data-port/trait-sensei-data-port-test-helpers.php';
		require_once SENSEI_TEST_FRAMEWORK_DIR . '/data-port/trait-sensei-export-task-tests.php';
		require_once SENSEI_TEST_FRAMEWORK_DIR . '/exceptions/class-sensei-wp-redirect-exception.php';
		require_once SENSEI_TEST_FRAMEWORK_DIR . '/class-sensei-mailpoet-api-factory.php';

		// Testing setup for event logging.
		require_once SENSEI_TEST_FRAMEWORK_DIR . '/class-sensei-test-events.php';

		Sensei_Test_Events::init();
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

