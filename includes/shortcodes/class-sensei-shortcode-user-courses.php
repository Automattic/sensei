<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 * This class is loaded int WP by the shortcode loader class.
 *
 * Renders the [sensei_user_courses] shortcode to all courses the current user is taking
 *
 * Shortcode parameters:
 * number - how many courses you'd like to show
 * orderby - the same as the WordPress orderby query parameter
 * order - ASC | DESC
 * status -  complete | active if none specified it will default to all
 *
 * If all courses for a given user is shown, there will also be a toggle link between active and complete. Please note
 * that the number you specify will be respected.
 *
 * @class Sensei_Shortcode_User_Courses
 *
 * @package    Content
 * @subpackage Shortcode
 * @author     Automattic
 *
 * @since 1.9.0
 */
class Sensei_Shortcode_User_Courses implements Sensei_Shortcode_Interface {

	/**
	 * The name of the status filter HTTP query param.
	 *
	 * @var string
	 */
	const MY_COURSES_STATUS_FILTER = 'my_courses_status';

	/**
	 * @var WP_Query to help setup the query needed by the render method.
	 */
	protected $query;

	/**
	 * @var string number of items to show on the current page
	 */
	protected $number;

	/**
	 * @var string ordery by course field
	 * Default: date
	 */
	protected $orderby;

	/**
	 * @var string ASC or DESC
	 * Default: 'DESC'
	 */
	protected $order;

	/**
	 * @var status can be completed or active. If none is specified all will be shown
	 */
	protected $status;

	/**
	 *  Rendering options.
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * Are we in my-courses?
	 *
	 * @var bool
	 */
	private $is_shortcode_initial_status_all = false;

	/**
	 * Current Page ID
	 *
	 * @var int
	 */
	private $page_id = 0;

	/**
	 * Rendering as a block.
	 *
	 * @var bool
	 */
	private $is_block = false;

	/**
	 * Setup the shortcode object
	 *
	 * @since 1.9.0
	 * @param array  $attributes
	 * @param string $content
	 * @param string $shortcode  the shortcode that was called for this instance
	 */
	public function __construct( $attributes, $content, $shortcode ) {
		global $wp_query;
		$this->is_shortcode_initial_status_all = ! isset( $attributes['status'] ) || 'all' === $attributes['status'];

		$attributes = shortcode_atts(
			array(
				'number'  => null,
				'status'  => 'all',
				'orderby' => 'title',
				'order'   => 'ASC',
				'options' => [],
			),
			$attributes,
			$shortcode
		);

		if ( $this->is_shortcode_initial_status_all && $wp_query->is_main_query() ) {
			// Check if we should filter the courses.
			if ( isset( $_GET[ self::MY_COURSES_STATUS_FILTER ] ) ) {
				$course_filter_by_status = sanitize_text_field( $_GET[ self::MY_COURSES_STATUS_FILTER ] );

				if ( ! empty( $course_filter_by_status ) && in_array( $course_filter_by_status, array( 'all', 'active', 'complete' ), true ) ) {
					$attributes['status'] = $course_filter_by_status;
				}
			}
		}

		$this->page_id = $wp_query->get_queried_object_id();

		$per_page = 10;
		if (
			isset( Sensei()->settings->settings['my_course_amount'] )
			&& 0 < absint( Sensei()->settings->settings['my_course_amount'] )
		) {
			$per_page = absint( Sensei()->settings->settings['my_course_amount'] );
		}

		// set up all argument need for constructing the course query
		$this->number  = isset( $attributes['number'] ) ? absint( $attributes['number'] ) : $per_page;
		$this->orderby = isset( $attributes['orderby'] ) ? $attributes['orderby'] : 'title';
		$this->status  = isset( $attributes['status'] ) ? $attributes['status'] : 'all';

		// set the default for menu_order to be ASC
		if ( 'menu_order' === $this->orderby && ! isset( $attributes['order'] ) ) {

			$this->order = 'ASC';

		} else {

			// for everything else use the value passed or the default DESC
			$this->order = isset( $attributes['order'] ) ? $attributes['order'] : 'ASC';

		}

		$this->is_block = ! empty( $attributes['options'] );

		$this->options = wp_parse_args(
			$attributes['options'],
			[
				'featuredImageEnabled'     => true,
				'courseCategoryEnabled'    => true,
				'courseDescriptionEnabled' => true,
				'progressBarEnabled'       => true,
				'columns'                  => 2,
				'layoutView'               => 'list',
			]
		);

	}

	private function is_my_courses() {
		global $wp_query;

		return $wp_query->is_page() && $wp_query->get_queried_object_id() === absint( Sensei()->settings->get( 'my_course_page' ) );
	}

