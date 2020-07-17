import { __ } from '@wordpress/i18n';
import { Section, H } from '@woocommerce/components';

/**
 * This component displays the final page when import has completed.
 */
export const DonePage = () => {
	return (
		<section className="sensei-done-page">
			<header className="sensei-data-port-step__header">
				<H>{ __( 'Done', 'sensei-lms' ) }</H>
			</header>
			<Section component="section">
				<p>Placeholder.</p>
			</Section>
		</section>
	);
};
