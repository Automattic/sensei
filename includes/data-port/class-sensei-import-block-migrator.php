<?php
/**
 * File containing the Sensei_Import_Course_Content_Migrator class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class is responsible for migrating post content which contains Sensei blocks.
 */
class Sensei_Import_Block_Migrator {

	/**
	 * The course id.
	 *
	 * @var int
	 */
	private $course_id;

	/**
	 * The data port task.
	 *
	 * @var Sensei_Data_Port_Task
	 */
	private $task;

	/**
	 * The course import model.
	 *
	 * @var Sensei_Import_Model
	 */
	private $import_model;

	/**
	 * Sensei_Import_Course_Content_Migrator constructor.
	 *
	 * @param int                   $course_id    The course which gets migrated.
	 * @param Sensei_Data_Port_Task $task         The data port task which this migration is part of.
	 * @param Sensei_Import_Model   $import_model The import model.
	 */
	public function __construct( int $course_id, Sensei_Data_Port_Task $task, Sensei_Import_Model $import_model ) {
		$this->course_id    = $course_id;
		$this->task         = $task;
		$this->import_model = $import_model;
	}

	/**
	 * Migrates the imported post content to use the ids of the newly created lessons and modules.
	 *
	 * @param string $post_content The post content.
	 *
	 * @return string The migrated post content.
	 */
	public function migrate( string $post_content ) : string {
		if ( ! has_block( 'sensei-lms/course-outline', $post_content ) ) {
			return $post_content;
		}

		$blocks = parse_blocks( $post_content );
		$blocks = $this->map_blocks( $blocks );

		return serialize_blocks( $blocks );
	}

	/**
	 * Goes through each block and its inner blocks, searches for the outline block and maps it.
	 *
	 * @param array $blocks The blocks.
	 *
	 * @return array The mapped blocks.
	 */
	private function map_blocks( $blocks ) {
		$i = 0;
		foreach ( $blocks as $block ) {
			if ( 'sensei-lms/course-outline' === $block['blockName'] ) {
				$blocks[ $i ] = $this->map_outline_block_ids( $block );
				break;
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$blocks[ $i ]['innerBlocks'] = $this->map_blocks( $block['innerBlocks'] );
			}

			$i++;
		}

		return $blocks;
	}

	/**
	 * Maps the ids of an outlined block to use the newly created values.
	 *
	 * @param array $outline_block The outline block.
	 *
	 * @return array The mapped block.
	 */
	private function map_outline_block_ids( array $outline_block ) : array {
		return $this->map_inner_blocks(
			$outline_block,
			function( $inner_block ) {
				if ( 'sensei-lms/course-outline-module' === $inner_block['blockName'] ) {
					return $this->map_module_block_id( $inner_block );
				} elseif ( 'sensei-lms/course-outline-lesson' === $inner_block['blockName'] ) {
					return $this->map_lesson_block_id( $inner_block );
				}

				return $inner_block;
			}
		);
	}

	/**
	 * Map the ids of a lesson block.
	 *
	 * @param array $lesson_block The lesson block.
	 *
	 * @return bool|array The lesson block or false if the id couldn't be mapped.
	 */
	private function map_lesson_block_id( array $lesson_block ) {
		if ( empty( $lesson_block['attrs']['id'] ) ) {
			return false;
		}

		// We first check for the lesson id to be a lesson which was imported during the import process. If that fails
		// we check if the lesson already exists in the database. This could happen in case of a course update.
		$lesson_id = $this->task->get_job()->translate_import_id( Sensei_Data_Port_Lesson_Schema::POST_TYPE, 'id:' . $lesson_block['attrs']['id'] );

		if ( null === $lesson_id ) {

			$args = [
				'post_type'      => Sensei_Data_Port_Lesson_Schema::POST_TYPE,
				'posts_per_page' => 1,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'p'              => $lesson_block['attrs']['id'],
			];

			if ( isset( $lesson_block['attrs']['title'] ) ) {
				$args['title'] = $lesson_block['attrs']['title'];
			}

			if ( empty( get_posts( $args ) ) ) {
				$this->import_model->add_line_warning(
					// translators: The %1$d is the lesson id and the %2$s the lesson title.
					sprintf( __( 'Lesson with id %1$d and title %2$s which is referenced in course outline block not found.', 'sensei-lms' ), $lesson_block['attrs']['id'], $lesson_block['attrs']['title'] ),
					[
						'code' => 'sensei_data_port_course_lesson_not_found',
					]
				);

				return false;
			}
		} else {
			$lesson_block['attrs']['id'] = $lesson_id;
		}

		return $lesson_block;
	}

	/**
	 * Map the ids of a module block.
	 *
	 * @param array $module_block The module block.
	 *
	 * @return bool|array The mapped module block or false if the block couldn't be mapped.
	 */
	private function map_module_block_id( array $module_block ) {
		if ( empty( $module_block['attrs']['title'] ) ) {
			$this->import_model->add_line_warning(
				__( 'No title for module found.', 'sensei-lms' ),
				[
					'code' => 'sensei_data_port_module_title_not_found',
				]
			);

			return false;
		}

		$term = Sensei_Data_Port_Utilities::get_module_for_course( $module_block['attrs']['title'], $this->course_id );

		if ( is_wp_error( $term ) ) {
			$this->import_model->add_line_warning( $term->get_error_message(), [ 'code' => $term->get_error_code() ] );

			return false;
		}

		$module_block['attrs']['id'] = $term->term_id;

		return $this->map_inner_blocks(
			$module_block,
			function( $inner_block ) {
				if ( 'sensei-lms/course-outline-lesson' === $inner_block['blockName'] ) {
					return $this->map_lesson_block_id( $inner_block );
				}

				return $inner_block;
			}
		);
	}

	/**
	 * Helper method which applies a mapping function to the inner blocks of a block.
	 *
	 * @param array    $block The block to map its inner blocks.
	 * @param callable $map   The mapping function to apply to the inner blocks. It accepts a block as an argument and
	 *                        should return the mapped block or false if the block shouldn't be included in the mapped block.
	 *
	 * @return array The mapped block.
	 */
	private function map_inner_blocks( array $block, callable $map ) : array {
		if ( empty( $block['innerBlocks'] ) ) {
			return $block;
		}

		// Inner blocks are represented as an entry to the 'innerBlocks' array and a null value in the 'innerContent' array.
		$inner_block_index   = 0;
		$mapped_inner_blocks = [];
		$inner_content       = [];

		foreach ( $block['innerContent'] as $chunk ) {
			// If the content is not an inner block there is nothing to do.
			if ( is_string( $chunk ) ) {
				$inner_content[] = $chunk;
				continue;
			}

			$inner_block = $block['innerBlocks'][ $inner_block_index ];

			// Map the inner block.
			$mapped_block = $map( $inner_block );

			// Add the entries in 'innerBlocks' and 'innerContent' arrays only if it was successfully mapped.
			if ( false !== $mapped_block ) {
				$mapped_inner_blocks[] = $mapped_block;
				$inner_content[]       = $chunk;
			}

			$inner_block_index++;
		}

		$block['innerBlocks']  = $mapped_inner_blocks;
		$block['innerContent'] = $inner_content;

		return $block;
	}
}
