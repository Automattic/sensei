<?php
use Sensei\Internal\Services\Grading_Stats_Service_Interface;
use Sensei\Internal\Services\Progress_Aggregation_Service_Interface;
use Sensei\Internal\Services\Progress_Query_Service_Factory;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress_Interface;
use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Sensei Grading Class
 *
 * All functionality pertaining to the Admin Grading in Sensei.
 *
 * @package Assessment
 * @author Automattic
 *
 * @since 1.3.0
 */
class Sensei_Grading {

	public $file;
	public $page_slug;

	/**
	 * Constructor
	 *
	 * @since  1.3.0
	 *
	 * @param string $file The main plugin file path.
	 */
	public function __construct( $file ) {
		$this->file      = $file;
		$this->page_slug = 'sensei_grading';

		// Admin functions
		if ( is_admin() ) {
			add_action( 'grading_wrapper_container', array( $this, 'wrapper_container' ) );

			if ( isset( $_GET['page'] ) && ( $_GET['page'] == $this->page_slug ) ) {
				add_action( 'admin_print_scripts', array( $this, 'enqueue_scripts' ) );
				add_action( 'admin_print_styles', array( $this, 'enqueue_styles' ) );
			}

			add_action( 'admin_init', array( $this, 'admin_process_grading_submission' ) );
			add_action( 'admin_notices', array( $this, 'add_grading_notices' ) );
		}

		// Ajax functions
		if ( is_admin() ) {
			add_action( 'wp_ajax_get_lessons_dropdown', array( $this, 'get_lessons_dropdown' ) );
			add_action( 'wp_ajax_get_redirect_url', array( $this, 'get_redirect_url' ) );
		}
	}

	/**
	 * Graceful fallback for deprecated properties.
	 *
	 * @since 4.24.4
	 *
	 * @param string $key The key to get.
	 *
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( 'name' === $key ) {
			_doing_it_wrong( __CLASS__ . '->name', 'The "name" property is deprecated. Use get_name() instead.', '4.24.5' );

			return $this->get_name();
		}
	}

	/**
	 * Get the name of the screen.
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Grading', 'sensei-lms' );
	}

	/**
	 * Get the progress aggregation service instance.
	 *
	 * @since 4.26.0
	 *
	 * @return Progress_Aggregation_Service_Interface
	 */
	private static function get_aggregation_service(): Progress_Aggregation_Service_Interface {
		return ( new Progress_Query_Service_Factory() )->create_aggregation_service();
	}

	/**
	 * Get the grading stats service instance.
	 *
	 * @since 4.26.0
	 *
	 * @return Grading_Stats_Service_Interface
	 */
	private static function get_grading_stats_service(): Grading_Stats_Service_Interface {
		return ( new Progress_Query_Service_Factory() )->create_grading_stats_service();
	}

	/**
	 * Add the Grading submenu.
	 *
	 * @since  1.3.0
	 * @access public
	 */
	public function grading_admin_menu() {
		$indicator_html = '';

		/** This filter is documented in includes/class-sensei-grading.php */
		$args           = apply_filters( 'sensei_count_statuses_args', array( 'type' => 'lesson' ) );
		$ungraded_count = self::get_aggregation_service()->count_ungraded_quizzes( $args );

		if ( $ungraded_count > 0 ) {
			$indicator_html = ' <span class="awaiting-mod">' . esc_html( (string) $ungraded_count ) . '</span>';
		}

		if ( current_user_can( 'manage_sensei_grades' ) ) {
			add_submenu_page(
				'sensei',
				__( 'Grading', 'sensei-lms' ),
				__( 'Grading', 'sensei-lms' ) . $indicator_html,
				'manage_sensei_grades',
				$this->page_slug,
				array( $this, 'grading_page' )
			);
		}
	}

	/**
	 * enqueue_scripts function.
	 *
	 * @description Load in JavaScripts where necessary.
	 * @access public
	 * @since 1.3.0
	 * @return void
	 */
	public function enqueue_scripts() {

		// Load Grading JS
		Sensei()->assets->enqueue( 'sensei-grading-general', 'js/grading-general.js', array( 'jquery', 'sensei-core-select2' ) );
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

		wp_enqueue_style( Sensei()->token . '-admin' );

		Sensei()->assets->enqueue( 'sensei-settings-api', 'css/settings.css' );
	}

	/**
	 * load_data_table_files loads required files for Grading
	 *
	 * @since  1.3.0
	 * @return void
	 */
	public function load_data_table_files() {

		// Load Grading Classes
		$classes_to_load = array(
			'list-table',
			'grading-main',
			'grading-user-quiz',
		);
		foreach ( $classes_to_load as $class_file ) {
			Sensei()->load_class( $class_file );
		}
	}

	/**
	 * load_data_object creates new instance of class
	 *
	 * @since  1.3.0
	 * @deprecated 3.3.0  Use constructors instead.
	 *
	 * @param  string    $name          Name of class
	 * @param  integer   $data          constructor arguments
	 * @param  undefined $optional_data optional constructor arguments
	 * @return object                 class instance object
	 */
	public function load_data_object( $name = '', $data = 0, $optional_data = null ) {
		// Use constructors directly.
		_deprecated_function( __METHOD__, '3.3.0', 'new Sensei_Grading_{$name}' );

		// Load Analysis data
		$object_name = 'Sensei_Grading_' . $name;
		if ( is_null( $optional_data ) ) {
			$sensei_grading_object = new $object_name( $data );
		} else {
			$sensei_grading_object = new $object_name( $data, $optional_data );
		}
		if ( 'Main' == $name ) {
			$sensei_grading_object->prepare_items();
		}
		return $sensei_grading_object;
	}

