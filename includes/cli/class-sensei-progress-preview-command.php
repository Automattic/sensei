<?php
/**
 * Targeted progress inspection command.
 *
 * @package sensei
 */

/**
 * Preview differences and conditional repairs without changing either store.
 *
 * @since $$next-version$$
 */
class Sensei_Progress_Preview_Command {
	/**
	 * Inspect both copies of one student's progress.
	 *
	 * ## OPTIONS
	 *
	 * <type>
	 * : The content type: course, lesson, or quiz. Courses include only course progress.
	 *
	 * <content-id>
	 * : The stored content ID, including an ID whose post was deleted.
	 *
	 * --student=<id>
	 * : The stored student ID, including an ID whose user was deleted.
	 *
	 * [--from=<store>]
	 * : Hypothetical source: comments or tables. Still requires review of intent.
	 *
	 * [--format=<format>]
	 * : Output format: table or json. Defaults to table.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp sensei progress preview lesson 123 --student=456
	 *     wp sensei progress preview quiz 124 --student=456 --from=tables
	 *     wp sensei progress preview quiz 124 --student=456 --format=json
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$content_id = $args[1] ?? '';
		$student_id = $assoc_args['student'] ?? '';
		$format     = $assoc_args['format'] ?? 'table';

		if ( ! in_array( $format, array( 'table', 'json' ), true ) ) {
			WP_CLI::error( 'The output format must be table or json.' );
			return;
		}

		if ( ! ctype_digit( (string) $content_id ) || ! ctype_digit( (string) $student_id ) ) {
			WP_CLI::error( 'Provide positive numeric content and student IDs.' );
			return;
		}

		try {
			$preview = ( new Sensei_Progress_Recovery_Preview() )->inspect(
				$args[0] ?? '',
				(int) $content_id,
				(int) $student_id,
				$assoc_args['from'] ?? null
			);
		} catch ( \InvalidArgumentException | \RuntimeException $exception ) {
			WP_CLI::error( $exception->getMessage() );
			return;
		}

		if ( 'json' === $format ) {
			WP_CLI::line( wp_json_encode( $preview, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
			return;
		}

		$this->output_table( $preview );
	}

	/**
	 * Show each inspected record and its comparison results.
	 *
	 * @param array $preview The read-only preview.
	 */
	private function output_table( array $preview ): void {
		WP_CLI::line( $preview['requires_review'] );

		foreach ( $preview['records'] as $record ) {
			WP_CLI::line( sprintf( '%s %d, student %d', ucfirst( $record['type'] ), $record['content_id'], $preview['student_id'] ) );

			if ( isset( $record['status'] ) ) {
				WP_CLI::warning( $record['review'] );
				$rows = array();
				foreach ( $record['stored'] as $kind => $stored_rows ) {
					foreach ( $stored_rows as $row ) {
						$rows[] = array(
							'kind' => $kind,
							'id'   => $row->id ?? $row->comment_ID ?? $row->meta_id,
						);
					}
				}

				WP_CLI\Utils\format_items( 'table', $rows, array( 'kind', 'id' ) );
				WP_CLI::line( 'Use --format=json to inspect all stored values.' );
				continue;
			}

			$this->output_comparison( 'Progress', $record['progress'] );
			if ( isset( $record['submission'] ) ) {
				$this->output_comparison( 'Submission', $record['submission'] );
			}
		}
	}

	/**
	 * Show both copies, warnings, and any conditional changes.
	 *
	 * @param string $label      The comparison label.
	 * @param array  $comparison The comparison result.
	 */
	private function output_comparison( string $label, array $comparison ): void {
		WP_CLI::line( $label . ': ' . $comparison['status'] );

		$comments = $this->get_table_values( $comparison['comments'] ?? array() );
		$tables   = $this->get_table_values( $comparison['tables'] ?? array() );
		$rows     = array();
		foreach ( array_keys( $comments + $tables ) as $field ) {
			$rows[] = array(
				'field'    => $field,
				'comments' => array_key_exists( $field, $comments ) ? $this->format_value( $comments[ $field ] ) : '(missing)',
				'tables'   => array_key_exists( $field, $tables ) ? $this->format_value( $tables[ $field ] ) : '(missing)',
			);
		}

		if ( $rows ) {
			WP_CLI\Utils\format_items( 'table', $rows, array( 'field', 'comments', 'tables' ) );
		} else {
			WP_CLI::line( 'No records in either store.' );
		}

		foreach ( $comparison['warnings'] as $warning ) {
			WP_CLI::warning( $warning );
		}
		if ( isset( $comparison['review'] ) ) {
			WP_CLI::warning( $comparison['review'] );
		}
		foreach ( $comparison['alternatives'] ?? array() as $alternative ) {
			WP_CLI::line( 'Alternative: ' . $alternative );
		}
		if ( ! empty( $comparison['missing_children'] ) ) {
			WP_CLI\Utils\format_items( 'table', $comparison['missing_children'], array( 'kind', 'question_id', 'missing_from' ) );
		}
		if ( ! empty( $comparison['withheld_fields'] ) ) {
			WP_CLI::line( 'Withheld changes: ' . implode( ', ', $comparison['withheld_fields'] ) );
		}

		if ( ! $comparison['changes'] ) {
			WP_CLI::line( 'No changes proposed.' );
			return;
		}

		WP_CLI::line( 'Conditional changes, only if the intended outcome is confirmed:' );
		$changes = $comparison['changes'];
		foreach ( $changes as &$change ) {
			$change['before'] = $this->format_value( $change['before'] );
			$change['after']  = $this->format_value( $change['after'] );
		}
		unset( $change );

		WP_CLI\Utils\format_items( 'table', $changes, array( 'if_confirmed', 'from', 'to', 'field', 'before', 'after' ) );
	}

	/**
	 * Put question values on separate rows while retaining their stored identities.
	 *
	 * @param array $copy One normalized copy.
	 * @return array Table values indexed by field.
	 */
	private function get_table_values( array $copy ): array {
		$values = array();
		foreach ( $copy as $field => $value ) {
			if ( in_array( $field, array( 'type', 'post_id', 'quiz_id', 'user_id' ), true ) ) {
				continue;
			}

			if ( in_array( $field, array( 'answers', 'grades' ), true ) ) {
				foreach ( $value as $question ) {
					foreach ( $question as $key => $question_value ) {
						if ( 'question_id' !== $key ) {
							$values[ $field . '.' . $question['question_id'] . '.' . $key ] = $question_value;
						}
					}
				}
			} else {
				$values[ $field ] = $value;
			}
		}

		return $values;
	}

	/**
	 * Keep null, empty strings, and structured values distinguishable in tables.
	 *
	 * @param mixed $value A compared value.
	 * @return string The display value.
	 */
	private function format_value( $value ): string {
		return is_string( $value ) && '' !== $value ? $value : (string) wp_json_encode( $value, JSON_UNESCAPED_SLASHES );
	}
}
