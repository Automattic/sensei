<?php
/**
 * View Formatting helper, all formatiing logic should be here
 *
 * @package view-helper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_View_Helper
 */
class Sensei_View_Helper {

	/**
	 * Formats a quiz question's points
	 *
	 * @param mixed $points The Points.
	 * @return string
	 */
	public function format_question_points( $points ) {
		$format = $this->get_setting_or_default( 'quiz_question_points_format', 'number' );

		// default is `number`.
		$formatted_points = $points;

		if ( 'none' === $format ) {
			$formatted_points = '';
		}

		if ( 'brackets' === $format ) {
			$formatted_points = $this->with_brackets( $formatted_points );
		}

		if ( 'text' === $format ) {
			$formatted_points = $this->with_text( $formatted_points );
		}

		if ( 'full' === $format ) {
			$formatted_points = $this->with_brackets( $this->with_text( $formatted_points ) );
		}

		/**
		 * Sensei Quiz question points format filter
		 *
		 * @since 1.9.13
		 *
		 * @param string|int $points the quiz question points
		 * @param string $formatted_points the formatted point output
		 */
		$filtered_formatted_points = apply_filters( 'sensei_quiz_question_points_format', $formatted_points, $points );

		return '<span class="grade">' . esc_html( $filtered_formatted_points ) . '</span>';
	}

	/**
	 * With Text.
	 *
	 * @param string $formatted_points Formatted.
	 * @return string
	 */
	private function with_text( $formatted_points ) {
		// translators: number of points.
		return sprintf( __( 'Points: %s', 'woothemes-sensei' ), $formatted_points );
	}

	/**
	 * With Brackets.
	 *
	 * @param string $formatted_points Points.
	 * @return string
	 */
	private function with_brackets( $formatted_points ) {
		return '[' . $formatted_points . ']';
	}

	/**
	 * Get Setting Or Default.
	 *
	 * @param string $setting_name Setting Name.
	 * @param mixed  $default Default Value.
	 * @return null
	 */
	private function get_setting_or_default( $setting_name, $default = null ) {
		if ( isset( Sensei()->settings->settings[ $setting_name ] ) ) {
			return Sensei()->settings->settings[ $setting_name ];
		}
		return $default;
	}
}
