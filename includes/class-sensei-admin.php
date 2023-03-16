<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handles all admin views, assets and navigation.
 *
 * @package Views
 * @author Automattic
 * @since 1.0.0
 */
class Sensei_Admin {

	/**
	 * @var $course_order_page_slug The slug for the Order Courses page.
	 */
	private $course_order_page_slug;

	/**
	 * @var $lesson_order_page_slug The slug for the Order Lessons page.
	 */
	private $lesson_order_page_slug;

	/**
	 * Sensei SVG Icon
	 *
	 * @var string The menu icon for Sensei LMS.
	 */
	private $sensei_icon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgZmlsbD0ibm9uZSIgdmlld0JveD0iMCAwIDI0IDI0Ij48cGF0aCBmaWxsPSIjMDAwIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik04LjkwOCAxNy4xN2MuNDI4LS4zMzUuMjU0LS42OTIuODU2LS44MjMtLjAyNy4yODUuMjA5LjU3My4zNTkuODI0LS4xNTYtLjUzNy0uMDAzLS43ODMuMTEzLTEuMDUzLjQ5Ny0xLjE0Ny0uOTQ1LTEuODktMS45MjgtMi4wNzEuMzM2LS4yNDEuNTctLjQ3OS45OTItLjUyYTMuMzMyIDMuMzMyIDAgMCAwLTEuNTEyLjMzN2MtMS41ODguNzc2LTIuODQ2LS43NjYtMi4wMTQtMi4xNjMuNDQ0LS43NDcuOTUtLjc2IDEuNDA0LTEuMjY1LjEwOS4yNi4xNDYuNTQ0LjEwNi44MjQuMTQyLS4zMDguMTY2LS42NTIuMDctLjk3LS4xNDgtLjQ5MS0uNTcyLS40OTUtLjY1MS0xLjItLjAxNy0uMTQ4LS4xNS4wODItLjM3Ni0uMTEzLS4wNTMtLjA0Ny0uMTYyLS4xMy0uMjMyLS4wOTMtLjEyMy4wNjQtLjI1OC4wMTItLjMzMi4wNDktLjEyLjA2LS4xNTYtLjA2Ni0uMjg2LjAyNC0uMjMuMTU2LS4yNTQtLjAyLS40NzQuMDA4LS4yNzYuMDMzLS40NTguMzgxLS42MTUuMjA5LS4xNDQtLjE1Ny0uMjkzLjExLS4zOTguMTEtLjA3MyAwLS40NDYuNDY0LS42MS4wNDEtLjEyMy0uMzE2LS40LjMwMS0uNTQ2LjA5Mi0uMDQ3LS4wNjctLjE3NS4xNTgtLjI1Ny0uMDY0LS4wMi0uMDUxLjAwMS0uMTE0LS4xNTQtLjA0LS4zMDcuMTQ1LS4xOTgtLjA4OC0uMjk2LS4yMS0uMTE5LS4xNS0uMTYxLS4xNS0uMDk3LS4zOTUuMDUtLjE5My0uMDI3LS40ODIuMjg1LS41MzEuMDgtLjAxMy4xNTQtLjAwOC4yNjMtLjIxNy4xMzctLjI2Mi4zMzEtLjEzNi4yOTYtLjI1NS0uMDUtLjE3NC4yMDUtLjQxLjMyMy0uNTIuMTU3LS4xNS4zNjQtLjM2OC40MzctLjU3OEEuOTIxLjkyMSAwIDAgMSAzLjc4IDYuNGMuMTE2LS4xMjIuMTUtLjAwNC4zMjUtLjA2NC4xMjUtLjA0NS4wMjcuMDc2LjE1MS0uMTAyLjA0LS4wNTcuMTQ5LS4wMDQuMjctLjA1OC4zNy0uMTY0LjQwNS0uMTYxLjQ5Ny0uNTQzLjAxNy0uMDcyLjAzNS0uMTI4LjExMy0uMTIuMTU3LjAxNC4xOTItLjA3Ni4yODgtLjA3NC4wODYuMDAyLjExNy0uMDQ0LjE2My0uMDk5LjI1Ni0uMzE3LjM5Ni0uMDguNTEtLjEwNS4wODctLjAxOS4wMzItLjIwOS4yNjMtLjE1LjMzLjA4NC4yMjItLjA3NC40MzMtLjAyMi4yNDkuMDYuMjY2LjI3Ny40MzcuMzI5LjIwNS4wNjIuMzAyLjI5LjQzOC4yODcuNDgyLS4wMDcuMzM2LjMzNi42NC40NzEtLjIyOC0uMjM1LjAxNy0uNjE0LS41MDItLjY2NC0uMTE2LS4wMS0uMTU3LS4xODUtLjMyOS0uMjY3LS4yMDgtLjA5OS0uMTg3LS4zMDgtLjU2Mi0uMzk0LjA5Ni0uMDYxLjEyNi4wMDYuMTg1LS4xMDguMDgzLS4xNi4xMy0uMDY3LjE2Mi0uMTY2LjAzLS4wOTEuMDA4LS4xNDMuMjM1LS4yMjYuMjM2LS4wODYuNDY0LS4yODUuNjc0LS4yNzQuMTg2LjAwOS4yNjQtLjExOC4yNTMtLjI3Ny0uMDExLS4xNjUtLjA0Ny0uMzkxLjMxOC0uMzIyLjIxOC4wNDIuMjE0LjAwNC4yMTMtLjE0Ni0uMDAyLS4xNS4wNTctLjI0Mi4yNDktLjIzNS4xNC4wMDQuMjc4LS4wMDMuMzMtLjE3MS4wNDItLjEzMy4yOTYtLjE2Ny40MzItLjIxNy40NTMtLjE2Ni41MTYuMDczLjYzOS0uMTM3LjEtLjE3Mi4yNzktLjQ2NS40OC0uMjU2LjA0LjA0Mi4wNzguMDczLjIxNC0uMDgzLjExNC0uMTI4LjM4Ni0uMjkyLjQ4My0uMDcuMDM4LjA4OC4xMjMuMTc4LjE5Ni4wNjVhLjExNC4xMTQgMCAwIDEgLjA3Ni0uMDU3Yy4wNzMtLjAxNi4xMTItLjAzNS4xNS0uMDUzLjMxLS4xNDguNDQ3LS4xMzguMzA0LjE4My0uMjA0LjQ2MiAxLjEzLS4yODEuNzYuMjItLjEzNy4xODQuNzY3LjI4OS44OTkuMzcyLjA3Mi4wNDYuMDcuMDg2LjIwNi4xMTIuMzg4LjA3Mi4zODIuMDM4LjQwNS4zNTUuMDEuMTQzLjE1NC4wNjMuMTU0LjI0NiAwIC43ODIuNjczLjM4OC43ODMuNTgzLjAxOC4wMzIuMDQ2LjA2MS4xMTQuMDYxLjIxMiAwIC4yMjQtLjA0Mi4zMi4xMy4wMzkuMDcuMDcuMTA1LjE4MS4xNC4xNy4wNTUuMTYuMDc0LjA5Ny4yMDItLjA3Ny4xNTIuMDQzLjE1LjE5OC4xNjQuMTQyLjAxNC4yMS4wOTEuMzA4LjIwMi4xNTguMTc4LjQwNS4yMjkuNjE0LjMzMi4xMi4wNTguMDAyLjIwMy4yMDQuMzU1LjE0OS4xMTIuMjI0LjA5Ni4wMjQuMjQtLjg0NC42MTIuMzEzLjQ0Ni42MzcgMS4wMTUuMDcyLjEyNy4xNjkuMjg2LjMwOC40MzIuMzIyLjMzNS4xNDIuNDI4LjMxNi42MzguMS4xMjEuMDIxLjA5Mi4xODYuMi4zODcuMjU3LS4xNS40MjctLjQ1Ni4zMS0uMTM5LS4wNTItLjIzNy0uMTI0LS4yMS0uMDM3LjAyMi4wNzItLjAxNS4xMjItLjA3NC4xNzYtLjE3OS4xNjQuMTc1LjEuMjU5LjA4OC4yNTgtLjAzNy45NDctLjExMi45NDIuMTM2LS4wMDcuMzIyLjE5NS4zOTQuMzU4LjQxNS4yNjQuMDM2LjMzOS4wODMuNDg0LjMzLjEzMi4yMjIuMjA5LjEwMy40MTIuMy4xNC4xMzUuMzEuMzc2LjAyOS40MTItLjA2OC4wMDgtLjE0MSAwLS4xNzMuMDI0YS41OC41OCAwIDAgMS0uMTk5LjA5N2MtLjQyMi4xMzIuMi4xOTkuMzUyLjE2NS4xMzMtLjAyOC4yOTgtLjA3Mi40MjctLjAzOC4wODIuMDIxLjA5LjExNi4zNS4xNTYuMDg2LjAxNC4wNS4xNDMuMDA4LjIzNC0uMTQ5LjMxMi4yNzQuMTQ2LjIzLjM4My0uMDI1LjEzNy4xNTUuMzU4LS4wOTcuMzYzLS4wODEuMDAyLS4xNjYtLjAyMi0uMTY0LjA0OS4wMDUuMTU0LS4yMzYtLjA0My0uNDUzLjE3LS4wOC4wNzgtLjI3MS4xMzMtLjM5LS4wMTQtLjA0NC0uMDU2LS4wOTktLjEtLjMxLS4wMzQtLjIwNi4wNjctLjM0Ni4yOTgtLjQzNy0uMDU5LS4wMjQtLjA5My0uMDcyLS4wOTUtLjIwMy0uMTQtLjE3OS0uMDY0LS4xNjMtLjM3OC0uNDI0LS4xNzZhLjMzNi4zMzYgMCAwIDEtLjMwNi4wNTZjLS4yMDYtLjA1My0uNDMzLjAxOC0uNDUyLS4xNGEuNTEuNTEgMCAwIDAtLjU0LS40MzNjLS4yMS4wMTItLjM5Ni4wNS0uNTA3LS4yLjA4My4yODYuMjk2LjMwNC41NDYuMzA0LjIwNiAwIC4zMjguMTEuMzY1LjMzLjA2NC4zODYuMjkxLjE0My41OC4zMjVhLjc0NS43NDUgMCAwIDEgLjMuMzk5Yy4wNi4xNzIuNTMtLjAzNS40MDMuMzc3LS4wNTQuMTc4LS4xNTMuMjQ1LS4zNDYuMzg5LS41NzMuNDI5LS43MTUuMDA1LS44ODQuMzQ4LS4wODcuMTc4LS4wOS4wNDYtLjI4LjEzMy0uMzE0LjE0My0uNTE0LS4xODgtLjc0NS0uMDY2LS4yNi4xMzctLjI0LS4xMTgtLjUwMy4wNTQtLjIxNy4xNDEtLjQ0LS4xMDgtLjU0NC4yOC0uMDg5LjMzMi0uNDU4LjM0LS42MjcuMTYxLS4yNy0uMjgzLS4xODkuMzM0LS41MTguMDgtLjE3NS0uMTM0LS4zMDQuMDYyLS41Ni4wNjYtLjQ3MS4wMDYtLjY5Ny0uNDEyLS45MjctLjE1OC0uMjc1LjMwMi0uNDc0LS4wNDYtLjQ3OC0uMzU0LS4wMDUtLjMxNC0xLjExLS4yMS0xLjY5Mi0uMzczLS4zMjItLjA5LTEuMzMzLjAyLTEuMDA0LS41MTItLjUzLjU3LjY1Mi41MzYuOTc0LjY2NS4xOTQuMDc3LjM1LjE0LjUzOS4xNjcuMTg2LjQzMi4zMDYuOS4zMjQgMS40NzIuMDYyLS4zMTQuMDc2LS42MzMuMDQtLjk0OS0uMDYtLjU2LjM0Ni4yNzguMzk1LjM5OC4yNjIuNjU5LS4wMzIgMS44NjctLjY5NyAyLjIzYTQuMzQ3IDQuMzQ3IDAgMCAwLS44MDQtMS40NDZjLjM2Ni41NzIuODgxIDEuNzQxLjQ2OCAyLjQxYTEuNDczIDEuNDczIDAgMCAxLS43ODUtLjcwNWMuMTEuNDg3LjU5IDEuMDI2IDEuMTY4Ljg0OCAxLjAxLS4zMTEgMS4wMDUuMzQgMi4zNDQuNDQ4Ljk3NC4xNjMgMi4zNjMgMCAzLjAyNi4wNTUuMzYyLjAyOSAxLjg4NS4yODcgMS44NzguNjc4LS4wMjcgMS42MTYtLjYyNiAxLjcwMS0yLjE5OCAxLjg1NC0uOTE0LjA4OS0xLjYyLjEyNC0yLjYwNC4xNjJhOTMuOTU1IDkzLjk1NSAwIDAgMS02Ljk1NS0uMDAzYy0xLjAzNC0uMDQzLTEuNTMyLS4wNS0yLjYzLS4xOTgtMS4wOTgtLjE0OC0xLjctLjUzMS0xLjY4My0xLjczNi4wMDctLjQ0NCAxLjI5My0uODY4IDEuNzEzLS45MTcuNDk3LS4wNTcgMS4yODkuMDA4IDIuMDEtLjE3Ljc1MS0uMTg1IDEuMDY2LS4zMDcgMS42Mi0uNzQyWm03LjYzNy04Ljg0NGMtLjIzOC0uMDYyLS4wNzItLjIwMi0uNDgzLS4wMi0uMjQzLjEwNi0uMjc3LS4xOS0uNTIyLS4xMzgtLjI0Ni4wNTMtLjM0Ni0uMTgzLS43MTMtLjI0My0uNDU0LS4wNzQtLjEyNi0uMTk0LS44NDYtLjA0Ni0uMTM4LjAyNy0uMjU1LS4xMzItLjY4Ny0uMDc2LS41MDYuMDY3LS4zMjkuMTIyLS42MzUtLjE2OC0uMzg5LS4zNjgtLjY5Ny0uMTIxLTEuMDk2LS4yNy4zMDguMjQzLjUyNS4wMjguODg5LjMyNC0uMzQ2LjItLjc0OCAxLjE3Mi0xLjM3NSAxLjIzMy42ODIuMTU2IDEuMDEzLjUxNyAxLjY0LjYwNC43Mi4wOTggMS4yNi4xNzEgMS42NDUuNzc2LjQzLjY3Ni42MjcuMjg2IDEuMjQ5LjI0NS4zOTItLjAyNS40MDItLjI3LjQ2Ny0uNTMuMDktLjM1Ny4xOC0uMzc0LjQ0NC0uNDIxLjMwNC0uMDU1LjIzMi0uMTM2LjE1Mi0uMjI4LS4yMTQtLjI1MS4wMjctLjMwMi4yMDktLjM1Ni4zMTctLjA5NS4wNi0uMTEuMDYtLjI0NC0uMzk3LS4xMy0uMDktLjMxNi4xMy0uMzk4LjI0Mi4xNTIuMzQ3LjEyNy40NTQuMTA3LjE1NS0uMDMyLjI2LjA0My4zOS0uMDQyLS4xOTMuMDItLjIzMy0uMDU0LS4zOTYtLjAzLS4wNzQuMDEtLjE2MS4wMTYtLjMxNC0uMDc3LS4yNTYtLjE1OC0uMzg2LjA2OS0uNjYyLS4wMDJabS0zLjI1NiAyLjM5Ni0xLjU1OS0uNzk2Yy0xLjg3OS0uOTYtMi43MzQuNDU3LTMuMzM4IDIuMDFsLTEuMDk0IDEuMDY4Yy43ODQtLjQ5NCAxLjQyNC0xLjA0NCAyLjI0OS0xLjAzOS4xMzEgMCAyLjE2NS43MDkgMS44MzgtLjEzMy0uMDU5LS4xNTIuMTI3LS4wNzcuMTc4LS4yOTQuMDU3LS4yNS4wNzgtLjIwNy4yNzktLjA0OC4xMjYuMS4zMDEuMTY1LjI0OC0uMDctLjAyNC0uMTA2LS4wNTctLjIuNC0uMDA2LjIxNS4wOS4xMzUtLjA5NC40MzMtLjEzLjQxNC0uMDUyLjMyNS0uNDU1LjUyLS41MTEuMTA4LS4wMzIuMjE3LjA4My40MjEtLjExMy4wNy0uMDY4LjE1Ny0uMTMuMzMyLS4wODQtLjIwNC0uMTA2LS4zMTQtLjAyLS40MTguMDQ0LS4yMDkuMTMtLjI5LS4wNTMtLjQ5LjEwMlptLTMuMTg2LTIuODFjLjIyOS0uMDMuNDU3LS4wNTMuNjg4LS4wNjhhLjE4Ni4xODYgMCAwIDAgLjEwNi0uMDg2LjE2Ni4xNjYgMCAwIDAgLjAxLS4xMzEuMzkyLjM5MiAwIDAgMSAuMjEtLjMwM2wtLjAwMi0uMDA0Yy0uMzY5LjAwMS0uNjY2LjE0NS0xLjA0My4zMDgtLjA3NS4wMzMtLjAzNS4xNjMtLjE2OS4zMTFhLjM3OC4zNzggMCAwIDEtLjI4OC4zNDQuMTkzLjE5MyAwIDAgMC0uMTI4LjA2Ny4xNjcuMTY3IDAgMCAwLS4wMzcuMTM0LjM1NC4zNTQgMCAwIDEtLjM5Ni4wOWMtLjI4NS0uMTEzLS40OTgtLjA5NS0uNjQuMDUzYS41NS41NSAwIDAgMSAuNTcuMDIuNDU0LjQ1NCAwIDAgMCAuNTU3LS4xMi4xOS4xOSAwIDAgMSAuMTc5LS4xNDRjLjIwMi0uMDc3LjMzLS4yMzUuMzgzLS40NzFaIiBjbGlwLXJ1bGU9ImV2ZW5vZGQiLz48L3N2Zz4=';

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {

		$this->course_order_page_slug = 'course-order';
		$this->lesson_order_page_slug = 'lesson-order';

		// register admin styles
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles_global' ) );

		// register admin scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
		add_action( 'admin_menu', array( $this, 'add_course_order' ) );
		add_action( 'admin_menu', array( $this, 'add_lesson_order' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 9 );
		add_action( 'menu_order', array( $this, 'admin_menu_order' ) );
		add_action( 'admin_head', array( $this, 'admin_menu_highlight' ) );
		add_action( 'admin_init', array( $this, 'sensei_add_custom_menu_items' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_print_scripts', array( $this, 'sensei_set_plugin_url' ) );

		// Duplicate lesson & courses
		add_filter( 'post_row_actions', array( $this, 'duplicate_action_link' ), 10, 2 );
		add_action( 'admin_action_duplicate_lesson', array( $this, 'duplicate_lesson_action' ) );
		add_action( 'admin_action_duplicate_course', array( $this, 'duplicate_course_action' ) );
		add_action( 'admin_action_duplicate_course_with_lessons', array( $this, 'duplicate_course_with_lessons_action' ) );

		// Handle course and lesson ordering.
		add_action( 'admin_post_order_courses', array( $this, 'handle_order_courses' ) );
		add_action( 'admin_post_order_lessons', array( $this, 'handle_order_lessons' ) );

		// Handle lessons list table filtering
		add_action( 'restrict_manage_posts', array( $this, 'lesson_filter_options' ) );
		add_filter( 'request', array( $this, 'lesson_filter_actions' ) );

		// Add Sensei items to 'at a glance' widget
		add_filter( 'dashboard_glance_items', array( $this, 'glance_items' ), 10, 1 );

		// Handle course and lesson deletions
		add_action( 'trash_course', array( $this, 'delete_content' ), 10, 2 );
		add_action( 'trash_lesson', array( $this, 'delete_content' ), 10, 2 );

		// Add notices to WP dashboard
		add_action( 'admin_notices', array( $this, 'theme_compatibility_notices' ) );
		// warn users in case admin_email is not a real WP_User
		add_action( 'admin_notices', array( $this, 'notify_if_admin_email_not_real_admin_user' ) );

		// remove a course from course order when trashed
		add_action( 'transition_post_status', array( $this, 'remove_trashed_course_from_course_order' ) );

		// Add workaround for block editor bug on CPT pages. See the function doc for more information.
		add_action( 'admin_footer', array( $this, 'output_cpt_block_editor_workaround' ) );

		// Add AJAX endpoint for event logging.
		add_action( 'wp_ajax_sensei_log_event', array( $this, 'ajax_log_event' ) );

		Sensei_Tools::instance()->init();
		Sensei_Status::instance()->init();

	}

	/**
	 * Add items to admin menu
	 *
	 * @since 1.4.0
	 * @since 4.8.0 Reactivate method since we have a new home page.
	 *
	 * @return void
	 */
	public function admin_menu() {
		add_menu_page( 'Sensei LMS', 'Sensei LMS', self::get_top_menu_capability(), 'sensei', '', $this->sensei_icon, '50' );
	}

	/**
	 * Get the top menu minimum capability.
	 *
	 * @since 4.8.0
	 *
	 * @return string
	 */
	public static function get_top_menu_capability() {
		$menu_cap = 'manage_sensei';

		if ( ! current_user_can( 'manage_sensei' ) && current_user_can( 'manage_sensei_grades' ) ) {
			$menu_cap = 'manage_sensei_grades';
		}

		return $menu_cap;
	}

	/**
	 * Add Course order page to admin panel.
	 *
	 * @since  4.0.0
	 * @access private
	 */
	public function add_course_order() {
		add_submenu_page(
			null, // Hide in menu.
			__( 'Order Courses', 'sensei-lms' ),
			__( 'Order Courses', 'sensei-lms' ),
			'manage_sensei',
			$this->course_order_page_slug,
			array( $this, 'course_order_screen' )
		);
	}

	/**
	 * Add Lesson order page to admin panel.
	 *
	 * @since  4.0.0
	 * @access private
	 */
	public function add_lesson_order() {
		add_submenu_page(
			null,
			__( 'Order Lessons', 'sensei-lms' ),
			__( 'Order Lessons', 'sensei-lms' ),
			'edit_published_lessons',
			$this->lesson_order_page_slug,
			array( $this, 'lesson_order_screen' )
		);
	}

	/**
	 * [admin_menu_order description]
	 *
	 * @since  1.4.0
	 * @param  array $menu_order Existing menu order
	 * @return array             Modified menu order for Sensei
	 */
	public function admin_menu_order( $menu_order ) {

		// Initialize our custom order array
		$sensei_menu_order = array();

		// Get the index of our custom separator
		$sensei_separator = array_search( 'separator-sensei', $menu_order );

		// Loop through menu order and do some rearranging
		foreach ( $menu_order as $index => $item ) :

			if ( ( ( 'sensei' ) == $item ) ) :
				$sensei_menu_order[] = 'separator-sensei';
				$sensei_menu_order[] = $item;
				unset( $menu_order[ $sensei_separator ] );
			elseif ( ! in_array( $item, array( 'separator-sensei' ) ) ) :
				$sensei_menu_order[] = $item;
			endif;

		endforeach;

		// Return order
		return $sensei_menu_order;
	}

	/**
	 * Handle highlighting of admin menu items
	 *
	 * @since 1.4.0
	 * @since 4.8.0 General review after adding the new Sensei Home page.
	 *
	 * @return void
	 */
	public function admin_menu_highlight() {
		global $parent_file, $submenu_file, $taxonomy, $_wp_real_parent_file;

		$screen = get_current_screen();

		if ( empty( $screen ) ) {
			return;
		}

		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- Only way to highlight our special pages in menu.
		if ( 'edit-tags' === $screen->base && 'module' === $taxonomy ) {
			$parent_file  = 'sensei';
			$submenu_file = 'edit-tags.php?taxonomy=module&post_type=course';

		} elseif ( in_array( $screen->id, [ 'edit-module', 'admin_page_module-order' ], true ) ) {
			// Module pages.
			$parent_file              = 'sensei';
			$_wp_real_parent_file[''] = 'sensei';
			$submenu_file             = 'edit-tags.php?taxonomy=module&post_type=course';

		} elseif ( in_array( $screen->id, [ 'course', 'edit-course-category', 'admin_page_course-order', 'admin_page_' . Sensei_Course::SHOWCASE_COURSES_SLUG ], true ) ) {
			// Course pages.
			$parent_file              = 'sensei';
			$_wp_real_parent_file[''] = 'sensei';
			$submenu_file             = 'edit.php?post_type=course';

		} elseif ( in_array( $screen->id, [ 'lesson', 'edit-lesson-tag', 'admin_page_lesson-order' ], true ) ) {
			// Lesson pages.
			$parent_file              = 'sensei';
			$_wp_real_parent_file[''] = 'sensei';
			$submenu_file             = 'edit.php?post_type=lesson';

		} elseif ( in_array( $screen->id, [ 'question', 'edit-question-category' ], true ) ) {
			// Question pages.
			$parent_file              = 'sensei';
			$_wp_real_parent_file[''] = 'sensei';
			$submenu_file             = 'edit.php?post_type=question';

		} elseif ( in_array( $screen->id, [ 'sensei_message' ], true ) ) {
			// Message pages.
			$parent_file              = 'sensei';
			$_wp_real_parent_file[''] = 'sensei';
			$submenu_file             = 'edit.php?post_type=sensei_message';

		} elseif ( in_array( $screen->id, [ 'sensei_email', 'edit-sensei_email' ], true ) ) {
			// Message pages.
			$parent_file              = 'sensei';
			$_wp_real_parent_file[''] = 'sensei';
			$submenu_file             = 'sensei-settings';
		}
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * Redirect Sensei menu item to Analysis page
	 *
	 * @since  1.4.0
	 * @deprecated 4.0.0
	 *
	 * @return void
	 */
	public function page_redirect() {
		_deprecated_function( __METHOD__, '4.0.0' );

		if ( isset( $_GET['page'] ) && $_GET['page'] == 'sensei' ) {
			wp_safe_redirect( 'admin.php?page=sensei_reports' );
			exit;
		}
	}

	/**
	 * install_pages_output function.
	 *
	 * Handles installation of the 2 pages needs for courses and my courses
	 *
	 * @deprecated 3.1.0 use Sensei_Setup_Wizard_Pages::create_pages instead.
	 * @access public
	 * @return void
	 */
	function install_pages_output() {
		_deprecated_function( __METHOD__, '3.1.0', 'Sensei_Setup_Wizard_Pages::create_pages' );

	}


	/**
	 * create_page function.
	 *
	 * @deprecated 3.1.0 use Sensei_Setup_Wizard_Pages::create_page instead.
	 *
	 * @access public
	 * @param mixed  $slug
	 * @param mixed  $option
	 * @param string $page_title (default: '')
	 * @param string $page_content (default: '')
	 * @param int    $post_parent (default: 0)
	 * @return integer $page_id
	 */
	function create_page( $slug, $page_title = '', $page_content = '', $post_parent = 0 ) {

		_deprecated_function( __METHOD__, '3.1.0', 'Sensei_Setup_Wizard_Pages::create_page' );
		return Sensei()->setup_wizard->pages->create_page( $slug, $page_title, $page_content, $post_parent );

	}


	/**
	 * create_pages function.
	 *
	 * @deprecated 3.1.0 use Sensei_Setup_Wizard_Pages::create_pages instead.
	 *
	 * @access public
	 * @return void
	 */
	function create_pages() {

		_deprecated_function( __METHOD__, '3.1.0', 'Sensei_Setup_Wizard_Pages::create_pages' );
		Sensei()->setup_wizard->pages->create_pages();

	}

	/**
	 * Load the global admin styles for the menu icon and the relevant page icon.
	 *
	 * @access public
	 * @since 1.0.0
	 *
	 * @param string $hook The current admin page.
	 */
	public function admin_styles_global( $hook ) {
		global $post_type;

		// Global Styles for icons and menu items
		Sensei()->assets->enqueue( 'sensei-global', 'css/global.css', [], 'screen' );

		// WordPress component styles with Sensei theming.
		Sensei()->assets->register( 'sensei-wp-components', 'shared/styles/wp-components.css', [], 'screen' );

		// Select 2 styles
		Sensei()->assets->enqueue( 'sensei-core-select2', '../vendor/select2/select2.min.css', [], 'screen' );

		Sensei()->assets->register( 'jquery-modal', '../vendor/jquery-modal-0.9.1/jquery.modal.min.css' );

		// Test for Write Panel Pages
		if ( $this->are_custom_admin_styles_allowed( $post_type, $hook, get_current_screen() ) ) {
			Sensei()->assets->enqueue( 'sensei-admin-custom', 'css/admin-custom.css', [], 'screen' );
		}

	}

	/**
	 * Check if it is allowed to enqueue admin custom styles.
	 *
	 * @param string         $post_type The post type slug.
	 * @param string         $hook_suffix The current admin page.
	 * @param WP_Screen|null $screen The current screen.
	 * @return bool Returns true if admin custom styles are allowed.
	 */
	private function are_custom_admin_styles_allowed( $post_type, $hook_suffix, $screen ) {
		$allowed_post_types      = apply_filters( 'sensei_scripts_allowed_post_types', array( 'lesson', 'course', 'question' ) );
		$allowed_post_type_pages = apply_filters( 'sensei_scripts_allowed_post_type_pages', array( 'edit.php', 'post-new.php', 'post.php', 'edit-tags.php' ) );
		$allowed_pages           = apply_filters( 'sensei_scripts_allowed_pages', array( 'sensei_grading', Sensei_Analysis::PAGE_SLUG, 'sensei_learners', 'sensei_updates', 'sensei-settings', 'sensei_learners', Sensei_Course::SHOWCASE_COURSES_SLUG, $this->lesson_order_page_slug, $this->course_order_page_slug ) );
		$module_pages_screen_ids = [ 'edit-module' ];

		$is_allowed_type           = isset( $post_type ) && in_array( $post_type, $allowed_post_types, true );
		$is_allowed_post_type_page = isset( $hook_suffix ) && in_array( $hook_suffix, $allowed_post_type_pages, true );
		$is_allowed_page           = isset( $_GET['page'] ) && in_array( $_GET['page'], $allowed_pages, true ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_modules_page           = $screen && in_array( $screen->id, $module_pages_screen_ids, true );

		return ( $is_allowed_type && $is_allowed_post_type_page ) || $is_allowed_page || $is_modules_page;
	}


	/**
	 * Globally register all scripts needed in admin.
	 *
	 * The script users should enqueue the script when needed.
	 *
	 * @since 1.8.2
	 * @access public
	 */
	public function register_scripts( $hook ) {
		$screen = get_current_screen();

		Sensei()->assets->register( 'sensei-dismiss-notices', 'js/admin/sensei-notice-dismiss.js', [] );

		// Select2 script used to enhance all select boxes.
		Sensei()->assets->register( 'sensei-core-select2', '../vendor/select2/select2.full.js', [ 'jquery' ] );

		Sensei()->assets->register( 'jquery-modal', '../vendor/jquery-modal-0.9.1/jquery.modal.js', [ 'jquery' ], true );

		Sensei()->assets->register(
			'sensei-learners-admin-bulk-actions-js',
			'js/learners-bulk-actions.js',
			[ 'jquery', 'sensei-core-select2', 'jquery-modal', 'wp-i18n' ],
			true
		);

		$ajax_object = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		);
		wp_localize_script( 'sensei-learners-admin-bulk-actions-js', 'ajax_object', $ajax_object );

		Sensei()->assets->register( 'sensei-chosen', '../vendor/chosen/chosen.jquery.min.js', [ 'jquery' ], true );
		Sensei()->assets->register( 'sensei-chosen-ajax', '../vendor/chosen/ajax-chosen.jquery.min.js', [ 'jquery', 'sensei-chosen' ], true );

		// Load ordering script on Order Courses and Order Lessons pages.
		if ( in_array( $screen->id, [ 'admin_page_course-order', 'admin_page_lesson-order' ], true ) ) {
			Sensei()->assets->enqueue( 'sensei-ordering', 'js/admin/ordering.js', [ 'jquery', 'jquery-ui-sortable', 'sensei-core-select2' ], true );
		}

		// Load edit module scripts.
		if ( 'edit-module' === $screen->id ) {
			wp_enqueue_script( 'sensei-chosen-ajax' );
		}

		Sensei()->assets->enqueue( 'sensei-message-menu-fix', 'js/admin/message-menu-fix.js', [ 'jquery' ], true );

		// Event logging.
		Sensei()->assets->enqueue( 'sensei-event-logging', 'js/admin/event-logging.js', [ 'jquery' ], true );

		if ( $this->has_custom_navigation( $screen ) ) {
			Sensei()->assets->enqueue( 'sensei-admin-custom-navigation', 'js/admin/custom-navigation.js', [], true );
		}

		wp_localize_script( 'sensei-event-logging', 'sensei_event_logging', [ 'enabled' => Sensei_Usage_Tracking::get_instance()->get_tracking_enabled() ] );
	}

	/**
	 * Check if the current screen has a custom navigation.
	 *
	 * @param WP_Screen|null $screen The current screen.
	 * @return bool
	 */
	private function has_custom_navigation( $screen ) {
		$screens_with_custom_navigation = [
			'edit-course',
			'edit-course-category',
			'edit-module',
			'edit-lesson',
			'edit-lesson-tag',
			'edit-question',
			'edit-question-category',
			'sensei-lms_page_' . Sensei_Analysis::PAGE_SLUG,
			'sensei-lms_page_sensei_learners',
		];
		/**
		 * Allows modifying the list of screens where the scripts for custom
		 * navigation (which handles some operations like hiding the
		 * navigation title that is generated by wp, for example, the name of the
		 * custom post type that's shown on the list page of custom post type)
		 * should be loaded.
		 *
		 * @since 4.5.0
		 * @hook sensei_custom_navigation_allowed_screens
		 *
		 * @param {array} $screens_with_custom_navigation Screens where custom navigation scrips will be loaded.
		 *
		 * @return {array} Screens where custom navigation scrips will be loaded.
		 */
		$screens_with_custom_navigation = apply_filters(
			'sensei_custom_navigation_allowed_screens',
			$screens_with_custom_navigation
		);

		return $screen
			&& ( in_array( $screen->id, $screens_with_custom_navigation, true ) )
			&& ( 'term' !== $screen->base );
	}


	/**
	 * admin_install_notice function.
	 *
	 * @deprecated 3.1.0
	 * @access public
	 * @return void
	 */
	function admin_install_notice() {
		_deprecated_function( __METHOD__, '3.1.0', 'Sensei_Setup_Wizard::setup_wizard_notice' );
	}


	/**
	 * admin_installed_notice function.
	 *
	 * @deprecated 3.1.0
	 * @access public
	 * @return void
	 */
	function admin_installed_notice() {
		_deprecated_function( __METHOD__, '3.1.0', 'Sensei_Setup_Wizard::setup_wizard_notice' );
	}

	/**
	 * admin_notices_styles function.
	 *
	 * @deprecated 3.1.0
	 * @access public
	 * @return void
	 */
	function admin_notices_styles() {
		_deprecated_function( __METHOD__, '3.1.0', 'Sensei_Setup_Wizard::setup_wizard_notice' );
	}

	/**
	 * Add links for duplicating lessons & courses
	 *
	 * @param  array  $actions Default actions
	 * @param  object $post    Current post
	 * @return array           Modified actions
	 */
	public function duplicate_action_link( $actions, $post ) {
		switch ( $post->post_type ) {
			case 'lesson':
				$confirm              = __( 'This will duplicate the lesson quiz and all of its questions. Are you sure you want to do this?', 'sensei-lms' );
				$actions['duplicate'] = "<a onclick='return confirm(\"" . $confirm . "\");' href='" . $this->get_duplicate_link( $post->ID ) . "' title='" . esc_attr( __( 'Duplicate this lesson', 'sensei-lms' ) ) . "'>" . __( 'Duplicate', 'sensei-lms' ) . '</a>';
				break;

			case 'course':
				$confirm                           = __( 'This will duplicate the course lessons along with all of their quizzes and questions. Are you sure you want to do this?', 'sensei-lms' );
				$actions['duplicate']              = '<a href="' . $this->get_duplicate_link( $post->ID ) . '" title="' . esc_attr( __( 'Duplicate this course', 'sensei-lms' ) ) . '">' . __( 'Duplicate', 'sensei-lms' ) . '</a>';
				$actions['duplicate_with_lessons'] = '<a onclick="return confirm(\'' . $confirm . '\');" href="' . $this->get_duplicate_link( $post->ID, true ) . '" title="' . esc_attr( __( 'Duplicate this course with its lessons', 'sensei-lms' ) ) . '">' . __( 'Duplicate (with lessons)', 'sensei-lms' ) . '</a>';
				break;
		}

		return $actions;
	}

	/**
	 * Generate duplicationlink
	 *
	 * @param  integer $post_id      Post ID
	 * @param  boolean $with_lessons Include lessons or not
	 * @return string                Duplication link
	 */
	private function get_duplicate_link( $post_id = 0, $with_lessons = false ) {

		$post = get_post( $post_id );

		$action = 'duplicate_' . $post->post_type;

		if ( 'course' == $post->post_type && $with_lessons ) {
			$action .= '_with_lessons';
		}

		$bare_url = admin_url( 'admin.php?action=' . $action . '&post=' . $post_id );
		$url      = wp_nonce_url( $bare_url, $action . '_' . $post_id );

		return apply_filters( $action . '_link', $url, $post_id );
	}

	/**
	 * Duplicate lesson
	 *
	 * @return void
	 */
	public function duplicate_lesson_action() {
		$this->duplicate_content( 'lesson' );
	}

	/**
	 * Duplicate course
	 *
	 * @return void
	 */
	public function duplicate_course_action() {
		$this->duplicate_content( 'course' );
	}

	/**
	 * Duplicate course with lessons.
	 *
	 * @return void
	 */
	public function duplicate_course_with_lessons_action() {
		$this->duplicate_content( 'course', true );
	}

	/**
	 * Redirect the user safely.
	 *
	 * @access private
	 * @param  string $redirect_url URL to redirect the user.
	 * @return void
	 */
	protected function safe_redirect( $redirect_url ) {
		wp_safe_redirect( esc_url_raw( $redirect_url ) );
		exit;
	}

	/**
	 * Duplicate content.
	 *
	 * @param  string  $post_type    Post type being duplicated.
	 * @param  boolean $with_lessons Include lessons or not.
	 * @return void
	 */
	private function duplicate_content( $post_type = 'lesson', $with_lessons = false ) {
		if ( ! isset( $_GET['post'] ) ) {
			// translators: Placeholder is the post type string.
			wp_die( esc_html( sprintf( __( 'Please supply a %1$s ID.', 'sensei-lms' ) ), $post_type ) );
		}

		$post_id = $_GET['post'];
		$post    = get_post( $post_id );
		if ( ! in_array( get_post_type( $post_id ), array( 'lesson', 'course' ), true ) ) {
			wp_die( esc_html__( 'Invalid post type. Can duplicate only lessons and courses', 'sensei-lms' ) );
		}

		$event = false;
		if ( 'course' === $post_type ) {
			$event = 'course_duplicate';
		} elseif ( 'lesson' === $post_type ) {
			$event = 'lesson_duplicate';
		}

		$event_properties = [
			$post_type . '_id' => $post_id,
		];

		$action = 'duplicate_' . $post_type;
		if ( $with_lessons ) {
			$action .= '_with_lessons';
		}
		check_admin_referer( $action . '_' . $post_id );
		if ( ! current_user_can( 'manage_sensei_grades' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'sensei-lms' ) );
		}

		if ( ! is_wp_error( $post ) ) {

			$new_post = $this->duplicate_post( $post );

			if ( $new_post && ! is_wp_error( $new_post ) ) {

				if ( 'lesson' == $new_post->post_type ) {
					$this->duplicate_lesson_quizzes( $post_id, $new_post->ID );
				}

				if ( 'course' == $new_post->post_type && $with_lessons ) {
					$event                            = 'course_duplicate_with_lessons';
					$event_properties['lesson_count'] = $this->duplicate_course_lessons( $post_id, $new_post->ID );
				}

				$redirect_url = admin_url( 'post.php?post=' . $new_post->ID . '&action=edit' );
			} else {
				$redirect_url = admin_url( 'edit.php?post_type=' . $post->post_type . '&message=duplicate_failed' );
			}

			// Log event.
			if ( $event ) {
				sensei_log_event( $event, $event_properties );
			}

			$this->safe_redirect( $redirect_url );
		}
	}

	/**
	 * Duplicate quizzes inside lessons.
	 *
	 * @param  integer $old_lesson_id ID of original lesson.
	 * @param  integer $new_lesson_id ID of duplicate lesson.
	 * @return void
	 */
	private function duplicate_lesson_quizzes( $old_lesson_id, $new_lesson_id ) {
		$old_quiz_id = Sensei()->lesson->lesson_quizzes( $old_lesson_id );

		if ( empty( $old_quiz_id ) ) {
			return;
		}

		$old_quiz_questions = Sensei()->lesson->lesson_quiz_questions( $old_quiz_id );

		// duplicate the generic wp post information
		$new_quiz = $this->duplicate_post( get_post( $old_quiz_id ), '' );

		// update the new lesson data
		add_post_meta( $new_lesson_id, '_lesson_quiz', $new_quiz->ID );

		// update the new quiz data
		add_post_meta( $new_quiz->ID, '_quiz_lesson', $new_lesson_id );
		wp_update_post(
			array(
				'ID'          => $new_quiz->ID,
				'post_parent' => $new_lesson_id,
			)
		);

		foreach ( $old_quiz_questions as $question ) {

			// copy the question order over to the new quiz
			$old_question_order = get_post_meta( $question->ID, '_quiz_question_order' . $old_quiz_id, true );
			$new_question_order = str_ireplace( $old_quiz_id, $new_quiz->ID, $old_question_order );
			add_post_meta( $question->ID, '_quiz_question_order' . $new_quiz->ID, $new_question_order );

			// Add question to quiz
			add_post_meta( $question->ID, '_quiz_id', $new_quiz->ID, false );

		}
	}

	/**
	 * Update prerequisite ids after course duplication.
	 *
	 * @param  array $lessons_to_update    List with lesson_id and old_prerequisite_id id to update.
	 * @param  array $new_lesson_id_lookup History with the id before and after duplication.
	 * @return void
	 */
	private function update_lesson_prerequisite_ids( $lessons_to_update, $new_lesson_id_lookup ) {
		foreach ( $lessons_to_update as $lesson_to_update ) {
			$old_prerequisite_id = $lesson_to_update['old_prerequisite_id'];
			$new_prerequisite_id = $new_lesson_id_lookup[ $old_prerequisite_id ];
			add_post_meta( $lesson_to_update['lesson_id'], '_lesson_prerequisite', $new_prerequisite_id );
		}
	}

	/**
	 * Get an prerequisite update object.
	 *
	 * @param  integer $old_lesson_id ID of the lesson before the duplication.
	 * @param  integer $new_lesson_id New ID of the lesson.
	 * @return array                  Object with the id of the lesson to update and its old prerequisite id.
	 */
	private function get_prerequisite_update_object( $old_lesson_id, $new_lesson_id ) {
		$lesson_prerequisite = get_post_meta( $old_lesson_id, '_lesson_prerequisite', true );

		if ( ! empty( $lesson_prerequisite ) ) {
			return array(
				'lesson_id'           => $new_lesson_id,
				'old_prerequisite_id' => $lesson_prerequisite,
			);
		}

		return null;
	}

	/**
	 * Update the _lesson_order meta on the duplicated Course so that it uses
	 * the new Lesson IDs.
	 *
	 * @since 3.0.0
	 *
	 * @param int   $course_id            The ID of the new Course.
	 * @param array $new_lesson_id_lookup An array mapping old lesson IDs to the
	 *                                    IDs of their duplicates.
	 */
	private function update_lesson_order_on_course( $course_id, $new_lesson_id_lookup ) {
		$old_lesson_order_string = get_post_meta( $course_id, '_lesson_order', true );

		if ( empty( $old_lesson_order_string ) ) {
			return;
		}

		$old_lesson_order = explode( ',', $old_lesson_order_string );
		$new_lesson_order = [];

		// Map old lesson IDs to new IDs.
		foreach ( $old_lesson_order as $old_lesson_id ) {
			if ( ! isset( $new_lesson_id_lookup[ $old_lesson_id ] ) ) {
				continue;
			}

			// Add new lesson ID to order.
			$new_lesson_id      = $new_lesson_id_lookup[ $old_lesson_id ];
			$new_lesson_order[] = $new_lesson_id;
		}

		// Persist new lesson order to course meta.
		$new_lesson_order_string = join( ',', $new_lesson_order );
		update_post_meta( $course_id, '_lesson_order', $new_lesson_order_string );
	}

	/**
	 * Update the _order_<course-id> on a newly duplicated Lesson to use the
	 * new Course ID.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_Post $lesson        The new Lesson.
	 * @param int     $old_course_id The ID of the old Course that was duplicated.
	 * @param int     $new_course_id The ID of the new Course.
	 */
	private function update_lesson_order_on_lesson( $lesson, $old_course_id, $new_course_id ) {
		$lesson_order_value = get_post_meta( $lesson->ID, "_order_$old_course_id", true );
		update_post_meta( $lesson->ID, "_order_$new_course_id", $lesson_order_value );
		delete_post_meta( $lesson->ID, "_order_$old_course_id" );
	}

	/**
	 * Duplicate lessons inside a course.
	 *
	 * @param  integer $old_course_id ID of original course.
	 * @param  integer $new_course_id ID of duplicated course.
	 * @return int Number of lessons duplicated.
	 */
	private function duplicate_course_lessons( $old_course_id, $new_course_id ) {
		$lessons              = Sensei()->course->course_lessons( $old_course_id, 'any' );
		$new_lesson_id_lookup = array();
		$lessons_to_update    = array();

		foreach ( $lessons as $lesson ) {
			$new_lesson = $this->duplicate_post( $lesson, '', true );
			add_post_meta( $new_lesson->ID, '_lesson_course', $new_course_id );

			$update_prerequisite_object = $this->get_prerequisite_update_object( $lesson->ID, $new_lesson->ID );

			if ( ! is_null( $update_prerequisite_object ) ) {
				$lessons_to_update[] = $update_prerequisite_object;
			}

			$new_lesson_id_lookup[ $lesson->ID ] = $new_lesson->ID;
			$this->duplicate_lesson_quizzes( $lesson->ID, $new_lesson->ID );

			// Update the _order_<course-id> meta on the lesson.
			$this->update_lesson_order_on_lesson( $new_lesson, $old_course_id, $new_course_id );
		}

		$this->update_lesson_prerequisite_ids( $lessons_to_update, $new_lesson_id_lookup );

		// Update the _lesson_order meta on the course.
		$this->update_lesson_order_on_course( $new_course_id, $new_lesson_id_lookup );

		return count( $lessons );
	}

	/**
	 * Duplicate post.
	 *
	 * @param  object  $post          Post to be duplicated.
	 * @param  string  $suffix        Suffix for duplicated post title.
	 * @param  boolean $ignore_course Ignore lesson course when dulicating.
	 * @return object                 Duplicate post object.
	 */
	private function duplicate_post( $post, $suffix = null, $ignore_course = false ) {

		$new_post = array();

		foreach ( $post as $k => $v ) {
			if ( ! in_array( $k, array( 'ID', 'post_status', 'post_date', 'post_date_gmt', 'post_name', 'post_modified', 'post_modified_gmt', 'guid', 'comment_count' ) ) ) {
				$new_post[ $k ] = $v;
			}
		}

		$new_post['post_title']       .= $suffix;
		$new_post['post_date']         = current_time( 'mysql' );
		$new_post['post_date_gmt']     = get_gmt_from_date( $new_post['post_date'] );
		$new_post['post_modified']     = $new_post['post_date'];
		$new_post['post_modified_gmt'] = $new_post['post_date_gmt'];

		switch ( $post->post_type ) {
			case 'course':
				$new_post['post_status'] = 'draft';
				break;
			case 'lesson':
				$new_post['post_status'] = 'draft';
				break;
			case 'quiz':
				$new_post['post_status'] = 'publish';
				break;
			case 'question':
				$new_post['post_status'] = 'publish';
				break;
		}

		// As per wp_update_post() we need to escape the data from the db.
		$new_post = wp_slash( $new_post );

		/**
		 * Filter arguments for `wp_insert_post` when duplicating a Sensei
		 * post. This may be a Course, Lesson, or Quiz.
		 *
		 * @hook  sensei_duplicate_post_args
		 * @since 3.11.0
		 *
		 * @param {array}   $new_post The arguments for duplicating the post.
		 * @param {WP_Post} $post     The original post being duplicated.
		 *
		 * @return {array}  The new arguments to be handed to `wp_insert_post`.
		 */
		$new_post = apply_filters( 'sensei_duplicate_post_args', $new_post, $post );

		$new_post_id = wp_insert_post( $new_post );

		if ( ! is_wp_error( $new_post_id ) ) {

			$post_meta = get_post_custom( $post->ID );
			if ( $post_meta && count( $post_meta ) > 0 ) {

				/**
				 * Ignored meta fields when duplicating a post.
				 *
				 * @hook  sensei_duplicate_post_ignore_meta
				 * @since 3.7.0
				 *
				 * @param {array}   $meta_keys The meta keys to be ignored.
				 * @param {WP_Post} $new_post  The new duplicate post.
				 * @param {WP_Post} $post      The original post that's being duplicated.
				 *
				 * @return {array} $meta_keys The meta keys to be ignored.
				 */
				$ignore_meta = apply_filters( 'sensei_duplicate_post_ignore_meta', [ '_quiz_lesson', '_quiz_id', '_lesson_quiz', '_lesson_prerequisite' ], $new_post, $post );

				if ( $ignore_course ) {
					$ignore_meta[] = '_lesson_course';
				}

				foreach ( $post_meta as $key => $meta ) {
					foreach ( $meta as $value ) {
						$value = maybe_unserialize( $value );
						if ( ! in_array( $key, $ignore_meta ) ) {
							add_post_meta( $new_post_id, $key, $value );
						}
					}
				}
			}

			add_post_meta( $new_post_id, '_duplicate', $post->ID );

			$taxonomies = get_object_taxonomies( $post->post_type, 'objects' );

			foreach ( $taxonomies as $slug => $tax ) {
				$terms = get_the_terms( $post->ID, $slug );
				if ( isset( $terms ) && is_array( $terms ) && 0 < count( $terms ) ) {
					foreach ( $terms as $term ) {
						wp_set_object_terms( $new_post_id, $term->term_id, $slug, true );
					}
				}
			}

			$new_post = get_post( $new_post_id );

			return $new_post;
		}

		return false;
	}

	/**
	 * Add options to filter lessons
	 *
	 * @return void
	 */
	public function lesson_filter_options() {
		global $typenow;

		if ( is_admin() && 'lesson' == $typenow ) {

			$args    = array(
				'post_type'        => 'course',
				'post_status'      => array( 'publish', 'pending', 'draft', 'future', 'private' ),
				'posts_per_page'   => -1,
				'suppress_filters' => 0,
				'orderby'          => 'title menu_order date',
				'order'            => 'ASC',
			);
			$courses = get_posts( $args );

			$selected       = isset( $_GET['lesson_course'] ) ? $_GET['lesson_course'] : '';
			$course_options = '';
			foreach ( $courses as $course ) {
				$course_options .= '<option value="' . esc_attr( $course->ID ) . '" ' . selected( $selected, $course->ID, false ) . '>' . esc_html( get_the_title( $course->ID ) ) . '</option>';
			}

			$output  = '<select name="lesson_course" id="dropdown_lesson_course">';
			$output .= '<option value="">' . esc_html__( 'Show all courses', 'sensei-lms' ) . '</option>';
			$output .= $course_options;
			$output .= '</select>';

			$allowed_html = array(
				'option' => array(
					'selected' => array(),
					'value'    => array(),
				),
				'select' => array(
					'id'   => array(),
					'name' => array(),
				),
			);

			echo wp_kses( $output, $allowed_html );
		}
	}

	/**
	 * Filter lessons
	 *
	 * @param  array $request Current request
	 * @return array          Modified request
	 */
	public function lesson_filter_actions( $request ) {
		global $typenow;

		if ( is_admin() && 'lesson' == $typenow ) {
			$lesson_course = isset( $_GET['lesson_course'] ) ? $_GET['lesson_course'] : '';

			if ( $lesson_course ) {
				$request['meta_key']     = '_lesson_course';
				$request['meta_value']   = $lesson_course;
				$request['meta_compare'] = '=';
			}
		}

		return $request;
	}

	/**
	 * Adding Sensei items to 'At a glance' dashboard widget
	 *
	 * @param  array $items Existing items
	 * @return array        Updated items
	 */
	public function glance_items( $items = array() ) {

		$types = array( 'course', 'lesson', 'question' );

		foreach ( $types as $type ) {
			if ( ! post_type_exists( $type ) ) {
				continue;
			}

			$num_posts = wp_count_posts( $type );

			if ( $num_posts ) {

				$published = intval( $num_posts->publish );
				$post_type = get_post_type_object( $type );

				$text = '%s ' . $post_type->labels->singular_name;
				$text = sprintf( $text, number_format_i18n( $published ) );

				if ( current_user_can( $post_type->cap->edit_posts ) ) {
					$items[] = sprintf( '<a class="%1$s-count" href="edit.php?post_type=%1$s">%2$s</a>', $type, $text ) . "\n";
				} else {
					$items[] = sprintf( '<span class="%1$s-count">%2$s</span>', $type, $text ) . "\n";
				}
			}
		}

		return $items;
	}

	/**
	 * Process lesson and course deletion
	 *
	 * @param  integer $post_id Post ID
	 * @param  object  $post    Post object
	 * @return void
	 */
	public function delete_content( $post_id, $post ) {

		$type = $post->post_type;

		if ( in_array( $type, array( 'lesson', 'course' ) ) ) {

			$meta_key = '_' . $type . '_prerequisite';

			$args = array(
				'post_type'      => $type,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'meta_key'       => $meta_key,
				'meta_value'     => $post_id,
			);

			$posts = get_posts( $args );

			foreach ( $posts as $post ) {
				delete_post_meta( $post->ID, $meta_key );
			}
		}
	}

	/**
	 * Delete all user activity when user is deleted.
	 *
	 * @deprecated 3.0.0 Use `\Sensei_Learner::delete_all_user_activity` instead.
	 *
	 * @param  integer $user_id User ID.
	 * @return void
	 */
	public function delete_user_activity( $user_id = 0 ) {
		_deprecated_function( __METHOD__, '3.0.0', 'Sensei_Learner::delete_all_user_activity' );

		\Sensei_Learner::instance()->delete_all_user_activity( $user_id );
	}

	public function render_settings( $settings = array(), $post_id = 0, $group_id = '' ) {

		$html = '';

		if ( 0 == count( $settings ) ) {
			return $html;
		}

		$html .= '<div class="sensei-options-panel">' . "\n";

			$html .= '<div class="options_group" id="' . esc_attr( $group_id ) . '">' . "\n";

		foreach ( $settings as $field ) {

			$data = '';

			if ( $post_id ) {
				if ( 'plain-text' !== $field['type'] ) {
					$data = get_post_meta( $post_id, '_' . $field['id'], true );
					if ( ! $data && isset( $field['default'] ) ) {
						$data = $field['default'];
					}
				} else {
					$data = $field['default'];
				}
			} else {
				$option = get_option( $field['id'] );
				if ( isset( $field['default'] ) ) {
					$data = $field['default'];
					if ( $option ) {
						$data = $option;
					}
				}
			}

			if ( ! isset( $field['disabled'] ) ) {
				$field['disabled'] = false;
			}

			if ( 'hidden' != $field['type'] ) {

				$class_tail = '';

				if ( isset( $field['class'] ) ) {
					$class_tail .= $field['class'];
				}

				if ( isset( $field['disabled'] ) && $field['disabled'] ) {
					$class_tail .= ' disabled';
				}

				$html .= '<p class="form-field ' . esc_attr( $field['id'] ) . ' ' . esc_attr( $class_tail ) . '">' . "\n";
			}

			if ( ! in_array( $field['type'], array( 'hidden', 'checkbox_multi', 'radio' ) ) ) {
					$html .= '<label for="' . esc_attr( $field['id'] ) . '">' . "\n";
			}

			if ( $field['label'] ) {
				$html .= '<span class="label">' . esc_html( $field['label'] ) . '</span>';
			}

			switch ( $field['type'] ) {
				case 'plain-text':
					$html .= '<strong>' . esc_html( $data ) . '</strong>';
					break;
				case 'text':
				case 'password':
					$html .= '<input id="' . esc_attr( $field['id'] ) . '" ';
					$html .= 'type="' . esc_attr( $field['type'] ) . '" ';
					$html .= 'name="' . esc_attr( $field['id'] ) . '" ';
					$html .= 'placeholder="' . esc_attr( $field['placeholder'] ) . '" ';
					$html .= 'value="' . esc_attr( $data ) . '" ';
					$html .= disabled( $field['disabled'], true, false );
					$html .= ' />' . "\n";
					break;

				case 'number':
					$min = '';
					if ( isset( $field['min'] ) ) {
						$min = 'min="' . esc_attr( $field['min'] ) . '"';
					}

					$max = '';
					if ( isset( $field['max'] ) ) {
						$max = 'max="' . esc_attr( $field['max'] ) . '"';
					}

					$html .= '<input id="' . esc_attr( $field['id'] ) . '" ';
					$html .= 'type="' . esc_attr( $field['type'] ) . '" ';
					$html .= 'name="' . esc_attr( $field['id'] ) . '" ';
					$html .= 'placeholder="' . esc_attr( $field['placeholder'] ) . '" ';
					$html .= 'value="' . esc_attr( $data ) . '" ';
					$html .= $min . '  ' . $max . ' '; // $min and $max are escaped above, for better readibility
					$html .= 'class="small-text" ';
					$html .= disabled( $field['disabled'], true, false );
					$html .= ' />' . "\n";
					break;

				case 'textarea':
					$html .= '<textarea id="' . esc_attr( $field['id'] ) . '" ';
					$html .= 'rows="5" cols="50" ';
					$html .= 'name="' . esc_attr( $field['id'] ) . '" ';
					$html .= 'placeholder="' . esc_attr( $field['placeholder'] ) . '" ';
					$html .= disabled( $field['disabled'], true, false );
					$html .= '>' . strip_tags( $data ) . '</textarea><br/>' . "\n";
					break;

				case 'checkbox':
					// backwards compatibility
					if ( empty( $data ) || 'on' == $data ) {
						$checked_value = 'on';
					} elseif ( 'yes' == $data ) {

						$checked_value = 'yes';

					} elseif ( 'auto' == $data ) {

						$checked_value = 'auto';

					} else {
						$checked_value = 1;
						$data          = intval( $data );
					}

					$html .= '<input id="' . esc_attr( $field['id'] ) . '" ';
					$html .= 'type="' . esc_attr( $field['type'] ) . '" ';
					$html .= 'name="' . esc_attr( $field['id'] ) . '" ';
					$html .= checked( $checked_value, $data, false );
					$html .= disabled( $field['disabled'], true, false );
					$html .= " /> \n";

					// Input hidden to identify if checkbox is present.
					$html .= '<input type="hidden" ';
					$html .= 'name="contains_' . esc_attr( $field['id'] ) . '" ';
					$html .= 'value="1" ';
					$html .= " /> \n";
					break;

				case 'checkbox_multi':
					foreach ( $field['options'] as $k => $v ) {
						$checked = false;
						if ( in_array( $k, $data ) ) {
							$checked = true;
						}

						$html     .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '">';
							$html .= '<input type="checkbox" ';
							$html .= checked( $checked, true, false ) . ' ';
							$html .= 'name="' . esc_attr( $field['id'] ) . '[]" ';
							$html .= 'value="' . esc_attr( $k ) . '" ';
							$html .= 'id="' . esc_attr( $field['id'] . '_' . $k ) . '" ';
							$html .= disabled( $field['disabled'], true, false );
							$html .= ' /> ' . esc_html( $v );
						$html     .= "</label> \n";
					}
					break;

				case 'radio':
					foreach ( $field['options'] as $k => $v ) {
						$checked = false;
						if ( $k == $data ) {
							$checked = true;
						}

						$html     .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '">';
							$html .= '<input type="radio" ';
							$html .= checked( $checked, true, false ) . ' ';
							$html .= 'name="' . esc_attr( $field['id'] ) . '" ';
							$html .= 'value="' . esc_attr( $k ) . '" ';
							$html .= 'id="' . esc_attr( $field['id'] . '_' . $k ) . '" ';
							$html .= disabled( $field['disabled'], true, false );
							$html .= ' /> ' . esc_html( $v );
						$html     .= "</label> \n";
					}
					break;

				case 'select':
					$html .= '<select name="' . esc_attr( $field['id'] ) . '" ';
					$html .= 'id="' . esc_attr( $field['id'] ) . '" ';
					$html .= disabled( $field['disabled'], true, false );
					$html .= ">\n";

					foreach ( $field['options'] as $k => $v ) {
						$selected = false;
						if ( $k == $data ) {
							$selected = true;
						}

						$html .= '<option ' . selected( $selected, true, false ) . ' ';
						$html .= 'value="' . esc_attr( $k ) . '">' . esc_html( $v ) . "</option>\n";
					}

					$html .= "</select><br/>\n";
					break;

				case 'select_multi':
					$html .= '<select name="' . esc_attr( $field['id'] ) . '[]" ';
					$html .= 'id="' . esc_attr( $field['id'] ) . '" multiple="multiple" ';
					$html .= disabled( $field['disabled'], true, false );
					$html .= ">\n";

					foreach ( $field['options'] as $k => $v ) {
						$selected = false;
						if ( in_array( $k, $data ) ) {
							$selected = true;
						}
						$html .= '<option ' . selected( $selected, true, false ) . ' ';
						$html .= 'value="' . esc_attr( $k ) . '" />' . esc_html( $v ) . "</option>\n";
					}

					$html .= "</select>\n";
					break;

				case 'hidden':
					$html .= '<input id="' . esc_attr( $field['id'] ) . '" ';
					$html .= 'type="' . esc_attr( $field['type'] ) . '" ';
					$html .= 'name="' . esc_attr( $field['id'] ) . '" ';
					$html .= 'value="' . esc_attr( $data ) . '" ';
					$html .= disabled( $field['disabled'], true, false );
					$html .= "/>\n";
					break;

			}

			if ( $field['description'] ) {
				$html .= ' <span class="description">' . esc_html( $field['description'] ) . '</span>' . "\n";
			}

			if ( ! in_array( $field['type'], array( 'hidden', 'checkbox_multi', 'radio' ) ) ) {
						$html .= '</label>' . "\n";
			}

			if ( 'hidden' != $field['type'] ) {
				$html .= "</p>\n";
			}
		}

			$html .= "</div>\n";
		$html     .= "</div>\n";

		return $html;
	}

	/**
	 * Handle the POST request for reordering the Courses.
	 *
	 * @since 1.12.2
	 */
	public function handle_order_courses() {
		check_admin_referer( 'order_courses' );

		$ordered = null;
		if ( isset( $_POST['course-order'] ) && 0 < strlen( $_POST['course-order'] ) ) {
			$ordered = $this->save_course_order( esc_attr( $_POST['course-order'] ) );
		}

		wp_redirect(
			esc_url_raw(
				add_query_arg(
					array(
						'page'    => $this->course_order_page_slug,
						'ordered' => $ordered,
					),
					admin_url( 'admin.php' )
				)
			)
		);
	}

	/**
	 * Dsplay Course Order screen
	 *
	 * @return void
	 */
	public function course_order_screen() {

		$should_update_order = false;
		$new_course_order    = array();

		?>
		<div id="<?php echo esc_attr( $this->course_order_page_slug ); ?>" class="wrap <?php echo esc_attr( $this->course_order_page_slug ); ?>">
		<h1><?php esc_html_e( 'Order Courses', 'sensei-lms' ); ?></h1>
							  <?php

								$html = '';

								if ( isset( $_GET['ordered'] ) && $_GET['ordered'] ) {
									$html .= '<div class="updated fade">' . "\n";
									$html .= '<p>' . esc_html__( 'The course order has been saved.', 'sensei-lms' ) . '</p>' . "\n";
									$html .= '</div>' . "\n";
								}

								$courses = Sensei()->course->get_all_courses();

								if ( 0 < count( $courses ) ) {

									// order the courses as set by the users
									$all_course_ids = array();
									foreach ( $courses as $course ) {

										$all_course_ids[] = (string) $course->ID;

									}
									$order_string = $this->get_course_order();

									if ( ! empty( $order_string ) ) {
										$ordered_course_ids = explode( ',', $order_string );
										$all_course_ids     = array_unique( array_merge( $ordered_course_ids, $all_course_ids ) );
									}

									$html .= '<form id="editgrouping" method="post" action="'
										. esc_url( admin_url( 'admin-post.php' ) ) . '" class="validate">' . "\n";
									$html .= '<ul class="sortable-course-list">' . "\n";
									foreach ( $all_course_ids as $course_id ) {
										$course = get_post( $course_id );
										if ( empty( $course ) || in_array( $course->post_status, array( 'trash', 'auto-draft' ), true ) ) {
											$should_update_order = true;
											continue;
										}
										$new_course_order[] = $course_id;

										$title = $course->post_title;
										if ( $course->post_status === 'draft' ) {
											$title .= ' (Draft)';
										}

										$html .= '<li class="course"><span rel="' . esc_attr( $course->ID ) . '" style="width: 100%;"> ' . esc_html( $title ) . '</span></li>' . "\n";
									}
									$html .= '</ul>' . "\n";

									$html .= '<input type="hidden" name="action" value="order_courses" />' . "\n";
									$html .= wp_nonce_field( 'order_courses', '_wpnonce', true, false ) . "\n";
									$html .= '<input type="hidden" name="course-order" value="' . esc_attr( $order_string ) . '" />' . "\n";
									$html .= '<input type="submit" class="button-primary" value="' . esc_attr__( 'Save course order', 'sensei-lms' ) . '" />' . "\n";
									$html .= '</form>';
								}

								echo wp_kses(
									$html,
									array_merge(
										wp_kses_allowed_html( 'post' ),
										array(
											// Explicitly allow form tag for WP.com.
											'form'  => array(
												'action' => array(),
												'class'  => array(),
												'id'     => array(),
												'method' => array(),
											),
											'input' => array(
												'class' => array(),
												'name'  => array(),
												'type'  => array(),
												'value' => array(),
											),
											'span'  => array(
												'rel'   => array(),
												'style' => array(),
											),
										)
									)
								);

								?>
		</div>
		<?php

		if ( $should_update_order ) {
			$this->save_course_order( implode( ',', $new_course_order ) );
		}
	}

	public function get_course_order() {
		return get_option( 'sensei_course_order', '' );
	}

	public function save_course_order( $order_string = '' ) {
		global $wpdb;
		$order = array();

		$i = 1;
		foreach ( explode( ',', $order_string ) as $course_id ) {
			if ( $course_id ) {
				$order[] = $course_id;

				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Performance improvement.
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE $wpdb->posts SET menu_order = %d WHERE ID = %d",
						$i,
						absint( $course_id )
					)
				);

				++$i;
			}
		}

		update_option( 'sensei_course_order', implode( ',', $order ) );

		return true;
	}

	/**
	 * Handle the POST request for reordering the Lessons.
	 *
	 * @since 1.12.2
	 */
	public function handle_order_lessons() {
		check_admin_referer( 'order_lessons' );
		if ( ! current_user_can( 'edit_published_lessons' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'sensei-lms' ) );
		}

		if (
			empty( $_POST['course_id'] )
			|| empty( $_POST['lessons'] )
		) {
			_doing_it_wrong(
				'handle_order_lessons',
				'The handle_order_lessons AJAX call should be a POST request with parameters "course_id" and "lessons".',
				'4.1.0'
			);

			wp_die();
		}

		$lessons_order = [];
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- The input is sanitized by hand.
		foreach ( $_POST['lessons'] as $lesson_id => $lesson_data ) {
			$lessons_order[ (int) $lesson_id ] = [
				'module' => (int) $lesson_data['module'],
			];
		}

		$course_id = (int) $_POST['course_id'];
		$ordered   = $this->sync_lesson_order(
			$lessons_order,
			$course_id
		);

		wp_safe_redirect(
			esc_url_raw(
				add_query_arg(
					array(
						'page'      => $this->lesson_order_page_slug,
						'ordered'   => $ordered,
						'course_id' => $course_id,
					),
					admin_url( 'admin.php' )
				)
			)
		);
		exit;
	}

