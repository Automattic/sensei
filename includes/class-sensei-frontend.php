<?php
/**
 * Frontend
 *
 * Handles the frontend display.
 *
 * @package Sensei\Frontend
 * @since 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Frontend Class
 *
 * All functionality pertaining to the frontend of Sensei.
 *
 * @package Core
 * @author Automattic
 *
 * @since 1.0.0
 */
class Sensei_Frontend {
	/**
	 * Messages to display to the user.
	 *
	 * @var string $messages
	 */
	public $messages;
	/**
	 * TODO: Eliminate this.
	 *
	 * @var stdClass $data
	 */
	public $data;
	/**
	 * List of allowed HTML elements.
	 *
	 * @var array $allowed_html
	 */
	public $allowed_html;

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {

		$this->allowed_html = array(
			'embed'  => array(),
			'iframe' => array(
				'width'           => array(),
				'height'          => array(),
				'src'             => array(),
				'frameborder'     => array(),
				'allowfullscreen' => array(),
			),
			'video'  => Sensei_Wp_Kses::get_video_html_tag_allowed_attributes(),
		);

		// Template output actions.
		add_action( 'sensei_before_main_content', array( $this, 'sensei_output_content_wrapper' ), 10 );
		add_action( 'sensei_after_main_content', array( $this, 'sensei_output_content_wrapper_end' ), 10 );
		add_action( 'sensei_lesson_archive_lesson_title', array( $this, 'sensei_lesson_archive_lesson_title' ), 10 );
		add_action( 'wp', array( $this, 'sensei_complete_lesson' ), 10 );
		add_action( 'wp_head', array( $this, 'sensei_complete_course' ), 10 );
		add_action( 'sensei_course_status_updated', array( $this, 'redirect_to_course_completed_page' ), 10, 3 );
		add_action( 'sensei_frontend_messages', array( $this, 'sensei_frontend_messages' ) );
		add_action( 'sensei_lesson_video', array( $this, 'sensei_lesson_video' ), 10, 1 );
		add_action( 'sensei_complete_lesson_button', array( $this, 'sensei_complete_lesson_button' ) );
		add_action( 'sensei_reset_lesson_button', array( $this, 'sensei_reset_lesson_button' ) );
		add_action( 'sensei_course_archive_meta', array( $this, 'sensei_course_archive_meta' ) );
		add_action( 'sensei_lesson_meta', array( $this, 'sensei_lesson_meta' ), 10 );
		add_action( 'wp', array( $this, 'sensei_course_start' ), 10 );
		add_filter( 'wp_login_failed', array( $this, 'sensei_login_fail_redirect' ), 10 );
		add_filter( 'init', array( $this, 'sensei_handle_login_request' ), 10 );
		add_action( 'init', array( $this, 'sensei_process_registration' ), 2 );

		add_action( 'sensei_pagination', array( $this, 'sensei_breadcrumb' ), 80, 1 );

		// Fix pagination for course archive pages when filtering by course type.
		add_filter( 'pre_get_posts', array( $this, 'sensei_course_archive_pagination' ) );

		// Scripts and Styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Custom Menu Item filters.
		add_filter( 'wp_setup_nav_menu_item', array( $this, 'sensei_setup_nav_menu_item' ) );
		add_filter( 'wp_nav_menu_objects', array( $this, 'sensei_wp_nav_menu_objects' ) );
		// Search Results filters.
		add_filter( 'post_class', array( $this, 'sensei_search_results_classes' ), 10 );
		// Only show course & lesson excerpts in search results.
		add_filter( 'the_content', array( $this, 'sensei_search_results_excerpt' ) );

		// Lesson tags.
		add_action( 'sensei_lesson_meta_extra', array( $this, 'lesson_tags_display' ), 10, 1 );
		add_action( 'pre_get_posts', array( $this, 'lesson_tag_archive_filter' ), 10, 1 );
		add_filter( 'sensei_lessons_archive_text', array( $this, 'lesson_tag_archive_header' ) );
		add_action( 'sensei_loop_lesson_inside_before', array( $this, 'lesson_tag_archive_description' ), 11 );

		// Hide Sensei activity comments from lesson and course pages.
		add_filter( 'wp_list_comments_args', array( $this, 'hide_sensei_activity' ) );
	}

	/**
	 * Graceful fallback for course and lesson variables on Frontend object.
	 *
	 * @param string $key Key to get.
	 * @since  1.7.3
	 * @return array|mixed
	 */
	public function __get( $key ) {

		if ( 'lesson' == $key || 'course' == $key ) {
			if ( WP_DEBUG ) {
				trigger_error( sprintf( 'Sensei()->frontend->%1$s has been <strong>deprecated</strong> since version %2$s! Please use Sensei()->%1$s to access the instance.', esc_html( $key ), '1.7.3' ) );
			}
			return Sensei()->$key;
		}

		return null;
	}

	/**
	 * Enqueue frontend JavaScripts.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
		$disable_js = Sensei_Utils::get_setting_as_flag( 'js_disable', 'sensei_settings_js_disable' );

		if ( ! $disable_js ) {

			// Course Archive javascript.
			if ( is_post_type_archive( 'course' ) ) {

				Sensei()->assets->register( 'sensei-course-archive-js', 'js/frontend/course-archive.js', [ 'jquery' ], true );
				wp_enqueue_script( 'sensei-course-archive-js' );

			}

			Sensei()->assets->register( 'sensei-stop-double-submission', 'js/stop-double-submission.js', [], true );
			Sensei()->assets->register( Sensei()->token . '-user-dashboard', 'js/user-dashboard.js', [ 'jquery-ui-tabs' ], true );

			// Allow additional scripts to be loaded.
			do_action( 'sensei_additional_scripts' );

		}

	}

	/**
	 * Enqueue frontend CSS files.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function enqueue_styles() {
		Sensei()->assets->enqueue( 'pages-frontend', 'css/pages-frontend.css' );

		$disable_styles = Sensei_Utils::get_setting_as_flag( 'styles_disable', 'sensei_disable_styles' );

		if ( ! $disable_styles ) {
			Sensei()->assets->enqueue( Sensei()->token . '-frontend', 'css/frontend.css', [], 'screen' );

			// Allow additional stylesheets to be loaded.
			do_action( 'sensei_additional_styles' );
		}
	}

	/**
	 * Get template part.
	 *
	 * @deprecated since 1.9.0 use Sensei_Templates::get_part
	 * @access public
	 * @param mixed  $slug Template slug.
	 * @param string $name Optional. Template name. Default ''.
	 * @return void
	 */
	function sensei_get_template_part( $slug, $name = '' ) {

		// To be removed in 5.0.0.
		_deprecated_function( __METHOD__, '1.9.0', 'Sensei_Templates::get_part' );

		Sensei_Templates::get_part( $slug, $name );

	}

	/**
	 * Output the start of the content wrapper.
	 *
	 * @access public
	 * @return void
	 */
	function sensei_output_content_wrapper() {

		// backwards compatibility check for old location under the wrappers directory of the active theme.
		$backwards_compatible_wrapper_location = array(
			Sensei()->template_url . 'wrappers/wrapper-start.php',
			'wrappers/wrapper-start.php',
		);

		$template = locate_template( $backwards_compatible_wrapper_location );
		if ( ! empty( $template ) ) {

			Sensei_Templates::get_template( 'wrappers/wrapper-start.php' );
			return;

		}

		Sensei_Templates::get_template( 'globals/wrapper-start.php' );

	}