	/**
	 * grading_page function.
	 *
	 * @since 1.3.0
	 * @access public
	 * @return void
	 */
	public function grading_page() {

		if ( isset( $_GET['quiz_id'] ) && 0 < intval( $_GET['quiz_id'] ) && isset( $_GET['user'] ) && 0 < intval( $_GET['user'] ) ) {
			$this->grading_user_quiz_view();
		} else {
			$this->grading_default_view();
		}
	}

	/**
	 * grading_default_view default view for grading page
	 *
	 * @since  1.3.0
	 * @return void
	 */
	public function grading_default_view() {

		$course_id = null;
		$lesson_id = null;
		$user_id   = null;
		$view      = null;

		// Load Grading data
		if ( ! empty( $_GET['course_id'] ) ) {
			$course_id = intval( $_GET['course_id'] );

			if ( ! current_user_can( get_post_type_object( 'course' )->cap->edit_post, $course_id ) ) {
				return;
			}
		}
		if ( ! empty( $_GET['lesson_id'] ) ) {
			$lesson_id = intval( $_GET['lesson_id'] );

			if ( ! current_user_can( get_post_type_object( 'lesson' )->cap->edit_post, $lesson_id ) ) {
				return;
			}
		}

		if ( ! empty( $_GET['user_id'] ) ) {
			$user_id = intval( $_GET['user_id'] );
		}
		if ( ! empty( $_GET['view'] ) ) {
			$view = esc_html( $_GET['view'] );
		}

		$sensei_grading_overview = new Sensei_Grading_Main( compact( 'course_id', 'lesson_id', 'user_id', 'view' ) );
		$sensei_grading_overview->prepare_items();

		// Wrappers
		/**
		 * Fires before the container of the Grading page.
		 *
		 * @hook grading_before_container
		 */
		do_action( 'grading_before_container' );
		/**
		 * Fires before the container of the Grading page.
		 * This hook allows to wrap the container.
		 *
		 * @hook grading_wrapper_container
		 *
		 * @param {string} $which The position ('top' here).
		 */
		do_action( 'grading_wrapper_container', 'top' );

		$this->grading_default_nav();

		/**
		 * Fires after the headers of the Grading page.
		 *
		 * @hook sensei_grading_after_headers
		 */
		do_action( 'sensei_grading_after_headers' );

		$sensei_grading_overview->views();
		?>
		<form id="grading-filters" method="get">
			<?php
			Sensei_Utils::output_query_params_as_inputs( array( 'course_id', 'lesson_id', 's' ) );
			$sensei_grading_overview->table_search_form();
			$sensei_grading_overview->display();
			?>
		</form>
		<?php
		/**
		 * Fires after the container with main content on the Grading page.
		 * Allows to add extra content.
		 *
		 * @hook sensei_grading_extra
		 */
		do_action( 'sensei_grading_extra' );

		/**
		 * Fires after the container of the Grading page.
		 * This hook allows to wrap the container.
		 *
		 * @hook grading_wrapper_container
		 *
		 * @param {string} $which The position ('bottom' here).
		 */
		do_action( 'grading_wrapper_container', 'bottom' );

		/**
		 * Fires after the container of the Grading page.
		 *
		 * @hook grading_after_container
		 */
		do_action( 'grading_after_container' );
	}

	/**
	 * grading_user_quiz_view user quiz answers view for grading page
	 *
	 * @since  1.2.0
	 * @return void
	 */
	public function grading_user_quiz_view() {

		// Load Grading data
		$user_id = 0;
		$quiz_id = 0;
		if ( isset( $_GET['user'] ) ) {
			$user_id = intval( $_GET['user'] );
		}
		if ( isset( $_GET['quiz_id'] ) ) {
			$quiz_id   = intval( $_GET['quiz_id'] );
			$lesson_id = get_post_meta( $quiz_id, '_quiz_lesson', true );

			if ( ! current_user_can( get_post_type_object( 'lesson' )->cap->edit_post, $lesson_id ) ) {
				return;
			}
		}

		$sensei_grading_user_profile = new Sensei_Grading_User_Quiz( $user_id, $quiz_id );

		// Wrappers
		/**
		 * Fires before the container of the Grading page.
		 *
		 * @hook grading_before_container
		 */
		do_action( 'grading_before_container' );

		/**
		 * Fires before the container of the Grading page.
		 * This hook allows to wrap the container.
		 *
		 * @hook grading_wrapper_container
		 *
		 * @param {string} $which The position ('top' here).
		 */
		do_action( 'grading_wrapper_container', 'top' );

		$this->grading_user_quiz_nav();

		/**
		 * Fires after the headers of the Grading page.
		 *
		 * @hook sensei_grading_after_headers
		 */
		do_action( 'sensei_grading_after_headers' );

		?>
		<div id="poststuff" class="sensei-grading-wrap user-profile">
			<div class="sensei-grading-main">
				<?php $sensei_grading_user_profile->display(); ?>
			</div>
		</div>
		<?php
		/**
		 * Fires after the container on the Grading page.
		 * This hook allows to wrap the container.
		 *
		 * @hook grading_wrapper_container
		 *
		 * @param {string} $which The position ('bottom' here).
		 */
		do_action( 'grading_wrapper_container', 'bottom' );

		/**
		 * Fires after the container of the Grading page.
		 *
		 * @hook grading_after_container
		 */
		do_action( 'grading_after_container' );
	}

