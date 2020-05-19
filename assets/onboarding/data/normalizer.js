import { __ } from '@wordpress/i18n';

import { INSTALLED_STATUS } from '../features/feature-status';

/**
 * Add details to title.
 *
 * @param {string}        title    Feature title.
 * @param {string|number} price    Feature price.
 * @param {string}        [status] Feature status.
 */
const addDetailsToTitle = ( title, price, status ) => {
	let titleComplement;

	if ( status === INSTALLED_STATUS ) {
		titleComplement = __( 'Installed', 'sensei-lms' );
	} else {
		titleComplement = price
			? `${ price } ${ __( 'per year', 'sensei-lms' ) }`
			: __( 'Free', 'sensei-lms' );
	}

	return `${ title } — ${ titleComplement }`;
};

/**
 * Normalize setup wizard data.
 *
 * @param {Object} data Setup wizard data.
 *
 * @return {Object} Normalized steup wizard data.
 */
export const normalizeSetupWizardData = ( data ) => ( {
	...data,
	features: {
		...data.features,
		options: data.features.options.map( ( feature ) => ( {
			...feature,
			slug: feature.product_slug,
			title: addDetailsToTitle(
				feature.title,
				feature.price,
				feature.status
			),
		} ) ),
	},
} );
