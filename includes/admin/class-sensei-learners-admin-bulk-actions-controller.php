<?php
/**
 * File containing the class Sensei_Learners_Admin_Bulk_Actions_Controller.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * This class handles the bulk learner actions in learner management.
 */
class Sensei_Learners_Admin_Bulk_Actions_Controller {

	const NONCE_SENSEI_BULK_LEARNER_ACTIONS       = 'sensei-bulk-learner-actions';
	const SENSEI_BULK_LEARNER_ACTIONS_NONCE_FIELD = '_sensei_bulk_learner_actions_field';
	const ENROL_RESTORE_ENROLMENT                 = 'enrol_restore_enrolment';
	const REMOVE_ENROLMENT                        = 'remove_enrolment';
	const REMOVE_PROGRESS                         = 'remove_progress';
	const COMPLETE_COURSE                         = 'complete_course';
	const RECALCULATE_COURSE_COMPLETION           = 'recalculate_course_completion';

	/**
	 * The available bulk actions.
	 *
	 * @var array|null
	 */
	private $known_bulk_actions;

	/**
	 * The page slug.
	 *
	 * @var string
	 */
	private $page_slug;

	/**
	 * The page view.
	 *
	 * @var string
	 */
	private $view;

	/**
	 * The Sensei_Learner_Management object.
	 *
	 * @var Sensei_Learner_Management
	 */
	private $learner_management;

	/**
	 * The name of the page
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Get the name of the page.
	 *
	 * @return string|void
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Get the page slug.
	 *
	 * @return string
	 */
	public function get_page_slug() {
		return $this->page_slug;
	}

	/**
	 * This method returns an empty array and it exists only for backwards compatibility.
	 *
	 * @deprecated 3.0.0
	 * @return array Empty array
	 */
	public function get_query_args() {

		_deprecated_function( __METHOD__, '3.0.0' );

		return [];
	}

	/**
	 * This method adds a 'Learner Admin' submenu page in Sensei. If any content is generated for this page elsewhere,
	 * you should move this call beside it.
	 *
	 * @deprecated 3.0.0
	 */
	public function learners_admin_menu() {

		_deprecated_function( __METHOD__, '3.0.0' );

		if ( current_user_can( 'manage_sensei_grades' ) ) {
			add_submenu_page( 'sensei', __( 'Learner Admin', 'sensei-lms' ), __( 'Learner Admin', 'sensei-lms' ), 'manage_sensei_grades', 'sensei_learner_admin', array( $this, 'learner_admin_page' ) );
		}
	}

	/**
	 * Sensei_Learners_Admin_Main constructor.
	 *
	 * @param Sensei_Learner_Management $management The learner managemnt object.
	 */
	public function __construct( $management ) {
		$this->name               = __( 'Bulk Learner Actions', 'sensei-lms' );
		$this->page_slug          = $management->page_slug;
		$this->view               = 'sensei_learner_admin';
		$this->learner_management = $management;

		$this->known_bulk_actions = [
			self::ENROL_RESTORE_ENROLMENT       => __( 'Enroll / Restore Enrollment', 'sensei-lms' ),
			self::REMOVE_ENROLMENT              => __( 'Remove Enrollment', 'sensei-lms' ),
			self::REMOVE_PROGRESS               => __( 'Reset or Remove Progress', 'sensei-lms' ),
			self::COMPLETE_COURSE               => __( 'Recalculate Course(s) Completion (notify on complete)', 'sensei-lms' ),
			self::RECALCULATE_COURSE_COMPLETION => __( 'Recalculate Course(s) Completion (do not notify on complete)', 'sensei-lms' ),
		];

		if ( is_admin() ) {
			$this->register_hooks();
		}
	}

