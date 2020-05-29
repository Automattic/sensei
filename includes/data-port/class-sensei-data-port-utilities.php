<?php
/**
 * File containing the Sensei_Data_Port_Utilities.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A collection of utilies used in data port.
 */
class Sensei_Data_Port_Utilities {

	/**
	 * Create or update an existing user. If the user exists and the email is different, it will be updated.
	 *
	 * @param string $username  The username.
	 * @param string $email     User's email.
	 *
	 * @return int|WP_Error
	 */
	public static function create_or_update_user( $username, $email = '' ) {
		$user = get_user_by( 'login', $username );

		if ( ! $user ) {
			return wp_create_user( $username, $email, wp_generate_password() );
		}

		if ( $user->user_email !== $email && ! empty( $email ) ) {
			wp_update_user(
				[
					'ID'         => $user->ID,
					'user_email' => $email,
				]
			);
		}

		return $user->ID;
	}
}
