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
			add_action( 'admin_init', array( &$this, 'process_grading' ) );
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
		global $woothemes_sensei;
		$posts_array = array();

		// Parse POST data
		$data = $_POST['data'];
		$lesson_data = array();
		parse_str($data, $lesson_data);

		$lesson_id = intval( $lesson_data['lesson_id'] );

		$args_array = array();
		// Get the data required
		$users = get_users( $args_array );
		$output_counter = 0;
		$lesson_quizzes = $woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson_id );
		// Get Quiz ID
	    foreach ($lesson_quizzes as $quiz_item) {
	    	$lesson_quiz_id = $quiz_item->ID;
	    } // End For Loop
	    // Output the users data
		$html = '<table class="widefat">
					<thead>
					    <tr>
					        <th class="hidden">#</th>
					        <th>' . __( 'User', 'woothemes-sensei' ) . '</th>
					        <th style="width:125px;">' . __( 'Status', 'woothemes-sensei' ) . '</th>
					        <th style="width:125px;">' . __( 'Grade', 'woothemes-sensei' ) . '</th>
					    </tr>
					</thead>
					<tfoot>
					    <tr>
					    <th class="hidden">#</th>
					    <th>' . __( 'User', 'woothemes-sensei' ) . '</th>
					    <th>' . __( 'Status', 'woothemes-sensei' ) . '</th>
					    <th>' . __( 'Grade', 'woothemes-sensei' ) . '</th>
					    </tr>
					</tfoot>
					<tbody>';
		$to_be_graded_html = '<div id="to-be-graded-container" class="grading-table-container"><h3>' . __( 'Learners to be Graded', 'woothemes-sensei' ) . '</h3>' . $html;
		$to_be_graded_count = 0;
		$in_progress_html = '<div id="in-progress-container" class="grading-table-container"><h3>' . __( 'Learners in Progress', 'woothemes-sensei' ) . '</h3>' . $html;
		$in_progress_count = 0;
		$graded_html = '<div id="graded-container" class="grading-table-container"><h3>' . __( 'Graded Learners', 'woothemes-sensei' ) . '</h3>' . $html;
		$graded_count = 0;
		foreach ( $users as $user_key => $user_item ) {
			// Get Quiz Answers
			$lesson_start_date =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_id, 'user_id' => $user_item->ID, 'type' => 'sensei_lesson_start', 'field' => 'comment_date' ) );
			// Check if Lesson is complete
			$lesson_end_date =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_id, 'user_id' => $user_item->ID, 'type' => 'sensei_lesson_end', 'field' => 'comment_date' ) );
			// Quiz Grade
			$lesson_grade =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_quiz_id, 'user_id' => $user_item->ID, 'type' => 'sensei_quiz_grade', 'field' => 'comment_content' ) );
			$quiz_grade = __( 'No Grade', 'woothemes-sensei' );
			if ( 0 < intval( $lesson_grade ) ) {
		    	$quiz_grade = $lesson_grade . '%';
		    } // End If Statement

		    /**
		     * Logic Rules
		     *
		     * To be Graded
		     * sensei_lesson_end AND !sensei_quiz_grade
		     *
		     * In Progress
		     * sensei_lesson_start AND !sensei_lesson_end
		     *
		     * Graded
		     * sensei_quiz_grade
		     */
			if ( ( isset( $lesson_end_date ) && '' != $lesson_end_date ) && ( isset( $lesson_grade ) && '' == $lesson_grade ) ) {
				// To Be Graded
				$to_be_graded_html .= '<tr>';
					$to_be_graded_html .= '<td class="table-count hidden">Test</td>';
						$to_be_graded_html .= '<td><a href="' . add_query_arg( array( 'page' => 'sensei_grading', 'user' => $user_item->ID, 'quiz_id' => $lesson_quiz_id ), admin_url( 'edit.php?post_type=lesson' ) ) . '">'.$user_item->user_login.'</a></td>';
						$to_be_graded_html .= '<td>' . __( 'Submitted for Grading', 'woothemes-sensei' ) . '</td>';
						$to_be_graded_html .= '<td>' . $quiz_grade . '</td>';
				$to_be_graded_html .= '</tr>';
				$to_be_graded_count++;
			} elseif ( ( isset( $lesson_start_date ) && '' != $lesson_start_date ) && ( isset( $lesson_end_date ) && '' == $lesson_end_date  ) ) {
				// In Progress
				$in_progress_html .= '<tr>';
					$in_progress_html .= '<td class="table-count hidden">Test</td>';
						$in_progress_html .= '<td><a href="' . add_query_arg( array( 'page' => 'sensei_grading', 'user' => $user_item->ID, 'quiz_id' => $lesson_quiz_id ), admin_url( 'edit.php?post_type=lesson' ) ) . '">'.$user_item->user_login.'</a></td>';
						$in_progress_html .= '<td>' . __( 'In Progress', 'woothemes-sensei' ) . '</td>';
						$in_progress_html .= '<td>' . $quiz_grade . '</td>';
				$in_progress_html .= '</tr>';
				$in_progress_count++;
			} elseif ( isset( $lesson_grade ) && 0 < intval( $lesson_grade ) ) {
				// Graded
				$graded_html .= '<tr>';
					$graded_html .= '<td class="table-count hidden">Test</td>';
						$graded_html .= '<td><a href="' . add_query_arg( array( 'page' => 'sensei_grading', 'user' => $user_item->ID, 'quiz_id' => $lesson_quiz_id ), admin_url( 'edit.php?post_type=lesson' ) ) . '">'.$user_item->user_login.'</a></td>';
						$graded_html .= '<td>' . __( 'Graded', 'woothemes-sensei' ) . '</td>';
						$graded_html .= '<td>' . $quiz_grade . '</td>';
				$graded_html .= '</tr>';
				$graded_count++;
			} // End If Statement

		} // End For Loop

		// Handle zero results
		if ( 0 == $to_be_graded_count ) {
			$to_be_graded_html .= '<tr>';
				$to_be_graded_html .= '<td colspan="4">' . __( 'There are no Learners in this Lesson to be graded right now.', 'woothemes-sensei' ) . '</td>';
			$to_be_graded_html .= '</tr>';
		} // End If Statement
		if ( 0 == $in_progress_count ) {
			$in_progress_html .= '<tr>';
				$in_progress_html .= '<td colspan="4">' . __( 'There are no Learners currently working on the Lesson.', 'woothemes-sensei' ) . '</td>';
			$in_progress_html .= '</tr>';
		} // End If Statement
		if ( 0 == $graded_count ) {
			$graded_html .= '<tr>';
				$graded_html .= '<td colspan="4">' . __( 'No Learners have been graded for this Lesson yet.', 'woothemes-sensei' ) . '</td>';
			$graded_html .= '</tr>';
		} // End If Statement
		$html = '</tbody></table>';
		$to_be_graded_html .= $html . '</div>';
		$in_progress_html .= $html . '</div>';
		$graded_html .= $html . '</div>';

		// Final HTML
		$html = $to_be_graded_html . $in_progress_html . $graded_html;

		echo $html;
		die(); // WordPress may print out a spurious zero without this can be particularly bad if using JSON
	}

	public function process_grading() {
		if( isset( $_POST['sensei_manual_grade'] ) && isset( $_GET['quiz_id'] ) ) {
			$quiz_id = $_GET['quiz_id'];
			$verify_nonce = wp_verify_nonce( $_POST['_wp_sensei_manual_grading_nonce'], 'sensei_manual_grading' );
			if( $verify_nonce && $quiz_id == $_POST['sensei_manual_grade'] ) {
				$questions = WooThemes_Sensei_Utils::sensei_get_quiz_questions( $quiz_id );
				$quiz_grade = 0;
				$count = 0;
				foreach( $questions as $question ) {
					++$count;
					$question_id = $question->ID;
					if( isset( $_POST[ 'question_' . $question_id ] ) ) {
						$correct = false;
						$question_grade = 0;
						if( $_POST[ 'question_' . $question_id ] == 'right' ) {
							$correct = true;
							$question_grade = $_POST[ 'question_' . $question_id . '_grade' ];
						}
						$activity_logged = WooThemes_Sensei_Utils::sensei_grade_question( $question_id, $question_grade );
						$quiz_grade += $question_grade;
					} else {
						WooThemes_Sensei_Utils::sensei_delete_question_grade( $question_id );
					}
				}

				$quiz_percent = abs( round( ( doubleval( $quiz_grade ) * 100 ) / ( $count ), 2 ) );
				$activity_logged = WooThemes_Sensei_Utils::sensei_grade_quiz( $quiz_id, $quiz_percent );

				if( isset( $_POST['_wp_http_referer'] ) ) {
					wp_redirect( $_POST['_wp_http_referer'] );
					exit;
				}
			}
		}
	}

} // End Class
?>