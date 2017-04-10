<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Updates Class
 *
 * Class that contains the updates for Sensei data and structures.
 *
 * @package Core
 * @author Automattic
 * @since 1.1.0
 */
class Sensei_Updates
{
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
    public function __construct($parent)
    {

		// Setup object data
		$this->parent = $parent;
		$this->updates_run = get_option( 'woothemes-sensei-upgrades', array() );

        // The list of upgrades to run
        $this->updates = array('1.1.0' => array('auto' => array('assign_role_caps' => array('title' => __('Assign role capabilities', 'woothemes-sensei'), 'desc' => __('Assigns Sensei capabilites to the relevant user roles.', 'woothemes-sensei'), 'product' => 'Sensei')),
            'manual' => array()
        ),
            '1.3.0' => array('auto' => array('set_default_quiz_grade_type' => array('title' => __('Set default quiz grade type', 'woothemes-sensei'), 'desc' => __('Sets all quizzes to the default \'auto\' grade type.', 'woothemes-sensei')),
                'set_default_question_type' => array('title' => __('Set default question type', 'woothemes-sensei'), 'desc' => __('Sets all questions to the default \'multiple choice\' type.', 'woothemes-sensei'))
            ),
                'manual' => array('update_question_answer_data' => array('title' => __('Update question answer data', 'woothemes-sensei'), 'desc' => __('Updates questions to use the new question types structure.', 'woothemes-sensei')))
            ),
            '1.4.0' => array('auto' => array('update_question_grade_points' => array('title' => __('Update question grade points', 'woothemes-sensei'), 'desc' => __('Sets all question grade points to the default value of \'1\'.', 'woothemes-sensei'))),
                'manual' => array()
            ),
            '1.5.0' => array('auto' => array('convert_essay_paste_questions' => array('title' => __('Convert essay paste questions into multi-line questions', 'woothemes-sensei'), 'desc' => __('Converts all essay paste questions into multi-line questions as the essay paste question type was removed in v1.5.0.', 'woothemes-sensei'))),
                'manual' => array('set_random_question_order' => array('title' => __('Set all quizzes to have a random question order', 'woothemes-sensei'), 'desc' => __('Sets the order all of questions in all quizzes to a random order, which can be switched off per quiz.', 'woothemes-sensei')),
                    'set_default_show_question_count' => array('title' => __('Set all quizzes to show all questions', 'woothemes-sensei'), 'desc' => __('Sets all quizzes to show all questions - this can be changed per quiz.', 'woothemes-sensei')),
                    'remove_deleted_user_activity' => array('title' => __('Remove Sensei activity for deleted users', 'woothemes-sensei'), 'desc' => __('Removes all course, lesson &amp; quiz activity for users that have already been deleted from the database. This will fix incorrect learner counts in the Analysis section.', 'woothemes-sensei')))
            ),
            '1.6.0' => array('auto' => array('add_teacher_role' => array('title' => __('Add \'Teacher\' role', 'woothemes-sensei'), 'desc' => __('Adds a \'Teacher\' role to your WordPress site that will allow users to mange the Grading and Analysis pages.', 'woothemes-sensei')),
                'add_sensei_caps' => array('title' => __('Add administrator capabilities', 'woothemes-sensei'), 'desc' => __('Adds the \'manage_sensei\' and \'manage_sensei_grades\' capabilities to the Administrator role.', 'woothemes-sensei')),
                'restructure_question_meta' => array('title' => __('Restructure question meta data', 'woothemes-sensei'), 'desc' => __('Restructures the question meta data as it relates to quizzes - this accounts for changes in the data structure in v1.6+.', 'woothemes-sensei')),
                'update_quiz_settings' => array('title' => __('Add new quiz settings', 'woothemes-sensei'), 'desc' => __('Adds new settings to quizzes that were previously registered as global settings.', 'woothemes-sensei')),
                'reset_lesson_order_meta' => array('title' => __('Set default order of lessons', 'woothemes-sensei'), 'desc' => __('Adds data to lessons to ensure that they show up on the \'Order Lessons\' screen - if this update has been run once before then it will reset all lessons to the default order.', 'woothemes-sensei')),),
                'manual' => array()
            ),
            '1.7.0' => array('auto' => array('add_editor_caps' => array('title' => __('Add Editor capabilities', 'woothemes-sensei'), 'desc' => __('Adds the \'manage_sensei_grades\' capability to the Editor role.', 'woothemes-sensei')),),
                'forced' => array('update_question_gap_fill_separators' => array('title' => __('Update Gap Fill questions', 'woothemes-sensei'), 'desc' => __('Updates the format of gap fill questions to allow auto grading and greater flexibility in matching.', 'woothemes-sensei')),
                    'update_quiz_lesson_relationship' => array('title' => __('Restructure quiz lesson relationship', 'woothemes-sensei'), 'desc' => __('Adds data to quizzes and lessons to ensure that they maintain their 1 to 1 relationship.', 'woothemes-sensei')),
                    'status_changes_fix_lessons' => array('title' => __('Update lesson statuses', 'woothemes-sensei'), 'desc' => __('Update existing lesson statuses.', 'woothemes-sensei')),
                    'status_changes_convert_lessons' => array('title' => __('Convert lesson statuses', 'woothemes-sensei'), 'desc' => __('Convert to new lesson statuses.', 'woothemes-sensei')),
                    'status_changes_convert_courses' => array('title' => __('Convert course statuses', 'woothemes-sensei'), 'desc' => __('Convert to new course statuses.', 'woothemes-sensei')),
                    'status_changes_convert_questions' => array('title' => __('Convert question statuses', 'woothemes-sensei'), 'desc' => __('Convert to new question statuses.', 'woothemes-sensei')),
                    'update_legacy_sensei_comments_status' => array('title' => __('Convert legacy Sensei activity types', 'woothemes-sensei'), 'desc' => __('Convert all legacy Sensei activity types such as \'sensei_lesson_start\' and \'sensei_user_answer\' to new status format.', 'woothemes-sensei')),
                    'update_comment_course_lesson_comment_counts' => array('title' => __('Update comment counts', 'woothemes-sensei'), 'desc' => __('Update comment counts on Courses and Lessons due to status changes.', 'woothemes-sensei')),),
            ),
            '1.7.2' => array('auto' => array('index_comment_status_field' => array('title' => __('Add database index to comment statuses', 'woothemes-sensei'), 'desc' => __('This indexes the comment statuses in the database, which will speed up all Sensei activity queries.', 'woothemes-sensei')),),
            ),
            '1.8.0' => array('auto' => array('enhance_teacher_role' => array('title' => 'Enhance the \'Teacher\' role', 'desc' => 'Adds the ability for a \'Teacher\' to create courses, lessons , quizes and manage their learners.'),),
                'manual' => array()
            ),
        );

		$this->updates = apply_filters( 'sensei_upgrade_functions', $this->updates, $this->updates );
		$this->version = get_option( 'woothemes-sensei-version' );

        // Manual Update Screen
        add_action('admin_menu', array($this, 'add_update_admin_screen'), 50);

    } // End __construct()

