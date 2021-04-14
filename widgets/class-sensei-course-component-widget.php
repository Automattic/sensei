<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Course Component Widget
 *
 * @package Views
 * @subpackage Widgets
 * @author Automattic
 *
 * @since 1.1.0
 */
class Sensei_Course_Component_Widget extends WP_Widget {
	protected $widget_cssclass;
	protected $widget_description;
	protected $widget_idbase;
	protected $widget_title;
	protected $instance;

	/**
	 * Constructor function.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		/* Widget variable settings. */
		$this->widget_cssclass    = 'widget_sensei_course_component';
		$this->widget_description = __( 'This widget will output a list of Courses - New, Featured, Free, Paid, Active, Completed.', 'sensei-lms' );
		$this->widget_idbase      = 'sensei_course_component';
		$this->widget_title       = __( 'Sensei LMS - Course Component', 'sensei-lms' );

		/**
		 * Allows filtering of the widget's component list.
		 *
		 * @since 2.0.0
		 *
		 * @param array $components_list {
		 *     Array of course components to allow in the widget.
		 *
		 *     @type string ${$component_name} Label for the component.
		 * }
		 */
		$this->widget_componentslist = apply_filters(
			'sensei_widget_course_component_components_list',
			array(
				'usercourses'      => __( 'New Courses', 'sensei-lms' ),
				'featuredcourses'  => __( 'Featured Courses', 'sensei-lms' ),
				'activecourses'    => __( 'My Active Courses', 'sensei-lms' ),
				'completedcourses' => __( 'My Completed Courses', 'sensei-lms' ),
			)
		);

		/* Widget settings. */
		$widget_ops = array(
			'classname'   => $this->widget_cssclass,
			'description' => $this->widget_description,
		);

		/* Widget control settings. */
		$control_ops = array(
			'width'   => 250,
			'height'  => 350,
			'id_base' => $this->widget_idbase,
		);

