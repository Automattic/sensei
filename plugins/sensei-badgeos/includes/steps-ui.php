<?php
/**
 * Custom Achievement Steps UI
 *
 * @package BadgeOS Sensei
 * @subpackage Achievements
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Update badgeos_get_step_requirements to include our custom requirements
 *
 * @since  1.0.0
 *
 * @param  array $requirements The current step requirements
 * @param  integer $step_id      The given step's post ID
 *
 * @return array                 The updated step requirements
 */
function badgeos_sensei_step_requirements( $requirements, $step_id ) {

	// Add our new requirements to the list
	$requirements[ 'sensei_trigger' ] = get_post_meta( $step_id, '_badgeos_sensei_trigger', true );
	$requirements[ 'sensei_object_id' ] = (int) get_post_meta( $step_id, '_badgeos_sensei_object_id', true );
	$requirements[ 'sensei_object_arg1' ] = (int) get_post_meta( $step_id, '_badgeos_sensei_object_arg1', true );

	// Return the requirements array
	return $requirements;

}

add_filter( 'badgeos_get_step_requirements', 'badgeos_sensei_step_requirements', 10, 2 );

/**
 * Filter the BadgeOS Triggers selector with our own options
 *
 * @since  1.0.0
 *
 * @param  array $triggers The existing triggers array
 *
 * @return array           The updated triggers array
 */
function badgeos_sensei_activity_triggers( $triggers ) {

	$triggers[ 'sensei_trigger' ] = __( 'Sensei Activity', 'badgeos-sensei' );

	return $triggers;

}

add_filter( 'badgeos_activity_triggers', 'badgeos_sensei_activity_triggers' );

/**
 * Add a Community Triggers selector to the Steps UI
 *
 * @since 1.0.0
 *
 * @param integer $step_id The given step's post ID
 * @param integer $post_id The given parent post's post ID
 */
function badgeos_sensei_step_sensei_trigger_select( $step_id, $post_id ) {

	// Setup our select input
	echo '<select name="sensei_trigger" class="select-sensei-trigger">';
	echo '<option value="">' . __( 'Select a Sensei Trigger', 'badgeos-sensei' ) . '</option>';

	// Loop through all of our Sensei trigger groups
	$current_trigger = get_post_meta( $step_id, '_badgeos_sensei_trigger', true );

	$sensei_triggers = $GLOBALS[ 'badgeos_sensei' ]->triggers;

	if ( !empty( $sensei_triggers ) ) {
		foreach ( $sensei_triggers as $trigger => $trigger_label ) {
			if ( is_array( $trigger_label ) ) {
				$optgroup_name = $trigger;
				$triggers = $trigger_label;

				echo '<optgroup label="' . esc_attr( $optgroup_name ) . '">';
				// Loop through each trigger in the group
				foreach ( $triggers as $trigger_hook => $trigger_name ) {
					echo '<option' . selected( $current_trigger, $trigger_hook, false ) . ' value="' . esc_attr( $trigger_hook ) . '">' . esc_html( $trigger_name ) . '</option>';
				}
				echo '</optgroup>';
			}
			else {
				echo '<option' . selected( $current_trigger, $trigger, false ) . ' value="' . esc_attr( $trigger ) . '">' . esc_html( $trigger_label ) . '</option>';
			}
		}
	}

	echo '</select>';

}

add_action( 'badgeos_steps_ui_html_after_trigger_type', 'badgeos_sensei_step_sensei_trigger_select', 10, 2 );

/**
 * Add a BuddyPress group selector to the Steps UI
 *
 * @since 1.0.0
 *
 * @param integer $step_id The given step's post ID
 * @param integer $post_id The given parent post's post ID
 */
