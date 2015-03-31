<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Learners Class
 *
 * All functionality pertaining to the Admin Learners in Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Core
 * @author WooThemes
 * @since 1.3.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 * - learners_admin_menu()
 * - enqueue_scripts()
 * - enqueue_styles()
 * - load_data_table_files()
 * - load_data_object()
 * - learners_page()
 * - learners_default_view()
 * - learners_headers()
 * - wrapper_container()
 * - learners_default_nav()
 * - get_redirect_url()
 * - remove_user_from_post()
 * - json_search_users()
 * - add_new_learners()
 * - add_learner_notices()
 */
class WooThemes_Sensei_Learners {
	public $token;
	public $name;
	public $file;
	public $page_slug;

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
			add_action( 'admin_menu', array( $this, 'learners_admin_menu' ), 10);
			add_action( 'learners_wrapper_container', array( $this, 'wrapper_container'  ) );
			if ( isset( $_GET['page'] ) && ( $_GET['page'] == $this->page_slug ) ) {
				add_action( 'admin_print_scripts', array( $this, 'enqueue_scripts' ) );
				add_action( 'admin_print_styles', array( $this, 'enqueue_styles' ) );
			}

			add_action( 'admin_init', array( $this, 'add_new_learners' ) );

			add_action( 'admin_notices', array( $this, 'add_learner_notices' ) );
		} // End If Statement

		// Ajax functions
		if ( is_admin() ) {
			add_action( 'wp_ajax_get_redirect_url_learners', array( $this, 'get_redirect_url' ) );
			add_action( 'wp_ajax_remove_user_from_post', array( $this, 'remove_user_from_post' ) );
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
		}

	} // End learners_admin_menu()

	/**
	 * enqueue_scripts function.
	 *
	 * @description Load in JavaScripts where necessary.
	 * @access public
	 * @since 1.6.0
	 * @return void
	 */
	public function enqueue_scripts () {
		global $woothemes_sensei;

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Load Learners JS
		wp_enqueue_script( 'sensei-learners-general', $woothemes_sensei->plugin_url . 'assets/js/learners-general' . $suffix . '.js', array( 'jquery' ), '1.6.0' );

		$data = array(
			'remove_generic_confirm' => __( 'Are you sure you want to remove this user?', 'woothemes-sensei' ),
			'remove_from_lesson_confirm' => __( 'Are you sure you want to remove the user from this lesson?', 'woothemes-sensei' ),
			'remove_from_course_confirm' => __( 'Are you sure you want to remove the user from this course?', 'woothemes-sensei' ),
			'remove_user_from_post_nonce' => wp_create_nonce( 'remove_user_from_post_nonce' ),
		);

		wp_localize_script( 'sensei-learners-general', 'woo_localized_data', $data );

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
		global $woothemes_sensei;
		wp_enqueue_style( $woothemes_sensei->token . '-admin' );
	} // End enqueue_styles()

	/**
	 * load_data_table_files loads required files for Learners
	 * @since  1.6.0
	 * @return void
	 */
	public function load_data_table_files() {
		global $woothemes_sensei;
		// Load Learners Classes
		$classes_to_load = array(	'list-table',
									'learners-main',
									);
		foreach ( $classes_to_load as $class_file ) {
			$woothemes_sensei->load_class( $class_file );
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
		global $woothemes_sensei;
		// Load Learners data
		$this->load_data_table_files();
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
	 * @return void
	 */
	public function learners_headers( $args = array( 'nav' => 'default' ) ) {
		global $woothemes_sensei;

		$function = 'learners_' . $args['nav'] . '_nav';
		$this->$function();
		?>
			<p class="powered-by-woo"><?php _e( 'Powered by', 'woothemes-sensei' ); ?><a href="http://www.woothemes.com/" title="WooThemes"><img src="<?php echo $woothemes_sensei->plugin_url; ?>assets/images/woothemes.png" alt="WooThemes" /></a></p>
		<?php
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
			?><div id="woothemes-sensei" class="wrap <?php echo esc_attr( $this->token ); ?>"><?php
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
		$title = sprintf( '<a href="%s">%s</a>', add_query_arg( array( 'page' => $this->page_slug ), admin_url( 'admin.php' ) ), esc_html( $this->name ) );
		if ( isset( $_GET['course_id'] ) ) { 
			$course_id = intval( $_GET['course_id'] );
			$url = add_query_arg( array( 'page' => $this->page_slug, 'course_id' => $course_id, 'view' => 'learners' ), admin_url( 'admin.php' ) );
			$title .= sprintf( '&nbsp;&nbsp;<span class="course-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>', $url, get_the_title( $course_id ) ); 
		}
		if ( isset( $_GET['lesson_id'] ) ) { 
			$lesson_id = intval( $_GET['lesson_id'] );
			$title .= '&nbsp;&nbsp;<span class="lesson-title">&gt;&nbsp;&nbsp;' . get_the_title( intval( $lesson_id ) ) . '</span>'; 
		}
		?>
			<h2><?php echo apply_filters( 'sensei_learners_nav_title', $title ); ?></h2>
		<?php
	} // End learners_default_nav()

	public function get_redirect_url() {

		// Parse POST data
		$data = $_POST['data'];
		$course_data = array();
		parse_str( $data, $course_data );

		$course_cat = intval( $course_data['course_cat'] );

		$redirect_url = apply_filters( 'sensei_ajax_redirect_url', add_query_arg( array( 'page' => $this->page_slug, 'course_cat' => $course_cat ), admin_url( 'admin.php' ) ) );

		echo $redirect_url;
		die();
	}

	public function remove_user_from_post() {
		global $woothemes_sensei;

		$return = '';

		// Security check
		$nonce = '';
		if ( isset($_POST['remove_user_from_post_nonce']) ) {
			$nonce = esc_html( $_POST['remove_user_from_post_nonce'] );
		}
		if ( ! wp_verify_nonce( $nonce, 'remove_user_from_post_nonce' ) ) {
			die( $return );
		}

		// Parse POST data
		$data = $_POST['data'];
		$action_data = array();
		parse_str( $data, $action_data );

		if( $action_data['user_id'] && $action_data['post_id'] && $action_data['post_type'] ) {

			$user_id = intval( $action_data['user_id'] );
			$post_id = intval( $action_data['post_id'] );
			$post_type = sanitize_text_field( $action_data['post_type'] );

			$user = get_userdata( $user_id );

			switch( $post_type ) {

				case 'course':
					$removed = WooThemes_Sensei_Utils::sensei_remove_user_from_course( $post_id, $user_id );
				break;

				case 'lesson':
					$removed = WooThemes_Sensei_Utils::sensei_remove_user_from_lesson( $post_id, $user_id );
				break;

			}

			if( $removed ) {
				$return = 'removed';
			}

		}

		die( $return );
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
			'search_columns' => array( 'ID', 'user_login', 'user_email', 'user_nicename' )
		), $term ) );

		$users = $users_query->get_results();

		if ( $users ) {
			foreach ( $users as $user ) {
				$found_users[ $user->ID ] = $user->display_name . ' (#' . $user->ID . ' &ndash; ' . sanitize_email( $user->user_email ) . ')';
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
		$user_id = absint( $_POST['add_user_id'] );
		$course_id = absint( $_POST['add_course_id'] );
		$lesson_id = absint( $_POST['add_lesson_id'] );

		switch( $post_type ) {
			case 'course':

				$result = WooThemes_Sensei_Utils::user_start_course( $user_id, $course_id );

				// Complete each lesson if course is set to be completed
				if( isset( $_POST['add_complete_course'] ) && 'yes' == $_POST['add_complete_course'] ) {

					$lesson_ids = WooThemes_Sensei_Course::course_lessons( $course_id, 'any', 'ids' );

					foreach( $lesson_ids as $lesson_id ) {
						WooThemes_Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id, true );
					}

					// Updates the Course status and it's meta data
					WooThemes_Sensei_Utils::user_complete_course( $course_id, $user_id );

					do_action( 'sensei_user_course_end', $user_id, $course_id );
				}

			break;

			case 'lesson':

				$complete = false;
				if( isset( $_POST['add_complete_lesson'] ) && 'yes' == $_POST['add_complete_lesson'] ) {
					$complete = true;
				}

				$result = WooThemes_Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id, $complete );

				// Updates the Course status and it's meta data
				WooThemes_Sensei_Utils::user_complete_course( $course_id, $user_id );

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

		wp_safe_redirect( $redirect_url );
		exit;
	}

	public function add_learner_notices() {
		if( isset( $_GET['page'] ) && $this->page_slug == $_GET['page'] && isset( $_GET['message'] ) && $_GET['message'] ) {
			if( 'success' == $_GET['message'] ) {
				$msg = array(
					'updated',
					__( 'Learner added successfully!', 'woothemes-sensei' ),
				);
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

} // End Class
