<?php
/**
 * File containing the Sensei_Import_CSV_Reader class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class is responsible for reading a CSV file.
 */
class Sensei_Import_CSV_Reader {
	const MB_ENCODING_DETECTION_ORDER = 'UTF-8, ISO-8859-1, ISO-8859-15, EUC-JP, eucJP-win, JIS, ISO-2022-JP, ASCII';

	/**
	 * The file to be read.
	 *
	 * @var SplFileObject
	 */
	private $file;

	/**
	 * Number of data lines that are already read.
	 *
	 * @var int
	 */
	private $completed_lines;

	/**
	 * Number of the total lines of the file (the first line is not included).
	 *
	 * @var int
	 */
	private $total_lines;

	/**
	 * Whether reading is completed.
	 *
	 * @var bool
	 */
	private $is_completed;

	/**
	 * Number of lines to be read by each read_lines call.
	 *
	 * @var int
	 */
	private $lines_per_batch;

	/**
	 * Sensei_Import_CSV_Reader constructor.
	 *
	 * @param string $csv_file         The CSV file name.
	 * @param int    $completed_lines  Number of data lines to skip.
	 * @param int    $lines_per_batch  Number of lines to be read on each read_lines call.
	 */
	public function __construct( $csv_file, $completed_lines = 0, $lines_per_batch = 30 ) {
		$this->file = new SplFileObject( $csv_file );
		$this->file->setFlags( SplFileObject::READ_CSV );
		$this->detect_delimiter();

		$this->file->seek( PHP_INT_MAX );
		$this->total_lines     = $this->file->key();
		$this->completed_lines = $completed_lines;
		$this->is_completed    = $this->completed_lines >= $this->total_lines;
		$this->lines_per_batch = $lines_per_batch;
	}

	/**
	 * Set the delimiter to read the CSV file.
	 *
	 * The delimiter detection works testing the delimiter which find more columns.
	 */
	private function detect_delimiter() {
		/**
		 * Filters the default CSV delimiter.
		 *
		 * @since 3.2.0
		 * @since 3.3.0 Updated the default to `false`, so it'll get through the delimiter detection.
		 * @hook sensei_import_csv_delimiter
		 *
		 * @param {string} $delimiter The CSV file delimiter.
		 *
		 * @return {false|string} CSV file delimiter or false to skip.
		 */
		$forced_delimiter = apply_filters( 'sensei_import_csv_delimiter', false );

		if ( $forced_delimiter ) {
			$this->file->setCsvControl( $forced_delimiter );
			return;
		}

		/**
		 * Filters the CSV delimiter options.
		 *
		 * @since 3.3.0
		 * @hook sensei_import_csv_delimiter_options
		 *
		 * @param {string[]} $delimiters The CSV file delimiter options.
		 *
		 * @return {array} CSV delimiter options.
		 */
		$delimiters         = apply_filters( 'sensei_import_csv_delimiter_options', [ ',', ';', "\t", '|' ] );
		$max_columns        = 0;
		$selected_delimiter = $delimiters[0];

		foreach ( $delimiters as $delimiter ) {
			$this->file->setCsvControl( $delimiter );

			$columns = $this->get_columns_number();

			if ( false !== $columns && $columns > $max_columns ) {
				$max_columns        = $columns;
				$selected_delimiter = $delimiter;
			}
		}

		$this->file->setCsvControl( $selected_delimiter );
	}

	/**
	 * Get the number of columns matching the header with the content.
	 *
	 * @return int|false Number of columns or
	 *                   `false` if the number of columns in the header and content doesn't match.
	 */
	private function get_columns_number() {
		$this->file->seek( 0 );
		$first_line_columns = count( $this->file->current() );

		// Skip the header.
		$this->file->next();

		while ( ! $this->file->eof() ) {
			$second_line         = $this->file->current();
			$second_line_columns = count( $second_line );

			// SplFileObject->current() returns [ 0 => null ] on empty lines.
			if ( 1 === $second_line_columns && empty( $second_line[0] ) ) {
				$this->file->next();
				continue;
			}

			if ( $first_line_columns === $second_line_columns ) {
				return $first_line_columns;
			}

			return false;
		}

		return false;
	}

	/**
	 * Read a batch of lines from the CSV file. It is expected that the file has been validated before this method is
	 * called.
	 *
	 * @return array {
	 *    An array of read lines.
	 *
	 *    @type array {
	 *      An array of the values of a line.
	 *
	 *      @type $$column_name Column value.
	 *    }
	 *    @type WP_Error An error for the specific line.
	 * }
	 */
	public function read_lines() {

		if ( $this->is_completed() ) {
			return [];
		}

		$columns = $this->get_column_names();

		$this->file->seek( $this->completed_lines + 1 );
		$lines_processed = 0;
		$lines           = [];

		$convert_to_utf8 = in_array( get_option( 'blog_charset' ), [ 'utf8', 'utf-8', 'UTF8', 'UTF-8' ], true );

		while ( $lines_processed < $this->lines_per_batch ) {
			$lines_processed++;

			$indexed_line = $this->file->current();

			// SplFileObject->current() returns [ 0 => null ] on empty lines.
			if ( 1 < count( $indexed_line ) || ( 1 === count( $indexed_line ) && ! empty( $indexed_line[0] ) ) ) {

				if ( count( $indexed_line ) !== count( $columns ) ) {
					$lines[] = new WP_Error(
						'sensei_data_port_job_wrong_number_of_columns',
						__( 'Line has incorrect number of columns.', 'sensei-lms' )
					);
				} else {
					if ( $convert_to_utf8 ) {
						$indexed_line = array_map( [ $this, 'convert_to_utf8' ], $indexed_line );
					}

					$lines[] = array_combine( $columns, $indexed_line );
				}
			} else {
				$lines[] = [];
			}

			if ( $this->file->eof() ) {
				break;
			}

			$this->file->next();
		}

		if ( $this->file->eof() ) {
			$this->is_completed = true;
		}

		$this->completed_lines += $lines_processed;

		return $lines;
	}

