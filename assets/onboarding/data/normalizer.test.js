import { normalizeSetupWizardData } from './normalizer';

describe( 'Data normalizer', () => {
	it( 'Setup wizard data normalizer', () => {
		const expectedData = {
			features: {
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
			},
		};

		const normalizedData = normalizeSetupWizardData( {
			features: {
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
			},
		} );

		expect( normalizedData ).toEqual( expectedData );
	} );
} );