	/**
	 * Outputs Grading general headers.
	 *
	 * @since  1.3.0
	 * @deprecated 3.13.4
	 *
	 * @param array $args
	 * @return void
	 */
	public function grading_headers( $args = array( 'nav' => 'default' ) ) {
		_deprecated_function( __METHOD__, '3.13.4' );

		$function = 'grading_' . $args['nav'] . '_nav';
		$this->$function();
		do_action( 'sensei_grading_after_headers' );
	}

	/**
	 * wrapper_container wrapper for Grading area
	 *
	 * @since  1.3.0
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
	 * Default nav area for Grading
	 *
	 * @since  1.3.0
	 * @return void
	 */
	public function grading_default_nav() {
		$title = esc_html( $this->get_name() );

		if ( isset( $_GET['course_id'] ) ) {
			$course_id = intval( $_GET['course_id'] );
			$title    .= '<span class="course-title">&gt;&nbsp;&nbsp;' . esc_html( get_the_title( $course_id ) ) . '</span>';
		}
		if ( isset( $_GET['lesson_id'] ) ) {
			$lesson_id = intval( $_GET['lesson_id'] );
			$title    .= '&nbsp;&nbsp;<span class="lesson-title">&gt;&nbsp;&nbsp;' . esc_html( get_the_title( intval( $lesson_id ) ) ) . '</span>';
		}
		if ( isset( $_GET['user_id'] ) && 0 < intval( $_GET['user_id'] ) ) {

			$user_name = Sensei_Learner::get_full_name( $_GET['user_id'] );
			$title    .= '&nbsp;&nbsp;<span class="user-title">&gt;&nbsp;&nbsp;' . esc_html( $user_name ) . '</span>';

		}
		?>
			<h1>
			<?php
			/**
			 * Filter the title of the Grading page.
			 *
			 * @hook sensei_grading_nav_title
			 *
			 * @param {string} $title
			 * @return {string} Filtered title.
			 */
			echo wp_kses_post( apply_filters( 'sensei_grading_nav_title', $title ) );
			?>
			</h1>
		<?php
	}

	/**
	 * Nav area for Grading specific users' quiz answers
	 *
	 * @since  1.3.0
	 * @return void
	 */
	public function grading_user_quiz_nav() {
		$title = esc_html( $this->get_name() );

		if ( isset( $_GET['quiz_id'] ) ) {
			$quiz_id   = intval( $_GET['quiz_id'] );
			$lesson_id = get_post_meta( $quiz_id, '_quiz_lesson', true );
			$course_id = get_post_meta( $lesson_id, '_lesson_course', true );
			$url       = add_query_arg(
				array(
					'page'      => $this->page_slug,
					'course_id' => $course_id,
				),
				admin_url( 'admin.php' )
			);
			$title    .= sprintf( '&nbsp;&nbsp;<span class="course-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>', esc_url( $url ), esc_html( get_the_title( $course_id ) ) );

			$url    = add_query_arg(
				array(
					'page'      => $this->page_slug,
					'lesson_id' => $lesson_id,
				),
				admin_url( 'admin.php' )
			);
			$title .= sprintf( '&nbsp;&nbsp;<span class="lesson-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>', esc_url( $url ), esc_html( get_the_title( $lesson_id ) ) );
		}
		if ( isset( $_GET['user'] ) && 0 < intval( $_GET['user'] ) ) {

			$user_name = Sensei_Learner::get_full_name( $_GET['user'] );
			$title    .= '&nbsp;&nbsp;<span class="user-title">&gt;&nbsp;&nbsp;' . esc_html( $user_name ) . '</span>';

		}
		?>
			<h2>
			<?php
			/**
			 * Filter the title of the Grading page.
			 *
			 * @hook sensei_grading_nav_title
			 *
			 * @param {string} $title
			 * @return {string} Filtered title.
			 */
			echo wp_kses_post( apply_filters( 'sensei_grading_nav_title', $title ) );
			?>
			</h2>
		<?php
	}

	/**
	 * Return array of valid statuses for either Course or Lesson
	 *
	 * @since  1.7.0
	 * @return array
	 */
	public function get_stati( $type ) {
		$statuses = array();
		switch ( $type ) {
			case 'course':
				$statuses = array(
					'in-progress',
					'complete',
				);
				break;

			case 'lesson':
				$statuses = array(
					'in-progress',
					'complete',
					'ungraded',
					'graded',
					'passed',
					'failed',
				);
				break;

		}
		return $statuses;
	}

