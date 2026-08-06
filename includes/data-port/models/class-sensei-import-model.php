<?php
/**
 * File containing the Sensei_Import_Model class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class handles the port for a single post.
 */
abstract class Sensei_Import_Model {

	/**
	 * The line number being imported.
	 *
	 * @var int
	 */
	protected $line_number;
	/**
	 * The schema for the model.
	 *
	 * @var Sensei_Data_Port_Schema
	 */
	protected $schema;

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
	 * The import task.
	 *
	 * @var Sensei_Import_File_Process_Task
	 */
	protected $task;

	/**
	 * Deferred warnings. So it can get the correct post ID.
	 *
	 * @var array
	 */
	private $deferred_warnings = [];
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
	 * Sensei_Import_Model constructor.
	 */
	protected function __construct() {
		// Silence is golden.
	}

	/**
	 * Set up item from an array.
	 *
	 * @param int                             $line_number Line number.
	 * @param array                           $data        Data to restore item from.
	 * @param Sensei_Data_Port_Schema         $schema      The schema for the item.
	 * @param Sensei_Import_File_Process_Task $task        The import task.
	 *
	 * @return static
	 */
	public static function from_source_array( $line_number, $data, Sensei_Data_Port_Schema $schema, Sensei_Import_File_Process_Task $task = null ) {
		$self                 = new static();
		$self->line_number    = $line_number;
		$self->schema         = $schema;
		$self->task           = $task;
		$self->default_author = null === $task ? 0 : $task->get_job()->get_user_id();
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
	 * Lookup precedence:
	 *   1. Slug (`post_name`).
	 *   2. Job-local map (`Sensei_Import_Job::get_import_id()`).
	 *   3. Source-import-id post meta (`_sensei_import_id`) — durable across runs.
	 *
	 * Rows with no slug and no source `id` cannot be matched durably and will
	 * be re-inserted on re-import.
	 *
	 * @since $$next-version$$
	 *
	 * @return int|null
	 */
	protected function get_existing_post_id() {
		$data = $this->get_data();

		// 1. Slug fast path — unchanged behavior.
		$slug = isset( $data[ $this->schema->get_column_slug() ] ) ? (string) $data[ $this->schema->get_column_slug() ] : '';
		if ( '' !== $slug ) {
			$existing_posts = get_posts(
				array(
					'post_type'      => $this->schema->get_post_type(),
					'post_name__in'  => array( $slug ),
					'posts_per_page' => 1,
					'post_status'    => 'any',
					'fields'         => 'ids',
				)
			);

			if ( ! empty( $existing_posts[0] ) ) {
				return (int) $existing_posts[0];
			}
		}

		// 2 & 3. Resolve by source import id — first via the in-memory job map,
		// then via the durable post meta.
		$import_id = isset( $data[ $this->schema->get_column_id() ] ) ? (string) $data[ $this->schema->get_column_id() ] : '';
		if ( '' !== $import_id && $this->task ) {
			$mapped = $this->task->get_job()->get_import_id( $this->schema->get_post_type(), $import_id );
			if ( $mapped ) {
				return (int) $mapped;
			}

			$post_id = $this->get_existing_post_id_by_import_id( $import_id );
			if ( $post_id ) {
				return $post_id;
			}
		}

		return null;
	}

	/**
	 * Look up an existing post id by the durable source import id meta.
	 *
	 * Scoped to the schema's post type so `_sensei_import_id = 7` on a course
	 * does not match a lesson with the same id. Trashed and auto-draft posts
	 * are excluded to match the slug path, where `post_status => 'any'` never
	 * returns those statuses.
	 *
	 * @since $$next-version$$
	 *
	 * @param string $import_id The source import id from the CSV row.
	 *
	 * @return int|null
	 */
	private function get_existing_post_id_by_import_id( $import_id ) {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Single indexed postmeta lookup by meta key; no cache layer exists for this path.
		$post_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT pm.post_id
				   FROM {$wpdb->postmeta} pm
				   INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				  WHERE pm.meta_key = %s
				    AND pm.meta_value = %s
				    AND p.post_type = %s
				    AND p.post_status NOT IN ( 'trash', 'auto-draft' )
				  ORDER BY p.ID ASC
				  LIMIT 1",
				$this->schema->get_meta_key_import_id(),
				$import_id,
				$this->schema->get_post_type()
			)
		);

		return $post_id > 0 ? $post_id : null;
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
						if ( '' === $value ) {
							$value = null;
						} else {
							if ( ! is_numeric( $value ) || floor( $value ) !== floatval( $value ) ) {
								$this->add_line_warning(
									sprintf(
										// translators: Placeholder is the column name.
										__( '%s must be a whole number.', 'sensei-lms' ),
										ucwords( $key )
									),
									[
										'code' => 'sensei_data_port_int_sanitization',
									]
								);
							}
							$value = intval( $value );
						}
						break;
					case 'float':
						if ( '' === $value ) {
							$value = null;
						} else {
							if ( ! is_numeric( $value ) ) {
								$this->add_line_warning(
									sprintf(
										// translators: Placeholder is the column name.
										__( '%s must be a number.', 'sensei-lms' ),
										ucwords( $key )
									),
									[
										'code' => 'sensei_data_port_float_sanitization',
									]
								);
							}
							$value = floatval( $value );
						}
						break;
					case 'bool':
						$accepted_options = [ '0', '1', 'true', 'false' ];

