<?php
/**
 * File containing the Sensei_Data_Port_Schema class.
 *
 * @package sensei
 */

/**
 * Class Sensei_Data_Port_Schema
 *
 * This class defines the schema for a data port entity.
 */
abstract class Sensei_Data_Port_Schema {
	const COLUMN_ID   = 'id';
	const COLUMN_SLUG = 'slug';

	/**
	 * Get the schema for the data type.
	 *
	 * @return array {
	 *     @type array $$field_name {
	 *          @type string   $type       Type of data. Options: string, int, float, bool, slug, ref, email, url-or-file, username, video.
	 *          @type string   $pattern    Regular expression that the value should match (Optional).
	 *          @type mixed    $default    Default value if not set or invalid. Default is `null` (Optional).
	 *          @type bool     $required   True if a non-empty value is required. Default is `false` (Optional).
	 *          @type bool     $allow_html True if HTML should be allowed. Default is `false` (Optional).
	 *          @type callable $validator  Callable to use when validating data (Optional).
	 *     }
	 * }
	 */
	abstract public function get_schema();

	/**
	 * Get the post type of the data port entity.
	 *
	 * @return string
	 */
	abstract public function get_post_type();

	/**
	 * Get the column name for the title.
	 *
	 * @return string
	 */
	abstract public function get_column_title();

	/**
	 * Get the column name for the id.
	 *
	 * @return string
	 */
	public function get_column_id() {
		return self::COLUMN_ID;
	}

	/**
	 * Get the column name for the slug.
	 *
	 * @return string
	 */
	public function get_column_slug() {
		return self::COLUMN_SLUG;
	}

	/**
	 * Get the optional fields in the schema.
	 *
	 * @return array Field names.
	 */
	public function get_required_fields() {
		$schema = $this->get_schema();

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
	 * Get the optional fields in the schema.
	 *
	 * @return array Field names.
	 */
	public function get_optional_fields() {
		$schema = $this->get_schema();

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
}
