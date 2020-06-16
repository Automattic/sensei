import { render, fireEvent } from '@testing-library/react';

import FeatureDescription from './feature-description';

describe( '<FeatureDescription />', () => {
	it( 'Should render with the description', () => {
		const { container } = render( <FeatureDescription excerpt="test" /> );

		expect( container.firstChild ).toMatchInlineSnapshot( 'test' );
	} );

	it( 'Should render with learn more link', () => {
		const link = 'https://senseilms.com/';
		const { queryByText } = render(
			<FeatureDescription excerpt="test" link={ link } />
		);

		const href = queryByText( 'Learn more' ).getAttribute( 'href' );

		expect( href ).toEqual( link );
	} );

	it( 'Should render WooCommerce description with observation for one plugin', () => {
		const { queryByText } = render(
			<FeatureDescription
				slug="woocommerce"
				excerpt="test"
				selectedFeatures={ [
					{ rawTitle: 'Title 1', wccom_product_id: '123' },
					{ rawTitle: 'Title 2' },
				] }
			/>
		);

		expect( queryByText( /updates for Title 1\./ ) ).toBeTruthy();
	} );

	it( 'Should render WooCommerce description with observation for two plugins', () => {
		const { queryByText } = render(
			<FeatureDescription
				slug="woocommerce"
				excerpt="test"
				selectedFeatures={ [
					{ rawTitle: 'Title 1', wccom_product_id: '123' },
					{ rawTitle: 'Title 2', wccom_product_id: '456' },
				] }
			/>
		);

		expect(
			queryByText( /updates for Title 1 and Title 2\./ )
		).toBeTruthy();
	} );

	it( 'Should log event when clicking learn more link', () => {
		window.sensei_log_event = jest.fn();
		const { queryByText } = render(
			<FeatureDescription
				excerpt="test"
				link="https://senseilms.com/"
				slug="plugin_slug"
			/>
		);

		fireEvent.click( queryByText( 'Learn more' ) );

		expect( window.sensei_log_event ).toHaveBeenCalledWith(
			'setup_wizard_features_learn_more',
			{
				slug: 'plugin_slug',
			}
		);

		delete window.sensei_log_event;
	} );
} );
