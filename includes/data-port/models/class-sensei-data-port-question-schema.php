<?php
/**
 * File containing the Sensei_Data_Port_Question_Schema class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defines the expected data to port to/from.
 */
class Sensei_Data_Port_Question_Schema extends Sensei_Data_Port_Schema {
	const POST_TYPE = 'question';

	const TAXONOMY_QUESTION_TYPE     = 'question-type';
	const TAXONOMY_QUESTION_CATEGORY = 'question-category';

	const COLUMN_TITLE           = 'question';
	const COLUMN_ANSWER          = 'answer';
	const COLUMN_DESCRIPTION     = 'description';
	const COLUMN_STATUS          = 'status';
	const COLUMN_TYPE            = 'type';
	const COLUMN_GRADE           = 'grade';
	const COLUMN_RANDOM_ORDER    = 'random answer order';
	const COLUMN_MEDIA           = 'media';
	const COLUMN_CATEGORIES      = 'categories';
	const COLUMN_FEEDBACK        = 'feedback';
	const COLUMN_TEXT_BEFORE_GAP = 'text before gap';
	const COLUMN_GAP             = 'gap';
	const COLUMN_TEXT_AFTER_GAP  = 'text after gap';
	const COLUMN_UPLOAD_NOTES    = 'upload notes';
	const COLUMN_TEACHER_NOTES   = 'teacher notes';

	/**
	 * Get the schema for the data type.
	 *
	 * @return (Closure|array|closure|int|string|true)[][] {
	 *
	 * @type array $$field_name {
	 * @type string   $type       Type of data. Options: string, int, float, bool, slug, ref, email, url-or-file, username, video.
	 * @type string   $pattern    Regular expression that the value should match (Optional).
	 * @type mixed    $default    Default value if not set or invalid. Default is `null` (Optional).
	 * @type bool     $required   True if a non-empty value is required. Default is `false` (Optional).
	 * @type bool     $allow_html True if HTML should be allowed. Default is `false` (Optional).
	 * @type callable $validator  Callable to use when validating data (Optional).
	 *     }
	 * }
	 *
	 * @psalm-return array{id: array{type: 'string'}, question: array{type: 'string', required: true, allow_html: true}, slug: array{type: 'slug'}, description: array{type: 'string', allow_html: true}, status: array{type: 'string', default: 'draft', pattern: '/^(publish|pending|draft|)$/'}, type: array{type: 'string', default: 'multiple-choice', pattern: '/^(multiple\-choice|boolean|gap\-fill|single\-line|multi\-line|file\-upload|)$/'}, grade: array{type: 'int', default: 1}, 'random answer order': array{type: 'bool', default: true}, media: array{type: 'url-or-file', mime_types: array}, categories: array{type: 'string'}, answer: array{type: 'string', validator: closure, default: Closure(mixed, Sensei_Import_Question_Model):(1|null)}, feedback: array{type: 'string', allow_html: true}, 'text before gap': array{type: 'string', validator: closure, allow_html: true}, gap: array{type: 'string', validator: closure, allow_html: true}, 'text after gap': array{type: 'string', validator: closure, allow_html: true}, 'upload notes': array{type: 'string', allow_html: true}, 'teacher notes': array{type: 'string', allow_html: true}}
	 */
	public function get_schema() {
		return [
			self::COLUMN_ID              => [
				'type' => 'string',
			],
			self::COLUMN_TITLE           => [
				'type'       => 'string',
				'required'   => true,
				'allow_html' => true,
			],
			self::COLUMN_SLUG            => [
				'type' => 'slug',
			],
			self::COLUMN_DESCRIPTION     => [
				'type'       => 'string',
				'allow_html' => true,
			],
			self::COLUMN_STATUS          => [
				'type'    => 'string',
				'default' => 'draft',
				'pattern' => '/^(publish|pending|draft|)$/',
			],
			self::COLUMN_TYPE            => [
				'type'    => 'string',
				'default' => 'multiple-choice',
				'pattern' => '/^(multiple\-choice|boolean|gap\-fill|single\-line|multi\-line|file\-upload|)$/',
			],
			self::COLUMN_GRADE           => [
				'type'    => 'int',
				'default' => 1,
			],
			self::COLUMN_RANDOM_ORDER    => [
				'type'    => 'bool',
				'default' => true,
			],
			self::COLUMN_MEDIA           => [
				'type'       => 'url-or-file',
				'mime_types' => $this->get_allowed_mime_types(),
			],
			self::COLUMN_CATEGORIES      => [
				'type' => 'string',
			],
			self::COLUMN_ANSWER          => [
				'type'      => 'string',
				'validator' => $this->validate_for_question_type( 'multiple-choice', true ),
				'default'   => function( $field, Sensei_Import_Question_Model $model ) {
					$data = $model->get_data();

					if (
						isset( $data[ self::COLUMN_TYPE ] )
						&& 'boolean' === $data[ self::COLUMN_TYPE ]
					) {
						return 1;
					}

					return null;
				},
			],
			self::COLUMN_FEEDBACK        => [
				'type'       => 'string',
				'allow_html' => true,
			],
			self::COLUMN_TEXT_BEFORE_GAP => [
				'type'       => 'string',
				'validator'  => $this->validate_for_question_type( 'gap-fill', false ),
				'allow_html' => true,
			],
			self::COLUMN_GAP             => [
				'type'       => 'string',
				'validator'  => $this->validate_for_question_type( 'gap-fill', false ),
				'allow_html' => true,
			],
			self::COLUMN_TEXT_AFTER_GAP  => [
				'type'       => 'string',
				'validator'  => $this->validate_for_question_type( 'gap-fill', false ),
				'allow_html' => true,
			],
			self::COLUMN_UPLOAD_NOTES    => [
				'type'       => 'string',
				'allow_html' => true,
			],
			self::COLUMN_TEACHER_NOTES   => [
				'type'       => 'string',
				'allow_html' => true,
			],
		];
	}

	/**
	 * Get a validator for a field that is only required when the question type is a specific value.
	 *
	 * @param string $type            Question type that makes this field required.
	 * @param bool   $default_no_type Default validation result when no type value is set.
	 *
	 * @psalm-return Closure(mixed, Sensei_Import_Question_Model):bool
	 */
	private function validate_for_question_type( $type, $default_no_type = true ): Closure {
		return function ( $field, Sensei_Import_Question_Model $model ) use ( $type, $default_no_type ) {
			$data          = $model->get_data();
			$question_type = $model->get_question_type();

			if ( ! $question_type ) {
				return $default_no_type;
			}

			if ( $type === $question_type ) {
				return ! empty( $data[ $field ] );
			}

			return true;
		};
	}

	/**
	 * Get question post type.
	 *
	 * @return string
	 *
	 * @psalm-return 'question'
	 */
	public function get_post_type() {
		return self::POST_TYPE;
	}

	/**
	 * Get the column name for the title.
	 *
	 * @return string
	 *
	 * @psalm-return 'question'
	 */
	public function get_column_title() {
		return self::COLUMN_TITLE;
	}
}
