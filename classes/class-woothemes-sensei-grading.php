<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Grading Class
 *
 * All functionality pertaining to the Admin Grading in Sensei.
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
 * - analysis_admin_menu()
 */
class WooThemes_Sensei_Grading {
	public $token;
	public $name;
	public $file;

	/**
	 * Constructor
	 * @since  1.3.0
	 * @return  void
	 */
	public function __construct ( $file ) {
		$this->name = 'Grading';
		$this->file = $file;
		// Admin functions
		if ( is_admin() ) {
			add_action( 'admin_menu', array( &$this, 'grading_admin_menu' ), 10);
			add_action( 'admin_print_scripts', array( &$this, 'enqueue_scripts' ) );
			add_action( 'admin_print_styles', array( &$this, 'enqueue_styles' ) );
			add_action( 'grading_wrapper_container', array( &$this, 'wrapper_container'  ) );
		} // End If Statement
		// Ajax functions
		if ( is_admin() ) {
			add_action( 'wp_ajax_get_lessons_dropdown', array( &$this, 'get_lessons_dropdown' ) );
			add_action( 'wp_ajax_nopriv_get_lessons_dropdown', array( &$this, 'get_lessons_dropdown' ) );
			add_action( 'wp_ajax_get_lessons_html', array( &$this, 'get_lessons_html' ) );
			add_action( 'wp_ajax_nopriv_get_lessons_html', array( &$this, 'get_lessons_html' ) );
		} // End If Statement
	} // End __construct()

	/**
	 * grading_admin_menu function.
	 * @since  1.3.0
	 * @access public
	 * @return void
	 */
	public function grading_admin_menu() {
	    global $menu, $woocommerce;

	    if ( current_user_can( 'manage_options' ) )
	    	$analysis_page = add_submenu_page('edit.php?post_type=lesson', __('Grading', 'woothemes-sensei'),  __('Grading', 'woothemes-sensei') , 'manage_options', 'sensei_grading', array( &$this, 'grading_page' ) );

	} // End analysis_admin_menu()

	/**
	 * enqueue_scripts function.
	 *
	 * @description Load in JavaScripts where necessary.
	 * @access public
	 * @since 1.3.0
	 * @return void
	 */
	public function enqueue_scripts () {
		global $woothemes_sensei;
		// Load Grading JS
		wp_enqueue_script( 'woosensei-grading-general', $woothemes_sensei->plugin_url . 'assets/js/grading-general.js', array( 'jquery' ), '1.3.0' );

	} // End enqueue_scripts()

	/**
	 * enqueue_styles function.
	 *
	 * @description Load in CSS styles where necessary.
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_styles () {
		global $woothemes_sensei;
		wp_enqueue_style( $woothemes_sensei->token . '-admin' );

		wp_enqueue_style( 'woothemes-sensei-settings-api', $woothemes_sensei->plugin_url . 'assets/css/settings.css', '', '1.0.0' );

	} // End enqueue_styles()

	/**
	 * load_data_table_files loads required files for Grading
	 * @since  1.3.0
	 * @return void
	 */
	public function load_data_table_files() {
		global $woothemes_sensei;
		// Load Grading Classes
		$classes_to_load = array(	'list-table',
									'grading-overview',
									'grading-user-quiz'
									);
		foreach ( $classes_to_load as $class_file ) {
			$woothemes_sensei->load_class( $class_file );
		} // End For Loop
	} // End load_data_table_files()

	/**
	 * load_data_object creates new instance of class
	 * @since  1.3.0
	 * @param  string  $name          Name of class
	 * @param  integer $data          constructor arguments
	 * @param  undefined  $optional_data optional constructor arguments
	 * @return object                 class instance object
	 */
	public function load_data_object( $name = '', $data = 0, $optional_data = null ) {
		// Load Analysis data
		$object_name = 'WooThemes_Sensei_Grading_' . $name;
		if ( is_null($optional_data) ) {
			$sensei_grading_object = new $object_name( $data );
		} else {
			$sensei_grading_object = new $object_name( $data, $optional_data );
		}
		return $sensei_grading_object;
	} // End load_data_object()

