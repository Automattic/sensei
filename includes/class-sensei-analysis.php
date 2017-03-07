<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * All functionality pertaining to the Admin Analysis in Sensei.
 *
 * @package Analytics
 * @author Automattic
 * @since 1.0.0
 */
class Sensei_Analysis {

	public $name;
	public $file;
	public $page_slug;

	/**
	 * Constructor
	 * @since  1.0.0
	 * @param string $file
	 */
	public function __construct ( $file ) {
		$this->name = __('Analysis', 'woothemes-sensei');
		$this->file = $file;
		$this->page_slug = 'sensei_analysis';

		// Admin functions
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'analysis_admin_menu' ), 10);
			add_action( 'analysis_wrapper_container', array( $this, 'wrapper_container'  ) );
			if ( isset( $_GET['page'] ) && ( $_GET['page'] == $this->page_slug ) ) {

				add_action( 'admin_print_styles', array( $this, 'enqueue_styles' ) );

			}

			add_action( 'admin_init', array( $this, 'report_download_page' ) );

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

            add_submenu_page( 'sensei', __('Analysis', 'woothemes-sensei'),  __('Analysis', 'woothemes-sensei') , 'manage_sensei_grades', 'sensei_analysis', array( $this, 'analysis_page' ) );

		}

	} // End analysis_admin_menu()

	/**
	 * enqueue_styles function.
	 *
	 * @description Load in CSS styles where necessary.
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_styles () {

		wp_enqueue_style( 'woothemes-sensei-admin' );

		wp_enqueue_style( 'woothemes-sensei-settings-api', Sensei()->plugin_url . 'assets/css/settings.css', '', Sensei()->version );

	} // End enqueue_styles()

	/**
	 * load_data_table_files loads required files for Analysis
	 * @since  1.2.0
	 * @return void
	 */
	public function load_data_table_files() {

		// Load Analysis Classes
		$classes_to_load = array(	'list-table',
									'analysis-overview',
									'analysis-user-profile',
									'analysis-course',
									'analysis-lesson'
									);
		foreach ( $classes_to_load as $class_file ) {
            Sensei()->load_class( $class_file );
		} // End For Loop
	} // End load_data_table_files()

	/**
	 * load_data_object creates new instance of class
	 * @param  string  $name          Name of class
	 * @param  integer $data          constructor arguments
	 * @param  undefined  $optional_data optional constructor arguments
	 * @return object                 class instance object
	 */
	public function load_data_object( $name = '', $data = 0, $optional_data = null ) {
		// Load Analysis data
		$object_name = 'Sensei_Analysis_' . $name . '_List_Table';
		if ( is_null($optional_data) ) {
			$sensei_analysis_object = new $object_name( $data );
		} else {
			$sensei_analysis_object = new $object_name( $data, $optional_data );
		}
		$sensei_analysis_object->prepare_items();
		return $sensei_analysis_object;
	} // End load_data_object()

	/**
	 * analysis_page function.
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function analysis_page() {

		$course_id = 0;
		$lesson_id = 0;
		$user_id = 0;
		if( isset( $_GET['course_id'] ) ) {
			$course_id = intval( $_GET['course_id'] );
		}
		if( isset( $_GET['lesson_id'] ) ) {
			$lesson_id = intval( $_GET['lesson_id'] );
		}
		if( isset( $_GET['user_id'] ) ) {
			$user_id = intval( $_GET['user_id'] );
		}
		$type = isset( $_GET['view'] ) ? esc_html( $_GET['view'] ) : false;

		if ( 0 < $lesson_id ) {
			// Viewing a specific Lesson and all its Learners
			$this->analysis_lesson_users_view( $lesson_id );
		}
		elseif ( 0 < $course_id && !$user_id && 'user' == $type ) {
			// Viewing a specific Course and all its Learners
			$this->analysis_course_users_view( $course_id );
		}
		elseif ( 0 < $course_id && 0 < $user_id ) {
			// Viewing a specific Learner on a specific Course, showing their Lessons
			$this->analysis_user_course_view( $course_id, $user_id );
		}
		elseif( 0 < $course_id ) {
			// Viewing a specific Course and all it's Lessons
			$this->analysis_course_view( $course_id );
		}
		elseif ( 0 < $user_id ) {
			// Viewing a specific Learner, and their Courses
			$this->analysis_user_profile_view( $user_id );
		}
		else {
			// Overview of all Learners, all Courses, or all Lessons
			$this->analysis_default_view( $type );
		} // End If Statement
	} // End analysis_page()

	/**
	 * Default view for analysis, showing an overview of all Learners, Courses and Lessons
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_default_view( $type ) {

		// Load Analysis data
		$sensei_analysis_overview = $this->load_data_object( 'Overview', $type );
		// Wrappers
		do_action( 'analysis_before_container' );
		do_action( 'analysis_wrapper_container', 'top' );
		$this->analysis_headers();
		?>
		<div id="poststuff" class="sensei-analysis-wrap">
			<div class="sensei-analysis-sidebar">
				<?php
				do_action( 'sensei_analysis_before_stats_boxes' );
				foreach ( $sensei_analysis_overview->stats_boxes() as $key => $value ) {
					$this->render_stats_box( esc_html( $key ), esc_html( $value ) );
				} // End For Loop
				do_action( 'sensei_analysis_after_stats_boxes' );
				?>
			</div>
			<div class="sensei-analysis-main">
				<?php $sensei_analysis_overview->display(); ?>
			</div>
			<div class="sensei-analysis-extra">
				<?php do_action( 'sensei_analysis_extra' ); ?>
			</div>
		</div>
		<?php
		do_action( 'analysis_wrapper_container', 'bottom' );
		do_action( 'analysis_after_container' );
	} // End analysis_default_view()

	/**
	 * An individual users' profile view for analysis, showing their Courses
	 * @since  1.2.0
	 * @param integer $user_id
	 * @return void
	 */
	public function analysis_user_profile_view( $user_id ) {

		// Load Analysis data
		$sensei_analysis_user_profile = $this->load_data_object( 'User_Profile', $user_id );
		// Wrappers
		do_action( 'analysis_before_container' );
		do_action( 'analysis_wrapper_container', 'top' );
		$this->analysis_headers( array( 'nav' => 'user_profile' ) );
		?>
		<div id="poststuff" class="sensei-analysis-wrap user-profile">
			<div class="sensei-analysis-main">
				<?php $sensei_analysis_user_profile->display(); ?>
			</div>
			<div class="sensei-analysis-extra">
				<?php do_action( 'sensei_analysis_extra' ); ?>
			</div>
		</div>
		<?php
		do_action( 'analysis_wrapper_container', 'bottom' );
		do_action( 'analysis_after_container' );
	} // End analysis_user_profile_view()

	/**
	 * An individual Course view for analysis, showing the Courses Lessons
	 * @since  1.2.0
	 * @param integer $course_id
	 * @return void
	 */
	public function analysis_course_view( $course_id ) {

		// Load Analysis data
		$sensei_analysis_course = $this->load_data_object( 'Course', $course_id );
		// Wrappers
		do_action( 'analysis_before_container' );
		do_action( 'analysis_wrapper_container', 'top' );
		$this->analysis_headers( array( 'nav' => 'course' ) );
		?>
		<div id="poststuff" class="sensei-analysis-wrap course-profile">
			<div class="sensei-analysis-main">
				<?php $sensei_analysis_course->display(); ?>
			</div>
			<div class="sensei-analysis-extra">
				<?php do_action( 'sensei_analysis_extra' ); ?>
			</div>
		</div>
		<?php
		do_action( 'analysis_wrapper_container', 'bottom' );
		do_action( 'analysis_after_container' );
	} // End analysis_course_view()

	/**
	 * An individual Course view for analysis, showing a specific Learners Lessons
	 * @since  1.2.0
	 * @param integer $course_id
	 * @param integer $user_id
	 * @return void
	 */
	public function analysis_user_course_view( $course_id, $user_id ) {

		// Load Analysis data
		$sensei_analysis_user_course = $this->load_data_object( 'Course', $course_id, $user_id );
		// Wrappers
		do_action( 'analysis_before_container' );
		do_action( 'analysis_wrapper_container', 'top' );
		$this->analysis_headers( array( 'nav' => 'user_course' ) );
		?>
		<div id="poststuff" class="sensei-analysis-wrap course-profile">
			<div class="sensei-analysis-main">
				<?php $sensei_analysis_user_course->display(); ?>
			</div>
			<div class="sensei-analysis-extra">
				<?php do_action( 'sensei_analysis_extra' ); ?>
			</div>
		</div>
		<?php
		do_action( 'analysis_wrapper_container', 'bottom' );
		do_action( 'analysis_after_container' );
	} // End analysis_user_course_view()

	/**
	 * An individual Course view for analysis, showing all the Learners
	 * @since  1.2.0
	 * @param integer $course_id
	 * @return void
	 */
	public function analysis_course_users_view( $course_id ) {

		// Load Analysis data
		$sensei_analysis_course_users = $this->load_data_object( 'Course', $course_id );
		// Wrappers
		do_action( 'analysis_before_container' );
		do_action( 'analysis_wrapper_container', 'top' );
		$this->analysis_headers( array( 'nav' => 'course_users' ) );
		?>
		<div id="poststuff" class="sensei-analysis-wrap course-profile">
			<div class="sensei-analysis-main">
				<?php $sensei_analysis_course_users->display(); ?>
			</div>
			<div class="sensei-analysis-extra">
				<?php do_action( 'sensei_analysis_extra' ); ?>
			</div>
		</div>
		<?php
		do_action( 'analysis_wrapper_container', 'bottom' );
		do_action( 'analysis_after_container' );
	} // End analysis_course_users_view()

	/**
	 * An individual Lesson view for analysis, showing all the Learners
	 * @since  1.2.0
	 * @param integer $lesson_id
	 * @return void
	 */
	public function analysis_lesson_users_view( $lesson_id ) {

		// Load Analysis data
		$sensei_analysis_lesson_users = $this->load_data_object( 'Lesson', $lesson_id );
		// Wrappers
		do_action( 'analysis_before_container' );
		do_action( 'analysis_wrapper_container', 'top' );
		$this->analysis_headers( array( 'nav' => 'lesson_users' ) );
		?>
		<div id="poststuff" class="sensei-analysis-wrap course-profile">
			<div class="sensei-analysis-main">
				<?php $sensei_analysis_lesson_users->display(); ?>
			</div>
			<div class="sensei-analysis-extra">
				<?php do_action( 'sensei_analysis_extra' ); ?>
			</div>
		</div>
		<?php
		do_action( 'analysis_wrapper_container', 'bottom' );
		do_action( 'analysis_after_container' );
	} // End analysis_lesson_users_view()

	/**
	 * render_stats_box outputs stats boxes
	 * @since  1.2.0
	 * @param  $title string title of stat
	 * @param  $data string stats data
	 * @return void
	 */
	public function render_stats_box( $title, $data ) {
		?><div class="postbox">
			<h2><span><?php echo $title; ?></span></h2>
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
		do_action( 'sensei_analysis_after_headers' );

	} // End analysis_headers()

	/**
	 * wrapper_container wrapper for analysis area
	 * @since  1.2.0
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
	 * Default nav area for Analysis, overview of Learners, Courses and Lessons
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_default_nav() {

		$title = $this->name;
		?>
			<h1>
				<?php echo apply_filters( 'sensei_analysis_nav_title', $title ); ?>
			</h1>
		<?php
	} // End analysis_default_nav()

	/**
	 * Nav area for Analysis of a specific User profile
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_user_profile_nav() {

		$title = sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( array( 'page' => $this->page_slug ), admin_url( 'admin.php' ) ) ), esc_html( $this->name ) );
		if ( isset( $_GET['user_id'] ) && 0 < intval( $_GET['user_id'] ) ) {

			$user_id = intval( $_GET['user_id'] );
			$url = esc_url( add_query_arg( array( 'page' => $this->page_slug, 'user' => $user_id ), admin_url( 'admin.php' ) ) );
            $user_name = Sensei_Learner::get_full_name( $user_id );
			$title .= sprintf( '&nbsp;&nbsp;<span class="user-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>', $url, $user_name );

		} // End If Statement
		?>
			<h1><?php echo apply_filters( 'sensei_analysis_nav_title', $title ); ?></h1>
		<?php
	} // End analysis_user_profile_nav()

	/**
	 * Nav area for Analysis of a specific Course and its Lessons, specific to a User
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_user_course_nav() {

		$title = sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( array( 'page' => $this->page_slug ), admin_url( 'admin.php' ) ) ), esc_html( $this->name ) );
		if ( isset( $_GET['user_id'] ) && 0 < intval( $_GET['user_id'] ) ) {
			$user_id = intval( $_GET['user_id'] );
			$user_data = get_userdata( $user_id );
			$url = add_query_arg( array( 'page' => $this->page_slug, 'user_id' => $user_id ), admin_url( 'admin.php' ) );
            $user_name = Sensei_Learner::get_full_name( $user_id );
            $title .= sprintf( '&nbsp;&nbsp;<span class="user-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>', $url, $user_name );
			$title .= sprintf( '&nbsp;&nbsp;<span class="user-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>', esc_url( $url ), $user_data->display_name );
		} // End If Statement
		if ( isset( $_GET['course_id'] ) ) { 
			$course_id = intval( $_GET['course_id'] );
			$url = add_query_arg( array( 'page' => $this->page_slug, 'course_id' => $course_id ), admin_url( 'admin.php' ) );
			$title .= sprintf( '&nbsp;&nbsp;<span class="course-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>', esc_url( $url ), get_the_title( $course_id ) );
		}
		?>
			<h1><?php echo apply_filters( 'sensei_analysis_nav_title', $title ); ?></h1>
		<?php
	} // End analysis_user_course_nav()

	/**
	 * Nav area for Analysis of a specific Course and displaying its Lessons
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_course_nav() {

		$title = sprintf( '<a href="%s">%s</a>', add_query_arg( array( 'page' => $this->page_slug ), admin_url( 'admin.php' ) ), esc_html( $this->name ) );
		if ( isset( $_GET['course_id'] ) ) { 
			$course_id = intval( $_GET['course_id'] );
			$url = add_query_arg( array( 'page' => $this->page_slug, 'course_id' => $course_id ), admin_url( 'admin.php' ) );
			$title .= sprintf( '&nbsp;&nbsp;<span class="course-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>',esc_url( $url ), get_the_title( $course_id ) );
		}
		?>
			<h1><?php echo apply_filters( 'sensei_analysis_nav_title', $title ); ?></h1>
		<?php
	} // End analysis_course_nav()

	/**
	 * Nav area for Analysis of a specific Course displaying its Users
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_course_users_nav() {

		$title = sprintf( '<a href="%s">%s</a>', add_query_arg( array( 'page' => $this->page_slug ), admin_url( 'admin.php' ) ), esc_html( $this->name ) );
		if ( isset( $_GET['course_id'] ) ) { 
			$course_id = intval( $_GET['course_id'] );
			$url = add_query_arg( array( 'page' => $this->page_slug, 'course_id' => $course_id ), admin_url( 'admin.php' ) );
			$title .= sprintf( '&nbsp;&nbsp;<span class="course-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>', esc_url( $url ), get_the_title( $course_id ) );
		}
		?>
			<h1><?php echo apply_filters( 'sensei_analysis_nav_title', $title ); ?></h1>
		<?php
	} // End analysis_course_users_nav()

	/**
	 * Nav area for Analysis of a specific Lesson displaying its Users
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_lesson_users_nav() {

		$title = sprintf( '<a href="%s">%s</a>', add_query_arg( array( 'page' => $this->page_slug ), admin_url( 'admin.php' ) ), esc_html( $this->name ) );
		if ( isset( $_GET['lesson_id'] ) ) { 
			$lesson_id = intval( $_GET['lesson_id'] );
			$course_id = intval( get_post_meta( $lesson_id, '_lesson_course', true ) );
			$url = add_query_arg( array( 'page' => $this->page_slug, 'course_id' => $course_id ), admin_url( 'admin.php' ) );
			$title .= sprintf( '&nbsp;&nbsp;<span class="course-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>', esc_url( $url ), get_the_title( $course_id ) );
			$url = add_query_arg( array( 'page' => $this->page_slug, 'lesson_id' => $lesson_id ), admin_url( 'admin.php' ) );
			$title .= sprintf( '&nbsp;&nbsp;<span class="lesson-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>', esc_url( $url ), get_the_title( $lesson_id ) );
		}
		?>
			<h1><?php echo apply_filters( 'sensei_analysis_nav_title', $title ); ?></h1>
		<?php
	} // End analysis_lesson_users_nav()

	/**
	 * Handles CSV export requests
	 * @since  1.2.0
	 * @return void
	 */
	public function report_download_page() {
		// Check if is a report
		if ( !empty( $_GET['sensei_report_download'] ) ) {
			$report = sanitize_text_field( $_GET['sensei_report_download'] );

			// Simple verification to ensure intent, Note that a Nonce is per user, so the URL can't be shared
			if ( !wp_verify_nonce( $_REQUEST['_sdl_nonce'], 'sensei_csv_download-' . $report ) ) {
				wp_die( __('Invalid request', 'woothemes-sensei') );
			}

			// Setup the variables we might need
			$filename = apply_filters( 'sensei_csv_export_filename', $report );
			$course_id = 0;
			$lesson_id = 0;
			$user_id = 0;
			if( isset( $_GET['course_id'] ) ) {
				$course_id = intval( $_GET['course_id'] );
			}
			if( isset( $_GET['lesson_id'] ) ) {
				$lesson_id = intval( $_GET['lesson_id'] );
			}
			if( isset( $_GET['user_id'] ) ) {
				$user_id = intval( $_GET['user_id'] );
			}
			$type = isset( $_GET['view'] ) ? esc_html( $_GET['view'] ) : false;

			if ( 0 < $lesson_id ) {
				// Viewing a specific Lesson and all its Learners
				$sensei_analysis_report_object = $this->load_report_object( 'Lesson', $lesson_id );
			}
			elseif ( 0 < $course_id && 0 < $user_id ) {
				// Viewing a specific User on a specific Course
				$sensei_analysis_report_object = $this->load_report_object( 'Course', $course_id, $user_id );
			}
			elseif( 0 < $course_id ) {
				// Viewing a specific Course and all it's Lessons, or it's Learners
				$sensei_analysis_report_object = $this->load_report_object( 'Course', $course_id );
			}
			elseif ( 0 < $user_id ) {
				// Viewing a specific Learner, and their Courses
				$sensei_analysis_report_object = $this->load_report_object( 'User_Profile', $user_id );
			}
			else {
				// Overview of all Learners, all Courses, or all Lessons
				$sensei_analysis_report_object = $this->load_report_object( 'Overview', $type );
			} // End If Statement

			// Handle the headers
			$this->report_set_headers( $filename );

			// Collate the data, there could be many different reports for a single object
			$report_data_array = $sensei_analysis_report_object->generate_report( $report );

			// Output the data
			$this->report_write_download( $report_data_array );

			// Cleanly exit
			exit;
		} // End wp_query check
	} // End report_download_page()

	/**
	 * Sets headers for CSV reporting export
	 * @since  1.2.0
	 * @param  string $filename name of report file
	 * @return void
	 */
	public function report_set_headers( $filename = '' ) {
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment;filename=' . $filename . '.csv');
	} // End report_set_headers()

	/**
	 * Loads the right object for CSV reporting
	 * @since  1.2.0
	 * @param  string  $name          Name of class
	 * @param  integer $data          constructor arguments
	 * @param  undefined  $optional_data optional constructor arguments
	 * @return object                 class instance object
	 */
	public function load_report_object( $name = '', $data = 0, $optional_data = null ) {
		$object_name = 'Sensei_Analysis_' . $name . '_List_Table';
		if ( is_null($optional_data) ) {
			$sensei_analysis_report_object = new $object_name( $data );
		} else {
			$sensei_analysis_report_object = new $object_name( $data, $optional_data );
		}
		return $sensei_analysis_report_object;
	} // End load_report_object()

	/**
	 * Write array data to CSV
	 * @since  1.2.0
	 * @param  array  $report_data data array
	 * @return void
	 */
	public function report_write_download( $report_data = array() ) {
		$fp = fopen('php://output', 'w');
		foreach ($report_data as $row) {
			fputcsv($fp, $row);
		} // End For Loop
		fclose($fp);
	} // End report_write_download()

	/**
	 * Adds display_name to the default list of search columns for the WP User Object
	 * @since  1.4.5
	 * @param  array $search_columns         array of default user columns to search
	 * @param  string $search                search string
	 * @param  object $user_query_object     WP_User_Query Object
	 * @return array $search_columns         array of user columns to search
	 */
	public function user_search_columns_filter( $search_columns, $search, $user_query_object ) {
		// Alter $search_columns to include the fields you want to search on
		array_push( $search_columns, 'display_name' );
		return $search_columns;
	}

} // End Class

/**
 * Class WooThemes_Sensei_Analysis
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Analysis extends Sensei_Analysis {}
