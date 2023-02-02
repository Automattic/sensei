<?php
/**
 * Learner Management
 *
 * Handles adding or removing learners from a course/lesson, resetting progress in a course/lesson,
 * and editing the start date of a course/lesson.
 *
 * @package Sensei\Learner\Management
 * @since 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Learners Class
 *
 * All functionality pertaining to the Admin Learners in Sensei.
 *
 * @package Users
 * @author Automattic
 *
 * @since 1.3.0
 */
class Sensei_Learner_Management {
	/**
	 * Name of the menu/page.
	 *
	 * @var string $name
	 */
	public $name;
	/**
	 * Main plugin file name.
	 *
	 * @var string $file
	 */
	public $file;
	/**
	 * Menu slug name.
	 *
	 * @var string $page_slug
	 */
	public $page_slug;
	/**
	 * Reference to the class responsible for Bulk Learner Actions.
	 *
	 * @var Sensei_Learners_Admin_Bulk_Actions_Controller $bulk_actions_controller
	 */
	public $bulk_actions_controller;
	/**
	 * Per page screen option ID.
	 *
	 * @var string SENSEI_LEARNER_MANAGEMENT_PER_PAGE
	 */
	const SENSEI_LEARNER_MANAGEMENT_PER_PAGE = 'sensei_learner_management_per_page';

	/**
	 * Constructor
	 *
	 * @since  1.6.0
	 *
	 * @param string $file Main plugin file name.
	 */
	public function __construct( $file ) {
		$this->name      = __( 'Students', 'sensei-lms' );
		$this->file      = $file;
		$this->page_slug = 'sensei_learners';

		$this->bulk_actions_controller = new Sensei_Learners_Admin_Bulk_Actions_Controller( $this, Sensei_Learner::instance() );

		// Admin functions.
		if ( is_admin() ) {
			add_filter( 'set-screen-option', array( $this, 'set_learner_management_screen_option' ), 20, 3 );

			add_action( 'learners_wrapper_container', array( $this, 'wrapper_container' ) );

			if ( isset( $_GET['page'] ) && ( ( $this->page_slug === $_GET['page'] ) ) ) {
				add_action( 'admin_print_scripts', array( $this, 'enqueue_scripts' ) );
				add_action( 'admin_print_styles', array( $this, 'enqueue_styles' ) );
			}

			add_action( 'admin_init', array( $this, 'add_new_learners' ) );
			add_action( 'admin_init', array( $this, 'handle_learner_actions' ) );

			add_action( 'admin_notices', array( $this, 'add_learner_notices' ) );
		}

		// Ajax functions.
		if ( is_admin() ) {
			add_action( 'wp_ajax_get_redirect_url_learners', array( $this, 'get_redirect_url' ) );
			add_action( 'wp_ajax_edit_date_started', array( $this, 'edit_date_started' ) );
			add_action( 'wp_ajax_remove_user_from_post', array( $this, 'remove_user_from_post' ) );
			add_action( 'wp_ajax_reset_user_post', array( $this, 'reset_user_post' ) );
			add_action( 'wp_ajax_sensei_json_search_users', array( $this, 'json_search_users' ) );

			// Add custom navigation.
			add_action( 'in_admin_header', [ $this, 'add_custom_navigation' ] );
		}
	}


