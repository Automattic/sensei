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
 * - analysis_default_view()
 * - analysis_user_profile_view()
 * - analysis_course_view()
 * - analysis_user_course_view()
 * - analysis_course_users_view()
 * - analysis_lesson_users_view()
 * - load_data_object()
 * - enqueue_scripts()
 * - enqueue_styles()
 * - render_stats_box()
 * - analysis_headers()
 * - analysis_default_nav()
 * - analysis_user_profile_nav()
 * - analysis_user_course_nav()
 * - analysis_course_nav()
 * - analysis_course_users_nav()
 * - analysis_lesson_users_nav()
 * - report_download_page()
 * - report_set_headers()
 * - report_load_object()
 * - report_write_download()
 * - user_search_columns_filter()
 */
class WooThemes_Sensei_Analysis {
	public $token;
	public $name;
	public $file;
	public $page_slug;

	/**
	 * Constructor
	 * @since  1.0.0
	 * @return  void
	 */
	public function __construct ( $file ) {
		$this->name = 'Analysis';
		$this->file = $file;
		$this->page_slug = 'sensei_analysis';

		// Admin functions
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'analysis_admin_menu' ), 10);
			add_action( 'analysis_wrapper_container', array( $this, 'wrapper_container'  ) );
			add_action( 'admin_init', array( $this, 'report_download_page' ) );
			if ( isset( $_GET['page'] ) && ( $_GET['page'] == $this->page_slug ) ) {
				add_action( 'admin_print_scripts', array( $this, 'enqueue_scripts' ) );
				add_action( 'admin_print_styles', array( $this, 'enqueue_styles' ) );
			}
			add_filter( 'user_search_columns', array( $this, 'user_search_columns_filter' ), 10, 3 );
		} // End If Statement
	} // End __construct()


	/**
	 * analysis_admin_menu function.
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function analysis_admin_menu() {
	    global $menu, $woocommerce;

	    if ( current_user_can( 'manage_sensei_grades' ) ) {
	    	$analysis_page = add_submenu_page( 'sensei', __('Analysis', 'woothemes-sensei'),  __('Analysis', 'woothemes-sensei') , 'manage_sensei_grades', 'sensei_analysis', array( $this, 'analysis_page' ) );
	    }

	} // End analysis_admin_menu()

	/**
	 * load_data_table_files loads required files for Analysis
	 * @since  1.2.0
	 * @return void
	 */
	public function load_data_table_files() {
		global $woothemes_sensei;
		// Load Analysis Classes
		$classes_to_load = array(	'list-table',
									'analysis-overview',
									'analysis-user-profile',
									'analysis-course',
									'analysis-lesson'
									);
		foreach ( $classes_to_load as $class_file ) {
			$woothemes_sensei->load_class( $class_file );
		} // End For Loop
	} // End load_data_table_files()

	/**
	 * analysis_page function.
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function analysis_page() {
		global $woothemes_sensei;
		if ( isset( $_GET['lesson_id'] ) && 0 < intval( $_GET['lesson_id'] ) ) {
			$this->analysis_lesson_users_view();
		} elseif ( isset( $_GET['user'] ) && -1 == intval( $_GET['user'] ) && isset( $_GET['course_id'] ) && 0 < intval( $_GET['course_id'] ) ) {
			$this->analysis_course_users_view();
		} elseif ( isset( $_GET['user'] ) && 0 < intval( $_GET['user'] ) && isset( $_GET['course_id'] ) && 0 < intval( $_GET['course_id'] ) ) {
			$this->analysis_user_course_view();
		} elseif( isset( $_GET['course_id'] ) && 0 < intval( $_GET['course_id'] ) ) {
			$this->analysis_course_view();
		} elseif ( isset( $_GET['user'] ) && 0 < intval( $_GET['user'] ) ) {
			$this->analysis_user_profile_view();
		} elseif( isset( $_GET['course_id'] ) && -1 == intval( $_GET['course_id'] ) ) {
			$this->analysis_default_view( 'courses' );
		} elseif( isset( $_GET['lesson_id'] ) && -1 == intval( $_GET['lesson_id'] ) ) {
			$this->analysis_default_view( 'lessons' );
		} else {
			$this->analysis_default_view();
		} // End If Statement
	} // End analysis_page()

	/**
	 * analysis_default_view default view for analysis page
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_default_view( $type = '' ) {
		global $woothemes_sensei;
		// Load Analysis data
		$this->load_data_table_files();
		$sensei_analysis_overview = $this->load_data_object( 'Overview', $type );
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
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_user_profile_view() {
		global $woothemes_sensei;
		// Load Analysis data
		$this->load_data_table_files();
		$sensei_analysis_user_profile = $this->load_data_object( 'User_Profile', intval( $_GET['user'] ) );
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
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_course_view() {
		global $woothemes_sensei;
		// Load Analysis data
		$this->load_data_table_files();
		$sensei_analysis_course = $this->load_data_object( 'Course', intval( $_GET['course_id'] ) );
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
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_user_course_view() {
		global $woothemes_sensei;
		// Load Analysis data
		$this->load_data_table_files();
		$sensei_analysis_user_course = $this->load_data_object( 'Course', intval( $_GET['course_id'] ), intval( $_GET['user'] ) );
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
	 * analysis_course_users_view user individual course view for analysis page
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_course_users_view() {
		global $woothemes_sensei;
		// Load Analysis data
		$this->load_data_table_files();
		$sensei_analysis_course_users = $this->load_data_object( 'Course', intval( $_GET['course_id'] ), -1 );
		// Wrappers
		do_action( 'analysis_before_container' );
		do_action( 'analysis_wrapper_container', 'top' );
		$this->analysis_headers( array( 'nav' => 'course_users' ) );
		?><div id="poststuff" class="sensei-analysis-wrap course-profile">
				<div class="sensei-analysis-main">
					<?php $sensei_analysis_course_users->display(); ?>
				</div>
			</div>
		<?php
		do_action( 'analysis_wrapper_container', 'bottom' );
		do_action( 'analysis_after_container' );
	} // End analysis_course_users_view()

	/**
	 * analysis_lesson_users_view user individual course view for analysis page
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_lesson_users_view() {
		global $woothemes_sensei;
		// Load Analysis data
		$this->load_data_table_files();
		$sensei_analysis_lesson_users = $this->load_data_object( 'Lesson', intval( $_GET['lesson_id'] ) );
		// Wrappers
		do_action( 'analysis_before_container' );
		do_action( 'analysis_wrapper_container', 'top' );
		$this->analysis_headers( array( 'nav' => 'lesson_users' ) );
		?><div id="poststuff" class="sensei-analysis-wrap course-profile">
				<div class="sensei-analysis-main">
					<?php $sensei_analysis_lesson_users->display(); ?>
				</div>
			</div>
		<?php
		do_action( 'analysis_wrapper_container', 'bottom' );
		do_action( 'analysis_after_container' );
	} // End analysis_lesson_users_view()

	/**
	 * load_data_object creates new instance of class
	 * @param  string  $name          Name of class
	 * @param  integer $data          constructor arguments
	 * @param  undefined  $optional_data optional constructor arguments
	 * @return object                 class instance object
	 */
	public function load_data_object( $name = '', $data = 0, $optional_data = null ) {
		// Load Analysis data
		$object_name = 'WooThemes_Sensei_Analysis_' . $name . '_List_Table';
		if ( is_null($optional_data) ) {
			$sensei_analysis_object = new $object_name( $data );
		} else {
			$sensei_analysis_object = new $object_name( $data, $optional_data );
		}
		$sensei_analysis_object->prepare_items();
		$sensei_analysis_object->load_stats();
		return $sensei_analysis_object;
	} // End load_data_object()

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

		wp_enqueue_style( 'woothemes-sensei-settings-api', $woothemes_sensei->plugin_url . 'assets/css/settings.css', '', '1.6.0' );

	} // End enqueue_styles()

	/**
	 * render_stats_box outputs stats boxes
	 * @since  1.2.0
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
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_headers( $args = array( 'nav' => 'default' ) ) {
		$function = 'analysis_' . $args['nav'] . '_nav';
		$this->$function();
	} // End analysis_headers()

	/**
	 * wrapper_container wrapper for analysis area
	 * @since  1.2.0
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
	 * analysis_default_nav default nav area for analysis
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_default_nav() {
		global $woothemes_sensei;
		?><?php screen_icon( 'woothemes-sensei' ); ?>
			<h2><?php echo esc_html( $this->name ); ?><?php if ( isset( $_GET['course_id'] ) ) { echo '&nbsp;&nbsp;&gt;&nbsp;&nbsp;' . __( 'Courses', 'woothemes-sensei' ); } ?><?php if ( isset( $_GET['lesson_id'] ) ) { echo '&nbsp;&nbsp;&gt;&nbsp;&nbsp;' . __( 'Lessons', 'woothemes-sensei' ); } ?></h2>
			<p class="powered-by-woo"><?php _e( 'Powered by', 'woothemes-sensei' ); ?><a href="http://www.woothemes.com/" title="WooThemes"><img src="<?php echo $woothemes_sensei->plugin_url; ?>assets/images/woothemes.png" alt="WooThemes" /></a></p>
			<ul class="subsubsub">
				<li><a href="<?php echo add_query_arg( array( 'page' => 'sensei_analysis' ), admin_url( 'admin.php' ) ); ?>" <?php if ( !isset( $_GET['course_id'] ) && !isset( $_GET['lesson_id'] ) ) { ?>class="current"<?php } ?>><?php _e( 'Overview', 'woothemes-sensei' ); ?></a> |</li>
				<li><a href="<?php echo add_query_arg( array( 'page' => 'sensei_analysis', 'course_id' => -1 ), admin_url( 'admin.php' ) ); ?>" <?php if ( isset( $_GET['course_id'] ) ) { ?>class="current"<?php } ?>><?php _e( 'Courses', 'woothemes-sensei' ); ?></a> |</li>
				<li><a href="<?php echo add_query_arg( array( 'page' => 'sensei_analysis', 'lesson_id' => -1 ), admin_url( 'admin.php' ) ); ?>" <?php if ( isset( $_GET['lesson_id'] ) ) { ?>class="current"<?php } ?>><?php _e( 'Lessons', 'woothemes-sensei' ); ?></a></li>
			</ul>
			<br class="clear"><?php
	} // End analysis_default_nav()

	/**
	 * analysis_user_profile_nav nav area for analysis user profile
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_user_profile_nav() {
		global $woothemes_sensei;
		if ( isset( $_GET['user'] ) && 0 < intval( $_GET['user'] ) ) {
			$user_data = get_userdata( intval( $_GET['user'] ) );
			?><?php screen_icon( 'woothemes-sensei' ); ?>
			<h2><?php echo esc_html( $this->name ) . '&nbsp;&nbsp;&gt;&nbsp;&nbsp;' . $user_data->display_name; ?></h2>
			<p class="powered-by-woo"><?php _e( 'Powered by', 'woothemes-sensei' ); ?><a href="http://www.woothemes.com/" title="WooThemes"><img src="<?php echo $woothemes_sensei->plugin_url; ?>assets/images/woothemes.png" alt="WooThemes" /></a></p>
			<br class="clear"><?php
		} // End If Statement
	} // End analysis_user_profile_nav()

	/**
	 * analysis_user_course_nav nav area for analysis user course
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_user_course_nav() {
		global $woothemes_sensei;
		if ( isset( $_GET['user'] ) && 0 < intval( $_GET['user'] ) ) {
			$user_data = get_userdata( intval( $_GET['user'] ) );
			?><?php screen_icon( 'woothemes-sensei' ); ?>
			<h2><?php echo esc_html( $this->name ) . '&nbsp;&nbsp;&gt;&nbsp;&nbsp;' . $user_data->display_name . '&nbsp;&nbsp;&gt;&nbsp;&nbsp;' . get_the_title( intval( $_GET['course_id'] ) ); ?></h2>
			<p class="powered-by-woo"><?php _e( 'Powered by', 'woothemes-sensei' ); ?><a href="http://www.woothemes.com/" title="WooThemes"><img src="<?php echo $woothemes_sensei->plugin_url; ?>assets/images/woothemes.png" alt="WooThemes" /></a></p>
			<br class="clear"><?php
		} // End If Statement
	} // End analysis_user_course_nav()

	/**
	 * analysis_course_nav nav area for analysis courses
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_course_nav() {
		global $woothemes_sensei;
		if ( isset( $_GET['course_id'] ) && 0 < intval( $_GET['course_id'] ) ) {
			?><?php screen_icon( 'woothemes-sensei' ); ?>
			<h2><?php echo esc_html( $this->name ) . '&nbsp;&nbsp;&gt;&nbsp;&nbsp;' . get_the_title( intval( $_GET['course_id'] ) ); ?></h2>
			<p class="powered-by-woo"><?php _e( 'Powered by', 'woothemes-sensei' ); ?><a href="http://www.woothemes.com/" title="WooThemes"><img src="<?php echo $woothemes_sensei->plugin_url; ?>assets/images/woothemes.png" alt="WooThemes" /></a></p>
			<br class="clear"><?php
		} // End If Statement
	} // End analysis_course_nav()

	/**
	 * analysis_course_users_nav nav area for analysis course users
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_course_users_nav() {
		global $woothemes_sensei;
		if ( isset( $_GET['course_id'] ) && 0 < intval( $_GET['course_id'] ) ) {
			?><?php screen_icon( 'woothemes-sensei' ); ?>
			<h2><?php echo esc_html( $this->name ) . '&nbsp;&nbsp;&gt;&nbsp;&nbsp;' . get_the_title( intval( $_GET['course_id'] ) ); ?></h2>
			<p class="powered-by-woo"><?php _e( 'Powered by', 'woothemes-sensei' ); ?><a href="http://www.woothemes.com/" title="WooThemes"><img src="<?php echo $woothemes_sensei->plugin_url; ?>assets/images/woothemes.png" alt="WooThemes" /></a></p>
			<br class="clear"><?php
		} // End If Statement
	} // End analysis_course_users_nav()

	/**
	 * analysis_lesson_users_nav nav area for analysis lesson users
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_lesson_users_nav() {
		global $woothemes_sensei;
		if ( isset( $_GET['lesson_id'] ) && 0 < intval( $_GET['lesson_id'] ) ) {
			$course_id = intval( get_post_meta( intval( $_GET['lesson_id'] ), '_lesson_course', true ) );
			$course_title = '';
			if ( 0 < $course_id ) {
				$course_title = '&nbsp;&nbsp;&gt;&nbsp;&nbsp;' . get_the_title( $course_id );
			} // End If Statement
			?><?php screen_icon( 'woothemes-sensei' ); ?>
			<h2><?php echo esc_html( $this->name ) . $course_title . '&nbsp;&nbsp;&gt;&nbsp;&nbsp;' . get_the_title( intval( $_GET['lesson_id'] ) ); ?></h2>
			<p class="powered-by-woo"><?php _e( 'Powered by', 'woothemes-sensei' ); ?><a href="http://www.woothemes.com/" title="WooThemes"><img src="<?php echo $woothemes_sensei->plugin_url; ?>assets/images/woothemes.png" alt="WooThemes" /></a></p>
			<br class="clear"><?php
		} // End If Statement
	} // End analysis_lesson_users_nav()

	/**
	 * report_download_page handles CSV export request
	 * @since  1.2.0
	 * @return void
	 */
    public function report_download_page() {
        // Check if is a report
        if ( isset( $_GET['report_id'] ) && '' != $_GET['report_id'] ) {
        	switch ( esc_html( $_GET['report_id'] ) ) {
				case 'courses-overview':
				case 'lessons-overview':
				case 'user-overview':
					$header_setting = esc_html( $_GET['report_id'] );
					$report_object_setting = 'Overview';
				break;
				default :
				break;
			} // End Switch Statement
			// Handles headers and generates download file
			if ( '' != $header_setting && '' != $report_object_setting ) {
				$this->report_set_headers( $header_setting );
				$report_array = $this->report_load_object( $report_object_setting );
				$this->report_write_download( $report_array );
            } // End If Statement
            exit;
        } // End If Statement
    } // End report_download_page()

    /**
     * report_set_headers sets headers for reporting export
     * @since  1.2.0
     * @param  string $filename name of report file
     * @return void
     */
    public function report_set_headers( $filename = '' ) {
    	header( 'Content-Type: text/csv' );
        header( 'Content-Disposition: attachment;filename=' . $filename . '.csv');
    } // End report_set_headers()

    /**
     * report_load_object loads the right object for reporting
     * @since  1.2.0
     * @param  string $type object name
     * @return array data array for reporting output
     */
    public function report_load_object( $type = '' ) {
    	$report_array = array();
    	if ( '' != $type ) {
    		$this->load_data_table_files();
    		$class_name = 'WooThemes_Sensei_Analysis_' . $type . '_List_Table';
			$sensei_analysis_overview_report = new $class_name();
			switch ( esc_html( $_GET['report_id'] ) ) {
				case 'courses-overview':
					$sensei_analysis_overview_report->type = 'courses';
				break;
				case 'lessons-overview':
					$sensei_analysis_overview_report->type = 'lessons';
				break;
				default :
				break;
			} // End Switch Statement
			$report_array = $sensei_analysis_overview_report->build_data_array( true );
    	} // End If Statement
    	return $report_array;
    } // End report_load_object()

    /**
     * report_write_download write array data to CSV
     * @since  1.2.0
     * @param  array  $report_array data array
     * @return void
     */
    public function report_write_download( $report_array = array() ) {
    	$fp = fopen('php://output', 'w');
        foreach ($report_array as $row) {
            fputcsv($fp, $row);
        } // End For Loop
        fclose($fp);
    } // End report_write_download()

    /**
	 * user_search_columns_filter adds display_name to the default list of search columns for the WP User Object
	 * @since  1.4.5
	 * @param  array $search_columns 	array of default user columns to search
	 * @param  string $search 			search string
	 * @param  object $user_object    	WordPress User Object
	 * @return array $search_columns 	array of user columns to search
	 */
	public function user_search_columns_filter( $search_columns, $search, $user_object ) {
	    // Alter $search_columns to include the fields you want to search on
	    array_push( $search_columns, 'display_name' );
	    return $search_columns;
	}

} // End Class
?>