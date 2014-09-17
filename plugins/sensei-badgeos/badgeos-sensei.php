<?php
/**
 * Plugin Name: BadgeOS Sensei Add-On
 * Plugin URI: http://www.woothemes.com/products/sensei/
 * Description: This BadgeOS add-on integrates BadgeOS features with Sensei
 * Tags: sensei
 * Author: Credly
 * Version: 1.0.0
 * Author URI: https://credly.com/
 * License: GNU AGPLv3
 * License URI: http://www.gnu.org/licenses/agpl-3.0.html
 */

/*
 * Copyright © 2013 Credly, LLC
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Affero General
 * Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>;.
*/

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once('woo-includes/woo-functions.php');

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '6febd1627ac0d3adb34a73e1f97ed52b', 247517 );

class BadgeOS_Sensei {

	/**
	 * Plugin Basename
	 *
	 * @var string
	 */
	public $basename = '';

	/**
	 * Plugin Directory Path
	 *
	 * @var string
	 */
	public $directory_path = '';

	/**
	 * Plugin Directory URL
	 *
	 * @var string
	 */
	public $directory_url = '';

	/**
	 * BadgeOS Sensei Triggers
	 *
	 * @var array
	 */
	public $triggers = array();

	/**
	 * Actions to forward for splitting an action up
	 *
	 * @var array
	 */
	public $actions = array();

	/**
	 *
	 */
	function __construct() {

		// Define plugin constants
		$this->basename = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url = plugin_dir_url( __FILE__ );

		// If BadgeOS is unavailable, deactivate our plugin
		add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );

		// Sensei Action Hooks
		$this->triggers = array(
			'sensei_user_course_start' => __( 'Enrolled in Course', 'badgeos-sensei' ),
			'badgeos_sensei_user_course_start_category' => __( 'Enrolled in Course from a Category', 'badgeos-sensei' ),
			'sensei_user_quiz_grade' => __( 'Passed Quiz', 'badgeos-sensei' ),
			'badgeos_sensei_user_quiz_grade_specific' => __( 'Minimum % Grade on a Quiz', 'badgeos-sensei' ),
			'sensei_user_lesson_end' => __( 'Completed Lesson', 'badgeos-sensei' ),
			'sensei_user_course_end' => __( 'Completed Course', 'badgeos-sensei' ),
			'badgeos_sensei_user_course_end_category' => __( 'Completed Course from a Category', 'badgeos-sensei' )
		);

		// Actions that we need split up
		$this->actions = array(
			'sensei_user_course_start' => 'badgeos_sensei_user_course_start_category',
			'sensei_user_course_end' => 'badgeos_sensei_user_course_end_category',
			'sensei_user_quiz_grade' => 'badgeos_sensei_user_quiz_grade_specific'

			/*
			 * Default action split will be badgeos_sensei_{$action}, can set multiple actions with 'actions'
			 *
			 * 'original_action' => array(
			 * 	'priority' => 12,
			 * 	'accepted_args' => 5,
			 * 	'actions' => array(
			 * 		'another_action1'
			 * 		'another_action2'
			 * 		'another_action3'
			 * 	)
			 * )
			 *
			 *
			 * shorthand forwarding to a single action
			 *
			 * 'original_action' => 'another_action'
			 */
		);

