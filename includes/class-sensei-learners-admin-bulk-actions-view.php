<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

class Sensei_Learners_Admin_Bulk_Actions_View extends WooThemes_Sensei_List_Table {


    public $view = '';
    public $page_slug = 'sensei_learner_admin';
    private $name;
    private $query_args = array();
    /**
     * @var Sensei_Learners_Admin_Bulk_Actions_Controller
     */
    private $controller;

    /**
     * Sensei_Learners_Admin_Main_View constructor.
     * @param Sensei_Learners_Admin_Bulk_Actions_Controller $controller
     */
    public function __construct( $controller ) {
        $this->controller = $controller;
        $this->name = $controller->get_name();
        $this->page_slug = $controller->get_page_slug();
        parent::__construct( $this->page_slug );
        $this->query_args = $this->parse_query_args();
        add_action('sensei_before_list_table', array($this, 'data_table_header'));
        add_action('sensei_after_list_table', array($this, 'data_table_footer'));
        add_filter('sensei_list_table_search_button_text', array($this, 'search_button'));
    }

    public function output_headers() {
        $link_back_to_lm = '<a href="' . esc_attr( $this->controller->analysis->get_url() ) . '">' . esc_html( $this->controller->analysis->get_name() ) . '</a>';
        $title = $this->name;
        $subtitle = '';
        if ( isset( $this->query_args['filter_by_course_id'] ) ) {
            $course = get_post( absint( $this->query_args['filter_by_course_id'] ) );
            if ( !empty( $course ) ) {
                $subtitle .= '<h2>' . $course->post_title . '</h2>';
            }
        }
        echo '<h1>'. $link_back_to_lm . ' | ' . $title . '</h1>' . $subtitle;
    }

    function get_columns() {
        $columns = array(
            'cb' => '<label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox">',
            'learner' => __( 'Learner', 'woothemes-sensei' ),
            'overview' => __( 'Overview', 'woothemes-sensei' )
        );

        return apply_filters( 'sensei_learners_admin_default_columns', $columns, $this );
    }

    function get_sortable_columns() {
        $columns = array(
            'learner' => array( 'learner', false ),
        );
        return apply_filters( 'sensei_learner_admin_default_columns_sortable', $columns, $this );
    }


