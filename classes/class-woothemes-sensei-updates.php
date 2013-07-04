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
 * - manual_update_admin_screen()
 * - manual_update_adminpage_hidden()
 * - update()
 * - assign_role_caps()
 * - set_default_quiz_grade_type()
 * - set_default_question_type()
 * - update_question_answer_data()
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
													'manual' 	=> array( 'update_question_answer_data' )
												),
							);
		$this->legacy = apply_filters( 'sensei_upgrade_functions', $this->legacy, $this->legacy );
		$this->version = get_option( $this->token . '-version' );

		// Manual Update Screen
		add_action('admin_menu', array( &$this, 'manual_update_admin_screen' ) );

	} // End __construct()

	/**
	 * manual_update_admin_screen Adds hidden screen to run manual udpates
	 * @access public
	 * @since  1.3.7
	 * @return void
	 */
	public function manual_update_admin_screen() {

		// This WordPress variable is essential: it stores which admin pages are registered to WordPress
		global $_registered_pages;

		// Get the name of the hook for this plugin
		// We use "options-general.php" as the parent as we want our page to appear under "options-general.php?page=sensei-manual-update-hidden-page"
		$hookname = get_plugin_page_hookname('sensei-manual-update-hidden-page', 'options-general.php');

		// Add the callback via the action on $hookname, so the callback function is called when the page "options-general.php?page=sensei-manual-update-hidden-page" is loaded
		if (!empty($hookname)) {
			add_action($hookname, array( &$this, 'manual_update_adminpage_hidden' ) );
		} // End If Statement

		// Add this page to the registered pages
		$_registered_pages[$hookname] = true;

	} // End manual_update_admin_screen()

	/**
	 * manual_update_adminpage_hidden html output for hidden manual update screen
	 * @access public
	 * @since  1.3.7
	 * @return void
	 */
	public function manual_update_adminpage_hidden() {
		// Page contents
		?>

		<div class="wrap">

			<div id="icon-tools" class="icon32"><br></div>	<h2><?php _e( 'Sensei Updates', 'woothemes-sensei' ); ?></h2>

			<?php
			if ( isset( $_GET['action'] ) && $_GET['action'] == 'update' && isset( $_GET['n'] ) && intval( $_GET['n'] ) >= 0 && ( ( isset( $_POST['checked'][0] ) && '' != $_POST['checked'][0] ) || ( isset( $_GET['functions'] ) && '' != $_GET['functions'] ) ) ) {

				// Setup the data variables
				$n = intval( $_GET['n'] );
				$functions_list = '';
				$done_processing = false;

				// Check for updates to run
				if ( isset( $_POST['checked'][0] ) && '' != $_POST['checked'][0] ) {

					foreach ( $_POST['checked'] as $key => $value ) {

						// Dynamic function call
						$done_processing = call_user_func_array( array( $this, $value ), array( false, 1, $n ) );

						// Add to functions list get args
						if ( '' == $functions_list ) {
							$functions_list .= $value;
						} else {
							$functions_list .= '+' . $value;
						} // End If Statement

					} // End For Loop

				} // End If Statement

				// Check for updates to run
				if ( isset( $_GET['functions'] ) && '' != $_GET['functions'] ) {

					// Existing functions from GET variables instead of POST
					$functions_array = $_GET['functions'];

					foreach ( $functions_array as $key => $value ) {

						// Dynamic function call
						$done_processing = call_user_func_array( array( $this, $value ), array( false, 1, $n ) );

						// Add to functions list get args
						if ( '' == $functions_list ) {
							$functions_list .= $value;
						} else {
							$functions_list .= '+' . $value;
						} // End If Statement

					} // End For Loop

				} // End If Statement

				if ( !$done_processing ) { ?>

					<h3><?php _e( 'Processing Updates.....', 'woothemes-sensei' ); ?></h3>

					<p><?php _e( 'If your browser doesn&#8217;t start loading the next page automatically, click this link:', 'woothemes-sensei' ); ?>&nbsp;&nbsp;<a class="button" href="options-general.php?page=sensei-manual-update-hidden-page&action=update&n=<?php echo ($n + 1) ?>&functions[]=<?php echo $functions_list; ?>"><?php _e( 'Next', 'woothemes-sensei' ); ?></a></p>
					<script type='text/javascript'>
					<!--
					function sensei_nextpage() {
						location.href = "options-general.php?page=sensei-manual-update-hidden-page&action=update&n=<?php echo ($n + 1) ?>&functions[]=<?php echo $functions_list; ?>";
					}
					setTimeout( "sensei_nextpage()", 250 );
					//-->
					</script><?php

				} else { ?>

					<p><?php _e( 'Update Completed Successfully!', 'woothemes-sensei' ); ?>&nbsp;&nbsp;<a href="<?php echo admin_url('edit.php?post_type=lesson'); ?>"><?php _e( 'Carry on creating Courses!', 'woothemes-sensei' ); ?></a></p>

				<?php } // End If Statement

			} else { ?>

				<h3><?php _e( 'Updates', 'woothemes-sensei' ); ?></h3>
				<p><?php _e( 'The following manual updates may be run.', 'woothemes-sensei' ); ?></p>

				<form method="post" action="options-general.php?page=sensei-manual-update-hidden-page&action=update&n=0" name="update-sensei" class="upgrade">

					<table class="widefat" cellspacing="0" id="update-plugins-table">

						<thead>
							<tr>
								<th scope="col" class="manage-column check-column"><input type="checkbox" id="plugins-select-all"></th>
								<th scope="col" class="manage-column"><label for="plugins-select-all"><?php _e( 'Select All', 'woothemes-sensei' ); ?></label></th>
							</tr>
						</thead>

						<tfoot>
							<tr>
								<th scope="col" class="manage-column check-column"><input type="checkbox" id="plugins-select-all-2"></th>
								<th scope="col" class="manage-column"><label for="plugins-select-all-2"><?php _e( 'Select All', 'woothemes-sensei' ); ?></label></th>
							</tr>
						</tfoot>

						<tbody class="plugins">
							<tr class="active">
								<th scope="row" class="check-column"><input type="checkbox" name="checked[]" value="update_question_answer_data"></th>
								<td><p><strong><?php _e( 'Update Question Answer Data', 'woothemes-sensei' ); ?></strong><br><?php _e( 'Runs through all Learners data and updates the answers from the old quiz types system to the new question types system.', 'woothemes-sensei' ); ?><br><em><?php _e( 'Originally run in v1.3.0', 'woothemes-sensei' ); ?></em></p></td>
							</tr>
						</tbody>

					</table>

					<p><input id="update-sensei" class="button" type="submit" value="<?php _e( 'Run Updates', 'woothemes-sensei' ); ?>" name="update"></p>

				</form>

			<?php
			} // End If Statement
	}

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
	public function update_question_answer_data( $force = false, $n = 5, $offset = 0 ) {

		// Get Total Number of Updates to run
		$quiz_count_object = wp_count_posts( 'quiz' );
		$quiz_count_published = $quiz_count_object->publish;

		// Calculate if this is the last page
		if ( 0 == $offset ) {
			$current_page = 1;
		} else {
			$current_page = intval( $offset / $n );
		} // End If Statement
		$total_pages = intval( $quiz_count_published / $n );

		// Check if update has run
		$updated = get_option( 'sensei_question_answer_data_update' );

		if( ! $updated || $force ) {

			$args = array(	'post_type' 		=> 'quiz',
							'numberposts' 		=> $n,
							'offset'			=> $offset,
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
			if ( $current_page == $total_pages ) {
				add_option( 'sensei_question_answer_data_update', true );
			} // End If Statement
		}

		if ( $current_page == $total_pages ) {
			return true;
		} else {
			return false;
		} // End If Statement

	} // End update_question_answer_data

} // End Class
?>