						if ( '' === $value ) {
							$value = null;
						} elseif ( ! in_array( $value, $accepted_options, true ) ) {
							$this->add_line_warning(
								sprintf(
									// translators: Placeholder %1$s is the column name. %2$s is the accepted values.
									__( '%1$s must be one of the following: %2$s.', 'sensei-lms' ),
									ucwords( $key ),
									implode( ', ', $accepted_options )
								),
								[
									'code' => 'sensei_data_port_bool_sanitization',
								]
							);
							$value = null;
						} else {
							$value = in_array( $value, [ '1', 'true' ], true );
						}
						break;
					case 'slug':
						$raw_value = $value;
						$value     = sanitize_title( $value );

						if ( $raw_value !== $value ) {
							$this->add_line_warning(
								sprintf(
									// translators: Placeholder is the column name.
									__( '%s contains invalid characters.', 'sensei-lms' ),
									ucwords( $key )
								),
								[
									'code' => 'sensei_data_port_slug_sanitization',
								]
							);
						}

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
	 * Get the model key to identify items in log entries.
	 *
	 * @return string
	 */
	abstract public function get_model_key();

	/**
	 * Get the data for the model.
	 *
	 * @return array
	 */
	public function get_data() {
		return $this->data;
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
	 * Set the data for the model.
	 *
	 * @param array $data The data array.
	 */
	public function set_data( $data ) {
		$this->data = $data;
	}

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
		if ( $entry_title ) {
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
	 * Get the post ID that this references.
	 *
	 * @return int
	 */
	public function get_post_id() {
		return $this->post_id;
	}


	/**
	 * Adds a thumbnail to a post. The source of the thumbnail can be either a filename from the media library or an
	 * external URL.
	 *
	 * An external URL is downloaded in a post-process task rather than inline, so the slow,
	 * network-bound work cannot exceed `max_execution_time` mid-batch and stall the import (see
	 * Sensei_Import_Attachment_Trait). A media library reference resolves with a DB lookup only, so
	 * it is handled inline.
	 *
	 * @param string $column_name  The CSV column name which has the image source.
	 */
	protected function add_thumbnail_to_post( $column_name ) {
		$post_id   = $this->get_post_id();
		$thumbnail = $this->get_value( $column_name );

		if ( null === $thumbnail ) {
			return;
		}

		if ( '' === $thumbnail ) {
			delete_post_meta( $post_id, '_thumbnail_id' );

			return;
		}

		$mime_types = $this->schema->get_schema()[ $column_name ]['mime_types'];

		// A URL means a remote download, so defer it to a post-process task to keep it off the line loop.
		if ( false !== filter_var( $thumbnail, FILTER_VALIDATE_URL ) ) {
			$this->task->add_attachment_task(
				array(
					'post_id'     => $post_id,
					'source'      => $thumbnail,
					'mime_types'  => $mime_types,
					'line_number' => $this->line_number,
					'model_key'   => $this->get_model_key(),
				)
			);

			return;
		}

		// Otherwise it is a media library reference: resolve it inline (a DB lookup, no network).
		$attachment_id = Sensei_Data_Port_Utilities::get_attachment_from_source( $thumbnail, 0, $mime_types );

		if ( $attachment_id instanceof WP_Error ) {
			$this->add_line_warning(
				$attachment_id->get_error_message(),
				array( 'code' => $attachment_id->get_error_code() )
			);

			return;
		}

		update_post_meta( $post_id, '_thumbnail_id', $attachment_id );
	}

	/**
	 * Add warning to a deferred queue for a line in the model.
	 *
	 * @param string $message  Warning message.
	 * @param array  $log_data Log data.
	 */
	public function add_line_warning( $message, $log_data = [] ) {
		$this->deferred_warnings[] = [
			'message'  => $message,
			'log_data' => $log_data,
		];
	}

	/**
	 * Add deferred warnings to the job.
	 */
	public function add_warnings_to_job() {
		foreach ( $this->deferred_warnings as $warning ) {
			$this->task->get_job()->add_line_warning(
				$this->get_model_key(),
				$this->line_number,
				$warning['message'],
				$this->get_error_data( $warning['log_data'] )
			);
		}

		$this->deferred_warnings = [];
	}

	/**
	 * Stores an import id on the post and the job.
	 *
	 * The post meta is the durable identity; the in-memory job map remains the
	 * fast path within a single run. An existing meta value is never
	 * overwritten, so a slug-resolved post whose source id has changed keeps
	 * the id it was first written with. The in-memory map is also not
	 * overwritten when the post already carries a different id, matching the
	 * meta-write contract.
	 *
	 * @since $$next-version$$
	 */
	protected function store_import_id() {
		$import_id = $this->get_value( $this->schema->get_column_id() );

		if ( empty( $import_id ) || ! $this->task ) {
			return;
		}

		$post_id  = $this->get_post_id();
		$meta_key = $this->schema->get_meta_key_import_id();

		$existing = (string) get_post_meta( $post_id, $meta_key, true );

		if ( '' === $existing ) {
			update_post_meta( $post_id, $meta_key, (string) $import_id );
		}

		if ( '' === $existing || (string) $existing === (string) $import_id ) {
			$this->task->get_job()->set_import_id( $this->schema->get_post_type(), $import_id, $post_id );
		}
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

	/**
	 * Set the post ID that this references.
	 *
	 * @param int $id Post ID.
	 */
	protected function set_post_id( $id ) {
		$this->post_id = $id;
	}
}
