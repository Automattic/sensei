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

use Sensei\ThirdParty\Pelago\Emogrifier\CssInliner;
use Sensei_Settings;
use WP_Post;

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
	 * Email repository instance.
	 *
	 * @var Email_Repository
	 */
	private $repository;


	/**
	 * Email template repository instance.
	 *
	 * @var Email_Template_Repository
	 */
	private $template_repository;

	/**
	 * Email settings instance.
	 *
	 * @var Sensei_Settings
	 */
	private $settings;

	/**
	 * Email patterns instance.
	 *
	 * @var Email_Patterns
	 */
	private $email_patterns;

	/**
	 * Email_Sender constructor.
	 *
	 * @param Email_Repository $repository Email repository instance.
	 * @param Sensei_Settings  $settings Sensei settings instance.
	 * @param Email_Patterns   $email_patterns Email patterns instance.
	 */
	public function __construct( Email_Repository $repository, Sensei_Settings $settings, Email_Patterns $email_patterns ) {
		$this->repository     = $repository;
		$this->settings       = $settings;
		$this->email_patterns = $email_patterns;
	}

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
		add_action( 'sensei_email_send', [ $this, 'send_email' ], 10, 3 );
	}
	/**
	 * Send email of type.
	 *
	 * @param string $email_name The email type.
	 * @param array  $replacements The placeholder replacements.
	 * @param string $usage_tracking_type Usage tracking type.
	 *
	 * @access private
	 */
	public function send_email( $email_name, $replacements, $usage_tracking_type ) {
		$email_post = $this->get_email_post_by_name( $email_name );

		if ( ! $email_post ) {
			return;
		}

		// In case patterns are not registered.
		$this->email_patterns->register_email_block_patterns();

		/**
		 * Filter the email replacements.
		 *
		 * @since 4.12.0
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

		foreach ( $replacements as $recipient => $replacement ) {
			wp_mail(
				$recipient,
				$this->get_email_subject( $email_post, $replacement ),
				$this->get_email_body( $email_post, $replacement ),
				$this->get_email_headers(),
				null
			);
			sensei_log_event( 'email_send', [ 'type' => $usage_tracking_type ] );
		}
	}

	/**
	 * Get the email subject.
	 *
	 * @internal
	 *
	 * @param WP_Post $email_post The email post.
	 * @param array   $placeholders The placeholders.
	 *
	 * @return string
	 */
	public function get_email_subject( WP_Post $email_post, array $placeholders = [] ): string {
		return $this->replace_placeholders(
			wp_strip_all_tags( $email_post->post_title ),
			$placeholders
		);
	}

	/**
	 * Get the email body.
	 *
	 * @internal
	 *
	 * @param WP_Post $email_post The email post.
	 * @param array   $placeholders The placeholders.
	 *
	 * @return string
	 */
	public function get_email_body( WP_Post $email_post, array $placeholders = [] ): string {

		$post_id = 'revision' === $email_post->post_type ? $email_post->post_parent : $email_post->ID;

		// phpcs:ignore WordPress.WP.DiscouragedFunctions.query_posts_query_posts -- We need to modify the global query object in order to render templates.
		query_posts(
			[
				'posts_per_page' => 1,
				'p'              => $post_id,
				'post_type'      => Email_Post_Type::POST_TYPE,
			]
		);

		the_post();

		$templated_output = $this->get_templated_post_content( $placeholders );
		wp_reset_postdata();

		return CssInliner::fromHtml( $templated_output )
			->inlineCss( $this->load_email_styles() )
			->render();
	}

	/**
	 * Replace the placeholders in the provided string.
	 *
	 * @internal
	 *
	 * @param string $string The string.
	 * @param array  $placeholders The placeholders.
	 *
	 * @return string
	 */
	private function replace_placeholders( string $string, array $placeholders ): string {
		foreach ( $placeholders as $placeholder => $value ) {
			$string = str_replace( '[' . $placeholder . ']', $value, $string );
		}

		return $string;
	}

	/**
	 * Load the emails styles that should overwrite the Gutebenrg styles
	 *
	 * @internal
	 *
	 * @return string
	 */
	private function load_email_styles(): string {
		$css_dist_path = Sensei()->assets->dist_path( 'css/email-notifications/email-style.css' );

		if ( file_exists( $css_dist_path ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file usage.
			return file_get_contents( $css_dist_path );
		}

		return '';
	}

	/**
	 * Get the email post by name meta.
	 *
	 * @param string $email_identifier The email's unique name.
	 *
	 * @return WP_Post|null
	 */
	private function get_email_post_by_name( $email_identifier ) {
		$email_post = $this->repository->get( $email_identifier );

		if ( ! $email_post ) {
			return;
		}

		return $email_post;
	}
	/**
	 * Get the email body rendered in the email template.
	 *
	 * @param array $placeholders The placeholder replaced email content.
	 *
	 * @return string
	 */
	private function get_templated_post_content( $placeholders ) {
		global $sensei_email_data;
		$sensei_email_data['body_class'] = '';

		// Force use the default template usage.
		$template = do_blocks( get_block_template( Email_Page_Template::ID, 'wp_template' )->content );

		$post_content = $this->replace_placeholders(
			$template,
			$placeholders
		);

		$post_content                    = $this->add_base_url_for_images( $post_content );
		$sensei_email_data['email_body'] = $post_content;

		ob_start();

		require Sensei()->plugin_path() . 'templates/emails/block-email-template.php';

		return ltrim( ob_get_clean() );
	}

	/**
	 * Append the site URL on all images before send the email.
	 *
	 * @param string $content The email content that should be updated.
	 *
	 * @return string
	 */
	private function add_base_url_for_images( $content ) {

		return str_replace( 'src="/wp-content', 'src="' . site_url( '/' ) . 'wp-content', $content );
	}

	/**
	 * Return the email headers.
	 *
	 * @return array Headers.
	 */
	private function get_email_headers():array {
		$settings = $this->settings->get_settings();
		$headers  = [
			'Content-Type: text/html; charset=UTF-8',
		];

		if ( ! empty( $settings['email_reply_to_address'] ) ) {
			$reply_to_address = $settings['email_reply_to_address'];
			$reply_to_name    = isset( $settings['email_reply_to_name'] ) ? $settings['email_reply_to_name'] : '';
			$headers[]        = "Reply-To: $reply_to_name <$reply_to_address>";
		}

		return $headers;
	}
}