        add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 11 );

	}

	/**
	 * Check if BadgeOS is available
	 *
	 * @since  1.0.0
	 * @return bool True if BadgeOS is available, false otherwise
	 */
	public static function meets_requirements() {

		if ( !class_exists( 'BadgeOS' ) || !function_exists( 'badgeos_get_user_earned_achievement_types' ) ) {
			return false;
		}
		elseif ( !class_exists( 'WooThemes_Sensei' ) || version_compare( $GLOBALS[ 'woothemes_sensei' ]->version, '1.4.0', '<' ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Generate a custom error message and deactivates the plugin if we don't meet requirements
	 *
	 * @since 1.0.0
	 */
	public function maybe_disable_plugin() {

		if ( !$this->meets_requirements() ) {
			// Display our error
			echo '<div id="message" class="error">';

			if ( !class_exists( 'BadgeOS' ) || !function_exists( 'badgeos_get_user_earned_achievement_types' ) ) {
				echo '<p>' . sprintf( __( 'BadgeOS Sensei Add-On requires BadgeOS and has been <a href="%s">deactivated</a>. Please install and activate BadgeOS and then reactivate this plugin.', 'badgeos-sensei' ), admin_url( 'plugins.php' ) ) . '</p>';
			}

			if ( !class_exists( 'WooThemes_Sensei' ) || version_compare( $GLOBALS[ 'woothemes_sensei' ]->version, '1.4.0', '<' ) ) {
				echo '<p>' . sprintf( __( 'BadgeOS Sensei Add-On requires Woothemes Sensei and has been <a href="%s">deactivated</a>. Please install and activate Woothemes Sensei and then reactivate this plugin.', 'badgeos-sensei' ), admin_url( 'plugins.php' ) ) . '</p>';
			}

			echo '</div>';

			// Deactivate our plugin
			deactivate_plugins( $this->basename );
		}
	}

	/**
	 * Load the plugin textdomain and include files if plugin meets requirements
	 *
	 * @since 1.0.0
	 */
	public function plugins_loaded() {
		// Load translations
		load_plugin_textdomain( 'badgeos-sensei', false, dirname( $this->basename ) . '/languages/' );

		if ( $this->meets_requirements() ) {
			require_once( $this->directory_path . '/includes/rules-engine.php' );
			require_once( $this->directory_path . '/includes/steps-ui.php' );

			$this->action_forwarding();
		}
	}

	/**
	 * Forward WP actions into a new set of actions
	 *
	 * @since 1.0.0
	 */
	public function action_forwarding() {
		foreach ( $this->actions as $action => $args ) {
			$priority = 10;
			$accepted_args = 20;

			if ( is_array( $args ) ) {
				if ( isset( $args[ 'priority' ] ) ) {
					$priority = $args[ 'priority' ];
				}

				if ( isset( $args[ 'accepted_args' ] ) ) {
					$accepted_args = $args[ 'accepted_args' ];
				}
			}

			add_action( $action, array( $this, 'action_forward' ), $priority, $accepted_args );
		}
	}

	/**
	 * Forward a specific WP action into a new set of actions
	 *
	 * @return mixed Action return
	 *
	 * @since 1.0.0
	 */
	public function action_forward() {
		$action = current_filter();
		$args = func_get_args();

		if ( isset( $this->actions[ $action ] ) ) {
			if ( is_array( $this->actions[ $action ] )
				 && isset( $this->actions[ $action ][ 'actions' ] ) && is_array( $this->actions[ $action ][ 'actions' ] )
				 && !empty( $this->actions[ $action ][ 'actions' ] ) ) {
				foreach ( $this->actions[ $action ][ 'actions' ] as $new_action ) {
					if ( 0 !== strpos( $new_action, strtolower( __CLASS__ ) . '_' ) ) {
						$new_action = strtolower( __CLASS__ ) . '_' . $new_action;
					}

					$action_args = $args;

					array_unshift( $action_args, $new_action );

					call_user_func_array( 'do_action', $action_args );
				}

				return null;
			}
			elseif ( is_string( $this->actions[ $action ] ) ) {
				$action =  $this->actions[ $action ];
			}
		}

		if ( 0 !== strpos( $action, strtolower( __CLASS__ ) . '_' ) ) {
			$action = strtolower( __CLASS__ ) . '_' . $action;
		}

		array_unshift( $args, $action );

		return call_user_func_array( 'do_action', $args );
	}

}

$GLOBALS[ 'badgeos_sensei' ] = new BadgeOS_Sensei();