function badgeos_sensei_step_etc_select( $step_id, $post_id ) {

	$current_trigger = get_post_meta( $step_id, '_badgeos_sensei_trigger', true );
	$current_object_id = (int) get_post_meta( $step_id, '_badgeos_sensei_object_id', true );
	$current_object_arg1 = (int) get_post_meta( $step_id, '_badgeos_sensei_object_arg1', true );

	// Quizes
	echo '<select name="badgeos_sensei_quiz_id" class="select-quiz-id">';
	echo '<option value="">' . __( 'Any Quiz', 'badgeos-sensei' ) . '</option>';

	// Loop through all objects
	$objects = get_posts( array(
		'post_type' => 'quiz',
		'post_status' => 'publish',
		'posts_per_page' => -1,
	) );

	if ( !empty( $objects ) ) {
		foreach ( $objects as $object ) {
			$selected = '';
			// Check in this quiz even has lessons...
			$question_args = array(
				'post_type'         => array( 'question', 'multiple_question' ),
				'numberposts'       => -1,
				'meta_query'        => array(
					array(
						'key'       => '_quiz_id',
						'value'     => $object->ID,
					)
				),
				'post_status'       => 'any',
				'suppress_filters'  => 0,
				'fields'            => 'ids',
			);
			$questions_array = get_posts( $question_args );
			// ...and skip if not
			if( 0 < count( $questions_array ) ) {
				if ( in_array( $current_trigger, array( 'sensei_user_quiz_grade', 'badgeos_sensei_user_quiz_grade_specific' ) ) )
					$selected = selected( $current_object_id, $object->ID, false );

				echo '<option' . $selected . ' value="' . $object->ID . '">' . esc_html( get_the_title( $object->ID ) ) . '</option>';
			}
		}
	}

	echo '</select>';

	// Grade input
	$grade = 100;

	if ( in_array( $current_trigger, array( 'badgeos_sensei_user_quiz_grade_specific' ) ) )
		$grade = (int) $current_object_arg1;

	if ( empty( $grade ) )
		$grade = 100;

	echo '<span><input name="badgeos_sensei_quiz_grade" class="input-quiz-grade" type="text" value="' . $grade . '" size="3" maxlength="3" placeholder="100" />%</span>';

	// Lessons
	echo '<select name="badgeos_sensei_lesson_id" class="select-lesson-id">';
	echo '<option value="">' . __( 'Any Lesson', 'badgeos-sensei' ) . '</option>';

	// Loop through all objects
	$objects = get_posts( array(
		'post_type' => 'lesson',
		'post_status' => 'publish',
		'posts_per_page' => -1
	) );

	if ( !empty( $objects ) ) {
		foreach ( $objects as $object ) {
			$selected = '';

			if ( in_array( $current_trigger, array( 'sensei_user_lesson_end' ) ) )
				$selected = selected( $current_object_id, $object->ID, false );

			echo '<option' . $selected . ' value="' . $object->ID . '">' . esc_html( get_the_title( $object->ID ) ) . '</option>';
		}
	}

	echo '</select>';

	// Courses
	echo '<select name="badgeos_sensei_course_id" class="select-course-id">';
	echo '<option value="">' . __( 'Any Course', 'badgeos-sensei' ) . '</option>';

	// Loop through all objects
	$objects = get_posts( array(
		'post_type' => 'course',
		'post_status' => 'publish',
		'posts_per_page' => -1
	) );

	if ( !empty( $objects ) ) {
		foreach ( $objects as $object ) {
			$selected = '';

			if ( in_array( $current_trigger, array( 'sensei_user_course_start', 'sensei_user_course_end' ) ) )
				$selected = selected( $current_object_id, $object->ID, false );

			echo '<option' . $selected . ' value="' . $object->ID . '">' . esc_html( get_the_title( $object->ID ) ) . '</option>';
		}
	}

	echo '</select>';

	// Course Category
	echo '<select name="badgeos_sensei_course_category_id" class="select-course-category-id">';
	echo '<option value="">' . __( 'Any Course Category', 'badgeos-sensei' ) . '</option>';

	// Loop through all objects
	$objects = get_terms( 'course-category', array(
		'hide_empty' => false
	) );

	if ( !empty( $objects ) ) {
		foreach ( $objects as $object ) {
			$selected = '';

			if ( in_array( $current_trigger, array( 'badgeos_sensei_user_course_start_category', 'badgeos_sensei_user_course_end_category' ) ) )
				$selected = selected( $current_object_id, $object->term_id, false );

			echo '<option' . $selected . ' value="' . $object->term_id . '">' . esc_html( $object->name ) . '</option>';
		}
	}

	echo '</select>';

}

add_action( 'badgeos_steps_ui_html_after_trigger_type', 'badgeos_sensei_step_etc_select', 10, 2 );

/**
 * AJAX Handler for saving all steps
 *
 * @since  1.0.0
 *
 * @param  string $title     The original title for our step
 * @param  integer $step_id   The given step's post ID
 * @param  array $step_data Our array of all available step data
 *
 * @return string             Our potentially updated step title
 */
