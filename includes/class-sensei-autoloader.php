<?php
/**
 * File containing the class Sensei_Autoloader.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // security check, don't load file outside WP.
}

/**
 * Loading all class files within the Sensei/includes directory
 *
 * The auto loader class listens for calls to classes within Sensei and loads
 * the file containing the class.
 *
 * @package Core
 * @since 1.9.0
 */
class Sensei_Autoloader {

	/**
	 * Path to the includes directory within Sensei.
	 *
	 * @var string
	 */
	private $include_path = 'includes';

	/**
	 * List of classes mapped to their files.
	 *
	 * @var array
	 */
	private $class_file_map;

	/**
	 * An array of bundle directories to be loaded.
	 *
	 * @var array
	 */
	private $autoloader_bundles;

	/**
	 * Constructor
	 *
	 * @since 1.9.0
	 */
	public function __construct() {

		// Make sure we do not override an existing autoload function.
		if ( function_exists( '__autoload' ) ) {
			spl_autoload_register( '__autoload' );
		}

		// Setup a relative path for the current autoload instance.
		$this->include_path = trailingslashit( untrailingslashit( __DIR__ ) );

		// Setup the class file map.
		$this->initialize_class_file_map();

		require_once __DIR__ . '/class-sensei-autoloader-bundle.php';

		$this->autoloader_bundles = array(
			new Sensei_Autoloader_Bundle( 'rest-api' ),
			new Sensei_Autoloader_Bundle( 'domain-models' ),
			new Sensei_Autoloader_Bundle( '' ),
			new Sensei_Autoloader_Bundle( 'background-jobs' ),
			new Sensei_Autoloader_Bundle( 'enrolment' ),
		);

		if ( is_admin() ) {
			$this->autoloader_bundles[] = new Sensei_Autoloader_Bundle( 'data-port' );
		}

		// Add Sensei custom auto loader.
		spl_autoload_register( array( $this, 'autoload' ) );

	}

