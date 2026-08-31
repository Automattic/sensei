<?php
/**
 * File containing the \Sensei\WPML\Course_Translation class.
 *
 * @package sensei
 */

namespace Sensei\WPML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Course_Translation
 *
 * Compatibility code with WPML.
 *
 * @since 4.22.0
 *
 * @internal
 */
class Course_Translation {

	use Lesson_Translation_Helper;
	use Quiz_Translation_Helper;
	use WPML_API;

	/**
	 * Init hooks.
	 */
	public function init() {
		// Create translations for lessons and update lesson properties on course translation created.
		add_action( 'wpml_pro_translation_completed', array( $this, 'update_lesson_properties_on_course_translation_created' ) );

		// After the lesson translations above exist, point the delivered outline at them.
		add_action( 'wpml_pro_translation_completed', array( $this, 'translate_outline_lesson_ids_on_course_translation_created' ), 20 );
		// The Duplicate feature copies the outline verbatim too.
		add_action( 'icl_make_duplicate', array( $this, 'translate_outline_lesson_ids_on_course_duplicated' ), 10, 4 );
	}

	/**
	 * Save lessons fields on course translation created.
	 *
	 * @since 4.22.0
	 *
	 * @internal
	 *
	 * @param int $new_course_id New course ID.
	 */
	public function update_lesson_properties_on_course_translation_created( $new_course_id ) {
		if ( 'course' !== get_post_type( $new_course_id ) ) {
			return;
		}

		$details = $this->get_element_language_details( $new_course_id, 'course' );
		if ( empty( $details ) ) {
			return;
		}

		if ( empty( $details['source_language_code'] ) ) {
			return;
		}

		$master_id = $this->get_object_id( $new_course_id, 'course', false, $details['source_language_code'] );
		if ( empty( $master_id ) || $master_id === $new_course_id ) {
			return;
		}

		$lesson_ids          = Sensei()->course->course_lessons( $master_id, 'any', 'ids' );
		$duplicate_languages = array();
		foreach ( $lesson_ids as $lesson_id ) {
			if ( ! is_int( $lesson_id ) ) {
				$lesson_id = (int) $lesson_id;
			}

			// Create translatons if they don't exist.
			$is_translated = $this->has_translation_in_language( $lesson_id, 'post_lesson', $details['language_code'] );
			if ( ! $is_translated ) {
				$this->copy_post_to_language( $lesson_id, $details['language_code'], true );
			}

			$translations = $this->get_post_duplicates( $lesson_id );
			foreach ( $translations as $language_code => $translated_lesson_id ) {
				if ( empty( $this->get_element_language_details( (int) $translated_lesson_id, 'lesson' ) ) ) {
					continue;
				}

				$this->update_lesson_course( (int) $translated_lesson_id, $new_course_id );
				$this->set_module_taxonomies( (int) $translated_lesson_id, $lesson_id, array( 'language_code' => $language_code ) );
				$duplicate_languages[ $language_code ] = true;
			}

			$this->update_quiz_translations( $lesson_id, $details['language_code'] );

			// Sync lesson course field across translations.
			$this->sync_custom_field( $lesson_id, '_lesson_course' );
		}

		// One order sync per language instead of one per lesson.
		foreach ( array_keys( $duplicate_languages ) as $language_code ) {
			$translated_course_id = $this->get_object_id( $master_id, 'course', false, $language_code );
			if ( $translated_course_id && $translated_course_id !== $master_id ) {
				$this->sync_course_lesson_order( $master_id, $translated_course_id, $language_code );
			}
		}
	}

	/**
	 * Rewrite the delivered course outline to the lesson and module IDs of the course language.
	 *
	 * WPML delivers translated content with the source language's lesson and module
	 * IDs (its ID conversion runs at render time only), so the stored outline points
	 * at another language's content and a later editor save adopts it. Remapping the
	 * stored outline right after delivery keeps every consumer of the content safe.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 *
	 * @param int $new_course_id New course ID.
	 */
	public function translate_outline_lesson_ids_on_course_translation_created( $new_course_id ) {
		if ( 'course' !== get_post_type( $new_course_id ) ) {
			return;
		}

		$details = $this->get_element_language_details( $new_course_id, 'course' );
		if ( empty( $details ) || empty( $details['language_code'] ) || empty( $details['source_language_code'] ) ) {
			return;
		}

		$this->translate_outline_to_language( $new_course_id, $details['language_code'] );
	}

	/**
	 * Rewrite a duplicated course outline to the lesson and module IDs of the duplicate's language.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 *
	 * @param int    $master_post_id Master course ID.
	 * @param string $lang           Language of the duplicate.
	 * @param array  $post_array     Post data of the duplicate.
	 * @param int    $new_course_id  Duplicated course ID.
	 */
	public function translate_outline_lesson_ids_on_course_duplicated( $master_post_id, $lang, $post_array, $new_course_id ) {
		if ( 'course' !== get_post_type( $new_course_id ) || empty( $lang ) ) {
			return;
		}

		$this->translate_outline_to_language( (int) $new_course_id, $lang );
	}

