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
	 * Create a user. If the user exists, the method simply returns the user.
	 *
	 * @param string $username The username.
	 * @param string $email    User's email.
	 * @param string $role     The user's role.
	 *
	 * @return WP_User|WP_Error  WP_User on success, WP_Error on failure.
	 */
	public static function create_user( $username, $email = '', $role = '' ) {
		$user = get_user_by( 'login', $username );

		if ( ! $user ) {
			$user_id = wp_create_user( $username, wp_generate_password(), $email );

			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}

			$user = get_user_by( 'ID', $user_id );

			if ( ! empty( $role ) && $user ) {
				$user->set_role( $role );
			}
		}

		return $user;
	}

	/**
	 * Get an attachment by providing its source. The source can be a URL or a filename from the media library. If the
	 * source is an external URL, it will be retrieved and an appropriate attachment will be created.
	 *
	 * @param string $source             Filename or URL.
	 * @param int    $parent_id          Id of the parent post.
	 * @param array  $allowed_mime_types Allowed mime types.
	 *
	 * @return int|WP_Error  Attachment id on success, WP_Error on failure.
	 */
	public static function get_attachment_from_source( $source, $parent_id = 0, $allowed_mime_types = null ) {
		if ( false === filter_var( $source, FILTER_VALIDATE_URL ) ) {

			$attachments = get_posts(
				[
					'fields'         => 'ids',
					'post_type'      => 'attachment',
					'posts_per_page' => 1,
					'post_status'    => 'any',
					'meta_compare'   => 'REGEXP',
					'meta_key'       => '_wp_attached_file', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- No faster way to search an attachment from its filename.
					'meta_value'     => '(^|/)' . sanitize_file_name( $source ) . '$', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- See above.
				]
			);

			if ( empty( $attachments ) ) {
				return new WP_Error(
					'sensei_data_port_attachment_not_found',
					__( 'No attachment with the specified file name was found.', 'sensei-lms' )
				);
			}

			$attachment_id   = $attachments[0];
			$valid_mime_type = self::validate_file_mime_type_by_attachment_id( $attachment_id, $allowed_mime_types );

			if ( is_wp_error( $valid_mime_type ) ) {
				return $valid_mime_type;
			}
		} else {
			// In case a local URL is provided, try to convert it to the attachment.
			$attachment_id = attachment_url_to_postid( $source );

			if ( ! $attachment_id ) {
				$attachment_id = self::create_attachment_from_url( $source, $parent_id, $allowed_mime_types );
			}
		}

		return $attachment_id;
	}

	/**
	 * This method retrieves a file from an external url, creates an attachment and links the attachment with the
	 * downloaded file. If the file has been already downloaded an linked to an attachment, it returns the existing
	 * attachment instead.
	 *
	 * @param string $external_url       The external url.
	 * @param int    $parent_id          The attachment's parent id.
	 * @param array  $allowed_mime_types Allowed mime types.
	 *
	 * @return int|WP_Error  The attachment id or an error.
	 */
	public static function create_attachment_from_url( $external_url, $parent_id = 0, $allowed_mime_types = null ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$existing_attachment = get_posts(
			[
				'fields'         => 'ids',
				'post_type'      => 'attachment',
				'posts_per_page' => 1,
				'post_status'    => 'inherit',
				'post_parent'    => $parent_id,
				'meta_key'       => '_sensei_attachment_source_key', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Only attachments are checked.
				'meta_value'     => md5( $external_url ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- See above.
			]
		);

		if ( ! empty( $existing_attachment ) ) {
			$attachment_id   = $existing_attachment[0];
			$valid_mime_type = self::validate_file_mime_type_by_attachment_id( $attachment_id, $allowed_mime_types );

			if ( is_wp_error( $valid_mime_type ) ) {
				return $valid_mime_type;
			}

			return $attachment_id;
		}

		/**
		 * Filters the timeout value for the HTTP request which retrieves an external attachment.
		 *
		 * Increase this value in case big attachments are imported and the request to get them
		 * times out.
		 *
		 * @param float $timeout Time in seconds until a request times out. Default 10.
		 *
		 * @since 3.3.0
		 */
		$timeout  = apply_filters( 'sensei_import_attachment_request_timeout', 10 );
		$response = wp_safe_remote_get( $external_url, [ 'timeout' => $timeout ] );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error(
				'sensei_data_port_attachment_failure',
				__( 'Error encountered while retrieving the attachment from the provided URL.', 'sensei-lms' )
			);
		}

		$upload_result = wp_upload_bits( basename( $external_url ), null, wp_remote_retrieve_body( $response ) );

		if ( ! empty( $upload_result['error'] ) ) {
			return new WP_Error( 'sensei_data_port_storing_file_failure', $upload_result['error'] );
		}

		$file_path = $upload_result['file'];
		$file_url  = $upload_result['url'];

		$wp_filetype     = wp_check_filetype_and_ext( $file_path, basename( $file_path ) );
		$valid_mime_type = self::validate_file_mime_type( $wp_filetype['type'], $allowed_mime_types, $file_path );

		if ( is_wp_error( $valid_mime_type ) ) {
			return $valid_mime_type;
		}

		$attachment_args = [
			'post_content'   => '',
			'post_title'     => basename( $file_path ),
			'post_mime_type' => $wp_filetype['type'],
			'guid'           => $file_url,
			'post_status'    => 'inherit',
		];

		$attachment_id = wp_insert_attachment( $attachment_args, $file_path, $parent_id );
		update_post_meta( $attachment_id, '_sensei_attachment_source_key', md5( $external_url ) );

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		if ( 0 === $attachment_id ) {
			return new WP_Error(
				'sensei_data_port_attachment_failure',
				__( 'Attachment insertion failed.', 'sensei-lms' )
			);
		}

		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $file_path ) );

		return $attachment_id;
	}

	/**
	 * Validate file mime type by attachment ID.
	 *
	 * @param int   $attachment_id      Attachment ID.
	 * @param array $allowed_mime_types Allowed mime types.
	 *
	 * @return true|WP_Error
	 */
	private static function validate_file_mime_type_by_attachment_id( $attachment_id, $allowed_mime_types = null ) {
		$file_path   = get_attached_file( $attachment_id );
		$wp_filetype = wp_check_filetype_and_ext( $file_path, basename( $file_path ) );

		return self::validate_file_mime_type( $wp_filetype['type'], $allowed_mime_types, $file_path );
	}

	/**
	 * Validate file mime type.
	 *
	 * @param string $mime_type          File mime type.
	 * @param array  $allowed_mime_types Allowed mime types.
	 * @param string $file_name          File name to validate by extension, as fallback for administrators.
	 *
	 * @return true|WP_Error
	 */
	public static function validate_file_mime_type( $mime_type, $allowed_mime_types = null, $file_name = null ) {
		if ( null === $allowed_mime_types ) {
			return true;
		}

		$valid_mime_type  = $mime_type && in_array( $mime_type, $allowed_mime_types, true );
		$valid_extensions = self::mime_types_extensions( $allowed_mime_types );

		// If we cannot determine the type, allow check based on extension for administrators.
		if ( ! $mime_type && current_user_can( 'unfiltered_upload' ) && null !== $file_name ) {
			$valid_mime_type = in_array( pathinfo( $file_name, PATHINFO_EXTENSION ), $valid_extensions, true );
		}

		if ( ! $valid_mime_type ) {
			return new WP_Error(
				'sensei_data_port_unexpected_file_type',
				// translators: Placeholder is list of file extensions.
				sprintf( __( 'File type is not supported. Must be one of the following: %s.', 'sensei-lms' ), implode( ', ', $valid_extensions ) )
			);
		}

		return true;
	}

	/**
	 * Get an array of extensions.
	 *
	 * @param array $mime_types Array of mime types.
	 *
	 * @return array Array of valid extensions.
	 */
	private static function mime_types_extensions( $mime_types ) {
		$extensions = [];
		foreach ( array_keys( $mime_types ) as $ext_list ) {
			$extensions = array_merge( $extensions, explode( '|', $ext_list ) );
		}

		return array_unique( $extensions );
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
		if ( ! $term_name_path ) {
			return false;
		}

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
		if ( empty( trim( $str_list ) ) ) {
			return [];
		}

		$str_list = self::replace_curly_quotes( $str_list );
		$list     = preg_split( '/,(?=(?:[^\"]*\"[^\"]*\")*[^\"]*$)/', $str_list );

		if ( $remove_quotes ) {
			$list = array_map(
				function( $value ) {
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

	/**
	 * Serialize a list of terms into comma-separated list.
	 * Adds quotes if name contains commas.
	 *
	 * @param WP_Term[] $terms
	 *
	 * @return string
	 */
	public static function serialize_term_list( $terms ) {
		$names = array_map( 'Sensei_Data_Port_Utilities::serialize_term', $terms );
		return implode( ',', $names );
	}

	/**
	 * Return term name and hierarchy representation, in the format of 'Parent > Child'.
	 *
	 * @param WP_Term $term
	 *
	 * @return string
	 */
	public static function serialize_term( WP_Term $term ) {

		$name = self::escape_list_item( $term->name );
		if ( ! empty( $term->parent ) ) {
			$parent_term = get_term( $term->parent, $term->taxonomy );
			$parent_str  = self::serialize_term( $parent_term );
			return $parent_str . ' > ' . $name;
		}
		return $name;
	}

	/**
	 * Serialize a list into comma-separated list.
	 * Wrap values in quotes if they contain a comma.
	 *
	 * @deprecated 3.5.2
	 *
	 * @param string[] $values
	 *
	 * @return string
	 */
	public static function serialize_list( $values = [] ) {
		_deprecated_function( __METHOD__, '3.5.2' );

		return ! empty( $values )
			? implode( ',', array_map( 'Sensei_Data_Port_Utilities::escape_list_item', $values ) )
			: '';
	}

	/**
	 * Wrap value in quotes if it contains a comma.
	 * Escape quotes if wrapped.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public static function escape_list_item( $value ) {
		if ( false !== strpos( $value, ',' ) ) {
			$value = '"' . str_replace( '"', '\"', $value ) . '"';
		}
		return $value;
	}

	/**
	 * Serialize ID field.
	 *
	 * @param int|int[] $ids ID or IDs array to format.
	 *
	 * @return string Serialized ID field.
	 */
	public static function serialize_id_field( $ids ) {
		if ( empty( $ids ) ) {
			return '';
		}

		return 'id:' . implode( ',id:', (array) $ids );
	}

	/**
	 * Helper method which gets a module by name and checks if the module can be applied to a course's lesson.
	 *
	 * @param string $module_name  The module name.
	 * @param int    $course_id    Course ID.
	 *
	 * @return WP_Error|WP_Term  WP_Error when the module can't be applied to the lesson, WP_Term otherwise.
	 */
	public static function get_module_for_course( $module_name, $course_id ) {
		$module = get_term_by( 'name', $module_name, 'module' );

		if ( ! $module ) {
			return new WP_Error(
				'sensei_data_port_module_not_found',
				// translators: Placeholder is the term which errored.
				sprintf( __( 'Module does not exist: %s.', 'sensei-lms' ), $module_name )
			);
		}

		$course_modules = wp_list_pluck( wp_get_post_terms( $course_id, 'module' ), 'term_id' );

		if ( ! in_array( $module->term_id, $course_modules, true ) ) {
			return new WP_Error(
				'sensei_data_port_module_not_part_of_course',
				// translators: First placeholder is the term which errored, second is the course id.
				sprintf( __( 'Module %1$s is not part of course %2$s.', 'sensei-lms' ), $module_name, $course_id )
			);
		}

		return $module;
	}
}
