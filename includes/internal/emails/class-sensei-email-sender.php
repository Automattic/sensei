<?php
/**
 * File containing the Sensei_Email_Sender class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis -- Prevent "Unused global variable $sensei_email_data"
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


require plugin_dir_path( __DIR__ ) . '../../vendor/autoload.php';

use \Pelago\Emogrifier\CssInliner;

/**
 * Class Sensei_Email_Sender
 *
 * @package Sensei\Internal\Emails
 */
class Sensei_Email_Sender {

	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * The constructor.
	 *
	 * @var array
	 */
	private function __construct() {}

	/**
	 * Fetches an instance of the class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Adds all filters and actions.
	 */
	public function init() {
		/**
		 * Send email of predefined types with provided contents.
		 *
		 * @param string $email_type The email type.
		 * @param array  $replacements The placeholder replacements.
		 */
		add_action( 'sensei_send_html_email', [ $this, 'send_email_of_type' ], 10, 2 );
	}

	/**
	 * Send email of type.
	 *
	 * @param string $email_type The email type.
	 * @param array  $replacements The placeholder replacements.
	 *
	 * @return WP_Post|null
	 */
	public function send_email_of_type( $email_type, $replacements ) {
		$email_post = $this->get_email_post_by_type( $email_type );

		if ( ! $email_post ) {
			return;
		}

		$templated_output = $this->get_templated_post_content( $email_post );

		$style_string = apply_filters( 'sensei_email_styles', $this->get_header_styles(), $email_type, $email_post );

		$html_output_with_inlined_css = CssInliner::fromHtml( $templated_output )->inlineCss( $style_string )->render();

		$subject_text = wp_strip_all_tags( $email_post->post_title );

		$headers[] = 'Content-Type: text/html; charset=UTF-8';

		foreach ( $replacements as $recipient => $replacement ) {
			$email_body    = $html_output_with_inlined_css;
			$email_subject = $subject_text;

			foreach ( $replacement as $key => $value ) {
				$email_body    = str_replace( '[' . $key . ']', $value, $email_body );
				$email_subject = str_replace( '[' . $key . ']', $value, $email_subject );
			}

			wp_mail(
				apply_filters( 'sensei_test_email_address', $recipient ),
				$email_subject,
				$email_body,
				$headers,
				null
			);
		}
	}

	/**
	 * Get the email post by type.
	 *
	 * @param string $email_type The email type.
	 *
	 * @return WP_Post|null
	 */
	private function get_email_post_by_type( $email_type ) {
		$email_post = get_posts(
			[
				'post_type'      => \Sensei\Internal\Emails\Email_Post_Type::POST_TYPE,
				'posts_per_page' => 1,
				'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					[
						'key'   => '_sensei_email_type',
						'value' => $email_type,
					],
				],
			]
		);

		if ( ! $email_post ) {
			return;
		}

		return $email_post[0];
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

		require dirname( __FILE__ ) . '/../../../templates/emails/block-email-template.php';

		return ltrim( ob_get_clean() );
	}

	/**
	 * Get the styles from the header.
	 *
	 * @return string
	 */
	private function get_header_styles() {
		remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );

		ob_start() &&

		get_header();

		$header_content = ltrim( ob_get_clean() );

		$dom = new \DOMDocument();
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