	/**
	 * Count the various statuses for Course or Lesson.
	 *
	 * @since  1.7.0
	 * @param  array $args (default: array())
	 * @return array
	 */
	public function count_statuses( $args = array() ) {
		/**
		 * Filter the arguments used to count progress statuses.
		 *
		 * Alter the query arguments (post restrictions, user exclusions) used
		 * to count progress statuses on the Grading page.
		 *
		 * @since 1.8.0
		 *
		 * @hook sensei_count_statuses_args
		 *
		 * @param {array} $args Array of arguments for the query.
		 * @return {array} Filtered arguments.
		 */
		$args = apply_filters( 'sensei_count_statuses_args', $args );

		$type = $args['type'] ?? 'lesson';

		$cache_key = 'sensei-statuses-' . md5( wp_json_encode( $args ) );
		$counts    = wp_cache_get( $cache_key, 'counts' );

		if ( false === $counts ) {
			$counts = self::get_aggregation_service()->count_statuses( $args );
			wp_cache_set( $cache_key, $counts, 'counts' );
		}

		// Ensure all expected statuses exist with 0 defaults.
		$defaults = array_fill_keys( $this->get_stati( $type ), 0 );
		$counts   = array_merge( $defaults, $counts );

		// Also ensure these specific keys always exist.
		foreach ( array(
			Quiz_Progress_Interface::STATUS_GRADED,
			Quiz_Progress_Interface::STATUS_UNGRADED,
			Quiz_Progress_Interface::STATUS_PASSED,
			Quiz_Progress_Interface::STATUS_FAILED,
			Lesson_Progress_Interface::STATUS_IN_PROGRESS,
			Lesson_Progress_Interface::STATUS_COMPLETE,
		) as $status ) {
			if ( ! isset( $counts[ $status ] ) ) {
				$counts[ $status ] = 0;
			}
		}

		if ( 'course' === $type ) {
			$comment_type = 'sensei_course_status';
		} else {
			$comment_type = 'sensei_lesson_status';
		}

		/**
		 * Filter the counts of statuses for a given type.
		 *
		 * @hook sensei_count_statuses
		 *
		 * @param {array} $counts Array of counts for each status.
		 * @param {string} $type Type of status to count: sensei_course_status or sensei_lesson_status.
		 * @return {array} Filtered counts.
		 */
		return apply_filters( 'sensei_count_statuses', $counts, $comment_type );
	}

	/**
	 * Build the Courses dropdown for return in AJAX
	 *
	 * @since  1.7.0
	 * @return string
	 */
	public function courses_drop_down_html( $selected_course_id = 0 ) {

		$html = '';

		$course_args = array(
			'post_type'        => 'course',
			'posts_per_page'   => -1,
			'orderby'          => 'title',
			'order'            => 'ASC',
			'post_status'      => 'any',
			'suppress_filters' => 0,
			'fields'           => 'ids',
		);
		/**
		 * Filter the arguments used to query for courses in the grading dropdown.
		 *
		 * @hook sensei_grading_filter_courses
		 *
		 * @param {array} $course_args Array of arguments for the query.
		 * @return {array} Filtered arguments.
		 */
		$courses = get_posts( apply_filters( 'sensei_grading_filter_courses', $course_args ) );

		$html .= '<option value="">' . __( 'Select a course', 'sensei-lms' ) . '</option>';
		if ( $courses ) {
			foreach ( $courses as $course_id ) {
				$html .= '<option value="' . esc_attr( absint( $course_id ) ) . '" ' . selected( $course_id, $selected_course_id, false ) . '>' . esc_html( get_the_title( $course_id ) ) . '</option>' . "\n";
			}
		}

		return $html;
	}

	/**
	 * Build the Lessons dropdown for return in AJAX
	 *
	 * @since  1.?
	 * @return string
	 */
	public function get_lessons_dropdown() {
		if ( ! isset( $_GET['course_id'] ) ) {
			// Try deprecated functionality as a fallback.
			// phpcs:ignore WordPress.Security.NonceVerification -- No modifications are made here.
			if ( isset( $_POST['data'] ) ) {
				_doing_it_wrong(
					'get_lessons_dropdown',
					'The get_lessons_dropdown AJAX call should be a GET request with parameter "course_id".',
					'1.12.2'
				);
				$this->deprecated_get_lessons_dropdown();
			}
			wp_die();
		}

		// Get course ID.
		$course_id = intval( $_GET['course_id'] );

		echo wp_kses(
			$this->lessons_drop_down_html( $course_id ),
			array(
				'option' => array(
					'selected' => array(),
					'value'    => array(),
				),
			)
		);

		wp_die(); // WordPress may print out a spurious zero without this can be particularly bad if using JSON
	}

	/**
	 * Deprecated version of the get_lessons_dropdown function, used as a
	 * fallback.
	 */
	private function deprecated_get_lessons_dropdown() {
		// Parse POST data
		// phpcs:ignore WordPress.Security.NonceVerification -- No modifications are made here.
		$data        = $_POST['data'];
		$course_data = array();
		parse_str( $data, $course_data );

		$course_id = intval( $course_data['course_id'] );

		echo wp_kses(
			$this->lessons_drop_down_html( $course_id ),
			array(
				'option' => array(
					'selected' => array(),
					'value'    => array(),
				),
			)
		);

		die(); // WordPress may print out a spurious zero without this can be particularly bad if using JSON
	}

