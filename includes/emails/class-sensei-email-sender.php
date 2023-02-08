<?php

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis -- Prevent "Unused global variable $sensei_email_data"
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require plugin_dir_path( __DIR__ ) . '../vendor/autoload.php';

use \Pelago\Emogrifier\CssInliner;

/**
 * Class Sensei_Email_Sender
 *
 * @package Sensei\Internal\Emails
 */
class Sensei_Email_Sender {

	/**
	 * The constructor.
	 *
	 * @var array
	 */
	public function __construct() {
		add_action( 'post_updated', [ $this, 'send_email_from_email_post' ], 10, 2 );
	}


	/**
	 * Check if the post is an email post and send the email.
	 *
	 * @param int     $post_ID The post ID.
	 * @param WP_Post $post    The post object.
	 */
	public function send_email_from_email_post( $post_ID, $post ) {

		if ( \Sensei\Internal\Emails\Email_Post_Type::POST_TYPE !== $post->post_type ) {
			return;
		}

		$templated_output = $this->get_templated_post_content( $post );

		$style_string = apply_filters( 'sensei_email_styles', $this->get_header_styles() );

		$html_output_with_inlined_css = CssInliner::fromHtml( $templated_output )->inlineCss( $style_string )->render();

		$headers[] = 'Content-Type: text/html; charset=UTF-8';

		wp_mail( 'test-email@senseitestemailcheck.com', 'Dummy text', $html_output_with_inlined_css, $headers, null );
	}

	/**
	 * Get the post content rendered with the email template.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return string
	 */
	private function get_templated_post_content( $post ) {
		global $sensei_email_data;
		$sensei_email_data['email_body'] = do_blocks( get_the_content( null, false, $post ) );
		$sensei_email_data['body_class'] = '';

		ob_start();

		require dirname( __FILE__ ) . '/../../templates/emails/block-email-template.php';

		return ltrim( ob_get_clean() );
	}

	/**
	 * Get the styles from the header.
	 *
	 * @return string
	 */
	private function get_header_styles() {
		ob_start() &&

		get_header();

		$header_content = ltrim( ob_get_clean() );

		$dom = new DOMDocument();
		$dom->loadHTML( $header_content );

		$header_styles = '';
		$stylesheets   = [];

		foreach ( $dom->getElementsByTagName( 'link' ) as $style_node ) {
			if ( 'stylesheet' === $style_node->attributes->getNamedItem( 'rel' )->value ) {
				$stylesheets[] = $style_node->attributes->getNamedItem( 'href' )->value;
			}
		}

		// Fetch the styles from the scripts.
		foreach ( array_reverse( $stylesheets ) as $stylesheet ) {
			$header_styles .= file_get_contents( $stylesheet ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Get css file contents.
		}

		// Fetch the internal styles from the <style></style> tags.
		foreach ( $dom->getElementsByTagName( 'style' ) as $style_node ) {
			$header_styles .= $style_node->nodeValue; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHP property.
		}

		return $header_styles;
	}
}
