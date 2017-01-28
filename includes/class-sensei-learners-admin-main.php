<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Sensei Learners Sensei_Learners_Admin_Main List Table Class
 *
 * All functionality pertaining to the Admin Learners Overview Data Table in Sensei.
 *
 * @package Assessment
 * @author Automattic
 *
 * @since 1.3.0
 */
class Sensei_Learners_Admin_Main {

    const ACTION_SENSEI_ADMIN_LEARNER_MANAGEMENT_ENQUEUE_SCRIPTS = 'sensei_admin_learner_management_enqueue_scripts';
    const ACTION_SENSEI_LEARNERS_ADD_LEARNER_FORM = 'sensei_learners_bulk_add_learners_form';
    const NONCE_SENSEI_BULK_ADD_LEARNERS = 'bulk-add-learners';
    const SENSEI_BULK_ADD_LEARNERS_NONCE_FIELD_NAME = 'sensei_bulk_add_learners_nonce';
    
    public $course_id = 0;
    public $lesson_id = 0;
    public $view = '';
    public $page_slug = 'sensei_learner_admin';
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

    public function __construct( $file ) {
        $this->name = __( 'Learner Admin', 'woothemes-sensei' );
        $this->file = $file;
        if ( is_admin() ) {
            $this->hook();
        }
    }

    private function redirect_to_learner_admin_index( $result, $notice ) {
        $url = add_query_arg(array(
            'page' => $this->page_slug,
            'result' => $result,
            'notice' => $notice,
        ), admin_url( 'admin.php' ));
        wp_redirect( $url );
        wp_die();
    }

    public function handle_http_post() {
        if (!$this->is_current_page() ) {
            return;
        }

        if ( !isset( $_POST['sensei_bulk_action'] ) ) {
            return;
        }

        $sensei_bulk_action = $_POST['sensei_bulk_action'];

        if (!in_array( $sensei_bulk_action, array( 'add_to_course', 'remove_from_course' ) )) {
            $this->redirect_to_learner_admin_index( 'error', 'invalid-action' );
        }

        $course_id = isset( $_POST['course_id'] ) ? $_POST['course_id'] : 0;
        $user_ids = isset( $_POST['bulk_action_user_ids'] ) ? array_map('absint', explode(',', $_POST['bulk_action_user_ids'])) : array();
        $course = get_post( $course_id );
        if (empty($course)) {
            $this->redirect_to_learner_admin_index( 'error', 'invalid-course' );
        }
        foreach ( $user_ids as $user_id ) {
            $user = new WP_User( $user_id );
            if ( $user->exists() && 'add_to_course' === $sensei_bulk_action ) {
                Sensei_Utils::user_start_course( $user_id, $course_id );
            }
            if ( $user->exists() && 'remove_from_course' === $sensei_bulk_action ) {
                if (!Sensei_Utils::user_started_course( $course_id, $user_id )) {
                    continue;
                }
                Sensei_Utils::sensei_remove_user_from_course($course_id, $user_id);
            }
        }
//        $this->redirect_to_learner_admin_index( 'success', 'action-success' );
    }

    public function enqueue_scripts() {
        $is_debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
        $suffix = '';

        $bulk_learner_actions_dependencies = array( 'jquery', 'sensei-core-select2' );
        $sensei_learners_bulk_actions_js = 'sensei_learners_admin_bulk_actions_script';
        $the_file = Sensei()->plugin_url . 'assets/js/learners-bulk-actions' . $suffix . '.js';
        wp_enqueue_script( $sensei_learners_bulk_actions_js, $the_file, $bulk_learner_actions_dependencies, Sensei()->version, true );

        $data = array(
            'remove_generic_confirm' => __( 'Are you sure you want to remove this user?', 'woothemes-sensei' ),
            'remove_from_lesson_confirm' => __( 'Are you sure you want to remove the user from this lesson?', 'woothemes-sensei' ),
            'remove_from_course_confirm' => __( 'Are you sure you want to remove the user from this course?', 'woothemes-sensei' ),
            'remove_user_from_post_nonce' => wp_create_nonce( 'remove_user_from_post_nonce' ),
            'bulk_add_learners_nonce' => wp_create_nonce( self::NONCE_SENSEI_BULK_ADD_LEARNERS ),
            'select_course_placeholder'=> __( 'Select Course', 'woothemes-sensei' ),
            'is_debug' => $is_debug,
            'sensei_version' => Sensei()->version
        );

        wp_localize_script( $sensei_learners_bulk_actions_js, 'sensei_learners_bulk_data', $data );

    }

    public function learner_admin_page() {
        // Load Learners data
        $sensei_learners_main_view = new Sensei_Learners_Admin_Main_View($this);
        $sensei_learners_main_view->prepare_items();
        // Wrappers
        do_action( 'sensei_learner_admin_before_container' );
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
        do_action( 'sensei_learner_admin_after_container' );
    }

    private function is_current_page() {
        return isset( $_GET['page'] ) && ( $_GET['page'] == $this->page_slug );
    }

    public function hook() {
        if ( $this->is_current_page() ) {
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 30 );
        }
        add_action( 'admin_menu', array( $this, 'learners_admin_menu' ), 30);
        add_action( 'admin_init', array( $this, 'handle_http_post' ) );
    }

    public function learners_admin_menu() {
        global $menu;
        if ( current_user_can( 'manage_sensei_grades' ) ) {
            $learners_page = add_submenu_page( 'sensei', 'Learner Admin', 'Learner Admin', 'manage_sensei_grades', 'sensei_learner_admin', array( $this, 'learner_admin_page' ) );
        }
    }

} // End Class

