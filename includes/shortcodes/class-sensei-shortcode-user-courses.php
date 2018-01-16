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
	 * Default: -1.
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
	protected  $order;

	/**
	 * @var status can be completed or active. If none is specified all will be shown
	 */
	protected $status;

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

		$attributes = shortcode_atts( array(
			'number' => '10',
			'status' => 'all',
			'orderby' => 'title',
			'order' => 'ASC'
		), $attributes, $shortcode );

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

		// set up all argument need for constructing the course query
		$this->number = isset( $attributes['number'] ) ? $attributes['number'] : '10';
		$this->orderby = isset( $attributes['orderby'] ) ? $attributes['orderby'] : 'title';
		$this->status = isset( $attributes['status'] ) ? $attributes['status'] : 'all';

		// set the default for menu_order to be ASC
		if ( 'menu_order' == $this->orderby && ! isset( $attributes['order'] ) ) {

			$this->order = 'ASC';

		} else {

			// for everything else use the value passed or the default DESC
			$this->order = isset( $attributes['order'] ) ? $attributes['order'] : 'ASC';

		}

	}

	private function is_my_courses() {
		global $wp_query;

		return $wp_query->is_page() && $wp_query->get_queried_object_id() === absint( Sensei()->settings->get( 'my_course_page' ) );
	}

	private function should_filter_course_by_status( $course_status, $user_id ) {
		$should_filter = Sensei_WC_Subscriptions::has_user_bought_subscription_but_cancelled(
			$course_status->comment_post_ID,
			$user_id
		);

		return (bool) apply_filters(
			'sensei_setup_course_query_should_filter_course_by_status',
			$should_filter,
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
		$user_id = get_current_user_id();
		$status_query = array(
			'user_id' => $user_id,
			'type' => 'sensei_course_status',
		);
		$user_courses_logs = Sensei_Utils::sensei_check_for_activity( $status_query, true );
		if ( ! is_array( $user_courses_logs ) ) {

			$user_courses_logs = array( $user_courses_logs );

		}

		$completed_ids = array();
		$active_ids = array();
		foreach ( $user_courses_logs as $course_status ) {
			if ( true === $this->should_filter_course_by_status( $course_status, $user_id ) ) {
				continue;
			}
			if ( Sensei_Utils::user_completed_course( $course_status, get_current_user_id() ) ) {

				$completed_ids[] = $course_status->comment_post_ID;

			} else {

				$active_ids[] = $course_status->comment_post_ID;

			}
		}

		if ( 'complete' == $this->status ) {

			$included_courses = empty( $completed_ids ) ? array( '-1000' ) : $completed_ids;
			if ( empty( $completed_ids ) ) {
				add_action( 'sensei_loop_course_inside_before', array( $this, 'completed_no_course_message_output' ) );
			}

		} elseif ( 'active' == $this->status ) {

			$included_courses = empty( $active_ids ) ? array( '-1000' ) : $active_ids;
			if ( empty( $active_ids ) ) {
				add_action( 'sensei_loop_course_inside_before', array( $this, 'active_no_course_message_output' ) );
			}

		} else { // all courses

			if ( empty( $completed_ids ) && empty( $active_ids ) ) {

				$included_courses = array( '-1000' ); // don't show any courses

			} else {
				$included_courses = Sensei_Utils::array_zip_merge( (array) $active_ids, (array) $completed_ids );
			}
		}

		$number_of_posts = $this->number;

		// course query parameters
		$query_var_paged = get_query_var( 'paged' );
		$query_args = array(
			'post_type'        => 'course',
			'post_status'      => 'publish',
			'orderby'          => $this->orderby,
			'order'            => $this->order,
			'paged' => empty( $query_var_paged ) ? 1 : $query_var_paged,
			'posts_per_page'   => $number_of_posts,
			'post__in'         => $included_courses,
		);

		$this->query = new WP_Query( $query_args );

	}//end setup_course_query()

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

				<?php _e( 'You have not completed any courses yet.', 'woothemes-sensei' ); ?>

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

				<?php _e( 'You have no active courses.', 'woothemes-sensei' ); ?>

				<a href="<?php echo esc_attr( Sensei_Course::get_courses_page_url() ); ?>">

					<?php _e( 'Start a Course!', 'woothemes-sensei' ); ?>

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

		global $wp_query;

		if ( false === is_user_logged_in() ) {
			// show the login form
			return $this->render_login_form();
		}
		// setup the course query that will be used when rendering
		$this->setup_course_query();

		// keep a reference to old query
		$current_global_query = $wp_query;

		// assign the query setup in $this-> setup_course_query
		$wp_query = $this->query;

		$this->attach_shortcode_hooks();

		// mostly hooks added for legacy and backwards compatiblity sake
		do_action( 'sensei_my_courses_before' );
		do_action( 'sensei_before_user_course_content', get_current_user() );

		ob_start();
		echo '<section id="sensei-user-courses">';

		Sensei_Messages::the_my_messages_link();
		do_action( 'sensei_my_courses_content_inside_before' );
		Sensei_Templates::get_template( 'loop-course.php' );
		do_action( 'sensei_my_courses_content_inside_after' );
		Sensei_Templates::get_template( 'globals/pagination.php' );
		echo '</section>';

		// mostly hooks added for legacy and backwards compatiblity sake
		do_action( 'sensei_after_user_course_content', get_current_user() );
		do_action( 'sensei_my_courses_after' );

		$shortcode_output = ob_get_clean();

		$this->detach_shortcode_hooks();

		// restore old query
		$wp_query = $current_global_query;

		wp_reset_postdata();

		return $shortcode_output;

	}//end render()

	/**
	 * Add hooks for the shortcode
	 *
	 * @since 1.9.0
	 */
	public function attach_shortcode_hooks() {

		// attach the toggle functionality
		// don't show the toggle action if the user specified complete or active for this shortcode
		if ( $this->is_shortcode_initial_status_all ) {

			add_action( 'sensei_loop_course_before', array( $this, 'course_toggle_actions' ) );
			add_action( 'wp_footer', array( $this, 'print_course_toggle_actions_inline_script' ), 90 );

		}

		// add extra classes to distinguish the course based on user completed or active
		add_filter( 'sensei_course_loop_content_class', array( $this, 'course_status_class_tagging' ), 20, 2 );

		// attach progress meter below course
		add_action( 'sensei_course_content_inside_after', array( $this, 'attach_course_progress' ) );
		add_action( 'sensei_course_content_inside_after', array( $this, 'attach_course_buttons' ) );

	}

	/**
	 * Remove hooks for the shortcode
	 *
	 * @since 1.9.0
	 */
	public function detach_shortcode_hooks() {

		// remove all hooks after the output is generated
		remove_action( 'sensei_course_content_inside_after', array( $this, 'attach_course_progress' ) );
		remove_action( 'sensei_course_content_inside_after', array( $this, 'attach_course_buttons' ) );
		remove_filter( 'sensei_course_loop_content_class', array( $this, 'course_status_class_tagging' ), 20, 2 );
		remove_action( 'sensei_loop_course_before', array( $this, 'course_toggle_actions' ) );
	}

	/**
	 * Hooks into sensei_course_content_inside_after
	 *
	 * @param $course
	 */
	public function attach_course_progress( $course_id ) {

		$percentage = Sensei()->course->get_completion_percentage( $course_id, get_current_user_id() );
		echo Sensei()->course->get_progress_meter( $percentage );

	}//end attach_course_progress()


	/**
	 * Hooked into sensei_course_content_inside_after
	 *
	 * Prints out the course action buttons
	 *
	 * @param integer $course_id
	 */
	public function attach_course_buttons( $course_id ) {

		Sensei()->course->the_course_action_buttons( get_post( $course_id ) );

	}//end attach_course_buttons()

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

	}//end course_status_class_tagging()

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
			'all'      => __( 'All Courses', 'woothemes-sensei' ),
			'active'   => __( 'Active Courses', 'woothemes-sensei' ),
			'complete' => __( 'Completed Courses', 'woothemes-sensei' ),
		);

		$base_url = get_page_link( $this->page_id );
		?>

		<section id="user-course-status-toggle">
			<?php
			foreach ( $active_filter_options as $key => $option ) {
				$css_class = $key === $this->status ? 'active' : 'inactive';
				$url = add_query_arg( self::MY_COURSES_STATUS_FILTER, $key, $base_url );
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
