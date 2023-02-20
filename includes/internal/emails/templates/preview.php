<?php
/**
 * The template used for the email preview.
 *
 * @package sensei
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals, WordPress.WP.GlobalVariablesOverride, WordPress.WP.EnqueuedResources
 */

use Sensei\Internal\Emails\Email_Post_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification -- Nonce validated at a later point.
$post_id = isset( $_GET['sensei_email_preview_id'] ) ? (int) $_GET['sensei_email_preview_id'] : 0;

// TODO: Enable the nonce check once we have it working.
// check_admin_referer( 'preview-email-post_' . $post_id );.

if ( ! current_user_can( 'manage_sensei' ) ) {
	wp_die( esc_html__( 'Insufficient permissions', 'sensei-lms' ) );
}

$post = get_post( $post_id );

if ( ! $post || ! Email_Post_Type::POST_TYPE === $post->post_type ) {
	wp_die( esc_html__( 'Invalid request', 'sensei-lms' ) );
}

$subject      = get_the_title( $post );
$from_address = Sensei()->emails->get_from_address();
$from_name    = Sensei()->emails->get_from_name();
$avatar       = get_avatar( $from_address, 40, '', '', [ 'force_display' => true ] );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<title><?php esc_html_e( 'Email Preview', 'sensei-lms' ); ?></title>

		<meta charset="<?php bloginfo( 'charset' ); ?>" />
		<meta name="viewport" content="width=device-width" />

		<style>
			body {
				margin: 20px;
				padding: 0;
				background: #f6f7f7;
				color: rgba(0, 0, 0, 0.54);
				font-size: 12px;
				font-weight: 500;
				font-family: 'Roboto', sans-serif;
				line-height: 20px;
			}

			h2 {
				margin-bottom: 20px;
				color: #000;
				font-size: 20px;
				font-family: system-ui, 'Roboto', sans-serif;
				line-height: 24px;
			}

			.container {
				max-width: 800px;
				margin: 0 auto;
			}

			.headers {
				margin-bottom: 48px;
			}

			.avatar {
				float: left;
				margin-right: 12px;
				border-radius: 50%;
			}

			.from strong {
				color: #000;
				font-size: 14px;
			}

			.to:after {
				content: "\25BE";
				margin-left: 5px;
			}

			@media screen and (min-width: 1024px) {
				.container {
					margin: 120px auto;
				}
			}
		</style>

		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@500;700&display=swap" rel="stylesheet">
	</head>
	<body>
		<div class="container">
			<h2><?php echo esc_html( $subject ); ?></h2>

			<div class="headers">
				<?php echo wp_kses_post( $avatar ); ?>

				<div class="from">
					<strong><?php echo esc_html( $from_name ); ?></strong>
					&#60;<?php echo esc_html( $from_address ); ?>&#62;
				</div>

				<div class="to"><?php esc_html_e( 'to me', 'sensei-lms' ); ?></div>
			</div>

			<div class="content">
				<!-- TODO -->
			</div>
		</div>
	</body>
</html>
