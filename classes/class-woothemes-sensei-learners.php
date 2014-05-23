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
 * - learners_user_quiz_view()
 * - learners_headers()
 * - wrapper_container()
 * - learners_default_nav()
 * - learners_user_quiz_nav()
 * - get_lessons_dropdown()
 * - lessons_drop_down_html()
 * - get_lessons_html()
 * - process_learners()
 * - get_direct_url()
 * - sensei_learners_notices()
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
		} // End If Statement

		add_action( 'wp_ajax_get_redirect_url_learners', array( $this, 'get_redirect_url' ) );
		add_action( 'wp_ajax_nopriv_get_redirect_url_learners', array( $this, 'get_redirect_url' ) );

		add_action( 'wp_ajax_remove_user_from_post', array( $this, 'remove_user_from_post' ) );
		add_action( 'wp_ajax_nopriv_remove_user_from_post', array( $this, 'remove_user_from_post' ) );

	} // End __construct()

	/**
	 * learners_admin_menu function.
	 * @since  1.6.0
	 * @access public
	 * @return void
	 */
	public function learners_admin_menu() {
	    global $menu;

	    if ( current_user_can( 'manage_sensei' ) ) {
	    	$learners_page = add_submenu_page( 'sensei', $this->name, $this->name, 'manage_sensei_grades', $this->page_slug, array( $this, 'learners_page' ) );
	    }

	} // End analysis_admin_menu()

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
		if ( isset( $_GET['user'] ) && 0 < intval( $_GET['user'] ) && isset( $_GET['quiz_id'] ) && 0 < intval( $_GET['quiz_id'] ) ) {
			$this->learners_user_quiz_view();
		} else {
			$this->learners_default_view();
		} // End If Statement
	} // End analysis_page()

	/**
	 * learners_default_view default view for learners page
	 * @since  1.6.0
	 * @return void
	 */
	public function learners_default_view( $type = '' ) {
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
		$sensei_learners_overview = $this->load_data_object( 'Main', $course_id, $lesson_id );
		// Wrappers
		do_action( 'learners_before_container' );
		do_action( 'learners_wrapper_container', 'top' );
		$this->learners_headers();
		?><div id="poststuff" class="sensei-learners-wrap">
				<div class="sensei-learners-main">
					<?php $sensei_learners_overview->display(); ?>
				</div>
			</div>
		<?php
		do_action( 'learners_wrapper_container', 'bottom' );
		do_action( 'learners_after_container' );
	} // End learners_default_view()

	/**
	 * analysis_headers outputs analysis general headers
	 * @since  1.6.0
	 * @return void
	 */
	public function learners_headers( $args = array( 'nav' => 'default' ) ) {
		$function = 'learners_' . $args['nav'] . '_nav';
		$this->$function();
	} // End learners_headers()

	/**
	 * wrapper_container wrapper for analysis area
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
	 * learners_default_nav default nav area for analysis
	 * @since  1.6.0
	 * @return void
	 */
	public function learners_default_nav() {
		global $woothemes_sensei;
		?><?php screen_icon( 'woothemes-sensei' ); ?>
			<h2><?php echo esc_html( $this->name ); ?><?php if ( isset( $_GET['course_id'] ) ) { echo '&nbsp;&nbsp;&gt;&nbsp;&nbsp;' . get_the_title( intval( $_GET['course_id'] ) ); } ?><?php if ( isset( $_GET['lesson_id'] ) ) { echo '&nbsp;&nbsp;&gt;&nbsp;&nbsp;' . get_the_title( intval( $_GET['lesson_id'] ) ); } ?></h2>
			<p class="powered-by-woo"><?php _e( 'Powered by', 'woothemes-sensei' ); ?><a href="http://www.woothemes.com/" title="WooThemes"><img src="<?php echo $woothemes_sensei->plugin_url; ?>assets/images/woothemes.png" alt="WooThemes" /></a></p>
			<?php
	} // End learners_default_nav()

	public function get_redirect_url() {

		// Parse POST data
		$data = $_POST['data'];
		$course_data = array();
		parse_str( $data, $course_data );

		$course_cat = intval( $course_data['course_cat'] );

		$redirect_url = add_query_arg( array( 'page' => $this->page_slug, 'course_cat' => $course_cat ), admin_url( 'admin.php' ) );

		echo $redirect_url;
		die();
	}

	public function remove_user_from_post() {

		// Parse POST data
		$data = $_POST['data'];
		$action_data = array();
		parse_str( $data, $action_data );

		// echo '<pre>';print_r( $action_data );echo '</pre>';

		die('');
	}

} // End Class
?>