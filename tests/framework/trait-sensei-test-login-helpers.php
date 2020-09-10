<?php
/**
 * File with trait Sensei_Test_Login_Helpers.
 *
 * @package sensei-tests
 */

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Using PHPUnit conventions.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helpers for logging in as different users.
 *
 * @since 3.6.0
 */
trait Sensei_Test_Login_Helpers {
	protected function get_user_by_role( $role, $variant = '' ) {
		$slug = $role . $variant;
		$user = get_user_by( 'email', 'sensei_' . $slug . '_user@example.com' );
		if ( empty( $user ) ) {
			$user_id = wp_create_user(
				'sensei_' . $slug . '_user',
				'sensei_' . $slug . '_user',
				'sensei_' . $slug . '_user@example.com'
			);
			$user    = get_user_by( 'ID', $user_id );
			$user->set_role( $role );
		}
		return $user->ID;
	}

	protected function login_as_admin() {
		return $this->login_as( $this->get_user_by_role( 'administrator' ) );
	}

	protected function login_as_teacher() {
		return $this->login_as( $this->get_user_by_role( 'teacher' ) );
	}

	protected function login_as_teacher_b() {
		return $this->login_as( $this->get_user_by_role( 'teacher', '_b' ) );
	}

	protected function login_as_student() {
		return $this->login_as( $this->get_user_by_role( 'subscriber' ) );
	}

	protected function login_as_student_b() {
		return $this->login_as( $this->get_user_by_role( 'subscriber', '_b' ) );
	}

	protected function login_as( $user_id ) {
		wp_set_current_user( $user_id );
		return $this;
	}

	protected function logout() {
		$this->login_as( 0 );
		wp_logout();
		return $this;
	}
}
