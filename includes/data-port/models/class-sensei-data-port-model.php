<?php
/**
 * File containing the Sensei_Data_Port_Model class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defines the expected data to port to/from and handles the port.
 */
abstract class Sensei_Data_Port_Model {
	/**
	 * Data in its array form.
	 *
	 * @var array
	 */
	private $data;

	/**
	 * Post ID of top-most post. This will be null if creating a new post.
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * The default author to be used in courses if none is provided.
	 *
	 * @var int
	 */
	private $default_author;

	/**
	 * Sensei_Data_Port_Model constructor.
	 */
	private function __construct() {
		// Silence is golden.
	}

	/**
	 * Set up item from an array.
	 *
	 * @param array $data            Data to restore item from.
	 * @param int   $default_author  The default author.
	 *
	 * @return static
	 */
	public static function from_source_array( $data, $default_author = 0 ) {
		$self = new static();
		$self->restore_from_source_array( $data );
		$self->default_author = $default_author;

		$post_id = $self->get_existing_post_id();
		if ( $post_id ) {
			$self->set_post_id( $post_id );
		}

		return $self;
	}

	/**
	 * Check to see if the post already exists in the database.
	 *
	 * @return int
	 */
	abstract protected function get_existing_post_id();

	/**
	 * Create a new post or update an existing post.
	 *
	 * @return true|WP_Error
	 */
	abstract public function sync_post();

	/**
	 * Get the data to return with any errors.
	 *
	 * @param array $base_data Base error data to pass along.
	 *
	 * @return array
	 */
	abstract public function get_error_data( $base_data = [] );

	/**
	 * Get the value of a field.
	 *
	 * @param string $field Field name.
	 *
	 * @return mixed
	 */
	public function get_value( $field ) {
		if (
			isset( $this->data[ $field ] )
			&& '' !== $this->data[ $field ]
		) {
			return $this->data[ $field ];
		}

		$schema = static::get_schema();
		if ( ! isset( $schema[ $field ] ) ) {
			return null;
		}

		// If the field exists, assume it is an empty string. Otherwise, set it to null.
		$value  = isset( $this->data[ $field ] ) ? '' : null;
		$config = $schema[ $field ];

		// If we're creating a new post, get the default value.
		if ( ! $this->get_post_id() && isset( $config['default'] ) ) {
			if ( is_callable( $config['default'] ) ) {
				return call_user_func( $config['default'], $field, $this );
			}

			return $config['default'];
		}

		return $value;
	}

	/**
	 * Check if all required fields are set.
	 *
	 * @return bool
	 */
	public function is_valid() {
		$data = $this->get_data();

		foreach ( static::get_schema() as $field => $field_config ) {
			if ( isset( $data[ $field ] ) ) {
				if (
					isset( $field_config['validator'] )
					&& ! call_user_func( $field_config['validator'], $field, $this )
				) {
					return false;
				}

				continue;
			}

			// If the field is required, it must be set.
			if ( ! empty( $field_config['required'] ) ) {
				return false;
			}

			// If a default exists as well as a pattern, a `null` value is for a field that didn't match the pattern.
			if (
				array_key_exists( $field, $data )
				&& ! empty( $field_config['default'] )
				&& ! empty( $field_config['pattern'] )
			) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Restore object from an array.
	 *
	 * @param array $data Data to restore item from.
	 */
	private function restore_from_source_array( $data ) {
		$sanitized_data = [];
		$schema         = static::get_schema();

		foreach ( $data as $key => $value ) {
			if ( ! isset( $schema[ $key ] ) ) {
				continue;
			}

			$config = $schema[ $key ];
			$value  = trim( $value );

			if ( null !== $value ) {
				switch ( $config['type'] ) {
					case 'int':
						$value = intval( $value );
						break;
					case 'float':
						$value = floatval( $value );
						break;
					case 'bool':
						$value = boolval( $value );
						break;
					case 'slug':
						$value = sanitize_title( $value );
						break;
					case 'email':
						$value = sanitize_email( $value );
						break;
					case 'url':
						$value = esc_url_raw( $value );
						break;
					default:
						if (
							isset( $config['pattern'] )
							&& 1 !== preg_match( $config['pattern'], $value )
						) {
							$value = null;
						} elseif ( ! empty( $config['allow_html'] ) ) {
							$value = wp_kses_post( $value );
						} else {
							$value = sanitize_text_field( $value );
						}
				}
			}

			$sanitized_data[ $key ] = $value;
		}

		$this->data = $sanitized_data;
	}

	/**
	 * Get the post ID that this references.
	 *
	 * @return int
	 */
	public function get_post_id() {
		return $this->post_id;
	}

	/**
	 * Set the post ID that this references.
	 *
	 * @param int $id Post ID.
	 */
	protected function set_post_id( $id ) {
		$this->post_id = $id;
	}

	/**
	 * Get the data for the model.
	 *
	 * @return array
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Get the optional fields in the schema.
	 *
	 * @return array Field names.
	 */
	public static function get_optional_fields() {
		$schema = static::get_schema();

		return array_values(
			array_filter(
				array_map(
					function( $field ) use ( $schema ) {
						if ( empty( $schema[ $field ]['required'] ) ) {
							return $field;
						}

						return false;
					},
					array_keys( $schema )
				)
			)
		);
	}

	/**
	 * Get the optional fields in the schema.
	 *
	 * @return array Field names.
	 */
	public static function get_required_fields() {
		$schema = static::get_schema();

		return array_values(
			array_filter(
				array_map(
					function( $field ) use ( $schema ) {
						if ( ! empty( $schema[ $field ]['required'] ) ) {
							return $field;
						}

						return false;
					},
					array_keys( $schema )
				)
			)
		);
	}

	/**
	 * Get the schema for the data type.
	 *
	 * @return array {
	 *     @type array $$field_name {
	 *          @type string   $type       Type of data. Options: string, int, float, bool, slug, ref, email, url.
	 *          @type string   $pattern    Regular expression that the value should match (Optional).
	 *          @type mixed    $default    Default value if not set or invalid. Default is `null` (Optional).
	 *          @type bool     $required   True if a non-empty value is required. Default is `false` (Optional).
	 *          @type bool     $allow_html True if HTML should be allowed. Default is `false` (Optional).
	 *          @type callable $validator  Callable to use when validating data (Optional).
	 *     }
	 * }
	 */
	public static function get_schema() {
		_doing_it_wrong( __METHOD__, 'This should be implemented by the child classes.', '3.1.0' );

		return [];
	}

	/**
	 * Adds a thumbnail to a post. The source of the thumbnail can be either a filename from the media library or an
	 * external URL.
	 *
	 * @param string $column_name  The CSV column name which has the image source.
	 * @param int    $post_id      The post id.
	 *
	 * @return bool|WP_Error  True on success, WP_Error on failure.
	 */
	protected function add_thumbnail_to_post( $column_name, $post_id ) {
		$thumbnail = $this->get_value( $column_name );

		if ( null === $thumbnail ) {
			return true;
		}

		if ( '' === $thumbnail ) {
			delete_post_meta( $post_id, '_thumbnail_id' );
		} else {
			$attachment_id = Sensei_Data_Port_Utilities::get_attachment_from_source( $thumbnail, $post_id );

			if ( is_wp_error( $attachment_id ) ) {
				return $attachment_id;
			}

			update_post_meta( $post_id, '_thumbnail_id', $attachment_id );
		}

		return true;
	}

	/**
	 * Get the default author.
	 *
	 * @return int
	 */
	public function get_default_author(): int {
		return $this->default_author;
	}
}
