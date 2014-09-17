<?php
/**
 * Custom Achievement Rules
 *
 * @package BadgeOS Sensei
 * @subpackage Achievements
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Load up our Sensei triggers so we can add actions to them
 *
 * @since 1.0.0
 */
function badgeos_sensei_load_triggers() {

	// Grab our Sensei triggers
	$sensei_triggers = $GLOBALS[ 'badgeos_sensei' ]->triggers;

	if ( !empty( $sensei_triggers ) ) {
		foreach ( $sensei_triggers as $trigger => $trigger_label ) {
			if ( is_array( $trigger_label ) ) {
				$triggers = $trigger_label;

				foreach ( $triggers as $trigger_hook => $trigger_name ) {
					add_action( $trigger_hook, 'badgeos_sensei_trigger_event', 10, 20 );
				}
			}
			else {
				add_action( $trigger, 'badgeos_sensei_trigger_event', 10, 20 );
			}
		}
	}

}

add_action( 'init', 'badgeos_sensei_load_triggers' );

/**
 * Handle each of our Sensei triggers
 *
 * @since 1.0.0
 */
function badgeos_sensei_trigger_event() {

	// Setup all our important variables
	global $blog_id, $wpdb;

	// Setup args
	$args = func_get_args();

	$userID = (int) $args[ 0 ];

	$user_data = get_user_by( 'id', $userID );

	// Grab the current trigger
	$this_trigger = current_filter();

	// Update hook count for this user
	$new_count = badgeos_update_user_trigger_count( $userID, $this_trigger, $blog_id );

	// Mark the count in the log entry
	badgeos_post_log_entry( null, $userID, null, sprintf( __( '%1$s triggered %2$s (%3$dx)', 'badgeos' ), $user_data->user_login, $this_trigger, $new_count ) );

	// Now determine if any badges are earned based on this trigger event
	$triggered_achievements = $wpdb->get_results( $wpdb->prepare( "
		SELECT post_id
		FROM   $wpdb->postmeta
		WHERE  meta_key = '_badgeos_sensei_trigger'
				AND meta_value = %s
		", $this_trigger ) );

	foreach ( $triggered_achievements as $achievement ) {
		badgeos_maybe_award_achievement_to_user( $achievement->post_id, $userID, $this_trigger, $blog_id, $args );
	}
}

/**
 * Check if user deserves a Sensei trigger step
 *
 * @since  1.0.0
 *
 * @param  bool $return         Whether or not the user deserves the step
 * @param  integer $user_id        The given user's ID
 * @param  integer $achievement_id The given achievement's post ID
 * @param  string $trigger        The trigger
 * @param  integer $site_id        The triggered site id
 * @param  array $args        The triggered args
 *
 * @return bool                    True if the user deserves the step, false otherwise
 */
function badgeos_sensei_user_deserves_sensei_step( $return, $user_id, $achievement_id, $this_trigger = '', $site_id = 1, $args = array() ) {

	// If we're not dealing with a step, bail here
	if ( 'step' != get_post_type( $achievement_id ) ) {
		return $return;
	}

	// Grab our step requirements
	$requirements = badgeos_get_step_requirements( $achievement_id );

	// If the step is triggered by Sensei actions...
	if ( 'sensei_trigger' == $requirements[ 'trigger_type' ] ) {
		// Do not pass go until we say you can
		$return = false;

		// Unsupported trigger
		if ( !isset( $GLOBALS[ 'badgeos_sensei' ]->triggers[ $this_trigger ] ) ) {
			return $return;
		}

		// Sensei requirements not met yet
		$sensei_triggered = false;

		// Set our main vars
		$sensei_trigger = $requirements[ 'sensei_trigger' ];
		$object_id = $requirements[ 'sensei_object_id' ];

		// Extra arg handling for further expansion
		$object_arg1 = null;

		if ( isset( $requirements[ 'sensei_object_arg1' ] ) )
			$object_arg1 = $requirements[ 'sensei_object_arg1' ];

		// Object-specific triggers
		$sensei_object_triggers = array(
			'sensei_user_quiz_grade',
			'badgeos_sensei_user_quiz_grade_specific',
			'sensei_user_lesson_end',
			'sensei_user_course_start',
			'sensei_user_course_end'
		);

		// Category-specific triggers
		$sensei_category_triggers = array(
			'badgeos_sensei_user_course_start_category',
			'badgeos_sensei_user_course_end_category'
		);

		// Quiz-specific triggers
		$sensei_quiz_triggers = array(
			'sensei_user_quiz_grade',
			'badgeos_sensei_user_quiz_grade_specific'
		);

		// Quiz arg handling
		if ( in_array( $sensei_trigger, $sensei_quiz_triggers ) ) {
			// If no grade set (or no specificity defined), default to passing grade
			if ( empty( $object_arg1 ) || 'sensei_user_quiz_grade' == $sensei_trigger )
				$object_arg1 = $args[ 3 ];
		}

		// Triggered object ID (used in these hooks, generally 2nd arg)
		$triggered_object_id = 0;

		if ( isset( $args[ 1 ] ) )
			$triggered_object_id = (int) $args[ 1 ];

		// Use basic trigger logic if no object set
		if ( empty( $object_id ) ) {
			$sensei_triggered = true;

			// Failed test, do not pass go, do not collect your badge
			if ( in_array( $sensei_trigger, $sensei_quiz_triggers ) && $args[ 2 ] < $object_arg1 )
				$sensei_triggered = false;
		}
		// Object specific
		elseif ( in_array( $sensei_trigger, $sensei_object_triggers ) && $triggered_object_id == $object_id ) {
			$sensei_triggered = true;

			// Forcing 1 count due to BadgeOS bug tracking triggers properly
			$requirements[ 'count' ] = 1;

			// Failed test, do not pass go, do not collect your badge
			if ( in_array( $sensei_trigger, $sensei_quiz_triggers ) && $args[ 2 ] < $object_arg1 )
				$sensei_triggered = false;
		}
		// Category specific
		elseif ( in_array( $sensei_trigger, $sensei_category_triggers ) && has_term( $object_id, 'course-category', $triggered_object_id ) ) {
			$sensei_triggered = true;

			// Forcing 1 count due to BadgeOS bug tracking triggers properly
			$requirements[ 'count' ] = 1;
		}

		// Sensei requirements met
		if ( $sensei_triggered ) {
			// Grab the trigger count
			$trigger_count = badgeos_get_user_trigger_count( $user_id, $this_trigger, $site_id );

			// If we meet or exceed the required number of checkins, they deserve the step
			if ( $requirements[ 'count' ] <= $trigger_count ) {
				// OK, you can pass go now
				$return = true;
			}
		}
	}

	return $return;
}

add_filter( 'user_deserves_achievement', 'badgeos_sensei_user_deserves_sensei_step', 15, 6 );