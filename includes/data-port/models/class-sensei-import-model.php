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
abstract class Sensei_Import_Model extends Sensei_Data_Port_Model {

	/**
	 * The default author to be used in courses if none is provided.
	 *
	 * @var int
	 */
	private $default_author;

	/**
	 * True if this is a new entity.
	 *
	 * @var bool
	 */
	private $is_new;

	/**
	 * The import job.
	 *
	 * @var Sensei_Import_Job
	 */
	protected $import_job;

	/**
	 * Set up item from an array.
	 *
	 * @param array                   $data            Data to restore item from.
	 * @param Sensei_Data_Port_Schema $schema          The schema for the item.
	 * @param Sensei_Import_Job       $import_job      The import job.
	 *
	 * @return static
	 */
	public static function from_source_array( $data, Sensei_Data_Port_Schema $schema, Sensei_Import_Job $import_job = null ) {
		$self                 = new static();
		$self->schema         = $schema;
		$self->import_job     = $import_job;
		$self->default_author = null === $import_job ? 0 : $import_job->get_user_id();
		$self->restore_from_source_array( $data );

		$post_id = $self->get_existing_post_id();
		if ( $post_id ) {
			$self->set_post_id( $post_id );
			$self->is_new = false;
		} else {
			$self->is_new = true;
		}

		return $self;
	}


	/**
	 * Check to see if the post already exists in the database.
	 *
	 * @return int
	 */
	protected function get_existing_post_id() {
		$post_id = null;
		$data    = $this->get_data();

		if ( ! empty( $data[ $this->schema->get_column_slug() ] ) ) {
			$existing_posts = get_posts(
				[
					'post_type'      => $this->schema->get_post_type(),
					'post_name__in'  => [ $data[ $this->schema->get_column_slug() ] ],
					'posts_per_page' => 1,
					'post_status'    => 'any',
				]
			);

			if ( ! empty( $existing_posts[0] ) ) {
				return $existing_posts[0]->ID;
			}
		}

		return $post_id;
	}

	/**
	 * Restore object from an array.
	 *
	 * @param array $data Data to restore item from.
	 */
	private function restore_from_source_array( $data ) {
		$sanitized_data = [];
		$schema_array   = $this->schema->get_schema();

		foreach ( $data as $key => $value ) {
			if ( ! isset( $schema_array[ $key ] ) ) {
				continue;
			}

			$config = $schema_array[ $key ];
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
						if ( ! in_array( $value, [ '0', '1', 'true', 'false' ], true ) ) {
							$value = null;
						} else {
							$value = in_array( $value, [ '1', 'true' ], true );
						}
						break;
					case 'slug':
						$value = sanitize_title( $value );
						break;
					case 'email':
						$value = sanitize_email( $value );
						break;
					case 'url-or-file':
						$value = 0 === strpos( $value, 'http' ) ? esc_url_raw( $value ) : sanitize_file_name( $value );
						break;
					case 'username':
						$value = sanitize_user( $value );
						break;
					case 'video':
						$value = Sensei_Wp_Kses::maybe_sanitize( $value, Sensei_Course::$allowed_html );
						break;
					default:
						if (
							isset( $config['pattern'] )
							&& 1 !== preg_match( $config['pattern'], $value )
						) {
							$value = null;
						} elseif ( ! empty( $config['allow_html'] ) ) {
							$value = trim( wp_kses_post( $value ) );
						} else {
							$value = sanitize_text_field( $value );
						}
				}
			}

			$sanitized_data[ $key ] = $value;
		}

		$this->set_data( $sanitized_data );
	}


	/**
	 * Adds a thumbnail to a post. The source of the thumbnail can be either a filename from the media library or an
	 * external URL.
	 *
	 * @param string $column_name  The CSV column name which has the image source.
	 *
	 * @return bool|WP_Error  True on success, WP_Error on failure.
	 */
	protected function add_thumbnail_to_post( $column_name ) {
		$post_id   = $this->get_post_id();
		$thumbnail = $this->get_value( $column_name );

		if ( null === $thumbnail ) {
			return true;
		}

		if ( '' === $thumbnail ) {
			delete_post_meta( $post_id, '_thumbnail_id' );
		} else {
			$attachment_id = Sensei_Data_Port_Utilities::get_attachment_from_source( $thumbnail );

			if ( is_wp_error( $attachment_id ) ) {
				return $attachment_id;
			}

			update_post_meta( $post_id, '_thumbnail_id', $attachment_id );
		}

		return true;
	}

	/**
	 * Stores an import id to the job.
	 */
	protected function store_import_id() {
		$import_id = $this->get_value( $this->schema->get_column_id() );

		if ( ! empty( $import_id ) ) {
			$this->import_job->set_import_id( $this->schema->get_post_type(), $import_id, $this->get_post_id() );
		}
	}

	/**
	 * Returns the post id for an import id or check if the post exists.
	 *
	 * @param string $post_type  The post type.
	 * @param string $import_id  The import id.
	 *
	 * @return int|null The post id if the post exists, null otherwise.
	 */
	protected function translate_import_id( $post_type, $import_id ) {
		if ( empty( $import_id ) ) {
			return null;
		}

		if ( 0 === strpos( $import_id, 'id:' ) ) {
			return $this->import_job->get_import_id( $post_type, substr( $import_id, 3 ) );
		}

		if ( 0 === strpos( $import_id, 'slug:' ) ) {
			$post = get_posts(
				[
					'post_type'      => $post_type,
					'post_name__in'  => [ substr( $import_id, 5 ) ],
					'posts_per_page' => 1,
					'post_status'    => 'any',
					'fields'         => 'ids',
				]
			);

			return empty( $post ) ? null : $post[0];
		}

		if ( null !== get_post( (int) $import_id ) ) {
			return (int) $import_id;
		}

		return null;
	}

	/**
	 * Get the default author.
	 *
	 * @return int
	 */
	public function get_default_author() {
		return $this->default_author;
	}

	/**
	 * Whether this is a new data port entity.
	 *
	 * @return bool
	 */
	public function is_new() {
		return $this->is_new;
	}
}
