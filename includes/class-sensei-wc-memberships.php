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

		add_action( 'sensei_display_start_course_form', array( __CLASS__, 'display_start_course_form_to_members_only' ) );
		add_action( 'wc_memberships_user_membership_status_changed', array( __CLASS__, 'start_courses_associated_with_membership' ) );
		add_action( 'wc_memberships_user_membership_saved', array( __CLASS__, 'on_wc_memberships_user_membership_saved' ), 10, 2 );
	}

	/**
	 * Applied to the `sensei_display_start_course_form` filter to determine
	 * if the 'start taking this course' form should be displayed for a given course.
	 * If a course has membership rules, restrict to active logged in members.
	 *
	 * @param $course_id the course in question
	 * @return int|bool the course id or false in case a restriction applies
	 */
	public static function display_start_course_form_to_members_only( $course_id ) {

		if ( false === self::is_wc_memberships_active() ) {
			return $course_id;
		}

		$course_restriction_rules = WC_Memberships::instance()->get_rules_instance()->get_post_content_restriction_rules( $course_id );
		if ( false === $course_restriction_rules || empty( $course_restriction_rules ) ) {
			return $course_id;
		}

		$can_view_course = current_user_can( self::WC_MEMBERSHIPS_VIEW_RESTRICTED_POST_CONTENT, $course_id );

		return ( false === $can_view_course ) ? false : $course_id;
	}

	/**
	 * Determine if WC Memberships is installed and active
	 * @return bool
	 */
	public static function is_wc_memberships_active() {
		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ){
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
		$is_wc_memberships_present_and_activated = in_array( self::WC_MEMBERSHIPS_PLUGIN_PATH, $active_plugins, true ) || array_key_exists( self::WC_MEMBERSHIPS_PLUGIN_PATH, $active_plugins );
		return class_exists( 'WC_Memberships' ) || $is_wc_memberships_present_and_activated;
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

		$user_id = $user_membership->get_user_id();
		$membership_plan = $user_membership->get_plan();

		if ( false === wc_memberships_is_user_active_member($user_id, $membership_plan->get_id() ) ) {
			// User is Inactive so just Bail for now
			return;
		}

		$restricted_content = $membership_plan->get_restricted_content();

		foreach ( $restricted_content->get_posts() as $maybe_course ) {
			if ( empty( $maybe_course ) || 'course' !== $maybe_course->post_type ) {
				continue;
			}

			$course_id = $maybe_course->ID;
			if ( false === Sensei_Utils::user_started_course( $course_id, $user_id ) ) {
				Sensei_Utils::user_start_course( $user_id, $course_id );
			}
		}
	}

	public static function is_my_courses_page( $post_id ) {
		return is_page() && intval( Sensei()->settings->get( 'my_course_page' ) ) === intval( $post_id );
	}
}
