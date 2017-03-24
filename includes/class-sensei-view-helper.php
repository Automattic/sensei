<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * View Formatting helper, all formatiing logic should be here
 * Class Sensei_View_Helper
 */
class Sensei_View_Helper {
    public function format_question_points( $points ) {
        $format = '[%s]';
        if ( isset( Sensei()->settings->settings[ 'quiz_question_points_format' ] ) ) {
            $maybe_format = Sensei()->settings->settings[ 'quiz_question_points_format' ];
            if ( false !== strpos( $maybe_format, '%s' ) ) {
                $format = esc_html__( $maybe_format );
            }
        }
        $formatted_points = !empty( $format ) ? sprintf( $format, $points ) : $points;
        return '<span class="grade">' . $formatted_points . '</span>';
    }
}