	/**
	 * Output the end of the content wrapper.
	 *
	 * @access public
	 * @return void
	 */
	function sensei_output_content_wrapper_end() {

		// backwards compatibility check for old location under the wrappers directory of the active theme.
		$backwards_compatible_wrapper_location = array(
			Sensei()->template_url . 'wrappers/wrapper-end.php',
			'wrappers/wrapper-end.php',
		);

		$backwards_compatible_template = locate_template( $backwards_compatible_wrapper_location );
		if ( ! empty( $backwards_compatible_template ) ) {

			Sensei_Templates::get_template( 'wrappers/wrapper-end.php' );
			return;

		}

		Sensei_Templates::get_template( 'globals/wrapper-end.php' );

	}


	/**
	 * Load content pagination template.
	 *
	 * @access public
	 * @return void
	 */
	public static function load_content_pagination() {

		if ( is_singular( 'course' ) ) {

			// backwards compatibility check for old location under the wrappers directory of the active theme.
			$template = locate_template( array( Sensei()->template_url . 'wrappers/pagination-posts.php' ) );
			if ( ! empty( $template ) ) {

				Sensei_Templates::get_template( 'wrappers/pagination-posts.php' );
				return;

			}

			Sensei_Templates::get_template( 'globals/pagination-posts.php' );

		} elseif ( is_tax( 'module' ) || is_singular( 'lesson' ) ) {
			// Backwards compatibility check for old location under the wrappers directory of the active theme.
			$template = locate_template( array( Sensei()->template_url . 'wrappers/pagination-lesson.php' ) );
			if ( ! empty( $template ) ) {

				Sensei_Templates::get_template( 'wrappers/pagination-lesson.php' );
				return;

			}

			Sensei_Templates::get_template( 'globals/pagination-lesson.php' );

		} elseif ( is_singular( 'quiz' ) ) {

			// backwards compatibility check for old location under the wrappers directory of the active theme.
			$template = locate_template( array( Sensei()->template_url . 'wrappers/pagination-quiz.php' ) );
			if ( ! empty( $template ) ) {

				Sensei_Templates::get_template( 'wrappers/pagination-quiz.php' );
				return;

			}

			Sensei_Templates::get_template( 'globals/pagination-quiz.php' );

		} else {

			// backwards compatibility check for old location under the wrappers directory of the active theme.
			$template = locate_template( array( Sensei()->template_url . 'wrappers/pagination.php' ) );
			if ( ! empty( $template ) ) {

				Sensei_Templates::get_template( 'wrappers/pagination.php' );
				return;

			}

			Sensei_Templates::get_template( 'globals/pagination.php' );

		}

	}

	/**
	 * Outputs comments for the specified pages.
	 *
	 * @deprecated
	 * @return void
	 */
	function sensei_output_comments() {

		Sensei_Lesson::output_comments();

	}

	/**
	 * Generates URLs for custom menu items.
	 *
	 * @access public
	 * @param object $item Menu item.
	 * @return object $item Menu item.
	 */
	public function sensei_setup_nav_menu_item( $item ) {
		global $pagenow, $wp_rewrite;

		if ( 'nav-menus.php' != $pagenow && ! defined( 'DOING_AJAX' ) && isset( $item->url ) && 'custom' == $item->type ) {
			// Set up Sensei menu links.
			$my_account_page_id = intval( Sensei()->settings->settings['my_course_page'] );
			$course_page_url    = Sensei_Course::get_courses_page_url();
			$lesson_archive_url = get_post_type_archive_link( 'lesson' );
			$my_courses_url     = get_permalink( $my_account_page_id );
			$my_messages_url    = get_post_type_archive_link( 'sensei_message' );

			switch ( $item->url ) {
				case '#senseicourses':
					$item->url = $course_page_url;
					break;

				case '#senseilessons':
					$item->url = $lesson_archive_url;
					break;

				case '#senseimycourses':
					$item->url = $my_courses_url;
					break;

				case '#senseimymessages':
					$item->url = $my_messages_url;
					// if no archive link exist for sensei_message
					// set it back to the place holder.
					if ( ! $item->url ) {

						$item->url = '#senseimymessages';

					}
					break;

				case '#senseilearnerprofile':
					$item->url = esc_url( Sensei()->learner_profiles->get_permalink() );
					break;

				case '#senseiloginlogout':
						$logout_url = wp_logout_url( home_url() );
						// Login link links to the My Courses page, to avoid the WP dashboard.
						$login_url = $my_courses_url;

						$item->url = ( is_user_logged_in() ? $logout_url : $login_url );

						// determine the menu title login or logout.
					if ( is_user_logged_in() ) {
						$menu_title = __( 'Logout', 'sensei-lms' );
					} else {
						$menu_title = __( 'Login', 'sensei-lms' );
					}

						/**
						 * Action Filter: login/logout menu title
						 *
						 * With this filter you can alter the login / login menu item title string
						 *
						 * @param $menu_title
						 */
						$item->title = apply_filters( 'sensei_login_logout_menu_title', $menu_title );

					break;

				default:
					break;
			}

			$_root_relative_current = untrailingslashit( $_SERVER['REQUEST_URI'] );
			$current_url            = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_root_relative_current );
			$item_url               = untrailingslashit( $item->url );
			$_indexless_current     = untrailingslashit( preg_replace( '/' . preg_quote( $wp_rewrite->index, '/' ) . '$/', '', $current_url ) );
			// Highlight current menu item.
			if ( $item_url && in_array( $item_url, array( $current_url, $_indexless_current, $_root_relative_current ) ) ) {
				$item->classes[] = 'current-menu-item current_page_item';
			}
		} // endif nav

