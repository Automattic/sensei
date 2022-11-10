<?php
/**
 * Admin View: Page - Home
 *
 * @package Sensei\Home
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div id="sensei-home-page" class="sensei-home-page">
	<noscript>
		<h1><?php esc_html_e( 'Sensei Home', 'sensei-lms' ); ?></h1>
		<div class="notice sensei-notice sensei-notice-error">
			<div class="sensei-notice__wrapper">
				<div class="sensei-notice__content">
					<div class="sensei-notice__heading">
						<?php esc_html_e( 'Error while loading Sensei Home', 'sensei-lms' ); ?>
					</div>
					<?php echo esc_html_e( 'This page requires JavaScript to work. Please enable JavaScript in your browser settings.', 'sensei-lms' ); ?>
				</div>
			</div>
		</div>
	</noscript>
</div>