	private function should_filter_course_by_status( $course_status, $user_id ) {
		/**
		 * Filters courses processed by the course query in the
		 * [sensei_user_courses] shortcode.
		 *
		 * @param bool       $should_filter Whether the course should be filtered out.
		 * @param WP_Comment $course_status The current course status record.
		 * @param int        $user_id       The user ID.
		 * @return bool
		 */
		return (bool) apply_filters(
			'sensei_setup_course_query_should_filter_course_by_status',
			false,
			$course_status,
			$user_id
		);
	}

	/**
	 * Sets up the object course query
	 * that will be used in the render method.
	 *
	 * @since 1.9.0
	 */
	protected function setup_course_query() {
		$learner_manager = Sensei_Learner::instance();
		$user_id         = get_current_user_id();
		$empty_callback  = [ $this, 'no_course_message_output' ];

		$number_of_posts = $this->number;
		$query_var_paged = get_query_var( 'paged' );
		$base_query_args = array(
			'orderby'        => $this->orderby,
			'order'          => $this->order,
			'paged'          => empty( $query_var_paged ) ? 1 : $query_var_paged,
			'posts_per_page' => $number_of_posts,
		);

		if ( 'complete' === $this->status ) {
			$this->query    = $learner_manager->get_enrolled_completed_courses_query( $user_id, $base_query_args );
			$empty_callback = [ $this, 'completed_no_course_message_output' ];
		} elseif ( 'active' === $this->status ) {
			$this->query    = $learner_manager->get_enrolled_active_courses_query( $user_id, $base_query_args );
			$empty_callback = [ $this, 'active_no_course_message_output' ];
		} else {
			$this->query = $learner_manager->get_enrolled_courses_query( $user_id, $base_query_args );
		}

		if ( empty( $this->query->found_posts ) ) {
			add_action( 'sensei_loop_course_inside_before', $empty_callback );
		}

	}

	/**
	 * Output the message that tells the user they have
	 * no courses.
	 *
	 * @since 3.0.0
	 */
	public function no_course_message_output() {
		?>

		<li class="user-active">
			<div class="sensei-message info">

				<?php esc_html_e( 'You have no active or completed courses.', 'sensei-lms' ); ?>

				<a href="<?php echo esc_attr( Sensei_Course::get_courses_page_url() ); ?>">

					<?php esc_html_e( 'Start a Course!', 'sensei-lms' ); ?>

				</a>

			</div>
		</li>
		<?php
	}

	/**
	 * Output the message that tells the user they have
	 * no completed courses.
	 *
	 * @since 1.9.0
	 */
	public function completed_no_course_message_output() {
		?>
		<li class="user-completed">
			<div class="sensei-message info">

				<?php esc_html_e( 'You have not completed any courses yet.', 'sensei-lms' ); ?>

			</div>
		</li>
		<?php
	}

	/**
	 * Output the message that tells the user they have
	 * no active courses.
	 *
	 * @since 1.9.0
	 */
	public function active_no_course_message_output() {
		?>

		<li class="user-active">
			<div class="sensei-message info">

				<?php esc_html_e( 'You have no active courses.', 'sensei-lms' ); ?>

				<a href="<?php echo esc_attr( Sensei_Course::get_courses_page_url() ); ?>">

					<?php esc_html_e( 'Start a Course!', 'sensei-lms' ); ?>

				</a>

			</div>
		</li>
		<?php
	}

	/**
	 * Rendering the shortcode this class is responsible for.
	 *
	 * @return string $content
	 */
	public function render() {
		// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		global $wp_query;
		global $sensei_is_block;

		$sensei_is_block = $this->is_block;

		if ( false === is_user_logged_in() ) {
			// show the login form
			return $this->render_login_form();
		}
		// setup the course query that will be used when rendering
		$this->setup_course_query();

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Mocking loop for shortcode. Reset below.
		$wp_query = $this->query;

		$this->attach_shortcode_hooks();

		// mostly hooks added for legacy and backwards compatiblity sake
		do_action( 'sensei_my_courses_before' );
		do_action( 'sensei_before_user_course_content', wp_get_current_user() );

		ob_start();
		echo '<section id="sensei-user-courses">';

		if ( ! $sensei_is_block ) {
			Sensei_Messages::the_my_messages_link();
		}

		do_action( 'sensei_my_courses_content_inside_before' );
		Sensei_Templates::get_template( 'loop-course.php' );
		do_action( 'sensei_my_courses_content_inside_after' );
		Sensei_Templates::get_template( 'globals/pagination.php' );
		echo '</section>';

		// mostly hooks added for legacy and backwards compatiblity sake
		do_action( 'sensei_after_user_course_content', wp_get_current_user() );
		do_action( 'sensei_my_courses_after' );

		$shortcode_output = ob_get_clean();

		// phpcs:ignore WordPress.WP.DiscouragedFunctions.wp_reset_query_wp_reset_query -- wp_reset_postdata() is not a good alternative.
		wp_reset_query();

		$this->detach_shortcode_hooks();
		$sensei_is_block = false;

		return $shortcode_output;

	}

