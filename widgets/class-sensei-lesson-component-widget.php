<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Lesson Component Widget
 *
 * @package Views
 * @subpackage Widgets
 * @author Automattic
 *
 * @since 1.0.0
 */
class Sensei_Lesson_Component_Widget extends WP_Widget {
	protected $widget_cssclass;
	protected $widget_description;
	protected $widget_idbase;
	protected $widget_title;

	/**
	 * Constructor function.
	 *
	 * @since  1.0.0
	 * @return  void
	 */
	public function __construct() {
		/* Widget variable settings. */
		$this->widget_cssclass    = 'widget_sensei_lesson_component';
		$this->widget_description = __( 'This widget will output a list of the latest Lessons.', 'sensei-lms' );
		$this->widget_idbase      = 'sensei_lesson_component';
		$this->widget_title       = __( 'Sensei LMS - Lesson Component', 'sensei-lms' );

		$this->widget_componentslist = array(
			'newlessons' => __( 'New Lessons', 'sensei-lms' ),
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

		$before_widget = $args['before_widget'];
		$before_title  = $args['before_title'];
		$after_title   = $args['after_title'];
		$after_widget  = $args['after_widget'];

		if ( in_array( $instance['component'], array_keys( $this->widget_componentslist ) ) && ( 'activecourses' == $instance['component'] || 'completedcourses' == $instance['component'] ) && ! is_user_logged_in() ) {
			// No Output
		} else {
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

			if ( in_array( $instance['component'], array_keys( $this->widget_componentslist ) ) ) {
				$this->load_component( $instance );
			}

			// Add actions for plugins/themes to hook onto.
			do_action( $this->widget_cssclass . '_bottom' );

			/* After widget (defined by themes). */
			echo wp_kses_post( $after_widget );
		}

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
			<label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"><?php esc_html_e( 'Number of Lessons (optional):', 'sensei-lms' ); ?></label>
			<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>"  value="<?php echo esc_attr( $instance['limit'] ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>" />
		</p>

		<?php
	}

	/**
	 * Load the desired component, if a method is available for it.
	 *
	 * @param  string $instance The component to potentially be loaded.
	 * @since  5.0.8
	 * @return void
	 */
	protected function load_component( $instance ) {
		/*
		newlessons
		*/
		$posts_array = array();

		$post_args   = array(
			'post_type'        => 'lesson',
			'posts_per_page'   => intval( $instance['limit'] ),
			'orderby'          => 'menu_order date',
			'order'            => 'DESC',
			'post_status'      => 'publish',
			'suppress_filters' => 0,
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
				$lesson_course_id    = get_post_meta( $post_id, '_lesson_course', true );
				?>
				<li class="fix">
					<?php do_action( 'sensei_lesson_image', $post_id, '100', '100', false, true ); ?>
					<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" title="<?php echo esc_attr( $post_title ); ?>"><?php echo esc_html( $post_title ); ?></a>
					<br />
					<?php if ( isset( Sensei()->settings->settings['lesson_author'] ) && ( Sensei()->settings->settings['lesson_author'] ) ) { ?>
						<span class="course-author">
							<?php esc_html_e( 'by', 'sensei-lms' ); ?>
							<a href="<?php echo esc_url( $author_link ); ?>" title="<?php echo esc_attr( $author_display_name ); ?>">
								<?php echo esc_html( $author_display_name ); ?>
							</a>
						</span>
						<br />
					<?php } ?>
					<?php if ( 0 < $lesson_course_id ) { ?>
						<span class="lesson-course">
							<?php
							echo ' ' . wp_kses_post(
								sprintf(
									// translators: Placeholder is a link to the Course permalink.
									__( 'Part of: %s', 'sensei-lms' ),
									'<a href="' . esc_url( get_permalink( $lesson_course_id ) ) . '" title="' . esc_attr( __( 'View course', 'sensei-lms' ) ) . '"><em>' . esc_html( get_the_title( $lesson_course_id ) ) . '</em></a>'
								)
							);
							?>
						</span>
					<?php } ?>
					<br />
				</li>
			<?php } ?>
			<?php echo '<li class="my-account fix"><a class="button" href="' . esc_url( get_post_type_archive_link( 'lesson' ) ) . '">' . esc_html__( 'More Lessons', 'sensei-lms' ) . '</a></li>'; ?>
		</ul>
			<?php
		}
	}
}
