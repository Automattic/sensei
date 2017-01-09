<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * Sensei Course Component Widget
 *
 * A WooThemes standardized component widget.
 *
 * @package Views
 * @subpackage Widgets
 * @author Automattic
 *
 * @since 1.1.0
 */
class WooThemes_Sensei_Course_Component_Widget extends WP_Widget {
	protected $woo_widget_cssclass;
	protected $woo_widget_description;
	protected $woo_widget_idbase;
	protected $woo_widget_title;
	protected $instance;

	/**
	 * Constructor function.
	 * @since  1.0.0
	 */
	public function __construct() {
		/* Widget variable settings. */
		$this->woo_widget_cssclass = 'widget_sensei_course_component';
		$this->woo_widget_description = __( 'This widget will output a list of Courses - New, Featured, Free, Paid, Active, Completed.', 'woothemes-sensei' );
		$this->woo_widget_idbase = 'sensei_course_component';
		$this->woo_widget_title = __( 'Sensei - Course Component', 'woothemes-sensei' );

		$this->woo_widget_componentslist = array(
												'usercourses' => __( 'New Courses', 'woothemes-sensei' ),
												'featuredcourses' => __( 'Featured Courses', 'woothemes-sensei' ),
												'activecourses' => __( 'My Active Courses', 'woothemes-sensei' ),
												'completedcourses' => __( 'My Completed Courses', 'woothemes-sensei' ),
												);

		// Add support for the WooCommerce shelf.
		if ( Sensei_WC::is_woocommerce_active() ) {
			$this->woo_widget_componentslist['freecourses'] = __( 'Free Courses', 'woothemes-sensei' );
			$this->woo_widget_componentslist['paidcourses'] = __( 'Paid Courses', 'woothemes-sensei' );
		}

		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->woo_widget_cssclass, 'description' => $this->woo_widget_description );

		/* Widget control settings. */
		$control_ops = array( 'width' => 250, 'height' => 350, 'id_base' => $this->woo_widget_idbase );