	/**
	 * Add custom navigation to the admin pages.
	 *
	 * @since 4.4.0
	 * @access private
	 */
	public function add_custom_navigation() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		if ( in_array( $screen->id, [ 'sensei-lms_page_sensei_learners' ], true ) && ( 'term' !== $screen->base ) ) {
			$this->display_students_navigation( $screen );
		}
	}

	/**
	 * Display the Students' page navigation.
	 *
	 * @param WP_Screen $screen WordPress current screen object.
	 */
	private function display_students_navigation( WP_Screen $screen ) {
		?>
		<div id="sensei-custom-navigation" class="sensei-custom-navigation">
			<div class="sensei-custom-navigation__heading-with-info">
				<div class="sensei-custom-navigation__title">
					<h1><?php esc_html_e( 'Students', 'sensei-lms' ); ?></h1>
				</div>
				<div class="sensei-custom-navigation__separator"></div>
				<a class="sensei-custom-navigation__info" target="_blank" href="https://senseilms.com/documentation/student-management?utm_source=plugin_sensei&utm_medium=docs&utm_campaign=student-management">
					<?php echo esc_html__( 'Guide To Student Management', 'sensei-lms' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Add learner management menu.
	 *
	 * @since  1.6.0
	 * @access public
	 */
	public function learners_admin_menu() {
		if ( current_user_can( 'manage_sensei_grades' ) ) {
			$learners_page = add_submenu_page(
				'sensei',
				$this->name,
				$this->name,
				'manage_sensei_grades',
				$this->page_slug,
				array( $this, 'learners_page' )
			);

			add_action( "load-$learners_page", array( $this, 'load_screen_options_when_on_bulk_actions' ) );
		}
	}

	/**
	 * Sets the pagination screen option value for the Bulk Learner Actions table.
	 *
	 * @param bool   $status Status.
	 * @param string $option Screen option ID.
	 * @param string $value  Learners per page.
	 * @return bool|string
	 */
	public function set_learner_management_screen_option( $status, $option, $value ) {
		if ( self::SENSEI_LEARNER_MANAGEMENT_PER_PAGE === $option ) {
			return $value;
		}
		return $status;
	}

	/**
	 * Adds a "Learners per page" screen option to the Bulk Learner Actions page.
	 */
	public function load_screen_options_when_on_bulk_actions() {
		if ( isset( $this->bulk_actions_controller ) && $this->bulk_actions_controller->is_current_page() ) {

			$args = array(
				'label'   => __( 'Students per page', 'sensei-lms' ),
				'default' => 20,
				'option'  => self::SENSEI_LEARNER_MANAGEMENT_PER_PAGE,
			);
			add_screen_option( 'per_page', $args );
		}
	}

	/**
	 * Enqueues scripts.
	 *
	 * @description Load in JavaScripts where necessary.
	 * @access public
	 * @since 1.6.0
	 */
	public function enqueue_scripts() {

		// Load Learners JS.

		Sensei()->assets->enqueue(
			'sensei-learners-general',
			'js/learners-general.js',
			[ 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker', 'jquery-ui-tooltip', 'sensei-core-select2' ],
			true
		);

		Sensei()->assets->enqueue( 'sensei-stop-double-submission', 'js/stop-double-submission.js', [], true );
		Sensei()->assets->enqueue( 'sensei-student-action-menu', 'admin/students/student-action-menu/index.js', [], true );
		Sensei()->assets->enqueue( 'sensei-student-bulk-action-button', 'admin/students/student-bulk-action-button/index.js', [], true );
		Sensei()->assets->enqueue(
			'sensei-student-bulk-action-button-style',
			'admin/students/student-bulk-action-button/student-bulk-action-button.css',
			[ 'sensei-wp-components', 'sensei-editor-components-style' ]
		);

		wp_localize_script(
			'sensei-learners-general',
			'slgL10n',
			array(
				'inprogress' => __( 'In Progress', 'sensei-lms' ),
			)
		);

		$data = array(
			'remove_generic_confirm'     => __( 'Are you sure you want to remove this student?', 'sensei-lms' ),
			'remove_from_lesson_confirm' => __( 'Are you sure you want to remove the student from this lesson?', 'sensei-lms' ),
			'remove_from_course_confirm' => __( 'Are you sure you want to remove this student\'s enrollment in the course?', 'sensei-lms' ),
			'enrol_in_course_confirm'    => __( 'Are you sure you want to enroll the student in this course?', 'sensei-lms' ),
			'restore_enrollment_confirm' => __( 'Are you sure you want to restore the student enrollment in this course?', 'sensei-lms' ),
			'reset_lesson_confirm'       => __( 'Are you sure you want to reset the progress of this student for this lesson?', 'sensei-lms' ),
			'reset_course_confirm'       => __( 'Are you sure you want to reset the progress of this student for this course?', 'sensei-lms' ),
			'remove_progress_confirm'    => __( 'Are you sure you want to remove the progress of this student for this course?', 'sensei-lms' ),
			'modify_user_post_nonce'     => wp_create_nonce( 'modify_user_post_nonce' ),
			'search_users_nonce'         => wp_create_nonce( 'search-users' ),
			'edit_date_nonce'            => wp_create_nonce( 'edit_date_nonce' ),
			'course_category_nonce'      => wp_create_nonce( 'course_category_nonce' ),
			'selectplaceholder'          => __( 'Select students to manually enroll...', 'sensei-lms' ),
		);

		wp_localize_script( 'sensei-learners-general', 'woo_learners_general_data', $data );
	}

	/**
	 * Enqueue styles.
	 *
	 * @description Load in CSS styles where necessary.
	 * @access public
	 * @since 1.6.0
	 */
	public function enqueue_styles() {
		Sensei()->assets->enqueue( 'sensei-jquery-ui', 'css/jquery-ui.css' );
		Sensei()->assets->enqueue(
			'sensei-student-modal-style',
			'admin/students/student-modal/student-modal.css',
			[ 'sensei-wp-components', 'sensei-editor-components-style' ]
		);
	}

	/**
	 * Loads dependent files.
	 *
	 * @since  1.6.0
	 */
	public function load_data_table_files() {

		// Load Learners Classes.
		$classes_to_load = array(
			'list-table',
			'learners-main',
		);
		foreach ( $classes_to_load as $class_file ) {
			Sensei()->load_class( $class_file );
		}

	}

	/**
	 * Creates new instance of class.
	 *
	 * @since  1.6.0
	 *
	 * @deprecated 3.0.0
	 *
	 * @param  string    $name          Name of class.
	 * @param  integer   $data          constructor arguments.
	 * @param  undefined $optional_data optional constructor arguments.
	 * @return object                 class instance object
	 */
	public function load_data_object( $name = '', $data = 0, $optional_data = null ) {

		_deprecated_function( __METHOD__, '3.0.0', 'new Sensei_Learners_$name' );

		// Load Analysis data.
		$object_name = 'Sensei_Learners_' . $name;
		if ( is_null( $optional_data ) ) {
			$sensei_learners_object = new $object_name( $data );
		} else {
			$sensei_learners_object = new $object_name( $data, $optional_data );
		}
		if ( 'Main' === $name ) {
			$sensei_learners_object->prepare_items();
		}
		return $sensei_learners_object;
	}

	/**
	 * Outputs the content for the Learner Management page.
	 *
	 * @since 1.6.0
	 * @access public
	 */
	public function learners_page() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for comparison.
		if ( ! empty( $_GET['course_id'] ) ) {
			require __DIR__ . '/views/html-admin-page-students-course.php';
		} else {
			require __DIR__ . '/views/html-admin-page-students-main.php';
		}

	}

	/**
	 * Outputs the breadcrumb.
	 *
	 * @since  1.6.0
	 * @param array $args Partial function names.
	 */
	public function learners_headers( $args = array( 'nav' => 'default' ) ) {

		$function = 'learners_' . $args['nav'] . '_nav';
		$this->$function();
		do_action( 'sensei_learners_after_headers' );

	}

	/**
	 * Wrapper for Learners area.
	 *
	 * @since  1.6.0
	 * @param string $which Wrapper location. Valid values are 'top' and 'bottom'.
	 */
	public function wrapper_container( $which ) {
		if ( 'top' === $which ) {
			?>
			<div id="woothemes-sensei" class="wrap woothemes-sensei">
			<?php
		} elseif ( 'bottom' === $which ) {
			?>
			</div><!--/#woothemes-sensei-->
			<?php
		}
	}

	/**
	 * Default nav area for Learners.
	 *
	 * @since  1.6.0
	 */
	public function learners_default_nav() {
		$course_id = (int) sanitize_text_field( wp_unslash( $_GET['course_id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$lesson_id = (int) sanitize_text_field( wp_unslash( $_GET['lesson_id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 0 < $course_id && 0 < $lesson_id ) {
			$back_url = add_query_arg(
				array(
					'page'      => $this->page_slug,
					'course_id' => $course_id,
					'view'      => 'lessons',
				),
				admin_url( 'admin.php' )
			);
		} else {
			$back_url = add_query_arg(
				[
					'page' => 'sensei_learners',
				],
				admin_url( 'admin.php' )
			);
		}

		$title_parts = [];
		if ( 0 < $course_id ) {
			$title_parts[] = get_the_title( $course_id );
		}
		if ( 0 < $lesson_id ) {
			$title_parts[] = get_the_title( $lesson_id );
		}
		$back_link = '<a href="' . esc_url( $back_url ) . '">←</a> ';
		$title     = $back_link . implode( ': ', $title_parts );
		?>
			<h2 class="sensei-students__subheading">
				<?php
				echo wp_kses(
					apply_filters( 'sensei_learners_nav_title', $title ),
					array(
						'span' => array(
							'class' => array(),
						),
						'a'    => array(
							'href' => array(),
						),
					)
				);
				?>
			</h2>
		<?php
	}

	/**
	 * Filters table by course category.
	 */
	public function get_redirect_url() {
		check_ajax_referer( 'course_category_nonce', 'security' );

		// Parse POST data.
		$data        = $_POST['data'];
		$course_data = array();
		parse_str( $data, $course_data );

		$course_cat = intval( $course_data['course_cat'] );

		$redirect_url = apply_filters(
			'sensei_ajax_redirect_url',
			add_query_arg(
				array(
					'page'       => $this->page_slug,
					'course_cat' => $course_cat,
				),
				admin_url( 'admin.php' )
			)
		);

		echo esc_url_raw( $redirect_url );
		die();
	}

	/**
	 * Edits the course/lesson start date.
	 */
	public function edit_date_started() {
		check_ajax_referer( 'edit_date_nonce', 'security' );

		if ( ! empty( $_POST['data']['post_id'] ) && is_numeric( $_POST['data']['post_id'] ) ) {
			$post_id = (int) sanitize_key( $_POST['data']['post_id'] );
		} else {
			exit( '' );
		}

		$post = get_post( $post_id );

		if ( empty( $post ) || ! is_a( $post, 'WP_Post' ) ) {
			exit( '' );
		}

		if ( ! empty( $_POST['data']['comment_id'] ) && is_numeric( $_POST['data']['comment_id'] ) ) {
			$comment_id = (int) sanitize_key( $_POST['data']['comment_id'] );
		} else {
			exit( '' );
		}

		$comment = get_comment( $comment_id );

		if ( empty( $comment ) ) {
			exit( '' );
		}

		// validate we can edit date.
		$may_edit_date = false;

		if ( current_user_can( 'manage_sensei' ) || get_current_user_id() === (int) $post->post_author ) {
			$may_edit_date = true;
		}

		if ( ! $may_edit_date ) {
			exit( '' );
		}

		if ( ! empty( $_POST['data']['new_dates']['start-date'] ) ) {
			$date_string = sanitize_text_field( wp_unslash( $_POST['data']['new_dates']['start-date'] ) );
		} else {
			exit( '' );
		}

		if ( empty( $date_string ) ) {
			exit( '' );
		}

		$date_started = get_comment_meta( $comment_id, 'start', true );

		$expected_date_format = 'Y-m-d H:i:s';
		if ( false === strpos( $date_string, ' ' ) ) {
			$expected_date_format = 'Y-m-d';
		}

		$date = DateTimeImmutable::createFromFormat( $expected_date_format, $date_string );
		if ( false === $date ) {
			exit( '' );
		}
		$mysql_date = date( 'Y-m-d H:i:s', $date->getTimestamp() );
		if ( false === $mysql_date ) {
			exit( '' );
		}

		$updated = (bool) update_comment_meta( $comment_id, 'start', $mysql_date, $date_started );

		/**
		 * Filter sensei_learners_learner_updated
		 *
		 * This filter should return false if there was no update in the learner row.
		 *
		 * @param {bool} $updated    A flag indicating if there was an update in the learner row.
		 * @param {int}  $post_id    Lesson or course id.
		 * @param {int}  $comment_id The comment id which tracks the progress of the learner.
		 *
		 * @return {bool} False if there were no updates.
		 */
		$updated = apply_filters( 'sensei_learners_learner_updated', $updated, $post_id, $comment_id );

		if ( false === $updated ) {
			exit( '' );
		}

		exit( esc_html( $mysql_date ) );
	}

	/**
	 * Handles actions that are performed asynchronously from learner management.
	 *
	 * @param string $action Action to perform. Currently handled values are 'reset'.
	 */
	public function handle_user_async_action( $action ) {
		check_ajax_referer( 'modify_user_post_nonce', 'security' );

		// Parse POST data.
		$data        = sanitize_text_field( $_POST['data'] );
		$action_data = array();
		parse_str( $data, $action_data );

		$post = get_post( intval( $action_data['post_id'] ) );

		if ( empty( $post ) ) {
			exit( '' );
		}

		// validate the user.
		$may_remove_user = false;

		// Only teachers and admins can remove users.
		if ( current_user_can( 'manage_sensei' ) || get_current_user_id() === intval( $post->post_author ) ) {
			$may_remove_user = true;
		}

		if ( ! is_a( $post, 'WP_Post' ) || ! $may_remove_user ) {
			exit( '' );
		}

		if ( $action_data['user_id'] && $action_data['post_id'] && $action_data['post_type'] ) {
			$user_id   = intval( $action_data['user_id'] );
			$post_id   = intval( $action_data['post_id'] );
			$post_type = sanitize_text_field( $action_data['post_type'] );

			$user = get_userdata( $user_id );
			if ( false === $user ) {
				exit( '' );
			}

			$altered = true;

			switch ( $post_type ) {
				case 'course':
					$altered = Sensei_Utils::reset_course_for_user( $post_id, $user_id );
					break;
				case 'lesson':
					switch ( $action ) {
						case 'reset':
							$altered = Sensei()->quiz->reset_user_lesson_data( $post_id, $user_id );
							break;
						case 'remove':
							$altered = Sensei_Utils::sensei_remove_user_from_lesson( $post_id, $user_id );
							break;
					}
					break;
			}

			if ( $altered ) {
				if ( 'course' === $post_type && ! Sensei_Utils::has_started_course( $post_id, $user_id ) ) {
					exit( 'removed' );
				}

				if ( 'lesson' === $post_type && 'remove' === $action ) {
					exit( 'removed' );
				}

				exit( 'altered' );
			}
		}

		exit( '' );
	}


	/**
	 * Handles learner actions (manually enrolling or withdrawing a student from a course).
	 */
	public function handle_learner_actions() {
		if ( ! isset( $_GET['learner_action'] ) ) {
			return;
		}

		$redirect_url = remove_query_arg( [ 'learner_action', '_wpnonce', 'user_id' ] );

		// phpcs:ignore WordPress.Security.NonceVerification -- Nonce checked below.
		$learner_action = sanitize_text_field( wp_unslash( $_GET['learner_action'] ) );
		if ( ! in_array( $learner_action, [ 'enrol', 'restore_enrollment', 'withdraw' ], true ) ) {
			wp_safe_redirect( esc_url_raw( $redirect_url ) );
			exit;
		}

		$failed_redirect_url = add_query_arg( [ 'message' => 'error_' . $learner_action ], $redirect_url );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Don't touch the nonce.
		if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'sensei-learner-action-' . $learner_action ) ) {
			wp_safe_redirect( esc_url_raw( $failed_redirect_url ) );
			exit;
		}

		if ( empty( $_GET['course_id'] ) || empty( $_GET['user_id'] ) ) {
			wp_safe_redirect( esc_url_raw( $failed_redirect_url ) );
			exit;
		}

		$user_id   = intval( $_GET['user_id'] );
		$course_id = intval( $_GET['course_id'] );
		$post      = get_post( $course_id );

		$may_manage_enrolment = false;

		// Only teachers and admins can enrol and withdraw users.
		if ( current_user_can( 'manage_sensei' ) || get_current_user_id() === intval( $post->post_author ) ) {
			$may_manage_enrolment = true;
		}

		if ( ! is_a( $post, 'WP_Post' ) || ! $may_manage_enrolment ) {
			wp_safe_redirect( esc_url_raw( $failed_redirect_url ) );
			exit;
		}

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$result           = false;

		if ( 'withdraw' === $learner_action ) {
			$result = $course_enrolment->withdraw( $user_id );
		} elseif ( in_array( $learner_action, [ 'enrol', 'restore_enrollment' ], true ) ) {
			$result = $course_enrolment->enrol( $user_id );
		}

		if ( ! $result ) {
			wp_safe_redirect( esc_url_raw( $failed_redirect_url ) );
			exit;
		} else {
			$success_redirect_url = add_query_arg( [ 'message' => 'success_' . $learner_action ], $redirect_url );
			wp_safe_redirect( esc_url_raw( $success_redirect_url ) );
			exit;
		}
	}

	/**
	 * Resets Learner progress for a course/lesson.
	 */
	public function reset_user_post() {
		$this->handle_user_async_action( 'reset' );
	}

	/**
	 * Removes a Learner from a course/lesson.
	 */
	public function remove_user_from_post() {
		$this->handle_user_async_action( 'remove' );
	}

	/**
	 * Searches for a Learner by name or username.
	 */
	public function json_search_users() {

		check_ajax_referer( 'search-users', 'security' );

		$term = sanitize_text_field( stripslashes( $_GET['term'] ) );

		if ( empty( $term ) ) {
			die();
		}

		$default = isset( $_GET['default'] ) ? $_GET['default'] : __( 'None', 'sensei-lms' );

		$found_users = array( '' => $default );

		$users_query = new WP_User_Query(
			apply_filters(
				'sensei_json_search_users_query',
				array(
					'fields'         => 'all',
					'orderby'        => 'display_name',
					'search'         => '*' . $term . '*',
					'search_columns' => array( 'ID', 'user_login', 'user_email', 'user_nicename', 'user_firstname', 'user_lastname' ),
				),
				$term
			)
		);

		$users = $users_query->get_results();

		if ( $users ) {
			foreach ( $users as $user ) {
				$full_name = Sensei_Learner::get_full_name( $user->ID );

				if ( trim( $user->display_name ) === trim( $full_name ) ) {

					$name = $full_name;

				} else {

					$name = $full_name . ' [' . $user->display_name . ']';

				}

				$found_users[ $user->ID ] = $name . ' (#' . $user->ID . ' - ' . sanitize_email( $user->user_email ) . ')';
			}
		}

		wp_send_json( $found_users );
	}

	/**
	 * Adds a Learner to a course/lesson.
	 *
	 * @return bool false if the Learner was not added.
	 */
	public function add_new_learners() {

		$result = false;

		if ( ! isset( $_POST['add_learner_submit'] ) ) {
			return $result;
		}

		if ( ! isset( $_POST['add_learner_nonce'] ) || ! wp_verify_nonce( $_POST['add_learner_nonce'], 'add_learner_to_sensei' ) ) {
			return $result;
		}

		if ( ( ! isset( $_POST['add_user_id'] ) || '' === $_POST['add_user_id'] ) || ! isset( $_POST['add_post_type'] ) || ! isset( $_POST['add_course_id'] ) || ! isset( $_POST['add_lesson_id'] ) ) {
			return $result;
		}

		$post_type = $_POST['add_post_type'];
		$user_ids  = array_map( 'intval', $_POST['add_user_id'] );
		$course_id = absint( $_POST['add_course_id'] );
		$lesson_id = isset( $_POST['add_lesson_id'] ) ? $_POST['add_lesson_id'] : '';
		$results   = [];

		$course = get_post( $course_id );
		if (
			! $course
			|| (
				! current_user_can( 'manage_sensei' )
				&& get_current_user_id() !== intval( $course->post_author )
			)
		) {
			return $result;
		}

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		foreach ( $user_ids as $user_id ) {
			$result = $course_enrolment->enrol( $user_id );

			switch ( $post_type ) {
				case 'course':
					// Complete each lesson if course is set to be completed.
					if (
						$result
						&& isset( $_POST['add_complete_course'] )
						&& 'yes' === $_POST['add_complete_course']
						&& ! Sensei_Utils::user_completed_course( $course_id, $user_id )
					) {
						Sensei_Utils::force_complete_user_course( $user_id, $course_id );
					}

					break;

				case 'lesson':
					$complete = false;
					if ( $result && isset( $_POST['add_complete_lesson'] ) && 'yes' === $_POST['add_complete_lesson'] ) {
						$complete = true;
					}

					$result = $result && Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id, $complete );

					break;
			}

			$results[] = $result;
		}

		// Set redirect URL after adding user to course/lesson.
		$query_args = array(
			'page' => $this->page_slug,
			'view' => 'learners',
		);

		if ( $course_id ) {
			$query_args['course_id'] = $course_id;
		}

		if ( $lesson_id ) {
			$query_args['lesson_id'] = $lesson_id;
		}

		if ( ! empty( $results ) && ! in_array( false, $results, true ) ) {
			$query_args['message'] = 'success_enrol';
		} else {
			$query_args['message'] = 'error_enrol';
		}

		if ( count( $user_ids ) > 1 ) {
			$query_args['message'] .= '_multiple';
		}

		$redirect_url = apply_filters( 'sensei_learners_add_learner_redirect_url', add_query_arg( $query_args, admin_url( 'admin.php' ) ) );

		wp_safe_redirect( esc_url_raw( $redirect_url ) );
		exit;
	}

	/**
	 * Displays a notice to indicate whether or not the Learner(s) was added successfully.
	 */
	public function add_learner_notices() {
		if ( isset( $_GET['page'] ) && $this->page_slug === $_GET['page'] && isset( $_GET['message'] ) && $_GET['message'] ) {
			$message = sanitize_text_field( wp_unslash( $_GET['message'] ) );
			$notice  = false;

			switch ( $message ) {
				case 'error':
				case 'error_enrol':
					$notice = [
						'error',
						__( 'An error occurred while enrolling the student.', 'sensei-lms' ),
					];
					break;
				case 'error_restore_enrollment':
					$notice = [
						'error',
						__( 'An error occurred while restoring student enrollment.', 'sensei-lms' ),
					];
					break;
				case 'error_enrol_multiple':
					$notice = [
						'error',
						__( 'An error occurred while enrolling the students.', 'sensei-lms' ),
					];
					break;
				case 'error_withdraw':
					$notice = [
						'error',
						__( 'An error occurred removing the student\'s enrollment.', 'sensei-lms' ),
					];
					break;
				case 'success_withdraw':
					$notice = [
						'updated',
						__( 'Student\'s enrollment has been removed.', 'sensei-lms' ),
					];
					break;
				case 'success_enrol':
					$notice = [
						'updated',
						__( 'Student has been enrolled.', 'sensei-lms' ),
					];
					break;
				case 'success_restore_enrollment':
					$notice = [
						'updated',
						__( 'Student enrollment has been restored.', 'sensei-lms' ),
					];
					break;
				case 'success_bulk':
				case 'success_enrol_multiple':
					$notice = [
						'updated',
						__( 'Student have been enrolled.', 'sensei-lms' ),
					];
					break;
			}
			if ( $notice ) {
				?>
				<div class="learners-notice <?php echo esc_attr( $notice[0] ); ?>">
					<p><?php echo esc_html( $notice[1] ); ?></p>
				</div>
				<?php
			}
		}
	}

	/**
	 * Rebuilds and appends query variables to the URL.
	 *
	 * @return string URL query string.
	 */
	public function get_url() {
		return add_query_arg( array( 'page' => $this->page_slug ), admin_url( 'admin.php' ) );
	}

	/**
	 * Gets the name of the menu/page.
	 *
	 * @return string Name of the menu/page.
	 */
	public function get_name() {
		return $this->name;
	}

}

/**
 * Class WooThemes_Sensei_Learners
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Learners extends Sensei_Learner_Management{}