	/**
	 * Dsplay Lesson Order screen
	 *
	 * @return void
	 */
	public function lesson_order_screen() {

		?>
		<div id="<?php echo esc_attr( $this->lesson_order_page_slug ); ?>" class="wrap <?php echo esc_attr( $this->lesson_order_page_slug ); ?>">
			<h1><?php esc_html_e( 'Order Lessons', 'sensei-lms' ); ?></h1>
			<?php

			$html = '';

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- No need to unslash or sanitize in this case.
			if ( isset( $_GET['ordered'] ) && $_GET['ordered'] ) {
				$html .= '<div class="updated fade">' . "\n";
				$html .= '<p>' . esc_html__( 'The lesson order has been saved.', 'sensei-lms' ) . '</p>' . "\n";
				$html .= '</div>' . "\n";
			}

			$args = array(
				'post_type'      => 'course',
				'post_status'    => array( 'publish', 'draft', 'future', 'private' ),
				'posts_per_page' => -1,
				'orderby'        => 'name',
				'order'          => 'ASC',
			);

			// Ensure that the user either has permission to edit other's courses or is the author of the course.
			if ( ! current_user_can( 'edit_others_courses' ) ) {
				$args['author'] = get_current_user_id();
			}

			$courses = get_posts( $args );

			$html .= '<form action="' . esc_url( admin_url( 'admin.php' ) ) . '" method="get">' . "\n";
			$html .= '<input type="hidden" name="post_type" value="course" />' . "\n";
			$html .= '<input type="hidden" name="page" value="lesson-order" />' . "\n";
			$html .= '<select id="lesson-order-course" name="course_id">' . "\n";
			$html .= '<option value="">' . esc_html__( 'Select a course', 'sensei-lms' ) . '</option>' . "\n";

			foreach ( $courses as $course ) {
				$course_id = '';
				if ( isset( $_GET['course_id'] ) ) {
					$course_id = intval( $_GET['course_id'] );
				}
				$html .= '<option value="' . esc_attr( intval( $course->ID ) ) . '" ' . selected( $course->ID, $course_id, false ) . '>' . esc_html( get_the_title( $course->ID ) ) . '</option>' . "\n";
			}

			$html .= '</select>' . "\n";
			$html .= '<input type="submit" class="button-primary lesson-order-select-course-submit" value="' . esc_attr__( 'Select', 'sensei-lms' ) . '" />' . "\n";
			$html .= '</form>' . "\n";

			if ( isset( $_GET['course_id'] ) ) {
				$course_id = intval( $_GET['course_id'] );
				if ( $course_id > 0 ) {
					$course_structure = $this->get_course_structure( $course_id );
					$modules          = $this->get_course_structure( $course_structure, 'module' );
					$has_lessons      = false;

					// Form start.
					$html .= '<form id="editgrouping" method="post" action="'
						. esc_url( admin_url( 'admin-post.php' ) ) . '" class="validate">' . "\n";

					foreach ( $modules as $module ) {
						// Modules.
						$html .= '<h3>' . esc_html( $module['title'] ) . '</h3>' . "\n";
						$html .= '<ul class="sortable-lesson-list" data-module-id="' . esc_attr( $module['id'] ) . '">' . "\n";

						if ( $module['lessons'] ) {
							$has_lessons = true;

							foreach ( $module['lessons'] as $lesson ) {
								$html .= '<li class="lesson">';
								$html .= '<span rel="' . esc_attr( $lesson['id'] ) . '" style="width: 100%;"> ' . esc_html( $lesson['title'] ) . '</span>';
								$html .= '<input type="hidden" name="lessons[' . intval( $lesson['id'] ) . '][module]" value="' . intval( $module['id'] ) . '">';
								$html .= '</li>' . "\n";
							}
						}

						$html .= '</ul>' . "\n";
					}

					$other_lessons = $this->get_course_structure( $course_structure, 'lesson' );
					$has_lessons   = $has_lessons || $other_lessons;

					if ( $has_lessons ) {
						// Other Lessons (lessons not related to a module).
						$html .= '<h3>' . esc_html__( 'Other Lessons', 'sensei-lms' ) . '</h3>' . "\n";
						$html .= '<ul class="sortable-lesson-list" data-module-id="0">' . "\n";

						foreach ( $other_lessons as $other_lesson ) {
							$html .= '<li class="lesson"><span rel="' . esc_attr( $other_lesson['id'] ) . '" style="width: 100%;"> ' . esc_html( $other_lesson['title'] ) . '</span>';
							$html .= '<input type="hidden" name="lessons[' . intval( $other_lesson['id'] ) . '][module]" value="">';
							$html .= '</li>' . "\n";
						}

						$html .= '</ul>' . "\n";

						// Form end.
						$html .= '<input type="hidden" name="action" value="order_lessons" />' . "\n";
						$html .= wp_nonce_field( 'order_lessons', '_wpnonce', true, false ) . "\n";
						$html .= '<input type="hidden" name="course_id" value="' . esc_attr( $course_id ) . '" />' . "\n";
						$html .= '<input type="submit" class="button-primary" value="' . esc_attr__( 'Save lesson order', 'sensei-lms' ) . '" />' . "\n";
						$html .= '</form>';
					} else {
						$html .= '<p><em>' . esc_html__( 'There are no lessons in this course.', 'sensei-lms' ) . '</em></p>';
					}
				}
			}

			echo wp_kses(
				$html,
				array_merge(
					wp_kses_allowed_html( 'post' ),
					array(
						// Explicitly allow form tag for WP.com.
						'form'   => array(
							'action' => array(),
							'class'  => array(),
							'id'     => array(),
							'method' => array(),
						),
						'input'  => array(
							'class' => array(),
							'name'  => array(),
							'type'  => array(),
							'value' => array(),
						),
						'option' => array(
							'selected' => array(),
							'value'    => array(),
						),
						'select' => array(
							'id'   => array(),
							'name' => array(),
						),
						'span'   => array(
							'rel'   => array(),
							'style' => array(),
						),
						'ul'     => array(
							'class'          => array(),
							'data-module-id' => array(),
						),
					)
				)
			);

			?>
		</div>
		<?php
	}

