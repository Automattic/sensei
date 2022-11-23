<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Category Courses Widget
 *
 * @package Views
 * @subpackage Widgets
 * @author Automattic
 *
 * @since 1.1.0
 */
class Sensei_Category_Courses_Widget extends WP_Widget {
	protected $widget_cssclass;
	protected $widget_description;
	protected $widget_idbase;
	protected $widget_title;

	/**
	 * Constructor function.
	 *
	 * @since  1.1.0
	 * @return  void
	 */
	public function __construct() {
		/* Widget variable settings. */
		$this->widget_cssclass    = 'widget_sensei_category_courses';
		$this->widget_description = __( 'This widget will output a list of Courses for a specific category.', 'sensei-lms' );
		$this->widget_idbase      = 'sensei_category_courses';
		$this->widget_title       = __( 'Sensei LMS - Category Courses', 'sensei-lms' );

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
	 * @since  1.1.0
	 * @param  array $args     Widget arguments.
	 * @param  array $instance Widget settings for this instance.
	 * @return void
	 */
	public function widget( $args, $instance ) {

		$before_widget = $args['before_widget'];
		$before_title  = $args['before_title'];
		$after_title   = $args['after_title'];
		$after_widget  = $args['after_widget'];

		/* Our variables from the widget settings. */
		$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );

		/* Before widget (defined by themes). */
		echo wp_kses_post( $before_widget );

		/* Display the widget title if one was input (before and after defined by themes). */
		if ( $title ) {
			echo wp_kses_post( $before_title . $title . $after_title ); }

		/*
		 Widget content. */
		// Add actions for plugins/themes to hook onto.
		do_action( $this->widget_cssclass . '_top' );

		if ( 0 < intval( $instance['course_category'] ) ) {
			$this->load_component( $instance );
		}

		// Add actions for plugins/themes to hook onto.
		do_action( $this->widget_cssclass . '_bottom' );

		/* After widget (defined by themes). */
		echo wp_kses_post( $after_widget );

	}

	/**
	 * Method to update the settings from the form() method.
	 *
	 * @since  1.1.0
	 * @param  array $new_instance New settings.
	 * @param  array $old_instance Previous settings.
	 * @return array               Updated settings.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['title'] = strip_tags( $new_instance['title'] );

		/* The select box is returning a text value, so we escape it. */
		$instance['course_category'] = esc_attr( $new_instance['course_category'] );

		/* Strip tags for limit to remove HTML (important for text inputs). */
		$instance['limit'] = strip_tags( $new_instance['limit'] );

		return $instance;
	}

	/**
	 * The form on the widget control in the widget administration area.
	 * Make use of the get_field_id() and get_field_name() function when creating your form elements. This handles the confusing stuff.
	 *
	 * @since  1.1.0
	 * @param  array $instance The settings for this instance.
	 * @return void
	 */
	public function form( $instance ) {

		/*
		 Set up some default widget settings. */
		/* Make sure all keys are added here, even with empty string values. */
		$defaults = array(
			'title'           => '',
			'course_category' => 0,
			'limit'           => 3,
		);

		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title (optional):', 'sensei-lms' ); ?></label>
			<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"  value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" />
		</p>
		<!-- Widget Course Category: Select Input -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'course_category' ) ); ?>"><?php esc_html_e( 'Course Category:', 'sensei-lms' ); ?></label>
			<?php
			$cat_args = array(
				'hierarchical'     => true,
				'show_option_none' => __( 'Select Category:', 'sensei-lms' ),
				'taxonomy'         => 'course-category',
				'orderby'          => 'name',
				'selected'         => intval( $instance['course_category'] ),
				'id'               => $this->get_field_id( 'course_category' ),
				'name'             => $this->get_field_name( 'course_category' ),
				'class'            => 'widefat',
			);
			wp_dropdown_categories( apply_filters( 'widget_course_categories_dropdown_args', $cat_args ) );
			?>
		</p>
		<!-- Widget Limit: Text Input -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"><?php esc_html_e( 'Number of Courses (optional):', 'sensei-lms' ); ?></label>
			<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>"  value="<?php echo esc_attr( $instance['limit'] ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>" />
		</p>

		<?php
	}

	/**
	 * Load the output.
	 *
	 * @param  array $instance.
	 * @since  1.1.0
	 * @return void
	 */
	protected function load_component( $instance ) {

		$posts_array = array();
		$post_args   = array(
			'post_type'        => 'course',
			'posts_per_page'   => intval( $instance['limit'] ),
			'orderby'          => 'menu_order date',
			'order'            => 'ASC',
			'post_status'      => 'publish',
			'suppress_filters' => 0,
		);

		$post_args['tax_query'] = array(
			array(
				'taxonomy' => 'course-category',
				'field'    => 'id',
				'terms'    => intval( $instance['course_category'] ),
			),
		);

		$posts_array = get_posts( $post_args );

		if ( count( $posts_array ) > 0 ) {
			?>
			<ul>
			<?php
			foreach ( $posts_array as $post_item ) {
				$post_id             = absint( $post_item->ID );
				$post_title          = $post_item->post_title;
				$user_info           = get_userdata( absint( $post_item->post_author ) );
				$author_link         = get_author_posts_url( absint( $post_item->post_author ) );
				$author_display_name = $user_info->display_name;
				$lesson_count        = Sensei()->course->course_lesson_count( $post_id );
				?>
				<li class="fix">
					<?php do_action( 'sensei_course_image', $post_id ); ?>
					<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" title="<?php echo esc_attr( $post_title ); ?>"><?php echo esc_html( $post_title ); ?></a>
					<br />
					<?php
					/** This action is documented in includes/class-sensei-frontend.php */
					do_action( 'sensei_course_meta_inside_before', $post_id );

					if ( isset( Sensei()->settings->settings['course_author'] ) && ( Sensei()->settings->settings['course_author'] ) ) {
						?>
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
			<?php } ?>
			</ul>
			<?php
		}
	}
}
