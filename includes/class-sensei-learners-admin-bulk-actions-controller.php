<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


class Sensei_Learners_Admin_Bulk_Actions_Controller {

	const NONCE_SENSEI_BULK_LEARNER_ACTIONS       = 'sensei-bulk-learner-actions';
	const SENSEI_BULK_LEARNER_ACTIONS_NONCE_FIELD = '_sensei_bulk_learner_actions_field';
	const MANUALLY_ENROL                          = 'manually_enrol';
	const REMOVE_MANUAL_ENROLMENT                 = 'remove_manual_enrolment';
	const REMOVE_PROGRESS                         = 'remove_progress';
	const COMPLETE_COURSE                         = 'complete_course';
	const RECALCULATE_COURSE_COMPLETION           = 'recalculate_course_completion';

	/**
	 * @var array|null we only do these actions
	 */
	private $known_bulk_actions;
	private $page_slug;
	private $view;
	private $name;
	private $learner_management;

	/**
	 * @return string|void
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function get_page_slug() {
		return $this->page_slug;
	}

	/**
	 * Sensei_Learners_Admin_Main constructor.
	 *
	 * @param $management Sensei_Learner_Management
	 */
	public function __construct( $management ) {
		$this->name      = __( 'Bulk Learner Actions', 'sensei-lms' );
		$this->page_slug = $management->page_slug;
		$this->view      = 'sensei_learner_admin';
		$this->learner_management = $management;

		$this->known_bulk_actions = [
			self::MANUALLY_ENROL                => __( 'Manually enroll', 'sensei-lms' ),
			self::REMOVE_MANUAL_ENROLMENT       => __( 'Remove manual enrollment', 'sensei-lms' ),
			self::REMOVE_PROGRESS               => __( 'Remove progress', 'sensei-lms' ),
			self::COMPLETE_COURSE               => __( 'Recalculate Course(s) Completion (notify on complete)', 'sensei-lms' ),
			self::RECALCULATE_COURSE_COMPLETION => __( 'Recalculate Course(s) Completion (do not notify on complete)', 'sensei-lms' ),
		];

		if ( is_admin() ) {
			$this->hook();
		}
	}

	private function redirect_to_learner_admin_index( $result ) {
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

	private function get_page_url_parts() {
		return array(
			'page' => $this->get_page_slug(),
			'view' => $this->get_view(),
		);
	}

	public function get_url() {
		return $this->build_admin_url( $this->get_page_url_parts() );
	}

	private function build_admin_url( $args_array ) {
		return add_query_arg( $args_array, admin_url( 'admin.php' ) );
	}

	public function get_learner_management_course_url( $course_id ) {
		return $this->build_admin_url(
			array(
				'page'      => 'sensei_learners',
				'course_id' => absint( $course_id ),
				'view'      => 'learners',
			)
		);
	}

	public function get_known_bulk_actions() {
		return (array) apply_filters( 'sensei_learners_admin_get_known_bulk_actions', $this->known_bulk_actions );
	}

	public function handle_http_post() {
		if ( ! $this->is_current_page() ) {
			return;
		}

		if ( ! isset( $_POST['sensei_bulk_action'], $_POST['bulk_action_course_ids'], $_POST['bulk_action_user_ids'] ) ) {
			return;
		}

		check_admin_referer( self::NONCE_SENSEI_BULK_LEARNER_ACTIONS, self::SENSEI_BULK_LEARNER_ACTIONS_NONCE_FIELD );

		$sensei_bulk_action = sanitize_text_field( wp_unslash( $_POST['sensei_bulk_action'] ) );
		$course_ids         = explode( ',', sanitize_text_field( wp_unslash( $_POST['bulk_action_course_ids'] ) ) );
		$user_ids           = array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['bulk_action_user_ids'] ) ) ) );

		if ( ! array_key_exists( $sensei_bulk_action, $this->get_known_bulk_actions() ) ) {
			$this->redirect_to_learner_admin_index( 'error-invalid-action' );
		}

		foreach ( $course_ids as $course_id ) {
			// Validate courses before continuing.
			$course = get_post( absint( $course_id ) );
			if ( empty( $course ) ) {
				$this->redirect_to_learner_admin_index( 'error-invalid-course' );
			}
		}

		foreach ( $user_ids as $user_id ) {
			$user = new WP_User( $user_id );

			if ( $user->exists() ) {
				foreach ( $course_ids as $course_id ) {
					$this->do_user_action( $user_id, $course_id, $sensei_bulk_action );
				}
			}
		}

		$this->redirect_to_learner_admin_index( 'action-success' );
	}

	private function do_user_action( $user_id, $course_id, $action ) {
		$manual_enrolment_provider = Sensei_Course_Enrolment_Manager::instance()->get_manual_enrolment_provider();

		switch ( $action ) {
			case self::MANUALLY_ENROL:
				if ( ! $manual_enrolment_provider->is_enrolled( $user_id, $course_id ) ) {
					$manual_enrolment_provider->enrol_student( $user_id, $course_id );
				}
				break;
			case self::REMOVE_MANUAL_ENROLMENT:
				if ( $manual_enrolment_provider->is_enrolled( $user_id, $course_id ) ) {
					$manual_enrolment_provider->withdraw_student( $user_id, $course_id );
				}
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

	public function enqueue_scripts() {
		$is_debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;

		wp_enqueue_script(
			'sensei-admin-jquery-modal',
			'https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.js',
			[ 'jquery' ],
			Sensei()->version,
			true
		);

		wp_enqueue_style(
			'sensei-admin-jquery-modal-css',
			'https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.css',
			[],
			Sensei()->version
		);

		wp_enqueue_script(
			'sensei-learners-admin-bulk-actions-js',
			Sensei()->plugin_url . 'assets/js/learners-bulk-actions.js',
			[ 'jquery', 'sensei-core-select2', 'sensei-admin-jquery-modal' ],
			Sensei()->version,
			true
		);

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

	public function learner_admin_page() {
		// Load Learners data
		$sensei_learners_main_view = new Sensei_Learners_Admin_Bulk_Actions_View( $this, $this->learner_management );
		$sensei_learners_main_view->prepare_items();
		// Wrappers
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

	public function is_current_page() {
		return isset( $_GET['page'] ) && ( $_GET['page'] == $this->page_slug )
			&& isset( $_GET['view'] ) && ( $_GET['view'] == $this->view );
	}

	private function hook() {
		if ( $this->is_current_page() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 30 );
		}

		add_action( 'admin_init', array( $this, 'handle_http_post' ) );
		add_action( 'admin_notices', array( $this, 'add_notices' ) );
	}

	public function get_view() {
		return $this->view;
	}

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

