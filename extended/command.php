<?php
// This file contains material which is the pre-existing property of Psycle Interactive Limited.
// Copyright (c) 2014 Psycle Interactive. All rights reserved.

// Bail if WP-CLI is not present
if ( !defined( 'WP_CLI' ) ) return;

// This class is shared between the others to centralise the Notification ability
class Imperial_Base_CLI_Command extends WP_CLI_Command {

	protected $notify_emails = 'm.wells@imperial.ac.uk,david.page@psycle.com';
	
	/**
	 * Send notification email
	 * 
	 * @global type $phpmailer
	 * @param type $subject
	 * @param type $message
	 * @param type $headers
	 */
	protected function _notify( $subject, $message, $headers = array() ) {
		$headers = array();
		$headers[] = 'Content-Type: ' . get_option('html_type') . "; charset=\"". get_option('blog_charset') . "\"";

		$to = $this->notify_emails;
		WP_CLI::line( "Emailing $to; $subject; $message;" );
		if ( defined('LIVE_ENVIRONMENT') && LIVE_ENVIRONMENT ) {
			$result = wp_mail( $to, $subject, $message, $headers );
			if ( !$result ) {
				global $phpmailer;
				WP_CLI::error( 'Email sending failed: ' . $phpmailer->ErrorInfo );
			}
		}
	}
} // Imperial_Base_CLI_Command

WP_CLI::add_command( 'imperial', 'Imperial_CLI_Command' );

class Imperial_CLI_Command extends Imperial_Base_CLI_Command {

	/**
	 * Example command
	 *
	 * @subcommand example
	 */
	function example() {
		WP_CLI::success( __('Hi World', 'imperial') );
	}

	/**
	 * Build user programme meta data for Programme Switcher dropdown
	 *
	 * @subcommand update-user-programmes
	 */
	function update_user_programmes( $args, $assoc_args ) {
		global $wpdb, $blog_id;
		$notify = isset( $assoc_args['skip-notify'] ) ? false : true;
		$imp = imperial();
		$programme_titles = array();
		$prog_ids = $imp->get_programmes( null, 'ids' );
		foreach ( $prog_ids as $id ) {
			$programme_titles[ $id ] = get_the_title($id);
		}
		// Restrict to those on this site
		$args = array(
			'meta_key' => $wpdb->get_blog_prefix( $blog_id ) . 'capabilities',
		);
		$cnt = 0;
		$users = get_users( $args );
		foreach ( $users as $u ) {
			$user_programme_meta = $user_prog_ids = false;
			if ( user_can( $u, 'administrator' ) ) {
				// Bypass the get_user_programme_ids() call as we already have what we need
				$user_programme_meta = $programme_titles;
			}
			else {
				$user_prog_ids = $imp->get_user_programme_ids( $u );
			}
			// Assuming there are Programmes for the User
			if ( is_array( $user_prog_ids ) ) {
				foreach ( $user_prog_ids as $p_id ) {
					$user_programme_meta[ $p_id ] = $programme_titles[ $p_id ];
				}
			}
			// Don't check that we have anything to set as we may well want it to be empty
			update_user_meta( $u->ID, 'user_programme_select', $user_programme_meta );
			$cnt++;
		}
		$summary .= "\n" . '=> Memory used: ' . size_format(memory_get_usage()) . "\n" .
			'=> Time taken: ' . timer_stop() . ' secs';
		$message = sprintf( __('%s User Programmes dropdown cache updated, with the message: %s', 'imperial' ), $cnt, $summary );
		if ( $notify ) {
			$this->_notify( 'Automated Update: ' . get_bloginfo('name') . ': User Programmes Cache: SUCCESS ', $message );
		}
		WP_CLI::success( $message );
	}
} // END Imperial_CLI_Command


WP_CLI::add_command( 'imperial courses', 'Imperial_Courses_Feed_Command' );

class Imperial_Courses_Feed_Command extends Imperial_Base_CLI_Command {

	/**
	 * Processes the external XML based DSS feed for any Course with a valid field
	 *
	 * @subcommand update-dss
	 */
	function update_dss( $args, $assoc_args ) {
		$notify = isset( $assoc_args['skip-notify'] ) ? false : true;
		$imp = imperial();
		$inc = reset($imp->includes_dir); // Uses the first item
		require_once( $inc . 'course_feeds.php' );

		$feeds = imperial_course_feeds();
		$courses = $imp->get_courses_by_meta_field( 'course_feed_url' );

		$errors = array();
		$cnt = 0;
		foreach($courses AS $course) {
			$url = get_post_meta( $course->ID, 'course_feed_url', true );
			// Double check
			if ( !empty($url) ) {
				$result = $feeds->process_course_feed( $course->ID, $url );
				if ( is_wp_error( $result ) ) {
					$error = $result->get_error_messages();
					$errors[] = implode( "\n", $error );
				}
				else {
					$cnt++;
				}
			}
		}
		$summary .= "\n" . 
			'=> Memory used: ' . size_format(memory_get_usage()) . "\n" .
			'=> Time taken: ' . timer_stop() . ' secs';
		$status = 'success';
		if ( !empty($errors) ) {
			$status = 'error';
			$summary .= "\n\nErrors:\n " . implode("\n ", $errors);
		}
		$message = sprintf( __('%s Courses have successfully updated via their DSS feed, with the message: %s', 'imperial' ), $cnt, $summary );
		if ( $notify ) {
			$this->_notify( 'Automated Update: ' . get_bloginfo('name') . ': Course Feeds: ' . strtoupper( $status ), $message );
		}
		if ( 'error' == $status ) {
			WP_CLI::error( $message );
		}
		// If we get here everything has gone ok
		WP_CLI::success( $message );
	}

	/**
	 * Processes the external XML based Lecture feed for any Course with a valid field
	 *
	 * @subcommand update-lectures
	 */
	function update_lectures( $args, $assoc_args ) {
		$notify = isset( $assoc_args['skip-notify'] ) ? false : true;
		$imp = imperial();
		$inc = reset($imp->includes_dir); // Uses the first item
		require_once( $inc . 'course_feeds.php' );

		$feeds = imperial_course_feeds();
		$courses = $imp->get_courses_by_meta_field( 'course_lecture_capture_url' );

		$errors = array();
		$cnt = 0;
		foreach($courses AS $course) {
			$url = get_post_meta( $course->ID, 'course_lecture_capture_url', true );
			// Double check
			if ( !empty($url) ) {
				$result = $feeds->process_lecture_feed( $course->ID, $url );
				if ( is_wp_error( $result ) ) {
					$error = $result->get_error_messages();
					$errors[] = implode( "\n", $error );
				}
				else {
					$cnt++;
				}
			}
		}
		$summary .= "\n" . 
			'=> Memory used: ' . size_format(memory_get_usage()) . "\n" .
			'=> Time taken: ' . timer_stop() . ' secs';
		$status = 'success';
		if ( !empty($errors) ) {
			$status = 'error';
			$summary .= "\n\nErrors:\n " . implode("\n ", $errors);
		}
		$message = sprintf( __('%s Lecture Captures have successfully updated, with the message: %s', 'imperial' ), $cnt, $summary );
		if ( $notify ) {
			$this->_notify( 'Automated Update: ' . get_bloginfo('name') . ': Lecture Captures: ' . strtoupper( $status ), $message );
		}
		if ( 'error' == $status ) {
			WP_CLI::error( $message );
		}
		// If we get here everything has gone ok
		WP_CLI::success( $message );
	}

} // END Imperial_Courses_Feed_Command


WP_CLI::add_command( 'imperial user-feed', 'Imperial_Users_Feed_Command' );

class Imperial_Users_Feed_Command extends Imperial_Base_CLI_Command {

