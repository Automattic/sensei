<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Analysis Class
 *
 * All functionality pertaining to the Admin Analysis in Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Core
 * @author WooThemes
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 * - analysis_admin_menu()
 * - load_data_table_files()
 * - analysis_page()
 * - enqueue_scripts()
 * - enqueue_styles()
 */
class WooThemes_Sensei_Analysis {
	public $token;
	public $name;
	public $file;

	/**
	 * Constructor
	 * @since  1.0.0
	 * @return  void
	 */
	public function __construct ( $file ) {

		$this->name = 'Analysis';
		$this->file = $file;
		// Admin functions
		if ( is_admin() ) {
			add_action( 'admin_menu', array( &$this, 'analysis_admin_menu' ), 10);
			add_action( 'admin_print_scripts', array( &$this, 'enqueue_scripts' ) );
			add_action( 'admin_print_styles', array( &$this, 'enqueue_styles' ) );
			add_action( 'analysis_wrapper_container', array( &$this, 'wrapper_container'  ) );
		} // End If Statement

	} // End __construct()


	/**
	 * analysis_admin_menu function.
	 *
	 * @access public
	 * @return void
	 */
	public function analysis_admin_menu() {
	    global $menu, $woocommerce;

	    if ( current_user_can( 'manage_options' ) )

	    $analysis_page = add_submenu_page('edit.php?post_type=lesson', __('Analysis', 'woothemes-sensei'),  __('Analysis', 'woothemes-sensei') , 'manage_options', 'sensei_analysis', array( &$this, 'analysis_page' ) );

	} // End analysis_admin_menu()

	/**
	 * load_data_table_files loads required files for Analysis
	 * @return void
	 */
	public function load_data_table_files() {
		global $woothemes_sensei;
		// Load Analysis Reports
		$woothemes_sensei->load_class( 'list-table' );
		$woothemes_sensei->load_class( 'analysis-overview' );
		$woothemes_sensei->load_class( 'analysis-user-profile' );
		$woothemes_sensei->load_class( 'analysis-course' );
	} // End load_data_table_files()

	/**
	 * analysis_page function.
	 *
	 * @access public
	 * @return void
	 */
	public function analysis_page() {
		global $woothemes_sensei;
		if ( isset( $_GET['user'] ) && 0 < intval( $_GET['user'] ) && isset( $_GET['course_id'] ) && 0 < intval( $_GET['course_id'] ) ) {
			$this->analysis_user_course_view();
		} elseif( isset( $_GET['course_id'] ) && 0 < intval( $_GET['course_id'] ) ) {
			$this->analysis_course_view();
		} elseif ( isset( $_GET['user'] ) && 0 < intval( $_GET['user'] ) ) {
			$this->analysis_user_profile_view();
		} else {
			$this->analysis_default_view();
		} // End If Statement
	} // End analysis_page()

	/**
	 * analysis_default_view default view for analysis page
	 * @since  1.1.3
	 * @return void
	 */
	public function analysis_default_view() {
		global $woothemes_sensei;
		// Load Analysis data
		$this->load_data_table_files();
		$sensei_analysis_overview = new WooThemes_Sensei_Analysis_Overview_List_Table();
		$sensei_analysis_overview->prepare_items();
		$sensei_analysis_overview->load_stats();
		// Wrappers
		do_action( 'analysis_before_container' );
		do_action( 'analysis_wrapper_container', 'top' );
		$this->analysis_headers();
		?><div id="poststuff" class="sensei-analysis-wrap">
				<div class="sensei-analysis-sidebar">
					<?php
					foreach ( $sensei_analysis_overview->stats_boxes() as $key => $value ) {
						$this->render_stats_box( esc_html( $key ), esc_html( $value ) );
					} // End For Loop
					?>
				</div>
				<div class="sensei-analysis-main">
					<?php $sensei_analysis_overview->display(); ?>
				</div>
			</div>
		<?php
		do_action( 'analysis_wrapper_container', 'bottom' );
		do_action( 'analysis_after_container' );
	} // End analysis_default_view()

