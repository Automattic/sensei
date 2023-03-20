<!doctype html>
<?php
/**
 * Email template
 *
 * @since $$next-version$$
 * @package sensei
 */

	global $sensei_email_data;
?>
<html>

<head>
	<meta name="viewport" content="width=device-width" />
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<style>
		* {
			font-family: -apple-system, "SF Pro Text", BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif !important;
		}

		.wp-element-button, .editor-styles-wrapper .wp-block-button__link {
			text-transform: capitalize;
		}

		.inline-block {
			display: inline-block;
		}
		.wp-block-button__link{
			text-decoration: none;
		}
		.wp-block-post-title {
			font-family: -apple-system, "SF Pro Text", BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif !important;
		}
		img {
			-ms-interpolation-mode: bicubic;
			max-width: 100%;
			border-style: solid;
		}

		body {
			background-color: #f3f3f3;
			-webkit-font-smoothing: antialiased;
			-moz-osx-font-smoothing: grayscale;
			color: #00101C;
			line-height: 1.7;
			margin: 0;
			padding: 0;
			-ms-text-size-adjust: 100%;
			-webkit-text-size-adjust: 100%;
		}

		table {
			border-collapse: collapse;
			mso-table-lspace: 0pt;
			mso-table-rspace: 0pt;
			width: 100%;
		}

		/* -------------------------------------
				BODY & CONTAINER
		------------------------------------- */

		.body {
			background-color: #f3f3f3;
			width: 100%;
		}

		.container {
			display: block;
			margin: 0 auto !important;
			max-width: 800px;
			width: 100%;
		}

		.narrow .container {
			max-width: 528px;
			width: 528px;
		}

		.content {
			box-sizing: border-box;
			display: block;
			margin: 0 auto;
			padding: 40px 24px 24px;
		}


		/* -------------------------------------
				RESPONSIVE AND MOBILE FRIENDLY STYLES
		------------------------------------- */
		@media only screen and (max-width: 800px) {
			table[class=body] .container {
				width: 100% !important;
			}
		}

	</style>
</head>

<body class="<?php echo esc_attr( $sensei_email_data['body_class'] ); ?>">
<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="body">
	<tr>
		<td class="container">
			<div class="content">
				<table role="presentation" class="main">
					<tr>
						<td class="wrapper">
							<table role="presentation" border="0" cellpadding="0" cellspacing="0">
								<tr>
									<td>
										<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="main-content">
											<tbody>
											<tr>
												<td>
													<div class="post-body">
														<?php echo wp_kses_post( $sensei_email_data['email_body'] ); ?>
													</div>
												</td>
											</tr>
											</tbody>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</div>
		</td>
	</tr>
</table>
</body>
</html>