		/* Create the widget. */
		parent::__construct( $this->widget_idbase, $this->widget_title, $widget_ops, $control_ops );
	}

	/**
	 * Display the widget on the frontend.
	 *
	 * @since  1.0.0
	 * @param  array $args     Widget arguments.
	 * @param  array $instance Widget settings for this instance.
	 * @return void
	 */
	public function widget( $args, $instance ) {

		remove_filter( 'pre_get_posts', 'sensei_course_archive_filter', 10, 1 );

		// don't show active or completed course if a user is not logged in
		if ( ! in_array( $instance['component'], array_keys( $this->widget_componentslist ) )
			 || ( ! is_user_logged_in() && ( 'activecourses' == $instance['component'] || 'completedcourses' == $instance['component'] ) ) ) {
			// No Output
			return;

		}

		$this->instance = $instance;

		/* Our variables from the widget settings. */
		$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );

		/* Before widget (defined by themes). */
		echo wp_kses_post( $args['before_widget'] );

		/* Display the widget title if one was input (before and after defined by themes). */
		if ( $title ) {
			echo wp_kses_post( $args['before_title'] . $title . $args['after_title'] ); }

		/*
		 Widget content. */
		// Add actions for plugins/themes to hook onto.
		do_action( $this->widget_cssclass . '_top' );

		if ( in_array( $instance['component'], array_keys( $this->widget_componentslist ) ) ) {
			$this->load_component( $instance );
		}

		// Add actions for plugins/themes to hook onto.
		do_action( $this->widget_cssclass . '_bottom' );

		/* After widget (defined by themes). */
		echo wp_kses_post( $args['after_widget'] );

		add_filter( 'pre_get_posts', 'sensei_course_archive_filter', 10, 1 );

	}

	/**
	 * Method to update the settings from the form() method.
	 *
	 * @since  1.0.0
	 * @param  array $new_instance New settings.
	 * @param  array $old_instance Previous settings.
	 * @return array               Updated settings.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['title'] = strip_tags( $new_instance['title'] );

		/* The select box is returning a text value, so we escape it. */
		$instance['component'] = esc_attr( $new_instance['component'] );

		/* The select box is returning a text value, so we escape it. */
		$instance['limit'] = esc_attr( $new_instance['limit'] );

		return $instance;
	}

	/**
	 * The form on the widget control in the widget administration area.
	 * Make use of the get_field_id() and get_field_name() function when creating your form elements. This handles the confusing stuff.
	 *
	 * @since  1.0.0
	 * @param  array $instance The settings for this instance.
	 * @return void
	 */
	public function form( $instance ) {

		/*
		 Set up some default widget settings. */
		/* Make sure all keys are added here, even with empty string values. */
		$defaults = array(
			'title'     => '',
			'component' => '',
			'limit'     => 3,
		);

		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title (optional):', 'sensei-lms' ); ?></label>
			<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"  value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" />
		</p>
		<!-- Widget Component: Select Input -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'component' ) ); ?>"><?php esc_html_e( 'Component:', 'sensei-lms' ); ?></label>
			<select name="<?php echo esc_attr( $this->get_field_name( 'component' ) ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'component' ) ); ?>">
			<?php foreach ( $this->widget_componentslist as $k => $v ) { ?>
				<option value="<?php echo esc_attr( $k ); ?>"<?php selected( $instance['component'], $k ); ?>><?php echo esc_html( $v ); ?></option>
			<?php } ?>
			</select>
		</p>
		<!-- Widget Limit: Text Input -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"><?php esc_html_e( 'Number of Courses (optional):', 'sensei-lms' ); ?></label>
			<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>"  value="<?php echo esc_attr( $instance['limit'] ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>" />
		</p>

		<?php
	}

	/**
	 * Load the desired component, if a method is available for it.
	 *
	 * @param array $instance The component to potentially be loaded.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function load_component( $instance ) {
		$component = esc_attr( $instance['component'] );

		if ( 'usercourses' === $component ) {
			$courses = $this->get_new_courses();
		} elseif ( 'activecourses' === $component ) {
			$courses = $this->get_active_courses();
		} elseif ( 'completedcourses' === $component ) {
			$courses = $this->get_completed_courses();
		} elseif ( 'featuredcourses' === $component ) {
			$courses = $this->get_featured_courses();
		} else {
			if ( ! has_filter( 'sensei_widget_course_component_get_courses_' . $component ) ) {
				return;
			}

			/**
			 * Get the courses for a custom component.
			 *
			 * @since 2.0.0
			 *
			 * @param WP_Post[] $courses  List of course post objects.
			 * @param array     $instance Widget instance arguments.
			 */
			$courses = apply_filters( 'sensei_widget_course_component_get_courses_' . $component, array(), $instance );
		}

		// course_query() is buggy, it doesn't honour the 1st arg if includes are provided, so instead slice the includes.
		if ( ! empty( $instance['limit'] ) && intval( $instance['limit'] ) >= 1 && intval( $instance['limit'] ) < count( $courses ) ) {
			$courses = array_slice( $courses, 0, intval( $instance['limit'] ) );
		}

		if ( empty( $courses ) && 0 !== $instance['limit'] ) {
			$this->display_no_courses_message();
			return;
		}

		$this->display_courses( $courses );

	}


	/**
	 * Output the message telling the user that
	 * there are no course for their desired settings
	 *
	 * @since 1.9.2
	 */
	public function display_no_courses_message() {

		if ( 'featuredcourses' === $this->instance['component'] ) {
			esc_html_e( 'You have no featured courses.', 'sensei-lms' );
		} elseif ( 'activecourses' === $this->instance['component'] ) {
			esc_html_e( 'You have no active courses.', 'sensei-lms' );
		} elseif ( 'completedcourses' === $this->instance['component'] ) {
			esc_html_e( 'You have no completed courses.', 'sensei-lms' );
		} else {
			/**
			 * Filter on the no courses message.
			 *
			 * @since 2.0.0
			 *
			 * @param string $message  No course message to display.
			 * @param array  $instance Widget instance arguments.
			 */
			echo esc_html( apply_filters( 'sensei_widget_course_component_no_courses_message_' . $this->instance['component'], __( 'You have no courses.', 'sensei-lms' ), $this->instance ) );
		}
	}

	/**
	 * Output the widget courses
	 *
	 * @since 1.9.2
	 * @param array $courses
	 */
	public function display_courses( $courses = array() ) {
		?>
		<ul>
			<?php

			foreach ( $courses as $course ) {

				$post_id             = absint( $course->ID );
				$post_title          = $course->post_title;
				$user_info           = get_userdata( absint( $course->post_author ) );
				$author_link         = get_author_posts_url( absint( $course->post_author ) );
				$author_display_name = $user_info->display_name;
				$lesson_count        = Sensei()->course->course_lesson_count( $post_id );
				?>

				<li class="fix">

					<?php do_action( 'sensei_course_image', $post_id ); ?>

					<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"
					   title="<?php echo esc_attr( $post_title ); ?>">

						<?php echo esc_html( $post_title ); ?>

					</a>
					<br />

					<?php
					/** This action is documented in includes/class-sensei-frontend.php */
					do_action( 'sensei_course_meta_inside_before', $post_id );
					?>
					<?php if ( isset( Sensei()->settings->settings['course_author'] ) && ( Sensei()->settings->settings['course_author'] ) ) { ?>
						<span class="course-author">
							<?php esc_html_e( 'by', 'sensei-lms' ); ?>
							<a href="<?php echo esc_url( $author_link ); ?>" title="<?php echo esc_attr( $author_display_name ); ?>">
								<?php echo esc_html( $author_display_name ); ?>
							</a>
						</span>
						<br />
					<?php } ?>

					<span class="course-lesson-count">
						<?php
						// translators: Placeholder %d is the lesson count.
						echo esc_html( sprintf( _n( '%d Lesson', '%d Lessons', $lesson_count, 'sensei-lms' ), $lesson_count ) );
						?>
					</span>

					<br />

					<?php
					/** This action is documented in includes/class-sensei-frontend.php */
					do_action( 'sensei_course_meta_inside_after', $post_id );
					?>

				</li>

				<?php
			}

			if ( 'activecourses' == esc_attr( $this->instance['component'] ) || 'completedcourses' == esc_attr( $this->instance['component'] ) ) {
				$my_account_page_id = intval( Sensei()->settings->settings['my_course_page'] );
				echo '<li class="my-account fix"><a href="' . esc_url( get_permalink( $my_account_page_id ) ) . '">'
					 . esc_html__( 'My Courses', 'sensei-lms' )
					 . '<span class="meta-nav"></span></a></li>';
			}

			?>
		</ul>

		<?php
	}

	/**
	 * The default course query args
	 *
	 * @return array
	 */
	public function get_default_query_args() {

		return array(
			'post_type'        => 'course',
			'orderby'          => 'date',
			'order'            => 'DESC',
			'post_status'      => 'publish',
			'posts_per_page'   => $this->instance['limit'],
			'suppress_filters' => 0,
		);

	}

	/**
	 * Get all new courses.
	 *
	 * @since 1.9.2
	 *
	 * @return array $courses
	 */
	public function get_new_courses() {

		return get_posts( $this->get_default_query_args() );

	}

	/**
	 * Get all active courses for the current user
	 *
	 * @since 1.9.2
	 *
	 * @return WP_Post[]
	 */
	public function get_active_courses() {
		$user_id = get_current_user_id();
		if ( empty( $user_id ) ) {
			return [];
		}

		$query_args = [
			'posts_per_page' => $this->instance['limit'],
		];

		$learner_manager = Sensei_Learner::instance();
		$courses_query   = $learner_manager->get_enrolled_active_courses_query( $user_id, $query_args );

		return $courses_query->posts;
	}

	/**
	 * Get all courses for the current user.
	 *
	 * @since 1.9.2
	 *
	 * @return array $courses
	 */
	public function get_completed_courses() {
		$user_id = get_current_user_id();
		if ( empty( $user_id ) ) {
			return [];
		}

		$query_args = [
			'posts_per_page' => $this->instance['limit'],
		];

		$learner_manager = Sensei_Learner::instance();
		$courses_query   = $learner_manager->get_enrolled_completed_courses_query( $user_id, $query_args );

		return $courses_query->posts;
	}

	/**
	 * Get the featured courses.
	 *
	 * @since 1.9.2
	 *
	 * @return array $courses
	 */
	public function get_featured_courses() {

		$query_args                 = $this->get_default_query_args();
		$query_args['meta_key']     = '_course_featured';
		$query_args['meta_value']   = 'featured';
		$query_args['meta_compare'] = '=';

		return get_posts( $query_args );

	}
}