	private $student_fields = array(
		'username'         => 'username',
		'email'            => 'Email',
		'surname'          => 'Surname',
		'nickname'         => 'Preferred/Given Names',
		'cid'              => 'CID',
		'course_code'      => 'Course Code',
		'course_year'      => 'Course Year',
		'programme_code'   => 'Programme Code',
		'programme_year'   => 'Academic Year',
		'programme_status' => 'Programme Status',
	);

	private $staff_fields = array(
		'username'         => 'username',
		'email'            => 'Email',
		'surname'          => 'Surname',
		'nickname'         => 'Preferred/Given Names',
		'cid'              => 'CID',
		'course_code'      => 'Course Code',
		'course_year'      => 'Course Year',
		'programme_code'   => 'Programme Code',
		'programme_year'   => 'Academic Year',
		'course_leader'    => 'Course Leader',
	);

	/**
	 * Update the student feed with the CSV specified
	 * 
	 * @subcommand update-students
	 */
	function update_students( $args, $assoc_args ) {
		$feed_file = isset( $args[0] ) ? $args[0] : false;
		$notify = isset( $assoc_args['skip-notify'] ) ? false : true;
		if ( ! is_file( $feed_file ) ) {
			$message = sprintf( __( '%s is not a valid file, or cannot be read.', 'imperial' ), $feed_file );
			$this->_notify( 'Automated Update: ' . get_bloginfo('name') . ': Student feed: ERROR ', $message );
			WP_CLI::error( $message );
		}
		// Load the data from the given url
		ini_set('auto_detect_line_endings', '1');
		$data = $this->_get_csv_data_from_file( $feed_file, $this->student_fields );
		if ( WP_DEBUG ) {
			WP_CLI::line( sprintf( __( 'Memory: %s, %s secs', 'imperial' ), size_format( memory_get_usage() ), timer_stop() ) );
		}
		if ( empty($data) ) {
			$message = sprintf( __( '%s contains no data.', 'imperial' ), $feed_file );
			$this->_notify( 'Automated Update: ' . get_bloginfo('name') . ': Student feed: ERROR ', $message );
			WP_CLI::error( $message );
		}
		// Check the data integrity
		if ( count($data[0]) != count($this->student_fields) ){
			WP_CLI::error( __('The number of expected columns does not match what is in the feed.', 'imperial' ) );
		}
		// Process the data
		$result = $this->_process_student_feed( $data );
		$message = sprintf( __('Student feed has imported, with the message: %s', 'imperial'), "\n" . $result['summary'] );
		$email_message = str_replace( "\n", '<br>', $message );
		if ( $notify ) {
			$this->_notify( 'Automated Update: ' . get_bloginfo('name') . ': Student feed: ' . strtoupper( $result['status'] ), $email_message );
		}
		if ( 'error' == $result['status'] ) {
			WP_CLI::error( $message );
		}
		// If we get here everything has gone ok
		WP_CLI::success( $message );
	}