	/**
	 * grading_page function.
	 * @since 1.3.0
	 * @access public
	 * @return void
	 */
	public function grading_page() {
		global $woothemes_sensei;
		if ( isset( $_GET['user'] ) && 0 < intval( $_GET['user'] ) && isset( $_GET['quiz_id'] ) && 0 < intval( $_GET['quiz_id'] ) ) {
			$this->grading_user_quiz_view();
		} else {
			$this->grading_default_view();
		} // End If Statement
	} // End analysis_page()

	/**
	 * grading_default_view default view for grading page
	 * @since  1.3.0
	 * @return void
	 */
	public function grading_default_view( $type = '' ) {
		global $woothemes_sensei;
		// Load Grading data
		$this->load_data_table_files();
		$sensei_grading_overview = $this->load_data_object( 'Overview' );
		// Wrappers
		do_action( 'grading_before_container' );
		do_action( 'grading_wrapper_container', 'top' );
		$this->grading_headers();
		?><div id="poststuff" class="sensei-grading-wrap">
				<div class="sensei-grading-main">
					<?php $sensei_grading_overview->display(); ?>
				</div>
			</div>
		<?php
		do_action( 'grading_wrapper_container', 'bottom' );
		do_action( 'grading_after_container' );
	} // End grading_default_view()

	/**
	 * grading_user_quiz_view user quiz answers view for grading page
	 * @since  1.2.0
	 * @return void
	 */
	public function grading_user_quiz_view() {
		global $woothemes_sensei;
		// Load Grading data
		$this->load_data_table_files();
		$sensei_grading_user_profile = $this->load_data_object( 'User_Quiz', intval( $_GET['user'] ), intval( $_GET['quiz_id'] ) );
		// Wrappers
		do_action( 'grading_before_container' );
		do_action( 'grading_wrapper_container', 'top' );
		$this->grading_headers( array( 'nav' => 'user_quiz' ) );
		?><div id="poststuff" class="sensei-grading-wrap user-profile">
				<div class="sensei-grading-main">
					<?php $sensei_grading_user_profile->display(); ?>
				</div>
			</div>
		<?php
		do_action( 'grading_wrapper_container', 'bottom' );
		do_action( 'grading_after_container' );
	} // End grading_user_quiz_view()

	/**
	 * analysis_headers outputs analysis general headers
	 * @since  1.3.0
	 * @return void
	 */
	public function grading_headers( $args = array( 'nav' => 'default' ) ) {
		$function = 'grading_' . $args['nav'] . '_nav';
		$this->$function();
	} // End grading_headers()

	/**
	 * wrapper_container wrapper for analysis area
	 * @since  1.3.0
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
	 * grading_default_nav default nav area for analysis
	 * @since  1.3.0
	 * @return void
	 */
	public function grading_default_nav() {
		global $woothemes_sensei;
		?><?php screen_icon( 'woothemes-sensei' ); ?>
			<h2><?php echo esc_html( $this->name ); ?><?php if ( isset( $_GET['course_id'] ) ) { echo '&nbsp;&nbsp;&gt;&nbsp;&nbsp;' . __( 'Courses', 'woothemes-sensei' ); } ?><?php if ( isset( $_GET['lesson_id'] ) ) { echo '&nbsp;&nbsp;&gt;&nbsp;&nbsp;' . __( 'Lessons', 'woothemes-sensei' ); } ?></h2>
			<p class="powered-by-woo"><?php _e( 'Powered by', 'woothemes-sensei' ); ?><a href="http://www.woothemes.com/" title="WooThemes"><img src="<?php echo $woothemes_sensei->plugin_url; ?>assets/images/woothemes.png" alt="WooThemes" /></a></p>
			<!-- <ul class="subsubsub">
				<li><a href="<?php echo add_query_arg( array( 'page' => 'sensei_grading' ), admin_url( 'edit.php?post_type=lesson' ) ); ?>" <?php if ( !isset( $_GET['course_id'] ) && !isset( $_GET['lesson_id'] ) ) { ?>class="current"<?php } ?>><?php _e( 'Overview', 'woothemes-sensei' ); ?></a></li>
				<li><a href="<?php echo add_query_arg( array( 'page' => 'sensei_grading', 'course_id' => -1 ), admin_url( 'edit.php?post_type=lesson' ) ); ?>" <?php if ( isset( $_GET['course_id'] ) ) { ?>class="current"<?php } ?>><?php _e( 'Courses', 'woothemes-sensei' ); ?></a></li>
				<li><a href="<?php echo add_query_arg( array( 'page' => 'sensei_grading', 'lesson_id' => -1 ), admin_url( 'edit.php?post_type=lesson' ) ); ?>" <?php if ( isset( $_GET['lesson_id'] ) ) { ?>class="current"<?php } ?>><?php _e( 'Lessons', 'woothemes-sensei' ); ?></a></li>
			</ul> -->
			<br class="clear"><?php
	} // End grading_default_nav()

