import { getFeatureObservation } from './feature-description-utils';

describe( '<FeatureDescription />', () => {
	it( 'Should render WooCommerce description with observation for one plugin', () => {
		const selectedFeatures = [
			{ rawTitle: 'Title 1', wccom_product_id: '123' },
			{ rawTitle: 'Title 2' },
		];

		const observation = getFeatureObservation(
			'woocommerce',
			selectedFeatures
		);

		expect( observation ).toMatch( /updates for Title 1\./ );
	} );

	it( 'Should render WooCommerce description with observation for two plugins', () => {
		const selectedFeatures = [
			{ rawTitle: 'Title 1', wccom_product_id: '123' },
			{ rawTitle: 'Title 2', wccom_product_id: '456' },
		];

		const observation = getFeatureObservation(
			'woocommerce',
			selectedFeatures
		);

		expect( observation ).toMatch( /updates for Title 1 and Title 2\./ );
	} );
} );