	/**
	 * Processes the Student data updating the users and their connections
	 * 
	 * @param array $data the complete csv file
	 * @return array
	 */
	private function _process_student_feed( $data ) {
		$imp = Imperial();
		$staffUsers = $imp->get_staff_ids();
		$existingUserIDs = $imp->get_student_ids();
		$csvCnt = $csvErrCnt = $usersCnt = 0;
		$processedUserIDs = $skipUserIDs = $disabledUserIDs = $disabledUsers = $removedUserIDs = $removedUsers = 
				$failedUsers = $createdUsers = $programmeCodes = $programmeGroupIDs = $courseCodes = $courseGroupIDs = 
				$programmeConnections = $courseConnections = $errors = array();

		// First step is loop through all CSV data rows setting users, and pre-process for everything else
		foreach ($data as $userData) {
			// Standardise on lowercase
			$userData['username'] = strtolower($userData['username']);
			// Skip non-valid usernames, and users that failed to create
			if( '#n/a' == $userData['username'] || in_array($userData['username'], $failedUsers) ) {
				continue;
			}
			// check for userID in staff users
			if ( isset($staffUsers[$userData['username']]) ) {
				$error_message = 'User exists as a staff member: '.$userData['email'];
				$errors[] = $error_message;
//				Error::addError($error_message, FeedManager::$TYPE_STUDENT);
				$failedUsers[] = $userData['username']; // Store username, we don't want to try this again
				$csvErrCnt++;
				continue;   // Skip to next row of data
			}
			// Check for userID from existing users (via login name) - This should be most (if not all) Students
			elseif ( isset($existingUserIDs[$userData['username']]) ) {
				$userID = $existingUserIDs[$userData['username']];

				// If we haven't already done so (i.e. we do this on the first row for this user), update the base user information...
				if ( !in_array($userID, $processedUserIDs) && !in_array($userID, $skipUserIDs) ) {
					// ...except for Alumni students we do a reduced change of data...
					if ( 'alumni' == strtolower($userData['programme_status']) ) {
						$skipUserIDs[] = $userID; // Don't re-process this user later
						// ...check what their status currently is, only change if not already changed
						if ( $userData['programme_status'] != get_user_meta($userID, 'status', true) ) {
							$userID = $this->_update_user($userID, $userData, 'student');// Use the whole row of data
						}
					} 
					// ...and for 'disabled' students (i.e. those that are to be locked out from the site)...
					elseif ( 'disabled' == strtolower($userData['programme_status']) ) {
						$skipUserIDs[] = $userID; // Don't re-process this user later
						// ...check what their status currently is, only change if not already changed
						if ( $userData['programme_status'] != get_user_meta($userID, 'status', true) ) {
							$userID = $this->_update_user($userID, $userData, 'student');// Use the whole row of data
							$disabledUserIDs[] = $userID; // Disable this User later in the overall process
							$disabledUsers[] = $userData['username'];
						}
					} 
					// ...and for 'removed' students (i.e. those that are to be completely removed from the site)...
					elseif ( 'removed' == strtolower($userData['programme_status']) ) {
						$skipUserIDs[] = $userID; // Don't re-process this user later
						// ...check what their status currently is, only change if not already changed
						if ( $userData['programme_status'] != get_user_meta($userID, 'status', true) ) {
							$userID = $this->_update_user($userID, $userData, 'student');// Use the whole row of data
							$removedUserIDs[] = $userID; // Disable this User later in the overall process
							$removedUsers[] = $userData['username'];
						}
					} 
					// ...otherwise it's everyone else
					else {
						$processedUserIDs[] = $userID; // Don't re-process this user later
						$userID = $this->_update_user($userID, $userData, 'student');// Use the whole row of data
					}
					if ( is_wp_error($userID) ) {
						$error_message = 'Failed to update the user for '.$userData['email'].': '.implode(', ', $userID->get_error_messages());
						$errors[] = $error_message;
						error_log($error_message);
						error_log('Failed to update the user for '.$userData['email']);
						$csvErrCnt++;
						continue;   // Skip to next row of data
					}
					$usersCnt++; // Track the users updated
				}
				// If this has been processed in another way, skip everything else..
				if ( in_array($userID, $skipUserIDs) ) {
					$csvCnt++; // ...except mark it as a row we've successfully processed
					continue;   // Skip to next row of data
				}
			} 
			// Else it's a whole new user/Student, so create, as long as we haven't already (this should occur only once)
			elseif ( empty($createdUsers[$userData['username']]) ) {
				$userID = $this->_create_user($userData, 'student');// Use the whole row of data

				if ( is_wp_error($userID) ) {
					$error_message = 'Failed to create a new user for '.$userData['email'].': '.implode(', ', $userID->get_error_messages());
					if ( 'existing_user_email' == $userID->get_error_code() ) {
						if ( $existing_user = get_user_by('email', $userData['email'] ) ) {
							$error_message .= ' Existing email address used for the user \'' . $existing_user->user_login . '\'';
						}
					}
					$errors[] = $error_message;
					error_log($error_message);
					$failedUsers[] = $userData['username']; // Store username, we don't want to try this again
					$csvErrCnt++;
					continue;   // Skip to next row of data
				}
//				elseif ( !$userID ) {
//					Error::addError('Failed to create a new user for '.$userData['email'], FeedManager::$TYPE_STUDENT);
//					$failedUsers[] = $userData['username']; // Store username, we don't want to try this again
//					$csvErrCnt++;
//					continue;   // Skip to next row of data
//				}
				$createdUsers[$userData['username']] = $userID; // Track the users created successfully, we don't need to do this again
				$processedUserIDs[] = $userID; // Don't re-process this user later
			}
			// Else this was a previously created user, so use the ID (this'll occur every subsequent row of a newly created user)
			elseif ( isset($createdUsers[$userData['username']]) ) {
				$userID = $createdUsers[$userData['username']];
			}

			// Double check we have a valid ID
			if ( !$userID ) {
				continue;
			}
			// Collecting the Course/Programme information for later setting
			$prog_code      = $userData['programme_code'];
			$prog_year      = $userData['programme_year'];
			$course_code    = $userData['course_code'];
			$course_year    = $userData['course_year'];

			// Check for existing Programme ID for that code/year combo
			if ( !isset($programmeCodes[$prog_year][$prog_code]) ) {
				$postID = $imp->get_programme_id_by_code_year( $prog_code, $prog_year );
				// Used for later...
				$group_id = $this->_get_group_id( $prog_code, $prog_year );
				if ( $group_id ) {
					$programmeGroupIDs[ $postID ] = $group_id;
				}
				$programmeCodes[$prog_year][$prog_code] = $postID;
			}
			// Mark as a Connection for later adding, assuming that the Programme exists
			if ( !empty($programmeCodes[$prog_year][$prog_code]) ) {
				$programmeConnections[ $programmeCodes[$prog_year][$prog_code] ][ $userID ] = $userID;
			}
			// Check for existing Course ID for that code/year combo
			if ( !isset($courseCodes[$course_year][$course_code]) ) {
				$postID = $imp->get_course_id_by_code_year( $course_code, $course_year );
				// Used for later...
				$group_id = $this->_get_group_id( $course_code, $course_year );
				if ( $group_id ) {
					$courseGroupIDs[ $postID ] = $group_id;
				}
				$courseCodes[$course_year][$course_code] = $postID;
			}
			// Mark as a Connection for later adding, assuming that the Course exists
			if ( !empty($courseCodes[$course_year][$course_code]) ) {
				$courseConnections[ $courseCodes[$course_year][$course_code] ][ $userID ] = $userID;
			}
			$csvCnt++; // Track the rows we've successfully processed
			if ( WP_DEBUG && 0 === ( $csvCnt % 300 ) ) {
				WP_CLI::line( __('...processing CSV rows - ', 'imperial') . sprintf( __( 'Memory: %s, %s secs', 'imperial' ), size_format( memory_get_usage() ), timer_stop() ) );
			}
		} // END each CSV row

		// Clear some memory
		unset($data);
		unset($userData);
		unset($staffUsers);
		unset($courseCodes);
		unset($programmeCodes);
		
		if ( WP_DEBUG ) {
			WP_CLI::line( __('=> Finished processing CSV rows - ', 'imperial') . sprintf( __( 'Memory: %s, %s secs', 'imperial' ), size_format( memory_get_usage() ), timer_stop() ) );
		}

		// Briefly turn off the Activity component of BuddyPress, we don't want 100s of 'joined_group' entries
		$bp = buddypress();
		$restore_activity = false;
		if ( bp_is_active( 'activity' ) ) {
			unset($bp->active_components['activity']);
			$restore_activity = true;
		}
		// ...final steps is to take the pre-processed data and do the final database P2U Connections work
		if ( 0 < count($programmeConnections) ) {
			foreach( $programmeConnections AS $programmeID => $users ) {
				foreach( $users AS $userID ) {
					p2p_type( 'student_programme' )->connect( $userID, $programmeID );
					// Assuming the Programme group exists, add the Student to it
					if ( !empty($programmeGroupIDs[$programmeID]) ) {
						groups_join_group( $programmeGroupIDs[$programmeID], $userID );
					}
				}
			}
		}
		if ( 0 < count($courseConnections) ) {
			// We'll do this manually thanks
			remove_action( 'sensei_user_course_start', 'imperial_sensei_user_course_start_action', 10, 2 );
			foreach( $courseConnections AS $courseID => $users ) {
				foreach( $users AS $userID ) {
					p2p_type( 'student_course' )->connect( $userID, $courseID );
					// Assuming the Course group exists, add the Student to it
					// we do this separately as the action 'sensei_user_course_start' may not trigger if the User has already started the Course
					if ( !empty($courseGroupIDs[$courseID]) ) {
						groups_join_group( $courseGroupIDs[$courseID], $userID );
					}
					// Start them on the Sensei Course, if not already
					$is_user_taking_course = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $courseID, 'user_id' => $userID, 'type' => 'sensei_course_start' ) );
					if ( !$is_user_taking_course ) {
						// This <del>also auto-adds</del> doesn't auto-add the user to the corresponding Group, see above
						WooThemes_Sensei_Utils::user_start_course( $userID, $courseID );
					}
				}
			}
		}

		if ( WP_DEBUG ) {
			WP_CLI::line( __('=> Finished connecting Programmes/Courses and starting Courses - ', 'imperial') . sprintf( __( 'Memory: %s, %s secs', 'imperial' ), size_format( memory_get_usage() ), timer_stop() ) );
		}
		// ...remove connections not in the csv.
		foreach ($processedUserIDs as $uid) {
			// Remove user programme connections.
			$userProgrammes = p2p_get_connections('student_programme', array('from' => $uid, 'fields' => 'p2p_to'));
			if (!empty($userProgrammes) && is_array($userProgrammes)) {
				foreach ($userProgrammes as $up) {
					$prog = (!empty($programmeConnections[$up])) ? $programmeConnections[$up] : NULL;
					if (empty($prog) || !array_key_exists($uid, $prog)) {
						p2p_type( 'student_programme' )->disconnect( $uid, $up );
						// And leave the associated Group
						if ( !empty($programmeGroupIDs[$up]) ) {
							groups_leave_group( $programmeGroupIDs[$up], $uid );
						}
					}
					unset($prog);
				}
			}
			// Remove user course connections.
			$userCourses = p2p_get_connections('student_course', array('from' => $uid, 'fields' => 'p2p_to'));
			if (!empty($userCourses) && is_array($userCourses)) {
				foreach ($userCourses as $uc) {
					$c = (!empty($courseConnections[$uc])) ? $courseConnections[$uc] : NULL;
					if (empty($c) || !array_key_exists($uid, $c)) {
						p2p_type( 'student_course' )->disconnect( $uid, $uc );
						// And leave the associated Group
						if ( !empty($courseGroupIDs[$uc]) ) {
							groups_leave_group( $courseGroupIDs[$uc], $uid );
						}
					}
					unset($c);
				}
			}
		}
		// Restore the BuddyPress Activity component if it was disabled
		if ( $restore_activity ) {
			$bp->active_components['activity'] = 1;
		}
		if ( WP_DEBUG ) {
			WP_CLI::line( __('=> Finished dis-connecting Programmes/Courses - ', 'imperial') . sprintf( __( 'Memory: %s, %s secs', 'imperial' ), size_format( memory_get_usage() ), timer_stop() ) );
		}
		// Enable all those users that were processed, i.e. they are in the CSV
		$enabled_count = $this->_enable_users( $processedUserIDs );
		// Disable all those users that have been marked as such in the CSV
		$disabled_count = $this->_disable_users( $disabledUserIDs );
		// Remove all those users that have been marked as such in the CSV
		$removed_count = $this->_remove_users( $removedUserIDs );

		$summary = '=> Total of ' . $csvCnt . ' successful rows, with ' . $csvErrCnt . ' bad rows' . "\n" .
					'=> ' . count($createdUsers) . ' Students created, ' . $usersCnt . ' Students updated';
		if ( $disabled_count ) {
			$summary .= ', ' . $disabled_count . ' Students were marked as disabled and were locked out of the site';
			if ( WP_DEBUG ) {
				$summary .= "\n" . '=> Disabled users: ' . implode( ', ', $disabledUsers );
			}
		}
		if ( $removed_count ) {
			$summary .= ', ' . $removed_count . ' Students were marked as removed and had all connections and data removed';
			if ( WP_DEBUG ) {
				$summary .= "\n" . '=> Removed users: ' . implode( ', ', $removedUsers );
			}
		}
		$summary .= "\n" . '=> Memory used: ' . size_format(memory_get_usage()) . "\n" .
					'=> Time taken: ' . timer_stop() . ' secs';
		$status = 'success';
		if ( WP_DEBUG ) {
			WP_CLI::line('***** StudentFeed finished *****');
		}
		if ( !empty($errors) ) {
			$status = 'error';
			$summary .= "\n\nErrors:\n " . implode("\n ", $errors);
		}
		return compact('status', 'summary');
	}

	/**
	 * Update the staff feed with the CSV specified
	 * 
	 * @subcommand update-staff
	 */
	function update_staff( $args, $assoc_args ) {
		$feed_file = isset( $args[0] ) ? $args[0] : false;
		$notify = isset( $assoc_args['skip-notify'] ) ? false : true;
		if ( ! is_file( $feed_file ) ) {
			$message = sprintf( __( '%s is not a valid file, or cannot be read.', 'imperial' ), $feed_file );
			$this->_notify( 'Automated Update: ' . get_bloginfo('name') . ': Staff feed: ERROR ', $message );
			WP_CLI::error( $message );
		}
		// Load the data from the given url
		ini_set('auto_detect_line_endings', '1');
		$data = $this->_get_csv_data_from_file( $feed_file, $this->staff_fields );
		if ( WP_DEBUG ) {
			WP_CLI::line( sprintf( __( 'Memory: %s, %s secs', 'imperial' ), size_format( memory_get_usage() ), timer_stop() ) );
		}
		if ( empty($data) ) {
			$message = sprintf( __( '%s contains no data.', 'imperial' ), $feed_file );
			$this->_notify( 'Automated Update: ' . get_bloginfo('name') . ': Staff feed: ERROR ', $message );
			WP_CLI::error( $message );
		}
		// Check the data integrity
		if ( count($data[0]) != count($this->staff_fields) ){
			WP_CLI::error( __('The number of expected columns does not match what is in the feed.', 'imperial' ) );
		}
		// Process the data
		$result = $this->_process_staff_feed( $data );
		$message = sprintf( __('Staff feed has imported, with the message: %s', 'imperial'), "\n" . $result['summary'] );
		$email_message = str_replace( "\n", '<br>', $message );
		if ( $notify ) {
			$this->_notify( 'Automated Update: ' . get_bloginfo('name') . ': Staff feed: ' . strtoupper( $result['status'] ), $email_message );
		}
		if ( 'error' == $result['status'] ) {
			WP_CLI::error( $message );
		}
		// If we get here everything has gone ok
		WP_CLI::success( $message );
	}

	/**
	 * Processes the Student data updating the users and their connections
	 * 
	 * @param array $data the complete csv file
	 * @return array
	 */
	private function _process_staff_feed( $data ) {
		$imp = Imperial();
		$existingUserIDs = $imp->get_staff_ids();
		$csvCnt = $csvErrCnt = $usersCnt = 0;
		$processedUserIDs = $failedUsers = $createdUsers = $programmeCodes = $programmeGroupIDs = $courseCodes = $courseGroupIDs = 
				$programmeConnections = $programmeTeamConnections = $courseConnections = $courseLeaderConnections = $errors = array();

		// First step is loop through all CSV data rows setting users, and pre-process for everything else
		foreach ($data as $userData) {
			// Standardise on lowercase
			$userData['username'] = strtolower($userData['username']);
			// Skip non-valid usernames, and users that failed to create
			if( '#n/a' == $userData['username'] || in_array($userData['username'], $failedUsers) ) {
				continue;
			}
			// Check for userID from existing users (via login name) - This should be most (if not all) Staffs
			if ( isset($existingUserIDs[$userData['username']]) ) {
				$userID = $existingUserIDs[$userData['username']];

				// If we haven't already done so (i.e. we do this on the first row for this user), update the base user information
				if ( !in_array($userID, $processedUserIDs) ) {
					$processedUserIDs[] = $userID; // Don't re-process this user later
					$userID = $this->_update_user($userID, $userData, 'staff'); // Use the whole row of data

					if ( is_wp_error($userID) ) {
						$error_message = 'Failed to update the user for '.$userData['email'].' - '.implode(', ', $userID->get_error_messages());
						$errors[] = $error_message;
						error_log($error_message);
						$csvErrCnt++;
						continue;   // Skip to next row of data
					}
					$usersCnt++; // Track the users updated
				}
			} 
			// Else it's a whole new user/Staff, so create, as long as we haven't already
			elseif ( empty($createdUsers[$userData['username']]) ) {
				$userID = $this->_create_user($userData, 'staff'); // Use the whole row of data

				if ( is_wp_error($userID) ) {
					$error_message = 'Failed to create a new user for '.$userData['email'].' - '.implode(', ', $userID->get_error_messages());
					if ( 'existing_user_email' == $userID->get_error_code() ) {
						if ( $existing_user = get_user_by('email', $userData['email'] ) ) {
							$error_message .= ' Existing email address used for the user \'' . $existing_user->user_login . '\'';
						}
					}
					$errors[] = $error_message;
					error_log($error_message);
					$failedUsers[] = $userData['username']; // Store username, we don't want to try this again
					$csvErrCnt++;
					continue;   // Skip to next row of data
				}
				$createdUsers[$userData['username']] = $userID; // Track the users created successfully, we don't need to do this again
				$processedUserIDs[] = $userID; // Don't re-process this user later
			}
			// Else this was a previously created user, so use the ID
			elseif ( isset($createdUsers[$userData['username']]) ) {
				$userID = $createdUsers[$userData['username']];
				$processedUserIDs[] = $userID; // Don't re-process this user later
			}

			// Collecting the Course/Programme information for later setting
			$prog_code      = $userData['programme_code'];
			$prog_year      = $userData['programme_year'];
			$course_code    = $userData['course_code'];
			$course_year    = $userData['course_year'];

			if ( !empty($prog_code) ) {
				// Check for existing Programme ID for that year/code combo
				if ( !isset($programmeCodes[$prog_year][$prog_code]) ) {
					$postID = $imp->get_programme_id_by_code_year( $prog_code, $prog_year );
					// Used for later...
					$group_id = $this->_get_group_id( $prog_code, $prog_year );
					if ( $group_id ) {
						$programmeGroupIDs[ $postID ] = $group_id;
					}
					$programmeCodes[$prog_year][$prog_code] = $postID;
				}
				// Mark as a Connection for later adding, assuming that the Programme exists
				if ( !empty($programmeCodes[$prog_year][$prog_code]) ) {
					// If on no Courses this is a 'Programme Team Member'
					if ( empty($course_code) ) {
						$programmeTeamConnections[ $programmeCodes[$prog_year][$prog_code] ][ $userID ] = $userID;
					} else {
						$programmeConnections[ $programmeCodes[$prog_year][$prog_code] ][ $userID ] = $userID;
					}
				}
			}

			if ( !empty($course_code) ) {
				// Check for existing Course ID for that year/code combo 
				if ( !isset($courseCodes[$course_year][$course_code]) ) {
					$postID = $imp->get_course_id_by_code_year( $course_code, $course_year );
					// Used for later...
					$group_id = $this->_get_group_id( $course_code, $course_year );
					if ( $group_id ) {
						$courseGroupIDs[ $postID ] = $group_id;
					}
					$courseCodes[$course_year][$course_code] = $postID;
				}
				// Check if they are also a Leader for this Course
				if ( !empty($courseCodes[$course_year][$course_code]) && !empty($userData['course_leader']) ) {
					$courseLeaderConnections[ $courseCodes[$course_year][$course_code] ][ $userID ] = $userID;
				}
				// Mark as a Connection for later adding, assuming that the Course exists
				else if ( !empty($courseCodes[$course_year][$course_code]) ) {
					$courseConnections[ $courseCodes[$course_year][$course_code] ][ $userID ] = $userID;
				}
			}
			$csvCnt++; // Track the rows we've successfully processed
			if ( WP_DEBUG && 0 === ( $csvCnt % 50 ) ) {
				WP_CLI::line('step1... memory: '.size_format(memory_get_usage()) . ', ' . timer_stop() . ' secs');
			}
		} // END each CSV row

		// Clear some memory
		unset($data);
		unset($userData);
		unset($courseCodes);
		unset($programmeCodes);

		if ( WP_DEBUG ) {
			WP_CLI::line('step1 finished. Memory: '.size_format(memory_get_usage()) . ', ' . timer_stop() . ' secs');
		}

//		// ...second step is to remove all existing P2U Connections on each user...
//		foreach( $processedUserIDs AS $userID ) {
//			// Remove all pre-existing Staff Programme Connections
//			p2p_delete_connections( 'staff_programmes', array( 'from' => $userID ) );
//
//			// Remove all pre-existing Staff Course Connections
//			p2p_delete_connections( 'staff_courses', array( 'from' => $userID ) );
//		}
//
		// Briefly turn off the Activity component of BuddyPress, we don't want 100s of 'joined_group' entries
		$bp = buddypress();
		$restore_activity = false;
		if ( bp_is_active( 'activity' ) ) {
			unset($bp->active_components['activity']);
			$restore_activity = true;
		}
		$supportGroupIDs = array();
		// ...final steps is to take the pre-processed data and do the final database P2U Connections work
		if ( 0 < count($programmeTeamConnections) ) {
			foreach( $programmeTeamConnections AS $programmeID => $users ) {
				foreach( $users AS $userID ) {
					p2p_type( 'staff_programme' )->connect( $userID, $programmeID, array( 'team' => '1' ) );
					// Join the associated group (if not already)
					if ( !empty($programmeGroupIDs[$programmeID]) ) {
						groups_join_group( $programmeGroupIDs[$programmeID], $userID );
						// Promote user to group admin (can't use groups_promote_member() as it checks for the current user permission)...
//						groups_promote_member( $userID, $programmeGroupIDs[$programmeID], $status = 'admin');
						// .. so we do it direct
						$member = new BP_Groups_Member( $userID, $programmeGroupIDs[$programmeID] );
						$member->promote( $status = 'admin' );
						// Mark user to receive support notifications
						if ( empty($supportGroupIDs[ $programmeGroupIDs[$programmeID] ]) ) {
							$supportGroupIDs[ $programmeGroupIDs[$programmeID] ] = array();
						}
						$supportGroupIDs[ $programmeGroupIDs[$programmeID] ][] = $userID;
					}
				}
			}
		}
		if ( 0 < count($programmeConnections) ) {
			foreach( $programmeConnections AS $programmeID => $users ) {
				foreach( $users AS $userID ) {
					p2p_type( 'staff_programme' )->connect( $userID, $programmeID );
					// Join the associated group (if not already)
					if ( !empty($programmeGroupIDs[$programmeID]) ) {
						groups_join_group( $programmeGroupIDs[$programmeID], $userID );
						// Promote user to group moderator (can't use groups_promote_member() as it checks for the current user permission)
//						groups_promote_member( $userID, $programmeGroupIDs[$programmeID], $status = 'mod');
						// .. so we do it direct
						$member = new BP_Groups_Member( $userID, $programmeGroupIDs[$programmeID] );
						$member->promote( $status = 'mod' );
						// NOTE: Not everyone connected to the Programme receive Support Topics, only Programme Team members do
					}
				}
			}
		}
		if ( 0 < count($courseLeaderConnections) ) {
			foreach( $courseLeaderConnections AS $courseID => $users ) {
				foreach( $users AS $userID ) {
					// Check if the connection (with this role) already exists, if not create it
					p2p_type( 'staff_course' )->connect( $userID, $courseID, array( 'role' => 'Course Leader' ) );
					// Join the associated group (if not already)
					if ( !empty($courseGroupIDs[$courseID]) ) {
						groups_join_group( $courseGroupIDs[$courseID], $userID );
						// Promote user to group admin (can't use groups_promote_member() as it checks for the current user permission)
//						groups_promote_member( $userID, $courseGroupIDs[$courseID], $status = 'admin');
						// .. so we do it direct
						$member = new BP_Groups_Member( $userID, $courseGroupIDs[$courseID] );
						$member->promote( $status = 'admin' );
						// NOTE: Not everyone connected to the Course receive Support Topics, Course leaders don't
					}
				}
			}
		}
		if ( 0 < count($courseConnections) ) {
			foreach( $courseConnections AS $courseID => $users ) {
				foreach( $users AS $userID ) {
					// Check if the connection (with this role) already exists, if not create it
					p2p_type( 'staff_course' )->connect( $userID, $courseID );
					// Join the associated group (if not already)
					if ( !empty($courseGroupIDs[$courseID]) ) {
						groups_join_group( $courseGroupIDs[$courseID], $userID );
						// Promote user to group moderator (can't use groups_promote_member() as it checks for the current user permission)
//						groups_promote_member( $userID, $courseGroupIDs[$courseID], $status = 'mod');
						$member = new BP_Groups_Member( $userID, $courseGroupIDs[$courseID] );
						$member->promote( $status = 'mod' );
						// Mark user to receive support notifications
						if ( empty($supportGroupIDs[ $courseGroupIDs[$courseID] ]) ) {
							$supportGroupIDs[ $courseGroupIDs[$courseID] ] = array();
						}
						$supportGroupIDs[ $courseGroupIDs[$courseID] ][] = $userID;
					}
				}
			}
		}
		// Loop through all those users for support and set as such in the database
		if ( 0 < count($supportGroupIDs) ) {
			foreach ( $supportGroupIDs as $group_id => $support_users ) {
				$support_users = array_map( 'intval', (array) $support_users );
				$support_recipients = groups_get_groupmeta( $group_id, '_bpbbpst_support_bp_recipients', true );
				if ( !empty($support_recipients) ) {
					$support_users = array_unique( array_merge( $support_recipients, $support_users ) );
				}
				$support_users = array_map( 'intval', array_filter( $support_users ) );
				groups_update_groupmeta( $group_id, '_bpbbpst_support_bp_recipients', $support_users );
			}
		}
		// Restore the BuddyPress Activity component if it was disabled
		if ( $restore_activity ) {
			$bp->active_components['activity'] = 1;
		}
		$summary = '=> Total of ' . $csvCnt . ' successful rows, with ' . $csvErrCnt . ' bad rows' . "\n" .
					'=> ' . count($createdUsers) . ' Staff created, ' . $usersCnt . ' Staff updated' . "\n" .
					'=> Memory used: ' . size_format(memory_get_usage()) . "\n" .
					'=> Time taken: ' . timer_stop() . ' secs';

		$status = 'success';
		if ( WP_DEBUG ) {
			WP_CLI::line('***** StaffFeed finished *****' . "\n" . $summary);
		}
		if ( !empty($errors) ) {
			$status = 'error';
			$summary .= "\n\nErrors:\n " . implode("\n ", $errors);
		}
		return compact('status', 'summary');
	}

	/**
	 * Given a filename and an array of the column headers return an associated array of the data
	 * 
	 * @param string $filename
	 * @param array $expected_fields
	 * @param boolean $header_row
	 * @return array
	 */
	private function _get_csv_data_from_file( $filename, $expected_fields, $header_row = true ) {
		$fh = fopen( $filename, 'r' );
		if( false == $fh ) {
			WP_CLI::error( sprintf( __( 'File %s could not be read.', 'imperial' ), $file ) );
		}
		$translate_encoding = false;
		$file_encoding = $this->_guess_encoding( $filename );
		if ( $file_encoding && 'UTF-8' != $file_encoding ) {
			$translate_encoding = true;
		}
		$col_num = 0;
		$data = $headers = array();
		while ( false !== ($row = @fgets($fh) ) ) {
			if ( $translate_encoding ) {
				$row = iconv( $file_encoding, 'UTF-8', $row );
			}
			$row = str_getcsv($row); // Only available PHP 5.3+
			if ( $header_row && 0 == $col_num ) {
				// Calculate the order that the cols appear in
				foreach( $row as $col_head ) {
					$headers[$col_head] = $col_num;
					$col_num++;
				}
				continue; // to first line of data
			}
			$row_data = array();
			$counter = 0;
			$cols = count($row);
			// Add keys we are expecting
			foreach ( $expected_fields as $field => $label ) {
				if ( $header_row ) {
					if ( !isset( $headers[$label] ) ) {
						continue;  // Unknown label reference
					}
					// Allow arbitary ordering of cols
					$counter = $headers[$label];
				}

				if ( !isset( $row[$counter] ) || $counter == $cols ) {
					break;  // Hit the end of the line
				}
				// Trim any spaces
				$row_data[$field] = trim($row[$counter]);
				$counter++;
			}
			$data[] = $row_data;
//			if (10 == count($data)) { WP_CLI::error('Data:'.print_r($data,true)); } // for testing
		}
		fclose($fh);
		return $data;
	}

	/**
	 * Guess the encoding of the file
	 * 
	 * @param type $filepath
	 * @return type
	 */
	private function _guess_encoding($filepath) {
		ini_set('auto_detect_line_endings', true);
		$encoding = false;
		if ( function_exists('mb_detect_encoding') ) {
			$contents = @file_get_contents($filepath);

			$encodings = array (
				'UTF-8',
				'UTF-16LE',
				'windows-1252',
				'ISO-8859-1',
				'windows-1251',
			);

			$encoding = mb_detect_encoding($contents, $encodings);
		}
		return $encoding;
	}

	/**
	 * Creates a user based on the csv data we are given
	 * 
	 * @param array $userData
	 * @param string type of user, either staff or student
	 * @return wordpress user data or false on failure
	 */
	private function _create_user($userCSV, $type) {
		if ( WP_DEBUG ) {
			WP_CLI::line('Creating ' .$type . ' user: '.$userCSV['username']);
		}
		$data = $this->_process_base_data($userCSV, $type);
		// New user so set the login
		$data['user_login'] = strtolower($userCSV['username']);
		$data['user_pass'] = wp_generate_password();
		if ( 'staff' == $type ) {
			// Set the initial user level of the Staff
			$data['role'] = 'author';
		}

		$userID = wp_insert_user($data);

		// User created ok?
		if ( is_wp_error($userID) ) {
			return $userID;
		}
		$this->_update_base_meta_data($userID, $userCSV, $type);

		return $userID; // get_userdata($userID);
	}

	/**
	 * Updates the base data on a user given the csv data
	 * 
	 * @param int $existingUserID
	 * @param array $userCSV
	 * @param string type, either staff or student
	 * @return int userid
	 */
	private function _update_user($existingUserID, $userCSV, $type) {
		if ( WP_DEBUG ) {
			WP_CLI::line('Updating ' .$type . ' user: '.$userCSV['username']);
		}
		$data = $this->_process_base_data($userCSV, $type);
		$data['ID'] = $existingUserID;

		$userID = wp_update_user($data);

		if ( is_wp_error($userID) ) {
			return $userID;   // Something very wrong happened
		}
		$this->_update_base_meta_data($userID, $userCSV, $type);
		if ( 'student' == $type ) {
			// Sync the WP display name to BuddyPress profile field
			xprofile_set_field_data( bp_xprofile_fullname_field_id(), $userID, $data['display_name'] );
		}
		return $userID;
	}

	/**
	 * Set the base data used by all Staff/Students
	 * 
	 * @param type $userCSV
	 * @return type
	 */
	private function _process_base_data($userCSV, $type) {
		$data = array();
		$data['user_email'] = $userCSV['email'];

		$data['nickname'] = $userCSV['nickname'];
		$data['first_name'] = $userCSV['nickname'];
		$data['last_name'] = $userCSV['surname'];
		$data['display_name'] = $userCSV['nickname'] . ' ' . $userCSV['surname'];

		return $data;
	}

	/**
	 * Udpate/set the basic meta data for Staff/Students
	 * 
	 * @param type $user
	 * @param type $userCSV
	 * @return boolean
	 */
	private function _update_base_meta_data($userID, $userCSV, $type) {
		// Set their CID
		if ( !empty($userCSV['cid']) ) {
			update_user_meta($userID, 'cid', sprintf("%08s", $userCSV['cid']));  // Don't need to check before updating, WP does that
		}
		if ( 'student' == $type ) {
			// Mark their overall status
			update_user_meta($userID, 'status', $userCSV['programme_status']);  // Don't need to check before updating, WP does that
		}
		// Mark as a 'student' or 'staff'
		update_user_meta($userID, $type, true);

		return true;
	}

	/**
	 * Enable the provided user IDs for login
	 * 
	 * @param int $userID
	 * @return boolean true on success or false on failure
	 */
	private function _enable_users($users){
		$enabled = 0;
		// Loop through and enable an array of users
		if ( is_array($users) ) {
			foreach($users as $userID) {
				delete_user_meta( $userID, 'disabled' );
				$enabled++;
			}
		}
		return $enabled;
	}

	/**
	 * Disable the provided user IDs from login
	 * 
	 * @param array $users
	 * @return int count of affected users
	 */
	private function _disable_users($users) {
		$disabled = 0;
		// Loop through and disable an array of users
		if ( is_array($users) ) {
			foreach($users as $userID) {
				if ( WP_DEBUG ) {
					WP_CLI::line( "Disabling $userID;" );
				}
				// Mark them as cannot login in
				update_user_meta( $userID, 'disabled', true );
			}
			wp_cache_flush();
		}
		return $disabled;
	}

	/**
	 * Remove the provided user IDs from all aspects of the site
	 * 
	 * @param int $userID
	 * @return int count of affected users
	 */
	private function _remove_users($users) {
		global $wpdb;
		$disabled = 0;
		// Loop through and disable an array of users
		if ( is_array($users) ) {
			foreach($users as $userID) {
				if ( WP_DEBUG ) {
					WP_CLI::line( "Removing $userID;" );
				}
				// Update user_status on Users table
				// Mark them as cannot login in
				update_user_meta( $userID, 'disabled', true );
				// Remove the Programme Selector data
				delete_user_meta( $userID, 'user_programme_select' );

				// Remove all BuddyPress activity data
				if ( function_exists( 'bp_activity_delete' ) ) {
//					bp_activity_remove_all_user_data( $userID );
					bp_activity_delete( array( 'user_id' => $userID ) );
				}

				// Remove all BuddyPress group data
				if ( function_exists('groups_remove_data_for_user') ) {
					groups_remove_data_for_user( $userID );
				}

				// Remove all BuddyPress friends data
				if ( function_exists('friends_remove_data') ) {
					friends_remove_data( $userID );
				}

				// Remove all BuddyPress core data
				if ( function_exists('bp_core_remove_data') ) {
					bp_core_remove_data( $userID );
				}

				// Remove all BuddyPress xprofile data
				if ( function_exists('xprofile_remove_data') ) {
					xprofile_remove_data( $userID );
				}

				// Remove from all Sensei Courses, remove all quiz answers, lessons statises, even post comments etc
				// This code from WooThemes_Sensei_Utils::sensei_delete_activities(), but allows us to not have to provide post_ids or types
				if ( class_exists('WooThemes_Sensei_Utils') ) {
					$sensei_comments = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'user_id' => $userID ), true );
				}
				else {
					$sensei_comments = get_comments( array( 'user_id' => $userID ) );
				}
				foreach ( $sensei_comments as $key => $value  ) {
					if ( isset( $value->comment_ID ) && 0 < $value->comment_ID ) {
						$dataset_changes = wp_delete_comment( intval( $value->comment_ID ), true );
					} // End If Statement
				} // End For Loop

				// Remove their Programme and Course relationships
				$userProgrammes = p2p_delete_connections( 'student_programme', array( 'from' => $userID, 'fields' => 'p2p_to' ) );
				$userCourses = p2p_delete_connections( 'student_course', array( 'from' => $userID, 'fields' => 'p2p_to' ) );
				
				// Mark User to not show in the admin (a filter that is part of BuddyPress), but has to be a direct call, there is no function for this
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->users} SET user_status = 2 WHERE ID = %d", $userID ) );
				$disabled++;
			}
			wp_cache_flush();
		}
		return $disabled;
	}

	/**
	 * Generalise the creation of the Group ID for BuddyPress groups
	 */
	private function _get_group_id( $code, $year ) {
		$group_id = false;
		if ( class_exists('BP_Groups_Group') ) {
			$slug = sprintf( '%s - %s - group', $code, $year );
			$group_id = BP_Groups_Group::get_id_from_slug( sanitize_title( $slug ) );
		}
		return $group_id;
	}

} // Imperial_Users_Feed_Command