	public function lessons_drop_down_html( $course_id = 0, $selected_lesson_id = 0 ) {

		$html = '';
		if ( 0 < intval( $course_id ) ) {

			$lesson_args = array(
				'post_type'        => 'lesson',
				'posts_per_page'   => -1,
				'orderby'          => 'title',
				'order'            => 'ASC',
				'meta_key'         => '_lesson_course',
				'meta_value'       => $course_id,
				'post_status'      => array( 'publish', 'private' ),
				'suppress_filters' => 0,
				'fields'           => 'ids',
			);
			/**
			 * Filter the arguments used to query for lessons in the grading dropdown.
			 *
			 * @hook sensei_grading_filter_lessons
			 *
			 * @param {array} $lesson_args Array of arguments for the query.
			 * @return {array} Filtered arguments.
			 */
			$lessons = get_posts( apply_filters( 'sensei_grading_filter_lessons', $lesson_args ) );

			$html .= '<option value="">' . esc_html__( 'Select a lesson', 'sensei-lms' ) . '</option>';
			if ( $lessons ) {
				foreach ( $lessons as $lesson_id ) {
					$html .= '<option value="' . esc_attr( absint( $lesson_id ) ) . '" ' . selected( $lesson_id, $selected_lesson_id, false ) . '>' . esc_html( get_the_title( $lesson_id ) ) . '</option>' . "\n";
				}
			}
		}

		return $html;
	}

	/**
	 * The process grading function handles admin grading submissions.
	 *
	 * This function is hooked on to admin_init. It simply accepts
	 * the grades as the Grader selected theme and saves the total grade and
	 * individual question grades.
	 *
	 * @return bool
	 */
	public function admin_process_grading_submission() {

		if ( ! isset( $_POST['sensei_manual_grade'] )
			|| ! wp_verify_nonce( $_POST['_wp_sensei_manual_grading_nonce'], 'sensei_manual_grading' )
			|| ! isset( $_GET['quiz_id'] )
			|| $_GET['quiz_id'] != $_POST['sensei_manual_grade'] ) {

			return false; // exit and do not grade.

		}

		$quiz_id = $_GET['quiz_id'];
		$user_id = $_GET['user'];

		$questions            = Sensei_Utils::sensei_get_quiz_questions( $quiz_id );
		$quiz_lesson_id       = Sensei()->quiz->get_lesson_id( $quiz_id );
		$quiz_grade           = 0;
		$quiz_grade_total     = $_POST['quiz_grade_total'];
		$all_question_grades  = array();
		$all_answers_feedback = array();

		foreach ( $questions as $question ) {

			$question_id = $question->ID;

			if ( isset( $_POST[ 'question_' . $question_id . '_grade' ] ) ) {

				$question_grade = absint( wp_unslash( $_POST[ 'question_' . $question_id . '_grade' ] ) ) ?? 0;

				// add data to the array that will, after the loop, be stored on the lesson status
				$all_question_grades[ $question_id ] = $question_grade;

				// tally up the total quiz grade
				$quiz_grade += $question_grade;

			} // endif

			// Question answer feedback / notes
			$question_feedback = '';
			if ( isset( $_POST['questions_feedback'][ $question_id ] ) ) {

				$question_feedback = wp_unslash( $_POST['questions_feedback'][ $question_id ] );

			}
			$all_answers_feedback[ $question_id ] = $question_feedback;

		}

		// store all question grades on the lesson status
		Sensei()->quiz->set_user_grades( $all_question_grades, $quiz_lesson_id, $user_id );

		// store the feedback from grading
		Sensei()->quiz->save_user_answers_feedback( $all_answers_feedback, $quiz_lesson_id, $user_id );

		$lesson_progress = Sensei()->lesson_progress_repository->get( $quiz_lesson_id, $user_id );
		if ( ! $lesson_progress ) {
			$lesson_progress = Sensei()->lesson_progress_repository->create( $quiz_lesson_id, $user_id );
		}

		$quiz_progress = Sensei()->quiz_progress_repository->get( $quiz_id, $user_id );
		if ( ! $quiz_progress ) {
			return false;
		}

		$lesson_metadata = array();
		$quiz_progress->ungrade();

		// $_POST['all_questions_graded'] is set when all questions have been graded
		// in the class sensei grading user quiz -> display()
		if ( $_POST['all_questions_graded'] == 'yes' ) {

			// Set the users total quiz grade.
			$grade = Sensei_Utils::quotient_as_absolute_rounded_percentage( $quiz_grade, $quiz_grade_total, 2 );
			Sensei_Utils::sensei_grade_quiz( $quiz_id, $grade, $user_id );

			// Duplicating what Frontend->sensei_complete_quiz() does.
			$pass_required = get_post_meta( $quiz_id, '_pass_required', true );
			$quiz_passmark = Sensei_Utils::as_absolute_rounded_number( get_post_meta( $quiz_id, '_quiz_passmark', true ), 2 );

			if ( $pass_required ) {
				// Student has reached the pass mark and lesson is complete.
				if ( $quiz_passmark <= $grade ) {
					// Due to our internal logic, we need to complete the lesson first.
					// This is because in the comments-based version the lesson status is used for both the lesson and the quiz.
					$lesson_progress->complete();
					$quiz_progress->pass();
				} else {
					$quiz_progress->fail();
				}
			}

			// Student only has to partake the quiz.
			else {
				$lesson_progress->complete();
				$quiz_progress->grade();
			}
		}

		// Due to our internal logic, we need to save the lesson progress first.
		// This is because in the comments-based version the lesson status is used for both the lesson and the quiz.
		// And in this context the quiz status should be preserved in the comments-based version.
		// For the tables-based version the order does not matter.
		Sensei()->lesson_progress_repository->save( $lesson_progress );
		Sensei()->quiz_progress_repository->save( $quiz_progress );
		if ( count( $lesson_metadata ) ) {
			foreach ( $lesson_metadata as $key => $value ) {
				update_comment_meta( $quiz_progress->get_id(), $key, $value );
			}
		}

		if ( $lesson_progress->is_complete() ) {
			/**
			 * Fires when a user completes a lesson.
			 * Here as part of grading a quiz.
			 *
			 * @hook sensei_user_lesson_end
			 *
			 * @param {int} $user_id The user ID.
			 * @param {int} $lesson_id The lesson ID.
			 */
			do_action( 'sensei_user_lesson_end', $user_id, $quiz_lesson_id );

		}

		if ( isset( $_POST['sensei_grade_next_learner'] ) && strlen( $_POST['sensei_grade_next_learner'] ) > 0 ) {

			$load_url = add_query_arg( array( 'message' => 'graded' ) );

		} elseif ( isset( $_POST['_wp_http_referer'] ) ) {

			$load_url = add_query_arg( array( 'message' => 'graded' ), $_POST['_wp_http_referer'] );

		} else {

			$load_url = add_query_arg( array( 'message' => 'graded' ) );

		}

		wp_safe_redirect( esc_url_raw( $load_url ) );
		exit;
	}

