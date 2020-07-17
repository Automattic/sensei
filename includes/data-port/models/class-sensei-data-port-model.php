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
 * This class handles the port for a single post.
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
	 * The schema for the model.
	 *
	 * @var Sensei_Data_Port_Schema
	 */
	protected $schema;

	/**
	 * Sensei_Data_Port_Model constructor.
	 */
	protected function __construct() {
		// Silence is golden.
	}

	/**
	 * Get the model key to identify items in log entries.
	 *
	 * @return string
	 */
	abstract public function get_model_key();

	/**
	 * Get the data to return with any errors.
	 *
	 * @param array $data Base error data to pass along.
	 *
	 * @return array
	 */
	public function get_error_data( $data = [] ) {
		$data['type'] = $this->get_model_key();

		$entry_id = $this->get_value( $this->schema->get_column_id() );
		if ( $entry_id ) {
			$data['entry_id'] = $entry_id;
		}

		$entry_title = $this->get_value( $this->schema->get_column_title() );
		if ( $entry_id ) {
			$data['entry_title'] = $entry_title;
		}

		$post_id = $this->get_post_id();
		if ( $post_id ) {
			$data['post_id'] = $post_id;
		}

		return $data;
	}

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

		$schema_array = $this->schema->get_schema();
		if ( ! isset( $schema_array[ $field ] ) ) {
			return null;
		}

		// If the field exists, assume it is an empty string. Otherwise, set it to null.
		$value  = isset( $this->data[ $field ] ) ? '' : null;
		$config = $schema_array[ $field ];

		// If we're creating a new post, get the default value.
		if ( $this->is_new() && isset( $config['default'] ) ) {
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

		foreach ( $this->schema->get_schema() as $field => $field_config ) {
			// If the field is required, it must be set.
			if ( ! empty( $field_config['required'] ) && empty( $data[ $field ] ) ) {
				return false;
			}

			if ( isset( $data[ $field ] ) ) {
				if (
					isset( $field_config['validator'] )
					&& ! call_user_func( $field_config['validator'], $field, $this )
				) {
					return false;
				}

				continue;
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
	 * Set the data for the model.
	 *
	 * @param array $data The data array.
	 */
	public function set_data( $data ) {
		$this->data = $data;
	}
}
