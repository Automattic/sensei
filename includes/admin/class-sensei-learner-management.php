<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

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

	public $name;
	public $file;
	public $page_slug;
	public $bulk_actions_controller;
	const SENSEI_LEARNER_MANAGEMENT_PER_PAGE = 'sensei_learner_management_per_page';

	/**
	 * Constructor
	 * @since  1.6.0
	 * @return  void
	 */
	public function __construct ( $file ) {
		$this->name = __( 'Learner Management', 'woothemes-sensei' );;
		$this->file = $file;
		$this->page_slug = 'sensei_learners';

		// Admin functions
		if ( is_admin() ) {
			add_filter('set-screen-option', array($this, 'set_learner_management_screen_option'), 20, 3);
			add_action( 'admin_menu', array( $this, 'learners_admin_menu' ), 30);
			add_action( 'learners_wrapper_container', array( $this, 'wrapper_container'  ) );
			if ( isset( $_GET['page'] ) && ( ( $_GET['page'] == $this->page_slug ) || ( $_GET['page'] == 'sensei_learner_admin' ) ) ) {
				add_action( 'admin_print_scripts', array( $this, 'enqueue_scripts' ) );
				add_action( 'admin_print_styles', array( $this, 'enqueue_styles' ) );
			}

			add_action( 'admin_init', array( $this, 'add_new_learners' ) );

			add_action( 'admin_notices', array( $this, 'add_learner_notices' ) );
			$this->bulk_actions_controller = new Sensei_Learners_Admin_Bulk_Actions_Controller( $this );
		} // End If Statement

		// Ajax functions
		if ( is_admin() ) {
			add_action( 'wp_ajax_get_redirect_url_learners', array( $this, 'get_redirect_url' ) );
			add_action( 'wp_ajax_remove_user_from_post', array( $this, 'remove_user_from_post' ) );
			add_action( 'wp_ajax_reset_user_post', array( $this, 'reset_user_post' ) );
			add_action( 'wp_ajax_sensei_json_search_users', array( $this, 'json_search_users' ) );
		}
	} // End __construct()

	/**
	 * learners_admin_menu function.
	 * @since  1.6.0
	 * @access public
	 * @return void
	 */
	public function learners_admin_menu() {
		global $menu;

		if ( current_user_can( 'manage_sensei_grades' ) ) {
			$learners_page = add_submenu_page( 'sensei', $this->name, $this->name, 'manage_sensei_grades', $this->page_slug, array( $this, 'learners_page' ) );
			add_action("load-$learners_page", array($this, "load_screen_options_when_on_bulk_actions") );
		}

	} // End learners_admin_menu()

	public function set_learner_management_screen_option($status, $option, $value) {
		if ( self::SENSEI_LEARNER_MANAGEMENT_PER_PAGE == $option ) {
			return $value;
		}
		return $status;
	}
	
	public function load_screen_options_when_on_bulk_actions() {
		if ( isset($this->bulk_actions_controller) && $this->bulk_actions_controller->is_current_page() ) {

			$args = array(
				'label' => __('Learners per page', 'woothemes-sensei'),
				'default' => 20,
				'option' => self::SENSEI_LEARNER_MANAGEMENT_PER_PAGE
			);
			add_screen_option( 'per_page', $args );
		}
	}

	/**
	 * enqueue_scripts function.
	 *
	 * @description Load in JavaScripts where necessary.
	 * @access public
	 * @since 1.6.0
	 * @return void
	 */
	public function enqueue_scripts () {
		$is_debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
		$suffix = $is_debug ? '' : '.min';

		// Load Learners JS
		wp_enqueue_script( 'sensei-learners-general',
            Sensei()->plugin_url . 'assets/js/learners-general' . $suffix . '.js',
                            array('jquery','sensei-core-select2','sensei-chosen-ajax' ), Sensei()->version, true );


		wp_localize_script( 'sensei-learners-general', 'slgL10n', array(
			'inprogress'    => __( 'In Progress', 'woothemes-sensei' ),
		) );

		$data = array(
			'remove_generic_confirm' => __( 'Are you sure you want to remove this user?', 'woothemes-sensei' ),
			'remove_from_lesson_confirm' => __( 'Are you sure you want to remove the user from this lesson?', 'woothemes-sensei' ),
			'remove_from_course_confirm' => __( 'Are you sure you want to remove the user from this course?', 'woothemes-sensei' ),
			'reset_lesson_confirm' => __( 'Are you sure you want to reset the progress of this user for this lesson?', 'woothemes-sensei' ),
			'reset_course_confirm' => __( 'Are you sure you want to reset the progress of this user for this course?', 'woothemes-sensei' ),
			'modify_user_post_nonce' => wp_create_nonce( 'modify_user_post_nonce' ),
            'search_users_nonce' => wp_create_nonce( 'search-users' ),
            'selectplaceholder'=> __( 'Select Learner', 'woothemes-sensei' )
		);

		wp_localize_script( 'sensei-learners-general', 'woo_learners_general_data', $data );
	} // End enqueue_scripts()

	/**
	 * enqueue_styles function.
	 *
	 * @description Load in CSS styles where necessary.
	 * @access public
	 * @since 1.6.0
	 * @return void
	 */
	public function enqueue_styles () {

		wp_enqueue_style( 'woothemes-sensei-admin' );

	} // End enqueue_styles()

	/**
	 * load_data_table_files loads required files for Learners
	 * @since  1.6.0
	 * @return void
	 */
	public function load_data_table_files() {

		// Load Learners Classes
		$classes_to_load = array(	'list-table',
									'learners-main',
									);
		foreach ( $classes_to_load as $class_file ) {
			Sensei()->load_class( $class_file );
		} // End For Loop

	} // End load_data_table_files()

	/**
	 * load_data_object creates new instance of class
	 * @since  1.6.0
	 * @param  string  $name          Name of class
	 * @param  integer $data          constructor arguments
	 * @param  undefined  $optional_data optional constructor arguments
	 * @return object                 class instance object
	 */
	public function load_data_object( $name = '', $data = 0, $optional_data = null ) {
		// Load Analysis data
		$object_name = 'WooThemes_Sensei_Learners_' . $name;
		if ( is_null($optional_data) ) {
			$sensei_learners_object = new $object_name( $data );
		} else {
			$sensei_learners_object = new $object_name( $data, $optional_data );
		} // End If Statement
		if ( 'Main' == $name ) {
			$sensei_learners_object->prepare_items();
		} // End If Statement
		return $sensei_learners_object;
	} // End load_data_object()

	/**
	 * learners_page function.
	 * @since 1.6.0
	 * @access public
	 * @return void
	 */
	public function learners_page() {
		$type = isset( $_GET['view'] ) ? esc_html( $_GET['view'] ) : false;
		if ( $this->bulk_actions_controller->get_view() === $type ) {
			$this->bulk_actions_controller->learner_admin_page();
			return;
		}
		// Load Learners data
		$course_id = 0;
		$lesson_id = 0;
		if( isset( $_GET['course_id'] ) ) {
			$course_id = intval( $_GET['course_id'] );
		}
		if( isset( $_GET['lesson_id'] ) ) {
			$lesson_id = intval( $_GET['lesson_id'] );
		}
		$sensei_learners_main = $this->load_data_object( 'Main', $course_id, $lesson_id );
		// Wrappers
		do_action( 'learners_before_container' );
		do_action( 'learners_wrapper_container', 'top' );
		$this->learners_headers();
		?>
		<div id="poststuff" class="sensei-learners-wrap">
			<div class="sensei-learners-main">
				<?php $sensei_learners_main->display(); ?>
			</div>
			<div class="sensei-learners-extra">
				<?php do_action( 'sensei_learners_extra' ); ?>
			</div>
		</div>
		<?php
		do_action( 'learners_wrapper_container', 'bottom' );
		do_action( 'learners_after_container' );
	} // End learners_default_view()

	/**
	 * learners_headers outputs Learners general headers
	 * @since  1.6.0
     * @param array $args
	 * @return void
	 */
	public function learners_headers( $args = array( 'nav' => 'default' ) ) {

		$function = 'learners_' . $args['nav'] . '_nav';
		$this->$function();
		do_action( 'sensei_learners_after_headers' );

	} // End learners_headers()

	/**
	 * wrapper_container wrapper for Learners area
	 * @since  1.6.0
	 * @param $which string
	 * @return void
	 */
	public function wrapper_container( $which ) {
		if ( 'top' == $which ) {
			?><div id="woothemes-sensei" class="wrap woothemes-sensei"><?php
		} elseif ( 'bottom' == $which ) {
			?></div><!--/#woothemes-sensei--><?php
		} // End If Statement
	} // End wrapper_container()

	/**
	 * learners_default_nav default nav area for Learners
	 * @since  1.6.0
	 * @return void
	 */
	public function learners_default_nav() {
		$title = $this->name;
		if ( isset( $_GET['course_id'] ) ) { 
			$course_id = intval( $_GET['course_id'] );
			$url = add_query_arg( array( 'page' => $this->page_slug, 'course_id' => $course_id, 'view' => 'learners' ), admin_url( 'admin.php' ) );
			$title .= sprintf( '&nbsp;&nbsp;<span class="course-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>', esc_url( $url ), get_the_title( $course_id ) );
		}
		if ( isset( $_GET['lesson_id'] ) ) { 
			$lesson_id = intval( $_GET['lesson_id'] );
			$title .= '&nbsp;&nbsp;<span class="lesson-title">&gt;&nbsp;&nbsp;' . get_the_title( intval( $lesson_id ) ) . '</span>'; 
		}
		?>
			<h1><?php echo apply_filters( 'sensei_learners_nav_title', $title ); ?> | <a href="<?php echo esc_attr($this->bulk_actions_controller->get_url()); ?>"><?php echo $this->bulk_actions_controller->get_name(); ?></a></h1>
		<?php
	} // End learners_default_nav()

	public function get_redirect_url() {

		// Parse POST data
		$data = $_POST['data'];
		$course_data = array();
		parse_str( $data, $course_data );

		$course_cat = intval( $course_data['course_cat'] );

		$redirect_url = apply_filters( 'sensei_ajax_redirect_url', add_query_arg( array( 'page' => $this->page_slug, 'course_cat' => $course_cat ), admin_url( 'admin.php' ) ) );

		echo esc_url_raw( $redirect_url );
		die();
	}

	public function handle_reset_remove_user_post( $action ) {
		// Parse POST data
		$data = sanitize_text_field( $_POST[ 'data' ] );
		$action_data = array();
		parse_str( $data, $action_data );

		// Security checks
		// ensure the current user may remove users from post
		// only teacher or admin can remove users

		// check the nonce, valid post
		$nonce = '';
		if ( isset( $_POST[ 'modify_user_post_nonce' ] ) ) {
			$nonce = esc_html( $_POST[ 'modify_user_post_nonce' ] );
		}

		$post = get_post( intval( $action_data[ 'post_id' ] ) );

		if ( empty($post) ) {
			exit('');
		}

		// validate the user
		$may_remove_user = false;
		if ( current_user_can('manage_sensei') || $post->post_author == get_current_user_id() ) {
			$may_remove_user = true;
		}

		if ( ! wp_verify_nonce( $nonce, 'modify_user_post_nonce' ) || ! is_a( $post, 'WP_Post' ) || ! $may_remove_user ) {
			exit('');
		}

		if ( $action_data[ 'user_id' ] && $action_data[ 'post_id' ] && $action_data[ 'post_type' ] ) {
			$user_id = intval( $action_data['user_id'] );
			$post_id = intval( $action_data['post_id'] );
			$post_type = sanitize_text_field( $action_data['post_type'] );

			$user = get_userdata( $user_id );
			if ( false === $user ) {
				exit('');
			}

			$altered = true;

			switch ( $action ) {
				case 'reset':
					switch ( $post_type ) {
						case 'course':
							$altered = Sensei_Utils::reset_course_for_user( $post_id, $user_id );
						break;

						case 'lesson':
							$altered = Sensei()->quiz->reset_user_lesson_data( $post_id, $user_id );
						break;
					}
				break;

				case 'remove':
					switch ( $post_type ) {
						case 'course':
							$altered = Sensei_Utils::sensei_remove_user_from_course( $post_id, $user_id );
						break;

						case 'lesson':
							$altered = Sensei_Utils::sensei_remove_user_from_lesson( $post_id, $user_id );
						break;
					}
				break;
			}

			if ( $altered ) {
				exit( 'altered' );
			}
		}

		exit('');
	}

	public function reset_user_post() {
		$this->handle_reset_remove_user_post( 'reset' );
	}

	public function remove_user_from_post() {
		$this->handle_reset_remove_user_post( 'remove' );
	}

	public function json_search_users() {


		check_ajax_referer( 'search-users', 'security' );

		$term = sanitize_text_field( stripslashes( $_GET['term'] ) );

		if ( empty( $term ) ) {
			die();
		}

		$default = isset( $_GET['default'] ) ? $_GET['default'] : __( 'None', 'woocommerce' );

		$found_users = array( '' => $default );

		$users_query = new WP_User_Query( apply_filters( 'sensei_json_search_users_query', array(
			'fields'         => 'all',
			'orderby'        => 'display_name',
			'search'         => '*' . $term . '*',
			'search_columns' => array( 'ID', 'user_login', 'user_email', 'user_nicename','user_firstname','user_lastname' )
		), $term ) );

		$users = $users_query->get_results();

		if ( $users ) {
			foreach ( $users as $user ) {
                $full_name = Sensei_Learner::get_full_name( $user->ID );

                if( trim($user->display_name ) == trim( $full_name ) ){

                    $name = $full_name;

                }else{

                    $name = $full_name . ' ['. $user->display_name .']';

                }

                $found_users[ $user->ID ] = $name  . ' (#' . $user->ID . ' - ' . sanitize_email( $user->user_email ) . ')';
			}
		}

		wp_send_json( $found_users );
	}

	public function add_new_learners() {

		$result = false;

		if( ! isset( $_POST['add_learner_submit'] ) ) return $result;

		if ( ! isset( $_POST['add_learner_nonce'] ) || ! wp_verify_nonce( $_POST['add_learner_nonce'], 'add_learner_to_sensei' ) ) return $result;

		if( ( ! isset( $_POST['add_user_id'] ) || '' ==  $_POST['add_user_id'] ) || ! isset( $_POST['add_post_type'] ) || ! isset( $_POST['add_course_id'] ) || ! isset( $_POST['add_lesson_id'] ) ) return $result;

		$post_type = $_POST['add_post_type'];
		$user_id   = absint( $_POST['add_user_id'] );
		$course_id = absint( $_POST['add_course_id'] );
		$lesson_id = isset( $_POST['add_lesson_id'] ) ? $_POST['add_lesson_id'] : '';

		switch( $post_type ) {
			case 'course':

				$result = Sensei_Utils::user_start_course( $user_id, $course_id );

				// Complete each lesson if course is set to be completed
				if( isset( $_POST['add_complete_course'] ) && 'yes' == $_POST['add_complete_course'] ) {

					$lesson_ids = Sensei()->course->course_lessons( $course_id, 'any', 'ids' );

					foreach( $lesson_ids as $id ) {
						Sensei_Utils::sensei_start_lesson( $id, $user_id, true );
					}

					// Updates the Course status and it's meta data
					Sensei_Utils::user_complete_course( $course_id, $user_id );
				}

			break;

			case 'lesson':

				$complete = false;
				if( isset( $_POST['add_complete_lesson'] ) && 'yes' == $_POST['add_complete_lesson'] ) {
					$complete = true;
				}

				$result = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id, $complete );

				// Updates the Course status and it's meta data
				Sensei_Utils::user_complete_course( $course_id, $user_id );

			break;
		}

		// Set redirect URL after adding user to course/lesson
		$query_args = array( 'page' => $this->page_slug, 'view' => 'learners' );

		if( $result ) {

			if( $course_id ) {
				$query_args['course_id'] = $course_id;
			}

			if( $lesson_id ) {
				$query_args['lesson_id'] = $lesson_id;
			}

			$query_args['message'] = 'success';

		} else {
			$query_args['message'] = 'error';
		}

		$redirect_url = apply_filters( 'sensei_learners_add_learner_redirect_url', add_query_arg( $query_args, admin_url( 'admin.php' ) ) );

		wp_safe_redirect( esc_url_raw( $redirect_url ) );
		exit;
	}

	public function add_learner_notices() {
		if( isset( $_GET['page'] ) && $this->page_slug == $_GET['page'] && isset( $_GET['message'] ) && $_GET['message'] ) {
			if( 'error' != $_GET['message'] ) {
				$message = __( 'Learner added successfully!', 'woothemes-sensei' );
				if ( 'success_bulk' == $_GET['message'] ) {
					$message = __( 'Learners added successfully!', 'woothemes-sensei' );
				}
				$msg = array( 'updated', $message );
			} else {
				$msg = array(
					'error',
					__( 'Error adding learner.', 'woothemes-sensei' ),
				);
			}
			?>
			<div class="learners-notice <?php echo $msg[0]; ?>">
				<p><?php echo $msg[1]; ?></p>
			</div>
			<?php
		}
	}

    /**
     * Return the full name and surname or the display name of the user.
     *
     * The user must have both name and surname otherwise display name will be returned.
     *
     * @deprecated since 1.9.0 use Se
     * @since 1.8.0
     *
     * @param int $user_id | bool false for an invalid $user_id
     *
     * @return string $full_name
     */
    public function get_learner_full_name( $user_id ){

        return Sensei_Learner::get_full_name( $user_id );

    }

	public function get_url() {
		return add_query_arg(array('page' => $this->page_slug), admin_url('admin.php') );
	}

	public function get_name() {
		return $this->name;
	}

} // End Class

/**
 * Class WooThemes_Sensei_Learners
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Learners extends Sensei_Learner_Management{}