	/**
	 * grading_user_quiz_nav nav area for grading user quiz answers
	 * @since  1.3.0
	 * @return void
	 */
	public function grading_user_quiz_nav() {
		global $woothemes_sensei;
		if ( isset( $_GET['user'] ) && 0 < intval( $_GET['user'] ) ) {
			$user_data = get_userdata( intval( $_GET['user'] ) );
			?><?php screen_icon( 'woothemes-sensei' ); ?>
			<h2><?php echo esc_html( $this->name ) . '&nbsp;&nbsp;&gt;&nbsp;&nbsp;' . $user_data->display_name; ?></h2>
			<p class="powered-by-woo"><?php _e( 'Powered by', 'woothemes-sensei' ); ?><a href="http://www.woothemes.com/" title="WooThemes"><img src="<?php echo $woothemes_sensei->plugin_url; ?>assets/images/woothemes.png" alt="WooThemes" /></a></p>
			<br class="clear"><?php
		} // End If Statement
	} // End grading_user_quiz_nav()

	public function get_lessons_dropdown() {

		$posts_array = array();

		// Parse POST data
		$data = $_POST['data'];
		$course_data = array();
		parse_str($data, $course_data);

		$course_id = intval( $course_data['course_id'] );

		$post_args = array(	'post_type' 		=> 'lesson',
							'numberposts' 		=> -1,
							'orderby'         	=> 'menu_order',
    						'order'           	=> 'ASC',
    						'meta_key'        	=> '_lesson_course',
    						'meta_value'      	=> $course_id,
    						'post_status'       => 'publish',
							'suppress_filters' 	=> 0
							);
		$posts_array = get_posts( $post_args );

		$html .= '<label>' . __( 'Select a Lesson to Grade', 'woothemes-sensei' ) . '</label>';

		// $html .= '<select id="grading-lesson-options" name="grading_lesson" class="widefat">' . "\n";
			$html .= '<option value="">' . __( 'None', 'woothemes-sensei' ) . '</option>';
			if ( count( $posts_array ) > 0 ) {
				foreach ($posts_array as $post_item){
					$html .= '<option value="' . esc_attr( absint( $post_item->ID ) ) . '">' . esc_html( $post_item->post_title ) . '</option>' . "\n";
				} // End For Loop
			} // End If Statement
		// $html .= '</select>' . "\n";
		echo $html;
		die(); // WordPress may print out a spurious zero without this can be particularly bad if using JSON
	}

	public function get_lessons_html() {

		$posts_array = array();

		// Parse POST data
		$data = $_POST['data'];
		$lesson_data = array();
		parse_str($data, $lesson_data);

		$lesson_id = intval( $lesson_data['lesson_id'] );

		$html = $lesson_id;

		echo $html;
		die(); // WordPress may print out a spurious zero without this can be particularly bad if using JSON
	}

} // End Class
?>