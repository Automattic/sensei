<?php
/**
 * File containing Sensei_Updates class.
 *
 * @package sensei-lms
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Updates Class
 *
 * Class that contains the updates for Sensei data and structures.
 *
 * @author Automattic
 * @since 1.1.0
 */
class Sensei_Updates {
	/**
	 * Handles deprecation notices for old methods.
	 *
	 * @param string $name Method name.
	 * @param array  $args Called method arguments.
	 *
	 * @return mixed
	 * @throws BadMethodCallException When method is not known.
	 */
	public function __call( $name, $args ) {
		$methods = [
			'sensei_updates_page'                         => [
				'version' => '3.7.0',
				'default' => null,
			],
			'function_in_whitelist'                       => [
				'version' => '3.7.0',
				'default' => false,
			],
			'update'                                      => [
				'version' => '3.7.0',
				'default' => false,
			],
			'set_default_quiz_grade_type'                 => [
				'version' => '3.7.0',
				'default' => null,
			],
			'set_default_question_type'                   => [
				'version' => '3.7.0',
				'default' => null,
			],
			'update_question_answer_data'                 => [
				'version' => '3.7.0',
				'default' => true,
			],
			'update_question_grade_points'                => [
				'version' => '3.7.0',
				'default' => true,
			],
			'convert_essay_paste_questions'               => [
				'version' => '3.7.0',
				'default' => true,
			],
			'set_random_question_order'                   => [
				'version' => '3.7.0',
				'default' => true,
			],
			'set_default_show_question_count'             => [
				'version' => '3.7.0',
				'default' => true,
			],
			'remove_deleted_user_activity'                => [
				'version' => '3.7.0',
				'default' => true,
			],
			'add_teacher_role'                            => [
				'version' => '3.7.0',
				'default' => true,
			],
			'restructure_question_meta'                   => [
				'version' => '3.7.0',
				'default' => true,
			],
			'update_quiz_settings'                        => [
				'version' => '3.7.0',
				'default' => true,
			],
			'reset_lesson_order_meta'                     => [
				'version' => '3.7.0',
				'default' => true,
			],
			'update_question_gap_fill_separators'         => [
				'version' => '3.7.0',
				'default' => true,
			],
			'update_quiz_lesson_relationship'             => [
				'version' => '3.7.0',
				'default' => true,
			],
			'status_changes_fix_lessons'                  => [
				'version' => '3.7.0',
				'default' => true,
			],
			'status_changes_convert_lessons'              => [
				'version' => '3.7.0',
				'default' => true,
			],
			'status_changes_convert_courses'              => [
				'version' => '3.7.0',
				'default' => true,
			],
			'status_changes_repair_course_statuses'       => [
				'version' => '3.7.0',
				'default' => true,
			],
			'status_changes_convert_questions'            => [
				'version' => '3.7.0',
				'default' => true,
			],
			'update_legacy_sensei_comments_status'        => [
				'version' => '3.7.0',
				'default' => true,
			],
			'update_comment_course_lesson_comment_counts' => [
				'version' => '3.7.0',
				'default' => true,
			],
			'remove_legacy_comments'                      => [
				'version' => '3.7.0',
				'default' => true,
			],
			'index_comment_status_field'                  => [
				'version' => '3.7.0',
				'default' => true,
			],
			'enhance_teacher_role'                        => [
				'version' => '3.7.0',
				'default' => true,
			],
			'recalculate_enrolment'                       => [
				'version' => '3.7.0',
				'default' => true,
			],
		];

		if ( isset( $methods[ $name ] ) ) {
			_deprecated_function( esc_html( 'Sensei_Updates::' . $name ), esc_html( $methods[ $name ]['version'] ) );

			return isset( $methods[ $name ]['default'] ) ? $methods[ $name ]['default'] : null;
		}

		throw new BadMethodCallException( sprintf( 'Sensei_Updates::%s method does not exist' ) );
	}

	/**
	 * Sets the role capabilities for WordPress users.
	 *
	 * @since 1.1.0
	 * @deprecated 3.7.0
	 */
	public function assign_role_caps() {
		_deprecated_function( __METHOD__, '3.7.0', 'Sensei_Main::assign_role_caps' );

		Sensei()->assign_role_caps();
	}

	/**
	 * Add Sensei Admin Capabilities.
	 *
	 * @deprecated 3.7.0
	 *
	 * @return bool
	 */
	public function add_sensei_caps() {
		_deprecated_function( __METHOD__, '3.7.0', 'Sensei_Main::add_sensei_admin_caps' );

		return Sensei()->add_sensei_admin_caps();
	}

	/**
	 * Add editor role capabilities.
	 *
	 * @deprecated 3.7.0
	 *
	 * @return bool
	 */
	public function add_editor_caps() {
		_deprecated_function( __METHOD__, '3.7.0', 'Sensei_Main::add_editor_caps' );

		return Sensei()->add_editor_caps();
	}
}

/**
 * Class WooThemes_Sensei_Updates
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Updates extends Sensei_Updates {} // phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound
