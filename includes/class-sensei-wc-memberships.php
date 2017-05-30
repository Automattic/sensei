<?php
/**
 * Sensei WooCommerce Memberships Integration
 *
 * All functions needed to integrate Sensei and WooCommerce Memberships
 *
 * @package Access-Management
 * @author Automattic
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_WC_Memberships
 */
class Sensei_WC_Memberships {
	const WC_MEMBERSHIPS_PLUGIN_PATH = 'woocommerce-memberships/woocommerce-memberships.php';

	const WC_MEMBERSHIPS_VIEW_RESTRICTED_POST_CONTENT = 'wc_memberships_view_restricted_post_content';

	/**
	 * Load WC Memberships integration hooks if WC Memberships is active
	 *
	 * @return void
	 */
	public static function load_wc_memberships_integration_hooks() {
		if ( false === self::is_wc_memberships_active() ) {
			return;
		}

		add_filter( 'sensei_is_course_content_restricted', array( __CLASS__, 'is_course_access_restricted' ), 10, 2 );
		add_filter( 'sensei_couse_access_permission_message', array( __CLASS__, 'add_wc_memberships_notice' ), 10, 2 );
		add_filter( 'sensei_display_start_course_form', array( __CLASS__, 'display_start_course_form_to_members_only' ), 10, 2 );
		add_filter( 'sensei_user_can_register_for_course', array( __CLASS__, 'display_start_course_form_to_members_only' ), 10, 2 );

		add_action( 'wc_memberships_user_membership_status_changed', array( __CLASS__, 'start_courses_associated_with_membership' ) );
		add_action( 'wc_memberships_user_membership_saved', array( __CLASS__, 'on_wc_memberships_user_membership_saved' ), 10, 2 );
		// Adds Memberships restrictions support to Sensei Lessons and Optionally, Course Videos.
		add_action( 'wp', array( __CLASS__, 'restrict_lesson_details' ) );
		add_action( 'wp', array( __CLASS__, 'restrict_course_videos' ) );
	}

	/**
	 * Is Course Access Restricted.
	 *
	 * @param bool $access_restricted Access Restricted.
	 * @param int  $course_id Course ID.
	 * @return bool
	 */
	public static function is_course_access_restricted( $access_restricted, $course_id ) {
		if ( false === self::is_wc_memberships_active() ) {
			return $access_restricted;
		}

		return self::is_content_restricted( $course_id );
	}

	/**
	 * Is content restricted?
	 *
	 * @param int $object_id The object id.
	 * @return bool
	 */
	private static function is_content_restricted( $object_id ) {
		if ( get_current_user_id() > 0 ) {
			$access_restricted = ! current_user_can( self::WC_MEMBERSHIPS_VIEW_RESTRICTED_POST_CONTENT, $object_id );
			return $access_restricted;
		}

		return wc_memberships_is_post_content_restricted( $object_id );
	}

	/**
	 * Add Notice.
	 *
	 * @param string $content The content.
	 * @return string
	 */
	public static function add_wc_memberships_notice( $content = '' ) {
		global $post;
		if ( false === self::is_wc_memberships_active() ) {
			return $content;
		}

		if ( isset( $post->ID ) && ! in_array( get_post_type( $post->ID ), array( 'course', 'lesson', 'quiz' ), true ) ||
			 ! self::is_content_restricted( $post->ID ) ) {
			return $content;
		}
		$message = wc_memberships()->get_frontend_instance()->get_content_restricted_message( $post->ID );
		return $message;
	}

	/**
	 * Display Start Course form to members only.
	 *
	 * Applied to the `sensei_display_start_course_form` filter to determine
	 * if the 'start taking this course' form should be displayed for a given course.
	 * If a course has membership rules, restrict to active logged in members.
	 *
	 * @param bool $should_display Should Display.
	 * @param int  $course_id The course in question.
	 *
	 * @return bool|int The course id or false in case a restriction applies.
	 */
	public static function display_start_course_form_to_members_only( $should_display, $course_id ) {

		return ! self::is_course_access_restricted( $should_display, $course_id );
	}

	/**
	 * Determine if WC Memberships is installed and active
	 *
	 * @return bool
	 */
	public static function is_wc_memberships_active() {
		return Sensei_Utils::is_plugin_present_and_activated(
			'WC_Memberships',
			self::WC_MEMBERSHIPS_PLUGIN_PATH
		);
	}

	/**
	 * Start courses associated with new membership
	 * so they show up on "my courses".
	 *
	 * Hooked into wc_memberships_user_membership_saved and wc_memberships_user_membership_created
	 *
	 * @param mixed $membership_plan The Membership Plan.
	 * @param array $args The args.
	 */
	public static function on_wc_memberships_user_membership_saved( $membership_plan, $args = array() ) {
		$user_membership_id = isset( $args['user_membership_id'] ) ? absint( $args['user_membership_id'] ) : null;

		if ( ! $user_membership_id ) {
			return;
		}

		$user_membership = wc_memberships_get_user_membership( $user_membership_id );
		self::start_courses_associated_with_membership( $user_membership );
	}