		return $item;

	}

	/**
	 *
	 * Removes custom menu items depending on settings and logged in status.
	 *
	 * @access public
	 * @param object $sorted_menu_items Menu items, sorted by each menu item's menu order.
	 * @return object $sorted_menu_items Filtered list of menu items.
	 */
	public function sensei_wp_nav_menu_objects( $sorted_menu_items ) {

		foreach ( $sorted_menu_items as $k => $item ) {

			// Remove the My Messages link for logged out users or if Private Messages are disabled.
			if ( ! get_post_type_archive_link( 'sensei_message' )
				&& '#senseimymessages' == $item->url ) {

				if ( ! is_user_logged_in() || ( isset( Sensei()->settings->settings['messages_disable'] ) && Sensei()->settings->settings['messages_disable'] ) ) {

					unset( $sorted_menu_items[ $k ] );

				}
			}
			// Remove the My Profile link for logged out users.
			if ( Sensei()->learner_profiles->get_permalink() == $item->url ) {

				if ( ! is_user_logged_in() || ! ( isset( Sensei()->settings->settings['learner_profile_enable'] ) && Sensei()->settings->settings['learner_profile_enable'] ) ) {

					unset( $sorted_menu_items[ $k ] );

				}
			}
		}
		return $sorted_menu_items;
	}

	/**
	 * Adds category nicenames to the body and post class.
	 *
	 * @param array $classes CSS classes for the current post.
	 * @return array Filtered list of CSS classes for the current post.
	 */
	function sensei_search_results_classes( $classes ) {
		global $post;
		// Handle Search Classes for Courses, Lessons, and WC Products.
		if ( isset( $post->post_type ) && ( ( 'course' == $post->post_type ) || ( 'lesson' == $post->post_type ) || ( 'product' == $post->post_type ) ) ) {
			$classes[] = 'post';
		}
		return $classes;
	}

	/**
	 * Outputs the course image.
	 *
	 * @deprecated since 1.9.0 use Sensei()->course->course_image
	 * @param int    $course_id Course ID.
	 * @param string $width Optional. Image width. Default '100'.
	 * @param string $height Optional. Image height. Default '100'.
	 * @param bool   $return true if the image should be returned, false if the image should be
	 *                       echoed.
	 * @return string|null Course image or null if the image was echoed.
	 */
	function sensei_course_image( $course_id, $width = '100', $height = '100', $return = false ) {

		// To be removed in 5.0.0.
		_deprecated_function( __METHOD__, '1.9.0', 'Sensei()->course->course_image' );
		if ( ! $return ) {

			echo wp_kses_post( Sensei()->course->course_image( $course_id, $width, $height ) );
			return '';

		}

		return Sensei()->course->course_image( $course_id, $width, $height );

	}

	/**
	 * Outputs the lesson image.
	 *
	 * @since  1.2.0
	 * @deprecated since 1.9.0 use Sensei()->lesson->lesson_image
	 * @param int        $lesson_id Lesson ID.
	 * @param string     $width Optional. Image width. Default '100'.
	 * @param string     $height Optional. Image height. Default '100'.
	 * @param bool       $return true if the image should be returned, false if the image should be
	 *                           echoed.
	 * @param bool|false $widget Widget.
	 * @return string Lesson image or empty string if the image was echoed.
	 */
	function sensei_lesson_image( $lesson_id, $width = '100', $height = '100', $return = false, $widget = false ) {

		// To be removed in 5.0.0.
		_deprecated_function( __METHOD__, '1.9.0', 'Sensei()->lesson->lesson_image' );

		if ( ! $return ) {

			echo wp_kses_post( Sensei()->lesson->lesson_image( $lesson_id, $width, $height, $widget ) );
			return '';
		}

		return Sensei()->lesson->lesson_image( $lesson_id, $width, $height, $widget );

	}

	/**
	 * Pagination for course archive pages when filtering by course type.
	 *
	 * @since 1.0.0
	 * @param WP_Query $query WP_Query instance.
	 */
	function sensei_course_archive_pagination( $query ) {

		if ( ! is_admin() && $query->is_main_query() && isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'newcourses', 'featuredcourses', 'freecourses', 'paidcourses' ) ) ) {

			$amount = 0;
			if ( isset( Sensei()->settings->settings['course_archive_amount'] ) && ( 0 < absint( Sensei()->settings->settings['course_archive_amount'] ) ) ) {
				$amount = absint( Sensei()->settings->settings['course_archive_amount'] );
			}

			if ( $amount ) {
				$query->set( 'posts_per_page', $amount );
			}

			$query->set( 'orderby', 'menu_order date' );

		}
	}

	/**
	 * Output for course archive page individual course title.
	 *
	 * @since  1.2.0
	 * @param WP_Post $post_item Post.
	 * @return void
	 */
	function sensei_course_archive_course_title( $post_item ) {
		if ( isset( $post_item->ID ) && ( 0 < $post_item->ID ) ) {
			$post_id    = absint( $post_item->ID );
			$post_title = $post_item->post_title;
		} else {
			$post_id    = get_the_ID();
			$post_title = get_the_title();
		}
		?><header><h2><a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" title="<?php echo esc_attr( $post_title ); ?>"><?php echo esc_html( $post_title ); ?></a></h2></header>
		<?php
	}

	/**
	 * Outputs the title on the course archive page.
	 *
	 * @since  1.2.1
	 * @return void
	 */
	public function sensei_lesson_archive_lesson_title() {
		$post_id    = get_the_ID();
		$post_title = get_the_title();
		?>
		<header><h2><a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" title="<?php echo esc_attr( $post_title ); ?>"><?php echo esc_html( $post_title ); ?></a></h2></header>
		<?php
	}

	/**
	 * Outputs the breadcrumb for lessons and quizzes.
	 *
	 * @since  1.7.0
	 * @param  integer $id Course, lesson or quiz ID.
	 * @return void
	 */
	public function sensei_breadcrumb( $id = 0 ) {

		// Only output on lesson, quiz and taxonomy (module) pages.
		if ( ! ( is_tax( 'module' ) || is_singular( 'lesson' ) || is_singular( 'quiz' ) ) ) {
			return;
		}

		if ( empty( $id ) ) {

			$id = get_the_ID();

		}

		$sensei_breadcrumb_prefix = __( 'Back to: ', 'sensei-lms' );
		$separator                = apply_filters( 'sensei_breadcrumb_separator', '&gt;' );

		$html = '<section class="sensei-breadcrumb">' . esc_html( $sensei_breadcrumb_prefix );
		// Lesson.
		if ( is_singular( 'lesson' ) && 0 < intval( $id ) ) {
			$course_id = intval( get_post_meta( $id, '_lesson_course', true ) );
			if ( ! $course_id ) {
				return;
			}
			$html .= '<a href="' . esc_url( get_permalink( $course_id ) ) . '" title="' . esc_attr__( 'Back to the course', 'sensei-lms' ) . '">' . esc_html( get_the_title( $course_id ) ) . '</a>';
		}
		// Quiz.
		if ( is_singular( 'quiz' ) && 0 < intval( $id ) ) {
			$lesson_id = intval( get_post_meta( $id, '_quiz_lesson', true ) );
			if ( ! $lesson_id ) {
				return;
			}
			 $html .= '<a href="' . esc_url( get_permalink( $lesson_id ) ) . '" title="' . esc_attr__( 'Back to the lesson', 'sensei-lms' ) . '">' . esc_html( get_the_title( $lesson_id ) ) . '</a>';
		}

		// Allow other plugins to filter html.
		$html  = apply_filters( 'sensei_breadcrumb_output', $html, $separator );
		$html .= '</section>';

		echo wp_kses_post( $html );
	}

	/**
	 * Outputs the lesson tags.
	 *
	 * @param int $lesson_id Lesson ID.
	 */
	public function lesson_tags_display( $lesson_id = 0 ) {
		if ( $lesson_id ) {
			$tags = wp_get_post_terms( $lesson_id, 'lesson-tag' );
			if ( $tags && count( $tags ) > 0 ) {
				$tag_list = '';
				foreach ( $tags as $tag ) {
					$tag_link = get_term_link( $tag, 'lesson-tag' );
					if ( ! is_wp_error( $tag_link ) ) {
						if ( $tag_list ) {
							$tag_list .= ', '; }
						$tag_list .= '<a href="' . esc_url( $tag_link ) . '">' . esc_html( $tag->name ) . '</a>';
					}
				}
				if ( $tag_list ) {
					?>
					<section class="lesson-tags">
						<?php
							// translators: Placeholder is a comma-separated list of links to the tags.
							printf( esc_html__( 'Lesson tags: %1$s', 'sensei-lms' ), wp_kses_post( $tag_list ) );
						?>
					</section>
					<?php
				}
			}
		}
	}

	/**
	 * Filters the query variable object to only return lessons.
	 *
	 * @param WP_Query $query WP_Query instance.
	 */
	public function lesson_tag_archive_filter( $query ) {
		if ( $query->is_main_query() && is_tax( 'lesson-tag' ) ) {
			// Limit to lessons only.
			$query->set( 'post_type', 'lesson' );

			// Set order of lessons.
			$query->set( 'orderby', 'menu_order' );
			$query->set( 'order', 'ASC' );

		}
	}

	/**
	 * Gets the lesson tag archive title.
	 *
	 * @param string $title Lesson tag title.
	 * @return string Lesson tag title.
	 */
	public function lesson_tag_archive_header( $title ) {
		if ( is_tax( 'lesson-tag' ) ) {
			// translators: Placeholder is the filtered tag name.
			$title = sprintf( __( 'Lesson tag: %1$s', 'sensei-lms' ), apply_filters( 'sensei_lesson_tag_archive_title', get_queried_object()->name ) );
		}
		return $title;
	}

	/**
	 * Outputs the lesson tag archive description.
	 */
	public function lesson_tag_archive_description() {
		if ( is_tax( 'lesson-tag' ) ) {
			$tag = get_queried_object();
			echo '<p class="archive-description lesson-description">' . wp_kses_post( apply_filters( 'sensei_lesson_tag_archive_description', nl2br( $tag->description ), $tag->term_id ) ) . '</p>';
		}
	}

	/**
	 * Marks a lesson as complete.
	 */
	public function sensei_complete_lesson() {
		global $post, $current_user;

		if ( ! isset( $_POST['quiz_action'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['woothemes_sensei_complete_lesson_noonce'], 'woothemes_sensei_complete_lesson_noonce' ) ) {
			return;
		}

		$lesson_id = $post->ID;

		if ( 0 >= $lesson_id ) {
			return;
		}

		// Handle Quiz Completion.
		$sanitized_submit = esc_html( $_POST['quiz_action'] );

		switch ( $sanitized_submit ) {
			case 'lesson-complete':
				Sensei_Utils::sensei_start_lesson( $lesson_id, $current_user->ID, true );
				$this->maybe_redirect_to_next_lesson( $lesson_id );

				break;

			case 'lesson-reset':
				Sensei_Utils::sensei_remove_user_from_lesson( $lesson_id, $current_user->ID );

				$this->messages = '<div class="sensei-message note">' . __( 'Lesson Reset Successfully.', 'sensei-lms' ) . '</div>';
				break;

			default:
				// Nothing.
				break;

		}

	}

	/**
	 * Redirect to the next lesson, if applicable.
	 *
	 * @since 1.12.0
	 *
	 * @param int $lesson_id Lesson ID.
	 */
	private function maybe_redirect_to_next_lesson( $lesson_id = 0 ) {
		if ( 0 >= $lesson_id ) {
			return;
		}

		$nav_links    = sensei_get_prev_next_lessons( $lesson_id );
		$redirect_url = false;

		if ( isset( $nav_links['next'] ) ) {
			$redirect_url = $nav_links['next']['url'];
		}

		/**
		 * Filter the URL that students are redirected to after completing a lesson.
		 *
		 * @since 1.12.0
		 *
		 * @param string|bool $redirect_url URL to redirect students to after completing a lesson. False to skip redirect.
		 * @param int         $lesson_id    Current lesson ID.
		 * @param array       $nav_links    Navigation links found for the current lesson.
		 */
		$redirect_url = apply_filters( 'sensei_complete_lesson_redirect_url', $redirect_url, $lesson_id, $nav_links );

		if ( $redirect_url ) {
			wp_safe_redirect( esc_url_raw( $redirect_url ) );
			exit;
		}
	}

	/**
	 * Redirect to the course completed page, if applicable.
	 *
	 * @since 3.13.0
	 * @access private
	 *
	 * @param string $status    Course status.
	 * @param int    $user_id   The user ID (unused).
	 * @param int    $course_id The course ID.
	 */
	public function redirect_to_course_completed_page( $status, $user_id, $course_id ) {
		if ( 'complete' !== $status || ! $course_id ) {
			return;
		}

		$url = Sensei_Course::get_course_completed_page_url( $course_id );

		if ( $url ) {
			wp_safe_redirect( esc_url_raw( $url ) );
			exit;
		}
	}

	/**
	 * Marks a course as complete.
	 */
	public function sensei_complete_course() {
		global $current_user;

		if ( isset( $_POST['course_complete'] ) && wp_verify_nonce( $_POST['woothemes_sensei_complete_course_noonce'], 'woothemes_sensei_complete_course_noonce' ) ) {

			$sanitized_submit    = esc_html( $_POST['course_complete'] );
			$sanitized_course_id = absint( esc_html( $_POST['course_complete_id'] ) );
			// Handle submit data.
			switch ( $sanitized_submit ) {
				case __( 'Mark as Complete', 'sensei-lms' ):
					// Add user to course.
					$course_metadata = array(
						'start'    => current_time( 'mysql' ),
						'percent'  => 0, // No completed lessons yet.
						'complete' => 0,
					);
					$activity_logged = Sensei_Utils::update_course_status( $current_user->ID, $sanitized_course_id, 'in-progress', $course_metadata );

					if ( $activity_logged ) {
						// Get all course lessons.
						$course_lesson_ids = Sensei()->course->course_lessons( $sanitized_course_id, 'any', 'ids' );
						// Mark all quiz user meta lessons as complete.
						foreach ( $course_lesson_ids as $lesson_item_id ) {
							// Mark lesson as complete.
							$activity_logged = Sensei_Utils::sensei_start_lesson( $lesson_item_id, $current_user->ID, true );
						}

						// Update with final stats.
						$course_metadata = array(
							'percent'  => 100,
							'complete' => count( $course_lesson_ids ),
						);
						$activity_logged = Sensei_Utils::update_course_status( $current_user->ID, $sanitized_course_id, 'complete', $course_metadata );

						do_action( 'sensei_user_course_end', $current_user->ID, $sanitized_course_id );

						// Success message.
						$this->messages = '<header class="archive-header"><div class="sensei-message tick">'
							// translators: Placeholder is the Course title.
							. sprintf( __( '%1$s marked as complete.', 'sensei-lms' ), get_the_title( $sanitized_course_id ) )
							. '</div></header>';
					}

					break;

				/**
				 * Handle the Delete Course button. This is deprecated and will
				 * be removed.
				 *
				 * @deprecated 2.0.0
				 */
				case __( 'Delete Course', 'sensei-lms' ):
					_doing_it_wrong(
						'Sensei_Frontend::sensei_complete_course',
						'Handling for "Delete Course" button will be removed in version 4.0.',
						'2.0.0'
					);
					Sensei_Utils::sensei_remove_user_from_course( $sanitized_course_id, $current_user->ID );

					// Success message.
					$this->messages = '<header class="archive-header"><div class="sensei-message tick">'
						// translators: Placeholder is the Course title.
						. sprintf( __( '%1$s deleted.', 'sensei-lms' ), get_the_title( $sanitized_course_id ) )
						. '</div></header>';
					break;

				default:
					// Nothing.
					break;
			}
		}
	}

	/**
	 * Gets the quiz answers for the current user.
	 *
	 * @deprecated 3.10.0 use Sensei_Quiz::get_user_answers
	 * @param int $lesson_id Lesson ID.
	 * @return array Quiz answers for the current user.
	 */
	public function sensei_get_user_quiz_answers( $lesson_id = 0 ) {
		global $current_user;

		_deprecated_function( __METHOD__, '3.10.0', 'Sensei_Quiz::get_user_answers' );

		$user_answers = array();

		if ( 0 < intval( $lesson_id ) ) {
			$lesson_quiz_questions = Sensei()->lesson->lesson_quiz_questions( $lesson_id );
			foreach ( $lesson_quiz_questions as $question ) {
				$answer                        = maybe_unserialize(
					base64_decode(
						Sensei_Utils::sensei_get_activity_value(
							array(
								'post_id' => $question->ID,
								'user_id' => $current_user->ID,
								'type'    => 'sensei_user_answer',
								'field'   => 'comment_content',
							)
						)
					)
				);
				$user_answers[ $question->ID ] = $answer;
			}
		}

		return $user_answers;
	}

	/**
	 * Outputs all notices.
	 */
	public function sensei_frontend_messages() {
		Sensei()->notices->maybe_print_notices();
	}

	/**
	 * Outputs the video for a lesson.
	 *
	 * @param int $post_id Optional. Lesson ID. Default 0.
	 */
	public function sensei_lesson_video( $post_id = 0 ) {
		if ( 0 < intval( $post_id ) && sensei_can_user_view_lesson( $post_id ) ) {
			$lesson_video_embed = get_post_meta( $post_id, '_lesson_video_embed', true );
			if ( 'http' == substr( $lesson_video_embed, 0, 4 ) ) {
				// V2 - make width and height a setting for video embed.
				$lesson_video_embed = wp_oembed_get( esc_url( $lesson_video_embed ) );
			}

			$lesson_video_embed = do_shortcode( html_entity_decode( $lesson_video_embed ) );
			$lesson_video_embed = Sensei_Wp_Kses::maybe_sanitize( $lesson_video_embed, $this->allowed_html );

			if ( '' != $lesson_video_embed ) {
				?>
				<div class="video"><?php echo wp_kses( $lesson_video_embed, $this->allowed_html ); ?></div>
				<?php
			}
		}
	}

	/**
	 * Outputs the "Complete Lesson" button.
	 */
	public function sensei_complete_lesson_button() {
		global  $post;

		$lesson_id = $post->ID;

		// make sure user is taking course.
		$course_id = Sensei()->lesson->get_course_id( $lesson_id );

		if ( ! Sensei_Course::is_user_enrolled( $course_id ) ) {
			return;
		}

		if ( false === Sensei()->lesson->lesson_has_quiz_with_questions_and_pass_required( $lesson_id ) ) {

			wp_enqueue_script( 'sensei-stop-double-submission' );

			?>
			<form class="lesson_button_form" method="POST" action="<?php echo esc_url( get_permalink() ); ?>">
				<input type="hidden"
					   name="woothemes_sensei_complete_lesson_noonce"
					   id="woothemes_sensei_complete_lesson_noonce"
					   value="<?php echo esc_attr( wp_create_nonce( 'woothemes_sensei_complete_lesson_noonce' ) ); ?>"
				/>

				<input type="hidden" name="quiz_action" value="lesson-complete" />

				<input type="submit"
					   name="quiz_complete"
					   class="quiz-submit complete sensei-stop-double-submission"
					   value="<?php esc_attr_e( 'Complete Lesson', 'sensei-lms' ); ?>"/>

			</form>
			<?php
		}
	}

	/**
	 * Outputs the "Reset Lesson" button.
	 */
	public function sensei_reset_lesson_button() {
		global  $post;

		// Lesson quizzes.
		$quiz_id       = Sensei()->lesson->lesson_quizzes( $post->ID );
		$reset_allowed = true;
		if ( $quiz_id ) {
			// Get quiz pass setting.
			$reset_allowed = get_post_meta( $quiz_id, '_enable_quiz_reset', true );
		}
		if ( ! $quiz_id || ! empty( $reset_allowed ) ) {
			wp_enqueue_script( 'sensei-stop-double-submission' );

			?>
		<form method="POST" action="<?php echo esc_url( get_permalink() ); ?>">

			<input
			type="hidden"
			name="<?php echo esc_attr( 'woothemes_sensei_complete_lesson_noonce' ); ?>"
			id="<?php echo esc_attr( 'woothemes_sensei_complete_lesson_noonce' ); ?>"
			value="<?php echo esc_attr( wp_create_nonce( 'woothemes_sensei_complete_lesson_noonce' ) ); ?>" />

			<input type="hidden" name="quiz_action" value="lesson-reset" />

			<input type="submit" name="quiz_complete" class="quiz-submit reset sensei-stop-double-submission" value="<?php esc_attr_e( 'Reset Lesson', 'sensei-lms' ); ?>"/>

		</form>
			<?php
		}
	}

	/**
	 * Outputs the quiz buttons and messages.
	 *
	 * @deprecated since 1.9.0 use Sensei_Lesson::footer_quiz_call_to_action()
	 */
	public function sensei_lesson_quiz_meta() {

		// To be removed in 5.0.0.
		_deprecated_function( __METHOD__, '1.9.0', 'Sensei_Lesson::footer_quiz_call_to_action' );

		Sensei_Lesson::footer_quiz_call_to_action();

	}

	public function sensei_course_archive_meta() {
		// Meta data.
		$post_id           = get_the_ID();
		$category_output   = get_the_term_list( $post_id, 'course-category', '', ', ', '' );
		$free_lesson_count = intval( Sensei()->course->course_lesson_preview_count( $post_id ) );
		$lesson_count      = Sensei()->course->course_lesson_count( $post_id );
		?>
		<section class="entry">
			<p class="sensei-course-meta">
				<?php
				/**
				 * Fires before course meta is displayed.
				 *
				 * @since 2.0.0
				 *
				 * @params int $course_id Course post ID.
				 */
				do_action( 'sensei_course_meta_inside_before', $post_id );

				if ( isset( Sensei()->settings->settings['course_author'] ) && ( Sensei()->settings->settings['course_author'] ) ) {
					?>
					<span class="course-author"><?php esc_html_e( 'by', 'sensei-lms' ); ?><?php the_author_link(); ?></span>
					<?php
				}
				?>
				<span class="course-lesson-count">
					<?php
					// translators: Placeholder %d is the lesson count.
					echo esc_html( sprintf( _n( '%d Lesson', '%d Lessons', $lesson_count, 'sensei-lms' ), $lesson_count ) );
					?>
				</span>
			<?php
			if ( ! empty( $category_output ) ) {
				?>
				<span class="course-category">
					<?php
					// translators: Placeholder is a comma-separated list of the Course categories.
					echo sprintf( esc_html__( 'in %s', 'sensei-lms' ), wp_kses_post( $category_output ) );
					?>
				</span>
				<?php
			}

			/**
			 * Fires after course meta is displayed.
			 *
			 * @since 2.0.0
			 *
			 * @params int $course_id Course post ID.
			 */
			do_action( 'sensei_course_meta_inside_after', $post_id );
			?>
			</p>
			<p class="course-excerpt"><?php the_excerpt(); ?></p>
			<?php
			if ( 0 < $free_lesson_count ) {
				// translators: Placeholder is the number of free lessons in the course.
				$free_lessons = sprintf( __( 'You can access %d of this course\'s lessons for free', 'sensei-lms' ), $free_lesson_count );
				?>
				<p class="sensei-free-lessons"><a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php esc_html_e( 'Preview this course', 'sensei-lms' ); ?></a> - <?php echo esc_html( $free_lessons ); ?></p>
			<?php } ?>
		</section>
		<?php
	}

	public function sensei_course_category_main_content() {
		global $post;
		if ( have_posts() ) {
			?>

			<section id="main-course" class="course-container">

				<?php sensei_do_deprecated_action( 'sensei_course_archive_header', '3.0.0', 'sensei_course_content_inside_before' ); ?>

				<?php
				while ( have_posts() ) {
					the_post();
					?>

					<article class="<?php echo esc_attr( join( ' ', get_post_class( array( 'course', 'post' ), get_the_ID() ) ) ); ?>">

						<?php do_action( 'sensei_course_archive_meta' ); ?>

					</article>

				<?php } ?>

			</section>

		<?php } else { ?>

			<p>

				<?php esc_html_e( 'No courses found that match your selection.', 'sensei-lms' ); ?>

			</p>

			<?php
		}

	}

	public function sensei_login_form() {
		/*
		 * It is safe to ignore nonce verification below because we are just
		 * using the POST data to display values in the form, not to change any
		 * data on the server.
		 */

		?>
		<div id="my-courses">
			<?php Sensei()->notices->maybe_print_notices(); ?>
			<div class="col2-set" id="customer_login">

				<div class="col-1">
					<?php
					// output the actual form markup.
					Sensei_Templates::get_template( 'user/login-form.php' );
					?>
				</div>

			<?php
			if ( get_option( 'users_can_register' ) ) {

				// get current url.
				$action_url = get_permalink();

				?>

				<div class="col-2">
					<h2><?php esc_html_e( 'Register', 'sensei-lms' ); ?></h2>

					<form method="post" class="register"  action="<?php echo esc_url( $action_url ); ?>" >

						<?php do_action( 'sensei_register_form_start' ); ?>

						<p class="form-row form-row-wide">
							<label for="sensei_reg_username"><?php esc_html_e( 'Username', 'sensei-lms' ); ?> <span class="required">*</span></label>
							<input type="text" class="input-text" name="sensei_reg_username" id="sensei_reg_username" value="<?php echo ( ! empty( $_POST['sensei_reg_username'] ) ) ? esc_attr( $_POST['sensei_reg_username'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification ?>" />
						</p>

						<p class="form-row form-row-wide">
							<label for="sensei_reg_email"><?php esc_html_e( 'Email address', 'sensei-lms' ); ?> <span class="required">*</span></label>
							<input type="email" class="input-text" name="sensei_reg_email" id="sensei_reg_email" value="<?php echo ( ! empty( $_POST['sensei_reg_email'] ) ) ? esc_attr( $_POST['sensei_reg_email'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification ?>" />
						</p>

						<p class="form-row form-row-wide">
							<label for="sensei_reg_password"><?php esc_html_e( 'Password', 'sensei-lms' ); ?> <span class="required">*</span></label>
							<input type="password" class="input-text" name="sensei_reg_password" id="sensei_reg_password" value="<?php echo ( ! empty( $_POST['sensei_reg_password'] ) ) ? esc_attr( $_POST['sensei_reg_password'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification ?>" />
						</p>

						<!-- Spam Trap -->
						<div style="left:-999em; position:absolute;"><label for="trap"><?php esc_html_e( 'Anti-spam', 'sensei-lms' ); ?></label><input type="text" name="email_2" id="trap" tabindex="-1" /></div>

						<?php do_action( 'sensei_register_form_fields' ); ?>
						<?php do_action( 'register_form' ); ?>

						<?php wp_nonce_field( 'sensei-register' ); ?>

						<p class="form-row">
							<input type="submit" class="button" name="register" value="<?php esc_attr_e( 'Register', 'sensei-lms' ); ?>" />
						</p>

						<?php do_action( 'sensei_register_form_end' ); ?>

					</form>
				</div>
				<?php
			}
			?>
			</div>
		</div>

		<?php
	}

	public function sensei_lesson_meta( $post_id = 0 ) {
		if ( 0 < intval( $post_id ) ) {
			$lesson_course_id = absint( get_post_meta( $post_id, '_lesson_course', true ) );
			?>
		<section class="entry">
			<p class="sensei-course-meta">
				<?php if ( isset( Sensei()->settings->settings['lesson_author'] ) && ( Sensei()->settings->settings['lesson_author'] ) ) { ?>
				<span class="course-author"><?php esc_html_e( 'by', 'sensei-lms' ); ?><?php the_author_link(); ?></span>
				<?php } ?>
				<?php if ( 0 < intval( $lesson_course_id ) ) { ?>
				<span class="lesson-course">
					<?php
					echo '&nbsp;' . wp_kses_post(
						sprintf(
							// translators: Placeholder is a link to the Course permalink.
							__( 'Part of: %s', 'sensei-lms' ),
							'<a href="' . esc_url( get_permalink( $lesson_course_id ) ) . '" title="' . esc_attr__( 'View course', 'sensei-lms' ) . '"><em>' . esc_html( get_the_title( $lesson_course_id ) ) . '</em></a>'
						)
					);
					?>
				</span>
				<?php } ?>
			</p>
			<p class="lesson-excerpt"><?php the_excerpt(); ?></p>
		</section>
			<?php
		}
	} // sensei_lesson_meta()

	public function sensei_lesson_preview_title_text( $course_id ) {
		$preview_text = __( 'Preview', 'sensei-lms' );

		/**
		 * The lesson preview indicator text. Defaults to "Preview".
		 *
		 * @since 1.11.0
		 *
		 * @param string $preview_text
		 * @param int    $course_id
		 */
		return apply_filters( 'sensei_lesson_preview_title_text', $preview_text, $course_id );
	}

	public function sensei_lesson_preview_title_tag( $course_id ) {
		return '<span class="preview-label">'
			. $this->sensei_lesson_preview_title_text( $course_id )
			. '</span>';
	}

	public function sensei_lesson_preview_title( $title = '', $id = 0 ) {
		global $post;

		// Limit to lessons and check if lesson ID matches filtered post ID.
		// @see https://github.com/woothemes/sensei/issues/574.
		if ( isset( $post->ID ) && $id == $post->ID && 'lesson' == get_post_type( $post ) ) {

			// Limit to main query only.
			if ( is_main_query() ) {

				// Get the course ID.
				$course_id = get_post_meta( $post->ID, '_lesson_course', true );

				// Check if the user is taking the course.
				if ( is_singular( 'lesson' ) && Sensei_Utils::is_preview_lesson( $post->ID ) && ! Sensei_Course::is_user_enrolled( $course_id ) && $post->ID == $id ) {
					$title .= ' ' . $this->sensei_lesson_preview_title_tag( $course_id );
				}
			}
		}
		return $title;
	} // sensei_lesson_preview_title

	public function sensei_course_start() {
		global $post, $current_user;

		// Handle user starting the course.
		if (
			is_singular( 'course' )
			&& isset( $_POST['course_start'] )
			&& wp_verify_nonce( $_POST['woothemes_sensei_start_course_noonce'], 'woothemes_sensei_start_course_noonce' )
			&& Sensei_Course::can_current_user_manually_enrol( $post->ID )
		) {

			/**
			 * Lets providers give their own course sign-up handler.
			 *
			 * @since 3.0.0
			 *
			 * @param callable $handler {
			 *     Frontend enrolment handler. Returns `true` if successful; `false` if not.
			 *
			 *     @type int $user_id   User ID.
			 *     @type int $course_id Course post ID.
			 * }
			 * @param int      $user_id          User ID.
			 * @param int      $course_id        Course post ID.
			 */
			$learner_enrollment_handler = apply_filters( 'sensei_frontend_learner_enrolment_handler', [ $this, 'manually_enrol_learner' ], $current_user->ID, $post->ID );

			$student_enrolled = false;
			if ( is_callable( $learner_enrollment_handler ) ) {
				$student_enrolled = call_user_func( $learner_enrollment_handler, $current_user->ID, $post->ID );
			}

			$this->data                        = new stdClass();
			$this->data->is_user_taking_course = false;
			if ( $student_enrolled ) {
				$this->data->is_user_taking_course = true;

				// Refresh page to avoid re-posting.
				/**
				 * Filter the URL that students are redirected to after starting a course.
				 *
				 * @since 1.10.0
				 *
				 * @param string|bool  $redirect_url URL to redirect students to after starting course. Return `false` to prevent redirect.
				 * @param WP_Post      $post         Post object for course.
				 */
				$redirect_url = apply_filters( 'sensei_start_course_redirect_url', get_permalink( $post->ID ), $post );

				if ( false !== $redirect_url ) {
					?>

					<script type="text/javascript"> window.location = '<?php echo esc_url( $redirect_url ); ?>'; </script>

					<?php
				}
			}
		}
	}

	/**
	 * Handle the frontend manual enrollment of a learner.
	 *
	 * @since 3.0.0
	 * @access private
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 *
	 * @return bool True if successful.
	 */
	public function manually_enrol_learner( $user_id, $course_id ) {
		$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();
		$manual_enrolment  = $enrolment_manager->get_manual_enrolment_provider();

		return $manual_enrolment && $manual_enrolment->enrol_learner( $user_id, $course_id );
	}

	/**
	 * This function shows the WooCommerce cart notice if the user has
	 * added the current course to cart. It does not show if the user is already taking
	 * the course.
	 *
	 * @since 1.0.2
	 * @deprecated 2.0.0 Replaced with WCPC plugin's Sensei_WC::course_in_cart_message method.
	 *
	 * @return void
	 */
	public function sensei_woocommerce_in_cart_message() {
		_deprecated_function( __METHOD__, '2.0.0', 'Sensei_WC::course_in_cart_message' );

		if ( ! method_exists( 'Sensei_WC', 'course_in_cart_message' ) ) {
			return;
		}

		Sensei_WC::course_in_cart_message();
	}

	// Deprecated.
	public function sensei_lesson_comment_count( $count ) {
		return $count;
	}

	/**
	 * Only show excerpts for lessons and courses in search results.
	 *
	 * @param  string $content Original content.
	 * @return string          Modified content.
	 */
	public function sensei_search_results_excerpt( $content ) {
		global $post;

		if ( is_search() && in_array( $post->post_type, array( 'course', 'lesson' ) ) ) {

			// Prevent infinite loop, because wp_trim_excerpt calls the_content filter again.
			remove_filter( 'the_content', array( $this, 'sensei_search_results_excerpt' ) );

			// Don't echo the excerpt.
			$content = '<p class="course-excerpt">' . get_the_excerpt() . '</p>';
		}

		return $content;
	}

	/**
	 * Remove active course when an order is refunded or cancelled.
	 *
	 * @deprecated 2.0.0 Moved to WCPC plugin. Use \Sensei_WC_Paid_Courses\Courses::remove_active_course
	 *
	 * @param  integer $order_id ID of order.
	 * @return void
	 */
	public function remove_active_course( $order_id ) {
		_deprecated_function( __METHOD__, '2.0.0', '\Sensei_WC_Paid_Courses\Courses::remove_active_course' );

		if ( ! method_exists( 'Sensei_WC_Paid_Courses\Courses', 'remove_active_course' ) ) {
			return;
		}

		\Sensei_WC_Paid_Courses\Courses::remove_active_course( $order_id );
	}

	/**
	 * Activate all purchased courses for user.
	 *
	 * @deprecated 2.0.0 Use `\Sensei_WC_Paid_Courses\Courses::activate_purchased_courses()` if it exists.
	 * @since  1.4.8
	 * @param  integer $user_id User ID.
	 * @return void
	 */
	public function activate_purchased_courses( $user_id = 0 ) {
		_deprecated_function( __METHOD__, '2.0.0', '\Sensei_WC_Paid_Courses\Courses::activate_purchased_courses' );

		if ( ! method_exists( '\Sensei_WC_Paid_Courses\Courses', 'activate_purchased_courses' ) ) {
			return;
		}

		\Sensei_WC_Paid_Courses\Courses::instance()->activate_purchased_courses( $user_id );
	}

	/**
	 * Activate single course if already purchases.
	 *
	 * @deprecated 2.0.0 Use `\Sensei_WC_Paid_Courses\Courses::activate_purchased_single_course()` if it exists.
	 * @return void
	 */
	public function activate_purchased_single_course() {
		_deprecated_function( __METHOD__, '2.0.0', '\Sensei_WC_Paid_Courses\Courses::activate_purchased_single_course' );

		if ( ! method_exists( '\Sensei_WC_Paid_Courses\Courses', 'activate_purchased_single_course' ) ) {
			return;
		}

		\Sensei_WC_Paid_Courses\Courses::instance()->activate_purchased_single_course();
	}

	/**
	 * Hide Sensei activity comments from frontend (requires WordPress 4.0+).
	 *
	 * @param  array $args Default arguments.
	 * @return array        Modified arguments.
	 */
	public function hide_sensei_activity( $args = array() ) {

		if ( is_singular( 'lesson' ) || is_singular( 'course' ) ) {
			$args['type'] = 'comment';
		}

		return $args;
	}

	/**
	 * Redirect failed login attempts to the front end login page
	 * in the case where the login fields are not left empty.
	 *
	 * @return void redirect
	 */
	function sensei_login_fail_redirect() {

		// if not posted from the sensei login form let
		// WordPress or any other party handle the failed request.
		if ( ! isset( $_REQUEST['form'] ) || 'sensei-login' != $_REQUEST['form'] ) {

			return;

		}

		// Get the reffering page, where did the post submission come from?
		$referrer = add_query_arg( 'login', false, $_SERVER['HTTP_REFERER'] );

		 // if there's a valid referrer, and it's not the default log-in screen.
		if ( ! empty( $referrer ) && ! strstr( $referrer, 'wp-login' ) && ! strstr( $referrer, 'wp-admin' ) ) {
			// let's append some information (login=failed) to the URL for the theme to use.
			wp_redirect( esc_url_raw( add_query_arg( 'login', 'failed', $referrer ) ) );
			exit;
		}
	}

	/**
	 * Handle the login reques from all sensei intiated login forms.
	 *
	 * @return void redirect
	 */
	function sensei_handle_login_request() {

		// Check that it is a sensei login request and if it has a valid nonce.
		if ( isset( $_REQUEST['form'] ) && 'sensei-login' == $_REQUEST['form'] ) {

			// Validate the login request nonce.
			if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'sensei-login' ) ) {
				return;
			}

			// get the page where the sensei log form is located.
			$referrer = $_REQUEST['_wp_http_referer'];

			if ( ( isset( $_REQUEST['log'] ) && ! empty( $_REQUEST['log'] ) )
				 && ( isset( $_REQUEST['pwd'] ) && ! empty( $_REQUEST['pwd'] ) ) ) {

				// when the user has entered a password or username do the sensei login.
				$creds = array();

				// check if the requests login is an email address.
				if ( is_email( trim( $_REQUEST['log'] ) ) ) {
					$login = sanitize_email( $_REQUEST['log'] );

					// Occasionally a user's username IS an email,
					// but they have changed their actual email, so check for this case.
					$user = get_user_by( 'login', $login );

					if ( ! $user ) {
						// Ok, fallback to checking by email.
						$user = get_user_by( 'email', $login );
					}

					// validate the user object.
					if ( ! $user ) {

						// the email doesnt exist.
						wp_redirect( esc_url_raw( add_query_arg( 'login', 'failed', $referrer ) ) );
						exit;

					}

					// assigne the username to the creds array for further processing.
					$creds['user_login'] = $user->user_login;

				} else {

					// process this as a default username login.
					$creds['user_login'] = sanitize_text_field( $_REQUEST['log'] );

				}

				// get setup the rest of the creds array.
				$creds['user_password'] = $_REQUEST['pwd'];
				$creds['remember']      = isset( $_REQUEST['rememberme'] ) ? true : false;

				// attempt logging in with the given details.
				$secure_cookie = is_ssl() ? true : false;
				$user          = wp_signon( $creds, $secure_cookie );

				if ( is_wp_error( $user ) ) { // on login failure.
					wp_redirect( esc_url_raw( add_query_arg( 'login', 'failed', $referrer ) ) );
					exit;
				} else { // on login success.

					/**
					* Change the redirect url programatically.
					*
					* @since 1.6.1
					*
					* @param string $referrer the page where the current url wheresensei login form was posted from.
					*/

					$success_redirect_url = apply_filters( 'sesei_login_success_redirect_url', remove_query_arg( 'login', $referrer ) );

					wp_redirect( esc_url_raw( $success_redirect_url ) );
					exit;

				}
			} else { // if username or password is empty.

				wp_redirect( esc_url_raw( add_query_arg( 'login', 'emptyfields', $referrer ) ) );
				exit;

			}
		} elseif ( ( isset( $_GET['login'] ) ) ) {
			// else if this request is a redircect from a previously faile login request.
			$this->login_message_process();

			// exit the handle login request function.
			return;
		}

		// if none of the above.
		return;

	}

	/**
	 * Handles Sensei specific registration requests.
	 *
	 * @return void redirect
	 */
	public function sensei_process_registration() {
		global   $current_user;
		// check the for the sensei specific registration requests.
		// phpcs:ignore WordPress.Security.NonceVerification -- We are just checking whether to return here.
		if ( ! isset( $_POST['sensei_reg_username'] ) && ! isset( $_POST['sensei_reg_email'] ) && ! isset( $_POST['sensei_reg_password'] ) ) {
			// exit if this is not a sensei registration request.
			return;
		}

		// Validate the registration request nonce.
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'sensei-register' ) ) {
			return;
		}

		// check for spam throw cheating huh.
		if ( isset( $_POST['email_2'] ) && '' !== $_POST['email_2'] ) {
			$message = 'Error:  The spam field should be empty';
			Sensei()->notices->add_notice( $message, 'alert' );
			return;
		}

		// retreive form variables.
		$new_user_name     = sanitize_user( $_POST['sensei_reg_username'] );
		$new_user_email    = $_POST['sensei_reg_email'];
		$new_user_password = $_POST['sensei_reg_password'];

		// Check the username.
		$username_error_notice = '';
		if ( $new_user_name == '' ) {
			$username_error_notice = __( '<strong>ERROR</strong>: Please enter a username.', 'sensei-lms' );
		} elseif ( ! validate_username( $new_user_name ) ) {
			$username_error_notice = __( '<strong>ERROR</strong>: This username is invalid because it uses illegal characters. Please enter a valid username.', 'sensei-lms' );
		} elseif ( username_exists( $new_user_name ) ) {
			$username_error_notice = __( '<strong>ERROR</strong>: This username is already registered. Please choose another one.', 'sensei-lms' );
		}

		// exit on username error.
		if ( '' !== $username_error_notice ) {
			Sensei()->notices->add_notice( $username_error_notice, 'alert' );
			return;
		}

		// Check the e-mail address.
		$email_error_notice = '';
		if ( $new_user_email == '' ) {
			$email_error_notice = __( '<strong>ERROR</strong>: Please enter an email address.', 'sensei-lms' );
		} elseif ( ! is_email( $new_user_email ) ) {
			$email_error_notice = __( '<strong>ERROR</strong>: The email address isn&#8217;t correct.', 'sensei-lms' );
		} elseif ( email_exists( $new_user_email ) ) {
			$email_error_notice = __( '<strong>ERROR</strong>: This email is already registered, please choose another one.', 'sensei-lms' );
		}

		// exit on email address error.
		if ( '' !== $email_error_notice ) {
			Sensei()->notices->add_notice( $email_error_notice, 'alert' );
			return;
		}

		// check user password
		// exit on email address error.
		if ( empty( $new_user_password ) ) {
			Sensei()->notices->add_notice( __( '<strong>ERROR</strong>: The password field is empty.', 'sensei-lms' ), 'alert' );
			return;
		}

		// register user.
		$user_id = wp_create_user( $new_user_name, $new_user_password, $new_user_email );
		if ( ! $user_id || is_wp_error( $user_id ) ) {
			// translators: Placeholder is the admin email address.
			Sensei()->notices->add_notice( sprintf( __( '<strong>ERROR</strong>: Couldn&#8217;t register you&hellip; please contact the <a href="mailto:%s">webmaster</a> !', 'sensei-lms' ), get_option( 'admin_email' ) ), 'alert' );
		}

		// Notify the Admin and not the user. See https://github.com/Automattic/sensei/issues/1761.
		global $wp_version;
		if ( version_compare( $wp_version, '4.3.1', '>=' ) ) {
			wp_new_user_notification( $user_id, null, 'admin' );
		} else {
			wp_new_user_notification( $user_id, $new_user_password );
		}

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Log in recently registered user.
		$current_user = get_user_by( 'id', $user_id );
		wp_set_auth_cookie( $user_id, true );

		// Redirect.
		global $wp;
		if ( wp_get_referer() ) {
			$redirect = esc_url( wp_get_referer() );
		} else {
			$redirect = esc_url( home_url( $wp->request ) );
		}

		wp_redirect( apply_filters( 'sensei_registration_redirect', $redirect ) );
		exit;

	}

	/**
	 * Displays an appropriate message for a failed login attempt.
	 *
	 * @return void redirect
	 * @since 1.7.0
	 */
	public function login_message_process() {

			// setup the message variables.
			$message = '';

			// only output message if the url contains login=failed and login=emptyfields.
		if ( $_GET['login'] == 'failed' ) {

			$message = __( 'Incorrect login details', 'sensei-lms' );

		} elseif ( $_GET['login'] == 'emptyfields' ) {

			$message = __( 'Please enter your username and password', 'sensei-lms' );
		}

			Sensei()->notices->add_notice( $message, 'alert' );

	}

}

/**
 * Class WooThemes_Sensei_Frontend
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Frontend extends Sensei_Frontend{}