// This class is shared between the others to centralise the Notification ability
class Imperial_Sensei_CLI_Command extends WP_CLI_Command {

	/**
	 * Fix those lessons that are marked as having a quiz with questions when the quiz doesn't
	 *
	 * @subcommand fix-lesson-quizzes
	 */
	function fix_lessons() {
		global $wpdb;

		// Get all Lessons with Quizzes...
		$args = array(
			'post_type' => 'lesson',
			'numberposts' => -1,
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
		$meta_list = $wpdb->get_results( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_lesson_quiz' AND post_id IN ($id_list)", ARRAY_A );
		$lesson_quiz_ids = array();
		if ( !empty($meta_list) ) {
			foreach ( $meta_list as $metarow ) {
				$lesson_id = $metarow['post_id'];
				$quiz_id = $metarow['meta_value'];
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
		$count = 0;
		foreach ( $lesson_quiz_ids AS $lesson_id => $quiz_id ) {
			if ( !in_array( $quiz_id, $lesson_quiz_ids_with_questions ) ) {
//				error_log( "Error with quiz $quiz_id and lesson $lesson_id ");
				// Quiz has no questions, drop the corresponding data
				delete_post_meta( $quiz_id, '_pass_required' );
				delete_post_meta( $quiz_id, '_quiz_passmark' );
				delete_post_meta( $lesson_id, '_quiz_has_questions' );
				$count++;
			}
		}
		WP_CLI::success( sprintf( __("Fixed %s Lessons!", 'imperial'), $count ) );
	}

	/**
	 * Convert the existing Sensei lesson and course activity logs to new status entries
	 *
	 * @subcommand convert-lesson-activities
	 */
	function convert_lessons() {
		global $wpdb;

		wp_defer_comment_counting( true );

			// Directly querying the database is normally frowned upon, but all
			// of the API functions will return full objects or are limited to 
			// single post_IDs which will overall suck up lots of memory and take
			// far longer to process, in addition to calling filters and actions
			// that we wouldn't want running. This is best, just not as future proof.
			// But then this isn't expected to run more than once.

		// Get all Lessons with Quizzes...
		$args = array(
			'post_type' => 'lesson',
			'numberposts' => -1,
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
		$meta_list = $wpdb->get_results( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_lesson_quiz' AND post_id IN ($id_list)", ARRAY_A );
		$lesson_quiz_ids = array();
		if ( !empty($meta_list) ) {
			foreach ( $meta_list as $metarow ) {
				$lesson_id = $metarow['post_id'];
				$quiz_id = $metarow['meta_value'];
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

		$statuses_to_check = array( 'in-progress' => 1, 'complete' => 1, 'ungraded' => 1, 'graded' => 1, 'passed' => 1, 'failed' => 1 );

		$per_page = 40;
		$user_id_offset = 0;
		$count = $statuses_added = $dup_logs = $dup_statuses = 0;

		$users_sql = "SELECT ID FROM $wpdb->users WHERE ID > %d LIMIT $per_page";
		$start_sql = "SELECT comment_post_ID, comment_date FROM $wpdb->comments WHERE comment_type = 'sensei_lesson_start' AND user_id = %d GROUP BY comment_post_ID ";
		$end_sql = "SELECT comment_post_ID, comment_date FROM $wpdb->comments WHERE comment_type = 'sensei_lesson_end' AND user_id = %d GROUP BY comment_post_ID ";
		$grade_sql = "SELECT comment_post_ID, comment_content FROM $wpdb->comments WHERE comment_type = 'sensei_quiz_grade' AND user_id = %d GROUP BY comment_post_ID ORDER BY comment_content DESC ";
		$answers_sql = "SELECT comment_post_ID, comment_content FROM $wpdb->comments WHERE comment_type = 'sensei_quiz_asked' AND user_id = %d GROUP BY comment_post_ID ORDER BY comment_date_gmt DESC ";
		$check_existing_sql = "SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = %d AND user_id = %d AND comment_type = 'sensei_lesson_status' ";

		// $per_page users at a time, could be batch run via an admin ajax command, 1 user at a time?
		while ( $user_ids = $wpdb->get_col( $wpdb->prepare($users_sql, $user_id_offset) ) ) {

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
						if ( array_key_exists( $status, $statuses_to_check ) ) {
							unset( $statuses_to_check[$status] );
							error_log( 'Adding: ' . print_r($data, true) . ' with meta: '.print_r($meta_data, true));
						}
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
						$statuses_added++;
					}
					else {
						$dup_statuses++;
						if ( 0 == ( $dup_statuses % 100 ) ) {
							WP_CLI::line( '...' );
						}
					}
				}
				$count++;
				if ( 0 == ( $count % 100 ) ) {
					WP_CLI::line( '.' );
//					break 2; // Handbreak to stop rather than process it all
				}
			}
			$wpdb->flush();
			$user_id_offset = $user_id; // Next set of users, basically an offset
		}

		WP_CLI::line( 'Timer end: ' . timer_stop() . ' secs' );
		WP_CLI::line( 'Memory used: ' . size_format(memory_get_usage()) );
		WP_CLI::line( sprintf( __('%s duplicates found.', 'imperial'), $dup_statuses ) );
		WP_CLI::success( sprintf( __('%s lesson statuses added.', 'imperial'), $statuses_added ) );
	}

	/**
	 * Example command
	 *
	 * @subcommand convert-course-activities
	 */
	function convert_courses() {
		global $wpdb;

		wp_defer_comment_counting( true );

			// Directly querying the database is normally frowned upon, but all
			// of the API functions will return full objects or are limited to 
			// single post_IDs which will overall suck up lots of memory and take
			// far longer to process, in addition to calling filters and actions
			// that we wouldn't want running. This is best, just not as future proof.
			// But then this isn't expected to run more than once.

		// Get all Lesson => Course relationships
		$meta_list = $wpdb->get_results( "SELECT $wpdb->postmeta.post_id, $wpdb->postmeta.meta_value FROM $wpdb->postmeta INNER JOIN $wpdb->posts ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) WHERE $wpdb->posts.post_type = 'lesson' AND $wpdb->postmeta.meta_key = '_lesson_course' ", ARRAY_A );
		$course_lesson_ids = array();
		if ( !empty($meta_list) ) {
			foreach ( $meta_list as $metarow ) {
				$lesson_id = $metarow['post_id'];
				$course_id = $metarow['meta_value'];
				$course_lesson_ids[ $course_id ][] = $lesson_id;
			}
		}

		$statuses_to_check = array( 'in-progress' => 1, 'complete' => 1 );

		$per_page = 40;
		$user_id_offset = 0;
		$count = $statuses_added = $dup_logs = $dup_statuses = 0;

		$users_sql = "SELECT ID FROM $wpdb->users WHERE ID > %d LIMIT $per_page";
		$start_sql = "SELECT comment_post_ID, comment_date FROM $wpdb->comments WHERE comment_type = 'sensei_course_start' AND user_id = %d GROUP BY comment_post_ID ";
		$lessons_sql = "SELECT comment_approved AS status, comment_date FROM $wpdb->comments WHERE comment_type = 'sensei_lesson_status' AND user_id = %d AND comment_post_ID IN ( %s ) ORDER BY comment_date_gmt ASC";
		$check_existing_sql = "SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = %d AND user_id = %d AND comment_type = 'sensei_course_status' ";

		// $per_page users at a time, could be batch run via an admin ajax command, 1 user at a time?
		while ( $user_ids = $wpdb->get_col( $wpdb->prepare($users_sql, $user_id_offset) ) ) {

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
						$meta_data['percent'] = abs( round( ( doubleval( $lessons_completed ) * 100 ) / ( $total_lessons ), 0 ) );
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
						if ( array_key_exists( $status, $statuses_to_check ) ) {
							unset( $statuses_to_check[$status] );
							error_log( 'Adding: ' . print_r($data, true) . ' with meta: '.print_r($meta_data, true));
						}
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
						$statuses_added++;
					}
					else {
						$dup_statuses++;
						if ( 0 == ( $dup_statuses % 100 ) ) {
							WP_CLI::line( '...' );
						}
					}
				}
				$count++;
				if ( 0 == ( $count % 100 ) ) {
					WP_CLI::line( '.' );
//					break 2; // Handbreak to stop rather than process it all
				}
			}
			$wpdb->flush();
			$user_id_offset = $user_id; // Next set of users, basically an offset
		}

		WP_CLI::line( 'Timer end: ' . timer_stop() . ' secs' );
		WP_CLI::line( 'Memory used: ' . size_format(memory_get_usage()) );
		WP_CLI::line( sprintf( __('%s duplicates found.', 'imperial'), $dup_statuses ) );
		WP_CLI::success( sprintf( __('%s course statuses added.', 'imperial'), $statuses_added ) );
	}

	/**
	 * Example command
	 *
	 * @subcommand convert-question-activities
	 */
	function convert_questions() {
		global $wpdb;

		wp_defer_comment_counting( true );

			// Directly querying the database is normally frowned upon, but all
			// of the API functions will return full objects or are limited to 
			// single post_IDs which will overall suck up lots of memory and take
			// far longer to process, in addition to calling filters and actions
			// that we wouldn't want running. This is best, just not as future proof.
			// But then this isn't expected to run more than once.

		$per_page = 40;
		$user_id_offset = $count = $questions_updated = 0;

		$users_sql = "SELECT ID FROM $wpdb->users WHERE ID > %d LIMIT $per_page";
		$answers_sql = "SELECT * FROM $wpdb->comments WHERE comment_type = 'sensei_user_answer' AND user_id = %d GROUP BY comment_post_ID ";
		$grades_sql = "SELECT comment_post_ID, comment_content FROM $wpdb->comments WHERE comment_type = 'sensei_user_grade' AND user_id = %d GROUP BY comment_post_ID ";
		$notes_sql = "SELECT comment_post_ID, comment_content FROM $wpdb->comments WHERE comment_type = 'sensei_answer_notes' AND user_id = %d GROUP BY comment_post_ID ";

		// $per_page users at a time
		while ( $user_ids = $wpdb->get_col( $wpdb->prepare($users_sql, $user_id_offset) ) ) {

			foreach ( $user_ids AS $user_id ) {

				$answer_grades = $answer_notes = array();

				// Pre-process the answer grades
				$_answer_grades = $wpdb->get_results( $wpdb->prepare($grades_sql, $user_id), ARRAY_A );
				foreach ( $_answer_grades as $answer_grade ) {
					// This will overwrite existing entries with the newer ones
					$answer_grades[ $answer_grade['comment_post_ID'] ] = $answer_grade['comment_content'];
				}
				unset( $_answer_grades );
//	error_log(count($answer_grades) . ' : ' . print_r($answer_grades, true));

				// Pre-process the answer notes
				$_answer_notes = $wpdb->get_results( $wpdb->prepare($notes_sql, $user_id), ARRAY_A );
				foreach ( $_answer_notes as $answer_note ) {
					// This will overwrite existing entries with the newer ones
					$answer_notes[ $answer_note['comment_post_ID'] ] = $answer_note['comment_content'];
				}
				unset( $_answer_notes );
//	error_log(count($answer_notes) . ' : ' . print_r($answer_notes, true));

				// Grab all the questions for the user
				$sql = $wpdb->prepare($answers_sql, $user_id);
//	error_log($sql);
				$answers = $wpdb->get_results( $sql, ARRAY_A );
//	error_log(count($answers) . ' : ' . print_r($answers, true));
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
//							'comment_approved' => 'log', // New status for 'sensei_user_answer'
						);
					$data = array_merge($answer, $data);
//					error_log( print_r($data, true));

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
						$questions_updated++;
					}
				}
				$count++;
				if ( 0 == ( $count % 100 ) ) {
					WP_CLI::line( '.' );
//					break 2; // Handbreak to stop rather than process it all
				}
			}
			$wpdb->flush();
			$user_id_offset = $user_id;
		}
//		wp_defer_comment_counting( false );

		WP_CLI::line( 'Timer end: ' . timer_stop() . ' secs' );
		WP_CLI::line( 'Memory used: ' . size_format(memory_get_usage()) );
		WP_CLI::success( sprintf( __('%s questions updated.', 'imperial'), $questions_updated ) );
	}

} // Imperial_Sensei_CLI_Command

WP_CLI::add_command( 'sensei', 'Imperial_Sensei_CLI_Command' );
