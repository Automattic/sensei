<?php

/**
 * Sensei_Legacy_Shortcodes class
 *
 * All functionality pertaining the the shortcodes before
 * version 1.9
 *
 * @package Content
 * @subpackage Shortcode
 * @author Automattic
 *
 * @since       1.6.0
 */
class Sensei_Legacy_Shortcodes {

	const DOCS_SHORTCODE_URL = 'https://senseilms.com/documentation/shortcodes/';

	/**
	 * Add the legacy shortcodes to WordPress
	 *
	 * @since 1.9.0
	 */
	public static function init() {

		add_shortcode( 'allcourses', array( __CLASS__, 'all_courses' ) );
		add_shortcode( 'newcourses', array( __CLASS__, 'new_courses' ) );
		add_shortcode( 'featuredcourses', array( __CLASS__, 'featured_courses' ) );
		add_shortcode( 'freecourses', array( __CLASS__, 'free_courses' ) );
		add_shortcode( 'paidcourses', array( __CLASS__, 'paid_courses' ) );
		add_shortcode( 'usercourses', array( __CLASS__, 'user_courses' ) );

	}

	/**
	 * Call `_doing_it_wrong()` for a deprecated shortcode.
	 *
	 * @param string $shortcode Shortcode that was deprecated.
	 */
	private static function throw_deprecation_warning( $shortcode ) {
		$permalink = get_permalink();

		// translators: %s is the name of the shortcode.
		$caller  = sprintf( __( 'Shortcode `[%s]`', 'sensei-lms' ), $shortcode );
		$message = sprintf(
			// translators: %1$s is the name of the shortcode; %2$s is page URL with shortcode; %3$s is URL for shortcode documentation.
			__(
				'The shortcode `[%1$s]` (used on: %2$s) has been deprecated since Sensei v1.9.0. Check %3$s for alternatives.',
				'sensei-lms'
			),
			$shortcode,
			$permalink,
			self::DOCS_SHORTCODE_URL
		);

		_doing_it_wrong( esc_html( $caller ), esc_html( $message ), '2.0.0' );
	}