	/**
	 * analysis_user_profile_view user profile view for analysis page
	 * @since  1.1.3
	 * @return void
	 */
	public function analysis_user_profile_view() {
		global $woothemes_sensei;
		// Load Analysis data
		$this->load_data_table_files();
		$sensei_analysis_user_profile = new WooThemes_Sensei_Analysis_User_Profile_List_Table( intval( $_GET['user'] ) );
		$sensei_analysis_user_profile->prepare_items();
		$sensei_analysis_user_profile->load_stats();
		// Wrappers
		do_action( 'analysis_before_container' );
		do_action( 'analysis_wrapper_container', 'top' );
		$this->analysis_headers( array( 'nav' => 'user_profile' ) );
		?><div id="poststuff" class="sensei-analysis-wrap user-profile">
				<div class="sensei-analysis-main">
					<?php $sensei_analysis_user_profile->display(); ?>
				</div>
			</div>
		<?php
		do_action( 'analysis_wrapper_container', 'bottom' );
		do_action( 'analysis_after_container' );
	} // End analysis_user_profile_view()

	/**
	 * analysis_course_view individual course view for analysis page
	 * @since  1.1.3
	 * @return void
	 */
	public function analysis_course_view() {
		global $woothemes_sensei;
		// Load Analysis data
		$this->load_data_table_files();
		$sensei_analysis_course = new WooThemes_Sensei_Analysis_Course_List_Table( intval( $_GET['course_id'] ) );
		$sensei_analysis_course->prepare_items();
		$sensei_analysis_course->load_stats();
		// Wrappers
		do_action( 'analysis_before_container' );
		do_action( 'analysis_wrapper_container', 'top' );
		$this->analysis_headers( array( 'nav' => 'course' ) );
		?><div id="poststuff" class="sensei-analysis-wrap course-profile">
				<div class="sensei-analysis-main">
					<?php $sensei_analysis_course->display(); ?>
				</div>
			</div>
		<?php
		do_action( 'analysis_wrapper_container', 'bottom' );
		do_action( 'analysis_after_container' );
	} // End analysis_course_view()

	/**
	 * analysis_user_course_view user individual course view for analysis page
	 * @since  1.1.3
	 * @return void
	 */
	public function analysis_user_course_view() {
		global $woothemes_sensei;
		// Load Analysis data
		$this->load_data_table_files();
		$sensei_analysis_user_course = new WooThemes_Sensei_Analysis_Course_List_Table( intval( $_GET['course_id'] ), intval( $_GET['user'] ) );
		$sensei_analysis_user_course->prepare_items();
		$sensei_analysis_user_course->load_stats();
		// Wrappers
		do_action( 'analysis_before_container' );
		do_action( 'analysis_wrapper_container', 'top' );
		$this->analysis_headers( array( 'nav' => 'user_course' ) );
		?><div id="poststuff" class="sensei-analysis-wrap course-profile">
				<div class="sensei-analysis-main">
					<?php $sensei_analysis_user_course->display(); ?>
				</div>
			</div>
		<?php
		do_action( 'analysis_wrapper_container', 'bottom' );
		do_action( 'analysis_after_container' );
	} // End analysis_user_course_view()

	/**
	 * enqueue_scripts function.
	 *
	 * @description Load in JavaScripts where necessary.
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts () {
		global $woothemes_sensei;
		// None for now

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
	 * render_stats_box outputs stats boxes
	 * @since  1.1.3
	 * @param  $title string title of stat
	 * @param  $data string stats data
	 * @return void
	 */
	public function render_stats_box( $title, $data ) {
		?><div class="postbox">
			<h3><span><?php echo $title; ?></span></h3>
			<div class="inside">
				<p class="stat"><?php echo $data; ?></p>
			</div>
		</div><?php
	} // End render_stats_box()