	/**
	 * Add hooks for the shortcode
	 *
	 * @since 1.9.0
	 */
	public function attach_shortcode_hooks() {

		// Don't show the toggle action if the user specified complete or active for this shortcode.
		if ( $this->is_shortcode_initial_status_all ) {
			add_action( 'sensei_loop_course_before', array( $this, 'course_toggle_actions' ) );
		}

		add_filter( 'sensei_course_loop_content_class', array( $this, 'course_status_class_tagging' ), 20, 2 );

		if ( $this->is_block ) {
			// Remove default WordPress theme hook that overrides Sensei styles.
			remove_filter( 'wp_get_attachment_image_attributes', 'twenty_twenty_one_get_attachment_image_attributes', 10 );

			remove_action( 'sensei_course_content_inside_before', array( Sensei()->course, 'the_course_meta' ) );
			remove_action( 'sensei_course_content_inside_before', array( Sensei()->course, 'course_image' ), 30 );

			if ( $this->options['featuredImageEnabled'] ) {
				add_action( 'sensei_course_content_inside_before', array( Sensei()->course, 'course_image' ), 1 );
			}

			add_action( 'sensei_course_content_inside_before', array( $this, 'add_course_details_wrapper_start' ), 2 );

			if ( $this->options['courseCategoryEnabled'] ) {
				add_action( 'sensei_course_content_inside_before', array( $this, 'course_category' ), 6 );
			}

			if ( ! $this->options['courseDescriptionEnabled'] ) {
				add_filter( 'get_the_excerpt', '__return_false' );
			}

			if ( $this->options['progressBarEnabled'] ) {
				add_action( 'sensei_course_content_inside_after', array( $this, 'attach_course_progress' ) );
			}
		}

		add_action( 'sensei_course_content_inside_after', array( $this, 'attach_course_buttons' ) );
		$this->is_block && add_action( 'sensei_course_content_inside_after', array( $this, 'add_course_details_wrapper_end' ) );
	}

	/**
	 * Remove hooks for the shortcode
	 *
	 * @since 1.9.0
	 */
	public function detach_shortcode_hooks() {

		// Remove all hooks after the output is generated.
		remove_action( 'sensei_course_content_inside_before', array( $this, 'course_category' ), 3 );
		remove_action( 'sensei_course_content_inside_after', array( $this, 'attach_course_progress' ) );
		remove_action( 'sensei_course_content_inside_after', array( $this, 'attach_course_buttons' ) );
		remove_filter( 'sensei_course_loop_content_class', array( $this, 'course_status_class_tagging' ), 20 );
		remove_action( 'sensei_loop_course_before', array( $this, 'course_toggle_actions' ) );
		remove_filter( 'get_the_excerpt', '__return_false' );

		if ( false === $this->options['featuredImageEnabled'] ) {
			add_action( 'sensei_course_content_inside_before', array( Sensei()->course, 'course_image' ), 30, 1 );
		}

		if ( $this->is_block ) {
			add_action( 'sensei_course_content_inside_before', array( Sensei()->course, 'the_course_meta' ) );
		}
	}

	/**
	 * Hooks into sensei_course_content_inside_after
	 *
	 * @param int $course_id Course ID.
	 */
	public function attach_course_progress( $course_id ) {

		if ( $this->is_block ) {
			$progress_block = ( new Sensei_Course_Progress_Block() )->render_course_progress( [ 'postId' => $course_id ] );
			echo wp_kses_post( $progress_block );
		} else {
			$percentage = Sensei()->course->get_completion_percentage( $course_id, get_current_user_id() );
			echo wp_kses_post( Sensei()->course->get_progress_meter( $percentage ) );
		}

	}


	/**
	 * Hooked into sensei_course_content_inside_after
	 *
	 * Prints out the course action buttons
	 *
	 * @param integer $course_id
	 */
	public function attach_course_buttons( $course_id ) {

		Sensei()->course->the_course_action_buttons( get_post( $course_id ) );

	}

