<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * View Formatting helper, all formatiing logic should be here
 * Class Sensei_View_Helper
 */
class Sensei_View_Helper {

    /**
     * Formats a quiz question's points
     * @param $points
     * @return string
     */
    public function format_question_points( $points ) {
        $format = $this->get_setting_or_default( 'quiz_question_points_format', 'number' );

        // default is `number`
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
     * @param $formatted_points
     * @return string
     */
    private function with_text($formatted_points)
    {
        return sprintf(__('Points: %s', 'woothemes-sensei'), $formatted_points);
    }

    /**
     * @param $formatted_points
     * @return string
     */
    private function with_brackets($formatted_points)
    {
        return '[' . $formatted_points . ']';
    }

    /**
     * @param $setting_name
     * @param null $default
     * @return null
     */
    private function get_setting_or_default( $setting_name, $default = null ) {
        if ( isset( Sensei()->settings->settings[ $setting_name ] ) ) {
            return Sensei()->settings->settings[ $setting_name ];
        }
        return $default;
    }
}