function badgeos_sensei_save_step( $title, $step_id, $step_data ) {

	// If we're working on a Sensei trigger
	if ( 'sensei_trigger' == $step_data[ 'trigger_type' ] ) {

		// Update our Sensei trigger post meta
		update_post_meta( $step_id, '_badgeos_sensei_trigger', $step_data[ 'sensei_trigger' ] );

		// Rewrite the step title
		$title = $step_data[ 'sensei_trigger_label' ];

		$object_id = 0;
		$object_arg1 = 0;

		// Quiz specific
		if ( 'sensei_user_quiz_grade' == $step_data[ 'sensei_trigger' ] ) {
			// Get Object ID
			$object_id = (int) $step_data[ 'sensei_quiz_id' ];

			// Set new step title
			if ( empty( $object_id ) ) {
				$title = __( 'Completed any quiz', 'badgeos-sensei' );
			}
			else {
				$title = sprintf( __( 'Completed quiz "%s"', 'badgeos-sensei' ), get_the_title( $object_id ) );
			}
		}
		// Quiz specific (grade specific)
		if ( 'badgeos_sensei_user_quiz_grade_specific' == $step_data[ 'sensei_trigger' ] ) {
			// Get Object ID
			$object_id = (int) $step_data[ 'sensei_quiz_id' ];
			$object_arg1 = (int) $step_data[ 'sensei_quiz_grade' ];

			// Set new step title
			if ( empty( $object_id ) ) {
				$title = sprintf( __( 'Completed any quiz with a score of %d or higher', 'badgeos-sensei' ), $object_arg1 );
			}
			else {
				$title = sprintf( __( 'Completed quiz "%s" with a score of %d or higher', 'badgeos-sensei' ), get_the_title( $object_id ), $object_arg1 );
			}
		}
		// Lesson specific
		elseif ( 'sensei_user_lesson_end' == $step_data[ 'sensei_trigger' ] ) {
			// Get Object ID
			$object_id = (int) $step_data[ 'sensei_lesson_id' ];

			// Set new step title
			if ( empty( $object_id ) ) {
				$title = __( 'Completed any lesson', 'badgeos-sensei' );
			}
			else {
				$title = sprintf( __( 'Completed lesson "%s"', 'badgeos-sensei' ), get_the_title( $object_id ) );
			}
		}
		// Course specific
		elseif ( 'sensei_user_course_end' == $step_data[ 'sensei_trigger' ] ) {
			// Get Object ID
			$object_id = (int) $step_data[ 'sensei_course_id' ];

			// Set new step title
			if ( empty( $object_id ) ) {
				$title = __( 'Completed any course', 'badgeos-sensei' );
			}
			else {
				$title = sprintf( __( 'Completed course "%s"', 'badgeos-sensei' ), get_the_title( $object_id ) );
			}
		}
		// Course Category specific
		elseif ( 'badgeos_sensei_user_course_end_category' == $step_data[ 'sensei_trigger' ] ) {
			// Get Object ID
			$object_id = (int) $step_data[ 'sensei_course_category_id' ];

			// Set new step title
			if ( empty( $object_id ) ) {
				$title = __( 'Completed course in any category', 'badgeos-sensei' );
			}
			else {
				$title = sprintf( __( 'Completed course in category "%s"', 'badgeos-sensei' ), get_term( $object_id, 'course-category' )->name );
			}
		}
		// Course enrollment specific
		elseif ( 'sensei_user_course_start' == $step_data[ 'sensei_trigger' ] ) {
			// Get Object ID
			$object_id = (int) $step_data[ 'sensei_course_id' ];

			// Set new step title
			if ( empty( $object_id ) ) {
				$title = __( 'Enrolled in any course', 'badgeos-sensei' );
			}
			else {
				$title = sprintf( __( 'Enrolled in course "%s"', 'badgeos-sensei' ), get_the_title( $object_id ) );
			}
		}
		// Course enrollment Category specific
		elseif ( 'badgeos_sensei_user_course_start_category' == $step_data[ 'sensei_trigger' ] ) {
			// Get Object ID
			$object_id = (int) $step_data[ 'sensei_course_category_id' ];

			// Set new step title
			if ( empty( $object_id ) ) {
				$title = __( 'Enrolled in course in any category', 'badgeos-sensei' );
			}
			else {
				$title = sprintf( __( 'Enrolled in course in category "%s"', 'badgeos-sensei' ), get_term( $object_id, 'course-category' )->name );
			}
		}

		// Store our Object ID in meta
		update_post_meta( $step_id, '_badgeos_sensei_object_id', $object_id );
		update_post_meta( $step_id, '_badgeos_sensei_object_arg1', $object_arg1 );
	}

	// Send back our custom title
	return $title;

}