	/**
	 * Start courses associated with an active membership if not already started
	 * so they show up on "my courses".
	 *
	 * Hooked into wc_memberships_user_membership_status_changed
	 *
	 * @param WC_Memberships_User_Membership $user_membership The user membership.
	 */
	public static function start_courses_associated_with_membership( $user_membership ) {

		if ( false === self::is_wc_memberships_active() ) {
			return;
		}

		if ( ! $user_membership ) {
			return;
		}

		$auto_start_courses = self::should_auto_start_membership_courses( $user_membership );
		if ( false === $auto_start_courses ) {
			return;
		}

		$user_id = $user_membership->get_user_id();
		$membership_plan = $user_membership->get_plan();
		if ( empty( $membership_plan ) ) {
			return;
		}

		$restricted_content = $membership_plan->get_restricted_content();
		if ( empty( $restricted_content ) ) {
			return;
		}

		foreach ( $restricted_content->get_posts() as $maybe_course ) {
			if ( empty( $maybe_course ) || 'course' !== $maybe_course->post_type ) {
				continue;
			}

			$course_id = $maybe_course->ID;

			/**
			 * Filter sensei_wc_memberships_auto_start_course
			 *
			 * Determine if we should automatically start users on a specific course
			 * that is part of a user membership and has not started yet.
			 *
			 * @param bool $auto_start_courses
			 * @param WC_Memberships_User_Membership $user_membership the user membership
			 * @param $course_id int the course that will be started
			 * @param $user_id int the user that will start this course
			 * @since 1.9.10
			 */
			$auto_start_course = (bool) apply_filters( 'sensei_wc_memberships_auto_start_course', true, $user_membership, $course_id, $user_id );

			if ( $auto_start_course && false === Sensei_Utils::user_started_course( $course_id, $user_id ) ) {
				Sensei_Utils::user_start_course( $user_id, $course_id );
			}
		}
	}

	/**
	 * Should we auto start any Courses thie Membership controls access to?
	 *
	 * @param WC_Memberships_User_Membership $user_membership User Membership.
	 * @return bool
	 */
	private static function should_auto_start_membership_courses( $user_membership ) {
		$auto_start_courses = (bool) Sensei()->settings->get( 'sensei_wc_memberships_auto_start_courses' );

		/**
		 * Determine if we should automatically start users on any courses that are part of this user membership;
		 *
		 * @param bool $auto_start_courses
		 * @param WC_Memberships_User_Membership $user_membership the user membership
		 */
		return (bool) apply_filters( 'sensei_wc_memberships_auto_start_courses', $auto_start_courses, $user_membership );
	}

	/**
	 * Is My Courses Page
	 *
	 * @param int $post_id Post Id.
	 * @return bool
	 */
	public static function is_my_courses_page( $post_id ) {
		return is_page() && intval( Sensei()->settings->get( 'my_course_page' ) ) === intval( $post_id );
	}

	/**
	 * Required: Restrict lesson videos & quiz links until the member has access to the lesson.
	 * Used to ensure content dripping from Memberships is compatible with Sensei.
	 *
	 * This will also remove the "complete lesson" button until the lesson is available.
	 */
	static function restrict_lesson_details() {
		global $post;

		// sanity checks.
		if ( ! function_exists( 'wc_memberships_get_user_access_start_time' ) || ! function_exists( 'Sensei' ) || 'lesson' !== get_post_type( $post ) ) {
			return;
		}

		// if access start time isn't set, or is after the current date, remove the video.
		if ( ! wc_memberships_get_user_access_start_time( get_current_user_id(), 'view', array(
			'lesson' => $post->ID,
		) )
			|| current_time( 'timestamp' ) < wc_memberships_get_user_access_start_time( get_current_user_id(), 'view', array(
				'lesson' => $post->ID,
			) ) ) {

			remove_action( 'sensei_single_lesson_content_inside_after',  array( 'Sensei_Lesson', 'footer_quiz_call_to_action' ) );
			remove_action( 'sensei_single_lesson_content_inside_before', array( 'Sensei_Lesson', 'user_lesson_quiz_status_message' ), 20 );

			remove_action( 'sensei_lesson_video',           array( Sensei()->frontend, 'sensei_lesson_video' ), 10, 1 );
			remove_action( 'sensei_lesson_meta',            array( Sensei()->frontend, 'sensei_lesson_meta' ), 10 );
			remove_action( 'sensei_complete_lesson_button', array( Sensei()->frontend, 'sensei_complete_lesson_button' ) );
		}
	}


	/**
	 * Optional: Restrict course videos unless the member has access.
	 * Used if you don't want to show course previews to non-members.
	 */
	static function restrict_course_videos() {
		global $post;

		// sanity checks.
		if ( ! function_exists( 'wc_memberships_get_user_access_start_time' ) || ! function_exists( 'Sensei' ) || 'course' !== get_post_type( $post ) ) {
			return;
		}

		$restrict_course_video = (bool) Sensei()->settings->get( 'sensei_wc_memberships_restrict_course_video' );

		if ( ! $restrict_course_video ) {
			return;
		}

		// if access start time isn't set, or is after the current date, remove the video.
		if ( ! wc_memberships_get_user_access_start_time( get_current_user_id(), 'view', array(
			'course' => $post->ID,
		) )
			|| current_time( 'timestamp' ) < wc_memberships_get_user_access_start_time( get_current_user_id(), 'view', array(
				'course' => $post->ID,
			) ) ) {

			remove_action( 'sensei_single_course_content_inside_before',  array( 'Sensei_Course', 'the_course_video' ), 40 );
			remove_action( 'sensei_no_permissions_inside_before_content', array( 'Sensei_Course', 'the_course_video' ), 40 );
		}
	}
}