	/**
	 * Get lesson order.
	 *
	 * @deprecated 3.6.0
	 *
	 * @param integer $course_id Course ID.
	 *
	 * @return string Order string.
	 */
	public function get_lesson_order( $course_id = 0 ) {
		_deprecated_function( __METHOD__, '3.6.0' );

		$order_string = get_post_meta( $course_id, '_lesson_order', true );
		return $order_string;
	}

	public function save_lesson_order( $order_string = '', $course_id = 0 ) {

		/**
		 * It is safe to ignore nonce validation here, because this is called
		 * from `handle_order_lessons` and the nonce validation is done there.
		 */

		if ( $course_id ) {
			remove_filter( 'get_terms', array( Sensei()->modules, 'append_teacher_name_to_module' ), 70 );
			$course_structure = $this->get_course_structure( intval( $course_id ) );
			add_filter( 'get_terms', array( Sensei()->modules, 'append_teacher_name_to_module' ), 70, 3 );

			$order = array_map( 'absint', explode( ',', $order_string ) );

			$course_structure = Sensei_Course_Structure::sort_structure( $course_structure, $order, 'lesson' );

			// Sort module lessons.
			foreach ( $course_structure as $key => $module ) {
				if ( 'module' !== $module['type'] ) {
					continue;
				}

				if (
					// phpcs:ignore WordPress.Security.NonceVerification
					! empty( $_POST[ 'lesson-order-module-' . $module['id'] ] )
					&& ! empty( $course_structure[ $key ]['lessons'] )
				) {
					// phpcs:ignore WordPress.Security.NonceVerification
					$order = sanitize_text_field( wp_unslash( $_POST[ 'lesson-order-module-' . $module['id'] ] ) );
					$order = array_map( 'absint', explode( ',', $order ) );

					$course_structure[ $key ]['lessons'] = Sensei_Course_Structure::sort_structure( $course_structure[ $key ]['lessons'], $order, 'lesson' );
				}
			}

			if ( true === Sensei_Course_Structure::instance( $course_id )->save( $course_structure ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Sync the course lessons with a list of IDs.
	 *
	 * @since 4.1.0
	 *
	 * @param array $lesson_ids {
	 *     Arguments that accompany the lesson ids.
	 *
	 *     @type int $module The module ID of the lesson.
	 * }
	 * @param int   $course_id
	 *
	 * @return bool
	 */
	private function sync_lesson_order( array $lesson_ids, int $course_id ): bool {

		remove_filter( 'get_terms', array( Sensei()->modules, 'append_teacher_name_to_module' ), 70 );
		$original_course_structure = $this->get_course_structure( $course_id );
		add_filter( 'get_terms', array( Sensei()->modules, 'append_teacher_name_to_module' ), 70, 3 );

		$lessons = [];
		$modules = [];

		// Extract the lessons from the course structure in preparation for the re-ordering.
		foreach ( $original_course_structure as $item ) {
			if ( 'module' === $item['type'] ) {
				foreach ( $item['lessons'] as $lesson ) {
					$lessons[ $lesson['id'] ] = $lesson;
				}

				$item['lessons']        = [];
				$modules[ $item['id'] ] = $item;
			} elseif ( 'lesson' === $item['type'] ) {
				$lessons[ $item['id'] ] = $item;
			}
		}

		// Map the lessons to the modules.
		foreach ( $lesson_ids as $lesson_id => $lesson_data ) {
			$module_id = (int) $lesson_data['module'];
			if ( $module_id ) {
				$modules[ $module_id ]['lessons'][] = $lessons[ $lesson_id ];
			}
		}

		$reordered_course_structure = array_values( $modules );

		// Map the lessons that don't belong to a module.
		foreach ( $lesson_ids as $lesson_id => $lesson_data ) {
			if ( ! $lesson_data['module'] ) {
				$reordered_course_structure[] = $lessons[ $lesson_id ];
			}
		}

		// Save the new course structure.
		$saved = Sensei_Course_Structure::instance( $course_id )
			->save( $reordered_course_structure );

		return true === $saved;
	}

	/**
	 * Get or filter course structure for lesson ordering.
	 *
	 * @param int|array   $course_structure Structure array or course ID to get the structure.
	 * @param null|string $type             Optional type to filter the content.
	 *
	 * @return array Course structure.
	 */
	private function get_course_structure( $course_structure = null, $type = null ) {
		$course_structure = is_array( $course_structure )
			? $course_structure
			: Sensei_Course_Structure::instance( $course_structure )->get( 'edit', wp_using_ext_object_cache() );

		if ( isset( $type ) ) {
			$course_structure = array_filter(
				$course_structure,
				function( $item ) use ( $type ) {
					return $type === $item['type'];
				}
			);
		}

		return $course_structure;
	}

	/**
	 * Registers the hook to call mark_completed on tasks that have been
	 * completed.
	 *
	 * @access private
	 * @return void
	 */
	public function admin_init() {
		global $pagenow;

		if ( Sensei_Home_Task_Sell_Course_With_WooCommerce::is_active() ) {
			$hook = get_plugin_page_hook( 'wc-admin', 'woocommerce' );
			if ( null !== $hook ) {
				add_action( $hook, [ Sensei_Home_Task_Sell_Course_With_WooCommerce::class, 'mark_completed' ] );
			}
		}

		// Mark the Course Theme Customization as completed if we are visiting
		// the site editor or the customizer with the Course theme installed.
		if ( Sensei_Home_Task_Customize_Course_Theme::is_active() ) {
			if ( in_array( $pagenow, [ 'site-editor.php', 'customize.php' ], true ) ) {
				Sensei_Home_Task_Customize_Course_Theme::mark_completed();
			}
		}
	}

	function sensei_add_custom_menu_items() {
		global $pagenow;

		if ( 'nav-menus.php' == $pagenow ) {
			add_meta_box( 'add-sensei-links', 'Sensei LMS', array( $this, 'wp_nav_menu_item_sensei_links_meta_box' ), 'nav-menus', 'side', 'low' );
		}
	}

	function wp_nav_menu_item_sensei_links_meta_box( $object ) {
		global $nav_menu_selected_id;

		$menu_items = array(
			'#senseicourses'        => __( 'Courses', 'sensei-lms' ),
			'#senseilessons'        => __( 'Lessons', 'sensei-lms' ),
			'#senseimycourses'      => __( 'My Courses', 'sensei-lms' ),
			'#senseilearnerprofile' => __( 'My Profile', 'sensei-lms' ),
			'#senseimymessages'     => __( 'My Messages', 'sensei-lms' ),
			'#senseiloginlogout'    => __( 'Login', 'sensei-lms' ) . '|' . __( 'Logout', 'sensei-lms' ),
		);

		$menu_items_obj = array();
		foreach ( $menu_items as $value => $title ) {
			$menu_items_obj[ $title ]                   = new stdClass();
			$menu_items_obj[ $title ]->object_id        = esc_attr( $value );
			$menu_items_obj[ $title ]->title            = esc_attr( $title );
			$menu_items_obj[ $title ]->url              = esc_attr( $value );
			$menu_items_obj[ $title ]->description      = 'description';
			$menu_items_obj[ $title ]->db_id            = 0;
			$menu_items_obj[ $title ]->object           = 'sensei';
			$menu_items_obj[ $title ]->menu_item_parent = 0;
			$menu_items_obj[ $title ]->type             = 'custom';
			$menu_items_obj[ $title ]->target           = '';
			$menu_items_obj[ $title ]->attr_title       = '';
			$menu_items_obj[ $title ]->classes          = array();
			$menu_items_obj[ $title ]->xfn              = '';
		}

		$walker = new Walker_Nav_Menu_Checklist( array() );
		?>

		<div id="sensei-links" class="senseidiv taxonomydiv">
			<div id="tabs-panel-sensei-links-all" class="tabs-panel tabs-panel-view-all tabs-panel-active">

				<ul id="sensei-linkschecklist" class="list:sensei-links categorychecklist form-no-clear">
					<?php echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $menu_items_obj ), 0, (object) array( 'walker' => $walker ) ); ?>
				</ul>

			</div>
			<p class="button-controls">
				<span class="add-to-menu">
					<input type="submit"<?php disabled( $nav_menu_selected_id, 0 ); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu', 'sensei-lms' ); ?>" name="add-sensei-links-menu-item" id="submit-sensei-links" />
					<span class="spinner"></span>
				</span>
			</p>
		</div><!-- .senseidiv -->
		<?php
	}

	/**
	 * Adding admin notice if the current
	 * installed theme is not compatible
	 *
	 * @return void
	 */
	public function theme_compatibility_notices() {

		if ( isset( $_GET['sensei_hide_notice'] ) ) {
			switch ( esc_attr( $_GET['sensei_hide_notice'] ) ) {
				case 'menu_settings':
					add_user_meta( get_current_user_id(), 'sensei_hide_menu_settings_notice', true );
					break;
			}
		}
	}

	/**
	 * Hooked onto admin_init. Listens for install_sensei_pages and skip_install_sensei_pages query args
	 * on the sensei settings page.
	 *
	 * @deprecated 3.1.0 use Sensei()->setup_wizard->pages->create_pages() instead
	 *
	 * @since 1.8.7
	 */
	public static function install_pages() {
		_deprecated_function( __METHOD__, '3.1.0', 'Sensei_Setup_Wizard_Pages::create_pages' );
	}

	/**
	 * Remove a course from course order option when trashed
	 *
	 * @since 1.9.8
	 * @param $new_status null|string
	 * @param $old_status null|string
	 * @param $post null|WP_Post
	 */
	public function remove_trashed_course_from_course_order( $new_status = null, $old_status = null, $post = null ) {
		if ( empty( $new_status ) || empty( $old_status ) || $new_status === $old_status ) {
			return;
		}

		if ( empty( $post ) || 'course' !== $post->post_type ) {
			return;
		}

		if ( 'trash' === $new_status ) {
			$order_string = $this->get_course_order();

			if ( ! empty( $order_string ) ) {
				$course_id          = $post->ID;
				$ordered_course_ids = array_map( 'intval', explode( ',', $order_string ) );
				$course_id_position = array_search( $course_id, $ordered_course_ids );
				if ( false !== $course_id_position ) {
					array_splice( $ordered_course_ids, $course_id_position, 1 );
					$this->save_course_order( implode( ',', $ordered_course_ids ) );
				}
			}
		}
	}

	public function notify_if_admin_email_not_real_admin_user() {
		$maybe_admin = get_user_by( 'email', get_bloginfo( 'admin_email' ) );

		if ( false === $maybe_admin || false === user_can( $maybe_admin, 'manage_options' ) ) {
			$general_settings_url         = '<a href="' . esc_url( admin_url( 'options-general.php' ) ) . '">' . esc_html__( 'Settings > General', 'sensei-lms' ) . '</a>';
			$add_new_user_url             = '<a href="' . esc_url( admin_url( 'user-new.php' ) ) . '">' . esc_html__( 'add a new Administrator', 'sensei-lms' ) . '</a>';
			$existing_administrators_link = '<a href="' . esc_url( admin_url( 'users.php?role=administrator' ) ) . '">' . esc_html__( 'existing Administrator', 'sensei-lms' ) . '</a>';
			$current_setting              = get_bloginfo( 'admin_email' );

			/*
			 * translators: The %s placeholders are as follows:
			 *
			 * - A link to the General Settings page with the translated text "Settings > General".
			 * - A link to add an admin user with the translated text "add a new Administrator".
			 * - The current admin email address from the Settings.
			 * - A link to view the existing admin users, with the translated text "existing Administrator".
			 */
			$warning = __( 'To prevent issues with Sensei LMS module names, your Email Address in %1$s should also belong to an Administrator user. You can either %2$s with the email address %3$s, or change that email address to match the email of an %4$s.', 'sensei-lms' );

			?>
			<div id="message" class="error sensei-message sensei-connect">
				<p>
					<strong>
						<?php printf( esc_html( $warning ), wp_kses_post( $general_settings_url ), wp_kses_post( $add_new_user_url ), esc_html( $current_setting ), wp_kses_post( $existing_administrators_link ) ); ?>
					</strong>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Adds a workaround for fixing an issue with CPT's in the block editor.
	 *
	 * See https://github.com/WordPress/gutenberg/pull/15375
	 *
	 * Once that PR makes its way into WP Core, this function (and its
	 * attachment to the action in `__construct`) can be removed.
	 *
	 * @access private
	 *
	 * @since 2.1.0
	 */
	public function output_cpt_block_editor_workaround() {
		$screen = get_current_screen();

		if ( ! ( method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor() ) ) {
			return;
		}

		?>
<script type="text/javascript">
	jQuery( document ).ready( function() {
		if ( wp.apiFetch ) {
			wp.apiFetch.use( function( options, next ) {
				let url = options.path || options.url;
				if ( 'POST' === options.method && wp.url.getQueryArg( url, 'meta-box-loader' ) ) {
					if ( options.body instanceof FormData && 'undefined' === options.body.get( 'post_author' ) ) {
						options.body.delete( 'post_author' );
					}
				}
				return next( options );
			} );
		}
	} );
</script>
		<?php
	}

	/**
	 * Attempt to log a Sensei event.
	 *
	 * @since 2.1.0
	 * @access private
	 */
	public function ajax_log_event() {
		// phpcs:disable WordPress.Security.NonceVerification
		if ( ! isset( $_REQUEST['event_name'] ) ) {
			wp_die();
		}

		$event_name = $_REQUEST['event_name'];
		$properties = isset( $_REQUEST['properties'] ) ? $_REQUEST['properties'] : [];

		if ( is_string( $properties ) ) {
			$properties = json_decode( stripslashes( $properties ), true );
		}

		// Set the source to js-event.
		add_filter(
			'sensei_event_logging_source',
			function() {
				return 'js-event';
			}
		);

		sensei_log_event( $event_name, $properties );
		// phpcs:enable WordPress.Security.NonceVerification
	}

	/**
	 * Set `window.sensei.pluginUrl` to be used from javascript.
	 *
	 * @since  4.5.0
	 * @access private
	 */
	public function sensei_set_plugin_url() {

		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$screens = [
			'course',
			'lesson',
			Sensei_Home::SCREEN_ID,
			'admin_page_' . Sensei_Setup_Wizard::instance()->page_slug,
		];

		if ( in_array( $screen->id, $screens, true ) ) {
			?>
			<script>
				window.sensei = window.sensei || {};
				window.sensei.pluginUrl = '<?php echo esc_url( Sensei()->plugin_url ); ?>';
			</script>
			<?php
		}
	}

}

/**
 * Legacy Class WooThemes_Sensei_Admin
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 * @ignore
 */
class WooThemes_Sensei_Admin extends Sensei_Admin{ }
