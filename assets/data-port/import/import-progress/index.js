import { __ } from '@wordpress/i18n';
import { Section, H } from '@woocommerce/components';

/**
 * This component displays the final page when import has completed.
 */
export const ImportProgressPage = () => {
	return (
		<section className={ 'sensei-import-progress' }>
			<header className={ 'sensei-import-progress__header' }>
				<H>{ __( 'Import', 'sensei-lms' ) }</H>
			</header>
			<Section component={ 'section' }>
				<p>Placeholder.</p>
			</Section>
		</section>
	);
};
