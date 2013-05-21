<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Updates Class
 *
 * Class that contains the updates for Sensei data and structures.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Core
 * @author WooThemes
 * @since 1.1.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 * - update()
 * - assign_role_caps()
 */
class WooThemes_Sensei_Updates {
	public $token = 'woothemes-sensei';
	public $version;
	public $upgrades_run;
	public $legacy;
	private $parent;

	/**
	 * Constructor.
	 * @access  public
	 * @since   1.1.0
	 * @param   string $parent The main Sensei object by Ref.
	 * @return  void
	 */
	public function __construct ( &$parent ) {
		// Setup object data
		$this->parent = $parent;
		$this->upgrades_run = get_option( $this->token . '-upgrades', array() );
		// The list of upgrades to run
		$this->legacy = array( 	'1.0.0' => array(),
								'1.1.0' => array( 	'auto' 		=> array( 'assign_role_caps' ),
													'manual' 	=> array()
												),
								'1.3.0' => array( 	'auto' 		=> array( 'set_default_quiz_grade_type', 'set_default_question_type' ),
													'manual' 	=> array()
												),
							);
		$this->legacy = apply_filters( 'sensei_upgrade_functions', $this->legacy, $this->legacy );
		$this->version = get_option( $this->token . '-version' );
	} // End __construct()

	/**
	 * update Calls the functions for updating
	 * @param  string $type specifies if the update is 'auto' or 'manual'
	 * @since  1.1.0
	 * @access public
	 * @return boolean
	 */
	public function update ( $type = 'auto' ) {
		// Run through all functions
		foreach ( $this->legacy as $key => $value ) {
			if ( !in_array( $key, $this->upgrades_run ) ) {
				// Run the update function
				foreach ( $this->legacy[$key] as $upgrade_type => $function_to_run ) {
					$updated = false;
					foreach ( $function_to_run as $function_name ) {
						if ( isset( $function_name ) && '' != $function_name ) {
							if ( $upgrade_type == $type && method_exists( $this, $function_name ) ) {
								$updated = call_user_func( array( $this, $function_name ) );
							} elseif( $upgrade_type == $type && function_exists( $function_name ) ) {
								$updated = call_user_func( $function_name );
							} else {
								// Nothing to see here...
							} // End If Statement
						} // End If Statement
					} // End For Loop
					// If successful
					if ( $updated ) {
						array_push( $this->upgrades_run, $key );
					} // End If Statement
				} // End For Loop
			} // End If Statement
		} // End For Loop
		update_option( $this->token . '-upgrades', $this->upgrades_run );
		return true;
	} // End update()

	/**
	 * Sets the role capabilities for WordPress users.
	 *
	 * @since  1.1.0
	 * @access public
	 * @return void
	 */
	public function assign_role_caps() {
		$success = false;
		foreach ( $this->parent->post_types->role_caps as $role_cap_set  ) {
			foreach ( $role_cap_set as $role_key => $capabilities_array ) {
				/* Get the role. */
				$role =& get_role( $role_key );
				foreach ( $capabilities_array as $cap_name  ) {
					/* If the role exists, add required capabilities for the plugin. */
					if ( !empty( $role ) ) {
						if ( !$role->has_cap( $cap_name ) ) {
							$role->add_cap( $cap_name );
							$success = true;
						} // End If Statement
					} // End If Statement
				} // End For Loop
			} // End For Loop
		} // End For Loop
		return $success;
	} // End assign_role_caps

	/**
	 * Set default quiz grade type
	 *
	 * @since 1.3.0
	 * @access public
	 * @return void
	 */
	public function set_default_quiz_grade_type() {

		// Check if update has run
		$updated = get_option( 'sensei_quiz_grade_type_update' );

		if( ! $updated ) {

			$args = array(	'post_type' 		=> 'quiz',
							'numberposts' 		=> -1,
    						'post_status'		=> 'publish',
							'suppress_filters' 	=> 0
							);
			$quizzes = get_posts( $args );

			foreach( $quizzes as $quiz ) {
				update_post_meta( $quiz->ID, '_quiz_grade_type', 'auto' );
				update_post_meta( $quiz->ID, '_quiz_grade_type_disabled', '' );
			}

			// Mark update as complete
			add_option( 'sensei_quiz_grade_type_update', true );
		}
	} // End set_default_quiz_grade_type

	/**
	 * Set default question type
	 *
	 * @since 1.3.0
	 * @access public
	 * @return void
	 */
	public function set_default_question_type() {

		// Check if update has run
		$updated = get_option( 'sensei_question_type_update' );

		if( ! $updated ) {

			$args = array(	'post_type' 		=> 'question',
							'numberposts' 		=> -1,
    						'post_status'		=> 'publish',
							'suppress_filters' 	=> 0
							);
			$questions = get_posts( $args );

			foreach( $questions as $question ) {
				wp_set_post_terms( $question->ID, array( $question_type ), 'question-type' );
			}

			// Mark update as complete
			add_option( 'sensei_question_type_update', true );
		}
	} // End set_default_question_type

} // End Class
?>