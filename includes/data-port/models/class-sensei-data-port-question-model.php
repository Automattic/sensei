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
class Sensei_Data_Port_Question_Model extends Sensei_Data_Port_Model {
	const POST_TYPE = 'question';

	const TAXONOMY_QUESTION_TYPE     = 'question-type';
	const TAXONOMY_QUESTION_CATEGORY = 'question-category';

	const COLUMN_QUESTION        = 'question';
	const COLUMN_ANSWER          = 'answer';
	const COLUMN_ID              = 'id';
	const COLUMN_SLUG            = 'slug';
	const COLUMN_DESCRIPTION     = 'description';
	const COLUMN_STATUS          = 'status';
	const COLUMN_TYPE            = 'type';
	const COLUMN_GRADE           = 'grade';
	const COLUMN_RANDOMISE       = 'randomise';
	const COLUMN_MEDIA           = 'media';
	const COLUMN_CATEGORIES      = 'categories';
	const COLUMN_FEEDBACK        = 'feedback';
	const COLUMN_TEXT_BEFORE_GAP = 'text before gap';
	const COLUMN_GAP             = 'gap';
	const COLUMN_TEXT_AFTER_GAP  = 'text after gap';
	const COLUMN_UPLOAD_NOTES    = 'upload notes';
	const COLUMN_TEACHER_NOTES   = 'teacher notes';

	/**
	 * Cached question type.
	 *
	 * @var string
	 */
	private $question_type;

