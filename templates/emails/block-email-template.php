<!doctype html>
<?php
	global $sensei_email_data;
?>
<html>

<head>
	<meta name="viewport" content="width=device-width" />
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<style>
		img {
			border: none;
			-ms-interpolation-mode: bicubic;
			max-width: 100%;
		}

		body {
			background-color: #f3f3f3;
			font-family: -apple-system, system-ui, blinkmacsystemfont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			-webkit-font-smoothing: antialiased;
			-moz-osx-font-smoothing: grayscale;
			font-size: 15px;
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

		table td {
			font-family: -apple-system, system-ui, blinkmacsystemfont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			font-size: 16px;
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

		.narrow .content {
			max-width: 800px;
		}

		/* -------------------------------------
				HEADER, FOOTER, MAIN
		------------------------------------- */
		.main {
			background: #ffffff;
			width: 100%;
			border-radius: 2px;
		}

		.wrapper {
			box-sizing: border-box;
			padding: 40px 56px 0;
		}

		.content-block {
			padding-bottom: 16px;
			padding-top: 16px;
		}

		.header {
			margin-bottom: 48px;
			line-height: 24px;
		}

		.header a {
			text-decoration: none;
		}

		.logo {
			vertical-align: top;
			min-width: 88px;
			width: 30%;
		}

		.logo img {
			width: 52px;
			height: 24px;
			display: block;
		}

		.header-p2 {
			font-size: 16px;
			vertical-align: top;
			text-align: right;
		}

		.header-p2 a {
			font-weight: 700;
		}

		.header-type {
			white-space: nowrap;
			vertical-align: top;
		}

		.footer {
			clear: both;
			text-align: center;
			width: 100%;
		}

		.footer td,
		.footer p,
		.footer span,
		.footer a {
			color: #00101C;
			font-size: 14px;
			text-align: center;
		}

		.w-logo {
			width: 20px;
			height: 20px;
			display: inline-block;
			margin-bottom: 3px;
			margin-right: 8px;
			vertical-align: middle;
		}

		.narrow .wrapper {
			padding: 40px 48px 0;
		}

		/* -------------------------------------
				TYPOGRAPHY
		------------------------------------- */
		h1,
		h2,
		h3,
		h4 {
			color: #00101C;
			line-height: 1.2;
			margin: 0 0 24px;
		}

		h1 {
			font-size: 40px;
			font-weight: 900;
			letter-spacing: -0.01em;
			line-height: 1.15;
			margin-bottom: 56px;
			-webkit-font-smoothing: default;
			-moz-osx-font-smoothing: auto;
		}

		h1 a {
			color: inherit;
			text-decoration: none;
		}

		h1.post-name {
			font-size: 40px;
			font-weight: 900;
			margin: 0 0 16px;
		}

		h1.post-name a {
			color: #00101C;
			text-decoration: none;
		}

		h2 {
			font-size: 28px;
			font-weight: 700;
			margin: 48px 0 24px;
		}

		h3 {
			font-size: 22px;
			font-weight: 700;
			margin: 48px 0 24px;
		}

		h4 {
			font-size: 16px;
			font-weight: 700;
			margin: 48px 0 24px;
			letter-spacing: -0.02em;
			text-transform: uppercase;
		}

		p,
		ul,
		ol {
			font-family: -apple-system, system-ui, blinkmacsystemfont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			font-size: 16px;
			font-weight: 400;
			margin: 0;
			line-height: 1.7;
			padding: 0;
			color: #00101C;
		}

		li {
			font-family: -apple-system, system-ui, blinkmacsystemfont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			font-size: 16px;
			font-weight: 400;
			line-height: 1.7;
			padding: 0;
			color: #00101C;
		}

		p li,
		ul li,
		ol li {
			margin-left: 8px;
		}

		ul,
		ol {
			margin-left: 40px;
		}

		p li + li,
		ul li + li,
		ol li + li {
			margin-top: 24px;
		}

		a {
			color: #0267FF;
			text-decoration: underline;
		}

		.narrow h1 {
			margin-bottom: 40px;
		}

		.narrow .person {
			margin-top: 0;
		}

		/* -------------------------------------
				BUTTONS
		------------------------------------- */
		.btn {
			box-sizing: border-box;
			width: 100%;
			width: auto;
			float: left;
		}

		.btn > tbody > tr > td {

		}

		.btn table {
			width: auto;
		}

		.btn table td {
			background-color: #ffffff;
			text-align: center;
			border-radius: 100px;
		}

		.btn a {
			background-color: #ffffff;
			border: solid 1px transparent;
			box-sizing: border-box;
			color: #0267FF;
			cursor: pointer;
			display: inline-block;
			font-size: 16px;
			font-weight: 400;
			line-height: 1;
			margin: 0;
			padding: 16px 16px;
			text-decoration: underline;
			white-space: nowrap;
			border-radius: 100px;
		}

		.btn-primary a {
			background-color: #0267FF !important;
			border-color: #0267FF !important;
			text-decoration: none;
			color: #ffffff;
			padding: 16px 48px;
		}

		.actions {
			margin: 24px 0 64px;
			width: 100%;
		}

		.actions .btn-primary > tbody > tr > td > table {
			margin-right: 16px;
			border-radius: 100px;
		}

		/* -------------------------------------
				OTHER STYLES THAT MIGHT BE USEFUL
		------------------------------------- */

		.person {
			margin-bottom: 32px;
			margin-top: 16px;
		}

		.person img {
			border-radius: 24px;
			display: block;
			margin: 0 !important;
			width: 48px;
			height: 48px;
		}

		.person p {
			margin: 0 !important;
			padding: 0;
			line-height: 1.35;
		}

		.person .name {
			font-weight: 700;
			letter-spacing: -0.01em;
			font-size: 16px !important;
		}

		.person .meta {
			font-weight: 700;
			color: #0267FF;
			font-size: 16px !important;
		}

		.person .meta a {
			text-decoration: none;
		}

		.last {
			margin-bottom: 0;
		}

		.first {
			margin-top: 0;
		}

		.align-center {
			text-align: center;
		}

		.align-right {
			text-align: right;
		}

		.align-left {
			text-align: left;
		}

		.clear {
			clear: both;
		}

		.preheader {
			color: transparent;
			display: none;
			height: 0;
			max-height: 0;
			max-width: 0;
			opacity: 0;
			overflow: hidden;
			mso-hide: all;
			visibility: hidden;
			width: 0;
		}

		.powered-by {
			vertical-align: middle;
		}

		.powered-by a {
			text-decoration: none;
		}

		hr {
			border: 0;
			border-bottom: 1px solid #eaeaea;
			margin: 20px 0;
		}

		.wp-block-quote,
		.blockquote,
		blockquote {
			background: #f9f9f9;
			margin: 0;
			color: #00101C;
			border-left: 4px solid #0267FF;
			border-radius: 2px;
			padding: 32px;
			font-family: -apple-system, system-ui, blinkmacsystemfont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
		}

		blockquote {
			margin: 0 0 24px !important;
		}

		blockquote cite {
			font-size: 14px;
			text-align: right;
			display: block;
			font-style: normal;
			padding: 0 32px 16px;
		}

		.blockquote p,
		blockquote p {
			margin: 0 0 24px;
			font-size: 16px;
		}

		blockquote.comment-parent {
			margin-bottom: 40px !important;
			font-style: italic;
		}

		blockquote.comment-parent .comment-parent-author {
			font-size: 14px;
			font-style: normal;
			margin-bottom: 16px;
			text-decoration: none;
		}

		blockquote.comment-parent .comment-parent-author a {
			text-decoration: none;
		}

		figure {
			margin: 0 32px 24px;
		}

		.mention {
			font-weight: 600;
			padding: 3px 4px;
			border-radius: 3px;
			text-decoration: none;
			color: #0267ff;
			background: #e6f0ff;
		}

		.mention-current-user {
			font-weight: 600;
			padding: 3px 4px;
			border-radius: 3px;
			text-decoration: none;
			color: #FFFFFF;
			background: #0267ff;
		}

		.avatar {
			border: none !important;
			border-radius: 50%;
		}

		.tag,
		.po-xposts {
			font-weight: 600;
			padding: 3px 4px;
			border-radius: 3px;
			text-decoration: none;
			color: #0267FF;
			background: #f3f3f3;
		}

		.im {
			color: #00101C;
		}

		.text-ellipsis {
			color: #6B6B6B;
		}

		.footer-content p {
			font-size: 16px;
			margin: 0;
		}

		.footer-content-border {
			background: #EAEAEA;
			height: 1px;
			width: 80px;
		}

		.footer-content-text {
			padding: 24px 0;
		}

		.main-content p,
		.main-content div,
		.main-content ul,
		.main-content ol,
		.main-content img {
			margin-bottom: 24px;
		}

		.main-content .wp-smiley,
		.main-content .emoji {
			display: inline-block;
			margin-bottom: 0;
			width: auto;
			height: 1em;
		}

		.challenge-code {
			background: #f3f3f3;
			font-size: 1.4em;
			font-weight: 700;
			padding: 1.4em 0;
			text-align: center;
		}

		/* -------------------------------------
				RESPONSIVE AND MOBILE FRIENDLY STYLES
		------------------------------------- */
		@media only screen and (max-width: 800px) {
			table[class=body] .container {
				width: 100% !important;
			}
		}

		@media only screen and (max-width: 620px) {
			table[class=body] .wrapper {
				padding: 32px 24px !important;
			}

			table[class=body] .content {
				padding: 0 !important;
			}

			table[class=body] .container {
				padding: 0 !important;
				width: 100% !important;
				margin-top: 0 !important;
				margin-bottom: 5px !important;
			}

			table[class=body] .main {
				border-left-width: 0 !important;
				border-radius: 0 !important;
				border-right-width: 0 !important;
			}

			table[class=body] .header {
				margin-bottom: 40px !important;
			}

			table[class=body] p,
			table[class=body] ul,
			table[class=body] ol,
			table[class=body] li {
				font-size: 18px !important;
			}

			table[class=body] ul,
			table[class=body] ol {
				margin-left: 32px !important;
			}

			table[class=body] .btn {
				float: none !important;
				width: 100% !important;
			}

			table[class=body] .btn + .btn {
				margin-top: 16px !important;
			}

			table[class=body] .btn table {
				width: 100% !important;
			}

			table[class=body] .btn a {
				width: 100% !important;
				background: #f9f9f9 !important;
				font-size: 18px !important;
			}

			table[class=body] .btn-primary a {
				background: #0267FF !important;
			}

			table[class=body] .img-responsive {
				height: auto !important;
				max-width: 100% !important;
				width: auto !important;
			}

			table[class=body] .img-responsive {
				height: auto !important;
				max-width: 100% !important;
				width: auto !important;
			}

			.powered-by span {
				display: none;
			}
		}

		/* -------------------------------------
				PRESERVE THESE STYLES IN THE HEAD
		------------------------------------- */
		@media all {
			.ExternalClass {
				width: 100%;
			}

			.ExternalClass,
			.ExternalClass p,
			.ExternalClass span,
			.ExternalClass font,
			.ExternalClass td,
			.ExternalClass div {
				line-height: 100%;
			}

			.apple-link a {
				color: inherit !important;
				font-family: inherit !important;
				font-size: inherit !important;
				font-weight: inherit !important;
				line-height: inherit !important;
				text-decoration: none !important;
			}

			#MessageViewBody a {
				color: inherit;
				text-decoration: none;
				font-size: inherit;
				font-family: inherit;
				font-weight: inherit;
				line-height: inherit;
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
														<?php echo $sensei_email_data['email_body']; ?>
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
