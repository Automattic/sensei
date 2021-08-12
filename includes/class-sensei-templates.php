<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // security check, don't load file outside WP
}
/**
 * Sensei Template Class
 *
 * Handles all Template loading and redirecting functionality.
 *
 * @package Views
 * @author Automattic
 *
 * @since 1.9.0
 */
class Sensei_Templates {

	/**
	 *  Load the template files from within sensei/templates/ or the the theme if overrided within the theme.
	 *
	 * @since 1.9.0
	 * @param string $slug
	 * @param string $name default: ''
	 *
	 * @return void
	 */
	public static function get_part( $slug, $name = '' ) {

		$template             = '';
		$plugin_template_url  = Sensei()->template_url;
		$plugin_template_path = Sensei()->plugin_path() . '/templates/';

		// Look in yourtheme/slug-name.php and yourtheme/sensei/slug-name.php
		if ( $name ) {

			$template = locate_template( array( "{$slug}-{$name}.php", "{$plugin_template_url}{$slug}-{$name}.php" ) );

		}

		// Get default slug-name.php
		if ( ! $template && $name && file_exists( $plugin_template_path . "{$slug}-{$name}.php" ) ) {

			$template = $plugin_template_path . "{$slug}-{$name}.php";

		}

		// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/sensei/slug.php
		if ( ! $template ) {

			$template = locate_template( array( "{$slug}.php", "{$plugin_template_url}{$slug}.php" ) );

		}

		if ( $template ) {

			load_template( $template, false );

		}

	}

	/**
	 * Get the template.
	 *
	 * @since 1.9.0
	 *
	 * @param $template_name
	 * @param array         $args
	 * @param string        $template_path
	 * @param string        $default_path
	 */
	public static function get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {

		if ( $args && is_array( $args ) ) {
			extract( $args );
		}

		$located = self::locate_template( $template_name, $template_path, $default_path );

		if ( ! empty( $located ) ) {

			do_action( 'sensei_before_template_part', $template_name, $template_path, $located );

			include $located;

			do_action( 'sensei_after_template_part', $template_name, $template_path, $located );

		}

	}

	/**
	 * Check if the template file exists. A wrapper for WP locate_template.
	 *
	 * @since 1.9.0
	 *
	 * @param $template_name
	 * @param string        $template_path
	 * @param string        $default_path
	 *
	 * @return mixed|void
	 */
	public static function locate_template( $template_name, $template_path = '', $default_path = '' ) {

		if ( ! $template_path ) {
			$template_path = Sensei()->template_url;
		}
		if ( ! $default_path ) {
			$default_path = Sensei()->plugin_path() . 'templates/';
		}

		// Look within passed path within the theme - this is priority
		$template = locate_template(
			array(
				$template_path . $template_name,
				$template_name,
			)
		);

		// Get default template
		if ( ! $template ) {

			$template = $default_path . $template_name;

		}
		// return nothing for file that do not exist
		if ( ! file_exists( $template ) ) {
			$template = '';
		}

		// Return what we found
		return apply_filters( 'sensei_locate_template', $template, $template_name, $template_path );

	}