	public function get_redirect_url() {
		if ( ! isset( $_GET['course_id'] ) || ! isset( $_GET['lesson_id'] ) ) {
			// Try deprecated functionality as a fallback.
			// phpcs:ignore WordPress.Security.NonceVerification -- No modifications are made here.
			if ( isset( $_POST['data'] ) ) {
				_doing_it_wrong(
					'get_redirect_url',
					'The get_redirect_url AJAX call should be a GET request with parameters "course_id" and "lesson_id".',
					'1.12.2'
				);
				$this->deprecated_get_redirect_url();
			}
			wp_die();
		}

		// Get data.
		$course_id    = intval( $_GET['course_id'] );
		$lesson_id    = intval( $_GET['lesson_id'] );
		$grading_view = ( isset( $_GET['view'] ) && $_GET['view'] ) ? $_GET['view'] : 'ungraded';

		if ( 0 < $lesson_id && 0 < $course_id ) {
			echo esc_url_raw(
				apply_filters(
					'sensei_ajax_redirect_url',
					add_query_arg(
						array(
							'page'      => $this->page_slug,
							'lesson_id' => $lesson_id,
							'course_id' => $course_id,
							'view'      => $grading_view,
						),
						admin_url( 'admin.php' )
					)
				)
			);
		} else {
			echo '';
		}

		wp_die();
	}

	/**
	 * Deprecated version of the get_redirect_url function, used as a fallback.
	 */
	private function deprecated_get_redirect_url() {
		// Parse POST data
		// phpcs:ignore WordPress.Security.NonceVerification -- No modifications are made here.
		$data        = $_POST['data'];
		$lesson_data = array();
		parse_str( $data, $lesson_data );

		$lesson_id    = intval( $lesson_data['lesson_id'] );
		$course_id    = intval( $lesson_data['course_id'] );
		$grading_view = sanitize_text_field( $lesson_data['view'] );

		if ( 0 < $lesson_id && 0 < $course_id ) {
			echo esc_url_raw(
				apply_filters(
					'sensei_ajax_redirect_url',
					add_query_arg(
						array(
							'page'      => $this->page_slug,
							'lesson_id' => $lesson_id,
							'course_id' => $course_id,
							'view'      => $grading_view,
						),
						admin_url( 'admin.php' )
					)
				)
			);
		} else {
			echo '';
		}

		die();
	}

	public function add_grading_notices() {
		$page    = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : false;
		$message = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : false;

		if (
			$page
			&& $message
			&& $this->page_slug === $page
			&& 'graded' === $message
		) {
			?>
			<div class="grading-notice updated">
				<p><?php echo esc_html__( 'Quiz Graded Successfully!', 'sensei-lms' ); ?></p>
			</div>
			<?php
		}
	}

	public function sensei_grading_notices() {
		if ( isset( $_GET['action'] ) && 'graded' == $_GET['action'] ) {
			echo '<div class="grading-notice updated">';
				echo '<p>' . esc_html__( 'Quiz Graded Successfully!', 'sensei-lms' ) . '</p>';
			echo '</div>';
		}
	}