	/**
	 * Normalize all strings to UTF-8.
	 *
	 * @param string $value Value to be converted to UTF-8.
	 *
	 * @return string|null
	 */
	private function convert_to_utf8( $value ) {
		if ( null === $value || '' === $value ) {
			return $value;
		}

		$use_mb = function_exists( 'mb_convert_encoding' );

		// Convert to UTF-8.
		if ( $use_mb ) {
			$encoding = mb_detect_encoding( $value, self::MB_ENCODING_DETECTION_ORDER, true );

			if ( $encoding ) {
				$value = mb_convert_encoding( $value, 'UTF-8', $encoding );
			} else {
				$value = mb_convert_encoding( $value, 'UTF-8', 'UTF-8' );
			}
		} else {
			$value = wp_check_invalid_utf8( $value, true );
		}

		return $value;
	}

	/**
	 * Get the column names of the file.
	 *
	 * @return string[]
	 */
	private function get_column_names() {
		$this->file->seek( 0 );

		$column_names = $this->file->current();

		if ( empty( $column_names ) ) {
			return [];
		}

		// Remove BOM if it's present.
		$column_names[0] = str_replace( "\xEF\xBB\xBF", '', $column_names[0] );

		// Make the column names of the CSV file case insensitive.
		return array_map(
			function ( $name ) {
				return strtolower( trim( $name ) );
			},
			$column_names
		);
	}

	/**
	 * Whether the reading of the file is completed.
	 *
	 * @return bool
	 */
	public function is_completed() {
		return $this->is_completed;
	}

	/**
	 * The number of lines that have already been read.
	 *
	 * @return int
	 */
	public function get_completed_lines() {
		return $this->completed_lines;
	}

	/**
	 * The number of total lines in the file (the first line is not included).
	 *
	 * @return int
	 */
	public function get_total_lines() {
		return $this->total_lines;
	}

	/**
	 * Validate a CSV file.
	 *
	 * @param string $file_path        The file path.
	 * @param array  $required_columns The columns that the CSV file is required to have.
	 * @param array  $optional_columns The columns that are optional.
	 *
	 * @return bool|WP_Error
	 */
	public static function validate_csv_file( $file_path, $required_columns, $optional_columns ) {
		if ( ! is_readable( $file_path ) ) {
			return new WP_Error(
				'sensei_data_port_job_unreadable_file',
				__( 'Uploaded file could not be opened.', 'sensei-lms' )
			);
		}

		try {
			$reader = new Sensei_Import_CSV_Reader( $file_path );
		} catch ( Exception $e ) {
			return new WP_Error(
				'sensei_data_port_job_unreadable_file',
				$e->getMessage()
			);
		}

		$columns = $reader->get_column_names();
		if ( empty( $columns ) ) {
			return new WP_Error(
				'sensei_data_port_job_invalid_file',
				__( 'Uploaded file was not a valid CSV.', 'sensei-lms' )
			);
		}

		$has_required_columns     = array_intersect( $required_columns, $columns );
		$missing_required_columns = array_diff( $required_columns, $has_required_columns );

		if ( ! empty( $missing_required_columns ) ) {
			return new WP_Error(
				'sensei_data_port_job_missing_columns',
				sprintf(
					// translators: Placeholder is list of columns that are missing.
					_n(
						'Source file is missing the required column: %s',
						'Source file is missing the required columns: %s',
						count( $missing_required_columns ),
						'sensei-lms'
					),
					implode( ', ', $missing_required_columns )
				)
			);
		}

		$unknown_columns = array_diff( $columns, $required_columns, $optional_columns );

		if ( ! empty( $unknown_columns ) ) {
			return new WP_Error(
				'sensei_data_port_job_unknown_columns',
				sprintf(
					// translators: Placeholder is list of columns that are unknown.
					_n(
						'The following column is unknown: %s',
						'The following columns are unknown: %s',
						count( $unknown_columns ),
						'sensei-lms'
					),
					implode( ', ', $unknown_columns )
				)
			);
		}

		while ( true ) {
			$lines = $reader->read_lines();

			$non_empty_lines = array_filter(
				$lines,
				function( $line ) {
					return ! empty( $line );
				}
			);

			if ( ! empty( $non_empty_lines ) ) {
				break;
			}

			if ( $reader->is_completed() ) {
				return new WP_Error(
					'sensei_data_port_job_empty_file',
					__( 'Uploaded file is empty.', 'sensei-lms' )
				);
			}
		}

		return true;
	}
}
