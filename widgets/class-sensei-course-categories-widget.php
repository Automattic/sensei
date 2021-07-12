<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Course Categories Widget
 *
 * @package Views
 * @subpackage Widgets
 * @author Automattic
 *
 * @since 1.1.0
 */
class Sensei_Course_Categories_Widget extends WP_Widget {
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
		$this->widget_cssclass    = 'widget_sensei_course_categories';
		$this->widget_description = __( 'This widget will output a list of Course Categories.', 'sensei-lms' );
		$this->widget_idbase      = 'sensei_course_categories';
		$this->widget_title       = __( 'Sensei LMS - Course Categories', 'sensei-lms' );

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

		$this->load_component( $instance );

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
		$instance['title'] = strip_tags( $new_instance['title'] ?? '' );

		/* The select box is returning a text value, so we escape it. */
		$instance['limit'] = (int) $new_instance['limit'] ?? 3;

		/* The check box is returning a boolean value. */
		$instance['count']        = $new_instance['count'] ?? 0;
		$instance['hierarchical'] = $new_instance['hierarchical'] ?? 0;

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
			'title'        => '',
			'limit'        => 3,
			'count'        => false,
			'hierarchical' => false,
		);

		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title (optional):', 'sensei-lms' ); ?></label>
			<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"  value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" />
		</p>
		<!-- Widget Limit: Text Input -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"><?php esc_html_e( 'Number of Categories (optional):', 'sensei-lms' ); ?></label>
			<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>"  value="<?php echo esc_attr( $instance['limit'] ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>" />
		</p>
		<!-- Widget Show Count: Checkbox Input -->
		<p>
			<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>"<?php checked( $instance['count'], 'on' ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php esc_html_e( 'Show post counts', 'sensei-lms' ); ?></label><br />
		</p>
		<!-- Widget Show Hierarchy: Checkbox Input -->
		<p>
			<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'hierarchical' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hierarchical' ) ); ?>"<?php checked( $instance['hierarchical'], 'on' ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'hierarchical' ) ); ?>"><?php esc_html_e( 'Show hierarchy', 'sensei-lms' ); ?></label>
		</p>
		<?php
	}

	/**
	 * Load the output.
	 *
	 * @param  array $instance
	 * @since  1.1.0
	 * @return void
	 */
	protected function load_component( $instance ) {

		$limit        = intval( $instance['limit'] );
		$count        = isset( $instance['count'] ) ? (bool) $instance['count'] : false;
		$hierarchical = isset( $instance['hierarchical'] ) ? (bool) $instance['hierarchical'] : false;

		$cat_args = array(
			'title_li'     => '',
			'taxonomy'     => 'course-category',
			'orderby'      => 'name',
			'show_count'   => $count,
			'hierarchical' => $hierarchical,
		);
		if ( 0 < $limit ) {
			$cat_args['number'] = $limit;
		}
		echo '<ul>';
			wp_list_categories( apply_filters( 'widget_course_categories_args', $cat_args ) );
		echo '</ul>';
	}
}
