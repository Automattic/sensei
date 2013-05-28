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
								'1.3.0' => array( 	'auto' 		=> array( 'set_default_quiz_grade_type', 'set_default_question_type', 'update_question_answer_data' ),
													'manual' 	=> array( 'update_question_answer_data' )
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
						flush_rewrite_rules();
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

	/**
	 * Update question answers to use new data structure
	 *
	 * @since 1.3.0
	 * @access public
	 * @return void
	 */
	public function update_question_answer_data( $force = false ) {

		// Check if update has run
		$updated = get_option( 'sensei_question_answer_data_update' );

		if( ! $updated || $force ) {

			$args = array(	'post_type' 		=> 'quiz',
							'numberposts' 		=> -1,
    						'post_status'		=> 'publish',
							'suppress_filters' 	=> 0
							);
			$quizzes = get_posts( $args );

			$old_answers = array();
			$right_answers = array();
			$old_user_answers = array();

			if( is_array( $quizzes ) ) {
				foreach( $quizzes as $quiz ) {
					$quiz_id = $quiz->ID;

					// Get current user answers
					$comments = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $quiz_id, 'type' => 'sensei_quiz_answers' ), true  );
					if( is_array( $comments ) ) {
						foreach ( $comments as $comment ) {
							$user_id = $comment->user_id;
							$content = maybe_unserialize( base64_decode( $comment->comment_content ) );
							$old_user_answers[ $quiz_id ][ $user_id ] = $content;
						}
					}

					// Get correct answers
					$questions = WooThemes_Sensei_Utils::sensei_get_quiz_questions( $quiz_id );
					if( is_array( $questions ) ) {
						foreach( $questions as $question ) {
							$right_answer = get_post_meta( $question->ID, '_question_right_answer', true );
							$right_answers[ $quiz_id ][ $question->ID ] = $right_answer;
						}
					}
				}
			}

			if( is_array( $right_answers ) ) {
				foreach( $right_answers as $quiz_id => $question ) {
					$count = 0;
					if( is_array( $question ) ) {
						foreach( $question as $question_id => $answer ) {
							++$count;
							if( isset( $old_user_answers[ $quiz_id ] ) ) {
								$answers_linkup[ $quiz_id ][ $count ] = $question_id;
							}
						}
					}
				}
			}

			if( is_array( $old_user_answers ) ) {
				foreach( $old_user_answers as $quiz_id => $user_answers ) {
					foreach( $user_answers as $user_id => $answers ) {
						foreach( $answers as $answer_id => $user_answer ) {
							$question_id = $answers_linkup[ $quiz_id ][ $answer_id ];
							$new_user_answers[ $question_id ] = $user_answer;
							WooThemes_Sensei_Utils::sensei_grade_question_auto( $question_id, $user_answer, $user_id );
						}
						$lesson_id = get_post_meta( $quiz_id, '_quiz_lesson', true );
						WooThemes_Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );
						WooThemes_Sensei_Utils::sensei_save_quiz_answers( $new_user_answers, $user_id );
					}
				}
			}

			// Mark update as complete
			add_option( 'sensei_question_answer_data_update', true );
		}
	} // End update_question_answer_data

} // End Class
?>