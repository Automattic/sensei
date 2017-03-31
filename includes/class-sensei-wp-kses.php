<?php

class Sensei_Wp_Kses {

    private static $allowed_html = null;

    /**
     * Essentially a copy of wp_kses() with a custom hook
     * see https://github.com/Automattic/sensei/issues/1560
     * @param $string
     * @param $allowed_html
     * @param array $allowed_protocols
     * @return string
     */
    public static function wp_kses($string, $allowed_html = null, $allowed_protocols = array() ) {
        if ( empty( $allowed_protocols ) ) {
            $allowed_protocols = wp_allowed_protocols();
        }
        if ( null === $allowed_html ) {
            $allowed_html = self::get_default_wp_kses_allowed_html();
        }
        $string = wp_kses_no_null( $string, array( 'slash_zero' => 'keep' ) );
        $string = wp_kses_normalize_entities( $string );
        /**
         * Filter content similar to pre_kses
         */
        $string = apply_filters( 'sensei_pre_kses', $string, $allowed_html, $allowed_protocols );
        return wp_kses_split( $string, $allowed_html, $allowed_protocols );
    }

    static function get_default_wp_kses_allowed_html() {
        if ( null === self::$allowed_html ) {
            self::$allowed_html = array(
                'embed'  => array(),
                'iframe' => array(
                    'width'           => array(),
                    'height'          => array(),
                    'src'             => array(),
                    'frameborder'     => array(),
                    'allowfullscreen' => array(),
                ),
                'video'  => self::get_video_html_tag_allowed_attributes(),
                'a' => array(
                    'class' => array(),
                    'href' => array(),
                    'rel' => array(),
                ),
                'span' => array(
                    'class' => array()
                ),
                'source' => self::get_source_html_tag_allowed_attributes()
            );
        }
        return self::$allowed_html;
    }

    public static function get_video_html_tag_allowed_attributes() {
        return array(
            'source'   => array(),
            'autoplay' => array(),
            'controls' => array(),
            'height'   => array(),
            'loop'     => array(),
            'muted'    => array(),
            'poster'   => array(),
            'preload'  => array(),
            'src'      => array(),
            'width'    => array(),
        );
    }

    public static function get_source_html_tag_allowed_attributes() {
        return array(
            'src'    => array(),
            'type'   => array(),
            'srcset' => array(),
            'sizes'  => array(),
            'media'  => array()
        );
    }

	/**
	 * Will act as a sanitization or an identity function, depending on HTML security settings.
	 *
	 * @param string $content Content
	 * @param array $allowed_html
	 * @return string Content
	 */
	public static function maybe_sanitize( $content, $allowed_html )
	{
		$html_security = ! Sensei()->settings->get( 'sensei_video_embed_html_sanitization_disable' );

		return $html_security ? self::wp_kses( $content, $allowed_html ) : $content;
	}
}
