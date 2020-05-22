import { normalizeFeaturesData, normalizeSetupWizardData } from './normalizer';

describe( 'Data normalizer', () => {
	const expectedFeaturesData = {
		selected: [ 'free' ],
		options: [
			{
				product_slug: 'free',
				slug: 'free',
				title: 'Title — Free',
				price: 0,
			},
			{
				product_slug: 'price',
				slug: 'price',
				title: 'Title — $100.00 per year',
				price: '$100.00',
			},
			{
				product_slug: 'installed',
				slug: 'installed',
				title: 'Title — Installed',
				price: 0,
				status: 'installed',
			},
		],
	};

	const rawFeatures = {
		selected: [ 'free' ],
		options: [
			{
				product_slug: 'free',
				title: 'Title',
				price: 0,
			},
			{
				product_slug: 'price',
				title: 'Title',
				price: '$100.00',
			},
			{
				product_slug: 'installed',
				title: 'Title',
				price: 0,
				status: 'installed',
			},
		],
	};

	it( 'Features data normalizer', () => {
		const normalizedData = normalizeFeaturesData( rawFeatures );

		expect( normalizedData ).toEqual( expectedFeaturesData );
	} );

	it( 'Setup wizard data normalizer', () => {
		const expectedData = {
			features: expectedFeaturesData,
		};

		const normalizedData = normalizeSetupWizardData( {
			features: rawFeatures,
		} );

		expect( normalizedData ).toEqual( expectedData );
	} );
} );
