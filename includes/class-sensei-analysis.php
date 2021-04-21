<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

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
	 *
	 * @since  1.0.0
	 * @param string $file
	 */
	public function __construct( $file ) {
		$this->name      = __( 'Analysis', 'sensei-lms' );
		$this->file      = $file;
		$this->page_slug = 'sensei_analysis';

		// Admin functions
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'analysis_admin_menu' ), 10 );
			add_action( 'analysis_wrapper_container', array( $this, 'wrapper_container' ) );
			if ( isset( $_GET['page'] ) && ( $_GET['page'] == $this->page_slug ) ) {

				add_action( 'admin_print_styles', array( $this, 'enqueue_styles' ) );

			}

			add_action( 'admin_init', array( $this, 'report_download_page' ) );

			add_filter( 'user_search_columns', array( $this, 'user_search_columns_filter' ), 10, 3 );
		}
	}


	/**
	 * analysis_admin_menu function.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function analysis_admin_menu() {
		if ( current_user_can( 'manage_sensei_grades' ) ) {
			add_submenu_page( 'sensei', __( 'Analysis', 'sensei-lms' ), __( 'Analysis', 'sensei-lms' ), 'manage_sensei_grades', 'sensei_analysis', array( $this, 'analysis_page' ) );
		}

	}

	/**
	 * enqueue_styles function.
	 *
	 * @description Load in CSS styles where necessary.
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_styles() {

		Sensei()->assets->enqueue( 'sensei-settings-api', 'css/settings.css' );

	}

	/**
	 * load_data_table_files loads required files for Analysis
	 *
	 * @since  1.2.0
	 * @return void
	 */
	public function load_data_table_files() {

		// Load Analysis Classes
		$classes_to_load = array(
			'list-table',
			'analysis-overview',
			'analysis-user-profile',
			'analysis-course',
			'analysis-lesson',
		);
		foreach ( $classes_to_load as $class_file ) {
			Sensei()->load_class( $class_file );
		}
	}

	/**
	 * load_data_object creates new instance of class
	 *
	 * @param  string    $name          Name of class
	 * @param  integer   $data          constructor arguments
	 * @param  undefined $optional_data optional constructor arguments
	 * @return object                 class instance object
	 */
	public function load_data_object( $name = '', $data = 0, $optional_data = null ) {
		// Load Analysis data
		$object_name = 'Sensei_Analysis_' . $name . '_List_Table';
		if ( is_null( $optional_data ) ) {
			$sensei_analysis_object = new $object_name( $data );
		} else {
			$sensei_analysis_object = new $object_name( $data, $optional_data );
		}
		$sensei_analysis_object->prepare_items();
		return $sensei_analysis_object;
	}

	/**
	 * analysis_page function.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function analysis_page() {

		$course_id = 0;
		$lesson_id = 0;
		$user_id   = 0;
		if ( isset( $_GET['course_id'] ) ) {
			$course_id = intval( $_GET['course_id'] );
		}
		if ( isset( $_GET['lesson_id'] ) ) {
			$lesson_id = intval( $_GET['lesson_id'] );
		}
		if ( isset( $_GET['user_id'] ) ) {
			$user_id = intval( $_GET['user_id'] );
		}

		$this->check_course_lesson( $course_id, $lesson_id, $user_id );

		$type = isset( $_GET['view'] ) ? esc_html( $_GET['view'] ) : false;

		if ( 0 < $lesson_id ) {
			// Viewing a specific Lesson and all its Learners
			$this->analysis_lesson_users_view( $lesson_id );
		} elseif ( 0 < $course_id && ! $user_id && 'user' == $type ) {
			// Viewing a specific Course and all its Learners
			$this->analysis_course_users_view( $course_id );
		} elseif ( 0 < $course_id && 0 < $user_id ) {
			// Viewing a specific Learner on a specific Course, showing their Lessons
			$this->analysis_user_course_view( $course_id, $user_id );
		} elseif ( 0 < $course_id ) {
			// Viewing a specific Course and all it's Lessons
			$this->analysis_course_view( $course_id );
		} elseif ( 0 < $user_id ) {
			// Viewing a specific Learner, and their Courses
			$this->analysis_user_profile_view( $user_id );
		} else {
			// Overview of all Learners, all Courses, or all Lessons
			$this->analysis_default_view( $type );
		}
	}

	/**
	 * Default view for analysis, showing an overview of all Learners, Courses and Lessons
	 *
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
				}
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
	}

	/**
	 * An individual users' profile view for analysis, showing their Courses
	 *
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
	}

	/**
	 * An individual Course view for analysis, showing the Courses Lessons
	 *
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
	}

	/**
	 * An individual Course view for analysis, showing a specific Learners Lessons
	 *
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
	}

	/**
	 * An individual Course view for analysis, showing all the Learners
	 *
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
	}

	/**
	 * An individual Lesson view for analysis, showing all the Learners
	 *
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
	}

	/**
	 * render_stats_box outputs stats boxes
	 *
	 * @since  1.2.0
	 * @param  $title string title of stat
	 * @param  $data string stats data
	 * @return void
	 */
	public function render_stats_box( $title, $data ) {
		?>
		<div class="postbox">
			<h2><span><?php echo esc_html( $title ); ?></span></h2>
			<div class="inside">
				<p class="stat"><?php echo esc_html( $data ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * analysis_headers outputs analysis general headers
	 *
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_headers( $args = array( 'nav' => 'default' ) ) {

		$function = 'analysis_' . $args['nav'] . '_nav';
		$this->$function();
		do_action( 'sensei_analysis_after_headers' );

	}

	/**
	 * wrapper_container wrapper for analysis area
	 *
	 * @since  1.2.0
	 * @param $which string
	 * @return void
	 */
	public function wrapper_container( $which ) {
		if ( 'top' == $which ) {
			?>
			<div id="woothemes-sensei" class="wrap woothemes-sensei">
			<?php
		} elseif ( 'bottom' == $which ) {
			?>
			</div><!--/#woothemes-sensei-->
			<?php
		}
	}

	/**
	 * Default nav area for Analysis, overview of Learners, Courses and Lessons
	 *
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_default_nav() {

		$title = $this->name;
		?>
			<h1>
				<?php echo wp_kses_post( apply_filters( 'sensei_analysis_nav_title', $title ) ); ?>
			</h1>
		<?php
	}

	/**
	 * Nav area for Analysis of a specific User profile
	 *
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_user_profile_nav() {

		$title = sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( array( 'page' => $this->page_slug ), admin_url( 'admin.php' ) ) ), esc_html( $this->name ) );
		if ( isset( $_GET['user_id'] ) && 0 < intval( $_GET['user_id'] ) ) {

			$user_id   = intval( $_GET['user_id'] );
			$url       = esc_url(
				add_query_arg(
					array(
						'page' => $this->page_slug,
						'user' => $user_id,
					),
					admin_url( 'admin.php' )
				)
			);
			$user_name = Sensei_Learner::get_full_name( $user_id );
			$title    .= sprintf( '&nbsp;&nbsp;<span class="user-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>', $url, $user_name );

		}
		?>
			<h1><?php echo wp_kses_post( apply_filters( 'sensei_analysis_nav_title', $title ) ); ?></h1>
		<?php
	}

	/**
	 * Nav area for Analysis of a specific Course and its Lessons, specific to a User
	 *
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_user_course_nav() {

		$title = sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( array( 'page' => $this->page_slug ), admin_url( 'admin.php' ) ) ), esc_html( $this->name ) );
		if ( isset( $_GET['user_id'] ) && 0 < intval( $_GET['user_id'] ) ) {
			$user_id   = intval( $_GET['user_id'] );
			$user_data = get_userdata( $user_id );
			$url       = add_query_arg(
				array(
					'page'    => $this->page_slug,
					'user_id' => $user_id,
				),
				admin_url( 'admin.php' )
			);
			$user_name = Sensei_Learner::get_full_name( $user_id );
			$title    .= sprintf( '&nbsp;&nbsp;<span class="user-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>', $url, $user_name );
			$title    .= sprintf( '&nbsp;&nbsp;<span class="user-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>', esc_url( $url ), $user_data->display_name );
		}
		if ( isset( $_GET['course_id'] ) ) {
			$course_id = intval( $_GET['course_id'] );
			$url       = add_query_arg(
				array(
					'page'      => $this->page_slug,
					'course_id' => $course_id,
				),
				admin_url( 'admin.php' )
			);
			$title    .= sprintf( '&nbsp;&nbsp;<span class="course-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>', esc_url( $url ), get_the_title( $course_id ) );
		}
		?>
			<h1><?php echo wp_kses_post( apply_filters( 'sensei_analysis_nav_title', $title ) ); ?></h1>
		<?php
	}

	/**
	 * Nav area for Analysis of a specific Course and displaying its Lessons
	 *
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_course_nav() {

		$title = sprintf( '<a href="%s">%s</a>', add_query_arg( array( 'page' => $this->page_slug ), admin_url( 'admin.php' ) ), esc_html( $this->name ) );
		if ( isset( $_GET['course_id'] ) ) {
			$course_id = intval( $_GET['course_id'] );
			$url       = add_query_arg(
				array(
					'page'      => $this->page_slug,
					'course_id' => $course_id,
				),
				admin_url( 'admin.php' )
			);
			$title    .= sprintf( '&nbsp;&nbsp;<span class="course-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>', esc_url( $url ), get_the_title( $course_id ) );
		}
		?>
			<h1><?php echo wp_kses_post( apply_filters( 'sensei_analysis_nav_title', $title ) ); ?></h1>
		<?php
	}

	/**
	 * Nav area for Analysis of a specific Course displaying its Users
	 *
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_course_users_nav() {

		$title = sprintf( '<a href="%s">%s</a>', add_query_arg( array( 'page' => $this->page_slug ), admin_url( 'admin.php' ) ), esc_html( $this->name ) );
		if ( isset( $_GET['course_id'] ) ) {
			$course_id = intval( $_GET['course_id'] );
			$url       = add_query_arg(
				array(
					'page'      => $this->page_slug,
					'course_id' => $course_id,
				),
				admin_url( 'admin.php' )
			);
			$title    .= sprintf( '&nbsp;&nbsp;<span class="course-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>', esc_url( $url ), get_the_title( $course_id ) );
		}
		?>
			<h1><?php echo wp_kses_post( apply_filters( 'sensei_analysis_nav_title', $title ) ); ?></h1>
		<?php
	}

	/**
	 * Nav area for Analysis of a specific Lesson displaying its Users
	 *
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_lesson_users_nav() {

		$title = sprintf( '<a href="%s">%s</a>', add_query_arg( array( 'page' => $this->page_slug ), admin_url( 'admin.php' ) ), esc_html( $this->name ) );
		if ( isset( $_GET['lesson_id'] ) ) {
			$lesson_id = intval( $_GET['lesson_id'] );
			$course_id = intval( get_post_meta( $lesson_id, '_lesson_course', true ) );
			$url       = add_query_arg(
				array(
					'page'      => $this->page_slug,
					'course_id' => $course_id,
				),
				admin_url( 'admin.php' )
			);
			$title    .= sprintf( '&nbsp;&nbsp;<span class="course-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>', esc_url( $url ), get_the_title( $course_id ) );
			$url       = add_query_arg(
				array(
					'page'      => $this->page_slug,
					'lesson_id' => $lesson_id,
				),
				admin_url( 'admin.php' )
			);
			$title    .= sprintf( '&nbsp;&nbsp;<span class="lesson-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>', esc_url( $url ), get_the_title( $lesson_id ) );
		}
		?>
			<h1><?php echo wp_kses_post( apply_filters( 'sensei_analysis_nav_title', $title ) ); ?></h1>
		<?php
	}

	/**
	 * Handles CSV export requests
	 *
	 * @since  1.2.0
	 * @return void
	 */
	public function report_download_page() {
		// Check if is a report
		if ( ! empty( $_GET['sensei_report_download'] ) ) {
			$report = sanitize_text_field( $_GET['sensei_report_download'] );

			// Simple verification to ensure intent, Note that a Nonce is per user, so the URL can't be shared
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Do not change the nonce.
			if ( ! ( isset( $_REQUEST['_sdl_nonce'] ) && wp_verify_nonce( wp_unslash( $_REQUEST['_sdl_nonce'] ), 'sensei_csv_download' ) ) ) {
				wp_die( esc_html__( 'Invalid request', 'sensei-lms' ) );
			}

			// Setup the variables we might need
			$filename  = apply_filters( 'sensei_csv_export_filename', $report );
			$course_id = 0;
			$lesson_id = 0;
			$user_id   = 0;
			if ( isset( $_GET['course_id'] ) ) {
				$course_id = intval( $_GET['course_id'] );
			}
			if ( isset( $_GET['lesson_id'] ) ) {
				$lesson_id = intval( $_GET['lesson_id'] );
			}
			if ( isset( $_GET['user_id'] ) ) {
				$user_id = intval( $_GET['user_id'] );
			}
			$type = isset( $_GET['view'] ) ? esc_html( $_GET['view'] ) : false;

			$this->check_course_lesson( $course_id, $lesson_id, $user_id );

			// Set up default properties for logging an event.
			$event_properties = [ 'view' => '' ];

			if ( 0 < $lesson_id ) {
				// Viewing a specific Lesson and all its Learners
				$sensei_analysis_report_object = $this->load_report_object( 'Lesson', $lesson_id );
				$event_properties['view']      = 'course-lesson-users';
			} elseif ( 0 < $course_id && 0 < $user_id ) {
				// Viewing a specific User on a specific Course
				$sensei_analysis_report_object = $this->load_report_object( 'Course', $course_id, $user_id );
				$event_properties['view']      = 'user-course-lessons';
			} elseif ( 0 < $course_id ) {
				// Viewing a specific Course and all it's Lessons, or it's Learners
				$sensei_analysis_report_object = $this->load_report_object( 'Course', $course_id );

				// Set view property for event logging.
				if ( isset( $_GET['view'] ) ) {
					if ( 'lesson' === $_GET['view'] ) {
						$event_properties['view'] = 'course-lessons';
					} elseif ( 'user' === $_GET['view'] ) {
						$event_properties['view'] = 'course-users';
					}
				}
			} elseif ( 0 < $user_id ) {
				// Viewing a specific Learner, and their Courses
				$sensei_analysis_report_object = $this->load_report_object( 'User_Profile', $user_id );
				$event_properties['view']      = 'user-courses';
			} else {
				// Overview of all Learners, all Courses, or all Lessons
				$sensei_analysis_report_object = $this->load_report_object( 'Overview', $type );
				$event_properties['view']      = isset( $_GET['view'] ) ? $_GET['view'] : '';
			}

			// Handle the headers
			$this->report_set_headers( $filename );

			// Collate the data, there could be many different reports for a single object
			$report_data_array = $sensei_analysis_report_object->generate_report( $report );

			// Output the data
			$this->report_write_download( $report_data_array );

			// Log event.
			sensei_log_event( 'analysis_export', $event_properties );

			// Cleanly exit
			exit;
		}
	}

	/**
	 * Check course and lesson objects are valid posts that the user has access to.
	 *
	 * @param int $course_id Course post ID.
	 * @param int $lesson_id Lesson post ID.
	 * @param int $user_id   User ID.
	 */
	private function check_course_lesson( $course_id, $lesson_id, $user_id ) {
		if (
			$course_id
			&& (
				'course' !== get_post_type( $course_id )
				|| ! current_user_can( get_post_type_object( 'course' )->cap->edit_post, $course_id )
			)
		) {
			wp_die( esc_html__( 'Invalid course', 'sensei-lms' ), 404 );
		}

		if (
			$lesson_id
			&& (
				'lesson' !== get_post_type( $lesson_id )
				|| ! current_user_can( get_post_type_object( 'lesson' )->cap->edit_post, $lesson_id )
			)
		) {
			wp_die( esc_html__( 'Invalid lesson', 'sensei-lms' ), 404 );
		}

		if (
			$user_id
			&& (
				! in_array( $user_id, Sensei()->teacher->get_learner_ids_for_courses_with_edit_permission(), true )
			)
		) {
			wp_die( esc_html__( 'Invalid user', 'sensei-lms' ), 404 );
		}
	}

	/**
	 * Sets headers for CSV reporting export
	 *
	 * @since  1.2.0
	 * @param  string $filename name of report file
	 * @return void
	 */
	public function report_set_headers( $filename = '' ) {
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment;filename=' . $filename . '.csv' );
	}

	/**
	 * Loads the right object for CSV reporting
	 *
	 * @since  1.2.0
	 * @param  string    $name          Name of class
	 * @param  integer   $data          constructor arguments
	 * @param  undefined $optional_data optional constructor arguments
	 * @return object                 class instance object
	 */
	public function load_report_object( $name = '', $data = 0, $optional_data = null ) {
		$object_name = 'Sensei_Analysis_' . $name . '_List_Table';
		if ( is_null( $optional_data ) ) {
			$sensei_analysis_report_object = new $object_name( $data );
		} else {
			$sensei_analysis_report_object = new $object_name( $data, $optional_data );
		}
		return $sensei_analysis_report_object;
	}

	/**
	 * Write array data to CSV
	 *
	 * @since  1.2.0
	 * @param  array $report_data data array
	 * @return void
	 */
	public function report_write_download( $report_data = array() ) {
		$fp = fopen( 'php://output', 'w' );
		foreach ( $report_data as $row ) {
			fputcsv( $fp, $row );
		}
		fclose( $fp );
	}

	/**
	 * Adds display_name to the default list of search columns for the WP User Object
	 *
	 * @since  1.4.5
	 * @param  array  $search_columns         array of default user columns to search
	 * @param  string $search                search string
	 * @param  object $user_query_object     WP_User_Query Object
	 * @return array $search_columns         array of user columns to search
	 */
	public function user_search_columns_filter( $search_columns, $search, $user_query_object ) {
		// Alter $search_columns to include the fields you want to search on
		array_push( $search_columns, 'display_name' );
		return $search_columns;
	}

}

/**
 * Class WooThemes_Sensei_Analysis
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Analysis extends Sensei_Analysis {}