	/**
	 * Grade quiz automatically
	 *
	 * This function grades each question automatically if there all questions are auto gradable. If not
	 * the quiz will not be auto gradable.
	 *
	 * @since 1.7.4
	 *
	 * @param  integer $quiz_id         ID of quiz
	 * @param  array   $submitted questions id ans answers {
	 *          @type int $question_id
	 *          @type mixed $answer
	 * }
	 * @param  integer $total_questions Total questions in quiz (not used)
	 * @param string  $quiz_grade_type Optional defaults to auto
	 *
	 * @return int $quiz_grade total sum of all question grades
	 */
	public static function grade_quiz_auto( $quiz_id = 0, $submitted = array(), $total_questions = 0, $quiz_grade_type = 'auto' ) {

		if ( ! ( intval( $quiz_id ) > 0 ) || ! $submitted
			|| $quiz_grade_type != 'auto' ) {
			return false; // exit early
		}

		$user_id           = get_current_user_id();
		$lesson_id         = Sensei()->quiz->get_lesson_id( $quiz_id );
		$quiz_autogradable = true;

		/**
		 * Filter question types that can be automatically graded.
		 *
		 * This filter fires inside the auto grade quiz function and provides you with the default list.
		 *
		 * @hook sensei_autogradable_question_types
		 *
		 * @param {array} Types of questions, default: array( 'multiple-choice', 'boolean', 'gap-fill' ).
		 * @return {array} Filtered array of question types.
		 */
		$autogradable_question_types = apply_filters( 'sensei_autogradable_question_types', array( 'multiple-choice', 'boolean', 'gap-fill' ) );

		$grade_total         = 0;
		$all_question_grades = array();
		foreach ( $submitted as $question_id => $answer ) {

			// check if the question is autogradable, either by type, or because the grade is 0
			$question_type    = Sensei()->question->get_question_type( $question_id );
			$achievable_grade = Sensei()->question->get_question_grade( $question_id );
			// Question has a zero grade, so skip grading
			if ( 0 == $achievable_grade ) {
				$all_question_grades[ $question_id ] = $achievable_grade;
			} elseif ( in_array( $question_type, $autogradable_question_types ) ) {
				// Get user question grade
				$question_grade                      = self::grade_question_auto( $question_id, $question_type, $answer, $user_id );
				$all_question_grades[ $question_id ] = $question_grade;
				$grade_total                        += $question_grade;

			} else {

				// There is a question that cannot be autograded
				$quiz_autogradable = false;

			}
		}

		// Only if the whole quiz was autogradable do we set a grade
		if ( $quiz_autogradable ) {
			$quiz_total = Sensei_Utils::sensei_get_quiz_total( $quiz_id );
			$grade      = Sensei_Utils::quotient_as_absolute_rounded_percentage( $grade_total, $quiz_total, 2 );
			Sensei_Utils::sensei_grade_quiz( $quiz_id, $grade, $user_id, $quiz_grade_type );

		} else {

			$grade = new WP_Error( 'autograde', __( 'This quiz is not able to be automatically graded.', 'sensei-lms' ) );

		}

		// store the auto gradable grades. If the quiz is not auto gradable the grades can be use as the default
		// when doing manual grading.
		Sensei()->quiz->set_user_grades( $all_question_grades, $lesson_id, $user_id );

		return $grade;
	}

	/**
	 * Grade question automatically
	 *
	 * This function checks the question type and then grades it accordingly.
	 *
	 * @since 1.7.4
	 *
	 * @param integer $question_id
	 * @param string  $question_type of the standard Sensei question types
	 * @param string  $answer
	 * @param int     $user_id
	 *
	 * @return int $question_grade
	 */
	public static function grade_question_auto( $question_id = 0, $question_type = '', $answer = '', $user_id = 0 ) {

		if ( intval( $user_id ) == 0 ) {

			$user_id = get_current_user_id();

		}

		if ( ! ( intval( $question_id ) > 0 ) ) {

			return false;

		}

		Sensei()->question->get_question_type( $question_id );

		/**
		 * Applying a grade before the auto grading takes place.
		 *
		 * This filter is applied just before the question is auto graded. It fires in the context of a single question
		 * in the sensei_grade_question_auto function. It fires irrespective of the question type. If you return a value
		 * other than false the auto grade functionality will be ignored and your supplied grade will be user for this question.
		 *
		 * @hook sensei_pre_grade_question_auto
		 *
		 * @param {int|false} $question_grade Question grade, default false.
		 * @param {int}       $question_id    ID of the question being graded.
		 * @param {string}    $question_type  One of the Sensei question type.
		 * @param {string}    $answer         User supplied question answer.
		 * @return {int|false} Filtered question grade.
		 */
		$question_grade = apply_filters( 'sensei_pre_grade_question_auto', false, $question_id, $question_type, $answer );

		if ( false !== $question_grade ) {

			return $question_grade;

		}

		// auto grading core
		if ( in_array( $question_type, array( 'multiple-choice', 'boolean' ) ) ) {

			$right_answer = (array) get_post_meta( $question_id, '_question_right_answer', true );

			$answer = wp_unslash( $answer );
			$answer = (array) $answer;
			if ( is_array( $right_answer ) && count( $right_answer ) == count( $answer ) ) {
				// Loop through all answers ensure none are 'missing'
				$all_correct = true;
				foreach ( $answer as $check_answer ) {
					if ( ! in_array( $check_answer, $right_answer ) ) {
						$all_correct = false;
					}
				}
				// If all correct then grade
				if ( $all_correct ) {
					$question_grade = Sensei()->question->get_question_grade( $question_id );
				}
			}
		} elseif ( 'gap-fill' == $question_type ) {

			$question_grade = self::grade_gap_fill_question( $question_id, $answer );

		} else {

			/**
			 * Grading questions that are not auto gradable.
			 *
			 * This filter is applied the context of ta single question within the sensei_grade_question_auto function.
			 * It fires for all other questions types. It does not apply to 'multiple-choice'  , 'boolean' and gap-fill.
			 *
			 * @hook sensei_grade_question_auto
			 *
			 * @param {int}    $question_grade Question grade.
			 * @param {int}    $question_id    ID of the question being graded.
			 * @param {string} $question_type  One of the Sensei question type.
			 * @param {string} $answer         User supplied question answer
			 * @return {int} Filtered question grade.
			 */
			$question_grade = (int) apply_filters( 'sensei_grade_question_auto', $question_grade, $question_id, $question_type, $answer );

		}

		return $question_grade;
	}