	/**
	 * Check to see if the post already exists in the database.
	 *
	 * @return int
	 */
	protected function get_existing_post_id() {
		$post_id = null;
		$data    = $this->get_data();

		if ( ! empty( $data[ self::COLUMN_SLUG ] ) ) {
			$existing_posts = get_posts(
				[
					'post_type'      => self::POST_TYPE,
					'name'           => $data[ self::COLUMN_SLUG ],
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
	 * Create a new question or update an existing question.
	 *
	 * @return true|WP_Error
	 */
	public function sync_post() {
		$post_object_result = $this->sync_post_object();
		if ( is_wp_error( $post_object_result ) ) {
			return $post_object_result;
		}

		$taxonomy_result = $this->sync_taxonomies();
		if ( is_wp_error( $taxonomy_result ) ) {
			return $taxonomy_result;
		}

		$meta_result = $this->sync_meta();
		if ( is_wp_error( $meta_result ) ) {
			return $meta_result;
		}

		return true;
	}

	/**
	 * Synchronize the post object.
	 *
	 * @return bool|WP_Error
	 */
	private function sync_post_object() {
		$postarr = $this->get_post_array();

		$post_id = wp_insert_post( $postarr );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$this->set_post_id( $post_id );

		return true;
	}

	/**
	 * Generates the post array.
	 *
	 * @return array
	 */
	private function get_post_array() {
		$postarr = [
			'post_type' => self::POST_TYPE,
		];

		if ( $this->get_post_id() ) {
			$postarr['ID'] = $this->get_post_id();
		} elseif ( get_current_user_id() ) {
			$postarr['post_author'] = get_current_user_id();
		}

		$data = $this->get_data();

		$postarr['post_title'] = $data[ self::COLUMN_QUESTION ];

		$post_name = $this->get_value( self::COLUMN_SLUG );
		if ( null !== $post_name ) {
			$postarr['post_name'] = $post_name;
		}

		$post_content = $this->get_value( self::COLUMN_DESCRIPTION );
		if ( null !== $post_content ) {
			$postarr['post_content'] = $post_content;
		}

		$post_status = $this->get_value( self::COLUMN_STATUS );
		if ( null !== $post_status ) {
			$postarr['post_status'] = $post_status;
		}

		return $postarr;
	}

	/**
	 * Synchronize the post meta.
	 *
	 * @return true|WP_Error
	 */
	private function sync_meta() {
		$current_meta = get_post_meta( $this->get_post_id() );
		$meta_fields  = $this->get_meta_fields();

		foreach ( $meta_fields as $field => $new_value ) {
			if ( is_callable( $new_value ) ) {
				$new_value = call_user_func( $new_value, $field );
			}

			if ( null === $new_value ) {
				continue;
			}

			if ( is_wp_error( $new_value ) ) {
				return new WP_Error(
					'sensei_import_question_meta_field_invalid',
					sprintf(
						// translators: First placeholder is name of field, second placeholder is error returned.
						__( 'Meta field "%1$s" is invalid: %2$s', 'sensei-lms' ),
						$field,
						$new_value->get_error_message()
					)
				);
			}

			$current_value = null;
			if ( ! empty( $current_meta[ $field ] ) ) {
				$current_value = $current_meta[ $field ][0];
			}

			if ( $new_value !== $current_value ) {
				if ( false === update_post_meta( $this->get_post_id(), $field, $new_value ) ) {
					return new WP_Error(
						'sensei_import_question_meta_field_failed',
						sprintf(
							// translators: First placeholder is name of field.
							__( 'Meta field "$1%s" is could not be saved.', 'sensei-lms' ),
							$field
						)
					);
				}
			}
		}

		return true;
	}

	/**
	 * Get the meta fields and their values.
	 *
	 * @return array
	 */
	private function get_meta_fields() {
		$fields = [];

		$fields['_question_grade']  = $this->get_value( self::COLUMN_GRADE );
		$fields['_random_order']    = $this->get_value( self::COLUMN_RANDOMISE );
		$fields['_answer_feedback'] = $this->get_value( self::COLUMN_FEEDBACK );
		$fields['_question_media']  = $this->get_question_media_value();

		$answer_field_values = $this->get_answer_field_values();
		$fields              = array_merge( $fields, $answer_field_values );

		return $fields;
	}

	/**
	 * Get the question media value.
	 *
	 * @return null|string
	 */
	private function get_question_media_value() {
		$value = $this->get_value( self::COLUMN_MEDIA );
		if ( null === $value ) {
			return null;
		}

		if ( empty( $value ) ) {
			return '';
		}

		return Sensei_Data_Port_Utilities::get_attachment_from_source( $value, $this->get_post_id() );
	}

	/**
	 * Process answer field and return fields..
	 *
	 * @return array
	 */
	private function get_answer_field_values() {
		// Process answers.
		$values = [
			'_question_right_answer'  => '',
			'_question_wrong_answers' => '',
			'_wrong_answer_count'     => '',
			'_right_answer_count'     => '',
			'_answer_order'           => '',
		];

		$question_type = $this->get_value( self::COLUMN_TYPE );

		switch ( $question_type ) {
			case 'multiple-choice':
				$values = $this->parse_multiple_choice_answers();
				break;
			case 'boolean':
				$answers_raw = $this->get_value( self::COLUMN_ANSWER );
				$values      = [
					'_question_right_answer' => 1 === intval( $answers_raw ) ? 1 : 0,
				];

				break;
			case 'gap-fill':
				$answer   = [];
				$answer[] = $this->get_value( self::COLUMN_TEXT_BEFORE_GAP );
				$answer[] = $this->get_value( self::COLUMN_GAP );
				$answer[] = $this->get_value( self::COLUMN_TEXT_AFTER_GAP );

				$values = [
					'_question_right_answer' => implode( '||', $answer ),
				];

				break;
			case 'single-line':
			case 'multi-line':
				$values = [
					'_question_right_answer' => $this->get_value( self::COLUMN_ANSWER ),
				];

				break;
		}

		return $values;
	}

	/**
	 * Get the question type.
	 *
	 * @return string|null
	 */
	public function get_question_type() {
		$data_column = $this->get_value( self::COLUMN_TYPE );
		if ( $data_column ) {
			return $data_column;
		}

		if ( ! $this->get_post_id() ) {
			return null;
		}

		if ( ! $this->question_type ) {
			$this->question_type = Sensei()->question->get_question_type( $this->get_post_id() );
		}

		return $this->question_type;
	}

	/**
	 * Parse multiple choice answer fields.
	 *
	 * @return array
	 */
	private function parse_multiple_choice_answers() {
		$values = [
			'_question_right_answer'  => [],
			'_question_wrong_answers' => [],
			'_right_answer_count'     => null,
			'_wrong_answer_count'     => null,
			'_answer_order'           => [],
		];

		$split_answers = Sensei_Data_Port_Utilities::split_list_safely( $this->get_value( self::COLUMN_ANSWER ), false );
		foreach ( $split_answers as $answer_raw ) {
			$type = strtolower( substr( $answer_raw, 0, 6 ) );

			if ( ! in_array( $type, [ 'right:', 'wrong:' ], true ) ) {
				continue;
			}

			$type   = substr( $type, 0, 5 );
			$answer = trim( substr( $answer_raw, 6 ) );
			$answer = trim( $answer, Sensei_Data_Port_Utilities::CHARS_WHITESPACE_AND_QUOTES );

			if ( 'right' === $type ) {
				$values['_question_right_answer'][] = $answer;
			} else {
				$values['_question_wrong_answers'][] = $answer;
			}

			$values['_answer_order'][] = md5( $answer );
		}

		$values['_answer_order']       = implode( ',', $values['_answer_order'] );
		$values['_right_answer_count'] = count( $values['_question_right_answer'] );
		$values['_wrong_answer_count'] = count( $values['_question_wrong_answers'] );

		return $values;
	}

	/**
	 * Synchronize the post taxonomies.
	 *
	 * @return true|WP_Error
	 */
	private function sync_taxonomies() {
		$taxonomies = $this->get_taxonomy_terms();

		foreach ( $taxonomies as $taxonomy_type => $terms ) {
			if ( ! wp_set_post_terms( $this->get_post_id(), $terms, $taxonomy_type ) ) {
				return new WP_Error(
					'sensei_import_question_taxonomy_failed',
					sprintf(
						// translators: Placeholder is taxonomy type.
						__( 'Unable to set "%s" taxonomies', 'sensei-lms' ),
						$taxonomy_type
					)
				);
			}
		}

		return true;
	}

	/**
	 * Get the terms for the question, keyed by taxonomy type.
	 *
	 * @return array
	 */
	private function get_taxonomy_terms() {
		$taxonomy_terms = [];

		$question_type = $this->get_value( self::COLUMN_TYPE );
		if ( $question_type ) {
			$taxonomy_terms[ self::TAXONOMY_QUESTION_TYPE ] = [ $question_type ];
		}

		$taxonomy_terms[ self::TAXONOMY_QUESTION_CATEGORY ] = [];

		$category_list = Sensei_Data_Port_Utilities::split_list_safely( $this->get_value( self::COLUMN_CATEGORIES ), true );
		if ( ! empty( $category_list ) ) {
			foreach ( $category_list as $category ) {
				$category_term = Sensei_Data_Port_Utilities::get_term( $category, self::TAXONOMY_QUESTION_CATEGORY );
				if ( $category_term ) {
					$taxonomy_terms[ self::TAXONOMY_QUESTION_CATEGORY ][] = $category_term->term_id;
				}
			}
		}

		return $taxonomy_terms;
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
		return [
			self::COLUMN_QUESTION        => [
				'type'       => 'string',
				'required'   => true,
				'allow_html' => true,
			],
			self::COLUMN_ANSWER          => [
				'type'      => 'string',
				'validator' => static::validate_for_question_type( 'multiple-choice', true ),
				'default'   => function( $field, $data ) {
					if (
						isset( $data[ self::COLUMN_TYPE ] )
						&& 'boolean' === $data[ self::COLUMN_TYPE ]
					) {
						return 1;
					}

					return null;
				},
			],
			self::COLUMN_ID              => [
				'type' => 'string',
			],
			self::COLUMN_SLUG            => [
				'type' => 'slug',
			],
			self::COLUMN_DESCRIPTION     => [
				'type' => 'string',
			],
			self::COLUMN_STATUS          => [
				'type'    => 'string',
				'default' => 'draft',
				'pattern' => '/publish|pending|draft/',
			],
			self::COLUMN_TYPE            => [
				'type'    => 'string',
				'default' => 'multiple-choice',
				'pattern' => '/multiple\-choice|boolean|gap\-fill|single\-line|multiple\-line|file\-upload/',
			],
			self::COLUMN_GRADE           => [
				'type'    => 'int',
				'default' => 1,
			],
			self::COLUMN_RANDOMISE       => [
				'type'    => 'bool',
				'default' => true,
			],
			self::COLUMN_MEDIA           => [
				'type' => 'url',
			],
			self::COLUMN_CATEGORIES      => [
				'type' => 'string',
			],
			self::COLUMN_FEEDBACK        => [
				'type' => 'string',
			],
			self::COLUMN_TEXT_BEFORE_GAP => [
				'type'      => 'string',
				'validator' => static::validate_for_question_type( 'gap-fill', false ),
			],
			self::COLUMN_GAP             => [
				'type'      => 'string',
				'validator' => static::validate_for_question_type( 'gap-fill', false ),
			],
			self::COLUMN_TEXT_AFTER_GAP  => [
				'type'      => 'string',
				'validator' => static::validate_for_question_type( 'gap-fill', false ),
			],
			self::COLUMN_UPLOAD_NOTES    => [
				'type' => 'string',
			],
			self::COLUMN_TEACHER_NOTES   => [
				'type' => 'string',
			],
		];
	}

	/**
	 * Get a validator for a field that is only required when the question type is a specific value.
	 *
	 * @param string $type            Question type that makes this field required.
	 * @param bool   $default_no_type Default validation result when no type value is set.
	 *
	 * @return closure
	 */
	private static function validate_for_question_type( $type, $default_no_type = true ) {
		return function ( $field, Sensei_Data_Port_Question_Model $model ) use ( $type, $default_no_type ) {
			$data          = $model->get_data();
			$question_type = $model->get_question_type();

			if ( ! $question_type ) {
				return $default_no_type;
			}

			if ( $type === $question_type ) {
				return isset( $data[ $field ] );
			}

			return true;
		};
	}

	/**
	 * Get the data to return with any errors.
	 *
	 * @param array $data Base error data to pass along.
	 *
	 * @return array
	 */
	public function get_error_data( $data = [] ) {
		$entry_id = $this->get_value( self::COLUMN_ID );
		if ( $entry_id ) {
			$data['entry_id'] = $entry_id;
		}

		$entry_title = $this->get_value( self::COLUMN_QUESTION );
		if ( $entry_id ) {
			$data['entry_title'] = $entry_title;
		}

		$post_id = $this->get_post_id();
		if ( $post_id ) {
			$data['post_id'] = $post_id;
		}

		return $data;
	}
}
