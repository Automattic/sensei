<!doctype html>
<?php
/**
 * Email template
 *
 * @since 9.9.9
 * @package sensei
 */

	global $sensei_email_data;
?>
<html>

<head>
	<meta name="viewport" content="width=device-width" />
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
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