		/* Create the widget. */
		parent::__construct( $this->woo_widget_idbase, $this->woo_widget_title, $widget_ops, $control_ops );
	} // End __construct()

	/**
	 * Display the widget on the frontend.
	 * @since  1.0.0
	 * @param  array $args     Widget arguments.
	 * @param  array $instance Widget settings for this instance.
	 * @return void
	 */
	public function widget( $args, $instance ) {

		remove_filter( 'pre_get_posts', 'sensei_course_archive_filter', 10, 1 );

		//don't show active or completed course if a user is not logged in
		if ( ! in_array( $instance['component'], array_keys( $this->woo_widget_componentslist ) )
		     || ( ! is_user_logged_in() && ( 'activecourses' == $instance['component'] || 'completedcourses' == $instance['component'] ) )  ) {
			// No Output
            return;

		}

		$this->instance = $instance;

		/* Our variables from the widget settings. */
		$title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base );

		/* Before widget (defined by themes). */
		echo $args['before_widget'];

		/* Display the widget title if one was input (before and after defined by themes). */
		if ( $title ) { echo $args['before_title'] . $title . $args['after_title']; }

		/* Widget content. */
		// Add actions for plugins/themes to hook onto.
		do_action( $this->woo_widget_cssclass . '_top' );

		if ( in_array( $instance['component'], array_keys( $this->woo_widget_componentslist ) ) ) {
			$this->load_component( $instance );
		}

		// Add actions for plugins/themes to hook onto.
		do_action( $this->woo_widget_cssclass . '_bottom' );

		/* After widget (defined by themes). */
		echo $args['after_widget'];


		add_filter( 'pre_get_posts', 'sensei_course_archive_filter', 10, 1 );

	} // End widget()

	/**
	 * Method to update the settings from the form() method.
	 * @since  1.0.0
	 * @param  array $new_instance New settings.
	 * @param  array $old_instance Previous settings.
	 * @return array               Updated settings.
	 */
	public function update ( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['title'] = strip_tags( $new_instance['title'] );

		/* The select box is returning a text value, so we escape it. */
		$instance['component'] = esc_attr( $new_instance['component'] );

		/* The select box is returning a text value, so we escape it. */
		$instance['limit'] = esc_attr( $new_instance['limit'] );


		return $instance;
	} // End update()

	/**
	 * The form on the widget control in the widget administration area.
	 * Make use of the get_field_id() and get_field_name() function when creating your form elements. This handles the confusing stuff.
	 * @since  1.0.0
	 * @param  array $instance The settings for this instance.
	 * @return void
	 */
    public function form( $instance ) {

		/* Set up some default widget settings. */
		/* Make sure all keys are added here, even with empty string values. */
		$defaults = array(
						'title' => '',
						'component' => '',
						'limit' => 3
					);

		$instance = wp_parse_args( (array) $instance, $defaults );
?>
		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title (optional):', 'woothemes-sensei' ); ?></label>
			<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"  value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" />
		</p>
		<!-- Widget Component: Select Input -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'component' ) ); ?>"><?php _e( 'Component:', 'woothemes-sensei' ); ?></label>
			<select name="<?php echo esc_attr( $this->get_field_name( 'component' ) ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'component' ) ); ?>">
			<?php foreach ( $this->woo_widget_componentslist as $k => $v ) { ?>
				<option value="<?php echo esc_attr( $k ); ?>"<?php selected( $instance['component'], $k ); ?>><?php echo $v; ?></option>
			<?php } ?>
			</select>
		</p>
		<!-- Widget Limit: Text Input -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"><?php _e( 'Number of Courses (optional):', 'woothemes-sensei' ); ?></label>
			<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>"  value="<?php echo esc_attr( $instance['limit'] ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>" />
		</p>

<?php
	} // End form()

	/**
	 * Load the desired component, if a method is available for it.
	 *
	 * @param array $instance The component to potentially be loaded.
     *
	 * @since  1.0.0
	 * @return void
	 */
	protected function load_component ( $instance ) {

		$courses = array();

		if ( 'usercourses' == esc_attr( $instance['component'] ) ) {
			// usercourses == new courses
			$courses =  $this->get_new_courses( );

		} elseif ( 'activecourses' == esc_attr( $instance['component'] ) ) {

			$courses =  $this->get_active_courses( );


		} elseif ( 'completedcourses' == esc_attr( $instance['component'] ) ) {

			$courses =  $this->get_completed_courses();

		} elseif ( 'featuredcourses' == esc_attr( $instance['component'] ) ) {

			$courses =  $this->get_featured_courses();

		} elseif ( 'paidcourses' == esc_attr( $instance['component'] ) ) {

			$args = array( 'posts_per_page' => $this->instance['limit'] );
			$courses =  Sensei_WC::get_paid_courses( $args );

		} elseif ( 'freecourses' == esc_attr( $instance['component'] ) ) {

			$args = array( 'posts_per_page' => $this->instance['limit'] );
			$courses =  Sensei_WC::get_free_courses( $args );

		} else {

			return;

		}

		// course_query() is buggy, it doesn't honour the 1st arg if includes are provided, so instead slice the includes
		if ( !empty($instance['limit']) && intval( $instance['limit'] ) >= 1 && intval( $instance['limit'] ) < count($courses) ) {

			$courses = array_slice( $courses, 0, intval( $instance['limit'] ) );

		}

		if ( empty( $courses ) && $instance['limit'] != 0 ) {

			$this->display_no_courses_message();
			return;

		}

		$this->display_courses( $courses );

	} // End load_component()


	/**
	 * Output the message telling the user that
	 * there are no course for their desired settings
	 *
	 * @since 1.9.2
	 */
	public function display_no_courses_message ( ) {

		if ( 'featuredcourses' == $this->instance['component'] ) {

			_e( 'You have no featured courses.', 'woothemes-sensei' );

		} elseif ( 'activecourses' == $this->instance['component'] ) {

			_e( 'You have no active courses.', 'woothemes-sensei' );

		} elseif ( 'completedcourses' == $this->instance['component'] ) {

			_e( 'You have no completed courses.', 'woothemes-sensei' );

		}else{

			_e( 'You have no courses.', 'woothemes-sensei' );

		}
	}

	/**
	 * Output the widget courses
	 *
	 * @since 1.9.2
	 * @param array $courses
	 */
	public function display_courses( $courses = array() ){ ?>
		<ul>
			<?php

			foreach ($courses as $course) {

				$post_id = absint( $course->ID );
				$post_title = $course->post_title;
				$user_info = get_userdata( absint( $course->post_author ) );
				$author_link = get_author_posts_url( absint( $course->post_author ) );
				$author_display_name = $user_info->display_name;
				$author_id = $course->post_author;
				?>

				<li class="fix">

					<?php do_action( 'sensei_course_image', $post_id ); ?>

					<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"
					   title="<?php echo esc_attr( $post_title ); ?>">

						<?php echo $post_title; ?>

					</a>
					<br />

					<?php if ( isset( Sensei()->settings->settings[ 'course_author' ] ) && ( Sensei()->settings->settings[ 'course_author' ] ) ) { ?>
						<span class="course-author">
							<?php _e( 'by ', 'woothemes-sensei' ); ?>
							<a href="<?php echo esc_url( $author_link ); ?>" title="<?php echo esc_attr( $author_display_name ); ?>">
								<?php echo esc_html( $author_display_name ); ?>
							</a>
						</span>
						<br />
					<?php } // End If Statement ?>

					<span class="course-lesson-count">
						<?php echo Sensei()->course->course_lesson_count( $post_id ) . '&nbsp;' . __( 'Lessons', 'woothemes-sensei' ); ?>
					</span>

					<br />

					<?php sensei_simple_course_price( $post_id ); ?>

				</li>

			<?php
			} // End For Loop

			if ( 'activecourses' == esc_attr( $this->instance['component'] ) || 'completedcourses' == esc_attr( $this->instance['component'] ) ) {
				$my_account_page_id = intval( Sensei()->settings->settings[ 'my_course_page' ] );
				echo '<li class="my-account fix"><a href="'. esc_url( get_permalink( $my_account_page_id ) ) .'">'
				     .__('My Courses', 'woothemes-sensei')
				     .'<span class="meta-nav"></span></a></li>';
			} // End If Statement

			?>
		</ul>

	<?php }

	/**
	 * The default course query args
	 *
	 * @return array
	 */
	public function get_default_query_args(){

		return array(
				'post_type' => 'course',
				'orderby'         	=> 'date',
				'order'           	=> 'DESC',
				'post_status'      	=> 'publish',
				'posts_per_page' => $this->instance['limit'],
				'suppress_filters' => 0,
		);

	}

	/**
	 * Get all new course IDS
	 * @since 1.9.2
	 *
	 * @return array $courses
	 */
	public function get_new_courses ( ) {

		return get_posts( $this->get_default_query_args( ) );

	}

	/**
	 * Get all active course IDS for the current user
	 * @since 1.9.2
	 *
	 * @return array $courses
	 */
	public function get_active_courses ( ) {

		$courses = array();
		$activity_args = array( 'user_id' => get_current_user_id(), 'type' => 'sensei_course_status', 'status' => 'in-progress' );
		$user_courses_activity = Sensei_Utils::sensei_check_for_activity( $activity_args, true );

		if ( ! is_array( $user_courses_activity ) ) {

			$user_courses_activity_arr[] = $user_courses_activity;
			$user_courses_activity = $user_courses_activity_arr;

		}

		foreach( $user_courses_activity AS $activity ) {
			$courses[] = get_post( $activity->comment_post_ID );
		}

		return $courses;

	}

	/**
	 * Get all active course IDS for the current user
	 * @since 1.9.2
	 *
	 * @return array $courses
	 */
	public function get_completed_courses ( ) {

		$courses = array();
		$activity_args = array( 'user_id' => get_current_user_id(), 'type' => 'sensei_course_status', 'status' => 'complete' );

		$user_courses_activity = Sensei_Utils::sensei_check_for_activity( $activity_args , true );

		if ( ! is_array( $user_courses_activity ) ) {

			$user_courses_activity_arr[] = $user_courses_activity;
			$user_courses_activity = $user_courses_activity_arr;

		}

		foreach( $user_courses_activity AS $activity ) {

			if( isset(  $activity->comment_post_ID ) ){

				$courses[] = get_post( $activity->comment_post_ID );

			}

		}
		return $courses;
	}

	/**
	 * Get all active course IDS for the current user
	 * @since 1.9.2
	 *
	 * @return array $courses
	 */
	public function get_featured_courses ( ) {

		$query_args = $this->get_default_query_args();
		$query_args[ 'meta_key' ] = '_course_featured';
		$query_args[ 'meta_value' ] = 'featured';
		$query_args[ 'meta_compare' ] = '=';

		return get_posts( $query_args );

	}
} // End Class