	/**
	 * Output message on frontend to warn those with the privileges on the site.
	 *
	 * @param string $shortcode Shortcode that was deprecated.
	 */
	private static function output_deprecation_notice( $shortcode ) {
		if ( ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		echo '<div class="sensei"><div class="sensei-message alert">';
		$message = sprintf(
			// translators: %1$s is the name of the shortcode; %2$s is the link to Sensei documentation.
			__(
				'The Sensei LMS shortcode <strong>[%1$s]</strong> has been deprecated and will soon be removed. Check <a href="%2$s" rel="noopener">Sensei LMS documentation</a> for alternatives. Only site editors will see this notice.',
				'sensei-lms'
			),
			$shortcode,
			self::DOCS_SHORTCODE_URL
		);
		echo wp_kses(
			$message,
			array(
				'a'      => array(
					'href' => array(),
					'rel'  => array(),
				),
				'strong' => array(),
			)
		);
		echo '</div></div>';
	}

	/**
	 * all_courses shortcode output function.
	 *
	 * The function should only be called indirectly through do_shortcode()
	 *
	 * @access public
	 * @param mixed $atts
	 * @param mixed $content (default: null)
	 * @return string
	 */
	public static function all_courses( $atts, $content = null ) {

		return self::generate_shortcode_courses( '', 'allcourses' ); // all courses but no title

	} // all_courses()

	/**
	 * paid_courses function.
	 *
	 * @access public
	 * @param mixed $atts
	 * @param mixed $content (default: null)
	 * @return string
	 */
	public static function paid_courses( $atts, $content = null ) {

		return self::generate_shortcode_courses( __( 'Paid Courses', 'sensei-lms' ), 'paidcourses' );

	}


	/**
	 * featured_courses function.
	 *
	 * @access public
	 * @param mixed $atts
	 * @param mixed $content (default: null)
	 * @return string
	 */
	public static function featured_courses( $atts, $content = null ) {

		return self::generate_shortcode_courses( __( 'Featured Courses', 'sensei-lms' ), 'featuredcourses' );

	}

	/**
	 * shortcode_free_courses function.
	 *
	 * @access public
	 * @param mixed $atts
	 * @param mixed $content (default: null)
	 * @return string
	 */
	public static function free_courses( $atts, $content = null ) {

		return self::generate_shortcode_courses( __( 'Free Courses', 'sensei-lms' ), 'freecourses' );

	}

	/**
	 * shortcode_new_courses function.
	 *
	 * @access public
	 * @param mixed $atts
	 * @param mixed $content (default: null)
	 * @return string
	 */
	public static function new_courses( $atts, $content = null ) {

		return self::generate_shortcode_courses( __( 'New Courses', 'sensei-lms' ), 'newcourses' );

	}

	/**
	 * Generate courses adding a title.
	 *
	 * @since 1.9.0
	 *
	 * @param $title
	 * @param $shortcode_specific_override
	 * @return string
	 */
	public static function generate_shortcode_courses( $title, $shortcode_specific_override ) {
		self::throw_deprecation_warning( $shortcode_specific_override, '1.9.0' );

		global  $shortcode_override, $posts_array;

		$shortcode_override = $shortcode_specific_override;

		// do not show this short code if there is a shortcode int he url and
		// this specific shortcode is not the one requested in the ur.
		$specific_shortcode_requested = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
		if ( ! empty( $specific_shortcode_requested ) &&
			$specific_shortcode_requested != $shortcode_override ) {

			return '';

		}

		// loop and get all courses html
		ob_start();

		self::output_deprecation_notice( $shortcode_specific_override );

		self::initialise_legacy_course_loop();
		$courses = ob_get_clean();

		$content = '';
		if ( count( $posts_array ) > 0 ) {

			$before  = empty( $title ) ? '' : '<header class="archive-header"><h2>' . $title . '</h2></header>';
			$before .= '<section id="main-course" class="course-container">';

			$after = '</section>';

			// assemble
			$content = $before . $courses . $after;

		}

		return $content;

	}


	/**
	 * user_courses function.
	 *
	 * @access public
	 * @param mixed $atts
	 * @param mixed $content (default: null)
	 * @return string
	 */
	public static function user_courses( $atts, $content = null ) {
		global $shortcode_override;
		self::throw_deprecation_warning( 'usercourses', '1.9.0' );

		extract( shortcode_atts( array( 'amount' => 0 ), $atts ) );

		$shortcode_override = 'usercourses';

		ob_start();

		self::output_deprecation_notice( 'usercourses' );

		if ( is_user_logged_in() ) {

			if ( ! Sensei_Utils::get_setting_as_flag( 'js_disable', 'sensei_settings_js_disable' ) ) {
				wp_enqueue_script( Sensei()->token . '-user-dashboard' );
			}
			Sensei_Templates::get_template( 'user/my-courses.php' );

		} else {

			Sensei()->frontend->sensei_login_form();

		}

		$content = ob_get_clean();
		return $content;

	}

	/**
	 * This function is simply to honor the legacy
	 * loop-course.php for the old shortcodes.
	 *
	 * @since 1.9.0
	 */
	public static function initialise_legacy_course_loop() {
		global $wp_query, $shortcode_override, $course_excludes;

		if ( ! is_array( $course_excludes ) ) {
			$course_excludes = array(); }

		// Check that query returns results
		// Handle Pagination
		$paged = $wp_query->get( 'paged' );
		$paged = empty( $paged ) ? 1 : $paged;

		// Check for pagination settings
		if ( isset( Sensei()->settings->settings['course_archive_amount'] ) && ( 0 < absint( Sensei()->settings->settings['course_archive_amount'] ) ) ) {

			$amount = absint( Sensei()->settings->settings['course_archive_amount'] );

		} else {

			$amount = $wp_query->get( 'posts_per_page' );

		}

		// This is not a paginated page (or it's simply the first page of a paginated page/post)
		global $posts_array;
		$course_includes = array();

		$query_args   = Sensei()->course->get_archive_query_args( $shortcode_override, $amount, $course_includes, $course_excludes );
		$course_query = new WP_Query( $query_args );
		$posts_array  = $course_query->get_posts();

		// output the courses
		if ( ! empty( $posts_array ) ) {

			// output all courses for current query
			self::loop_courses( $course_query, $amount );

		}

	}

	/**
	 * Loop through courses in the query and output the information needed
	 *
	 * @since 1.9.0
	 *
	 * @param WP_Query $course_query
	 */
	public static function loop_courses( $course_query, $amount ) {
		global $shortcode_override, $posts_array, $course_excludes, $course_includes;

		if ( count( $course_query->get_posts() ) > 0 ) {

			do_action( 'sensei_course_archive_header', $shortcode_override );

			foreach ( $course_query->get_posts() as $course ) {

				// Make sure the other loops dont include the same post twice!
				array_push( $course_excludes, $course->ID );

				// output the course markup
				self::the_course( $course->ID );

			}

			// More and Prev links
			$posts_array_query = new WP_Query( Sensei()->course->course_query( $shortcode_override, $amount, $course_includes, $course_excludes ) );
			$posts_array       = $posts_array_query->get_posts();
			$max_pages         = $course_query->found_posts / $amount;
			if ( '' != $shortcode_override && ( $max_pages > $course_query->get( 'paged' ) ) ) {

				switch ( $shortcode_override ) {
					case 'paidcourses':
						$filter = 'paid';
						break;
					case 'featuredcourses':
						$filter = 'featured';
						break;
					case 'freecourses':
						$filter = 'free';
						break;
					default:
						$filter = '';
						break;
				}

				$quer_args          = array();
				$quer_args['paged'] = '2';
				if ( ! empty( $filter ) ) {
					$quer_args['course_filter'] = $filter;
				}

				$course_pagination_link = get_post_type_archive_link( 'course' );
				$more_link_text         = esc_html( Sensei()->settings->settings['course_archive_more_link_text'] );
				$more_link_url          = esc_url( add_query_arg( $quer_args, $course_pagination_link ) );

				// next/more
				$html  = '<div class="navigation"><div class="nav-next">';
				$html .= '<a href="' . $more_link_url . '">';
				$html .= $more_link_text;
				$html .= '<span class="meta-nav"></span></a></div>';

				echo wp_kses_post( apply_filters( 'course_archive_next_link', $html ) );

			}
		}
	}

	/**
	 * Print a single course markup
	 *
	 * @param $course_id
	 */
	public static function the_course( $course_id ) {

		// Get meta data
		$course_data           = get_post( $course_id );
		$course                = apply_filters( 'sensei_courses_shortcode_course_data', $course_data );
		$user_info             = get_userdata( absint( $course->post_author ) );
		$author_link           = get_author_posts_url( absint( $course->post_author ) );
		$author_display_name   = $user_info->display_name;
		$category_output       = get_the_term_list( $course_id, 'course-category', '', ', ', '' );
		$preview_lesson_count  = intval( Sensei()->course->course_lesson_preview_count( $course_id ) );
		$lesson_count          = Sensei()->course->course_lesson_count( $course_id );
		$is_user_taking_course = Sensei_Course::is_user_enrolled( $course_id, get_current_user_id() );
		?>

		<article class="<?php echo esc_attr( join( ' ', get_post_class( array( 'course', 'post' ), $course_id ) ) ); ?>">
			<?php
			// so that legacy shortcodes work with the party plugins that wants to hook in
			do_action( 'sensei_course_content_before', $course->ID );
			?>
			<div class="course-content">

				<?php Sensei()->course->course_image( $course_id ); ?>

				<header>

					<h2><a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>" title="<?php echo esc_attr( $course->post_title ); ?>"><?php echo esc_html( $course->post_title ); ?></a></h2>

				</header>

				<section class="entry">

					<p class="sensei-course-meta">

						<?php
						/** This action is documented in includes/class-sensei-frontend.php */
						do_action( 'sensei_course_meta_inside_before', $course_id );
						?>

						<?php if ( isset( Sensei()->settings->settings['course_author'] ) && ( Sensei()->settings->settings['course_author'] ) ) { ?>
							<span class="course-author">
								<?php esc_html_e( 'by', 'sensei-lms' ); ?>
								<a href="<?php echo esc_url( $author_link ); ?>" title="<?php echo esc_attr( $author_display_name ); ?>">
									<?php echo esc_html( $author_display_name ); ?>
								</a>
							</span>
						<?php } ?>

						<span class="course-lesson-count">
							<?php
							// translators: Placeholder %d is the lesson count.
							echo esc_html( sprintf( _n( '%d Lesson', '%d Lessons', $lesson_count, 'sensei-lms' ), $lesson_count ) );
							?>
						</span>

						<?php if ( ! empty( $category_output ) ) { ?>
							<span class="course-category">
								<?php
								// translators: Placeholder is a comma-separated list of the Course categories.
								echo wp_kses_post( sprintf( __( 'in %s', 'sensei-lms' ), $category_output ) );
								?>
							</span>
						<?php } ?>

						<?php
						/** This action is documented in includes/class-sensei-frontend.php */
						do_action( 'sensei_course_meta_inside_after', $course_id );
						?>

					</p>

					<p class="course-excerpt"><?php echo esc_html( $course->post_excerpt ); ?>

					</p>

					<?php
					if ( 0 < $preview_lesson_count && ! $is_user_taking_course ) {
						// translators: Placeholder is the number of preview lessons.
						$preview_lessons = sprintf( __( '(%d preview lessons)', 'sensei-lms' ), $preview_lesson_count );
						?>
						<p class="sensei-free-lessons">
							<a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>"><?php esc_html_e( 'Preview this course', 'sensei-lms' ); ?>
							</a> - <?php echo esc_html( $preview_lessons ); ?>
						</p>
					<?php } ?>

				</section>

			</div>
			<?php
			// so that legacy shortcodes work with thir party plugins that wants to hook in
			do_action( 'sensei_course_content_after', $course->ID );
			?>

		</article>

		<?php

	}

}
