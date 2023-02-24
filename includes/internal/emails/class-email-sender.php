<?php
/**
 * File containing the Email_Sender class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


require plugin_dir_path( __DIR__ ) . '../../vendor/autoload.php';

use \Pelago\Emogrifier\CssInliner;

/**
 * Class Email_Sender
 *
 * @package Sensei\Internal\Emails
 */
class Email_Sender {

	/**
	 * Email unique identifier meta key.
	 */
	public const EMAIL_ID_META_KEY = '_sensei_email_identifier';

	/**
	 * Adds all filters and actions.
	 */
	public function init() {
		/**
		 * Send email of predefined types with provided contents.
		 *
		 * @param string $email_name   The name of the email template.
		 * @param array  $replacements The placeholder replacements.
		 */
		add_action( 'sensei_send_html_email', [ $this, 'send_email' ], 10, 2 );
	}
	/**
	 * Send email of type.
	 *
	 * @param string $email_name The email type.
	 * @param array  $replacements The placeholder replacements.
	 *
	 * @access private
	 */
	public function send_email( $email_name, $replacements ) {
		$email_post = $this->get_email_post_by_name( $email_name );

		if ( ! $email_post ) {
			return;
		}

		global $post;
		$post = $email_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Necessary for the post title block to work.

		// In case patterns are not registered.
		Email_Customization::instance( \Sensei()->settings )->patterns->register_email_block_patterns();

		/**
		 * Filter the email replacements.
		 *
		 * @since $$next-version$$
		 * @hook sensei_email_replacements
		 *
		 * @param {Array}        $replacements The email replacements.
		 * @param {string}       $email_name   The email name.
		 * @param {WP_Post}      $email_post   The email post.
		 * @param {Email_Sender} $email_sender The email sender class instance.
		 *
		 * @return {Array} The email replacements.
		 */
		$replacements = apply_filters( 'sensei_email_replacements', $replacements, $email_name, $email_post, $this );

		/**
		 * Filter the email styles.
		 *
		 * @since $$next-version$$
		 * @hook sensei_email_styles
		 *
		 * @param {string}       $style_string The email styles.
		 * @param {string}       $email_name   The email name.
		 * @param {WP_Post}      $email_post   The email post.
		 * @param {Email_Sender} $email_sender The email sender class instance.
		 *
		 * @return {string}
		 */
		$style_string = apply_filters( 'sensei_email_styles', $this->get_header_styles(), $email_name, $email_post, $this );

		$subject_text = wp_strip_all_tags( $email_post->post_title );

		$headers = [
			'Content-Type: text/html; charset=UTF-8',
		];

		foreach ( $replacements as $recipient => $replacement ) {
			$email_body    = do_blocks( $email_post->post_content );
			$email_subject = $subject_text;

			foreach ( $replacement as $key => $value ) {
				$email_body    = str_replace( '[' . $key . ']', $value, $email_body );
				$email_subject = str_replace( '[' . $key . ']', $value, $email_subject );
			}

			$email_body = $this->get_templated_post_content( $email_body );
			$email_body     = CssInliner::fromHtml( $email_body )->inlineCss( $style_string )->render();

			wp_mail(
				$recipient,
				$email_subject,
				$email_body,
				$headers,
				null
			);
		}
	}

	/**
	 * Get the email post by name meta.
	 *
	 * @param string $email_name The email's name.
	 *
	 * @return WP_Post|null
	 */
	private function get_email_post_by_name( $email_name ) {
		$email_post = get_posts(
			[
				'post_type'      => Email_Post_Type::POST_TYPE,
				'posts_per_page' => 1,
				'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					[
						'key'   => self::EMAIL_ID_META_KEY,
						'value' => $email_name,
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
	 * Get the email body rendered in the email template.
	 *
	 * @param string $email_content The placeholder replaced email content.
	 *
	 * @return string
	 */
	private function get_templated_post_content( $email_content ) {
		global $sensei_email_data;

		$sensei_email_data['email_body'] = $email_content;
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

		// Enqueue block library styles in case it already isn't.
		if ( wp_script_is( 'wp-block-library', 'registered' ) && ! wp_script_is( 'wp-block-library', 'enqueued' ) ) {
			wp_enqueue_style( 'wp-block-library' );
		}

		ob_start() &&

		get_header();

		$header_content = ltrim( ob_get_clean() );

		if ( ! $header_content ) {
			return '';
		}

		$dom = new \DOMDocument();
		$dom->loadHTML( $header_content );

		$header_styles = '';
		$stylesheets   = [];

		foreach ( $dom->getElementsByTagName( 'link' ) as $style_node ) {
			if ( 'stylesheet' === $style_node->attributes->getNamedItem( 'rel' )->value ) {
				$stylesheets[] = $style_node->attributes->getNamedItem( 'href' )->value;
			}
		}

		/**
		 * Filter the allowed stylesheets to be included in the email.
		 *
		 * @since $$next-version$$
		 * @hook sensei_email_allowed_stylesheets
		 *
		 * @param {string[]} $allowed_stylesheets Parts of paths to uniquely identify allowed stylesheets.
		 * @param {string[]} $stylesheets         All the stylesheets found in the header.
		 *
		 * @return {string[]}
		 */
		$allowed_stylesheets = apply_filters( 'sensei_email_allowed_stylesheets', [ 'block-library/style.min.css' ], $stylesheets );

		$stylesheets = array_filter(
			$stylesheets,
			function( $stylesheet ) use ( $allowed_stylesheets ) {
				foreach ( $allowed_stylesheets as $allowed_stylesheet ) {
					if ( false !== strpos( $stylesheet, $allowed_stylesheet ) ) {
						return true;
					}
				}
				return false;
			}
		);

		/**
		 * Filter the stylesheets to be included in the email.
		 *
		 * @since $$next-version$$
		 * @hook sensei_email_stylesheets
		 *
		 * @param {string[]} $stylesheets Stylesheets to be included in the email.
		 *
		 * @return {string[]}
		 */
		$stylesheets = apply_filters( 'sensei_email_stylesheets', $stylesheets );

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