	/**
	 * Determine which Sensei template to load based on the
	 * current page context.
	 *
	 * @since 1.0
	 *
	 * @param string $template
	 * @return string $template
	 */
	public static function template_loader( $template = '' ) {

		global $wp_query, $email_template;

		$find = array( 'sensei.php' );
		$file = '';

		/**
		 * Filters if Sensei templates and content wrappers should be used. For development purposes.
		 *
		 * @hook   sensei_use_sensei_template
		 *
		 * @param  {bool} $use_templates Whether to use Sensei templates for the request.
		 *
		 * @since  3.6.0
		 * @access private
		 */
		if ( ! apply_filters( 'sensei_use_sensei_template', true ) && ! isset( $email_template ) ) {
			return $template;
		}

		if ( isset( $email_template ) && $email_template ) {

			$file   = 'emails/' . $email_template;
			$find[] = $file;
			$find[] = Sensei()->template_url . $file;

		} elseif ( Sensei_Unsupported_Themes::get_instance()->is_handling_request() ) {

			/*
			* If our unsupported theme renderer is handling the request, we do
			* not need to find a custom template.
			*/
			$file = null;

		} elseif ( is_single() && get_post_type() == 'course' ) {

			// possible backward compatible template include if theme overrides content-single-course.php
			// this template was removed in 1.9.0 and code all moved into the main single-course.php file
			self::locate_and_load_template_overrides( Sensei()->template_url . 'content-single-course.php', true );

			$file   = 'single-course.php';
			$find[] = $file;
			$find[] = Sensei()->template_url . $file;

		} elseif ( is_single() && get_post_type() == 'lesson' ) {  // check

			// possible backward compatible template include if theme overrides content-single-lesson.php
			// this template was removed in 1.9.0 and code all moved into the main single-lesson.php file
			self::locate_and_load_template_overrides( Sensei()->template_url . 'content-single-lesson.php', true );

			$file   = 'single-lesson.php';
			$find[] = $file;
			$find[] = Sensei()->template_url . $file;

		} elseif ( is_single() && get_post_type() == 'quiz' ) {  // check

			// possible backward compatible template include if theme overrides content-single-quiz.php
			// this template was removed in 1.9.0 and code all moved into the main single-quiz.php file
			self::locate_and_load_template_overrides( Sensei()->template_url . 'content-single-quiz.php', true );

			$file   = 'single-quiz.php';
			$find[] = $file;
			$find[] = Sensei()->template_url . $file;

		} elseif ( is_single() && get_post_type() == 'sensei_message' ) { // check

			// possible backward compatible template include if theme overrides content-single-message.php
			// this template was removed in 1.9.0 and code all moved into the main single-message.php file
			self::locate_and_load_template_overrides( Sensei()->template_url . 'content-single-message.php', true );

			$file   = 'single-message.php';
			$find[] = $file;
			$find[] = Sensei()->template_url . $file;

		} elseif ( is_post_type_archive( 'course' )
					|| is_page( Sensei()->get_page_id( 'courses' ) )
					|| is_tax( 'course-category' ) ) {

			// possible backward compatible template include if theme overrides 'taxonomy-course-category'
			// this template was removed in 1.9.0 and replaced by archive-course.php
			self::locate_and_load_template_overrides( Sensei()->template_url . 'taxonomy-course-category.php' );

			$file   = 'archive-course.php';
			$find[] = $file;
			$find[] = Sensei()->template_url . $file;

		} elseif ( is_post_type_archive( 'sensei_message' ) ) {

			$file   = 'archive-message.php';
			$find[] = $file;
			$find[] = Sensei()->template_url . $file;

		} elseif ( is_tax( 'lesson-tag' ) || is_post_type_archive( 'lesson' ) ) {

			// possible backward compatible template include if theme overrides 'taxonomy-lesson-tag.php'
			// this template was removed in 1.9.0 and replaced by archive-lesson.php
			self::locate_and_load_template_overrides( Sensei()->template_url . 'taxonomy-lesson-tag.php' );

			$file   = 'archive-lesson.php';
			$find[] = $file;
			$find[] = Sensei()->template_url . $file;

		} elseif ( isset( $wp_query->query_vars['learner_profile'] ) ) {

			// Override for sites with static home page
			$wp_query->is_home = false;

			$file   = 'learner-profile.php';
			$find[] = $file;
			$find[] = Sensei()->template_url . $file;

		} elseif ( isset( $wp_query->query_vars['course_results'] ) ) {

			// Override for sites with static home page
			$wp_query->is_home = false;

			$file   = 'course-results.php';
			$find[] = $file;
			$find[] = Sensei()->template_url . $file;

		} elseif ( is_author()
				 && Sensei_Teacher::is_a_teacher( get_query_var( 'author' ) )
				 && ! user_can( get_query_var( 'author' ), 'manage_options' ) ) {

			$file   = 'teacher-archive.php';
			$find[] = $file;
			$find[] = Sensei()->template_url . $file;

		} // Load the template file

		// if file is present set it to be loaded otherwise continue with the initial template given by WP
		if ( $file ) {

			$template = locate_template( $find );
			if ( ! $template ) {
				$template = Sensei()->plugin_path() . '/templates/' . $file;
			}
		}

		return $template;

	}