	/**
	 * Grading logic specifically for the gap fill questions
	 *
	 * @since 1.9.0
	 * @param $question_id
	 * @param $user_answer
	 *
	 * @return bool | int false or the grade given to the user answer
	 */
	public static function grade_gap_fill_question( $question_id, $user_answer ) {

		$right_answer  = get_post_meta( $question_id, '_question_right_answer', true );
		$gapfill_array = explode( '||', $right_answer );

		$user_answer = wp_unslash( $user_answer );

		/**
		 * case sensitive grading filter
		 *
		 * alter the value simply use this code in your plugin or the themes functions.php
		 * add_filter( 'sensei_gap_fill_case_sensitive_grading','__return_true' );
		 *
		 * @since 1.9.0
		 *
		 * @hook sensei_gap_fill_case_sensitive_grading
		 *
		 * @param {bool} $do_case_sensitive_comparison Whether to do case sensitive comparison or not, default false.
		 * @return {bool} Filtered value.
		 */
		$do_case_sensitive_comparison = apply_filters( 'sensei_gap_fill_case_sensitive_grading', false );

		$regex_modifier = '';
		if ( $do_case_sensitive_comparison ) {
			$is_exact_answer_match = trim( $user_answer ) === trim( $gapfill_array[1] );
		} else {
			$is_exact_answer_match = trim( strtolower( $user_answer ) ) === trim( strtolower( $gapfill_array[1] ) );
			$regex_modifier        = 'i';
		}

		$regex_answer_check = '/' . addcslashes( $gapfill_array[1], '/' ) . '/' . $regex_modifier;

		if (
			$is_exact_answer_match
			|| 1 === @preg_match( $regex_answer_check, $user_answer )
		) {
			return Sensei()->question->get_question_grade( $question_id );
		}

		return false;
	}

	/**
	 * Counts the lessons that have been graded manually and automatically.
	 *
	 * @since 1.9.0
	 * @return int Number of graded lessons.
	 */
	public static function get_graded_lessons_count() {
		return self::get_grading_stats_service()->get_grade_totals()['count'];
	}

	/**
	 * Add together all the graded lesson grades.
	 *
	 * @since 1.9.0
	 * @return int Sum of all graded lesson grades.
	 */
	public static function get_graded_lessons_sum() {
		return (int) self::get_grading_stats_service()->get_grade_totals()['sum'];
	}

	/**
	 * Get average grade of all lessons graded in all the courses.
	 *
	 * @since 4.2.0
	 * @access public
	 * @return float Average value of all the graded lessons in all the courses.
	 */
	public function get_graded_lessons_average_grade() {
		$totals = self::get_grading_stats_service()->get_grade_totals();

		if ( 0 === $totals['count'] ) {
			return 0.0;
		}

		return $totals['sum'] / $totals['count'];
	}

	/**
	 * Get the sum of all grades for the given user.
	 *
	 * @since 1.9.0
	 * @param $user_id
	 * @return int
	 */
	public static function get_user_graded_lessons_sum( $user_id ) {
		return (int) self::get_grading_stats_service()->get_grade_totals( array( 'user_id' => $user_id ) )['sum'];
	}

	/**
	 * Get the sum of all user grades for the given lesson.
	 *
	 * @since 1.9.0
	 *
	 * @param int lesson_id
	 * @return int
	 */
	public static function get_lessons_users_grades_sum( $lesson_id ) {
		return (int) self::get_grading_stats_service()->get_grade_totals( array( 'lesson_id' => $lesson_id ) )['sum'];
	}

	/**
	 * Get the sum of all user grades for the given course.
	 *
	 * @since 1.9.0
	 *
	 * @param int $course_id
	 * @return int
	 */
	public static function get_course_users_grades_sum( $course_id ) {
		$lesson_ids = Sensei()->course->course_lessons( $course_id, 'any', 'ids' );
		if ( ! $lesson_ids ) {
			return 0;
		}

		return (int) self::get_grading_stats_service()->get_grade_totals( array( 'post__in' => $lesson_ids ) )['sum'];
	}

	/**
	 * Get the average grade of all courses.
	 *
	 * @since 4.2.0
	 * @access public
	 *
	 * @return double Average grade of all courses.
	 */
	public function get_courses_average_grade() {
		return self::get_grading_stats_service()->get_courses_average_grade();
	}
}

/**
 * Class WooThemes_Sensei_Grading
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Grading extends Sensei_Grading{}
