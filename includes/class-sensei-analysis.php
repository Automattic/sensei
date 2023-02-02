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

	/**
	 * The reports' page slug.
	 */
	const PAGE_SLUG = 'sensei_reports';

	/**
	 * The reports' page name (title).
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Reference to the main plugin file name.
	 *
	 * @var string
	 */
	public $file;

	/**
	 * Constructor
	 *
	 * @since  1.0.0
	 * @param string $file
	 */
	public function __construct( $file ) {
		$this->name = __( 'Reports', 'sensei-lms' );
		$this->file = $file;

		// Admin functions.
		if ( is_admin() ) {
			add_action( 'analysis_wrapper_container', array( $this, 'wrapper_container' ) );

			// phpcs:ignore WordPress.Security.NonceVerification -- Arguments used for comparison.
			if ( isset( $_GET['page'] ) && self::PAGE_SLUG === $_GET['page'] ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
				add_action( 'admin_print_styles', array( $this, 'enqueue_styles' ) );
			}

			add_action( 'admin_init', array( $this, 'report_download_page' ) );

			add_filter( 'user_search_columns', array( $this, 'user_search_columns_filter' ), 10, 3 );

			// Add custom navigation.
			add_action( 'in_admin_header', [ $this, 'add_custom_navigation' ] );
		}
	}

	/**
	 * Graceful fallback for deprecated properties.
	 *
	 * @since 4.2.0
	 *
	 * @param string $key The key to get.
	 *
	 * @return string|void
	 */
	public function __get( $key ) {
		if ( 'page_slug' === $key ) {
			_doing_it_wrong( 'Sensei_Analysis->page_slug', 'The "page_slug" property is deprecated. Use the Sensei_Analysis::PAGE_SLUG constant instead.', '4.2.0' );

			return self::PAGE_SLUG;
		}
	}

	/**
	 * Add custom navigation to the admin pages.
	 *
	 * @since 4.2.0
	 * @access private
	 */
	public function add_custom_navigation() {
		// phpcs:ignore WordPress.Security.NonceVerification -- No action, nonce is not required.
		$is_reports_page = isset( $_GET['page'] ) && ( self::PAGE_SLUG === $_GET['page'] );

		if ( ! $is_reports_page ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification -- No action, nonce is not required.
		if ( isset( $_GET['course_id'] ) || isset( $_GET['lesson_id'] ) || isset( $_GET['user_id'] ) ) {
			return;
		}

		$this->display_reports_navigation();
	}

	/**
	 * Display the Reports navigation.
	 */
	private function display_reports_navigation() {
		// phpcs:ignore
		$type = isset( $_GET['view'] ) ? esc_html( $_GET['view'] ) : false;

		$reports            = array(
			'students' => __( 'Students', 'sensei-lms' ),
			'courses'  => __( 'Courses', 'sensei-lms' ),
			'lessons'  => __( 'Lessons', 'sensei-lms' ),
		);
		$current_report_key = isset( $reports[ $type ] ) ? $type : 'students';

		$link_template = '<div><a href="%s" class="sensei-custom-navigation__tab %s">%s</a></div>';
		$menu          = array();
		foreach ( $reports as $key => $title ) {
			$class_name   = $current_report_key === $key ? 'active' : '';
			$query_args   = array(
				'page' => self::PAGE_SLUG,
				'view' => $key,
			);
			$menu[ $key ] = sprintf( $link_template, esc_url( add_query_arg( $query_args, admin_url( 'admin.php' ) ) ), $class_name, $title );
		}
		/**
		 * Filter the Reports navigation menu items.
		 *
		 * @since 4.2.0
		 * @hook sensei_analysis_sub_menu
		 *
		 * @param {array} $menu The menu items.
		 * @return {array} Returns filtered menu items.
		 */
		$menu = apply_filters( 'sensei_analysis_sub_menu', $menu );
		/**
		 * Filter the Reports page title.
		 *
		 * @since 4.2.0
		 * @hook sensei_analysis_nav_title
		 *
		 * @param {string} $title The page title.
		 * @return {string} Returns filtered page title.
		 */
		$data = apply_filters( 'sensei_analysis_nav_title', $this->name );
		?>
		<div id="sensei-custom-navigation" class="sensei-custom-navigation">
			<div class="sensei-custom-navigation__heading">
				<div class="sensei-custom-navigation__title">
					<h1><?php echo wp_kses_post( $data ); ?></h1>
				</div>
			</div>
			<div class="sensei-custom-navigation__tabbar">
				<?php echo wp_kses( implode( '', $menu ), wp_kses_allowed_html( 'post' ) ); ?>
				<div class="sensei-custom-navigation__tabbar-separator"></div>
				<a class="sensei-custom-navigation__info" target="_blank" href="https://senseilms.com/documentation/reports/?utm_source=plugin_sensei&utm_medium=docs&utm_campaign=reports">
					<?php echo esc_html__( 'Guide To Using Reports', 'sensei-lms' ); ?>
				</a>
				<div></div>
			</div>
		</div>
		<?php
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
			add_submenu_page(
				'sensei',
				$this->name,
				$this->name,
				'manage_sensei_grades',
				self::PAGE_SLUG,
				array( $this, 'analysis_page' )
			);
		}
	}

	/**
	 * Enqueue JS scripts.
	 *
	 * @since  4.2.0
	 * @access private
	 */
	public function enqueue_scripts() {

		Sensei()->assets->enqueue( 'sensei-reports', 'js/admin/reports.js', [ 'jquery', 'jquery-ui-datepicker' ] );

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
		Sensei()->assets->enqueue( 'sensei-jquery-ui', 'css/jquery-ui.css' );

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
	 * The load_data_object method creates new instance of class
	 *
	 * @param  string $name          Name of class.
	 * @param  mixed  $data          Constructor arguments.
	 * @param  mixed  $optional_data Optional constructor arguments.
	 * @return Sensei_List_Table     Class instance object
	 */
	public function load_data_object( $name = '', $data = 0, $optional_data = null ) {
		if ( 'Overview' === $name ) {
			$factory                = new Sensei_Reports_Overview_List_Table_Factory();
			$sensei_analysis_object = $factory->create( $data );
		} else {
			$object_name = 'Sensei_Analysis_' . $name . '_List_Table';
			if ( is_null( $optional_data ) ) {
				$sensei_analysis_object = new $object_name( $data );
			} else {
				$sensei_analysis_object = new $object_name( $data, $optional_data );
			}
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

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$type = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'students';

		// Set up default properties for logging an event.
		$event_properties = [ 'view' => '' ];

		if ( 0 < $lesson_id ) {
			// Viewing a specific Lesson and all its Learners
			$this->analysis_lesson_users_view( $lesson_id );

			$event_properties['view'] = 'course-lesson-users';
		} elseif ( 0 < $course_id && ! $user_id && 'user' === $type ) {
			// Viewing a specific Course and all its Learners
			$this->analysis_course_users_view( $course_id );

			$event_properties['view'] = 'course-users';
		} elseif ( 0 < $course_id && 0 < $user_id ) {
			// Viewing a specific Learner on a specific Course, showing their Lessons
			$this->analysis_user_course_view( $course_id, $user_id );

			$event_properties['view'] = 'user-course-lessons';
		} elseif ( 0 < $course_id ) {
			// Viewing a specific Course and all it's Lessons
			$this->analysis_course_view( $course_id );

			$event_properties['view'] = 'course-lessons';
		} elseif ( 0 < $user_id ) {
			// Viewing a specific Learner, and their Courses
			$this->analysis_user_profile_view( $user_id );

			$event_properties['view'] = 'user-courses';
		} else {
			// Overview of all Learners, all Courses, or all Lessons
			$this->analysis_default_view( $type );

			if ( 'lessons' === $type ) {
				// Ensure the user has actually filtered by a course first before sending the "lessons" event.
				if ( ! empty( $_GET['course_filter'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for comparison.
					$event_properties['view'] = $type;
				}
			} else {
				// For consistency reasons we use "users" instead of "students".
				$event_properties['view'] = 'students' === $type ? 'users' : $type;
			}
		}

		// Log event.
		if ( $event_properties['view'] ) {
			sensei_log_event( 'analysis_view', $event_properties );
		}
	}

	/**
	 * Default view for analysis, showing an overview of all Learners, Courses and Lessons
	 *
	 * @since  1.2.0
	 * @return void
	 */
	public function analysis_default_view( $type ) {
		$sensei_analysis_overview = $this->load_data_object( 'Overview', $type );
		$exclude_query_params     = [ 'start_date', 'end_date' ];
		$this->display_report_page( $sensei_analysis_overview, null, $exclude_query_params );
	}

	/**
	 * An individual users' profile view for analysis, showing their Courses
	 *
	 * @since  1.2.0
	 *
	 * @param int $user_id
	 * @return void
	 */
	public function analysis_user_profile_view( $user_id ) {
		$sensei_analysis_user_profile = $this->load_data_object( 'User_Profile', $user_id );
		$this->display_report_page( $sensei_analysis_user_profile, 'user_profile' );
	}

	/**
	 * An individual Course view for analysis, showing the Courses Lessons
	 *
	 * @since  1.2.0
	 *
	 * @param int $course_id Course ID for the report.
	 * @return void
	 */
	public function analysis_course_view( $course_id ) {
		$sensei_analysis_course = $this->load_data_object( 'Course', $course_id );
		$this->display_report_page( $sensei_analysis_course, 'course' );
	}

	/**
	 * An individual Course view for analysis, showing a specific Learners Lessons
	 *
	 * @since  1.2.0
	 *
	 * @param int $course_id Course ID for the report.
	 * @param int $user_id User ID for the report.
	 * @return void
	 */
	public function analysis_user_course_view( int $course_id, int $user_id ) {
		$sensei_analysis_user_course = $this->load_data_object( 'Course', $course_id, $user_id );
		$this->display_report_page( $sensei_analysis_user_course, 'user_course' );
	}

	/**
	 * An individual Course view for analysis, showing all the Learners
	 *
	 * @since 1.2.0
	 *
	 * @param int $course_id Course ID for the report.
	 * @return void
	 */
	public function analysis_course_users_view( int $course_id ) {
		$sensei_analysis_course_users = $this->load_data_object( 'Course', $course_id );
		$exclude_query_params         = [ 'start_date', 'end_date' ];
		$this->display_report_page( $sensei_analysis_course_users, 'course_users', $exclude_query_params );
	}

	/**
	 * An individual Lesson view for analysis, showing all the Learners
	 *
	 * @since  1.2.0
	 *
	 * @param int $lesson_id Lesson ID for the report.
	 * @return void
	 */
	public function analysis_lesson_users_view( int $lesson_id ) {
		$sensei_analysis_lesson_users = $this->load_data_object( 'Lesson', $lesson_id );
		$this->display_report_page( $sensei_analysis_lesson_users, 'lesson_users' );
	}

	/**
	 * Output the report page with given list table.
	 *
	 * @param Sensei_List_Table $list_table List table to display.
	 * @param string|null       $nav_type Navigation type.
	 * @param array             $exclude_query_params Query parameters to exclude from output.
	 */
	private function display_report_page( Sensei_List_Table $list_table, $nav_type = null, array $exclude_query_params = [] ) {
		$exclude_query_params = array_merge( $exclude_query_params, [ '_wpnonce', '_wp_http_referer', 's' ] );

		// Wrappers
		do_action( 'analysis_before_container' );
		do_action( 'analysis_wrapper_container', 'top' );

		$this->display_nav( $nav_type );
		do_action( 'sensei_analysis_after_headers' );

		$list_table->views();
		?>
		<form id="reports-filter" method="get">
			<?php
			$list_table->table_search_form();
			Sensei_Utils::output_query_params_as_inputs( $exclude_query_params );
			$list_table->display();
			do_action( 'sensei_analysis_extra' );
			?>
		</form>
		<?php

		do_action( 'analysis_wrapper_container', 'bottom' );
		do_action( 'analysis_after_container' );
	}

	/**
	 * Display the navigation for the report page.
	 *
	 * @param string|null $nav_type Navigation type.
	 */
	private function display_nav( $nav_type ) {
		switch ( $nav_type ) {
			case 'user_profile':
				$this->analysis_user_profile_nav();
				break;
			case 'course':
				$this->analysis_course_nav();
				break;
			case 'course_users':
				$this->analysis_course_users_nav();
				break;
			case 'user_course':
				$this->analysis_user_course_nav();
				break;
			case 'lesson_users':
				$this->analysis_lesson_users_nav();
				break;
			default:
				break;
		}
	}

	/**
	 * Outputs stats boxes.
	 *
	 * @since      1.2.0
	 * @deprecated 4.2.0
	 * @param      string $title Title of stat.
	 * @param      string $data  Stats data.
	 * @return     void
	 */
	public function render_stats_box( $title, $data ) {

		_deprecated_function( __METHOD__, '4.2.0' );

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
	 * Analysis_headers outputs analysis general headers.
	 *
	 * @deprecated 3.13.4
	 * @since  1.2.0
	 *
	 * @param array $args
	 *
	 * @return void
	 */
	public function analysis_headers( $args = array( 'nav' => 'default' ) ) {
		_deprecated_function( __METHOD__, '3.13.4' );

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
	 * @deprecated 4.2.0
	 * @return void
	 */
	public function analysis_default_nav() {
		_deprecated_function( __METHOD__, '4.2.0' );
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

		$analysis_args = array(
			'page' => self::PAGE_SLUG,
		);
		$title         = sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( $analysis_args, admin_url( 'admin.php' ) ) ), esc_html( $this->name ) );
		if ( isset( $_GET['user_id'] ) && 0 < intval( $_GET['user_id'] ) ) {
			$user_id   = intval( $_GET['user_id'] );
			$url       = esc_url(
				add_query_arg(
					array(
						'page' => self::PAGE_SLUG,
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

		$analysis_args = array(
			'page' => self::PAGE_SLUG,
		);
		$title         = sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( $analysis_args, admin_url( 'admin.php' ) ) ), esc_html( $this->name ) );
		if ( isset( $_GET['user_id'] ) && 0 < intval( $_GET['user_id'] ) ) {
			$user_id   = intval( $_GET['user_id'] );
			$user_data = get_userdata( $user_id );
			$url       = add_query_arg(
				array(
					'page'    => self::PAGE_SLUG,
					'user_id' => $user_id,
				),
				admin_url( 'admin.php' )
			);
			$user_name = Sensei_Learner::get_full_name( $user_id );
			$title    .= sprintf( '&nbsp;&nbsp;<span class="user-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>', esc_url( $url ), $user_name );
		}
		if ( isset( $_GET['course_id'] ) ) {
			$course_id = intval( $_GET['course_id'] );
			$url       = add_query_arg(
				array(
					'page'      => self::PAGE_SLUG,
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

		$analysis_args = array(
			'page' => self::PAGE_SLUG,
		);
		$title         = sprintf( '<a href="%s">%s</a>', add_query_arg( $analysis_args, admin_url( 'admin.php' ) ), esc_html( $this->name ) );
		if ( isset( $_GET['course_id'] ) ) {
			$course_id = intval( $_GET['course_id'] );
			$url       = add_query_arg(
				array(
					'page'      => self::PAGE_SLUG,
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

		$analysis_args = array(
			'page' => self::PAGE_SLUG,
		);
		$title         = sprintf( '<a href="%s">%s</a>', add_query_arg( $analysis_args, admin_url( 'admin.php' ) ), esc_html( $this->name ) );
		if ( isset( $_GET['course_id'] ) ) {
			$course_id = intval( $_GET['course_id'] );
			$url       = add_query_arg(
				array(
					'page'      => self::PAGE_SLUG,
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

		$analysis_args = array(
			'page' => self::PAGE_SLUG,
		);
		$title         = sprintf( '<a href="%s">%s</a>', add_query_arg( $analysis_args, admin_url( 'admin.php' ) ), esc_html( $this->name ) );
		if ( isset( $_GET['lesson_id'] ) ) {
			$lesson_id = intval( $_GET['lesson_id'] );
			$course_id = intval( get_post_meta( $lesson_id, '_lesson_course', true ) );
			$url       = add_query_arg(
				array(
					'page'      => self::PAGE_SLUG,
					'course_id' => $course_id,
				),
				admin_url( 'admin.php' )
			);
			$title    .= sprintf( '&nbsp;&nbsp;<span class="course-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>', esc_url( $url ), get_the_title( $course_id ) );
			$url       = add_query_arg(
				array(
					'page'      => self::PAGE_SLUG,
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
	 * @param  string    $name          Name of class.
	 * @param  integer   $data          constructor arguments.
	 * @param  undefined $optional_data optional constructor arguments.
	 * @return object                 class instance object
	 */
	public function load_report_object( $name = '', $data = 0, $optional_data = null ) {
		if ( 'Overview' === $name ) {
			$factory                       = new Sensei_Reports_Overview_List_Table_Factory();
			$sensei_analysis_report_object = $factory->create( $data );
		} else {
			$object_name = 'Sensei_Analysis_' . $name . '_List_Table';
			if ( is_null( $optional_data ) ) {
				$sensei_analysis_report_object = new $object_name( $data );
			} else {
				$sensei_analysis_report_object = new $object_name( $data, $optional_data );
			}
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