	/**
	 * Generate a list of Sensei class and map them the their respective
	 * files within the includes directory
	 *
	 * @since 1.9.0
	 */
	public function initialize_class_file_map() {

		$this->class_file_map = array(

			/**
			 * Main Sensei class
			 */
			'Sensei_Main'                                => 'class-sensei.php',

			/**
			 * Admin
			 */
			'Sensei_Learner_Management'                  => 'admin/class-sensei-learner-management.php',
			'Sensei_Extensions'                          => 'admin/class-sensei-extensions.php',
			'Sensei_Learners_Admin_Bulk_Actions_Controller' => 'admin/class-sensei-learners-admin-bulk-actions-controller.php',
			'Sensei_Learners_Admin_Bulk_Actions_View'    => 'admin/class-sensei-learners-admin-bulk-actions-view.php',
			'Sensei_Learners_Main'                       => 'admin/class-sensei-learners-main.php',
			'Sensei_Email_Signup_Form'                   => 'email-signup/class-sensei-email-signup-form.php', // @deprecated since 3.1.0
			'Sensei_Onboarding'                          => 'admin/class-sensei-onboarding.php',
			'Sensei_Onboarding_Pages'                    => 'admin/class-sensei-onboarding-pages.php',

			/**
			 * Shortcodes
			 */
			'Sensei_Shortcode_Loader'                    => 'shortcodes/class-sensei-shortcode-loader.php',
			'Sensei_Shortcode_Interface'                 => 'shortcodes/interface-sensei-shortcode.php',
			'Sensei_Shortcode_Featured_Courses'          => 'shortcodes/class-sensei-shortcode-featured-courses.php',
			'Sensei_Shortcode_User_Courses'              => 'shortcodes/class-sensei-shortcode-user-courses.php',
			'Sensei_Shortcode_Courses'                   => 'shortcodes/class-sensei-shortcode-courses.php',
			'Sensei_Shortcode_Teachers'                  => 'shortcodes/class-sensei-shortcode-teachers.php',
			'Sensei_Shortcode_User_Messages'             => 'shortcodes/class-sensei-shortcode-user-messages.php',
			'Sensei_Shortcode_Course_Page'               => 'shortcodes/class-sensei-shortcode-course-page.php',
			'Sensei_Shortcode_Lesson_Page'               => 'shortcodes/class-sensei-shortcode-lesson-page.php',
			'Sensei_Shortcode_Course_Categories'         => 'shortcodes/class-sensei-shortcode-course-categories.php',
			'Sensei_Shortcode_Unpurchased_Courses'       => 'shortcodes/class-sensei-shortcode-unpurchased-courses.php',
			'Sensei_Legacy_Shortcodes'                   => 'shortcodes/class-sensei-legacy-shortcodes.php',

			/**
			 * Renderers
			 */
			'Sensei_Renderer_Interface'                  => 'renderers/interface-sensei-renderer.php',
			'Sensei_Renderer_Single_Post'                => 'renderers/class-sensei-renderer-single-post.php',

			/**
			 * Unsupported theme handlers.
			 */
			'Sensei_Unsupported_Theme_Handler_Interface' => 'unsupported-theme-handlers/interface-sensei-unsupported-theme-handler.php',
			'Sensei_Unsupported_Theme_Handler_Page_Imitator' => 'unsupported-theme-handlers/class-sensei-unsupported-theme-handler-page-imitator.php',
			'Sensei_Unsupported_Theme_Handler_Utils'     => 'unsupported-theme-handlers/class-sensei-unsupported-theme-handler-utils.php',
			'Sensei_Unsupported_Theme_Handler_CPT'       => 'unsupported-theme-handlers/class-sensei-unsupported-theme-handler-cpt.php',
			'Sensei_Unsupported_Theme_Handler_Module'    => 'unsupported-theme-handlers/class-sensei-unsupported-theme-handler-module.php',
			'Sensei_Unsupported_Theme_Handler_Course_Results' => 'unsupported-theme-handlers/class-sensei-unsupported-theme-handler-course-results.php',
			'Sensei_Unsupported_Theme_Handler_Lesson_Tag_Archive' => 'unsupported-theme-handlers/class-sensei-unsupported-theme-handler-lesson-tag-archive.php',
			'Sensei_Unsupported_Theme_Handler_Teacher_Archive' => 'unsupported-theme-handlers/class-sensei-unsupported-theme-handler-teacher-archive.php',
			'Sensei_Unsupported_Theme_Handler_Message_Archive' => 'unsupported-theme-handlers/class-sensei-unsupported-theme-handler-message-archive.php',
			'Sensei_Unsupported_Theme_Handler_Learner_Profile' => 'unsupported-theme-handlers/class-sensei-unsupported-theme-handler-learner-profile.php',
			'Sensei_Unsupported_Theme_Handler_Course_Archive' => 'unsupported-theme-handlers/class-sensei-unsupported-theme-handler-course-archive.php',

			/**
			 * Built in theme integration support
			 */
			'Sensei_Theme_Integration_Loader'            => 'theme-integrations/theme-integration-loader.php',
			'Sensei__S'                                  => 'theme-integrations/underscores.php',
			'Sensei_Twentyeleven'                        => 'theme-integrations/twentyeleven.php',
			'Sensei_Twentytwelve'                        => 'theme-integrations/twentytwelve.php',
			'Sensei_Twentythirteen'                      => 'theme-integrations/Twentythirteen.php',
			'Sensei_Twentyfourteen'                      => 'theme-integrations/Twentyfourteen.php',
			'Sensei_Twentyfifteen'                       => 'theme-integrations/Twentyfifteen.php',
			'Sensei_Twentysixteen'                       => 'theme-integrations/Twentysixteen.php',
			'Sensei_Storefront'                          => 'theme-integrations/Storefront.php',

			/**
			* WPML
			*/
			'Sensei_WPML'                                => 'wpml/class-sensei-wpml.php',

		);
	}

	/**
	 * Autoload all sensei files as the class names are used.
	 *
	 * @param string $class The class name.
	 */
	public function autoload( $class ) {

		// Only handle classes with the word `sensei` in it.
		if ( ! is_numeric( strpos( strtolower( $class ), 'sensei' ) ) ) {

			return;

		}

		// Exit if we didn't provide mapping for this class.
		if ( isset( $this->class_file_map[ $class ] ) ) {

			$file_location = $this->include_path . $this->class_file_map[ $class ];
			require_once $file_location;
			return;

		}

		foreach ( $this->autoloader_bundles as $bundle ) {
			if ( true === $bundle->load_class( $class ) ) {
				return;
			}
		}
	}
}
