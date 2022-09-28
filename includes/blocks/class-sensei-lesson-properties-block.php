<?php
/**
 * File containing the Sensei_Lesson_Properties_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Lesson_Properties_Block
 */
class Sensei_Lesson_Properties_Block {

	/**
	 * Sensei_Lesson_Properties_Block constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/lesson-properties',
			[
				'render_callback' => [ $this, 'render' ],
			],
			Sensei()->assets->src_path( 'blocks/lesson-properties' )
		);
	}

	/**
	 * Renders lesson properties block on the frontend.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Inner block content.
	 *
	 * @return string HTML of the block.
	 */
	public function render( array $attributes, string $content ) : string {
		$length     = get_post_meta( get_the_ID(), '_lesson_length', true );
		$difficulty = get_post_meta( get_the_ID(), '_lesson_complexity', true );

		if ( ! $length && ! $difficulty ) {
			return $content;
		}

		$content = '<div class="wp-block-sensei-lms-lesson-properties">';

		if ( $length ) {
			$content .=
				'<span class="wp-block-sensei-lms-lesson-properties__length">' .
					__( 'Length', 'sensei-lms' ) . ': ' .
					// translators: placeholder is lesson length in minutes.
					sprintf( _n( '%d minute', '%d minutes', $length, 'sensei-lms' ), $length ) .
				'</span>';
		}

		if ( $length && $difficulty ) {
			$content .= '<span class="wp-block-sensei-lms-lesson-properties__separator">|</span>';
		}

		if ( $difficulty ) {
			$difficulties      = Sensei()->lesson->lesson_complexities();
			$lesson_difficulty = $difficulties[ $difficulty ];

			if ( $lesson_difficulty ) {
				$content .=
					'<span class="wp-block-sensei-lms-lesson-properties__difficulty">' .
						__( 'Difficulty', 'sensei-lms' ) . ': ' . $lesson_difficulty .
					'</span>';
			}
		}

		$content .= '</div>';

		return $content;
	}
}
