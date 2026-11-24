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
	 * : Summary table or complete JSON report. Defaults to table.
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
		WP_CLI::line( 'Read-only preview. Neither store is assumed correct; --from only previews a possible repair.' );
		WP_CLI::line( 'Use --format=json for stored rows and full answer and grade values.' );

		foreach ( $preview['records'] as $record ) {
			WP_CLI::line( sprintf( '%s %d, student %d', ucfirst( $record['type'] ), $record['content_id'], $preview['student_id'] ) );

			if ( isset( $record['status'] ) ) {
				WP_CLI::warning( $record['review'] );
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

		$comments = $comparison['comments'] ?? array();
		$tables   = $comparison['tables'] ?? array();
		$rows     = array();
		foreach ( array_intersect( array( 'status', 'started_at', 'completed_at', 'final_grade', 'answers', 'grades' ), array_keys( $comments + $tables ) ) as $field ) {
			$rows[] = array(
				'field'    => $field,
				'comments' => array_key_exists( $field, $comments ) ? $this->format_value( $comments[ $field ] ) : '(not found)',
				'tables'   => array_key_exists( $field, $tables ) ? $this->format_value( $tables[ $field ] ) : '(not found)',
			);
		}

		if ( $rows ) {
			WP_CLI\Utils\format_items( 'table', $rows, array( 'field', 'comments', 'tables' ) );
		} else {
			WP_CLI::line( 'No records found in either store.' );
		}

		foreach ( $comparison['warnings'] as $warning ) {
			WP_CLI::warning( $warning );
		}
		if ( isset( $comparison['review'] ) ) {
			WP_CLI::warning( $comparison['review'] );
		}
		if ( ! empty( $comparison['missing_children'] ) ) {
			WP_CLI\Utils\format_items( 'table', $comparison['missing_children'], array( 'kind', 'question_id', 'missing_from' ) );
		}
		if ( ! empty( $comparison['withheld_fields'] ) ) {
			WP_CLI::line( 'Withheld changes: ' . implode( ', ', $comparison['withheld_fields'] ) );
		}

		if ( ! $comparison['changes'] ) {
			if ( isset( $comparison['review'] ) ) {
				WP_CLI::line( 'No changes proposed.' );
			}
			return;
		}

		WP_CLI::line( 'Proposed changes, only if the source values are confirmed correct (nothing will be written):' );
		WP_CLI\Utils\format_items( 'table', $comparison['changes'], array( 'field', 'if_confirmed', 'from', 'to' ) );
	}

	/**
	 * Summarize collections and keep null and empty strings distinguishable.
	 *
	 * @param mixed $value A compared value.
	 * @return string The display value.
	 */
	private function format_value( $value ): string {
		if ( is_array( $value ) ) {
			return count( $value ) . ' records (count only)';
		}

		return is_string( $value ) && '' !== $value ? $value : (string) wp_json_encode( $value, JSON_UNESCAPED_SLASHES );
	}
}
