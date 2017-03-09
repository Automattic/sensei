<?php

class Sensei_WC_Memberships {
	const WC_MEMBERSHIPS_PLUGIN_PATH = 'woocommerce-memberships/woocommerce-memberships.php';

	const WC_MEMBERSHIPS_VIEW_RESTRICTED_POST_CONTENT = 'wc_memberships_view_restricted_post_content';

	/**
	 * Load WC Memberships integration hooks if WC Memberships is active
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
	}

	public static function is_course_access_restricted( $access_restricted, $course_id ) {
		if ( false === self::is_wc_memberships_active() ) {
			return $access_restricted;
		}

		return self::is_content_restricted( $course_id );
	}

	private static function is_content_restricted( $object_id ) {
		if ( get_current_user_id() > 0 ) {
			$access_restricted = !current_user_can( self::WC_MEMBERSHIPS_VIEW_RESTRICTED_POST_CONTENT, $object_id );
			return $access_restricted;
		}

		return wc_memberships_is_post_content_restricted( $object_id );
	}

	public static function add_wc_memberships_notice( $content = '' ) {
		global $post;
		if ( false === self::is_wc_memberships_active() ) {
			return $content;
		}

		if ( isset( $post->ID ) && !in_array( get_post_type( $post->ID ), array( 'course', 'lesson', 'quiz' ), true ) ||
			 !self::is_content_restricted( $post->ID ) ) {
			return $content;
		}
		$message = wc_memberships()->get_frontend_instance()->get_content_restricted_message( $post->ID );
		return $message;
	}

	/**
	 * Applied to the `sensei_display_start_course_form` filter to determine
	 * if the 'start taking this course' form should be displayed for a given course.
	 * If a course has membership rules, restrict to active logged in members.
	 *
	 * @param $course_id the course in question
	 * @return int|bool the course id or false in case a restriction applies
	 */
	public static function display_start_course_form_to_members_only( $should_display, $course_id ) {

		return !self::is_course_access_restricted( $should_display, $course_id );
	}

	/**
	 * Determine if WC Memberships is installed and active
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
	 * @param $membership_plan
	 * @param array $args
	 */
	public static function on_wc_memberships_user_membership_saved( $membership_plan, $args = array() ) {
		$user_membership_id = isset( $args['user_membership_id'] ) ? absint( $args['user_membership_id'] ) : null;

		if ( !$user_membership_id ) {
			return;
		}

		$user_membership = wc_memberships_get_user_membership( $user_membership_id );
		return self::start_courses_associated_with_membership( $user_membership );
	}

	/**
	 * Start courses associated with an active membership if not already started
	 * so they show up on "my courses".
	 *
	 * Hooked into wc_memberships_user_membership_status_changed
	 *
	 * @param WC_Memberships_User_Membership $user_membership the user membership
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

		if ( false === self::is_user_active_member( $user_id, $membership_plan->get_id() ) ) {
			// User is Inactive so just Bail for now
			return;
		}

		$restricted_content = $membership_plan->get_restricted_content();

		foreach ( $restricted_content->get_posts() as $maybe_course ) {
			if ( empty( $maybe_course ) || 'course' !== $maybe_course->post_type ) {
				continue;
			}

			$course_id = $maybe_course->ID;

			/**
			 * filter sensei_wc_memberships_auto_start_course
			 * determine if we should automatically start users on a specific course that is part of a user membership
			 * and has not started yet.
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

	private static function should_auto_start_membership_courses( $user_membership ) {
		$auto_start_courses = (bool) Sensei()->settings->get( 'sensei_wc_memberships_auto_start_courses' );

		/**
		 * determine if we should automatically start users on any courses that are part of this user membership;
		 *
		 * @param bool $auto_start_courses
		 * @param WC_Memberships_User_Membership $user_membership the user membership
		 */
		return (bool) apply_filters( 'sensei_wc_memberships_auto_start_courses', $auto_start_courses, $user_membership );
	}

	public static function is_my_courses_page( $post_id ) {
		return is_page() && intval( Sensei()->settings->get( 'my_course_page' ) ) === intval( $post_id );
	}

	/**
	 * @param int $user_id
	 * @param int $membership_plan_id
	 * @return bool
	 */
	private static function is_user_active_member( $user_id, $membership_plan_id )
	{
		if ( true === version_compare(wc_memberships()->get_version(), '1.7.0', '>=') ) {
			/**
			 * Sensei WC Memberships "start_courses_associated_with_membership_skip_cache" Filter
			 *
			 * Overrides `wc_memberships_is_user_active_member` cache control on WC Memberships >= 1.7.0
			 * Busts cache when false. We default to false, as this is triggered on membership save/status-change and we don't want stale info
			 *
			 * @since 1.9.13
			 *
			 * @param bool $use_cache (default false)
			 * @param int $user_id
			 * @param int $membership_plan
			 */
			$use_cache = (bool)apply_filters(
				'sensei_wc_memberships_start_courses_associated_with_membership_skip_cache',
				false,
				$user_id,
				$membership_plan_id
			);

			return wc_memberships_is_user_active_member($user_id, $membership_plan_id, $use_cache);
		} else {
			return wc_memberships_is_user_active_member($user_id, $membership_plan_id);
		}
	}
}
