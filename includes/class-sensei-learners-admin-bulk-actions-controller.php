<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly


class Sensei_Learners_Admin_Bulk_Actions_Controller {

    const NONCE_SENSEI_BULK_LEARNER_ACTIONS = 'sensei-bulk-learner-actions';
    const SENSEI_BULK_LEARNER_ACTIONS_NONCE_FIELD = '_sensei_bulk_learner_actions_field';
    const ADD_TO_COURSE = 'add_to_course';
    const REMOVE_FROM_COURSE = 'remove_from_course';
    const RESET_COURSE = 'reset_course';
    /**
     * @var array|null we only do these actions
     */
    private $known_bulk_actions = null;
    private $page_slug = 'sensei_learner_admin';
    private $view = 'sensei_learner_admin';
    private $name;
    private $query_args = array();

    /**
     * @return array
     */
    public function get_query_args()
    {
        return $this->query_args;
    }

    /**
     * @return string|void
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function get_page_slug()
    {
        return $this->page_slug;
    }

    /**
     * Sensei_Learners_Admin_Main constructor.
     * @param $analysis Sensei_Learner_Management
     */
    public function __construct( $analysis ) {
        $this->analysis = $analysis;
        $this->name =  __( 'Bulk Learner Actions', 'woothemes-sensei' );
        $this->file = $analysis->file;
        $this->page_slug = $this->analysis->page_slug;
        if ( is_admin() ) {
            $this->hook();
        }
    }

    private function redirect_to_learner_admin_index( $result ) {
        $url = add_query_arg( array(
            'page' => $this->get_page_slug(),
            'view' => $this->get_view(),
            'message' => $result,
        ), admin_url( 'admin.php' ));
        wp_safe_redirect( $url );
        exit;
    }

    private function get_page_url_parts() {
        return array(
            'page' => $this->get_page_slug(),
            'view' => $this->get_view()
        );
    }

    public function get_url() {
        return $this->build_admin_url( $this->get_page_url_parts() );
    }

    private function build_admin_url( $args_array ) {
        return add_query_arg( $args_array, admin_url( 'admin.php' ));
    }

    public function get_learner_management_course_url( $course_id ) {
        return $this->build_admin_url(array(
            'page' => 'sensei_learners',
            'course_id' => absint($course_id),
            'view' => 'learners')
        );
    }

    public function get_known_bulk_actions() {
        if ( null === $this->known_bulk_actions ) {
            $this->known_bulk_actions = array(
                self::ADD_TO_COURSE => __( 'Assign to Course(s)', 'woothemes-sensei' ),
                self::REMOVE_FROM_COURSE => __( 'Unassign from Course(s)', 'woothemes-sensei' ),
                self::RESET_COURSE => __( 'Reset Course(s)', 'woothemes-sensei' )
            );
        }
        return (array)apply_filters( 'sensei_learners_admin_get_known_bulk_actions', $this->known_bulk_actions );
    }

    public function handle_http_post() {
        if (!$this->is_current_page() ) {
            return;
        }

        if ( !isset( $_POST['sensei_bulk_action'] ) ) {
            return;
        }

        $sensei_bulk_action = $_POST['sensei_bulk_action'];

        if (!in_array( $sensei_bulk_action, array_keys( $this->get_known_bulk_actions() ) )) {
            $this->redirect_to_learner_admin_index( 'error-invalid-action' );
        }

        check_admin_referer( self::NONCE_SENSEI_BULK_LEARNER_ACTIONS, self::SENSEI_BULK_LEARNER_ACTIONS_NONCE_FIELD );

        $course_ids = isset( $_POST['bulk_action_course_ids'] ) ? explode(',', $_POST['bulk_action_course_ids'] ) : array();
        $user_ids = isset( $_POST['bulk_action_user_ids'] ) ? array_map('absint', explode(',', $_POST['bulk_action_user_ids'])) : array();

        foreach ( $course_ids as $course_id ) {
            // Validate courses before continuing
            $course = get_post( absint( $course_id ) );
            if ( empty( $course ) ) {
                $this->redirect_to_learner_admin_index( 'error-invalid-course' );
            }
        }

        foreach ( $user_ids as $user_id ) {
            $user = new WP_User( $user_id );

            if ( !$user->exists() ) {
                continue;
            }

            foreach ( $course_ids as $course_id ) {

                if ( self::ADD_TO_COURSE === $sensei_bulk_action ) {
                    Sensei_Utils::user_start_course( $user_id, $course_id );
                }

                if ( self::REMOVE_FROM_COURSE === $sensei_bulk_action ) {
                    if ( false === Sensei_Utils::user_started_course( $course_id, $user_id ) ) {
                        continue;
                    }
                    Sensei_Utils::sensei_remove_user_from_course( $course_id, $user_id );
                }

                if ( self::RESET_COURSE === $sensei_bulk_action ) {
                    if ( false === Sensei_Utils::user_started_course( $course_id, $user_id ) ) {
                        continue;
                    }
                    Sensei_Utils::reset_course_for_user( $course_id, $user_id );
                }
            }
        }

        $this->redirect_to_learner_admin_index( 'success-action-success' );
    }

