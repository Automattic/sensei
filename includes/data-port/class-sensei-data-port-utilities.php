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

	const CHARS_WHITESPACE_AND_QUOTES = " \"\t\n\r\0\x0B";

	/**
	 * Create a user. If the user exists, the method simply returns the user id..
	 *
	 * @param string $username  The username.
	 * @param string $email     User's email.
	 *
	 * @return int|WP_Error
	 */
	public static function create_user( $username, $email = '' ) {
		$user = get_user_by( 'login', $username );

		if ( ! $user ) {
			return wp_create_user( $username, $email, wp_generate_password() );
		}

		return $user->ID;
	}

	/**
	 * Get a term based on human readable string and create it if needed. If the taxonomy is hierarchical,
	 * this method processes that as well and returns the \WP_Term object for the last in their hierarchy.
	 *
	 * @param string $term_name_path  Term name with optional hierarchy path, separated by " > ".
	 * @param string $taxonomy_name   Name of the taxonomy.
	 * @param int    $teacher_user_id User ID for the teacher (only needed for modules).
	 *
	 * @return WP_Term|false
	 */
	public static function get_term( $term_name_path, $taxonomy_name, $teacher_user_id = null ) {
		$taxonomy = get_taxonomy( $taxonomy_name );
		if ( ! $taxonomy ) {
			return false;
		}

		if ( $taxonomy->hierarchical ) {
			$term_path = preg_split( '/ ?> ?/', $term_name_path );
		} else {
			$term_path = [ $term_name_path ];
		}

		/**
		 * Last term object.
		 *
		 * @var WP_Term $last_term
		 */
		$last_term = null;

		foreach ( $term_path as $term_name ) {
			$term_name = trim( $term_name );
			$parent_id = isset( $last_term ) ? $last_term->term_id : 0;

			$term_query = new WP_Term_Query( self::get_term_query_args( $term_name, $taxonomy_name, $teacher_user_id, $parent_id ) );
			$terms      = $term_query->get_terms();

			if ( ! empty( $terms ) ) {
				$last_term = array_shift( $terms );
			} else {
				$last_term = self::create_term( $term_name, $taxonomy_name, $teacher_user_id, $parent_id );
			}

			if ( ! $last_term ) {
				return false;
			}
		}

		return $last_term;
	}

	/**
	 * Generate the term slug.
	 *
	 * @param string $term_name       Term name.
	 * @param string $taxonomy_name   Name of the taxonomy.
	 * @param int    $teacher_user_id User ID for the teacher.
	 *
	 * @return string
	 */
	private static function get_term_slug( $term_name, $taxonomy_name, $teacher_user_id ) {
		if ( 'module' === $taxonomy_name && ! user_can( $teacher_user_id, 'manage_options' ) ) {
			return intval( $teacher_user_id ) . '-' . sanitize_title( $term_name );
		}

		return sanitize_title( $term_name );
	}

	/**
	 * Generate the arguments for the term query.
	 *
	 * @param string $term_name       Term name.
	 * @param string $taxonomy_name   Name of the taxonomy.
	 * @param int    $teacher_user_id User ID for the teacher.
	 * @param int    $parent_id       Parent ID (optional).
	 *
	 * @return array
	 */
	private static function get_term_query_args( $term_name, $taxonomy_name, $teacher_user_id, $parent_id = 0 ) {
		$args               = [];
		$args['number']     = 1;
		$args['taxonomy']   = $taxonomy_name;
		$args['hide_empty'] = false;
		$args['parent']     = $parent_id;

		if ( 'module' === $taxonomy_name ) {
			$args['slug'] = self::get_term_slug( $term_name, $taxonomy_name, $teacher_user_id );
		} else {
			$args['name'] = $term_name;
		}

		return $args;
	}

	/**
	 * Create a new term.
	 *
	 * @param string $term_name       Term name.
	 * @param string $taxonomy_name   Name of the taxonomy.
	 * @param int    $teacher_user_id User ID for the teacher.
	 * @param int    $parent_id       Parent ID (optional).
	 *
	 * @return WP_Term|false
	 */
	private static function create_term( $term_name, $taxonomy_name, $teacher_user_id, $parent_id = null ) {
		$args         = [];
		$args['slug'] = self::get_term_slug( $term_name, $taxonomy_name, $teacher_user_id );

		if ( $parent_id ) {
			$args['parent'] = $parent_id;
		}

		$term_arr = wp_insert_term( $term_name, $taxonomy_name, $args );
		if ( is_wp_error( $term_arr ) ) {
			return false;
		}

		return get_term_by( 'id', $term_arr['term_id'], $taxonomy_name );
	}

	/**
	 * Split a list and ignore commas enclosed in quotes. Legitimate quotes should be HTML escaped.
	 *
	 * @param string $str_list      List in string form, separated by commas.
	 * @param bool   $remove_quotes Remove the surrounding quotes.
	 *
	 * @return array|string[]
	 */
	public static function split_list_safely( $str_list, $remove_quotes = false ) {
		$str_list = self::replace_curly_quotes( $str_list );
		$list     = preg_split( '/,(?=(?:[^\"]*\"[^\"]*\")*[^\"]*$)/', $str_list );

		if ( $remove_quotes ) {
			$list = array_map(
				function ( $value ) {
					return trim( $value, self::CHARS_WHITESPACE_AND_QUOTES );
				},
				$list
			);
		} else {
			$list = array_map( 'trim', $list );
		}

		return $list;
	}

	/**
	 * Replace the curly quotes with straight quotes in the string.
	 *
	 * @param string $string String that possibly has curly quotes.
	 *
	 * @return string
	 */
	public static function replace_curly_quotes( $string ) {
		return str_replace( [ '“', '”' ], '"', $string );
	}
}