	/**
	 * analysis_headers outputs analysis general headers
	 * @since  1.1.3
	 * @return void
	 */
	public function analysis_headers( $args = array( 'nav' => 'default' ) ) {
		$function = 'analysis_' . $args['nav'] . '_nav';
		call_user_method( $function, $this );
	} // End analysis_headers()

	/**
	 * wrapper_container wrapper for analysis area
	 * @since  1.1.3
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

	public function analysis_default_nav() {
		global $woothemes_sensei;
		?><?php screen_icon( 'woothemes-sensei' ); ?>
			<h2><?php echo esc_html( $this->name ); ?></h2>
			<p class="powered-by-woo"><?php _e( 'Powered by', 'woothemes-sensei' ); ?><a href="http://www.woothemes.com/" title="WooThemes"><img src="<?php echo $woothemes_sensei->plugin_url; ?>assets/images/woothemes.png" alt="WooThemes" /></a></p>
			<ul class="subsubsub">
				<li><a href="admin.php?page=sensei_analysis" class="current"><?php _e( 'Overview', 'woothemes-sensei' ); ?></a></li>
			</ul>
			<br class="clear"><?php
	}

	public function analysis_user_profile_nav() {
		global $woothemes_sensei;
		if ( isset( $_GET['user'] ) && 0 < intval( $_GET['user'] ) ) {
			$user_data = get_userdata( $_GET['user']  );
			?><?php screen_icon( 'woothemes-sensei' ); ?>
			<h2><?php echo esc_html( $this->name ) . '&nbsp;&nbsp;&gt;&nbsp;&nbsp;' . $user_data->display_name; ?></h2>
			<p class="powered-by-woo"><?php _e( 'Powered by', 'woothemes-sensei' ); ?><a href="http://www.woothemes.com/" title="WooThemes"><img src="<?php echo $woothemes_sensei->plugin_url; ?>assets/images/woothemes.png" alt="WooThemes" /></a></p>
			<br class="clear"><?php
		} // End If Statement
	}

	public function analysis_user_course_nav() {
		global $woothemes_sensei;
		if ( isset( $_GET['user'] ) && 0 < intval( $_GET['user'] ) ) {
			$user_data = get_userdata( $_GET['user']  );
			?><?php screen_icon( 'woothemes-sensei' ); ?>
			<h2><?php echo esc_html( $this->name ) . '&nbsp;&nbsp;&gt;&nbsp;&nbsp;' . $user_data->display_name . '&nbsp;&nbsp;&gt;&nbsp;&nbsp;' . get_the_title( intval( $_GET['course_id'] ) ); ?></h2>
			<p class="powered-by-woo"><?php _e( 'Powered by', 'woothemes-sensei' ); ?><a href="http://www.woothemes.com/" title="WooThemes"><img src="<?php echo $woothemes_sensei->plugin_url; ?>assets/images/woothemes.png" alt="WooThemes" /></a></p>
			<br class="clear"><?php
		} // End If Statement
	}

	public function analysis_course_nav() {
		global $woothemes_sensei;
		if ( isset( $_GET['course_id'] ) && 0 < intval( $_GET['course_id'] ) ) {
			?><?php screen_icon( 'woothemes-sensei' ); ?>
			<h2><?php echo esc_html( $this->name ) . '&nbsp;&nbsp;&gt;&nbsp;&nbsp;' . get_the_title( intval( $_GET['course_id'] ) ); ?></h2>
			<p class="powered-by-woo"><?php _e( 'Powered by', 'woothemes-sensei' ); ?><a href="http://www.woothemes.com/" title="WooThemes"><img src="<?php echo $woothemes_sensei->plugin_url; ?>assets/images/woothemes.png" alt="WooThemes" /></a></p>
			<br class="clear"><?php
		} // End If Statement
	}

} // End Class
?>