    public function prepare_items() {
        $this->items = $this->get_learners( $this->query_args );

        $total_items = $this->total_items;
        $total_pages = ceil( $total_items / $this->query_args['per_page'] );
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'total_pages' => $total_pages,
            'per_page' => $this->query_args['per_page']
        ) );

    }

    protected function get_row_data( $item ) {
        if( ! $item ) {
            return array(
                'cb' => '',
                'learner' => __( 'No results found', 'woothemes-sensei' ),
                'overview' => ''
            );
        }
        $learner = $item;
        $courses = $this->get_learner_courses_html( $item->course_statuses );
        $column_data = array(
            'cb' => '<label class="screen-reader-text" for="cb-select-all-1">Select All</label>' . '<input type="checkbox" name="user_id" value="' . $learner->user_id . '" class="sensei_user_select_id">',
            'learner' =>  $this->get_learner_html( $learner ),
            'overview' => $courses
        );
        return $column_data;
    }

    private function get_learner_html( $learner ) {
        $login = esc_html( $learner->user_login );
        $title = esc_html( Sensei_Learner::get_full_name( $learner->user_id ) );
        $a_title = sprintf( __( 'Edit &#8220;%s&#8221;' ), $title );
        $html = '<strong><a class="row-title" href="' . admin_url( 'user-edit.php?user_id=' . $learner->user_id ) . '" title="' . esc_attr( $a_title ) . '">' . esc_html( $login ) . '</a></strong>';
        $html .= ' <span>(<em>' . $title . '</em>, ' . esc_html( $learner->user_email ) . ')</span>';
        return $html;
    }

    private function get_learners( $args ) {
        $query = new Sensei_Db_Query_Learners( $args );
        $learners = $query->get_all();
        $this->total_items = $query->total_items;
        return $learners;
    }


    public function no_items() {
        $text = __( 'No learners found.', 'woothemes-sensei' );
        echo apply_filters( 'sensei_learners_no_items_text', $text );
    }

    private function courses_select($courses, $selected_course, $select_id = 'course-select', $name='course_id', $select_label = 'Select Course', $multiple = false) {
        ?>

        <select id="<?php echo esc_attr( $select_id ); ?>" data-placeholder="<?php echo esc_attr__( $select_label, 'woothemes-sensei' ); ?>" name="<?php echo esc_attr( $name ); ?>" class="sensei-course-select" style="width: 50%" <?php echo $multiple ? 'multiple="true"' : ''; ?>>
            <option value="0"><?php echo esc_attr__( $select_label, 'woothemes-sensei' ) ?></option>
            <?php foreach( $courses as $course ) {
                echo '<option value="' . esc_attr( $course->ID ) . '"' . selected( $course->ID, $selected_course, false ) . '>' . esc_html( $course->post_title ) . '</option>';
            } ?>
        </select>
        <?php
    }

    private function render_bulk_actions_form( $courses ) {
        ?>
        <form id="bulk-learner-actions-form" "action="" method="post">
        <label for="bulk-action-course-select" class="screen-reader-text"><?php echo esc_html__( 'Select Course(s)', 'woothemes-sensei' ); ?></label>
        <?php $this->courses_select( $courses, -1, 'bulk-action-course-select', 'course_id', 'Select Course(s)', true ); ?>
        <input type="hidden" id="bulk-action-user-ids"  name="bulk_action_user_ids" value="">
        <input type="hidden" id="sensei-bulk-action"  name="sensei_bulk_action" value="">
        <input type="hidden" id="bulk-action-course-ids"  name="bulk_action_course_ids" value="">
        <?php wp_nonce_field( Sensei_Learners_Admin_Bulk_Actions_Controller::NONCE_SENSEI_BULK_LEARNER_ACTIONS, Sensei_Learners_Admin_Bulk_Actions_Controller::SENSEI_BULK_LEARNER_ACTIONS_NONCE_FIELD ); ?>
        <button type="submit" id="bulk-learner-action-submit" class="button button-primary action"><?php echo esc_html__( 'Apply', 'woothemes-sensei' ); ?></button>
        </form>
        <?php
    }

    private function render_bulk_action_select_box() {
        $rendered =
            '<select name="sensei_bulk_action_select" id="bulk-action-selector-top">' .
            '<option value="">' . esc_html__('Bulk Learner Actions', 'woothemes-sensei' ) . '</option>';
        $bulk_actions = $this->controller->get_known_bulk_actions();
        foreach ( $bulk_actions as $value => $translation ) {
            $rendered .= '<option value="' . esc_attr( $value ) . '">' . esc_html( $translation ) .'</option>';
        }

        return $rendered . '</select>';
    }


    public function data_table_header() {
        $courses = Sensei_Course::get_all_courses();
        $selected_course = 0;
        if ( isset( $_GET['filter_by_course_id'] ) && '' != esc_html( $_GET['filter_by_course_id'] ) ) {
            $selected_course = intval( $_GET['filter_by_course_id'] );
        }
        ?>
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <div id="sensei-bulk-learner-actions-modal" style="display:none;">
                    <?php $this->render_bulk_actions_form( $courses ); ?>
                </div>
                <?php echo $this->render_bulk_action_select_box(); ?>
                <button type="submit" id="sensei-bulk-learner-actions-modal-toggle" class="button button-primary action"><?php echo esc_html__( 'Select Courses', 'woothemes-sensei' ); ?></button>
            </div>
            <div class="alignleft actions">
                <form action="" method="get">
                    <?php
                    foreach ( $this->query_args as $name => $value ) {
                        if ('filter_by_course_id' ==  $name || 'filter_type' == $name) {
                            continue;
                        }
                        echo '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">';
                    }
                    $this->courses_select($courses, $selected_course, 'courses-select-filter', 'filter_by_course_id', 'Filter By Course');
                    ?>
                    <button type="submit" id="filt" class="button action"><?php echo esc_html__( 'Filter', 'woothemes-sensei' ); ?></button>
                </form>

            </div>
        </div>
        <?php
    }

    public function search_button( $text = '' ) {
        return __( 'Search Learners', 'woothemes-sensei' );
    }

    private function get_learner_courses_html($courses)
    {
        if ( empty($courses) ) {
            return '0 ' . esc_html__('Courses', 'woothemes-sensei') . ' ' . esc_html__('In Progress', 'woothemes-sensei');
        } else {
            $courses = explode(',', $courses);
            $course_arr = array();
            $courses_total = count($courses);
            $courses_completed = 0;
            foreach ($courses as $course_id) {
                $splitted = explode('|', $course_id);
                $course_id = absint($splitted[0]);
                $course_status = $splitted[1];
                if ($course_status === 'c') {
                    $courses_completed++;
                }

                $course = get_post($course_id);
//                $span_style = 'display: inline; padding: .2em .6em .3em; font-size: 75%; font-weight: 700; line-height: 1; color: #fff; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: .25em;';
                $span_style = $course_status == 'c' ? ' button-primary action' : ' action';
                $course_arr[] = '<a  href="' . $this->controller->get_learner_management_course_url( $course_id ) . '"class="button' . $span_style . '" data-course-id="' . $course_id . '">' . $course->post_title . '</a>';
            }

            $html = $courses_total - $courses_completed . ' ' . esc_html__('Courses', 'woothemes-sensei') . ' ' . esc_html__('In Progress', 'woothemes-sensei');
            if ($courses_completed > 0) {
                $html .= ', ' . $courses_completed . ' '. esc_html__('Completed', 'woothemes-sensei');
            }
            $html .= ' <a href="#" class="learner-course-overview-detail-btn" >...<span>more</span></a><br/>';
            $courses = implode('<br />', $course_arr);
            return $html . '<div class="learner-course-overview-detail" style="display:none">' . $courses . '</div>';
        }
    }

    public function parse_query_args() {
        global $per_page;
        // Handle orderby
        $course_id = 0;
        $lesson_id = 0;
        if( isset( $_GET['course_id'] ) ) {
            $course_id = intval( $_GET['course_id'] );
        }
        if( isset( $_GET['lesson_id'] ) ) {
            $lesson_id = intval( $_GET['lesson_id'] );
        }
        $this->course_id = intval( $course_id );
        $this->lesson_id = intval( $lesson_id );

        $orderby = '';
        if ( !empty( $_GET['orderby'] ) ) {
            if ( array_key_exists( esc_html( $_GET['orderby'] ), $this->get_sortable_columns() ) ) {
                $orderby = esc_html( $_GET['orderby'] );
            } // End If Statement
        }

        // Handle order
        $order = 'DESC';
        if ( !empty( $_GET['order'] ) ) {
            $order = ( 'ASC' == strtoupper($_GET['order']) ) ? 'ASC' : 'DESC';
        }

        // Handle search
        $search = false;
        if ( !empty( $_GET['s'] ) ) {
            $search = esc_html( $_GET['s'] );
        } // End If Statement


        $screen = get_current_screen();
        $per_page = 0;
        if (!empty( $screen ) ) {
            $screen_option = $screen->get_option( 'per_page', 'option' );
            $per_page = absint( get_user_meta( get_current_user_id(), $screen_option, true) );
            if ( empty ( $per_page ) || $per_page < 1 ) {
                // get the default value if none is set
                $per_page = absint( $screen->get_option( 'per_page', 'default' ) );
            }
        } else {
            $per_page = $this->get_items_per_page( 'sensei_comments_per_page' );
            $per_page = absint( apply_filters( 'sensei_comments_per_page', $per_page, 'sensei_comments' ) );
        }

        $paged = $this->get_pagenum();
        $offset = 0;
        if ( !empty($paged) ) {
            $offset = $per_page * ( $paged - 1 );
        } // End If Statement
        if ( empty($orderby) ) {
            $orderby = '';
        }

        $filter_by_course_id = 0;
        if ( !empty( $_GET['filter_by_course_id'] ) ) {
            $filter_by_course_id = absint( $_GET['filter_by_course_id'] );
        }

        $filter_type = 'inc';
        if ( !empty( $_GET['filter_type'] ) ) {
            $filter_type = in_array( $_GET['filter_type'], array('inc', 'exc') ) ? $_GET['filter_type'] : 'inc';
        }
        $page = $this->page_slug;
        $view = $this->controller->get_view();
        $args = compact( 'page', 'view', 'per_page', 'offset', 'orderby', 'order', 'search', 'filter_by_course_id', 'filter_type' );
        $this->query_args = $args;
        return $args;
    }
}