	/**
	 * Redirects to the bulk learner management screen and displays a message.
	 *
	 * @param string $result The result code or an error message to be displayed.
	 *
	 * @access private
	 */
	public function redirect_to_learner_admin_index( $result ) {
		$url = add_query_arg(
			array(
				'page'    => $this->get_page_slug(),
				'view'    => $this->get_view(),
				'message' => $result,
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Get the url of the bulk learner management screen.
	 *
	 * @return string
	 */
	public function get_url() {
		return add_query_arg(
			[
				'page' => $this->get_page_slug(),
				'view' => $this->get_view(),
			],
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Get the url of the learner management screen of a course.
	 *
	 * @param integer $course_id The course id.
	 * @return string
	 */
	public function get_learner_management_course_url( $course_id ) {
		return add_query_arg(
			[
				'page'      => 'sensei_learners',
				'course_id' => absint( $course_id ),
				'view'      => 'learners',
			],
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Get the supported bulk actions.
	 *
	 * @return array
	 */
	public function get_known_bulk_actions() {
		$known_bulk_actions = $this->known_bulk_actions;

		return (array) apply_filters( 'sensei_learners_admin_get_known_bulk_actions', $known_bulk_actions );
	}

	/**
	 * Handles the bulk action POST request. Required arguments are:
	 *
	 * 'sensei_bulk_action'         The action to perform.
	 * 'bulk_action_user_ids'       The users to perform the action on.
	 * 'bulk_action_course_ids and' The courses which the action is aimed on.
	 *
	 * @access private
	 */
	public function handle_http_post() {
		if ( ! $this->is_current_page() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification -- Nonce checked in check_nonce below.
		if ( ! isset( $_POST['sensei_bulk_action'], $_POST['bulk_action_course_ids'], $_POST['bulk_action_user_ids'] ) ) {
			return;
		}

		$this->check_nonce();

		// phpcs:ignore WordPress.Security.NonceVerification -- Nonce checked in check_nonce
		$sensei_bulk_action = sanitize_text_field( wp_unslash( $_POST['sensei_bulk_action'] ) );
		$course_ids         = explode( ',', sanitize_text_field( wp_unslash( $_POST['bulk_action_course_ids'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$user_ids           = array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['bulk_action_user_ids'] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! array_key_exists( $sensei_bulk_action, $this->get_known_bulk_actions() ) ) {
			$this->redirect_to_learner_admin_index( 'error-invalid-action' );
			return;
		}

		$edit_course_cap = get_post_type_object( 'course' )->cap->edit_post;
		foreach ( $course_ids as $course_id ) {
			// Validate courses before continuing.
			$course = get_post( absint( $course_id ) );
			if ( empty( $course ) || ! current_user_can( $edit_course_cap, $course_id ) ) {
				$this->redirect_to_learner_admin_index( 'error-invalid-course' );
				return;
			}
		}

		foreach ( $user_ids as $user_id ) {
			$user = new WP_User( $user_id );

			if ( $user->exists() ) {
				foreach ( $course_ids as $course_id ) {
					$this->do_user_action( $user_id, (int) $course_id, $sensei_bulk_action );
				}
			}
		}

		$this->redirect_to_learner_admin_index( 'action-success' );
	}

	/**
	 * Wraps global method to facilitate mocking.
	 *
	 * @access private
	 */
	public function check_nonce() {
		check_admin_referer( self::NONCE_SENSEI_BULK_LEARNER_ACTIONS, self::SENSEI_BULK_LEARNER_ACTIONS_NONCE_FIELD );
	}

	/**
	 * Helper method to perform the action on a user and course.
	 *
	 * @param integer $user_id   The user to perform the action on.
	 * @param integer $course_id The course which the action relates to.
	 * @param string  $action    The action.
	 */
	private function do_user_action( $user_id, $course_id, $action ) {
		switch ( $action ) {
			case self::ENROL_RESTORE_ENROLMENT:
				$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
				$course_enrolment->enrol( $user_id );
				break;
			case self::REMOVE_ENROLMENT:
				$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
				$course_enrolment->withdraw( $user_id );
				break;
			case self::REMOVE_PROGRESS:
				if ( Sensei_Utils::has_started_course( $course_id, $user_id ) ) {
					Sensei_Utils::reset_course_for_user( $course_id, $user_id );
				}
				break;
			case self::COMPLETE_COURSE:
				if ( Sensei_Utils::has_started_course( $course_id, $user_id ) && ! Sensei_Utils::user_completed_course( $course_id, $user_id ) ) {
					Sensei_Utils::user_complete_course( $course_id, $user_id );
				}
				break;
			case self::RECALCULATE_COURSE_COMPLETION:
				if ( Sensei_Utils::has_started_course( $course_id, $user_id ) && ! Sensei_Utils::user_completed_course( $course_id, $user_id ) ) {
					Sensei_Utils::user_complete_course( $course_id, $user_id, false );
				}
				break;
		}
	}

	/**
	 * Enqueues JS and CSS dependencies.
	 *
	 * @access private
	 */
	public function enqueue_scripts() {
		$is_debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;

		wp_enqueue_script( 'jquery-modal' );
		wp_enqueue_style( 'jquery-modal' );
		wp_enqueue_script( 'sensei-learners-admin-bulk-actions-js' );

		$data = array(
			'remove_generic_confirm'      => __( 'Are you sure you want to remove this user?', 'sensei-lms' ),
			'remove_from_lesson_confirm'  => __( 'Are you sure you want to remove the user from this lesson?', 'sensei-lms' ),
			'remove_from_course_confirm'  => __( 'Are you sure you want to remove the user from this course?', 'sensei-lms' ),
			'remove_user_from_post_nonce' => wp_create_nonce( 'remove_user_from_post_nonce' ),
			'bulk_add_learners_nonce'     => wp_create_nonce( self::NONCE_SENSEI_BULK_LEARNER_ACTIONS ),
			'select_course_placeholder'   => __( 'Select Course', 'sensei-lms' ),
			'is_debug'                    => $is_debug,
			'sensei_version'              => Sensei()->version,
		);

		wp_localize_script( 'sensei-learners-admin-bulk-actions-js', 'sensei_learners_bulk_data', $data );
	}

	/**
	 * Display the learner bulk action page.
	 */
	public function learner_admin_page() {
		// Load Learners data.
		$sensei_learners_main_view = new Sensei_Learners_Admin_Bulk_Actions_View( $this, $this->learner_management );
		$sensei_learners_main_view->prepare_items();

		// Wrappers.
		do_action( 'sensei_learner_admin_before_container' );
		?>
		<div id="woothemes-sensei" class="wrap woothemes-sensei">
		<?php
		do_action( 'sensei_learner_admin_wrapper_container', 'top' );
		$sensei_learners_main_view->output_headers();
		?>
		<div id="poststuff" class="sensei-learners-wrap">
			<div class="sensei-learners-main">
				<?php $sensei_learners_main_view->display(); ?>
			</div>
			<div class="sensei-learners-extra">
				<?php do_action( 'sensei_learner_admin_extra' ); ?>
			</div>
		</div>
		<?php
		do_action( 'sensei_learner_admin_wrapper_container', 'bottom' );
		?>
		</div>
		<?php
		do_action( 'sensei_learner_admin_after_container' );
	}

	/**
	 * Checks if this is the bulk management page.
	 */
	public function is_current_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for comparison.
		return isset( $_GET['page'], $_GET['view'] ) && ( $_GET['page'] === $this->page_slug ) && ( $_GET['view'] === $this->view );
	}

	/**
	 * Registers the class' hooks.
	 */
	private function register_hooks() {
		if ( $this->is_current_page() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 30 );
		}

		add_action( 'admin_init', array( $this, 'handle_http_post' ) );
		add_action( 'admin_notices', array( $this, 'add_notices' ) );
	}

	/**
	 * Get the page view string.
	 *
	 * @return string
	 */
	public function get_view() {
		return $this->view;
	}

	/**
	 * Adds a notice in the bulk management page. The notice message is retrieved from the message GET argument. The
	 * GET argument can be either the actual message or a specific code.
	 */
	public function add_notices() {
		if ( ! $this->is_current_page() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['message'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe use of message.
		$msg       = sanitize_text_field( wp_unslash( $_GET['message'] ) );
		$msg_class = 'notice notice-error';

		switch ( $msg ) {
			case 'error-invalid-action':
				$msg = __( 'This bulk action is not supported', 'sensei-lms' );
				break;
			case 'error-invalid-course':
				$msg = __( 'Invalid Course', 'sensei-lms' );
				break;
			case 'action-success':
				$msg_class = 'notice notice-success';
				$msg       = __( 'Bulk learner action succeeded', 'sensei-lms' );
				break;
		}

		?>
		<div class="learners-notice <?php echo esc_attr( $msg_class ); ?>">
			<p><?php echo esc_html( $msg ); ?></p>
		</div>
		<?php
	}

}