add_filter( 'badgeos_save_step', 'badgeos_sensei_save_step', 10, 3 );

/**
 * Include custom JS for the BadgeOS Steps UI
 *
 * @since 1.0.0
 */
function badgeos_sensei_step_js() {

	?>
	<script type="text/javascript">
		jQuery( document ).ready( function ( $ ) {

			// Listen for our change to our trigger type selector
			$( document ).on( 'change', '.select-trigger-type', function () {

				var trigger_type = $( this );

				// Show our group selector if we're awarding based on a specific group
				if ( 'sensei_trigger' == trigger_type.val() ) {
					trigger_type.siblings( '.select-sensei-trigger' ).show().change();
				}
				else {
					trigger_type.siblings( '.select-sensei-trigger' ).hide().change();
				}

			} );

			// Listen for our change to our trigger type selector
			$( document ).on( 'change', '.select-sensei-trigger,' +
										'.select-quiz-id,' +
										'.select-lesson-id,' +
										'.select-course-id,' +
										'.select-course-category-id', function () {

				badgeos_sensei_step_change( $( this ) );

			} );

			// Trigger a change so we properly show/hide our Sensei menues
			$( '.select-trigger-type' ).change();

			// Inject our custom step details into the update step action
			$( document ).on( 'update_step_data', function ( event, step_details, step ) {
				step_details.sensei_trigger = $( '.select-sensei-trigger', step ).val();
				step_details.sensei_trigger_label = $( '.select-sensei-trigger option', step ).filter( ':selected' ).text();

				step_details.sensei_quiz_id = $( '.select-quiz-id', step ).val();
				step_details.sensei_quiz_grade = $( '.input-quiz-grade', step ).val();
				step_details.sensei_lesson_id = $( '.select-lesson-id', step ).val();
				step_details.sensei_course_id = $( '.select-course-id', step ).val();
				step_details.sensei_course_category_id = $( '.select-course-category-id', step ).val();
			} );

		} );

		function badgeos_sensei_step_change( $this ) {
				var trigger_parent = $this.parent(),
					trigger_value = trigger_parent.find( '.select-sensei-trigger' ).val();

				// Quiz specific
				trigger_parent.find( '.select-quiz-id' )
					.toggle(
						( 'sensei_user_quiz_grade' == trigger_value
						 || 'badgeos_sensei_user_quiz_grade_specific' == trigger_value )
					);

				// Lesson specific
				trigger_parent.find( '.select-lesson-id' )
					.toggle( 'sensei_user_lesson_end' == trigger_value );

				// Course specific
				trigger_parent.find( '.select-course-id' )
					.toggle(
						( 'sensei_user_course_start' == trigger_value
						 || 'sensei_user_course_end' == trigger_value )
					);

				// Course Category specific
				trigger_parent.find( '.select-course-category-id' )
					.toggle(
						( 'badgeos_sensei_user_course_start_category' == trigger_value
						 || 'badgeos_sensei_user_course_end_category' == trigger_value )
					);

				// Quiz Grade specific
				trigger_parent.find( '.input-quiz-grade' ).parent() // target parent span
					.toggle( 'badgeos_sensei_user_quiz_grade_specific' == trigger_value );

				if ( ( 'sensei_user_quiz_grade' == trigger_value
					   && '' != trigger_parent.find( '.select-quiz-id' ).val() )
					 || ( 'badgeos_sensei_user_quiz_grade_specific' == trigger_value
					   && '' != trigger_parent.find( '.select-quiz-id' ).val() )
					 || ( 'sensei_user_lesson_end' == trigger_value
						  && '' != trigger_parent.find( '.select-lesson-id' ).val() )
					 || ( ( 'sensei_user_course_start' == trigger_value
							|| 'sensei_user_course_end' == trigger_value )
						  && '' != trigger_parent.find( '.select-course-id' ).val() )
					 || ( ( 'badgeos_sensei_user_course_start_category' == trigger_value
							|| 'badgeos_sensei_user_course_end_category' == trigger_value )
						  && '' != trigger_parent.find( '.select-course-category-id' ).val() ) ) {
					trigger_parent.find( '.required-count' )
						.val( '1' )
						.prop( 'disabled', true );
				}
		}
	</script>
<?php
}

add_action( 'admin_footer', 'badgeos_sensei_step_js' );