    public function enqueue_scripts() {
        $is_debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
        $suffix = '';

        $jquery_modal_js_path = Sensei()->plugin_url . 'assets/vendor/jquery-modal-0.8.0/jquery.modal.min.js';
        wp_register_script( 'sensei-admin-jquery-modal', $jquery_modal_js_path );

        $jquery_modal_css_filepath = Sensei()->plugin_url . 'assets/vendor/jquery-modal-0.8.0/jquery.modal.min.css';
        wp_enqueue_style( 'sensei-admin-jquery-modal-css', $jquery_modal_css_filepath );

        $bulk_learner_actions_dependencies = array( 'jquery', 'sensei-core-select2', 'sensei-admin-jquery-modal' );
        $sensei_learners_bulk_actions_js = 'sensei-learners-admin-bulk-actions-js';
        $the_file = Sensei()->plugin_url . 'assets/js/learners-bulk-actions' . $suffix . '.js';
        wp_enqueue_script( $sensei_learners_bulk_actions_js, $the_file, $bulk_learner_actions_dependencies, Sensei()->version, true );

        $data = array(
            'remove_generic_confirm' => __( 'Are you sure you want to remove this user?', 'woothemes-sensei' ),
            'remove_from_lesson_confirm' => __( 'Are you sure you want to remove the user from this lesson?', 'woothemes-sensei' ),
            'remove_from_course_confirm' => __( 'Are you sure you want to remove the user from this course?', 'woothemes-sensei' ),
            'remove_user_from_post_nonce' => wp_create_nonce( 'remove_user_from_post_nonce' ),
            'bulk_add_learners_nonce' => wp_create_nonce( self::NONCE_SENSEI_BULK_LEARNER_ACTIONS ),
            'select_course_placeholder'=> __( 'Select Course', 'woothemes-sensei' ),
            'is_debug' => $is_debug,
            'sensei_version' => Sensei()->version
        );

        wp_localize_script( $sensei_learners_bulk_actions_js, 'sensei_learners_bulk_data', $data );

    }

    public function learner_admin_page() {
        // Load Learners data
        $sensei_learners_main_view = new Sensei_Learners_Admin_Bulk_Actions_View($this);
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
        ?> </div> <?php
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
//        add_action( 'admin_menu', array( $this, 'learners_admin_menu' ), 30);
        add_action( 'admin_init', array( $this, 'handle_http_post' ) );
        add_action( 'admin_notices', array( $this, 'add_notices' ) );
    }

    public function get_view() {
        return $this->view;
    }

    public function learners_admin_menu() {
        global $menu;
        if ( current_user_can( 'manage_sensei_grades' ) ) {
            $learners_page = add_submenu_page( 'sensei', 'Learner Admin', 'Learner Admin', 'manage_sensei_grades', 'sensei_learner_admin', array( $this, 'learner_admin_page' ) );
        }
    }

    public function add_notices() {
        if (!$this->is_current_page()) {
            return;
        }
        if (!isset($_GET['message'])) {
            return;
        }
        $msg = $_GET['message'];
        $msgClass = 'notice-error';
        $trans = $msg;
        if ('error-invalid-action' === $msg) {
            $trans = esc_html__( 'This bulk action is not supported', 'woothemes-sensei' );
        }
        if ('error-invalid-course' === $msg) {
            $trans = esc_html__( 'Invalid Course', 'woothemes-sensei' );
        }
        if ('success-action-success' === $msg) {
            $msgClass = 'notice-success';
            $trans = esc_html__( 'Bulk learner action succeeded', 'woothemes-sensei' );
        }
        ?>
        <div class="learners-notice <?php echo $msgClass; ?>">
            <p><?php echo $trans; ?></p>
        </div>
        <?php
    }

}

