<?php
/**
 * File containing the Sensei_Lesson_Metadata_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Lesson_Metadata_Block
 */
class Sensei_Lesson_Metadata_Block {

	/**
	 * Sensei_Lesson_Metadata_Block constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/lesson-metadata',
			[
				'render_callback' => [ $this, 'render' ],
			],
			Sensei()->assets->src_path( 'blocks/lesson-metadata' )
		);
	}

	/**
	 * Renders lesson metadata block on the frontend.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Inner block content.
	 *
	 * @return string HTML of the block.
	 */
	public function render( array $attributes, string $content ) : string {
		$length     = get_post_meta( get_the_ID(), '_lesson_length', true );
		$complexity = get_post_meta( get_the_ID(), '_lesson_complexity', true );

		if ( ! $length && ! $complexity ) {
			return $content;
		}

		$content = '<div class="lesson-metadata">';

		if ( $length ) {
			$content .=
				'<span class="lesson-length">' .
					__( 'Length', 'sensei-lms' ) . ': ' .
					sprintf( _n( '%d minute', '%d minutes', $length, 'sensei-lms' ), $length ) .
				'</span>';
		}

		if ( $complexity ) {
			$complexities = Sensei()->lesson->lesson_complexities();
			$lesson_complexity = $complexities[ $complexity ];

			if ( $lesson_complexity ) {
				$content .=
					'<span class="lesson-complexity">' .
						__( 'Complexity', 'sensei-lms' ) . ': ' . $lesson_complexity .
					'</span>';
			}
		}

		$content .= '</div>';

		return $content;
	}
}