	/**
	 * Rewrite a course's stored outline to the lesson and module IDs of the given language.
	 *
	 * @param int    $course_id     Course ID.
	 * @param string $language_code Language to map the outline to.
	 */
	private function translate_outline_to_language( $course_id, $language_code ) {
		$course = get_post( $course_id );
		if ( ! $course || ! has_block( 'sensei-lms/course-outline', $course ) ) {
			return;
		}

		$blocks  = parse_blocks( $course->post_content );
		$blocks  = $this->translate_outline_blocks( $blocks, $language_code );
		$content = serialize_blocks( $blocks );

		if ( $content === $course->post_content ) {
			return;
		}

		// Slashed, or wp_update_post strips the backslashes of the escaped block attributes.
		wp_update_post(
			wp_slash(
				array(
					'ID'           => $course_id,
					'post_content' => $content,
				)
			)
		);
	}

	/**
	 * Map outline lesson and module blocks to the given language, dropping untranslated lessons.
	 *
	 * @param array  $blocks        Parsed blocks.
	 * @param string $language_code Language to map the outline IDs to.
	 *
	 * @return array
	 */
	private function translate_outline_blocks( array $blocks, $language_code ) {
		foreach ( $blocks as $i => $block ) {
			if ( 'sensei-lms/course-outline' === $block['blockName'] ) {
				$blocks[ $i ] = $this->map_inner_blocks( $block, array( $this, 'outline_item_to_language' ), $language_code );
				continue;
			}

			// The outline can sit inside wrapper blocks such as groups or columns.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$blocks[ $i ]['innerBlocks'] = $this->translate_outline_blocks( $block['innerBlocks'], $language_code );
			}
		}

		return $blocks;
	}

	/**
	 * Map one outline item to the course language.
	 *
	 * @param array  $block         The outline item block.
	 * @param string $language_code Language to map the item to.
	 *
	 * @return array|false The mapped block, or false to drop it.
	 */
	private function outline_item_to_language( array $block, $language_code ) {
		if ( 'sensei-lms/course-outline-module' === $block['blockName'] ) {
			$module_id = (int) ( $block['attrs']['id'] ?? 0 );

			if ( $module_id ) {
				$translated_module_id = $this->get_object_id( $module_id, 'module', false, $language_code );

				if ( $translated_module_id ) {
					$block['attrs']['id'] = (int) $translated_module_id;

					// The slug travels with the block too, and a stale source slug would
					// point a later save back at the source language's term.
					$translated_term = get_term( (int) $translated_module_id, 'module' );
					if ( $translated_term && ! is_wp_error( $translated_term ) ) {
						$block['attrs']['slug'] = $translated_term->slug;
					}
				} else {
					// Without id and slug the structure save creates the course's own
					// module instead of renaming or adopting the source language's term.
					unset( $block['attrs']['id'], $block['attrs']['slug'] );
				}
			}

			return $this->map_inner_blocks( $block, array( $this, 'outline_item_to_language' ), $language_code );
		}

		if ( 'sensei-lms/course-outline-lesson' !== $block['blockName'] || empty( $block['attrs']['id'] ) ) {
			return $block;
		}

		$translated_id = $this->get_object_id( (int) $block['attrs']['id'], 'lesson', false, $language_code );
		if ( ! $translated_id ) {
			return false;
		}

		$block['attrs']['id'] = (int) $translated_id;

		// The lesson translation is created with the source language's title, and the
		// translated title only exists in this block attribute, so apply it to the
		// lesson here.
		$title             = isset( $block['attrs']['title'] ) ? (string) $block['attrs']['title'] : '';
		$translated_lesson = get_post( $translated_id );
		if ( '' !== $title && $translated_lesson && $translated_lesson->post_title !== $title ) {
			wp_update_post(
				wp_slash(
					array(
						'ID'         => (int) $translated_id,
						'post_title' => $title,
					)
				)
			);
		}

		return $block;
	}

	/**
	 * Map the inner blocks of a block, dropping the ones mapped to false.
	 *
	 * Mirror of `Sensei_Import_Block_Migrator::map_inner_blocks()`.
	 *
	 * @param array    $block         The block whose inner blocks to map.
	 * @param callable $map           Map receiving the inner block and the language code.
	 * @param string   $language_code Language passed through to the map.
	 *
	 * @return array The block with its inner blocks mapped.
	 */
	private function map_inner_blocks( array $block, callable $map, $language_code ) {
		if ( empty( $block['innerBlocks'] ) ) {
			return $block;
		}

		// Inner blocks are represented as an entry in the 'innerBlocks' array and a null value in the 'innerContent' array.
		$inner_block_index   = 0;
		$mapped_inner_blocks = array();
		$inner_content       = array();

		foreach ( $block['innerContent'] as $chunk ) {
			// If the content is not an inner block there is nothing to do.
			if ( is_string( $chunk ) ) {
				$inner_content[] = $chunk;
				continue;
			}

			$mapped_block = $map( $block['innerBlocks'][ $inner_block_index ], $language_code );

			// Add the entries in 'innerBlocks' and 'innerContent' only if the block was not dropped.
			if ( false !== $mapped_block ) {
				$mapped_inner_blocks[] = $mapped_block;
				$inner_content[]       = $chunk;
			}

			++$inner_block_index;
		}

		$block['innerBlocks']  = $mapped_inner_blocks;
		$block['innerContent'] = $inner_content;

		return $block;
	}
}
