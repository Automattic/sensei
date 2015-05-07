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
 * - __construct
 * - grading_admin_menu()
 * - enqueue_scripts()
 * - enqueue_styles()
 * - load_data_table_files()
 * - load_data_object()
 * - grading_page()
 * - grading_default_view()
 * - grading_user_quiz_view()
 * - grading_headers()
 * - wrapper_container()
 * - grading_default_nav()
 * - grading_user_quiz_nav()
 * - get_stati()
 * - count_statuses()
 * - courses_drop_down_html()
 * - get_lessons_dropdown()
 * - lessons_drop_down_html()
 * - admin_process_grading_submission()
 * - get_redirect_url()
 * - add_grading_notices()
 * - sensei_grading_notices()
 * - grade_quiz_auto()
 * - grade_question_auto()
 */
class WooThemes_Sensei_Grading {
	public $token;
	public $name;
	public $file;
	public $page_slug;

	/**
	 * Constructor
	 * @since  1.3.0
     *
     * @param $file
	 */
	public function __construct ( $file ) {
		$this->name = __( 'Grading', 'woothemes-sensei' );
		$this->file = $file;
		$this->page_slug = 'sensei_grading';

		// Admin functions
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'grading_admin_menu' ), 20);
			add_action( 'grading_wrapper_container', array( $this, 'wrapper_container'  ) );
			if ( isset( $_GET['page'] ) && ( $_GET['page'] == $this->page_slug ) ) {
				add_action( 'admin_print_scripts', array( $this, 'enqueue_scripts' ) );
				add_action( 'admin_print_styles', array( $this, 'enqueue_styles' ) );
			}

			add_action( 'admin_init', array( $this, 'admin_process_grading_submission' ) );

			add_action( 'admin_notices', array( $this, 'add_grading_notices' ) );
