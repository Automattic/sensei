<?php
/**
 * Sensei Course Progress Widget
 *
 * @author 		WooThemes
 * @category 	Widgets
 * @package 	Sensei/Widgets
 * @version 	1.0.0
 * @extends 	WC_Widget
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Sensei_Course_Progress_Widget extends WP_Widget {
	protected $woo_widget_cssclass;
	protected $woo_widget_description;
	protected $woo_widget_idbase;
	protected $woo_widget_title;

	/**
	 * Constructor function.
	 * @since  1.1.0
	 * @return  void
	 */
	public function __construct() {
		/* Widget variable settings. */
		$this->woo_widget_cssclass = 'widget_sensei_course_progress';
		$this->woo_widget_description = __( 'Displays the current learners progress within the current course/module (only displays on single lesson page).', 'sensei-course-progress' );
		$this->woo_widget_idbase = 'sensei_course_progress';
		$this->woo_widget_title = __( 'Sensei - Course Progress', 'sensei-course-progress' );
		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->woo_widget_cssclass, 'description' => $this->woo_widget_description );

		/* Widget control settings. */
		$control_ops = array( 'width' => 250, 'height' => 350, 'id_base' => $this->woo_widget_idbase );

		/* Create the widget. */
		$this->WP_Widget( $this->woo_widget_idbase, $this->woo_widget_title, $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {
		
		global $woothemes_sensei, $post, $current_user, $view_lesson, $user_taking_course, $sensei_modules;

		// get the course for the current lesson/quiz
		$lesson_course_id = get_post_meta( $post->ID, '_lesson_course', true );

		// Check if the user is taking the course
		$is_user_taking_course = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $lesson_course_id, 'user_id' => $current_user->ID, 'type' => 'sensei_course_start' ) );

		//Check for preview lesson
		$is_preview = false;
		if ( method_exists( 'WooThemes_Sensei_Utils', 'is_preview_lesson' ) ) {
			$is_preview = WooThemes_Sensei_Utils::is_preview_lesson( $post->ID );
		}

		// If not viewing a lesson/quiz, don't display the widget
		if( !( ( is_singular('lesson') || is_singular('quiz') ) ) ) return;

		extract( $args );

		$quiz_lesson = absint( get_post_meta( $post->ID, '_quiz_lesson', true ) );
		$course_title = htmlspecialchars( get_the_title( $lesson_course_id ) );
		$course_url = get_the_permalink($lesson_course_id);

		$in_module = false;
		$lesson_module = '';
		$lesson_array = array();

		if ( 0 < $post->ID ) {
			// get an array of lessons in the module if there is one		
			if( isset( $sensei_modules ) && has_term( '', $sensei_modules->taxonomy, $post->ID ) ) {
				$lesson_module = $sensei_modules->get_lesson_module( $post->ID );
				$in_module = true;
				$module_title = htmlspecialchars( $lesson_module->name );

		    	// get all lessons in the current module
				$args = array(
					'post_type' => 'lesson',
					'post_status' => 'publish',
					'posts_per_page' => -1,
					'meta_query' => array(
						array(
							'key' => '_lesson_course',
							'value' => intval( $lesson_course_id ),
							'compare' => '='
						)
					),
					'tax_query' => array(
						array(
							'taxonomy' => $sensei_modules->taxonomy,
							'field' => 'id',
							'terms' => $lesson_module
						)
					),
					'meta_key' => '_order_module_' . intval( $lesson_module->term_id ),
					'orderby' => 'meta_value_num date',
					'order' => 'ASC'
				);

				$lesson_array = get_posts( $args );
			} else {
				// if there's no module, get all lessons in the course	
				$lesson_array = $woothemes_sensei->frontend->course->course_lessons( $lesson_course_id );
			}
		}

		echo $before_widget; ?>

		<header>
			<h2 class="course-title"><a href="<?php echo $course_url; ?>"><?php echo $course_title; ?></a></h2>

			<?php if ( $in_module ) { ?>
				<h3 class="module-title"><?php echo $module_title ; ?></h3>
			<?php } ?>

		</header>

		<ul class="course-progress-lessons">

			<?php foreach( $lesson_array as $lesson ) { 
				$lesson_id = $lesson->ID;
				$lesson_title = htmlspecialchars( $lesson->post_title );
				$lesson_url = get_the_permalink( $lesson_id );

				// add 'completed' class to completed lessons
				$classes = "not-completed";
				if( WooThemes_Sensei_Utils::user_completed_lesson( $lesson->ID, $current_user->ID ) ) {
					$classes = "completed";
				}

				// Lesson Quiz Meta
				$lesson_quiz_id = $woothemes_sensei->frontend->lesson->lesson_quizzes( $lesson_id );

				// add 'current' class on the current lesson/quiz
				if( $lesson_id == $post->ID || $lesson_quiz_id == $post->ID ) {
					$classes .= " current";
				} ?>

				<li class="course-progress-lesson <?php echo $classes; ?>">
					<?php if( $lesson->ID == $post->ID || $lesson_quiz_id == $post->ID ) {
						echo '<span>' . $lesson_title . '</span>';
					} else {
						echo '<a href="' . $lesson_url . '">' . $lesson_title . '</a>';
					} ?>
				</li>
			
			<?php } ?>

		</ul>

		<?php echo $after_widget;
	}
}