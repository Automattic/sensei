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
	public $updates_run;
	public $updates;
	private $parent;

	/**
	 * Constructor.
	 *
	 * @access  public
	 * @since   1.1.0
	 * @param   string $parent The main Sensei object by Ref.
	 * @return  void
	 */
	public function __construct ( $parent ) {
		// Setup object data
		$this->parent = $parent;
		$this->updates_run = get_option( $this->token . '-upgrades', array() );
		// The list of upgrades to run
		$this->updates = array( '1.1.0' => array( 	'auto' 		=> array( 'assign_role_caps' => array( 'title' => 'Assign role capbilities', 'desc' => 'Assigns Sensei capabilites to the relevant user roles.', 'product' => 'Sensei' ) ),
													'manual' 	=> array()
												),
								'1.3.0' => array( 	'auto' 		=> array( 'set_default_quiz_grade_type' => array( 'title' => 'Set default quiz grade type', 'desc' => 'Sets all quizzes to the default \'auto\' grade type.' ),
																		  'set_default_question_type' => array( 'title' => 'Set default question type', 'desc' => 'Sets all questions to the default \'multiple choice\' type.' )
													),
													'manual' 	=> array( 'update_question_answer_data' => array( 'title' => 'Update question answer data', 'desc' => 'Updates questions to use the new question types structure.' ) )
												),
								'1.4.0' => array( 	'auto' 		=> array( 'update_question_grade_points' => array( 'title' => 'Update question grade points', 'desc' => 'Sets all question grade points to the default value of \'1\'.' ) ),
													'manual' 	=> array()
												),
							);
		$this->updates = apply_filters( 'sensei_upgrade_functions', $this->updates, $this->updates );
		$this->version = get_option( $this->token . '-version' );

		// Manual Update Screen
		add_action('admin_menu', array( $this, 'add_update_admin_screen' ) );

	} // End __construct()

	/**
	 * add_update_admin_screen Adds admin screen to run manual udpates
	 *
	 * @access public
	 * @since  1.3.7
	 * @return void
	 */
	public function add_update_admin_screen() {
		if ( current_user_can( 'manage_options' ) ) {
			add_submenu_page( 'sensei', 'Sensei Updates', 'Updates', 'manage_options', 'sensei_updates', array( $this, 'sensei_updates_page' ) );
		}
	} // End add_update_admin_screen()

	/**
	 * sensei_updates_page HTML output for manual update screen
	 *
	 * @access public
	 * @since  1.3.7
	 * @return void
	 */
	public function sensei_updates_page() {
		// Page contents
		?>

		<div class="wrap">

			<div id="icon-woothemes-sensei" class="icon32"><br></div>
			<h2><?php _e( 'Sensei Updates', 'woothemes-sensei' ); ?></h2>

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
						if ( method_exists( $this, $value) ) {
							$done_processing = call_user_func_array( array( $this, $value ), array( 5, $n ) );
						} else {
							$done_processing = call_user_func_array( $value, array( 5, $n ) );
						} // End If Statement

						// Add to functions list get args
						if ( '' == $functions_list ) {
							$functions_list .= $value;
						} else {
							$functions_list .= '+' . $value;
						} // End If Statement

						// Mark update has having been run
						$this->set_update_run( $value );

					} // End For Loop

				} // End If Statement

				// Check for updates to run
				if ( isset( $_GET['functions'] ) && '' != $_GET['functions'] ) {

					// Existing functions from GET variables instead of POST
					$functions_array = $_GET['functions'];

					foreach ( $functions_array as $key => $value ) {

						// Dynamic function call
						if ( method_exists( $this, $value) ) {
							$done_processing = call_user_func_array( array( $this, $value ), array( 5, $n ) );
						} else {
							$done_processing = call_user_func_array( $value, array( 5, $n ) );
						} // End If Statement

						// Add to functions list get args
						if ( '' == $functions_list ) {
							$functions_list .= $value;
						} else {
							$functions_list .= '+' . $value;
						} // End If Statement

					} // End For Loop

				} // End If Statement

				if ( ! $done_processing ) { ?>

					<h3><?php _e( 'Processing Updates......', 'woothemes-sensei' ); ?></h3>

					<p><?php _e( 'If your browser doesn&#8217;t start loading the next page automatically, click this link:', 'woothemes-sensei' ); ?>&nbsp;&nbsp;<a class="button" href="admin.php?page=sensei_updates&action=update&n=<?php echo ($n + 1) ?>&functions[]=<?php echo $functions_list; ?>"><?php _e( 'Next', 'woothemes-sensei' ); ?></a></p>
					<script type='text/javascript'>
					<!--
					function sensei_nextpage() {
						location.href = "admin.php?page=sensei_updates&action=update&n=<?php echo ($n + 5) ?>&functions[]=<?php echo $functions_list; ?>";
					}
					setTimeout( "sensei_nextpage()", 250 );
					//-->
					</script><?php

				} else { ?>

					<p><strong><?php _e( 'Update completed successfully!', 'woothemes-sensei' ); ?></strong></p>
					<p><a href="<?php echo admin_url('edit.php?post_type=lesson'); ?>"><?php _e( 'Create a new lesson', 'woothemes-sensei' ); ?></a> or <a href="<?php echo admin_url('admin.php?page=sensei_updates'); ?>"><?php _e( 'run some more updates', 'woothemes-sensei' ); ?></a>.</p>

				<?php } // End If Statement

			} else { ?>

				<h3><?php _e( 'Updates', 'woothemes-sensei' ); ?></h3>
				<p><?php printf( __( 'These are updates that have been made available as new Sensei versions have been released. Updates of type %1$sAuto%2$s will run as you update Sensei to the relevant version - other updates need to be run manually and you can do that here.', 'woothemes-sensei' ), '<code>', '</code>' ); ?></p>

				<div class="updated"><p><strong><?php _e( 'Only run these updates if you have been instructed to do so by WooThemes support staff.', 'woothemes-sensei' ); ?></strong></p></div>

				<table class="widefat" cellspacing="0" id="update-plugins-table">

					<thead>
						<tr>
							<th scope="col" class="manage-column"><?php _e( 'Update', 'woothemes-sensei' ); ?></th>
							<th scope="col" class="manage-column"><?php _e( 'Type', 'woothemes-sensei' ); ?></th>
							<th scope="col" class="manage-column"><?php _e( 'Action', 'woothemes-sensei' ); ?></th>
						</tr>
					</thead>

					<tfoot>
						<tr>
							<th scope="col" class="manage-column"><?php _e( 'Update', 'woothemes-sensei' ); ?></th>
							<th scope="col" class="manage-column"><?php _e( 'Type', 'woothemes-sensei' ); ?></th>
							<th scope="col" class="manage-column"><?php _e( 'Action', 'woothemes-sensei' ); ?></th>
						</tr>
					</tfoot>

					<tbody class="plugins">
						<?php
						// Sort updates with the latest at the top
						uksort( $this->updates, array( $this, 'sort_updates' ) );
						$this->updates = array_reverse( $this->updates, true );
						foreach( $this->updates as $version => $version_updates ) {
							foreach( $version_updates as $type => $updates ) {
								foreach( $updates as $update => $data ) {
									$update_run = $this->has_update_run( $update );
									$product = 'Sensei';
									if ( isset( $data['product'] ) && '' != $data['product'] ) {
										$product = $data['product'];
									} // End If Statement
									?>
									<form method="post" action="admin.php?page=sensei_updates&action=update&n=0" name="update-sensei" class="upgrade">
										<tr class="active">
											<td>
												<p>
													<input type="hidden" name="checked[]" value="<?php echo $update; ?>">
													<strong><?php echo $data['title']; ?></strong><br><?php echo $data['desc']; ?><br>
													<em><?php printf( __( 'Originally included in %s v%s', 'woothemes-sensei' ), $product, $version ); ?></em>
												</p>
											</td>
											<td><p><?php echo ucfirst( $type ); ?></p></td>
											<td><p><input onclick="javascript:return confirm('<?php echo addslashes( sprintf( __( 'Are you sure you want to run the \'%s\' update?', 'woothemes-sensei' ), $data['title'] ) ); ?>');" id="update-sensei" class="button<?php if( ! $update_run ) { echo ' button-primary'; } ?>" type="submit" value="<?php if( $update_run ) { _e( 'Re-run Update', 'woothemes-sensei' ); } else { _e( 'Run Update', 'woothemes-sensei' ); } ?>" name="update"></p></td>
										</tr>
									</form>
									<?php
								}
							}
						}
						?>
					</tbody>

					</table>

				</form>

			<?php
			} // End If Statement
	} // End sensei_updates_page()

	/**
	 * Sort updates list by version number
	 *
	 * @param  string $a First key
	 * @param  string $b Second key
	 * @return integer
	 */
	private function sort_updates( $a, $b ) {
		return strcmp( $a, $b );
	}

	/**
	 * update Calls the functions for updating
	 *
	 * @param  string $type specifies if the update is 'auto' or 'manual'
	 * @since  1.1.0
	 * @access public
	 * @return boolean
	 */
	public function update ( $type = 'auto' ) {
		// Run through all functions
		foreach ( $this->updates as $version => $value ) {
			foreach ( $this->updates[$version] as $upgrade_type => $function_to_run ) {
				if ( $upgrade_type == $type ) {
					$updated = false;
					// Run the update function
					foreach ( $function_to_run as $function_name => $update_data ) {
						if ( isset( $function_name ) && '' != $function_name ) {
							if ( ! in_array( $function_name, $this->updates_run ) ) {
								$updated = false;
								if ( method_exists( $this, $function_name ) ) {
									$updated = call_user_func( array( $this, $function_name ) );
								} elseif( function_exists( $function_name ) ) {
									$updated = call_user_func( $function_name );
								} else {
									// Nothing to see here...
								} // End If Statement
								if ( $updated ) {
									array_push( $this->updates_run, $function_name );
								} // End If Statement
							}
						} // End If Statement
					} // End For Loop
				} // End If Statement
			} // End For Loop
		} // End For Loop
		update_option( $this->token . '-upgrades', $this->updates_run );
		return true;
	} // End update()

	/**
	 * Check if specified update has already been run
	 *
	 * @param  string  $update Update to check
	 * @since  1.4.0
	 * @return boolean
	 */
	private function has_update_run( $update ) {
		if ( in_array( $update, $this->updates_run ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Mark update as having been run
	 *
	 * @param string $update Update to process
	 * @since  1.4.0
	 */
	private function set_update_run( $update ) {
		array_push( $this->updates_run, $update );
		update_option( $this->token . '-upgrades', $this->updates_run );
	}

	/**
	 * Sets the role capabilities for WordPress users.
	 *
	 * @since  1.1.0
	 * @access public
	 * @return void
	 */
	public function assign_role_caps() {
		foreach ( $this->parent->post_types->role_caps as $role_cap_set  ) {
			foreach ( $role_cap_set as $role_key => $capabilities_array ) {
				/* Get the role. */
				$role =& get_role( $role_key );
				foreach ( $capabilities_array as $cap_name  ) {
					/* If the role exists, add required capabilities for the plugin. */
					if ( !empty( $role ) ) {
						if ( !$role->has_cap( $cap_name ) ) {
							$role->add_cap( $cap_name );
						} // End If Statement
					} // End If Statement
				} // End For Loop
			} // End For Loop
		} // End For Loop
		return true;
	} // End assign_role_caps

	/**
	 * Set default quiz grade type
	 *
	 * @since 1.3.0
	 * @access public
	 * @return void
	 */
	public function set_default_quiz_grade_type() {
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
		return true;
	} // End set_default_quiz_grade_type

	/**
	 * Set default question type
	 *
	 * @since 1.3.0
	 * @access public
	 * @return void
	 */
	public function set_default_question_type() {
		$args = array(	'post_type' 		=> 'question',
						'numberposts' 		=> -1,
						'post_status'		=> 'publish',
						'suppress_filters' 	=> 0
						);
		$questions = get_posts( $args );

		foreach( $questions as $question ) {
			wp_set_post_terms( $question->ID, array( $question_type ), 'question-type' );
		}
		return true;
	} // End set_default_question_type

	/**
	 * Update question answers to use new data structure
	 *
	 * @since 1.3.0
	 * @access public
	 * @return void
	 */
	public function update_question_answer_data( $n = 5, $offset = 0 ) {

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

		if ( $current_page == $total_pages ) {
			return true;
		} else {
			return false;
		} // End If Statement

	} // End update_question_answer_data

	/**
	 * Add default question grade points for v1.4.0
	 *
	 * @since  1.4.0
	 * @return void
	 */
	public function update_question_grade_points() {
		$args = array(	'post_type' 		=> 'question',
						'numberposts' 		=> -1,
						'post_status'		=> 'publish',
						'suppress_filters' 	=> 0
						);
		$questions = get_posts( $args );

		foreach( $questions as $question ) {
			update_post_meta( $question->ID, '_question_grade', '1' );
		}
		return true;
	} // End update_question_grade_points

} // End Class
?>