//			add_action( 'sensei_grading_notices', array( $this, 'sensei_grading_notices' ) );
		} // End If Statement

		// Ajax functions
		if ( is_admin() ) {
			add_action( 'wp_ajax_get_lessons_dropdown', array( $this, 'get_lessons_dropdown' ) );
			add_action( 'wp_ajax_get_redirect_url', array( $this, 'get_redirect_url' ) );
		} // End If Statement
	} // End __construct()

	/**
	 * grading_admin_menu function.
	 * @since  1.3.0
	 * @access public
	 * @return void
	 */
	public function grading_admin_menu() {
		global $menu;

		if ( current_user_can( 'manage_sensei_grades' ) ) {
			$grading_page = add_submenu_page('sensei', __('Grading', 'woothemes-sensei'),  __('Grading', 'woothemes-sensei') , 'manage_sensei_grades', $this->page_slug, array( $this, 'grading_page' ) );
		}

	} // End grading_admin_menu()

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

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Load Grading JS
		wp_enqueue_script( 'sensei-grading-general', $woothemes_sensei->plugin_url . 'assets/js/grading-general' . $suffix . '.js', array( 'jquery' ), '1.5.2' );

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

		wp_enqueue_style( 'woothemes-sensei-settings-api', $woothemes_sensei->plugin_url . 'assets/css/settings.css', '', '1.7.0' );

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
									'grading-main',
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
		}
		else {
			$sensei_grading_object = new $object_name( $data, $optional_data );
		} // End If Statement
		if ( 'Main' == $name ) {
			$sensei_grading_object->prepare_items();
		} // End If Statement
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
		if ( isset( $_GET['quiz_id'] ) && 0 < intval( $_GET['quiz_id'] ) && isset( $_GET['user'] ) && 0 < intval( $_GET['user'] ) ) {
			$this->grading_user_quiz_view();
		}
		else {
			$this->grading_default_view();
		} // End If Statement
	} // End grading_page()

	/**
	 * grading_default_view default view for grading page
	 * @since  1.3.0
	 * @return void
	 */
	public function grading_default_view() {
		global $woothemes_sensei;
		// Load Grading data
		$this->load_data_table_files();

		if( !empty( $_GET['course_id'] ) ) {
			$course_id = intval( $_GET['course_id'] );
		}
		if( !empty( $_GET['lesson_id'] ) ) {
			$lesson_id = intval( $_GET['lesson_id'] );
		}
		if( !empty( $_GET['user_id'] ) ) {
			$user_id = intval( $_GET['user_id'] );
		}
		if( !empty( $_GET['view'] ) ) {
			$view = esc_html( $_GET['view'] );
		}
		$sensei_grading_overview = $this->load_data_object( 'Main', compact( 'course_id', 'lesson_id', 'user_id', 'view' ) );
		// Wrappers
		do_action( 'grading_before_container' );
		do_action( 'grading_wrapper_container', 'top' );
		$this->grading_headers();
		?>
		<div id="poststuff" class="sensei-grading-wrap">
			<div class="sensei-grading-main">
				<?php $sensei_grading_overview->display(); ?>
			</div>
			<div class="sensei-grading-extra">
				<?php do_action( 'sensei_grading_extra' ); ?>
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
		$user_id = 0;
		$quiz_id = 0;
		if( isset( $_GET['user'] ) ) {
			$user_id = intval( $_GET['user'] );
		}
		if( isset( $_GET['quiz_id'] ) ) {
			$quiz_id = intval( $_GET['quiz_id'] );
		}
		$sensei_grading_user_profile = $this->load_data_object( 'User_Quiz', $user_id, $quiz_id );
		// Wrappers
		do_action( 'grading_before_container' );
		do_action( 'grading_wrapper_container', 'top' );
		$this->grading_headers( array( 'nav' => 'user_quiz' ) );
		?>
		<div id="poststuff" class="sensei-grading-wrap user-profile">
			<div class="sensei-grading-main">
				<?php // do_action( 'sensei_grading_notices' ); ?>
				<?php $sensei_grading_user_profile->display(); ?>
			</div>
		</div>
		<?php
		do_action( 'grading_wrapper_container', 'bottom' );
		do_action( 'grading_after_container' );
	} // End grading_user_quiz_view()

	/**
	 * Outputs Grading general headers
	 * @since  1.3.0
	 * @return void
	 */
	public function grading_headers( $args = array( 'nav' => 'default' ) ) {
		global $woothemes_sensei;

		$function = 'grading_' . $args['nav'] . '_nav';
		$this->$function();
		?>
			<p class="powered-by-woo"><?php _e( 'Powered by', 'woothemes-sensei' ); ?><a href="http://www.woothemes.com/" title="WooThemes"><img src="<?php echo $woothemes_sensei->plugin_url; ?>assets/images/woothemes.png" alt="WooThemes" /></a></p>
		<?php
		do_action( 'sensei_grading_after_headers' );
	} // End grading_headers()

	/**
	 * wrapper_container wrapper for Grading area
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
	 * Default nav area for Grading
	 * @since  1.3.0
	 * @return void
	 */
	public function grading_default_nav() {
		global $woothemes_sensei, $wp_version;

		$title = sprintf( '<a href="%s">%s</a>', add_query_arg( array( 'page' => $this->page_slug ), admin_url( 'admin.php' ) ), esc_html( $this->name ) );
		if ( isset( $_GET['course_id'] ) ) { 
			$course_id = intval( $_GET['course_id'] );
			if ( version_compare($wp_version, '4.1', '>=') ) {
				$url = add_query_arg( array( 'page' => $this->page_slug, 'course_id' => $course_id ), admin_url( 'admin.php' ) );
				$title .= sprintf( '&nbsp;&nbsp;<span class="course-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>', $url, get_the_title( $course_id ) );
			}
			else {
				$title .= sprintf( '&nbsp;&nbsp;<span class="course-title">&gt;&nbsp;&nbsp;%s</span>', get_the_title( $course_id ) ); 
			}
		}
		if ( isset( $_GET['lesson_id'] ) ) { 
			$lesson_id = intval( $_GET['lesson_id'] );
			$title .= '&nbsp;&nbsp;<span class="lesson-title">&gt;&nbsp;&nbsp;' . get_the_title( intval( $lesson_id ) ) . '</span>'; 
		}
		if ( isset( $_GET['user_id'] ) && 0 < intval( $_GET['user_id'] ) ) {

            $user_name = $woothemes_sensei->learners->get_learner_full_name( $_GET['user_id'] );
			$title .= '&nbsp;&nbsp;<span class="user-title">&gt;&nbsp;&nbsp;' . $user_name . '</span>';

		} // End If Statement
		?>
			<h2><?php echo apply_filters( 'sensei_grading_nav_title', $title ); ?></h2>
		<?php
	} // End grading_default_nav()

	/**
	 * Nav area for Grading specific users' quiz answers
	 * @since  1.3.0
	 * @return void
	 */
	public function grading_user_quiz_nav() {
		global $woothemes_sensei, $wp_version;

		$title = sprintf( '<a href="%s">%s</a>', add_query_arg( array( 'page' => $this->page_slug ), admin_url( 'admin.php' ) ), esc_html( $this->name ) );
		if ( isset( $_GET['quiz_id'] ) ) { 
			$quiz_id = intval( $_GET['quiz_id'] );
			$lesson_id = get_post_meta( $quiz_id, '_quiz_lesson', true );
			$course_id = get_post_meta( $lesson_id, '_lesson_course', true );
			if ( version_compare($wp_version, '4.1', '>=') ) {
				$url = add_query_arg( array( 'page' => $this->page_slug, 'course_id' => $course_id ), admin_url( 'admin.php' ) );
				$title .= sprintf( '&nbsp;&nbsp;<span class="course-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>', $url, get_the_title( $course_id ) );
			}
			else {
				$title .= sprintf( '&nbsp;&nbsp;<span class="course-title">&gt;&nbsp;&nbsp;%s</span>', get_the_title( $course_id ) ); 
			}
			$url = add_query_arg( array( 'page' => $this->page_slug, 'lesson_id' => $lesson_id ), admin_url( 'admin.php' ) );
			$title .= sprintf( '&nbsp;&nbsp;<span class="lesson-title">&gt;&nbsp;&nbsp;<a href="%s">%s</a></span>', $url, get_the_title( $lesson_id ) ); 
		}
		if ( isset( $_GET['user'] ) && 0 < intval( $_GET['user'] ) ) {

            $user_name = $woothemes_sensei->learners->get_learner_full_name( $_GET['user'] );
			$title .= '&nbsp;&nbsp;<span class="user-title">&gt;&nbsp;&nbsp;' . $user_name . '</span>';

		} // End If Statement
		?>
			<h2><?php echo apply_filters( 'sensei_grading_nav_title', $title ); ?></h2>
		<?php
	} // End grading_user_quiz_nav()

	/**
	 * Return array of valid statuses for either Course or Lesson
	 * @since  1.7.0
	 * @return array
	 */
	public function get_stati( $type ) {
		$statuses = array();
		switch( $type ) {
			case 'course' :
				$statuses = array(
					'in-progress',
					'complete',
				);
				break;

			case 'lesson' :
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
	 * Count the various statuses for Course or Lesson
	 * Very similar to get_comment_count()
	 * @since  1.7.0
	 * @param  array $args (default: array())
	 * @return object
	 */
	public function count_statuses( $args = array() ) {
		global $woothemes_sensei, $wpdb;

        /**
         * Filter fires inside Sensei_Grading::count_statuses
         *
         * Alter the the post_in array to determine which posts the
         * comment query should be limited to.
         * @since 1.8.0
         * @param array $args
         */
        $args = apply_filters( 'sensei_count_statuses_args', $args );

		if ( 'course' == $args['type'] ) {
			$type = 'sensei_course_status';
		}
		else {
			$type = 'sensei_lesson_status';
		}
		$cache_key = 'sensei-' . $args['type'] . '-statuses';

		$query = "SELECT comment_approved, COUNT( * ) AS total FROM {$wpdb->comments} WHERE comment_type = %s ";

        // Restrict to specific posts
		if ( isset( $args['post__in'] ) && is_array( $args['post__in'] ) ) {
			$query .= ' AND comment_post_ID IN (' . implode( ',', array_map( 'absint', $args['post__in'] ) ) . ')';
		}
		elseif ( !empty( $args['post_id'] ) ) {
			$query .= $wpdb->prepare( ' AND comment_post_ID = %d', $args['post_id'] );
		}
		// Restrict to specific users
		if ( isset( $args['user_id'] ) && is_array( $args['user_id'] ) ) {
			$query .= ' AND user_id IN (' . implode( ',', array_map( 'absint', $args['user_id'] ) ) . ')';
		}
		elseif ( !empty( $args['user_id'] ) ) {
			$query .= $wpdb->prepare( ' AND user_id = %d', $args['user_id'] );
		}
		$query .= ' GROUP BY comment_approved';

		$counts = wp_cache_get( $cache_key, 'counts' );
		if ( false === $counts ) {
			$sql = $wpdb->prepare( $query, $type );
			$results = (array) $wpdb->get_results( $sql, ARRAY_A );
			$counts = array_fill_keys( $this->get_stati( $type ), 0 );

			foreach ( $results as $row ) {
				$counts[ $row['comment_approved'] ] = $row['total'];
			}
			wp_cache_set( $cache_key, $counts, 'counts' );
		}

		if( ! isset( $counts['graded'] ) ) {
			$counts['graded'] = 0;
		}

		if( ! isset( $counts['ungraded'] ) ) {
			$counts['ungraded'] = 0;
		}

		if( ! isset( $counts['passed'] ) ) {
			$counts['passed'] = 0;
		}

		if( ! isset( $counts['failed'] ) ) {
			$counts['failed'] = 0;
		}

		if( ! isset( $counts['in-progress'] ) ) {
			$counts['in-progress'] = 0;
		}

		if( ! isset( $counts['complete'] ) ) {
			$counts['complete'] = 0;
		}

		return apply_filters( 'sensei_count_statuses', $counts, $type );
	} // End sensei_count_statuses()

	/**
	 * Build the Courses dropdown for return in AJAX
	 * @since  1.7.0
	 * @return string
	 */
	public function courses_drop_down_html( $selected_course_id = 0 ) {

		$html = '';

		$course_args = array(   'post_type'         => 'course',
								'numberposts'       => -1,
								'orderby'           => 'title',
								'order'             => 'ASC',
								'post_status'       => 'any',
								'suppress_filters'  => 0,
								'fields'            => 'ids',
							);
		$courses = get_posts( apply_filters( 'sensei_grading_filter_courses', $course_args ) );

		$html .= '<option value="">' . __( 'Select a course', 'woothemes-sensei' ) . '</option>';
		if ( count( $courses ) > 0 ) {
			foreach ($courses as $course_id){
				$html .= '<option value="' . esc_attr( absint( $course_id ) ) . '" ' . selected( $course_id, $selected_course_id, false ) . '>' . esc_html( get_the_title( $course_id ) ) . '</option>' . "\n";
			} // End For Loop
		} // End If Statement

		return $html;
	} // End lessons_drop_down_html()

	/**
	 * Build the Lessons dropdown for return in AJAX
	 * @since  1.?
	 * @return string
	 */
	public function get_lessons_dropdown() {

		$posts_array = array();

		// Parse POST data
		$data = $_POST['data'];
		$course_data = array();
		parse_str($data, $course_data);

		$course_id = intval( $course_data['course_id'] );

		$html = $this->lessons_drop_down_html( $course_id );

		echo $html;
		die(); // WordPress may print out a spurious zero without this can be particularly bad if using JSON
	}

	public function lessons_drop_down_html( $course_id = 0, $selected_lesson_id = 0 ) {

		$html = '';
		if ( 0 < intval( $course_id ) ) {

			$lesson_args = array( 'post_type'       => 'lesson',
								'numberposts'       => -1,
								'orderby'           => 'title',
								'order'             => 'ASC',
								'meta_key'          => '_lesson_course',
								'meta_value'        => $course_id,
								'post_status'       => 'publish',
								'suppress_filters'  => 0,
								'fields'            => 'ids',
								);
			$lessons = get_posts( apply_filters( 'sensei_grading_filter_lessons', $lesson_args ) );

			$html .= '<option value="">' . __( 'Select a lesson', 'woothemes-sensei' ) . '</option>';
			if ( count( $lessons ) > 0 ) {
				foreach ( $lessons as $lesson_id ){
					$html .= '<option value="' . esc_attr( absint( $lesson_id ) ) . '" ' . selected( $lesson_id, $selected_lesson_id, false ) . '>' . esc_html( get_the_title( $lesson_id ) ) . '</option>' . "\n";
				} // End For Loop
			} // End If Statement

		} // End If Statement

		return $html;
	} // End lessons_drop_down_html()

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

        // NEEDS REFACTOR/OPTIMISING, such as combining the various meta data stored against the sensei_user_answer entry
        if( ! isset( $_POST['sensei_manual_grade'] )
            || ! wp_verify_nonce( $_POST['_wp_sensei_manual_grading_nonce'], 'sensei_manual_grading' )
            || ! isset( $_GET['quiz_id'] )
            || $_GET['quiz_id'] != $_POST['sensei_manual_grade'] ) {

            return false; //exit and do not grade

        }

        $quiz_id = $_GET['quiz_id'];
        $user_id = $_GET['user'];

        global $woothemes_sensei;
        $questions = WooThemes_Sensei_Utils::sensei_get_quiz_questions( $quiz_id );
        $quiz_lesson_id =  $woothemes_sensei->quiz->get_lesson_id( $quiz_id );
        $quiz_grade = 0;
        $count = 0;
        $quiz_grade_total = $_POST['quiz_grade_total'];
        $all_question_grades = array();
        $all_answers_feedback = array();

        foreach( $questions as $question ) {

            ++$count;
            $question_id = $question->ID;

            if( isset( $_POST[ 'question_' . $question_id ] ) ) {

                $question_grade = 0;
                if( $_POST[ 'question_' . $question_id ] == 'right' ) {

                    $question_grade = $_POST[ 'question_' . $question_id . '_grade' ];

                }

                // add data to the array that will, after the loop, be stored on the lesson status
                $all_question_grades[ $question_id ] = $question_grade;

                // tally up the total quiz grade
                $quiz_grade += $question_grade;

            } // endif

            // Question answer feedback / notes
            $question_feedback = '';
            if( isset( $_POST[ 'questions_feedback' ][ $question_id ] ) ){

                $question_feedback = wp_unslash( $_POST[ 'questions_feedback' ][ $question_id ] );

            }
            $all_answers_feedback[ $question_id ] = $question_feedback;

        } // end for each $questions

        //store all question grades on the lesson status
        $woothemes_sensei->quiz->set_user_grades( $all_question_grades, $quiz_lesson_id , $user_id );

        //store the feedback from grading
        $woothemes_sensei->quiz->save_user_answers_feedback( $all_answers_feedback, $quiz_lesson_id , $user_id );

        // $_POST['all_questions_graded'] is set when all questions have been graded
        // in the class sensei grading user quiz -> display()
        if( $_POST['all_questions_graded'] == 'yes' ) {

            // set the users total quiz grade
            $grade = abs( round( ( doubleval( $quiz_grade ) * 100 ) / ( $quiz_grade_total ), 2 ) );
            WooThemes_Sensei_Utils::sensei_grade_quiz( $quiz_id, $grade, $user_id );

            // Duplicating what Frontend->sensei_complete_quiz() does
            $pass_required = get_post_meta( $quiz_id, '_pass_required', true );
            $quiz_passmark = abs( round( doubleval( get_post_meta( $quiz_id, '_quiz_passmark', true ) ), 2 ) );
            $lesson_metadata = array();
            if ( $pass_required ) {
                // Student has reached the pass mark and lesson is complete
                if ( $quiz_passmark <= $grade ) {
                    $lesson_status = 'passed';
                }
                else {
                    $lesson_status = 'failed';
                } // End If Statement
            }
            // Student only has to partake the quiz
            else {
                $lesson_status = 'graded';
            }
            $lesson_metadata['grade'] = $grade; // Technically already set as part of "WooThemes_Sensei_Utils::sensei_grade_quiz()" above

            WooThemes_Sensei_Utils::update_lesson_status( $user_id, $quiz_lesson_id, $lesson_status, $lesson_metadata );

            if(  in_array( $lesson_status, array( 'passed', 'graded'  ) ) ) {

                /**
                 * Summary.
                 *
                 * Description.
                 *
                 * @since 1.7.0
                 *
                 * @param int  $user_id
                 * @param int $quiz_lesson_id
                 */
                do_action( 'sensei_user_lesson_end', $user_id, $quiz_lesson_id );

            } // end if in_array

        }// end if $_POST['all_que...

        if( isset( $_POST['sensei_grade_next_learner'] ) && strlen( $_POST['sensei_grade_next_learner'] ) > 0 ) {

            $load_url = add_query_arg( array( 'message' => 'graded' ) );

        } elseif ( isset( $_POST['_wp_http_referer'] ) ) {

            $load_url = add_query_arg( array( 'message' => 'graded' ), $_POST['_wp_http_referer'] );

        } else {

            $load_url = add_query_arg( array( 'message' => 'graded' ) );

        }

        wp_safe_redirect( $load_url );
        exit;

    } // end admin_process_grading_submission

	public function get_redirect_url() {
		// Parse POST data
		$data = $_POST['data'];
		$lesson_data = array();
		parse_str($data, $lesson_data);

		$lesson_id = intval( $lesson_data['lesson_id'] );
		$course_id = intval( $lesson_data['course_id'] );
		$grading_view = sanitize_text_field( $lesson_data['view'] );

		$redirect_url = '';
		if ( 0 < $lesson_id && 0 < $course_id ) {
			$redirect_url = apply_filters( 'sensei_ajax_redirect_url', add_query_arg( array( 'page' => $this->page_slug, 'lesson_id' => $lesson_id, 'course_id' => $course_id, 'view' => $grading_view ), admin_url( 'admin.php' ) ) );
		} // End If Statement

		echo $redirect_url;
		die();
	}

	public function add_grading_notices() {
		if( isset( $_GET['page'] ) && $this->page_slug == $_GET['page'] && isset( $_GET['message'] ) && $_GET['message'] ) {
			if( 'graded' == $_GET['message'] ) {
				$msg = array(
					'updated',
					__( 'Quiz Graded Successfully!', 'woothemes-sensei' ),
				);
			}
			?>
			<div class="grading-notice <?php echo $msg[0]; ?>">
				<p><?php echo $msg[1]; ?></p>
			</div>
			<?php
		}
	}

	public function sensei_grading_notices() {
		if ( isset( $_GET['action'] ) && 'graded' == $_GET['action'] ) {
			echo '<div class="grading-notice updated">';
				echo '<p>' . __( 'Quiz Graded Successfully!', 'woothemes-sensei' ) . '</p>';
			echo '</div>';
		} // End If Statement
	} // End sensei_grading_notices()

    /**
     * Grade quiz automatically
     *
     * This function grades each question automatically if there all questions are auto gradable. If not
     * the quiz will not be auto gradable.
     *
     * @since 1.7.4
     *
     * @param  integer $quiz_id         ID of quiz
     * @param  array $submitted questions id ans answers {
     *          @type int $question_id
     *          @type mixed $answer
     * }
     * @param  integer $total_questions Total questions in quiz (not used)
     * @param string $quiz_grade_type Optional defaults to auto
     *
     * @return int $quiz_grade total sum of all question grades
     */
    public static function grade_quiz_auto( $quiz_id = 0, $submitted = array(), $total_questions = 0, $quiz_grade_type = 'auto' ) {

        if( ! ( intval( $quiz_id ) > 0 )  || ! $submitted
            || $quiz_grade_type != 'auto' ) {
            return false; // exit early
        }

        global $woothemes_sensei;
        $user_id = get_current_user_id();
        $lesson_id =  $woothemes_sensei->quiz->get_lesson_id(  $quiz_id ) ;
        $quiz_autogradable = true;

        /**
         * Filter the types of question types that can be automatically graded.
         *
         * This filter fires inside the auto grade quiz function and provides you with the default list.
         *
         * @param array {
         *      'multiple-choice',
         *      'boolean',
         *      'gap-fill'.
         * }
         */
        $autogradable_question_types = apply_filters( 'sensei_autogradable_question_types', array( 'multiple-choice', 'boolean', 'gap-fill' ) );

        $grade_total = 0;
        $all_question_grades = array();
        foreach( $submitted as $question_id => $answer ) {

            // check if the question is autogradable
            $question_type = $woothemes_sensei->question->get_question_type( $question_id );
            if ( in_array( $question_type, $autogradable_question_types ) ) {

                // Get user question grade
                $question_grade = WooThemes_Sensei_Utils::sensei_grade_question_auto( $question_id, $question_type, $answer, $user_id );
                $all_question_grades[ $question_id ] = $question_grade;
                $grade_total += $question_grade;

            } else {

                // There is a question that cannot be autograded
                $quiz_autogradable = false;

            } // end if in_array( $question_type...

        }// end for each question

        // Only if the whole quiz was autogradable do we set a grade
        if ( $quiz_autogradable ) {

            $quiz_total = WooThemes_Sensei_Utils::sensei_get_quiz_total( $quiz_id );

            $grade = abs( round( ( doubleval( $grade_total ) * 100 ) / ( $quiz_total ), 2 ) );
            WooThemes_Sensei_Utils::sensei_grade_quiz( $quiz_id, $grade, $user_id, $quiz_grade_type );

        } else {

            $grade = new WP_Error( 'autograde', __( 'This quiz is not able to be automatically graded.', 'woothemes-sensei' ) );

        }

        // store the auto gradable grades. If the quiz is not auto gradable the grades can be use as the default
        // when doing manual grading.
        $woothemes_sensei->quiz-> set_user_grades( $all_question_grades, $lesson_id, $user_id );

        return $grade;

    } // End grade_quiz_auto()

    /**
     * Grade question automatically
     *
     * This function checks the question typ and then grades it accordingly.
     *
     * @since 1.7.4
     *
     * @param integer $question_id
     * @param string $question_type of the standard Sensei question types
     * @param string $answer
     * @param int $user_id
     *
     * @return int $question_grade
     */
    public static function grade_question_auto( $question_id = 0, $question_type = '', $answer = '', $user_id = 0 ) {

        if( intval( $user_id ) == 0 ) {

            $user_id = get_current_user_id();

        }

        if( ! ( intval( $question_id ) > 0 ) ) {

            return false;

        }

        global $woothemes_sensei;
        $woothemes_sensei->question->get_question_type( $question_id );

        /**
         * Applying a grade before the auto grading takes place.
         *
         * This filter is applied just before the question is auto graded. It fires in the context of ta single question
         * in the sensei_grade_question_auto function. It fire irrespective of the question type. If you return a grade
         * more than 0 the auto grade functionality with be ignored and your supplied grade will be user for this question.
         *
         * @param int $question_grade default zero
         * @param int $question_id
         * @param string $question_type one of the Sensei question type.
         * @param string $answer user supplied question answer
         */
        $question_grade = apply_filters( 'sensei_pre_grade_question_auto',0 , $question_id, $question_type, $answer );

        if ( $question_grade > 0  ) {

            return $question_grade;

        }

        // auto grading core
        if( in_array( $question_type ,  array( 'multiple-choice'  , 'boolean'  ) )   ){

            $right_answer = (array) get_post_meta( $question_id, '_question_right_answer', true );

            if( 0 == get_magic_quotes_gpc() ) {
                $answer = wp_unslash( $answer );
            }
            $answer = (array) $answer;
            if ( is_array( $right_answer ) && count( $right_answer ) == count( $answer ) ) {
                // Loop through all answers ensure none are 'missing'
                $all_correct = true;
                foreach ( $answer as $check_answer ) {
                    if ( !in_array( $check_answer, $right_answer ) ) {
                        $all_correct = false;
                    }
                }
                // If all correct then grade
                if ( $all_correct ) {
                    $question_grade = ( int ) get_post_meta( $question_id, '_question_grade', true );
                    if( ! $question_grade || $question_grade == '' ) {
                        $question_grade = 1;
                    }
                }
            }

        } elseif( 'gap-fill' == $question_type ){

            $right_answer = get_post_meta( $question_id, '_question_right_answer', true );

            if( 0 == get_magic_quotes_gpc() ) {
                $answer = wp_unslash( $answer );
            }
            $gapfill_array = explode( '||', $right_answer );
            // Check that the 'gap' is "exactly" equal to the given answer
            if ( trim(strtolower($gapfill_array[1])) == trim(strtolower($answer)) ) {
                $question_grade = ( int ) get_post_meta( $question_id, '_question_grade', true );
                if ( empty($question_grade) ) {
                    $question_grade = 1;
                }
            }
            else if (@preg_match('/' . $gapfill_array[1] . '/i', null) !== FALSE) {
                if (preg_match('/' . $gapfill_array[1] . '/i', $answer)) {
                    $question_grade = ( int ) get_post_meta( $question_id, '_question_grade', true );
                    if ( empty($question_grade) ) {
                        $question_grade = 1;
                    }
                }
            }

        } else{

            /**
             * Grading questions that are not auto gradable.
             *
             * This filter is applied the context of ta single question within the sensei_grade_question_auto function.
             * It fires for all other questions types. It does not apply to 'multiple-choice'  , 'boolean' and gap-fill.
             *
             * @param int $question_grade default zero
             * @param int $question_id
             * @param string $question_type one of the Sensei question type.
             * @param string $answer user supplied question answer
             */
            $question_grade = ( int ) apply_filters( 'sensei_grade_question_auto', $question_grade, $question_id, $question_type, $answer );

        } // end if $question_type

        return $question_grade;
    } // end grade_question_auto

} // End Class
