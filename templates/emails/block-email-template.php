<!doctype html>
<?php
/**
 * Email template
 *
 * @since 4.12.0
 * @package sensei
 */

global $sensei_email_data;
?>
<html>

<head>
	<meta name="viewport" content="width=device-width" />
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>

<style>
	html {
		background-color: #f3f3f3;
		padding-top: 32px;
	}

	a {
		color: currentColor;
	}

	img {
		-ms-interpolation-mode: bicubic;
		max-width: 100%;
	}

	body {
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

	.body {
		background-color: #f3f3f3;
		width: 100%;
	}

	.container {
		display: block;
		margin: 0 auto;
		max-width: 800px;
		width: 100%;
	}

	.content {
		box-sizing: border-box;
		display: block;
		margin: 0 auto;
		padding: 0;
	}

	.content,
	.content * {
		font-family: -apple-system, "SF Pro Text", BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
	}
</style>

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