    /**
     * add_update_admin_screen Adds admin screen to run manual udpates
     *
     * @access public
     * @since  1.3.7
     * @return void
     */
    public function add_update_admin_screen()
    {
        if (current_user_can('manage_options')) {
            add_submenu_page('sensei', __('Sensei Updates', 'woothemes-sensei'), __('Data Updates', 'woothemes-sensei'), 'manage_options', 'sensei_updates', array($this, 'sensei_updates_page'));
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

        // Only allow admins to load this page and run the update functions
        if ( ! current_user_can('manage_options')) {

            return;

        }
        ?>

        <div class="wrap">

        <div id="icon-woothemes-sensei" class="icon32"><br></div>
        <h2><?php _e('Sensei Updates', 'woothemes-sensei'); ?></h2>

        <?php
        $function_name= '';
        if ( isset($_GET['action']) && $_GET['action'] == 'update'
            && isset($_GET['n']) && intval($_GET['n']) >= 0
            && ( (isset($_POST['checked'][0]) && '' != $_POST['checked'][0]) || (isset($_GET['functions']) && '' != $_GET['functions']))) {

            // Setup the data variables
            $n = intval($_GET['n']);
            $functions_list = '';
            $done_processing = false;

            // Check for updates to run
            if (isset($_POST['checked'][0]) && '' != $_POST['checked'][0]) {

                foreach ($_POST['checked'] as $key => $function_name) {

                    if( ! isset(  $_POST[ $function_name.'_nonce_field' ] ) 
                        || ! wp_verify_nonce( $_POST[ $function_name.'_nonce_field' ] , 'run_'.$function_name ) ){

                        wp_die(
                            '<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
                            '<p>' . __( 'The nonce supplied in order to run this update function is invalid', 'woothemes-sensei' ) . '</p>',
                            403
                        );

                    }

                    // Dynamic function call
                    if (method_exists($this, $function_name)) {

                        $done_processing = call_user_func_array(array($this, $function_name), array(50, $n));

                    } elseif ( $this->function_in_whitelist( $function_name ) && function_exists( $function_name ) ) {

                        $done_processing = call_user_func_array( $function_name, array( 50, $n ) );

                    } else {

                        _doing_it_wrong( esc_html( $function_name) , 'Is not a valid Sensei updater function', 'Sensei 1.9.0');
                        return;

                    }// End If Statement

                // Add to functions list get args
                if ('' == $functions_list) {
                    $functions_list .= $function_name;
                } else {
                    $functions_list .= '+' . $function_name;
                } // End If Statement

                // Mark update has having been run
                $this->set_update_run($function_name);

            } // End For Loop

        } // End If Statement

        // Check for updates to run
        if (isset($_GET['functions']) && '' != $_GET['functions']) {

            // Existing functions from GET variables instead of POST
            $functions_array = $_GET['functions'];

            foreach ($functions_array as $key => $function_name) {

                if( ! isset( $_GET[ $function_name.'_nonce' ] )
                    || ! wp_verify_nonce( $_GET[ $function_name.'_nonce' ] , 'run_'.$function_name ) ){

                    wp_die(
                        '<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
                        '<p>' . __( 'The nonce supplied in order to run this update function is invalid', 'woothemes-sensei' ) . '</p>',
                        403
                    );

                }

                // Dynamic function call
                if (method_exists($this, $function_name)) {

                    $done_processing = call_user_func_array(array($this, $function_name), array(50, $n));

                } elseif ($this->function_in_whitelist($function_name)) {

                    $done_processing = call_user_func_array($function_name, array(50, $n));

                } else {

                    _doing_it_wrong( esc_html( $function_name) , 'Is not a valid Sensei updater function', 'Sensei 1.9.0');
                    return;

                } // End If Statement

                // Add to functions list get args
                if ('' == $functions_list) {
                    $functions_list .= $function_name;
                } else {
                    $functions_list .= '+' . $function_name;
                } // End If Statement

                $this->set_update_run($function_name);

            } // End For Loop

        } // End If Statement

        if (!$done_processing) { ?>

            <h3><?php _e('Processing Updates...', 'woothemes-sensei'); ?></h3>

            <p>

                <?php _e( "If your browser doesn't start loading the next page automatically, click this button:", 'woothemes-sensei' ); ?>

                <?php
                $next_action_url = add_query_arg( array(
                    'page' => 'sensei_updates',
                    'action' => 'update',
                    'n' => $n + 50,
                    'functions' => array( $functions_list ),
                    $function_name.'_nonce' => wp_create_nonce( 'run_'. $function_name ),
                ), admin_url( 'admin.php' ) );
                ?>

                <a class="button"  href="<?php echo esc_url( $next_action_url ); ?>">

                    <?php _e( 'Next', 'woothemes-sensei' ); ?>

                </a>

            </p>
            <script type='text/javascript'>
                <!--
                function js_sensei_nextpage() {
                    location.href = "<?php echo esc_url_raw(  $next_action_url );?>";
                }
                setTimeout( "js_sensei_nextpage()", 250 );
                //-->
            </script>

        <?php  } else { ?>

            <p><strong><?php _e('Update completed successfully!', 'woothemes-sensei'); ?></strong></p>
            <p>
                <a href="<?php echo admin_url('edit.php?post_type=lesson'); ?>"><?php _e('Create a new lesson', 'woothemes-sensei'); ?></a>
                or <a
                    href="<?php echo admin_url('admin.php?page=sensei_updates'); ?>"><?php _e('run some more updates', 'woothemes-sensei'); ?></a>.
            </p>

        <?php } // End If Statement

        } else { ?>

            <h3><?php _e('Updates', 'woothemes-sensei'); ?></h3>
            <p><?php printf(__('These are updates that have been made available as new Sensei versions have been released. Updates of type %1$sAuto%2$s will run as you update Sensei to the relevant version - other updates need to be run manually and you can do that here.', 'woothemes-sensei'), '<code>', '</code>'); ?></p>

            <div class="updated"><p>
                    <strong><?php _e('Only run these updates if you have been instructed to do so by WooThemes support staff.', 'woothemes-sensei'); ?></strong>
                </p></div>

            <table class="widefat" cellspacing="0" id="update-plugins-table">

                <thead>
                <tr>
                    <th scope="col" class="manage-column"><?php _e('Update', 'woothemes-sensei'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('Type', 'woothemes-sensei'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('Action', 'woothemes-sensei'); ?></th>
                </tr>
                </thead>

                <tfoot>
                <tr>
                    <th scope="col" class="manage-column"><?php _e('Update', 'woothemes-sensei'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('Type', 'woothemes-sensei'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('Action', 'woothemes-sensei'); ?></th>
                </tr>
                </tfoot>

                <tbody class="updates">
                <?php
                // Sort updates with the latest at the top
                uksort($this->updates, array($this, 'sort_updates'));
                $this->updates = array_reverse($this->updates, true);
                $class = 'alternate';
                foreach ($this->updates as $version => $version_updates) {
                    foreach ($version_updates as $type => $updates) {
                        foreach ($updates as $update => $data) {
                            $update_run = $this->has_update_run($update);
                            $product = 'Sensei';
                            if (isset($data['product']) && '' != $data['product']) {
                                $product = $data['product'];
                            } // End If Statement
                            ?>
                            <form method="post" action="admin.php?page=sensei_updates&action=update&n=0"
                                  name="update-sensei" class="upgrade">
                                <tr class="<?php echo $class; ?>">
                                    <td>
                                        <p>
                                            <input type="hidden" name="checked[]" value="<?php echo $update; ?>">
                                            <strong><?php echo $data['title']; ?></strong><br><?php echo $data['desc']; ?>
                                            <br>
                                            <em><?php printf(__('Originally included in %s v%s', 'woothemes-sensei'), $product, $version); ?></em>
                                        </p>
                                    </td>
                                    <?php
                                    $type_label = __('Auto', 'woothemes-sensei');
                                    if ($type != 'auto') {
                                        $type_label = __('Manual', 'woothemes-sensei');
                                    }
                                    ?>
                                    <td><p><?php echo $type_label; ?></p></td>
                                    <td>
                                        <p>
                                            <input onclick="javascript:return confirm('<?php echo addslashes( sprintf( __( 'Are you sure you want to run the \'%s\' update?', 'woothemes-sensei' ), $data['title'] ) ); ?>');"
                                                   id="update-sensei"
                                                   class="button<?php if( ! $update_run ) { echo ' button-primary'; } ?>"
                                                   type="submit"
                                                   value="<?php if( $update_run ) { _e( 'Re-run Update', 'woothemes-sensei' ); } else { _e( 'Run Update', 'woothemes-sensei' ); } ?>"
                                                   name="update">

                                            <?php
                                            $nonce_action = 'run_'.$update;
                                            $nonce_field_name = $update.'_nonce_field';
                                            wp_nonce_field( $nonce_action, $nonce_field_name, false, true );
                                            ?>
                                        </p>
                                    </td>
                                </tr>
                            </form>
                            <?php
                            if ('alternate' == $class) {
                                $class = '';
                            } else {
                                $class = 'alternate';
                            }
                        }
                    }
                }
                ?>
                </tbody>

            </table>

            </div>

            <?php
        } // End If Statement
    } // End sensei_updates_page()

    /**
     * Since 1.9.0
     *
     * A list of safe to execute functions withing the
     * updater context.
     *
     * @param string $function_name
     * @return bool
     */
    public function function_in_whitelist( $function_name ) {

        /**
         * Filters the function whitelist for Sensei_Updates::function_in_whitelist.
         * Allows extensions to add/remove whitelisted functions.
         *
         * @since 1.9.??
         *
         * @param array $function_whitelist
         * @return array
         */
        $function_whitelist = (array)apply_filters( 'sensei_updates_function_whitelist', array(
            'status_changes_convert_questions',
            'status_changes_fix_lessons',
            'status_changes_convert_courses',
            'status_changes_convert_lessons',
            'status_changes_repair_course_statuses',
        ) );

        return in_array( $function_name, $function_whitelist );

    }// end function_in_whitelist

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

		// Only allow admins to run update functions
		if( ! current_user_can( 'manage_options' ) ) {
            return false;
        }

        $this->force_updates();

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

            $this->updates_run = array_unique( $this->updates_run ); // we only need one reference per update
			update_option( Sensei()->token . '-upgrades', $this->updates_run );
			return true;

                                } elseif( $this->function_in_whitelist( $function_name ) ) {

                                    $updated = call_user_func( $function_name );

                                }  // End If Statement

                                if ( $updated ) {
                                    array_push( $this->updates_run, $function_name );
                                } // End If Statement
                            }
                        } // End If Statement
                    } // End For Loop
                } // End If Statement
            } // End For Loop
        } // End For Loop

        $this->updates_run = array_unique( $this->updates_run ); // we only need one reference per update
        update_option( $this->token . '-upgrades', $this->updates_run );

        return true;

	} // End update()

	private function force_updates() {

		if( ! isset( $_GET['page'] ) || 'sensei_updates' != $_GET['page'] ) {

			// Force critical updates if only if lessons already exist
			$skip_forced_updates = false;
			$lesson_posts = wp_count_posts( 'lesson' );
			if( ! isset( $lesson_posts->publish ) || ! $lesson_posts->publish ) {
				$skip_forced_updates = true;
			}

			$use_the_force = false;

			$updates_to_run = array();

			foreach ( $this->updates as $version => $value ) {
				foreach ( $this->updates[$version] as $upgrade_type => $function_to_run ) {
					if ( $upgrade_type == 'forced' ) {
						foreach ( $function_to_run as $function_name => $update_data ) {

							if( $skip_forced_updates ) {
								$this->set_update_run( $function_name );
								continue;
							}

							$update_run = $this->has_update_run( $function_name );

							if( ! $update_run ) {
								$use_the_force = true;
								$updates_to_run[ $function_name ] = $update_data;
							}
						}
					}
				}
			}

			if( $skip_forced_updates ) {
				return;
			}

			if( $use_the_force && 0 < count( $updates_to_run ) ) {

				$update_title = __( 'Important Sensei updates required', 'woothemes-sensei' );

				$update_message = '<h1>' . __( 'Important Sensei upgrades required!', 'woothemes-sensei' ) . '</h1>' . "\n";
				$update_message .= '<p>' . __( 'The latest version of Sensei requires some important database upgrades. In order to run these upgrades you will need to follow the step by step guide below. Your site will not function correctly unless you run these critical updates.', 'woothemes-sensei' ) . '</p>' . "\n";

				$update_message .= '<p><b>' . __( 'To run the upgrades click on each of the links below in the order that they appear.', 'woothemes-sensei' ) . '</b></p>' . "\n";

				$update_message .= '<p>' . __( 'Clicking each link will open up a new window/tab - do not close that window/tab until you see the message \'Update completed successfully\'. Once you see that message you can close the window/tab and start the next upgrade by clicking on the next link in the list.', 'woothemes-sensei' ) . '</p>' . "\n";

				$update_message .= '<p><b>' . __( 'Once all the upgrades have been completed you will be able to use your WordPress site again.', 'woothemes-sensei' ) . '</b></p>' . "\n";

				$update_message .= '<ol>' . "\n";

					foreach( $updates_to_run as $function => $data ) {

						if( ! isset( $data['title'] ) ) {
							break;
						}

						$update_message .= '<li style="margin:5px 0;"><a href="' . admin_url( 'admin.php?page=sensei_updates&action=update&n=0&functions[]=' . $function ) . '" target="_blank">' . $data['title'] . '</a></li>';
					}

				$update_message .= '</ol>' . "\n";

				switch( $version ) {

					case '1.7.0':
						$update_message .= '<p><em>' . sprintf( __( 'Want to know what these upgrades are all about? %1$sFind out more here%2$s.', 'woothemes-sensei' ), '<a href="http://develop.woothemes.com/sensei/2014/12/03/important-information-about-sensei-1-7" target="_blank">', '</a>' ) . '</em></p>' . "\n";
					break;

				}

				wp_die( $update_message, $update_title );
			}
		}
	}

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
        $this->updates_run = array_unique( $this->updates_run ); // we only need one reference per update
		update_option( Sensei()->token . '-upgrades', $this->updates_run );
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
				$role = get_role( $role_key );
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
						'posts_per_page' 		=> -1,
						'post_status'		=> 'publish',
						'suppress_filters' 	=> 0
						);
		$quizzes = get_posts( $args );

		foreach( $quizzes as $quiz ) {
			update_post_meta( $quiz->ID, '_quiz_grade_type', 'auto' );
			update_post_meta( $quiz->ID, '_quiz_grade_type_disabled', '' );
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
		$args = array(	'post_type' 		=> 'question',
						'posts_per_page' 		=> -1,
						'post_status'		=> 'publish',
						'suppress_filters' 	=> 0
						);
		$questions = get_posts( $args );

		$already_run = true;
		foreach( $questions as $question ) {
			if( $already_run ) {
				$terms = wp_get_post_terms( $question->ID, 'question-type' );
				if( is_array( $terms ) && count( $terms ) > 0 ) {
					break;
				}
			}
			$already_run = false;
			wp_set_post_terms( $question->ID, array( 'multiple-choice' ), 'question-type' );
		}

	} // End set_default_question_type

	/**
	 * Update question answers to use new data structure
	 *
	 * @since 1.3.0
	 * @access public
	 * @return boolean
	 */
	public function update_question_answer_data( $n = 50, $offset = 0 ) {

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
						'posts_per_page' 		=> $n,
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
				$comments = Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $quiz_id, 'type' => 'sensei_quiz_answers' ), true  );
				// Need to always return an array, even with only 1 item
				if ( !is_array($comments) ) {
					$comments = array( $comments );
				}
				foreach ( $comments as $comment ) {
					$user_id = $comment->user_id;
					$content = maybe_unserialize( base64_decode( $comment->comment_content ) );
					$old_user_answers[ $quiz_id ][ $user_id ] = $content;
				}

				// Get correct answers
				$questions = Sensei_Utils::sensei_get_quiz_questions( $quiz_id );
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
						Sensei_Utils::sensei_grade_question_auto( $question_id, '', $user_answer, $user_id );
					}
					$lesson_id = get_post_meta( $quiz_id, '_quiz_lesson', true );
					Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );
					Sensei_Utils::sensei_save_quiz_answers( $new_user_answers, $user_id );
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
	 * @return boolean
	 */
	public function update_question_grade_points() {
		$args = array(	'post_type' 		=> 'question',
						'posts_per_page' 		=> -1,
						'post_status'		=> 'publish',
						'suppress_filters' 	=> 0
						);
		$questions = get_posts( $args );

		foreach( $questions as $question ) {
			update_post_meta( $question->ID, '_question_grade', '1' );
		}
		return true;
	} // End update_question_grade_points

	/**
	 * Convert all essay paste questions into multi-line for v1.5.0
	 *
	 * @since  1.5.0
	 * @return boolean
	 */
	public function convert_essay_paste_questions() {
		$args = array(	'post_type' 		=> 'question',
						'posts_per_page' 		=> -1,
						'post_status'		=> 'publish',
						'tax_query'			=> array(
							array(
								'taxonomy'		=> 'question-type',
								'terms'			=> 'essay-paste',
								'field'			=> 'slug'
							)
						),
						'suppress_filters' 	=> 0
						);
		$questions = get_posts( $args );

		foreach( $questions as $question ) {
			wp_set_object_terms( $question->ID, 'multi-line', 'question-type', false );

			$quiz_id = get_post_meta( $question->ID, '_quiz_id', true );
			if( 0 < intval( $quiz_id ) ) {
				add_post_meta( $question->ID, '_quiz_question_order' . $quiz_id, $quiz_id . '0000', true );
			}
		}
		return true;
	} // End convert_essay_paste_questions

	/**
	 * Set all quizzes to have a random question order
	 *
	 * @since  1.5.0
	 * @return boolean
	 */
	public function set_random_question_order( $n = 50, $offset = 0 ) {

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
						'post_status'		=> 'any',
						'posts_per_page' 		=> $n,
						'offset'			=> $offset,
						'suppress_filters' 	=> 0
						);
		$quizzes = get_posts( $args );

		foreach( $quizzes as $quiz ) {
			update_post_meta( $quiz->ID, '_random_question_order', 'yes' );
		}

		if ( $current_page == $total_pages ) {
			return true;
		} else {
			return false;
		} // End If Statement

	} // End set_random_question_order()

	/**
	 * Set all quizzes to display all questions
	 *
	 * @since  1.5.0
	 * @return boolean
	 */
	public function set_default_show_question_count( $n = 50, $offset = 0 ) {

		$args = array(	'post_type' 		=> 'quiz',
						'post_status'		=> 'any',
						'posts_per_page' 		=> $n,
						'offset'			=> $offset,
						'meta_key'			=> '_show_questions',
						'suppress_filters' 	=> 0
						);
		$quizzes = get_posts( $args );

		$total_quizzes = count( $quizzes );

		if( 0 == intval( $total_quizzes ) ) {
			return true;
		}

		foreach( $quizzes as $quiz ) {
			delete_post_meta( $quiz->ID, '_show_questions' );
		}

		$total_pages = intval( $total_quizzes / $n );

		// Calculate if this is the last page
		if ( 0 == $offset ) {
			$current_page = 1;
		} else {
			$current_page = intval( $offset / $n );
		} // End If Statement

		if ( $current_page == $total_pages ) {
			return true;
		} else {
			return false;
		} // End If Statement

	}

	public function remove_deleted_user_activity( $n = 50, $offset = 0 ) {

		$all_activity = get_comments( array( 'status' => 'approve' ) );
		$activity_count = array();
		foreach( $all_activity as $activity ) {
			if( '' == $activity->comment_type ) continue;
			if( strpos( 'sensei_', $activity->comment_type ) != 0 ) continue;
			if( 0 == $activity->user_id ) continue;
			$activity_count[] = $activity->comment_ID;
		}

		$args = array(
			'number' => $n,
			'offset' => $offset,
			'status' => 'approve'
		);

		$activities = get_comments( $args );

		foreach( $activities as $activity ) {
			if( '' == $activity->comment_type ) continue;
			if( strpos( 'sensei_', $activity->comment_type ) != 0 ) continue;
			if( 0 == $activity->user_id ) continue;

			$user_exists = get_userdata( $activity->user_id );

			if( ! $user_exists ) {
				wp_delete_comment( intval( $activity->comment_ID ), true );
				wp_cache_flush();
			}
		}

		$total_activities = count( $activity_count );

		$total_pages = intval( $total_activities / $n );

		// Calculate if this is the last page
		if ( 0 == $offset ) {
			$current_page = 1;
		} else {
			$current_page = intval( $offset / $n );
		} // End If Statement

		if ( $current_page >= $total_pages ) {
			return true;
		} else {
			return false;
		} // End If Statement

	}

	public function add_teacher_role() {
		add_role( 'teacher', __( 'Teacher', 'woothemes-sensei' ), array( 'read' => true, 'manage_sensei_grades' => true ) );
		return true;
	}

	public function add_sensei_caps() {
		$role = get_role( 'administrator' );

		if( ! is_null( $role ) ) {
			$role->add_cap( 'manage_sensei' );
			$role->add_cap( 'manage_sensei_grades' );
		}

		return true;
	}

	public function restructure_question_meta() {
		$args = array(
			'post_type' 		=> 'question',
			'posts_per_page' 	=> -1,
			'post_status'		=> 'any',
			'suppress_filters' 	=> 0
		);

		$questions = get_posts( $args );

		foreach( $questions as $question ) {

			if( ! isset( $question->ID ) ) continue;

			$quiz_id = get_post_meta( $question->ID, '_quiz_id', true );

			$question_order = get_post_meta( $question->ID, '_quiz_question_order', true );
			update_post_meta( $question->ID, '_quiz_question_order' . $quiz_id, $question_order );

		}

		return true;
	}

	public function update_quiz_settings() {

		$settings = get_option( 'woothemes-sensei-settings', array() );

		$lesson_completion = false;
		if( isset( $settings['lesson_completion'] ) ) {
			$lesson_completion = $settings['lesson_completion'];
		}

		$reset_quiz_allowed = false;
		if( isset( $settings['quiz_reset_allowed'] ) ) {
			$reset_quiz_allowed = $settings['quiz_reset_allowed'];
		}

		$args = array(
			'post_type' 		=> 'quiz',
			'posts_per_page' 	=> -1,
			'post_status'		=> 'any',
			'suppress_filters' 	=> 0
		);

		$quizzes = get_posts( $args );

		foreach( $quizzes as $quiz ) {

			if( ! isset( $quiz->ID ) ) continue;

			if( isset( $lesson_completion ) && 'passed' == $lesson_completion ) {
				update_post_meta( $quiz->ID, '_pass_required', 'on' );
			} else {
				update_post_meta( $quiz->ID, '_quiz_passmark', 0 );
			}

			if( isset( $reset_quiz_allowed ) && $reset_quiz_allowed ) {
				update_post_meta( $quiz->ID, '_enable_quiz_reset', 'on' );
			}
		}

		return true;
	}

	public function reset_lesson_order_meta() {
		$args = array(
			'post_type' 		=> 'lesson',
			'posts_per_page' 	=> -1,
			'post_status'		=> 'any',
			'suppress_filters' 	=> 0
		);

		$lessons = get_posts( $args );

		foreach( $lessons as $lesson ) {

			if( ! isset( $lesson->ID ) ) continue;

			$course_id = get_post_meta( $lesson->ID, '_lesson_course', true);

			if( $course_id ) {
				update_post_meta( $lesson->ID, '_order_' . $course_id, 0 );
			}

            $module = Sensei()->modules->get_lesson_module( $lesson->ID );

            if( $module ) {
                update_post_meta( $lesson->ID, '_order_module_' . $module->term_id, 0 );
            }

		}

		return true;
	}

	public function add_editor_caps() {
		$role = get_role( 'editor' );

		if( ! is_null( $role ) ) {
			$role->add_cap( 'manage_sensei_grades' );
		}

		return true;
	}

	/**
	 * Updates all gap fill questions, converting the | separator to || matching the changes in code. Using || allows the use of | within the pre, gap or post field.
	 *
	 * @global type $wpdb
	 * @return boolean
	 */
	public function update_question_gap_fill_separators() {
		global $wpdb;

		$sql = "UPDATE $wpdb->postmeta AS m, $wpdb->term_relationships AS tr, $wpdb->term_taxonomy AS tt, $wpdb->terms AS t SET m.meta_value = replace(m.meta_value, '|', '||')
					WHERE m.meta_key = '_question_right_answer' AND m.meta_value LIKE '%|%' AND m.meta_value NOT LIKE '%||%'
						AND m.post_id = tr.object_id AND tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.term_id = t.term_id
						AND tt.taxonomy = 'question-type' AND t.slug = 'gap-fill'";
		$wpdb->query( $sql );

		return true;
	}

	public function update_quiz_lesson_relationship( $n = 50, $offset = 0 ) {
		$count_object = wp_count_posts( 'quiz' );

		$count_published = 0;
		foreach ( $count_object AS $status => $count ) {
			$count_published += $count;
		}

		// Calculate if this is the last page
		if ( 0 == $offset ) {
			$current_page = 1;
		} else {
			$current_page = intval( $offset / $n );
		}
		$total_pages = ceil( $count_published / $n );

		$args = array(
			'post_type' => 'quiz',
			'posts_per_page' => $n,
			'offset' => $offset,
			'post_status' => 'any'
		);

		$quizzes = get_posts( $args );

		foreach( $quizzes as $quiz ) {

			if( ! isset( $quiz->ID ) || 0 != $quiz->post_parent ) continue;

			$lesson_id = get_post_meta( $quiz->ID, '_quiz_lesson', true );

			if( empty( $lesson_id ) ) continue;

			$data = array(
				'ID' => $quiz->ID,
				'post_parent' => $lesson_id,
			);
			wp_update_post( $data );

			update_post_meta( $lesson_id, '_lesson_quiz', $quiz->ID );
		}

		if ( $current_page == $total_pages || 0 == $total_pages ) {
			return true;
		} else {
			return false;
		}
	}

	function status_changes_fix_lessons( $n = 50, $offset = 0 ) {
		global $wpdb;

		$count_object = wp_count_posts( 'lesson' );
		$count_published = 0;
		foreach ( $count_object AS $status => $count ) {
			$count_published += $count;
		}

		if ( 0 == $count_published ) {
			return true;
		}

		// Calculate if this is the last page
		if ( 0 == $offset ) {
			$current_page = 1;
		} else {
			$current_page = intval( $offset / $n );
		}
		$total_pages = ceil( $count_published / $n );

		// Get all Lessons with (and without) Quizzes...
		$args = array(
			'post_type' => 'lesson',
			'post_status' => 'any',
			'posts_per_page' => $n,
			'offset' => $offset,
			'fields' => 'ids'
		);
		$lesson_ids = get_posts( $args );

		// ...get all Quiz IDs for the above Lessons
		$id_list = join( ',', $lesson_ids );
		$meta_list = $wpdb->get_results( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_quiz_lesson' AND meta_value IN ($id_list)", ARRAY_A );
		$lesson_quiz_ids = array();
		if ( !empty($meta_list) ) {
			foreach ( $meta_list as $metarow ) {
				$lesson_id = $metarow['meta_value'];
				$quiz_id = $metarow['post_id'];
				$lesson_quiz_ids[ $lesson_id ] = $quiz_id;
			}
		}

		// ...check all Quiz IDs for questions
		$id_list = join( ',', array_values($lesson_quiz_ids) );
		$meta_list = $wpdb->get_results( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_quiz_id' AND meta_value IN ($id_list)", ARRAY_A );
		$lesson_quiz_ids_with_questions = array();
		if ( !empty($meta_list) ) {
			foreach ( $meta_list as $metarow ) {
				$quiz_id = $metarow['meta_value'];
				$lesson_quiz_ids_with_questions[] = $quiz_id;
			}
		}

		// For each quiz check there are questions, if not remove the corresponding meta keys from Quizzes and Lessons
		// if there are questions on the quiz add the corresponding meta keys to Quizzes and Lessons
		$d_count = $a_count =0;
		foreach ( $lesson_quiz_ids AS $lesson_id => $quiz_id ) {
			if ( !in_array( $quiz_id, $lesson_quiz_ids_with_questions ) ) {

				// Quiz has no questions, drop the corresponding data
				delete_post_meta( $quiz_id, '_pass_required' );
				delete_post_meta( $quiz_id, '_quiz_passmark' );
				delete_post_meta( $lesson_id, '_quiz_has_questions' );
				$d_count++;
			}
			else if ( in_array( $quiz_id, $lesson_quiz_ids_with_questions ) ) {

				// Quiz has no questions, drop the corresponding data
				update_post_meta( $lesson_id, '_quiz_has_questions', true );
				$a_count++;
			}
		}

		if ( $current_page == $total_pages ) {
			return true;
		} else {
			return false;
		}
	}

	function status_changes_convert_lessons( $n = 50, $offset = 0 ) {
		global $wpdb;

		wp_defer_comment_counting( true );

		$user_count_result = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->users " );

		if ( 0 == $user_count_result ) {
			return true;
		}

		if ( 0 == $offset ) {
			$current_page = 1;
		} else {
			$current_page = intval( $offset / $n );
		}

		$total_pages = ceil( $user_count_result / $n );

		// Get all Lessons with Quizzes...
		$args = array(
			'post_type' => 'lesson',
			'post_status' => 'any',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => '_quiz_has_questions',
					'value' => 1,
				),
			),
			'fields' => 'ids'
		);
		$lesson_ids_with_quizzes = get_posts( $args );
		// ...get all Quiz IDs for the above Lessons
		$id_list = join( ',', $lesson_ids_with_quizzes );
		$meta_list = $wpdb->get_results( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_quiz_lesson' AND meta_value IN ($id_list)", ARRAY_A );
		$lesson_quiz_ids = array();
		if ( !empty($meta_list) ) {
			foreach ( $meta_list as $metarow ) {
				$lesson_id = $metarow['meta_value'];
				$quiz_id = $metarow['post_id'];
				$lesson_quiz_ids[ $lesson_id ] = $quiz_id;
			}
		}

		// ...get all Pass Required & Passmarks for the above Lesson/Quizzes
		$id_list = join( ',', array_values($lesson_quiz_ids) );
		$meta_list = $wpdb->get_results( "SELECT post_id, meta_key, meta_value FROM $wpdb->postmeta WHERE ( meta_key = '_pass_required' OR meta_key = '_quiz_passmark' ) AND post_id IN ($id_list)", ARRAY_A );
		$quizzes_pass_required = $quizzes_passmarks = array();
		if ( !empty($meta_list) ) {
			foreach ( $meta_list as $metarow ) {
				if ( !empty($metarow['meta_value']) ) {
					$quiz_id = $metarow['post_id'];
					$key = $metarow['meta_key'];
					$value = $metarow['meta_value'];
					if ( '_pass_required' == $key ) {
						$quizzes_pass_required[ $quiz_id ] = $value;
					}
					if ( '_quiz_passmark' == $key ) {
						$quizzes_passmarks[ $quiz_id ] = $value;
					}
				}
			}
		}

		$users_sql = "SELECT ID FROM $wpdb->users ORDER BY ID ASC LIMIT %d OFFSET %d";
		$start_sql = "SELECT comment_post_ID, comment_date FROM $wpdb->comments WHERE comment_type = 'sensei_lesson_start' AND user_id = %d GROUP BY comment_post_ID ";
		$end_sql = "SELECT comment_post_ID, comment_date FROM $wpdb->comments WHERE comment_type = 'sensei_lesson_end' AND user_id = %d GROUP BY comment_post_ID ";
		$grade_sql = "SELECT comment_post_ID, comment_content FROM $wpdb->comments WHERE comment_type = 'sensei_quiz_grade' AND user_id = %d GROUP BY comment_post_ID ORDER BY comment_content DESC ";
		$answers_sql = "SELECT comment_post_ID, comment_content FROM $wpdb->comments WHERE comment_type = 'sensei_quiz_asked' AND user_id = %d GROUP BY comment_post_ID ORDER BY comment_date_gmt DESC ";
		$check_existing_sql = "SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = %d AND user_id = %d AND comment_type = 'sensei_lesson_status' ";

		// $per_page users at a time, could be batch run via an admin ajax command, 1 user at a time?
		$user_ids = $wpdb->get_col( $wpdb->prepare($users_sql, $n, $offset) );

		foreach ( $user_ids AS $user_id ) {

			$lesson_ends = $lesson_grades = $lesson_answers = array();

			// Pre-process the lesson ends
			$_lesson_ends = $wpdb->get_results( $wpdb->prepare($end_sql, $user_id), ARRAY_A );
			foreach ( $_lesson_ends as $lesson_end ) {
				// This will overwrite existing entries with the newer ones
				$lesson_ends[ $lesson_end['comment_post_ID'] ] = $lesson_end['comment_date'];
			}
			unset( $_lesson_ends );

			// Pre-process the lesson grades
			$_lesson_grades = $wpdb->get_results( $wpdb->prepare($grade_sql, $user_id), ARRAY_A );
			foreach ( $_lesson_grades as $lesson_grade ) {
				// This will overwrite existing entries with the newer ones (assuming the grade is higher)
				if ( empty($lesson_grades[ $lesson_grade['comment_post_ID'] ]) || $lesson_grades[ $lesson_grade['comment_post_ID'] ] < $lesson_grade['comment_content'] ) {
					$lesson_grades[ $lesson_grade['comment_post_ID'] ] = $lesson_grade['comment_content'];
				}
			}
			unset( $_lesson_grades );

			// Pre-process the lesson answers
			$_lesson_answers = $wpdb->get_results( $wpdb->prepare($answers_sql, $user_id), ARRAY_A );
			foreach ( $_lesson_answers as $lesson_answer ) {
				// This will overwrite existing entries with the newer ones
				$lesson_answers[ $lesson_answer['comment_post_ID'] ] = $lesson_answer['comment_content'];
			}
			unset( $_lesson_answers );

			// Grab all the lesson starts for the user
			$lesson_starts = $wpdb->get_results( $wpdb->prepare($start_sql, $user_id), ARRAY_A );
			foreach ( $lesson_starts as $lesson_log ) {

				$lesson_id = $lesson_log['comment_post_ID'];

				// Default status
				$status = 'in-progress';

				$status_date = $lesson_log['comment_date'];
				// Additional data for the lesson
				$meta_data = array(
					'start' => $status_date,
				);
				// Check if there is a lesson end
				if ( !empty($lesson_ends[$lesson_id]) ) {
					$status_date = $lesson_ends[$lesson_id];
					// Check lesson has quiz
					if ( !empty( $lesson_quiz_ids[$lesson_id] ) ) {
						// Check for the quiz answers
						if ( !empty($lesson_answers[$quiz_id]) ) {
							$meta_data['questions_asked'] = $lesson_answers[$quiz_id];
						}
						// Check if there is a quiz grade
						$quiz_id = $lesson_quiz_ids[$lesson_id];
						if ( !empty($lesson_grades[$quiz_id]) ) {
							$meta_data['grade'] = $quiz_grade = $lesson_grades[$quiz_id];
							// Check if the user has to get the passmark and has or not
							if ( !empty( $quizzes_pass_required[$quiz_id] ) && $quizzes_passmarks[$quiz_id] <= $quiz_grade ) {
								$status = 'passed';
							}
							elseif ( !empty( $quizzes_pass_required[$quiz_id] ) && $quizzes_passmarks[$quiz_id] > $quiz_grade ) {
								$status = 'failed';
							}
							else {
								$status = 'graded';
							}
						}
						else {
							// If the lesson has a quiz, but the user doesn't have a grade, it's not yet been graded
							$status = 'ungraded';
						}
					}
					else {
						// Lesson has no quiz, so it can only ever be this status
						$status = 'complete';
					}
				}
				$data = array(
					// This is the minimum data needed, the db defaults handle the rest
						'comment_post_ID' => $lesson_id,
						'comment_approved' => $status,
						'comment_type' => 'sensei_lesson_status',
						'comment_date' => $status_date,
						'user_id' => $user_id,
						'comment_date_gmt' => get_gmt_from_date($status_date),
						'comment_author' => '',
					);
				// Check it doesn't already exist
				$sql = $wpdb->prepare( $check_existing_sql, $lesson_id, $user_id );
				$comment_ID = $wpdb->get_var( $sql );
				if ( !$comment_ID ) {
					// Bypassing WP wp_insert_comment( $data ), so no actions/filters are run
					$wpdb->insert($wpdb->comments, $data);
					$comment_ID = (int) $wpdb->insert_id;

					if ( $comment_ID && !empty($meta_data) ) {
						foreach ( $meta_data as $key => $value ) {
							// Bypassing WP add_comment_meta(() so no actions/filters are run
							if ( $wpdb->get_var( $wpdb->prepare(
									"SELECT COUNT(*) FROM $wpdb->commentmeta WHERE comment_id = %d AND meta_key = %s ",
									$comment_ID, $key ) ) ) {
									continue; // Found the meta data already
							}
							$result = $wpdb->insert( $wpdb->commentmeta, array(
								'comment_id' => $comment_ID,
								'meta_key' => $key,
								'meta_value' => $value
							) );
						}
					}
				}
			}
		}
		$wpdb->flush();

		if ( $current_page == $total_pages ) {
			return true;
		} else {
			return false;
		}
	}

	function status_changes_convert_courses( $n = 50, $offset = 0 ) {
		global $wpdb;

		wp_defer_comment_counting( true );

		$user_count_result = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->users " );

		if ( 0 == $user_count_result ) {
			return true;
		}

		if ( 0 == $offset ) {
			$current_page = 1;
		} else {
			$current_page = intval( $offset / $n );
		}

		$total_pages = ceil( $user_count_result / $n );

		// Get all Lesson => Course relationships
		$meta_list = $wpdb->get_results( "SELECT $wpdb->postmeta.post_id, $wpdb->postmeta.meta_value FROM $wpdb->postmeta INNER JOIN $wpdb->posts ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) WHERE $wpdb->posts.post_type = 'lesson' AND $wpdb->postmeta.meta_key = '_lesson_course'", ARRAY_A );
		$course_lesson_ids = array();
		if ( !empty($meta_list) ) {
			foreach ( $meta_list as $metarow ) {
				$lesson_id = $metarow['post_id'];
				$course_id = $metarow['meta_value'];
				$course_lesson_ids[ $course_id ][] = $lesson_id;
			}
		}

		$users_sql = "SELECT ID FROM $wpdb->users ORDER BY ID ASC LIMIT %d OFFSET %d";
		$start_sql = "SELECT comment_post_ID, comment_date FROM $wpdb->comments WHERE comment_type = 'sensei_course_start' AND user_id = %d GROUP BY comment_post_ID ";
		$lessons_sql = "SELECT comment_approved AS status, comment_date FROM $wpdb->comments WHERE comment_type = 'sensei_lesson_status' AND user_id = %d AND comment_post_ID IN ( %s ) GROUP BY comment_post_ID ORDER BY comment_date_gmt DESC ";
		$check_existing_sql = "SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = %d AND user_id = %d AND comment_type = 'sensei_course_status' ";

		// $per_page users at a time, could be batch run via an admin ajax command, 1 user at a time?
		$user_ids = $wpdb->get_col( $wpdb->prepare($users_sql, $n, $offset) );

		foreach ( $user_ids AS $user_id ) {

			// Grab all the course starts for the user
			$course_starts = $wpdb->get_results( $wpdb->prepare($start_sql, $user_id), ARRAY_A );
			foreach ( $course_starts as $course_log ) {

				$course_id = $course_log['comment_post_ID'];

				// Default status
				$status = 'complete';

				$status_date = $course_log['comment_date'];
				// Additional data for the course
				$meta_data = array(
					'start' => $status_date,
					'complete' => 0,
					'percent' => 0,
				);
				// Check if the course has lessons
				if ( !empty( $course_lesson_ids[$course_id] ) ) {

					$lessons_completed = 0;
					$total_lessons = count( $course_lesson_ids[ $course_id ] );

					// Don't use prepare as we need to provide the id join
					$sql = sprintf($lessons_sql, $user_id, join(', ', $course_lesson_ids[ $course_id ]) );
					// Get all lesson statuses for this Courses' lessons
					$lesson_statuses = $wpdb->get_results( $sql, ARRAY_A );
					// Not enough lesson statuses, thus cannot be complete
					if ( $total_lessons > count($lesson_statuses) ) {
						$status = 'in-progress';
					}
					// Count each lesson to work out the overall percentage
					foreach ( $lesson_statuses as $lesson_status ) {
						$status_date = $lesson_status['comment_date'];
						switch ( $lesson_status['status'] ) {
							case 'complete': // Lesson has no quiz/questions
							case 'graded': // Lesson has quiz, but it's not important what the grade was
							case 'passed':
								$lessons_completed++;
								break;

							case 'in-progress':
							case 'ungraded': // Lesson has quiz, but it hasn't been graded
							case 'failed': // User failed the passmark on the lesson/quiz
								$status = 'in-progress';
								break;
						}
					}
					$meta_data['complete'] = $lessons_completed;
					$meta_data['percent'] = Sensei_Utils::quotient_as_absolute_rounded_percentage( $lessons_completed, $total_lessons, 0 );
				}
				else {
					// Course has no lessons, therefore cannot be 'complete'
					$status = 'in-progress';
				}
				$data = array(
					// This is the minimum data needed, the db defaults handle the rest
						'comment_post_ID' => $course_id,
						'comment_approved' => $status,
						'comment_type' => 'sensei_course_status',
						'comment_date' => $status_date,
						'user_id' => $user_id,
						'comment_date_gmt' => get_gmt_from_date($status_date),
						'comment_author' => '',
					);
				// Check it doesn't already exist
				$sql = $wpdb->prepare( $check_existing_sql, $course_id, $user_id );
				$comment_ID = $wpdb->get_var( $sql );
				if ( !$comment_ID ) {
					// Bypassing WP wp_insert_comment( $data ), so no actions/filters are run
					$wpdb->insert($wpdb->comments, $data);
					$comment_ID = (int) $wpdb->insert_id;

					if ( $comment_ID && !empty($meta_data) ) {
						foreach ( $meta_data as $key => $value ) {
							// Bypassing WP wp_insert_comment( $data ), so no actions/filters are run
							if ( $wpdb->get_var( $wpdb->prepare(
									"SELECT COUNT(*) FROM $wpdb->commentmeta WHERE comment_id = %d AND meta_key = %s ",
									$comment_ID, $key ) ) ) {
									continue; // Found the meta data already
							}
							$result = $wpdb->insert( $wpdb->commentmeta, array(
								'comment_id' => $comment_ID,
								'meta_key' => $key,
								'meta_value' => $value
							) );
						}
					}
				}
			}
		}
		$wpdb->flush();

		if ( $current_page == $total_pages ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Force the re-calculation of all Course statuses working from all Lesson statuses
	 *
	 * @global type $woothemes_sensei
	 * @global type $wpdb
	 * @param type $n
	 * @param type $offset
	 * @return boolean
	 */
	function status_changes_repair_course_statuses( $n = 50, $offset = 0 ) {
		global $wpdb;

		$count_object = wp_count_posts( 'lesson' );
		$count_published = $count_object->publish;

		if ( 0 == $count_published ) {
			return true;
		}

		// Calculate if this is the last page
		if ( 0 == $offset ) {
			$current_page = 1;
		} else {
			$current_page = intval( $offset / $n );
		}
		$total_pages = ceil( $count_published / $n );

		$course_lesson_ids = $lesson_user_statuses = array();

		// Get all Lesson => Course relationships
		$meta_list = $wpdb->get_results( "SELECT $wpdb->postmeta.post_id, $wpdb->postmeta.meta_value FROM $wpdb->postmeta INNER JOIN $wpdb->posts ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) WHERE $wpdb->posts.post_type = 'lesson' AND $wpdb->postmeta.meta_key = '_lesson_course' LIMIT $n OFFSET $offset ", ARRAY_A );
		if ( !empty($meta_list) ) {
			foreach ( $meta_list as $metarow ) {
				$lesson_id = $metarow['post_id'];
				$course_id = $metarow['meta_value'];
				$course_lesson_ids[ $course_id ][] = $lesson_id;
			}
		}

		// Get all Lesson => Course relationships
		$status_list = $wpdb->get_results( "SELECT user_id, comment_post_ID, comment_approved FROM $wpdb->comments WHERE comment_type = 'sensei_lesson_status' GROUP BY user_id, comment_post_ID ", ARRAY_A );
		if ( !empty($status_list) ) {
			foreach ( $status_list as $status ) {
				$lesson_user_statuses[ $status['comment_post_ID'] ][ $status['user_id'] ] = $status['comment_approved'];
			}
		}

		$course_completion = Sensei()->settings->settings[ 'course_completion' ];

		$per_page = 40;
		$comment_id_offset = $count = 0;

		$course_sql = "SELECT * FROM $wpdb->comments WHERE comment_type = 'sensei_course_status' AND comment_ID > %d LIMIT $per_page";
		// $per_page users at a time
		while ( $course_statuses = $wpdb->get_results( $wpdb->prepare($course_sql, $comment_id_offset) ) ) {

			foreach ( $course_statuses AS $course_status ) {
				$user_id = $course_status->user_id;
				$course_id = $course_status->comment_post_ID;
				$total_lessons = count( $course_lesson_ids[ $course_id ] );
				if ( $total_lessons <= 0 ) {
					$total_lessons = 1; // Fix division of zero error, some courses have no lessons
				}
				$lessons_completed = 0;
				$status = 'in-progress';

				// Some Courses have no lessons... (can they ever be complete?)
				if ( !empty($course_lesson_ids[ $course_id ]) ) {
					foreach( $course_lesson_ids[ $course_id ] AS $lesson_id ) {
						$lesson_status = $lesson_user_statuses[ $lesson_id ][ $user_id ];
						// If lessons are complete without needing quizzes to be passed
						if ( 'passed' != $course_completion ) {
							switch ( $lesson_status ) {
								// A user cannot 'complete' a course if a lesson...
								case 'in-progress': // ...is still in progress
								case 'ungraded': // ...hasn't yet been graded
									break;

								default:
									$lessons_completed++;
									break;
							}
						}
						else {
							switch ( $lesson_status ) {
								case 'complete': // Lesson has no quiz/questions
								case 'graded': // Lesson has quiz, but it's not important what the grade was
								case 'passed': // Lesson has quiz and the user passed
									$lessons_completed++;
									break;

								// A user cannot 'complete' a course if on a lesson...
								case 'failed': // ...a user failed the passmark on a quiz
								default:
									break;
							}
						}
					} // Each lesson
				} // Check for lessons
				if ( $lessons_completed == $total_lessons ) {
					$status = 'complete';
				}
				// update the overall percentage of the course lessons complete (or graded) compared to 'in-progress' regardless of the above
				$metadata = array(
					'complete' => $lessons_completed,
					'percent' => Sensei_Utils::quotient_as_absolute_rounded_number( $lessons_completed, $total_lessons, 0 )
				);
				Sensei_Utils::update_course_status( $user_id, $course_id, $status, $metadata );
				$count++;

			} // per course status
			$comment_id_offset = $course_status->comment_ID;
		} // all course statuses

		if ( $current_page == $total_pages ) {
			return true;
		} else {
			return false;
		}
	}

	function status_changes_convert_questions( $n = 50, $offset = 0 ) {
		global $wpdb;

		wp_defer_comment_counting( true );

		$user_count_result = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->users " );

		if ( 0 == $user_count_result ) {
			return true;
		}

		// Calculate if this is the last page
		if ( 0 == $offset ) {
			$current_page = 1;
		} else {
			$current_page = intval( $offset / $n );
		}

		$total_pages = ceil( $user_count_result / $n );

		$users_sql = "SELECT ID FROM $wpdb->users ORDER BY ID ASC LIMIT %d OFFSET %d";
		$answers_sql = "SELECT * FROM $wpdb->comments WHERE comment_type = 'sensei_user_answer' AND user_id = %d GROUP BY comment_post_ID ";
		$grades_sql = "SELECT comment_post_ID, comment_content FROM $wpdb->comments WHERE comment_type = 'sensei_user_grade' AND user_id = %d GROUP BY comment_post_ID ";
		$notes_sql = "SELECT comment_post_ID, comment_content FROM $wpdb->comments WHERE comment_type = 'sensei_answer_notes' AND user_id = %d GROUP BY comment_post_ID ";

		$user_ids = $wpdb->get_col( $wpdb->prepare($users_sql, $n, $offset) );

		foreach ( $user_ids AS $user_id ) {

			$answer_grades = $answer_notes = array();

			// Pre-process the answer grades
			$_answer_grades = $wpdb->get_results( $wpdb->prepare($grades_sql, $user_id), ARRAY_A );
			foreach ( $_answer_grades as $answer_grade ) {
				// This will overwrite existing entries with the newer ones
				$answer_grades[ $answer_grade['comment_post_ID'] ] = $answer_grade['comment_content'];
			}
			unset( $_answer_grades );

			// Pre-process the answer notes
			$_answer_notes = $wpdb->get_results( $wpdb->prepare($notes_sql, $user_id), ARRAY_A );
			foreach ( $_answer_notes as $answer_note ) {
				// This will overwrite existing entries with the newer ones
				$answer_notes[ $answer_note['comment_post_ID'] ] = $answer_note['comment_content'];
			}
			unset( $_answer_notes );

			// Grab all the questions for the user
			$sql = $wpdb->prepare($answers_sql, $user_id);
			$answers = $wpdb->get_results( $sql, ARRAY_A );
			foreach ( $answers as $answer ) {

				// Excape data
				$answer = wp_slash($answer);

				$comment_ID = $answer['comment_ID'];

				$meta_data = array();

				// Check if the question has been graded, add as meta
				if ( !empty($answer_grades[ $answer['comment_post_ID'] ]) ) {
					$meta_data['user_grade'] = $answer_grades[ $answer['comment_post_ID'] ];
				}
				// Check if there is an answer note, add as meta
				if ( !empty($answer_notes[ $answer['comment_post_ID'] ]) ) {
					$meta_data['answer_note'] = $answer_notes[ $answer['comment_post_ID'] ];
				}

				// Wipe the unnessary data from the main comment
				$data = array(
						'comment_author' => '',
						'comment_author_email' => '',
						'comment_author_url' => '',
						'comment_author_IP' => '',
						'comment_agent' => '',
					);
				$data = array_merge($answer, $data);

				$rval = $wpdb->update( $wpdb->comments, $data, compact( 'comment_ID' ) );
				if ( $rval ) {
					if ( !empty($meta_data) ) {
						foreach ( $meta_data as $key => $value ) {
							// Bypassing WP wp_insert_comment( $data ), so no actions/filters are run
							if ( $wpdb->get_var( $wpdb->prepare(
									"SELECT COUNT(*) FROM $wpdb->commentmeta WHERE comment_id = %d AND meta_key = %s ",
									$comment_ID, $key ) ) ) {
									continue; // Found the meta data already
							}
							$result = $wpdb->insert( $wpdb->commentmeta, array(
								'comment_id' => $comment_ID,
								'meta_key' => $key,
								'meta_value' => $value
							) );
						}
					}
				}
			}
		}
		$wpdb->flush();

		if ( $current_page == $total_pages ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Updates all pre-existing Sensei activity types with a new status value
	 *
	 * @global type $wpdb
	 * @return boolean
	 */
	public function update_legacy_sensei_comments_status() {
		global $wpdb;

		// Update 'sensei_user_answer' entries to use comment_approved = 'log' so they don't appear in counts
		$wpdb->query( "UPDATE $wpdb->comments SET comment_approved = 'log' WHERE comment_type = 'sensei_user_answer' " );

		// Mark all old Sensei comment types with comment_approved = 'legacy' so they no longer appear in counts, but can be restored if required
		$wpdb->query( "UPDATE $wpdb->comments SET comment_approved = 'legacy' WHERE comment_type IN ('sensei_course_start', 'sensei_course_end', 'sensei_lesson_start', 'sensei_lesson_end', 'sensei_quiz_asked', 'sensei_user_grade', 'sensei_answer_notes', 'sensei_quiz_grade') " );

		return true;
	}

	/**
	 * Update the comment counts for all Courses and Lessons now that sensei comments will no longer be counted.
	 *
	 * @global type $wpdb
	 * @param type $n
	 * @param type $offset
	 * @return boolean
	 */
	public function update_comment_course_lesson_comment_counts( $n = 50, $offset = 0 ) {
		global $wpdb;

		$item_count_result = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type IN ('course', 'lesson') " );

		if ( 0 == $item_count_result ) {
			return true;
		}

		// Calculate if this is the last page
		if ( 0 == $offset ) {
			$current_page = 1;
		} else {
			$current_page = intval( $offset / $n );
		}

		$total_pages = ceil( $item_count_result / $n );

		// Recalculate all counts
		$items = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type IN ('course', 'lesson') LIMIT %d OFFSET %d", $n, $offset ) );
		foreach ( (array) $items as $post ) {
			// Code copied from wp_update_comment_count_now()
			$new = (int) $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_approved = '1'", $post->ID) );
			$wpdb->update( $wpdb->posts, array('comment_count' => $new), array('ID' => $post->ID) );

			clean_post_cache( $post->ID );
		}

		if ( $current_page == $total_pages ) {
			return true;
		} else {
			return false;
		}
	}

	public function remove_legacy_comments () {
		global $wpdb;

		$result = $wpdb->delete( $wpdb->comments, array( 'comment_approved' => 'legacy' ) );

		return true;
	}

	public function index_comment_status_field () {
		global $wpdb;

		$wpdb->query("ALTER TABLE `$wpdb->comments` ADD INDEX `comment_type` ( `comment_type` )");
		$wpdb->query("ALTER TABLE `$wpdb->comments` ADD INDEX `comment_type_user_id` ( `comment_type`, `user_id` )");

		return true;


	}

     /**
     * WooThemes_Sensei_Updates::enhance_teacher_role
     *
     * This runs the update to create the teacher role
     * @access public
     * @since 1.8.0
     * @return bool;
     */
    public  function enhance_teacher_role ( ) {

        require_once('class-sensei-teacher.php');
        $teacher = new Sensei_Teacher();
        $teacher->create_role();
        return true;

    }// end enhance_teacher_role

} // End Class

/**
 * Class WooThemes_Sensei_Updates
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Updates extends Sensei_Updates {}