	/**
	 * Display course categories.
	 *
	 * @param int|WP_Post $course
	 */
	public function course_category( $course ) {
		$category_output = get_the_term_list( $course, 'course-category', '', ', ', '' );
		echo '<span class="wp-block-sensei-lms-learner-courses__courses-list__category">
					' . wp_kses_post( $category_output ) . '
				</span>';
	}

	/**
	 * Add an opening wrapper element around the course details.
	 */
	public function add_course_details_wrapper_start() {
		echo '<div class="wp-block-sensei-lms-learner-courses__courses-list__details">';
	}

	/**
	 * Add a closing wrapper element around the course details.
	 */
	public function add_course_details_wrapper_end() {
		echo '</div>';
	}

	/**
	 * Add a the user status class for the given course.
	 *
	 * @since 1.9
	 *
	 * @param  array   $classes
	 * @param  WP_Post $course
	 * @return array $classes
	 */
	public function course_status_class_tagging( $classes, $course ) {

		if ( Sensei_Utils::user_completed_course( $course, get_current_user_id() ) ) {

			$classes[] = 'user-completed';

		} else {

			$classes[] = 'user-active';

		}

		return $classes;

	}

	/**
	 * Output the course toggle functionality
	 */
	public function course_toggle_actions() {
		/**
		 * Determine if we should display course toggles on User Courses Shortcode.
		 *
		 * @param bool $should_display_course_toggles Should we Display the course toggles.
		 *
		 * @since 1.9.18
		 *
		 * @return bool
		 */
		$should_display_course_toggles = (bool) apply_filters( 'sensei_shortcode_user_courses_display_course_toggle_actions', true );
		if ( false === $should_display_course_toggles ) {
			   return;
		}

		$active_filter_options = array(
			'all'      => __( 'All Courses', 'sensei-lms' ),
			'active'   => __( 'Active Courses', 'sensei-lms' ),
			'complete' => __( 'Completed Courses', 'sensei-lms' ),
		);

		$base_url = get_page_link( $this->page_id );
		?>

		<section id="user-course-status-toggle">
			<?php
			foreach ( $active_filter_options as $key => $option ) {
				$css_class = $key === $this->status ? 'active' : 'inactive';
				$url       = add_query_arg( self::MY_COURSES_STATUS_FILTER, $key, $base_url );
				?>
				<a id="sensei-user-courses-all-action" href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $css_class ); ?>"><?php echo esc_html( $option ); ?></a>
			<?php } ?>
		</section>

		<?php
	}


	/**
	 * Load the javascript for the toggle functionality
	 *
	 * @since 1.9.0
	 */
	function print_course_toggle_actions_inline_script() {
		?>

		<script type="text/javascript">
			var buttonContainer = jQuery('#user-course-status-toggle');
			var courseList = jQuery('ul.course-container');

			///
			/// EVENT LISTENERS
			///
			buttonContainer.on('click','a#sensei-user-courses-active-action', function( e ){

				e.preventDefault();
				sensei_user_courses_hide_all_completed();
				sensei_user_courses_show_all_active();
				sensei_users_courses_toggle_button_active( e );


			});

			buttonContainer.on('click', 'a#sensei-user-courses-complete-action', function( e ){

				e.preventDefault();
				sensei_user_courses_hide_all_active();
				sensei_user_courses_show_all_completed();
				sensei_users_courses_toggle_button_active( e );

			});


			///
			// Set initial state
			///
			jQuery( 'a#sensei-user-courses-active-action').trigger( 'click' );


			///
			/// FUNCTIONS
			///
			function sensei_user_courses_hide_all_completed(){

				courseList.children('li.user-completed').hide();

			}

			function sensei_user_courses_hide_all_active(){

				courseList.children('li.user-active').hide();

			}

			function sensei_user_courses_show_all_completed(){

				courseList.children('li.user-completed').show();

			}

			function sensei_user_courses_show_all_active(){

				courseList.children('li.user-active').show();

			}

			/**
			 * Toggle buttons active a classes
			 */
			function sensei_users_courses_toggle_button_active( e ){

				//reset both buttons
				buttonContainer.children('a').removeClass( 'active' );
				buttonContainer.children('a').addClass( 'inactive' );

				// setup the curent clicked button
				jQuery( e.target).addClass( 'active' ) ;
				jQuery( e.target).removeClass( 'inactive' ) ;

			}

		</script>

		<?php
	}

	/**
	 * @return string
	 */
	private function render_login_form() {
		ob_start();
		Sensei()->frontend->sensei_login_form();
		$shortcode_output = ob_get_clean();
		return $shortcode_output;
	}

}