	/**
	 * This function loads the no-permissions template for users with no access
	 * if a Sensei template was loaded.
	 *
	 * This function doesn't determine the permissions. Permissions must be determined
	 * before loading this function as it only gets the template.
	 *
	 * This function also checks the user theme for overrides to ensure the right template
	 * file is returned.
	 *
	 * @since 1.9.0
	 */
	public static function get_no_permission_template() {

		// possible backward compatible template loading
		// this template was removed in 1.9.0 and code all moved into the no-permissions.php file
		self::locate_and_load_template_overrides( Sensei()->template_url . 'content-no-permissions.php', true );

		$file   = 'no-permissions.php';
		$find   = [];
		$find[] = $file;
		$find[] = Sensei()->template_url . $file;

		$template = locate_template( $find );
		if ( ! $template ) {
			$template = Sensei()->plugin_path() . '/templates/' . $file;
		}

		// set a global constant so that we know that we're in this template
		define( 'SENSEI_NO_PERMISSION', true );

		return $template;

	}

	/**
	 * This function is specifically created for loading template files from the theme.
	 *
	 * This function checks if the user has overwritten the templates like in their theme. If they have it in their theme it will load the header and the footer
	 * around the singular content file from their theme and exit.
	 *
	 * If none is found this function will do nothing. If a template is found this funciton
	 * will exit execution of the script an not continue.
	 *
	 * @since 1.9.0
	 * @param string $template
	 * @param bool   $load_header_footer should the file be wrapped in between header and footer? Default: true
	 */
	public static function locate_and_load_template_overrides( $template = '', $load_header_footer = false ) {

		$found_template = locate_template( array( $template ) );
		if ( $found_template ) {

			if ( $load_header_footer ) {

				get_sensei_header();
				include $found_template;
				get_sensei_footer();

			} else {

				include $found_template;

			}

			exit;

		}

	}

	/**
	 * A generic function for echoing the post title
	 *
	 * @since 1.9.0
	 * @param  WP_Post $post
	 */
	public static function the_title( $post ) {

		// ID passed in
		if ( is_numeric( $post ) ) {
			$post = get_post( $post );
		}

		/**
		 * Filter the template html tag for the title
		 *
		 * @since 1.9.0
		 *
		 * @param $title_html_tag default is 'h3'
		 */
		$title_html_tag = apply_filters( 'sensei_the_title_html_tag', 'h3' );

		/**
		 * Filter the title classes
		 *
		 * @since 1.9.0
		 * @param string $title_classes defaults to $post_type-title
		 */
		$title_classes = apply_filters( 'sensei_the_title_classes', $post->post_type . '-title' );

		$html  = '';
		$html .= '<' . $title_html_tag . ' class="' . $title_classes . '" >';
		$html .= '<a href="' . get_permalink( $post->ID ) . '" >';
		/**
		 * Alters the course title
		 *
		 * @since 1.9.16
		 *
		 * @param string $course_title The Course Title.
		 */
		$course_title = (string) apply_filters( 'sensei_course_the_title', $post->post_title );
		$html        .= $course_title;
		$html        .= '</a>';
		$html        .= '</' . $title_html_tag . '>';
		echo wp_kses_post( $html );

	}

	/**
	 * Fire the sensei_complete_course action.
	 *
	 * This is just a backwards compatible function to add the action
	 * to a template. This should not be used as the function from this
	 * hook will be added directly to my-courses page via one of the hooks there.
	 *
	 * @since 1.9.0
	 */
	public static function fire_sensei_complete_course_hook() {

		do_action( 'sensei_complete_course' );

	} //fire_sensei_complete_course_hook

	/**
	 * Fire the frontend message hook
	 *
	 * @since 1.9.0
	 */
	public static function fire_frontend_messages_hook() {

		do_action( 'sensei_frontend_messages' );

	}

	public static function the_register_button( $post_id = '' ) {
		global $current_user;

		// This function is no longer used internally. It should be removed in
		// version 4.0.
		_deprecated_function( __METHOD__, '2.2.0' );

		if ( ! get_option( 'users_can_register' )
			 || 'course' != get_post_type( $post_id )
			 || ! empty( $current_user->caps )
			 || ! Sensei()->settings->get( 'access_permission' ) ) {

			return;
		}

		// if user is not logged in skipped for single lesson
		// show a link to the my_courses page or the WordPress register page if
		// not my courses page was set in the settings
		$my_courses_page_id = 0;
		if ( ! empty( $my_courses_page_id ) && $my_courses_page_id ) {

			$my_courses_url = get_permalink( $my_courses_page_id );

			echo '<div class="status register"><a href="' . esc_url( $my_courses_url ) . '">' .
				esc_html__( 'Register', 'sensei-lms' ) . '</a></div>';

		} else {

			wp_register( '<div class="status register">', '</div>' );

		}

	}
}
