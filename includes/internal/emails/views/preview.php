<?php
/**
 * The template used for the email preview.
 *
 * @package sensei
 * @since 4.12.0
 *
 * @var string $subject The email subject.
 * @var string $avatar The avatar image.
 * @var string $from_name The email from name.
 * @var string $from_address The email from address.
 *
 * phpcs:disable WordPress.WP.EnqueuedResources, VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<title><?php esc_html_e( 'Email Preview', 'sensei-lms' ); ?></title>

		<meta charset="<?php bloginfo( 'charset' ); ?>" />
		<meta name="viewport" content="width=device-width" />

		<style>
			body {
				margin: 0;
				padding: 0;
				background: #f3f3f3;
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

			.wrapper {
				margin: 40px 20px;
			}

			.container {
				max-width: 800px;
				margin: 0 auto;
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

			.content {
				margin: 0 -20px;
				background: #f3f3f3;
			}

			.content iframe {
				width: 100%;
				border: none;
				overflow: hidden;
			}

			@media screen and (min-width: 1024px) {
				.wrapper {
					margin: 120px 20px;
				}
			}
		</style>

		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@500;700&display=swap" rel="stylesheet">
	</head>
	<body>
		<div class="wrapper">
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
			</div>

			<div class="content">
				<iframe
					src="<?php echo esc_url( add_query_arg( 'render_email', 1 ) ); ?>"
					sandbox="allow-same-origin"
					onload="this.style.height=(this.contentWindow.document.body.scrollHeight+20)+'px';"
					frameborder="0"
					scrolling="no"
				>
				</iframe>
			</div>
		</div>
	</body>
</html>
