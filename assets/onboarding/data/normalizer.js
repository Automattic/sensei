import { __ } from '@wordpress/i18n';

import { INSTALLED_STATUS } from '../features/feature-status';

/**
 * Add details to title.
 *
 * @param {Object}        feature
 * @param {string}        feature.title    Feature title.
 * @param {string|number} feature.price    Feature price.
 * @param {string}        [feature.status] Feature status.
 */
const getTitleWithDetails = ( { title, price, status } ) => {
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
 * Normalize features data.
 *
 * @param {Object} data Fatures data.
 *
 * @return {Object} Normalized features data.
 */
export const normalizeFeaturesData = ( data ) => ( {
	...data,
	options: data.options.map( ( feature ) => ( {
		...feature,
		slug: feature.product_slug,
		title: getTitleWithDetails( feature ),
	} ) ),
} );

/**
 * Normalize setup wizard data.
 *
 * @param {Object} data Setup wizard data.
 *
 * @return {Object} Normalized steup wizard data.
 */
export const normalizeSetupWizardData = ( data ) => ( {
	...data,
	features: normalizeFeaturesData( data.features ),
} );
